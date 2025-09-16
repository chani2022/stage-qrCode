<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    /**
     * Personnaliser le message de retour lors de la deconnexion
     * 
     * @param LogoutEvent $event
     * @return void
     */
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

    /**
     * Definir les évènements à abonner.
     * 
     * @return array<string, string|array{int, string|int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogoutEvent',
        ];
    }
}
