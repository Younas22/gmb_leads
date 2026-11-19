{{-- The user's offer inputs as coloured chips (same colours as the highlights). $chips, $caption --}}
<div class="mb-2" data-testid="offer-input-chips">
    @if(!empty($caption))<p class="text-[11px] font-medium text-gray-500 mb-1">{{ $caption }}</p>@endif
    <div class="flex flex-wrap gap-1.5">
        @foreach($chips as $chip)
            <span class="inline-flex items-center gap-1 max-w-full text-[11px] rounded-full px-2 py-0.5 ring-1 {{ $chip['class'] }}" title="{{ $chip['label'] }}">
                <span class="font-semibold whitespace-nowrap">{{ $chip['label'] }}:</span>
                <span class="truncate">{{ \Illuminate\Support\Str::limit($chip['value'], 60) }}</span>
            </span>
        @endforeach
    </div>
</div>
