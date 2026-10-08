# 📦 COMPOSANTS VUE.JS CRÉÉS - CLIENT A ISO 9001

**Date:** 9 février 2026  
**Objectif:** Templates concrets pour les 4 modules prioritaires

---

## ✅ FICHIERS CRÉÉS

### 1. Layout Principal
```
✅ /frontend/src/layouts/ClientALayout.vue (420 lignes)
   - Sidebar ISO 9001 complète (Points 4-10)
   - Points 4-7 PRIORITAIRES (affichés normalement)
   - Points 8-10 (affichés en gris, Phase 2)
   - Navigation hiérarchique avec v-list-group
   - User info en bas
   - Top bar avec breadcrumbs + search
   - Responsive (mobile/desktop)
```

### 2. Composants UI Base
```
✅ /frontend/src/components/ui/Icon.vue (100 lignes)
   - 25+ icônes SVG (plus, edit, trash, download, etc.)
   - Tailles: sm, md, lg
   - Utilisable partout

❌ /frontend/src/components/ui/Button.vue (EXISTE DÉJÀ)
   - À enrichir selon template
```

---

## 🏗️ STRUCTURE SIDEBAR ISO 9001

### Menu hiérarchique complet (Points 4-10)

```
ClientA Layout
└── Sidebar (260px)
    ├── Logo QualiBest
    ├── "ISO 9001:2015"
    │
    ├── 📊 Tableau de bord
    │
    ├── ────────────────
    │
    ├── 📋 4 - Contexte ⭐ PRIORITAIRE
    │   ├── Domaine d'application
    │   ├── SWOT / PESTEL
    │   └── Parties intéressées
    │
    ├── 👔 5 - Leadership ⭐ PRIORITAIRE
    │   ├── Politique QHSE
    │   ├── Organigramme
    │   └── Fiches de poste
    │
    ├── 📈 6 - Planification ⭐ PRIORITAIRE
    │   ├── Risques & Opportunités
    │   ├── Objectifs QHSE
    │   └── Plans d'action
    │
    ├── 🛠️ 7 - Support ⭐ PRIORITAIRE
    │   ├── Formation
    │   ├── Équipements
    │   ├── Inventaire documentaire
    │   ├── Communication
    │   └── Maintenance
    │
    ├── ────────────────
    │
    ├── ⚙️ 8 - Réalisation (Phase 2)
    │   └── Processus
    │
    ├── 📊 9 - Évaluation (Phase 2)
    │   ├── Audits
    │   ├── Indicateurs
    │   └── Satisfaction client
    │
    ├── 📈 10 - Amélioration (Phase 2)
    │   ├── Non-conformités
    │   ├── Actions correctives
    │   └── Amélioration continue
    │
    ├── ────────────────
    │
    ├── 📁 Documents
    ├── ⚙️ Paramètres
    │
    └── User Info (avatar + nom + rôle)
        └── Menu: Profil, Déconnexion
```

---

## 🎯 ROUTES À CRÉER (14 pages prioritaires)

### Sprint 1 - Point 4 (3 pages)
```
/iso/context/application-scope      → Domaine d'application
/iso/context/swot-pestel             → SWOT/PESTEL
/iso/context/stakeholders            → Parties intéressées
```

### Sprint 2 - Point 5 (3 pages)
```
/iso/leadership/policy               → Politique QHSE
/iso/leadership/org-chart            → Organigramme
/iso/leadership/job-descriptions     → Fiches de poste
```

### Sprint 3 - Point 6 (3 pages)
```
/iso/planning/risks-opportunities    → Risques & Opportunités
/iso/planning/objectives             → Objectifs QHSE
/iso/planning/action-plans           → Plans d'action
```

### Sprint 4 - Point 7 (5 pages)
```
/iso/support/training                → Formation
/iso/support/equipment               → Équipements
/iso/support/document-inventory      → Inventaire documentaire
/iso/support/communication           → Communication
/iso/support/maintenance             → Maintenance
```

---

## 📂 STRUCTURE DOSSIERS CRÉÉE

```
frontend/src/
├── layouts/
│   └── ClientALayout.vue ✅
│
├── components/
│   ├── ui/
│   │   ├── Button.vue (existe)
│   │   └── Icon.vue ✅
│   │
│   ├── forms/
│   │   └── (à créer)
│   │
│   └── iso/
│       ├── context/
│       ├── leadership/
│       ├── planning/
│       └── support/
│
├── pages/
│   └── iso/
│       ├── context/
│       ├── leadership/
│       ├── planning/
│       └── support/
│
├── stores/
│   └── iso/
│       └── (à créer)
│
└── services/
    └── api/
        └── iso/
            └── (à créer)
```

---

## 🎨 DESIGN SYSTEM (ClientALayout)

### Couleurs
```css
Sidebar background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%)
Menu item hover: rgba(255, 255, 255, 0.1)
Menu item active: rgba(59, 130, 246, 0.2) + color #3B82F6
Text normal: rgba(255, 255, 255, 0.7)
Text hover: white
Dividers: rgba(255, 255, 255, 0.1)
```

### Composants Vuetify utilisés
```
- v-navigation-drawer (sidebar)
- v-list, v-list-item, v-list-group (menu)
- v-app-bar (top bar)
- v-breadcrumbs (fil d'ariane)
- v-text-field (search)
- v-badge (notifications)
- v-menu (user menu)
- v-avatar (user avatar)
```

---

## 🚀 PROCHAINES ÉTAPES

### Immédiat (avant Sprint 1)
- [ ] Créer fichier router ISO `/router/modules/iso.ts`
- [ ] Créer 3 premières pages (Point 4)
- [ ] Créer composants forms de base
- [ ] Créer stores Pinia (contextStore, etc.)
- [ ] Créer services API

### Sprint 1 (Point 4 - Contexte)
- [ ] Page: ApplicationScope.vue
- [ ] Page: SWOTPestel.vue  
- [ ] Page: Stakeholders.vue
- [ ] Composants: SWOTMatrix, PESTELGrid, IssueCard
- [ ] Store: contextStore.ts
- [ ] Service: contextService.ts

---

## 📝 NOTES IMPORTANTES

1. **Sidebar uniforme:** Le même `ClientALayout.vue` est utilisé pour TOUTES les pages Client A
2. **Points prioritaires:** 4-7 affichés normalement, 8-10 en gris (Phase 2)
3. **Responsive:** Sidebar permanent sur desktop, drawer temporaire sur mobile
4. **Navigation:** Breadcrumbs automatiques (à implémenter avec router meta)
5. **User info:** Avatar avec initiales + menu (Profil, Déconnexion)

---

## ✅ VALIDATION

- [x] Sidebar ISO 9001 complète (Points 4-10)
- [x] Points prioritaires bien identifiés (4-7)
- [x] Layout responsive
- [x] User info en bas
- [x] Top bar avec search et breadcrumbs
- [x] Structure dossiers créée
- [x] Composant Icon.vue créé

**Status:** Layout principal prêt, composants à créer

