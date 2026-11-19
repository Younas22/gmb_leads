@extends('layouts.app')

@section('title', 'AI analysis progress')

@section('content')
<div class="p-4 lg:p-8 max-w-7xl mx-auto space-y-6">
    <div>
        <a href="{{ route('user.ai-outreach.index') }}" class="text-sm text-gray-600 hover:text-orange-600"><i class="fas fa-arrow-left mr-1"></i> AI Outreach</a>
    </div>

    <div class="bg-white border border-orange-100 rounded-2xl p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">AI analysis · {{ $batch->created_at->format('M d, Y H:i') }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    <span id="progress-text">{{ $progress['finished'] }} of {{ $progress['total'] }} finished</span>
                    · {{ $progress['completed'] }} completed · {{ $progress['failed'] }} failed · {{ $progress['quota_exhausted'] }} quota exhausted
                </p>
            </div>
            @if($progress['failed'] + $progress['quota_exhausted'] > 0)
                <form method="POST" action="{{ route('user.ai-outreach.batch.retry', $batch->id) }}">
                    @csrf
                    <button class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-lg border border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100"><i class="fas fa-redo"></i> Retry failed</button>
                </form>
            @endif
        </div>
        <div class="mt-4 h-2.5 bg-orange-50 rounded-full overflow-hidden border border-orange-100">
            <div id="progress-bar" class="h-full bg-orange-500 rounded-full transition-all" style="width: {{ (int) $progress['percent'] }}%"></div>
        </div>
        @if($progress['finished'] < $progress['total'])
            <p class="text-xs text-gray-500 mt-2"><i class="fas fa-circle-notch fa-spin mr-1"></i> Processing in the background. You can leave this page; results are saved.</p>
        @endif
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl">
        @include('user.ai-outreach._table', ['rows' => $items])
        @if($items->hasPages())
            <div class="p-4 border-t border-gray-100">{{ $items->links() }}</div>
        @endif
    </div>
</div>

@if($progress['finished'] < $progress['total'])
<script>
(function () {
    const url = @js(route('user.ai-outreach.batch.status', $batch->id));
    let lastFinished = {{ (int) $progress['finished'] }};
    let tries = 0;

    async function poll() {
        tries++;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                const p = data.progress;
                document.getElementById('progress-bar').style.width = parseInt(p.percent, 10) + '%';
                document.getElementById('progress-text').textContent = p.finished + ' of ' + p.total + ' finished';
                if (p.finished !== lastFinished) { window.location.reload(); return; }
                if (p.finished >= p.total) { window.location.reload(); return; }
            }
        } catch (e) { /* keep polling */ }
        if (tries < 360) setTimeout(poll, 5000);
    }
    setTimeout(poll, 5000);
})();
</script>
@endif
@endsection
