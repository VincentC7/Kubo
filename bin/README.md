# Scripts utilitaires Kubo

Ce dossier contient les scripts shell utilitaires pour le projet Kubo.

## 📜 Scripts disponibles

### 🔍 check-config
Vérifie la configuration complète du projet (PHP, Composer, Docker, PostgreSQL, dépendances).

**Usage :**
```bash
./bin/check-config
# ou
make check
```

### 🔧 fix-db
Diagnostique et répare les problèmes de connexion PostgreSQL. Recrée complètement la base de données avec les bons identifiants.

**Usage :**
```bash
./bin/fix-db
# ou
make fix-db
```

**⚠️ Attention :** Cette commande supprime toutes les données de la base de données.

### 🔄 reset-db
Redémarre rapidement les conteneurs Docker et recrée la base de données.

**Usage :**
```bash
./bin/reset-db
# ou
make reset-docker
```

### 🧪 test-connection
Teste la connexion à PostgreSQL et vérifie que tout fonctionne correctement.

**Usage :**
```bash
./bin/test-connection
# ou
make test-connection
```

## 🚀 Utilisation via Makefile (recommandé)

Tous ces scripts sont accessibles via des commandes `make` :

```bash
make check           # Vérifier la configuration
make fix-db          # Réparer la connexion BDD
make reset-docker    # Redémarrer Docker
make test-connection # Tester la connexion
```

## 📝 Notes

- Tous les scripts sont exécutables (`chmod +x`)
- Les scripts doivent être exécutés depuis la racine du projet
- Les scripts utilisent Docker Compose pour gérer PostgreSQL

## 🔐 Credentials PostgreSQL

Les scripts utilisent les identifiants suivants :
- **User:** db_user
- **Password:** db_pass
- **Database:** kubo
- **Port:** 7007

Ces identifiants sont définis dans `.env.local` et `compose.yaml`.

