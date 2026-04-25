<?php

namespace App\Controller;

use App\Dto\SaisonItemDto;
use App\Repository\IngredientRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ingredients/saison', name: 'api_ingredients_saison', methods: ['GET'])]
#[OA\Tag(name: 'Ingrédients')]
#[OA\Get(
    path: '/api/ingredients/saison',
    description: 'Retourne la liste des fruits et légumes de saison pour un mois donné. Par défaut, le mois courant.',
    summary: 'Fruits et légumes de saison',
)]
#[OA\Parameter(
    name: 'mois',
    description: 'Mois (1–12). Défaut : mois courant.',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 6),
)]
#[OA\Response(
    response: 200,
    description: 'Liste des fruits et légumes de saison',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'mois',
                description: 'Mois interrogé (1–12)',
                type: 'integer',
                example: 6,
            ),
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(ref: new Model(type: SaisonItemDto::class)),
            ),
            new OA\Property(property: 'total', type: 'integer', example: 12),
        ],
    ),
)]
#[OA\Response(response: 400, description: 'Paramètre mois invalide (doit être entre 1 et 12)')]
class GetSaisonEndpoint extends AbstractController
{
    public function __invoke(Request $request, IngredientRepository $repository): JsonResponse
    {
        $moisParam = $request->query->get('mois');

        if ($moisParam !== null && $moisParam !== '') {
            $mois = (int) $moisParam;
            if ($mois < 1 || $mois > 12) {
                return new JsonResponse(
                    ['error' => 'Paramètre mois invalide. Valeur attendue : entier entre 1 et 12.'],
                    Response::HTTP_BAD_REQUEST
                );
            }
        } else {
            $mois = (int) (new \DateTimeImmutable())->format('n');
        }

        $ingredients = $repository->findBySaison($mois);

        $data = array_map(
            fn ($ingredient) => SaisonItemDto::fromEntity($ingredient),
            $ingredients,
        );

        return new JsonResponse([
            'mois'  => $mois,
            'data'  => $data,
            'total' => count($data),
        ]);
    }
}
