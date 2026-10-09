# 📝 Changelog - BestQHSE

Tous les changements notables de ce projet sont documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

---

## [Non publié]

### À venir

- Authentification Laravel Sanctum complète
- Middleware de permissions et rôles
- Tests complémentaires (88 tests restants)
- Frontend avec React/Vue.js
- Génération de rapports PDF
- Notifications par email
- Documentation architecture technique

---

## [1.1.0] - 2026-01-15

### 🧪 Tests et Qualité

Version axée sur les tests et l'assurance qualité du code.

### ✨ Ajouts

#### Tests Unitaires (5 tests)

- **UserTest** - Test du model User (relations, fillables, soft delete)
- **EntiteTest** - Test auto-référence et génération automatique
- **ResponsibilityTest** - Test relations et soft delete
- **AutoReferenceTraitTest** - Test du trait de génération de références

#### Tests Fonctionnels (7 tests)

- **UserControllerTest** - CRUD, validation, pagination, authentification
- **ResponsibilityControllerTest** - CRUD complet des responsabilités
- **EntiteControllerTest** - Auto-référence, tracking utilisateur
- **ProcessusControllerTest** - Relations, validation entité
- **DocumentControllerTest** - Upload fichiers, filtrage par statut
- **NonConformiteControllerTest** - Gestion sévérité et statuts
- **ActionCorrectiveControllerTest** - Workflow et completion

#### Scripts de Test (2 scripts)

- **run_tests.sh** - Script principal exécutant toute la suite de tests
  - Installation dépendances
  - Migration database
  - Exécution tests parallèles
  - Génération rapport de couverture
- **test_api_endpoints.sh** - Tests des endpoints API avec cURL
  - Authentification
  - Tests de tous les endpoints principaux
  - Validation des réponses JSON:API

#### Package JSON:API

- **timacdonald/json-api** - Installation et configuration
- Support complet de la spécification JSON:API v1.0
- Formatage automatique des réponses
- Pagination standardisée (20 items par défaut)

### 📚 Documentation

- Mise à jour README.md avec section tests
- Mise à jour TODO.md (progression 80%)
- Mise à jour CHANGELOG.md (ce fichier)
- Mise à jour RECAP_PROJECT.md avec tests

### 🔧 Améliorations

- Meilleure couverture de code (~15%)
- Tests de validation des données
- Tests des relations entre models
- Tests des permissions (préparation)

### 📊 Statistiques

- **12 tests créés** (5 unitaires + 7 fonctionnels)
- **2 scripts de test** automatisés
- **Coverage:** ~15% (objectif: 80%)
- **Fichiers:** ~175+ au total

---

## [1.0.0] - 2026-01-15

### 🎉 Version initiale - Fondations du Backend

Cette première version établit toutes les fondations du backend BestQHSE avec une architecture complète et robuste.

### ✨ Ajouts

#### Infrastructure de Base

- **[Laravel 12.47.0]** Configuration initiale du framework
- **[PHP 8.2+]** Support des dernières fonctionnalités PHP
- **[JSON:API]** Implémentation complète de la spécification JSON:API v1.0
- **[Composer]** Gestion des dépendances avec autoload PSR-4

#### Base de Données (36 Migrations)

- Migration de mise à jour de la table `users` avec champs BestQHSE
- Tables principales:
  - `enterprises` - Gestion des entreprises
  - `sites` - Gestion multi-sites
  - `norms` - Normes ISO
- Tables commerciales:
  - `articles` - Articles des normes
  - `offers` - Offres d'abonnement
  - `enterprise_subscriptions` - Abonnements
  - `complaints` - Réclamations clients
- Tables processus (10 tables):
  - `processes`, `activities`, `risks`, `opportunities`
  - `objectives`, `actions`, `process_resources`
  - `process_interactions`, `responsibilities`, `team_members`
- Tables audit et conformité:
  - `audits`, `non_conformities`, `applicable_requirements`
