<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

class SecurityControllerTest extends TestCase
{
    public function testSecurityControllerExists(): void
    {
        $this->assertTrue(class_exists(\App\Controller\SecurityController::class));
    }

    public function testLoginMethodExists(): void
    {
        $reflection = new \ReflectionClass(\App\Controller\SecurityController::class);
        $this->assertTrue($reflection->hasMethod('login'));
    }

    public function testLogoutMethodExists(): void
    {
        $reflection = new \ReflectionClass(\App\Controller\SecurityController::class);
        $this->assertTrue($reflection->hasMethod('logout'));
    }
}