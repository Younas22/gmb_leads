@extends('layouts.app')

@section('title', 'Feedback History')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-2">Your Feedback History</h1>
                <p class="text-sm sm:text-base text-gray-600">Track all the feedback you've shared with us</p>
            </div>
            <button onclick="openFeedbackModal()" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-sm sm:text-base font-medium transition-colors text-center">
                <i class="fas fa-plus mr-1 sm:mr-2 text-xs"></i>
                Add New Feedback
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Total Feedback</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-primary-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-comment-alt text-primary-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Average Rating</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">{{ number_format($stats['avg_rating'], 1) }}</p>
                    <div class="flex text-yellow-400 mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($stats['avg_rating']))
                                <i class="fas fa-star text-xs sm:text-sm"></i>
                            @elseif($i <= $stats['avg_rating'])
                                <i class="fas fa-star-half-alt text-xs sm:text-sm"></i>
                            @else
                                <i class="far fa-star text-xs sm:text-sm"></i>
                            @endif
                        @endfor
                    </div>
                </div>
                <div class="bg-yellow-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-star text-yellow-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Pending Review</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $stats['pending'] }}</p>
                    <p class="text-xs text-orange-600 mt-1">Awaiting response</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-clock text-orange-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Resolved</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $stats['resolved'] }}</p>
                    <p class="text-xs text-green-600 mt-1">Completed</p>
                </div>
                <div class="bg-green-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-check-circle text-green-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-3 sm:p-4 border border-gray-100 mb-6">
        <form method="GET" action="{{ route('user.feedback.history') }}" class="flex flex-wrap items-end gap-3 sm:gap-4">
            <div class="flex-1 min-w-full sm:min-w-48">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-xs sm:text-sm">
                    <option value="">All Types</option>
                    <option value="suggestion" {{ request('type') == 'suggestion' ? 'selected' : '' }}>Suggestions</option>
                    <option value="bug" {{ request('type') == 'bug' ? 'selected' : '' }}>Bug Reports</option>
                    <option value="feature" {{ request('type') == 'feature' ? 'selected' : '' }}>Feature Requests</option>
                    <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>General</option>
                </select>
            </div>

            <div class="flex-1 min-w-full sm:min-w-48">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-xs sm:text-sm">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>

            <div class="flex-1 min-w-full sm:min-w-48">
                <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Filter by Rating</label>
                <select name="rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-xs sm:text-sm">
                    <option value="">All Ratings</option>
                    <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5 Stars</option>
                    <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>4 Stars</option>
                    <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>3 Stars</option>
                    <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>2 Stars</option>
                    <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>1 Star</option>
                </select>
            </div>

            <div class="w-full sm:w-auto flex gap-2">
                <button type="submit" class="flex-1 sm:flex-none bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-1 sm:mr-2"></i>Filter
                </button>

                <a href="{{ route('user.feedback.history') }}" class="flex-1 sm:flex-none bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 sm:px-6 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors border text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="space-y-6">
        @forelse($feedback as $item)
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row items-start justify-between gap-3 sm:gap-0 mb-4">
                <div class="flex items-start space-x-3 sm:space-x-4 w-full sm:w-auto">
                    <div class="bg-{{ $item->feedback_type == 'bug' ? 'red' : ($item->feedback_type == 'feature' ? 'green' : ($item->feedback_type == 'suggestion' ? 'yellow' : 'blue')) }}-100 rounded-lg p-2 sm:p-3 flex-shrink-0">
                        <i class="fas fa-{{ $item->feedback_type == 'bug' ? 'bug' : ($item->feedback_type == 'feature' ? 'plus-circle' : ($item->feedback_type == 'suggestion' ? 'lightbulb' : 'comment')) }} text-{{ $item->feedback_type == 'bug' ? 'red' : ($item->feedback_type == 'feature' ? 'green' : ($item->feedback_type == 'suggestion' ? 'yellow' : 'blue')) }}-600 text-base sm:text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-3 mb-2">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800">{{ $item->feedback_type_name }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium w-fit
                                @if($item->status == 'pending') bg-orange-100 text-orange-800
                                @elseif($item->status == 'reviewed') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800
                                @endif">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-4 text-xs sm:text-sm text-gray-500">
                            <div class="flex items-center">
                                <div class="flex text-yellow-400 mr-2">
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
                            <span class="hidden sm:inline">{{ $item->created_at->format('M d, Y') }}</span>
                            <span>{{ $item->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end space-x-2 w-full sm:w-auto">
                    @if($item->contact_permission)
                        <span class="inline-flex items-center px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                            <i class="fas fa-check mr-1"></i>Contact OK
                        </span>
                    @endif
                    <button onclick="toggleFeedback({{ $item->id }})" class="text-gray-400 hover:text-gray-600 transition-colors p-2">
                        <i class="fas fa-chevron-down" id="icon-{{ $item->id }}"></i>
                    </button>
                </div>
            </div>

            <!-- Expandable Content -->
            <div id="content-{{ $item->id }}" class="hidden border-t border-gray-200 pt-3 sm:pt-4">
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-3 sm:mb-4">
                    <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">Your Feedback:</h4>
                    <p class="text-gray-800 text-xs sm:text-sm leading-relaxed">{{ $item->message }}</p>
                </div>

                @if($item->admin_response)
                <div class="bg-blue-50 rounded-lg p-3 sm:p-4 border border-blue-200">
                    <h4 class="text-xs sm:text-sm font-medium text-blue-700 mb-2 flex items-center">
                        <i class="fas fa-reply mr-2"></i>Admin Response:
                    </h4>
                    <p class="text-blue-800 text-xs sm:text-sm leading-relaxed">{{ $item->admin_response }}</p>
                    <p class="text-xs text-blue-600 mt-2">Responded on {{ $item->updated_at->format('M d, Y \a\t g:i A') }}</p>
                </div>
                @else
                <div class="bg-orange-50 rounded-lg p-3 sm:p-4 border border-orange-200">
                    <p class="text-orange-700 text-xs sm:text-sm flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        We're reviewing your feedback and will respond soon.
                    </p>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 border border-gray-100 text-center">
            <i class="fas fa-comment-slash text-gray-300 text-3xl sm:text-4xl mb-4"></i>
            <h3 class="text-base sm:text-lg font-semibold text-gray-500 mb-2">No Feedback Found</h3>
            <p class="text-sm sm:text-base text-gray-400 mb-4">You haven't submitted any feedback yet.</p>
            <button onclick="openFeedbackModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-sm sm:text-base font-medium transition-colors">
                <i class="fas fa-plus mr-1 sm:mr-2 text-xs"></i>Submit Your First Feedback
            </button>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($feedback->hasPages())
    <div class="mt-8">
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
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>
@endsection