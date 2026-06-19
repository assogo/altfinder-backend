<?php

namespace App\Service;

use App\Entity\Job;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Intégration de l'API publique "Offres d'emploi" de France Travail
 * (ex Pôle Emploi). Doc : https://francetravail.io/produits-partages/documentation
 *
 * Authentification : OAuth2 client_credentials.
 * - Token endpoint : https://entreprise.francetravail.fr/connexion/oauth2/access_token?realm=/partenaire
 * - API endpoint   : https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search
 */
class FranceTravailService
{
    private const TOKEN_URL = 'https://entreprise.francetravail.fr/connexion/oauth2/access_token?realm=/partenaire';
    private const SEARCH_URL = 'https://api.francetravail.io/partenaire/offresdemploi/v2/offres/search';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
        private string $clientId,
        private string $clientSecret,
        private string $scope = 'api_offresdemploiv2 o2dsoffre',
    ) {
    }

    /**
     * Récupère un token d'accès et le met en cache jusqu'à expiration.
     *
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    private function getAccessToken(): string
    {
        return $this->cache->get('france_travail_access_token', function (ItemInterface $item) {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->scope,
                ],
            ]);

            $data = $response->toArray();

            $item->expiresAfter(max(60, ($data['expires_in'] ?? 1500) - 30));

            return $data['access_token'];
        });
    }

    /**
     * @param array $criteria ex: ['motsCles' => 'développeur', 'commune' => '75056', 'range' => '0-49']
     */
    public function fetchOffers(array $criteria = []): array
    {
        $token = $this->getAccessToken();

        $response = $this->httpClient->request('GET', self::SEARCH_URL, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'query' => $criteria,
        ]);

        $data = $response->toArray(false);

        return $data['resultats'] ?? [];
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function fetchAndStoreAlternances(array $keywords = ['développeur', 'marketing', 'design']): array
    {
        $created = 0;
        $updated = 0;

        foreach ($keywords as $keyword) {
            $offers = $this->fetchOffers([
                'motsCles' => $keyword . ' alternance',
                'range' => '0-49',
            ]);

            foreach ($offers as $offer) {
                $job = $this->jobRepository->findOneByExternalId($offer['id']);
                $isNew = $job === null;

                if ($isNew) {
                    $job = new Job();
                    $job->setExternalId($offer['id']);
                    $job->setSource('france_travail');
                }

                $this->hydrateJobFromOffer($job, $offer, $keyword);
                $job->refreshStatus();

                $this->entityManager->persist($job);

                $isNew ? $created++ : $updated++;
            }
        }

        $this->entityManager->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    private function hydrateJobFromOffer(Job $job, array $offer, string $searchedKeyword): void
    {
        $job->setTitle($offer['intitule'] ?? 'Sans titre');
        $job->setCompany($offer['entreprise']['nom'] ?? null);
        $job->setLocation($offer['lieuTravail']['libelle'] ?? null);
        $job->setDescription($offer['description'] ?? null);
        $job->setContractType($offer['typeContratLibelle'] ?? $offer['typeContrat'] ?? null);
        $job->setUrl($offer['origineOffre']['urlOrigine'] ?? null);
        $job->setCategory($this->guessCategory($searchedKeyword));
        $job->setIsAlternance(true);

        $publishedAt = isset($offer['dateCreation'])
            ? new \DateTimeImmutable($offer['dateCreation'])
            : new \DateTimeImmutable();
        $job->setPublishedAt($publishedAt);

        if (!empty($offer['dateActualisation']) && !empty($offer['dureeValidite'])) {
            $job->setExpiresAt(null);
        }
    }

    private function guessCategory(string $keyword): string
    {
        $keyword = mb_strtolower($keyword);

        return match (true) {
            str_contains($keyword, 'dev') => 'developpeur',
            str_contains($keyword, 'market') => 'marketing',
            str_contains($keyword, 'design') => 'design',
            default => 'autre',
        };
    }
}