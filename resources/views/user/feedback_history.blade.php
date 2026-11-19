@extends('layouts.app')

@section('title', 'Feedback History')

@php
    $typeMeta = [
        'bug'        => ['fa-bug', 'is-bad'],
        'feature'    => ['fa-plus-circle', 'is-ok'],
        'suggestion' => ['fa-lightbulb', 'is-warn'],
        'general'    => ['fa-comment', ''],
    ];
@endphp

@section('content')
@include('user._ui-kit')
<main class="cnm-ui ui-page ui-stack !max-w-[1040px]">

    <!-- Header -->
    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-comments"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Feedback History</h1>
                <p class="ui-sub">Every idea, bug report and request you've shared, with our replies. Your feedback shapes what we build next.</p>
            </div>
        </div>
        <button onclick="openFeedbackModal()" class="ui-btn">
            <i class="fas fa-plus"></i>Add Feedback
        </button>
    </div>

    <!-- Stats -->
    <div class="ui-stats c4">
        <div class="ui-stat">
            <p class="ui-stat-l"><i class="fas fa-comment-alt"></i>Total Feedback</p>
            <p class="ui-stat-v">{{ $stats['total'] }}</p>
            <p class="ui-stat-h">Shared with our team</p>
        </div>
        <div class="ui-stat">
            <p class="ui-stat-l"><i class="fas fa-star"></i>Avg Rating</p>
            <p class="ui-stat-v">{{ number_format($stats['avg_rating'], 1) }}</p>
            <div class="flex text-amber-400 gap-px mt-0.5" aria-hidden="true">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= floor($stats['avg_rating']))
                        <i class="fas fa-star text-[10px]"></i>
                    @elseif($i <= $stats['avg_rating'])
                        <i class="fas fa-star-half-alt text-[10px]"></i>
                    @else
                        <i class="far fa-star text-[10px]"></i>
                    @endif
                @endfor
            </div>
        </div>
        <div class="ui-stat">
            <p class="ui-stat-l"><i class="fas fa-clock"></i>Pending</p>
            <p class="ui-stat-v">{{ $stats['pending'] }}</p>
            <p class="ui-stat-h !text-orange-600">Awaiting response</p>
        </div>
        <div class="ui-stat">
            <p class="ui-stat-l"><i class="fas fa-check-circle"></i>Resolved</p>
            <p class="ui-stat-v">{{ $stats['resolved'] }}</p>
            <p class="ui-stat-h !text-green-600">Completed</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('user.feedback.history') }}" class="ui-card p-3 grid grid-cols-1 sm:grid-cols-4 gap-2 items-end">
        <div>
            <label for="fb-type" class="ui-label">Type</label>
            <select id="fb-type" name="type" class="ui-field">
                <option value="">All Types</option>
                <option value="suggestion" {{ request('type') == 'suggestion' ? 'selected' : '' }}>Suggestions</option>
                <option value="bug" {{ request('type') == 'bug' ? 'selected' : '' }}>Bug Reports</option>
                <option value="feature" {{ request('type') == 'feature' ? 'selected' : '' }}>Feature Requests</option>
                <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>General</option>
            </select>
        </div>
        <div>
            <label for="fb-status" class="ui-label">Status</label>
            <select id="fb-status" name="status" class="ui-field">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
        </div>
        <div>
            <label for="fb-rating" class="ui-label">Rating</label>
            <select id="fb-rating" name="rating" class="ui-field">
                <option value="">All Ratings</option>
                <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
                <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
                <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
                <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
                <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="ui-btn flex-1"><i class="fas fa-filter text-[11px]"></i>Filter</button>
            <a href="{{ route('user.feedback.history') }}" class="ui-btn-light">Clear</a>
        </div>
    </form>

    <!-- Feedback List -->
    <div class="ui-card divide-y divide-slate-100">
        @forelse($feedback as $item)
        @php [$tIcon, $tTone] = $typeMeta[$item->feedback_type] ?? $typeMeta['general']; @endphp
        <div class="px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <button type="button" onclick="toggleFeedback({{ $item->id }})" class="flex items-start gap-3 min-w-0 text-left flex-1" aria-expanded="false" aria-controls="content-{{ $item->id }}">
                    <span class="ui-badge {{ $tTone }} !w-8 !h-8 !p-0 justify-center !rounded-lg flex-shrink-0"><i class="fas {{ $tIcon }} text-[12px]"></i></span>
                    <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-[13px] font-semibold text-gray-900">{{ $item->feedback_type_name }}</span>
                            <span class="ui-badge {{ $item->status == 'pending' ? 'is-brand' : ($item->status == 'reviewed' ? '' : 'is-ok') }}">{{ ucfirst($item->status) }}</span>
                            @if($item->admin_response)<span class="ui-badge is-ok"><i class="fas fa-reply text-[9px]"></i>Replied</span>@endif
                        </span>
                        <span class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11.5px] text-slate-500 mt-0.5">
                            <span class="flex items-center gap-1">
                                <span class="flex text-amber-400 gap-px" aria-hidden="true">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="{{ $i <= $item->rating ? 'fas' : 'far' }} fa-star text-[10px]"></i>
                                    @endfor
                                </span>
                                {{ $item->rating_text }}
                            </span>
                            <span>{{ $item->created_at->format('M d, Y') }}</span>
                            <span>{{ $item->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="block text-[12px] text-slate-600 mt-1 line-clamp-1">{{ \Illuminate\Support\Str::limit($item->message, 140) }}</span>
                    </span>
                </button>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($item->contact_permission)
                        <span class="ui-badge is-ok hidden sm:inline-flex"><i class="fas fa-check text-[9px]"></i>Contact OK</span>
                    @endif
                    <button onclick="toggleFeedback({{ $item->id }})" class="w-7 h-7 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center" aria-label="Show details">
                        <i class="fas fa-chevron-down text-[11px]" id="icon-{{ $item->id }}"></i>
                    </button>
                </div>
            </div>

            <!-- Expandable Content -->
            <div id="content-{{ $item->id }}" class="hidden mt-2.5 space-y-2 sm:pl-11">
                <div class="bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                    <h4 class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-0.5">Your Feedback:</h4>
                    <p class="text-gray-800 text-[12.5px] leading-relaxed whitespace-pre-line">{{ $item->message }}</p>
                </div>

                @if($item->admin_response)
                <div class="ui-note is-info !block !py-2">
                    <h4 class="text-[11px] font-semibold uppercase tracking-wide mb-0.5"><i class="fas fa-reply mr-1"></i>Admin Response:</h4>
                    <p class="text-[12.5px] leading-relaxed whitespace-pre-line">{{ $item->admin_response }}</p>
                    <p class="text-[11px] opacity-70 mt-1">{{ $item->updated_at->format('M d, Y \a\t g:i A') }}</p>
                </div>
                @else
                <div class="ui-note is-brand !py-2">
                    <i class="fas fa-clock text-orange-500"></i><span>We're reviewing your feedback and will respond soon.</span>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="ui-empty">
            <div class="ui-empty-i"><i class="fas fa-comment-slash"></i></div>
            <p class="ui-empty-t">No Feedback Found</p>
            <p class="mt-1 mb-3">You haven't submitted any feedback yet. Spotted a bug or have an idea? We read every message.</p>
            <button onclick="openFeedbackModal()" class="ui-btn ui-sm">
                <i class="fas fa-plus"></i>Submit Your First Feedback
            </button>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($feedback->hasPages())
    <div>
        {{ $feedback->appends(request()->query())->links() }}
    </div>
    @endif
</main>

<script>
function toggleFeedback(id) {
    const content = document.getElementById(`content-${id}`);
    const icon = document.getElementById(`icon-${id}`);
    const open = content.classList.contains('hidden');
    content.classList.toggle('hidden', !open);
    icon.classList.replace(open ? 'fa-chevron-down' : 'fa-chevron-up', open ? 'fa-chevron-up' : 'fa-chevron-down');
    content.closest('.px-4').querySelectorAll('[aria-expanded]').forEach(b => b.setAttribute('aria-expanded', open ? 'true' : 'false'));
}
</script>
@endsection
