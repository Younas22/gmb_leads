{{-- Schedule a follow-up reminder. Expects $leadId, optional $emailId, $followUpBlocked. --}}
@if($followUpBlocked)
    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">{{ $followUpBlocked }} No new follow-ups can be scheduled.</p>
@else
    <form method="POST" action="{{ route('user.email-follow-ups.store') }}" class="space-y-3">
        @csrf
        @if($leadId)<input type="hidden" name="saved_lead_id" value="{{ $leadId }}">@endif
        @if(!empty($contactId))<input type="hidden" name="marketing_contact_id" value="{{ $contactId }}">@endif
        @isset($emailId)<input type="hidden" name="outbound_email_id" value="{{ $emailId }}">@endisset
        @error('follow_up')<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2" role="alert">{{ $message }}</div>@enderror
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label for="fu-date" class="block text-xs font-medium text-gray-600 mb-1">Follow up on</label>
                <input id="fu-date" type="date" name="due_date" required min="{{ today()->toDateString() }}" value="{{ old('due_date', today()->addDays(3)->toDateString()) }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
                @error('due_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="fu-notes" class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                <input id="fu-notes" type="text" name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="e.g. Ask if they had a chance to review the proposal"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500">
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button class="px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium"><i class="fas fa-bell mr-1"></i> Save reminder</button>
            <span class="text-xs text-gray-500">A reminder only. Nothing is sent automatically.</span>
        </div>
    </form>
@endif
