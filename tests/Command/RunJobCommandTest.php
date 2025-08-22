<?php

namespace App\Tests\Command;

use App\Command\RunJobCommand;
use App\Entity\Job;
use App\Entity\JobLog;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunJobCommandTest extends TestCase
{
    private RunJobCommand $command;
    private EntityManagerInterface $entityManager;
    private JiraClient $jiraClient;
    private CsvExporter $csvExporter;
    private EntityRepository $repository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jiraClient = $this->createMock(JiraClient::class);
        $this->csvExporter = $this->createMock(CsvExporter::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Job::class)
            ->willReturn($this->repository);

        $this->command = new RunJobCommand(
            $this->entityManager,
            $this->jiraClient,
            $this->csvExporter
        );

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

    public function testExecuteSuccessfully(): void
    {
        $job = $this->createJob(1, 'Test Job', 'project = TEST');
        $issues = [
            ['key' => 'TEST-1', 'fields' => ['summary' => 'Issue 1']],
            ['key' => 'TEST-2', 'fields' => ['summary' => 'Issue 2']]
        ];
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('searchIssues')
            ->with('project = TEST')
            ->willReturn(['issues' => $issues, 'total' => 2]);

        $this->csvExporter->expects($this->once())
            ->method('exportToCsv')
            ->with($issues, null, null)
            ->willReturn('/tmp/export.csv');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (JobLog $log) {
                return $log->getJobId() === 1
                    && $log->getJobName() === 'Test Job'
                    && $log->getStatus() === 'ok'
                    && $log->getIssueCount() === 2
                    && $log->getStartedAt() instanceof \DateTimeImmutable
                    && $log->getFinishedAt() instanceof \DateTimeImmutable;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->execute(['--job-id' => '1']);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Starte Export für Job: Test Job', $output);
        $this->assertStringContainsString('JQL: project = TEST', $output);
        $this->assertStringContainsString('Gefundene Issues: 2', $output);
        $this->assertStringContainsString('Export erfolgreich abgeschlossen', $output);
        $this->assertStringContainsString('Datei: /tmp/export.csv', $output);
    }

    public function testExecuteWithCustomOptions(): void
    {
        $job = $this->createJob(2, 'Custom Job', 'project = CUSTOM');
        $issues = [['key' => 'CUSTOM-1', 'fields' => ['summary' => 'Custom Issue']]];
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('searchIssues')
            ->with('project = CUSTOM')
            ->willReturn(['issues' => $issues, 'total' => 1]);

        $this->csvExporter->expects($this->once())
            ->method('exportToCsv')
            ->with($issues, '/custom/dir', 'custom.csv')
            ->willReturn('/custom/dir/custom.csv');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(JobLog::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->execute([
            '--job-id' => '2',
            '--export-dir' => '/custom/dir',
            '--filename' => 'custom.csv'
        ]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithJiraException(): void
    {
        $job = $this->createJob(3, 'Failed Job', 'invalid jql');
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('searchIssues')
            ->with('invalid jql')
            ->willThrowException(new \RuntimeException('Jira connection failed'));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (JobLog $log) {
                return $log->getJobId() === 3
                    && $log->getJobName() === 'Failed Job'
                    && $log->getStatus() === 'error'
                    && $log->getErrorMessage() === 'Jira connection failed'
                    && $log->getStartedAt() instanceof \DateTimeImmutable
                    && $log->getFinishedAt() instanceof \DateTimeImmutable;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->execute(['--job-id' => '3']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Export fehlgeschlagen: Jira connection failed', $this->commandTester->getDisplay());
    }

    public function testExecuteWithCsvExporterException(): void
    {
        $job = $this->createJob(4, 'Export Failed Job', 'project = TEST');
        $issues = [['key' => 'TEST-1', 'fields' => ['summary' => 'Issue 1']]];
        
        $this->repository->expects($this->once())
            ->method('find')
            ->with(4)
            ->willReturn($job);

        $this->jiraClient->expects($this->once())
            ->method('searchIssues')
            ->with('project = TEST')
            ->willReturn(['issues' => $issues, 'total' => 1]);

        $this->csvExporter->expects($this->once())
            ->method('exportToCsv')
            ->with($issues, null, null)
            ->willThrowException(new \RuntimeException('CSV export failed'));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (JobLog $log) {
                return $log->getStatus() === 'error'
                    && $log->getErrorMessage() === 'CSV export failed';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->execute(['--job-id' => '4']);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Export fehlgeschlagen: CSV export failed', $this->commandTester->getDisplay());
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('app:run-job', $this->command->getName());
        $this->assertEquals('Run a job and export to CSV', $this->command->getDescription());
        
        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasOption('job-id'));
        $this->assertTrue($definition->getOption('job-id')->isValueRequired());
        
        $this->assertTrue($definition->hasOption('export-dir'));
        $this->assertFalse($definition->getOption('export-dir')->isValueRequired());
        
        $this->assertTrue($definition->hasOption('filename'));
        $this->assertFalse($definition->getOption('filename')->isValueRequired());
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