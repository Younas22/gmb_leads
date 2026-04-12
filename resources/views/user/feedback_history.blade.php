@extends('layouts.app')

@section('title', 'Feedback History')

@section('content')
<main class="p-4 lg:p-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-base font-bold text-gray-800">Feedback History</h1>
            <p class="text-xs text-gray-500">Track all the feedback you've shared with us</p>
        </div>
        <button onclick="openFeedbackModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
            <i class="fas fa-plus mr-1"></i>Add Feedback
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Total Feedback</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-primary-100 rounded-lg p-2">
                    <i class="fas fa-comment-alt text-primary-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Avg Rating</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['avg_rating'], 1) }}</p>
                    <div class="flex text-yellow-400 mt-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($stats['avg_rating']))
                                <i class="fas fa-star text-xs"></i>
                            @elseif($i <= $stats['avg_rating'])
                                <i class="fas fa-star-half-alt text-xs"></i>
                            @else
                                <i class="far fa-star text-xs"></i>
                            @endif
                        @endfor
                    </div>
                </div>
                <div class="bg-yellow-100 rounded-lg p-2">
                    <i class="fas fa-star text-yellow-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Pending</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['pending'] }}</p>
                    <p class="text-xs text-orange-600 mt-0.5">Awaiting response</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-2">
                    <i class="fas fa-clock text-orange-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-0.5">Resolved</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['resolved'] }}</p>
                    <p class="text-xs text-green-600 mt-0.5">Completed</p>
                </div>
                <div class="bg-green-100 rounded-lg p-2">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-3 border border-gray-100 mb-4">
        <form method="GET" action="{{ route('user.feedback.history') }}" class="flex flex-wrap items-end gap-2">
            <div class="flex-1 min-w-36">
                <label class="block text-xs font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 text-xs">
                    <option value="">All Types</option>
                    <option value="suggestion" {{ request('type') == 'suggestion' ? 'selected' : '' }}>Suggestions</option>
                    <option value="bug" {{ request('type') == 'bug' ? 'selected' : '' }}>Bug Reports</option>
                    <option value="feature" {{ request('type') == 'feature' ? 'selected' : '' }}>Feature Requests</option>
                    <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>General</option>
                </select>
            </div>
            <div class="flex-1 min-w-36">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 text-xs">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="flex-1 min-w-36">
                <label class="block text-xs font-medium text-gray-700 mb-1">Rating</label>
                <select name="rating" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500 text-xs">
                    <option value="">All Ratings</option>
                    <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
                    <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
                    <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
                    <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
                    <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                    <i class="fas fa-filter mr-1"></i>Filter
                </button>
                <a href="{{ route('user.feedback.history') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium border">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="space-y-3">
        @forelse($feedback as $item)
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
            <div class="flex items-start justify-between gap-3 mb-2">
                <div class="flex items-start gap-3">
                    <div class="bg-{{ $item->feedback_type == 'bug' ? 'red' : ($item->feedback_type == 'feature' ? 'green' : ($item->feedback_type == 'suggestion' ? 'yellow' : 'blue')) }}-100 rounded-lg p-2 flex-shrink-0">
                        <i class="fas fa-{{ $item->feedback_type == 'bug' ? 'bug' : ($item->feedback_type == 'feature' ? 'plus-circle' : ($item->feedback_type == 'suggestion' ? 'lightbulb' : 'comment')) }} text-{{ $item->feedback_type == 'bug' ? 'red' : ($item->feedback_type == 'feature' ? 'green' : ($item->feedback_type == 'suggestion' ? 'yellow' : 'blue')) }}-600 text-sm"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $item->feedback_type_name }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @if($item->status == 'pending') bg-orange-100 text-orange-800
                                @elseif($item->status == 'reviewed') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <div class="flex items-center">
                                <div class="flex text-yellow-400 mr-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $item->rating)
                                            <i class="fas fa-star text-xs"></i>
                                        @else
                                            <i class="far fa-star text-xs"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span>{{ $item->rating_text }}</span>
                            </div>
                            <span>{{ $item->created_at->format('M d, Y') }}</span>
                            <span>{{ $item->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($item->contact_permission)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>Contact OK
                        </span>
                    @endif
                    <button onclick="toggleFeedback({{ $item->id }})" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                        <i class="fas fa-chevron-down text-xs" id="icon-{{ $item->id }}"></i>
                    </button>
                </div>
            </div>

            <!-- Expandable Content -->
            <div id="content-{{ $item->id }}" class="hidden border-t border-gray-100 pt-3">
                <div class="bg-gray-50 rounded-lg p-3 mb-2">
                    <h4 class="text-xs font-medium text-gray-700 mb-1">Your Feedback:</h4>
                    <p class="text-gray-800 text-xs leading-relaxed">{{ $item->message }}</p>
                </div>

                @if($item->admin_response)
                <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                    <h4 class="text-xs font-medium text-blue-700 mb-1 flex items-center">
                        <i class="fas fa-reply mr-1"></i>Admin Response:
                    </h4>
                    <p class="text-blue-800 text-xs leading-relaxed">{{ $item->admin_response }}</p>
                    <p class="text-xs text-blue-500 mt-1">{{ $item->updated_at->format('M d, Y \a\t g:i A') }}</p>
                </div>
                @else
                <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                    <p class="text-orange-700 text-xs flex items-center">
                        <i class="fas fa-clock mr-1"></i>We're reviewing your feedback and will respond soon.
                    </p>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-100 text-center">
            <i class="fas fa-comment-slash text-gray-300 text-3xl mb-3"></i>
            <h3 class="text-sm font-semibold text-gray-500 mb-1">No Feedback Found</h3>
            <p class="text-xs text-gray-400 mb-4">You haven't submitted any feedback yet.</p>
            <button onclick="openFeedbackModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-1.5 rounded-lg text-xs font-medium transition-colors">
                <i class="fas fa-plus mr-1"></i>Submit Your First Feedback
            </button>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($feedback->hasPages())
    <div class="mt-4">
        {{ $feedback->appends(request()->query())->links() }}
    </div>
    @endif
</main>

<script>
function toggleFeedback(id) {
    const content = document.getElementById(`content-${id}`);
    const icon = document.getElementById(`icon-${id}`);
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
    }
}
</script>
@endsection
