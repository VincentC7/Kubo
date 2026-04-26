<?php

namespace App\Tests\Api;

use App\DataFixtures\UserDataFixtures;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class PlanningEndpointTest extends ApiTestCase
{
    private string $week;

    protected function setUp(): void
    {
        parent::setUp();
        $this->week = UserDataFixtures::WEEK;
    }

    // ── GET /api/planning ────────────────────────────────────────────────────

    public function testGetPlanningReturnsTwoEntries(): void
    {
        $this->client->request('GET', '/api/planning?week=' . $this->week, [], [], $this->userJsonHeaders());

        $this->assertResponseIsSuccessful();
        $json = $this->json();

        $this->assertCount(2, $json['data']);
        $this->assertSame($this->week, $json['meta']['week']);
        $this->assertArrayHasKey('weekStart', $json['meta']);
        $this->assertArrayHasKey('weekEnd', $json['meta']);
    }

    public function testGetPlanningDefaultsToCurrentWeek(): void
    {
        $this->client->request('GET', '/api/planning', [], [], $this->userJsonHeaders());
        $this->assertResponseIsSuccessful();
        $json = $this->json();
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('week', $json['meta']);
    }

    public function testGetPlanningEntryHasExpectedShape(): void
    {
        $this->client->request('GET', '/api/planning?week=' . $this->week, [], [], $this->userJsonHeaders());
        $entry = $this->json()['data'][0];

        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('recette', $entry);
        $this->assertArrayHasKey('week', $entry);
        $this->assertArrayHasKey('portions', $entry);
        $this->assertArrayHasKey('done', $entry);

        $recette = $entry['recette'];
        $this->assertArrayHasKey('id', $recette);
        $this->assertArrayHasKey('nom', $recette);
        $this->assertArrayHasKey('tempsTotal', $recette);
        $this->assertArrayHasKey('difficulte', $recette);
    }

    public function testGetPlanningReturnsEmptyForUnknownWeek(): void
    {
        $this->client->request('GET', '/api/planning?week=2000-W01', [], [], $this->userJsonHeaders());
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json()['data']);
    }

    public function testGetPlanningRejectsInvalidWeekFormat(): void
    {
        $this->client->request('GET', '/api/planning?week=invalid', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetPlanningRequiresAuth(): void
    {
        $this->client->request('GET', '/api/planning?week=' . $this->week, [], [], $this->apiHeaders());
        $this->assertResponseStatusCodeSame(401);
    }

    // ── POST /api/planning ───────────────────────────────────────────────────

    public function testPostPlanningCreatesEntry(): void
    {
        /** @var \App\Entity\Recette $risotto */
        $risotto = $this->getRisotto();

        $this->client->request('POST', '/api/planning', [], [], $this->userJsonHeaders(), json_encode([
            'recetteId' => (string) $risotto->getId(),
            'week'      => $this->week,
            'portions'  => 3,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json()['data'];

        $this->assertSame($this->week, $data['week']);
        $this->assertSame(3, $data['portions']);
        $this->assertFalse($data['done']);
        $this->assertSame('Risotto aux champignons', $data['recette']['nom']);
    }

    public function testPostPlanningRejectsDuplicate(): void
    {
        /** @var \App\Entity\Recette $poulet */
        $poulet = $this->getPoulet();

        // Poulet est déjà dans le planning W18 via fixtures
        $this->client->request('POST', '/api/planning', [], [], $this->userJsonHeaders(), json_encode([
            'recetteId' => (string) $poulet->getId(),
            'week'      => $this->week,
            'portions'  => 2,
        ]));

        $this->assertResponseStatusCodeSame(409);
    }

    public function testPostPlanningRejectsUnknownRecette(): void
    {
        $this->client->request('POST', '/api/planning', [], [], $this->userJsonHeaders(), json_encode([
            'recetteId' => '00000000-0000-0000-0000-000000000000',
            'week'      => $this->week,
            'portions'  => 2,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPostPlanningValidatesPortions(): void
    {
        $risotto = $this->getRisotto();

        $this->client->request('POST', '/api/planning', [], [], $this->userJsonHeaders(), json_encode([
            'recetteId' => (string) $risotto->getId(),
            'week'      => $this->week,
            'portions'  => 99,
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('portions', $this->json()['errors']);
    }

    // ── PATCH /api/planning/{id} ─────────────────────────────────────────────

    public function testPatchPlanningUpdatesPortionsAndDone(): void
    {
        $id = $this->getEntry1Id();

        $this->client->request('PATCH', '/api/planning/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'portions' => 6,
            'done'     => true,
        ]));

        $this->assertResponseIsSuccessful();
        $data = $this->json()['data'];
        $this->assertSame(6, $data['portions']);
        $this->assertTrue($data['done']);
    }

    public function testPatchPlanningReturns404ForOtherUser(): void
    {
        $id = $this->getEntryOtherId();

        $this->client->request('PATCH', '/api/planning/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'portions' => 2,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchPlanningReturns404ForUnknownId(): void
    {
        $this->client->request('PATCH', '/api/planning/00000000-0000-0000-0000-000000000000', [], [], $this->userJsonHeaders(), json_encode([
            'portions' => 2,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchPlanningValidatesPortions(): void
    {
        $id = $this->getEntry1Id();

        $this->client->request('PATCH', '/api/planning/' . $id, [], [], $this->userJsonHeaders(), json_encode([
            'portions' => 0,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    // ── DELETE /api/planning/{id} ────────────────────────────────────────────

    public function testDeletePlanningEntry(): void
    {
        $id = $this->getEntry1Id();

        $this->client->request('DELETE', '/api/planning/' . $id, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(204);

        // Vérifier qu'il ne réapparaît pas
        $this->client->request('GET', '/api/planning?week=' . $this->week, [], [], $this->userJsonHeaders());
        $this->assertCount(1, $this->json()['data']);
    }

    public function testDeletePlanningReturns404ForOtherUser(): void
    {
        $id = $this->getEntryOtherId();

        $this->client->request('DELETE', '/api/planning/' . $id, [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeletePlanningReturns404ForUnknownId(): void
    {
        $this->client->request('DELETE', '/api/planning/00000000-0000-0000-0000-000000000000', [], [], $this->userJsonHeaders());
        $this->assertResponseStatusCodeSame(404);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getPoulet(): \App\Entity\Recette
    {
        return static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(\App\Entity\Recette::class)
            ->findOneBy(['nom' => 'Poulet rôti aux herbes']);
    }

    private function getRisotto(): \App\Entity\Recette
    {
        return static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(\App\Entity\Recette::class)
            ->findOneBy(['nom' => 'Risotto aux champignons']);
    }

    private function getEntry1Id(): string
    {
        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $user   = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'user@kubo.dev']);
        $poulet = $em->getRepository(\App\Entity\Recette::class)->findOneBy(['nom' => 'Poulet rôti aux herbes']);
        return (string) $em->getRepository(\App\Entity\PlanningEntry::class)
            ->findOneBy(['user' => $user, 'recette' => $poulet, 'week' => $this->week])
            ->getId();
    }

    private function getEntryOtherId(): string
    {
        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $other = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'other@kubo.dev']);
        return (string) $em->getRepository(\App\Entity\PlanningEntry::class)
            ->findOneBy(['user' => $other, 'week' => $this->week])
            ->getId();
    }
}
