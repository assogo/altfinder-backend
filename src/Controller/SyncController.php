<?php
namespace App\Controller;
use App\Service\AlternanceExcelService;
use App\Service\AlternanceMailerService;
use App\Service\FranceTravailService;
use App\Repository\JobRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/internal/sync')]
class SyncController
{
    public function __construct(
        private FranceTravailService    $franceTravailService,
        private JobRepository           $jobRepository,
        private AlternanceExcelService  $excelService,
        private AlternanceMailerService $mailerService,
        private string $syncSecret,
    ) {
    }

    #[Route('', name: 'internal_sync', methods: ['GET'])]
    public function sync(Request $request): JsonResponse
    {
        if (empty($this->syncSecret) || $request->query->get('secret') !== $this->syncSecret) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

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
    }
}