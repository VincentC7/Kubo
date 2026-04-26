<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCase;

class SettingsEndpointTest extends ApiTestCase
{
    // ── GET /api/settings ────────────────────────────────────────────────────

    public function testGetSettingsReturnsDefaults(): void
    {
        $this->client->request('GET', '/api/settings', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];

        $this->assertSame(2, $data['portionsDefault']);
        $this->assertSame(5, $data['mealsGoal']);
        $this->assertSame('week', $data['viewMode']);
        $this->assertIsArray($data['dietaryPrefs']);
        $this->assertIsArray($data['notifications']);
    }

    public function testGetSettingsRequiresAuth(): void
    {
        $this->client->request('GET', '/api/settings', [], [], $this->apiHeaders());
        $this->assertResponseStatusCodeSame(401);
    }

    // ── PATCH /api/settings ──────────────────────────────────────────────────

    public function testPatchSettingsUpdatesPortionsDefault(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'portionsDefault' => 4,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSame(4, $this->json()['data']['portionsDefault']);
    }

    public function testPatchSettingsUpdatesMealsGoal(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'mealsGoal' => 7,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSame(7, $this->json()['data']['mealsGoal']);
    }

    public function testPatchSettingsUpdatesViewMode(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'viewMode' => 'list',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSame('list', $this->json()['data']['viewMode']);
    }

    public function testPatchSettingsUpdatesDietaryPrefs(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'dietaryPrefs' => ['vegetarien', 'sans-gluten'],
        ]));

        $this->assertResponseIsSuccessful();
        $prefs = $this->json()['data']['dietaryPrefs'];
        $this->assertContains('vegetarien', $prefs);
        $this->assertContains('sans-gluten', $prefs);
    }

    public function testPatchSettingsUpdatesNotifications(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'notifications' => [
                'planningReminder' => false,
                'expiryAlert'      => true,
            ],
        ]));

        $this->assertResponseIsSuccessful();
        $notifs = $this->json()['data']['notifications'];
        $this->assertFalse($notifs['planningReminder']);
        $this->assertTrue($notifs['expiryAlert']);
    }

    public function testPatchSettingsIsPersisted(): void
    {
        // Mise à jour
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'portionsDefault' => 6,
            'viewMode'        => 'list',
        ]));
        $this->assertResponseIsSuccessful();

        // Re-lecture
        $this->client->request('GET', '/api/settings', [], [], $this->userJsonHeaders());
        $data = $this->json()['data'];
        $this->assertSame(6, $data['portionsDefault']);
        $this->assertSame('list', $data['viewMode']);
    }

    public function testPatchSettingsValidatesPortionsDefault(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'portionsDefault' => 0,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('portionsDefault', $this->json()['errors']);
    }

    public function testPatchSettingsValidatesPortionsDefaultMax(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'portionsDefault' => 99,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPatchSettingsValidatesMealsGoal(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'mealsGoal' => 0,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('mealsGoal', $this->json()['errors']);
    }

    public function testPatchSettingsValidatesViewMode(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'viewMode' => 'invalid',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('viewMode', $this->json()['errors']);
    }

    public function testPatchSettingsValidatesDietaryPrefs(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->userJsonHeaders(), json_encode([
            'dietaryPrefs' => ['inconnu'],
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('dietaryPrefs', $this->json()['errors']);
    }

    public function testPatchSettingsRequiresAuth(): void
    {
        $this->client->request('PATCH', '/api/settings', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'portionsDefault' => 4,
        ]));

        $this->assertResponseStatusCodeSame(401);
    }
}
