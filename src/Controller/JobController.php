<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\JobLog;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use App\Form\JobType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/jobs')]
class JobController extends AbstractController
{
    #[Route('/', name: 'job_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $jobs = $em->getRepository(Job::class)->findBy([], ['id' => 'ASC']);

        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/new', name: 'job_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $job = new Job();
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($job);
            $em->flush();

            $this->addFlash('success', 'Job wurde erfolgreich erstellt.');
            return $this->redirectToRoute('job_show', ['id' => $job->getId()]);
        }

        return $this->render('job/new.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'job_show', methods: ['GET'])]
    public function show(Job $job, EntityManagerInterface $em): Response
    {
        $logs = $em->getRepository(JobLog::class)->findBy(
            ['jobId' => $job->getId()],
            ['startedAt' => 'DESC'],
            10
        );

        return $this->render('job/show.html.twig', [
            'job' => $job,
            'logs' => $logs,
        ]);
    }

    #[Route('/{id}/edit', name: 'job_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Job $job, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Job wurde erfolgreich aktualisiert.');
            return $this->redirectToRoute('job_show', ['id' => $job->getId()]);
        }

        return $this->render('job/edit.html.twig', [
            'job' => $job,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'job_delete', methods: ['POST'])]
    public function delete(Request $request, Job $job, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$job->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($job);
            $em->flush();
            $this->addFlash('success', 'Job wurde erfolgreich gelöscht.');
        }

        return $this->redirectToRoute('job_index');
    }

    #[Route('/{id}/run', name: 'job_run', methods: ['POST'])]
    public function run(Job $job, JiraClient $jiraClient, CsvExporter $csvExporter, EntityManagerInterface $em): Response
    {
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
            $filename = 'job_' . $job->getId() . '_' . date('Y-m-d_H-i-s') . '.csv';
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

            $this->addFlash('error', 'Export fehlgeschlagen: ' . $e->getMessage());
            return $this->redirectToRoute('job_show', ['id' => $job->getId()]);
        }
    }
}
