<?php

namespace App\Services\Supervision;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SDK pour reporter l'activité des work sessions vers Supervision.
 */
class WorkSessionReporter
{
    protected string $supervisionUrl;
    protected string $apiToken;
    protected string $projectCode;

    public function __construct()
    {
        $this->supervisionUrl = rtrim(config('services.supervision.url', env('SUPERVISION_ENDPOINT', 'http://localhost:8000')), '/');
        $this->apiToken = config('services.supervision.api_token', env('SUPERVISION_API_TOKEN'));
        $this->projectCode = config('services.supervision.project_code', env('SUPERVISION_PROJECT_CODE', 'BESTQHSE'));

        if (! $this->apiToken) {
            Log::warning('[WorkSessionReporter] SUPERVISION_API_TOKEN non configuré');
        }
    }

    public function createSession(string $userEmail, ?string $sourceIp = null, ?string $userAgent = null): ?int
    {
        if (! $this->apiToken) {
            return null;
        }

        try {
            $signature = $this->generateSignatureForCreate();

            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->projectCode}:{$signature['timestamp']}:{$signature['signature']}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->supervisionUrl}/api/v1/ingestion/work-sessions/create", [
                    'user_email' => $userEmail,
                    'project_code' => $this->projectCode,
                    'source_ip' => $sourceIp,
                    'user_agent' => $userAgent,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['session_id'];
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('[WorkSessionReporter] Erreur création session', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function heartbeat(int $sessionId, string $source = 'backend'): bool
    {
        if (! $this->apiToken) {
            return false;
        }

        try {
            $signature = $this->generateSignature($sessionId);

            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->projectCode}:{$signature['timestamp']}:{$signature['signature']}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->supervisionUrl}/api/v1/ingestion/work-sessions/{$sessionId}/heartbeat", [
                    'source' => $source,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[WorkSessionReporter] Erreur heartbeat', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function end(int $sessionId, string $reason = 'logout'): bool
    {
        if (! $this->apiToken) {
            return false;
        }

        try {
            $signature = $this->generateSignature($sessionId);

            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->projectCode}:{$signature['timestamp']}:{$signature['signature']}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->supervisionUrl}/api/v1/ingestion/work-sessions/{$sessionId}/end", [
                    'reason' => $reason,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[WorkSessionReporter] Erreur end session', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function generateSignature(int $sessionId): array
    {
        $timestamp = time();
        $payload = "{$this->projectCode}:{$timestamp}:{$sessionId}";
        $signature = hash_hmac('sha256', $payload, $this->apiToken);

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    protected function generateSignatureForCreate(): array
    {
        $timestamp = time();
        $payload = "{$this->projectCode}:{$timestamp}:create";
        $signature = hash_hmac('sha256', $payload, $this->apiToken);

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    public static function createSessionFor(string $userEmail, ?string $sourceIp = null, ?string $userAgent = null): ?int
    {
        return (new static)->createSession($userEmail, $sourceIp, $userAgent);
    }

    public static function sendHeartbeat(?int $sessionId, string $source = 'backend'): bool
    {
        if (! $sessionId) {
            return false;
        }

        return (new static)->heartbeat($sessionId, $source);
    }

    public static function endSession(?int $sessionId, string $reason = 'logout'): bool
    {
        if (! $sessionId) {
            return false;
        }

        return (new static)->end($sessionId, $reason);
    }
}
