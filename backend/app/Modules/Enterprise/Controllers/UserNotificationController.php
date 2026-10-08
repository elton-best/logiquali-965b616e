<?php

namespace App\Modules\Enterprise\Controllers;

use App\Models\Enterprise;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * UserNotificationController
 * 
 * Source unique: notifications Laravel.
 */
class UserNotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     * 
     * Retourne les notifications Laravel uniquement (tri date décroissante)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);
        $unreadOnly = $request->boolean('unread_only', false);

        // 1. Récupérer notifications Laravel
        $laravelNotificationsQuery = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User');

        if ($unreadOnly) {
            $laravelNotificationsQuery->whereNull('read_at');
        }

        $laravelNotifications = $laravelNotificationsQuery->latest()->get();

        $normalized = $laravelNotifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'source' => 'laravel',
            ];
        });

        $paginated = $normalized->forPage(
            $request->get('page', 1),
            $perPage
        )->values();

        // Compter les non-lues Laravel
        $unreadCount = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => $paginated,
            'unread_count' => $unreadCount,
            'total' => $normalized->count(),
            'pagination' => [
                'per_page' => $perPage,
                'current_page' => $request->get('page', 1),
                'total_pages' => ceil($normalized->count() / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/v1/notifications/{notificationId}
     * 
     * Récupère une notification spécifique (Laravel uniquement)
     */
    public function show(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
                'source' => 'laravel',
            ],
        ]);
    }

    /**
     * POST /api/v1/notifications/{notificationId}/read
     * 
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * POST /api/v1/notifications/mark-all-as-read
     * 
     * Marquer toutes les notifications Laravel comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Marquer toutes les notifications Laravel comme lues
        $laravelCount = DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Toutes les notifications ont été marquées comme lues",
            'marked_count' => $laravelCount,
        ]);
    }

    /**
     * DELETE /api/v1/notifications/{notificationId}
     * 
     * Supprimer une notification Laravel
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * POST /api/v1/notifications/{type}/mark-type-as-read
     * 
     * Marquer toutes les notifications d'un type comme lues
     */
    public function markTypeAsRead(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        // Marquer les notifications Laravel du type
        DatabaseNotification::where('notifiable_id', $user->id)
            ->where('notifiable_type', 'App\\Models\\User')
            ->where('type', $type)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Les notifications du type '$type' ont été marquées comme lues",
        ]);
    }
}
