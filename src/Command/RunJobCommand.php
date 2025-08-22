<?php

namespace App\Command;

use App\Entity\Job;
use App\Entity\JobLog;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:run-job',
    description: 'Run a job and export to CSV',
)]
class RunJobCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JiraClient $jiraClient,
        private CsvExporter $csvExporter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('job-id', null, InputOption::VALUE_REQUIRED, 'Job ID to run')
            ->addOption('export-dir', null, InputOption::VALUE_OPTIONAL, 'Export directory override')
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'Filename override');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $jobId = $input->getOption('job-id');
        $exportDir = $input->getOption('export-dir');
        $filename = $input->getOption('filename');

        if (!$jobId) {
            $io->error('Job ID ist erforderlich. Nutzen Sie --job-id=<ID>');
            return Command::FAILURE;
        }

        $job = $this->entityManager->getRepository(Job::class)->find($jobId);
        if (!$job) {
            $io->error('Job mit ID ' . $jobId . ' nicht gefunden.');
            return Command::FAILURE;
        }

        $log = new JobLog();
        $log->setJobId($job->getId());
        $log->setJobName($job->getName());
        $log->setStartedAt(new \DateTimeImmutable());

        $io->info('Starte Export für Job: ' . $job->getName());
        $io->info('JQL: ' . $job->getJql());

        try {
            // Fetch issues from Jira
            $result = $this->jiraClient->searchIssues($job->getJql());
            $issues = $result['issues'];
            $total = $result['total'];

            $io->info('Gefundene Issues: ' . $total);

            // Export to CSV
            $filePath = $this->csvExporter->exportToCsv($issues, $exportDir, $filename);

            // Log success
            $log->setFinishedAt(new \DateTimeImmutable());
            $log->setStatus('ok');
            $log->setIssueCount($total);

            $this->entityManager->persist($log);
            $this->entityManager->flush();

            $io->success('Export erfolgreich abgeschlossen!');
            $io->info('Datei: ' . $filePath);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Log error
            $log->setFinishedAt(new \DateTimeImmutable());
            $log->setStatus('error');
            $log->setErrorMessage($e->getMessage());

            $this->entityManager->persist($log);
            $this->entityManager->flush();

            $io->error('Export fehlgeschlagen: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
