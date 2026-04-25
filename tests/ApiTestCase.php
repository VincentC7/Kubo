<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\RecetteFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for all API tests.
 *
 * Provides helpers to inject the X-Api-Key header and obtain JWT tokens.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    /** API key defined in .env (or .env.test) */
    private const API_KEY_ENV = 'API_KEY';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->loadFixtures();
    }

    // ── Fixture loading ───────────────────────────────────────────────────────

    /**
     * Override in subclasses to load additional or different fixtures.
     */
    protected function loadFixtures(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(AppFixtures::class));
        $loader->addFixture(new RecetteFixtures());

        $purger   = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    // ── API key helpers ───────────────────────────────────────────────────────

    /**
     * Returns the API key from the container parameters / env.
     */
    protected function apiKey(): string
    {
        return (string) ($_ENV['API_KEY'] ?? $_SERVER['API_KEY'] ?? getenv('API_KEY'));
    }

    /**
     * Returns the default server headers with X-Api-Key already set.
     */
    protected function apiHeaders(array $extra = []): array
    {
        return array_merge(['HTTP_X-API-KEY' => $this->apiKey()], $extra);
    }

    // ── JWT helpers ───────────────────────────────────────────────────────────

    /**
     * Logs in and returns the JWT access token.
     */
    protected function loginAs(string $email, string $password): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            array_merge(
                $this->apiHeaders(['CONTENT_TYPE' => 'application/json']),
            ),
            json_encode(['email' => $email, 'password' => $password]),
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode(), 'Login failed: ' . $response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data, 'Login response must contain token');

        return $data['token'];
    }

    /**
     * Returns server headers with both X-Api-Key and Authorization: Bearer set.
     */
    protected function authHeaders(string $token, array $extra = []): array
    {
        return $this->apiHeaders(array_merge(
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $extra,
        ));
    }
}
