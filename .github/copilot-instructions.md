# GitHub Copilot Instructions - Projet Kubo

## 📋 Vue d'ensemble du projet

**Kubo** est une application web développée avec Symfony 8.0, utilisant PHP 8.4 et PostgreSQL comme base de données.

### Technologies principales
- **Framework**: Symfony 8.0
- **Langage**: PHP 8.4+
- **Base de données**: PostgreSQL 16
- **ORM**: Doctrine ORM 3.6
- **Frontend**: Stimulus (Hotwire), Turbo, Asset Mapper
- **Conteneurisation**: Docker Compose
- **Tests**: PHPUnit 12.5

## 🏗️ Architecture du projet

### Structure des dossiers

```
Kubo/
├── assets/              # Assets frontend (JS, CSS)
│   ├── controllers/     # Stimulus controllers
│   └── styles/          # Fichiers CSS
├── bin/                 # Scripts exécutables et utilitaires
├── config/              # Configuration Symfony
│   ├── packages/        # Configuration des bundles
│   └── routes/          # Configuration des routes
├── docs/                # Documentation du projet
├── migrations/          # Migrations Doctrine
├── public/              # Point d'entrée web (index.php)
├── src/                 # Code source de l'application
│   ├── Controller/      # Contrôleurs Symfony
│   ├── Entity/          # Entités Doctrine
│   ├── Repository/      # Repositories Doctrine
│   └── Kernel.php       # Kernel Symfony
├── templates/           # Templates Twig
├── tests/               # Tests PHPUnit
├── translations/        # Fichiers de traduction
├── var/                 # Fichiers temporaires (cache, logs)
└── vendor/              # Dépendances Composer
```

## 🎯 Objectifs du projet

**Kubo** est une application de gestion alimentaire personnelle qui aide l'utilisateur à :

- 🥗 **Manger plus sainement** : suivre l'équilibre nutritionnel des repas
- 💰 **Faire des économies** : optimiser les courses en fonction des recettes planifiées
- 😋 **Cuisiner avec plaisir** : choisir des recettes selon ses envies et préférences

### Fonctionnalités métier

> ⚠️ **Convention** : Dès qu'une nouvelle fonctionnalité métier est ajoutée, cette section doit être mise à jour en conséquence.

#### 📖 Gestion des recettes
- Créer, modifier et supprimer des recettes personnelles
- Associer des ingrédients avec quantités et unités
- Catégoriser les recettes (entrée, plat, dessert, snack…)
- Taguer les recettes (végétarien, rapide, économique, healthy…)
- Évaluer et noter ses recettes favorites
- Filtrer/rechercher par tags, ingrédients disponibles, temps de préparation

#### 🗓️ Planification des repas
- Planifier les repas de la semaine (petit-déjeuner, déjeuner, dîner)
- Choisir les recettes selon ses envies du moment
- Visualiser le planning hebdomadaire
- Adapter les portions selon le nombre de personnes

#### 🛒 Liste de courses intelligente
- Générer automatiquement la liste de courses depuis le planning
- Regrouper les ingrédients par catégorie (légumes, viandes, épicerie…)
- Déduire les ingrédients déjà disponibles (gestion du stock/garde-manger)
- Estimer le coût approximatif des courses
- Exporter / partager la liste de courses

#### 🥦 Suivi nutritionnel _(optionnel)_
- Afficher les apports nutritionnels par repas / par jour
- Indiquer si le planning respecte un équilibre alimentaire
- Mettre en avant les recettes saines selon des critères définis

#### 🏪 Gestion du garde-manger
- Gérer un inventaire des ingrédients disponibles à la maison
- Suggérer des recettes réalisables avec les ingrédients en stock
- Alerter sur les produits bientôt périmés

### 👤 Utilisateurs cibles

- **Utilisateur principal** : personne seule ou en couple souhaitant mieux s'organiser au quotidien
- **Profil** : actif, soucieux de sa santé et de son budget, manque de temps pour planifier ses repas

## 🔧 Configuration de l'environnement

### Base de données PostgreSQL

**Configuration Docker** (via `compose.yaml`) :
- **Service**: kubodb
- **Image**: postgres:16-alpine
- **Port**: 7007 (host) → 5432 (container)
- **Database**: kubo
- **User**: db_user
- **Password**: db_pass

