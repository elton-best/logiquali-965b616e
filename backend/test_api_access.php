<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get user
$user = App\Models\User::where('email', 'kpessouley@gmail.com')->first();

echo "=== User Info ===" . PHP_EOL;
echo "ID: {$user->id}" . PHP_EOL;
echo "Type: {$user->user_type}" . PHP_EOL;
echo "Enterprise ID: {$user->enterprise_id}" . PHP_EOL;
echo PHP_EOL;

// Test Policy directly
echo "=== Testing Policies Directly ===" . PHP_EOL;

try {
    $auditPolicy = new App\Policies\AuditPolicy();
    $canViewAny = $auditPolicy->viewAny($user);
    echo "AuditPolicy::viewAny() => " . ($canViewAny ? 'YES' : 'NO') . PHP_EOL;
    
    $canCreate = $auditPolicy->create($user);
    echo "AuditPolicy::create() => " . ($canCreate ? 'YES' : 'NO') . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test NC Policy
try {
    $ncPolicy = new App\Policies\NonConformityPolicy();
    $canViewAny = $ncPolicy->viewAny($user);
    echo "NonConformityPolicy::viewAny() => " . ($canViewAny ? 'YES' : 'NO') . PHP_EOL;
    
    $canCreate = $ncPolicy->create($user);
    echo "NonConformityPolicy::create() => " . ($canCreate ? 'YES' : 'NO') . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test Process Policy
try {
    $processPolicy = new App\Policies\ProcessPolicy();
    $canViewAny = $processPolicy->viewAny($user);
    echo "ProcessPolicy::viewAny() => " . ($canViewAny ? 'YES' : 'NO') . PHP_EOL;
    
    $canCreate = $processPolicy->create($user);
    echo "ProcessPolicy::create() => " . ($canCreate ? 'YES' : 'NO') . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

