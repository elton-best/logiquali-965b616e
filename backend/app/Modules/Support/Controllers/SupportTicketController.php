<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    /**
     * Get all support tickets for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $tickets = SupportTicket::where('user_id', $user->id)
            ->with(['assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ]
        ]);
    }

    /**
     * Store a new support ticket
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:general_question,technical_problem,assistance_request,complaint,suggestion,other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
            'priority' => 'medium',
        ]);

        return response()->json([
            'success' => true,
            'data' => $ticket->load(['user', 'assignedTo']),
            'message' => 'Ticket de support créé avec succès. Nous vous répondrons dans les plus brefs délais.'
        ], 201);
    }

    /**
     * Show a specific support ticket
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $ticket = SupportTicket::where('user_id', $user->id)
            ->with(['assignedTo'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * Get support statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        $total = SupportTicket::where('user_id', $user->id)->count();
        $open = SupportTicket::where('user_id', $user->id)->where('status', 'open')->count();
        $inProgress = SupportTicket::where('user_id', $user->id)->where('status', 'in_progress')->count();
        $resolved = SupportTicket::where('user_id', $user->id)->where('status', 'resolved')->count();
        $closed = SupportTicket::where('user_id', $user->id)->where('status', 'closed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'open' => $open,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'closed' => $closed,
            ]
        ]);
    }
}
