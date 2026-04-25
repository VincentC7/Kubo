<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCase;

/**
 * Tests pour PATCH /api/user et POST /api/user/password.
 */
class UserEndpointTest extends ApiTestCase
{
    // ── PATCH /api/user ─────────────────────────────────────────────────────────

    public function testUpdateProfileReturnsUpdatedData(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'PATCH',
            '/api/user',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Nouveau', 'lastName' => 'Nom']),
        );

        $this->assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Nouveau', $json['firstName']);
        $this->assertSame('Nom', $json['lastName']);
        $this->assertSame('user@kubo.dev', $json['email']);
    }

    public function testUpdateProfilePartialPatch(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'PATCH',
            '/api/user',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Seulement']),
        );

        $this->assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Seulement', $json['firstName']);
        // lastName inchangé
        $this->assertSame('Dupont', $json['lastName']);
    }

    public function testUpdateProfileWithEmptyFirstNameReturns400(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'PATCH',
            '/api/user',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => '']),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateProfileWithoutAuthReturns401(): void
    {
        $this->client->request(
            'PATCH',
            '/api/user',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Test']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // ── POST /api/user/password ─────────────────────────────────────────────────

    public function testChangePasswordSucceeds(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'POST',
            '/api/user/password',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['currentPassword' => 'Password1', 'newPassword' => 'NewPass2']),
        );

        $this->assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $json);
    }

    public function testChangePasswordWithWrongCurrentReturns400(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'POST',
            '/api/user/password',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['currentPassword' => 'WrongPass1', 'newPassword' => 'NewPass2']),
        );

        $this->assertResponseStatusCodeSame(400);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('currentPassword', $json['errors']);
    }

    public function testChangePasswordWithWeakNewPasswordReturns400(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');

        $this->client->request(
            'POST',
            '/api/user/password',
            [],
            [],
            $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']),
            json_encode(['currentPassword' => 'Password1', 'newPassword' => 'weak']),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testChangePasswordWithoutAuthReturns401(): void
    {
        $this->client->request(
            'POST',
            '/api/user/password',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['currentPassword' => 'Password1', 'newPassword' => 'NewPass2']),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