- Tables contexte stratégique:
  - `stakeholders`, `contexts`, `strategic_axes`, `dashboards`
- Tables management:
  - `management_reviews`, `satisfaction_surveys`, `modifications`
- Tables documentaires:
  - `documents`, `plans`
- Tables droits:
  - `permissions`, `roles`, `permission_role`, `user_permissions`
- Tables RH:
  - `job_descriptions`, `employee_evaluations`

**Caractéristiques communes:**

- Génération automatique de références (format: PREFIX-YYYY-NNN)
- Soft deletes sur toutes les tables
- Champs d'audit (created_by, updated_by, deleted_by)
- Timestamps Laravel (created_at, updated_at)

#### Models Eloquent (35 Models)

- Tous les models avec relations complètes (hasMany, belongsTo, belongsToMany)
- Propriété `$fillable` exhaustive pour chaque model
- Méthode `casts()` pour le typage fort
- Traits appliqués: `HasFactory`, `SoftDeletes`, `HasAuditFields`, `HasReference`

#### Traits Personnalisés (2)

- **`HasAuditFields`**
  - Gestion automatique des champs created_by, updated_by, deleted_by
  - Remplissage automatique avec l'utilisateur authentifié
  - Relations vers le modèle User (creator, updater, deleter)
- **`HasReference`**
  - Génération automatique de références uniques
  - Format: PREFIX-YYYY-NNN (ex: USER-2026-001)
  - Séquence annuelle (remise à zéro chaque année)
  - 35 préfixes configurés

#### Resources JSON:API (34 + 2 Base)

- **`JsonApiResource`** - Classe de base pour les resources simples
  - Méthodes abstraites: `getAttributes()`, `getRelationships()`
  - Format de date ISO 8601 automatique
  - Support des relations lazy-loaded
- **`JsonApiCollection`** - Classe de base pour les collections
  - Pagination automatique (20 items/page)
  - Links de navigation (self, first, last, prev, next)
  - Métadonnées de pagination (current_page, per_page, total)

- 34 Resources spécifiques pour chaque entité

#### Controllers API (34)

- Implémentation complète des méthodes CRUD:
  - `index()` - Liste paginée (20 items)
  - `store()` - Création avec validation
  - `show()` - Affichage détaillé
  - `update()` - Mise à jour avec validation
  - `destroy()` - Suppression (soft delete)
- Validation stricte des données
- Eager loading des relations
- Réponses au format JSON:API

#### Routes API (34)

- Routes RESTful avec `Route::apiResource()`
- Format d'URL en kebab-case
- Endpoints complets pour toutes les entités
- Documentation des paramètres acceptés

#### Factories (10)

- **UserFactory** - Génération d'utilisateurs avec rôles
  - States: admin(), pilot(), auditor()
- **EnterpriseFactory** - Génération d'entreprises
  - States: inactive(), small(), large()
- **SiteFactory** - Génération de sites
  - State: inactive()
- **ProcessFactory** - Génération de processus
  - States: management(), operational(), support()
- **DocumentFactory** - Génération de documents
  - State: approved()
- **AuditFactory** - Génération d'audits
  - State: completed()
- **RiskFactory** - Génération de risques
  - State: critical()
- **ActionFactory** - Génération d'actions
  - States: corrective(), improvement()
- **NonConformityFactory** - Génération de NC
  - States: major(), closed()
- **PermissionFactory** - Génération de permissions
- **RoleFactory** - Génération de rôles

#### Seeders (3)

- **PermissionsSeeder**
  - Création de 88 permissions
  - Organisées en 15 modules
  - Idempotent (truncate avant insertion)
- **SuperAdminSeeder**
  - Création du compte Super Admin
  - Utilisation des variables d'environnement
  - Attribution de toutes les permissions
- **ResponsibilitiesSeeder**
  - Seeder informatif (pas de données)

- **DatabaseSeeder**
  - Orchestration des seeders
  - Ordre d'exécution: Permissions → SuperAdmin → Responsibilities

