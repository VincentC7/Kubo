<?php

namespace App\Controller;

use App\Dto\InventoryItemDto;
use App\Entity\InventoryItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/inventory', name: 'api_inventory_post', methods: ['POST'])]
#[OA\Tag(name: 'Inventaire')]
#[OA\Post(path: '/api/inventory', summary: 'Ajoute un item au garde-manger')]
#[OA\Response(response: 201, description: 'Item créé')]
#[OA\Response(response: 400, description: 'Validation')]
class PostInventoryEndpoint extends AbstractController
{
    public function __invoke(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data     = json_decode($request->getContent(), true) ?? [];
        $errors   = [];

        $name      = $data['name']      ?? null;
        $quantity  = $data['quantity']  ?? null;
        $unit      = $data['unit']      ?? null;
        $category  = $data['category']  ?? null;
        $expiresAt = $data['expiresAt'] ?? null;

        if (empty($name) || strlen((string) $name) > 255) {
            $errors['name'][] = 'Requis, max 255 caractères.';
        }
        if ($quantity === null || !is_numeric($quantity) || (float) $quantity <= 0) {
            $errors['quantity'][] = 'Float strictement positif requis.';
        }
        if ($unit !== null && strlen((string) $unit) > 50) {
            $errors['unit'][] = 'Max 50 caractères.';
        }
        if ($category !== null && strlen((string) $category) > 100) {
            $errors['category'][] = 'Max 100 caractères.';
        }
        $expiresAtObj = null;
        if ($expiresAt !== null) {
            $expiresAtObj = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $expiresAt);
            if ($expiresAtObj === false) {
                $errors['expiresAt'][] = 'Format attendu : YYYY-MM-DD.';
                $expiresAtObj = null;
            } else {
                $expiresAtObj = $expiresAtObj->setTime(0, 0, 0);
            }
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $item = new InventoryItem($user, (string) $name);
        $item->setQuantity((float) $quantity);
        $item->setUnit($unit !== null ? (string) $unit : null);
        $item->setCategory($category !== null ? (string) $category : null);
        $item->setExpiresAt($expiresAtObj);

        $em->persist($item);
        $em->flush();

        return $this->json(['data' => InventoryItemDto::fromEntity($item)], Response::HTTP_CREATED);
    }
}
