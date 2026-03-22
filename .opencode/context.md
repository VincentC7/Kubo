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

---

## Arborescence clé

```
src/
├── Kernel.php
├── Command/
│   └── ImportRecettesCommand.php
├── Controller/
│   ├── CatalogueController.php
│   ├── RecetteDetailController.php
│   └── RecetteListController.php
├── DataFixtures/
│   ├── AppFixtures.php
│   └── RecetteFixtures.php
├── Doctrine/
│   └── JsonbContains.php
├── Dto/
│   ├── CatalogueDto.php
│   ├── EtapeDto.php
│   ├── IngredientDto.php
│   ├── NutritionDto.php
│   ├── RecetteDetailDto.php
│   └── RecetteListItemDto.php
├── Entity/
│   ├── Allergene.php
│   ├── Etape.php
│   ├── Ingredient.php
│   ├── NutritionFait.php
│   ├── Recette.php
│   ├── RecetteIngredient.php
│   ├── Tag.php
│   ├── TypeIngredient.php
│   └── Ustensile.php
├── Repository/
│   └── (9 repositories, dont RecetteRepository avec findAllForMenu())
├── Security/
│   ├── User.php
│   └── UserIdHeaderAuthenticator.php
└── Service/
    ├── IngredientClassifier.php
    ├── MenuGeneratorService.php
    └── MenuScoringService.php

tests/
└── Api/
    ├── CatalogueApiTest.php   (6 tests)
    └── RecetteApiTest.php     (14 tests)
```

---

## Endpoints actifs

### `GET /api/recettes` — public
- Paginé, 7 filtres optionnels
- Retourne `{ data: RecetteListItemDto[], meta: { ... } }`

### `GET /api/recettes/{uuid}` — public
- Retourne `RecetteDetailDto` ou 404

### `GET /api/catalogue` — protégé (`api_secured`)
- Auth : fake automatique (pas de header requis)
- Paramètres : `week` (ISO, ex. `2026-W12`), `page`, `limit`
- Retourne `CatalogueDto` avec `meta.catalogue_size`

---

## Services

### `MenuGeneratorService`
- `buildOrderedCatalogue(User, DateTimeImmutable): Recette[]`
- `buildCataloguePage(array, int $page, int $limit): array`
- Seed déterministe : `crc32(userId + isoYear + isoWeek)`
- Constante : `CATALOGUE_SIZE = 70`

### `MenuScoringService`
- `score(Recette, int $month): float`
- `+2.0` si ingrédient de saison pour le mois donné
- `+0.5` si type d'ingrédient est légume ou fruit

### `IngredientClassifier`
- `classify(string $nom, array $types): [TypeIngredient, ?list<int>]`

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

---

## Entités

`Allergene`, `Etape`, `Ingredient`, `NutritionFait`, `Recette`, `RecetteIngredient`, `Tag`, `TypeIngredient`, `Ustensile`

Toutes les entités utilisent `Symfony\Component\Uid\Uuid` pour les UUIDs.

---

## Couche sécurité

- Firewall `api_secured` : pattern `^/api/catalogue`, stateless
- `UserIdHeaderAuthenticator` :
  - `supports()` retourne toujours `true`
  - `FAKE_USER_ID = '00000000-0000-0000-0000-000000000001'` (hard-codé)
  - Pas de lecture de header HTTP, pas d'`entry_point`
  - Commentaire de migration JWT présent pour le futur
- `access_control` : `^/api/catalogue → ROLE_USER`

---

## Repository custom

- `RecetteRepository::findAllForMenu()` — retourne toutes les recettes pour le générateur de catalogue

---

## Fixtures

- `AppFixtures.php` — fixtures de base
- `RecetteFixtures.php` — fixtures de recettes

---

## Tests

- Total : **20 tests** (tous passants)
- `CatalogueApiTest.php` : 6 tests (catalogue paginé, semaine ISO, etc.)
- `RecetteApiTest.php` : 14 tests (liste, détail, filtres, 404, etc.)

---

## Faux positifs LSP connus

| Symbole | Contexte |
|---|---|
| `Symfony\Component\Uid\Uuid` | Toutes les entités |
| `OpenApi\Attributes\*` | Contrôleurs |
| `Nelmio\ApiDocBundle\Attribute\Model` | Contrôleurs |
| `Doctrine\Common\DataFixtures\*` | Tests |
| `getUser()` nullable | `CatalogueController` — garanti non-null par le firewall |

---

## Conventions

- Les contrôleurs ne lisent pas de header `X-User-Id` (auth fake, pas de vrai JWT pour l'instant)
- Les UUIDs sont gérés via `symfony/uid`
- Pagination standard : paramètres `page` et `limit`
- Les semaines ISO sont au format `YYYY-Www` (ex. `2026-W12`)
- Doctrine custom function : `JsonbContains` (pour les filtres JSONB PostgreSQL)
