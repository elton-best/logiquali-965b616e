<?php

namespace App\Modules\Support\Controllers;

use App\Models\Action;
use App\Models\CodificationElement;
use App\Models\Maintenance;

use App\Models\Equipement;
use App\Models\EquipementTransferHistory;
use App\Models\TransferReasonCode;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class EquipementTransferController extends Controller
{
    private const DEFAULT_TRANSFER_REASONS = [
        [
            'code' => 'maintenance',
            'name' => 'Maintenance',
            'description' => 'Transfert pour maintenance préventive ou corrective',
        ],
        [
            'code' => 'repair',
            'name' => 'Réparation',
            'description' => 'Transfert pour réparation de l\'équipement',
        ],
        [
            'code' => 'consolidation',
            'name' => 'Consolidation',
            'description' => 'Consolidation d\'équipements ou regroupement',
        ],
        [
            'code' => 'transfer_site',
            'name' => 'Transfert de site',
            'description' => 'Transfert d\'un site à un autre',
        ],
        [
            'code' => 'acquisition',
            'name' => 'Acquisition',
            'description' => 'Nouvel équipement acquis',
        ],
        [
            'code' => 'relocation',
            'name' => 'Relocalisation',
            'description' => 'Relocalisation interne ou déménagement',
        ],
        [
            'code' => 'obsolescence',
            'name' => 'Obsolescence',
            'description' => 'Transfert lié à l\'obsolescence ou mise au rebut',
        ],
        [
            'code' => 'other',
            'name' => 'Autre',
            'description' => 'Autre raison de transfert',
        ],
    ];

    public function __construct()
    {
        $this->middleware('permission:equipements.transfer|equipements.update|support.ressources.transfer|support.ressources.update')->only(['store', 'verify']);
        $this->middleware('permission:equipements.read|support.ressources.read')->only(['index', 'show', 'equipmentTransfers', 'getTransferReasons']);
    }

    /**
     * Task 2.1.1: List transfer history for an equipment or enterprise
     * GET /api/v1/equipements/{id}/transfers
     * GET /api/v1/transfers?enterprise_id=...&status=...
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enterprise_id' => 'required|integer|exists:enterprises,id',
            'equipement_id' => 'nullable|integer|exists:equipements,id',
            'status' => 'nullable|string|in:pending,verified,all',
            'reason_code' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            $this->logValidationFailure('transfer.index', $request, $validator->errors()->toArray());
            return $this->invalidParametersResponse();
        }

        $enterpriseId = $request->input('enterprise_id');
        if (!$this->canAccessEnterprise($request, (int) $enterpriseId)) {
            return $this->forbiddenResponse();
        }

        $equipementId = $request->input('equipement_id');
        $status = $request->input('status', 'all');
        $reasonCode = $request->input('reason_code');
        $perPage = $request->input('per_page', 20);

        $query = EquipementTransferHistory::query()
            ->where('enterprise_id', $enterpriseId)
            ->with([
                'equipement:id,code_complet,nom_commun,enterprise_id',
                'previousSite:id,name',
                'newSite:id,name',
                'previousLocalisation:id,code,libelle',
                'newLocalisation:id,code,libelle',
                'transferReason:id,code,name',
                'transferredByUser:id,name,email',
                'verifiedByUser:id,name,email',
            ]);

        if ($equipementId) {
            $query->where('equipement_id', $equipementId);
        }

        if ($status === 'pending') {
            $query->where('is_verified', false);
        } elseif ($status === 'verified') {
            $query->where('is_verified', true);
        }

        if ($reasonCode) {
            $query->where('transfer_reason_code', $reasonCode);
        }

        $transfers = $query->orderBy('transferred_at', 'desc')
            ->paginate($perPage);

        return response()->json($transfers);
    }

    /**
     * Task 2.1.1: Get transfer history for specific equipment
     * GET /api/v1/equipements/{equipement}/transfers
     */
    public function equipmentTransfers(Request $request, Equipement $equipement)
    {
        if (!$this->canAccessEnterprise($request, (int) $equipement->enterprise_id)) {
            return $this->forbiddenResponse();
        }

        $transfers = EquipementTransferHistory::query()
            ->where('equipement_id', $equipement->id)
            ->where('enterprise_id', $equipement->enterprise_id)
            ->with([
                'previousSite:id,name',
                'newSite:id,name',
                'previousLocalisation:id,code,libelle',
                'newLocalisation:id,code,libelle',
                'transferReason:id,code,name',
                'transferredByUser:id,name,email',
                'verifiedByUser:id,name,email',
            ])
            ->orderBy('transferred_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($transfers);
    }

    /**
     * Task 2.1.2: Register an equipment location transfer
     * POST /api/v1/equipements/{id}/transfer
     */
    public function store(Request $request, Equipement $equipement)
    {
        if (!$this->canAccessEnterprise($request, (int) $equipement->enterprise_id)) {
            return $this->forbiddenResponse();
        }

        $validator = Validator::make($request->all(), [
            'new_site_id' => 'required|integer|exists:sites,id',
            'new_localisation_id' => 'required|integer|exists:codification_elements,id',
            'transfer_reason_code' => 'required|string|max:50',
            'transfer_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            $this->logValidationFailure('transfer.store', $request, $validator->errors()->toArray(), [
                'equipement_id' => (int) $equipement->id,
            ]);
            return $this->invalidParametersResponse();
        }

        $newSiteId = $request->input('new_site_id');
        $newLocalisationId = $request->input('new_localisation_id');
        $reasonCode = $request->input('transfer_reason_code');
        $notes = $request->input('transfer_notes');
        $userId = $request->user()?->id;

        // Validate new site and localisation belong to same enterprise
        $newSite = Site::where('id', $newSiteId)
            ->where('enterprise_id', $equipement->enterprise_id)
            ->first();

        if (!$newSite) {
            Log::warning('Equipment transfer rejected: invalid target site', [
                'equipement_id' => (int) $equipement->id,
                'enterprise_id' => (int) $equipement->enterprise_id,
                'new_site_id' => (int) $newSiteId,
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);

            return $this->invalidParametersResponse();
        }

        // Validate localisation exists and belongs to enterprise + is of type 'localisation'
        $newLocalisation = \App\Models\CodificationElement::query()
            ->where('id', $newLocalisationId)
            ->where('enterprise_id', $equipement->enterprise_id)
            ->where('type', 'localisation')
            ->first();

        if (!$newLocalisation) {
            Log::warning('Equipment transfer rejected: invalid target localisation', [
                'equipement_id' => (int) $equipement->id,
                'enterprise_id' => (int) $equipement->enterprise_id,
                'new_localisation_id' => (int) $newLocalisationId,
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);

            return $this->invalidParametersResponse();
        }

        // Validate transfer reason code exists in enterprise
        $transferReason = TransferReasonCode::query()
            ->where('enterprise_id', $equipement->enterprise_id)
            ->where('code', $reasonCode)
            ->where('is_active', true)
            ->first();

        if (!$transferReason) {
            Log::warning('Equipment transfer rejected: invalid transfer reason', [
                'equipement_id' => (int) $equipement->id,
                'enterprise_id' => (int) $equipement->enterprise_id,
                'transfer_reason_code' => (string) $reasonCode,
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);

            return $this->invalidParametersResponse();
        }

        // Prevent self-transfer
        if (
            $equipement->site_id == $newSiteId
            && $equipement->localisation_id == $newLocalisationId
        ) {
            Log::warning('Equipment transfer rejected: self-transfer', [
                'equipement_id' => (int) $equipement->id,
                'enterprise_id' => (int) $equipement->enterprise_id,
                'new_site_id' => (int) $newSiteId,
                'new_localisation_id' => (int) $newLocalisationId,
                'user_id' => (int) ($request->user()?->id ?? 0),
            ]);

            return $this->invalidParametersResponse();
        }

        try {
            return DB::transaction(function () use (
                $equipement,
                $newSiteId,
                $newLocalisationId,
                $reasonCode,
                $notes,
                $userId,
            ) {
                // Create transfer history entry
                $transfer = EquipementTransferHistory::create([
                    'equipement_id' => $equipement->id,
                    'enterprise_id' => $equipement->enterprise_id,
                    'previous_site_id' => $equipement->site_id,
                    'previous_localisation_id' => $equipement->localisation_id,
                    'new_site_id' => $newSiteId,
                    'new_localisation_id' => $newLocalisationId,
                    'transfer_reason_code' => $reasonCode,
                    'transfer_notes' => $notes,
                    'transferred_by' => $userId,
                    'transferred_at' => now(),
                    'is_verified' => false,
                ]);

                // Update equipment location (auto-mark as verified for now if admin)
                $equipement->update([
                    'site_id' => $newSiteId,
                    'localisation_id' => $newLocalisationId,
                ]);

                return response()->json([
                    'success' => true,
                    'transfer_id' => $transfer->id,
                    'equipement_id' => $equipement->id,
                    'previous_site_id' => $transfer->previous_site_id,
                    'new_site_id' => $newSiteId,
                    'transferred_by' => $userId,
                    'transferred_at' => $transfer->transferred_at?->toIso8601String(),
                ], 201);
            });
        } catch (\Throwable $exception) {
            Log::error('Equipment transfer store failed', [
                'equipement_id' => (int) $equipement->id,
                'enterprise_id' => (int) $equipement->enterprise_id,
                'user_id' => (int) ($request->user()?->id ?? 0),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement du transfert.',
            ], 422);
        }
    }

    /**
     * Task 2.1.3: Get verification status of a transfer
     * GET /api/v1/transfers/{id}
     */
    public function show(Request $request, EquipementTransferHistory $transfer)
    {
        if (!$this->canAccessEnterprise($request, (int) $transfer->enterprise_id)) {
            return $this->forbiddenResponse();
        }

        return response()->json($transfer->load([
            'equipement',
            'previousSite',
            'newSite',
            'previousLocalisation',
            'newLocalisation',
            'transferReason',
            'transferredByUser',
            'verifiedByUser',
        ]));
    }

    /**
     * Task 2.1.3: Verify/approve a transfer
     * PUT /api/v1/transfers/{id}/verify
     */
    public function verify(Request $request, EquipementTransferHistory $transfer)
    {
        if (!$this->canAccessEnterprise($request, (int) $transfer->enterprise_id)) {
            return $this->forbiddenResponse();
        }

        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            $this->logValidationFailure('transfer.verify', $request, $validator->errors()->toArray(), [
                'transfer_id' => (int) $transfer->id,
            ]);
            return $this->invalidParametersResponse();
        }

        if ($transfer->is_verified) {
            return response()->json([
                'message' => 'Ce transfert a déjà été vérifié.',
            ], 422);
        }

        $notes = $request->input('verification_notes');
        $userId = $request->user()?->id;

        $transfer->update([
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);

        return response()->json([
            'success' => true,
            'transfer_id' => $transfer->id,
            'is_verified' => true,
            'verified_by' => $userId,
            'verified_at' => $transfer->verified_at?->toIso8601String(),
        ]);
    }

    /**
     * Task 2.1.3: Get available transfer reason codes for an enterprise
     * GET /api/v1/enterprises/{id}/transfer-reasons
     */
    public function getTransferReasons(Request $request, $enterpriseId)
    {
        $enterpriseId = (int) $enterpriseId;
        if (!$this->canAccessEnterprise($request, $enterpriseId)) {
            return $this->forbiddenResponse();
        }

        $query = TransferReasonCode::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('is_active', true)
            ->orderBy('code');

        if (!(clone $query)->exists()) {
            foreach (self::DEFAULT_TRANSFER_REASONS as $reason) {
                TransferReasonCode::query()->firstOrCreate(
                    [
                        'enterprise_id' => (int) $enterpriseId,
                        'code' => $reason['code'],
                    ],
                    [
                        'name' => $reason['name'],
                        'description' => $reason['description'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $reasons = TransferReasonCode::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'description']);

        return response()->json($reasons);
    }

    private function canAccessEnterprise(Request $request, int $enterpriseId): bool
    {
        if ($enterpriseId <= 0) {
            return false;
        }

        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ((string) $user->user_type === 'super_admin') {
            return true;
        }

        return (int) ($user->enterprise_id ?? 0) === $enterpriseId;
    }

    private function forbiddenResponse()
    {
        return response()->json([
            'message' => 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.',
        ], 403);
    }

    private function invalidParametersResponse()
    {
        return response()->json([
            'message' => 'Paramètres invalides.',
        ], 422);
    }

    private function logValidationFailure(
        string $action,
        Request $request,
        array $errors,
        array $context = []
    ): void {
        Log::warning('Equipment transfer validation failed', array_merge([
            'action' => $action,
            'user_id' => (int) ($request->user()?->id ?? 0),
            'enterprise_id' => (int) ($request->user()?->enterprise_id ?? 0),
            'errors' => $errors,
        ], $context));
    }
}
