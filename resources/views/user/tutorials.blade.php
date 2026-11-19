@extends('layouts.app')

@section('title', 'Watch Tutorials - Business Search Tool')

@section('content')
@include('user._ui-kit')
<style>
    .tt-card { display: flex; flex-direction: column; text-align: left; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; transition: border-color .15s, box-shadow .15s; }
    .tt-card:hover { border-color: #FDBA74; box-shadow: 0 4px 14px -8px rgba(249, 115, 22, .45); }
    .tt-card:focus-visible { outline: 2px solid #F97316; outline-offset: 2px; }
    .tt-card img { transition: transform .3s; }
    .tt-card:hover img { transform: scale(1.04); }
    @media (prefers-reduced-motion: reduce) { .tt-card img { transition: none; } }
</style>

<div class="cnm-ui ui-page ui-stack">
    <!-- Heading -->
    <div class="ui-head">
        <div class="ui-head-main">
            <span class="ui-head-icon"><i class="fas fa-play-circle"></i></span>
            <div class="min-w-0">
                <h1 class="ui-title">Tutorials</h1>
                <p class="ui-sub">Short videos that take you from your first search to your first client: finding leads, using AI and sending outreach.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <span class="ui-badge is-brand"><i class="fas fa-film text-[10px]"></i>{{ count($tutorials) }} Tutorials</span>
            <span class="ui-badge"><i class="far fa-clock text-[10px]"></i>{{ $totalDuration }}+ Min</span>
            <span class="ui-badge"><i class="fas fa-signal text-[10px]"></i>Beginner to Advanced</span>
        </div>
    </div>

    <!-- Progress -->
    <div class="ui-card px-4 py-2.5 flex flex-wrap items-center gap-x-4 gap-y-2">
        <span class="text-[12.5px] font-semibold text-gray-900 whitespace-nowrap"><i class="fas fa-trophy text-orange-500 mr-1"></i>Your Progress</span>
        <div class="flex-1 min-w-[140px] ui-bar !mt-0 !h-2" role="progressbar" aria-label="Tutorial progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $progressPercentage }}">
            <span id="tt-progress-bar" class="!bg-green-500" style="width: {{ $progressPercentage }}%"></span>
        </div>
        <span class="text-[12px] text-slate-500 whitespace-nowrap">{{ $completedCount }}/{{ count($tutorials) }} done · {{ $progressPercentage }}%</span>
        <button onclick="resetProgress()" class="ui-btn-light ui-sm">Reset</button>
    </div>

    <!-- All Tutorials -->
    <section class="ui-card">
        <div class="ui-card-h">
            <div>
                <h2 class="ui-card-t"><i class="fas fa-rocket"></i> All Tutorials</h2>
                <p class="ui-card-d">Dashboard, Find Leads, My Leads, Extension, Subscription, Profile &amp; more. Click a video to watch.</p>
            </div>
        </div>
        <div class="p-3 sm:p-4 grid grid-cols-1 min-[440px]:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($tutorials as $tutorial)
                @php $done = in_array($tutorial['key'], $completedTutorials); @endphp
                <button type="button" class="tutorial-item tt-card group"
                        data-tutorial="{{ $tutorial['key'] }}"
                        data-youtube-id="{{ $tutorial['youtube_id'] ?? '' }}"
                        aria-label="Watch: {{ $tutorial['title'] }}">

                    {{-- Thumbnail --}}
                    @if(!empty($tutorial['youtube_id']))
                        <span class="relative block w-full overflow-hidden bg-slate-100" style="padding-top: 56.25%;">
                            <img src="https://img.youtube.com/vi/{{ $tutorial['youtube_id'] }}/mqdefault.jpg" alt="" loading="lazy"
                                 class="absolute inset-0 w-full h-full object-cover">
                            <span class="absolute inset-0 bg-black/25 flex items-center justify-center">
                                <span class="w-9 h-9 bg-white/95 rounded-full flex items-center justify-center shadow">
                                    <i class="fas fa-play text-orange-500 text-[12px] ml-0.5"></i>
                                </span>
                            </span>
                            <span class="absolute bottom-1.5 right-1.5 bg-black/70 text-white text-[10.5px] px-1.5 py-0.5 rounded">{{ $tutorial['duration'] }}</span>
                            @if($done)
                                <span class="tt-done absolute top-1.5 left-1.5 bg-green-500 text-white text-[10.5px] font-medium px-1.5 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="fas fa-check text-[9px]"></i> Completed
                                </span>
                            @endif
                        </span>
                    @endif

                    {{-- Info --}}
                    <span class="flex items-start gap-2.5 p-2.5">
                        <span class="relative flex-shrink-0 w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center text-[12px]">
                            <i class="fas fa-{{ $tutorial['icon'] }}"></i>
                            <span class="tt-dot absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white {{ $done ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[12.5px] font-semibold text-gray-900 group-hover:text-orange-700 leading-snug">{{ $tutorial['order'] }}. {{ $tutorial['title'] }}</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ $tutorial['description'] }}</span>
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <!-- Quick Tips -->
    <div class="ui-note is-ok !items-start">
        <i class="fas fa-lightbulb text-green-600"></i>
        <div class="flex-1">
            <p class="font-semibold text-[13px] text-gray-900 mb-1">Pro Tips for Learning</p>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-[12px] text-slate-700">
                <li><i class="fas fa-check-circle text-green-500 mr-1.5"></i>Follow tutorials in order for best results</li>
                <li><i class="fas fa-check-circle text-green-500 mr-1.5"></i>Practice each feature right after watching</li>
                <li><i class="fas fa-check-circle text-green-500 mr-1.5"></i>Fill in your AI Sales Profile before using AI Outreach</li>
                <li><i class="fas fa-check-circle text-green-500 mr-1.5"></i>Rewatch any section whenever you need</li>
            </ul>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div id="videoModal" class="cnm-ui fixed inset-0 bg-black/75 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[92vh] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-slate-100">
                <div class="min-w-0">
                    <h3 id="modalTitle" class="text-[15px] font-semibold text-gray-900">Tutorial Title</h3>
                    <p id="modalDescription" class="text-[12px] text-slate-500 mt-0.5">Tutorial description</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="markAsCompleted()" id="markCompletedBtn" class="ui-btn ui-sm !bg-green-600 !border-green-600 hover:!bg-green-700">
                        <i class="fas fa-check"></i>Mark as Completed
                    </button>
                    <button onclick="closeVideoModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Video Player -->
            <div class="bg-black">
                <div id="videoContainer" class="aspect-video bg-gray-900 flex items-center justify-center">
                    <div id="videoPlaceholder" class="text-center text-white">
                        <i class="fas fa-video text-4xl mb-3 opacity-50"></i>
                        <p class="text-[15px] font-medium mb-1">Video Coming Soon</p>
                        <p class="text-[12px] text-gray-400">Tutorial video will be uploaded here</p>
                    </div>
                    <iframe id="youtubePlayer" class="hidden w-full h-full" frameborder="0" allowfullscreen title="Tutorial video"></iframe>
                </div>
            </div>

            <!-- Tutorial Info -->
            <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <h4 class="text-[12.5px] font-semibold text-gray-900 mb-1.5">What You'll Learn</h4>
                    <ul id="learningObjectives" class="space-y-1 text-[12.5px] text-slate-600"></ul>
                </div>
                <div>
                    <h4 class="text-[12.5px] font-semibold text-gray-900 mb-1.5">Tutorial Details</h4>
                    <div class="space-y-1 text-[12.5px]">
                        <div class="flex justify-between"><span class="text-slate-500">Duration:</span><span id="modalDuration" class="font-medium">6 min</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Difficulty:</span><span class="font-medium text-green-600">Beginner</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Status:</span><span id="modalStatus" class="font-medium">Not Started</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<button id="backToTopBtn" onclick="scrollToTop()" aria-label="Back to top"
    class="fixed bottom-6 right-6 z-40 bg-orange-500 hover:bg-orange-600 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-all duration-300 opacity-0 pointer-events-none">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
    // Tutorial data from backend
    const tutorials = @json($tutorialsData);
    let completedTutorials = @json($completedTutorials);
    let currentTutorialKey = null;

    document.querySelectorAll('.tutorial-item').forEach(item => {
        item.addEventListener('click', () => openVideoModal(item.dataset.tutorial, item.dataset.youtubeId));
    });

    function openVideoModal(tutorialKey, youtubeId = null) {
        const tutorial = tutorials[tutorialKey];
        if (!tutorial) return;
        currentTutorialKey = tutorialKey;

        document.getElementById('modalTitle').textContent = tutorial.title;
        document.getElementById('modalDescription').textContent = tutorial.description;
        document.getElementById('modalDuration').textContent = tutorial.duration;

        const player = document.getElementById('youtubePlayer');
        if (youtubeId) {
            player.src = `https://www.youtube.com/embed/${encodeURIComponent(youtubeId)}`;
            player.classList.remove('hidden');
            document.getElementById('videoPlaceholder').classList.add('hidden');
        } else {
            player.classList.add('hidden');
            document.getElementById('videoPlaceholder').classList.remove('hidden');
        }

        const objectivesList = document.getElementById('learningObjectives');
        objectivesList.replaceChildren();
        (tutorial.objectives || []).forEach(objective => {
            const li = document.createElement('li');
            const icon = document.createElement('i');
            icon.className = 'fas fa-check text-green-500 mr-2';
            li.append(icon, document.createTextNode(objective));
            objectivesList.appendChild(li);
        });

        const isCompleted = completedTutorials.includes(tutorialKey);
        const status = document.getElementById('modalStatus');
        status.textContent = isCompleted ? 'Completed' : 'Not Started';
        status.className = isCompleted ? 'font-medium text-green-600' : 'font-medium text-slate-600';
        document.getElementById('markCompletedBtn').classList.toggle('hidden', isCompleted);

        document.getElementById('videoModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeVideoModal() {
        document.getElementById('videoModal').classList.add('hidden');
        document.getElementById('youtubePlayer').src = '';
        document.body.style.overflow = 'auto';
    }

    function markAsCompleted() {
        const tutorialKey = currentTutorialKey;
        if (!tutorialKey || completedTutorials.includes(tutorialKey)) return;
        const btn = document.getElementById('markCompletedBtn');
        btn.disabled = true;

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
            if (!data.success) throw new Error();
            completedTutorials.push(tutorialKey);
            closeVideoModal();
            location.reload(); // refresh progress and badges from the server
        })
        .catch(() => {
            btn.disabled = false;
            alert('Error marking tutorial as completed');
        });
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
            const item = document.querySelector(`[data-tutorial="${CSS.escape(hash)}"]`);
            openVideoModal(hash, item ? item.dataset.youtubeId : null);
            history.replaceState(null, '', window.location.pathname);
        }
    });

    document.getElementById('videoModal').addEventListener('click', function(e) {
        if (e.target === this || e.target === this.firstElementChild) closeVideoModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeVideoModal();
    });

    const backToTopBtn = document.getElementById('backToTopBtn');
    window.addEventListener('scroll', function() {
        const show = window.scrollY > 300;
        backToTopBtn.classList.toggle('opacity-0', !show);
        backToTopBtn.classList.toggle('pointer-events-none', !show);
    });

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>
@endsection
