<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test Observer - Création d'Audit\n\n";

$user = App\Models\User::first();
$site = App\Models\Site::first();

echo "📍 Site: " . $site->name . "\n";
echo "👤 User: " . $user->name . "\n\n";

$countBefore = App\Models\UserNotification::count();
echo "📊 Notifications avant: $countBefore\n";

try {
    $audit = App\Models\Audit::create([
        'title' => 'Audit Test Notifications ' . now()->format('H:i:s'),
        'audit_type' => 'internal',
        'scope' => 'Test scope',
        'site_id' => $site->id,
        'enterprise_id' => $site->enterprise_id,
        'lead_auditor_id' => $user->id,
        'status' => 'planned',
        'planned_start_date' => now()->addDays(7),
        'planned_end_date' => now()->addDays(14),
    ]);
    
    echo "✅ Audit créé: {$audit->title} (ID: {$audit->id})\n\n";
    
    sleep(1);
    $countAfter = App\Models\UserNotification::count();
    echo "📊 Notifications après: $countAfter\n";
    echo "📈 Nouvelles: " . ($countAfter - $countBefore) . "\n\n";
    
    if ($countAfter > $countBefore) {
        echo "🎉 SUCCESS! Observer AuditObserver fonctionne!\n\n";
        $latest = App\Models\UserNotification::latest()->first();
        echo "   📧 Type: {$latest->type}\n";
        echo "   👤 Pour: " . App\Models\User::find($latest->user_id)->name . "\n";
        echo "   📅 Créée: " . $latest->created_at . "\n";
        echo "   📖 Lu: " . ($latest->read_at ? 'Oui' : 'Non') . "\n\n";
        echo "   📋 Data: " . json_encode($latest->data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️  Observer n'a pas créé de notification\n";
        echo "   Vérifier que AuditObserver est bien enregistré dans ObserverServiceProvider\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
