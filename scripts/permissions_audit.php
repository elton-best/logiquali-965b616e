<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Permission;

require __DIR__ . '/../backend/vendor/autoload.php';

$app = require __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$root = realpath(__DIR__ . '/..');
$frontendRoot = $root . '/frontend/src';

function scanPermissions(string $root): array
{
    $perms = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($rii as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        if (!preg_match('/\.(vue|ts)$/', $path)) {
            continue;
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            continue;
        }
        if (preg_match_all('/permission[s]?\s*:\s*(\[[^\]]*\]|\'[^\']+\'|"[^"]+")/m', $content, $matches)) {
            foreach ($matches[1] as $blob) {
                if (str_starts_with($blob, '[')) {
                    if (preg_match_all('/\'([^\']+)\'|"([^"]+)"/', $blob, $sm)) {
                        foreach ($sm[1] as $idx => $val1) {
                            $perm = $val1 ?: $sm[2][$idx] ?? '';
                            if (strpos($perm, '.') !== false) {
                                $perms[$perm] = true;
                            }
                        }
                    }
                } else {
                    $perm = trim($blob, " \t\n\r\0\x0B'\"");
                    if (strpos($perm, '.') !== false) {
                        $perms[$perm] = true;
                    }
                }
            }
        }
    }
    return array_values(array_keys($perms));
}

$frontendPerms = scanPermissions($frontendRoot);
sort($frontendPerms);

$dbPerms = Permission::query()->pluck('name')->all();
sort($dbPerms);

$frontendSet = array_flip($frontendPerms);
$dbSet = array_flip($dbPerms);

$missingInDb = array_values(array_diff($frontendPerms, $dbPerms));
$unusedInFrontend = array_values(array_diff($dbPerms, $frontendPerms));

$report = [
    'generated_at' => now()->toISOString(),
    'frontend_permissions_count' => count($frontendPerms),
    'db_permissions_count' => count($dbPerms),
    'missing_in_db' => $missingInDb,
    'unused_in_frontend' => $unusedInFrontend,
];

file_put_contents($root . '/docs/PERMISSIONS_AUDIT.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$lines = [];
$lines[] = '# Audit Permissions (Frontend vs DB)';
$lines[] = '';
$lines[] = 'Generated: ' . $report['generated_at'];
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = '- Frontend permissions: ' . $report['frontend_permissions_count'];
$lines[] = '- DB permissions: ' . $report['db_permissions_count'];
$lines[] = '- Missing in DB: ' . count($missingInDb);
$lines[] = '- Unused in frontend: ' . count($unusedInFrontend);
$lines[] = '';
$lines[] = '## Missing in DB';
$lines[] = '';
if (count($missingInDb) === 0) {
    $lines[] = '- None';
} else {
    foreach ($missingInDb as $perm) {
        $lines[] = '- ' . $perm;
    }
}
$lines[] = '';
$lines[] = '## Unused in Frontend';
$lines[] = '';
if (count($unusedInFrontend) === 0) {
    $lines[] = '- None';
} else {
    foreach ($unusedInFrontend as $perm) {
        $lines[] = '- ' . $perm;
    }
}

file_put_contents($root . '/docs/PERMISSIONS_AUDIT.md', implode(PHP_EOL, $lines));

echo "OK: docs/PERMISSIONS_AUDIT.md and docs/PERMISSIONS_AUDIT.json\n";
