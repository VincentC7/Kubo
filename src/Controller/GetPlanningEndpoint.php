<?php

namespace App\Controller;

use App\Dto\PlanningEntryDto;
use App\Repository\PlanningEntryRepository;
use App\Service\WeekHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/planning', name: 'api_planning_list', methods: ['GET'])]
#[OA\Tag(name: 'Planning')]
#[OA\Get(
    path: '/api/planning',
    summary: 'Retourne le planning de la semaine',
)]
#[OA\Parameter(name: 'week', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: '2026-W18'))]
#[OA\Response(response: 200, description: 'Entrées de planning')]
#[OA\Response(response: 400, description: 'Format de semaine invalide')]
class GetPlanningEndpoint extends AbstractController
{
    public function __invoke(Request $request, PlanningEntryRepository $repository): JsonResponse
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

        $entries = $repository->findByUserAndWeek($user, $week);
        $bounds  = WeekHelper::bounds($week);

        return $this->json([
            'data' => array_map(fn ($e) => PlanningEntryDto::fromEntity($e), $entries),
            'meta' => [
                'week'      => $week,
                'weekStart' => $bounds['weekStart'],
                'weekEnd'   => $bounds['weekEnd'],
            ],
        ]);
    }
}
