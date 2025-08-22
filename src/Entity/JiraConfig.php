<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'jira_config')]
class JiraConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $baseUrl = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null; // Plain text as per requirements

    #[ORM\Column]
    private ?bool $verifyTls = true;

    #[ORM\Column(length: 500)]
    private ?string $exportBaseDir = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isVerifyTls(): ?bool
    {
        return $this->verifyTls;
    }

    public function setVerifyTls(bool $verifyTls): static
    {
        $this->verifyTls = $verifyTls;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getExportBaseDir(): ?string
    {
        return $this->exportBaseDir;
    }

    public function setExportBaseDir(string $exportBaseDir): static
    {
        $this->exportBaseDir = $exportBaseDir;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
