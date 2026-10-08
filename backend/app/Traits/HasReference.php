<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasReference
{
    protected static function bootHasReference()
    {
        static::creating(function ($model) {
            if (empty($model->ref)) {
                $model->ref = $model->generateReference();
            }
        });
    }

    public function generateReference(): string
    {
        $prefix = $this->getReferencePrefix();
        $year = date('Y');
        $query = $this->referenceQuery();
        
        // Chercher la dernière référence avec ce préfixe et cette année
        $lastRecord = (clone $query)
            ->where('ref', 'like', "{$prefix}-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastRecord && $lastRecord->ref) {
            // Extraire le numéro de séquence de la dernière référence
            $parts = explode('-', $lastRecord->ref);
            if (count($parts) === 3) {
                $sequence = ((int) $parts[2]) + 1;
            }
        }
        
        // Vérifier que la référence n'existe pas déjà (sécurité)
        do {
            $ref = sprintf('%s-%s-%03d', $prefix, $year, $sequence);
            $exists = (clone $query)->where('ref', $ref)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists && $sequence < 9999);
        
        return $ref;
    }

    protected function getReferencePrefix(): string
    {
        $tableName = $this->getTable();
        
        $prefixes = [
            'users' => 'USER',
            'enterprises' => 'ENT',
            'sites' => 'SITE',
            'norms' => 'NORM',
            'articles' => 'ART',
            'complaints' => 'COMP',
            'reclamations' => 'REC',
            'offers' => 'OFF',
            'enterprise_subscriptions' => 'SUB',
            'processes' => 'PROC',
            'activities' => 'ACT',
            'risks' => 'RISK',
            'opportunities' => 'OPP',
            'objectives' => 'OBJ',
            'actions' => 'ACN',
            'audits' => 'AUD',
            'non_conformities' => 'NC',
            'stakeholders' => 'STK',
            'contexts' => 'CTX',
            'dashboards' => 'DASH',
            'management_reviews' => 'MR',
            'satisfaction_surveys' => 'SAT',
            'documents' => 'DOC',
            'modifications' => 'MOD',
            'strategic_axes' => 'STAX',
            'plans' => 'PLAN',
            'permissions' => 'PERM',
            'roles' => 'ROLE',
            'job_descriptions' => 'JOB',
            'responsibilities' => 'RESP',
            'team_members' => 'TEAM',
            'process_resources' => 'RES',
            'process_interactions' => 'INT',
            'employee_evaluations' => 'EVAL',
            'applicable_requirements' => 'REQ',
            'client_satisfaction_forms' => 'FS',
            'improvement_suggestions' => 'SUG',
        ];
        
        return $prefixes[$tableName] ?? 'REF';
    }

    private function referenceQuery(): Builder
    {
        $query = static::query();

        if (method_exists(static::class, 'scopeWithoutEnterpriseScope')) {
            $query = $query->withoutEnterpriseScope();
        }

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        return $query;
    }
}
