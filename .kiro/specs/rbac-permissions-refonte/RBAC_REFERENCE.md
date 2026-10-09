# LOGIQUALI — DOCUMENT DE RÉFÉRENCE RBAC
## Système de Permissions & Rôles — Refonte Complète

**Version :** 1.0  
**Date :** Mai 2026  
**Statut :** Document de référence actif  
**Périmètre :** Backend Laravel + Frontend Vue.js

---

## 1. CONTEXTE ET DÉCISIONS ARCHITECTURALES

### 1.1 Décisions prises

| Décision | Choix retenu | Raison |
|---|---|---|
| Moteur RBAC | Spatie Permission (maintenu) | Standard Laravel, éprouvé |
| Modèle de rôles entreprise | 3 rôles système + rôles personnalisés | Flexibilité maximale pour l'entreprise |
| Permissions directes | Supprimées (RBAC pur) | Simplicité, auditabilité, sécurité |
| Table user_permissions custom | Supprimée | Doublon avec tables Spatie |
| Rôles métier legacy | Supprimés | Remplacés par rôles personnalisés |
| Scope souscription | Enforced en production | Sécurité : pas d'accès hors souscription |
| Alias de permissions | Supprimés | Éviter les sur-permissions silencieuses |

### 1.2 Modèle de rôles cible

