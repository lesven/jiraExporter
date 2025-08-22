<?php

namespace App\Tests\Repository;

use App\Entity\JobLog;
use App\Repository\JobLogRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class JobLogRepositoryTest extends TestCase
{
    private JobLogRepository $repository;
    private EntityManager $entityManager;
    private QueryBuilder $queryBuilder;
    private Query $query;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->repository = $this->getMockBuilder(JobLogRepository::class)
            ->setConstructorArgs([$this->createMock(\Doctrine\Persistence\ManagerRegistry::class)])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    public function testFindRecent(): void
    {
        $expectedLogs = [
            $this->createJobLog(1, 'Success'),
            $this->createJobLog(2, 'Failed')
        ];

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLogs);

        $result = $this->repository->findRecent();

        $this->assertSame($expectedLogs, $result);
    }

    public function testFindRecentWithCustomLimit(): void
    {
        $expectedLogs = [$this->createJobLog(1, 'Success')];

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLogs);

        $result = $this->repository->findRecent(5);

        $this->assertSame($expectedLogs, $result);
    }

    public function testFindByJobId(): void
    {
        $jobId = 123;
        $expectedLogs = [
            $this->createJobLog(1, 'Success', $jobId),
            $this->createJobLog(2, 'Failed', $jobId)
        ];

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('j.jobId = :jobId')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('jobId', $jobId)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLogs);

        $result = $this->repository->findByJobId($jobId);

        $this->assertSame($expectedLogs, $result);
    }

    public function testFindByJobIdWithCustomLimit(): void
    {
        $jobId = 456;
        $expectedLogs = [$this->createJobLog(1, 'Success', $jobId)];

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('j.jobId = :jobId')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('jobId', $jobId)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(3)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLogs);

        $result = $this->repository->findByJobId($jobId, 3);

        $this->assertSame($expectedLogs, $result);
    }

    public function testFindRecentReturnsEmptyArray(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $result = $this->repository->findRecent();

        $this->assertSame([], $result);
    }

    public function testFindByJobIdReturnsEmptyArray(): void
    {
        $jobId = 999;

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('j.jobId = :jobId')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('jobId', $jobId)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('j.startedAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $result = $this->repository->findByJobId($jobId);

        $this->assertSame([], $result);
    }

    private function createJobLog(int $id, string $status, ?int $jobId = null): JobLog
    {
        $jobLog = new JobLog();
        $jobLog->setJobId($jobId ?? 1);
        $jobLog->setStatus($status);
        $jobLog->setStartedAt(new \DateTimeImmutable());
        
        // Set ID via reflection since it's typically set by Doctrine
        $reflection = new \ReflectionClass($jobLog);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($jobLog, $id);
        
        return $jobLog;
    }
}