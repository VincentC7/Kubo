<?php

namespace App\Service;

use App\Entity\Recette;
use App\Repository\RecetteRepository;

/**
 * Orchestre la génération du catalogue hebdomadaire.
 *
 * ## Catalogue (buildOrderedCatalogue)
 *
 * 1. Charge toutes les recettes (eager-load ingrédients + types)
 * 2. Score chaque recette via MenuScoringService
 * 3. Sépare en deux groupes :
 *    - Groupe A (score > 0) : trié par score DESC, puis shuffle déterministe des ex-aequo
 *    - Groupe B (score = 0) : shuffle déterministe
 * 4. Concatène [A…, B…] → les CATALOGUE_SIZE premiers = "sélection premium"
 *
 * ## Déterminisme
 *
 * La seed est : crc32($userId . $isoYear . $isoWeek)
 * → même user + même semaine = même catalogue.
 * → deux users différents ou deux semaines différentes = résultats différents.
 */
final class MenuGeneratorService
{
    /** Nombre de recettes dans la sélection "premium" du catalogue */
    public const CATALOGUE_SIZE = 70;

    public function __construct(
        private readonly RecetteRepository  $recetteRepository,
        private readonly MenuScoringService $scoringService,
    ) {}

    /**
     * Retourne le catalogue ordonné complet (toutes les recettes).
     * Les CATALOGUE_SIZE premières sont la sélection scorée.
     * Les suivantes sont le reste dans un ordre aléatoire déterministe.
     *
     * @return array{recettes: Recette[], total: int, catalogue_size: int}
     */
    public function buildOrderedCatalogue(string $userId, int $isoYear, int $isoWeek): array
    {
        $allRecettes = $this->recetteRepository->findAllForMenu();
        $currentMonth = $this->currentMonth($isoYear, $isoWeek);
        $seed = $this->computeSeed($userId, $isoYear, $isoWeek);

        // Score chaque recette
        $scored = [];
        foreach ($allRecettes as $recette) {
            $scored[] = [
                'recette' => $recette,
                'score'   => $this->scoringService->score($recette, $currentMonth),
            ];
        }

        // Séparer en deux groupes
        $groupA = array_values(array_filter($scored, fn ($r) => $r['score'] > 0.0));
        $groupB = array_values(array_filter($scored, fn ($r) => $r['score'] === 0.0));

        // Trier le groupe A par score DESC (stable : on conserve l'ordre relatif des ex-aequo)
        usort($groupA, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Shuffle déterministe des ex-aequo dans le groupe A
        // (regrouper par score, shuffler chaque groupe)
        $groupA = $this->deterministicShuffleByScore($groupA, $seed);

        // Shuffle déterministe du groupe B
        $groupB = $this->deterministicShuffle($groupB, $seed);

        // Concaténer
        $ordered = array_merge($groupA, $groupB);

        return [
            'recettes'       => array_column($ordered, 'recette'),
            'total'          => count($ordered),
            'catalogue_size' => min(self::CATALOGUE_SIZE, count($ordered)),
        ];
    }

    /**
     * Retourne une page du catalogue.
     *
     * @return array{recettes: Recette[], total: int, catalogue_size: int}
     */
    public function buildCataloguePage(
        string $userId,
        int    $isoYear,
        int    $isoWeek,
        int    $page,
        int    $limit,
    ): array {
        $catalogue = $this->buildOrderedCatalogue($userId, $isoYear, $isoWeek);

        $offset  = ($page - 1) * $limit;
        $recettes = array_slice($catalogue['recettes'], $offset, $limit);

        return [
            'recettes'       => $recettes,
            'total'          => $catalogue['total'],
            'catalogue_size' => $catalogue['catalogue_size'],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Shuffle déterministe d'un tableau en utilisant une seed PHP.
     *
     * @param array<array{recette: Recette, score: float}> $items
     * @return array<array{recette: Recette, score: float}>
     */
    private function deterministicShuffle(array $items, int $seed): array
    {
        if (empty($items)) {
            return $items;
        }
        mt_srand($seed);
        // Fisher-Yates
        $n = count($items);
        for ($i = $n - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }

    /**
     * Trie par score DESC puis shuffle déterministe les ex-aequo de chaque groupe de score.
     *
     * @param array<array{recette: Recette, score: float}> $items Déjà triés par score DESC
     * @return array<array{recette: Recette, score: float}>
     */
    private function deterministicShuffleByScore(array $items, int $seed): array
    {
        if (empty($items)) {
            return $items;
        }

        // Regrouper par score
        $groups = [];
        foreach ($items as $item) {
            $key = (string) $item['score'];
            $groups[$key][] = $item;
        }

        // Shuffler chaque groupe et reconstruire
        $result = [];
        foreach ($groups as $group) {
            $result = array_merge($result, $this->deterministicShuffle($group, $seed));
        }

        return $result;
    }

    /**
     * Calcule la seed entière à partir de l'user et de la semaine ISO.
     */
    private function computeSeed(string $userId, int $isoYear, int $isoWeek): int
    {
        return crc32($userId . $isoYear . sprintf('%02d', $isoWeek));
    }

    /**
     * Retourne le mois (1–12) du lundi de la semaine ISO donnée.
     * Utilisé pour déterminer quels ingrédients sont de saison.
     */
    private function currentMonth(int $isoYear, int $isoWeek): int
    {
        $date = new \DateTimeImmutable();
        $date = $date->setISODate($isoYear, $isoWeek, 1); // lundi de la semaine

        return (int) $date->format('n');
    }
}
