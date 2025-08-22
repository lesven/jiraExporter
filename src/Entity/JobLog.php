<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'job_logs')]
/**
 * Repräsentiert ein Log eines ausgeführten Jobs.
 * Enthält Status, Zeitstempel, Fehler und Issue-Anzahl.
 */
class JobLog
{
    /**
     * Eindeutige ID des Job-Logs (Primärschlüssel).
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * ID des zugehörigen Jobs.
     *
     * @var int|null
     */
    #[ORM\Column]
    private ?int $jobId = null;

    /**
     * Name des zugehörigen Jobs.
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $jobName = null;

    /**
     * Startzeitpunkt des Jobs.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startedAt = null;

    /**
     * Endzeitpunkt des Jobs (optional).
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    /**
     * Status des Jobs ('ok' oder 'error').
     *
     * @var string|null
     */
    #[ORM\Column(length: 20)]
    private ?string $status = null; // 'ok' or 'error'

    /**
     * Anzahl der exportierten Issues (optional).
     *
     * @var int|null
     */
    #[ORM\Column(nullable: true)]
    private ?int $issueCount = null;

    /**
     * Fehlermeldung, falls Fehler aufgetreten sind (optional).
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    /**
     * Gibt die ID des Job-Logs zurück.
     *
     * @return int|null Die eindeutige ID des Logs
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt die Job-ID zurück.
     *
     * @return int|null Die Job-ID
     */
    public function getJobId(): ?int
    {
        return $this->jobId;
    }

    /**
     * Setzt die Job-ID.
     *
     * @param int $jobId Die neue Job-ID
     * @return static
     */
    public function setJobId(int $jobId): static
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * Gibt den Job-Namen zurück.
     *
     * @return string|null Der Job-Name
     */
    public function getJobName(): ?string
    {
        return $this->jobName;
    }

    /**
     * Setzt den Job-Namen.
     *
     * @param string $jobName Der neue Job-Name
     * @return static
     */
    public function setJobName(string $jobName): static
    {
        $this->jobName = $jobName;
        return $this;
    }

    /**
     * Gibt den Startzeitpunkt zurück.
     *
     * @return \DateTimeImmutable|null Der Startzeitpunkt
     */
    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Setzt den Startzeitpunkt.
     *
     * @param \DateTimeImmutable $startedAt Der neue Startzeitpunkt
     * @return static
     */
    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * Gibt den Endzeitpunkt zurück.
     *
     * @return \DateTimeImmutable|null Der Endzeitpunkt
     */
    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    /**
     * Setzt den Endzeitpunkt.
     *
     * @param \DateTimeImmutable|null $finishedAt Der neue Endzeitpunkt
     * @return static
     */
    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    /**
     * Gibt den Status zurück.
     *
     * @return string|null Der Status ('ok' oder 'error')
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Setzt den Status.
     *
     * @param string $status Der neue Status ('ok' oder 'error')
     * @return static
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Gibt die Issue-Anzahl zurück.
     *
     * @return int|null Die Anzahl der Issues
     */
    public function getIssueCount(): ?int
    {
        return $this->issueCount;
    }

    /**
     * Setzt die Issue-Anzahl.
     *
     * @param int|null $issueCount Die neue Issue-Anzahl
     * @return static
     */
    public function setIssueCount(?int $issueCount): static
    {
        $this->issueCount = $issueCount;
        return $this;
    }

    /**
     * Gibt die Fehlermeldung zurück.
     *
     * @return string|null Die Fehlermeldung
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Setzt die Fehlermeldung.
     *
     * @param string|null $errorMessage Die neue Fehlermeldung
     * @return static
     */
    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * Gibt die Dauer des Jobs in Sekunden zurück.
     *
     * @return int|null Dauer in Sekunden oder null, wenn nicht beendet
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->startedAt || !$this->finishedAt) {
            return null;
        }
        return $this->finishedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
}
