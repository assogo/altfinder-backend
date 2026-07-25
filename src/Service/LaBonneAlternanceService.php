<?php

namespace App\Service;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LaBonneAlternanceService
{
    private const API_URL = 'https://api.apprentissage.beta.gouv.fr/api/job/v1/search';

    private const ROME_CODES = [
        'M1805',
        'M1810',
        'M1806',
    ];

    public function __construct(
        private HttpClientInterface    $httpClient,
        private JobRepository          $jobRepository,
        private EntityManagerInterface $entityManager,
        private string $apiKey,
    ) {}

    public function fetchAndStoreAlternances(): array
    {
        $created = 0;
        $updated = 0;

        $this->entityManager->clear();

        $response = $this->httpClient->request('GET', self::API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept'        => 'application/json',
            ],
            'query' => [
                'latitude'  => 45.75,
                'longitude' => 4.85,
                'radius'    => 50,
                'romes'     => implode(',', self::ROME_CODES),
            ],
        ]);

        $data = $response->toArray(false);
        $jobs = $data['jobs'] ?? [];

        foreach ($jobs as $offer) {
            // Ignorer les offres France Travail (déjà récupérées via FranceTravailService)
            if (($offer['identifier']['partner_label'] ?? '') === 'France Travail') {
                continue;
            }

            $externalId = 'lba_' . ($offer['identifier']['partner_job_id'] ?? uniqid());
            $existing   = $this->jobRepository->findOneByExternalId($externalId);
            $isNew      = $existing === null;

            $job = $isNew ? new Job() : $existing;

            if ($isNew) {
                $job->setExternalId($externalId);
                $job->setSource('la_bonne_alternance');
            }

            $this->hydrateJob($job, $offer);
            $this->entityManager->persist($job);

            $isNew ? $created++ : $updated++;
        }

        $this->entityManager->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    private function hydrateJob(Job $job, array $offer): void
    {
        $job->setTitle($offer['offer']['title']                      ?? 'Sans titre');
        $job->setCompany($offer['workplace']['name']                 ?? null);
        $job->setLocation($offer['workplace']['location']['address'] ?? null);
        $job->setDescription($offer['offer']['description']         ?? null);
        $job->setContractType('Alternance');
        $job->setIsAlternance(true);
        $job->setCategory('developpeur');
        $job->setUrl($offer['apply']['url']                          ?? null);
        $job->setContact($offer['apply']['phone']                    ?? null);

        $publishedAt = isset($offer['offer']['publication']['creation'])
            ? new \DateTimeImmutable($offer['offer']['publication']['creation'])
            : new \DateTimeImmutable();
        $job->setPublishedAt($publishedAt);

        if (!empty($offer['offer']['publication']['expiration'])) {
            $job->setExpiresAt(new \DateTimeImmutable($offer['offer']['publication']['expiration']));
        }

        $job->refreshStatus();
    }
}
