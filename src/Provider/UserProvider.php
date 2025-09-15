<?php

namespace App\Provider;

use App\Models\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository) {}

    public function supportsClass(string $class): bool
    {
        return $class == User::class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->userRepository->loadUserByIdentifier($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) throw new UnsupportedUserException();
        return $this->userRepository->loadUserByIdentifier(
            $user->getUserIdentifier()
        );
    }
}
