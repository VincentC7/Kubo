<?php

namespace App\Controller;

use App\Repository\ShoppingItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping/items/{id}', name: 'api_shopping_items_delete', methods: ['DELETE'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Delete(path: '/api/shopping/items/{id}', summary: 'Supprime un item')]
#[OA\Response(response: 204, description: 'Supprimé')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Item inconnu')]
class DeleteShoppingItemEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        ShoppingItemRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $item = $repository->findOneByIdAndUser($id, $user);

        if ($item === null) {
            return $this->json(['error' => 'Item non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $list = $item->getShoppingList();
        $em->remove($item);
        $list->touch();
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
