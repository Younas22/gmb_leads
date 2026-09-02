@extends('layouts.app')

@section('title', 'Conversation - ' . $lead->company_name)

@section('content')
<div class="p-3 lg:p-4 max-w-5xl mx-auto">

    <!-- Breadcrumb -->
    <div class="mb-4">
        <a href="{{ route('user.lead-center.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary-600 transition-colors">
            <i class="fas fa-arrow-left"></i> Back to Lead Center
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Lead Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
                <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ $lead->company_name }}</h2>

                @if($lead->website)
                    <a href="{{ $lead->website }}" target="_blank" class="text-sm text-blue-600 hover:underline break-all mt-1 inline-block">
                        <i class="fas fa-globe mr-1"></i>{{ str_replace(['http://','https://'], '', $lead->website) }}
                    </a>
                @else
                    <p class="text-sm text-gray-400 italic mt-1">No website</p>
                @endif

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-map-marker-alt w-4 text-gray-400"></i>
                        {{ collect([$lead->cityRelation->name ?? null, $lead->stateRelation->name ?? null, $lead->countryRelation->name ?? null])->filter()->implode(', ') ?: 'No location set' }}
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-folder w-4 text-gray-400"></i>
                        @if($lead->folder)
                            {{ $lead->folder->name }}
                        @else
                            <span class="text-gray-400 italic">Unfiled</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-clock w-4 text-gray-400"></i>
                        Updated {{ $lead->updated_at->diffForHumans() }}
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5 block">Status</label>
                    @php $statusColors = \App\Models\LeadCenterLead::statusColors(); @endphp
                    <select id="convStatusSelect" onchange="updateConvStatus(this.value)"
                            class="w-full text-sm font-medium rounded-lg px-3 py-2 border-0 cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-400 {{ $statusColors[$lead->status] ?? 'bg-gray-100 text-gray-700' }}">
                        @foreach(\App\Models\LeadCenterLead::statusLabels() as $key => $label)
                            <option value="{{ $key }}" {{ $lead->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Conversation -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col" style="min-height: 60vh;">
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                    <h3 class="text-sm font-semibold text-gray-800">
                        <i class="fas fa-comments text-primary-500 mr-1.5"></i> Conversation
                    </h3>
                    <span class="text-xs text-gray-400" id="convMessageCount">{{ $messages->count() }} message{{ $messages->count() !== 1 ? 's' : '' }}</span>
                </div>

                <div id="messageList" class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    @forelse($messages as $message)
                        @include('user.lead-center._message-bubble', ['message' => $message])
                    @empty
                        <div id="noMessagesState" class="text-center py-10">
                            <i class="fas fa-comment-slash text-gray-300 text-3xl mb-3"></i>
                            <p class="text-sm font-medium text-gray-600">No messages yet.</p>
                            <p class="text-xs text-gray-400 mt-1">Start the conversation by adding your first message.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Add Message -->
                <div class="px-5 py-4 border-t border-gray-100 flex-shrink-0">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="text-xs font-medium text-gray-500">Sender:</label>
                        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
                            <button type="button" id="senderBtnOur" onclick="setSender('our')" class="px-3 py-1 rounded-md text-xs font-medium transition-colors bg-primary-600 text-white">
                                Our Message
                            </button>
                            <button type="button" id="senderBtnClient" onclick="setSender('client')" class="px-3 py-1 rounded-md text-xs font-medium transition-colors text-gray-500">
                                Client Message
                            </button>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <textarea id="newMessageText" rows="2" placeholder="Type a message…"
                                  class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-primary-400"
                                  onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendMessage(); }"></textarea>
                        <button type="button" onclick="sendMessage()" id="sendMessageBtn"
                                class="bg-primary-600 hover:bg-primary-700 text-white w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const LEAD_ID = {{ $lead->id }};
const STATUS_URL = '{{ url("/user/lead-center") }}/' + LEAD_ID + '/status';
const MESSAGE_STORE_URL = '{{ route("user.lead-center.conversation.message.store", $lead->id) }}';
const MESSAGE_DELETE_BASE = '{{ url("/user/lead-center/" . $lead->id . "/conversation/messages") }}';

let _sender = 'our';

function showToast(message, type) {
    const t = document.createElement('div');
    t.className = `fixed bottom-6 right-6 z-[999] px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2 transition-all
        ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function setSender(type) {
    _sender = type;
    document.getElementById('senderBtnOur').className = 'px-3 py-1 rounded-md text-xs font-medium transition-colors ' + (type === 'our' ? 'bg-primary-600 text-white' : 'text-gray-500');
    document.getElementById('senderBtnClient').className = 'px-3 py-1 rounded-md text-xs font-medium transition-colors ' + (type === 'client' ? 'bg-gray-700 text-white' : 'text-gray-500');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function bubbleHtml(msg) {
    const isOur = msg.sender_type === 'our';
    return `
        <div class="flex ${isOur ? 'justify-end' : 'justify-start'}" data-message-id="${msg.id}">
            <div class="max-w-[80%] group">
                <div class="flex items-center gap-1.5 mb-1 ${isOur ? 'justify-end' : ''}">
                    <span class="text-[10px] font-semibold uppercase tracking-wide ${isOur ? 'text-primary-600' : 'text-gray-500'}">${isOur ? 'You' : 'Client'}</span>
                    <button onclick="deleteMessage(${msg.id})" title="Delete message" class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-opacity">
                        <i class="fas fa-trash-alt text-[10px]"></i>
                    </button>
                </div>
                <div class="rounded-2xl px-4 py-2.5 text-sm whitespace-pre-wrap break-words ${isOur ? 'bg-primary-600 text-white rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-bl-sm'}">${escapeHtml(msg.message)}</div>
                <p class="text-[10px] text-gray-400 mt-1 ${isOur ? 'text-right' : ''}">${msg.created_at_human}</p>
            </div>
        </div>
    `;
}

function sendMessage() {
    const textEl = document.getElementById('newMessageText');
    const text = textEl.value.trim();
    if (!text) return;

    const btn = document.getElementById('sendMessageBtn');
    btn.disabled = true;

    fetch(MESSAGE_STORE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ sender_type: _sender, message: text })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const noMsg = document.getElementById('noMessagesState');
            if (noMsg) noMsg.remove();

            const list = document.getElementById('messageList');
            list.insertAdjacentHTML('beforeend', bubbleHtml(data.message_data));
            list.scrollTop = list.scrollHeight;

            document.getElementById('convMessageCount').textContent = `${data.messages_count} message${data.messages_count !== 1 ? 's' : ''}`;
            textEl.value = '';
        } else {
            showToast(data.message || 'Failed to send message', 'error');
        }
    })
    .catch(() => showToast('Failed to send message', 'error'))
    .finally(() => { btn.disabled = false; });
}

function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    fetch(`${MESSAGE_DELETE_BASE}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-message-id="${id}"]`)?.remove();
            document.getElementById('convMessageCount').textContent = `${data.messages_count} message${data.messages_count !== 1 ? 's' : ''}`;
            if (data.messages_count === 0) {
                document.getElementById('messageList').innerHTML = `
                    <div id="noMessagesState" class="text-center py-10">
                        <i class="fas fa-comment-slash text-gray-300 text-3xl mb-3"></i>
                        <p class="text-sm font-medium text-gray-600">No messages yet.</p>
                        <p class="text-xs text-gray-400 mt-1">Start the conversation by adding your first message.</p>
                    </div>`;
            }
        } else {
            showToast(data.message || 'Failed to delete message', 'error');
        }
    })
    .catch(() => showToast('Failed to delete message', 'error'));
}

function updateConvStatus(status) {
    fetch(STATUS_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ status })
    })
    .then(r => r.json())
    .then(data => showToast(data.message || 'Status updated', data.success ? 'success' : 'error'))
    .catch(() => showToast('Failed to update status', 'error'));
}

// Scroll to latest message on load
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('messageList');
    list.scrollTop = list.scrollHeight;
});
</script>
@endpush
@endsection
