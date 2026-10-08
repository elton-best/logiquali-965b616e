# GUIDE D'IMPLÉMENTATION - ARCHITECTURE MULTI-RÔLES

## 📋 Résumé des modifications

Votre système BestQHSE a été enrichi avec :

1. ✅ **Association collaborateurs-sites** : Chaque utilisateur est maintenant lié à un site principal
2. ✅ **Processus fournisseurs/clients** : Les activités de processus ont des entrées (processus fournisseurs) et sorties (processus clients)
3. ✅ **8 rôles utilisateurs** avec dashboards et permissions spécifiques
4. ✅ **Architecture frontend multi-rôles** avec layouts dédiés

---

## 📁 Fichiers créés

### 1. Base de données

- **`DATABASE_SCHEMA_SMI_MULTI_ROLES.sql`** : Schéma SQL complet avec :
  - `users.site_id` : Association utilisateur → site
  - `user_site_access` : Accès multi-sites pour un utilisateur
  - `process_activities` : Activités d'un processus
  - `activity_supplier_processes` : Processus fournisseurs (entrées)
  - `activity_client_processes` : Processus clients (sorties)
  - 8 rôles prédéfinis

### 2. Backend Laravel

- **`backend/database/migrations/2025_01_XX_add_multi_site_and_process_relations.php`** : Migration pour ajouter les nouvelles tables
- **`backend/database/seeders/RolesAndPermissionsSeeder.php`** : Seeder pour créer les 8 rôles avec leurs permissions

### 3. Documentation

- **`ROLES_ARCHITECTURE.md`** : Documentation complète des 8 rôles avec :
  - Description de chaque rôle
  - Permissions détaillées
  - Menus et dashboards
  - Matrice des permissions
  - Structure frontend proposée

### 4. Frontend

- **`frontend/src/modules/clienta/components/layouts/SiteLayout.vue`** : Layout pour le Site Manager

---

## 🎯 Les 8 rôles utilisateurs

| Rôle                 | Description                                 | Dashboard                      |
| -------------------- | ------------------------------------------- | ------------------------------ |
| **Super Admin**      | Administrateur système (toutes entreprises) | `/admin/dashboard`             |
| **Enterprise Admin** | Admin entreprise (multi-sites)              | `/company/dashboard` ✅ ACTUEL |
| **Site Manager**     | Responsable de site                         | `/site/dashboard`              |
| **Quality Manager**  | Responsable qualité/SMI                     | `/quality/dashboard`           |
| **Process Owner**    | Pilote de processus                         | `/process/dashboard`           |
| **Auditor**          | Auditeur interne                            | `/auditor/dashboard`           |
| **Employee**         | Collaborateur                               | `/employee/dashboard`          |
| **Viewer**           | Lecteur (consultation)                      | `/viewer/dashboard`            |

---

## 🔧 Étapes d'implémentation

### Phase 1 : Base de données (Backend)

```bash
# 1. Exécuter la migration
cd backend
php artisan migrate --path=database/migrations/2025_01_XX_add_multi_site_and_process_relations.php

# 2. Exécuter le seeder des rôles
php artisan db:seed --class=RolesAndPermissionsSeeder

# 3. Vérifier les rôles créés
php artisan tinker
>>> \Spatie\Permission\Models\Role::all()->pluck('name')
```

### Phase 2 : Modèles Laravel (Backend)

#### Modifier `app/Models/User.php`

```php
class User extends Authenticatable
{
    use HasRoles;

    protected $fillable = [
        // ... existant
        'site_id', // AJOUTER
    ];

    // AJOUTER ces relations
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function accessibleSites()
    {
        return $this->belongsToMany(Site::class, 'user_site_access')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
```

#### Créer `app/Models/ProcessActivity.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessActivity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref', 'process_id', 'name', 'description',
        'sequence_order', 'responsible_id', 'duration_estimated'
    ];

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    // Processus fournisseurs (entrées)
    public function supplierProcesses()
    {
        return $this->belongsToMany(Process::class, 'activity_supplier_processes', 'activity_id', 'supplier_process_id')
            ->withPivot('input_description', 'input_type', 'is_critical')
            ->withTimestamps();
    }

    // Processus clients (sorties)
    public function clientProcesses()
    {
        return $this->belongsToMany(Process::class, 'activity_client_processes', 'activity_id', 'client_process_id')
            ->withPivot('output_description', 'output_type', 'is_critical')
            ->withTimestamps();
    }
}
```

#### Modifier `app/Models/Process.php`

```php
class Process extends Model
{
    // ... existant

