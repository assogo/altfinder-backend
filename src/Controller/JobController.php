<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/jobs')]
class JobController
{
    public function __construct(private JobRepository $jobRepository)
    {
    }

    #[Route('', name: 'api_jobs_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'keyword' => $request->query->get('keyword'),
            'location' => $request->query->get('location'),
            'category' => $request->query->get('category'), // dev | marketing | design
            'alternanceOnly' => $request->query->getBoolean('alternanceOnly'),
            'status' => $request->query->get('status'), // open | expired | probably_closed
        ];

        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 20)));

        $result = $this->jobRepository->findByFilters($filters, $page, $limit);

        return new JsonResponse([
            'data' => array_map(fn ($job) => $job->toArray(), $result['items']),
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    #[Route('/latest', name: 'api_jobs_latest', methods: ['GET'])]
    public function latest(Request $request): JsonResponse
    {
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $jobs = $this->jobRepository->findLatest($limit);

        return new JsonResponse([
            'data' => array_map(fn ($job) => $job->toArray(), $jobs),
        ]);
    }

    #[Route('/{id}', name: 'api_jobs_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $job = $this->jobRepository->find($id);

        if (!$job) {
            return new JsonResponse(['error' => 'Offre introuvable'], 404);
        }

        return new JsonResponse(['data' => $job->toArray()]);
    }
}