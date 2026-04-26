<?php

namespace App\DataFixtures;

use App\Entity\InventoryItem;
use App\Entity\PlanningEntry;
use App\Entity\ShoppingItem;
use App\Entity\ShoppingList;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Charge des données user_data pré-existantes pour les tests :
 *  - 2 entrées de planning (semaine courante) pour user@kubo.dev
 *  - 1 liste de courses avec 2 items pour user@kubo.dev
 *  - 3 items d'inventaire pour user@kubo.dev (dont 1 expiré, 1 expirant bientôt)
 *  - 1 entrée de planning pour other@kubo.dev (pour tester le 403)
 */
class UserDataFixtures extends Fixture
{
    // Clés semaine fixes pour les tests
    public const WEEK = '2026-W18';

    // Références exportées
    public const REF_PLANNING_ENTRY_1 = 'planning-entry-1';
    public const REF_PLANNING_ENTRY_2 = 'planning-entry-2';
    public const REF_PLANNING_ENTRY_OTHER = 'planning-entry-other';

    public const REF_SHOPPING_LIST   = 'shopping-list-1';
    public const REF_SHOPPING_ITEM_1 = 'shopping-item-1';
    public const REF_SHOPPING_ITEM_2 = 'shopping-item-2';

    public const REF_INVENTORY_OK      = 'inventory-ok';
    public const REF_INVENTORY_SOON    = 'inventory-soon';
    public const REF_INVENTORY_EXPIRED = 'inventory-expired';
    public const REF_INVENTORY_OTHER   = 'inventory-other';

    // Dates fixes pour les tests d'inventaire
    // "ok" : très loin dans le futur
    public const INVENTORY_OK_EXPIRES      = '2099-12-31';
    // "expired" : date fixe dans le passé (avant toute date d'exécution raisonnable)
    public const INVENTORY_EXPIRED_EXPIRES = '2020-01-01';
    // "soon" : calculé dynamiquement comme +2 jours au moment du chargement des fixtures

    public function load(ObjectManager $manager): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->getReference(AppFixtures::REF_USER, \App\Entity\User::class);
        /** @var \App\Entity\User $other */
        $other = $this->getReference(AppFixtures::REF_USER_OTHER, \App\Entity\User::class);

        /** @var \App\Entity\Recette $recettePoulet */
        $recettePoulet = $this->getReference(RecetteFixtures::REF_POULET, \App\Entity\Recette::class);
        /** @var \App\Entity\Recette $recetteSalade */
        $recetteSalade = $this->getReference(RecetteFixtures::REF_SALADE, \App\Entity\Recette::class);
        /** @var \App\Entity\Recette $recetteRisotto */
        $recetteRisotto = $this->getReference(RecetteFixtures::REF_RISOTTO, \App\Entity\Recette::class);

        // ── Planning ─────────────────────────────────────────────────────────

        $entry1 = new PlanningEntry($user, $recettePoulet, self::WEEK);
        $entry1->setPortions(2);
        $manager->persist($entry1);
        $this->addReference(self::REF_PLANNING_ENTRY_1, $entry1);

        $entry2 = new PlanningEntry($user, $recetteSalade, self::WEEK);
        $entry2->setPortions(4);
        $entry2->setDone(true);
        $manager->persist($entry2);
        $this->addReference(self::REF_PLANNING_ENTRY_2, $entry2);

        // Entrée appartenant au second user
        $entryOther = new PlanningEntry($other, $recetteRisotto, self::WEEK);
        $entryOther->setPortions(2);
        $manager->persist($entryOther);
        $this->addReference(self::REF_PLANNING_ENTRY_OTHER, $entryOther);

        // ── Liste de courses ─────────────────────────────────────────────────

        $list = new ShoppingList($user, self::WEEK);
        $manager->persist($list);
        $this->addReference(self::REF_SHOPPING_LIST, $list);

        $item1 = new ShoppingItem($list, 'Tomates', 'planning');
        $item1->setQuantity(400)->setUnit('g')->setCategory('légume');
        $list->addItem($item1);
        $manager->persist($item1);
        $this->addReference(self::REF_SHOPPING_ITEM_1, $item1);

        $item2 = new ShoppingItem($list, 'Huile d\'olive', 'manual');
        $item2->setQuantity(1)->setUnit('bouteille')->setCategory('condiment');
        $list->addItem($item2);
        $manager->persist($item2);
        $this->addReference(self::REF_SHOPPING_ITEM_2, $item2);

        // ── Inventaire ───────────────────────────────────────────────────────

        $invOk = new InventoryItem($user, 'Carottes');
        $invOk->setQuantity(500)->setUnit('g')->setCategory('légume');
        $invOk->setExpiresAt(new \DateTimeImmutable(self::INVENTORY_OK_EXPIRES));
        $manager->persist($invOk);
        $this->addReference(self::REF_INVENTORY_OK, $invOk);

        $invSoon = new InventoryItem($user, 'Yaourt');
        $invSoon->setQuantity(2)->setUnit('pot')->setCategory('produit laitier');
        $invSoon->setExpiresAt(new \DateTimeImmutable('+2 days'));  // toujours dans les 3 jours
        $manager->persist($invSoon);
        $this->addReference(self::REF_INVENTORY_SOON, $invSoon);

        $invExpired = new InventoryItem($user, 'Lait');
        $invExpired->setQuantity(1)->setUnit('L')->setCategory('produit laitier');
        $invExpired->setExpiresAt(new \DateTimeImmutable(self::INVENTORY_EXPIRED_EXPIRES));
        $manager->persist($invExpired);
        $this->addReference(self::REF_INVENTORY_EXPIRED, $invExpired);

        // Item appartenant au second user
        $invOther = new InventoryItem($other, 'Beurre');
        $invOther->setQuantity(250)->setUnit('g');
        $manager->persist($invOther);
        $this->addReference(self::REF_INVENTORY_OTHER, $invOther);

        $manager->flush();
    }
}
