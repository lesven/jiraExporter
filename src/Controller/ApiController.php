<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\JobLog;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/jobs/{id}/run', name: 'api_job_run', methods: ['POST'])]
    public function runJob(
        int $id,
        JiraClient $jiraClient,
        CsvExporter $csvExporter,
        EntityManagerInterface $em
    ): Response {
        $job = $em->getRepository(Job::class)->find($id);
        
        if (!$job) {
            return new Response('Job not found', 404);
        }

        $log = new JobLog();
        $log->setJobId($job->getId());
        $log->setJobName($job->getName());
        $log->setStartedAt(new \DateTimeImmutable());

        try {
            // Fetch issues from Jira
            $result = $jiraClient->searchIssues($job->getJql());
            $issues = $result['issues'];
            $total = $result['total'];

            // Export to CSV
            $filename = 'api_job_' . $job->getId() . '_' . date('Y-m-d_H-i-s') . '.csv';
            $filePath = $csvExporter->exportToCsv($issues, null, $filename);

            // Log success
            $log->setFinishedAt(new \DateTimeImmutable());
            $log->setStatus('ok');
            $log->setIssueCount($total);

            $em->persist($log);
            $em->flush();

            // Return file as download
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($filePath)
            );
            
            return $response;

        } catch (\Exception $e) {
            // Log error
            $log->setFinishedAt(new \DateTimeImmutable());
            $log->setStatus('error');
            $log->setErrorMessage($e->getMessage());

            $em->persist($log);
            $em->flush();

            return new Response('Export failed', 500);
        }
    }
}
