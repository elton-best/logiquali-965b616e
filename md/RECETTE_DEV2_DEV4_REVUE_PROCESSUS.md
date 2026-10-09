# Recette Anti-Regression - Dev2 + Dev4 (Revue Processus)

## 1) Pre-requis
- Backend a jour:
  - `php artisan migrate`
  - `php artisan db:seed --class=CommonQHSECatalogSeeder`
  - `php artisan db:seed --class=AllISOStandardsSeeder`
  - `php artisan db:seed --class=NormativeCatalogMatrixSeeder`
  - `php artisan db:seed --class=UnifiedPermissionsSeeder`
- Frontend:
  - `npm install`
  - `npm run dev`

## 2) Checklist fonctionnelle

### 2.1 Catalogue / navigation
- [ ] Dans le module Evaluation des performances, le sous-module est affiche en **Evaluations PIP**.
- [ ] Le sous-module **Revue Processus** est affiche entre Evaluations PIP et Audits internes.
- [ ] Les routes alias legacy redirigent vers les routes canoniques (consultation, duerp, process-review).

### 2.2 Revue Processus - UX (Dev2)
- [ ] L'ecran d'entree affiche les processus par 3 familles colorees.
- [ ] Le titre dynamique affiche `Revue de : [Nom du processus]`.
- [ ] La section Identification contient: RQ, pilote/copilote, presents/autres, periode, heures auto.
- [ ] Un etat de chargement (skeleton) est visible au chargement de la fiche detail.
- [ ] Le mode `Lecture seule` est explicite quand l'API interdit la mise a jour.
- [ ] Le bouton `Enregistrer` manuel est visible uniquement en mode edition.
- [ ] Le bouton **Voir rapport** ouvre le recapitulatif.
- [ ] Le bouton **Cloturer la revue** ferme definitivement la revue.

### 2.3 Regle Management stricte
- [ ] La section **Leadership / DUERP** est visible uniquement pour un processus `Management` (titre/type/code).
- [ ] Pour les autres processus, un message informatif est affiche.

### 2.4 Securite / permissions (Dev4)
- [ ] Un utilisateur non-RQ ne peut pas modifier la section Identification (403).
- [ ] Un profil RQ/qualite peut modifier Identification.
- [ ] Un utilisateur `process_reviews.read` seul ne peut pas faire `PUT /reviews/current` (403).
- [ ] Les operations de lien d'evaluation (envoi/relance/annulation) sont journalisees.

### 2.5 Rapports
- [ ] Export PDF de revue processus fonctionnel.
- [ ] Export DOCX de revue processus fonctionnel.
- [ ] Le document exporte est synchronise dans l'inventaire documentaire.

## 3) Checklist technique
- [ ] `npx vue-tsc --noEmit` OK.
- [ ] `npx vitest run tests/unit/utils/routeCanonicalizer.test.ts` OK.
- [ ] `php artisan test --filter=ProcessReviewWorkflowTest` OK (si DB test disponible).
- [ ] `npx cypress run --spec cypress/e2e/process-review-critical.cy.ts` OK.

## 4) Scenarios critiques Cypress couverts
- `frontend/cypress/e2e/process-review-critical.cy.ts`
  - Navigation hub surveillance -> Revue Processus.
  - Affichage conditionnel strict de la section Leadership/DUERP.
  - Verrouillage complet de l'interface en mode lecture seule.
