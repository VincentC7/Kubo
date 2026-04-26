<?php

namespace App\Controller;

use App\Dto\ShoppingListDto;
use App\Repository\ShoppingListRepository;
use App\Service\WeekHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping', name: 'api_shopping_get', methods: ['GET'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Get(path: '/api/shopping', summary: 'Retourne la liste de courses de la semaine')]
#[OA\Parameter(name: 'week', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: '2026-W18'))]
#[OA\Response(response: 200, description: 'Liste de courses')]
class GetShoppingEndpoint extends AbstractController
{
    public function __invoke(Request $request, ShoppingListRepository $repository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $week = $request->query->get('week') ?? WeekHelper::current();

        if (!WeekHelper::validate($week)) {
            return $this->json(
                ['error' => 'Format de semaine invalide. Attendu : YYYY-Www'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $list = $repository->findOneByUserAndWeek($user, $week);

        return $this->json([
            'data' => $list !== null ? ShoppingListDto::fromEntity($list) : null,
        ]);
    }
}
