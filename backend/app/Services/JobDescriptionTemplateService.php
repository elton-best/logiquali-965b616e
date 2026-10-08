<?php

namespace App\Services;

use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class JobDescriptionTemplateService
{
    /**
     * Générer le template Excel pour import de fiches de poste.
     */
    public function generateTemplate(int $enterpriseId): array
    {
        $filename = 'modele_fiches_poste_' . now()->format('Ymd_His') . '.xlsx';
        $relativePath = 'exports/' . $filename;

        Excel::store(
            new \App\Exports\JobDescriptionTemplateExport($enterpriseId),
            $relativePath,
            'local'
        );

        return [
            'filename' => $filename,
            'path' => Storage::disk('local')->path($relativePath),
        ];
    }

    /**
     * Récupérer les collaborateurs de l'entreprise pour la feuille d'aide.
     */
    public function getEnterpriseCollaborators(int $enterpriseId): array
    {
        return User::query()
            ->where('enterprise_id', $enterpriseId)
            ->select('id', 'last_name', 'first_name', 'email')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'nom' => $user->last_name,
                'prenom' => $user->first_name,
                'email' => $user->email,
            ])
            ->toArray();
    }
}
