

@extends('layouts.app')

@section('title', 'Watch Tutorials - Business Search Tool')

@section('content')
        <!-- Tutorial Content -->
        <div class="p-4 lg:p-6">
            <!-- Welcome Section (compact) -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-xl px-5 py-3 mb-4 text-white">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white bg-opacity-20 rounded-full p-2">
                            <i class="fas fa-play-circle text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-base font-bold leading-tight">Master BusinessFinder</h1>
                            <p class="text-primary-100 text-xs">Complete video tutorials to become a lead generation expert</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="bg-white bg-opacity-20 rounded-lg px-3 py-1">
                            <span class="text-xs font-medium">{{ count($tutorials) }} Tutorials</span>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg px-3 py-1">
                            <span class="text-xs font-medium">{{ $totalDuration }}+ Min</span>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg px-3 py-1">
                            <span class="text-xs font-medium">Beginner to Advanced</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Overview (compact) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-3 mb-4">
                <div class="flex items-center gap-4">
                    <span class="text-xs font-semibold text-gray-700 whitespace-nowrap">Your Progress</span>
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $progressPercentage }}%"></div>
                    </div>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $completedCount }}/{{ count($tutorials) }} &nbsp; {{ $progressPercentage }}%</span>
                    <button onclick="resetProgress()" class="text-primary-600 hover:text-primary-700 text-xs font-medium whitespace-nowrap">
                        Reset
                    </button>
                </div>
            </div>

            <!-- All Tutorials -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-rocket text-2xl"></i>
                        <div>
                            <h3 class="text-xl font-bold">All Tutorials</h3>
                            <p class="text-blue-100">Dashboard, Find Leads, My Leads, Extension, Subscription, Profile & more</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($tutorials as $tutorial)
                        <div class="tutorial-item group cursor-pointer border rounded-lg overflow-hidden hover:border-primary-300 hover:bg-primary-50 transition-all"
                             data-tutorial="{{ $tutorial['key'] }}"
                             data-youtube-id="{{ $tutorial['youtube_id'] ?? '' }}">

                            {{-- Thumbnail --}}
                            @if(!empty($tutorial['youtube_id']))
                                <div class="relative w-full overflow-hidden" style="padding-top: 56.25%;">
                                    <img
                                        src="https://img.youtube.com/vi/{{ $tutorial['youtube_id'] }}/mqdefault.jpg"
                                        alt="{{ $tutorial['title'] }}"
                                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                                        <div class="w-12 h-12 bg-white bg-opacity-90 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                            <i class="fas fa-play text-primary-600 text-lg ml-1"></i>
                                        </div>
                                    </div>
                                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-70 text-white text-xs px-2 py-0.5 rounded">
                                        {{ $tutorial['duration'] }}
                                    </div>
                                    @if(in_array($tutorial['key'], $completedTutorials))
                                        <div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-0.5 rounded-full flex items-center gap-1">
                                            <i class="fas fa-check text-xs"></i> Completed
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Info --}}
                            <div class="flex items-center space-x-3 p-3">
                                <div class="relative flex-shrink-0">
                                    <div class="w-10 h-10 bg-{{ $tutorial['color'] }}-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-{{ $tutorial['icon'] }} text-{{ $tutorial['color'] }}-600"></i>
                                    </div>
                                    <div class="absolute -top-1 -right-1 w-4 h-4
                                        {{ in_array($tutorial['key'], $completedTutorials) ? 'bg-green-500' : 'bg-gray-300' }}
                                        rounded-full flex items-center justify-center">
                                        <i class="fas {{ in_array($tutorial['key'], $completedTutorials) ? 'fa-check text-white' : 'fa-clock text-gray-600' }} text-xs" style="font-size:7px"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-800 group-hover:text-primary-700 text-sm leading-tight">
                                        {{ $tutorial['order'] }}. {{ $tutorial['title'] }}
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $tutorial['description'] }}</p>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Video Player Modal -->
            <div id="videoModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <div>
                                <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Tutorial Title</h3>
                                <p id="modalDescription" class="text-gray-600 text-sm mt-1">Tutorial description</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button onclick="markAsCompleted()" id="markCompletedBtn" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-check mr-2"></i>Mark as Completed
                                </button>
                                <button onclick="closeVideoModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <i class="fas fa-times text-2xl"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Video Player -->
                        <div class="relative bg-black">
                            <div id="videoContainer" class="aspect-video bg-gray-900 flex items-center justify-center">
                                <div id="videoPlaceholder" class="text-center text-white">
                                    <i class="fas fa-video text-6xl mb-4 opacity-50"></i>
                                    <p class="text-xl font-medium mb-2">Video Coming Soon</p>
                                    <p class="text-gray-400">Tutorial video will be uploaded here</p>
                                </div>
                                <iframe id="youtubePlayer" class="hidden w-full h-full" frameborder="0" allowfullscreen></iframe>
                            </div>
                        </div>

