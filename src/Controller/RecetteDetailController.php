<?php

namespace App\Controller;

use App\Dto\RecetteDetailDto;
use App\Repository\RecetteRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recettes/{uuid}', name: 'api_recettes_detail', methods: ['GET'])]
#[OA\Tag(name: 'Recettes')]
#[OA\Get(
    path: '/api/recettes/{uuid}',
    description: 'Retourne le détail complet d\'une recette par son UUID.',
    summary: 'Détail d\'une recette',
)]
#[OA\PathParameter(
    name: 'uuid',
    description: 'UUID de la recette',
    schema: new OA\Schema(type: 'string', format: 'uuid', example: '018e1b2c-3d4e-7f8a-9b0c-1d2e3f4a5b6c'),
)]
#[OA\Response(
    response: 200,
    description: 'Détail complet de la recette',
    content: new OA\JsonContent(ref: new Model(type: RecetteDetailDto::class)),
)]
#[OA\Response(
    response: 404,
    description: 'Recette non trouvée',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'error', type: 'string', example: 'Recette non trouvée.'),
        ],
    ),
)]
class RecetteDetailController extends AbstractController
{
    public function __invoke(string $uuid, RecetteRepository $repository): JsonResponse
    {
        $recette = $repository->findOneByUuid($uuid);

        if ($recette === null) {
            return new JsonResponse(['error' => 'Recette non trouvée.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(RecetteDetailDto::fromEntity($recette));
    }
}
