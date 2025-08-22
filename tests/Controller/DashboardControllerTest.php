<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

class DashboardControllerTest extends TestCase
{
    public function testDashboardControllerExists(): void
    {
        $this->assertTrue(class_exists(\App\Controller\DashboardController::class));
    }

    public function testIndexMethodExists(): void
    {
        $reflection = new \ReflectionClass(\App\Controller\DashboardController::class);
        $this->assertTrue($reflection->hasMethod('index'));
    }
}