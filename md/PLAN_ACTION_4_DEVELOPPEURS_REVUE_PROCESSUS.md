# Plan d'Action IAM/RBAC (Index Opérationnel)

Ce fichier devient un index de pilotage propre.
Le contenu historique conversationnel a été externalisé pour éviter la duplication et améliorer la maintenabilité.

## Documents de référence
- Plan directeur: `md/ACCESS_PLAN_DIRECTEUR.md`
- Journal d'exécution: `md/ACCESS_EXECUTION_JOURNAL.md`
- Decision log: `md/ACCESS_DECISION_LOG.md`
- Statut hebdo: `md/ACCESS_STATUT_HEBDO.md`

## État courant (résumé)
- Option B validée et implémentée (gestion rôles custom par admin entreprise + super admin, avec garde scope stricte).
- Bypass front "admin = tout" supprimé sur guards de permissions explicites.
- Enforcement du scope actif prêt pour rollout progressif par cohorte.
- Gouvernance des rôles custom recentrée sur `roles.enterprise_id`.

## Reste à finaliser
- Exécution complète des tests backend en environnement PostgreSQL disponible.
- Finalisation matrice fine norme -> sous-modules (`restrictedMatrix`).
- Extinction complète des branches legacy résiduelles.
