<?php

namespace App\Modules\ClientB\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Complaint;
use App\Models\ClientSatisfactionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ClientBProfileController extends Controller
{
    /**
     * Get profile statistics for Client B
     */
    public function getStats(Request $request)
    {
        $user = $request->user();

        // Statistiques des plaintes (Complaints utilise user_id)
        $totalComplaints = Complaint::where('user_id', $user->id)->count();
        $pendingComplaints = Complaint::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $inProgressComplaints = Complaint::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->count();
        $resolvedComplaints = Complaint::where('user_id', $user->id)
            ->where('status', 'resolved')
            ->count();
        $closedComplaints = Complaint::where('user_id', $user->id)
            ->where('status', 'closed')
            ->count();

        // Statistiques des fiches de satisfaction Client B (utilise created_by)
        $totalSurveys = ClientSatisfactionForm::where('created_by', $user->id)->count();
        $completedSurveys = ClientSatisfactionForm::where('created_by', $user->id)
            ->whereIn('status', ['submitted', 'reviewed'])
            ->count();
        $draftSurveys = ClientSatisfactionForm::where('created_by', $user->id)
            ->where('status', 'draft')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'complaints' => [
                    'total' => $totalComplaints,
                    'pending' => $pendingComplaints,
                    'in_progress' => $inProgressComplaints,
                    'resolved' => $resolvedComplaints,
                    'closed' => $closedComplaints,
                ],
                'satisfaction_forms' => [
                    'total' => $totalSurveys,
                    'completed' => $completedSurveys,
                    'draft' => $draftSurveys,
                ],
            ]
        ]);
    }

    /**
     * Update Client B profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Profil mis à jour avec succès'
        ]);
    }

    /**
     * Change password for Client B
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ]);
    }
}
