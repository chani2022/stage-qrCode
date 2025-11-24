<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityController extends AbstractController
{
    private UserRepository $userRepository;
    private array $allowedFunctions = [
        'Responsable Personnel',
        'Directeur de Plateau', 
        'Directeur Général',
        'Directeur',
        'Securite'
    ];

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/users', name: 'app_user')]
    public function index(UserRepository $userRepository): JsonResponse
    {
        return $this->json([
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['identifiant']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Missing credentials'], 400);
        }

        $identifier = $data['identifiant'];
        $password = $data['password'];

        // Find user by identifier
        $user = $this->userRepository->findByIdentifier($identifier);
        
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Verify password using MD5 hash (as configured in security.yaml)
        if ($user->getMotsdepasse() !== md5($password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Check if user has allowed function
        $userFunction = $user->getNomFonction();
        if (!in_array($userFunction, $this->allowedFunctions)) {
            return new JsonResponse(['error' => 'Access denied: insufficient privileges'], 403);
        }

        // Check if user is active
        if ($user->getActif() !== 'Oui') {
            return new JsonResponse(['error' => 'Account is inactive'], 403);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'nom_fonction' => $user->getNomFonction(),
                'photo' => $user->getPhoto(),
                'sexe' => $user->getSexe(),
            ]
        ]);
    }

    /**
     * Get current user profile
     */
    #[Route('/api/me', name: 'app_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        // Check if user still has allowed function
        $userFunction = $user->getNomFonction();
        if (!in_array($userFunction, $this->allowedFunctions)) {
            return new JsonResponse(['error' => 'Access denied: insufficient privileges'], 403);
        }

        // Check if user is still active
        if ($user->getActif() !== 'Oui') {
            return new JsonResponse(['error' => 'Account is inactive'], 403);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'nom_fonction' => $user->getNomFonction(),
                'photo' => $user->getPhoto(),
                'sexe' => $user->getSexe(),
                'actif' => $user->getActif(),
            ]
        ]);
    }

    /**
     * Handle logout request
     */
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Symfony's firewall will handle the actual logout process
        // This method will only be reached if the firewall doesn't intercept it
        return new JsonResponse(['message' => 'Logged out successfully']);
    }
}
