# Kubo - Projet Symfony

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

