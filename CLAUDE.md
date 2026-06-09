# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full setup (Docker + deps + DB + migrations)
make setup

# Development
make start          # Start PostgreSQL container
make serve          # Start Symfony dev server
make cache-clear

# Database
make db-migrate     # Run pending migrations
make db-diff        # Generate migration from entity changes
make db-reset       # Drop + create + migrate
make db-fixtures    # Load test fixtures
make db-validate    # Validate schema against entities

# Tests
make test                                              # All tests
php bin/phpunit tests/Api/GetRecettesEndpointTest.php # Single test file
php bin/phpunit --filter testMethodName               # Single test method
make test-coverage  # HTML report → var/coverage/

# Rebuild test DB (after new migrations)
php bin/console doctrine:database:drop --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --no-interaction --env=test

# Import recipes depuis kubo-data/ (submodule git@github.com:VincentC7/kubo-data.git)
make db-data-update      # git submodule update --remote (tire les derniers JSON)
make db-import           # importe les recettes, skip existantes
make db-import-all       # réimporte tout (utile après db-reset)
make db-inject-ids       # injecte les UUID recette_id dans les JSON source
make db-link-images      # lie data/image/*.jpg aux recettes via recette_id

# Utilities
make psql           # PostgreSQL client in container
make fix-db         # Diagnose & repair DB connection issues
make check          # Verify project config
make sf c="about"   # Run arbitrary Symfony command
```

## Architecture

**kubo-api** is a Symfony 8 / PHP 8.4 JSON REST API for personal meal planning (~2150 recipes). PostgreSQL 16 via Docker (port 7007).

### Authentication (two layers)

All `/api/*` routes require `X-Api-Key` header. `/api/doc` is the only fully public route.

User-scoped endpoints additionally require `Authorization: Bearer <JWT>`.

| Endpoint | Auth required |
|---|---|
| `POST /api/register` | X-Api-Key + rate limit (3/h/IP) |
| `POST /api/login` | X-Api-Key + rate limit (10/h/IP) |
| `POST /api/token/refresh` | X-Api-Key |
| `GET /api/catalogue`, `GET /api/recettes`, `GET /api/recettes/{uuid}`, `GET /api/ingredients/saison` | X-Api-Key |
| `PATCH /api/user`, `POST /api/user/password` | X-Api-Key + JWT (ROLE_USER) |

**Why `ApiKeySubscriber` is an EventSubscriber, not a Security Authenticator:** a Symfony `ApiKeyAuthenticator` competes with LexikJWT on the same firewall and prevents JWT from responding. The EventSubscriber (priority 10 on `kernel.request`) runs before the firewall and returns a 401 JSON without interfering with the Security system.

**`PostTokenRefreshEndpoint` is a stub controller** — its method never executes. GesdinetJWTRefreshTokenBundle v2 handles the request via its Security authenticator, but a Symfony route must exist for the Router to find `/api/token/refresh` (otherwise 404 before the firewall).

### Controller pattern

Controllers are single-action invokable classes named `VerbResourceEndpoint` (e.g., `GetRecettesEndpoint`, `PostPlanningEndpoint`). Each maps to one route and lives in `src/Controller/`. All endpoints are documented with OpenAPI attributes and visible at `/api/doc`.

### Database schemas

The PostgreSQL database uses three schemas (not `public`):
- `auth` — `users`, `refresh_tokens`
- `recette` — all recipe-domain tables (recettes, ingredients, tags, allergenes, etapes, nutrition_faits, etc.)
- `public` — `doctrine_migration_versions`, `messenger_messages`

Doctrine generates fully-qualified table names via `#[ORM\Table(schema: '...')]` on each entity. The PDO init option `SET search_path TO recette, auth, public` is set in `doctrine.yaml` (cosmetic only).

### JSONB and custom DQL

`Ingredient.moisSaison` stores seasonal months as **jsonb** (list of integers 1–12) with a GIN index. Queries use the PostgreSQL `@>` operator via the custom Doctrine DQL function `JSONB_CONTAINS` → `src/Doctrine/JsonbContains.php`.

### Key services

- `MenuGeneratorService` — builds the weekly catalogue: scores all recipes via `MenuScoringService`, deterministic shuffle with seed `crc32(userId+isoYear+isoWeek)`, truncates to `CATALOGUE_SIZE = 70`. **Result cached 1h** (cache key: `catalogue_{userId}_{year}_{week}`).
- `MenuScoringService` — `+2.0` per seasonal ingredient, `+0.5` per legume/fruit type. Class is `final` → cannot be mocked; instantiate with real objects in tests.
- `IngredientClassifier` — classifies by name with priority order: `viande > poisson > legume > fruit > feculent > produit_laitier > herbe_epice > condiment > autre`. Handles edge cases: `pommes de terre` → féculent, `chou-fleur` pattern beats `chou`.

### DTO pattern

All DTOs are `final readonly` + `JsonSerializable` (in `src/Dto/`). Validation errors are always returned as arrays per field: `{ "errors": { "field": ["message1", "message2"] } }`.

### Conventions

- Weeks in ISO format: `YYYY-Www` (e.g., `2026-W12`)
- Pagination: query params `page` + `limit`, response `meta: { total, page, limit, pages }`
- All UUIDs via `symfony/uid` (`UuidGenerator`)

### Tests

Base class `ApiTestCase` (`tests/ApiTestCase.php`) handles fixture loading, rate-limiter cache clearing, and auth helpers. Fixtures are purged and reloaded in `setUp()` for every test.

Test users:
- `user@kubo.dev` / `Password1` — standard user (`$this->userJsonHeaders()`)
- `other@kubo.dev` / `Password1` — ownership isolation tests (`$this->otherJsonHeaders()`)
- `admin@kubo.dev` / `Admin1234` — admin role

**Rate limiter in tests:** `login_api` limiter is set to 1000/h in `test` env (via `config/packages/rate_limiter.yaml` `when@test`) to avoid false 429s.

### Environment files

| File | Purpose |
|------|---------|
| `.env` | Defaults (versioned) |
| `.env.dev` | Dev overrides (versioned) |
| `.env.test` | Test environment (versioned) |
| `.env.local` | Local secrets — **never commit** (`JWT_PASSPHRASE`, `API_KEY`) |
| `.env.test.local` | Test secrets — **never commit** (`JWT_PASSPHRASE=<same value>`) |

JWT keys at `config/jwt/private.pem` + `config/jwt/public.pem` (in `.gitignore`). Generate with `php bin/console lexik:jwt:generate-keypair`.

### Known LSP false positives

These appear in editors but are harmless (dependencies not indexed by static analysers):
`Symfony\Component\RateLimiter\RateLimiterFactory`, `LexikJWTAuthenticationBundle`, `GesdinetJWTRefreshTokenBundle`, `Gesdinet\...\RefreshToken`, `OpenApi\Attributes\*`, `Nelmio\ApiDocBundle\Attribute\Model`.
