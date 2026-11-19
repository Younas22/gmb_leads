@extends('layouts.app')

@section('title', 'Campaigns')

@section('content')
@include('user._ui-kit')
<div class="cnm-ui ui-page ui-stack">
    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-bullhorn"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Campaigns</h1>
                <p class="ui-sub">Email everyone who <strong>opted in</strong> at once, through your own Resend account as one Resend Broadcast. Every campaign needs your explicit approval; nothing is sent automatically. Saved leads are never added to campaigns.</p>
            </div>
        </div>
        <a href="{{ route('user.campaigns.create') }}" class="ui-btn"><i class="fas fa-plus"></i> New campaign</a>
    </div>

    @include('user.campaigns._nav', ['active' => 'campaigns'])

    <section class="ui-card">
        <div class="ui-card-h"><h2 class="ui-card-t"><i class="fas fa-list"></i> My campaigns</h2></div>
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full">
                <thead><tr><th>Campaign</th><th>Status</th><th>Recipients</th><th>When</th><th></th></tr></thead>
                <tbody>
                    @forelse($campaigns as $c)
                        <tr>
                            <td><div class="font-medium text-gray-900">{{ $c->name }}</div><div class="text-[11.5px] text-slate-500">{{ $c->sender?->from_email }} · {{ $c->list?->name ?? 'All opted-in contacts' }}</div></td>
                            <td><span class="ui-badge">{{ $c->label() }}</span></td>
                            <td>{{ $c->recipient_count ?: '—' }}</td>
                            <td class="text-[12px] text-slate-500">{{ $c->scheduled_at ? 'Scheduled ' . $c->scheduled_at->format('M j, H:i') : ($c->submitted_at?->format('M j, H:i') ?? $c->created_at->format('M j')) }}</td>
                            <td class="text-right"><a href="{{ route('user.campaigns.show', $c->id) }}" class="ui-btn-light ui-sm">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="ui-empty">
                                <div class="ui-empty-i"><i class="fas fa-bullhorn"></i></div>
                                <p class="ui-empty-t">Your first campaign is two steps away</p>
                                <p class="mt-1">No campaigns yet. Add opted-in contacts, then <a href="{{ route('user.campaigns.create') }}" class="ui-link">create a campaign</a>.</p>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())<div class="ui-card-f">{{ $campaigns->links() }}</div>@endif
    </section>

    {{-- Lead-folder sends (each lead gets its own AI email) --}}
    @if($folderSends->isNotEmpty())
    <section class="ui-card" data-testid="folder-sends">
        <div class="ui-card-h">
            <div>
                <h2 class="ui-card-t"><i class="fas fa-folder-open"></i> Lead folder sends <span class="ui-ai"><i class="fas fa-wand-magic-sparkles"></i> AI</span></h2>
                <p class="ui-card-d">Each lead in the folder gets its own personal AI email.</p>
            </div>
            <a href="{{ route('user.folder-sends.create') }}" class="ui-btn-light ui-sm"><i class="fas fa-paper-plane"></i> Send a folder</a>
        </div>
        <ul class="divide-y divide-slate-100 text-[12.5px]">
            @foreach($folderSends as $fs)
                <li class="px-4 py-2.5 flex flex-wrap items-center justify-between gap-2">
                    <span class="text-gray-900 font-medium">{{ $fs->folder_name }} <span class="text-[11.5px] font-normal text-slate-500">· {{ \App\Models\LeadFolderSend::PROVIDERS[$fs->provider] ?? $fs->provider }} · {{ $fs->created_at->format('M j, H:i') }}</span></span>
                    <span class="flex items-center gap-2"><span class="ui-badge">{{ ucfirst($fs->status) }}</span>
                    <a href="{{ route('user.folder-sends.show', $fs->id) }}" class="ui-btn-light ui-sm">Open</a></span>
                </li>
            @endforeach
        </ul>
    </section>
    @endif

    <section class="ui-card">
        <div class="ui-card-h">
            <div>
                <h2 class="ui-card-t"><i class="fas fa-file-lines"></i> Email templates</h2>
                <p class="ui-card-d">Save the emails you send often and reuse them in any campaign.</p>
            </div>
        </div>
        <ul class="divide-y divide-slate-100 px-4">
            @forelse($templates as $t)
                <li class="py-2 flex justify-between gap-2 text-[12.5px]"><span><span class="font-medium text-gray-900">{{ $t->name }}</span> <span class="text-slate-500">· {{ $t->subject }}</span></span>
                    <form method="POST" action="{{ route('user.campaigns.templates.destroy', $t->id) }}">@csrf @method('DELETE')<button class="text-[12px] text-slate-500 hover:text-red-600">Delete</button></form></li>
            @empty
                <li class="py-2.5 text-[12.5px] text-slate-500">No templates yet.</li>
            @endforelse
        </ul>
        <form method="POST" action="{{ route('user.campaigns.templates.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-4 border-t border-slate-100 bg-slate-50 rounded-b-[14px]">@csrf
            <input name="name" required maxlength="100" placeholder="Template name" aria-label="Template name" class="ui-field">
            <input name="subject" required maxlength="200" placeholder="Subject" aria-label="Subject" class="ui-field">
            <textarea name="body" required rows="3" maxlength="20000" placeholder="Hi {{ '{{' }}first_name|there}}, ..." aria-label="Template body" class="ui-field sm:col-span-2"></textarea>
            <button class="ui-btn-dark sm:col-span-2 justify-self-start">Save template</button>
        </form>
    </section>
</div>
@endsection
