<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\RecetteFixtures;
use App\DataFixtures\UserDataFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Base class for all API tests.
 *
 * Provides helpers to inject the X-Api-Key header and obtain JWT tokens.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Purge le cache rate limiter pour éviter les 429 entre les tests
        /** @var AdapterInterface $rateLimiterCache */
        $rateLimiterCache = static::getContainer()->get('cache.rate_limiter');
        $rateLimiterCache->clear();
        $this->loadFixtures();
    }

    // ── Fixture loading ───────────────────────────────────────────────────────

    protected function loadFixtures(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $appFixtures     = static::getContainer()->get(AppFixtures::class);
        $recetteFixtures = new RecetteFixtures();
        $userFixtures    = static::getContainer()->get(UserDataFixtures::class);

        $loader = new Loader();
        $loader->addFixture($appFixtures);
        $loader->addFixture($recetteFixtures);
        $loader->addFixture($userFixtures);

        $purger   = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    // ── API key helpers ───────────────────────────────────────────────────────

    protected function apiKey(): string
    {
        return (string) ($_ENV['API_KEY'] ?? $_SERVER['API_KEY'] ?? getenv('API_KEY'));
    }

    protected function apiHeaders(array $extra = []): array
    {
        return array_merge(['HTTP_X-API-KEY' => $this->apiKey()], $extra);
    }

    // ── JWT helpers ───────────────────────────────────────────────────────────

    protected function loginAs(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            json_encode(['email' => $email, 'password' => $password]),
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), 'Login failed: ' . $response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data, 'Login response must contain token');

        return $data['token'];
    }

    protected function authHeaders(string $token, array $extra = []): array
    {
        return $this->apiHeaders(array_merge(
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $extra,
        ));
    }

    /**
     * Shortcut : login as main user and return auth headers with JSON content-type.
     */
    protected function userJsonHeaders(): array
    {
        $token = $this->loginAs('user@kubo.dev', 'Password1');
        return $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']);
    }

    /**
     * Shortcut : login as second user.
     */
    protected function otherJsonHeaders(): array
    {
        $token = $this->loginAs('other@kubo.dev', 'Password1');
        return $this->authHeaders($token, ['CONTENT_TYPE' => 'application/json']);
    }

    // ── JSON response helpers ─────────────────────────────────────────────────

    protected function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
