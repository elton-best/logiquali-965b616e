# 📖 INDEX DOCUMENTATION BestQHSE

> Guide de navigation rapide pour toute la documentation du projet

---

## 🏃 Démarrage Rapide

| Document                           | Description                     | Temps  |
| ---------------------------------- | ------------------------------- | ------ |
| [Quick Start](./md/QUICK_START.md) | Lancer le projet en 5 minutes   | 5 min  |
| [Installation](./md/INSTALL.md)    | Installation complète pas-à-pas | 20 min |
| [Configuration](./md/CONFIG.md)    | Variables d'environnement       | 10 min |

---

## 👤 Documentation Utilisateur

| Document                                                            | Description                                                         | Public                 |
| ------------------------------------------------------------------- | ------------------------------------------------------------------- | ---------------------- |
| [Manuel d'utilisation BestQHSE](./MANUEL_UTILISATEUR.md)            | Guide complet SIGLE, transferts, historique, permissions et support | Utilisateurs finaux    |
| [Guide utilisateur abonnements](./GUIDE_UTILISATEUR_ABONNEMENTS.md) | Gestion des abonnements et dépannage courant                        | Admins & gestionnaires |

---

## 🏗️ Architecture & Design

### Vue d'ensemble

| Document                                                                                       | Contenu                              |
| ---------------------------------------------------------------------------------------------- | ------------------------------------ |
| [Architecture SMI](./architecture/ARCHITECTURE_SMI_COMPLETE.md)                                | Architecture complète du système     |
| [Schéma Base de Données](./architecture/DATABASE_SCHEMA_SMI_COMPLETE.sql)                      | Structure complète PostgreSQL        |
| [Schéma Actions & Objectifs](./architecture/DATABASE_SCHEMA_ACTIONS_OBJECTIVES_REFACTORED.sql) | Tables actions/objectifs refactorées |
| [Schéma Multi-Rôles](./architecture/DATABASE_SCHEMA_SMI_MULTI_ROLES.sql)                       | Gestion avancée des rôles            |

### Permissions & Rôles

| Document                                                            | Contenu                    |
| ------------------------------------------------------------------- | -------------------------- |
| [Architecture Rôles](./architecture/ROLES_ARCHITECTURE.md)          | 7 rôles principaux         |
| [Permissions](./architecture/HARMONISATION_PERMISSIONS_COMPLETE.md) | 88 permissions granulaires |

---

## 📦 Modules Métier

### Modules Livrés

| Module              | Documentation                                                     | Status     |
| ------------------- | ----------------------------------------------------------------- | ---------- |
| **Audits**          | [README Audits](./md/README_AUDIT_MODULE.md)                      | ✅ Complet |
| **Processus**       | [Guide Processus](./guides/GUIDE_UTILISATION_MODULE_PROCESSUS.md) | ✅ Complet |
| **Support**         | [Module Support V2](./modules/MODULE_SUPPORT_FINAL_V2.md)         | ✅ Complet |
| **Non-Conformités** | [README NC & Actions](./md/README_NC_ACTIONS.md)                  | ✅ Complet |

### Modules en Cours

| Module          | Documentation                                        | Status      |
| --------------- | ---------------------------------------------------- | ----------- |
| **Context ISO** | [Sprint Plan](./sprint/SPRINT_5_JOURS_PLAN_AGILE.md) | 🚧 En cours |

---

## 🎯 Sprint Actuel

| Document                                                     | Description                  |
| ------------------------------------------------------------ | ---------------------------- |
| [Plan Sprint 5 jours](./sprint/SPRINT_5_JOURS_PLAN_AGILE.md) | Plan détaillé Module Context |
| [Kanban](./sprint/KANBAN_SPRINT_5_JOURS.md)                  | Tableau de bord              |
| [Résumé Sprint](./sprint/RESUME_SPRINT_5_JOURS.md)           | Vue d'ensemble               |
| [Index Sprint](./sprint/INDEX_SPRINT_5_JOURS.md)             | Navigation sprint            |

### Tâches par Développeur

| Dev      | Document                                              | Focus                      |
| -------- | ----------------------------------------------------- | -------------------------- |
| **DEV1** | [Frontend Lead](./sprint/DEV1_FRONTEND_LEAD_TASKS.md) | UI/UX, Composants Vue      |
| **DEV2** | [Backend Lead](./sprint/DEV2_BACKEND_LEAD_TASKS.md)   | API, Migrations, Modèles   |
| **DEV3** | [Full-Stack](./sprint/DEV3_FULLSTACK_TASKS.md)        | Intégration, Stores, Tests |

### Roadmap

| Document                                                        | Contenu                    |
| --------------------------------------------------------------- | -------------------------- |
| [Roadmap 4 Modules](./sprint/ROADMAP_4_MODULES_PRIORITAIRES.md) | Points 4, 5, 6, 7 ISO 9001 |

---

## 📘 Guides d'Implémentation

## 📚 Documentation Générée

| Document                                                                          | Contenu                                                |
| --------------------------------------------------------------------------------- | ------------------------------------------------------ |
| [Documentation Technique Complète](./generated/DOCUMENTATION_TECHNIQUE_PROJET.md) | Frontend + Backend + architecture + API + installation |
| [Manuel Utilisateur](./generated/MANUEL_UTILISATEUR_APPLICATION.md)               | Guide pas-à-pas pour utilisateurs finaux               |

### Guides Techniques

| Guide                                                            | Objectif                      |
| ---------------------------------------------------------------- | ----------------------------- |
| [Guide Style Client A](./guides/GUIDE_STYLE_CLIENT_A.md)         | Charte graphique & composants |
| [Guide Menus Visuels](./guides/GUIDE_VISUEL_MENUS.md)            | Navigation & menus            |
| [Guide Implémentation](./guides/IMPLEMENTATION_GUIDE.md)         | Patterns & best practices     |
| [Template Réplication](./guides/TEMPLATE_REPLICATION_MODULES.md) | Créer nouveaux modules        |

### Guides Méthodologiques

| Guide                                                                            | Objectif                  |
| -------------------------------------------------------------------------------- | ------------------------- |
| [Recommandations Techniques](./guides/RECOMMANDATIONS_TECHNIQUES_INTEGRATION.md) | Intégration modules       |
| [Questions Décisions](./guides/QUESTIONS_DECISIONS_CLES.md)                      | Décisions architecturales |

---

## 🧪 Tests & Qualité

### Documentation Tests

| Document                                                       | Contenu                     |
| -------------------------------------------------------------- | --------------------------- |
| [Guide Tests](./md/TESTS.md)                                   | Stratégie & exécution       |
| [Guide Tests Notifications](./md/GUIDE_TESTS_NOTIFICATIONS.md) | Tests système notifications |
| [Guide Tests Audits](./md/GUIDE_TEST_MODULE_AUDITS.md)         | Tests module audits         |

### Scripts de Test

```bash
# Backend
./all_tests/run-backend-tests.sh

# Frontend
./all_tests/run-frontend-tests.sh

# E2E
./all_tests/test-e2e-final.sh

# Modules spécifiques
./test-subscription-flow.sh
./test-process-module.sh
./verify-audit-module-complete.sh
```

---

## 🔧 API & Techniques

| Document                             | Contenu                          |
| ------------------------------------ | -------------------------------- |
| [Routes API](./md/API_ROUTES.md)     | Documentation complète endpoints |
| [Changelog](./md/CHANGELOG.md)       | Historique versions              |
| [Recap Projet](./md/RECAP_PROJET.md) | Vue d'ensemble technique         |

---

## 📂 Archives

Documentation historique (sessions passées, corrections résolues) :

### Structure Archives

```
docs/archive/
├── ameliorations/     (4 fichiers) - Améliorations appliquées
├── corrections/       (3 fichiers) - Bugs fixés
├── solutions/         (2 fichiers) - Solutions 403 routes
├── analyses/          (3 fichiers) - Analyses techniques
├── resumes/           (3 fichiers) - Résumés exécutifs
└── [autres]           (3 fichiers) - Plans & nettoyage
```

Voir [docs/archive/](./archive/) pour la liste complète.

---

## 🚀 Démarrage par Profil

### 👨‍💻 Nouveau Développeur

1. [Installation](./md/INSTALL.md)
2. [Architecture](./architecture/ARCHITECTURE_SMI_COMPLETE.md)
3. [Guide Implémentation](./guides/IMPLEMENTATION_GUIDE.md)
4. [Sprint Actuel](./sprint/SPRINT_5_JOURS_PLAN_AGILE.md)

### 🎨 Designer/UX

1. [Guide Style Client A](./guides/GUIDE_STYLE_CLIENT_A.md)
2. [Guide Menus](./guides/GUIDE_VISUEL_MENUS.md)
3. [Composants créés](./archive/analyses/COMPOSANTS_CREES.md)

### 🏢 Product Owner

1. [Roadmap](./sprint/ROADMAP_4_MODULES_PRIORITAIRES.md)
2. [Kanban Sprint](./sprint/KANBAN_SPRINT_5_JOURS.md)
3. [Modules livrés](./modules/)

### 🔍 QA/Testeur

1. [Guide Tests](./md/TESTS.md)
2. [Scripts de test](../all_tests/)
3. [Guides tests modules](./md/)

---

## 📞 Support

- **Documentation manquante ?** Vérifier dans [archive/](./archive/)
- **Nouvelle fonctionnalité ?** Consulter le [Sprint actuel](./sprint/)
- **Question technique ?** Voir les [Guides](./guides/)

---

**Dernière mise à jour:** 11 février 2026  
**Fichiers documentés:** 38 fichiers organisés  
**Structure:** 6 catégories principales
