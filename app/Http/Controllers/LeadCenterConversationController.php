<?php

namespace App\Http\Controllers;

use App\Models\LeadCenterFolder;
use App\Models\LeadCenterLead;
use App\Models\LeadCenterMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadCenterConversationController extends Controller
{
    private function ownerUser()
    {
        $user = Auth::user();
        return $user->isTeamMember() ? $user->company : $user;
    }

    private function findLeadOrFail($id)
    {
        return LeadCenterLead::where('user_id', $this->ownerUser()->id)
            ->with(['folder', 'countryRelation', 'stateRelation', 'cityRelation'])
            ->findOrFail($id);
    }

    public function show($id)
    {
        $lead = $this->findLeadOrFail($id);
        $messages = $lead->messages()->get();
        $folders = LeadCenterFolder::where('user_id', $this->ownerUser()->id)->orderBy('name')->get();

        return view('user.lead-center.conversation', compact('lead', 'messages', 'folders'));
    }

    public function storeMessage(Request $request, $id)
    {
        $lead = LeadCenterLead::where('user_id', $this->ownerUser()->id)->findOrFail($id);

        $request->validate([
            'sender_type' => 'required|in:our,client',
            'message' => 'required|string|max:5000',
        ]);

        $message = LeadCenterMessage::create([
            'lead_center_lead_id' => $lead->id,
            'sender_type' => $request->sender_type,
            'message' => trim($request->message),
        ]);

        // Touch the lead so "Last Updated" reflects conversation activity
        $lead->touch();

        return response()->json([
            'success' => true,
            'message_data' => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
                'created_at_human' => $message->created_at->format('M j, Y g:i A'),
            ],
            'messages_count' => $lead->messages()->count(),
        ]);
    }

    public function destroyMessage($id, $messageId)
    {
        $lead = LeadCenterLead::where('user_id', $this->ownerUser()->id)->findOrFail($id);
        $message = LeadCenterMessage::where('lead_center_lead_id', $lead->id)->findOrFail($messageId);
        $message->delete();
        $lead->touch();

        return response()->json(['success' => true, 'messages_count' => $lead->messages()->count()]);
    }
}
