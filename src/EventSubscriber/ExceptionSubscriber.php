<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Empecher les utilisateurs non connecté d'acceder a une ressource proteger.
     * 
     * @param ExceptionEvent $event
     * @return void
     */
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'message' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN));
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
            KernelEvents::EXCEPTION => ['onExceptionEvent', 10],
        ];
    }
}
