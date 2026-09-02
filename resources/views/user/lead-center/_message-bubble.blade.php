@php $isOur = $message->sender_type === 'our'; @endphp
<div class="flex {{ $isOur ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
    <div class="max-w-[80%] group">
        <div class="flex items-center gap-1.5 mb-1 {{ $isOur ? 'justify-end' : '' }}">
            <span class="text-[10px] font-semibold uppercase tracking-wide {{ $isOur ? 'text-primary-600' : 'text-gray-500' }}">{{ $isOur ? 'You' : 'Client' }}</span>
            <button onclick="deleteMessage({{ $message->id }})" title="Delete message" class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-opacity">
                <i class="fas fa-trash-alt text-[10px]"></i>
            </button>
        </div>
        <div class="rounded-2xl px-4 py-2.5 text-sm whitespace-pre-wrap break-words {{ $isOur ? 'bg-primary-600 text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm' }}">{{ $message->message }}</div>
        <p class="text-[10px] text-gray-400 mt-1 {{ $isOur ? 'text-right' : '' }}">{{ $message->created_at->format('M j, Y g:i A') }}</p>
    </div>
</div>
