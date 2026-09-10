@php
    $statusCfg = [
        'pending'  => ['label' => 'Pending',  'class' => 'bg-yellow-100 text-yellow-700'],
        'accepted' => ['label' => 'Accepted', 'class' => 'bg-green-100 text-green-700'],
        'declined' => ['label' => 'Declined', 'class' => 'bg-red-100 text-red-700'],
    ];
    $cfg = $statusCfg[$grant->status] ?? ['label' => ucfirst($grant->status), 'class' => 'bg-gray-100 text-gray-700'];
@endphp
<div class="flex items-center justify-between p-2.5 border border-gray-100 rounded-xl" data-shared-grant-id="{{ $grant->id }}">
    <div class="min-w-0">
        <p class="text-sm font-medium text-gray-900 truncate">{{ $grant->grantee->full_name }}</p>
        <p class="text-xs text-gray-400 truncate">{{ $grant->grantee->email }}</p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
        <button onclick="revokeSharedGrant({{ $grant->id }})" title="Remove access" class="text-gray-300 hover:text-red-500">
            <i class="fas fa-trash-alt text-xs"></i>
        </button>
    </div>
</div>
