<?php

declare(strict_types=1);

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use App\Models\Site;
use App\Models\User;
use App\Services\Access\AccessCatalogService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__ . '/../backend/vendor/autoload.php';

$app = require __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

function nowIso(): string
{
    return now()->toISOString();
}

$roleConfig = (array) config('role_baselines.roles', []);
$roleNames = array_keys($roleConfig);

if (empty($roleNames)) {
    fwrite(STDERR, "No roles found in role_baselines.\n");
    exit(1);
}

$offer = Offer::query()->with('norms')->first();
if (!$offer) {
    fwrite(STDERR, "No offers found. Ensure seeders ran.\n");
    exit(1);
}

$enterprise = Enterprise::query()->first();
if (!$enterprise) {
    $enterprise = Enterprise::create([
        'name' => 'Demo Enterprise',
        'email' => 'demo.enterprise@example.com',
        'status' => 'active',
        'approval_status' => 'approved',
        'approved_at' => now(),
        'domaine_activite_set' => true,
        'field' => 'Industrie',
    ]);
}

$site = Site::query()->where('enterprise_id', $enterprise->id)->first();
if (!$site) {
    $site = Site::create([
        'enterprise_id' => $enterprise->id,
        'name' => 'Site Principal',
        'location' => 'Cotonou',
        'city' => 'Cotonou',
        'email' => 'site.principal@example.com',
        'is_headquarter' => true,
        'is_active' => true,
    ]);
}

$subscription = EnterpriseSubscription::query()
    ->where('site_id', $site->id)
    ->where('offer_id', $offer->id)
    ->first();

if (!$subscription) {
    $subscription = EnterpriseSubscription::create([
        'offer_id' => $offer->id,
        'site_id' => $site->id,
        'start_date' => now()->subDay(),
        'expiration_date' => now()->addDays(30),
        'is_active' => true,
        'is_trial' => false,
        'status' => 'active',
        'payment_status' => 'completed',
    ]);
} else {
    $subscription->forceFill([
        'start_date' => now()->subDay(),
        'expiration_date' => now()->addDays(30),
        'is_active' => true,
        'is_trial' => false,
        'status' => 'active',
        'payment_status' => 'completed',
    ])->save();
}

$password = 'Password123!';

$users = [];
foreach ($roleNames as $roleName) {
    $email = $roleName . '@example.com';
    $user = User::query()->where('email', $email)->first();

    if (!$user) {
        $user = User::create([
            'name' => Str::of($roleName)->replace('_', ' ')->title()->toString(),
            'username' => $roleName,
            'email' => $email,
            'password' => Hash::make($password),
            'user_type' => User::TYPE_COMPANY,
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    } else {
        $user->forceFill([
            'user_type' => User::TYPE_COMPANY,
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
    }

    $user->syncRoles([$roleName]);
    $users[$roleName] = $user;
}

$accessCatalog = app(AccessCatalogService::class);
$report = [
    'generated_at' => nowIso(),
    'enterprise_id' => $enterprise->id,
    'site_id' => $site->id,
    'offer_id' => $offer->id,
    'credentials' => [
        'password' => $password,
        'users' => array_map(fn ($role) => [
            'role' => $role,
            'email' => $role . '@example.com',
        ], $roleNames),
    ],
    'roles' => [],
];

foreach ($users as $roleName => $user) {
    $catalog = $accessCatalog->buildCatalog($user, $site->id);

    $modules = collect($catalog['modules'] ?? [])
        ->filter(fn ($m) => (bool) ($m['permissions']['read'] ?? false))
        ->map(fn ($m) => [
            'code' => $m['code'],
            'name' => $m['name'],
            'permissions' => $m['permissions'],
        ])
        ->values()
        ->all();

    $subModules = collect($catalog['sub_modules'] ?? [])
        ->filter(fn ($m) => (bool) ($m['permissions']['read'] ?? false))
        ->map(fn ($m) => [
            'code' => $m['code'],
            'name' => $m['name'],
            'module_code' => $m['module_code'],
            'permissions' => $m['permissions'],
        ])
        ->values()
        ->all();

    $sections = collect($catalog['sections'] ?? [])
        ->filter(fn ($s) => (bool) ($s['permissions']['read'] ?? false))
        ->map(fn ($s) => [
            'code' => $s['code'],
            'name' => $s['name'],
            'module_code' => $s['module_code'],
            'sub_module_code' => $s['sub_module_code'],
            'permissions' => $s['permissions'],
        ])
        ->values()
        ->all();

    $report['roles'][$roleName] = [
        'modules' => $modules,
        'sub_modules' => $subModules,
        'sections' => $sections,
        'meta' => $catalog['meta'] ?? [],
    ];
}

$jsonPath = __DIR__ . '/../docs/ROLE_ACCESS_LIVE.json';
$mdPath = __DIR__ . '/../docs/ROLE_ACCESS_LIVE.md';

file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$lines = [];
$lines[] = '# Simulation d’accès par rôle (live)';
$lines[] = '';
$lines[] = 'Généré: ' . $report['generated_at'];
$lines[] = 'Site ID: ' . $site->id;
$lines[] = 'Offer ID: ' . $offer->id;
$lines[] = '';
$lines[] = '## Comptes de test';
$lines[] = '';
foreach ($report['credentials']['users'] as $cred) {
    $lines[] = "- {$cred['role']} : {$cred['email']} (password: {$password})";
}
$lines[] = '';

foreach ($report['roles'] as $roleName => $data) {
    $lines[] = "## {$roleName}";
    $lines[] = '';
    $lines[] = '### Modules (read)';
    if (empty($data['modules'])) {
        $lines[] = '- Aucun';
    } else {
        foreach ($data['modules'] as $m) {
            $lines[] = "- {$m['code']} — {$m['name']}";
        }
    }
    $lines[] = '';
    $lines[] = '### Sous-modules (read)';
    if (empty($data['sub_modules'])) {
        $lines[] = '- Aucun';
    } else {
        foreach ($data['sub_modules'] as $m) {
            $lines[] = "- {$m['module_code']} / {$m['code']} — {$m['name']}";
        }
    }
    $lines[] = '';
    $lines[] = '### Sections (read)';
    if (empty($data['sections'])) {
        $lines[] = '- Aucune';
    } else {
        foreach ($data['sections'] as $s) {
            $lines[] = "- {$s['module_code']} / {$s['sub_module_code']} / {$s['code']} — {$s['name']}";
        }
    }
    $lines[] = '';
}

file_put_contents($mdPath, implode(PHP_EOL, $lines));

echo "OK: Report written to docs/ROLE_ACCESS_LIVE.md and docs/ROLE_ACCESS_LIVE.json\n";
