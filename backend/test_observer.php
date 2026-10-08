<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test Observer - Création d'Action\n\n";

$user = App\Models\User::first();
$site = App\Models\Site::first();

if (!$site) {
    echo "❌ Aucun site trouvé\n";
    exit(1);
}

echo "📍 Site: " . $site->name . "\n";
echo "👤 User: " . $user->name . "\n\n";

$countBefore = App\Models\UserNotification::count();
echo "📊 Notifications avant: $countBefore\n";

try {
    $action = App\Models\Action::create([
        'title' => 'Test Action Notifications ' . now()->format('H:i:s'),
        'description' => 'Test observer',
        'site_id' => $site->id,
        'responsible_id' => $user->id,
        'deadline' => now()->addDays(7),
        'status' => 'pending',
        'priority' => 'medium'
    ]);
    
    echo "✅ Action créée: {$action->title} (ID: {$action->id})\n\n";
    
    sleep(1);
    $countAfter = App\Models\UserNotification::count();
    echo "📊 Notifications après: $countAfter\n";
    echo "📈 Nouvelles: " . ($countAfter - $countBefore) . "\n\n";
    
    if ($countAfter > $countBefore) {
        echo "🎉 SUCCESS! Observer ActionObserver fonctionne!\n";
        $latest = App\Models\UserNotification::latest()->first();
        echo "   Type: {$latest->type}\n";
        echo "   Data: " . json_encode($latest->data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️  Observer n'a pas créé de notification\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}
