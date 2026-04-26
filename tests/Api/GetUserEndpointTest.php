<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCase;

class GetUserEndpointTest extends ApiTestCase
{
    public function testGetUserReturnsProfile(): void
    {
        $this->client->request('GET', '/api/user', [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $json = $this->json();

        $this->assertArrayHasKey('data', $json);
        $data = $json['data'];

        $this->assertSame('user@kubo.dev', $data['email']);
        $this->assertSame('Jean', $data['firstName']);
        $this->assertSame('Dupont', $data['lastName']);
        $this->assertContains('ROLE_USER', $data['roles']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('createdAt', $data);
    }

    public function testGetUserRequiresAuth(): void
    {
        $this->client->request('GET', '/api/user', [], [], $this->apiHeaders());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserRequiresApiKey(): void
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');
        $this->client->request('GET', '/api/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }
}
