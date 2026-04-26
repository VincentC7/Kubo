# Kubo — Documentation complète du projet

> Document de transfert de contexte. Décrit l'état actuel du projet, tout ce qui a été implémenté, les décisions d'architecture, et les conventions à respecter.

---

## 1. Vue d'ensemble

**Kubo** est une API REST en Symfony 8 / PHP 8.4 qui expose un catalogue de recettes de cuisine. Elle permet :

- De consulter, filtrer et paginer une base de ~2150 recettes importées depuis des fichiers JSON
- De générer un catalogue hebdomadaire personnalisé (menu de la semaine), déterministe et mis en cache
- De gérer des comptes utilisateurs (inscription, connexion JWT, changement de profil/mot de passe)
- De filtrer les ingrédients par saisonnalité (mois)

L'API est **privée** (toutes les routes exigent un header `X-Api-Key`) et certains endpoints exigent en plus un **JWT**.

---

## 2. Stack technique

| Composant | Version |
|---|---|
| PHP | 8.4+ (testé sur 8.5.3) |
| Symfony | 8.0.* |
| Doctrine ORM | ^3.6 |
| PostgreSQL | 16 |
| PHPUnit | ^12.5 |
| NelmioApiDocBundle | ^5.9 |
| symfony/uid | 8.0.* |
| LexikJWTAuthenticationBundle | ^3.x |
| GesdinetJWTRefreshTokenBundle | ^1.x |
| symfony/rate-limiter | inclus dans Symfony |

---

## 3. Base de données — Schémas PostgreSQL

Les tables sont organisées en **trois schémas** (migration en place depuis la session 3) :

### Schéma `recette` — domaine métier
| Table | Entité |
|---|---|
| `recette.recettes` | `Recette` |
| `recette.etapes` | `Etape` |
| `recette.nutrition_faits` | `NutritionFait` |
| `recette.recette_ingredients` | `RecetteIngredient` |
| `recette.ingredients` | `Ingredient` |
| `recette.type_ingredients` | `TypeIngredient` |
| `recette.tags` | `Tag` |
| `recette.allergenes` | `Allergene` |
| `recette.ustensiles` | `Ustensile` |
| `recette.recette_tags` | ManyToMany join (Recette ↔ Tag) |
| `recette.recette_allergenes` | ManyToMany join (Recette ↔ Allergene) |
| `recette.recette_ustensiles` | ManyToMany join (Recette ↔ Ustensile) |

### Schéma `auth` — authentification
| Table | Entité |
|---|---|
| `auth.users` | `User` |
| `auth.refresh_tokens` | `RefreshToken` |

### Schéma `public` — infrastructure
| Table | Géré par |
|---|---|
| `public.messenger_messages` | Symfony Messenger (non utilisé activement) |
| `public.doctrine_migration_versions` | Doctrine Migrations |

### Migrations (dans l'ordre d'exécution)
1. `Version20260317000000` — `CREATE SCHEMA IF NOT EXISTS recette; CREATE SCHEMA IF NOT EXISTS auth;`
2. `Version20260317112828` — création initiale des tables (dans `public`)
3. `Version20260319000000` — structure complète + données de base
4. `Version20260424122335` — ajout colonne `mois_saison` jsonb sur `ingredients`
5. `Version20260425142709` — ajout index GIN sur `mois_saison`
6. `Version20260425153431` — divers ajustements
7. `Version20260425163519` — déplace toutes les tables vers les schémas `recette.*` et `auth.*`, supprime les tables `public.*`

**Pour reconstruire from scratch :**
```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php -d memory_limit=1G bin/console app:import-recettes --skip-existing
```

---

## 4. Entités

Toutes les entités utilisent `Symfony\Component\Uid\Uuid` pour les IDs (UUID v4, générés par `UuidGenerator`).

### `Recette`
- `id` UUID, `nom`, `description`, `difficulte` (Facile/Intermédiaire/Difficile), `tempsTotal`, `tempsPreparation`, `nbPersonnes`, `imageUrl`, `source`
- Relations : OneToMany → `Etape`, `NutritionFait`, `RecetteIngredient` ; ManyToMany → `Tag`, `Allergene`, `Ustensile`

