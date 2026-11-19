@extends('layouts.app')

@section('title', 'Follow-ups')

@section('content')
@include('user._ui-kit')
<div class="cnm-ui ui-page ui-stack !max-w-[1040px]">
    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-bell"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Follow-ups</h1>
                <p class="ui-sub">A timely follow-up is often what wins the deal. These are reminders only: nothing is sent automatically. Add one from an email's or a lead's page in Email Analytics.</p>
            </div>
        </div>
    </div>

    @foreach([['Overdue', $overdue, 'text-red-600', 'No overdue follow-ups.', 'fa-triangle-exclamation'], ['Upcoming', $upcoming, 'text-gray-900', 'Nothing scheduled.', 'fa-calendar-day']] as [$title, $items, $color, $empty, $icon])
        <section class="ui-card" data-testid="follow-ups-{{ strtolower($title) }}">
            <div class="ui-card-h"><h2 class="ui-card-t {{ $color }}"><i class="fas {{ $icon }} {{ $title === 'Overdue' ? '!text-red-500' : '' }}"></i>{{ $title }} ({{ $items instanceof \Illuminate\Pagination\LengthAwarePaginator ? $items->total() : $items->count() }})</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse($items as $f)
                    <li class="px-4 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[12.5px]">
                        <div class="min-w-0">
                            @if($f->saved_lead_id)
                                <a href="{{ route('user.email-analytics.lead', $f->saved_lead_id) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $f->lead?->name ?? 'Lead' }}</a>
                            @else
                                <span class="font-medium text-gray-900">{{ $f->contact?->displayName() ?? 'Contact' }}</span> <span class="text-[11.5px] text-slate-500">(contact)</span>
                            @endif
                            <span class="{{ $f->isOverdue() ? 'text-red-600' : 'text-slate-500' }} ml-1">· {{ $f->due_date->format('D, M j, Y') }}</span>
                            @if($f->email)<div class="text-[11.5px] text-slate-500 break-words">Re: {{ $f->email->subject }}</div>@endif
                            @if($f->notes)<div class="text-[11.5px] text-slate-600 mt-0.5 break-words">{{ $f->notes }}</div>@endif
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <form method="POST" action="{{ route('user.email-follow-ups.complete', $f->id) }}" class="flex items-center gap-1.5">@csrf
                                <input type="text" name="notes" maxlength="1000" placeholder="Outcome (optional)" aria-label="Outcome" class="ui-field ui-sm !w-40">
                                <button class="ui-btn ui-sm"><i class="fas fa-check"></i> Mark done</button>
                            </form>
                            <form method="POST" action="{{ route('user.email-follow-ups.cancel', $f->id) }}">@csrf
                                <button class="ui-btn-light ui-sm">Cancel</button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-5 text-center text-[12.5px] text-slate-500">{{ $empty }}</li>
                @endforelse
            </ul>
            @if($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())<div class="ui-card-f">{{ $items->links() }}</div>@endif
        </section>
    @endforeach

    <section class="ui-card">
        <div class="ui-card-h"><h2 class="ui-card-t"><i class="fas fa-circle-check"></i> Recently closed</h2></div>
        <ul class="divide-y divide-slate-100">
            @forelse($recent as $f)
                <li class="px-4 py-2 text-[12.5px] flex flex-wrap justify-between gap-2">
                    <span><span class="font-medium text-gray-900">{{ $f->lead?->name ?? 'Lead' }}</span> <span class="text-slate-500">· due {{ $f->due_date->format('M j') }}</span></span>
                    <span class="text-[11.5px] text-slate-500">{{ $f->status === 'completed' ? 'Done' . ($f->completedBy ? ' by ' . $f->completedBy->name : '') : 'Cancelled' . ($f->cancel_reason && $f->cancel_reason !== 'user' ? ' (' . str_replace('_', ' ', $f->cancel_reason) . ')' : '') }} · {{ $f->updated_at->format('M j') }}</span>
                </li>
            @empty
                <li class="px-4 py-4 text-center text-[12.5px] text-slate-500">Nothing yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
