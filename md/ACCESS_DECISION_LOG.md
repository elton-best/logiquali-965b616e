# Decision Log IAM/RBAC

## D-2026-04-23-01
- Sujet: Gouvernance création rôles custom.
- Décision: Option B retenue.
- Règle: `admin_entreprise` et `super_admin` peuvent créer/éditer/supprimer les rôles custom; autres acteurs entreprise interdits.
- Conséquence: alignement UX/API, réduction 403 surprise au moment de création collaborateur.

## D-2026-04-23-02
- Sujet: Bypass front "admin = tout".
- Décision: supprimé pour routes à permissions explicites.
- Règle: le front s'aligne sur permissions effectives/scopées, sans court-circuit par rôle.
- Conséquence: cohérence forte avec backend, baisse des incohérences navigation/action.

## D-2026-04-23-03
- Sujet: Passage shadow -> enforcement scope actif.
- Décision: déploiement progressif par cohorte avant activation globale.
- Règle: enforcement actif si flag global true OU utilisateur/entreprise dans cohortes configurées.
- Conséquence: rollout contrôlé, mitigation des régressions.

## D-2026-04-23-04
- Sujet: Source de vérité rôles custom.
- Décision: sécurité basée sur `roles.enterprise_id`; le nom de rôle ne fait plus foi pour le scope.
- Règle: inférence par pattern de nom reléguée à la compatibilité transitoire/non-sécurité.
- Conséquence: réduction du risque spoofing par nom, simplification de la gouvernance multi-tenant.
