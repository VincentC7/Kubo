<?php

namespace App\Tests\Security;

use App\Tests\ApiTestCase;

/**
 * Tests that the X-Api-Key middleware correctly guards /api/* routes.
 */
class ApiKeyEndpointTest extends ApiTestCase
{
    // ── Without any key ───────────────────────────────────────────────────────

    public function testNoApiKeyOnApiRouteReturns401(): void
    {
        $this->client->request('GET', '/api/catalogue?week=2026-W12');

        $this->assertResponseStatusCodeSame(401);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }

    // ── Wrong key ─────────────────────────────────────────────────────────────

    public function testWrongApiKeyReturns401(): void
    {
        $this->client->request('GET', '/api/catalogue?week=2026-W12', [], [], [
            'HTTP_X-API-KEY' => 'wrong-key',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // ── Correct key ───────────────────────────────────────────────────────────

    public function testCorrectApiKeyAllowsAccess(): void
    {
        $this->client->request('GET', '/api/catalogue?week=2026-W12', [], [], $this->apiHeaders());

        // Could be 200 or something else (e.g. empty DB), but not 401
        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }

    // ── /api/doc is exempt from api key check ─────────────────────────────────

    public function testApiDocIsPublicWithoutApiKey(): void
    {
        $this->client->request('GET', '/api/doc');

        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }
}
