# Politique des Roles Super Admin

## Objectif
Definir des roles super admin specialistes pour limiter le champ d'action de chaque compte, renforcer la securite et faciliter l'audit.

## Principes
- Moindre privilege: donner uniquement ce qui est necessaire.
- Separation des pouvoirs: eviter qu'un seul role puisse tout faire.
- Traçabilite: chaque action sensible doit etre attribuable.

## Roles proposes

### Super Admin (Global)
- Acces complet a toutes les routes super admin.
- Utilise le role `super_admin` (strategy all).

### Admin KYC
- Valider, rejeter, suspendre, reactiver les entreprises.
- Acces en lecture aux entreprises et documents KYC.
- Pas de gestion des offres, abonnements, utilisateurs globaux.

### Admin Offres
- Creer, modifier, supprimer les offres.
- Gerer les normes (si necessaire pour les offres).
- Pas d'acces aux entreprises et KYC.

### Admin Support
- Acces aux utilisateurs (lecture), suspension/reactivation si policy autorise.
- Acces aux logs d'audit (lecture).
- Pas de gestion des offres et des abonnements.

## Permissions recommandees (exemples)
Les permissions utilisent le catalogue unifie (pas de prefixe superadmin).
- Entreprises/KYC: `enterprises.read`, `enterprises.update`, `enterprises.delete`
- Offres: `offers.read`, `offers.create`, `offers.update`, `offers.delete`
- Abonnements: `subscriptions.read`, `subscriptions.update`, `subscriptions.delete`
- Utilisateurs: `users.read`, `users.create`, `users.update`, `roles.read`
- Normes: `norms.read`, `norms.manage`
- Parametres: `settings.read`, `settings.update`
- Sessions: `sessions.read`, `sessions.revoke`
- Recherche: `search.read`

## Gouvernance
- Le role "Super Admin (Global)" est reserve au minimum de comptes possibles.
- Toute creation de role doit etre documentee et approuvee.
- Les roles doivent etre revus trimestriellement.

## Notes
- Les permissions exactes doivent etre alignees avec les endpoints exposes.
