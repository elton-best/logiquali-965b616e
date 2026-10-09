# Plan Directeur IAM/RBAC

## Objectif produit
Mettre en place un contrôle d'accès robuste, multi-tenant et maintenable où la décision d'accès repose sur:
- souscriptions actives (normes)
- scope entreprise/site actif
- rôle attribué (système ou custom)
- permissions effectives
- responsabilités métier (actions assignées)

## Principes directeurs
- Deny by default.
- Backend source de vérité unique.
- Distinction stricte entre visibilité, autorisation, souscription et scope.
- Rôles custom gouvernés par `roles.enterprise_id` (pas par convention de nom).
- Révocation immédiate (token invalidation + refresh state).

## Décisions structurantes actives
- Option B: création/édition/suppression des rôles custom autorisée pour `admin_entreprise` (et super_admin), interdite aux autres acteurs entreprise.
- Front sans bypass "admin = tout": navigation alignée sur permissions effectives/scopées.
- Enforcement du scope actif progressif par cohortes (users/entreprises), puis global.
- Workflow collaborateurs: création/import par acteur habilité, activation seulement après approbation admin entreprise.

## Chantiers ouverts prioritaires
- Finaliser la matrice fine norme -> sous-modules (`restrictedMatrix`) et ses tests.
- Éliminer complètement les restes legacy basés sur le préfixe de nom (usage informatif uniquement).
- Passer de cohortes à enforcement global (`AUTHZ_ENFORCE_ACTIVE_SCOPE=true`) après seuil qualité.
- Verrouiller la couverture "réalité prod" sur les parcours critiques.

## Critères de sortie
- Plus de divergence front/back sur les refus d'accès.
- Zéro accès inter-entreprise sur ressources scoppées.
- Parcours critiques validés en intégration: création, import, approbation, login pending, catalogue d'accès.
- Documentation opérationnelle tenue à jour (journal + décisions + statut hebdo).
