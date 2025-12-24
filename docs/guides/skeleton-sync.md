# 🔄 Skeleton Sync - Mise à jour du projet

> Synchronisation sécurisée avec le skeleton de référence

## 📋 Vue d'ensemble

Lorsque de nouvelles fonctionnalités sont ajoutées au framework Ogan, elles arrivent de deux façons :

| Source | Mise à jour | Exemples |
|--------|------------|----------|
| **ogan/core** | `composer update ogan/core` | Classes PHP, helpers |
| **Skeleton** | `skeleton:sync` | Commandes, docs, .env.example |

La commande `skeleton:sync` permet de récupérer les nouveaux fichiers du skeleton sans écraser votre travail.

---

## 🚀 Commandes

### Voir les différences

```bash
php bin/console skeleton:diff
```

Affiche la liste des fichiers nouveaux ou modifiés **sans rien modifier**.

```
🆕 Nouveaux fichiers :
   + bin/commands/seo.php
   + docs/guides/seo.md

📝 Fichiers modifiés :
   ~ .env.example
   ~ bin/console

💡 Utilisez 'php bin/console skeleton:sync' pour synchroniser.
```

### Synchroniser

```bash
php bin/console skeleton:sync
```

Lance l'assistant de synchronisation interactif.

---

## 📖 Menu interactif

```
Que voulez-vous faire ?
═══════════════════════════════════════════════════════════
[1] Copier tous les NOUVEAUX fichiers (sans écraser)
[2] Voir les différences (diff) d'un fichier modifié
[3] Copier un fichier spécifique
[4] Tout copier (avec confirmation pour chaque modification)
[0] Annuler
```

### Option 1 : Nouveaux fichiers uniquement

Copie tous les fichiers qui **n'existent pas** dans votre projet.
- ✅ Sûr : n'écrase jamais rien
- ✅ Rapide : pas de confirmation

### Option 2 : Voir les diffs

Affiche les différences entre votre fichier et la nouvelle version.

```
Fichiers modifiés :
  [0] docs/guides/configuration.md
  [1] .env.example

Numéro du fichier à comparer : 1

═══════════════════════════════════════════════════════════
Différences pour : .env.example
═══════════════════════════════════════════════════════════

- DB_HOST=localhost
+ DATABASE_URL="mysql://user:pass@localhost:3306/db"

Voulez-vous remplacer ce fichier ? (o/N) :
```

### Option 3 : Fichier spécifique

Choisissez un fichier précis à copier (nouveau ou modifié).

### Option 4 : Tout avec confirmation

Passe en revue chaque fichier :
- Nouveaux fichiers : copiés automatiquement
- Fichiers modifiés : demande confirmation avec option diff

```
📝 docs/guides/configuration.md
   Remplacer ? (o/N/d=diff) : d    ← Voir la diff d'abord
   Remplacer ? (o/N) : o           ← Confirmer
   ✓ Remplacé (backup: .backup-20251224-161600)
```

---

## 🔒 Sécurité

### Jamais d'écrasement automatique

La commande ne remplace **jamais** un fichier existant sans votre confirmation explicite.

### Backups automatiques

Avant chaque remplacement, un backup est créé :

```
fichier.php → fichier.php.backup-20251224-161600
```

Vous pouvez restaurer à tout moment :
```bash
mv fichier.php.backup-20251224-161600 fichier.php
```

### Fichiers synchronisés

Seuls les fichiers "framework" sont analysés :

| Dossier/Fichier | Contenu |
|-----------------|---------|
| `bin/commands/` | Commandes console |
| `bin/console` | Point d'entrée CLI |
| `docs/` | Documentation |
| `.env.example` | Exemple de configuration |

### Fichiers ignorés (jamais touchés)

- `src/` - Votre code
- `templates/` - Vos templates
- `config/` - Votre configuration
- `public/` - Vos assets
- `.env` - Vos secrets
- `vendor/` - Dépendances

---

## 💡 Workflow recommandé

### Après chaque `composer update ogan/core`

```bash
# 1. Mettre à jour le core
composer update ogan/core

# 2. Vérifier les nouveautés du skeleton
php bin/console skeleton:diff

# 3. Si des nouveautés, synchroniser
php bin/console skeleton:sync
# → Choisir [1] pour les nouveaux fichiers
# → Choisir [4] pour tout passer en revue
```

### Première installation de skeleton:sync

Si votre projet n'a pas encore la commande `skeleton:sync`, copiez-la manuellement :

```bash
# Depuis le skeleton
cp /path/to/Mini-Fw/bin/commands/skeleton.php votre-projet/bin/commands/

# Ajouter dans bin/console :
require $commandsDir . '/skeleton.php';
registerSkeletonCommands($app);
```

---

## ❓ FAQ

### Q: Un fichier a été écrasé par erreur ?

Restaurez le backup :
```bash
ls *.backup-*                    # Voir les backups
mv fichier.backup-XXX fichier    # Restaurer
```

### Q: Comment ignorer un fichier spécifique ?

Lors de la confirmation, tapez `N` (ou appuyez sur Entrée) :
```
Remplacer ? (o/N) : N
○ Ignoré
```

### Q: La commande échoue au téléchargement ?

Vérifiez :
1. Votre connexion internet
2. Que Git est installé : `git --version`
3. L'accès à GitHub : `ping github.com`
