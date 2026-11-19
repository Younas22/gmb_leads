@extends('layouts.app')

@section('title', 'Folder send')

@section('content')
<div class="p-4 lg:p-8 max-w-6xl mx-auto space-y-6">
    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm" role="status">{{ session('success') }}</div>@endif

    <div class="bg-white border border-orange-100 rounded-2xl p-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $send->folder_name }}</h1>
            <p class="text-sm text-gray-600 mt-1">
                Each lead's own AI email through <strong>{{ \App\Models\LeadFolderSend::PROVIDERS[$send->provider] ?? $send->provider }}</strong>, one every {{ intdiv($send->interval_seconds, 60) }} minute(s).
                Status: <strong>{{ ucfirst($send->status) }}</strong>{{ $send->finished_at ? ' · ' . $send->finished_at->format('M j, H:i') : '' }}
            </p>
        </div>
        @if($send->status === 'running')
            <form method="POST" action="{{ route('user.folder-sends.cancel', $send->id) }}" onsubmit="return confirm('Stop this folder send? Emails not sent yet will be cancelled.');">@csrf
                <button class="px-4 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 text-sm font-medium"><i class="fas fa-stop mr-1"></i> Stop</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
        @foreach(\App\Models\LeadFolderSendItem::LABELS as $k => $label)
            <div class="bg-white border border-gray-200 rounded-xl p-3"><div class="text-xs text-gray-500">{{ $label }}</div><div class="text-xl font-bold text-gray-900">{{ $counts[$k] ?? 0 }}</div></div>
        @endforeach
    </div>

    <section class="bg-white border border-gray-200 rounded-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    <tr><th class="px-4 py-3">Business</th><th class="px-4 py-3">To</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">When</th><th class="px-4 py-3 text-right">Email</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $i)
                        <tr>
                            <td class="px-4 py-3 align-top text-gray-900">{{ $i->lead_name ?: '—' }}</td>
                            <td class="px-4 py-3 align-top whitespace-nowrap text-gray-700">{{ $i->recipient ?: '—' }}</td>
                            <td class="px-4 py-3 align-top">
                                <span class="text-xs px-2 py-0.5 rounded-full border {{ ['queued' => 'bg-green-50 text-green-700 border-green-200', 'pending' => 'bg-orange-50 text-orange-700 border-orange-200', 'failed' => 'bg-red-50 text-red-700 border-red-200'][$i->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">{{ \App\Models\LeadFolderSendItem::LABELS[$i->status] ?? $i->status }}</span>
                                @if($i->reason)<div class="text-xs text-gray-500 mt-1 max-w-xs">{{ $i->reason }}</div>@endif
                                @if($i->email)<div class="text-xs text-gray-500 mt-1">Email: {{ $i->email->status === 'sent' ? 'accepted' : $i->email->status }}{{ $i->email->delivery_status ? ' · ' . $i->email->delivery_status : '' }}</div>@endif
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap text-xs text-gray-500">{{ ($i->processed_at ?? $i->due_at)?->format('M j, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-right">
                                @if($i->outbound_email_id && \App\Services\PlanFeatures::enabled(auth()->user(), 'email_analytics_enabled'))
                                    <a href="{{ route('user.email-analytics.email', $i->outbound_email_id) }}" class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Details</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="p-4 border-t border-gray-100">{{ $items->links() }}</div>@endif
    </section>
    <p class="text-xs text-gray-500">Emails are handed to the sending queue one at a time and usually leave within 1–2 minutes of their turn. Delivery details appear in Email Sending and Email Analytics.</p>
</div>
@endsection
