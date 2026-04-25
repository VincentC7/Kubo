<?php

namespace App\Tests\Unit;

use App\Command\ImportRecettesCommand;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour les méthodes de parsing d'ImportRecettesCommand.
 *
 * Les méthodes parseMinutes, parseNutrition et parseIngredient sont privées,
 * on les teste via ReflectionMethod pour garantir leur robustesse sans
 * passer par l'ensemble de la commande.
 */
class ImportRecettesCommandParsingTest extends TestCase
{
    private \ReflectionMethod $parseMinutes;
    private \ReflectionMethod $parseNutrition;
    private \ReflectionMethod $parseIngredient;
    private \ReflectionMethod $normalizeAllergene;

    /** Instance partielle (on ne construit pas les dépendances réelles) */
    private ImportRecettesCommand $command;

    protected function setUp(): void
    {
        // Crée une instance sans appeler le constructeur pour accéder aux méthodes privées
        $this->command = (new \ReflectionClass(ImportRecettesCommand::class))
            ->newInstanceWithoutConstructor();

        $ref = new \ReflectionClass(ImportRecettesCommand::class);

        $this->parseMinutes = $ref->getMethod('parseMinutes');
        $this->parseMinutes->setAccessible(true);

        $this->parseNutrition = $ref->getMethod('parseNutrition');
        $this->parseNutrition->setAccessible(true);

        $this->parseIngredient = $ref->getMethod('parseIngredient');
        $this->parseIngredient->setAccessible(true);

        $this->normalizeAllergene = $ref->getMethod('normalizeAllergene');
        $this->normalizeAllergene->setAccessible(true);
    }

    private function minutes(string $value): ?int
    {
        return $this->parseMinutes->invoke($this->command, $value);
    }

    private function nutrition(mixed $value): ?float
    {
        return $this->parseNutrition->invoke($this->command, $value);
    }

    /** @return array{0: string|null, 1: string|null, 2: string} */
    private function ingredient(string $raw): array
    {
        return $this->parseIngredient->invoke($this->command, $raw);
    }

    private function allergene(string $value): string
    {
        return $this->normalizeAllergene->invoke($this->command, $value);
    }

    // ── parseMinutes ─────────────────────────────────────────────────────────

    public function testParseMinutesChaineVide(): void
    {
        $this->assertNull($this->minutes(''));
        $this->assertNull($this->minutes('   '));
    }

    public function testParseMinutesMinutesSeules(): void
    {
        $this->assertSame(25, $this->minutes('25 minutes'));
        $this->assertSame(45, $this->minutes('45 minutes'));
        $this->assertSame(5, $this->minutes('5 minute'));
    }

    public function testParseMinutesHeuresSeules(): void
    {
        $this->assertSame(60, $this->minutes('1 heure'));
        $this->assertSame(120, $this->minutes('2 heures'));
    }

    public function testParseMinutesHeuresEtMinutes(): void
    {
        $this->assertSame(70, $this->minutes('1 heure 10 minutes'));
        $this->assertSame(95, $this->minutes('1 heure 35 minutes'));
        $this->assertSame(125, $this->minutes('2 heures 5 minutes'));
    }

    public function testParseMinutesFormatCourt(): void
    {
        $this->assertSame(90, $this->minutes('1h30'));
        $this->assertSame(60, $this->minutes('1h'));
        $this->assertSame(75, $this->minutes('1h15'));
    }

    public function testParseMinutesTexteInconnu(): void
    {
        $this->assertNull($this->minutes('rapide'));
        $this->assertNull($this->minutes('variable'));
    }

    // ── parseNutrition ────────────────────────────────────────────────────────

    public function testParseNutritionNull(): void
    {
        $this->assertNull($this->nutrition(null));
        $this->assertNull($this->nutrition(''));
    }

    public function testParseNutritionEntier(): void
    {
        $this->assertSame(3343.0, $this->nutrition('3343 kJ'));
        $this->assertSame(45.0, $this->nutrition('45 g'));
        $this->assertSame(0.0, $this->nutrition('0 g'));
    }

    public function testParseNutritionDecimalVirgule(): void
    {
        $this->assertSame(2.5, $this->nutrition('2,5 g'));
        $this->assertSame(0.3, $this->nutrition('0,3 g'));
    }

    public function testParseNutritionDecimalPoint(): void
    {
        $this->assertSame(12.5, $this->nutrition('12.5 g'));
    }

    public function testParseNutritionNombreSeul(): void
    {
        $this->assertSame(100.0, $this->nutrition('100'));
        $this->assertSame(0.0, $this->nutrition('0'));
    }

    public function testParseNutritionTexteInconnu(): void
    {
        $this->assertNull($this->nutrition('traces'));
        $this->assertNull($this->nutrition('n.a.'));
        $this->assertNull($this->nutrition('-'));
    }

    // ── parseIngredient ───────────────────────────────────────────────────────

    public function testParseIngredientSimple(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('2 carottes');
        $this->assertSame('2', $quantite);
        $this->assertNull($unite);
        $this->assertSame('carottes', $nom);
    }

    public function testParseIngredientAvecUnite(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('200 g de farine');
        $this->assertSame('200', $quantite);
        $this->assertSame('g', $unite);
        $this->assertStringContainsString('farine', $nom);
    }

    public function testParseIngredientSansQuantite(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('sel');
        $this->assertNull($quantite);
        $this->assertNull($unite);
        $this->assertSame('sel', $nom);
    }

    public function testParseIngredientFractionUnicode(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('½ citron');
        $this->assertSame('1/2', $quantite);
        $this->assertStringContainsString('citron', $nom);
    }

    public function testParseIngredientQuartUnicode(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('¼ de litre de lait');
        $this->assertSame('1/4', $quantite);
        $this->assertStringContainsString('lait', $nom);
    }

    public function testParseIngredientCuillere(): void
    {
        [$quantite, $unite, $nom] = $this->ingredient('2 cuil. à soupe d\'huile');
        $this->assertSame('2', $quantite);
        $this->assertStringContainsString('cuil.', $unite ?? '');
        $this->assertStringContainsString('huile', $nom);
    }

    public function testParseIngredientNomSeulSansMatch(): void
    {
        // Si aucun pattern ne matche, retourne [null, null, $raw]
        [$quantite, $unite, $nom] = $this->ingredient('quelque chose d\'inhabituel');
        $this->assertIsString($nom);
        $this->assertNotEmpty($nom);
    }

    // ── normalizeAllergene ────────────────────────────────────────────────────

    public function testNormalizeAllergeneDoublon(): void
    {
        $this->assertSame('Gluten', $this->allergene('Gluten/Gluten'));
        $this->assertSame('Lait', $this->allergene('Lait/Lait'));
    }

    public function testNormalizeAllergeneNormal(): void
    {
        $this->assertSame('Gluten', $this->allergene('Gluten'));
        $this->assertSame('Fruits à coque', $this->allergene('Fruits à coque'));
    }

    public function testNormalizeAllergenePuceEnlevee(): void
    {
        $result = $this->allergene('• Gluten');
        $this->assertSame('Gluten', $result);
    }
}
