{{-- Pricing card feature lines (home + subscription). Source: App\Support\PlanFeatureCatalog::CARD.
     Old callers still pass $boolFeatures / $featureLabels / $hideFromCards; they are no longer needed.
     Pass $lightCard = true when the popular card has a white background (homepage). --}}
@php $onDark = $package->is_popular && empty($lightCard); @endphp
<ul class="{{ empty($lightCard) ? 'space-y-4 mb-8' : 'space-y-2.5 mb-6 text-[13.5px] text-gray-700' }} flex-grow">
    @foreach(\App\Support\PlanFeatureCatalog::cardLines($package) as [$label, $included, $value, $locked])
        <li class="flex items-center">
            @if($locked)
                {{-- Locked by the admin: announced, not yet available --}}
                <i class="far fa-clock w-5 text-center mr-3 flex-shrink-0 {{ $onDark ? 'text-white/70' : 'text-orange-400' }}" aria-hidden="true"></i>
                <span class="{{ $onDark ? 'opacity-80' : 'text-gray-500' }}">{{ $label }}</span>
                <span class="ml-2 px-2 py-0.5 rounded-full text-[10.5px] font-semibold uppercase tracking-wide whitespace-nowrap {{ $onDark ? 'bg-white/15 text-white' : 'bg-orange-50 text-orange-700 border border-orange-200' }}">Coming soon</span>
            @elseif($included)
                <svg class="w-5 h-5 {{ $onDark ? 'text-white' : 'text-orange-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ $label }}{{ $value ? ':' : '' }}
                    @if($value)
                        <strong class="ml-1 whitespace-nowrap">{{ $value }}</strong>
                    @endif
                </span>
            @else
                <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="{{ $onDark ? 'opacity-50' : 'text-gray-400' }}">{{ $label }}</span>
            @endif
        </li>
    @endforeach
</ul>
