<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function index()
    {
        $teamMembers = TeamMember::with(['site', 'process', 'user'])->paginate(20);
        return TeamMemberResource::collection($teamMembers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:pilot,copilot,referent,member',
            'is_active' => 'boolean',
        ]);

        $teamMember = TeamMember::create($validated);
        return new TeamMemberResource($teamMember->load(['site', 'process', 'user']));
    }

    public function show(TeamMember $teamMember)
    {
        return new TeamMemberResource($teamMember->load(['site', 'process', 'user']));
    }

    public function update(Request $request, TeamMember $teamMember)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'user_id' => 'sometimes|exists:users,id',
            'role' => 'sometimes|in:pilot,copilot,referent,member',
            'is_active' => 'boolean',
        ]);

        $teamMember->update($validated);
        return new TeamMemberResource($teamMember->load(['site', 'process', 'user']));
    }

    public function destroy(TeamMember $teamMember)
    {
        $teamMember->delete();
        return response()->json(null, 204);
    }
}
