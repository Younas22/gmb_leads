<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ucfirst(auth()->user()->user_type) }} Dashboard - Customer Nearme</title>
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
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pl-16 lg:pl-8">
                <div class="flex items-center space-x-4">
                    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        Free Plan
                    </span>
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
        </header>


        <!-- Dashboard Content -->
            @yield('content')

    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>


    <!-- Feedback Modal -->
<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center">
                    <div class="bg-blue-100 rounded-full p-2 mr-3">
                        <i class="fas fa-comment-alt text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Share Your Feedback</h3>
                        <p class="text-sm text-gray-500">Help us improve our lead generation tool</p>
                    </div>
                </div>
                <button onclick="closeFeedbackModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
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

// Reset stars on mouse leave
document.querySelector('.flex.justify-center.space-x-2').addEventListener('mouseleave', function() {
    updateStarDisplay();
});

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

// Form submission
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeFeedbackModal();
            showSuccessToast();
        } else {
            alert(data.message || 'Error submitting feedback');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting feedback. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Show success toast
function showSuccessToast() {
    const toast = document.getElementById('successToast');
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('feedbackModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFeedbackModal();
    }
});
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