<?php

namespace App\Controller;

use App\Dto\PlanningEntryDto;
use App\Repository\PlanningEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/planning/{id}', name: 'api_planning_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Planning')]
#[OA\Patch(path: '/api/planning/{id}', summary: 'Met à jour portions ou done')]
#[OA\Response(response: 200, description: 'Entrée mise à jour')]
#[OA\Response(response: 400, description: 'Validation')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Entrée inconnue')]
class PatchPlanningEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        Request $request,
        PlanningEntryRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $entry = $repository->findOneByIdAndUser($id, $user);

        if ($entry === null) {
            return $this->json(['error' => 'Entrée de planning non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $errors = [];

        if (array_key_exists('portions', $data)) {
            $portions = $data['portions'];
            if (!is_int($portions) || $portions < 1 || $portions > 20) {
                $errors['portions'][] = 'Doit être un entier entre 1 et 20.';
            } else {
                $entry->setPortions($portions);
            }
        }

        if (array_key_exists('done', $data)) {
            $done = $data['done'];
            if (!is_bool($done)) {
                $errors['done'][] = 'Doit être un booléen.';
            } else {
                $entry->setDone($done);
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json(['data' => PlanningEntryDto::fromEntity($entry)]);
    }
}
