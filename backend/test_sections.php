<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Récupérer un utilisateur admin
$user = App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'admin_entreprise');
})->first();

if (!$user) {
    echo "❌ Aucun admin trouvé\n";
    exit(1);
}

echo "👤 Utilisateur: {$user->name} (ID: {$user->id})\n";
echo "📍 Site: " . ($user->site ? $user->site->name : 'AUCUN') . "\n";
echo "🏢 Entreprise: " . ($user->enterprise ? $user->enterprise->name : 'AUCUNE') . "\n";

$site = $user->site;
if (!$site) {
    echo "❌ Utilisateur sans site\n";
    exit(1);
}

$subscriptions = $site->getActiveSubscriptions();
echo "📦 Subscriptions actives: {$subscriptions->count()}\n";

if ($subscriptions->isEmpty()) {
    echo "❌ Aucune subscription active\n";
    exit(1);
}

$allSections = collect();
foreach ($subscriptions as $subscription) {
    $sections = $subscription->getAccessibleSections();
    echo "  ✓ Subscription: {$subscription->id} → {$sections->count()} sections\n";
    $allSections = $allSections->merge($sections);
}

$allSections = $allSections->unique('id');
echo "\n📋 Total sections accessibles: {$allSections->count()}\n";

$leadershipSections = $allSections->filter(function($s) {
    return $s->subModule && $s->subModule->code === 'roles_responsabilites';
});

echo "\n🎯 Sections Leadership (roles_responsabilites): {$leadershipSections->count()}\n";
foreach ($leadershipSections as $section) {
    echo "  - {$section->name} ({$section->code}) → {$section->route}\n";
}
