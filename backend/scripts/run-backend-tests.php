<?php

declare(strict_types=1);

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function err(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
}

function loadEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function envValue(string $name, array $env, string $default = ''): string
{
    $fromRuntime = getenv($name);
    if ($fromRuntime !== false && $fromRuntime !== '') {
        return $fromRuntime;
    }

    return $env[$name] ?? $default;
}

function run(string $command): void
{
    out("-> {$command}");
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        err("Command failed with exit code {$exitCode}");
        exit($exitCode);
    }
}

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    err('Unable to resolve backend root path.');
    exit(1);
}
chdir($projectRoot);

$envPath = $projectRoot . '/.env.testing';
$env = loadEnvFile($envPath);

if ($env === []) {
    err('.env.testing is missing or empty. Create backend/.env.testing before running tests.');
    exit(1);
}

$host = envValue('DB_HOST', $env, '127.0.0.1');
$port = envValue('DB_PORT', $env, '5432');
$database = envValue('DB_DATABASE', $env, 'BestQHSE_test');
$username = envValue('DB_USERNAME', $env, 'postgres');
$password = envValue('DB_PASSWORD', $env, '');

out('Checking PostgreSQL test connectivity...');
try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname=postgres",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $checkStmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = :db LIMIT 1');
    $checkStmt->execute(['db' => $database]);
    $exists = (bool) $checkStmt->fetchColumn();

    if (!$exists) {
        out("Test database '{$database}' not found. Creating it now...");
        $safeDbName = str_replace('"', '""', $database);
        $pdo->exec("CREATE DATABASE \"{$safeDbName}\"");
        out("Database '{$database}' created.");
    } else {
        out("Database '{$database}' is available.");
    }
} catch (Throwable $e) {
    err('Unable to connect to PostgreSQL for tests.');
    err("Connection target: host={$host} port={$port} db=postgres user={$username}");
    err($e->getMessage());
    exit(1);
}

$extraArgs = array_slice($argv, 1);
$argString = '';
if ($extraArgs !== []) {
    $argString = ' ' . implode(' ', array_map('escapeshellarg', $extraArgs));
}

run('php artisan config:clear --env=testing --ansi');
run('php artisan migrate:fresh --seed --env=testing --force --ansi');
run('php artisan test --env=testing' . $argString);
