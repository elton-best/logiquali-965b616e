# 🎯 ROADMAP - 4 MODULES PRIORITAIRES ISO 9001

**Date:** 9 février 2026  
**Équipe:** 3 développeurs full-stack  
**Contrainte:** Livraison par ordre de priorité

---

## 📋 MODULES PRIORITAIRES

Selon ISO 9001:2015, les 4 premiers points sont :

| #     | Module                  | Sous-modules clés                           | Tables BDD | Priorité        |
| ----- | ----------------------- | ------------------------------------------- | ---------- | --------------- |
| **4** | Contexte de l'organisme | SWOT, PESTEL, parties intéressées           | 4 tables   | ⭐⭐⭐ CRITIQUE |
| **5** | Leadership              | Engagement, Politique QHSE, Responsabilités | 3 tables   | ⭐⭐⭐ HAUTE    |
| **6** | Planification           | Risques/Opportunités, Objectifs qualité     | 5 tables   | ⭐⭐ MOYENNE    |
| **7** | Support                 | Ressources, Compétences, Documentation      | 6 tables   | ⭐⭐ MOYENNE    |

**Total:** 18 nouvelles tables + enrichissement de 8 tables existantes

---

## 🗓️ STRATÉGIE DE LIVRAISON

### Option A : Sprint unique 5 jours (MVP minimal)

**Scope réduit:** Point 4 uniquement (plan actuel)

- ✅ SWOT/PESTEL complets
- ✅ parties intéressées basiques
- ❌ Points 5, 6, 7 reportés

**Avantages:**

- Livraison rapide et focalisée
- Qualité maximale sur Point 4
- Demo convaincante

**Inconvénients:**

- Seulement 25% des modules prioritaires livrés
- Besoin de 3 sprints supplémentaires

---

### Option B : Sprints successifs 4×5 jours (Recommandé)

**Scope complet:** 4 modules en 4 sprints

```
Sprint 1 (10-14 fév) → Point 4 : Contexte
Sprint 2 (17-21 fév) → Point 5 : Leadership
Sprint 3 (24-28 fév) → Point 6 : Planification
Sprint 4 (03-07 mar) → Point 7 : Support
```

**Total:** 20 jours (4 semaines) - 480 heures
**Livraison:** 4 modules complets ISO 9001

---

### Option C : Sprint étendu 10 jours (Compromis)

**Scope:** Points 4 + 5 (les 2 plus critiques)

```
Semaine 1 (10-14 fév) → Point 4 : Contexte (plan actuel)
Semaine 2 (17-21 fév) → Point 5 : Leadership + intégration
```

**Avantages:**

- 50% des modules prioritaires en 2 semaines
- Points critiques couverts
- Démo plus impressionnante

---

## 📊 COMPARAISON DES OPTIONS

| Critère        | Option A<br>(5j) | Option B<br>(20j) | Option C<br>(10j) |
| -------------- | ---------------- | ----------------- | ----------------- |
| Modules livrés | 1/4 (25%)        | 4/4 (100%)        | 2/4 (50%)         |
| Charge totale  | 120h             | 480h              | 240h              |
| Qualité/module | ⭐⭐⭐⭐⭐       | ⭐⭐⭐⭐          | ⭐⭐⭐⭐⭐        |
| Risque         | 🟢 Faible        | 🟡 Moyen          | 🟢 Faible         |
| ROI rapide     | 🟢 Oui           | 🔴 Non            | 🟡 Partiel        |
| Valeur métier  | ⭐⭐             | ⭐⭐⭐⭐⭐        | ⭐⭐⭐⭐          |

---

## 🎯 RECOMMANDATION

### ✅ OPTION B - Sprints successifs 4×5 jours

**Justification:**

1. **Couverture complète** des modules prioritaires
2. **Qualité constante** avec sprints courts
3. **Démos régulières** (1/semaine) pour validation
4. **Flexibilité** : possibilité d'ajuster entre sprints
5. **Équipe motivée** : objectifs clairs par sprint

