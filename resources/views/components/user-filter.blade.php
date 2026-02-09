@props(['selectedUserId' => null])

@php
    $user = auth()->user();
    $accountOwner = $user->isTeamMember() ? $user->company : $user;

    // Only show filter if user is a company
    $showFilter = $accountOwner && $accountOwner->isCompany();

    // Get team members
    $teamMembers = [];
    if ($showFilter) {
        $teamMembers = $accountOwner->teamMembers()->orderBy('first_name')->get();
    }
@endphp

@if($showFilter && $teamMembers->count() > 0)
<div class="flex items-center space-x-2">
    <label for="userFilter" class="text-sm font-medium text-gray-700 whitespace-nowrap">
        <i class="fas fa-filter mr-1"></i>Filter by User:
    </label>
    <select
        id="userFilter"
        name="user_filter"
        onchange="filterByUser(this.value)"
        class="form-select rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
    >
        <option value="">All Users (Company + Team)</option>
        <option value="{{ $accountOwner->id }}" {{ $selectedUserId == $accountOwner->id ? 'selected' : '' }}>
            {{ $accountOwner->first_name }} {{ $accountOwner->last_name }} (Company Owner)
        </option>
        @foreach($teamMembers as $member)
        <option value="{{ $member->id }}" {{ $selectedUserId == $member->id ? 'selected' : '' }}>
            {{ $member->first_name }} {{ $member->last_name }}
        </option>
        @endforeach
    </select>
</div>

<script>
function filterByUser(userId) {
    const url = new URL(window.location.href);

    if (userId) {
        url.searchParams.set('user_id', userId);
    } else {
        url.searchParams.delete('user_id');
    }

    // Preserve other query parameters like page, sort, etc.
    window.location.href = url.toString();
}
</script>
@endif
