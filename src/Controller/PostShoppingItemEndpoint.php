<?php

namespace App\Controller;

use App\Dto\ShoppingItemDto;
use App\Entity\ShoppingItem;
use App\Entity\ShoppingList;
use App\Repository\ShoppingListRepository;
use App\Service\WeekHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping/items', name: 'api_shopping_items_post', methods: ['POST'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Post(path: '/api/shopping/items', summary: 'Ajoute un item manuel à la liste')]
#[OA\Response(response: 201, description: 'Item créé')]
#[OA\Response(response: 400, description: 'Validation')]
class PostShoppingItemEndpoint extends AbstractController
{
    public function __invoke(
        Request $request,
        ShoppingListRepository $listRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data   = json_decode($request->getContent(), true) ?? [];
        $errors = [];

        $week           = $data['week']           ?? null;
        $ingredientName = $data['ingredientName'] ?? null;
        $quantity       = $data['quantity']       ?? null;
        $unit           = $data['unit']           ?? null;
        $category       = $data['category']       ?? null;

        if (empty($week) || !WeekHelper::validate((string) $week)) {
            $errors['week'][] = 'Format de semaine invalide. Attendu : YYYY-Www';
        }
        if (empty($ingredientName) || strlen((string) $ingredientName) > 255) {
            $errors['ingredientName'][] = 'Requis, max 255 caractères.';
        }
        if ($quantity !== null && (!is_numeric($quantity) || (float) $quantity <= 0)) {
            $errors['quantity'][] = 'Doit être un nombre positif.';
        }
        if ($unit !== null && strlen((string) $unit) > 50) {
            $errors['unit'][] = 'Max 50 caractères.';
        }
        if ($category !== null && strlen((string) $category) > 100) {
            $errors['category'][] = 'Max 100 caractères.';
        }

        if (!empty($errors)) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Récupère ou crée la liste
        $list = $listRepository->findOneByUserAndWeek($user, (string) $week);
        if ($list === null) {
            $list = new ShoppingList($user, (string) $week);
            $em->persist($list);
        }

        $item = new ShoppingItem($list, (string) $ingredientName, 'manual');
        $item->setQuantity($quantity !== null ? (float) $quantity : null);
        $item->setUnit($unit !== null ? (string) $unit : null);
        $item->setCategory($category !== null ? (string) $category : null);
        $list->addItem($item);
        $list->touch();

        $em->persist($item);
        $em->flush();

        return $this->json(['data' => ShoppingItemDto::fromEntity($item)], Response::HTTP_CREATED);
    }
}
