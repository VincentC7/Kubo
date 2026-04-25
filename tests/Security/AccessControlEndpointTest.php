<?php

namespace App\Tests\Security;

use App\Tests\ApiTestCase;

/**
 * Tests access control rules:
 * - /api/catalogue         → public (X-Api-Key only, no JWT)
 * - /api/recettes          → public (X-Api-Key only, no JWT)
 * - /api/ingredients/saison → public (X-Api-Key only, no JWT)
 */
class AccessControlEndpointTest extends ApiTestCase
{
    // ── /api/catalogue is public (no JWT required) ────────────────────────────

    public function testCatalogueAccessibleWithoutJwt(): void
    {
        $this->client->request('GET', '/api/catalogue?week=2026-W12', [], [], $this->apiHeaders());

        $this->assertResponseIsSuccessful();
    }

    // ── /api/recettes is public (no JWT required) ─────────────────────────────

    public function testRecettesAccessibleWithoutJwt(): void
    {
        $this->client->request('GET', '/api/recettes', [], [], $this->apiHeaders());

        $this->assertResponseIsSuccessful();
    }

    public function testRecettesWithValidJwtReturns200(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request('GET', '/api/recettes', [], [], $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
    }

    public function testRecettesDetailWithValidJwtReturns200OrNotFound(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        // UUID that doesn't exist should return 404, not 401
        $this->client->request(
            'GET',
            '/api/recettes/00000000-0000-0000-0000-000000000000',
            [],
            [],
            $this->authHeaders($token),
        );

        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }

    // ── /api/ingredients/saison is public (no JWT required) ──────────────────

    public function testSaisonAccessibleWithoutJwt(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->apiHeaders());

        $this->assertResponseIsSuccessful();
    }

    public function testSaisonWithValidJwtReturns200(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
    }

    // ── Admin can access user routes too ─────────────────────────────────────

    public function testAdminCanAccessRecettes(): void
    {
        $token = $this->loginAs('admin@kubo.dev', 'AdminPass1');

        $this->client->request('GET', '/api/recettes', [], [], $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
    }
}
