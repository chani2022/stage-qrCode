<?php

namespace App\Security;

use App\Provider\UserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\JsonLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class JsonAuthenticator extends JsonLoginAuthenticator
{
    public function __construct(private UserProvider $userProvider) {}
    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() == '/api/login';
    }

    public function authenticate(Request $request): Passport
    {
        $data = $request->toArray();

        return new Passport(
            new UserBadge($data['matricule']),
            new PasswordCredentials($data['password'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new JsonResponse([
            'message' => 'authentification success.',
            'user' => $token->getUser()
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => 'authentification failed.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
