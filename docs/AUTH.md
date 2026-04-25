# Rapport de développement — Authentification & Sécurité API

**Date :** 24 avril 2026  
**Scope :** Back-end Symfony 8 + Front-end Vue 3  

---

## 1. Contexte

L'API Kubo était jusqu'ici accessible sans aucune authentification. L'objectif de cette feature était de mettre en place une stack de sécurité complète à deux couches, sans casser le comportement existant des endpoints publics.

---

## 2. Architecture de sécurité mise en place

### Couche 1 — Clé API applicative (`X-Api-Key`)

Tous les endpoints `/api/*` exigent le header `X-Api-Key`. La vérification est faite par un **EventSubscriber** (`ApiKeySubscriber`, priority 32 sur `kernel.request`), en amont du firewall Symfony.

Ce choix d'EventSubscriber (plutôt qu'un Authenticator Security) s'est avéré nécessaire : un `ApiKeyAuthenticator` Symfony prenait la main sur le système JWT et empêchait les autres authenticators de répondre correctement.

Seule exception : `/api/doc`, totalement publique.

### Couche 2 — JWT (lexik + gesdinet)

- **Access token** : 15 minutes, signé RSA
- **Refresh token** : 7 jours, rotatif, stocké en base (`refresh_tokens`)
- Payload : `{ iat, exp, roles, email }`

Trois niveaux d'accès :

| Endpoint | Requis |
|---|---|
| `POST /api/register` | X-Api-Key uniquement |
| `POST /api/login` | X-Api-Key uniquement |
| `POST /api/token/refresh` | X-Api-Key uniquement |
| `GET /api/catalogue` | X-Api-Key uniquement |
| `GET /api/recettes` | X-Api-Key + JWT (`ROLE_USER`) |
| `GET /api/recettes/{uuid}` | X-Api-Key + JWT (`ROLE_USER`) |
| `GET /api/ingredients/saison` | X-Api-Key + JWT (`ROLE_USER`) |
| `GET /api/doc` | Public |

### Anti-spam register

Rate limiter Symfony (sliding window, 3 créations/heure/IP) sur `POST /api/register`.

---

## 3. Entités et migrations

### `User`
UUID, email (unique), password (bcrypt), roles (JSON), createdAt. Créés via `POST /api/register` ou `php bin/console app:create-admin`.

### `RefreshToken`
Entité concrète étendant le mapped-superclass de `gesdinet/jwt-refresh-token-bundle` — nécessaire car gesdinet v2 ne fournit pas d'entité concrète.

**Migration `Version20260424122335`** : création des tables `users` et `refresh_tokens`.

---

## 4. Points techniques notables

### Pourquoi un EventSubscriber et non un Authenticator pour l'API Key

L'Authenticator Symfony Security fonctionne en compétition avec les autres authenticators du même firewall. En présence de LexikJWT, l'ApiKeyAuthenticator capturait toutes les requêtes et empêchait JWT de prendre la main. L'EventSubscriber s'exécute avant le firewall et rejette proprement avec un 401 JSON sans interférer avec le système Security.

### Gesdinet v2 — route stub nécessaire

Gesdinet v2 ne déclare pas de controller : tout passe par son authenticator Security. Il faut cependant une route Symfony pour que le Router trouve `/api/token/refresh` (sinon 404 avant même le firewall). `RefreshController` est un controller stub vide dont la méthode ne s'exécute jamais — le firewall intercepte avant.

### `em->clear()` et entités détachées dans l'import

Bogue découvert lors du ré-import des 2151 recettes : après chaque `em->clear()` (tous les 50 imports), les objets `TypeIngredient` du tableau `$typesIndexedBySlug` devenaient détachés. Au batch suivant, Doctrine les considérait comme de nouvelles entités non-persistées et levait une `ORMInvalidArgumentException`. Corrigé en ajoutant un rechargement de `$typesIndexedBySlug` après chaque `clear()`, cohérent avec ce qui était déjà fait en cas d'erreur.

---

## 5. CORS

Installation de `nelmio/cors-bundle`. Configuration :
- Headers autorisés : `Content-Type`, `Accept`, `Authorization`, `X-Api-Key`
- Origine autorisée : `CORS_ALLOW_ORIGIN` (regex, couvre `localhost:*` en dev)
- Les requêtes `OPTIONS` (preflight) sont exemptées du firewall via `access_control`

---

## 6. Tests

Suite complète PHPUnit (61 tests, 240 assertions) couvrant :

| Suite | Fichiers | Ce qui est testé |
|---|---|---|
| **Security** | `ApiKeyTest`, `AuthTest`, `AccessControlTest` | X-Api-Key obligatoire, register/login/refresh, contrôle d'accès par rôle |
| **Unit** | `RegisterDtoTest`, `MenuGeneratorServiceTest` | Validation DTO, logique catalogue (taille, déterminisme, userId null) |
| **Api** | `CatalogueApiTest`, `RecetteApiTest`, `SaisonApiTest` | Comportement fonctionnel complet des 3 endpoints |

Classe de base `ApiTestCase` : helpers `apiKey()`, `apiHeaders()`, `loginAs()`, `authHeaders()` partagés par tous les tests fonctionnels.

Points d'attention sur les tests :
- La DB de test (`kubo_test`) doit avoir les migrations à jour avant `php bin/phpunit`
- Le rate limiter persiste entre les tests : reset via `$factory->create('127.0.0.1')->reset()` dans `setUp()` de `AuthTest`
- `MenuScoringService` est `final` → pas de mock standard ; on l'instancie réellement avec des `Recette` mocks retournant une collection vide (score = 0)

---

## 7. Fichiers modifiés / créés

```
src/
├── Entity/User.php                          nouveau
├── Entity/RefreshToken.php                  nouveau
├── Repository/UserRepository.php            nouveau
├── EventSubscriber/ApiKeySubscriber.php     nouveau
├── Controller/Auth/RegisterController.php   nouveau
├── Controller/Auth/RefreshController.php    nouveau (stub)
├── Dto/RegisterDto.php                      nouveau
├── Command/CreateAdminCommand.php           nouveau
├── Command/ImportRecettesCommand.php        corrigé (em->clear + rechargement types)
├── Controller/CatalogueController.php       corrigé (userId nullable)
└── Service/MenuGeneratorService.php         corrigé (userId string|null)

config/
├── packages/security.yaml                   réécrit
├── packages/lexik_jwt_authentication.yaml   TTL 15min
├── packages/gesdinet_jwt_refresh_token.yaml nouveau
├── packages/rate_limiter.yaml               nouveau
├── packages/nelmio_cors.yaml                configuré
├── routes.yaml                              route api_login
├── services.yaml                            injection API_KEY + alias limiter
└── bundles.php                              +Lexik, +Gesdinet, +NelmioCors

migrations/
└── Version20260424122335.php               tables users + refresh_tokens

tests/
├── ApiTestCase.php                          nouveau
├── Security/ApiKeyTest.php                  nouveau
├── Security/AuthTest.php                    nouveau
├── Security/AccessControlTest.php           nouveau
├── Unit/RegisterDtoTest.php                 nouveau
├── Unit/MenuGeneratorServiceTest.php        nouveau
├── Api/CatalogueApiTest.php                 mis à jour
├── Api/RecetteApiTest.php                   mis à jour
└── Api/SaisonApiTest.php                    mis à jour

.env                                         +API_KEY, +CORS_ALLOW_ORIGIN
phpunit.xml                                  nouveau
```
