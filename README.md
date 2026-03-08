# Kubo - Gestion alimentaire personnelle

> Application web de planification des repas, de gestion des recettes et d'optimisation des courses du quotidien.

## 🎯 Description du projet

**Kubo** est une application de gestion alimentaire personnelle qui aide l'utilisateur à :

- 🥗 **Manger plus sainement** : suivre l'équilibre nutritionnel des repas
- 💰 **Faire des économies** : optimiser les courses en fonction des recettes planifiées
- 😋 **Cuisiner avec plaisir** : choisir des recettes selon ses envies et préférences

---

### Fonctionnalités principales

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

---

### 👤 Utilisateurs cibles

- **Utilisateur principal** : personne seule ou en couple souhaitant mieux s'organiser au quotidien
- **Profil** : actif, soucieux de sa santé et de son budget, manque de temps pour planifier ses repas

---

### 🏗️ Stack technique

- **Framework** : Symfony 8.0
- **Langage** : PHP 8.4+
- **Base de données** : PostgreSQL 16
- **ORM** : Doctrine ORM
- **Frontend** : Stimulus (Hotwire) + Turbo
- **Conteneurisation** : Docker Compose

---

## 📋 Prérequis

- PHP 8.2 ou supérieur
- Composer
- Docker et Docker Compose
- PostgreSQL (via Docker)

## 🚀 Installation

### 1. Installation complète automatique

```bash
make setup
```

Cette commande va :
- Démarrer les conteneurs Docker (PostgreSQL)
- Installer les dépendances PHP avec Composer
- Créer la base de données
- Exécuter les migrations

### 2. Installation manuelle

```bash
# Démarrer les conteneurs Docker
make start

# Installer les dépendances
make install

# Créer la base de données
make db-create

# Exécuter les migrations
make db-migrate

# Démarrer le serveur Symfony
make serve
```

## 🗄️ Configuration de la base de données

### Informations de connexion

- **Host:** localhost
- **Port:** 7007
- **Database:** kubo
- **User:** db_user
- **Password:** db_pass

### URL de connexion

```
DATABASE_URL="postgresql://db_user:db_pass@127.0.0.1:7007/kubo?serverVersion=16&charset=utf8"
```

### Accès direct à PostgreSQL

```bash
# Via psql dans le conteneur
make psql

# Ou avec un client externe
psql -h localhost -p 7007 -U db_user -d kubo
```

## 📝 Commandes disponibles

### Docker

- `make start` - Démarre les conteneurs Docker
- `make stop` - Arrête les conteneurs
- `make restart` - Redémarre les conteneurs
- `make down` - Arrête et supprime les conteneurs
- `make logs` - Affiche les logs des conteneurs
- `make ps` - Liste les conteneurs en cours d'exécution

### Composer

- `make install` - Installe les dépendances
- `make update` - Met à jour les dépendances
- `make composer c="commande"` - Exécute une commande composer

### Symfony

- `make serve` - Démarre le serveur de développement
- `make sf c="commande"` - Exécute une commande Symfony
- `make cache-clear` - Vide le cache
- `make cache-warmup` - Préchauffe le cache

### Base de données

- `make db-create` - Crée la base de données
- `make db-drop` - Supprime la base de données
- `make db-migrate` - Exécute les migrations
- `make db-rollback` - Annule la dernière migration
- `make db-diff` - Génère une nouvelle migration
- `make db-validate` - Valide le schéma de la base de données
- `make db-reset` - Réinitialise la base de données (drop + create + migrate)
- `make db-fixtures` - Charge les fixtures

### Tests

- `make test` - Exécute les tests
- `make test-coverage` - Exécute les tests avec couverture de code

### Outils

- `make shell-db` - Ouvre un shell dans le conteneur de base de données
- `make psql` - Ouvre psql dans le conteneur
- `make clean` - Nettoie le projet (cache, logs)
- `make help` - Affiche toutes les commandes disponibles

## 🌐 Accès à l'application

Une fois le serveur démarré avec `make serve`, l'application est accessible sur :

- **Application:** http://localhost:8000 (ou http://localhost:7000 selon la configuration)
- **Base de données:** localhost:7007

## 📁 Structure du projet

```
.
├── assets/           # Assets frontend (JS, CSS)
├── bin/              # Exécutables et scripts utilitaires
│   ├── console       # Console Symfony
│   ├── phpunit       # PHPUnit
│   ├── check-config  # Vérification de configuration
│   ├── fix-db        # Réparation de la BDD
│   ├── reset-db      # Redémarrage de la BDD
│   └── test-connection # Test de connexion PostgreSQL
├── config/           # Configuration Symfony
├── migrations/       # Migrations de base de données
├── public/           # Point d'entrée public
├── src/              # Code source PHP
├── templates/        # Templates Twig
├── tests/            # Tests
├── translations/     # Fichiers de traduction
├── var/              # Fichiers générés (cache, logs)
└── vendor/           # Dépendances Composer
```

## 🔧 Variables d'environnement

Les fichiers de configuration :

- `.env` - Configuration par défaut (versionné)
- `.env.local` - Configuration locale (non versionné)
- `.env.dev` - Configuration pour l'environnement dev
- `.env.test` - Configuration pour les tests

## 🐛 Résolution de problèmes

### Erreur : "Credentials incorrects" dans PHPStorm

**Cause :** Le conteneur PostgreSQL a été créé avec d'anciens identifiants qui persistent dans le volume Docker.

**Solution rapide :**
```bash
make fix-db
```

Ou manuellement :
```bash
docker compose down -v    # Le -v supprime les volumes !
docker compose up -d
```

**Important :** Le flag `-v` est crucial pour supprimer les anciennes données.

### Erreur : "Connection refused"

```bash
# Vérifier que le conteneur tourne
make ps

# Redémarrer les conteneurs
make restart
```

### Le port 7007 est déjà utilisé

Modifiez le port dans `compose.override.yaml` puis mettez à jour `DATABASE_URL` dans `.env`.

### Base de données corrompue

```bash
# Réinitialiser complètement
make down-volumes
make start
make db-create
make db-migrate
```

📄 **Documentation détaillée :** Voir `TROUBLESHOOT.md` et `docs/DOCKER_VOLUMES.md`

## 📚 Documentation

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)

## 🔒 Sécurité

⚠️ **Important:** Les identifiants actuels (db_user/db_pass) sont à usage de développement uniquement. 
Pour la production, utilisez des identifiants sécurisés et stockez-les dans `.env.local` ou utilisez le système de secrets de Symfony.

