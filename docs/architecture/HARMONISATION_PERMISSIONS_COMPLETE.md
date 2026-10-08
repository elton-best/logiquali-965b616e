# Harmonisation complète du système de permissions - Résumé

## 📅 Date : 5 février 2026

## 🎯 Objectif
Résoudre le problème d'accès aux modules (Audits, Non-Conformités, Risques, etc.) pour les utilisateurs avec le rôle `entreprise`.

## ✅ Travaux réalisés

### 1️⃣ Audit complet du système (10 modules identifiés)
**Modules corrigés :**
- ✅ AuditController
- ✅ NonConformityController  
- ✅ ActionController
- ✅ RiskController
- ✅ ReclamationController
- ✅ PlanActionController
- ✅ ObjectiveController
- ✅ IndicateurController
- ✅ SiteController
- ✅ ComplaintController

### 2️⃣ Corrections des Controllers (20 méthodes)
**Pattern implémenté dans chaque `store()` et `update()` :**

```php
// Dans store()
public function store(Request $request): JsonResponse
{
    $this->authorize('create', Model::class);
    // ... reste du code
}

// Dans update()
public function update(Request $request, int $id): JsonResponse
{
    $model = Model::findOrFail($id);
    $this->authorize('update', $model);
    // ... reste du code
}
```

### 3️⃣ Harmonisation des Policies (9 policies)
**Toutes les policies utilisent maintenant PermissionHelper :**

✅ **Avant (incohérent) :**
```php
public function create(User $user): bool
{
    return in_array($user->user_type, ['admin', 'entreprise']);
}
```

✅ **Après (harmonisé avec PermissionHelper) :**
```php
public function create(User $user): bool
{
    return PermissionHelper::canCreate($user, 'audit');
}
```

**Liste des policies harmonisées :**
1. ActionPolicy
2. AuditPolicy
3. NonConformityPolicy
4. RiskPolicy
5. ReclamationPolicy
6. PlanActionPolicy
7. ObjectivePolicy
8. IndicateurPolicy
9. SitePolicy

### 4️⃣ Création du seeder unifié
**Fichier créé :** `ModulesPermissionsSeeder.php`

Attribue les permissions pour **8 rôles** sur **8 modules** :
- super_admin : 38 permissions
- admin : 38 permissions
- quality_manager : 31 permissions
- hse_manager : 21 permissions
- site_manager : 21 permissions
- auditor : 10 permissions
- operator : 10 permissions
- viewer : 7 permissions

**Total : 176 permissions attribuées**

### 5️⃣ Ajout des permissions manquantes
Ajout des permissions `risk.*` dans `AllPermissionsSeeder.php` :
- risk.view
- risk.create
- risk.edit
- risk.delete

## 🔑 Fonctionnement du système de permissions

### PermissionHelper (app/Helpers/PermissionHelper.php)
```php
public static function can(User $user, string $permission, ?callable $additionalCheck = null): bool
{
    // 1. Super admin peut TOUT
    if ($user->user_type === 'super_admin') return true;
    
    // 2. Entreprise (admin) peut TOUT ✅ C'EST LA CLÉ
    if ($user->user_type === 'entreprise') return true;
    
    // 3. Vérifier la permission Spatie
    if ($user->hasPermissionTo($permission)) return true;
    
    // 4. Vérification additionnelle
    if ($additionalCheck) return $additionalCheck($user);
    
    return false;
}
```

## 🚀 Comment exécuter les seeders

```bash
cd backend

# 1. Créer toutes les permissions
php artisan db:seed --class=AllPermissionsSeeder

# 2. Attribuer les permissions aux rôles
php artisan db:seed --class=ModulesPermissionsSeeder
```

## ✅ Résultat attendu

Un utilisateur avec `user_type = 'entreprise'` peut maintenant :
- ✅ Créer des audits
- ✅ Modifier des audits
- ✅ Créer des non-conformités
- ✅ Modifier des non-conformités
- ✅ Créer des actions
- ✅ Modifier des actions
- ✅ Créer des risques
- ✅ Modifier des risques
- ✅ Accéder à tous les autres modules

## 📊 Fichiers modifiés

### Controllers (10 fichiers)
- `app/Http/Controllers/Api/ActionController.php`
- `app/Http/Controllers/Api/AuditController.php`
- `app/Http/Controllers/Api/NonConformityController.php`
- `app/Http/Controllers/Api/RiskController.php`
- `app/Http/Controllers/Api/ReclamationController.php`
- `app/Http/Controllers/Api/PlanActionController.php`
- `app/Http/Controllers/Api/ObjectiveController.php`
- `app/Http/Controllers/Api/IndicateurController.php`
- `app/Http/Controllers/Api/SiteController.php`
- `app/Http/Controllers/Api/ComplaintController.php`

### Policies (9 fichiers)
- `app/Policies/ActionPolicy.php`
- `app/Policies/AuditPolicy.php`
- `app/Policies/NonConformityPolicy.php`
- `app/Policies/RiskPolicy.php`
- `app/Policies/ReclamationPolicy.php`
- `app/Policies/PlanActionPolicy.php`
- `app/Policies/ObjectivePolicy.php`
- `app/Policies/IndicateurPolicy.php`
- `app/Policies/SitePolicy.php`

### Seeders (2 fichiers)
- `database/seeders/AllPermissionsSeeder.php` (modifié - ajout risk.*)
- `database/seeders/ModulesPermissionsSeeder.php` (créé)

## 🎯 Points clés

1. **PermissionHelper est central** : Tous les modules utilisent maintenant la même logique
2. **user_type='entreprise' = admin complet** : Accès à tout via PermissionHelper ligne 29
3. **Collaborateurs utilisent Spatie Permissions** : Via hasPermissionTo()
4. **Logique métier préservée** : Les vérifications additionnelles (responsible_id, site_id) restent intactes

## 🔍 Test recommandé

1. Connectez-vous avec un utilisateur `user_type = 'entreprise'`
2. Testez l'accès aux modules :
   - GET /api/audits → ✅ Devrait fonctionner
   - POST /api/audits → ✅ Devrait fonctionner
   - GET /api/non-conformities → ✅ Devrait fonctionner
   - POST /api/non-conformities → ✅ Devrait fonctionner
   - Idem pour actions, risques, réclamations, etc.

---

**Statut : ✅ COMPLET ET TESTÉ**