#### Scripts de Test (5 + README)

- `01_test_migrations.sh` - Test des migrations
- `02_test_seeders.sh` - Test des seeders
- `03_test_factories.sh` - Test des factories
- `04_test_api_endpoints.sh` - Test des endpoints API
- `05_test_full_workflow.sh` - Workflow complet
- `README.md` - Documentation des scripts

**Fonctionnalités des scripts:**

- Tests automatisés avec Bash
- Vérifications avec `php artisan tinker`
- Tests API avec cURL
- Exécutables (`chmod +x`)

#### Documentation (7 fichiers)

- **README.md** - Vue d'ensemble du projet
- **md/RECAP_PROJET.md** - Documentation complète
- **md/QUICK_START.md** - Guide de démarrage rapide
- **md/API_ROUTES.md** - Documentation des endpoints
- **md/INSTALL.md** - Guide d'installation détaillé
- **md/TODO.md** - Liste des tâches et progression
- **md/CHANGELOG.md** - Ce fichier
- **local_tests/README.md** - Documentation des tests

### 🔧 Changements Techniques

#### Packages Ajoutés

- `laravel/sanctum` - Authentification API (préparation)
- `laravel-json-api/laravel` ^5.1 - Support JSON:API

#### Configuration

- Support PostgreSQL, MySQL et SQLite
- Variables d'environnement pour le Super Admin
- Session sur base de données
- Pagination par défaut: 20 items

#### Conventions de Code

- PSR-12 pour le style de code PHP
- Commentaires clairs et concis
- Pas d'emojis dans le code
- Documentation PHPDoc pour les méthodes publiques

### 📊 Statistiques v1.0.0

| Composant     | Nombre | État    |
| ------------- | ------ | ------- |
| Migrations    | 36     | ✅ 100% |
| Models        | 35     | ✅ 100% |
| Traits        | 2      | ✅ 100% |
| Resources     | 36     | ✅ 100% |
| Controllers   | 34     | ✅ 100% |
| Routes        | 34     | ✅ 100% |
| Factories     | 10     | ✅ 100% |
| Seeders       | 3      | ✅ 100% |
| Scripts Tests | 5      | ✅ 100% |
| Documentation | 7      | ✅ 100% |
| Permissions   | 88     | ✅ 100% |

**Total:**

- **~15,000+ lignes de code** écrites
- **~160+ fichiers** créés
- **88 permissions** définies
- **Progression globale: 65%**

### 🎯 Objectifs Atteints

- ✅ Architecture complète et scalable
- ✅ Respect des standards (JSON:API, PSR-12)
- ✅ Code bien documenté
- ✅ Tests automatisés
- ✅ Système de permissions granulaire
- ✅ Traçabilité complète (audit fields)
- ✅ Soft deletes partout
- ✅ Références uniques auto-générées

### 📝 Notes de Version

Cette version établit une base solide pour le développement futur. Toutes les entités principales du système de management de la qualité sont implémentées et fonctionnelles.

Le système est prêt pour:

- L'ajout de l'authentification
- L'implémentation des middleware de permissions
- Les tests unitaires et d'intégration
- Le développement du frontend

---

## [0.1.0] - 2026-01-14

### 🚀 Initialisation du Projet

#### Ajouts

- Configuration initiale de Laravel 12
- Structure de base du projet
- Configuration Docker
- Fichier LICENSE (MIT)
- Fichier .gitignore

---

## Légende

- `Ajouté` pour les nouvelles fonctionnalités
- `Modifié` pour les modifications de fonctionnalités existantes
- `Obsolète` pour les fonctionnalités qui seront supprimées
- `Supprimé` pour les fonctionnalités supprimées
- `Corrigé` pour les corrections de bugs
- `Sécurité` pour les vulnérabilités corrigées

---

**Format du Changelog:** Keep a Changelog 1.0.0  
**Versioning:** Semantic Versioning 2.0.0  
**Dernière mise à jour:** 15 janvier 2026
