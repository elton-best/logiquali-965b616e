<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminSetting;
use App\Services\Settings\SuperAdminSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SuperAdminSettingsController extends Controller
{
    public function show(Request $request)
    {
        $service = app(SuperAdminSettingsService::class);
        $settings = $this->getOrCreateSettings($service, $request->user()?->id);

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $settings->general,
                'email' => $service->maskEmailSettings($settings->email ?? []),
                'security' => $settings->security,
                'system' => $settings->system,
                'system_info' => $this->systemInfo(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $service = app(SuperAdminSettingsService::class);
        $validated = $request->validate([
            'general' => 'sometimes|array',
            'email' => 'sometimes|array',
            'security' => 'sometimes|array',
            'system' => 'sometimes|array',
        ]);

        $settings = $this->getOrCreateSettings($service, $request->user()?->id);

        if (array_key_exists('general', $validated)) {
            $settings->general = $service->mergeSettings($settings->general, $validated['general']);
        }

        if (array_key_exists('email', $validated)) {
            $settings->email = $service->mergeEmailSettings($settings->email, $validated['email']);
        }

        if (array_key_exists('security', $validated)) {
            $incomingSecurity = $validated['security'];
            if (array_key_exists('sessionTimeoutMinutes', $incomingSecurity)) {
                $incomingSecurity['sessionTimeoutMinutes'] = max(
                    1,
                    min((int) $incomingSecurity['sessionTimeoutMinutes'], 480)
                );
            }

            $settings->security = $service->mergeSettings($settings->security, $incomingSecurity);
        }

        if (array_key_exists('system', $validated)) {
            $settings->system = $service->mergeSettings($settings->system, $validated['system']);
        }

        $settings->updated_by = $request->user()?->id;
        $settings->save();
        $service->clearSecurityCache();

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $settings->general,
                'email' => $service->maskEmailSettings($settings->email ?? []),
                'security' => $settings->security,
                'system' => $settings->system,
                'system_info' => $this->systemInfo(),
            ],
        ]);
    }

    public function testEmail(Request $request)
    {
        $settings = $this->getOrCreateSettings(app(SuperAdminSettingsService::class), $request->user()?->id);
        $email = $settings->general['supportEmail'] ?? $request->user()?->email;

        if (!$email) {
            return response()->json(['message' => 'Email de support indisponible.'], 422);
        }

        Mail::raw('Test de configuration email BestQHSE.', function ($message) use ($email) {
            $message->to($email)
                ->subject('Test configuration email');
        });

        return response()->json(['success' => true, 'message' => 'Email de test envoye.']);
    }

    private function getOrCreateSettings(SuperAdminSettingsService $service, ?int $userId): SuperAdminSetting
    {
        $settings = SuperAdminSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return SuperAdminSetting::create(array_merge($service->defaultSettings(), [
            'updated_by' => $userId,
        ]));
    }

    private function systemInfo(): array
    {
        return [
            'version' => config('app.version', 'v2.4.1'),
            'database' => config('database.default', 'pgsql'),
            'server' => php_uname('s') . ' ' . php_uname('r'),
            'lastBackup' => null,
        ];
    }
}
