<?php

namespace App\Models;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private array $roles = [];
    private ?string $identifier;

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

    public function eraseCredentials(): void
    {
        $this->identifier = null;
    }
}
