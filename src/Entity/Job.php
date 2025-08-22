<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jobs')]
/**
 * Repräsentiert einen Export-Job für Jira-Issues.
 * Enthält Name, JQL, Beschreibung und Zeitstempel.
 */
class Job
{
    /**
     * Eindeutige ID des Jobs (Primärschlüssel).
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Name des Jobs (z.B. "Sprint Export").
     *
     * @var string|null
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * JQL-Query für die Jira-Suche.
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text')]
    private ?string $jql = null;

    /**
     * Beschreibung des Jobs (optional).
     *
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Erstellungszeitpunkt des Jobs.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Zeitpunkt der letzten Änderung des Jobs.
     *
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Konstruktor: Setzt Erstellungs- und Änderungsdatum auf den aktuellen Zeitpunkt.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Gibt die ID des Jobs zurück.
     *
     * @return int|null Die eindeutige ID des Jobs
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt den Namen des Jobs zurück.
     *
     * @return string|null Der Name des Jobs
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Setzt den Namen des Jobs und aktualisiert das Änderungsdatum.
     *
     * @param string $name Der neue Name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt die JQL-Query zurück.
     *
     * @return string|null Die JQL-Query
     */
    public function getJql(): ?string
    {
        return $this->jql;
    }

    /**
     * Setzt die JQL-Query und aktualisiert das Änderungsdatum.
     *
     * @param string $jql Die neue JQL-Query
     * @return static
     */
    public function setJql(string $jql): static
    {
        $this->jql = $jql;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt die Beschreibung des Jobs zurück.
     *
     * @return string|null Die Beschreibung
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Setzt die Beschreibung und aktualisiert das Änderungsdatum.
     *
     * @param string|null $description Die neue Beschreibung
     * @return static
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt das Erstellungsdatum zurück.
     *
     * @return \DateTimeImmutable|null Das Erstellungsdatum
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Gibt das Datum der letzten Änderung zurück.
     *
     * @return \DateTimeImmutable|null Zeitpunkt der letzten Änderung
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
