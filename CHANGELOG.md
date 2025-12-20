# 📋 Changelog - Ogan Framework

Tous les changements notables de ce projet sont documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

---

## [1.0.0] - 2024-12-20

### ✨ Nouvelles fonctionnalités

#### 🗑️ Soft Delete
- Ajout du trait `SoftDeletes` pour suppression logique
- Méthodes: `delete()`, `forceDelete()`, `restore()`, `trashed()`
- Scopes: `withTrashed()`, `onlyTrashed()`, `withoutTrashed()`
- Extension de `QueryBuilder` avec `whereNull()`, `whereNotNull()`
- Filtrage automatique via `static::query()` override
- Documentation: `docs/guides/soft-delete.md`

#### 📢 Event Dispatcher
- Classe `Event` de base avec `stopPropagation()`
- `EventDispatcher` singleton avec priorités
- Événements kernel: `RequestEvent`, `ResponseEvent`, `ExceptionEvent`, `ControllerEvent`, `TerminateEvent`
- Documentation: `docs/guides/event-dispatcher.md`

#### 🔌 API REST Support
- `ApiController` avec méthodes: `json()`, `success()`, `error()`, `notFound()`, `validationError()`, etc.
- Sérialisation des modèles: `toArray()`, `toJson()`
- Propriétés `$hidden` et `$visible` pour contrôler la sérialisation
- Méthodes `makeHidden()`, `makeVisible()`
- Commande `make:api` pour générer des controllers CRUD
- Amélioration de `AbstractController::json()` pour retourner Response
- Documentation: `docs/guides/api-rest.md`

#### 🌱 Seeders
- Classe de base `Seeder` avec helpers `info()`, `success()`, `error()`, `warning()`
- Méthode `create()` factory-like pour création en masse
- Commande `make:seeder` pour générer des seeders
- Commande `db:seed` pour exécuter les seeders
- Générateur `SeederGenerator`

#### 📝 Logging amélioré
- Support format JSON structuré
- Channels multiples (app, security, queries, etc.)
- Rotation automatique des fichiers (10 Mo par défaut, 5 fichiers)
- Méthodes `channel()`, `withJsonFormat()`
- Paramètres: `$maxFileSize`, `$maxFiles`

### 🔧 Améliorations

- `Model::find()` et `Model::all()` utilisent `static::query()` pour supporter les traits
- `AbstractController::json()` retourne maintenant `Response` au lieu de `void`
- Ajout de `getAttributes()` dans `Model`

### 📚 Documentation

- Mise à jour de `docs/guides/code-generation.md` avec nouvelles commandes
- Création de `docs/guides/soft-delete.md`
- Création de `docs/guides/api-rest.md`
- Création de `docs/guides/event-dispatcher.md`

### 📁 Nouveaux fichiers

```
ogan/
├── Controller/
│   └── ApiController.php
├── Console/Generator/
│   ├── ApiControllerGenerator.php
│   └── SeederGenerator.php
├── Database/
│   ├── Seeder.php
│   └── Traits/
│       └── SoftDeletes.php
└── Event/
    ├── Event.php
    ├── EventDispatcher.php
    └── KernelEvents.php

bin/commands/
├── api.php
└── seeder.php

docs/guides/
├── api-rest.md
├── event-dispatcher.md
└── soft-delete.md
```

---

## [0.x.x] - Versions antérieures

Voir l'historique Git pour les versions précédentes.
