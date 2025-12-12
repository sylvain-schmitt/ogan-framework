# 🏗️ Architecture Composer - Ogan Framework

## ❓ Question : Le code du framework doit-il aller dans `vendor/` ?

**Réponse courte :** 
- **Pour le DÉVELOPPEMENT** : Le code reste à la **racine** ✅
- **Pour les UTILISATEURS** : Le code ira dans `vendor/ogan/framework/` automatiquement ✅

## 📊 Deux Contextes Différents

### 1. 🛠️ CONTEXTE : Développement du Framework (Votre Situation Actuelle)

**Structure actuelle (CORRECTE) :**
```
Mini-Fw/                    ← Votre projet de développement
├── ogan/                   ← Code du framework (à la racine) ✅
│   ├── Kernel/
│   ├── Router/
│   └── ...
├── src/                    ← Exemples d'utilisation
├── vendor/                 ← Dépendances (PHPUnit, etc.)
├── composer.json           ← Configuration du package
└── public/
```

**Pourquoi à la racine ?**
- ✅ Vous développez le framework lui-même
- ✅ Vous testez directement le code
- ✅ Vous modifiez le code facilement
- ✅ C'est la structure standard pour un package Composer

**C'est exactement comme :**
- Symfony : Le code est à la racine du repo `symfony/symfony`
- Laravel : Le code est à la racine du repo `laravel/framework`
- Votre framework : Le code est à la racine du repo `ogan/framework`

---

### 2. 👥 CONTEXTE : Utilisation du Framework (Quand il sera publié)

**Quand quelqu'un installe votre framework :**
```bash
composer require ogan/framework
```

**Structure dans leur projet :**
```
leur-projet/                ← Leur application
├── src/                    ← Leur code applicatif
├── vendor/                 ← Toutes les dépendances
│   └── ogan/
│       └── framework/     ← Votre framework (installé ici) ✅
│           └── ogan/       ← Code du framework
│               ├── Kernel/
│               ├── Router/
│               └── ...
├── composer.json
└── public/
```

**Pourquoi dans `vendor/` ?**
- ✅ C'est la convention Composer
- ✅ Toutes les dépendances sont dans `vendor/`
- ✅ Le code est protégé (pas modifié par l'utilisateur)
- ✅ Facile à mettre à jour avec `composer update`

---

## 🔄 Cycle de Vie d'un Package Composer

### Étape 1 : Développement (Vous maintenant)
```
Votre repo GitHub
├── ogan/          ← Code source du framework
├── composer.json  ← Définit le package
└── ...
```

### Étape 2 : Publication sur Packagist
```
Packagist.org
└── ogan/framework  ← Votre package publié
```

### Étape 3 : Installation par un utilisateur
```bash
composer require ogan/framework
```

### Étape 4 : Structure dans le projet utilisateur
```
leur-projet/
└── vendor/
    └── ogan/
        └── framework/  ← Votre framework installé
            └── ogan/   ← Code du framework
```

---

## 📁 Structure Recommandée pour un Package Composer

### Pour le Développement (Votre Cas)

```
ogan-framework/              ← Nom du repo
├── ogan/                    ← Code source du framework ✅
│   ├── Kernel/
│   ├── Router/
│   └── ...
├── src/                     ← Exemples / Tests d'intégration
├── tests/                   ← Tests unitaires
├── vendor/                  ← Dépendances de développement
├── composer.json            ← Configuration du package
├── README.md
└── .gitignore
```

**Points importants :**
- ✅ Le code du framework (`ogan/`) reste à la racine
- ✅ `composer.json` définit `"Ogan\\": "ogan/"` dans autoload
- ✅ Quand vous publiez, Composer copie `ogan/` dans `vendor/ogan/framework/ogan/`

---

## 🎯 Configuration dans composer.json

Votre `composer.json` actuel est **CORRECT** :

```json
{
    "name": "ogan/framework",
    "autoload": {
        "psr-4": {
            "Ogan\\": "ogan/"    ← Dit à Composer où trouver le code
        }
    }
}
```

**Ce que ça signifie :**
- Le namespace `Ogan\` correspond au dossier `ogan/`
- Quand quelqu'un installe votre package, Composer copie `ogan/` dans `vendor/ogan/framework/ogan/`
- L'autoload fonctionne automatiquement

---

## 🔍 Vérification

### Dans votre projet (développement)
```bash
# Le code est à la racine
ls ogan/
# → Kernel/, Router/, etc.
```

### Dans un projet utilisateur (après installation)
```bash
# Le code est dans vendor/
ls vendor/ogan/framework/ogan/
# → Kernel/, Router/, etc.
```

**Les deux fonctionnent !** C'est la magie de Composer. ✨

---

## ⚠️ Ce qu'il NE faut PAS faire

### ❌ MAUVAIS : Déplacer `ogan/` dans `vendor/` manuellement
```
❌ vendor/
   └── ogan/  ← NE PAS FAIRE ÇA !
```

**Pourquoi ?**
- `vendor/` est généré par Composer
- Tout ce qui est dans `vendor/` sera écrasé par `composer install`
- Vous perdrez vos modifications

### ❌ MAUVAIS : Modifier le code dans `vendor/` d'un utilisateur
```
❌ Leur projet/
   └── vendor/
       └── ogan/framework/  ← Modifications perdues au prochain update
```

**Pourquoi ?**
- `composer update` écrasera vos modifications
- Le code doit être modifié dans le repo source, pas dans `vendor/`

---

## ✅ Bonnes Pratiques

### 1. Structure du Développement
```
ogan-framework/          ← Repo de développement
├── ogan/               ← Code source (à la racine) ✅
├── src/                ← Exemples
├── tests/              ← Tests
└── composer.json       ← Configuration
```

### 2. Structure de l'Utilisation
```
projet-utilisateur/     ← Application utilisant le framework
├── src/                ← Code applicatif
├── vendor/             ← Dépendances (généré par Composer)
│   └── ogan/
│       └── framework/  ← Framework installé ✅
└── composer.json
```

### 3. Workflow de Publication

```bash
# 1. Développer dans votre repo
git add .
git commit -m "Nouvelle fonctionnalité"
git push

# 2. Créer un tag de version
git tag -a v1.0.0 -m "Version 1.0.0"
git push origin v1.0.0

# 3. Packagist détecte automatiquement le tag
# 4. Les utilisateurs peuvent installer :
composer require ogan/framework:^1.0
```

---

## 📚 Exemples Réels

### Symfony
```
symfony/symfony (repo GitHub)
├── src/                ← Code source à la racine
└── composer.json

→ Quand installé : vendor/symfony/symfony/src/
```

### Laravel
```
laravel/framework (repo GitHub)
├── src/                ← Code source à la racine
└── composer.json

→ Quand installé : vendor/laravel/framework/src/
```

### Votre Framework
```
ogan/framework (votre repo)
├── ogan/               ← Code source à la racine ✅
└── composer.json

→ Quand installé : vendor/ogan/framework/ogan/
```

---

## 🎯 Conclusion

**Votre structure actuelle est PARFAITE !** ✅

- Le code du framework (`ogan/`) reste à la racine pour le développement
- Quand vous publiez sur Packagist, Composer gère automatiquement le placement dans `vendor/`
- Les utilisateurs auront le code dans `vendor/ogan/framework/ogan/`
- Vous continuez à développer à la racine

**Ne changez rien !** Votre architecture est correcte. 🎉

---

## 🔗 Ressources

- [Composer - Autoloading](https://getcomposer.org/doc/01-basic-usage.md#autoloading)
- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)
- [Publishing Packages on Packagist](https://packagist.org/about)

