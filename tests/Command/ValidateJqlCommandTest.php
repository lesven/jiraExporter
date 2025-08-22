<?php

namespace App\Tests\Command;

use App\Command\ValidateJqlCommand;
use App\Entity\Job;
use App\Service\JiraClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateJqlCommandTest extends TestCase
{
    private ValidateJqlCommand $command;
    private EntityManagerInterface $entityManager;
    private JiraClient $jiraClient;
    private EntityRepository $repository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jiraClient = $this->createMock(JiraClient::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($this->repository);

        $this->command = new ValidateJqlCommand($this->entityManager, $this->jiraClient);

        $application = new Application();
        $application->add($this->command);
        
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithoutJobId(): void
    {
        $this->commandTester->execute([]);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Job ID ist erforderlich', $this->commandTester->getDisplay());
    }

    public function testExecuteWithNonExistentJob(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->commandTester->execute(['--job-id' => '999']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Job mit ID 999 nicht gefunden', $this->commandTester->getDisplay());
    }

    public function testExecuteWithValidJql(): void
    {
        $job = $this->createJob(1, 'Test Job', 'project = TEST');
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('validateJql')
            ->with('project = TEST')
            ->willReturn(true);

        $this->commandTester->execute(['--job-id' => '1']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Validiere JQL für Job: Test Job', $output);
        $this->assertStringContainsString('JQL: project = TEST', $output);
        $this->assertStringContainsString('JQL ist gültig', $output);
    }

    public function testExecuteWithInvalidJql(): void
    {
        $job = $this->createJob(2, 'Invalid Job', 'invalid jql syntax');
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('validateJql')
            ->with('invalid jql syntax')
            ->willReturn(false);

        $this->commandTester->execute(['--job-id' => '2']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Validiere JQL für Job: Invalid Job', $output);
        $this->assertStringContainsString('JQL: invalid jql syntax', $output);
        $this->assertStringContainsString('JQL ist ungültig', $output);
    }

    public function testExecuteWithException(): void
    {
        $job = $this->createJob(3, 'Exception Job', 'project = TEST');
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('validateJql')
            ->with('project = TEST')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $this->commandTester->execute(['--job-id' => '3']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Validierung fehlgeschlagen: Connection failed', $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('app:validate-jql', $this->command->getName());
        $this->assertEquals('Validate JQL query for a job', $this->command->getDescription());
        
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('job-id'));
        $this->assertTrue($definition->getOption('job-id')->isValueRequired());
    }

    private function createJob(int $id, string $name, string $jql): Job
    {
        $job = new Job();
        $job->setName($name);
        $job->setJql($jql);

        // Set ID via reflection since it's typically set by Doctrine
        $reflection = new \ReflectionClass($job);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($job, $id);

        return $job;
    }
}