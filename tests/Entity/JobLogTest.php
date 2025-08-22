<?php

namespace App\Tests\Entity;

use App\Entity\JobLog;
use PHPUnit\Framework\TestCase;

class JobLogTest extends TestCase
{
    public function testDefaultValues()
    {
        $log = new JobLog();
        $this->assertNull($log->getId());
        $this->assertNull($log->getJobId());
        $this->assertNull($log->getJobName());
        $this->assertNull($log->getStartedAt());
        $this->assertNull($log->getFinishedAt());
        $this->assertNull($log->getStatus());
        $this->assertNull($log->getIssueCount());
        $this->assertNull($log->getErrorMessage());
        $this->assertNull($log->getDurationInSeconds());
    }

    public function testSettersAndGetters()
    {
        $log = new JobLog();
        $started = new \DateTimeImmutable('-1 hour');
        $finished = new \DateTimeImmutable();
        $log->setJobId(42)
            ->setJobName('TestJob')
            ->setStartedAt($started)
            ->setFinishedAt($finished)
            ->setStatus('ok')
            ->setIssueCount(10)
            ->setErrorMessage('Fehler');

        $this->assertEquals(42, $log->getJobId());
        $this->assertEquals('TestJob', $log->getJobName());
        $this->assertEquals($started, $log->getStartedAt());
        $this->assertEquals($finished, $log->getFinishedAt());
        $this->assertEquals('ok', $log->getStatus());
        $this->assertEquals(10, $log->getIssueCount());
        $this->assertEquals('Fehler', $log->getErrorMessage());
        $this->assertEquals($finished->getTimestamp() - $started->getTimestamp(), $log->getDurationInSeconds());
    }

    public function testDurationNullIfNotFinished()
    {
        $log = new JobLog();
        $log->setStartedAt(new \DateTimeImmutable());
        $this->assertNull($log->getDurationInSeconds());
    }
}
