# Kubo — Context OpenCode

> Ce fichier est maintenu automatiquement par OpenCode. Toute session qui modifie l'architecture doit le mettre à jour.

---

## Stack

| Composant | Version |
|---|---|
| PHP | 8.4+ |
| Symfony | 8.0.* |
| Doctrine ORM | ^3.6 |
| Base de données | PostgreSQL 16 |
| PHPUnit | ^12.5 |
| NelmioApiDocBundle | ^5.9 |
| symfony/uid | 8.0.* |
| LexikJWTAuthenticationBundle | ^3.x |
| GesdinetJWTRefreshTokenBundle | ^1.x |

---

## Arborescence clé

```
src/
├── Kernel.php
├── Command/
│   └── ImportRecettesCommand.php          ← import batch des fichiers JSON /data
├── Controller/
│   ├── Auth/
│   │   ├── PostRegisterEndpoint.php        ← POST /api/register (rate-limited 3/h)
│   │   └── PostTokenRefreshEndpoint.php    ← POST /api/token/refresh (géré par gesdinet)
│   ├── User/
│   │   ├── PatchUserEndpoint.php           ← PATCH /api/user (JWT requis)
│   │   └── PostUserPasswordEndpoint.php   ← POST /api/user/password (JWT requis)
│   ├── GetCatalogueEndpoint.php            ← GET /api/catalogue (public, API Key)
│   ├── GetRecetteDetailEndpoint.php        ← GET /api/recettes/{uuid} (public)
│   ├── GetRecetteListEndpoint.php          ← GET /api/recettes (public, 7 filtres)
│   └── GetSaisonEndpoint.php              ← GET /api/ingredients/saison (public)
├── DataFixtures/
│   ├── AppFixtures.php                    ← user@kubo.dev / Password1
│   └── RecetteFixtures.php               ← 5 recettes de test
├── Doctrine/
│   └── JsonbContains.php                 ← DQL custom function pour @> JSONB
├── Dto/
│   ├── CatalogueDto.php
│   ├── EtapeDto.php
│   ├── IngredientDto.php
│   ├── NutritionDto.php
│   ├── RecetteDetailDto.php
│   ├── RecetteListItemDto.php
│   └── RegisterDto.php
├── Entity/
│   ├── Ingredient.php                    ← moisSaison: jsonb (index GIN)
│   ├── RefreshToken.php
│   ├── Recette.php
│   ├── RecetteIngredient.php
│   ├── Tag.php
│   ├── TypeIngredient.php
│   ├── User.php
│   └── (Etape, NutritionFait, Allergene, Ustensile)
├── EventSubscriber/
│   ├── ApiKeySubscriber.php              ← vérifie X-Api-Key sur toutes les routes /api/*
│   └── LoginRateLimiterSubscriber.php   ← rate limit 10/h sur POST /api/login
├── Repository/
│   └── (9 repositories, dont RecetteRepository avec findAllForMenu() et findPaginated())
├── Security/
│   └── User.php
└── Service/
    ├── IngredientClassifier.php          ← classifie les ingrédients lors de l'import
    ├── MenuGeneratorService.php          ← génère le catalogue hebdomadaire (avec cache 1h)
    └── MenuScoringService.php            ← score les recettes par saisonnalité

tests/
├── ApiTestCase.php                       ← base class avec helpers auth/headers/fixtures
├── Api/
│   ├── GetCatalogueEndpointTest.php      ← 8 tests
│   ├── GetRecetteEndpointTest.php        ← 17 tests (liste, détail, filtres, 404)
│   ├── GetSaisonEndpointTest.php
│   └── UserEndpointTest.php
├── Security/
│   ├── AccessControlEndpointTest.php
│   ├── ApiKeyEndpointTest.php
│   └── AuthEndpointTest.php             ← inclut test rate limiter 429
└── Unit/
    ├── IngredientClassifierTest.php      ← 25 tests (types, saisons, cas limites)
    ├── ImportRecettesCommandParsingTest.php ← 25 tests (parseMinutes, parseNutrition, etc.)
    ├── MenuGeneratorServiceTest.php
    └── RegisterDtoTest.php
```

---

## Endpoints actifs

### `POST /api/register` — public (API Key + rate limit 3/h par IP)
- Body JSON : `firstName`, `lastName`, `email`, `password`
- Retourne 201 | 400 | 409 | 429

### `POST /api/login` — public (API Key + rate limit 10/h par IP)
- Body JSON : `email`, `password`
- Retourne `{ token, refresh_token }` | 401

### `POST /api/token/refresh` — public (API Key)
- Body JSON : `refresh_token`
- Géré par GesdinetJWTRefreshTokenBundle

### `GET /api/recettes` — public (API Key)
- Paginé, 7 filtres optionnels (q, tag, difficulte, temps_max, ingredient, type_ingredient, saison)
- `saison` est validé : entier 1–12, sinon 400
- Retourne `{ data: RecetteListItemDto[], meta: { total, page, limit, pages } }`

### `GET /api/recettes/{uuid}` — public (API Key)
- Retourne `RecetteDetailDto` ou 404

