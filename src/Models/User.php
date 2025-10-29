<?php

namespace App\Models;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    private ?int $id = null;
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?array $roles = [];
    private ?string $identifier = null;
    private ?string $password = null;
    private ?string $login = null;

    public function __construct(
        int $id,
        string $nom,
        string $prenom,
        string $roles,
        string $login
    ) {
        $this->id = $id;
        $this->identifier = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->login = $login;
        $this->setRoles($roles);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setIdentifier(?string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function setRoles(string $roles): static
    {
        $this->roles = $this->assignRoles($roles);

        return $this;
    }

    public function getRoles(): array
    {
        if (count($this->roles) == 0) {
            return ['ROLE_USER'];
        }
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function eraseCredentials(): void {}

    private function assignRoles(string $roles): array
    {
        return match ($roles) {
            'user' => ['ROLE_USER'],
            'admin' => ['ROLE_ADMIN'],
            'default' => ['ROLE_ADMIN']
        };
    }
}
