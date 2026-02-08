# Configuration de la base de données dans PHPStorm

## 📊 Connexion à PostgreSQL dans l'onglet Database

### Étape 1 : Ouvrir l'onglet Database

1. Dans PHPStorm, ouvrez l'onglet **Database** (à droite de l'IDE)
   - Ou via le menu : `View` → `Tool Windows` → `Database`
   - Ou raccourci : `Cmd + Shift + A` puis tapez "Database"

### Étape 2 : Ajouter une nouvelle source de données

1. Cliquez sur le bouton **`+`** (plus) dans l'onglet Database
2. Sélectionnez `Data Source` → `PostgreSQL`

### Étape 3 : Configuration de la connexion

Remplissez les champs suivants dans la fenêtre qui s'ouvre :

#### **General Tab (Onglet Général)**

```
┌─────────────────────────────────────────────────────────┐
│ Name: Kubo PostgreSQL                                   │
├─────────────────────────────────────────────────────────┤
│ Host: localhost                                         │
│ Port: 7007                                              │
│ Authentication: User & Password                         │
│ User: db_user                                           │
│ Password: db_pass                                       │
│ Database: kubo                                          │
│ URL: jdbc:postgresql://localhost:7007/kubo             │
└─────────────────────────────────────────────────────────┘
```

#### **Détails des champs :**

| Champ | Valeur |
|-------|--------|
| **Name** | `Kubo PostgreSQL` (ou le nom que vous voulez) |
| **Host** | `localhost` |
| **Port** | `7007` |
| **Authentication** | `User & Password` |
| **User** | `db_user` |
| **Password** | `db_pass` |
| **Database** | `kubo` |
| **Save** | `Forever` (pour sauvegarder le mot de passe) |

### Étape 4 : Télécharger les drivers (si nécessaire)

1. Si c'est votre première connexion PostgreSQL, PHPStorm vous demandera de télécharger les drivers
2. Cliquez sur **"Download"** dans le bandeau qui apparaît
3. Attendez que le téléchargement se termine

### Étape 5 : Tester la connexion

1. Cliquez sur le bouton **"Test Connection"** en bas de la fenêtre
2. Vous devriez voir : ✅ **"Successful"**
3. Si erreur, vérifiez que :
   - Docker est démarré : `make start`
   - Le port 7007 est correct : `make ps`

### Étape 6 : Appliquer et se connecter

1. Cliquez sur **"OK"** ou **"Apply"**
2. La connexion apparaît maintenant dans l'onglet Database
3. Double-cliquez sur la connexion pour l'ouvrir

## 🎯 Configuration visuelle (copier-coller)

```
Host:          localhost
Port:          7007
Database:      kubo
User:          db_user
Password:      db_pass
```

## 🔧 Configuration avancée (optionnel)

### Options Tab

- **Auto-connect** : ☑ Cochez pour connexion automatique
- **Auto-disconnect** : Selon préférence

### SSH/SSL Tab

- Non nécessaire pour le développement local

### Advanced Tab

Vous pouvez laisser les valeurs par défaut.

## ✅ Vérification

Une fois connecté, vous devriez voir dans l'arborescence :

```
📁 Kubo PostgreSQL
  └─ 📁 kubo (database)
      ├─ 📁 schemas
      │   └─ 📁 public
      │       ├─ 📁 tables
      │       ├─ 📁 views
      │       └─ ...
      └─ ...
```

## 🐛 Résolution de problèmes

### Erreur : "Connection refused"

```bash
# Vérifiez que PostgreSQL est démarré
make start

# Vérifiez les conteneurs
make ps
```

### Erreur : "FATAL: password authentication failed"

- Vérifiez que les identifiants sont corrects
- User: `db_user`
- Password: `db_pass`

### Erreur : "Connection timeout"

- Vérifiez le port : `7007`
- Vérifiez que le conteneur écoute sur ce port : `docker compose ps`

### Le bouton "Test Connection" est grisé

- Téléchargez d'abord les drivers PostgreSQL
- Cliquez sur "Download missing driver files"

## 🎨 Fonctionnalités utiles dans PHPStorm Database

Une fois connecté :

- **Explorateur de tables** : Double-cliquez sur une table pour voir les données
- **Console SQL** : Clic droit → `New` → `Query Console`
- **Diagrammes ER** : Clic droit sur une table → `Diagrams` → `Show Visualization`
- **Exporter des données** : Clic droit sur une table → `Export Data`
- **Import/Export** : Via le menu contextuel

## 📝 Raccourci rapide pour PHPStorm

**URL de connexion JDBC complète :**
```
jdbc:postgresql://localhost:7007/kubo
```

Vous pouvez coller directement cette URL dans le champ URL si PHPStorm le propose.

## 🔗 Ressources

- [Documentation PHPStorm Database](https://www.jetbrains.com/help/phpstorm/database-tool-window.html)
- [PostgreSQL DataGrip](https://www.jetbrains.com/help/datagrip/postgresql.html)

