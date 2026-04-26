<?php

namespace App\Tests\Api;

use App\Entity\InventoryItem;
use App\Entity\User;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class InventoryEndpointTest extends ApiTestCase
{
    // ── GET /api/inventory ───────────────────────────────────────────────────

    public function testGetInventoryReturnsItems(): void
    {
        $this->client->request('GET', '/api/inventory', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $json = $this->json();

        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertCount(3, $json['data']); // ok + soon + expired
        $this->assertSame(3, $json['meta']['total']);
        $this->assertSame(1, $json['meta']['expiringSoon']);
        $this->assertSame(1, $json['meta']['expired']);
    }

    public function testGetInventoryItemHasExpectedShape(): void
    {
        $this->client->request('GET', '/api/inventory', [], [], $this->userJsonHeaders());
        $item = $this->json()['data'][0];

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('unit', $item);
        $this->assertArrayHasKey('category', $item);
        $this->assertArrayHasKey('expiresAt', $item);
        $this->assertArrayHasKey('daysUntilExpiry', $item);
        $this->assertArrayHasKey('status', $item);
    }

    public function testGetInventoryStatusValues(): void
    {
        $this->client->request('GET', '/api/inventory', [], [], $this->userJsonHeaders());
        $items = $this->json()['data'];

        $statuses = array_column($items, 'status');
        $this->assertContains('ok', $statuses);
        $this->assertContains('expiring_soon', $statuses);
        $this->assertContains('expired', $statuses);
    }

    public function testGetInventoryFilterByExpiringSoon(): void
    {
        $this->client->request('GET', '/api/inventory?expiring_soon=true', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $json = $this->json();
        $data = $json['data'];
        $this->assertCount(1, $data);
        $this->assertSame('expiring_soon', $data[0]['status']);
        // Les méta sont toujours calculés sur l'ensemble complet
        $this->assertSame(3, $json['meta']['total']);
        $this->assertSame(1, $json['meta']['expiringSoon']);
        $this->assertSame(1, $json['meta']['expired']);
    }

    public function testGetInventoryFilterByCategory(): void
    {
        $this->client->request('GET', '/api/inventory?category=légume', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];
        $this->assertCount(1, $data);
        $this->assertSame('Carottes', $data[0]['name']);
    }

    public function testGetInventoryRequiresAuth(): void
    {
        $this->client->request('GET', '/api/inventory', [], [], $this->apiHeaders());
        $this->assertResponseStatusCodeSame(401);
    }

    // ── POST /api/inventory ──────────────────────────────────────────────────

    public function testPostInventoryCreatesItem(): void
    {
        $this->client->request('POST', '/api/inventory', [], [], $this->userJsonHeaders(), json_encode([
            'name'      => 'Farine',
            'quantity'  => 500,
            'unit'      => 'g',
            'category'  => 'féculent',
            'expiresAt' => '2026-12-31',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];

        $this->assertSame('Farine', $data['name']);
        $this->assertSame(500.0, (float) $data['quantity']);
        $this->assertSame('g', $data['unit']);
        $this->assertSame('2026-12-31', $data['expiresAt']);
        $this->assertSame('ok', $data['status']);
    }

    public function testPostInventoryWithoutExpiry(): void
    {
        $this->client->request('POST', '/api/inventory', [], [], $this->userJsonHeaders(), json_encode([
            'name'     => 'Sel',
            'quantity' => 1,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];
        $this->assertNull($data['expiresAt']);
        $this->assertNull($data['status']);
    }

    public function testPostInventoryValidatesName(): void
    {
        $this->client->request('POST', '/api/inventory', [], [], $this->userJsonHeaders(), json_encode([
            'name'     => '',
            'quantity' => 1,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('name', $this->json()['errors']);
    }

    public function testPostInventoryValidatesQuantity(): void
    {
        $this->client->request('POST', '/api/inventory', [], [], $this->userJsonHeaders(), json_encode([
            'name'     => 'Test',
            'quantity' => -5,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('quantity', $this->json()['errors']);
    }

    public function testPostInventoryValidatesExpiresAtFormat(): void
    {
        $this->client->request('POST', '/api/inventory', [], [], $this->userJsonHeaders(), json_encode([
            'name'      => 'Test',
            'quantity'  => 1,
            'expiresAt' => 'pas-une-date',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('expiresAt', $this->json()['errors']);
    }

    // ── PATCH /api/inventory/{id} ────────────────────────────────────────────

    public function testPatchInventoryUpdatesFields(): void
    {
        $id = $this->getOkItemId();

        $this->client->request('PATCH', '/api/inventory/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'name'     => 'Carottes bio',
            'quantity' => 250,
        ]));

        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];
        $this->assertSame('Carottes bio', $data['name']);
        $this->assertSame(250.0, (float) $data['quantity']);
    }

    public function testPatchInventoryClearsExpiry(): void
    {
        $id = $this->getSoonItemId();

        $this->client->request('PATCH', '/api/inventory/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'expiresAt' => null,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertNull($this->json()['data']['expiresAt']);
        $this->assertNull($this->json()['data']['status']);
    }

    public function testPatchInventoryReturns404ForOtherUser(): void
    {
        $id = $this->getOtherItemId();

        $this->client->request('PATCH', '/api/inventory/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'name' => 'Hack',
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchInventoryReturns404ForUnknown(): void
    {
        $this->client->request('PATCH', '/api/inventory/00000000-0000-0000-0000-000000000000', [], [], $this->userJsonHeaders(), json_encode([
            'name' => 'X',
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    // ── DELETE /api/inventory/{id} ───────────────────────────────────────────

    public function testDeleteInventoryItem(): void
    {
        $id = $this->getOkItemId();

        $this->client->request('DELETE', '/api/inventory/' . $id, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/inventory', [], [], $this->userJsonHeaders());
        $this->assertSame(2, $this->json()['meta']['total']);
    }

    public function testDeleteInventoryReturns404ForOtherUser(): void
    {
        $id = $this->getOtherItemId();

        $this->client->request('DELETE', '/api/inventory/' . $id, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteInventoryReturns404ForUnknown(): void
    {
        $this->client->request('DELETE', '/api/inventory/00000000-0000-0000-0000-000000000000', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getUserItem(string $name): InventoryItem
    {
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'user@kubo.dev']);
        return $em->getRepository(InventoryItem::class)->findOneBy(['name' => $name, 'user' => $user]);
    }

    private function getOkItemId(): string
    {
        return (string) $this->getUserItem('Carottes')->getId();
    }

    private function getSoonItemId(): string
    {
        return (string) $this->getUserItem('Yaourt')->getId();
    }

    private function getOtherItemId(): string
    {
        $em    = static::getContainer()->get(EntityManagerInterface::class);
        $other = $em->getRepository(User::class)->findOneBy(['email' => 'other@kubo.dev']);
        return (string) $em->getRepository(InventoryItem::class)
            ->findOneBy(['name' => 'Beurre', 'user' => $other])
            ->getId();
    }
}
