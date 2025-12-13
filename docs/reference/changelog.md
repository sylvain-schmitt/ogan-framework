# 📋 Changelog - Ogan Framework

> Historique des modifications et phases de développement

## ✅ Modifications Effectuées

### 1. Renommage BaseController → AbstractController

**Pourquoi ?**
- `BaseController` est une classe abstraite, donc `AbstractController` est plus approprié
- Conforme aux conventions de nommage (comme Symfony)

**Fichiers modifiés :**
- `ogan/Controller/BaseController.php` → `ogan/Controller/AbstractController.php`
- `src/Controller/HomeController.php`
- Documentation mise à jour

### 2. Renommage storage/ → var/

**Pourquoi ?**
- Convention standard (Symfony, Laravel utilisent `var/`)
- Séparation claire : `var/cache` et `var/log`

**Structure :**
```
var/
├── cache/    # Cache de l'application
└── log/      # Fichiers de logs
```

**Fichiers modifiés :**
- `ogan/Logger/Logger.php`
- `ogan/Database/Database.php`
- `ogan/Middleware/Examples/LoggerMiddleware.php`

### 3. Intégration Composer et .env

**Modifications :**

#### Kernel.php
- Initialisation automatique de `Config` avec support `.env`
- Chargement de la configuration au démarrage

#### AbstractController.php
- Utilise `Config::all()` au lieu de `require` direct
- Support automatique des variables d'environnement

**Hiérarchie de priorité :**
1. Variables d'environnement (`.env`) → **PRIORITÉ MAXIMALE**
2. Fichier PHP (`config/parameters.php`)
3. Valeurs par défaut

### 4. Système FormType (Comme Symfony)

**Fichiers créés :**

#### Interfaces et Classes de Base
- `ogan/Form/FormTypeInterface.php`
- `ogan/Form/AbstractType.php`
- `ogan/Form/FormBuilder.php`
- `ogan/Form/FormView.php`
- `ogan/Form/FormFactory.php`

#### Types de Champs
- `ogan/Form/Types/FieldTypeInterface.php`
- `ogan/Form/Types/TextType.php`
- `ogan/Form/Types/EmailType.php`
- `ogan/Form/Types/PasswordType.php`
- `ogan/Form/Types/TextareaType.php`
- `ogan/Form/Types/SelectType.php`
- `ogan/Form/Types/CheckboxType.php`
- `ogan/Form/Types/RadioType.php`
- `ogan/Form/Types/DateType.php`
- `ogan/Form/Types/NumberType.php`
- `ogan/Form/Types/FileType.php`
- `ogan/Form/Types/SubmitType.php`

**Fonctionnalités :**
- ✅ Création de formulaires déclaratifs
- ✅ Validation automatique
- ✅ Rendu HTML automatique
- ✅ Gestion des erreurs
- ✅ Intégration avec Validator
- ✅ Support des options personnalisées

---

## 📊 Phases de Développement

### Phase -1 : Renommage en "Ogan" et Documentation Pédagogique (TERMINÉE ✅)
- [x] Renommer framework/ en ogan/
- [x] Mettre à jour tous les namespaces Framework\ vers Ogan\
- [x] Créer le guide pédagogique complet
- [x] Commenter en détail les fichiers critiques
- [x] Mettre à jour la documentation

### Phase 0 : Restructuration Architecture (TERMINÉE ✅)
- [x] Créer la structure framework/ et src/
- [x] Déplacer les fichiers du framework
- [x] Mettre à jour les namespaces
- [x] Adapter l'autoloader pour 2 namespaces
- [x] Vérifier que tout fonctionne

### Phase 1 : Fondations et Principes SOLID (TERMINÉE ✅)
- [x] Créer les interfaces de base (PSR-11, PSR-7)
- [x] Implémenter les interfaces dans les classes existantes
- [x] Créer les exceptions personnalisées
- [x] Ajouter la gestion des erreurs (ErrorHandler)

### Phase 2 : Amélioration du Container DI (TERMINÉE ✅)
- [x] Ajouter l'autowiring avancé
- [x] Gérer les aliases de services
- [x] Gérer les tags de services

### Phase 3 : Router Avancé (TERMINÉE ✅)
- [x] Améliorer le matching avec contraintes de paramètres
- [x] Ajouter les middlewares/guards
- [x] Implémenter les groupes de routes
- [x] Gérer les sous-domaines et préfixes

### Phase 4 : Système HTTP Robuste (TERMINÉE ✅)
- [x] Enrichir Request (headers, files, session)
- [x] Enrichir Response (headers, cookies, redirects)
- [x] Ajouter la gestion des sessions