**Planning proposé:**

```
┌─────────────────────────────────────────────────────────────────┐
│                    ROADMAP 4 SEMAINES                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  SPRINT 1 (10-14 fév) : POINT 4 - CONTEXTE                    │
│  ├─ SWOT/PESTEL (matrices interactives)                       │
│  ├─ parties intéressées                                          │
│  ├─ Domaine d'application                                      │
│  └─ Demo vendredi 14/02 17h ✅                                 │
│                                                                 │
│  SPRINT 2 (17-21 fév) : POINT 5 - LEADERSHIP                  │
│  ├─ Politique QHSE                                             │
│  ├─ Organigramme responsabilités                               │
│  ├─ Engagement direction                                       │
│  └─ Demo vendredi 21/02 17h ✅                                 │
│                                                                 │
│  SPRINT 3 (24-28 fév) : POINT 6 - PLANIFICATION               │
│  ├─ Gestion risques/opportunités                              │
│  ├─ Objectifs QHSE                                             │
│  ├─ Planification actions                                      │
│  └─ Demo vendredi 28/02 17h ✅                                 │
│                                                                 │
│  SPRINT 4 (03-07 mar) : POINT 7 - SUPPORT                     │
│  ├─ Gestion ressources                                         │
│  ├─ Compétences & formations                                   │
│  ├─ Documentation intégrée                                     │
│  └─ Demo finale vendredi 07/03 17h 🎉                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 DÉTAILS PAR SPRINT

### SPRINT 1 : POINT 4 - CONTEXTE (10-14 février)

**Status:** ✅ Planifié (documents existants)

**Livrables:**

- Pages SWOT & PESTEL complètes
- CRUD parties intéressées
- Domaine d'application
- 3 migrations + 3 modèles + 10 endpoints API

**Charge:** 120h (plan actuel valide)

---

### SPRINT 2 : POINT 5 - LEADERSHIP (17-21 février)

**Tables BDD:**

```sql
qhse_policies          -- Politiques QHSE
organizational_chart   -- Organigramme
responsibilities       -- Rôles & responsabilités
```

**Pages frontend:**

- `/leadership/policy` - Gestion politique QHSE
- `/leadership/chart` - Organigramme interactif
- `/leadership/responsibilities` - Matrice RACI

**Composants UI:**

- `PolicyEditor.vue` (éditeur riche)
- `OrgChart.vue` (diagramme hiérarchique)
- `RACIMatrix.vue` (matrice responsabilités)

**API endpoints:**

```
GET/POST   /api/client-a/policies
GET/POST   /api/client-a/org-chart
GET/POST   /api/client-a/responsibilities
```

**Charge estimée:** 120h

- DEV 1: 40h (3 pages + composants)
- DEV 2: 40h (migrations + API)
- DEV 3: 40h (intégration + tests)

---

### SPRINT 3 : POINT 6 - PLANIFICATION (24-28 février)

**Tables BDD:**

```sql
risks                  -- Risques (enrichissement table existante)
opportunities          -- Opportunités
quality_objectives     -- Objectifs qualité
action_plans           -- Plans d'action
```

**Pages frontend:**

- `/planning/risks` - Registre risques/opportunités
- `/planning/objectives` - Objectifs QHSE SMART
- `/planning/actions` - Plans d'action

**Composants UI:**

- `RiskMatrix.vue` (matrice gravité×probabilité)
- `ObjectiveCard.vue` (carte objectif SMART)
- `ActionPlan.vue` (Gantt simplifié)

**API endpoints:**

```
GET/POST   /api/client-a/risks
GET/POST   /api/client-a/opportunities
GET/POST   /api/client-a/objectives
GET/POST   /api/client-a/action-plans
```

**Charge estimée:** 120h

- DEV 1: 40h (pages + RiskMatrix complexe)
- DEV 2: 40h (migrations + API + calculs)
- DEV 3: 40h (intégration + tests)

---

### SPRINT 4 : POINT 7 - SUPPORT (03-07 mars)

**Tables BDD:**

```sql
resources              -- Ressources (humaines, matérielles)
competencies           -- Compétences requises
training_plans         -- Plans formation (enrichissement)
documents              -- Documentation (enrichissement)
equipment              -- Équipements
```

**Pages frontend:**

- `/support/resources` - Gestion ressources
- `/support/competencies` - Matrice compétences
- `/support/training` - Plans de formation
- `/support/equipment` - Gestion équipements

**Composants UI:**

- `ResourceCard.vue`
- `CompetencyMatrix.vue` (heatmap compétences)
- `TrainingPlan.vue`
- `EquipmentList.vue`

**API endpoints:**

```
GET/POST   /api/client-a/resources
GET/POST   /api/client-a/competencies
GET/POST   /api/client-a/training-plans
GET/POST   /api/client-a/equipment
```

**Charge estimée:** 120h

- DEV 1: 40h (4 pages + matrice complexe)
- DEV 2: 40h (migrations + API)
- DEV 3: 40h (intégration + tests)

---

## 📊 SYNTHÈSE GLOBALE 4 SPRINTS

### Livrables totaux

| Type            | Quantité      |
| --------------- | ------------- |
| Tables BDD      | 18 nouvelles  |
| Modèles Laravel | 18            |
| Pages Vue.js    | 12            |
| Composants UI   | 30+           |
| Endpoints API   | 40+           |
| Tests E2E       | 20+ scénarios |

### Charge totale

- **Durée:** 4 semaines (20 jours)
- **Charge:** 480 heures
- **Coût estimé:** 36k€ (à 75€/h)

---

## 🚦 RISQUES & MITIGATION

| Risque                                 | Impact    | Mitigation                     |
| -------------------------------------- | --------- | ------------------------------ |
| Fatigue équipe (4 sprints consécutifs) | 🟡 Moyen  | Pause 2-3 jours entre sprints  |
| Complexité croissante                  | 🟡 Moyen  | Réutiliser composants Sprint 1 |
| Drift du scope                         | 🔴 Élevé  | MVP strict par sprint          |
| Dépendances inter-modules              | 🟢 Faible | Modules ISO indépendants       |

---

## ✅ DÉCISION À PRENDRE

**Question pour le Product Owner:**

> Quelle option préférez-vous pour livrer les 4 modules prioritaires ?

**A. Sprint unique 5 jours** (Point 4 seulement)

- ✅ Rapide et focalisé
- ❌ Seulement 25% de couverture

**B. 4 sprints successifs** (Points 4, 5, 6, 7)

- ✅ Couverture complète des priorités
- ✅ 4 démos régulières
- ⚠️ 4 semaines nécessaires

**C. Sprint étendu 10 jours** (Points 4 + 5)

- ✅ 50% de couverture
- ✅ Points critiques couverts
- ⚠️ 2 semaines nécessaires

---

## 🎯 ACTION IMMÉDIATE

**Si Option B validée (Recommandé):**

1. ✅ Garder Sprint 1 tel quel (10-14 fév)
2. 📝 Planifier Sprint 2 en détail cette semaine
3. 📅 Bloquer 4 semaines agenda équipe
4. 💰 Valider budget 36k€

**Si Option A (plan actuel):**

1. ✅ Exécuter Sprint 1 (10-14 fév)
2. ⏸️ Pause & réévaluation après démo
3. 🔄 Planifier Points 5-7 ultérieurement

**Si Option C:**

1. ✅ Exécuter Sprint 1 (10-14 fév)
2. 📝 Planifier Sprint 1.5 (Point 5) immédiatement après
3. 🎯 Livraison intermédiaire 21 février

---

**Créé le:** 9 février 2026  
**Version:** 1.0  
**Status:** En attente validation stratégique