### `Ingredient`
- `id` UUID, `nom` (unique), `moisSaison` (**jsonb**, liste d'entiers 1–12, nullable), relation ManyToOne → `TypeIngredient`
- Index GIN sur `moisSaison` pour les requêtes `@>` (JSONB_CONTAINS)

### `TypeIngredient`
- `id` UUID, `nom` (unique), `slug` (unique)
- Slugs utilisés : `viande`, `poisson`, `legume`, `fruit`, `feculent`, `produit_laitier`, `herbe_epice`, `condiment`, `autre`

### `RecetteIngredient`
- Table de jointure enrichie : `recette_id`, `ingredient_id`, `quantite`, `unite`, `raw` (chaîne brute originale)

### `Etape`
- `recette_id`, `numero`, `instructions` (json, liste de phrases), `astuce` (text, nullable)

### `NutritionFait`
- `recette_id`, `contexte` (`portion` ou `100g`), + 14 champs nutritionnels float nullable
- Contrainte unique : `(recette_id, contexte)`

### `Tag`, `Allergene`, `Ustensile`
- Structure identique : `id` UUID, `nom` (unique), relation ManyToMany inverse vers `Recette`

### `User`
- `id` UUID, `email` (unique), `roles` (json), `password` (haché), `firstName`, `lastName`, `createdAt`
- Implémente `UserInterface` + `PasswordAuthenticatedUserInterface`

### `RefreshToken`
- Étend `Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken`
- Colonnes : `id` (identity), `refresh_token` (varchar 128, unique), `username`, `valid` (timestamp)

---

## 5. Endpoints API

Tous les endpoints exigent le header `X-Api-Key: <valeur>` (défini dans `.env.local`).

### Auth

#### `POST /api/register`
- **Public** + rate limit 3/h par IP
- Body : `{ firstName, lastName, email, password }`
- Réponses : `201 { message }` | `400 { errors }` | `409 { error }` | `429 { error }`
- Validation : email valide, mot de passe ≥ 8 chars avec majuscule + chiffre, firstName/lastName requis

#### `POST /api/login`
- **Public** + rate limit 10/h par IP (géré par `LoginRateLimiterSubscriber`, priority 31)
- Body : `{ email, password }`
- Réponses : `200 { token, refresh_token }` | `401` | `429`
- Géré nativement par LexikJWTAuthenticationBundle

#### `POST /api/token/refresh`
- **Public**
- Body : `{ refresh_token }`
- Réponses : `200 { token, refresh_token }` | `401`
- Géré par GesdinetJWTRefreshTokenBundle

### Recettes

#### `GET /api/recettes`
- **Public**
- Query params (tous optionnels) :
  - `q` — recherche full-text sur le nom
  - `tag` — filtre par nom de tag
  - `difficulte` — `Facile` | `Intermédiaire` | `Difficile`
  - `temps_max` — entier (minutes)
  - `ingredient` — filtre par nom d'ingrédient
  - `type_ingredient` — filtre par slug de type
  - `saison` — entier 1–12 (400 si invalide)
  - `page` (défaut 1), `limit` (défaut 20)
- Réponse : `{ data: RecetteListItemDto[], meta: { total, page, limit, pages } }`

#### `GET /api/recettes/{uuid}`
- **Public**
- Réponse : `RecetteDetailDto` | `404`

### Catalogue

#### `GET /api/catalogue`
- **Public** (optionnellement authentifié via JWT pour personnalisation)
- Query params : `week` (format `YYYY-Www`, défaut semaine courante), `page`, `limit`
- Catalogue de 70 recettes, ordre déterministe par `crc32(userId + isoYear + isoWeek)`
- Mis en cache 1h (clé `catalogue_{userId}_{year}_{week}`)
- Réponse : `{ data: RecetteListItemDto[], meta: { catalogue_size: 70, page, limit, pages } }`

### Ingrédients

#### `GET /api/ingredients/saison`
- **Public**
- Query param : `mois` (entier 1–12)
- Retourne la liste des ingrédients dont `moisSaison @> [mois]` (requête JSONB)

### Utilisateur (JWT requis)

#### `PATCH /api/user`
- Header : `Authorization: Bearer <token>`
- Body partiel : `{ firstName?, lastName? }`
- Réponse : `200 { id, email, firstName, lastName }` ou `400 { errors: { field: [messages] } }`
- Guard : `instanceof User` vérifié sur `getUser()` (exception si non)

#### `POST /api/user/password`
- Header : `Authorization: Bearer <token>`
- Body : `{ currentPassword, newPassword }`
- Réponse : `200` | `400 { errors }` | `400 { error: "même mot de passe" }`
- Refuse si `newPassword === currentPassword`

---

## 6. Sécurité

### Double couche d'authentification
1. **API Key** — `ApiKeySubscriber` intercepte toutes les requêtes `KernelEvents::REQUEST` (priority 10). Compare `X-Api-Key` avec `hash_equals()`. Retourne 401 JSON si absent/invalide. **Exception** : `/api/doc` est public.
2. **JWT** — Firewall Symfony `api` avec `lexik_jwt_authentication`. Requis uniquement sur `/api/user*`.

### Rate limiting (Symfony RateLimiter)
- `register_api` : sliding_window, 3/h par IP → `POST /api/register`
- `login_api` : sliding_window, 10/h par IP → `POST /api/login` (via `LoginRateLimiterSubscriber`)
- **En env `test`** : `login_api` limite portée à 1000/h pour ne pas bloquer les tests

### Secrets
- `API_KEY` : dans `.env.local` uniquement (jamais versionné)
- `JWT_PASSPHRASE` : dans `.env.local` (dev/prod) et `.env.test.local` (tests)
- Clés PEM JWT (`config/jwt/private.pem`, `config/jwt/public.pem`) : dans `.gitignore`
- Historique git nettoyé via `git-filter-repo` après rotation des secrets

---

## 7. Services

### `IngredientClassifier`
Classifie un ingrédient par son nom en `TypeIngredient` et calcule ses `moisSaison`.

**Priorité de classification (première correspondance gagne) :**
`viande > poisson > legume > fruit > feculent > produit_laitier > herbe_epice > condiment > autre`

**Cas limites gérés :**
- `pommes de terre` → féculent (pas fruit via "pomme")
- `haricots blancs` → légume (non bloqué par "blanc")
- `rôti de porc` → viande (via "rôti")
- `chou-fleur` → le pattern "chou-fleur" prime sur "chou"

**Saisonnalité :** associée aux fruits et légumes via des dictionnaires hardcodés par ingrédient.

### `MenuGeneratorService`
- `buildOrderedCatalogue(?string $userId, int $isoYear, int $isoWeek): array`
  - Récupère toutes les recettes via `RecetteRepository::findAllForMenu()`
  - Score chaque recette via `MenuScoringService::score($recette, $moisCourant)`
  - Tri décroissant par score, puis shuffle déterministe avec seed `crc32(userId+year+week)`
  - Tronque à `CATALOGUE_SIZE = 70`
  - **Cache 1h** via `CacheInterface`
- `buildCataloguePage(...)` : pagine le résultat

### `MenuScoringService`
- `score(Recette $r, int $month): float`
  - `+2.0` par ingrédient dont `moisSaison` contient `$month`
  - `+0.5` par ingrédient de type `legume` ou `fruit`

---

## 8. DTOs (tous `final readonly` + `JsonSerializable`)

| DTO | Champs clés |
|---|---|
| `RecetteListItemDto` | `id`, `nom`, `description`, `difficulte`, `tempsTotal`, `nbPersonnes`, `imageUrl`, `tags[]` |
| `RecetteDetailDto` | tout `RecetteListItemDto` + `etapes[]`, `ingredients[]`, `nutrition[]`, `allergenes[]`, `ustensiles[]` |
| `CatalogueDto` | `data: RecetteListItemDto[]`, `meta: { catalogue_size, page, limit, pages }` |
| `EtapeDto` | `numero`, `instructions[]`, `astuce` |
| `IngredientDto` | `nom`, `quantite`, `unite`, `raw`, `type`, `moisSaison[]` |
| `NutritionDto` | `contexte`, + 14 champs nutritionnels |
| `RegisterDto` | `firstName`, `lastName`, `email`, `password` (avec contraintes de validation) |

---

## 9. Repositories

### `RecetteRepository`
- `findPaginated(array $filters, int $page, int $limit)` — 7 filtres, eager-load tags (LEFT JOIN fetch, évite N+1)
- `findAllForMenu()` — eager-load ingrédients + types (pour scoring)

### Autres repositories
`AllergeneRepository`, `EtapeRepository`, `IngredientRepository`, `NutritionFaitRepository`, `RecetteIngredientRepository`, `TagRepository`, `TypeIngredientRepository`, `UserRepository`, `UstensileRepository`

---

## 10. Commande d'import

```bash
php -d memory_limit=1G bin/console app:import-recettes [--data-dir <dir>] [--batch-size <n>] [--limit <n>] [--skip-existing]
```

- Lit les fichiers JSON dans `/data/` (un fichier = une recette)
- Parse : `parseMinutes()`, `parseNutrition()`, `parseIngredients()`
- Utilise `IngredientClassifier` pour typer et saisonnaliser les ingrédients
- `--skip-existing` : ignore les recettes déjà présentes (par nom)
- Résultat : `N recettes importées, M rejetées, P ignorées`
- **Nécessite `-d memory_limit=1G`** pour traiter les 2151 recettes sans OOM

---

## 11. Tests

**121 tests, 421 assertions — tous passent.**

### Structure
```
tests/
├── ApiTestCase.php              ← base class : setUp fixtures, helpers login/headers
├── Api/
│   ├── GetCatalogueEndpointTest.php   (8 tests)
│   ├── GetRecetteEndpointTest.php     (17 tests : liste, détail, filtres, 404)
│   ├── GetSaisonEndpointTest.php
│   └── UserEndpointTest.php
├── Security/
│   ├── AccessControlEndpointTest.php  (JWT requis sur certaines routes)
│   ├── ApiKeyEndpointTest.php         (401 sans key, 200 avec)
│   └── AuthEndpointTest.php           (register, login, refresh, rate limit 429)
└── Unit/
    ├── IngredientClassifierTest.php         (25 tests)
    ├── ImportRecettesCommandParsingTest.php (25 tests)
    ├── MenuGeneratorServiceTest.php
    └── RegisterDtoTest.php
```

### Base de données de test
- Suffixe `_test` (config `doctrine.yaml` `when@test`)
- Reconstruire : `php bin/console doctrine:database:drop --force --env=test && php bin/console doctrine:database:create --env=test && php bin/console doctrine:migrations:migrate --no-interaction --env=test`
- Fixtures chargées automatiquement via `ApiTestCase::setUp()`

### Variables d'environnement de test
- `.env.test` : `KERNEL_CLASS`, `APP_SECRET`
- `.env.test.local` (non versionné) : `JWT_PASSPHRASE=<valeur>`

---

## 12. Configuration importante

### `config/packages/doctrine.yaml`
- Type UUID enregistré : `Symfony\Bridge\Doctrine\Types\UuidType`
- Custom DQL function : `JSONB_CONTAINS` → `App\Doctrine\JsonbContains` (opérateur `@>` PostgreSQL)
- `options: 1002: 'SET search_path TO recette, auth, public'` (PDO init — cosmétique car Doctrine génère des noms qualifiés)

### `config/packages/rate_limiter.yaml`
```yaml
register_api: sliding_window, 3/h
login_api:    sliding_window, 10/h
when@test → login_api: 1000/h  # évite les faux 429 en tests
```

### `config/packages/security.yaml`
- Firewall `api` : JWT auth, stateless
- Firewall `main` : form_login
- `access_control` : `/api/user*` → `ROLE_USER`

---

## 13. Arborescence des fichiers sources

```
src/
├── Command/
│   └── ImportRecettesCommand.php
├── Controller/
│   ├── Auth/
│   │   ├── PostRegisterEndpoint.php
│   │   └── PostTokenRefreshEndpoint.php
│   ├── User/
│   │   ├── PatchUserEndpoint.php
│   │   └── PostUserPasswordEndpoint.php
│   ├── GetCatalogueEndpoint.php
│   ├── GetRecetteDetailEndpoint.php
│   ├── GetRecetteListEndpoint.php
│   └── GetSaisonEndpoint.php
├── DataFixtures/
│   ├── AppFixtures.php           ← user@kubo.dev/Password1, admin@kubo.dev/Admin1234
│   └── RecetteFixtures.php       ← 5 recettes de test
├── Doctrine/
│   └── JsonbContains.php
├── Dto/
│   ├── CatalogueDto.php
│   ├── EtapeDto.php
│   ├── IngredientDto.php
│   ├── NutritionDto.php
│   ├── RecetteDetailDto.php
│   ├── RecetteListItemDto.php
│   └── RegisterDto.php
├── Entity/
│   ├── Allergene.php             ← schema: recette
│   ├── Etape.php                 ← schema: recette
│   ├── Ingredient.php            ← schema: recette, moisSaison jsonb
│   ├── NutritionFait.php         ← schema: recette
│   ├── Recette.php               ← schema: recette
│   ├── RecetteIngredient.php     ← schema: recette
│   ├── RefreshToken.php          ← schema: auth
│   ├── Tag.php                   ← schema: recette
│   ├── TypeIngredient.php        ← schema: recette
│   ├── Ustensile.php             ← schema: recette
│   └── User.php                  ← schema: auth
├── EventSubscriber/
│   ├── ApiKeySubscriber.php
│   └── LoginRateLimiterSubscriber.php
├── Repository/
│   ├── AllergeneRepository.php
│   ├── EtapeRepository.php
│   ├── IngredientRepository.php
│   ├── NutritionFaitRepository.php
│   ├── RecetteIngredientRepository.php
│   ├── RecetteRepository.php
│   ├── TagRepository.php
│   ├── TypeIngredientRepository.php
│   ├── UserRepository.php
│   └── UstensileRepository.php
├── Security/
│   └── User.php
└── Service/
    ├── IngredientClassifier.php
    ├── MenuGeneratorService.php
    └── MenuScoringService.php
```

---

## 14. Conventions de code

- **Controllers** : pattern "single-action invokable", nommés `VerbResourceEndpoint` (ex. `GetRecetteListEndpoint`, `PostRegisterEndpoint`)
- **DTOs** : `final readonly` + `JsonSerializable`
- **Erreurs de validation** : retournées sous forme de tableau `{ errors: { field: [message1, message2] } }` pour supporter plusieurs violations par champ
- **UUIDs** : `symfony/uid`, générés par `UuidGenerator` Doctrine
- **Pagination** : paramètres `page` et `limit`, réponse `meta: { total, page, limit, pages }`
- **Semaines ISO** : format `YYYY-Www` (ex. `2026-W12`)
- **Pas de `git push` sans autorisation explicite**

---

## 15. Faux positifs LSP connus

Ces erreurs apparaissent dans l'éditeur mais sont inoffensives (dépendances non indexées par l'analyseur statique) :

| Symbole | Fichier(s) |
|---|---|
| `Symfony\Component\RateLimiter\RateLimiterFactory` | `PostRegisterEndpoint`, `LoginRateLimiterSubscriber` |
| `LexikJWTAuthenticationBundle` | `config/bundles.php` |
| `GesdinetJWTRefreshTokenBundle` | `config/bundles.php`, `RefreshToken.php` |
| `Gesdinet\...\RefreshToken` (base class) | `RefreshToken.php` |
| `OpenApi\Attributes\*` | Contrôleurs |
| `Nelmio\ApiDocBundle\Attribute\Model` | Contrôleurs |

---

## 16. Fichiers non versionnés (à créer manuellement)

| Fichier | Contenu requis |
|---|---|
| `.env.local` | `API_KEY=<valeur>`, `JWT_PASSPHRASE=<valeur>`, `DATABASE_URL=postgresql://...` |
| `.env.test.local` | `JWT_PASSPHRASE=<même valeur que .env.local>` |
| `config/jwt/private.pem` | Clé privée RSA (générée via `php bin/console lexik:jwt:generate-keypair`) |
| `config/jwt/public.pem` | Clé publique RSA correspondante |
