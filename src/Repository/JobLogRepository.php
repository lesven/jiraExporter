<?php

namespace App\Repository;

use App\Entity\JobLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobLog::class);
    }

    /**
     * Find recent logs with limit
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs for specific job
     */
    public function findByJobId(int $jobId, int $limit = 10): array
    {
        return $this->createQueryBuilder('j')
            ->where('j.jobId = :jobId')
            ->setParameter('jobId', $jobId)
            ->orderBy('j.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
