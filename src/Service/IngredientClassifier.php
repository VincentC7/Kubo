<?php

namespace App\Service;

use App\Entity\TypeIngredient;

/**
 * Classifie un ingrédient par son nom en déterminant son TypeIngredient
 * et ses mois de saison, en appliquant les mêmes règles ILIKE que la migration.
 *
 * Usage :
 *   [$type, $moisSaison] = $classifier->classify($nom, $typesIndexedBySlug);
 */
final class IngredientClassifier
{
    // ── Patterns par type (dans l'ordre de priorité décroissante) ────────────

    /** @var array<string, list<string>> */
    private const TYPE_PATTERNS = [
        'viande' => [
            'poulet', 'bœuf', 'boeuf', 'porc', 'agneau', 'veau', 'lardons', 'jambon',
            'saucisse', 'merguez', 'canard', 'dinde', 'steak', 'pintade', 'effiloché',
            'gyros', 'chipolata', 'chorizo', 'boudin', 'rôti', 'escalope',
            'filet de poulet', 'blanc de poulet', 'cuisses de poulet', 'aiguillettes',
            'côtelettes', 'côte de', 'magret', 'lapin', 'gibier', 'sanglier',
        ],
        'poisson' => [
            'saumon', 'cabillaud', 'thon', 'crevette', 'daurade', 'merlu', 'lieu noir',
            'fruits de mer', 'truite', 'anchois', 'saint-jacques', 'dorade',
            'moule', 'huître', 'calmar', 'poulpe', 'pieuvre', 'langoustine',
            'homard', 'crabe', 'palourde', 'seiche', 'maquereau', 'hareng',
            'sardine', 'sole', 'turbot', 'rouget', 'lotte', 'baudroie',
            'colin', 'haddock', 'tilapia', 'pangasius', 'gambas', 'langouste',
        ],
        'legume' => [
            'courgette', 'carotte', 'oignon', 'poivron', 'tomate', 'aubergine',
            'épinard', 'épinards', 'champignon', 'portobello', 'poireau', 'chou',
            'concombre', 'sucrine', 'roquette', 'fenouil', 'navet', 'céleri',
            'patate', 'courge', 'potimarron', 'asperge', 'radis', 'topinambour',
            'panais', 'endive', 'haricots', 'haricot', 'pois', 'maïs',
            'betterave', 'pak choï', 'pak choi', 'salade', 'laitue', 'mâche',
            'artichaut', 'brocoli', 'échalote', 'échalotes', 'poirée', 'blette',
            'cresson', 'pissenlit', 'pourpier', 'mizuna', 'bok choy',
            "tête d'ail", "gousses d'ail", 'ail', 'céleri-rave',
            'cébette', 'ciboule', 'oignons verts',
        ],
        'fruit' => [
            'citron', 'pomme', 'mangue', 'fraise', 'orange', 'poire', 'banane',
            'avocat', 'ananas', 'framboise', 'pêche', 'abricot', 'grenade',
            'myrtille', 'kiwi', 'nectarine', 'pamplemousse', 'melon', 'prune',
            'raisin', 'cerise', 'figue', 'litchi', 'papaye', 'passion',
            'fruit de la passion', 'noix de coco', 'clémentine', 'mandarine',
            'bergamote', 'kumquat', 'physalis', 'groseille', 'cassis', 'mûre',
            'airelle', 'canneberge',
        ],
        'feculent' => [
            'riz', 'pâtes', 'farine', 'pommes de terre', 'lentille', 'quinoa',
            'semoule', 'pain', 'boulgour', 'orge', 'spaghetti', 'penne',
            'gnocchi', 'couscous', 'polenta', 'tortilla', 'nouille', 'orzo',
            'rigatoni', 'linguine', 'farfalle', 'tagliatelle', 'fettuccine',
            'lasagne', 'pappardelle', 'macaroni', 'fusilli', 'conchiglie',
            'vermicelle', 'capellini', 'blé', 'épeautre', 'avoine', 'millet',
            'sarrasin', 'fécule', 'amidon', 'chapelure', 'panko',
            'pois chiche', 'haricots blancs', 'haricots rouges', 'haricots noirs',
            'flageolets', 'pois cassés',
        ],
        'produit_laitier' => [
            'beurre', 'crème', 'fromage', 'mozzarella', 'parmesan', 'yaourt',
            'yogourt', 'lait', 'ricotta', 'comté', 'gruyère', 'emmental',
            'cheddar', 'pecorino', 'feta', 'mascarpone', 'halloumi', 'burrata',
            'labneh', 'quark', 'fromage blanc', 'cottage', 'raclette', 'camembert',
            'brie', 'roquefort', 'gorgonzola', 'stilton', 'manchego', 'gouda',
            'edam', 'mimolette', 'reblochon', 'munster', 'époisses', 'livarot',
            "pont-l'évêque", 'maroilles', 'langres', 'beaufort', 'abondance',
            'tomme', 'ossau-iraty', 'sainte-maure', 'chèvre',
        ],
        'herbe_epice' => [
            'thym', 'basilic', 'persil', 'coriandre', 'cumin', 'paprika',
            'curry', 'gingembre', 'origan', 'laurier', 'cannelle', 'romarin',
            'menthe', 'estragon', 'ciboulette', 'sauge', 'épices', 'curcuma',
            'piment', 'zaatar', 'sumac', 'ras el', 'garam', 'herbes',
            'muscade', 'vadouvan', 'cardamome', 'anis', 'clou de girofle',
            'girofle', 'safran', 'vanille', 'fenugrec', "mélange d'épices",
            'quatre épices', 'bouquet garni', 'herbes de provence', 'fines herbes',
            'aneth', 'cerfeuil', 'marjolaine', 'livèche', 'mélisse', 'verveine',
            'poivre', 'sel', 'fleur de sel', 'poudre de', 'épice',
        ],
        'condiment' => [
            'huile', 'vinaigre', 'moutarde', 'ketchup', 'soja', 'bouillon',
            'sucre', 'miel', 'mayonnaise', 'sauce', 'concentré de tomates',
            'harissa', 'houmous', 'pesto', 'tahini', 'teriyaki', 'hoisin',
            'sriracha', 'tapenade', 'chutney', 'sirop', 'cube de', 'fond de',
            'fumet', 'worcestershire', 'tabasco', 'nuoc-mâm', 'nuoc mam',
            'fish sauce', 'miso', 'tamari', 'aigre-doux', 'citronelle',
            'cornichon', 'câpre', 'olive', 'confiture', 'marmelade', 'compote',
            'gelée', 'coulis', 'purée de', 'crème de', 'fond brun', 'demi-glace',
            'glace de', 'levure', 'bicarbonate', 'gélatine', 'agar', 'maïzena',
            'glucose', 'fructose', 'stevia', 'chocolat', 'cacao', 'café',
            'thé', 'tisane', 'eau', 'vin', 'bière', 'cidre', 'cognac',
            'rhum', 'whisky', 'cointreau', 'armagnac', 'calvados',
        ],
    ];

