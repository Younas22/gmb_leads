{{--
    "Share Your Feedback" modal.
    - Manual mode: opened from the sidebar / feedback history page, closable.
    - Prompt mode: opens by itself right after login (see App\Services\FeedbackPrompt) and asks
      "Have you used CustomerNearMe before?". Users with real activity in the tool can't close it
      until they submit feedback — whatever they answer. Only a genuinely new user can close it.
--}}
@php
    $fbPrompt = auth()->check() ? \App\Services\FeedbackPrompt::state(auth()->user()) : null;
    $fbActivity = $fbPrompt['activity'] ?? [];
@endphp

<style>
    .thin-scrollbar::-webkit-scrollbar { width: 4px; }
    .thin-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .thin-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
    .thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    .thin-scrollbar { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    .fb-choice input:checked + span { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
</style>

<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden" role="dialog" aria-modal="true" aria-labelledby="feedbackModalTitle">
    <div class="flex items-center justify-center min-h-screen p-2 sm:p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[92vh] overflow-y-auto thin-scrollbar">

            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 border-b border-gray-200">
                <div class="flex items-center min-w-0">
                    <div class="bg-blue-100 rounded-full p-2 mr-3 flex-shrink-0">
                        <i class="fas fa-comment-alt text-blue-600 text-sm sm:text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="feedbackModalTitle" class="text-base sm:text-lg font-semibold text-gray-800 truncate">Share Your Feedback</h3>
                        <p class="text-xs sm:text-sm text-gray-500 truncate">Help us improve our lead generation tool</p>
                    </div>
                </div>
                <button type="button" id="feedbackCloseBtn" onclick="closeFeedbackModal()" aria-label="Close" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 ml-2">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <!-- Activity summary (prompt mode, users who have used the tool) -->
            @if($fbPrompt && $fbPrompt['has_activity'])
                <div id="fbActivity" class="hidden mx-4 sm:mx-5 mt-4 rounded-lg border border-blue-100 bg-blue-50 p-3">
                    <p class="text-xs font-semibold text-blue-900 mb-2">
                        <i class="fas fa-chart-line mr-1"></i> Here's what you've done with {{ \App\Models\Setting::get('site_name', 'CustomerNearMe') }} so far
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-white rounded-md py-2">
                            <p class="text-lg font-bold text-gray-900 leading-tight">{{ number_format($fbActivity['saved_leads']) }}</p>
                            <p class="text-[11px] text-gray-500">Leads saved</p>
                        </div>
                        <div class="bg-white rounded-md py-2">
                            <p class="text-lg font-bold text-gray-900 leading-tight">{{ number_format($fbActivity['searches']) }}</p>
                            <p class="text-[11px] text-gray-500">Searches</p>
                        </div>
                        <div class="bg-white rounded-md py-2">
                            <p class="text-lg font-bold text-gray-900 leading-tight">{{ number_format($fbActivity['exports']) }}</p>
                            <p class="text-[11px] text-gray-500">Exports</p>
                        </div>
                        <div class="bg-white rounded-md py-2">
                            <p class="text-lg font-bold text-gray-900 leading-tight">{{ number_format($fbActivity['lead_center_leads']) }}</p>
                            <p class="text-[11px] text-gray-500">Lead Center leads</p>
                        </div>
                    </div>
                    @if($fbPrompt['member_since'])
                        <p class="text-[11px] text-blue-800 mt-2 text-center">Member since {{ $fbPrompt['member_since']->format('M d, Y') }}</p>
                    @endif
                    <p class="text-xs text-blue-900 mt-2 text-center">Based on this, please tell us how the tool is working for you.</p>
                </div>
            @endif

            <!-- Step 1 (prompt mode): have you used it before? -->
            <div id="fbStepQuestion" class="hidden p-4 sm:p-5">
                <p class="text-sm font-semibold text-gray-800 mb-3">Have you used {{ \App\Models\Setting::get('site_name', 'CustomerNearMe') }} before?</p>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <label class="fb-choice cursor-pointer">
                        <input type="checkbox" class="sr-only fb-used-choice" value="1" onchange="fbAnswerUsedBefore(this)">
                        <span class="flex items-center justify-center gap-2 border border-gray-300 rounded-lg py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-check-circle"></i> Yes, I have
                        </span>
                    </label>
                    <label class="fb-choice cursor-pointer">
                        <input type="checkbox" class="sr-only fb-used-choice" value="0" onchange="fbAnswerUsedBefore(this)">
                        <span class="flex items-center justify-center gap-2 border border-gray-300 rounded-lg py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user-plus"></i> No, I'm new
                        </span>
                    </label>
                </div>

                <!-- Genuinely new user: may close -->
                <div id="fbNewUserBox" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-sm text-gray-700 mb-3">Welcome! Explore the tool first — we'll ask for your feedback after you've had a chance to use it.</p>
                    <div class="flex gap-2">
                        <button type="button" onclick="fbDismissPrompt()" id="fbDismissBtn" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-white">
                            Close
                        </button>
                        <button type="button" onclick="fbShowForm(true)" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                            Give feedback anyway
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shown when the answer was "No" but the account has activity -->
            <p id="fbActivityNote" class="hidden mx-4 sm:mx-5 mt-3 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2.5">
                <i class="fas fa-info-circle mr-1"></i> Our records show you've already used the tool, so please share your feedback before continuing.
            </p>

            <!-- Feedback form -->
            <form id="feedbackForm" class="p-4 sm:p-5" novalidate>
                @csrf
                <input type="hidden" name="used_before" id="fbUsedBefore" value="" disabled>

                <!-- Rating -->
                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">How would you rate our tool? <span class="text-red-500">*</span></label>
                    <div id="fbStars" class="flex justify-center space-x-1 sm:space-x-2 mb-1.5">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="star-rating" data-rating="{{ $i }}" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                <i class="fas fa-star text-2xl text-gray-300 transition-colors"></i>
                            </button>
                        @endfor
                    </div>
                    <p id="ratingText" class="text-center text-xs sm:text-sm text-gray-500">Click to rate</p>
                    <input type="hidden" name="rating" id="ratingValue" value="">
                </div>

                <!-- Feedback (required) -->
                <div class="mb-4">
                    <label for="feedbackMessage" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">Feedback <span class="text-red-500">*</span></label>
                    <textarea name="message" id="feedbackMessage" rows="3" maxlength="2000"
                              placeholder="How is the tool working for you? What do you like, what isn't working?"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm resize-none"></textarea>
                    <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Required · minimum 10 characters</p>
                </div>

                <!-- Suggestion (optional) -->
                <div class="mb-4">
                    <label for="feedbackSuggestion" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">Suggestion <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="suggestion" id="feedbackSuggestion" rows="3" maxlength="2000"
                              placeholder="Any feature or improvement you'd like to see?"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs sm:text-sm resize-none"></textarea>
                </div>

                <p id="fbError" class="hidden text-xs text-red-600 mb-3"></p>

                <div class="flex space-x-2 sm:space-x-3">
                    <button type="button" id="feedbackCancelBtn" onclick="closeFeedbackModal()"
                            class="flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-xs sm:text-sm">
                        Cancel
                    </button>
                    <button type="submit" id="submitFeedback"
                            class="flex-1 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed text-xs sm:text-sm">
                        <i class="fas fa-paper-plane mr-1 sm:mr-2"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Toast -->
<div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-[70] hidden">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span>Feedback submitted successfully!</span>
    </div>
</div>

<script>
(function () {
    const FB_PROMPT = @json($fbPrompt ? ['has_activity' => $fbPrompt['has_activity']] : null);
    const STORE_URL = @json(route('user.feedback.store'));
    const DISMISS_URL = @json(route('user.feedback.dismiss-prompt'));
    const RATING_TEXTS = {
        1: 'Poor - Needs major improvements',
        2: 'Fair - Below expectations',
        3: 'Good - Meets expectations',
        4: 'Very Good - Above expectations',
        5: 'Excellent - Exceeds expectations'
    };

    let selectedRating = 0;
    let locked = false;        // true = can't be closed until feedback is submitted
    let promptMode = false;    // opened automatically after login
    let dismissOnClose = false; // new user without activity: closing records the dismissal

    const $ = (id) => document.getElementById(id);
    const show = (id, on) => { const el = $(id); if (el) el.classList.toggle('hidden', !on); };
    const csrf = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function setLocked(value) {
        locked = value;
        show('feedbackCloseBtn', !value);
        show('feedbackCancelBtn', !value);
    }

    function paintStars(rating) {
        document.querySelectorAll('#fbStars .star-rating i').forEach((icon, i) => {
            icon.className = 'fas fa-star text-2xl transition-colors ' + (i < rating ? 'text-yellow-400' : 'text-gray-300');
        });
    }

    function resetForm() {
        $('feedbackForm').reset();
        selectedRating = 0;
        $('ratingValue').value = '';
        $('ratingText').textContent = 'Click to rate';
        paintStars(0);
        show('fbError', false);
    }

    function fbError(msg) {
        $('fbError').textContent = msg;
        show('fbError', true);
    }

    // ---- open / close ----
    window.openFeedbackModal = function (mode) {
        promptMode = mode === 'prompt' && !!FB_PROMPT;
        dismissOnClose = false;
        resetForm();
        document.querySelectorAll('.fb-used-choice').forEach(c => c.checked = false);
        $('fbUsedBefore').value = '';
        $('fbUsedBefore').disabled = true;
        show('fbNewUserBox', false);
        show('fbActivityNote', false);

        if (promptMode) {
            setLocked(true);
            show('fbStepQuestion', true);
            show('fbActivity', !!FB_PROMPT.has_activity);
            show('feedbackForm', false);
        } else {
            setLocked(false);
            show('fbStepQuestion', false);
            show('fbActivity', false);
            show('feedbackForm', true);
        }

        $('feedbackModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    function hideModal() {
        $('feedbackModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    window.closeFeedbackModal = function () {
        if (locked) return;
        if (dismissOnClose) { fbDismissPrompt(); return; }
        hideModal();
        resetForm();
    };

    // ---- prompt flow ----
    window.fbAnswerUsedBefore = function (input) {
        // Two checkboxes behaving as one yes/no choice.
        document.querySelectorAll('.fb-used-choice').forEach(c => { if (c !== input) c.checked = false; });
        if (!input.checked) { show('fbNewUserBox', false); show('feedbackForm', false); return; }

        const usedBefore = input.value === '1';
        $('fbUsedBefore').value = usedBefore ? '1' : '0';
        $('fbUsedBefore').disabled = false;

        if (usedBefore || FB_PROMPT.has_activity) {
            // Says yes — or says no but the account has activity: feedback is mandatory.
            show('fbNewUserBox', false);
            show('fbActivityNote', !usedBefore);
            fbShowForm(false);
        } else {
            show('feedbackForm', false);
            show('fbActivityNote', false);
            show('fbNewUserBox', true);
            setLocked(false);
            dismissOnClose = true;
        }
    };

    window.fbShowForm = function (closable) {
        setLocked(!closable);
        show('feedbackForm', true);
        $('feedbackMessage').focus();
    };

    window.fbDismissPrompt = function () {
        const btn = $('fbDismissBtn');
        if (btn) btn.disabled = true;

        fetch(DISMISS_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                dismissOnClose = false;
                locked = false;
                hideModal();
                resetForm();
                return;
            }
            // The server found activity on the account: feedback is required after all.
            FB_PROMPT.has_activity = true;
            dismissOnClose = false;
            show('fbNewUserBox', false);
            show('fbActivityNote', true);
            fbShowForm(false);
            fbError(data.message || 'Please share your feedback before continuing.');
        })
        .catch(() => fbError('Something went wrong. Please try again.'))
        .finally(() => { if (btn) btn.disabled = false; });
    };

    // ---- stars ----
    document.querySelectorAll('#fbStars .star-rating').forEach(star => {
        star.addEventListener('click', function () {
            selectedRating = parseInt(this.dataset.rating, 10);
            $('ratingValue').value = selectedRating;
            $('ratingText').textContent = RATING_TEXTS[selectedRating];
            paintStars(selectedRating);
        });
        star.addEventListener('mouseenter', function () { paintStars(parseInt(this.dataset.rating, 10)); });
    });
    $('fbStars').addEventListener('mouseleave', () => paintStars(selectedRating));

    // ---- submit ----
    $('feedbackForm').addEventListener('submit', function (e) {
        e.preventDefault();
        show('fbError', false);

        const message = $('feedbackMessage').value.trim();
        if (!selectedRating) return fbError('Please give a rating.');
        if (message.length < 10) return fbError('Please write at least 10 characters of feedback.');

        const btn = $('submitFeedback');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

        fetch(STORE_URL, {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(async r => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                return fbError(firstError || data.message || 'Error submitting feedback.');
            }
            locked = false;
            dismissOnClose = false;
            hideModal();
            const toast = $('successToast');
            toast.classList.remove('hidden');
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(() => fbError('Error submitting feedback. Please try again.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = original; });
    });

    // Click outside / Escape close only when allowed.
    $('feedbackModal').addEventListener('click', function (e) { if (e.target === this) window.closeFeedbackModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !$('feedbackModal').classList.contains('hidden')) window.closeFeedbackModal();
    });

    // Post-login prompt: open straight away.
    if (FB_PROMPT) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => window.openFeedbackModal('prompt'));
        } else {
            window.openFeedbackModal('prompt');
        }
    }
})();
</script>
