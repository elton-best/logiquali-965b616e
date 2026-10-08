<?php

namespace App\Services\Settings;

use App\Models\SuperAdminSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SuperAdminSettingsService
{
    private const CACHE_KEY_SECURITY = 'superadmin_settings_security';
    private const CACHE_TTL_SECONDS = 300;

    public function getSettings(): SuperAdminSetting
    {
        $settings = SuperAdminSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return SuperAdminSetting::create($this->defaultSettings());
    }

    public function mergeSettings(?array $existing, array $incoming): array
    {
        return array_merge($existing ?? [], $incoming);
    }

    public function mergeEmailSettings(?array $existing, array $incoming): array
    {
        $current = $existing ?? [];
        $password = $incoming['smtpPassword'] ?? null;

        if (is_string($password) && trim($password) !== '' && $password !== '********') {
            $incoming['smtpPassword'] = Crypt::encryptString($password);
        } else {
            unset($incoming['smtpPassword']);
        }

        return array_merge($current, $incoming);
    }

    public function maskEmailSettings(array $email): array
    {
        $masked = $email;
        if (!empty($masked['smtpPassword'])) {
            $masked['smtpPassword'] = '********';
        }
        return $masked;
    }

    public function getSecuritySettings(): array
    {
        return Cache::remember(self::CACHE_KEY_SECURITY, self::CACHE_TTL_SECONDS, function () {
            $settings = SuperAdminSetting::query()->first();
            $security = $settings?->security ?? [];
            return array_merge($this->defaultSecurity(), $security);
        });
    }

    public function clearSecurityCache(): void
    {
        Cache::forget(self::CACHE_KEY_SECURITY);
    }

    public function defaultSettings(): array
    {
        return [
            'general' => $this->defaultGeneral(),
            'email' => $this->defaultEmail(),
            'security' => $this->defaultSecurity(),
            'system' => $this->defaultSystem(),
        ];
    }

    public function defaultGeneral(): array
    {
        return [
            'platformName' => 'BestQHSE',
            'platformDescription' => 'Plateforme de gestion de la qualite et des normes ISO',
            'supportEmail' => 'support@BestQHSE.com',
            'supportPhone' => '+237 690 000 000',
            'defaultLanguage' => 'Francais',
            'defaultCurrency' => 'FCFA',
            'timezone' => 'Africa/Douala',
        ];
    }

    public function defaultEmail(): array
    {
        return [
            'smtpHost' => 'smtp.gmail.com',
            'smtpPort' => 587,
            'smtpUser' => 'noreply@BestQHSE.com',
            'smtpPassword' => null,
            'encryption' => 'TLS',
        ];
    }

    public function defaultSecurity(): array
    {
        return [
            'enforceStrongPassword' => true,
            'enable2FA' => true,
            'sessionTimeout' => true,
            'sessionTimeoutMinutes' => 30,
            'maxLoginAttempts' => 5,
        ];
    }

    public function defaultSystem(): array
    {
        return [
            'maintenanceMode' => false,
            'maintenanceMessage' => 'Le systeme est en maintenance. Veuillez reessayer plus tard.',
        ];
    }

    public function passwordRules(bool $required = true): array
    {
        $settings = $this->getSecuritySettings();
        $enforceStrong = (bool) ($settings['enforceStrongPassword'] ?? false);

        $rules = [$required ? 'required' : 'sometimes', 'string', 'min:8', 'confirmed'];

        if ($enforceStrong) {
            $rules[] = 'regex:/[A-Z]/';
            $rules[] = 'regex:/[a-z]/';
            $rules[] = 'regex:/[0-9]/';
        }

        return $rules;
    }
}
