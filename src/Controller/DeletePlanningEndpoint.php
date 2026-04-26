<?php

namespace App\Controller;

use App\Repository\PlanningEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/planning/{id}', name: 'api_planning_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Planning')]
#[OA\Delete(path: '/api/planning/{id}', summary: 'Retire une recette du planning')]
#[OA\Response(response: 204, description: 'Supprimé')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Entrée inconnue')]
class DeletePlanningEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        PlanningEntryRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $entry = $repository->findOneByIdAndUser($id, $user);

        if ($entry === null) {
            return $this->json(['error' => 'Entrée de planning non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($entry);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
