<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticator fake stateless.
 *
 * Authentifie automatiquement toutes les requêtes avec un user ID fixe.
 * Aucun header ou paramètre n'est lu.
 *
 * Migration JWT : remplacer cet authenticator par un JWTAuthenticator qui
 * extrait l'id depuis le payload du token. Le reste du code (controllers,
 * MenuGeneratorService) n'a pas besoin de changer.
 */
final class UserIdHeaderAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const FAKE_USER_ID = '00000000-0000-0000-0000-000000000001';

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Ne devrait jamais être appelé (supports() retourne toujours true)
        return new \Symfony\Component\HttpFoundation\JsonResponse(
            ['error' => 'Non authentifié.'],
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function authenticate(Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge(self::FAKE_USER_ID, fn (string $id) => new User($id))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new \Symfony\Component\HttpFoundation\JsonResponse(
            ['error' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
