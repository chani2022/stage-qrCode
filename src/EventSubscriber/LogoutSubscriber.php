<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function onLogoutEvent(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $user = $event->getToken()?->getUser();

        // Vérifier si la requête attend une réponse JSON
        if (
            $request->getRequestFormat() === 'json' ||
            $request->headers->get('Content-Type') === 'application/json'
        ) {

            if (!$user) {
                $event->setResponse(
                    new JsonResponse([
                        'status' => 'failed',
                        'message' => 'L\'Utilisateur est déjà deconnecté.'
                    ], JsonResponse::HTTP_NOT_FOUND)
                );
            } else {

                $event->setResponse(new JsonResponse([
                    'status' => 'success',
                    'message' => 'Déconnexion réussie',
                    'timestamp' => time()
                ], JsonResponse::HTTP_OK));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutEvent',
        ];
    }
}
