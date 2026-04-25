<?php

namespace App\Tests\Api;

use App\DataFixtures\RecetteFixtures;
use App\Entity\Recette;
use App\Tests\ApiTestCase;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class RecetteApiTest extends ApiTestCase
{
    private string $uuidPoulet;
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

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $poulet = $em->getRepository(Recette::class)->findOneBy(['nom' => 'Poulet rôti aux herbes']);
        $this->assertNotNull($poulet, 'La fixture "Poulet rôti aux herbes" est introuvable.');
        $this->uuidPoulet = (string) $poulet->getId();

        $this->token = $this->loginAs('user@kubo.dev', 'Password1');
    }

    // ── Test 1 : GET /api/recettes retourne 200 avec data + meta ────────────

    public function testListReturns200WithDataAndMeta(): void
    {
        $this->client->request('GET', '/api/recettes', [], [], $this->authHeaders($this->token));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertIsArray($json['data']);
    }

    // ── Test 2 : meta contient total, page, limit, pages ────────────────────

    public function testListMetaHasRequiredFields(): void
    {
        $this->client->request('GET', '/api/recettes', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $meta = $json['meta'];

        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('page', $meta);
        $this->assertArrayHasKey('limit', $meta);
        $this->assertArrayHasKey('pages', $meta);
        $this->assertIsInt($meta['total']);
        $this->assertIsInt($meta['page']);
    }

    // ── Test 3 : pagination limit=2 retourne 2 items, page=1 ────────────────

    public function testListPaginationLimit2(): void
    {
        $this->client->request('GET', '/api/recettes?page=1&limit=2', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $json['data']);
        $this->assertSame(1, $json['meta']['page']);
        $this->assertSame(2, $json['meta']['limit']);
    }

    // ── Test 4 : filtre ?tag=Viande retourne uniquement des recettes avec ce tag

    public function testListFilterByTag(): void
    {
        $this->client->request('GET', '/api/recettes?tag=Viande', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, count($json['data']));
        foreach ($json['data'] as $item) {
            $this->assertContains('Viande', $item['tags'], 'La recette devrait avoir le tag "Viande"');
        }
    }

    // ── Test 5 : filtre ?q=poulet retourne au moins 1 résultat ──────────────

    public function testListFilterByQuery(): void
    {
        $this->client->request('GET', '/api/recettes?q=poulet', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, $json['meta']['total']);
        $this->assertNotEmpty($json['data']);
        $nom = mb_strtolower($json['data'][0]['nom']);
        $this->assertStringContainsString('poulet', $nom);
    }

    // ── Test 6 : filtre ?temps_max=20 retourne des recettes avec tempsTotal <= 20

    public function testListFilterByTempsMax(): void
    {
        $this->client->request('GET', '/api/recettes?temps_max=20', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($json['data']);
        foreach ($json['data'] as $item) {
            $this->assertLessThanOrEqual(20, $item['temps_total']);
        }
    }

    // ── Test 7 : filtre ?difficulte=Facile retourne uniquement des recettes Faciles

    public function testListFilterByDifficulte(): void
    {
        $this->client->request('GET', '/api/recettes?difficulte=Facile', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($json['data']);
        foreach ($json['data'] as $item) {
            $this->assertSame('Facile', $item['difficulte']);
        }
    }

    // ── Test 8 : GET /api/recettes/{uuid} retourne 200 avec tous les champs scalaires

    public function testDetailReturns200WithScalarFields(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet, [], [], $this->authHeaders($this->token));

        $this->assertResponseIsSuccessful();

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('uuid', $json);
        $this->assertArrayHasKey('nom', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertArrayHasKey('difficulte', $json);
        $this->assertArrayHasKey('temps_total', $json);
        $this->assertArrayHasKey('nb_personnes', $json);
        $this->assertSame($this->uuidPoulet, $json['uuid']);
        $this->assertSame('Poulet rôti aux herbes', $json['nom']);
    }

    // ── Test 9 : UUID inexistant retourne 404 ───────────────────────────────

    public function testDetailNotFound(): void
    {
        $this->client->request('GET', '/api/recettes/00000000-0000-0000-0000-000000000000', [], [], $this->authHeaders($this->token));

        $this->assertResponseStatusCodeSame(404);

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }

    // ── Test 10 : détail — ingredients non vide avec nom, raw, type, mois_saison

    public function testDetailHasIngredients(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet, [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('ingredients', $json);
        $this->assertNotEmpty($json['ingredients']);

        foreach ($json['ingredients'] as $ing) {
            $this->assertArrayHasKey('nom', $ing);
            $this->assertArrayHasKey('raw', $ing);
            $this->assertArrayHasKey('type', $ing);
            $this->assertArrayHasKey('mois_saison', $ing);
            $this->assertNotEmpty($ing['nom']);
            $this->assertNotEmpty($ing['raw']);
        }

        $types = array_column($json['ingredients'], 'type');
        $this->assertContains('viande', $types, 'Au moins un ingrédient de type "viande" attendu');
    }

    // ── Test 11 : détail — etapes non vide avec numero et instructions array

    public function testDetailHasEtapes(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet, [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('etapes', $json);
        $this->assertNotEmpty($json['etapes']);

        foreach ($json['etapes'] as $etape) {
            $this->assertArrayHasKey('numero', $etape);
            $this->assertArrayHasKey('instructions', $etape);
            $this->assertIsArray($etape['instructions']);
            $this->assertNotEmpty($etape['instructions']);
        }
    }

    // ── Test 12 : détail — nutrition non vide, contexte == "portion" ────────

    public function testDetailHasNutrition(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet, [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('nutrition', $json);
        $this->assertNotEmpty($json['nutrition']);
        $this->assertSame('portion', $json['nutrition'][0]['contexte']);
    }

    // ── Test 13 : filtre ?type_ingredient=viande ─────────────────────────────

    public function testListFilterByTypeIngredient(): void
    {
        $this->client->request('GET', '/api/recettes?type_ingredient=viande', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertGreaterThanOrEqual(1, $json['meta']['total'], 'Doit retourner au moins 1 recette avec des ingrédients de type viande');
    }

    // ── Test 14 : filtre ?saison=11 retourne recettes avec citron (nov–mars) ─

    public function testListFilterBySaison(): void
    {
        $this->client->request('GET', '/api/recettes?saison=11', [], [], $this->authHeaders($this->token));

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertGreaterThanOrEqual(1, $json['meta']['total'], 'Doit retourner au moins 1 recette avec ingrédient de saison en novembre');

        $noms = array_column($json['data'], 'nom');
        $this->assertContains('Tarte au citron meringuée', $noms, 'La tarte au citron doit apparaître (citron en saison en novembre)');
    }
}
