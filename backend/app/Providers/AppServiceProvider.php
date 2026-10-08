<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use App\Models\NonConformity;
use App\Models\Audit;
use App\Models\AuditProgram;
use App\Models\AuditFinding;
use App\Models\Indicateur;
use App\Models\Objective;
use App\Models\Risk;
use App\Models\Reclamation;
use App\Models\Action;
use App\Models\PlanAction;
use App\Models\Site;
use App\Models\User;
use App\Models\EnterpriseSubscription;
use App\Models\Process;
use App\Models\Equipement;
use App\Models\Maintenance;
use App\Models\Habilitation;
use App\Models\Enterprise;
use App\Policies\EquipementPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\HabilitationPolicy;
use App\Policies\NonConformityPolicy;
use App\Policies\AuditPolicy;
use App\Policies\AuditProgramPolicy;
use App\Policies\AuditFindingPolicy;
use App\Policies\IndicateurPolicy;
use App\Policies\ObjectivePolicy;
use App\Policies\RiskPolicy;
use App\Policies\ReclamationPolicy;
use App\Policies\ActionPolicy;
use App\Policies\PlanActionPolicy;
use App\Policies\SitePolicy;
use App\Policies\UserPolicy;
use App\Policies\EnterpriseSubscriptionPolicy;
use App\Policies\ProcessPolicy;
use App\Policies\EnterprisePolicy;
use App\Policies\AssignPermissionPolicy;
use Spatie\Permission\Models\Permission as SpatiePermissionModel;
use Spatie\Permission\Models\Role as SpatieRoleModel;
use App\Observers\ProcessObserver;
use App\Services\Settings\SuperAdminSettingsService;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        NonConformity::class => NonConformityPolicy::class,
        Audit::class => AuditPolicy::class,
        AuditProgram::class => AuditProgramPolicy::class,
        AuditFinding::class => AuditFindingPolicy::class,
        Indicateur::class => IndicateurPolicy::class,
        Objective::class => ObjectivePolicy::class,
        Risk::class => RiskPolicy::class,
        Reclamation::class => ReclamationPolicy::class,
        Action::class => ActionPolicy::class,
        PlanAction::class => PlanActionPolicy::class,
        Site::class => SitePolicy::class,
        User::class => UserPolicy::class,
        EnterpriseSubscription::class => EnterpriseSubscriptionPolicy::class,
        Process::class => ProcessPolicy::class,
        Equipement::class => EquipementPolicy::class,
        Maintenance::class => MaintenancePolicy::class,
        Habilitation::class => HabilitationPolicy::class,
        Enterprise::class => EnterprisePolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sidebar LOGIQUALI : source unique config/navigation.php (sections réelles).
        View::composer(
            ['components.layout.sidebar', 'components.layout.sidebar-link', 'layouts.app'],
            \App\View\Composers\SidebarComposer::class
        );

        // Register Observers
        Process::observe(ProcessObserver::class);

        // Register Policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Permission assignment guardrail (norm-aware matrix validation)
        Gate::define('assign-role-permission', function (User $user, SpatiePermissionModel $permission, SpatieRoleModel $role, int $normId) {
            return app(AssignPermissionPolicy::class)->assign($user, $permission, $role, $normId);
        });

        // Configure Rate Limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API global - limite augmentée
        RateLimiter::for('api', function (Request $request) {
            if ($request->user()) {
                if ($this->isBurstSafeReadEndpoint($request)) {
                    return Limit::perMinute(2000)->by('user:' . $request->user()->id);
                }

                return Limit::perMinute(800)->by('user:' . $request->user()->id);
            }

            return Limit::perMinute(200)->by('ip:' . $request->ip());
        });

        // Login attempts - configurable via super admin settings
        RateLimiter::for('login', function (Request $request) {
            $settings = app(SuperAdminSettingsService::class)->getSecuritySettings();
            $maxAttempts = (int) ($settings['maxLoginAttempts'] ?? 10);
            if ($maxAttempts <= 0) {
                $maxAttempts = 10;
            }
            return Limit::perMinute($maxAttempts)->by($request->ip());
        });

        // Exports - 10 per hour
        RateLimiter::for('exports', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Search - 100 requests per minute
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        // File uploads - 20 per hour
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // Public evaluation form token endpoints
        RateLimiter::for('evaluation-public', function (Request $request) {
            $token = (string) ($request->route('token') ?? 'unknown');

            return [
                Limit::perMinute(60)->by('eval-public-ip:' . $request->ip()),
                Limit::perMinute(20)->by('eval-public-token:' . $token . ':' . $request->ip()),
            ];
        });

        // Security audit logs endpoints (sensitive data, dedicated budget)
        RateLimiter::for('security-audit', function (Request $request) {
            $actor = $request->user();
            $key = $actor ? 'user:' . $actor->id : 'ip:' . $request->ip();

            return Limit::perMinute(30)->by('security-audit:' . $key);
        });
    }

    private function isBurstSafeReadEndpoint(Request $request): bool
    {
        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        $routeOrPath = trim((string) ($request->route()?->uri() ?? $request->path()), '/');
        if ($routeOrPath === '') {
            return false;
        }

        $patterns = [
            'dashboard/stats',
            'dashboard/layout',
            'access/catalog',
            'subscription/status',
            'subscription/current',
            'available-roles',
            'available-permissions',
            'notifications',
            'auth/me',
            'sites',
            'users',
            'processes',
            'reclamations',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($routeOrPath, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
