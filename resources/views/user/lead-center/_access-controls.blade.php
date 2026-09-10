<div class="flex items-center gap-2 flex-shrink-0">
    @if(($myAcceptedGrants ?? collect())->count())
        <select id="lcSwitchAccountSelect" onchange="lcSwitchAccount(this.value || null)" class="flex-shrink-0">
            <option value="">My Own Account</option>
            @foreach($myAcceptedGrants as $grant)
                <option value="{{ $grant->owner_user_id }}"
                        data-name="{{ $grant->owner->full_name }}"
                        data-email="{{ $grant->owner->email }}"
                        {{ (($actingAsOwner->id ?? null) == $grant->owner_user_id) ? 'selected' : '' }}>
                    {{ $grant->owner->full_name }} — {{ $grant->owner->email }}
                </option>
            @endforeach
        </select>
    @endif
    <button type="button" onclick="openShareAccessModal()" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors flex-shrink-0 whitespace-nowrap">
        <i class="fas fa-user-plus text-[10px]"></i> <span class="hidden sm:inline">Share Access</span><span class="sm:hidden">Share</span>
    </button>
</div>
