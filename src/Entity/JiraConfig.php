<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jira_config')]
/**
 * Repräsentiert die Konfiguration für die Jira-Anbindung.
 * Enthält Basis-URL, Zugangsdaten, TLS-Optionen und Exportverzeichnis.
 */
class JiraConfig
{
    /**
     * Eindeutige ID der Konfiguration (Primärschlüssel).
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Basis-URL des Jira-Servers (z.B. https://jira.example.com).
     */
    #[ORM\Column(length: 255)]
    private ?string $baseUrl = null;

    /**
     * Benutzername für die Jira-Authentifizierung.
     */
    #[ORM\Column(length: 255)]
    private ?string $username = null;

    /**
     * Passwort für die Jira-Authentifizierung (im Klartext, laut Vorgabe).
     */
    #[ORM\Column(length: 255)]
    private ?string $password = null; // Plain text as per requirements

    /**
     * Gibt an, ob TLS-Zertifikate beim Verbindungsaufbau geprüft werden sollen.
     */
    #[ORM\Column]
    private ?bool $verifyTls = true;

    /**
     * Basisverzeichnis für den Export von Dateien.
     */
    #[ORM\Column(length: 500)]
    private ?string $exportBaseDir = null;

    /**
     * Zeitpunkt der letzten Änderung der Konfiguration.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Konstruktor: Setzt das Änderungsdatum auf den aktuellen Zeitpunkt.
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Gibt die ID der Konfiguration zurück.
     *
     * @return int|null Die eindeutige ID der Konfiguration
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt die Basis-URL des Jira-Servers zurück.
     *
     * @return string|null Die Basis-URL des Jira-Servers
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Setzt die Basis-URL des Jira-Servers und aktualisiert das Änderungsdatum.
     *
     * @param string $baseUrl Die neue Basis-URL
     * @return static
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt den Benutzernamen zurück.
     *
     * @return string|null Der Benutzername
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Setzt den Benutzernamen und aktualisiert das Änderungsdatum.
     *
     * @param string $username Der neue Benutzername
     * @return static
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt das Passwort zurück.
     *
     * @return string|null Das Passwort
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Setzt das Passwort und aktualisiert das Änderungsdatum.
     *
     * @param string $password Das neue Passwort
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt zurück, ob TLS-Zertifikate geprüft werden.
     *
     * @return bool|null true, wenn TLS geprüft wird
     */
    public function isVerifyTls(): ?bool
    {
        return $this->verifyTls;
    }

    /**
     * Setzt die TLS-Prüfung und aktualisiert das Änderungsdatum.
     *
     * @param bool $verifyTls true, wenn TLS geprüft werden soll
     * @return static
     */
    public function setVerifyTls(bool $verifyTls): static
    {
        $this->verifyTls = $verifyTls;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Gibt das Export-Basisverzeichnis zurück.
     *
     * @return string|null Das Export-Basisverzeichnis
     */
    public function getExportBaseDir(): ?string
    {
        return $this->exportBaseDir;
    }

    /**
     * Setzt das Export-Basisverzeichnis und aktualisiert das Änderungsdatum.
     *
     * @param string $exportBaseDir Das neue Export-Basisverzeichnis
     * @return static
     */
    public function setExportBaseDir(string $exportBaseDir): static
    {
        $this->exportBaseDir = $exportBaseDir;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
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