### Phase 5 : Moteur de Templates Avancé (TERMINÉE ✅)
- [x] Système d'héritage de templates
- [x] Composants réutilisables
- [x] Helpers et fonctions de vue
- [x] Échappement automatique et sécurité
- [x] **Compilateur de templates avec syntaxe `{{ }}`** (style Twig/Blade)
- [x] Support des expressions PHP complexes (`{{ $variable }}`, `{{ ucfirst($type) }}`)
- [x] Compilation automatique des composants
- [x] Cache intelligent (auto-reload en dev, persistant en prod)

### Phase 5.5 : Refactorisation du Compilateur de Templates (TERMINÉE ✅ - 2025-01-XX)
- [x] **Refactorisation complète selon les principes SOLID**
- [x] Réduction de 92,5% du code (de 2538 à 190 lignes)
- [x] Séparation des responsabilités en classes spécialisées :
  - `ExpressionCompiler` : Compilation des expressions `{{ }}`
  - `ExpressionParser` : Parsing et transformation des expressions
  - `ControlStructureCompiler` : Compilation des structures de contrôle (if, foreach, etc.)
  - `VariableTransformer` : Transformation des variables (ajout de `$`)
  - `VariableProtector` : Protection des variables PHP existantes
  - `DotSyntaxTransformer` : Transformation de la syntaxe point (`.`) en flèche (`->`)
  - `StringProtector` : Protection des chaînes de caractères
  - `PlaceholderManager` : Gestion des placeholders
  - `PhpKeywordChecker` : Vérification des mots-clés PHP
- [x] Architecture modulaire et extensible
- [x] Code plus maintenable et testable
- [x] Correction de bugs de transformation de variables dans les expressions ternaires
- [x] Support complet des méthodes `$this` (getFlash, hasFlash, get, set, has)
- [x] Support des assignations de variables multi-lignes
- [x] Transformation correcte des variables dans les index de tableaux

**Structure créée :**
```
ogan/View/Compiler/
├── CompilerInterface.php
├── Exception/CompilationException.php
├── Expression/
│   ├── ExpressionCompiler.php
│   └── ExpressionParser.php
├── Control/
│   └── ControlStructureCompiler.php
├── Variable/
│   ├── VariableTransformer.php
│   └── VariableProtector.php
├── Syntax/
│   └── DotSyntaxTransformer.php
└── Utility/
    ├── PlaceholderManager.php
    ├── StringProtector.php
    └── PhpKeywordChecker.php
```

**Bénéfices :**
- ✅ Respect des principes SOLID (SRP, OCP, DIP)
- ✅ Code plus facile à maintenir et déboguer
- ✅ Extension possible sans modification du code existant
- ✅ Tests unitaires facilités pour chaque composant

### Phase 6 : Services et Outils (TERMINÉE ✅)
- [x] Service de validation de formulaires
- [x] Service de gestion de la base de données (PDO abstrait)
- [x] Logger (PSR-3)
- [x] Gestionnaire de configuration (env, yaml, etc.)

### Phase 7 : Intégration Composer (TERMINÉE ✅)
- [x] Configuration composer.json
- [x] Autoload PSR-4 avec Composer
- [x] Permettre l'ajout de packages externes
- [x] Documentation d'installation

### Phase 7.5 : ORM Maison (TERMINÉE ✅)
- [x] Créer la couche Database (connexion PDO)
- [x] Query Builder (SELECT, INSERT, UPDATE, DELETE)
- [x] Entity/Model de base avec méthodes CRUD
- [x] Repository Pattern
- [x] Hydratation automatique des entités
- [x] Documentation et exemples

### Phase 8 : Tests et Documentation (TERMINÉE ✅)
- [x] Exemples d'utilisation
- [x] Documentation pédagogique
- [x] Guide de démarrage rapide

### Phase 1.4 : Suite de Tests PHPUnit (TERMINÉE ✅ - 2025-12-07)
- [x] Installation de PHPUnit via Composer
- [x] Configuration `phpunit.xml` avec suites Unit et Integration
- [x] Structure complète `tests/` avec bootstrap
- [x] Tests unitaires pour tous les composants principaux :
  - [x] Router (7 tests : add route, match routes, generate URLs, method not allowed)
  - [x] Container (7 tests : set/get, singleton, autowiring, alias, factory)
  - [x] QueryBuilder (9 tests : SELECT, WHERE, INSERT, UPDATE, DELETE, ORDER BY, LIMIT)
  - [x] Model (6 tests : create, find, update, delete, all, where)
  - [x] View (5 tests : render, escape, section, extend)
  - [x] Session (8 tests : set/get, has, remove, flash, clear, all)
- [x] Tests d'intégration pour les routes (4 tests : dispatch complet, paramètres, erreurs)
- [x] Correction de tous les problèmes de namespace et d'architecture
- [x] **46 tests au total, 69 assertions, tous passent** ✅

