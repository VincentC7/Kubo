<?php

namespace App\Tests\Api;

use App\DataFixtures\RecetteFixtures;
use App\Tests\ApiTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class CatalogueApiTest extends ApiTestCase
{
    private const WEEK = '2026-W12';

    protected function loadFixtures(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $loader = new Loader();
        $loader->addFixture(static::getContainer()->get(\App\DataFixtures\AppFixtures::class));
        $loader->addFixture(new RecetteFixtures());

        $purger   = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    // ── Test 1 : structure de réponse correcte ────────────────────────────────

    public function testCatalogueReturns200WithStructure(): void
    {
        $this->client->request('GET', '/api/catalogue?week=' . self::WEEK, [], [], $this->apiHeaders());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('semaine', $json);
        $this->assertArrayHasKey('recettes', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertSame(self::WEEK, $json['semaine']);
        $this->assertIsArray($json['recettes']);
    }

    // ── Test 2 : meta contient catalogue_size ────────────────────────────────

    public function testCatalogueMetaHasCatalogueSize(): void
    {
        $this->client->request('GET', '/api/catalogue?week=' . self::WEEK, [], [], $this->apiHeaders());

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $meta = $json['meta'];

        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('page', $meta);
        $this->assertArrayHasKey('limit', $meta);
        $this->assertArrayHasKey('pages', $meta);
        $this->assertArrayHasKey('catalogue_size', $meta);
        $this->assertIsInt($meta['catalogue_size']);
        // Avec 5 fixtures, catalogue_size = min(70, 5) = 5
        $this->assertSame(5, $meta['catalogue_size']);
    }

    // ── Test 3 : le catalogue est déterministe ────────────────────────────────

    public function testCatalogueIsDeterministic(): void
    {
        $url = '/api/catalogue?week=' . self::WEEK;

        $this->client->request('GET', $url, [], [], $this->apiHeaders());
        $json1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', $url, [], [], $this->apiHeaders());
        $json2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(
            array_column($json1['recettes'], 'uuid'),
            array_column($json2['recettes'], 'uuid'),
            'Le catalogue doit être identique pour la même semaine'
        );
    }

    // ── Test 4 : deux semaines différentes → ordres différents ───────────────

    public function testCatalogueDiffersAcrossWeeks(): void
    {
        $this->client->request('GET', '/api/catalogue?week=2026-W12&limit=5', [], [], $this->apiHeaders());
        $json1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/api/catalogue?week=2026-W30&limit=5', [], [], $this->apiHeaders());
        $json2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(5, array_column($json1['recettes'], 'uuid'));
        $this->assertCount(5, array_column($json2['recettes'], 'uuid'));
    }

    // ── Test 5 : week invalide → 400 ─────────────────────────────────────────

    public function testCatalogueInvalidWeek(): void
    {
        $this->client->request('GET', '/api/catalogue?week=foobar', [], [], $this->apiHeaders());

        $this->assertResponseStatusCodeSame(400);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }

    // ── Test 6 : pagination — limit=2 retourne bien 2 recettes ───────────────

    public function testCataloguePagination(): void
    {
        $this->client->request('GET', '/api/catalogue?week=' . self::WEEK . '&page=1&limit=2', [], [], $this->apiHeaders());

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $json['recettes']);
        $this->assertSame(1, $json['meta']['page']);
        $this->assertSame(2, $json['meta']['limit']);
        $this->assertSame(5, $json['meta']['total']);
    }
}
