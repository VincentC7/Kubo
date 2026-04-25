<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Vérifie le header X-Api-Key sur toutes les routes /api/* (sauf /api/doc).
 * S'exécute avant le firewall Symfony Security.
 */
class ApiKeySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $apiKey,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 32], // Avant le firewall (priority 8)
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Seules les routes /api/* sont protégées par l'API Key
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        // La doc est publique
        if (str_starts_with($path, '/api/doc')) {
            return;
        }

        $providedKey = $request->headers->get('X-Api-Key');

        if (null === $providedKey || !hash_equals($this->apiKey, $providedKey)) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Clé API invalide ou absente.'],
                Response::HTTP_UNAUTHORIZED,
            ));
        }
    }
}
