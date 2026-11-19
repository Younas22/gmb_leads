{{-- Status chips. Expects $analysis (AiLeadAnalysis); optional $draft (AiEmailDraft|null) and $which ('status'|'website'|'draft') --}}
@php
    $chip = 'inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border whitespace-nowrap';
@endphp
@if($which === 'status')
    @php
        $cls = match ($analysis->status) {
            'completed' => 'bg-green-50 text-green-700 border-green-200',
            'failed' => 'bg-red-50 text-red-700 border-red-200',
            'quota_exhausted' => 'bg-amber-50 text-amber-800 border-amber-200',
            'analyzing' => 'bg-orange-50 text-orange-700 border-orange-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    @endphp
    <span class="{{ $chip }} {{ $cls }}" data-status="{{ $analysis->status }}">
        @if($analysis->status === 'analyzing')<i class="fas fa-spinner fa-spin text-[9px]"></i>@endif
        {{ $analysis->statusLabel() }}
    </span>
@elseif($which === 'website')
    @php
        $cls = match ($analysis->website_status) {
            'verified' => 'bg-green-50 text-green-700 border-green-200',
            'no_website' => 'bg-gray-100 text-gray-700 border-gray-200',
            'pending' => 'bg-gray-50 text-gray-500 border-gray-200',
            default => 'bg-amber-50 text-amber-800 border-amber-200',
        };
    @endphp
    <span class="{{ $chip }} {{ $cls }}">{{ $analysis->websiteLabel() }}</span>
@elseif($which === 'draft')
    @if(!$draft)
        <span class="{{ $chip }} bg-gray-50 text-gray-500 border-gray-200">No draft yet</span>
    @else
        @php
            $cls = match ($draft->review_status) {
                'approved' => 'bg-green-50 text-green-700 border-green-200',
                'discarded' => 'bg-gray-100 text-gray-500 border-gray-200',
                default => 'bg-orange-50 text-orange-700 border-orange-200',
            };
        @endphp
        <span class="{{ $chip }} {{ $cls }}">{{ $draft->reviewLabel() }}</span>
    @endif
@endif
