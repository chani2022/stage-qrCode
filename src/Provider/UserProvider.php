<?php

namespace App\Provider;

use App\Models\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(private UserRepository $userRepository) {}

    /**
     * Permet de debuter le processus de fournisseur d'utilisateur.
     * 
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return $class == User::class;
    }

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->userRepository->findByIdentifier($identifier);
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) throw new UnsupportedUserException();
        return $this->userRepository->findByIdentifier(
            $user->getUserIdentifier()
        );
    }
}
