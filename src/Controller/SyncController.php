<?php

namespace App\Controller;

use App\Service\FranceTravailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/internal/sync')]
class SyncController
{
    public function __construct(
        private FranceTravailService $franceTravailService,
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

        return new JsonResponse([
            'message' => 'Synchronisation effectuée',
            'result' => $result,
        ]);
    }
}
