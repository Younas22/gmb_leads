@if(($actingAsOwner ?? null) || ($pendingGrantsForMe ?? collect())->count())
<div class="space-y-2 mb-4">
    @if($actingAsOwner ?? null)
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2 text-sm text-amber-800">
                <i class="fas fa-user-shield"></i>
                Viewing <strong>{{ $actingAsOwner->full_name }}</strong>'s Lead Center
                <span class="text-amber-600 text-xs">({{ $actingAsOwner->email }})</span>
            </div>
            <button onclick="lcSwitchAccount(null)" class="text-xs font-medium bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Switch Back to My Account
            </button>
        </div>
    @endif

    @foreach($pendingGrantsForMe ?? [] as $grant)
        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 flex items-center justify-between flex-wrap gap-2" data-grant-id="{{ $grant->id }}">
            <div class="flex items-center gap-2 text-sm text-blue-800">
                <i class="fas fa-user-plus"></i>
                <strong>{{ $grant->owner->full_name }}</strong>
                <span class="text-blue-600 text-xs">({{ $grant->owner->email }})</span>
                wants to share their Lead Center with you.
            </div>
            <div class="flex items-center gap-2">
                <button onclick="lcRespondGrant({{ $grant->id }}, 'accept')" class="text-xs font-medium bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                    <i class="fas fa-check mr-1"></i> Accept
                </button>
                <button onclick="lcRespondGrant({{ $grant->id }}, 'decline')" class="text-xs font-medium bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg transition-colors">
                    Decline
                </button>
            </div>
        </div>
    @endforeach
</div>
@endif
