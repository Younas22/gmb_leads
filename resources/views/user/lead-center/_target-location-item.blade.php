@php
    $parts = collect([$loc->cityRelation->name ?? null, $loc->stateRelation->name ?? null, $loc->countryRelation->name ?? null])->filter()->implode(', ');
@endphp
<div class="flex items-start justify-between gap-2 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2" data-location-id="{{ $loc->id }}">
    <div class="min-w-0">
        <p class="text-sm font-medium text-gray-800 truncate">{{ $parts ?: 'No location' }}</p>
        @if($loc->notes)
            <p class="text-xs text-gray-500 mt-0.5">{{ $loc->notes }}</p>
        @endif
    </div>
    <button onclick="deleteTargetLocation({{ $loc->id }})" class="text-gray-300 hover:text-red-500 flex-shrink-0"><i class="fas fa-trash-alt text-xs"></i></button>
</div>
