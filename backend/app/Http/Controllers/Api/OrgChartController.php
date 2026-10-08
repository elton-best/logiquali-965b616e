<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrgChart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Utils\ExportHeaders;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrgChartController extends Controller
{
    public function __construct()
    {
        $read = 'permission:leadership.roles_responsabilites.organigramme.read';

        $create = 'permission:leadership.roles_responsabilites.organigramme.create'
            . '|leadership.roles_responsabilites.organigramme.update';

        $delete = 'permission:leadership.roles_responsabilites.organigramme.delete';

        $this->middleware($read)->only(['current']);
        $this->middleware($create)->only(['upload']);
        $this->middleware($delete)->only(['destroy']);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'site_id' => 'nullable|integer|exists:sites,id',
        ]);

        $user = $request->user();
        $siteId = $request->integer('site_id') ?: $user?->site_id;

        if ($siteId) {
            $siteBelongsToEnterprise = \App\Models\Site::query()
                ->whereKey($siteId)
                ->where('enterprise_id', $user?->enterprise_id)
                ->exists();

            if (!$siteBelongsToEnterprise) {
                return response()->json([
                    'message' => 'Site invalide pour votre entreprise.',
                ], 422);
            }
        }

        $file = $request->file('file');
        $path = $file->store('org-charts', 'public');

        // Marquer les anciens comme non courants pour le même site.
        $markCurrentQuery = OrgChart::query()
            ->where('enterprise_id', $user?->enterprise_id);

        if ($siteId) {
            $markCurrentQuery->where('site_id', $siteId);
        } else {
            $markCurrentQuery->whereNull('site_id');
        }

        $markCurrentQuery->update(['is_current' => false]);

        $orgChart = OrgChart::create([
            'enterprise_id' => $user?->enterprise_id,
            'site_id' => $siteId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_current' => true,
            'uploaded_by' => $user?->id,
        ]);

        $orgChart->download_url = $this->temporarySignedViewUrl($orgChart);

        return response()->json($orgChart, 201);
    }

    public function current(Request $request)
    {
        $user = $request->user();
        $siteId = $request->integer('site_id') ?: $user?->site_id;

        $query = OrgChart::query()
            ->where('enterprise_id', $user?->enterprise_id)
            ->where('is_current', true)
            ->latest('id');

        if ($siteId) {
            $query->where('site_id', $siteId);
        } else {
            $query->whereNull('site_id');
        }

        $orgChart = $query->first();

        if ($orgChart) {
            $orgChart->download_url = $this->temporarySignedViewUrl($orgChart);
        }

        return response()->json($orgChart);
    }

    public function viewSigned(Request $request, OrgChart $orgChart): BinaryFileResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Lien expiré ou invalide.');
        }

        if (!Storage::disk('public')->exists($orgChart->file_path)) {
            abort(404, 'Document introuvable.');
        }

        return response()->file(
            Storage::disk('public')->path($orgChart->file_path),
            ExportHeaders::attachmentHeaders((string) $orgChart->file_name, (string) $orgChart->file_type, true) + [
                'Cache-Control' => 'private, max-age=300, must-revalidate',
            ],
        );
    }

    public function destroy(int $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser instanceof User) {
            abort(401, 'Unauthorized');
        }

        $orgChart = OrgChart::where('enterprise_id', $currentUser->enterprise_id)
            ->findOrFail($id);

        Storage::disk('public')->delete($orgChart->file_path);
        $orgChart->delete();

        return response()->json(null, 204);
    }

    private function temporarySignedViewUrl(OrgChart $orgChart): string
    {
        return URL::temporarySignedRoute(
            'org-chart.signed-view',
            now()->addMinutes(10),
            ['orgChart' => $orgChart->id],
        );
    }
}