**URL de connexion Doctrine** :
```
DATABASE_URL="postgresql://db_user:db_pass@127.0.0.1:7007/kubo?serverVersion=16&charset=utf8"
```

### Variables d'environnement

Le fichier `.env` contient les variables d'environnement du projet. Les valeurs par défaut dans Docker Compose :
- `POSTGRES_DB=kubo`
- `POSTGRES_USER=db_user`
- `POSTGRES_PASSWORD=db_pass`
- `POSTGRES_VERSION=16`

## 🛠️ Commandes Makefile

Le projet utilise un Makefile pour simplifier les commandes courantes :

### Docker
- `make start` / `make up` - Démarre les conteneurs Docker
- `make stop` - Arrête les conteneurs
- `make down` - Arrête et supprime les conteneurs
- `make down-volumes` - Supprime conteneurs + volumes (⚠️ perte de données)
- `make restart` - Redémarre les conteneurs
- `make logs` - Affiche les logs
- `make ps` - Liste les conteneurs actifs

### Composer & Dépendances
- `make install` - Installe les dépendances PHP
- `make update` - Met à jour les dépendances
- `make composer c="commande"` - Exécute une commande composer

### Symfony
- `make serve` - Démarre le serveur de développement
- `make sf c="commande"` - Exécute une commande Symfony console
- `make cache-clear` - Vide le cache
- `make cache-warmup` - Préchauffe le cache

### Base de données
- `make db-create` - Crée la base de données
- `make db-migrate` - Exécute les migrations
- `make db-reset` - Réinitialise la base de données
- `make db-fixtures` - Charge les fixtures (si disponible)

### Installation & Setup
- `make setup` - Installation complète automatique
- `make test` - Lance les tests PHPUnit

## 📝 Conventions de code

### PHP / Symfony

#### Namespaces
- Contrôleurs : `App\Controller`
- Entités : `App\Entity`
- Repositories : `App\Repository`
- Services : `App\Service`
- Form Types : `App\Form`
- Voters : `App\Security\Voter`

#### Conventions de nommage
- **Classes** : PascalCase (ex: `UserController`, `BlogPost`)
- **Méthodes** : camelCase (ex: `getUserById`, `createNewPost`)
- **Propriétés** : camelCase (ex: `$createdAt`, `$userName`)
- **Constantes** : UPPER_SNAKE_CASE (ex: `MAX_RETRY_ATTEMPTS`)

#### Contrôleurs
- Utiliser les attributs PHP 8 pour les routes : `#[Route('/path', name: 'app_name')]`
- Privilégier l'injection de dépendances via constructeur
- Respecter le principe de responsabilité unique

#### Entités Doctrine
- Utiliser les attributs PHP 8 pour le mapping : `#[ORM\Entity]`, `#[ORM\Column]`, etc.
- Toujours définir explicitement les types de colonnes
- Utiliser les getters/setters
- Implémenter les relations bidirectionnelles si nécessaire

#### Repositories
- Hériter de `ServiceEntityRepository`
- Créer des méthodes de requête personnalisées avec QueryBuilder
- Nommer les méthodes de manière descriptive (ex: `findActiveUsers()`)

#### Services
- Marquer les services avec `#[AsService]` si nécessaire
- Utiliser l'autowiring et l'autoconfiguration
- Privilégier l'injection de dépendances

### Frontend (Stimulus)

#### Contrôleurs Stimulus
- Nommer en kebab-case : `user-profile_controller.js`
- Utiliser les conventions Stimulus pour les targets, actions et values
- Organiser le code de manière modulaire

#### CSS
- Utiliser une méthodologie cohérente (BEM recommandé)
- Préfixer les classes spécifiques au projet si nécessaire

### Twig

- Extensions de fichiers : `.html.twig`
- Utiliser l'héritage de templates avec `{% extends 'base.html.twig' %}`
- Nommer les blocks de manière descriptive
- Échapper les variables par défaut (déjà activé dans Symfony)

## 🧪 Tests

### PHPUnit
- Tests unitaires dans `tests/Unit/`
- Tests fonctionnels dans `tests/Functional/`
- Tests d'intégration dans `tests/Integration/`
- Utiliser les traits Symfony pour les tests : `WebTestCase`, `KernelTestCase`

### Lancer les tests
```bash
make test
# ou
bin/phpunit
```

## 🔒 Sécurité

