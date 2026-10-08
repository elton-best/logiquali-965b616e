<?php

namespace App\Providers;

use App\Models\Action;
use App\Models\ApplicationScope;
use App\Models\Audit;
use App\Models\CalibrationPlan;
use App\Models\Communication;
use App\Models\ComplianceObligationAction;
use App\Models\Document;
use App\Models\Enterprise;
use App\Models\Formation;
use App\Models\MaintenancePlan;
use App\Models\NonConformity;
use App\Models\Objective;
use App\Models\OperationalProjectActivity;
use App\Models\OperationalProjectTask;
use App\Models\Opportunity;
use App\Models\PlanAction;
use App\Models\Process;
use App\Models\ProcessRiskOpportunity;
use App\Models\Risk;
use App\Models\Stakeholder;
use App\Models\StakeholderRequirementAction;
use App\Models\StrategicAxis;
use App\Models\TaskTracking;
use App\Models\User;
use App\Observers\ActionObserver;
use App\Observers\ApplicationScopeObserver;
use App\Observers\AuditObserver;
use App\Observers\CalibrationPlanObserver;
use App\Observers\CommunicationObserver;
use App\Observers\ComplianceObligationActionObserver;
use App\Observers\DocumentObserver;
use App\Observers\EnterpriseObserver;
use App\Observers\FormationObserver;
use App\Observers\MaintenancePlanObserver;
use App\Observers\NonConformityObserver;
use App\Observers\ObjectiveObserver;
use App\Observers\OperationalProjectActivityObserver;
use App\Observers\OperationalProjectTaskObserver;
use App\Observers\OpportunityObserver;
use App\Observers\PlanActionObserver;
use App\Observers\ProcessCatalogSyncObserver;
use App\Observers\ProcessRiskOpportunityObserver;
use App\Observers\RiskObserver;
use App\Observers\StakeholderObserver;
use App\Observers\StakeholderRequirementActionObserver;
use App\Observers\StrategicAxisObserver;
use App\Observers\TaskTrackingObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Action::observe(ActionObserver::class);
        ApplicationScope::observe(ApplicationScopeObserver::class);
        Audit::observe(AuditObserver::class);
        CalibrationPlan::observe(CalibrationPlanObserver::class);
        Communication::observe(CommunicationObserver::class);
        ComplianceObligationAction::observe(ComplianceObligationActionObserver::class);
        Document::observe(DocumentObserver::class);
        Enterprise::observe(EnterpriseObserver::class);
        Formation::observe(FormationObserver::class);
        MaintenancePlan::observe(MaintenancePlanObserver::class);
        NonConformity::observe(NonConformityObserver::class);
        Objective::observe(ObjectiveObserver::class);
        OperationalProjectActivity::observe(OperationalProjectActivityObserver::class);
        OperationalProjectTask::observe(OperationalProjectTaskObserver::class);
        Opportunity::observe(OpportunityObserver::class);
        PlanAction::observe(PlanActionObserver::class);
        Process::observe(ProcessCatalogSyncObserver::class);
        ProcessRiskOpportunity::observe(ProcessRiskOpportunityObserver::class);
        Risk::observe(RiskObserver::class);
        Stakeholder::observe(StakeholderObserver::class);
        StakeholderRequirementAction::observe(StakeholderRequirementActionObserver::class);
        StrategicAxis::observe(StrategicAxisObserver::class);
        TaskTracking::observe(TaskTrackingObserver::class);
        User::observe(UserObserver::class);
    }
}
