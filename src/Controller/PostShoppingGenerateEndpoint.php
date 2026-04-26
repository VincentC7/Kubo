<?php

namespace App\Controller;

use App\Dto\ShoppingListDto;
use App\Entity\ShoppingItem;
use App\Entity\ShoppingList;
use App\Repository\PlanningEntryRepository;
use App\Repository\ShoppingListRepository;
use App\Service\WeekHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/shopping/generate', name: 'api_shopping_generate', methods: ['POST'])]
#[OA\Tag(name: 'Shopping')]
#[OA\Post(path: '/api/shopping/generate', summary: 'Génère la liste de courses depuis le planning')]
#[OA\Response(response: 201, description: 'Liste générée (vide si aucune recette au planning)')]
#[OA\Response(response: 400, description: 'Format de semaine invalide')]
class PostShoppingGenerateEndpoint extends AbstractController
{
    public function __invoke(
        Request $request,
        PlanningEntryRepository $planningRepository,
        ShoppingListRepository $shoppingListRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];
        $week = $data['week'] ?? null;

        if (empty($week) || !WeekHelper::validate((string) $week)) {
            return $this->json(
                ['error' => 'Format de semaine invalide. Attendu : YYYY-Www'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $entries = $planningRepository->findByUserAndWeek($user, (string) $week);

        // Récupère ou crée la liste
        $list = $shoppingListRepository->findOneByUserAndWeek($user, (string) $week);
        if ($list === null) {
            $list = new ShoppingList($user, (string) $week);
            $em->persist($list);
        } else {
            // Supprime les items planning existants, conserve les manuels
            foreach ($list->getItems()->toArray() as $item) {
                if ($item->getSource() === 'planning') {
                    $list->removeItem($item);
                    $em->remove($item);
                }
            }
        }

        // Agrège les ingrédients
        $aggregated = []; // ['nom|unite' => [qty, unit, category]]
        foreach ($entries as $entry) {
            $recette = $entry->getRecette();
            $ratio   = $entry->getPortions() / max(1, $recette->getNbPersonnes());

            foreach ($recette->getRecetteIngredients() as $ri) {
                $nom      = $ri->getIngredient()->getNom();
                $unite    = $ri->getUnite() ?? '';
                $quantite = $ri->getQuantite();
                $key      = $nom . '|' . $unite;

                $qty = null;
                if ($quantite !== null && is_numeric($quantite)) {
                    $qty = (float) $quantite * $ratio;
                }

                $category = $ri->getIngredient()->getType()?->getNom() ?? null;

                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'nom'      => $nom,
                        'unite'    => $unite ?: null,
                        'qty'      => $qty,
                        'category' => $category,
                    ];
                } else {
                    if ($qty !== null && $aggregated[$key]['qty'] !== null) {
                        $aggregated[$key]['qty'] += $qty;
                    } elseif ($qty === null && $aggregated[$key]['qty'] === null) {
                        // Les deux sont null, on reste null
                    } elseif ($qty !== null) {
                        // L'entrée accumulée est null mais on a une nouvelle valeur : on la garde
                        $aggregated[$key]['qty'] = $qty;
                    }
                    // Si $qty === null mais accumulée est non-null : on conserve l'accumulée
                }
            }
        }

        foreach ($aggregated as $agg) {
            $item = new ShoppingItem($list, $agg['nom'], 'planning');
            $item->setQuantity($agg['qty']);
            $item->setUnit($agg['unite']);
            $item->setCategory($agg['category']);
            $list->addItem($item);
            $em->persist($item);
        }

        $list->touch();
        $em->flush();

        return $this->json(['data' => ShoppingListDto::fromEntity($list)], Response::HTTP_CREATED);
    }
}
