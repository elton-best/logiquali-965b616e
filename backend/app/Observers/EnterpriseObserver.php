<?php

namespace App\Observers;

use App\Models\Enterprise;
use App\Services\DocumentTypeConfigurationBootstrapService;
use Illuminate\Support\Facades\Log;

class EnterpriseObserver
{
    public function created(Enterprise $enterprise): void
    {
        try {
            app(DocumentTypeConfigurationBootstrapService::class)
                ->initializeForEnterprise($enterprise);
        } catch (\Throwable $e) {
            Log::error('Echec bootstrap configurations documentaires entreprise', [
                'enterprise_id' => $enterprise->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
