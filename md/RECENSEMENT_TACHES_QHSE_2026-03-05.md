# Recensement des tâches QHSE (Audit / Revue / NC / Amélioration)

Date: 2026-03-05

## Fait

- Créer écran "Audit interne" (planification + suivi)
- Créer page de suivi des audits (liste minimale)
- Créer page détail/mise à jour d’un audit
- Créer écran "Revue de direction"
- Créer page de suivi des revues (liste minimale)
- Créer page détail/mise à jour d’une revue
- Ajouter planification revue (date + participants)
- Ajouter action "Clôturer revue" (via statut/workflow)
- Créer écran "Non-conformités et actions correctives"
- Créer page de suivi des non-conformités (liste minimale)
- Créer page détail/mise à jour d’une non-conformité
- Ajouter formulaire "Nouvelle non-conformité"
- Ajouter gestion des actions liées (création/suivi/statut) [socle]
- Créer écran "Amélioration continue"
- Ajouter formulaire "Fiche de suggestion d’amélioration" (UI)
- Ajouter espace collaborateur pour proposer des suggestions (UI)
- Créer page de suivi des suggestions d’amélioration (liste minimale)
- Créer page détail/mise à jour d’une suggestion
- Ajouter vues dédiées M13-D3/4/6
- Ajouter vues dédiées
- Ajouter vues dédiées M9-D5
- Ajouter vues dédiées M12-D2
- Ajouter vues dédiées M12-D3
- Ajouter vues dédiées M7-D4
- Créer module backend "Audit interne"
- Créer entité "Plan d’audit"
- Créer entité "Programme d’audit"
- Créer API de suivi minimal des audits (liste + filtres + statut)
- Créer API détail/mise à jour d’un audit
- Créer API upload rapport d’audit externe
- Gérer coexistence rapport auto + rapport externe (source/version)
- Créer module backend "Revue de direction"
- Créer entité "Revue de direction"
- Créer API détail/mise à jour d’une revue
- Créer module backend "Non-conformités et actions correctives"
- Créer entité "Non-conformité"
- Créer entité "Action corrective"
- Créer API de suivi minimal des non-conformités (liste + filtres + statut)
- Créer API détail/mise à jour d’une non-conformité
- Lier chaque non-conformité à une ou plusieurs actions de traitement [socle]
- Implémenter suivi des actions (responsable, échéance, statut, preuve) [socle]
- Ajouter règles de clôture non-conformité conditionnées par statut des actions
- Créer entité "Fiche satisfaction client"
- Créer entité "Fiche satisfaction personnel" (via satisfaction_survey type=employee)
- Créer entité "Fiche satisfaction prestataire" (via satisfaction_survey type=supplier)
- Créer API de suivi minimal "Satisfactions" (liste + filtres + statut)
- Créer API détail/mise à jour "Satisfaction"
- Créer endpoints de création "Fiches satisfaction" (client/personnel/prestataire)
- Créer entité "Fiche performance personnel"
- Créer API de suivi minimal "Performances" (liste + filtres + statut)
- Créer API détail/mise à jour "Performance"
- Créer endpoints de création "Fiches performance" (personnel/prestataire) [personnel OK, prestataire partiel]
- Ajouter champs de traçabilité M13-D3/D4/D6, /D5, M12-D2/D3, M7-D4
- Ajouter tests UI flux critique "Upload rapport d’audit"
- Ajouter tests UI flux critique "Clôture audit + rapport auto"
- Ajouter tests UI flux critique "Clôture revue + rapport auto"
- Créer backend persistant pour suggestions (CRUD + API + suivi) [socle en place]

## Partiel

- Ajouter formulaire "Procédure d’audit interne" (structure UI/Doc présente, pas de flux dédié complet)
- Ajouter formulaire "Plan d’audit" (UI légère)
- Ajouter formulaire "Programme d’audit" (UI + API, mais intégration navigation incomplète)
- Ajouter upload "Rapport d’audit" (upload externe API/UI en place, robustesse UX à compléter)
- Ajouter action "Clôturer audit" (workflow/statut présents, UX dédiée non stabilisée)
- Afficher génération automatique du rapport après clôture audit (génération existe via finalize/job)
- Ajouter formulaire "Procédure de gestion des revues" (template/générateur présent, gestion entité absente)
- Afficher alerte de revue à venir (possible via computed/store, pas matérialisé écran alerte dédié)
- Afficher génération automatique du rapport après clôture revue (export DOCX présent, auto post-clôture non explicite)
- Créer module backend "Surveillance, mesure, analyse et évaluation" [couverture fonctionnelle partielle]
- Créer entité "Procédure d’évaluation" [UI locale, pas backend CRUD dédié]
- Créer CRUD "Procédure d’évaluation" (create/read/update/archive) [manque backend]
- Créer entité "Fiche performance prestataire" [plutôt localStorage/UI]
- Créer API d’agrégation des indicateurs (surveillance/mesure/analyse/évaluation) [agrégations partielles]
- Créer entité "Procédure d’audit" [manque modèle/API dédié]
- Créer entité "Rapport d’audit" [stockage path/report, pas modèle métier autonome clair]
- Générer automatiquement le rapport à la clôture d’audit [en finalize, pas garanti sur toute clôture]
- Créer entité "Procédure de revue" [template/doc, pas entité métier CRUD]
- Générer automatiquement le rapport après clôture de revue [partiel]
- Créer module backend "Suggestions d’amélioration continue" [socle CRUD/API en place]
- Ajouter messages d’erreur homogènes (format + wording) [QHSE principal aligné, extension secondaire à poursuivre]

## Reste à faire

- Clôturer les items partiels restants (procédures métier, entités manquantes, agrégations et robustesse UX)

## Notes

- Plusieurs écrans "performance/\*" existent en version minimale alors que les pages métier complètes sont plutôt sur /company/audits, /company/management-reviews, /company/nonconformities.
- Certaines implémentations UI sont en localStorage (donc non persistées backend).
- Des incohérences de contrats API existent entre certains services front et routes backend sur les audits (à normaliser avant extension).

## Chantiers entamés (2026-03-05)

- Upload rapport d’audit externe: API ajoutée (`POST /audits/{id}/upload-external-report`) + UI bouton dans le détail audit.
- Coexistence rapport auto/externe: champs `report_source`, `report_version`, `external_report_path` ajoutés + logique backend.
- Génération automatique du rapport à la clôture audit: déclenchée dans `AuditService::complete`.
- Règle de clôture NC liée aux actions: blocage de clôture si actions associées non terminées.
- Alerte revue à venir: alerte visuelle ajoutée dans la liste des revues.
- Messages d’erreur homogènes: util frontend central `getErrorMessage` branché sur pages revues.
- Traçabilité: premiers champs backend ajoutés pour M13-D3/D4/D6, /D5, M12-D2/D3, M7-D4.
- Tests UI critiques: 3 specs Cypress ajoutées (upload rapport audit, clôture audit+rapport, clôture revue+rapport).
