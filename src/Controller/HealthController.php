<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health')]
    public function health(EntityManagerInterface $em): Response
    {
        try {
            // Test database connection
            $em->getConnection()->connect();
            
            return new Response('OK', 200, ['Content-Type' => 'text/plain']);
        } catch (\Exception $e) {
            return new Response('Error: Database connection failed', 500, ['Content-Type' => 'text/plain']);
        }
    }
}
