# Sprint 3 - Plan d'implementation (Dev 2 + Dev 4)

## Contexte
Sprint 3 cible uniquement:
1. Stabilisation UX Revue Processus (Dev 2)
2. Rapports PDF+DOCX + non-regression globale (Dev 4)

## Objectifs du sprint
1. Fiabiliser l'experience utilisateur de la Revue Processus (edition/lecture seule, feedback clair, responsive).
2. Rendre les exports PDF/DOCX metier, lisibles et coherents.
3. Verrouiller les parcours critiques par des tests cibles.

## Plan d'actions Dev 2 (Frontend UX)

### Lot D2-1 - Stabilisation de l'ecran detail Revue Processus
1. Ajouter un etat de chargement initial (skeleton).
2. Afficher un message explicite en mode lecture seule.
3. Ajouter un bouton "Enregistrer" manuel en plus de l'autosave.
4. Ajouter confirmation avant cloture definitive.

### Lot D2-2 - Cohesion visuelle
1. Uniformiser les etats d'information dans le panneau "Etat de la revue".
2. Rendre explicite le mode courant "Edition / Lecture seule".

### Critere d'acceptation Dev 2
1. Aucun blocage UX durant le parcours.
2. Un utilisateur read-only comprend immediatement qu'il ne peut pas modifier.
3. Le bouton de cloture n'est utilisable qu'en mode edition.

## Plan d'actions Dev 4 (Integration + QA + Rapports)

### Lot D4-1 - Rapports metier
1. Standardiser les libelles KPI dans PDF/DOCX.
2. Prioriser `computed_metrics` quand disponible, sinon fallback `metrics_snapshot`.
3. Normaliser l'affichage des dates de revue.

### Lot D4-2 - Non-regression
1. Ajouter scenario Cypress read-only.
2. Renforcer tests backend de permissions (reader-only ne peut pas update).
3. Mettre a jour la recette anti-regression avec les nouveaux cas.

### Critere d'acceptation Dev 4
1. Exports PDF/DOCX lisibles et metiers.
2. Tests critiques front/back disponibles et executables.
3. Aucun changement hors perimetre Dev 2/Dev 4.

## Execution proposee (3 jours)
1. Jour 1: UX stabilisation + feedback utilisateur.
2. Jour 2: Rapports PDF/DOCX + labels metier.
3. Jour 3: Tests/recette et correction des ecarts restants.

## Livrables sprint
1. Liste des fichiers modifies.
2. Resultats des tests executes.
3. Liste des risques residuels et plan de mitigation.

## Etat d'avancement (execution en cours)
1. Dev 2:
   1. Stabilisation UX detail revue (skeleton, lecture seule explicite, bouton enregistrer manuel, confirmation cloture) appliquee.
   2. Ecran d'entree revue renforce (etat loading, erreur, vide, action actualiser).
2. Dev 4:
   1. Rapports PDF/DOCX ameliores (libelles metier KPI, date normalisee, fallback metrics coherent).
   2. Tests non-regression front etendus (route canonicalizer + scenario Cypress read-only).
3. Blocages techniques:
   1. Tests backend feature dependants PostgreSQL non executables localement sans DB test active.
   2. Cypress headless instable localement (SIGILL) sur cet environnement.

## Resultats de validation executes
1. Frontend
   1. `npm run type-check`: OK
   2. `npm run test -- tests/unit/utils/routeCanonicalizer.test.ts`: OK (4 tests)
   3. `npm run build-only`: OK (build Vite production genere)
2. Backend
   1. Lint PHP fichiers Sprint 3: OK
   2. `php artisan test --env=testing --filter=ProcessReviewWorkflowTest`: bloque (PostgreSQL local indisponible)
3. E2E
   1. `npx cypress run --spec cypress/e2e/process-review-critical.cy.ts`: bloque localement (SIGILL sur runner Cypress)

## Actions externes necessaires pour cloture 100%
1. Demarrer PostgreSQL test local (cluster + base `logiquali_test`) puis relancer les feature tests backend.
2. Executer Cypress depuis une machine CI/locale stable (sans crash SIGILL) pour valider le scenario critique complet.