**Rôles système fixes (non modifiables par l'entreprise) :**

| Rôle | Code | Qui peut l'assigner | Description |
|---|---|---|---|
| Super Admin | `super_admin` | Personne (auto-assigné) | Accès total à la plateforme |
| Super Admin Readonly | `super_admin_readonly` | Super Admin | Lecture seule plateforme |
| Super Admin KYC | `super_admin_kyc` | Super Admin | Validation entreprises |
| Super Admin Offers | `super_admin_offers` | Super Admin | Gestion offres |
| Super Admin Norms | `super_admin_norms` | Super Admin | Gestion normes |
| Super Admin Subscriptions | `super_admin_subscriptions` | Super Admin | Gestion abonnements |
| Super Admin Users | `super_admin_users` | Super Admin | Gestion utilisateurs plateforme |
| Super Admin Settings | `super_admin_settings` | Super Admin | Paramètres système |
| Super Admin Sessions | `super_admin_sessions` | Super Admin | Gestion sessions |
| Super Admin Search | `super_admin_search` | Super Admin | Recherche globale |
| Admin Entreprise | `admin_entreprise` | Super Admin uniquement | Accès total à l'entreprise (hors super_admin_only) |
| Responsable de Site | `site_manager` | Admin Entreprise | Gestion complète d'un site |
| Lecteur | `lecteur` | Admin Entreprise, Site Manager | Lecture seule sur tous les modules |

**Rôles personnalisés (créés par l'entreprise) :**
- Créés par `admin_entreprise` ou tout collaborateur ayant `roles.create`
- Scopés à l'entreprise (`enterprise_id` obligatoire)
- Permissions choisies parmi les permissions disponibles (hors `super_admin_only_permissions`)
- Plusieurs rôles peuvent coexister et être assignés à un même utilisateur
- Un utilisateur peut avoir plusieurs rôles simultanément (les permissions s'additionnent)

### 1.3 Rôles legacy supprimés

Les rôles suivants sont **définitivement supprimés** et ne doivent plus apparaître nulle part :
- `quality_manager`
- `hse_manager`
- `environment_manager`
- `process_owner`
- `auditor`
- `team_leader`
- `operator`

Les utilisateurs qui avaient ces rôles sont migrés vers `lecteur` par défaut.

---

## 2. ARCHITECTURE DU SYSTÈME

### 2.1 Tables de base de données (Spatie uniquement)

```
roles                    — Rôles (system + custom)
  id, name, guard_name, enterprise_id, description, deleted_at

permissions              — Permissions atomiques
  id, name, guard_name

model_has_roles          — Assignation rôles → utilisateurs
  role_id, model_type, model_id

model_has_permissions    — (NON UTILISÉ pour les users — RBAC pur)
  role_id, model_type, model_id

role_has_permissions     — Permissions assignées aux rôles
  permission_id, role_id
```

**Table supprimée :** `user_permissions` (custom) — remplacée par les tables Spatie.

### 2.2 Nomenclature des permissions

Format : `{ressource}.{action}` ou `{module}.{sous-module}.{action}`

**Actions standard :**
- `read` — Consulter/lister
- `create` — Créer
- `update` — Modifier
- `delete` — Supprimer
- `manage` — Accès complet (équivalent read+create+update+delete)
- `validate` — Valider (workflow documentaire)

**Exemples :**
```
processes.read
processes.create
processes.update
processes.delete
leadership.roles_responsabilites.fiche_poste.read
verify_documents
approve_documents
configure_nomenclature
```

**Règle absolue :** Pas d'alias. Chaque permission a un nom canonique unique. Pas de `audits.read → evaluation.read`.

### 2.3 Calcul des permissions effectives

```
permissions_effectives = UNION(permissions de tous les rôles de l'utilisateur)
                       ∩ permissions_du_scope_actif (si enforce_active_scope = true)
```

Le scope actif est calculé depuis :
1. Les modules/sous-modules/sections accessibles via la souscription active du site
2. Les préfixes système toujours autorisés (users, roles, dashboard, settings…)

### 2.4 Fichiers de configuration

| Fichier | Rôle |
|---|---|
| `config/role_baselines.php` | Définit les permissions de chaque rôle système |
| `config/role_catalog.php` | Catalogue des rôles visibles dans l'UI |
| `config/authz.php` | Comportement runtime (enforce_active_scope, etc.) |

---

## 3. RÈGLES MÉTIER

### 3.1 Qui peut créer/modifier/supprimer des rôles

| Acteur | Peut créer | Peut modifier | Peut supprimer | Restrictions |
|---|---|---|---|---|
| Super Admin | Tous | Tous | Tous | — |
| Admin Entreprise | Rôles custom de son entreprise | Rôles custom de son entreprise | Rôles custom de son entreprise | Ne peut pas créer de rôles système |
| Site Manager | Rôles custom (si permission `roles.create`) | Rôles custom (si permission `roles.update`) | Rôles custom (si permission `roles.delete`) | Ne peut pas créer `admin_entreprise` ni `site_manager` |
| Lecteur | Non | Non | Non | — |

### 3.2 Qui peut assigner des rôles

| Acteur | Peut assigner | Restrictions |
|---|---|---|
| Super Admin | Tous les rôles | — |
| Admin Entreprise | Tous les rôles de son entreprise + rôles système (sauf super_admin*) | Ne peut pas s'auto-promouvoir super_admin |
| Site Manager | `lecteur` + rôles custom de son entreprise | Ne peut pas assigner `admin_entreprise` ni `site_manager` |

### 3.3 Permissions directes

**Les permissions directes sur les utilisateurs sont interdites.** Seules les permissions via rôles sont autorisées.

`AUTHZ_ENFORCE_ROLES_ONLY_PERMISSIONS=true` en production.

Exception : aucune. Si un cas particulier se présente, créer un rôle dédié.

### 3.4 Scope souscription

Un utilisateur ne peut accéder qu'aux modules pour lesquels son site a une souscription active.

`AUTHZ_ENFORCE_ACTIVE_SCOPE=true` en production.

### 3.5 Rôles multiples

Un utilisateur peut avoir plusieurs rôles simultanément. Les permissions s'additionnent (union). Il n'y a pas de conflit — une permission accordée par un rôle ne peut pas être retirée par un autre rôle.

---

## 4. PLAN D'IMPLÉMENTATION

### Phase 1 — Nettoyage et fondations (PRIORITÉ CRITIQUE)

#### Tâche 1.1 — Supprimer la table `user_permissions` custom
- **Fichiers backend :** `app/Models/User.php` (relation `permissions()`), migration de suppression
- **Impact :** `UserResource` utilise `$this->permissions` qui pointe vers cette table
- **Action :** Créer une migration `drop_user_permissions_table`, mettre à jour `User::permissions()` pour pointer vers les tables Spatie, mettre à jour `UserResource`
- **Test :** Vérifier que `$user->getAllPermissions()` retourne les mêmes résultats qu'avant

#### Tâche 1.2 — Supprimer les rôles legacy de `role_baselines.php`
- **Fichiers :** `config/role_baselines.php`, `RolePermissionBaselineService.php`
- **Action :** Supprimer les entrées `quality_manager`, `hse_manager`, `environment_manager`, `process_owner`, `auditor`, `team_leader`, `operator` de la config. Supprimer la constante `LEGACY_COLLABORATOR_ROLES` du service (devenue inutile).
- **Migration données :** Créer une migration qui réassigne les utilisateurs ayant ces rôles vers `lecteur`
- **Test :** Vérifier qu'aucun utilisateur n'a plus ces rôles en base

#### Tâche 1.3 — Supprimer les alias de permissions dans `authz.php`
- **Fichiers :** `config/authz.php`
- **Action :** Vider le tableau `permission_aliases`. Supprimer la logique de résolution d'alias dans `PermissionHelper::normalizePermissionAlias()` (ou la laisser mais avec un tableau vide).
- **Impact :** Vérifier tous les contrôleurs qui utilisaient ces alias et les corriger pour utiliser les permissions canoniques
- **Test :** Vérifier que les accès fonctionnent toujours avec les permissions canoniques

#### Tâche 1.4 — Activer `enforce_active_scope`
- **Fichiers :** `.env`, `config/authz.php`
- **Action :** Passer `AUTHZ_ENFORCE_ACTIVE_SCOPE=true`
- **Prérequis :** S'assurer que `DocumentTypeConfigurationBootstrapService` est en place (sinon les exports cassent)
- **Test :** Vérifier qu'un utilisateur sans souscription active ne peut pas accéder aux modules

#### Tâche 1.5 — Supprimer le champ `permissions` de `UserController::update()`
- **Fichiers :** `app/Http/Controllers/Api/UserController.php`
- **Action :** Supprimer `'permissions' => 'nullable|array'` de la validation dans `store()` et `update()`. Supprimer le bloc de traitement des permissions directes.
- **Test :** Vérifier que la création/modification d'un utilisateur fonctionne sans le champ permissions

#### Tâche 1.6 — Corriger `RoleController` : permettre plusieurs rôles simultanés
- **Problème actuel :** `$user->syncRoles([$role])` écrase les rôles existants
- **Fichiers :** `app/Http/Controllers/Api/UserController.php`, `app/Http/Controllers/Api/RoleController.php`
- **Action :** Remplacer `syncRoles()` par `assignRole()` pour l'assignation. Créer un endpoint dédié `POST /api/users/{id}/roles` pour ajouter un rôle et `DELETE /api/users/{id}/roles/{role}` pour en retirer un.
- **Test :** Vérifier qu'un utilisateur peut avoir 2 rôles simultanément et que ses permissions sont l'union des deux

### Phase 2 — Sécurité et contrôle d'accès (PRIORITÉ HAUTE)

#### Tâche 2.1 — Empêcher l'escalade horizontale `site_manager → site_manager`
- **Fichiers :** `app/Http/Controllers/Api/UserController.php`
- **Action :** Ajouter `site_manager` à la liste des rôles interdits pour un `site_manager` dans `isForbiddenForRestrictedSiteManager()`
- **Test :** Vérifier qu'un `site_manager` ne peut pas créer un autre `site_manager`

#### Tâche 2.2 — Réduire l'exposition dans `UserResource`
- **Fichiers :** `app/Http/Resources/UserResource.php`
- **Action :** Créer deux modes de sérialisation :
  - Mode liste (`/api/users`) : ne pas inclure `role_permissions`, `effective_permissions`, `active_scoped_permissions`
  - Mode détail (`/api/users/{id}`, `/api/me`) : inclure ces champs uniquement si l'acteur est admin ou si c'est son propre profil
- **Test :** Vérifier que `/api/users` ne retourne plus les listes de permissions

#### Tâche 2.3 — Ajouter le cache request-level sur `canAccessSubModule()` et `canAccessSection()`
- **Fichiers :** `app/Models/User.php`
- **Action :** Ajouter des propriétés de cache `private array $subModuleAccessCache = []` et `private array $sectionAccessCache = []`. Utiliser ces caches dans les méthodes correspondantes.
- **Test :** Vérifier que 10 appels à `canAccessSubModule('risques_opportunites')` ne génèrent qu'une seule requête SQL

#### Tâche 2.4 — Corriger `RoleScopeService` : rôles "Responsable de site" et "Lecteur" visibles
- **Problème :** Ces rôles système ont `enterprise_id = null` et sont filtrés par `applyRoleVisibilityScopeForUser()`
- **Fichiers :** `app/Services/Security/RoleScopeService.php`
- **Action :** Modifier la requête pour inclure les rôles système (`enterprise_id IS NULL`) ET les rôles de l'entreprise de l'utilisateur
- **Test :** Vérifier que `site_manager` et `lecteur` apparaissent dans la liste des rôles pour un `admin_entreprise`

### Phase 3 — Rôles personnalisés (PRIORITÉ HAUTE)

#### Tâche 3.1 — Implémenter la création de rôles personnalisés côté backend
- **Fichiers :** `app/Http/Controllers/Api/RoleController.php`
- **Action :** Compléter `store()` pour créer un rôle avec `enterprise_id` obligatoire, `name` unique par entreprise, et permissions choisies parmi les permissions non-super_admin_only
- **Validation :** Le nom du rôle ne peut pas être un nom de rôle système
- **Test :** Créer un rôle "Auditeur Senior" avec permissions `audits.read`, `audits.create`, `non_conformities.read`

#### Tâche 3.2 — Implémenter la modification des permissions d'un rôle personnalisé
- **Fichiers :** `app/Http/Controllers/Api/RoleController.php`
- **Action :** Compléter `update()` pour modifier les permissions d'un rôle. Utiliser `syncPermissions()` sur le rôle (pas sur l'utilisateur).
- **Test :** Modifier les permissions d'un rôle et vérifier que tous les utilisateurs ayant ce rôle voient leurs permissions mises à jour

#### Tâche 3.3 — Connecter l'UI de création/modification de rôles à l'API
- **Fichiers :** `frontend/src/modules/clienta/pages/roles/index.vue`
- **Problème actuel :** `createRole()` et `savePermissions()` affichent juste un toast "bientôt disponible"
- **Action :** Implémenter les appels API réels dans `createRole()`, `savePermissions()`, `deleteRole()`
- **Test :** Créer un rôle depuis l'UI, vérifier qu'il apparaît en base

#### Tâche 3.4 — Afficher les rôles personnalisés dans le formulaire de création de collaborateur
- **Fichiers :** `frontend/src/modules/clienta/pages/users/create.vue`, `frontend/src/modules/clienta/pages/users/[id].vue`
- **Action :** Charger les rôles disponibles (système + personnalisés de l'entreprise) et les afficher dans le sélecteur de rôle
- **Test :** Vérifier que les rôles personnalisés créés à la tâche 3.1 apparaissent dans le formulaire

### Phase 4 — UX/UI permissions (PRIORITÉ MOYENNE)

#### Tâche 4.1 — Refondre `UserPermissionsManager.vue`
- **Problème :** Affiche des noms techniques incompréhensibles
- **Action :** Créer un mapping `permission_name → label_humain` complet. Grouper par module avec des labels lisibles. Distinguer visuellement permissions héritées (gris, non modifiables) vs permissions supplémentaires (bleu).
- **Test :** Vérifier qu'un non-technicien comprend ce que fait chaque permission

#### Tâche 4.2 — Ajouter une description lisible aux rôles
- **Action :** Ajouter un champ `description` aux rôles système dans `role_catalog.php`. Afficher cette description dans l'UI lors de la sélection d'un rôle.
- **Test :** Vérifier que la description s'affiche dans le formulaire de création de collaborateur

#### Tâche 4.3 — Modale de confirmation pour changement de rôle
- **Action :** Quand on change le rôle d'un utilisateur, afficher une modale qui liste les permissions ajoutées et retirées avant de confirmer
- **Test :** Changer le rôle d'un utilisateur de `lecteur` à un rôle custom et vérifier que la modale affiche le diff

#### Tâche 4.4 — Supprimer le bouton "Tout sélectionner" de `PermissionsGrid.vue`
- **Action :** Remplacer par "Sélectionner toutes les permissions de lecture" uniquement
- **Test :** Vérifier que le bouton ne sélectionne que les permissions `.read`

### Phase 5 — Nettoyage final et documentation (PRIORITÉ BASSE)

#### Tâche 5.1 — Nettoyer `PermissionsRoleEditor.vue`
- **Problème :** Contient encore des références aux rôles legacy (`quality_manager`, `auditor`, etc.) dans `roleVisuals`
- **Action :** Supprimer ces entrées du dictionnaire `roleVisuals`

#### Tâche 5.2 — Mettre à jour `UnifiedPermissionsSeeder`
- **Action :** Supprimer la création des rôles legacy. Mettre à jour `backfillCoreRoleAssignments()` pour ne gérer que les 3 rôles système entreprise.

#### Tâche 5.3 — Ajouter des tests d'intégration
- **Tests à écrire :**
  - `lecteur` ne peut pas créer (POST sur n'importe quel endpoint métier → 403)
  - `site_manager` ne peut pas accéder aux modules non souscrits
  - `admin_entreprise` ne peut pas accéder aux fonctions `super_admin_only`
  - Un `site_manager` ne peut pas créer un autre `site_manager`
  - Un rôle personnalisé donne bien les permissions définies
  - Plusieurs rôles simultanés → union des permissions

---

## 5. VARIABLES D'ENVIRONNEMENT

| Variable | Valeur dev | Valeur prod | Description |
|---|---|---|---|
| `AUTHZ_ENFORCE_ROLES_ONLY_PERMISSIONS` | `true` | `true` | Interdit les permissions directes |
| `AUTHZ_ENFORCE_ACTIVE_SCOPE` | `false` | `true` | Filtre par souscription active |
| `AUTHZ_STRICT_LEGACY_ALIASES` | `true` | `true` | Pas d'alias legacy |
| `AUTHZ_SHADOW_COMPARE_ENABLED` | `false` | `false` | Comparaison shadow (debug uniquement) |

---

## 6. ENDPOINTS API

### Rôles
```
GET    /api/roles                    — Liste des rôles (filtrés par scope)
POST   /api/roles                    — Créer un rôle personnalisé
GET    /api/roles/{id}               — Détail d'un rôle
PUT    /api/roles/{id}               — Modifier un rôle (nom, description, permissions)
DELETE /api/roles/{id}               — Supprimer un rôle personnalisé
```

### Assignation de rôles aux utilisateurs
```
GET    /api/users/{id}/roles         — Rôles d'un utilisateur
POST   /api/users/{id}/roles         — Ajouter un rôle à un utilisateur
DELETE /api/users/{id}/roles/{role}  — Retirer un rôle d'un utilisateur
```

### Permissions disponibles
```
GET    /api/permissions              — Liste toutes les permissions (admin uniquement)
GET    /api/v1/available-permissions — Permissions disponibles pour l'UI (groupées par module)
```

---

## 7. CHECKLIST DE VALIDATION PAR TÂCHE

Pour chaque tâche, avant de la marquer comme terminée :

- [ ] Le code compile sans erreur
- [ ] Les tests unitaires passent
- [ ] Les tests manuels listés dans la tâche passent
- [ ] Aucune régression sur les fonctionnalités existantes
- [ ] Les messages d'erreur retournés à l'utilisateur sont en français et ne contiennent pas de détails techniques
- [ ] Les logs backend sont propres et exploitables
- [ ] Le document de référence est mis à jour si nécessaire

---

## 8. POINTS D'ATTENTION ET PIÈGES

1. **`syncRoles()` vs `assignRole()`** : `syncRoles()` écrase tous les rôles existants. Utiliser `assignRole()` pour ajouter un rôle sans écraser les autres.

2. **Cache Spatie** : Après toute modification de permissions/rôles, appeler `app(PermissionRegistrar::class)->forgetCachedPermissions()` pour vider le cache.

3. **`enterprise_id` sur les rôles** : Les rôles système ont `enterprise_id = null`. Les rôles personnalisés ont `enterprise_id` obligatoire. Ne jamais créer un rôle personnalisé sans `enterprise_id`.

4. **Permissions `super_admin_only`** : Ces permissions ne doivent jamais être assignées à un rôle entreprise. La liste est dans `config/role_baselines.php` → `super_admin_only_permissions`.

5. **`enforce_active_scope`** : Quand activé, un utilisateur avec toutes les permissions mais sans souscription active verra ses permissions filtrées à zéro. S'assurer que les souscriptions sont bien créées avant d'activer ce flag.

6. **Rôles multiples** : L'union des permissions s'applique. Si un utilisateur a `lecteur` (read only) ET un rôle custom avec `processes.create`, il peut créer des processus. C'est intentionnel.

---

*Ce document est la source de vérité pour le système RBAC de LOGIQUALI. Toute modification du système de permissions doit être reflétée ici.*
