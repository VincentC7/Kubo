<?php

namespace App\Tests\Unit;

use App\Entity\TypeIngredient;
use App\Service\IngredientClassifier;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour IngredientClassifier.
 *
 * Vérifie la classification des types d'ingrédients et la détection des mois de saison,
 * avec un focus sur les cas limites (pommes de terre ≠ pomme, chou-fleur ≠ chou, etc.)
 */
class IngredientClassifierTest extends TestCase
{
    private IngredientClassifier $classifier;

    /** @var array<string, TypeIngredient> */
    private array $types;

    protected function setUp(): void
    {
        $this->classifier = new IngredientClassifier();
        $this->types      = $this->buildTypesMap();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array<string, TypeIngredient> */
    private function buildTypesMap(): array
    {
        $slugs = [
            'viande', 'poisson', 'legume', 'fruit', 'feculent',
            'produit_laitier', 'herbe_epice', 'condiment', 'autre',
        ];

        $map = [];
        foreach ($slugs as $slug) {
            $type = $this->createMock(TypeIngredient::class);
            $type->method('getSlug')->willReturn($slug);
            $map[$slug] = $type;
        }

        return $map;
    }

    private function classifyType(string $nom): string
    {
        [$type] = $this->classifier->classify($nom, $this->types);
        return $type->getSlug();
    }

    /** @return list<int>|null */
    private function classifyMois(string $nom): ?array
    {
        [, $mois] = $this->classifier->classify($nom, $this->types);
        return $mois;
    }

    // ── Classification des types ──────────────────────────────────────────────

    public function testViandeDetectee(): void
    {
        $this->assertSame('viande', $this->classifyType('blanc de poulet'));
        $this->assertSame('viande', $this->classifyType('Bœuf haché'));
        $this->assertSame('viande', $this->classifyType('lardons fumés'));
        $this->assertSame('viande', $this->classifyType('magret de canard'));
    }

    public function testPoissonDetecte(): void
    {
        $this->assertSame('poisson', $this->classifyType('saumon fumé'));
        $this->assertSame('poisson', $this->classifyType('crevettes décortiquées'));
        $this->assertSame('poisson', $this->classifyType('Saint-Jacques'));
        $this->assertSame('poisson', $this->classifyType('gambas'));
    }

    public function testLegumeDetecte(): void
    {
        $this->assertSame('legume', $this->classifyType('courgette jaune'));
        $this->assertSame('legume', $this->classifyType('épinards frais'));
        $this->assertSame('legume', $this->classifyType('gousses d\'ail'));
        $this->assertSame('legume', $this->classifyType('tomates cerises'));
    }

    public function testFruitDetecte(): void
    {
        $this->assertSame('fruit', $this->classifyType('citron vert'));
        $this->assertSame('fruit', $this->classifyType('mangue mûre'));
        $this->assertSame('fruit', $this->classifyType('avocat'));
    }

    public function testFeculentDetecte(): void
    {
        $this->assertSame('feculent', $this->classifyType('pâtes'));
        $this->assertSame('feculent', $this->classifyType('riz basmati'));
        $this->assertSame('feculent', $this->classifyType('lentilles vertes'));
        $this->assertSame('feculent', $this->classifyType('spaghetti'));
        $this->assertSame('feculent', $this->classifyType('pois chiches'));
    }

    public function testProduitLaitierDetecte(): void
    {
        $this->assertSame('produit_laitier', $this->classifyType('parmesan râpé'));
        $this->assertSame('produit_laitier', $this->classifyType('crème fraîche'));
        $this->assertSame('produit_laitier', $this->classifyType('mozzarella di bufala'));
        $this->assertSame('produit_laitier', $this->classifyType('fromage de chèvre'));
    }

    public function testHerbeEpiceDetectee(): void
    {
        $this->assertSame('herbe_epice', $this->classifyType('thym frais'));
        $this->assertSame('herbe_epice', $this->classifyType('paprika fumé'));
        $this->assertSame('herbe_epice', $this->classifyType('curcuma en poudre'));
        $this->assertSame('herbe_epice', $this->classifyType('herbes de provence'));
    }

    public function testCondimentDetecte(): void
    {
        $this->assertSame('condiment', $this->classifyType('huile d\'olive'));
        $this->assertSame('condiment', $this->classifyType('sauce soja'));
        $this->assertSame('condiment', $this->classifyType('vinaigre balsamique'));
        $this->assertSame('condiment', $this->classifyType('miel'));
    }

    public function testAutreRetourneSiAucunPatternMatche(): void
    {
        $this->assertSame('autre', $this->classifyType('zglorf mystérieux'));
        $this->assertSame('autre', $this->classifyType(''));
    }

    // ── Cas limites critiques ─────────────────────────────────────────────────

    /**
     * "pommes de terre" ne doit PAS être classé comme fruit (pattern "pomme").
     */
    public function testPommeDeTerreEstFeculent(): void
    {
        $this->assertSame('feculent', $this->classifyType('pommes de terre'));
        $this->assertSame('feculent', $this->classifyType('pomme de terre'));
    }

    /**
     * "pomme" seul doit bien être un fruit.
     */
    public function testPommeEstFruit(): void
    {
        $this->assertSame('fruit', $this->classifyType('pomme golden'));
        $this->assertSame('fruit', $this->classifyType('compote de pommes'));
    }

    /**
     * "chou-fleur" doit être classé légume (pattern "chou-fleur" avant "chou").
     */
    public function testChouFleurEstLegume(): void
    {
        $this->assertSame('legume', $this->classifyType('chou-fleur rôti'));
        $this->assertSame('legume', $this->classifyType('chou vert'));
    }

    /**
     * "haricots blancs" est un féculent (avant le pattern "haricot" légume).
     */
    public function testHaricotsBlancsSontFeculent(): void
    {
        $this->assertSame('feculent', $this->classifyType('haricots blancs'));
        $this->assertSame('feculent', $this->classifyType('haricots rouges'));
        $this->assertSame('feculent', $this->classifyType('haricots noirs'));
    }

    /**
     * "haricots verts" doit être classé légume.
     */
    public function testHaricotsVertsEstLegume(): void
    {
        $this->assertSame('legume', $this->classifyType('haricots verts'));
        $this->assertSame('legume', $this->classifyType('haricot vert'));
    }

    // ── Détection des mois de saison ──────────────────────────────────────────

    public function testTomateEnSaison(): void
    {
        $mois = $this->classifyMois('tomates fraîches');
        $this->assertIsArray($mois);
        $this->assertContains(7, $mois, 'Tomate doit être de saison en juillet');
        $this->assertContains(8, $mois);
        $this->assertNotContains(1, $mois, 'Tomate ne doit pas être de saison en janvier');
    }

    public function testCitronEnSaison(): void
    {
        $mois = $this->classifyMois('citron jaune');
        $this->assertIsArray($mois);
        $this->assertContains(12, $mois, 'Citron doit être de saison en décembre');
        $this->assertContains(1, $mois);
        $this->assertContains(2, $mois);
        $this->assertNotContains(7, $mois, 'Citron ne doit pas être de saison en juillet');
    }

    public function testPommeDeTerreNaPasDeMoisSaison(): void
    {
        $mois = $this->classifyMois('pommes de terre');
        $this->assertNull($mois, '"pommes de terre" ne doit pas avoir de mois de saison (pas un fruit)');
    }

    public function testChouFleurMoisSaison(): void
    {
        $mois = $this->classifyMois('chou-fleur');
        $this->assertIsArray($mois);
        // chou-fleur : [9, 10, 11, 12, 1, 2]
        $this->assertContains(11, $mois, 'Chou-fleur doit être de saison en novembre');
        $this->assertNotContains(6, $mois, 'Chou-fleur ne doit pas être de saison en juin');
    }

    public function testChouMoisSaisonNInclutPasChouFleur(): void
    {
        $moisChou      = $this->classifyMois('chou vert');
        $moisChouFleur = $this->classifyMois('chou-fleur');

        // Les deux sont non-null mais leurs saisons doivent être différentes
        $this->assertNotNull($moisChou);
        $this->assertNotNull($moisChouFleur);
        $this->assertNotSame($moisChou, $moisChouFleur, 'chou et chou-fleur doivent avoir des saisons différentes');
    }

    public function testIngredientSansPatternRetourneNull(): void
    {
        $mois = $this->classifyMois('huile d\'olive');
        $this->assertNull($mois, 'Un condiment ne doit pas avoir de mois de saison');

        $mois = $this->classifyMois('riz basmati');
        $this->assertNull($mois, 'Un féculent ne doit pas avoir de mois de saison');
    }

    public function testFraiseEnSaison(): void
    {
        $mois = $this->classifyMois('fraises');
        $this->assertIsArray($mois);
        $this->assertContains(5, $mois);
        $this->assertContains(6, $mois);
        $this->assertNotContains(12, $mois);
    }

    public function testAspergesEnSaison(): void
    {
        $mois = $this->classifyMois('asperges vertes');
        $this->assertIsArray($mois);
        $this->assertContains(4, $mois);
        $this->assertContains(5, $mois);
        $this->assertNotContains(11, $mois);
    }
}
