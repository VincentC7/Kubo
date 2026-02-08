# 📚 Comprendre le problème des volumes Docker PostgreSQL

## 🤔 Pourquoi mes credentials ne marchent pas ?

### Le cycle de vie de PostgreSQL dans Docker

```
1️⃣ Premier démarrage (docker compose up)
   └─> PostgreSQL crée les utilisateurs
   └─> Les credentials sont STOCKÉS dans le volume
   └─> Le volume persiste même si on arrête le conteneur

2️⃣ Vous modifiez compose.yaml
   └─> Vous changez POSTGRES_USER et POSTGRES_PASSWORD
   └─> Vous faites docker compose restart

3️⃣ Redémarrage
   └─> PostgreSQL trouve le volume existant
   └─> PostgreSQL IGNORE les nouvelles variables
   └─> PostgreSQL utilise les ANCIENS credentials du volume
   └─> ❌ Échec de connexion !
```

## 🎯 La solution : Supprimer le volume

```bash
docker compose down -v
```

Le flag `-v` signifie "supprimer les volumes".

### Ce qui se passe alors :

```
1️⃣ docker compose down -v
   └─> Arrête le conteneur
   └─> SUPPRIME le volume avec les anciennes données
   
2️⃣ docker compose up -d
   └─> Crée un NOUVEAU volume vide
   └─> PostgreSQL s'initialise avec les NOUVEAUX credentials
   └─> ✅ Les bons identifiants sont maintenant actifs !
```

## 🔍 Comment vérifier ?

### Avant la correction :

```bash
# Lister les volumes
docker volume ls

# Vous verrez quelque chose comme :
# kubo_database_data

# Inspecter le volume
docker volume inspect kubo_database_data

# Voir les variables du conteneur
docker compose exec kubodb env | grep POSTGRES
```

### Après `docker compose down -v` :

```bash
# Le volume a disparu !
docker volume ls | grep kubo
# (rien ne s'affiche)

# Au prochain `docker compose up -d`, un nouveau volume est créé
```

## 🛡️ Bonne pratique

### Pour le développement :

Quand vous changez les credentials dans `compose.yaml` :

```bash
# Toujours utiliser -v pour supprimer les volumes
docker compose down -v
docker compose up -d
```

### Alternative : Utiliser des volumes nommés avec suppression explicite

```bash
# Supprimer un volume spécifique
docker volume rm kubo_database_data

# Ou supprimer tous les volumes non utilisés
docker volume prune
```

## 📊 Différence entre `down` et `down -v`

| Commande | Supprime les conteneurs | Supprime les volumes | Effet sur les données |
|----------|------------------------|----------------------|----------------------|
| `docker compose stop` | ❌ Non | ❌ Non | Données conservées |
| `docker compose down` | ✅ Oui | ❌ Non | Données conservées |
| `docker compose down -v` | ✅ Oui | ✅ Oui | ⚠️ Données perdues |

## 💡 Pourquoi Docker fait ça ?

C'est une **fonctionnalité**, pas un bug !

Les volumes Docker permettent de :
- 📦 Persister les données entre les redémarrages
- 🔄 Mettre à jour l'image sans perdre les données
- 💾 Faire des backups des données

Mais pour PostgreSQL, ça signifie que les credentials sont "fixés" à la création.

## 🚀 Commandes utiles

```bash
# Voir tous les volumes
docker volume ls

# Inspecter un volume
docker volume inspect kubo_database_data

# Supprimer un volume spécifique
docker volume rm kubo_database_data

# Supprimer tous les volumes non utilisés
docker volume prune -f

# Recréer complètement (conteneurs + volumes)
docker compose down -v && docker compose up -d
```

## 📝 Résumé

**Le problème :** PostgreSQL stocke les credentials dans le volume Docker à la première création.

**La solution :** Supprimer le volume avec `docker compose down -v` pour forcer une nouvelle initialisation.

**La commande magique :**
```bash
make fix-db
```

C'est tout ! 🎉

