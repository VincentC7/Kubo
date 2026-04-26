<?php

namespace App\Tests\Api;

use App\DataFixtures\UserDataFixtures;
use App\Entity\ShoppingItem;
use App\Entity\ShoppingList;
use App\Entity\User;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ShoppingEndpointTest extends ApiTestCase
{
    private string $week;

    protected function setUp(): void
    {
        parent::setUp();
        $this->week = UserDataFixtures::WEEK;
    }

    // ── GET /api/shopping ────────────────────────────────────────────────────

    public function testGetShoppingReturnsListWithItems(): void
    {
        $this->client->request('GET', '/api/shopping?week=' . $this->week, [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];

        $this->assertNotNull($data);
        $this->assertSame($this->week, $data['week']);
        $this->assertCount(2, $data['items']);
    }

    public function testGetShoppingReturnsNullForUnknownWeek(): void
    {
        $this->client->request('GET', '/api/shopping?week=2000-W01', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->json()['data']);
    }

    public function testGetShoppingRejectsInvalidWeek(): void
    {
        $this->client->request('GET', '/api/shopping?week=invalid', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetShoppingItemHasExpectedShape(): void
    {
        $this->client->request('GET', '/api/shopping?week=' . $this->week, [], [], $this->userJsonHeaders());
        $item = $this->json()['data']['items'][0];

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('ingredientName', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('unit', $item);
        $this->assertArrayHasKey('category', $item);
        $this->assertArrayHasKey('checked', $item);
        $this->assertArrayHasKey('source', $item);
    }

    public function testGetShoppingRequiresAuth(): void
    {
        $this->client->request('GET', '/api/shopping?week=' . $this->week, [], [], $this->apiHeaders());
        $this->assertResponseStatusCodeSame(401);
    }

    // ── POST /api/shopping/generate ──────────────────────────────────────────

    public function testGenerateCreatesListFromPlanning(): void
    {
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => $this->week,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];

        $this->assertSame($this->week, $data['week']);
        $this->assertNotEmpty($data['items']);

        // Les items source: planning doivent être présents
        $sources = array_column($data['items'], 'source');
        $this->assertContains('planning', $sources);
    }

    public function testGeneratePreservesManualItems(): void
    {
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => $this->week,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $sources = array_column($this->json()['data']['items'], 'source');
        $this->assertContains('manual', $sources);
    }

    public function testGenerateWithNoPlanningReturnsEmptyList(): void
    {
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => '2000-W01',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];
        $this->assertSame('2000-W01', $data['week']);
        $this->assertEmpty($data['items']);
    }

    public function testGenerateAfterDeletingAllPlanningItemsKeepsManualItems(): void
    {
        // Le planning W18 a 2 entrées et la liste a 1 item manuel
        // On génère d'abord pour avoir des items planning
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => $this->week,
        ]));
        $this->assertResponseStatusCodeSame(201);

        // On supprime toutes les entrées du planning de cette semaine
        $this->client->request('GET', '/api/planning?week=' . $this->week, [], [], $this->userJsonHeaders());
        $entries = $this->json()['data'];
        foreach ($entries as $entry) {
            $this->client->request('DELETE', '/api/planning/' . $entry['id'], [], [], $this->userJsonHeaders());
            $this->assertResponseStatusCodeSame(204);
        }

        // Régénère avec planning vide : ne doit pas faire 404, doit vider les items planning
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => $this->week,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];

        // Seuls les items manuels restent
        $sources = array_column($data['items'], 'source');
        $this->assertNotContains('planning', $sources);
        $this->assertContains('manual', $sources);
    }

    public function testGenerateRejectsInvalidWeek(): void
    {
        $this->client->request('POST', '/api/shopping/generate', [], [], $this->userJsonHeaders(), json_encode([
            'week' => 'bad',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    // ── POST /api/shopping/items ─────────────────────────────────────────────

    public function testPostItemAddsManualItem(): void
    {
        $this->client->request('POST', '/api/shopping/items', [], [], $this->userJsonHeaders(), json_encode([
            'week'           => $this->week,
            'ingredientName' => 'Parmesan',
            'quantity'       => 100,
            'unit'           => 'g',
            'category'       => 'fromage',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];

        $this->assertSame('Parmesan', $data['ingredientName']);
        $this->assertSame(100.0, (float) $data['quantity']);
        $this->assertSame('g', $data['unit']);
        $this->assertSame('manual', $data['source']);
        $this->assertFalse($data['checked']);
    }

    public function testPostItemCreatesListIfNotExists(): void
    {
        $this->client->request('POST', '/api/shopping/items', [], [], $this->userJsonHeaders(), json_encode([
            'week'           => '2030-W01',
            'ingredientName' => 'Sel',
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testPostItemValidatesIngredientName(): void
    {
        $this->client->request('POST', '/api/shopping/items', [], [], $this->userJsonHeaders(), json_encode([
            'week'           => $this->week,
            'ingredientName' => '',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('ingredientName', $this->json()['errors']);
    }

    public function testPostItemValidatesWeek(): void
    {
        $this->client->request('POST', '/api/shopping/items', [], [], $this->userJsonHeaders(), json_encode([
            'week'           => 'pas-une-semaine',
            'ingredientName' => 'Sel',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('week', $this->json()['errors']);
    }

    // ── PATCH /api/shopping/items/{id} ───────────────────────────────────────

    public function testPatchItemChecksItem(): void
    {
        $id = $this->getItem1Id();

        $this->client->request('PATCH', '/api/shopping/items/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'checked' => true,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->json()['data']['checked']);
    }

    public function testPatchItemUpdatesQuantityAndUnit(): void
    {
        $id = $this->getItem1Id();

        $this->client->request('PATCH', '/api/shopping/items/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'quantity' => 800,
            'unit'     => 'g',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSame(800.0, (float) $this->json()['data']['quantity']);
        $this->assertSame('g', $this->json()['data']['unit']);
    }

    public function testPatchItemReturns404ForOtherUser(): void
    {
        $id = $this->getItem1Id();

        $this->client->request('PATCH', '/api/shopping/items/' . $id, [], [], $this->otherJsonHeaders(), json_encode([
            'checked' => true,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchItemReturns400ForInvalidChecked(): void
    {
        $id = $this->getItem1Id();

        $this->client->request('PATCH', '/api/shopping/items/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'checked' => 'yes',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('checked', $this->json()['errors']);
    }

    public function testPatchItemReturns404ForUnknown(): void
    {
        $this->client->request('PATCH', '/api/shopping/items/00000000-0000-0000-0000-000000000000', [], [], $this->userJsonHeaders(), json_encode([
            'checked' => true,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    // ── DELETE /api/shopping/items/{id} ─────────────────────────────────────

    public function testDeleteItemRemovesIt(): void
    {
        $id = $this->getItem2Id();

        $this->client->request('DELETE', '/api/shopping/items/' . $id, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(204);

        // La liste doit avoir 1 item de moins
        $this->client->request('GET', '/api/shopping?week=' . $this->week, [], [], $this->userJsonHeaders());
        $this->assertCount(1, $this->json()['data']['items']);
    }

    public function testDeleteItemReturns404ForOtherUser(): void
    {
        $id = $this->getItem1Id();

        $this->client->request('DELETE', '/api/shopping/items/' . $id, [], [], $this->otherJsonHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    // ── DELETE /api/shopping ─────────────────────────────────────────────────

    public function testDeleteShoppingListClearsIt(): void
    {
        $this->client->request('DELETE', '/api/shopping?week=' . $this->week, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/shopping?week=' . $this->week, [], [], $this->userJsonHeaders());
        $this->assertNull($this->json()['data']);
    }

    public function testDeleteShoppingListRequiresWeek(): void
    {
        $this->client->request('DELETE', '/api/shopping', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(400);
    }

    public function testDeleteShoppingListRejectsInvalidWeek(): void
    {
        $this->client->request('DELETE', '/api/shopping?week=invalid', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(400);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getItem1Id(): string
    {
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@kubo.dev']);
        $list = $em->getRepository(ShoppingList::class)->findOneBy(['user' => $user, 'week' => $this->week]);
        return (string) $em->getRepository(ShoppingItem::class)
            ->findOneBy(['shoppingList' => $list, 'source' => 'planning'])
            ->getId();
    }

    private function getItem2Id(): string
    {
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@kubo.dev']);
        $list = $em->getRepository(ShoppingList::class)->findOneBy(['user' => $user, 'week' => $this->week]);
        return (string) $em->getRepository(ShoppingItem::class)
            ->findOneBy(['shoppingList' => $list, 'source' => 'manual'])
            ->getId();
    }
}
