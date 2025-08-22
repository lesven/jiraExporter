<?php

namespace App\Tests\Entity;

use App\Entity\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testDefaultValues()
    {
        $job = new Job();
        $this->assertNull($job->getId());
        $this->assertNull($job->getName());
        $this->assertNull($job->getJql());
        $this->assertNull($job->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getUpdatedAt());
    }

    public function testSettersAndGetters()
    {
        $job = new Job();
        $job->setName('Export Issues')
            ->setJql('project = TEST')
            ->setDescription('Exportiere alle Issues');

        $this->assertEquals('Export Issues', $job->getName());
        $this->assertEquals('project = TEST', $job->getJql());
        $this->assertEquals('Exportiere alle Issues', $job->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $job->getUpdatedAt());
    }

    public function testUpdatedAtChangesOnSetters()
    {
        $job = new Job();
        $oldUpdatedAt = $job->getUpdatedAt();
        sleep(1);
        $job->setName('Neuer Name');
        $this->assertNotEquals($oldUpdatedAt, $job->getUpdatedAt());
    }
}
