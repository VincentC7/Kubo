<?php

namespace App\Controller;

use App\Repository\InventoryItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/inventory/{id}', name: 'api_inventory_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Inventaire')]
#[OA\Delete(path: '/api/inventory/{id}', summary: 'Supprime un item du garde-manger')]
#[OA\Response(response: 204, description: 'Supprimé')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Item inconnu')]
class DeleteInventoryEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        InventoryItemRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $item = $repository->findOneByIdAndUser($id, $user);

        if ($item === null) {
            return $this->json(['error' => 'Item non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($item);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