    // ── Mois de saison par pattern (légumes et fruits) ────────────────────────

    /** @var array<string, list<int>> */
    private const SAISON_PATTERNS = [
        'aubergine'       => [7, 8, 9, 10],
        'courgette'       => [6, 7, 8, 9],
        'tomate'          => [6, 7, 8, 9, 10],
        'poivron'         => [7, 8, 9, 10],
        'carotte'         => [7, 8, 9, 10, 11, 12],
        'oignon'          => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        'échalote'        => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        'poireau'         => [9, 10, 11, 12, 1, 2, 3],
        'champignon'      => [9, 10, 11],
        'portobello'      => [9, 10, 11],
        'asperge'         => [4, 5, 6],
        'épinard'         => [4, 5, 6, 7, 8, 9, 10],
        'chou-fleur'      => [9, 10, 11, 12, 1, 2],
        'brocoli'         => [6, 7, 8],
        'chou'            => [9, 10, 11, 12, 1, 2, 3],
        'céleri'          => [9, 10, 11, 12, 1, 2],
        'fenouil'         => [8, 9, 10],
        'concombre'       => [6, 7, 8, 9],
        'salade'          => [4, 5, 6, 7, 8, 9, 10],
        'sucrine'         => [4, 5, 6, 7, 8, 9, 10],
        'roquette'        => [4, 5, 6, 7, 8, 9, 10],
        'mâche'           => [4, 5, 6, 7, 8, 9, 10],
        'laitue'          => [4, 5, 6, 7, 8, 9, 10],
        'patate douce'    => [9, 10, 11, 12, 1],
        'potimarron'      => [9, 10, 11, 12],
        'courge'          => [9, 10, 11, 12],
        'topinambour'     => [10, 11, 12, 1, 2],
        'panais'          => [10, 11, 12, 1, 2, 3],
        'radis'           => [4, 5, 6, 7, 8, 9],
        'haricots verts'  => [6, 7, 8, 9],
        'haricot vert'    => [6, 7, 8, 9],
        'pois'            => [5, 6, 7, 8],
        'maïs'            => [8, 9],
        'navet'           => [10, 11, 12, 1, 2],
        'betterave'       => [7, 8, 9, 10, 11, 12],
        'artichaut'       => [5, 6, 7, 8, 9, 10],
        'endive'          => [11, 12, 1, 2, 3],
        // Fruits
        'pomme'           => [8, 9, 10, 11],
        'poire'           => [8, 9, 10, 11],
        'citron'          => [11, 12, 1, 2, 3],
        'orange'          => [11, 12, 1, 2, 3],
        'pamplemousse'    => [11, 12, 1, 2, 3],
        'clémentine'      => [11, 12, 1, 2, 3],
        'mandarine'       => [11, 12, 1, 2, 3],
        'fraise'          => [4, 5, 6, 7],
        'framboise'       => [6, 7, 8],
        'myrtille'        => [7, 8],
        'pêche'           => [6, 7, 8, 9],
        'nectarine'       => [6, 7, 8, 9],
        'abricot'         => [6, 7, 8, 9],
        'prune'           => [8, 9, 10],
        'raisin'          => [8, 9, 10],
        'melon'           => [6, 7, 8, 9],
        'mangue'          => [5, 6, 7, 8, 9],
        'grenade'         => [10, 11, 12, 1],
        'avocat'          => [10, 11, 12, 1, 2, 3],
        'cerise'          => [5, 6, 7],
        'figue'           => [8, 9, 10],
        'cassis'          => [7, 8, 9],
        'groseille'       => [7, 8, 9],
        'mûre'            => [7, 8, 9],
        'airelle'         => [7, 8, 9],
    ];

