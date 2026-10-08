# ARCHITECTURE MULTI-RÔLES - BestQHSE SMI

## Vue d'ensemble

Le système BestQHSE supporte **8 types d'utilisateurs** avec des niveaux d'accès et des dashboards différents selon leur rôle dans l'organisation.

---

## 1. SUPER ADMIN (Administrateur Système)

### Description

Administrateur technique de la plateforme BestQHSE. Gère toutes les entreprises clientes.

### Permissions

- ✅ Accès à toutes les entreprises
- ✅ Gestion des offres et abonnements
- ✅ Configuration système globale
- ✅ Monitoring et logs système
- ✅ Support technique

### Dashboard

**Route**: `/admin/dashboard`

**Widgets**:

- Statistiques globales (nombre d'entreprises, sites, utilisateurs)
- Abonnements actifs/expirés
- Revenus mensuels
- Alertes système
- Activité récente (toutes entreprises)

### Menus

```
📊 Dashboard
🏢 Entreprises
💳 Offres & Abonnements
👥 Utilisateurs (tous)
⚙️ Configuration système
📈 Analytics
🔔 Alertes système
📋 Logs d'activité
```

---

## 2. ENTERPRISE ADMIN (Administrateur Entreprise)

### Description

Administrateur de l'entreprise cliente. Vue globale sur tous les sites de son entreprise.

### Permissions

- ✅ Gestion de tous les sites de l'entreprise
- ✅ Création/modification de sites
- ✅ Gestion des utilisateurs de l'entreprise
- ✅ Attribution des rôles
- ✅ Gestion de l'abonnement
- ✅ Vue consolidée multi-sites
- ✅ Rapports consolidés

### Dashboard

**Route**: `/company/dashboard` (actuel Client A)

**Widgets**:

- Vue d'ensemble multi-sites
- KPIs consolidés (NC, audits, actions par site)
- Carte des sites avec statuts
- Abonnement et facturation
- Alertes critiques (tous sites)
- Activité récente (tous sites)

### Menus (ACTUEL - ClientALayout.vue)

```
📊 Vue d'ensemble
🏢 Sites
   ├─ Liste des sites
   ├─ Créer un site
   └─ Statistiques par site
👥 Collaborateurs (tous sites)
📋 Rapports consolidés
💳 Abonnement
⚙️ Paramètres entreprise
🔔 Notifications
```

**Sections ISO** (vue consolidée):

- Contexte (tous sites)
- Leadership (organigramme global)
- Planification (objectifs globaux)
- Support (ressources globales)
- Réalisation
- Évaluation
- Amélioration

---

## 3. SITE MANAGER (Responsable de Site)

### Description

Responsable d'un site spécifique. Dashboard similaire à Enterprise Admin mais limité à son site.

### Permissions

- ✅ Gestion complète de SON site uniquement
- ✅ Gestion des utilisateurs du site
- ✅ Tous les modules ISO pour son site
- ✅ Validation des documents du site
- ✅ Création/suivi des audits
- ✅ Gestion des NC et actions
- ✅ Rapports du site

### Dashboard

**Route**: `/site/dashboard`

**Widgets**:

- KPIs du site (NC, audits, actions, objectifs)
- Processus du site (cartographie)
- Alertes et échéances du site
- Équipe du site
- Documents à valider
- Activité récente du site

### Menus

```
📊 Dashboard Site
👥 Équipe du site
📋 Processus
   ├─ Cartographie
   ├─ Liste des processus
   └─ Indicateurs processus

📁 ISO 9001/14001/45001
   ├─ 4. Contexte
   │   ├─ SWOT/PESTEL
   │   ├─ Parties intéressées
   │   ├─ Domaine d'application
   │   └─ Système de management
   │
   ├─ 5. Leadership
   │   ├─ Politique QHSE
   │   ├─ Organigramme site
   │   └─ Responsabilités
   │
   ├─ 6. Planification
   │   ├─ Risques & Opportunités
   │   ├─ Aspects environnementaux
   │   ├─ DUERP
   │   ├─ Obligations conformité
   │   ├─ Objectifs système
   │   └─ Plans d'action
   │
   ├─ 7. Support
   │   ├─ Ressources & Équipements
   │   ├─ Compétences & Formations
   │   ├─ Communication
   │   └─ Documents
   │
   ├─ 8. Réalisation
   │   ├─ Planification opérationnelle
   │   ├─ Exigences produits/services
   │   ├─ Situations d'urgence
   │   └─ Gestion prestataires
   │
   ├─ 9. Évaluation
   │   ├─ Surveillance & Mesure
   │   ├─ Audits internes
   │   └─ Revue de direction
   │
   └─ 10. Amélioration
       ├─ Non-conformités
       ├─ Actions correctives
       └─ Amélioration continue

📊 Indicateurs & Objectifs
🔍 Audits
⚠️ Non-conformités
✅ Actions
📞 Réclamations clients
📈 Rapports site
⚙️ Paramètres
```

---

## 4. QUALITY MANAGER (Responsable Qualité)

### Description

Responsable du système de management intégré (SMI) du site. Focus sur la conformité ISO.

### Permissions

- ✅ Gestion complète des modules ISO
- ✅ Création et suivi des audits
- ✅ Gestion des NC et actions
- ✅ Validation des documents qualité
- ✅ Gestion des risques et opportunités
- ✅ Suivi des indicateurs qualité
- ✅ Planification des revues de direction
- ⚠️ Lecture seule sur les processus (pas de modification)

### Dashboard

**Route**: `/quality/dashboard`

**Widgets**:

- Conformité ISO (taux de conformité par norme)
- NC ouvertes par gravité
- Audits planifiés/réalisés
- Actions en retard
- Documents à réviser
- Indicateurs qualité (tendances)
- Réclamations clients

### Menus

```
📊 Dashboard Qualité
🔍 Audits
   ├─ Programme d'audit
   ├─ Audits planifiés
   ├─ Audits réalisés
   └─ Constats d'audit
⚠️ Non-conformités
   ├─ NC ouvertes
   ├─ NC en cours
   ├─ NC clôturées
   └─ Statistiques NC
✅ Actions
   ├─ Actions en cours
   ├─ Actions en retard
   └─ Efficacité des actions
🎯 Risques & Opportunités
📊 Indicateurs Qualité
📋 Documents Qualité
📞 Réclamations Clients
📈 Revues de Direction
📚 Contexte ISO
   ├─ SWOT/PESTEL
   ├─ Parties intéressées
   └─ Domaine d'application
⚙️ Paramètres
```

---

## 5. PROCESS OWNER (Pilote de Processus)

### Description

Responsable d'un ou plusieurs processus spécifiques. Gère son/ses processus et les éléments associés.

### Permissions

- ✅ Gestion complète de SES processus
- ✅ Gestion des activités du processus
- ✅ Définition des entrées/sorties (fournisseurs/clients)
- ✅ Gestion des ressources du processus
- ✅ Suivi des indicateurs du processus
- ✅ Gestion des risques liés au processus
- ✅ Création d'actions d'amélioration
- ⚠️ Lecture seule sur les autres processus

### Dashboard

**Route**: `/process/dashboard`

**Widgets**:

- Mes processus (liste avec statuts)
- Indicateurs de mes processus
- Risques de mes processus
- Actions liées à mes processus
- Ressources nécessaires
- Documents liés
- Activité récente

### Menus

```
📊 Dashboard Processus
📋 Mes Processus
   ├─ Vue d'ensemble
   ├─ Cartographie
   └─ Interactions (fournisseurs/clients)
🔄 Activités
   ├─ Liste des activités
   ├─ Séquencement
   ├─ Entrées (processus fournisseurs)
   └─ Sorties (processus clients)
📊 Indicateurs
   ├─ Mes indicateurs
   ├─ Saisie des valeurs
   └─ Tableaux de bord
🎯 Risques Processus
   ├─ Risques identifiés
   └─ Plans de traitement
✅ Actions d'Amélioration
💼 Ressources
   ├─ Ressources humaines
   ├─ Ressources matérielles
   └─ Compétences requises
📁 Documents Processus
⚙️ Paramètres
```

---

## 6. AUDITOR (Auditeur)

### Description

Réalise les audits internes. Peut être auditeur principal ou membre d'équipe d'audit.

### Permissions

- ✅ Consultation de tous les processus du site
- ✅ Consultation des documents
- ✅ Création et réalisation d'audits
- ✅ Saisie des constats d'audit
- ✅ Création de NC suite à audit
- ✅ Suivi des actions correctives
- ⚠️ Pas de modification des processus/documents

### Dashboard

**Route**: `/auditor/dashboard`

**Widgets**:

- Mes audits (planifiés, en cours, terminés)
- Constats par gravité
- NC créées suite à audits
- Programme d'audit annuel
- Prochains audits
- Statistiques d'audit

### Menus

```
📊 Dashboard Auditeur
🔍 Mes Audits
   ├─ Audits planifiés
   ├─ Audits en cours
   ├─ Audits terminés
   └─ Créer un audit
📋 Programme d'Audit
📝 Constats d'Audit
   ├─ Saisir un constat
   ├─ Conformités
   ├─ Non-conformités
   └─ Opportunités d'amélioration
⚠️ NC Issues d'Audits
✅ Suivi des Actions
📚 Référentiels
   ├─ Processus (lecture)
   ├─ Documents (lecture)
   └─ Exigences ISO
📊 Statistiques Audits
⚙️ Paramètres
```

---

## 7. EMPLOYEE (Collaborateur)

### Description

Collaborateur standard du site. Accès limité aux fonctionnalités qui le concernent.

### Permissions

- ✅ Consultation des processus de son site
- ✅ Consultation des documents
- ✅ Saisie de ses formations
- ✅ Consultation de ses actions assignées
- ✅ Mise à jour du statut de ses actions
- ✅ Signalement de NC
- ✅ Consultation des communications
- ⚠️ Pas de validation/suppression

### Dashboard

**Route**: `/employee/dashboard`

**Widgets**:

- Mes actions en cours
- Mes formations
- Documents récents
- Communications importantes
- Mes habilitations
- Alertes me concernant

### Menus

```
📊 Mon Dashboard
✅ Mes Actions
   ├─ Actions en cours
   ├─ Actions terminées
   └─ Historique
📚 Mes Formations
   ├─ Formations suivies
   ├─ Formations planifiées
   └─ Mes certificats
📁 Documents
   ├─ Documents qualité
   ├─ Procédures
   └─ Instructions
📋 Processus (lecture)
⚠️ Signaler une NC
💬 Communications
📊 Mes Indicateurs
👤 Mon Profil
   ├─ Informations personnelles
   ├─ Mes compétences
   └─ Mes habilitations
⚙️ Paramètres
```

---

## 8. VIEWER (Lecteur)

### Description

Accès en lecture seule. Utilisé pour les parties intéressées externes, stagiaires, etc.

### Permissions

- ✅ Consultation uniquement
- ❌ Aucune modification
- ❌ Aucune création

### Dashboard

**Route**: `/viewer/dashboard`

**Widgets**:

- Vue d'ensemble du site
- Processus (lecture)
- Documents publics
- Indicateurs publics

### Menus

```
📊 Dashboard
📋 Processus (lecture)
📁 Documents (lecture)
📊 Indicateurs (lecture)
```

---

## MATRICE DES PERMISSIONS

| Fonctionnalité               | Super Admin | Enterprise Admin | Site Manager  | Quality Manager | Process Owner      | Auditor | Employee     | Viewer |
| ---------------------------- | ----------- | ---------------- | ------------- | --------------- | ------------------ | ------- | ------------ | ------ |
| **Sites**                    |
| Créer site                   | ✅          | ✅               | ❌            | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Modifier site                | ✅          | ✅               | ✅ (son site) | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Supprimer site               | ✅          | ✅               | ❌            | ❌              | ❌                 | ❌      | ❌           | ❌     |
| **Utilisateurs**             |
| Créer utilisateur            | ✅          | ✅               | ✅ (son site) | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Modifier utilisateur         | ✅          | ✅               | ✅ (son site) | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Attribuer rôles              | ✅          | ✅               | ✅ (son site) | ❌              | ❌                 | ❌      | ❌           | ❌     |
| **Processus**                |
| Créer processus              | ✅          | ✅               | ✅            | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Modifier processus           | ✅          | ✅               | ✅            | ❌              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Consulter processus          | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅           | ✅     |
| Gérer activités              | ✅          | ✅               | ✅            | ❌              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Définir fournisseurs/clients | ✅          | ✅               | ✅            | ❌              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| **Documents**                |
| Créer document               | ✅          | ✅               | ✅            | ✅              | ✅                 | ❌      | ❌           | ❌     |
| Modifier document            | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Valider document             | ✅          | ✅               | ✅            | ✅              | ❌                 | ❌      | ❌           | ❌     |
| Consulter document           | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅           | ✅     |
| **Audits**                   |
| Créer audit                  | ✅          | ✅               | ✅            | ✅              | ❌                 | ✅      | ❌           | ❌     |
| Réaliser audit               | ✅          | ✅               | ✅            | ✅              | ❌                 | ✅      | ❌           | ❌     |
| Consulter audit              | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅           | ✅     |
| **NC & Actions**             |
| Créer NC                     | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅           | ❌     |
| Traiter NC                   | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Créer action                 | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ❌           | ❌     |
| Mettre à jour action         | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅ (assigné) | ❌     |
| **Risques**                  |
| Créer risque                 | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Traiter risque               | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| **Objectifs**                |
| Créer objectif               | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| Saisir réalisation           | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |
| **Formations**               |
| Créer formation              | ✅          | ✅               | ✅            | ✅              | ❌                 | ❌      | ❌           | ❌     |
| Inscrire participants        | ✅          | ✅               | ✅            | ✅              | ❌                 | ❌      | ❌           | ❌     |
| Consulter formations         | ✅          | ✅               | ✅            | ✅              | ✅                 | ✅      | ✅           | ✅     |
| **Rapports**                 |
| Rapports consolidés          | ✅          | ✅               | ❌            | ❌              | ❌                 | ❌      | ❌           | ❌     |
| Rapports site                | ✅          | ✅               | ✅            | ✅              | ❌                 | ❌      | ❌           | ❌     |
| Rapports processus           | ✅          | ✅               | ✅            | ✅              | ✅ (ses processus) | ❌      | ❌           | ❌     |

---

## STRUCTURE FRONTEND PROPOSÉE

```
frontend/src/modules/clienta/
├── pages/
│   ├── admin/                    # Super Admin
│   │   ├── dashboard.vue
│   │   ├── enterprises/
│   │   ├── offers/
│   │   └── system-config/
│   │
│   ├── company/                  # Enterprise Admin (ACTUEL)
│   │   ├── dashboard.vue
│   │   ├── sites/
│   │   ├── collaborators/
│   │   ├── reports/
│   │   └── subscriptions/
│   │
│   ├── site/                     # Site Manager (NOUVEAU)
│   │   ├── dashboard.vue
│   │   ├── team/
│   │   ├── processes/
│   │   ├── iso/
│   │   ├── audits/
│   │   ├── nonconformities/
│   │   ├── actions/
│   │   └── reports/
│   │
│   ├── quality/                  # Quality Manager (NOUVEAU)
│   │   ├── dashboard.vue
│   │   ├── audits/
│   │   ├── nonconformities/
│   │   ├── actions/
│   │   ├── risks/
│   │   ├── indicators/
│   │   ├── documents/
│   │   └── complaints/
│   │
│   ├── process/                  # Process Owner (NOUVEAU)
│   │   ├── dashboard.vue
│   │   ├── my-processes/
│   │   ├── activities/
│   │   ├── indicators/
│   │   ├── risks/
│   │   ├── actions/
│   │   └── resources/
│   │
│   ├── auditor/                  # Auditor (NOUVEAU)
│   │   ├── dashboard.vue
│   │   ├── my-audits/
│   │   ├── audit-program/
│   │   ├── findings/
│   │   └── statistics/
│   │
│   ├── employee/                 # Employee (NOUVEAU)
│   │   ├── dashboard.vue
│   │   ├── my-actions/
│   │   ├── my-trainings/
│   │   ├── documents/
│   │   └── profile/
│   │
│   └── viewer/                   # Viewer (NOUVEAU)
│       ├── dashboard.vue
│       ├── processes/
│       └── documents/
│
├── components/
│   ├── layouts/
│   │   ├── AdminLayout.vue       # Super Admin
│   │   ├── ClientALayout.vue     # Enterprise Admin (ACTUEL)
│   │   ├── SiteLayout.vue        # Site Manager (NOUVEAU)
│   │   ├── QualityLayout.vue     # Quality Manager (NOUVEAU)
│   │   ├── ProcessLayout.vue     # Process Owner (NOUVEAU)
│   │   ├── AuditorLayout.vue     # Auditor (NOUVEAU)
│   │   ├── EmployeeLayout.vue    # Employee (NOUVEAU)
│   │   └── ViewerLayout.vue      # Viewer (NOUVEAU)
│   │
│   └── [autres composants existants]
│
└── router/
    └── index.ts                  # Routes avec guards par rôle
```

---

## NAVIGATION AUTOMATIQUE PAR RÔLE

Le système doit rediriger automatiquement l'utilisateur vers son dashboard selon son rôle :

```typescript
// router/index.ts
const roleRoutes = {
  super_admin: "/admin/dashboard",
  enterprise_admin: "/company/dashboard",
  site_manager: "/site/dashboard",
  quality_manager: "/quality/dashboard",
  process_owner: "/process/dashboard",
  auditor: "/auditor/dashboard",
  employee: "/employee/dashboard",
  viewer: "/viewer/dashboard",
};
```

---

## PROCHAINES ÉTAPES

1. ✅ Schéma SQL mis à jour (avec site_id dans users, processus fournisseurs/clients)
2. 📝 Documentation des rôles (ce document)
3. 🔨 Créer les layouts pour chaque rôle
4. 🔨 Créer les dashboards pour chaque rôle
5. 🔨 Implémenter les guards de routes
6. 🔨 Adapter les composants existants pour multi-rôles
7. 🔨 Créer les pages spécifiques par rôle