    // AJOUTER
    public function activities()
    {
        return $this->hasMany(ProcessActivity::class)->orderBy('sequence_order');
    }

    // Processus pour lesquels je suis fournisseur
    public function clientActivities()
    {
        return $this->belongsToMany(ProcessActivity::class, 'activity_supplier_processes', 'supplier_process_id', 'activity_id');
    }

    // Processus pour lesquels je suis client
    public function supplierActivities()
    {
        return $this->belongsToMany(ProcessActivity::class, 'activity_client_processes', 'client_process_id', 'activity_id');
    }
}
```

### Phase 3 : API Controllers (Backend)

#### Créer `app/Http/Controllers/Api/ProcessActivityController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessActivity;
use Illuminate\Http\Request;

class ProcessActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcessActivity::with(['process', 'responsible', 'supplierProcesses', 'clientProcesses']);

        if ($request->has('process_id')) {
            $query->where('process_id', $request->process_id);
        }

        $activities = $query->orderBy('sequence_order')->get();

        return response()->json(['data' => $activities]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ref' => 'required|unique:process_activities',
            'process_id' => 'required|exists:processes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sequence_order' => 'nullable|integer',
            'responsible_id' => 'nullable|exists:users,id',
            'duration_estimated' => 'nullable|integer',
        ]);

        $activity = ProcessActivity::create($validated);

        return response()->json(['data' => $activity->load(['process', 'responsible'])], 201);
    }

    public function attachSupplier(Request $request, ProcessActivity $activity)
    {
        $validated = $request->validate([
            'supplier_process_id' => 'required|exists:processes,id',
            'input_description' => 'nullable|string',
            'input_type' => 'nullable|string',
            'is_critical' => 'boolean',
        ]);

        $activity->supplierProcesses()->attach($validated['supplier_process_id'], [
            'input_description' => $validated['input_description'] ?? null,
            'input_type' => $validated['input_type'] ?? null,
            'is_critical' => $validated['is_critical'] ?? false,
        ]);

        return response()->json(['message' => 'Processus fournisseur ajouté']);
    }

    public function attachClient(Request $request, ProcessActivity $activity)
    {
        $validated = $request->validate([
            'client_process_id' => 'required|exists:processes,id',
            'output_description' => 'nullable|string',
            'output_type' => 'nullable|string',
            'is_critical' => 'boolean',
        ]);

        $activity->clientProcesses()->attach($validated['client_process_id'], [
            'output_description' => $validated['output_description'] ?? null,
            'output_type' => $validated['output_type'] ?? null,
            'is_critical' => $validated['is_critical'] ?? false,
        ]);

        return response()->json(['message' => 'Processus client ajouté']);
    }
}
```

#### Ajouter les routes dans `routes/api.php`

```php
// Process Activities
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('process-activities', ProcessActivityController::class);
    Route::post('process-activities/{activity}/suppliers', [ProcessActivityController::class, 'attachSupplier']);
    Route::post('process-activities/{activity}/clients', [ProcessActivityController::class, 'attachClient']);
});
```

### Phase 4 : Frontend - Layouts par rôle

Créer les layouts pour chaque rôle dans `frontend/src/modules/clienta/components/layouts/` :

- ✅ `SiteLayout.vue` (créé)
- `QualityLayout.vue`
- `ProcessLayout.vue`
- `AuditorLayout.vue`
- `EmployeeLayout.vue`
- `ViewerLayout.vue`

### Phase 5 : Frontend - Dashboards par rôle

Créer les dashboards dans `frontend/src/modules/clienta/pages/` :

```
pages/
├── site/
│   └── dashboard.vue
├── quality/
│   └── dashboard.vue
├── process/
│   └── dashboard.vue
├── auditor/
│   └── dashboard.vue
├── employee/
│   └── dashboard.vue
└── viewer/
    └── dashboard.vue
```

### Phase 6 : Router avec guards

Modifier `frontend/src/modules/clienta/router/index.ts` :

```typescript
import { useAuthStore } from "@/stores/auth";

const roleRoutes = {
  super_admin: "/admin/dashboard",
  enterprise_admin: "/company/dashboard",
  site_manager: "/site/dashboard",
  quality_manager: "/quality/dashboard",
  process_owner: "/process/dashboard",
  auditor: "/auditor/dashboard",
  employee: "/employee/dashboard",
  viewer: "/viewer/dashboard",
};

