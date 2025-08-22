<?php

namespace App\Tests\Controller;

use App\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends TestCase
{
    private HealthController $controller;
    private EntityManagerInterface $entityManager;
    private Connection $connection;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->controller = new HealthController();
    }

    public function testHealthCheckSuccess(): void
    {
        $this->connection->expects($this->once())
            ->method('connect')
            ->willReturn(true);

        $response = $this->controller->health($this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
    }

    public function testHealthCheckDatabaseFailure(): void
    {
        $this->connection->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('Database connection failed'));

        $response = $this->controller->health($this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Error: Database connection failed', $response->getContent());
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
    }
}