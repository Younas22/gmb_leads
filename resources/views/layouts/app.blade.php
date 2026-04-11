<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucfirst(auth()->user()->user_type) }} Dashboard - {{ \App\Models\Setting::get('site_name', config('app.name')) }}</title>

    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobile-menu-btn" class="bg-primary-600 text-white p-2 rounded-lg shadow-lg">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>

    <!-- Sidebar -->
        @include('layouts.sidebar')
    <!-- Main Content -->
    <div id="main-content" class="lg:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pl-16 lg:pl-8">
                <div class="flex items-center space-x-4">
                    <!-- Fullscreen / Sidebar Toggle -->
                    <button id="sidebar-toggle-btn" onclick="toggleSidebarFullscreen()" title="Toggle Fullscreen"
                        class="hidden lg:inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                        <i id="sidebar-toggle-icon" class="fas fa-expand-alt text-sm"></i>
                    </button>
                    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
                    @php
                        $activeSubscription = auth()->user()->activeSubscription();
                        $showCountdown = false;
                        $daysLeft = 0;
                        $endDate = null;

                        if ($activeSubscription && $activeSubscription->end_date) {
                            $endDate = $activeSubscription->end_date;
                            $daysLeft = now()->diffInDays($endDate, false);
                            $showCountdown = $daysLeft >= 0 && $daysLeft <= 10;
                        }
                    @endphp

                    @if($activeSubscription)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            {{ $activeSubscription->package->name ?? 'Free Plan' }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            Free Plan
                        </span>
                    @endif
                </div>

                <div class="flex items-center space-x-4" style="display: none;">
                    <!-- Search Bar -->
                    <div class="relative hidden md:block">
                        <input type="text" placeholder="Quick search..." class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-orange-500 rounded-full"></span>
                    </button>
                </div>
            </div>

            <!-- Subscription Expiry Countdown Banner -->
            @if($showCountdown)
            <div class="bg-gradient-to-r from-red-50 to-orange-50 border-t border-red-200 px-4 lg:px-8 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-100 rounded-full p-2">
                            <i class="fas fa-clock text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-red-900">
                                Your subscription is expiring soon!
                            </p>
                            <p class="text-xs text-red-700">
                                Renew now to continue enjoying uninterrupted service
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div id="countdown-timer" class="text-right" data-end-date="{{ $endDate->toIso8601String() }}">
                            <div class="flex items-center space-x-2 bg-white rounded-lg px-4 py-2 shadow-sm">
                                <i class="fas fa-hourglass-half text-orange-600"></i>
                                <div>
                                    <div class="flex items-center space-x-1 text-lg font-bold text-gray-900">
                                        <span id="countdown-days">{{ $daysLeft }}</span>
                                        <span class="text-xs font-normal text-gray-600">days</span>
                                        <span id="countdown-hours">00</span>
                                        <span class="text-xs font-normal text-gray-600">hrs</span>
                                        <span id="countdown-minutes">00</span>
                                        <span class="text-xs font-normal text-gray-600">min</span>
                                        <span id="countdown-seconds">00</span>
                                        <span class="text-xs font-normal text-gray-600">sec</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('user.subscription') }}" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors duration-200 flex items-center space-x-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Renew Now</span>
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </header>

        <!-- Flash Messages -->
        @if(session('success'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-green-50 border-l-4 border-green-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div id="flashMessage" class="mx-4 lg:mx-8 mt-4">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-yellow-600 hover:text-yellow-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Dashboard Content -->
            @yield('content')

    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>


    <!-- Feedback Modal -->
<style>
    .thin-scrollbar::-webkit-scrollbar { width: 4px; }
    .thin-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .thin-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
    .thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    .thin-scrollbar { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
</style>
<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto thin-scrollbar">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200">
                <div class="flex items-center min-w-0">
                    <div class="bg-blue-100 rounded-full p-2 mr-2 sm:mr-3 flex-shrink-0">
                        <i class="fas fa-comment-alt text-blue-600 text-sm sm:text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 truncate">Share Your Feedback</h3>
                        <p class="text-xs sm:text-sm text-gray-500 truncate">Help us improve our lead generation tool</p>
                    </div>
                </div>
                <button onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 ml-2">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

         <!-- Include Feedback Modal from previous code -->
        @include('partials.feedback-modal')
        </div>
    </div>
</div>

<!-- Success Toast (initially hidden) -->
<div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 hidden">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Feedback submitted successfully!</span>
    </div>
</div>

<script>
let selectedRating = 0;

// Open modal
function openFeedbackModal() {
    document.getElementById('feedbackModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Close modal
function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    resetForm();
}

// Reset form
function resetForm() {
    document.getElementById('feedbackForm').reset();
    selectedRating = 0;
    updateStarDisplay();
    document.getElementById('ratingText').textContent = 'Click to rate';
    document.getElementById('ratingValue').value = '';
}

// Update star display
function updateStarDisplay() {
    document.querySelectorAll('.star-rating').forEach((star, index) => {
        const starIcon = star.querySelector('i');
        if (index < selectedRating) {
            starIcon.className = 'fas fa-star text-2xl text-yellow-400';
        } else {
            starIcon.className = 'fas fa-star text-2xl text-gray-300';
        }
    });
}

// Highlight stars on hover
function highlightStars(rating) {
    document.querySelectorAll('.star-rating').forEach((star, index) => {
        const starIcon = star.querySelector('i');
        if (index < rating) {
            starIcon.className = 'fas fa-star text-2xl text-yellow-400';
        } else {
            starIcon.className = 'fas fa-star text-2xl text-gray-300';
        }
    });
}

// Update rating text
function updateRatingText() {
    const ratingTexts = {
        1: 'Poor - Needs major improvements',
        2: 'Fair - Below expectations',
        3: 'Good - Meets expectations',
        4: 'Very Good - Above expectations',
        5: 'Excellent - Exceeds expectations'
    };
    document.getElementById('ratingText').textContent = ratingTexts[selectedRating] || 'Click to rate';
}

// Show success toast
function showSuccessToast() {
    const toast = document.getElementById('successToast');
    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Wait for DOM to be ready before attaching event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Star rating functionality
    document.querySelectorAll('.star-rating').forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            document.getElementById('ratingValue').value = selectedRating;
            updateStarDisplay();
            updateRatingText();
        });

        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            highlightStars(hoverRating);
        });
    });

    // Reset stars on mouse leave
    const starContainer = document.querySelector('.flex.justify-center.space-x-2');
    if (starContainer) {
        starContainer.addEventListener('mouseleave', function() {
            updateStarDisplay();
        });
    }

    // Form submission
    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitFeedback');
            const originalText = submitBtn.innerHTML;

            // Validation
            const message = document.getElementById('feedbackMessage').value.trim();
            const feedbackType = document.querySelector('input[name="feedback_type"]:checked');

            if (!selectedRating) {
                alert('Please provide a rating');
                return;
            }

            if (!feedbackType) {
                alert('Please select feedback type');
                return;
            }

            if (message.length < 10) {
                alert('Please provide at least 10 characters of feedback');
                return;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

            // Prepare form data
            const formData = new FormData(this);

            // Fix checkbox value - ensure it's boolean
            const contactPermission = document.querySelector('input[name="contact_permission"]').checked;
            formData.set('contact_permission', contactPermission ? '1' : '0');

            // Submit via AJAX
            fetch('{{ route("user.feedback.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    closeFeedbackModal();
                    showSuccessToast();
                    // Reload page after 1 second to show updated feedback
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.message || 'Error submitting feedback');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting feedback: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    // Close modal when clicking outside
    const feedbackModal = document.getElementById('feedbackModal');
    if (feedbackModal) {
        feedbackModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedbackModal();
            }
        });
    }
});
</script>

