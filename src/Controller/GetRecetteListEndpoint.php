<?php

namespace App\Controller;

use App\Dto\RecetteListItemDto;
use App\Repository\RecetteRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recettes', name: 'api_recettes_list', methods: ['GET'])]
#[OA\Tag(name: 'Recettes')]
#[OA\Get(
    path: '/api/recettes',
    description: 'Retourne une liste paginée de recettes avec filtres optionnels.',
    summary: 'Liste paginée des recettes',
)]
#[OA\Parameter(
    name: 'page',
    description: 'Numéro de page (commence à 1)',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
)]
#[OA\Parameter(
    name: 'limit',
    description: 'Nombre d\'éléments par page (max 100)',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100),
)]
#[OA\Parameter(
    name: 'q',
    description: 'Recherche texte sur le nom et la description',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', example: 'poulet'),
)]
#[OA\Parameter(
    name: 'tag',
    description: 'Filtrer par nom de tag exact',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', example: 'Végétarien'),
)]
#[OA\Parameter(
    name: 'difficulte',
    description: 'Filtrer par difficulté',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', enum: ['Facile', 'Intermédiaire', 'Difficile']),
)]
#[OA\Parameter(
    name: 'temps_max',
    description: 'Temps total maximum en minutes',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', example: 30),
)]
#[OA\Parameter(
    name: 'ingredient',
    description: 'Recherche texte sur le nom d\'un ingrédient',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', example: 'tomate'),
)]
#[OA\Parameter(
    name: 'type_ingredient',
    description: 'Filtrer par type d\'ingrédient (slug exact)',
    in: 'query',
    required: false,
    schema: new OA\Schema(
        type: 'string',
        enum: ['viande', 'poisson', 'legume', 'fruit', 'feculent', 'produit_laitier', 'herbe_epice', 'condiment', 'autre'],
        example: 'viande',
    ),
)]
#[OA\Parameter(
    name: 'saison',
    description: 'Filtrer les recettes ayant au moins 1 ingrédient de saison pour ce mois (1–12)',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12, example: 7),
)]
#[OA\Response(
    response: 200,
    description: 'Liste paginée de recettes',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'data',
                type: 'array',
                items: new OA\Items(ref: new Model(type: RecetteListItemDto::class)),
            ),
            new OA\Property(
                property: 'meta',
                properties: [
                    new OA\Property(property: 'total', type: 'integer', example: 2151),
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'limit', type: 'integer', example: 20),
                    new OA\Property(property: 'pages', type: 'integer', example: 108),
                ],
                type: 'object',
            ),
        ],
    ),
)]
class GetRecetteListEndpoint extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MEDIA_BASE_URL)%')]
        private readonly string $mediaBaseUrl,
    ) {}

    public function __invoke(Request $request, RecetteRepository $repository): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        // Validation du paramètre saison : doit être un entier entre 1 et 12
        $saisonRaw = $request->query->get('saison');
        if ($saisonRaw !== null && $saisonRaw !== '') {
            if (!ctype_digit((string) $saisonRaw)) {
                return $this->json(
                    ['error' => 'Le paramètre "saison" doit être un entier entre 1 et 12.'],
                    Response::HTTP_BAD_REQUEST,
                );
            }
            $saisonInt = (int) $saisonRaw;
            if ($saisonInt < 1 || $saisonInt > 12) {
                return $this->json(
                    ['error' => 'Le paramètre "saison" doit être compris entre 1 et 12.'],
                    Response::HTTP_BAD_REQUEST,
                );
            }
        }

        $filters = array_filter([
            'q'               => $request->query->get('q'),
            'tag'             => $request->query->get('tag'),
            'difficulte'      => $request->query->get('difficulte'),
            'temps_max'       => $request->query->get('temps_max'),
            'ingredient'      => $request->query->get('ingredient'),
            'type_ingredient' => $request->query->get('type_ingredient'),
            'saison'          => $saisonRaw,
        ], fn ($v) => $v !== null && $v !== '');

        $result = $repository->findPaginated($filters, $page, $limit);
        $total  = $result['total'];
        $pages  = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $data = array_map(
            fn ($recette) => RecetteListItemDto::fromEntity($recette, $this->mediaBaseUrl),
            $result['items'],
        );

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'pages' => $pages,
            ],
        ]);
    }
}
