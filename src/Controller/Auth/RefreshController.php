<?php

namespace App\Controller\Auth;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * Ce controller ne devrait jamais être appelé directement.
 * Le firewall gesdinet intercepte la requête et retourne une réponse avant d'atteindre le controller.
 */
#[OA\Tag(name: 'Authentification')]
class RefreshController
{
    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[OA\Post(
        path: '/api/token/refresh',
        summary: 'Renouveler l\'access token via un refresh token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string', example: 'abc123...'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Nouveau token retourné', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ],
            )),
            new OA\Response(response: 401, description: 'Refresh token invalide ou expiré'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        // Ce code ne devrait jamais s'exécuter — géré par le firewall gesdinet
        return new JsonResponse(['error' => 'Unreachable'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
