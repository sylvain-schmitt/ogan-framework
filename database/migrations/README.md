# 📦 Migrations - Ogan Framework

> ⚠️ **Note** : Ce README concerne l'ancien système de migrations.  
> Le framework utilise maintenant un **système de migrations versionnées**.  
> Consultez la [documentation complète](../../docs/guides/migrations.md) pour plus d'informations.

## 🚀 Système de migrations versionnées

Le framework Ogan inclut maintenant un système de migrations versionnées complet.

### Commandes disponibles

```bash
# Exécuter toutes les migrations en attente
php bin/migrate

# Annuler la dernière migration
php bin/migrate rollback

# Annuler plusieurs migrations
php bin/migrate rollback --steps=3

# Voir le statut des migrations
php bin/migrate status
```

### Documentation complète

Consultez le [Guide des migrations](../../docs/guides/migrations.md) pour :
- Créer de nouvelles migrations
- Comprendre la structure d'une migration
- Gérer les migrations multi-base de données
- Bonnes pratiques et exemples

---

## 📁 Fichiers de migration

Les migrations sont stockées dans `database/migrations/` et suivent le format :
```
YYYY_MM_DD_HHMMSS_description.php
```

Exemple : `2024_01_01_000000_create_user_table.php`

---

## 🔄 Migration depuis l'ancien système

Si vous avez utilisé l'ancien système avec des fichiers `.sql`, vous devez :

1. Convertir vos migrations SQL en classes PHP
2. Les placer dans `database/migrations/` avec le bon format de nom
3. Exécuter `php bin/migrate` pour les appliquer

Voir la [documentation](../../docs/guides/migrations.md) pour des exemples de conversion.

