<?php

namespace App\Tests\Api;

use App\Tests\ApiTestCase;

class PostRegisterEndpointTest extends ApiTestCase
{
    // ── Succès ────────────────────────────────────────────────────────────────

    public function testRegisterCreatesAccount(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'alice@test.dev',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Compte créé avec succès.', $this->json()['message']);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function testRegisterRejectsEmptyFirstName(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => '',
            'lastName'  => 'Martin',
            'email'     => 'bob@test.dev',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('firstName', $this->json()['errors']);
    }

    public function testRegisterRejectsEmptyLastName(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => '',
            'email'     => 'bob@test.dev',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('lastName', $this->json()['errors']);
    }

    public function testRegisterRejectsInvalidEmail(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'pas-un-email',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('email', $this->json()['errors']);
    }

    public function testRegisterRejectsTooShortPassword(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'alice2@test.dev',
            'password'  => 'Ab1',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('password', $this->json()['errors']);
    }

    public function testRegisterRejectsPasswordWithoutUppercase(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'alice3@test.dev',
            'password'  => 'nouppercase1',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('password', $this->json()['errors']);
    }

    public function testRegisterRejectsPasswordWithoutDigit(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'alice4@test.dev',
            'password'  => 'NoDigitHere',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('password', $this->json()['errors']);
    }

    // ── Unicité email ─────────────────────────────────────────────────────────

    public function testRegisterRejectsDuplicateEmail(): void
    {
        // user@kubo.dev est créé par AppFixtures
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Alice',
            'lastName'  => 'Martin',
            'email'     => 'user@kubo.dev',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(409);
        $this->assertArrayHasKey('error', $this->json());
    }

    // ── Création de UserSettings ──────────────────────────────────────────────

    public function testRegisterCreatesUserSettings(): void
    {
        $this->client->request('POST', '/api/register', [], [], $this->apiHeaders(['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Bob',
            'lastName'  => 'Dupont',
            'email'     => 'bob.dupont@test.dev',
            'password'  => 'SecurePass1',
        ]));

        $this->assertResponseStatusCodeSame(201);

        // On peut se connecter et accéder aux settings
        $token = $this->loginAs('bob.dupont@test.dev', 'SecurePass1');
        $headers = $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']);

        $this->client->request('GET', '/api/settings', [], [], $headers);
        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];

        $this->assertArrayHasKey('portionsDefault', $data);
        $this->assertSame(2, $data['portionsDefault']);
    }
}
