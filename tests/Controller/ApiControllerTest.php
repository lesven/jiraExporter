<?php

namespace App\Tests\Controller;

use App\Controller\ApiController;
use App\Entity\Job;
use App\Entity\JobLog;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends TestCase
{
    private ApiController $controller;
    private EntityManagerInterface $entityManager;
    private EntityRepository $jobRepository;
    private JiraClient $jiraClient;
    private CsvExporter $csvExporter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jobRepository = $this->createMock(EntityRepository::class);
        $this->jiraClient = $this->createMock(JiraClient::class);
        $this->csvExporter = $this->createMock(CsvExporter::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($this->jobRepository);

        $this->controller = new ApiController();
    }

    public function testRunJobReturnsNotFoundWhenJobDoesNotExist(): void
    {
        $this->jobRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->runJob(
            999,
            $this->jiraClient,
            $this->csvExporter,
            $this->entityManager
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Job not found', $response->getContent());
    }

    public function testRunJobHandlesException(): void
    {
        $job = new Job();
        $job->setName('Test Job');
        $job->setJql('project = TEST');

        $reflection = new \ReflectionClass($job);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($job, 1);

        $this->jobRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('searchIssues')
            ->willThrowException(new \Exception('Connection failed'));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(JobLog::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->runJob(
            1,
            $this->jiraClient,
            $this->csvExporter,
            $this->entityManager
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Export failed', $response->getContent());
    }
}