# Point d'avancement - 2026-03-25

## Contexte
Objectif global: ameliorer 3 modules SMQ en sequence, sans casser l'existant, avec mutualisation des briques communes.

## Etat global
- Module 1 (Evaluations de performance): avancee forte, flux principal operationnel cote backend + frontend.
- Module 2 (Audits internes): backend deja bien avance (complements, norm inputs, synthese, rappels), frontend encore partiel.
- Module 3 (Revue de direction): non demarre de maniere structuree dans cette sequence.

## Detail par module

### 1) Evaluations de performance
Statut: Presque termine (MVP fonctionnel)

Fait:
- CRUD criteres dynamiques + initialisation de criteres par defaut.
- Page de gestion des criteres.
- Demandes d'evaluation: creation, envoi, relance, annulation, stats, lien public.
- Formulaire public par token pour soumission des reponses.
- Adaptateur frontend/backend sur les demandes pour aligner les contrats API.
- Boutons "Definir criteres" ajoutes sur plusieurs ecrans performance.

A verifier/finir:
- Validation E2E du flux complet (create -> send -> open -> submit -> stats).
- Nettoyage de diagnostics TypeScript/Vue residuels dans le projet (bruit global).

### 2) Audits internes
Statut: En cours (backend avance, integration frontend a consolider)

Fait:
- Extension du modele audit (champs complements, rappels, synthese).
- Nouvelles routes API:
  - PUT /audits/{id}/complements
  - PUT /audits/{id}/norm-inputs
  - GET /audits/{id}/synthesis
  - POST /audits/{id}/reminders
- Modeles/metiers ajoutes: AuditReminder, AuditNormInput.
- Job de rappels planifie (SendAuditReminders) + notification associee.

A verifier/finir:
- Ecran frontend de saisie/edition des complements d'audit.
- Ecran frontend de configuration des rappels par audit.
- Vue frontend de synthese SM exploitable.
- Cohesion schema/colonnes migrations vs usages backend (a valider en test d'integration).

### 3) Revue de direction
Statut: A planifier

Reste a faire:
- Structuration des champs normatifs d'entree (dont focus points demandes).
- Aggregation/synthese multi-sources.
- Export PDF/Word via module de reporting mutualisable.

## Mutualisation (DRY) - direction retenue
- Moteur de criteres dynamiques reutilisable (plusieurs formulaires).
- Brique de planification/rappels reutilisable (audits puis revue de direction).
- Base de reporting/synthese reutilisable pour exports.

## Risques/points de vigilance
- Repo avec beaucoup de changements en parallele: risque de derive de contrat API.
- Certaines modifications "1-5" -> "1-3" semblent transverses: impact potentiel hors perimetre SMQ a verifier.
- Validation globale frontend bruyante: privilegier des checks cibles + E2E des parcours critiques.

## Prochaine etape recommandee (immediate)
1. Finaliser Module 2 cote frontend (complements + rappels + synthese) en conservant les endpoints deja poses.
2. Lancer tests cibles backend/frontend sur ce flux.
3. Ouvrir ensuite Module 3 avec un design de reporting commun.

## References utiles
- Changelog technique en cours: CHANGELOG_CRITERES_EVALUATION.md
- Ce document: POINT_AVANCEMENT_2026-03-25.md
