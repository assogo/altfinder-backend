<?php

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    public function findOneByExternalId(string $externalId): ?Job
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * @param array{
     *   keyword?: string,
     *   location?: string,
     *   category?: string,
     *   alternanceOnly?: bool,
     *   status?: string
     * } $filters
     *
     * @return array{items: Job[], total: int}
     */
    public function findByFilters(array $filters, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('j');

        if (!empty($filters['keyword'])) {
            $qb->andWhere('j.title LIKE :keyword OR j.company LIKE :keyword OR j.description LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['location'])) {
            $qb->andWhere('j.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('j.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['alternanceOnly'])) {
            $qb->andWhere('j.isAlternance = true');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('j.status = :status')
                ->setParameter('status', $filters['status']);
        }

        $qb->orderBy('j.publishedAt', 'DESC');

        $countQb = (clone $qb)->select('COUNT(j.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return Job[]
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les offres qui n'ont pas été ré-évaluées récemment,
     * pour que le cron puisse rafraîchir leur statut (méthode 1 ou 3).
     *
     * @return Job[]
     */
    public function findStaleJobs(int $olderThanMinutes = 30, int $limit = 500): array
    {
        $threshold = new \DateTimeImmutable(sprintf('-%d minutes', $olderThanMinutes));

        return $this->createQueryBuilder('j')
            ->andWhere('j.updatedAt < :threshold')
            ->andWhere('j.status != :expired')
            ->setParameter('threshold', $threshold)
            ->setParameter('expired', Job::STATUS_EXPIRED)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}