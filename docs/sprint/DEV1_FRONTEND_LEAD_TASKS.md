# 👨‍💻 DEV 1 - FRONTEND LEAD - Tâches Sprint 5 Jours

**Rôle:** Frontend Lead (Focus UI/UX)  
**Sprint:** 10-14 février 2026  
**Charge:** 40 heures (8h/jour × 5 jours)

---

## 🎯 OBJECTIFS PERSONNELS

- ✅ Créer **6+ composants UI réutilisables** (radix-vue)
- ✅ Développer **2 pages complètes** (SWOT + PESTEL)
- ✅ **Tests E2E** Cypress fonctionnels
- ✅ **UI/UX moderne** inspirée du design Logi

---

## 📅 PLANNING DÉTAILLÉ

### JOUR 1 (Lundi) - COMPOSANTS UI BASE

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-10h30  Installer packages npm + Setup Tailwind
10h30-11h30 Créer Dialog.vue (radix-vue)
11h30-13h00 Créer Input.vue + Select.vue
```

**Après-midi (4h):**
```
14h00-15h30 Créer Tabs.vue
15h30-16h30 Améliorer Button.vue
16h30-17h30 Améliorer Card.vue
17h30-18h00 Tests unitaires composants
```

**Livrables J1:** 6 composants UI testés ✅

---

### JOUR 2 (Mardi) - COMPOSANTS MÉTIER

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-10h30  IssueCard.vue (carte enjeu)
10h30-11h30 IssueForm.vue (formulaire création)
11h30-13h00 CategoryBadge.vue + tests
```

**Après-midi (4h):**
```
14h00-15h00 Améliorer DataTable.vue
15h00-16h00 EmptyState.vue
16h00-17h00 LoadingSpinner.vue
17h00-18h00 Préparer layout pages Context
```

**Livrables J2:** Composants métier prêts ✅

---

### JOUR 3 (Mercredi) - PAGE SWOT

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  SWOTMatrix.vue (grid 2×2)
11h00-13h00 SWOTQuadrant.vue + CSS Tailwind
```

**Après-midi (4h):**
```
14h00-15h30 SWOT.vue page complète
15h30-17h00 Intégration SWOTMatrix + IssueCard
17h00-18h00 Dialog ajout enjeu + filtres
```

**Livrables J3:** Page SWOT interactive ✅

**🎯 DEMO mi-sprint 15h:** Montrer SWOT fonctionnel

---

### JOUR 4 (Jeudi) - PAGE PESTEL

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  PESTELGrid.vue (6 colonnes)
11h00-13h00 PESTELColumn.vue + Responsive
```

**Après-midi (4h):**
```
14h00-15h30 PESTEL.vue page complète
15h30-17h00 Formulaire + filtres PESTEL
17h00-18h00 Tests UI + corrections bugs
```

**Livrables J4:** Page PESTEL complète ✅

---

### JOUR 5 (Vendredi) - TESTS & DÉMO

**Matin (4h):**
```
9h00-9h15   Daily stand-up
9h15-11h00  Tests Cypress E2E (tous scénarios)
11h00-12h00 Fixer bugs détectés
12h00-13h00 Polish CSS + animations
```

**Après-midi (4h):**
```
14h00-15h00 Accessibilité (WCAG AA)
15h00-16h00 Documentation + screenshots
16h00-17h00 Build production
17h00-18h00 🎉 DÉMO FINALE
```

**Livrables J5:** Frontend testé et livré ✅

---

## 📋 CHECKLIST TÂCHES

### Composants UI Base (Jour 1)
- [ ] `/components/ui/Dialog.vue` - Modal réutilisable
- [ ] `/components/ui/Input.vue` - Champ texte styled
- [ ] `/components/ui/Select.vue` - Dropdown
- [ ] `/components/ui/Tabs.vue` - Onglets
- [ ] `/components/ui/Button.vue` - Bouton amélioré
- [ ] `/components/ui/Card.vue` - Carte améliorée

