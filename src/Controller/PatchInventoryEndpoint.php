<?php

namespace App\Controller;

use App\Dto\InventoryItemDto;
use App\Repository\InventoryItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/inventory/{id}', name: 'api_inventory_patch', methods: ['PATCH'])]
#[OA\Tag(name: 'Inventaire')]
#[OA\Patch(path: '/api/inventory/{id}', summary: 'Met à jour un item du garde-manger')]
#[OA\Response(response: 200, description: 'Item mis à jour')]
#[OA\Response(response: 400, description: 'Validation')]
#[OA\Response(response: 403, description: 'Accès refusé')]
#[OA\Response(response: 404, description: 'Item inconnu')]
class PatchInventoryEndpoint extends AbstractController
{
    public function __invoke(
        string $id,
        Request $request,
        InventoryItemRepository $repository,
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

        if (array_key_exists('name', $data)) {
            if (empty($data['name']) || strlen((string) $data['name']) > 255) {
                $errors['name'][] = 'Requis, max 255 caractères.';
            } else {
                $item->setName((string) $data['name']);
            }
        }

        if (array_key_exists('quantity', $data)) {
            $qty = $data['quantity'];
            if (!is_numeric($qty) || (float) $qty <= 0) {
                $errors['quantity'][] = 'Float strictement positif requis.';
            } else {
                $item->setQuantity((float) $qty);
            }
        }

        if (array_key_exists('unit', $data)) {
            $item->setUnit($data['unit'] !== null ? (string) $data['unit'] : null);
        }

        if (array_key_exists('category', $data)) {
            $item->setCategory($data['category'] !== null ? (string) $data['category'] : null);
        }

        if (array_key_exists('expiresAt', $data)) {
            if ($data['expiresAt'] === null) {
                $item->setExpiresAt(null);
            } else {
                $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $data['expiresAt']);
                if ($parsed === false) {
                    $errors['expiresAt'][] = 'Format attendu : YYYY-MM-DD.';
                } else {
                    $item->setExpiresAt($parsed->setTime(0, 0, 0));
                }
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $item->touch();
        $em->flush();

        return $this->json(['data' => InventoryItemDto::fromEntity($item)]);
    }
}
