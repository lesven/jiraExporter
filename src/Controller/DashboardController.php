<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\JiraClient;
use App\Service\CsvExporter;
use App\Repository\JobLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em, JobLogRepository $jobLogRepository): Response
    {
        $jobs = $em->getRepository(Job::class)->findBy([], ['id' => 'ASC']);
        $recentLogs = $jobLogRepository->findBy([], ['startedAt' => 'DESC'], 10);

        return $this->render('dashboard/index.html.twig', [
            'jobs' => $jobs,
            'recent_logs' => $recentLogs,
        ]);
    }
}
