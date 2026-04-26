<?php

namespace App\Controller;

use App\Dto\ShoppingItemDto;
use App\Repository\ShoppingItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping/items/{id}', name: 'api_shopping_items_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Patch(path: '/api/shopping/items/{id}', summary: 'Coche/décoche ou met à jour un item')]
#[OA\Response(response: 200, description: 'Item mis à jour')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Item inconnu')]
class PatchShoppingItemEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        Request $request,
        ShoppingItemRepository $repository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $item = $repository->findOneByIdAndUser($id, $user);

        if ($item === null) {
            return $this->json(['error' => 'Item non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $errors = [];

        if (array_key_exists('checked', $data)) {
            if (!is_bool($data['checked'])) {
                $errors['checked'][] = 'Doit être un booléen.';
            } else {
                $item->setChecked($data['checked']);
            }
        }

        if (array_key_exists('quantity', $data)) {
            $qty = $data['quantity'];
            if ($qty === null || (is_numeric($qty) && (float) $qty > 0)) {
                $item->setQuantity($qty !== null ? (float) $qty : null);
            }
        }

        if (array_key_exists('unit', $data)) {
            $item->setUnit($data['unit'] !== null ? (string) $data['unit'] : null);
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $item->getShoppingList()->touch();
        $em->flush();

        return $this->json(['data' => ShoppingItemDto::fromEntity($item)]);
    }
}