// Guard pour vérifier le rôle
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next("/login");
  }

  if (to.meta.roles) {
    const userRole = authStore.user?.roles?.[0]?.name;
    if (!to.meta.roles.includes(userRole)) {
      return next(roleRoutes[userRole] || "/unauthorized");
    }
  }

  next();
});

// Routes par rôle
const routes = [
  // Site Manager
  {
    path: "/site",
    component: () =>
      import("@/modules/clienta/components/layouts/SiteLayout.vue"),
    meta: { requiresAuth: true, roles: ["site_manager"] },
    children: [
      {
        path: "dashboard",
        component: () => import("@/modules/clienta/pages/site/dashboard.vue"),
      },
      // ... autres routes
    ],
  },
  // ... autres rôles
];
```

---

## 🔑 Concepts clés

### 1. Association Collaborateur → Site

Chaque utilisateur a maintenant un `site_id` qui indique son site principal :

```sql
-- Exemple
INSERT INTO users (ref, enterprise_id, site_id, email, first_name, last_name, position)
VALUES ('USR-001', 1, 1, 'john@example.com', 'John', 'Doe', 'Responsable Qualité');
```

### 2. Processus Fournisseurs/Clients

Pour chaque activité d'un processus, on définit :

**Entrées (Processus Fournisseurs)** :

```sql
-- L'activité "Réception commande" reçoit une entrée du processus "Vente"
INSERT INTO activity_supplier_processes (activity_id, supplier_process_id, input_description, input_type)
VALUES (1, 5, 'Bon de commande validé', 'document');
```

**Sorties (Processus Clients)** :

```sql
-- L'activité "Réception commande" fournit une sortie au processus "Production"
INSERT INTO activity_client_processes (activity_id, client_process_id, output_description, output_type)
VALUES (1, 8, 'Ordre de fabrication', 'document');
```

### 3. Accès multi-sites

Un utilisateur peut avoir accès à plusieurs sites via `user_site_access` :

```sql
-- John a accès au site 1 (principal) et au site 2
INSERT INTO user_site_access (user_id, site_id, is_primary) VALUES
(1, 1, true),
(1, 2, false);
```

---

## 📊 Exemple de flux complet

### Scénario : Processus "Gestion des commandes"

1. **Processus** : Gestion des commandes (site Yaoundé)
2. **Activités** :
   - A1: Réception commande
   - A2: Vérification stock
   - A3: Validation commande

3. **Relations fournisseurs/clients** :

```
Processus "Vente" (fournisseur)
    ↓ (Bon de commande)
A1: Réception commande
    ↓ (Demande de vérification)
A2: Vérification stock
    ↓ (Confirmation disponibilité)
A3: Validation commande
    ↓ (Ordre de fabrication)
Processus "Production" (client)
```

---

## ✅ Checklist d'implémentation

### Backend

- [ ] Exécuter la migration
- [ ] Exécuter le seeder des rôles
- [ ] Modifier le modèle User
- [ ] Créer le modèle ProcessActivity
- [ ] Modifier le modèle Process
- [ ] Créer ProcessActivityController
- [ ] Ajouter les routes API
- [ ] Tester les endpoints

### Frontend

- [ ] Créer les layouts pour chaque rôle
- [ ] Créer les dashboards pour chaque rôle
- [ ] Modifier le router avec guards
- [ ] Créer les pages spécifiques par rôle
- [ ] Adapter les composants existants
- [ ] Tester la navigation par rôle

### Tests

- [ ] Tester l'association utilisateur → site
- [ ] Tester les processus fournisseurs/clients
- [ ] Tester les permissions par rôle
- [ ] Tester la navigation automatique
- [ ] Tester l'accès multi-sites

---

## 🚀 Prochaines étapes recommandées

1. **Implémenter les dashboards** pour chaque rôle avec des KPIs adaptés
2. **Créer une interface de gestion des activités** avec drag & drop pour le séquencement
3. **Visualiser les flux processus** avec les relations fournisseurs/clients (diagramme)
4. **Ajouter des notifications** spécifiques par rôle
5. **Créer des rapports** adaptés à chaque rôle

---

## 📞 Support

Pour toute question sur l'implémentation, référez-vous à :

- `ROLES_ARCHITECTURE.md` : Documentation complète des rôles
- `DATABASE_SCHEMA_SMI_MULTI_ROLES.sql` : Schéma SQL complet
- Les migrations et seeders Laravel fournis
