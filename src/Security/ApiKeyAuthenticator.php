<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Vérifie la présence et la validité du header X-Api-Key sur toutes les requêtes.
 * Si le header est absent ou invalide → 401 immédiatement.
 */
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly string $apiKey,
    ) {
    }

    /**
     * Toujours actif : on intercepte toutes les requêtes du firewall pour vérifier la clé.
     */
    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $providedKey = $request->headers->get('X-Api-Key');

        if (null === $providedKey || !hash_equals($this->apiKey, $providedKey)) {
            throw new CustomUserMessageAuthenticationException('Clé API invalide ou absente.');
        }

        // Clé valide : user synthétique avec un loader inline pour éviter les conflits de provider
        return new SelfValidatingPassport(
            new UserBadge('api_client', fn () => new \Symfony\Component\Security\Core\User\InMemoryUser('api_client', null, ['ROLE_API_CLIENT'])),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Continuer vers le prochain authenticator (JWT) ou le controller
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => 'Clé API invalide ou absente.'],
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
