<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
/**
 * Repräsentiert einen Benutzer der Anwendung.
 * Enthält Username, Rollen und Passwort.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Eindeutige ID des Benutzers (Primärschlüssel).
     *
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Benutzername (muss eindeutig sein).
     *
     * @var string|null
     */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    /**
     * Rollen des Benutzers (z.B. ROLE_ADMIN).
     *
     * @var array
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Passwort des Benutzers (gehasht).
     *
     * @var string|null
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Gibt die ID des Benutzers zurück.
     *
     * @return int|null Die eindeutige ID
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * Setzt den Benutzernamen.
     *
     * @param string $username Der neue Benutzername
     * @return static
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    /**
     * Gibt den eindeutigen Identifier des Benutzers zurück (für Security).
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    /**
     * Gibt die Rollen des Benutzers zurück.
     *
     * @return array Die Rollen
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_ADMIN
        $roles[] = 'ROLE_ADMIN';
        return array_unique($roles);
    }

    /**
     * Setzt die Rollen des Benutzers.
     *
     * @param array $roles Die neuen Rollen
     * @return static
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
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
     * Setzt das Passwort.
     *
     * @param string $password Das neue Passwort
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    /**
     * Löscht temporäre, sensible Daten des Benutzers (z.B. Plain-Password).
     *
     * @return void
     */
    public function eraseCredentials(): void
    {
        // Wenn temporäre, sensible Daten gespeichert werden, hier löschen
        // $this->plainPassword = null;
    }
}
