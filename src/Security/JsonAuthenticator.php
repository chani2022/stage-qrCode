<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\JsonLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Models\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JsonAuthenticator extends JsonLoginAuthenticator
{
    public function __construct(private UserRepository $userRepository) {}

    public function supports(Request $request): ?bool
    {
        return ($request->isMethod('POST') && $request->getPathInfo() == '/api/login');
    }

    public function authenticate(Request $request): Passport
    {
        $data = $request->toArray();
        $matricule = $data['identifiant'] ?? '';
        $plainPassword = $data['password'] ?? '';

        if (!$this->userRepository->authenticatedUser($matricule, md5($plainPassword))) {
            throw new AuthenticationException('Invalid credentials');
        }

        return new SelfValidatingPassport(
            new UserBadge($matricule, function (string $identifier) {
                return $this->userRepository->findByIdentifier($identifier);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User */
        $user = $token->getUser();

        return new JsonResponse([
            'message' => 'Authentification success.',
            'user' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => 'Invalid credentials.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
