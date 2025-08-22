<?php

namespace App\Command;

use App\Entity\Job;
use App\Service\JiraClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:validate-jql',
    description: 'Validate JQL query for a job',
)]
class ValidateJqlCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JiraClient $jiraClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('job-id', null, InputOption::VALUE_REQUIRED, 'Job ID to validate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $jobId = $input->getOption('job-id');

        if (!$jobId) {
            $io->error('Job ID ist erforderlich. Nutzen Sie --job-id=<ID>');
            return Command::FAILURE;
        }

        $job = $this->entityManager->getRepository(Job::class)->find($jobId);
        if (!$job) {
            $io->error('Job mit ID ' . $jobId . ' nicht gefunden.');
            return Command::FAILURE;
        }

        $io->info('Validiere JQL für Job: ' . $job->getName());
        $io->info('JQL: ' . $job->getJql());

        try {
            $isValid = $this->jiraClient->validateJql($job->getJql());
            
            if ($isValid) {
                $io->success('JQL ist gültig.');
                return Command::SUCCESS;
            } else {
                $io->error('JQL ist ungültig.');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Validierung fehlgeschlagen: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
