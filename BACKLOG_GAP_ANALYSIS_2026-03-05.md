# Backlog - Etat au 2026-03-05

## Lot traite dans ce commit

- Ajout action backend `POST /api/v1/management-reviews/{id}/close`.
- Cloture revue avec generation automatique d'un rapport DOCX et stockage du chemin (`report_path`).
- Ajout traceabilite de generation dans `m12_d2_traceability`.
- Ajout bouton frontend `Cloturer revue` sur le detail revue.
- Affichage frontend: rapport auto genere apres cloture revue.
- Ajout bouton frontend `Cloturer l'audit` sur le detail audit.
- Affichage frontend: rapport auto genere apres cloture audit.
- Ajout endpoint backend `GET /api/v1/audits/{id}/findings`.
- Alignement frontend audits sur le endpoint backend `POST /audits/{id}/complete`.

## Elements confirmes comme deja en place

- Ecran `Audit interne` (navigation + pages associees).
- Page de suivi des audits (liste minimale).
- Page detail/mise a jour d'un audit.
- Upload rapport d'audit externe (API + UI).
- Coexistence rapport auto/externe (source/version) cote audit.
- Ecran `Revue de direction`.
- Page de suivi des revues (liste minimale).
- Page detail/mise a jour d'une revue.
- Planification revue (date + participants).
- Alerte revue a venir.
- Ecran `Non-conformites et actions correctives`.
- Page de suivi des non-conformites (liste minimale).
- Page detail/mise a jour d'une non-conformite.
- Formulaire `Nouvelle non-conformite`.
- Ecran `Amelioration continue`.
- Suivi des suggestions d'amelioration (liste/detail/update).
- Espace collaborateur pour proposer des suggestions.
- Vues dediees presentes: `M13-D3/4/6`, ``, `M9-D5`, `M12-D2`, `M12-D3`, `M7-D4`.
- Tests UI presents:
  - `audit-report-upload-critical.cy.ts`
  - `audit-closure-auto-report-critical.cy.ts`
  - `review-closure-auto-report-critical.cy.ts`
- Backend presents: modules `Audit`, `ManagementReview`, `NonConformity`, `ImprovementSuggestion`, `SatisfactionSurvey`.

## Elements partiels / a valider fonctionnellement

- Formulaire `Procedure d'audit interne`, `Plan d'audit`, `Programme d'audit` (pages presentes, profondeur metier variable).
- Formulaire `Procedure de gestion des revues` (template/structure presentes, validation metier a confirmer).
- Homogeneisation des messages d'erreur (utilitaire commun present, couverture complete a verifier).
- Gestion des actions liees aux NC (liaison modele presente, UX detaillee et preuves a verifier).
- APIs `Satisfactions` et `Performances`: base presente, alignement exact sur le besoin metier a confirmer (types client/personnel/prestataire distincts).
- Entite/CRUD `Procedure d'evaluation` (vue presente, entite backend dediee a confirmer).
- API d'agregation des indicateurs (endpoints dashboard existants, cadrage exact a confirmer).

## Restant prioritaire propose (ordre d'execution)

1. Stabiliser contrats API Front/Back Audit/Review (schemas de reponse, statuts, endpoints historiques).
2. Fermer les gaps metier sur `Procedure d'evaluation` (entite + CRUD + archivage).
3. Completer `Satisfactions`/`Performances` en modeles explicites par type si requis par le besoin.
4. Finaliser workflow NC lie aux actions (preuves + regles de cloture exposees clairement en UI).
5. Uniformiser erreurs backend/frontend (format unique `error.code/message/details` partout).
6. Renforcer tests UI E2E pour valider de bout en bout les actions de cloture (pas seulement presence UI).
