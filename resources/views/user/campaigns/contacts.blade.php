@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
<div class="p-4 lg:p-8 max-w-7xl mx-auto space-y-6">
    @include('user.campaigns._nav', ['active' => 'contacts'])
    @include('user.partials._other-errors', ['inline' => ['email', 'file', 'confirm', 'name']])

    <div class="bg-white border border-orange-100 rounded-2xl p-5">
        <h1 class="text-xl font-bold text-gray-900">Opted-in contacts</h1>
        <p class="text-sm text-gray-600 mt-1 max-w-3xl">Only people who agreed to receive your emails. Every contact needs a consent source, date and evidence (where and how they opted in). Your saved leads are <strong>not</strong> contacts. People who unsubscribed, bounced or complained can never be added back.</p>
        <p class="text-xs text-gray-500 mt-2">{{ $total }} / {{ $limit === -1 ? 'unlimited' : $limit }} contacts on your plan.</p>
    </div>

    @if(session('import_report'))
        @php $rep = session('import_report'); @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-sm" data-testid="import-report">
            <div class="font-semibold text-gray-900">Import result</div>
            <div class="text-gray-700">Added {{ $rep['created'] }} · Updated {{ $rep['updated'] }} · Rejected {{ $rep['rejected'] }} · Blocked {{ $rep['blocked'] }} · Over limit {{ $rep['limit'] }}</div>
            @if($rep['errors'])<ul class="mt-2 text-xs text-red-700 list-disc list-inside max-h-40 overflow-y-auto">@foreach($rep['errors'] as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Add a contact</h2>
            <form method="POST" action="{{ route('user.campaigns.contacts.store') }}" class="space-y-2 text-sm">@csrf
                @error('email')<div class="text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $message }}</div>@enderror
                <input name="email" type="email" required maxlength="254" placeholder="Email" value="{{ old('email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <div class="grid grid-cols-2 gap-2">
                    <input name="first_name" maxlength="100" placeholder="First name" value="{{ old('first_name') }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <input name="last_name" maxlength="100" placeholder="Last name" value="{{ old('last_name') }}" class="px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <input name="organization" maxlength="150" placeholder="Organization" value="{{ old('organization') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <select name="consent_source" required class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">How did they opt in?</option>
                    @foreach($sources as $k => $v)<option value="{{ $k }}" @selected(old('consent_source') === $k)>{{ $v }}</option>@endforeach
                </select>
                <input name="consent_date" type="date" required max="{{ today()->toDateString() }}" value="{{ old('consent_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <textarea name="consent_evidence" required minlength="10" maxlength="2000" rows="3" placeholder="Evidence, e.g. 'Signed up on pixelstudio.com/newsletter form on 3 Nov, ticked the email box'" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('consent_evidence') }}</textarea>
                <input name="tags" maxlength="300" placeholder="Tags (separate with ;)" value="{{ old('tags') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <select name="list_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white"><option value="">No list</option>@foreach($lists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
                <button class="w-full px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white font-semibold">Add contact</button>
            </form>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Import CSV</h2>
            <form method="POST" action="{{ route('user.campaigns.contacts.import') }}" enctype="multipart/form-data" class="space-y-2 text-sm">@csrf
                <input type="file" name="file" accept=".csv,text/csv" required class="w-full text-sm">
                @error('file')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <select name="list_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white"><option value="">No list</option>@foreach($lists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
                <label class="flex items-start gap-2 text-xs"><input type="checkbox" name="confirm" value="1" required class="mt-0.5"> Everyone in this file opted in to receive emails from me, and the evidence column is accurate.</label>
                @error('confirm')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button class="w-full px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white font-medium">Import</button>
            </form>
            <p class="text-xs text-gray-500 mt-3">Columns: <span class="font-mono">{{ implode(', ', \App\Services\Resend\ContactService::CSV_COLUMNS) }}</span>. consent_source must be one of: <span class="font-mono">{{ implode(', ', array_keys($sources)) }}</span>. Rows without evidence are rejected. Max {{ config('resend_user.import_max_rows') }} rows.</p>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Lists</h2>
            <ul class="divide-y divide-gray-100 mb-3 text-sm">
                @forelse($lists as $l)<li class="py-1.5 flex justify-between"><a href="?list={{ $l->id }}" class="hover:text-orange-600">{{ $l->name }}</a><span class="text-gray-500">{{ $l->contacts_count }}</span></li>@empty<li class="py-1.5 text-gray-500">No lists yet.</li>@endforelse
            </ul>
            <form method="POST" action="{{ route('user.campaigns.lists.store') }}" class="flex gap-2">@csrf
                <input name="name" required maxlength="100" placeholder="New list name" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <button class="px-3 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">Create</button>
            </form>
            @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </section>
    </div>

    <section class="bg-white border border-gray-200 rounded-2xl">
        <form method="GET" class="p-4 border-b border-gray-100 flex flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search email or organization" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white"><option value="">All</option><option value="sendable" @selected(request('status') === 'sendable')>Can receive email</option><option value="blocked" @selected(request('status') === 'blocked')>Opted out / bounced / complained</option></select>
            <button class="px-4 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50">Filter</button>
        </form>
        <form method="POST" action="{{ route('user.campaigns.lists.add') }}" id="bulk-list">@csrf</form>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase"><tr><th class="px-4 py-3"></th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Consent</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($contacts as $c)
                        <tr>
                            <td class="px-4 py-2"><input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" form="bulk-list"></td>
                            <td class="px-4 py-2"><div class="font-medium text-gray-900 break-all">{{ $c->email }}</div><div class="text-xs text-gray-500">{{ $c->displayName() }}{{ $c->organization ? ' · ' . $c->organization : '' }}{{ $c->lists->isNotEmpty() ? ' · ' . $c->lists->pluck('name')->implode(', ') : '' }}</div></td>
                            <td class="px-4 py-2 text-xs text-gray-600">{{ $sources[$c->consent_source] ?? $c->consent_source }}<br>{{ $c->consent_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-2 text-xs">@if($c->isSendable())<span class="text-green-700">Can receive email</span>@else<span class="text-red-600">{{ ucfirst(str_replace('_', ' ', $c->blockReason())) }}</span>@endif</td>
                            <td class="px-4 py-2 text-right">@if($c->isSendable())
                                <form method="POST" action="{{ route('user.campaigns.contacts.withdraw', $c->id) }}" onsubmit="return confirm('Stop emailing {{ $c->email }}? This cannot be undone.');">@csrf<button class="text-xs text-gray-500 hover:text-red-600">Stop emailing</button></form>
                            @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No contacts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lists->isNotEmpty() && $contacts->isNotEmpty())
            <div class="p-4 border-t border-gray-100 flex gap-2 items-center text-sm">
                <select name="list_id" form="bulk-list" class="px-3 py-2 border border-gray-300 rounded-lg bg-white">@foreach($lists as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
                <button form="bulk-list" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Add selected to list</button>
            </div>
        @endif
        @if($contacts->hasPages())<div class="p-4 border-t border-gray-100">{{ $contacts->links() }}</div>@endif
    </section>
</div>
@endsection
