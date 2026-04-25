<?php

namespace App\Tests\Api;

use App\DataFixtures\RecetteFixtures;
use App\Tests\ApiTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class GetSaisonEndpointTest extends ApiTestCase
{
    private string $token;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->loginAs('user@kubo.dev', 'Password1');
    }

    // ── Test 1 : GET /api/ingredients/saison retourne 200 avec la structure attendue ────────

    public function testSaisonReturns200WithStructure(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->authHeaders($this->token));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('mois', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('total', $json);
        $this->assertSame(6, $json['mois']);
        $this->assertIsArray($json['data']);
        $this->assertIsInt($json['total']);
        $this->assertSame(count($json['data']), $json['total']);
    }

    // ── Test 2 : chaque item a les champs nom, type, mois_saison ────────────────

    public function testSaisonItemStructure(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->authHeaders($this->token));

        $json  = json_decode($this->client->getResponse()->getContent(), true);
        $items = $json['data'];

        $this->assertNotEmpty($items, 'Mois 6 doit retourner au moins un fruit/légume');

        foreach ($items as $item) {
            $this->assertArrayHasKey('nom', $item);
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('mois_saison', $item);
            $this->assertContains($item['type'], ['fruit', 'legume']);
            $this->assertContains(6, $item['mois_saison']);
        }
    }

    // ── Test 3 : mois=6 retourne la salade romaine (légume, mois 4–10) ──────────

    public function testSaisonMois6ContientSalade(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->authHeaders($this->token));

        $json  = json_decode($this->client->getResponse()->getContent(), true);
        $noms  = array_column($json['data'], 'nom');

        $this->assertContains('salade romaine', $noms);
    }

    // ── Test 4 : mois=6 ne retourne PAS le citron (hors saison) ─────────────────

    public function testSaisonMois6NePasCitron(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=6', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $noms = array_column($json['data'], 'nom');

        $this->assertNotContains('citron', $noms);
    }

    // ── Test 5 : mois=1 retourne le citron (fruit, mois 11–12, 1–3) ─────────────

    public function testSaisonMois1ContientCitron(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=1', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $noms = array_column($json['data'], 'nom');

        $this->assertContains('citron', $noms);
    }

    // ── Test 6 : mois=8 ne retourne pas les champignons (mois 9–11) ─────────────

    public function testSaisonMois8PasChampignons(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=8', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $noms = array_column($json['data'], 'nom');

        $this->assertNotContains('champignons', $noms);
    }

    // ── Test 7 : mois=10 retourne les champignons ────────────────────────────────

    public function testSaisonMois10ContientChampignons(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=10', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $noms = array_column($json['data'], 'nom');

        $this->assertContains('champignons', $noms);
    }

    // ── Test 8 : sans paramètre mois, retourne 200 avec mois courant ─────────────

    public function testSaisonSansParametreRetourne200(): void
    {
        $this->client->request('GET', '/api/ingredients/saison', [], [], $this->authHeaders($this->token));

        $this->assertResponseIsSuccessful();

        $json       = json_decode($this->client->getResponse()->getContent(), true);
        $moisCourant = (int) (new \DateTimeImmutable())->format('n');

        $this->assertSame($moisCourant, $json['mois']);
    }

    // ── Test 9 : mois=0 → 400 ────────────────────────────────────────────────────

    public function testSaisonMoisInvalideZero(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=0', [], [], $this->authHeaders($this->token));

        $this->assertResponseStatusCodeSame(400);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }

    // ── Test 10 : mois=13 → 400 ──────────────────────────────────────────────────

    public function testSaisonMoisInvalideTreeize(): void
    {
        $this->client->request('GET', '/api/ingredients/saison?mois=13', [], [], $this->authHeaders($this->token));

        $this->assertResponseStatusCodeSame(400);
        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }
}
