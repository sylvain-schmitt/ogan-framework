# 📋 Plan d'Action - Améliorations Ogan Framework

> Plan structuré pour implémenter les améliorations suggérées

## 🎯 Vue d'Ensemble

Ce document organise les améliorations par priorité et fournit un plan d'implémentation détaillé.

---

## 📅 Phase 1 : Court Terme (1-2 mois) - PRIORITÉ HAUTE

### ✅ 1.1 Helpers de Vue (url, route, asset)
**Statut** : ✅ Terminé  
**Priorité** : 🔴 Critique  
**Estimation** : 2-3 jours  
**Date de complétion** : 2025-01-05

**Tâches** :
- [x] Helper `asset()` (déjà implémenté)
- [x] Helper `url()` pour générer des URLs absolues
- [x] Helper `route()` pour générer des URLs depuis un nom de route
- [x] Helper `css()` et `js()` pour les assets
- [x] Documentation des helpers (`docs/guides/view-helpers.md`)
- [x] Injection du Router dans la View
- [x] Mise à jour de `ViewInterface`

**Fichiers à modifier** :
- `ogan/View/View.php` : Ajouter les méthodes
- `ogan/View/ViewInterface.php` : Ajouter les signatures
- `docs/guides/view-helpers.md` : Documentation

---

### ✅ 1.2 Relations ORM (OneToMany, ManyToOne)
**Statut** : ✅ Terminé  
**Priorité** : 🔴 Critique  
**Estimation** : 5-7 jours  
**Date de complétion** : 2025-01-05

**Tâches** :
- [x] Créer `ogan/Database/Relations/Relation.php` (classe abstraite)
- [x] Créer `ogan/Database/Relations/OneToMany.php`
- [x] Créer `ogan/Database/Relations/ManyToOne.php`
- [x] Créer `ogan/Database/Relations/OneToOne.php`
- [x] Créer `ogan/Database/Relations/ManyToMany.php`
- [x] Ajouter méthodes `oneToMany()`, `manyToOne()`, `oneToOne()`, `manyToMany()` dans `Model`
- [x] Support du lazy loading (par défaut)
- [x] Support des contraintes WHERE, ORDER BY, LIMIT sur les relations
- [x] Méthodes `attach()` et `detach()` pour ManyToMany
- [x] Documentation complète (`docs/guides/orm-relations.md`)

**Fichiers à créer** :
- `ogan/Database/Relations/Relation.php` (classe abstraite)
- `ogan/Database/Relations/HasOne.php`
- `ogan/Database/Relations/HasMany.php`
- `ogan/Database/Relations/BelongsTo.php`
- `ogan/Database/Relations/BelongsToMany.php`

**Fichiers à modifier** :
- `ogan/Database/Model.php` : Ajouter les méthodes de relations
- `docs/guides/orm-relations.md` : Documentation

---

### ✅ 1.3 Système de Migrations Versionnées
**Statut** : ✅ Terminé  
**Priorité** : 🟠 Haute  
**Estimation** : 4-5 jours  
**Date de complétion** : 2025-12-06

**Tâches** :
- [x] Créer `ogan/Database/Migration/AbstractMigration.php` (classe abstraite)
- [x] Créer `ogan/Database/Migration/MigrationManager.php` (gestionnaire)
- [x] Créer table `migrations` pour suivre les migrations appliquées
- [x] Implémenter `up()` et `down()` dans les migrations
- [x] Commandes : `migrate`, `rollback`, `status`
- [x] Support des migrations par lots (batches)
- [x] Génération automatique depuis les modèles (`make`, `diff`)
- [x] Scanner automatique des modèles sans migration
- [x] Support multi-base de données (MySQL, PostgreSQL, SQLite, SQL Server)
- [x] Documentation complète (`docs/guides/migrations.md`)

**Fichiers créés** :
- `ogan/Database/Migration/AbstractMigration.php`
- `ogan/Database/Migration/MigrationManager.php`
- `ogan/Database/Migration/MigrationGenerator.php`
- `ogan/Database/Migration/MigrationScanner.php`
- `bin/migrate` (CLI pour les commandes)

**Fichiers modifiés** :
- `database/migrations/` : Migrations versionnées
- `docs/guides/migrations.md` : Documentation complète

---

### ✅ 1.4 Suite de Tests PHPUnit
**Statut** : ✅ Terminé  
**Priorité** : 🟠 Haute  
**Estimation** : 3-4 jours  
**Date de complétion** : 2025-12-07

**Tâches** :
- [x] Installer PHPUnit via Composer
- [x] Créer `phpunit.xml` avec suites Unit et Integration
- [x] Créer `tests/` directory avec structure complète
- [x] Créer `tests/bootstrap.php` pour l'initialisation
- [x] Tests unitaires pour les composants principaux :
  - [x] Router (7 tests : add route, match routes, generate URLs, etc.)
  - [x] Container (7 tests : set/get, singleton, autowiring, alias, etc.)
  - [x] QueryBuilder (9 tests : SELECT, WHERE, INSERT, UPDATE, DELETE, etc.)
  - [x] Model (6 tests : create, find, update, delete, all, where)
  - [x] View (5 tests : render, escape, section, extend)
  - [x] Session (8 tests : set/get, has, remove, flash, clear, etc.)
