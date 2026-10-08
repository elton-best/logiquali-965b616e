<?php

namespace App\Services\Payment;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BegPayApiService
{
    private const TOKEN_CACHE_KEY = 'begpay.oauth.access_token';
    private const TOKEN_TTL_FALLBACK = 3300; // 55 minutes

    public function requestPayment(array $payload): array
    {
        $response = $this->apiClient()->post('/api/v1/payment/request', $payload);
        return $this->decodeResponse($response, 'Erreur lors de la demande de paiement BegPay');
    }

    public function paymentStatus(string $referenceId): array
    {
        $response = $this->apiClient()->get("/api/v1/payment/request/{$referenceId}");
        return $this->decodeResponse($response, 'Erreur lors de la vérification du statut BegPay');
    }

    public function sendOtp(string $number, string $indicatif): array
    {
        $response = $this->apiClient()->post('/api/v1/payment/sendOtp', [
            'number' => $number,
            'indicatif' => $indicatif,
        ]);

        return $this->decodeResponse($response, 'Erreur lors de l’envoi OTP BegPay');
    }

    private function apiClient()
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->withToken($this->accessToken())
            ->withHeaders([
                'X-Subscription-Key' => $this->subscriptionKey(),
            ])
            ->timeout(30);
    }

    private function accessToken(): string
    {
        /** @var string|null $cached */
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        $response = $this->requestOAuthToken();
        $payload = $this->decodeResponse($response, 'Impossible d’obtenir le token OAuth BegPay');

        $token = $this->extractAccessToken($payload);
        if ($token === '') {
            Log::warning('BegPay OAuth payload without access token', [
                'keys' => array_keys($payload),
                'oauth_url' => $this->oauthUrl(),
            ]);
            throw new RuntimeException('Réponse OAuth BegPay invalide: access_token manquant.');
        }

        $expiresIn = $this->extractTokenTtl($payload);
        $ttl = max(60, $expiresIn - 60);
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function requestOAuthToken(): Response
    {
        $oauthUrl = $this->oauthUrl();
        $base = $this->oauthPayload();

        // 1) Standard OAuth2 form payload
        $formResponse = Http::asForm()
            ->acceptJson()
            ->withHeaders([
                'X-Subscription-Key' => $this->subscriptionKey(),
            ])
            ->timeout(20)
            ->post($oauthUrl, $base);

        if ($formResponse->successful()) {
            return $formResponse;
        }

        // 2) Fallback: some providers expect JSON payload
        return Http::acceptJson()
            ->withHeaders([
                'X-Subscription-Key' => $this->subscriptionKey(),
            ])
            ->timeout(20)
            ->post($oauthUrl, $base);
    }

    private function oauthPayload(): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.begpay.client_id'),
            'client_secret' => config('services.begpay.client_secret'),
            'scope' => '*',
        ];
    }

    private function extractAccessToken(array $payload): string
    {
        $candidates = [
            data_get($payload, 'access_token'),
            data_get($payload, 'accessToken'),
            data_get($payload, 'token'),
            data_get($payload, 'jwt'),
            data_get($payload, 'data.access_token'),
            data_get($payload, 'data.accessToken'),
            data_get($payload, 'data.token'),
            data_get($payload, 'result.access_token'),
            data_get($payload, 'result.token'),
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) ($candidate ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function extractTokenTtl(array $payload): int
    {
        $candidates = [
            data_get($payload, 'expires_in'),
            data_get($payload, 'expiresIn'),
            data_get($payload, 'data.expires_in'),
            data_get($payload, 'data.expiresIn'),
            data_get($payload, 'result.expires_in'),
            data_get($payload, 'result.expiresIn'),
        ];

        foreach ($candidates as $candidate) {
            $seconds = (int) $candidate;
            if ($seconds > 0) {
                return $seconds;
            }
        }

        return self::TOKEN_TTL_FALLBACK;
    }

    private function decodeResponse(Response $response, string $context): array
    {
        if ($response->failed()) {
            $body = $response->json();
            $message = is_array($body)
                ? (string) ($body['message'] ?? $body['error_description'] ?? $body['error'] ?? '')
                : trim((string) $response->body());

            throw new RuntimeException($context . ($message !== '' ? ": {$message}" : ''));
        }

        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }

        $rawBody = trim((string) $response->body());
        if ($rawBody !== '') {
            $snippet = mb_substr($rawBody, 0, 240);
            throw new RuntimeException($context . ': réponse non JSON du fournisseur (' . $snippet . ')');
        }

        return [];
    }

    private function baseUrl(): string
    {
        $value = (string) config('services.begpay.base_url', '');
        if ($value === '') {
            throw new RuntimeException('Configuration BegPay manquante: services.begpay.base_url');
        }

        return rtrim($value, '/');
    }

    private function oauthUrl(): string
    {
        $oauthUrl = (string) config('services.begpay.oauth_url', '');
        if ($oauthUrl !== '') {
            return $oauthUrl;
        }

        return $this->baseUrl() . '/api/oauth/token';
    }

    private function subscriptionKey(): string
    {
        $value = (string) config('services.begpay.subscription_key', '');
        if ($value === '') {
            throw new RuntimeException('Configuration BegPay manquante: services.begpay.subscription_key');
        }

        return $value;
    }
}
