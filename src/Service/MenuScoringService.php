<?php

namespace App\Service;

use App\Entity\Recette;

/**
 * Calcule un score flottant pour une recette individuelle.
 *
 * Le score est stateless et ne dépend que de la recette elle-même
 * et du mois courant. Il ne tient pas compte du contexte du menu
 * en cours de construction (c'est le rôle de MenuGeneratorService).
 *
 * Critères :
 *   +2.0  — au moins un ingrédient dont mois_saison contient le mois courant
 *   +0.5  — la recette contient un ingrédient de type "legume" ou "fruit"
 *            (favorise les recettes végétales indépendamment de la saison)
 */
final class MenuScoringService
{
    /**
     * @param int $currentMonth Mois courant (1–12)
     */
    public function score(Recette $recette, int $currentMonth): float
    {
        $score = 0.0;
        $hasProduceSeasonal = false;
        $hasProduce = false;

        foreach ($recette->getRecetteIngredients() as $ri) {
            $ingredient = $ri->getIngredient();
            $type = $ingredient->getType();

            // Bonus légume / fruit
            if ($type !== null && in_array($type->getSlug(), ['legume', 'fruit'], true)) {
                $hasProduce = true;
            }

            // Bonus saisonnalité
            $moisSaison = $ingredient->getMoisSaison();
            if ($moisSaison !== null && in_array($currentMonth, $moisSaison, true)) {
                $hasProduceSeasonal = true;
            }
        }

        if ($hasProduceSeasonal) {
            $score += 2.0;
        }

        if ($hasProduce) {
            $score += 0.5;
        }

        return $score;
    }
}