- [x] Tests d'intégration pour les routes (4 tests : dispatch complet, paramètres, erreurs)
- [x] Correction de tous les problèmes de namespace et d'architecture
- [x] 46 tests au total, 69 assertions, tous passent ✅
- [ ] Configuration CI/CD (optionnel - à faire plus tard)
- [x] Documentation mise à jour

**Fichiers créés** :
- `phpunit.xml` - Configuration PHPUnit
- `tests/bootstrap.php` - Bootstrap pour les tests
- `tests/Unit/RouterTest.php` - Tests du routeur
- `tests/Unit/ContainerTest.php` - Tests du conteneur DI
- `tests/Unit/QueryBuilderTest.php` - Tests du QueryBuilder
- `tests/Unit/ModelTest.php` - Tests des modèles
- `tests/Unit/ViewTest.php` - Tests de la vue
- `tests/Unit/SessionTest.php` - Tests de session
- `tests/Integration/RouteIntegrationTest.php` - Tests d'intégration des routes

---

## 📅 Phase 2 : Moyen Terme (3-6 mois) - PRIORITÉ MOYENNE

### ✅ 2.1 Cache de Configuration et Routes
**Statut** : ⚪ À faire  
**Priorité** : 🟡 Moyenne  
**Estimation** : 3-4 jours

**Tâches** :
- [ ] Créer `ogan/Cache/CacheInterface.php`
- [ ] Créer `ogan/Cache/FileCache.php`
- [ ] Implémenter cache de configuration
- [ ] Implémenter cache de routes compilées
- [ ] Commandes : `cache:clear`, `cache:warmup`
- [ ] Documentation

---

### ✅ 2.2 Commandes CLI
**Statut** : ✅ Terminé (Amélioré)  
**Priorité** : 🟡 Moyenne  
**Estimation** : 5-6 jours  
**Date de complétion** : 2025-12-07

**Tâches** :
- [x] Créer `ogan/Console/Generator/AbstractGenerator.php` (classe abstraite)
- [x] Créer `ogan/Console/Generator/ControllerGenerator.php`
- [x] Créer `ogan/Console/Generator/FormGenerator.php`
- [x] Créer `ogan/Console/Generator/ModelGenerator.php`
- [x] Créer `ogan/Console/Generator/RepositoryGenerator.php`
- [x] Créer `ogan/Console/Interactive/ModelBuilder.php` (mode interactif)
- [x] Créer `ogan/Console/Interactive/ModelAnalyzer.php` (analyse de modèles existants)
- [x] Créer `bin/make` (CLI pour la génération de code)
- [x] Commandes implémentées :
  - [x] `make controller` - Générer un contrôleur (mode interactif si nom non fourni)
  - [x] `make form` - Générer un FormType (mode interactif si nom non fourni)
  - [x] `make model` - Générer un modèle en mode interactif complet
  - [x] `make repository` - Générer un repository seul (mode interactif si nom non fourni)
  - [x] `make all` - Générer tout en mode interactif (modèle + repository + FormType + contrôleur)
- [x] Fonctionnalités avancées :
  - [x] Mode interactif pour tous les générateurs
  - [x] Détection automatique des relations via les noms de propriétés (categoryId, userId, etc.)
  - [x] Génération automatique des relations inverses (ManyToOne → OneToMany)
  - [x] Génération automatique du repository avec le modèle
  - [x] Modification de modèles existants (ajout de propriétés et relations)
  - [x] Détection automatique des clés étrangères dans les migrations (INT au lieu de VARCHAR)
- [x] Documentation (`docs/guides/code-generation.md`)
- [ ] Commandes restantes à implémenter :
  - [ ] `make:middleware`
  - [ ] `route:list`
  - [ ] `cache:clear`

---

### ✅ 2.3 Event Dispatcher
**Statut** : ⚪ À faire  
**Priorité** : 🟡 Moyenne  
**Estimation** : 4-5 jours

**Tâches** :
- [ ] Créer `ogan/Event/EventDispatcher.php`
- [ ] Créer `ogan/Event/EventInterface.php`
- [ ] Créer `ogan/Event/ListenerInterface.php`
- [ ] Implémenter événements prédéfinis :
  - [ ] `kernel.request`
  - [ ] `kernel.response`
  - [ ] `kernel.exception`
- [ ] Support des listeners asynchrones (optionnel)
- [ ] Documentation

---

### ✅ 2.4 Documentation API Générée
**Statut** : ⚪ À faire  
**Priorité** : 🟢 Basse  
**Estimation** : 2-3 jours

**Tâches** :
- [ ] Intégrer PHPDoc → HTML (Sami, phpDocumentor)
- [ ] Générer automatiquement depuis les commentaires
- [ ] Ajouter dans le workflow CI/CD
- [ ] Documentation

---

## 📅 Phase 3 : Long Terme (6+ mois) - PRIORITÉ BASSE

