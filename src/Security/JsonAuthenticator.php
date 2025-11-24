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

        // Find user by identifier
        $user = $this->userRepository->findByIdentifier($matricule);
        
        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Verify password using MD5 hash (as configured in security.yaml)
        if ($user->getMotsdepasse() !== md5($plainPassword)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Check if user has allowed function
        $allowedFunctions = [
            'Responsable Personnel',
            'Directeur de Plateau', 
            'Directeur Général',
            'Directeur',
            'Securite'
        ];
        
        $userFunction = $user->getNomFonction();
        if (!in_array($userFunction, $allowedFunctions)) {
            throw new AuthenticationException('Access denied: insufficient privileges');
        }

        // Check if user is active
        if ($user->getActif() !== 'Oui') {
            throw new AuthenticationException('Account is inactive');
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

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessage();
        
        // Map specific error messages to proper HTTP status codes
        if (str_contains($message, 'insufficient privileges')) {
            $statusCode = Response::HTTP_FORBIDDEN;
        } elseif (str_contains($message, 'inactive')) {
            $statusCode = Response::HTTP_FORBIDDEN;
        } else {
            $statusCode = Response::HTTP_UNAUTHORIZED;
        }
        
        return new JsonResponse([
            'error' => $message,
        ], $statusCode);
    }
}
