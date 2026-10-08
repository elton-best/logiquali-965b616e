<?php

namespace App\Modules\Support\Controllers;

use App\Models\User;

use App\Events\DocumentContentChanged;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class DocumentCollaborationController extends Controller
{
    /**
     * Join a document editing session
     */
    public function join(Request $request, Document $document): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('documents.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $userId = $request->user()->id;
        $sessionKey = "document.{$document->id}.users";
        
        // Add user to active users list
        $activeUsers = Cache::get($sessionKey, []);
        
        if (!in_array($userId, $activeUsers)) {
            $activeUsers[] = $userId;
            Cache::put($sessionKey, $activeUsers, now()->addHours(2));
        }

        return response()->json([
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'content' => $document->content ?? '',
                'version' => $document->collaboration_version ?? 1,
            ],
            'active_users' => $this->getActiveUsersData($activeUsers),
        ]);
    }

    /**
     * Leave a document editing session
     */
    public function leave(Request $request, Document $document): JsonResponse
    {
        $userId = $request->user()->id;
        $sessionKey = "document.{$document->id}.users";
        
        // Remove user from active users list
        $activeUsers = Cache::get($sessionKey, []);
        $activeUsers = array_filter($activeUsers, fn($id) => $id !== $userId);
        
        Cache::put($sessionKey, array_values($activeUsers), now()->addHours(2));

        return response()->json([
            'message' => 'Left session',
            'active_users' => $this->getActiveUsersData($activeUsers),
        ]);
    }

    /**
     * Sync document content changes (Quill delta)
     */
    public function syncContent(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delta' => 'required|array',
            'version' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Check permission
        if (! $request->user()->can('documents.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get current document version
        $currentVersion = $document->collaboration_version ?? 1;

        // Version conflict detection (simple last-write-wins)
        if ($validated['version'] < $currentVersion) {
            return response()->json([
                'conflict' => true,
                'current_version' => $currentVersion,
                'message' => 'Document version conflict. Please refresh.',
            ], 409);
        }

        // Broadcast change to other users BEFORE saving
        broadcast(new DocumentContentChanged(
            $document->id,
            $request->user()->id,
            $validated['delta'],
            $currentVersion + 1
        ))->toOthers();

        // Increment version
        $document->increment('collaboration_version');

        return response()->json([
            'success' => true,
            'version' => $document->collaboration_version,
        ]);
    }

    /**
     * Save document content (debounced auto-save)
     */
    public function save(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'version' => 'required|integer',
        ]);

        // Check permission
        if (!$request->user()->can('documents.update')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Update document content
        $document->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Document saved',
            'version' => $document->collaboration_version ?? 1,
        ]);
    }

    /**
     * Get document current state for new collaborators
     */
    public function getState(Document $document): JsonResponse
    {
        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'content' => $document->content ?? '',
            'version' => $document->collaboration_version ?? 1,
        ]);
    }

    /**
     * Get active users in a document
     */
    public function getActiveUsers(Document $document): JsonResponse
    {
        $sessionKey = "document.{$document->id}.users";
        $activeUsers = Cache::get($sessionKey, []);

        return response()->json([
            'active_users' => $this->getActiveUsersData($activeUsers),
        ]);
    }

    /**
     * Get user data for active users
     */
    private function getActiveUsersData(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return \App\Models\User::whereIn('id', $userIds)
            ->get(['id', 'name', 'username'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'color' => $this->generateUserColor($user->id),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Generate a consistent color for a user
     */
    private function generateUserColor(int $userId): string
    {
        $colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
        ];

        return $colors[$userId % count($colors)];
    }
}