### `GET /api/catalogue` — public (API Key)
- Paramètres : `week` (ISO, ex. `2026-W12`), `page`, `limit`
- Catalogue déterministe par `(userId, isoYear, isoWeek)` — mis en cache 1h
- Retourne `CatalogueDto` avec `meta.catalogue_size`

### `GET /api/ingredients/saison` — public (API Key)
- Retourne les ingrédients de saison pour un mois donné

### `PATCH /api/user` — JWT requis (ROLE_USER)
- Met à jour `firstName` et/ou `lastName`
- Retourne toutes les violations (array) par champ

### `POST /api/user/password` — JWT requis (ROLE_USER)
- Vérifie le mot de passe actuel
- Refuse si le nouveau = actuel
- Retourne toutes les violations (array) par champ

---

## Sécurité

### Double couche
1. **API Key** (`X-Api-Key` header) : `ApiKeySubscriber` vérifie toutes les routes `/api/*` (sauf `/api/doc`) avant le firewall. Utilise `hash_equals()`.
2. **JWT** : firewall `api` avec `lexik_jwt_authentication` — requis sur les endpoints `/api/user*`

### Rate limiting
- `POST /api/register` : 3 tentatives/heure par IP (`sliding_window`)
- `POST /api/login` : 10 tentatives/heure par IP (`sliding_window`)

### Secrets
- `JWT_PASSPHRASE` et `API_KEY` : définis dans `.env.local` uniquement (non versionné)
- Clés PEM JWT (`config/jwt/*.pem`) : dans `.gitignore`

---

## Services

### `MenuGeneratorService`
- `buildOrderedCatalogue(?string $userId, int $isoYear, int $isoWeek): array`
- `buildCataloguePage(..., int $page, int $limit): array`
- Seed déterministe : `crc32(userId + isoYear + isoWeek)`
- Constante : `CATALOGUE_SIZE = 70`
- **Cache** : résultat mis en cache 1h via `CacheInterface` (clé : `catalogue_{userId}_{year}_{week}`)

### `MenuScoringService`
- `score(Recette, int $month): float`
- `+2.0` si ingrédient de saison pour le mois donné
- `+0.5` si type d'ingrédient est légume ou fruit

### `IngredientClassifier`
- `classify(string $nom, array $types): [TypeIngredient, ?list<int>]`
- Priorité : `viande > poisson > legume > fruit > feculent > produit_laitier > herbe_epice > condiment > autre`
- Cas limites gérés : `pommes de terre` → féculent (pas fruit), `chou-fleur` prime sur `chou`

---

## DTOs

| DTO | Utilisé par |
|---|---|
| `RecetteListItemDto` | `GET /api/recettes` et `CatalogueDto` |
| `RecetteDetailDto` | `GET /api/recettes/{uuid}` |
| `CatalogueDto` | `GET /api/catalogue` |
| `EtapeDto` | `RecetteDetailDto` |
| `IngredientDto` | `RecetteDetailDto` |
| `NutritionDto` | `RecetteDetailDto` |
| `RegisterDto` | `POST /api/register` |

---

## Entités

`Allergene`, `Etape`, `Ingredient`, `NutritionFait`, `Recette`, `RecetteIngredient`, `RefreshToken`, `Tag`, `TypeIngredient`, `Ustensile`, `User`

Toutes les entités utilisent `Symfony\Component\Uid\Uuid` pour les UUIDs.

**Note :** `Ingredient.moisSaison` est de type **jsonb** (PostgreSQL) avec un index GIN pour les requêtes `JSONB_CONTAINS`.

---

## Repository custom

- `RecetteRepository::findAllForMenu()` — eager load ingrédients + types pour le scoring
- `RecetteRepository::findPaginated()` — 7 filtres + eager load tags (pas de N+1)

---

## Fixtures

- `AppFixtures.php` — user@kubo.dev / Password1 + admin@kubo.dev / Admin1234
- `RecetteFixtures.php` — 5 recettes de test avec ingrédients et tags

---

## Faux positifs LSP connus

| Symbole | Contexte |
|---|---|
| `Symfony\Component\Uid\Uuid` | Toutes les entités |
| `OpenApi\Attributes\*` | Contrôleurs |
| `Nelmio\ApiDocBundle\Attribute\Model` | Contrôleurs |
| `Doctrine\Common\DataFixtures\*` | Tests |
| `Symfony\Component\RateLimiter\RateLimiterFactory` | PostRegisterEndpoint, LoginRateLimiterSubscriber |
| `LexikJWTAuthenticationBundle` | config/bundles.php |
| `GesdinetJWTRefreshTokenBundle` | config/bundles.php, RefreshToken entity |

---

## Règles OpenCode

- **Ne jamais faire `git push` sans autorisation explicite de l'utilisateur.** Commit uniquement, push uniquement si demandé.

---

- Controllers : pattern "single-action invokable" nommés `VerbResourceEndpoint`
- DTOs : `final readonly` + `JsonSerializable`
- Violations de validation : retournées sous forme de **tableau** (`errors.field[]`) pour supporter plusieurs messages par champ
- UUIDs gérés via `symfony/uid`
- Pagination standard : paramètres `page` et `limit`
- Semaines ISO au format `YYYY-Www` (ex. `2026-W12`)
- Doctrine custom function : `JsonbContains` (opérateur `@>` PostgreSQL)
