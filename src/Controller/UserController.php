<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


final class UserController extends AbstractController
{
    #[IsGranted('ROLE_USER')]
    #[Route('/api/users', name: 'app_user')]
    public function index(UserRepository $userRepository): JsonResponse
    {
        return $this->json([
            'users' => $userRepository->findAll(),
        ]);
    }
}
