# CONTEXTE DE REPRISE - BestQHSE - 2026-03-25 (Leadership)

Stack: Frontend React + TypeScript, backend Laravel API, controle d'acces par roles/permissions.
Etat: Implementation Leadership livree sur frontend et UserController backend, avec durcissement d'acces role custom.

## Changements implementes

- Frontend Leadership:
  - Correction du merge des labels de site dans la vue Personnel.
  - Ajout d'une recherche de permissions dans PermissionsPicker.
  - Support du role personnalise dans le formulaire collaborateur (creation/mise a jour du role custom entreprise).
  - Visibilite/gestion du role custom limitees a admin_entreprise et super_admin.
- Backend UserController:
  - Prise en charge des roles custom entreprise dans getAvailableRoles.
  - Prise en charge des roles custom dans les controles d'eligibilite role/site.
  - Verrouillage de user_type pour les non-super-admin.
  - Validation du role avant update pour eviter les updates partielles.

## Fichiers modifies

- backend/app/Http/Controllers/Api/UserController.php
- frontend/src/modules/clienta/pages/leadership/PersonnelList.vue
- frontend/src/modules/clienta/pages/leadership/components/PermissionsPicker.vue
- frontend/src/modules/clienta/pages/leadership/components/PersonnelCreateDialog.vue

## Risques residuels

- Bornage serveur des permissions directes par scope site/souscription: non implemente dans ce lot.
- Vigilance zero regression: verifier qu'aucun profil non autorise ne peut voir/attribuer un role custom.

## Prochains pas recommandes

1. Ajouter un garde-fou serveur explicite sur les permissions directes (scope site + souscription active).
2. Ajouter/renforcer les tests integration UserController (getAvailableRoles, update, assignRole, assignRoles).
3. Executer un test de non-regression frontend Leadership (creation, edition, filtrage, permissions) sur profils super_admin/admin_entreprise/site_manager.
