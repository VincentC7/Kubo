<?php

namespace App\Controller;

use App\Dto\InventoryItemDto;
use App\Repository\InventoryItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/inventory', name: 'api_inventory_get', methods: ['GET'])]
#[OA\Tag(name: 'Inventaire')]
#[OA\Get(path: '/api/inventory', summary: 'Retourne le garde-manger')]
#[OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
#[OA\Parameter(name: 'expiring_soon', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))]
#[OA\Response(response: 200, description: 'Liste des items inventaire')]
class GetInventoryEndpoint extends AbstractController
{
    public function __invoke(Request $request, InventoryItemRepository $repository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user         = $this->getUser();
        $category     = $request->query->get('category');
        $expiringSoon = filter_var($request->query->get('expiring_soon', false), FILTER_VALIDATE_BOOLEAN);

        $items    = $repository->findByUser($user, $category, $expiringSoon);
        $allItems = ($category !== null || $expiringSoon)
            ? $repository->findByUser($user)
            : $items;

        $dtos  = array_map(fn ($i) => InventoryItemDto::fromEntity($i), $items);
        $today = new \DateTimeImmutable('today');

        $expiringSoonCount = 0;
        $expiredCount      = 0;
        foreach ($allItems as $item) {
            $exp = $item->getExpiresAt();
            if ($exp === null) continue;
            if ($exp < $today) {
                $expiredCount++;
            } elseif ((int) $today->diff($exp)->days <= 3) {
                $expiringSoonCount++;
            }
        }

        return $this->json([
            'data' => $dtos,
            'meta' => [
                'total'        => count($allItems),
                'expiringSoon' => $expiringSoonCount,
                'expired'      => $expiredCount,
            ],
        ]);
    }
}
