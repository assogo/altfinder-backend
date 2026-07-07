<?php
namespace App\Controller;
use App\Service\AlternanceExcelService;
use App\Service\AlternanceMailerService;
use App\Service\FranceTravailService;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/internal')]
class SyncController
{
    public function __construct(
        private FranceTravailService    $franceTravailService,
        private JobRepository           $jobRepository,
        private AlternanceExcelService  $excelService,
        private AlternanceMailerService $mailerService,
        private EntityManagerInterface  $entityManager,
        private string $syncSecret,
    ) {
    }

    #[Route('/sync', name: 'internal_sync', methods: ['GET'])]
    public function sync(Request $request): JsonResponse
    {
        if (empty($this->syncSecret) || $request->query->get('secret') !== $this->syncSecret) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->franceTravailService->fetchAndStoreAlternances();
            if ($result['created'] > 0) {
                $since   = new \DateTimeImmutable('-1 hour');
                $newJobs = $this->jobRepository->findCreatedSince($since);
                if (!empty($newJobs)) {
                    $excelPath = $this->excelService->appendJobs($newJobs);
                    $this->mailerService->sendDailyReport($excelPath, count($newJobs));
                }
            }
            return new JsonResponse([
                'message' => 'Synchronisation effectuée',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    #[Route('/migrate', name: 'internal_migrate', methods: ['GET'])]
    public function migrate(Request $request): JsonResponse
    {
        if (empty($this->syncSecret) || $request->query->get('secret') !== $this->syncSecret) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'ALTER TABLE job ADD COLUMN IF NOT EXISTS contact VARCHAR(255) DEFAULT NULL'
        );

        return new JsonResponse(['message' => 'Migration effectuée']);
    }
}