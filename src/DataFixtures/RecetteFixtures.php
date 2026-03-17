<?php

namespace App\DataFixtures;

use App\Entity\Allergene;
use App\Entity\Etape;
use App\Entity\Ingredient;
use App\Entity\NutritionFait;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
use App\Entity\Tag;
use App\Entity\Ustensile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RecetteFixtures extends Fixture
{
    // Références accessibles dans les tests
    public const REF_POULET   = 'recette-poulet';
    public const REF_SALADE   = 'recette-salade';
    public const REF_RISOTTO  = 'recette-risotto';
    public const REF_BOEUF    = 'recette-boeuf';
    public const REF_TARTE    = 'recette-tarte';

    public function load(ObjectManager $manager): void
    {
        // ── Tags ─────────────────────────────────────────────────────────────
        $tagViande     = new Tag('Viande');
        $tagRapide     = new Tag('Rapide');
        $tagSalade     = new Tag('Salade');
        $tagVegetarien = new Tag('Végétarien');
        $tagDessert    = new Tag('Dessert');

        foreach ([$tagViande, $tagRapide, $tagSalade, $tagVegetarien, $tagDessert] as $tag) {
            $manager->persist($tag);
        }

        // ── Allergènes ───────────────────────────────────────────────────────
        $allergeneGluten    = new Allergene('Gluten');
        $allergeneOeufs     = new Allergene('Œufs');
        $allergenePoisson   = new Allergene('Poisson');
        $allergeneLait      = new Allergene('Lait');
        $allergeneMoutarde  = new Allergene('Moutarde');

        foreach ([$allergeneGluten, $allergeneOeufs, $allergenePoisson, $allergeneLait, $allergeneMoutarde] as $a) {
            $manager->persist($a);
        }

        // ── Ustensiles ───────────────────────────────────────────────────────
        $ustensileFour      = new Ustensile('Four');
        $ustensilePoele     = new Ustensile('Poêle');
        $utensileCasserole  = new Ustensile('Casserole');
        $ustensileBot       = new Ustensile('Robot culinaire');

        foreach ([$ustensileFour, $ustensilePoele, $utensileCasserole, $ustensileBot] as $u) {
            $manager->persist($u);
        }

        // ── Ingrédients ──────────────────────────────────────────────────────
        $ingPoulet     = new Ingredient('poulet');
        $ingThym       = new Ingredient('thym');
        $ingSalade     = new Ingredient('salade romaine');
        $ingParmesan   = new Ingredient('parmesan');
        $ingRiz        = new Ingredient('riz arborio');
        $ingChampignon = new Ingredient('champignons');
        $ingBoeuf      = new Ingredient('bœuf');
        $ingVinRouge   = new Ingredient('vin rouge');
        $ingCitron     = new Ingredient('citron');
        $ingOeufs      = new Ingredient('œufs');

        foreach ([$ingPoulet, $ingThym, $ingSalade, $ingParmesan, $ingRiz, $ingChampignon,
                  $ingBoeuf, $ingVinRouge, $ingCitron, $ingOeufs] as $ing) {
            $manager->persist($ing);
        }

        // ── Recette 1 : Poulet rôti aux herbes (Facile, 15 min) ─────────────
        $poulet = new Recette('Poulet rôti aux herbes');
        $poulet->setDescription('Un poulet rôti savoureux aux herbes de Provence, croustillant et doré.');
        $poulet->setDifficulte('Facile');
        $poulet->setTempsTotal(15);
        $poulet->setTempsPreparation(5);
        $poulet->setNbPersonnes(2);
        $poulet->setImageUrl('https://example.com/poulet.jpg');
        $poulet->setSource('https://hellofresh.fr/recettes/poulet-roti');
        $poulet->addTag($tagViande);
        $poulet->addTag($tagRapide);
        $poulet->addAllergene($allergeneGluten);
        $poulet->addUstensile($ustensileFour);
        $manager->persist($poulet);

        $ri1 = new RecetteIngredient($poulet, $ingPoulet, '300 g de poulet');
        $ri1->setQuantite('300')->setUnite('g');
        $ri2 = new RecetteIngredient($poulet, $ingThym, '1 branche de thym');
        $ri2->setQuantite('1')->setUnite('branche');
        $manager->persist($ri1);
        $manager->persist($ri2);

        $e1 = new Etape($poulet, 1);
        $e1->setInstructions(['Préchauffer le four à 200°C.', 'Badigeonner le poulet d\'huile d\'olive.']);
        $e1->setAstuce('Utiliser de l\'huile d\'olive pour une peau croustillante.');
        $e2 = new Etape($poulet, 2);
        $e2->setInstructions(['Parsemer de thym et enfourner 10 minutes.']);
        $e2->setAstuce(null);
        $manager->persist($e1);
        $manager->persist($e2);

        $nf1 = new NutritionFait($poulet, NutritionFait::CONTEXTE_PORTION);
        $nf1->setEnergieKcal(320.0)->setProteines(28.0)->setGlucides(5.0)->setMatieresGrasses(18.0);
        $manager->persist($nf1);

        $this->addReference(self::REF_POULET, $poulet);

        // ── Recette 2 : Salade César (Facile, 20 min) ───────────────────────
        $salade = new Recette('Salade César');
        $salade->setDescription('La classique salade César avec croûtons dorés et parmesan râpé.');
        $salade->setDifficulte('Facile');
        $salade->setTempsTotal(20);
        $salade->setTempsPreparation(15);
        $salade->setNbPersonnes(2);
        $salade->setImageUrl('https://example.com/cesar.jpg');
        $salade->setSource(null);
        $salade->addTag($tagSalade);
        $salade->addAllergene($allergeneOeufs);
        $salade->addAllergene($allergeneMoutarde);
        $salade->addUstensile($ustensileBot);
        $manager->persist($salade);

        $ri3 = new RecetteIngredient($salade, $ingSalade, '1 salade romaine');
        $ri3->setQuantite('1')->setUnite(null);
        $ri4 = new RecetteIngredient($salade, $ingParmesan, '50 g de parmesan');
        $ri4->setQuantite('50')->setUnite('g');
        $manager->persist($ri3);
        $manager->persist($ri4);

        $e3 = new Etape($salade, 1);
        $e3->setInstructions(['Laver et essorer la salade.', 'Couper en morceaux.']);
        $e3->setAstuce(null);
        $e4 = new Etape($salade, 2);
        $e4->setInstructions(['Préparer la sauce César.', 'Mélanger avec la salade et le parmesan.']);
        $e4->setAstuce('Ajouter les croûtons au dernier moment pour qu\'ils restent croustillants.');
        $manager->persist($e3);
        $manager->persist($e4);

        $nf2 = new NutritionFait($salade, NutritionFait::CONTEXTE_PORTION);
        $nf2->setEnergieKcal(250.0)->setProteines(12.0)->setGlucides(15.0)->setMatieresGrasses(14.0);
        $manager->persist($nf2);

        $this->addReference(self::REF_SALADE, $salade);

        // ── Recette 3 : Risotto aux champignons (Intermédiaire, 45 min) ──────
        $risotto = new Recette('Risotto aux champignons');
        $risotto->setDescription('Un risotto crémeux aux champignons de Paris, parfumé au parmesan.');
        $risotto->setDifficulte('Intermédiaire');
        $risotto->setTempsTotal(45);
        $risotto->setTempsPreparation(10);
        $risotto->setNbPersonnes(4);
        $risotto->setImageUrl('https://example.com/risotto.jpg');
        $risotto->setSource(null);
        $risotto->addTag($tagVegetarien);
        $risotto->addAllergene($allergeneLait);
        $risotto->addUstensile($utensileCasserole);
        $manager->persist($risotto);

        $ri5 = new RecetteIngredient($risotto, $ingRiz, '300 g de riz arborio');
        $ri5->setQuantite('300')->setUnite('g');
        $ri6 = new RecetteIngredient($risotto, $ingChampignon, '200 g de champignons');
        $ri6->setQuantite('200')->setUnite('g');
        $manager->persist($ri5);
        $manager->persist($ri6);

        $e5 = new Etape($risotto, 1);
        $e5->setInstructions(['Faire revenir les champignons dans une casserole.', 'Ajouter le riz et nacrer.']);
        $e5->setAstuce('Remuer régulièrement pour éviter que le riz colle.');
        $e6 = new Etape($risotto, 2);
        $e6->setInstructions(['Ajouter le bouillon louche par louche.', 'Incorporer le parmesan hors du feu.']);
        $e6->setAstuce(null);
        $manager->persist($e5);
        $manager->persist($e6);

        $nf3 = new NutritionFait($risotto, NutritionFait::CONTEXTE_PORTION);
        $nf3->setEnergieKcal(410.0)->setProteines(10.0)->setGlucides(68.0)->setMatieresGrasses(9.0);
        $manager->persist($nf3);

        $this->addReference(self::REF_RISOTTO, $risotto);

        // ── Recette 4 : Bœuf bourguignon (Difficile, 120 min) ───────────────
        $boeuf = new Recette('Bœuf bourguignon');
        $boeuf->setDescription('Le grand classique de la cuisine française mijotée au vin rouge de Bourgogne.');
        $boeuf->setDifficulte('Difficile');
        $boeuf->setTempsTotal(120);
        $boeuf->setTempsPreparation(20);
        $boeuf->setNbPersonnes(6);
        $boeuf->setImageUrl('https://example.com/boeuf.jpg');
        $boeuf->setSource('https://hellofresh.fr/recettes/boeuf-bourguignon');
        $boeuf->addTag($tagViande);
        $boeuf->addAllergene($allergeneGluten);
        $boeuf->addUstensile($utensileCasserole);
        $manager->persist($boeuf);

        $ri7 = new RecetteIngredient($boeuf, $ingBoeuf, '800 g de bœuf à braiser');
        $ri7->setQuantite('800')->setUnite('g');
        $ri8 = new RecetteIngredient($boeuf, $ingVinRouge, '1 bouteille de vin rouge');
        $ri8->setQuantite('1')->setUnite('bouteille');
        $manager->persist($ri7);
        $manager->persist($ri8);

        $e7 = new Etape($boeuf, 1);
        $e7->setInstructions(['Faire dorer les morceaux de bœuf.', 'Ajouter les légumes et le vin.']);
        $e7->setAstuce('Utiliser un vin de Bourgogne pour plus de saveur.');
        $e8 = new Etape($boeuf, 2);
        $e8->setInstructions(['Mijoter à feu doux pendant 1h30.', 'Rectifier l\'assaisonnement.']);
        $e8->setAstuce(null);
        $manager->persist($e7);
        $manager->persist($e8);

        $nf4 = new NutritionFait($boeuf, NutritionFait::CONTEXTE_PORTION);
        $nf4->setEnergieKcal(480.0)->setProteines(35.0)->setGlucides(12.0)->setMatieresGrasses(22.0);
        $manager->persist($nf4);

        $this->addReference(self::REF_BOEUF, $boeuf);

        // ── Recette 5 : Tarte au citron meringuée (Intermédiaire, 60 min) ───
        $tarte = new Recette('Tarte au citron meringuée');
        $tarte->setDescription('Une tarte au citron acidulée surmontée d\'une meringue légère et dorée.');
        $tarte->setDifficulte('Intermédiaire');
        $tarte->setTempsTotal(60);
        $tarte->setTempsPreparation(25);
        $tarte->setNbPersonnes(8);
        $tarte->setImageUrl('https://example.com/tarte-citron.jpg');
        $tarte->setSource(null);
        $tarte->addTag($tagDessert);
        $tarte->addAllergene($allergeneOeufs);
        $tarte->addAllergene($allergeneGluten);
        $tarte->addUstensile($ustensileFour);
        $manager->persist($tarte);

        $ri9  = new RecetteIngredient($tarte, $ingCitron, '3 citrons');
        $ri9->setQuantite('3')->setUnite(null);
        $ri10 = new RecetteIngredient($tarte, $ingOeufs, '4 œufs');
        $ri10->setQuantite('4')->setUnite(null);
        $manager->persist($ri9);
        $manager->persist($ri10);

        $e9 = new Etape($tarte, 1);
        $e9->setInstructions(['Préparer la pâte sablée.', 'Foncer le moule et cuire à blanc 15 minutes.']);
        $e9->setAstuce('Piquer le fond de tarte pour éviter les bulles.');
        $e10 = new Etape($tarte, 2);
        $e10->setInstructions(['Préparer la crème citron.', 'Verser sur le fond de tarte refroidi.']);
        $e10->setAstuce(null);
        $e11 = new Etape($tarte, 3);
        $e11->setInstructions(['Monter les blancs en neige ferme.', 'Napper la tarte et passer au four 5 minutes.']);
        $e11->setAstuce('La meringue doit être bien ferme avant de l\'étaler.');
        $manager->persist($e9);
        $manager->persist($e10);
        $manager->persist($e11);

        $nf5 = new NutritionFait($tarte, NutritionFait::CONTEXTE_PORTION);
        $nf5->setEnergieKcal(290.0)->setProteines(5.0)->setGlucides(42.0)->setMatieresGrasses(11.0);
        $manager->persist($nf5);

        $this->addReference(self::REF_TARTE, $tarte);

        $manager->flush();
    }
}
