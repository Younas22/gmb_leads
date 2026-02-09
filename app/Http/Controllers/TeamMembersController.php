<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TeamMembersController extends Controller
{
    /**
     * Display team members page
     */
    public function index()
    {
        $user = Auth::user();

        // Only company accounts can access this page
        if (!$user->isCompany()) {
            return redirect()->route('user.dashboard')->with('error', 'Only company accounts can manage team members.');
        }

        // Get team members limit info
        $teamMembersLimit = $user->getFeatureLimit('team_members');
        $canAddMore = $user->canAddTeamMember();
        $remainingSlots = $user->getRemainingTeamMemberSlots();

        // Get all team members
        $teamMembers = $user->teamMembers()
                            ->orderBy('created_at', 'desc')
                            ->get();

        return view('user.team-members', compact('user', 'teamMembers', 'teamMembersLimit', 'canAddMore', 'remainingSlots'));
    }

    /**
     * Store a new team member
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user is a company
        if (!$user->isCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Only company accounts can add team members.',
            ], 403);
        }

        // Check if company allows new signups
        if (!$user->allowsNewSignups()) {
            return response()->json([
                'success' => false,
                'message' => 'New signups are currently disabled for your company. Please contact the administrator.',
            ], 403);
        }

        // Check if user can add more team members
        if (!$user->canAddTeamMember()) {
            $limit = $user->getFeatureLimit('team_members');

            return response()->json([
                'success' => false,
                'message' => "You have reached your team member limit ($limit). Please upgrade your package to add more members.",
                'limit_reached' => true
            ], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $teamMember = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'plain_password' => $request->password,
                'company_id' => $user->id,
                'user_type' => User::TYPE_USER,
                'login_type' => User::LOGIN_REGULAR,
                'status' => User::STATUS_ACTIVE,
                'email_verified' => true, // Auto-verify team members
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Team member added successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Team member creation error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'request_data' => $request->except('password')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create team member. Please try again later.'
            ], 500);
        }
    }

    /**
     * Update team member
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Only company accounts can update team members
        if (!$user->isCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Only company accounts can update team members.',
            ], 403);
        }

        try {
            $teamMember = User::where('company_id', $user->id)->findOrFail($id);

            $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email,' . $teamMember->id,
                'status' => 'nullable|in:active,inactive',
            ]);

            $updateData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => trim($request->first_name . ' ' . $request->last_name),
                'email' => $request->email,
            ];

            if ($request->filled('status')) {
                $updateData['status'] = $request->status;
            }

            // Update password if provided
            if ($request->filled('password')) {
                $request->validate([
                    'password' => 'string|min:6',
                ]);

                $updateData['password'] = Hash::make($request->password);
                $updateData['plain_password'] = $request->password;
            }

            $teamMember->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Team member update error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'team_member_id' => $id,
                'request_data' => $request->except('password')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update team member. Please try again later.'
            ], 500);
        }
    }

    /**
     * Toggle team member status
     */
    public function toggleStatus($id)
    {
        $user = Auth::user();

        if (!$user->isCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Only company accounts can toggle team member status.',
            ], 403);
        }

        try {
            $teamMember = User::where('company_id', $user->id)->findOrFail($id);

            $newStatus = $teamMember->status === User::STATUS_ACTIVE ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;

            $teamMember->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Team member status updated successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Team member status toggle error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'team_member_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update status. Please try again later.'
            ], 500);
        }
    }

    /**
     * Delete team member
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Only company accounts can delete team members.',
            ], 403);
        }

        try {
            $teamMember = User::where('company_id', $user->id)->findOrFail($id);

            $teamMember->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team member deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Team member deletion error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'team_member_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete team member. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get team member details (for modal)
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user->isCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $teamMember = User::where('company_id', $user->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'team_member' => [
                'id' => $teamMember->id,
                'first_name' => $teamMember->first_name,
                'last_name' => $teamMember->last_name,
                'name' => $teamMember->name,
                'email' => $teamMember->email,
                'password' => $teamMember->plain_password,
                'status' => $teamMember->status,
                'created_at' => $teamMember->created_at->format('M d, Y h:i A'),
                'last_login' => $teamMember->last_login ? $teamMember->last_login->format('M d, Y h:i A') : 'Never',
            ]
        ]);
    }
}
