<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds TypeIngredient entity + type_id / mois_saison columns on ingredients,
 * then classifies all 818 existing ingredients via ILIKE patterns and assigns
 * seasonal months (mois_saison) for fruits and vegetables.
 */
final class Version20260319000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TypeIngredient + type_id + mois_saison, classify existing ingredients';
    }

    public function up(Schema $schema): void
    {
        // ── 1. Create type_ingredients table ─────────────────────────────────
        $this->addSql('CREATE TABLE type_ingredients (
            id   UUID         NOT NULL,
            nom  VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TYPE_ING_NOM  ON type_ingredients (nom)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TYPE_ING_SLUG ON type_ingredients (slug)');

        // ── 2. Add columns on ingredients ─────────────────────────────────────
        $this->addSql('ALTER TABLE ingredients ADD COLUMN type_id     UUID  NULL');
        $this->addSql('ALTER TABLE ingredients ADD COLUMN mois_saison JSONB NULL');
        $this->addSql('ALTER TABLE ingredients ADD CONSTRAINT FK_ING_TYPE FOREIGN KEY (type_id) REFERENCES type_ingredients (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_ING_TYPE ON ingredients (type_id)');

        // ── 3. Insert the 9 canonical types ───────────────────────────────────
        $this->addSql("INSERT INTO type_ingredients (id, nom, slug) VALUES
            (gen_random_uuid(), 'Viande',               'viande'),
            (gen_random_uuid(), 'Poisson / fruit de mer','poisson'),
            (gen_random_uuid(), 'Légume',               'legume'),
            (gen_random_uuid(), 'Fruit',                'fruit'),
            (gen_random_uuid(), 'Féculent',             'feculent'),
            (gen_random_uuid(), 'Produit laitier',      'produit_laitier'),
            (gen_random_uuid(), 'Herbe / épice',        'herbe_epice'),
            (gen_random_uuid(), 'Condiment / sauce',    'condiment'),
            (gen_random_uuid(), 'Autre',                'autre')
        ");

        // ── 4. Classify ingredients ── most specific first ────────────────────

        // Helper: UPDATE ... SET type_id = (SELECT id FROM type_ingredients WHERE slug = ?) WHERE nom ILIKE patterns
        // We chain multiple patterns via OR for each type.

        // --- viande ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'viande')
            WHERE type_id IS NULL AND (
                nom ILIKE '%poulet%' OR nom ILIKE '%bœuf%' OR nom ILIKE '%boeuf%' OR
                nom ILIKE '%porc%'   OR nom ILIKE '%agneau%' OR nom ILIKE '%veau%' OR
                nom ILIKE '%lardons%' OR nom ILIKE '%jambon%' OR nom ILIKE '%saucisse%' OR
                nom ILIKE '%merguez%' OR nom ILIKE '%canard%' OR nom ILIKE '%dinde%' OR
                nom ILIKE '%steak%'  OR nom ILIKE '%pintade%' OR nom ILIKE '%effiloché%' OR
                nom ILIKE '%gyros%'  OR nom ILIKE '%chipolata%' OR nom ILIKE '%chorizo%' OR
                nom ILIKE '%boudin%' OR nom ILIKE '%rôti%' OR nom ILIKE '%escalope%' OR
                nom ILIKE '%filet de poulet%' OR nom ILIKE '%blanc de poulet%' OR
                nom ILIKE '%cuisses de poulet%' OR nom ILIKE '%aiguillettes%' OR
                nom ILIKE '%côtelettes%' OR nom ILIKE '%côte de%' OR nom ILIKE '%magret%' OR
                nom ILIKE '%lapin%' OR nom ILIKE '%gibier%' OR nom ILIKE '%sanglier%'
            )
        ");

        // --- poisson ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'poisson')
            WHERE type_id IS NULL AND (
                nom ILIKE '%saumon%' OR nom ILIKE '%cabillaud%' OR nom ILIKE '%thon%' OR
                nom ILIKE '%crevette%' OR nom ILIKE '%daurade%' OR nom ILIKE '%merlu%' OR
                nom ILIKE '%lieu noir%' OR nom ILIKE '%fruits de mer%' OR nom ILIKE '%truite%' OR
                nom ILIKE '%anchois%' OR nom ILIKE '%saint-jacques%' OR nom ILIKE '%bar%' OR
                nom ILIKE '%églefin%' OR nom ILIKE '%perche%' OR nom ILIKE '%dorade%' OR
                nom ILIKE '%moule%' OR nom ILIKE '%huître%' OR nom ILIKE '%calmar%' OR
                nom ILIKE '%poulpe%' OR nom ILIKE '%pieuvre%' OR nom ILIKE '%langoustine%' OR
                nom ILIKE '%homard%' OR nom ILIKE '%crabe%' OR nom ILIKE '%palourde%' OR
                nom ILIKE '%seiche%' OR nom ILIKE '%maquereau%' OR nom ILIKE '%hareng%' OR
                nom ILIKE '%sardine%' OR nom ILIKE '%sole%' OR nom ILIKE '%turbot%' OR
                nom ILIKE '%rouget%' OR nom ILIKE '%lotte%' OR nom ILIKE '%baudroie%' OR
                nom ILIKE '%colin%' OR nom ILIKE '%haddock%' OR nom ILIKE '%tilapia%' OR
                nom ILIKE '%pangasius%' OR nom ILIKE '%gambas%' OR nom ILIKE '%langouïste%' OR
                nom ILIKE '%langouste%'
            )
        ");

        // --- legume ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'legume')
            WHERE type_id IS NULL AND (
                nom ILIKE '%courgette%' OR nom ILIKE '%carotte%' OR nom ILIKE '%oignon%' OR
                nom ILIKE '%poivron%' OR nom ILIKE '%tomate%' OR nom ILIKE '%aubergine%' OR
                nom ILIKE '%épinard%' OR nom ILIKE '%épinards%' OR nom ILIKE '%champignon%' OR
                nom ILIKE '%portobello%' OR nom ILIKE '%poireau%' OR nom ILIKE '%chou%' OR
                nom ILIKE '%concombre%' OR nom ILIKE '%sucrine%' OR nom ILIKE '%roquette%' OR
                nom ILIKE '%fenouil%' OR nom ILIKE '%navet%' OR nom ILIKE '%céleri%' OR
                nom ILIKE '%patate%' OR nom ILIKE '%courge%' OR nom ILIKE '%potimarron%' OR
                nom ILIKE '%asperge%' OR nom ILIKE '%radis%' OR nom ILIKE '%topinambour%' OR
                nom ILIKE '%panais%' OR nom ILIKE '%endive%' OR nom ILIKE '%haricots%' OR
                nom ILIKE '%haricot%' OR nom ILIKE '%pois%' OR nom ILIKE '%maïs%' OR
                nom ILIKE '%betterave%' OR nom ILIKE '%pak choï%' OR nom ILIKE '%pak choi%' OR
                nom ILIKE '%salade%' OR nom ILIKE '%laitue%' OR nom ILIKE '%mâche%' OR
                nom ILIKE '%artichaut%' OR nom ILIKE '%brocoli%' OR nom ILIKE '%échalote%' OR
                nom ILIKE '%échalotes%' OR nom ILIKE '%poirée%' OR nom ILIKE '%blette%' OR
                nom ILIKE '%cresson%' OR nom ILIKE '%pissenlit%' OR nom ILIKE '%pourpier%' OR
                nom ILIKE '%mizuna%' OR nom ILIKE '%bok choy%' OR nom ILIKE '%tête d''ail%' OR
                nom ILIKE '%gousses d''ail%' OR nom ILIKE '%ail%' OR nom ILIKE '%céleri-rave%' OR
                nom ILIKE '%cébette%' OR nom ILIKE '%ciboule%' OR nom ILIKE '%oignons verts%'
            )
        ");

        // --- fruit ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'fruit')
            WHERE type_id IS NULL AND (
                nom ILIKE '%citron%' OR nom ILIKE '%pomme%' OR nom ILIKE '%mangue%' OR
                nom ILIKE '%fraise%' OR nom ILIKE '%orange%' OR nom ILIKE '%poire%' OR
                nom ILIKE '%banane%' OR nom ILIKE '%avocat%' OR nom ILIKE '%ananas%' OR
                nom ILIKE '%framboise%' OR nom ILIKE '%pêche%' OR nom ILIKE '%abricot%' OR
                nom ILIKE '%grenade%' OR nom ILIKE '%myrtille%' OR nom ILIKE '%kiwi%' OR
                nom ILIKE '%nectarine%' OR nom ILIKE '%pamplemousse%' OR nom ILIKE '%melon%' OR
                nom ILIKE '%prune%' OR nom ILIKE '%raisin%' OR nom ILIKE '%cerise%' OR
                nom ILIKE '%figue%' OR nom ILIKE '%litchi%' OR nom ILIKE '%papaye%' OR
                nom ILIKE '%goyave%' OR nom ILIKE '%passion%' OR nom ILIKE '%fruit de la passion%' OR
                nom ILIKE '%noix de coco%' OR nom ILIKE '%clémentine%' OR nom ILIKE '%mandarine%' OR
                nom ILIKE '%bergamote%' OR nom ILIKE '%kumquat%' OR nom ILIKE '%physalis%' OR
                nom ILIKE '%groseille%' OR nom ILIKE '%cassis%' OR nom ILIKE '%mûre%' OR
                nom ILIKE '%airelle%' OR nom ILIKE '%canneberge%'
            )
        ");

        // --- feculent ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'feculent')
            WHERE type_id IS NULL AND (
                nom ILIKE '%riz%' OR nom ILIKE '%pâtes%' OR nom ILIKE '%farine%' OR
                nom ILIKE '%pommes de terre%' OR nom ILIKE '%lentille%' OR nom ILIKE '%quinoa%' OR
                nom ILIKE '%semoule%' OR nom ILIKE '%pain%' OR nom ILIKE '%boulgour%' OR
                nom ILIKE '%orge%' OR nom ILIKE '%spaghetti%' OR nom ILIKE '%penne%' OR
                nom ILIKE '%gnocchi%' OR nom ILIKE '%couscous%' OR nom ILIKE '%polenta%' OR
                nom ILIKE '%tortilla%' OR nom ILIKE '%nouille%' OR nom ILIKE '%orzo%' OR
                nom ILIKE '%rigatoni%' OR nom ILIKE '%linguine%' OR nom ILIKE '%farfalle%' OR
                nom ILIKE '%tagliatelle%' OR nom ILIKE '%fettuccine%' OR nom ILIKE '%lasagne%' OR
                nom ILIKE '%pappardelle%' OR nom ILIKE '%macaroni%' OR nom ILIKE '%fusilli%' OR
                nom ILIKE '%conchiglie%' OR nom ILIKE '%vermicelle%' OR nom ILIKE '%capellini%' OR
                nom ILIKE '%blé%' OR nom ILIKE '%épeautre%' OR nom ILIKE '%avoine%' OR
                nom ILIKE '%millet%' OR nom ILIKE '%sarrasin%' OR nom ILIKE '%fécule%' OR
                nom ILIKE '%amidon%' OR nom ILIKE '%chapelure%' OR nom ILIKE '%panko%' OR
                nom ILIKE '%pois chiche%' OR nom ILIKE '%haricots blancs%' OR
                nom ILIKE '%haricots rouges%' OR nom ILIKE '%haricots noirs%' OR
                nom ILIKE '%flageolets%' OR nom ILIKE '%pois cassés%'
            )
        ");

        // --- produit_laitier ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'produit_laitier')
            WHERE type_id IS NULL AND (
                nom ILIKE '%beurre%' OR nom ILIKE '%crème%' OR nom ILIKE '%fromage%' OR
                nom ILIKE '%mozzarella%' OR nom ILIKE '%parmesan%' OR nom ILIKE '%yaourt%' OR
                nom ILIKE '%yogourt%' OR nom ILIKE '%lait%' OR nom ILIKE '%ricotta%' OR
                nom ILIKE '%comté%' OR nom ILIKE '%gruyère%' OR nom ILIKE '%emmental%' OR
                nom ILIKE '%cheddar%' OR nom ILIKE '%pecorino%' OR nom ILIKE '%feta%' OR
                nom ILIKE '%mascarpone%' OR nom ILIKE '%halloumi%' OR nom ILIKE '%burrata%' OR
                nom ILIKE '%labneh%' OR nom ILIKE '%quark%' OR nom ILIKE '%fromage blanc%' OR
                nom ILIKE '%cottage%' OR nom ILIKE '%raclette%' OR nom ILIKE '%camembert%' OR
                nom ILIKE '%brie%' OR nom ILIKE '%roquefort%' OR nom ILIKE '%gorgonzola%' OR
                nom ILIKE '%stilton%' OR nom ILIKE '%manchego%' OR nom ILIKE '%gouda%' OR
                nom ILIKE '%edam%' OR nom ILIKE '%mimolette%' OR nom ILIKE '%reblochon%' OR
                nom ILIKE '%munster%' OR nom ILIKE '%époisses%' OR nom ILIKE '%livarot%' OR
                nom ILIKE '%pont-l''évêque%' OR nom ILIKE '%maroilles%' OR nom ILIKE '%langres%' OR
                nom ILIKE '%beaufort%' OR nom ILIKE '%abondance%' OR nom ILIKE '%tomme%' OR
                nom ILIKE '%ossau-iraty%' OR nom ILIKE '%sainte-maure%' OR nom ILIKE '%chèvre%'
            )
        ");

        // --- herbe_epice ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'herbe_epice')
            WHERE type_id IS NULL AND (
                nom ILIKE '%thym%' OR nom ILIKE '%basilic%' OR nom ILIKE '%persil%' OR
                nom ILIKE '%coriandre%' OR nom ILIKE '%cumin%' OR nom ILIKE '%paprika%' OR
                nom ILIKE '%curry%' OR nom ILIKE '%gingembre%' OR nom ILIKE '%origan%' OR
                nom ILIKE '%laurier%' OR nom ILIKE '%cannelle%' OR nom ILIKE '%romarin%' OR
                nom ILIKE '%menthe%' OR nom ILIKE '%estragon%' OR nom ILIKE '%ciboulette%' OR
                nom ILIKE '%sauge%' OR nom ILIKE '%épices%' OR nom ILIKE '%curcuma%' OR
                nom ILIKE '%piment%' OR nom ILIKE '%zaatar%' OR nom ILIKE '%sumac%' OR
                nom ILIKE '%ras el%' OR nom ILIKE '%garam%' OR nom ILIKE '%herbes%' OR
                nom ILIKE '%muscade%' OR nom ILIKE '%vadouvan%' OR nom ILIKE '%cardamome%' OR
                nom ILIKE '%anis%' OR nom ILIKE '%fenouil%' AND nom ILIKE '%graines%' OR
                nom ILIKE '%clou de girofle%' OR nom ILIKE '%girofle%' OR nom ILIKE '%safran%' OR
                nom ILIKE '%vanille%' OR nom ILIKE '%fenugrec%' OR nom ILIKE '%mélange d''épices%' OR
                nom ILIKE '%quatre épices%' OR nom ILIKE '%bouquet garni%' OR
                nom ILIKE '%herbes de provence%' OR nom ILIKE '%fines herbes%' OR
                nom ILIKE '%aneth%' OR nom ILIKE '%cerfeuil%' OR nom ILIKE '%marjolaine%' OR
                nom ILIKE '%livèche%' OR nom ILIKE '%mélisse%' OR nom ILIKE '%verveine%' OR
                nom ILIKE '%poivre%' OR nom ILIKE '%sel%' OR nom ILIKE '%fleur de sel%' OR
                nom ILIKE '%poudre de%' OR nom ILIKE '%épice%'
            )
        ");

        // --- condiment ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'condiment')
            WHERE type_id IS NULL AND (
                nom ILIKE '%huile%' OR nom ILIKE '%vinaigre%' OR nom ILIKE '%moutarde%' OR
                nom ILIKE '%ketchup%' OR nom ILIKE '%soja%' OR nom ILIKE '%bouillon%' OR
                nom ILIKE '%sucre%' OR nom ILIKE '%miel%' OR nom ILIKE '%mayonnaise%' OR
                nom ILIKE '%sauce%' OR nom ILIKE '%concentré de tomates%' OR nom ILIKE '%harissa%' OR
                nom ILIKE '%houmous%' OR nom ILIKE '%pesto%' OR nom ILIKE '%tahini%' OR
                nom ILIKE '%teriyaki%' OR nom ILIKE '%hoisin%' OR nom ILIKE '%sriracha%' OR
                nom ILIKE '%tapenade%' OR nom ILIKE '%chutney%' OR nom ILIKE '%sirop%' OR
                nom ILIKE '%cube de%' OR nom ILIKE '%fond de%' OR nom ILIKE '%fumet%' OR
                nom ILIKE '%worcestershire%' OR nom ILIKE '%tabasco%' OR nom ILIKE '%nuoc-mâm%' OR
                nom ILIKE '%nuoc mam%' OR nom ILIKE '%fish sauce%' OR nom ILIKE '%miso%' OR
                nom ILIKE '%tamari%' OR nom ILIKE '%aigre-doux%' OR nom ILIKE '%citronelle%' OR
                nom ILIKE '%vinaigre balsamique%' OR nom ILIKE '%cornichon%' OR nom ILIKE '%câpre%' OR
                nom ILIKE '%olive%' OR nom ILIKE '%tapenade%' OR nom ILIKE '%confiture%' OR
                nom ILIKE '%marmelade%' OR nom ILIKE '%compote%' OR nom ILIKE '%gelée%' OR
                nom ILIKE '%coulis%' OR nom ILIKE '%purée de%' OR nom ILIKE '%crème de%' OR
                nom ILIKE '%fond brun%' OR nom ILIKE '%demi-glace%' OR nom ILIKE '%glace de%' OR
                nom ILIKE '%levure%' OR nom ILIKE '%bicarbonate%' OR nom ILIKE '%gélatine%' OR
                nom ILIKE '%agar%' OR nom ILIKE '%maïzena%' OR nom ILIKE '%amidon%' OR
                nom ILIKE '%glucose%' OR nom ILIKE '%fructose%' OR nom ILIKE '%stevia%' OR
                nom ILIKE '%chocolat%' OR nom ILIKE '%cacao%' OR nom ILIKE '%café%' OR
                nom ILIKE '%thé%' OR nom ILIKE '%tisane%' OR nom ILIKE '%eau%' OR
                nom ILIKE '%vin%' OR nom ILIKE '%bière%' OR nom ILIKE '%cidre%' OR
                nom ILIKE '%cognac%' OR nom ILIKE '%rhum%' OR nom ILIKE '%whisky%' OR
                nom ILIKE '%cointreau%' OR nom ILIKE '%armagnac%' OR nom ILIKE '%calvados%'
            )
        ");

        // --- autre : tout ce qui reste sans classification ---
        $this->addSql("UPDATE ingredients SET type_id = (SELECT id FROM type_ingredients WHERE slug = 'autre')
            WHERE type_id IS NULL
        ");

        // ── 5. Assign mois_saison for légumes ────────────────────────────────
        // (only set where type is legume/fruit)

        // aubergine
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8,9,10]' WHERE nom ILIKE '%aubergine%'");
        // courgette
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE nom ILIKE '%courgette%'");
        // tomate
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9,10]' WHERE nom ILIKE '%tomate%'");
        // poivron
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8,9,10]' WHERE nom ILIKE '%poivron%'");
        // carotte
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8,9,10,11,12]' WHERE nom ILIKE '%carotte%'");
        // oignon / échalote
        $this->addSql("UPDATE ingredients SET mois_saison = '[1,2,3,4,5,6,7,8,9,10,11,12]' WHERE nom ILIKE '%oignon%' OR nom ILIKE '%échalote%'");
        // poireau
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12,1,2,3]' WHERE nom ILIKE '%poireau%'");
        // champignon / portobello
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11]' WHERE nom ILIKE '%champignon%' OR nom ILIKE '%portobello%'");
        // asperge
        $this->addSql("UPDATE ingredients SET mois_saison = '[4,5,6]' WHERE nom ILIKE '%asperge%'");
        // épinard
        $this->addSql("UPDATE ingredients SET mois_saison = '[4,5,6,7,8,9,10]' WHERE nom ILIKE '%épinard%'");
        // chou-fleur (avant chou générique)
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12,1,2]' WHERE nom ILIKE '%chou-fleur%' OR nom ILIKE '%choufleur%'");
        // brocoli
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8]' WHERE nom ILIKE '%brocoli%'");
        // chou (générique — exclure chou-fleur déjà traité)
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12,1,2,3]' WHERE nom ILIKE '%chou%' AND nom NOT ILIKE '%chou-fleur%' AND nom NOT ILIKE '%choufleur%' AND nom NOT ILIKE '%brocoli%' AND mois_saison IS NULL");
        // céleri
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12,1,2]' WHERE nom ILIKE '%céleri%'");
        // fenouil (légume, pas graines)
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9,10]' WHERE nom ILIKE '%fenouil%' AND nom NOT ILIKE '%graines%'");
        // concombre
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE nom ILIKE '%concombre%'");
        // salade / sucrine / roquette / mâche / laitue
        $this->addSql("UPDATE ingredients SET mois_saison = '[4,5,6,7,8,9,10]' WHERE (nom ILIKE '%salade%' OR nom ILIKE '%sucrine%' OR nom ILIKE '%roquette%' OR nom ILIKE '%mâche%' OR nom ILIKE '%laitue%') AND mois_saison IS NULL");
        // patate douce
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12,1]' WHERE nom ILIKE '%patate douce%'");
        // potimarron / courge
        $this->addSql("UPDATE ingredients SET mois_saison = '[9,10,11,12]' WHERE nom ILIKE '%potimarron%' OR nom ILIKE '%courge%'");
        // topinambour
        $this->addSql("UPDATE ingredients SET mois_saison = '[10,11,12,1,2]' WHERE nom ILIKE '%topinambour%'");
        // panais
        $this->addSql("UPDATE ingredients SET mois_saison = '[10,11,12,1,2,3]' WHERE nom ILIKE '%panais%'");
        // radis
        $this->addSql("UPDATE ingredients SET mois_saison = '[4,5,6,7,8,9]' WHERE nom ILIKE '%radis%'");
        // haricots verts (avant haricots génériques)
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE nom ILIKE '%haricots verts%' OR nom ILIKE '%haricot vert%'");
        // pois
        $this->addSql("UPDATE ingredients SET mois_saison = '[5,6,7,8]' WHERE nom ILIKE '%pois%' AND mois_saison IS NULL");
        // maïs
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9]' WHERE nom ILIKE '%maïs%'");
        // navet
        $this->addSql("UPDATE ingredients SET mois_saison = '[10,11,12,1,2]' WHERE nom ILIKE '%navet%'");
        // betterave
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8,9,10,11,12]' WHERE nom ILIKE '%betterave%'");
        // artichaut
        $this->addSql("UPDATE ingredients SET mois_saison = '[5,6,7,8,9,10]' WHERE nom ILIKE '%artichaut%'");
        // endive
        $this->addSql("UPDATE ingredients SET mois_saison = '[11,12,1,2,3]' WHERE nom ILIKE '%endive%'");
        // ail (légume condiment)
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE (nom ILIKE '%tête d''ail%' OR nom ILIKE '%gousses d''ail%' OR nom ILIKE '% ail%' OR nom = 'ail') AND mois_saison IS NULL");

        // ── 6. Assign mois_saison for fruits ─────────────────────────────────
        // pomme (fruit)
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9,10,11]' WHERE nom ILIKE '%pomme%' AND nom NOT ILIKE '%pomme de terre%' AND nom NOT ILIKE '%pommes de terre%'");
        // poire
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9,10,11]' WHERE nom ILIKE '%poire%'");
        // citron / orange / pamplemousse
        $this->addSql("UPDATE ingredients SET mois_saison = '[11,12,1,2,3]' WHERE nom ILIKE '%citron%' OR nom ILIKE '%orange%' OR nom ILIKE '%pamplemousse%' OR nom ILIKE '%clémentine%' OR nom ILIKE '%mandarine%'");
        // fraise
        $this->addSql("UPDATE ingredients SET mois_saison = '[4,5,6,7]' WHERE nom ILIKE '%fraise%'");
        // framboise
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8]' WHERE nom ILIKE '%framboise%'");
        // myrtille
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8]' WHERE nom ILIKE '%myrtille%'");
        // pêche / nectarine / abricot
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE nom ILIKE '%pêche%' OR nom ILIKE '%nectarine%' OR nom ILIKE '%abricot%'");
        // prune / raisin
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9,10]' WHERE nom ILIKE '%prune%' OR nom ILIKE '%raisin%'");
        // melon
        $this->addSql("UPDATE ingredients SET mois_saison = '[6,7,8,9]' WHERE nom ILIKE '%melon%'");
        // mangue
        $this->addSql("UPDATE ingredients SET mois_saison = '[5,6,7,8,9]' WHERE nom ILIKE '%mangue%'");
        // grenade
        $this->addSql("UPDATE ingredients SET mois_saison = '[10,11,12,1]' WHERE nom ILIKE '%grenade%'");
        // avocat
        $this->addSql("UPDATE ingredients SET mois_saison = '[10,11,12,1,2,3]' WHERE nom ILIKE '%avocat%'");
        // cerise
        $this->addSql("UPDATE ingredients SET mois_saison = '[5,6,7]' WHERE nom ILIKE '%cerise%'");
        // figue
        $this->addSql("UPDATE ingredients SET mois_saison = '[8,9,10]' WHERE nom ILIKE '%figue%'");
        // cassis / groseille / mûre / airelle
        $this->addSql("UPDATE ingredients SET mois_saison = '[7,8,9]' WHERE nom ILIKE '%cassis%' OR nom ILIKE '%groseille%' OR nom ILIKE '%mûre%' OR nom ILIKE '%airelle%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ingredients DROP CONSTRAINT FK_ING_TYPE');
        $this->addSql('DROP INDEX IDX_ING_TYPE');
        $this->addSql('ALTER TABLE ingredients DROP COLUMN type_id');
        $this->addSql('ALTER TABLE ingredients DROP COLUMN mois_saison');
        $this->addSql('DROP TABLE type_ingredients');
    }
}
