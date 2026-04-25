<?php

namespace App\Tests\Security;

use App\Tests\ApiTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Tests register, login and token refresh flows.
 */
class AuthEndpointTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the rate limiter so register tests always start from a clean slate
        /** @var RateLimiterFactory $factory */
        $factory = static::getContainer()->get('limiter.register_api');
        $factory->create('127.0.0.1')->reset();
    }

    // ── Register ─────────────────────────────────────────────────────────────

    public function testRegisterCreatesUser(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Alice', 'lastName' => 'Martin', 'email' => 'new@kubo.dev', 'password' => 'NewPass1']),
        );

        $this->assertResponseStatusCodeSame(201);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $json);
    }

    public function testRegisterWithoutFirstNameReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['lastName' => 'Martin', 'email' => 'new2@kubo.dev', 'password' => 'NewPass1']),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterWithInvalidEmailReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Alice', 'lastName' => 'Martin', 'email' => 'not-an-email', 'password' => 'Password1']),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterWithWeakPasswordReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Alice', 'lastName' => 'Martin', 'email' => 'weak@kubo.dev', 'password' => 'short']),
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterDuplicateEmailReturns409(): void
    {
        // user@kubo.dev already loaded by AppFixtures
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['firstName' => 'Alice', 'lastName' => 'Martin', 'email' => 'user@kubo.dev', 'password' => 'Password1']),
        );

        $this->assertResponseStatusCodeSame(409);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function testLoginReturnsTokenAndRefreshToken(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['email' => 'user@kubo.dev', 'password' => 'Password1']),
        );

        $this->assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['email' => 'user@kubo.dev', 'password' => 'WrongPass1']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // ── Refresh token ─────────────────────────────────────────────────────────

    public function testRefreshTokenReturnsNewAccessToken(): void
    {
        // First login to get a refresh token
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['email' => 'user@kubo.dev', 'password' => 'Password1']),
        );

        $loginData    = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $loginData['refresh_token'];

        // Use it to get a new access token
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['refresh_token' => $refreshToken]),
        );

        $this->assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $json);
    }

    public function testRefreshWithInvalidTokenReturns401(): void
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['refresh_token' => 'invalid-token']),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
