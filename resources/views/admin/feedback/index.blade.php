@extends('layouts.admin')

@section('title', 'Feedback Management')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 mb-2">User Feedback Management</h1>
                <p class="text-sm sm:text-base text-gray-600">Review, approve, and respond to user feedback</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-8">
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
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Pending Review</p>
                    <p class="text-2xl sm:text-3xl font-bold text-orange-600">{{ $stats['pending'] }}</p>
                    <p class="text-xs text-orange-600 mt-1">Need attention</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-clock text-orange-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">High Rated</p>
                    <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ $stats['high_rated'] }}</p>
                    <p class="text-xs text-green-600 mt-1">4-5 Stars</p>
                </div>
                <div class="bg-green-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-smile text-green-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Low Rated</p>
                    <p class="text-2xl sm:text-3xl font-bold text-red-600">{{ $stats['low_rated'] }}</p>
                    <p class="text-xs text-red-600 mt-1">1-2 Stars</p>
                </div>
                <div class="bg-red-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-frown text-red-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs sm:text-sm font-medium text-gray-600 mb-1">Avg Rating</p>
                    <p class="text-2xl sm:text-3xl font-bold text-yellow-600">{{ $stats['avg_rating'] }}</p>
                    <div class="flex text-yellow-400 mt-1">
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
                <div class="bg-yellow-100 rounded-lg p-2 sm:p-3">
                    <i class="fas fa-star text-yellow-600 text-base sm:text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-3 sm:p-4 border border-gray-100 mb-6">
        <form method="GET" action="{{ route('admin.feedback.history') }}" class="flex flex-wrap items-end gap-3 sm:gap-4">
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

                <a href="{{ route('admin.feedback.history') }}" class="flex-1 sm:flex-none bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 sm:px-6 py-2 rounded-lg text-xs sm:text-sm font-medium transition-colors border text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="space-y-6">
        @forelse($feedback as $item)
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <!-- User Info & Feedback Header -->
            <div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-4 pb-4 border-b border-gray-200">
                <!-- User Info -->
                <div class="flex items-start space-x-3 sm:space-x-4 flex-1">
                    <div class="flex-shrink-0">
                        @if($item->user->avatar)
                            <img src="{{ asset('public/' . $item->user->avatar) }}" alt="Avatar" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                                {{ strtoupper(substr($item->user->first_name ?? $item->user->name, 0, 1)) }}{{ strtoupper(substr($item->user->last_name ?? '', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-1">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800">
                                {{ $item->user->first_name }} {{ $item->user->last_name }}
                            </h3>
                            @if($item->contact_permission)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                    <i class="fas fa-check mr-1"></i>Contact OK
                                </span>
                            @endif
                        </div>
                        <p class="text-xs sm:text-sm text-gray-600">{{ $item->user->email }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $item->created_at->format('M d, Y \a\t g:i A') }} ({{ $item->created_at->diffForHumans() }})</p>
                    </div>
                </div>

                <!-- Feedback Type & Rating -->
                <div class="flex flex-row lg:flex-col items-start lg:items-end gap-3 w-full lg:w-auto">
                    <div class="flex items-center space-x-2">
                        <div class="bg-{{ $item->feedback_type_color }}-100 rounded-lg p-2">
                            <i class="fas fa-{{ $item->feedback_type_icon }} text-{{ $item->feedback_type_color }}-600 text-sm"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ $item->feedback_type_name }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $item->rating)
                                    <i class="fas fa-star text-xs"></i>
                                @else
                                    <i class="far fa-star text-xs"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ $item->rating_text }}</span>
                    </div>
                </div>
            </div>

            <!-- Feedback Message -->
            <div class="mb-4">
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">User's Feedback:</h4>
                    <p class="text-gray-800 text-xs sm:text-sm leading-relaxed">{{ $item->message }}</p>
                </div>
            </div>

            <!-- Status & Actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <span class="text-xs sm:text-sm font-medium text-gray-700">Current Status:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($item->status == 'pending') bg-orange-100 text-orange-800
                        @elseif($item->status == 'reviewed') bg-blue-100 text-blue-800
                        @else bg-green-100 text-green-800
                        @endif">
                        <i class="fas fa-circle text-xs mr-1"></i>
                        {{ ucfirst($item->status) }}
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    @if($item->status != 'reviewed')
                        <button onclick="updateFeedbackStatus({{ $item->id }}, 'reviewed')"
                            class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i>Mark as Reviewed
                        </button>
                    @endif

                    @if($item->status != 'resolved')
                        <button onclick="updateFeedbackStatus({{ $item->id }}, 'resolved')"
                            class="flex-1 sm:flex-none bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-xs font-medium transition-colors">
                            <i class="fas fa-check mr-1"></i>Mark as Resolved
                        </button>
                    @endif

                    @if($item->status != 'pending')
                        <button onclick="updateFeedbackStatus({{ $item->id }}, 'pending')"
                            class="flex-1 sm:flex-none bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-xs font-medium transition-colors">
                            <i class="fas fa-undo mr-1"></i>Mark as Pending
                        </button>
                    @endif
                </div>
            </div>

            <!-- Admin Response (if exists) -->
            @if($item->admin_response)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="bg-blue-50 rounded-lg p-3 sm:p-4 border border-blue-200">
                    <h4 class="text-xs sm:text-sm font-medium text-blue-700 mb-2 flex items-center">
                        <i class="fas fa-reply mr-2"></i>Admin Response:
                    </h4>
                    <p class="text-blue-800 text-xs sm:text-sm leading-relaxed">{{ $item->admin_response }}</p>
                    <p class="text-xs text-blue-600 mt-2">Responded on {{ $item->updated_at->format('M d, Y \a\t g:i A') }}</p>
                </div>
            </div>
            @endif

            <!-- Additional Info -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-gray-600">
                    <div>
                        <span class="font-medium">IP Address:</span> {{ $item->ip_address ?? 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium">User Agent:</span>
                        <span class="truncate block sm:inline" title="{{ $item->user_agent }}">
                            {{ Str::limit($item->user_agent ?? 'N/A', 50) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm p-6 sm:p-8 border border-gray-100 text-center">
            <i class="fas fa-comment-slash text-gray-300 text-3xl sm:text-4xl mb-4"></i>
            <h3 class="text-base sm:text-lg font-semibold text-gray-500 mb-2">No Feedback Found</h3>
            <p class="text-sm sm:text-base text-gray-400">No feedback matches your current filters.</p>
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
function updateFeedbackStatus(feedbackId, status) {
    if (!confirm(`Are you sure you want to mark this feedback as ${status}?`)) {
        return;
    }

    const url = '{{ url("/") }}/admin/feedback/' + feedbackId + '/status';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Success', data.message, 'success');

            // Reload page after 1 second
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification('Error', data.message || 'Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'An error occurred while updating status', 'error');
    });
}

function showNotification(title, message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg border max-w-sm animate-fade-in-down ${
        type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'
    }`;

    notification.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} text-xl"></i>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium">${title}</h3>
                <p class="text-sm mt-1">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>

<style>
@keyframes fade-in-down {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.3s ease-out;
}
</style>
@endsection