<!-- Auto-hide Flash Messages -->
<script>
// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('#flashMessage');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000); // Hide after 5 seconds
    });
});
</script>

<!-- Subscription Countdown Timer -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countdownTimer = document.getElementById('countdown-timer');

    if (!countdownTimer) return;

    const endDate = new Date(countdownTimer.getAttribute('data-end-date'));

    function updateCountdown() {
        const now = new Date();
        const timeDiff = endDate - now;

        if (timeDiff <= 0) {
            // Subscription expired
            document.getElementById('countdown-days').textContent = '0';
            document.getElementById('countdown-hours').textContent = '00';
            document.getElementById('countdown-minutes').textContent = '00';
            document.getElementById('countdown-seconds').textContent = '00';
            return;
        }

        // Calculate time components
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

        // Update display with leading zeros
        document.getElementById('countdown-days').textContent = days;
        document.getElementById('countdown-hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = String(seconds).padStart(2, '0');
    }

    // Update immediately and then every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
});
</script>

<!-- Fullscreen / Sidebar Toggle -->
<script>
let sidebarHidden = false;

function toggleSidebarFullscreen() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const icon = document.getElementById('sidebar-toggle-icon');

    sidebarHidden = !sidebarHidden;

    if (sidebarHidden) {
        sidebar.style.transform = 'translateX(-100%)';
        mainContent.classList.remove('lg:ml-64');
        mainContent.classList.add('ml-0');
        icon.classList.remove('fa-expand-alt');
        icon.classList.add('fa-compress-alt');
    } else {
        sidebar.style.transform = '';
        mainContent.classList.add('lg:ml-64');
        mainContent.classList.remove('ml-0');
        icon.classList.remove('fa-compress-alt');
        icon.classList.add('fa-expand-alt');
    }
}
</script>

<!-- JavaScript for Mobile Menu Toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeSidebarBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    
    // Open sidebar
    mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    });
    
    // Close sidebar function
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scroll
    }
    
    // Close button click
    closeSidebarBtn.addEventListener('click', closeSidebar);
    
    // Backdrop click
    backdrop.addEventListener('click', closeSidebar);
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
            closeSidebar();
        }
    });
});
</script>
    @stack('scripts')
</body>
</html>