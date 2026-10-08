<?php

namespace App\Modules\ClientB\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

/**
 * ClientB Dashboard Controller
 * Provides dashboard statistics and recent activity for Client B users
 */
class ClientBDashboardController extends Controller
{
    /**
     * Get dashboard statistics for Client B user
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get user's complaints statistics
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
            
            // Average response time (in days)
            $avgResponseTime = Complaint::where('user_id', $user->id)
                ->whereNotNull('updated_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/86400) as avg_days')
                ->value('avg_days');

            $stats = [
                'total_complaints' => $totalComplaints,
                'pending_complaints' => $pendingComplaints,
                'in_progress_complaints' => $inProgressComplaints,
                'resolved_complaints' => $resolvedComplaints,
                'closed_complaints' => $closedComplaints,
                'avg_response_time_days' => round($avgResponseTime ?? 0, 1),
                'resolution_rate' => $totalComplaints > 0 
                    ? round(($resolvedComplaints + $closedComplaints) / $totalComplaints * 100, 1) 
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques'
            ], 500);
        }
    }

    /**
     * Get recent activity for Client B user
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = $request->get('limit', 10);

            $recentComplaints = Complaint::where('user_id', $user->id)
                ->with(['site', 'assignedUser'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($complaint) {
                    return [
                        'id' => $complaint->id,
                        'ref' => $complaint->ref,
                        'title' => $complaint->title,
                        'status' => $complaint->status,
                        'site' => $complaint->site ? $complaint->site->name : null,
                        'assigned_to' => $complaint->assignedUser ? $complaint->assignedUser->name : null,
                        'created_at' => $complaint->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $complaint->updated_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentComplaints
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'activité récente'
            ], 500);
        }
    }

    /**
     * Get quick actions available for Client B
     */
    public function getQuickActions(): JsonResponse
    {
        $actions = [
            [
                'id' => 'new_complaint',
                'title' => 'Déposer une réclamation',
                'description' => 'Besoin d\'aide ? Déposez votre réclamation en quelques clics',
                'icon' => 'mdi-message-alert-outline',
                'color' => 'primary',
                'route' => '/clientb/complaints/create'
            ],
            [
                'id' => 'view_complaints',
                'title' => 'Voir mes réclamations',
                'description' => 'Consultez l\'état de vos réclamations',
                'icon' => 'mdi-format-list-bulleted',
                'color' => 'info',
                'route' => '/clientb/complaints'
            ],
            [
                'id' => 'satisfaction_survey',
                'title' => 'Enquête de satisfaction',
                'description' => 'Donnez-nous votre avis',
                'icon' => 'mdi-star-outline',
                'color' => 'warning',
                'route' => '/clientb/satisfaction'
            ],
            [
                'id' => 'chatbot',
                'title' => 'Assistance instantanée',
                'description' => 'Discutez avec notre assistant virtuel',
                'icon' => 'mdi-robot-outline',
                'color' => 'success',
                'route' => '/clientb/chatbot'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $actions
        ]);
    }
}