### Composants Métier (Jour 2)
- [ ] `/components/context/IssueCard.vue` - Carte enjeu
- [ ] `/components/context/IssueForm.vue` - Formulaire
- [ ] `/components/context/CategoryBadge.vue` - Badge catégorie
- [ ] `/components/shared/DataTable.vue` - Table réutilisable
- [ ] `/components/shared/EmptyState.vue` - État vide
- [ ] `/components/shared/LoadingSpinner.vue` - Spinner

### Page SWOT (Jour 3)
- [ ] `/components/context/SWOTMatrix.vue` - Matrice 2×2
- [ ] `/components/context/SWOTQuadrant.vue` - Un quadrant
- [ ] `/pages/context/SWOT.vue` - Page complète
- [ ] Intégration Dialog + Form
- [ ] Filtres par catégorie
- [ ] CRUD fonctionnel

### Page PESTEL (Jour 4)
- [ ] `/components/context/PESTELGrid.vue` - Grid 6 colonnes
- [ ] `/components/context/PESTELColumn.vue` - Une colonne
- [ ] `/pages/context/PESTEL.vue` - Page complète
- [ ] Responsive mobile/tablet
- [ ] Formulaire + filtres
- [ ] Tests UI

### Tests & Démo (Jour 5)
- [ ] Tests Cypress E2E complets
- [ ] Fixer bugs
- [ ] Polish CSS/animations
- [ ] Accessibilité WCAG AA
- [ ] Documentation
- [ ] Build production
- [ ] Démo validée ✅

---

## 🛠️ COMMANDES UTILES

### Setup initial
```bash
cd frontend
npm install @headlessui/vue @vueuse/core @vueuse/motion vee-validate yup
npm run dev
```

### Lancer tests
```bash
npm run test              # Vitest (unitaires)
npm run test:e2e         # Cypress (E2E)
npm run test:coverage    # Coverage
```

### Build production
```bash
npm run build
npm run preview
```

---

## 📚 RESSOURCES TECHNIQUES

### Documentation
- radix-vue: https://www.radix-vue.com/
- Tailwind CSS: https://tailwindcss.com/docs
- Vee-Validate: https://vee-validate.logaretm.com/v4/
- Cypress: https://docs.cypress.io/

### Exemples de code
Voir: `/Logi/SaaS QHSE Platform Design/src/components/`

### Design system
- Couleurs: Voir `tailwind.config.js`
- Spacing: Tailwind par défaut
- Typography: Roboto (déjà installé)

---

## 🤝 COORDINATION AVEC L'ÉQUIPE

### Dépendances
**DEV 2 (Backend):**
- ⏳ Attendre API `/contexts` pour tester SWOT.vue (J3)
- ⏳ Attendre API `/issues` pour CRUD (J3)

**DEV 3 (Full-Stack):**
- ⏳ Attendre stores Pinia pour connexion (J2)
- 🤝 Collaboration sur intégration (J3-J4)

### Communication
- **Slack:** Questions en continu
- **Daily:** Partager avancement chaque matin
- **Demo mi-sprint:** Mercredi 15h
- **Review code:** Demander review avant merge

---

## ✅ DEFINITION OF DONE (DoD)

Un composant/page est "Done" quand:
- [ ] Code TypeScript strict (pas de `any`)
- [ ] Responsive (mobile/tablet/desktop)
- [ ] Accessible (aria-labels, keyboard nav)
- [ ] Tailwind CSS uniquement (pas de CSS custom)
- [ ] Tests unitaires (si complexe)
- [ ] Documentation JSDoc (si réutilisable)
- [ ] Code review par DEV 3
- [ ] Merged dans `develop`

---

## 🎯 KPIs PERSONNELS

| Métrique | Objectif | Suivi |
|----------|----------|-------|
| Composants créés | 12+ | __ |
| Pages complètes | 2 | __ |
| Tests E2E | 5+ scénarios | __ |
| Bugs trouvés | < 5 critiques | __ |
| Score Lighthouse | > 90 | __ |
| Accessibilité | WCAG AA ✅ | __ |

---

**Bon sprint ! 🚀**

