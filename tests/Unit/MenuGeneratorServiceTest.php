<?php

namespace App\Tests\Unit;

use App\Entity\Recette;
use App\Repository\RecetteRepository;
use App\Service\MenuGeneratorService;
use App\Service\MenuScoringService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MenuScoringService is final; we use the real instance.
 * Recette mocks return an empty ingredient collection so score = 0.0.
 */
class MenuGeneratorServiceTest extends TestCase
{
    private MenuGeneratorService $service;

    /** @var RecetteRepository&MockObject */
    private RecetteRepository $repo;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(RecetteRepository::class);
        $scoring       = new MenuScoringService();
        $this->service = new MenuGeneratorService($this->repo, $scoring);
    }

    /** Create a Recette mock with no ingredients (score = 0). */
    private function makeRecette(): Recette
    {
        $r = $this->createMock(Recette::class);
        $r->method('getRecetteIngredients')->willReturn(new ArrayCollection());

        return $r;
    }

    // ── Catalogue size ────────────────────────────────────────────────────────

    public function testCatalogueSizeIsCappedAt70(): void
    {
        $recettes = array_map(fn () => $this->makeRecette(), range(1, 100));

        $this->repo->method('findAllForMenu')->willReturn($recettes);

        $result = $this->service->buildOrderedCatalogue(null, 2026, 12);

        $this->assertSame(100, $result['total']);
        $this->assertSame(70, $result['catalogue_size']);
    }

    public function testCatalogueSizeWithFewerThan70Recipes(): void
    {
        $recettes = array_map(fn () => $this->makeRecette(), range(1, 5));

        $this->repo->method('findAllForMenu')->willReturn($recettes);

        $result = $this->service->buildOrderedCatalogue(null, 2026, 12);

        $this->assertSame(5, $result['catalogue_size']);
    }

    // ── Determinism ───────────────────────────────────────────────────────────

    public function testSameUserAndWeekProduceSameOrder(): void
    {
        $recettes = array_map(fn () => $this->makeRecette(), range(1, 10));

        $this->repo->method('findAllForMenu')->willReturn($recettes);

        $result1 = $this->service->buildOrderedCatalogue('user-id', 2026, 12);
        $result2 = $this->service->buildOrderedCatalogue('user-id', 2026, 12);

        $this->assertSame($result1['recettes'], $result2['recettes']);
    }

    // ── Null userId (guest) ───────────────────────────────────────────────────

    public function testNullUserIdDoesNotThrow(): void
    {
        $recettes = array_map(fn () => $this->makeRecette(), range(1, 3));

        $this->repo->method('findAllForMenu')->willReturn($recettes);

        $result = $this->service->buildOrderedCatalogue(null, 2026, 12);

        $this->assertCount(3, $result['recettes']);
    }
}
