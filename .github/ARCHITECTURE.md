# Architecture — Kubo API

API REST JSON construite avec **Symfony 8**, **Doctrine ORM** et **PostgreSQL**.  
Elle expose 2 151 recettes HelloFresh importées depuis des fichiers JSON.

---

## Diagramme des entités

![Diagramme des entités](uml/entities.png)

> Source PlantUML : [`uml/entities.puml`](uml/entities.puml)

---

## Entités

| Entité | Table SQL | Rôle |
|---|---|---|
| `Recette` | `recettes` | Entité centrale. Agrège toutes les informations d'une recette. |
| `Tag` | `tags` | Label libre associé à une recette (ex : *Végétarien*, *Rapide*). Dédupliqué par `nom`. |
| `Allergene` | `allergenes` | Allergène déclaré sur une recette (ex : *Gluten*, *Lait*). Dédupliqué par `nom`. |
| `Ustensile` | `ustensiles` | Matériel de cuisine requis (ex : *Poêle*, *Four*). Dédupliqué par `nom`. |
| `TypeIngredient` | `type_ingredients` | Catégorie d'ingrédient (9 types : *viande*, *poisson*, *legume*, *fruit*, *feculent*, *produit_laitier*, *herbe_epice*, *condiment*, *autre*). Identifié par `slug`. |
| `Ingredient` | `ingredients` | Ingrédient de base, dédupliqué par `nom`. Partagé entre recettes. Possède un `type` (ManyToOne vers `TypeIngredient`) et un champ `mois_saison` (JSONB, tableau d'entiers 1–12, uniquement pour fruits et légumes). |
| `RecetteIngredient` | `recette_ingredients` | Table de jointure enrichie entre `Recette` et `Ingredient`. Stocke la quantité, l'unité et la chaîne brute originale. |
| `Etape` | `etapes` | Étape de préparation ordonnée par `numero`. Les instructions sont stockées en JSON (tableau de phrases). |
| `NutritionFait` | `nutrition_faits` | Valeurs nutritionnelles pour un contexte donné (`portion` ou `100g`). Une recette peut avoir 0, 1 ou 2 entrées (une par contexte). |

---

## Relations

### ManyToMany

| Relation | Table pivot | Cascade | Notes |
|---|---|---|---|
| `Recette` ↔ `Tag` | `recette_tags` | `ON DELETE CASCADE` | Un tag peut appartenir à plusieurs recettes. |
| `Recette` ↔ `Allergene` | `recette_allergenes` | `ON DELETE CASCADE` | Un allergène peut appartenir à plusieurs recettes. |
| `Recette` ↔ `Ustensile` | `recette_ustensiles` | `ON DELETE CASCADE` | Un ustensile peut appartenir à plusieurs recettes. |

### OneToMany (owned by `Recette`)

| Relation | Cascade | Notes |
|---|---|---|
| `Recette` → `RecetteIngredient` | `CASCADE persist + remove`, `ON DELETE CASCADE` | Supprimés avec la recette. `orphanRemoval = true`. |
| `Recette` → `Etape` | `CASCADE persist + remove`, `ON DELETE CASCADE` | Triées par `numero ASC`. `orphanRemoval = true`. |
| `Recette` → `NutritionFait` | `CASCADE persist + remove`, `ON DELETE CASCADE` | Contrainte unique `(recette_id, contexte)`. |

### ManyToOne

| Relation | Nullable | Notes |
|---|---|---|
| `RecetteIngredient` → `Ingredient` | Non | L'ingrédient est partagé entre recettes, non supprimé en cascade. |
| `Ingredient` → `TypeIngredient` | Oui | Catégorie de l'ingrédient. `null` si non classifié. |

---

## DTOs

Les DTOs sont des classes `readonly` qui implémentent `\JsonSerializable`. Ils sont construits depuis les entités via des méthodes statiques `fromEntity()` et sérialisés directement par `JsonResponse`.

![Diagramme des DTOs](uml/dtos.png)

> Source PlantUML : [`uml/dtos.puml`](uml/dtos.puml)

---

## Services

| Classe | Rôle |
|---|---|
| `IngredientClassifier` | Détermine le `TypeIngredient` et le `mois_saison` d'un ingrédient à partir de son nom, via des patterns `ILIKE`. Utilisé à l'import et dans `ImportRecettesCommand`. |
| `MenuScoringService` | Calcule le score de saisonnalité/équilibre d'une recette pour la semaine courante : +2.0 par ingrédient de saison, +0.5 si type `legume` ou `fruit`. |
| `MenuGeneratorService` | Construit le catalogue hebdomadaire ordonné (seed déterministe `crc32(userId+année+semaine)`). |

---

## Doctrine

| Composant | Rôle |
|---|---|
| `JsonbContains` | Custom DQL function qui traduit `JSONB_CONTAINS(col, val)` en `col::jsonb @> val::jsonb` (PostgreSQL). Enregistrée dans `doctrine.yaml` sous `dql.string_functions`. Utilisée par le filtre `?saison`. |

---

## Sécurité

L'endpoint `/api/catalogue` est protégé par un firewall Symfony stateless (`api_secured`).

L'authenticateur fake authentifie automatiquement toutes les requêtes avec un user ID fixe hard-codé. Aucun header ou paramètre n'est requis.

| Composant | Rôle |
|---|---|
| `UserIdHeaderAuthenticator` | Authentifie toutes les requêtes avec `FAKE_USER_ID` fixe. `supports()` retourne toujours `true`. |
| `User` | Implémente `UserInterface` (stub stateless). Expose `getId(): string`. |

**Migration JWT future :** remplacer `UserIdHeaderAuthenticator` par un `JWTAuthenticator` qui extrait l'id depuis le payload du token. Les contrôleurs et services n'ont pas besoin de changer.

---

## Endpoints

### `GET /api/recettes`

Liste paginée des recettes avec filtres optionnels.

**Paramètres query :**

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `page` | `int` | `1` | Numéro de page (commence à 1) |
| `limit` | `int` | `20` | Éléments par page (max 100) |
| `q` | `string` | — | Recherche texte `ILIKE` sur `nom` et `description` |
| `tag` | `string` | — | Filtre sur le nom exact d'un tag |
| `difficulte` | `string` | — | `Facile`, `Intermédiaire` ou `Difficile` |
| `temps_max` | `int` | — | `temps_total <=` N minutes |
| `ingredient` | `string` | — | Recherche texte `ILIKE` sur le nom d'un ingrédient |
| `type_ingredient` | `string` | — | Filtre sur le slug du type d'ingrédient (ex : `viande`, `legume`) |
| `saison` | `int` | — | Mois 1–12 : retourne les recettes ayant au moins un ingrédient de saison ce mois |

**Réponse `200` :**

```json
{
  "data": [
    {
      "uuid": "018e1b2c-3d4e-7f8a-9b0c-1d2e3f4a5b6c",
      "nom": "Poulet rôti aux herbes",
      "description": "Un poulet rôti savoureux...",
      "image_url": "https://...",
      "temps_total": 35,
      "difficulte": "Facile",
      "nb_personnes": 2,
      "tags": ["Viande", "Rapide"]
    }
  ],
  "meta": {
    "total": 2151,
    "page": 1,
    "limit": 20,
    "pages": 108
  }
}
```

---

### `GET /api/recettes/{uuid}`

Détail complet d'une recette par son UUID.

**Paramètre path :**

| Paramètre | Type | Description |
|---|---|---|
| `uuid` | `string (uuid)` | UUID de la recette |

**Réponse `200` :**

```json
{
  "uuid": "018e1b2c-3d4e-7f8a-9b0c-1d2e3f4a5b6c",
  "nom": "Poulet rôti aux herbes",
  "description": "Un poulet rôti savoureux...",
  "image_url": "https://...",
  "source": "https://hellofresh.fr/recettes/...",
  "temps_total": 35,
  "temps_preparation": 10,
  "difficulte": "Facile",
  "nb_personnes": 2,
  "tags": ["Viande", "Rapide"],
  "allergenes": ["Gluten"],
  "ustensiles": ["Four"],
  "ingredients": [
    { "nom": "poulet", "quantite": "300", "unite": "g", "raw": "300 g de poulet", "type": "viande", "mois_saison": null }
  ],
  "etapes": [
    { "numero": 1, "instructions": ["Préchauffer le four à 200°C.", "Badigeonner d'huile."], "astuce": "Utiliser de l'huile d'olive." }
  ],
  "nutrition": [
    { "contexte": "portion", "energie_kcal": 320.0, "proteines": 28.0, "glucides": 5.0, "matieres_grasses": 18.0, "..." : "..." }
  ]
}
```

**Réponse `404` :**

```json
{ "error": "Recette non trouvée." }
```

---

### `GET /api/catalogue`

Catalogue hebdomadaire personnalisé.

**Paramètres query :**

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `week` | `string` | semaine courante | Semaine ISO (ex : `2026-W12`) |
| `page` | `int` | `1` | Numéro de page |
| `limit` | `int` | `20` | Éléments par page (max 50) |

**Réponse `200` :**

```json
{
  "semaine": "2026-W12",
  "recettes": [{ "uuid": "...", "nom": "...", "tags": ["..."] }],
  "meta": {
    "total": 2151,
    "page": 1,
    "limit": 20,
    "pages": 108,
    "catalogue_size": 70
  }
}
```

Les `catalogue_size` premières recettes constituent la **sélection premium** (scorée par saisonnalité + équilibre). Les suivantes sont le reste du catalogue dans un ordre déterministe.

