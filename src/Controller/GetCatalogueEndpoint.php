<?php

namespace App\Controller;

use App\Dto\CatalogueDto;
use App\Dto\RecetteListItemDto;
use App\Entity\User;
use App\Service\MenuGeneratorService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/catalogue', name: 'api_catalogue', methods: ['GET'])]
#[OA\Tag(name: 'Catalogue')]
#[OA\Get(
    path: '/api/catalogue',
    description: <<<'DESC'
Retourne le catalogue de recettes de la semaine, ordonné par pertinence :
- Les **catalogue_size** premières recettes sont la sélection scorée (saisonnalité, équilibre).
- Les suivantes sont le reste du catalogue dans un ordre aléatoire déterministe.

Le résultat est **déterministe** pour un même utilisateur et une même semaine.
DESC,
    summary: 'Catalogue hebdomadaire personnalisé',
)]
#[OA\Parameter(
    name: 'week',
    description: 'Semaine ISO (ex: 2026-W12). Défaut : semaine courante.',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'string', example: '2026-W12'),
)]
#[OA\Parameter(
    name: 'page',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
)]
#[OA\Parameter(
    name: 'limit',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 20, maximum: 50, minimum: 1),
)]
#[OA\Response(
    response: 200,
    description: 'Catalogue hebdomadaire',
    content: new OA\JsonContent(ref: new Model(type: CatalogueDto::class)),
)]
#[OA\Response(response: 400, description: 'Paramètre week invalide')]
class GetCatalogueEndpoint extends AbstractController
{
    public function __invoke(Request $request, MenuGeneratorService $generator): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Pour les visiteurs non authentifiés, on utilise un UUID générique
        $userId = ($user instanceof User) ? $user->getId() : null;

        // Parse semaine
        $weekParam = $request->query->get('week');
        [$isoYear, $isoWeek, $semaine] = $this->parseWeek($weekParam);
        if ($isoYear === null) {
            return new JsonResponse(
                ['error' => 'Paramètre week invalide. Format attendu : YYYY-Www (ex: 2026-W12).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $result = $generator->buildCataloguePage($userId, $isoYear, $isoWeek, $page, $limit);

        $dtos = array_map(
            fn ($recette) => RecetteListItemDto::fromEntity($recette),
            $result['recettes'],
        );

        $dto = new CatalogueDto(
            semaine:       $semaine,
            recettes:      $dtos,
            total:         $result['total'],
            page:          $page,
            limit:         $limit,
            catalogueSize: $result['catalogue_size'],
        );

        return new JsonResponse($dto);
    }

    /**
     * Parse le paramètre week.
     * @return array{int|null, int|null, string}  [isoYear, isoWeek, semaine]
     */
    private function parseWeek(?string $weekParam): array
    {
        if ($weekParam === null || $weekParam === '') {
            $now     = new \DateTimeImmutable();
            $isoYear = (int) $now->format('o');
            $isoWeek = (int) $now->format('W');
            $semaine = $now->format('o-\WW');

            return [$isoYear, $isoWeek, $semaine];
        }

        if (!preg_match('/^(\d{4})-W(\d{2})$/', $weekParam, $m)) {
            return [null, null, ''];
        }

        $isoYear = (int) $m[1];
        $isoWeek = (int) $m[2];

        // Validation : setISODate recrache la bonne semaine ISO si valide
        $date = (new \DateTimeImmutable())->setISODate($isoYear, $isoWeek);
        if ((int) $date->format('o') !== $isoYear || (int) $date->format('W') !== $isoWeek) {
            return [null, null, ''];
        }

        return [$isoYear, $isoWeek, $weekParam];
    }
}
