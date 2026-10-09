# Journal d'Exécution IAM/RBAC

## 2026-04-23

### Fait
- Option B implémentée:
  - `admin_entreprise` hérite `roles.create/update/delete`.
  - Guard backend durci: seuls `super_admin` et `admin_entreprise` peuvent muter les rôles custom.
- Suppression bypass guard front "admin entreprise/super admin = accès route" sur routes à permissions explicites.
- Passage progressif shadow -> enforcement:
  - Ajout cohortes `AUTHZ_ENFORCE_ACTIVE_SCOPE_COHORT_USER_IDS` et `AUTHZ_ENFORCE_ACTIVE_SCOPE_COHORT_ENTERPRISE_IDS`.
  - Exposition `authz_enforce_active_scope` dans payload user.
  - Front en mode strict quand ce flag est actif (plus de fallback legacy/effective).
- Gouvernance rôles custom:
  - Scoping sécurité recentré sur `roles.enterprise_id`.
  - Réduction des inférences de sécurité basées sur le nom du rôle.
- Qualité/tests:
  - Ajout test d'intégration "réalité prod" couvrant: login pending, création, import, approbation, accès catalog.
  - Ajout tests unitaires sur l'enforcement progressif par cohorte.

### Limites connues
- Exécution des tests backend non réalisable localement sans PostgreSQL disponible.
- `restrictedMatrix` catalogue encore à finaliser (matrice fine norme -> sous-modules).

### Prochaine tranche
- Stabiliser tests en CI/DB réelle.
- Nettoyage final des branches legacy liées au pattern de nom des rôles custom.
- Validation produit pour bascule enforcement global.
