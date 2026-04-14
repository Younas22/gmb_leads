<!-- Welcome Tutorial Modal (One Time Only) -->
<div id="welcomeTutorialModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 {{ $user->hasSeenWelcomeTutorial() ? 'hidden' : '' }}">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 p-4 text-white relative flex-shrink-0">
                <button onclick="closeWelcomeTutorial(false)" class="absolute top-3 right-3 text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
                <div class="flex items-center space-x-3">
                    <div class="bg-white bg-opacity-20 rounded-full p-2">
                        <i class="fas fa-rocket text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold leading-tight">Welcome to CustomerNearMe!</h2>
                        <p class="text-primary-100 text-xs mt-0.5">Quick 3-minute overview to get you started</p>
                    </div>
                </div>
            </div>

            <!-- Video Container -->
            <div class="relative bg-black flex-1 min-h-0">

            <div class="aspect-video bg-gray-900 flex items-center justify-center h-full">
                    @if(env('WELCOME_TUTORIAL_VIDEO_ID'))
                        <!-- YouTube Video Embed -->
                        <iframe 
                            id="welcomeVideo"
                            class="w-full h-full" 
                            src="https://www.youtube.com/embed/{{ env('WELCOME_TUTORIAL_VIDEO_ID') }}?autoplay=1&mute=1&rel=0&showinfo=0&modestbranding=1&controls=1" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    @else
                        <!-- Placeholder for when video is not available -->
                        <div class="welcome-video-placeholder">
                            <i class="fas fa-play-circle text-6xl mb-4 opacity-50"></i>
                            <h3 class="text-2xl font-bold mb-2">Welcome Tutorial Video</h3>
                            <p class="text-gray-300 mb-6">Video ID: {{ env('WELCOME_TUTORIAL_VIDEO_ID', 'Not Set') }}</p>
                            <div class="space-y-3">
                                <a href="https://www.youtube.com/watch?v={{ env('WELCOME_TUTORIAL_VIDEO_ID') }}" 
                                   target="_blank" 
                                   class="inline-block bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg">
                                    <i class="fab fa-youtube mr-2"></i>Watch on YouTube
                                </a>
                                <br>
                                <button onclick="skipToFullTutorials()" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg">
                                    <i class="fas fa-graduation-cap mr-2"></i>View All Tutorials
                                </button>
                                <button onclick="closeWelcomeTutorial(true)" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg ml-3">
                                    <i class="fas fa-check mr-2"></i>Skip for Now
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                
                
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-gray-50 border-t border-gray-100 flex-shrink-0">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="dontShowAgain" class="w-3.5 h-3.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-xs text-gray-500">Don't show again</span>
                        </label>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="closeWelcomeTutorial(false)" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-700 hover:bg-gray-100 transition-colors">
                            Maybe Later
                        </button>
                        <button onclick="closeWelcomeTutorial(true)" class="px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs transition-colors">
                            <i class="fas fa-check mr-1"></i>Got It!
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
// Welcome Tutorial Modal Functions
function closeWelcomeTutorial(markAsSeen = true) {
    const modal = document.getElementById('welcomeTutorialModal');
    if (!modal) return;
    
    const dontShowAgain = document.getElementById('dontShowAgain')?.checked || false;
    
    // Stop video if playing
    const iframe = document.getElementById('welcomeVideo');
    if (iframe && iframe.src) {
        iframe.src = iframe.src; // This reloads and stops the video
    }
    
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    
    // Hide modal with animation
    modal.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);

    // Mark as seen if user completed or chose "don't show again"
    if (markAsSeen || dontShowAgain) {
        fetch('{{ route("user.welcome-tutorial.mark-seen") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ 
                seen: true,
                dont_show_again: dontShowAgain
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Tutorial status updated:', data);
        })
        .catch(error => {
            console.error('Error updating tutorial status:', error);
        });
    }
}

function skipToFullTutorials() {
    closeWelcomeTutorial(true);
    setTimeout(() => {
        window.location.href = '{{ route("user.tutorials") }}';
    }, 100);
}

// Initialize modal when page loads
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('welcomeTutorialModal');
    const userHasSeenTutorial = {{ auth()->user()->welcome_tutorial_seen ?? false ? 'true' : 'false' }};
    
    console.log('User has seen tutorial:', userHasSeenTutorial);
    console.log('Video ID from ENV:', '{{ env("WELCOME_TUTORIAL_VIDEO_ID") }}');
    
    if (!userHasSeenTutorial && modal && !modal.classList.contains('hidden')) {
        // Add modal-open class to body
        document.body.classList.add('modal-open');
        modal.style.opacity = '1';
        
        console.log('Showing welcome tutorial modal');
    } else {
        // Ensure modal is completely hidden
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.classList.remove('modal-open');
        console.log('Modal hidden - user has seen tutorial or modal not found');
    }
});

// Handle clicks outside modal
document.addEventListener('click', function(e) {
    const modal = document.getElementById('welcomeTutorialModal');
    const modalContent = document.querySelector('.welcome-modal-content');
    
    if (e.target === modal && modal && !modal.classList.contains('hidden')) {
        if (confirm('Close the welcome tutorial? You can always access tutorials from the menu later.')) {
            closeWelcomeTutorial(false);
        }
    }
});

// Handle ESC key
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('welcomeTutorialModal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
        e.preventDefault();
        if (confirm('Close the welcome tutorial? You can always access tutorials from the menu later.')) {
            closeWelcomeTutorial(false);
        }
    }
});

// Enhanced YouTube API integration
@if(env('WELCOME_TUTORIAL_VIDEO_ID'))
let videoCompleted = false;
let player = null;

// Load YouTube API
function loadYouTubeAPI() {
    if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        tag.onload = function() {
            console.log('YouTube API loaded');
        };
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    } else {
        initializePlayer();
    }
}

function onYouTubeIframeAPIReady() {
    console.log('YouTube API ready');
    initializePlayer();
}

function initializePlayer() {
    const videoElement = document.getElementById('welcomeVideo');
    if (videoElement && typeof YT !== 'undefined' && YT.Player) {
        try {
            player = new YT.Player('welcomeVideo', {
                events: {
                    'onReady': function(event) {
                        console.log('YouTube player ready');
                        setTimeout(() => {
                            event.target.unMute();
                        }, 1000);
                    },
                    'onStateChange': function(event) {
                        console.log('Player state changed:', event.data);
                        if (event.data === YT.PlayerState.ENDED && !videoCompleted) {
                            videoCompleted = true;
                            showVideoCompletionMessage();
                        }
                    },
                    'onError': function(event) {
                        console.error('YouTube player error:', event.data);
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing YouTube player:', error);
        }
    }
}

function showVideoCompletionMessage() {
    const toast = document.createElement('div');
    toast.className = 'welcome-toast';
    toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Tutorial completed! Welcome to BusinessFinder.';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Initialize YouTube API when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('welcomeTutorialModal');
    const userHasSeenTutorial = {{ auth()->user()->welcome_tutorial_seen ?? false ? 'true' : 'false' }};
    
    if (!userHasSeenTutorial && modal && !modal.classList.contains('hidden')) {
        setTimeout(() => {
            loadYouTubeAPI();
        }, 500);
    }
});
@endif
</script>