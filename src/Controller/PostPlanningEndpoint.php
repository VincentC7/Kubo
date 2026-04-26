<?php

namespace App\Controller;

use App\Dto\PlanningEntryDto;
use App\Entity\PlanningEntry;
use App\Repository\PlanningEntryRepository;
use App\Repository\RecetteRepository;
use App\Service\WeekHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Uid\Uuid;

#[Route('/api/planning', name: 'api_planning_post', methods: ['POST'])]
#[OA\Tag(name: 'Planning')]
#[OA\Post(path: '/api/planning', summary: 'Ajoute une recette au planning')]
#[OA\Response(response: 201, description: 'Entrée créée')]
#[OA\Response(response: 400, description: 'Validation')]
#[OA\Response(response: 404, description: 'Recette non trouvée')]
#[OA\Response(response: 409, description: 'Doublon')]
class PostPlanningEndpoint extends AbstractController
{
    public function __invoke(
        Request $request,
        RecetteRepository $recetteRepository,
        PlanningEntryRepository $planningRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data     = json_decode($request->getContent(), true) ?? [];
        $errors   = [];

        $recetteId = $data['recetteId'] ?? null;
        $week      = $data['week']      ?? null;
        $portions  = $data['portions']  ?? 2;

        if (empty($recetteId) || !Uuid::isValid((string) $recetteId)) {
            $errors['recetteId'][] = 'UUID valide requis.';
        }
        if (empty($week) || !WeekHelper::validate((string) $week)) {
            $errors['week'][] = 'Format de semaine invalide. Attendu : YYYY-Www';
        }
        if (!is_int($portions) || $portions < 1 || $portions > 20) {
            $errors['portions'][] = 'Doit être un entier entre 1 et 20.';
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $recette = $recetteRepository->findOneByUuid((string) $recetteId);
        if ($recette === null) {
            return $this->json(['error' => 'Recette non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifie doublon
        $existing = $planningRepository->findOneByUserRecetteWeek($user, $recette, (string) $week);

        if ($existing !== null) {
            return $this->json(
                ['error' => 'Cette recette est déjà dans le planning de cette semaine'],
                Response::HTTP_CONFLICT,
            );
        }

        $entry = new PlanningEntry($user, $recette, (string) $week);
        $entry->setPortions((int) $portions);

        $em->persist($entry);
        $em->flush();

        return $this->json(['data' => PlanningEntryDto::fromEntity($entry)], Response::HTTP_CREATED);
    }
}