### ✅ 3.1 Packages Additionnels
**Statut** : ⚪ À faire  
**Priorité** : 🟢 Basse  
**Estimation** : Variable

**Packages** :
- `ogan/auth` : Authentification complète
- `ogan/mail` : Envoi d'emails
- `ogan/queue` : Files d'attente
- `ogan/cache` : Système de cache avancé

---

### ✅ 3.2 Support GraphQL
**Statut** : ⚪ À faire  
**Priorité** : 🟢 Basse  
**Estimation** : 10-15 jours

---

### ✅ 3.3 Monitoring Avancé
**Statut** : ⚪ À faire  
**Priorité** : 🟢 Basse  
**Estimation** : 5-7 jours

---

### ✅ 3.4 Application Exemple Complète
**Statut** : ⚪ À faire  
**Priorité** : 🟢 Basse  
**Estimation** : 10-15 jours

---

## 📊 Suivi des Progrès

### Légende des Statuts
- ✅ **Terminé** : Implémentation complète et testée
- 🟡 **En cours** : Implémentation en cours
- ⚪ **À faire** : Pas encore commencé
- 🔴 **Bloqué** : Bloqué par une dépendance

### Légende des Priorités
- 🔴 **Critique** : Bloque d'autres fonctionnalités
- 🟠 **Haute** : Important pour la stabilité
- 🟡 **Moyenne** : Améliore l'expérience développeur
- 🟢 **Basse** : Nice to have

---

## 🚀 Prochaines Étapes

1. ✅ **Phase 1.1** : Helpers de Vue (url, route, asset) - **TERMINÉ**
2. ✅ **Phase 1.2** : Relations ORM - **TERMINÉ**
3. ✅ **Phase 1.3** : Migrations - **TERMINÉ**
4. ✅ **Phase 1.4** : Tests PHPUnit - **TERMINÉ** (46 tests, 69 assertions)
5. ✅ **Phase 2.2** : Commandes CLI améliorées - **TERMINÉ** (Mode interactif, détection automatique des relations, analyse automatique des modèles pour FormTypes)
6. ⚪ **Phase 2.1** : Cache de configuration et routes - **À FAIRE** (Prochaine étape)

---

## 🔮 Améliorations Futures (À Planifier)

### 🔄 Détection automatique des changements de modèles

**Objectif** : Détecter automatiquement les modifications dans les modèles et générer des migrations ALTER TABLE au lieu de CREATE TABLE.

**Fonctionnalités à implémenter** :
- Comparer le modèle actuel avec la dernière migration exécutée
- Détecter les nouvelles propriétés → `ALTER TABLE ADD COLUMN`
- Détecter les propriétés supprimées → `ALTER TABLE DROP COLUMN`
- Détecter les changements de types → `ALTER TABLE MODIFY COLUMN`
- Détecter les nouveaux index → `CREATE INDEX`
- Détecter les index supprimés → `DROP INDEX`
- Générer une migration de type `YYYY_MM_DD_HHMMSS_alter_xxx_table.php`

**Commande** :
```bash
php bin/migrate diff  # Détecte les changements et génère les migrations ALTER
```

**Inspiration** : Symfony/Doctrine `doctrine:migrations:diff`

**Complexité** : 🔴 Élevée - Nécessite :
- Analyse des migrations existantes
- Comparaison des schémas
- Génération intelligente de ALTER TABLE
- Gestion des cas complexes (renommage, changements de contraintes)

---

**Dernière mise à jour** : 2025-12-07 (Phase 1.4 - Tests PHPUnit terminés)  
**Prochaine révision** : Après chaque phase complétée

---

## 📝 Notes de mise à jour récentes

### 2025-12-07 - Améliorations majeures du système de génération de code

**Nouvelles fonctionnalités** :
- ✅ Mode interactif complet pour tous les générateurs
- ✅ Détection automatique des relations via les noms de propriétés (categoryId → ManyToOne vers Category)
- ✅ Génération automatique des relations inverses (ManyToOne → OneToMany)
- ✅ Génération automatique du repository avec le modèle
- ✅ Modification de modèles existants (ajout de propriétés et relations)
- ✅ Détection automatique des clés étrangères dans les migrations (INT au lieu de VARCHAR)
- ✅ Support des 4 types de relations (ManyToOne, OneToOne, OneToMany, ManyToMany)
- ✅ Interface simplifiée (plus de questions "oui/non" répétitives)
- ✅ **NOUVEAU** : Analyse automatique du modèle pour générer les champs du FormType correspondants
- ✅ **NOUVEAU** : Détection intelligente des types de champs (description → TextareaType, email → EmailType)

**Améliorations techniques** :
- ✅ `ModelBuilder` : Assistant interactif pour créer/modifier des modèles
- ✅ `ModelAnalyzer` : Analyse des modèles existants pour préserver les propriétés
- ✅ `MigrationGenerator` : Détection améliorée des clés étrangères (categoryId, category_id, categoryid)
- ✅ `FormGenerator` : Analyse automatique du modèle et génération des champs correspondants
- ✅ Génération automatique des relations inverses dans les modèles liés

