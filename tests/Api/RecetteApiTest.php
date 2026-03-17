<?php

namespace App\Tests\Api;

use App\DataFixtures\RecetteFixtures;
use App\Entity\Recette;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecetteApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $uuidPoulet;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new RecetteFixtures());
        $purger   = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        // Récupère l'UUID du poulet dynamiquement
        $poulet = $this->em->getRepository(Recette::class)->findOneBy(['nom' => 'Poulet rôti aux herbes']);
        $this->assertNotNull($poulet, 'La fixture "Poulet rôti aux herbes" est introuvable.');
        $this->uuidPoulet = (string) $poulet->getId();
    }

    // ── Test 1 : GET /api/recettes retourne 200 avec data + meta ────────────

    public function testListReturns200WithDataAndMeta(): void
    {
        $this->client->request('GET', '/api/recettes');

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
        $this->client->request('GET', '/api/recettes');

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
        $this->client->request('GET', '/api/recettes?page=1&limit=2');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $json['data']);
        $this->assertSame(1, $json['meta']['page']);
        $this->assertSame(2, $json['meta']['limit']);
    }

    // ── Test 4 : filtre ?tag=Viande retourne uniquement des recettes avec ce tag

    public function testListFilterByTag(): void
    {
        $this->client->request('GET', '/api/recettes?tag=Viande');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, count($json['data']));
        foreach ($json['data'] as $item) {
            $this->assertContains('Viande', $item['tags'], 'La recette devrait avoir le tag "Viande"');
        }
    }

    // ── Test 5 : filtre ?q=poulet retourne au moins 1 résultat ──────────────

    public function testListFilterByQuery(): void
    {
        $this->client->request('GET', '/api/recettes?q=poulet');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, $json['meta']['total']);
        $this->assertNotEmpty($json['data']);
        // Le premier résultat doit mentionner "poulet" dans le nom (insensible à la casse)
        $nom = mb_strtolower($json['data'][0]['nom']);
        $this->assertStringContainsString('poulet', $nom);
    }

    // ── Test 6 : filtre ?temps_max=20 retourne des recettes avec tempsTotal <= 20

    public function testListFilterByTempsMax(): void
    {
        $this->client->request('GET', '/api/recettes?temps_max=20');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($json['data']);
        foreach ($json['data'] as $item) {
            $this->assertLessThanOrEqual(20, $item['temps_total']);
        }
    }

    // ── Test 7 : filtre ?difficulte=Facile retourne uniquement des recettes Faciles

    public function testListFilterByDifficulte(): void
    {
        $this->client->request('GET', '/api/recettes?difficulte=Facile');

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($json['data']);
        foreach ($json['data'] as $item) {
            $this->assertSame('Facile', $item['difficulte']);
        }
    }

    // ── Test 8 : GET /api/recettes/{uuid} retourne 200 avec tous les champs scalaires

    public function testDetailReturns200WithScalarFields(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet);

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
        $this->client->request('GET', '/api/recettes/00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(404);

        $json = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $json);
    }

    // ── Test 10 : détail — ingredients non vide avec nom et raw ─────────────

    public function testDetailHasIngredients(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet);

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('ingredients', $json);
        $this->assertNotEmpty($json['ingredients']);

        foreach ($json['ingredients'] as $ing) {
            $this->assertArrayHasKey('nom', $ing);
            $this->assertArrayHasKey('raw', $ing);
            $this->assertNotEmpty($ing['nom']);
            $this->assertNotEmpty($ing['raw']);
        }
    }

    // ── Test 11 : détail — etapes non vide avec numero et instructions array

    public function testDetailHasEtapes(): void
    {
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet);

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
        $this->client->request('GET', '/api/recettes/' . $this->uuidPoulet);

        $json = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('nutrition', $json);
        $this->assertNotEmpty($json['nutrition']);
        $this->assertSame('portion', $json['nutrition'][0]['contexte']);
    }
}
