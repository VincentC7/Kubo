# Configuration de la base de données - kubo-api

## 📊 Informations de connexion

### Environnement de développement

```yaml
Service: kubodb (PostgreSQL 16)
Host: 127.0.0.1 (localhost)
Port: 7007
Database: kubo
Username: db_user
Password: db_pass
```

### URL de connexion Doctrine

```
DATABASE_URL="postgresql://db_user:db_pass@127.0.0.1:7007/kubo?serverVersion=16&charset=utf8"
```

## 🐳 Configuration Docker

### Fichier compose.yaml

```yaml
services:
  kubodb:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: kubo
      POSTGRES_USER: db_user
      POSTGRES_PASSWORD: db_pass
```

### Fichier compose.override.yaml

```yaml
services:
  kubodb:
    ports:
      - "7007:5432"  # Port local:Port conteneur
```

## 🔌 Connexions

### Depuis l'application Symfony

L'application utilise automatiquement la variable `DATABASE_URL` définie dans le fichier `.env`.

### Depuis un client PostgreSQL externe

```bash
# Ligne de commande
psql -h localhost -p 7007 -U db_user -d kubo

# Via le Makefile
make psql
```

### Depuis DBeaver, pgAdmin, TablePlus, etc.

- Type: PostgreSQL
- Host: localhost
- Port: 7007
- Database: kubo
- Username: db_user
- Password: db_pass

## 🔐 Sécurité

⚠️ **Important pour la production:**

1. Changez les identifiants par défaut
2. Utilisez des mots de passe forts
3. Stockez les credentials dans `.env.local` (non versionné)
4. Ou utilisez le système de secrets de Symfony:
   ```bash
   php bin/console secrets:set DATABASE_URL
   ```

## 🔄 Commandes utiles

```bash
# Démarrer la base de données
make start

# Créer la base de données
make db-create

# Exécuter les migrations
make db-migrate

# Réinitialiser complètement la base
make db-reset

# Accéder à psql
make psql

# Vérifier la configuration
make check
```

## 🐛 Résolution de problèmes

### La base de données ne démarre pas

```bash
# Vérifier les logs
make logs

# Redémarrer les conteneurs
make restart
```

### Impossible de se connecter

1. Vérifiez que le conteneur est démarré: `make ps`
2. Vérifiez le port: `docker compose ps`
3. Vérifiez les credentials dans `.env`

### Conflits de port

Si le port 7007 est déjà utilisé, modifiez dans `compose.override.yaml`:

```yaml
services:
  kubodb:
    ports:
      - "NOUVEAU_PORT:5432"
```

Puis mettez à jour `DATABASE_URL` dans `.env` avec le nouveau port.

## 📝 Variables d'environnement

Les variables peuvent être surchargées dans les fichiers suivants (par ordre de priorité):

1. Variables d'environnement système
2. `.env.local` (non versionné)
3. `.env.dev` (pour l'environnement dev)
4. `.env` (défaut, versionné)

## 🔗 Ressources

- [Documentation PostgreSQL](https://www.postgresql.org/docs/)
- [Documentation Doctrine](https://www.doctrine-project.org/)
- [Docker Compose PostgreSQL](https://hub.docker.com/_/postgres)

