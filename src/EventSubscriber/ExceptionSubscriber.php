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
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $data = [
            'message' => null,
            'status' => null
        ];

        if ($exception instanceof AccessDeniedException) {
            $data['status'] = RESPONSE::HTTP_FORBIDDEN;
            $data['message'] = 'Seuls les admin peuvent acceder à cette ressource.';
        }

        $event->setResponse(new JsonResponse([
            'message' => $data['message']
        ], $data['status']));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onExceptionEvent', 10],
        ];
    }
}
