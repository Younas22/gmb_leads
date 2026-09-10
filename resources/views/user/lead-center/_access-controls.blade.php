<div class="flex items-center gap-2 flex-shrink-0">
    @if(($myAcceptedGrants ?? collect())->count())
        <select onchange="lcSwitchAccount(this.value || null)"
                class="text-xs border border-gray-300 rounded-lg px-2 py-2 cursor-pointer bg-white max-w-[140px] sm:max-w-[200px] truncate flex-shrink">
            <option value="">My Own Account</option>
            @foreach($myAcceptedGrants as $grant)
                <option value="{{ $grant->owner_user_id }}" {{ (($actingAsOwner->id ?? null) == $grant->owner_user_id) ? 'selected' : '' }}>
                    {{ $grant->owner->full_name }} ({{ $grant->owner->email }})
                </option>
            @endforeach
        </select>
    @endif
    <button type="button" onclick="openShareAccessModal()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors flex-shrink-0 whitespace-nowrap">
        <i class="fas fa-user-plus"></i> <span class="hidden sm:inline">Share Access</span><span class="sm:hidden">Share</span>
    </button>
</div>
