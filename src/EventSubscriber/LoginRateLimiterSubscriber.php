<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Applique un rate limiting sur POST /api/login pour prévenir le brute-force.
 * Limite : 10 tentatives par IP par heure (sliding window).
 * S'exécute avant le firewall Symfony Security (priority 31).
 */
class LoginRateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RateLimiterFactory $loginApiLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 31],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getPathInfo() !== '/api/login' || $request->getMethod() !== 'POST') {
            return;
        }

        $limiter = $this->loginApiLimiter->create($request->getClientIp());
        $limit   = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Trop de tentatives de connexion. Réessayez dans 1 heure.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['X-RateLimit-Remaining' => $limit->getRemainingTokens()],
            ));
        }
    }
}
