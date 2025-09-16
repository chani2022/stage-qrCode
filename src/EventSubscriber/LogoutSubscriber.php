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
            $status = 'failed';
            $message = 'L\'Utilisateur est déjà deconnecté.';

            if ($user) {
                $status = 'success';
                $message = 'Déconnexion réussie.';
            }

            $event->setResponse(new JsonResponse([
                'status' => $status,
                'message' => $message
            ]));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutEvent',
        ];
    }
}
