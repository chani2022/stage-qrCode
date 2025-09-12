<?php

namespace App\Provider;

use App\Models\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private Connection $connection) {}

    public function supportsClass(string $class): bool
    {
        return $class == User::class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = new User($this->connection);

        return $user->findByIdentifier($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {

        if (!$user instanceof User) throw new UnsupportedUserException();

        return $user->findByIdentifier($user->getUserIdentifier());
    }
}