<!-- Tutorial Info -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-2">
                                    <h4 class="font-semibold text-gray-800 mb-3">What You'll Learn</h4>
                                    <ul id="learningObjectives" class="space-y-2 text-sm text-gray-600">
                                        <!-- Learning objectives will be populated by JavaScript -->
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-3">Tutorial Details</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Duration:</span>
                                            <span id="modalDuration" class="font-medium">6 min</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Difficulty:</span>
                                            <span class="font-medium text-green-600">Beginner</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Status:</span>
                                            <span id="modalStatus" class="font-medium">Not Started</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="mt-8 bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6 border border-green-200">
                <div class="flex items-start space-x-4">
                    <div class="bg-green-500 rounded-full p-2 flex-shrink-0">
                        <i class="fas fa-lightbulb text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Pro Tips for Learning</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Follow tutorials in order for best results</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Practice each feature after watching</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Take notes on key shortcuts and tips</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>Rewatch sections if needed</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button id="backToTopBtn" onclick="scrollToTop()"
            class="fixed bottom-8 right-8 z-50 bg-primary-600 hover:bg-primary-700 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg transition-all duration-300 opacity-0 pointer-events-none">
            <i class="fas fa-arrow-up text-lg"></i>
        </button>

        <script>
            // Tutorial data from backend
            const tutorials = @json($tutorialsData);
            let completedTutorials = @json($completedTutorials);

            // Tutorial functionality
            document.querySelectorAll('.tutorial-item').forEach(item => {
                item.addEventListener('click', () => {
                    const tutorialKey = item.dataset.tutorial;
                    const youtubeId = item.dataset.youtubeId;
                    openVideoModal(tutorialKey, youtubeId);
                });
            });

            function openVideoModal(tutorialKey, youtubeId = null) {
                const tutorial = tutorials[tutorialKey];
                
                document.getElementById('modalTitle').textContent = tutorial.title;
                document.getElementById('modalDescription').textContent = tutorial.description;
                document.getElementById('modalDuration').textContent = tutorial.duration;
                
                // Set YouTube player
                if (youtubeId) {
                    document.getElementById('youtubePlayer').src = `https://www.youtube.com/embed/${youtubeId}`;
                    document.getElementById('youtubePlayer').classList.remove('hidden');
                    document.getElementById('videoPlaceholder').classList.add('hidden');
                } else {
                    document.getElementById('youtubePlayer').classList.add('hidden');
                    document.getElementById('videoPlaceholder').classList.remove('hidden');
                }
                
                // Populate learning objectives
                const objectivesList = document.getElementById('learningObjectives');
                objectivesList.innerHTML = '';
                tutorial.objectives.forEach(objective => {
                    const li = document.createElement('li');
                    li.innerHTML = `<i class="fas fa-check text-green-500 mr-2"></i>${objective}`;
                    objectivesList.appendChild(li);
                });
                
                // Update status
                const isCompleted = completedTutorials.includes(tutorialKey);
                document.getElementById('modalStatus').textContent = isCompleted ? 'Completed' : 'Not Started';
                document.getElementById('modalStatus').className = isCompleted ? 'font-medium text-green-600' : 'font-medium text-gray-600';
                
                document.getElementById('videoModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeVideoModal() {
                document.getElementById('videoModal').classList.add('hidden');
                document.getElementById('youtubePlayer').src = '';
                document.body.style.overflow = 'auto';
            }

            function markAsCompleted() {
                const currentTutorial = document.getElementById('modalTitle').textContent;
                const tutorialKey = Object.keys(tutorials).find(key => 
                    tutorials[key].title === currentTutorial
                );
                
                if (tutorialKey && !completedTutorials.includes(tutorialKey)) {
                    // Send AJAX request to mark as completed
                    fetch('{{ route("user.tutorials.complete") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ tutorial_key: tutorialKey })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            completedTutorials.push(tutorialKey);
                            updateTutorialUI(tutorialKey);
                            updateProgress();
                            alert('Tutorial marked as completed! 🎉');
                            closeVideoModal();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error marking tutorial as completed');
                    });
                }
            }

            function updateTutorialUI(tutorialKey) {
                const tutorialItem = document.querySelector(`[data-tutorial="${tutorialKey}"]`);
                if (tutorialItem) {
                    const statusBadge = tutorialItem.querySelector('.text-xs.px-2');
                    const checkIcon = tutorialItem.querySelector('.w-5.h-5 > i');
                    
                    statusBadge.className = 'text-xs bg-green-100 text-green-700 px-2 py-1 rounded';
                    statusBadge.textContent = 'Completed';
                    checkIcon.className = 'fas fa-check text-white text-xs';
                    checkIcon.parentElement.className = 'absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center';
                }
            }

            function updateProgress() {
                const total = Object.keys(tutorials).length;
                const completed = completedTutorials.length;
                const percentage = Math.round((completed / total) * 100);
                
                // Update progress bar
                document.querySelector('.bg-gradient-to-r.from-green-500').style.width = percentage + '%';
                location.reload(); // Refresh to update server-side progress
            }

            function resetProgress() {
                if (confirm('Are you sure you want to reset all progress? This cannot be undone.')) {
                    fetch('{{ route("user.tutorials.reset") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(() => location.reload());
                }
            }

            // Auto-open tutorial from URL hash (e.g. #add-extension)
            window.addEventListener('load', function() {
                const hash = window.location.hash.replace('#', '');
                if (hash && tutorials[hash]) {
                    const item = document.querySelector(`[data-tutorial="${hash}"]`);
                    const youtubeId = item ? item.dataset.youtubeId : null;
                    openVideoModal(hash, youtubeId);
                    history.replaceState(null, '', window.location.pathname);
                }
            });

            // Close modal when clicking outside
            document.getElementById('videoModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeVideoModal();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeVideoModal();
                }
            });

            // Back to Top button
            const backToTopBtn = document.getElementById('backToTopBtn');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 300) {
                    backToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
                    backToTopBtn.classList.add('opacity-100');
                } else {
                    backToTopBtn.classList.add('opacity-0', 'pointer-events-none');
                    backToTopBtn.classList.remove('opacity-100');
                }
            });

            function scrollToTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        </script>
@endsection