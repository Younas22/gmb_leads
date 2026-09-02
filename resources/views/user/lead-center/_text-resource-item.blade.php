@php
    $accent = $kind === 'prompt' ? 'text-purple-700' : 'text-teal-700';
    $deleteFn = $kind === 'prompt' ? 'deletePrompt' : 'deleteTemplate';
@endphp
<div class="bg-gray-50 border border-gray-100 rounded-lg px-3 py-2" data-{{ $kind }}-id="{{ $item->id }}">
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-semibold {{ $accent }} truncate">{{ $item->title }}</p>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button onclick='copyText({{ json_encode($item->content) }})' title="Copy" class="text-gray-300 hover:text-primary-600"><i class="fas fa-copy text-xs"></i></button>
            <button onclick="{{ $deleteFn }}({{ $item->id }})" title="Delete" class="text-gray-300 hover:text-red-500"><i class="fas fa-trash-alt text-xs"></i></button>
        </div>
    </div>
    <p class="text-xs text-gray-500 mt-1 whitespace-pre-wrap line-clamp-3">{{ $item->content }}</p>
</div>