- **Environnement actuel** : Développement (sécurité allégée)
- Les mots de passe de base de données seront renforcés en production
- Toujours utiliser les mécanismes de sécurité Symfony :
  - CSRF protection
  - Validation des formulaires
  - Échappement automatique dans Twig
  - Hachage des mots de passe avec le PasswordHasher

## 📚 Documentation

### Documentation interne
- `/docs/DATABASE.md` - Documentation de la base de données
- `/docs/DOCKER_VOLUMES.md` - Gestion des volumes Docker
- `/docs/PHPSTORM_DATABASE.md` - Configuration PhpStorm pour la DB

### Scripts utilitaires (dans `/bin/`)
- `check-config` - Vérifie la configuration
- `cleanup-old-scripts` - Nettoie les anciens scripts
- `console` - Console Symfony
- `fix-db` - Répare la base de données
- `reset-db` - Réinitialise la base de données
- `test-connection` - Teste la connexion à la DB
- `verify-migration` - Vérifie les migrations

## 🎨 Bundles et packages principaux

### Backend
- `doctrine/orm` - ORM pour la base de données
- `symfony/security-bundle` - Gestion de la sécurité
- `symfony/form` - Création et validation de formulaires
- `symfony/validator` - Validation des données
- `symfony/mailer` - Envoi d'emails
- `symfony/messenger` - Files de messages asynchrones
- `monolog` - Logging

### Frontend
- `symfony/stimulus-bundle` - Intégration Stimulus
- `symfony/ux-turbo` - Navigation Turbo
- `symfony/asset-mapper` - Gestion des assets

## 🚀 Workflow de développement

1. **Démarrer l'environnement**
   ```bash
   make start
   make serve
   ```

2. **Créer une nouvelle feature**
   - Créer l'entité : `php bin/console make:entity`
   - Générer la migration : `php bin/console make:migration`
   - Appliquer la migration : `make db-migrate`
   - Créer le contrôleur : `php bin/console make:controller`
   - Créer les templates Twig
   - Ajouter les tests

3. **Vérifier le code**
   ```bash
   make test
   php bin/console lint:twig templates
   php bin/console lint:yaml config
   ```

## 💡 Bonnes pratiques pour GitHub Copilot

### Lors de la génération de code

1. **Contrôleurs**
   - Générer des méthodes avec typage strict
   - Inclure les attributs de route
   - Gérer les erreurs avec try-catch si nécessaire
   - Retourner les bonnes réponses HTTP

2. **Entités**
   - Toujours typer les propriétés
   - Générer les getters/setters
   - Ajouter les contraintes de validation
   - Documenter les relations

3. **Repositories**
   - Utiliser QueryBuilder pour les requêtes complexes
   - Optimiser les requêtes (éviter N+1)
   - Typer les retours de méthodes

4. **Services**
   - Créer des interfaces si nécessaire
   - Injecter les dépendances via constructeur
   - Une seule responsabilité par service

5. **Templates Twig**
   - Respecter l'indentation
   - Utiliser les composants Symfony UX si disponibles
   - Rendre les templates accessibles (ARIA, sémantique HTML)

6. **Tests**
   - Tests unitaires pour la logique métier
   - Tests fonctionnels pour les endpoints
   - Mocker les services externes
   - Utiliser des fixtures pour les données de test

## 📌 Notes importantes

- **Version PHP** : 8.4+ (utiliser les features modernes : types union, readonly, etc.)
- **Version Symfony** : 8.0 (utiliser les attributs PHP 8 au lieu des annotations)
- **Base de données** : PostgreSQL (attention aux spécificités SQL)
- **Assets** : Asset Mapper (pas de Webpack/Encore)
- **Frontend** : Stimulus + Turbo (approche HTML-over-the-wire)

## 🔄 À compléter

Ce document évoluera au fur et à mesure du développement du projet. Sections à compléter :

- [ ] Objectifs métier précis du projet
- [ ] Modèle de données complet
- [ ] Architecture des services
- [ ] Règles métier spécifiques
- [ ] Intégrations tierces
- [ ] Processus de déploiement
- [ ] Stratégie de cache
- [ ] Gestion des erreurs et logging
- [ ] Performance et optimisations
- [ ] Internationalisation (i18n)

---

**Date de création** : Février 2026  
**Dernière mise à jour** : Février 2026  
**Mainteneur** : Équipe Kubo