    /**
     * Détermine le TypeIngredient et les mois de saison pour un nom d'ingrédient.
     *
     * @param array<string, TypeIngredient> $typesIndexedBySlug Résultat de TypeIngredientRepository::findAllIndexedBySlug()
     * @return array{0: TypeIngredient, 1: list<int>|null}
     */
    public function classify(string $nom, array $typesIndexedBySlug): array
    {
        $nomLower = mb_strtolower($nom);

        $type = $this->detectType($nomLower, $typesIndexedBySlug);
        $moisSaison = $this->detectMoisSaison($nomLower);

        return [$type, $moisSaison];
    }

    /**
     * @param array<string, TypeIngredient> $typesIndexedBySlug
     */
    private function detectType(string $nomLower, array $typesIndexedBySlug): TypeIngredient
    {
        // ── Exclusions prioritaires ────────────────────────────────────────────
        // Ces chaînes doivent être exclues des patterns généraux avant toute détection.

        // "rôti" dans le nom (ex: "chou-fleur rôti") ne doit pas classifier en viande
        // "pommes de terre" ne doit pas classifier en fruit via "pomme"
        // "haricots blancs/rouges/noirs" → féculent (avant le pattern "haricot" → légume)
        // "pois chiches" → féculent (avant "pois" → légume)

        // Vérification d'abord dans féculent pour les cas qui matcheraient ailleurs
        $feculentPriority = [
            'pomme de terre', 'pommes de terre',
            'haricots blancs', 'haricots rouges', 'haricots noirs',
            'pois chiche', 'pois chiches',
            'flageolets', 'pois cassés',
        ];
        foreach ($feculentPriority as $pattern) {
            if (str_contains($nomLower, mb_strtolower($pattern))) {
                return $typesIndexedBySlug['feculent'] ?? $typesIndexedBySlug['autre'];
            }
        }

        foreach (self::TYPE_PATTERNS as $slug => $patterns) {
            foreach ($patterns as $pattern) {
                $patternLower = mb_strtolower($pattern);

                // Exclure "rôti" comme pattern viande quand c'est un mode de cuisson
                // (présent dans d'autres contextes comme "chou-fleur rôti")
                if ($slug === 'viande' && $patternLower === 'rôti') {
                    // "rôti" comme viande uniquement si c'est un rôti de viande (ex: "rôti de porc")
                    // On l'ignore comme classificateur standalone
                    continue;
                }

                if (str_contains($nomLower, $patternLower)) {
                    return $typesIndexedBySlug[$slug] ?? $typesIndexedBySlug['autre'];
                }
            }
        }

        return $typesIndexedBySlug['autre'];
    }

    /** @return list<int>|null */
    private function detectMoisSaison(string $nomLower): ?array
    {
        // "pommes de terre" n'est pas un fruit — on l'exclut de "pomme"
        if (str_contains($nomLower, 'pomme de terre') || str_contains($nomLower, 'pommes de terre')) {
            return null;
        }

        // "chou-fleur" doit primer sur "chou"
        if (str_contains($nomLower, 'chou-fleur') || str_contains($nomLower, 'choufleur')) {
            return self::SAISON_PATTERNS['chou-fleur'];
        }

        foreach (self::SAISON_PATTERNS as $pattern => $mois) {
            if (str_contains($nomLower, mb_strtolower($pattern))) {
                return $mois;
            }
        }

        return null;
    }
}