**Fichiers créés :**
- `phpunit.xml` - Configuration PHPUnit
- `tests/bootstrap.php` - Bootstrap pour les tests
- `tests/Unit/RouterTest.php`
- `tests/Unit/ContainerTest.php`
- `tests/Unit/QueryBuilderTest.php`
- `tests/Unit/ModelTest.php`
- `tests/Unit/ViewTest.php`
- `tests/Unit/SessionTest.php`
- `tests/Integration/RouteIntegrationTest.php`

**Commandes disponibles :**
```bash
vendor/bin/phpunit              # Exécuter tous les tests
vendor/bin/phpunit --testdox    # Format lisible
vendor/bin/phpunit tests/Unit   # Tests unitaires uniquement
vendor/bin/phpunit tests/Integration  # Tests d'intégration uniquement
```

### Phase 1.5 : Système de Formulaires avec Contraintes (TERMINÉE ✅ - 2025-12-13)
- [x] **Système de contraintes pour les formulaires**
  - `Required` : Champ obligatoire
  - `Email` : Validation d'email
  - `MinLength` / `MaxLength` : Longueur de chaîne
  - `EqualTo` : Comparaison avec un autre champ
  - `UniqueEntity` : Validation d'unicité en base de données
- [x] Méthodes `isSubmitted()` et `isValid()` dans `FormBuilder`
- [x] Validation centralisée dans les FormTypes
- [x] Simplification des contrôleurs (logique de validation déplacée)
- [x] Mise à jour de `make:auth` avec les nouvelles contraintes

**Fichiers créés :**
- `ogan/Form/Constraint/ConstraintInterface.php`
- `ogan/Form/Constraint/Required.php`
- `ogan/Form/Constraint/Email.php`
- `ogan/Form/Constraint/MinLength.php`
- `ogan/Form/Constraint/MaxLength.php`
- `ogan/Form/Constraint/EqualTo.php`
- `ogan/Form/Constraint/UniqueEntity.php`

### Phase 1.6 : Authentification "Remember Me" (TERMINÉE ✅ - 2025-12-13)
- [x] **Service RememberMeService** (`ogan/Security/RememberMeService.php`)
  - Création de tokens sécurisés (SHA-256)
  - Stockage en base de données
  - Gestion des cookies (30 jours)
  - Cleanup des tokens expirés
- [x] **Middleware RememberMeMiddleware** (`ogan/Middleware/RememberMeMiddleware.php`)
  - Auto-login via cookie
  - Vérification du token à chaque requête
- [x] **Migration `remember_tokens`** générée par `make:auth`
- [x] **Intégration dans SecurityController** (login/logout avec remember me)
- [x] Checkbox "Se souvenir de moi" dans `LoginFormType`
- [x] Documentation mise à jour (`sessions-cookies.md`)

**Sécurité :**
- Tokens hashés SHA-256 en base de données
- Cookies HttpOnly, SameSite=Lax
- Expiration automatique après 30 jours
- Suppression du token au logout

### Phase 1.7 : Améliorations de `make:auth` (TERMINÉE ✅ - 2025-12-13)
- [x] Correction de l'espacement checkbox/label (`ml-2` dans CheckboxType)
- [x] Les migrations ne sont plus régénérées avec `--force`
- [x] Suppression des tables inutilisées (`password_resets` retiré)
- [x] Génération de la migration `remember_tokens`
- [x] Génération des pages Dashboard et Profil utilisateur

---

## 📁 Structure Finale

```
ogan/
├── Controller/
│   └── AbstractController.php  (renommé)
├── Form/                        (NOUVEAU)
│   ├── FormTypeInterface.php
│   ├── AbstractType.php
│   ├── FormBuilder.php
│   ├── FormView.php
│   ├── FormFactory.php
│   └── Types/
│       ├── FieldTypeInterface.php
│       ├── TextType.php
│       ├── EmailType.php
│       ├── PasswordType.php
│       ├── TextareaType.php
│       ├── SelectType.php
│       ├── CheckboxType.php
│       ├── RadioType.php
│       ├── DateType.php
│       ├── NumberType.php
│       ├── FileType.php
│       └── SubmitType.php
├── Kernel/
│   └── Kernel.php               (modifié : Config init)
└── ...

var/                             (renommé depuis storage/)
├── cache/
└── log/

config/
└── parameters.php               (utilisé via Config)

.env                             (supporté automatiquement)
```

---

## 🔄 Prochaines Étapes Recommandées

Voir [Améliorations](ameliorations.md) pour les suggestions d'améliorations futures.

---

**Toutes les modifications sont terminées et testées !** ✅

