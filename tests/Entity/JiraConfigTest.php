<?php

namespace App\Tests\Entity;

use App\Entity\JiraConfig;
use PHPUnit\Framework\TestCase;

class JiraConfigTest extends TestCase
{
    public function testDefaultValues()
    {
        $config = new JiraConfig();
        $this->assertNull($config->getId());
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getUsername());
        $this->assertNull($config->getPassword());
        $this->assertTrue($config->isVerifyTls());
        $this->assertNull($config->getExportBaseDir());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
    }

    public function testSettersAndGetters()
    {
        $config = new JiraConfig();
        $config->setBaseUrl('https://jira.example.com')
            ->setUsername('user')
            ->setPassword('pass')
            ->setVerifyTls(false)
            ->setExportBaseDir('/tmp/export');

        $this->assertEquals('https://jira.example.com', $config->getBaseUrl());
        $this->assertEquals('user', $config->getUsername());
        $this->assertEquals('pass', $config->getPassword());
        $this->assertFalse($config->isVerifyTls());
        $this->assertEquals('/tmp/export', $config->getExportBaseDir());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
    }

    public function testUpdatedAtChangesOnSetters()
    {
        $config = new JiraConfig();
        $oldUpdatedAt = $config->getUpdatedAt();
        sleep(1);
        $config->setBaseUrl('https://jira.example.com');
        $this->assertNotEquals($oldUpdatedAt, $config->getUpdatedAt());
    }
}
