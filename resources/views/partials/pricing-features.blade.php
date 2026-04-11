<ul class="space-y-4 mb-8 flex-grow">
    @php
        $sortedFeatures = $package->features->sort(function($a, $b) use ($boolFeatures) {
            $aIsBool = in_array($a->feature_key, $boolFeatures);
            $bIsBool = in_array($b->feature_key, $boolFeatures);
            if (!$aIsBool && $bIsBool) return -1;
            if ($aIsBool && !$bIsBool) return 1;
            if ($aIsBool && $bIsBool) {
                if ($a->feature_value === 'true' && $b->feature_value !== 'true') return -1;
                if ($a->feature_value !== 'true' && $b->feature_value === 'true') return 1;
            }
            return 0;
        });
    @endphp
    @foreach($sortedFeatures as $feature)
        @if(isset($featureLabels[$feature->feature_key]) && !in_array($feature->feature_key, $hideFromCards))
            <li class="flex items-center">
                @if(in_array($feature->feature_key, $boolFeatures))
                    @if($feature->feature_value === 'true')
                        <svg class="w-5 h-5 {{ $package->is_popular ? 'text-green-400' : 'text-green-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $featureLabels[$feature->feature_key] }}
                    @else
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="{{ $package->is_popular ? 'opacity-50' : 'text-gray-400' }}">{{ $featureLabels[$feature->feature_key] }}</span>
                    @endif
                @elseif($feature->feature_value === 'false')
                    <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span class="{{ $package->is_popular ? 'opacity-50' : 'text-gray-400' }}">{{ $featureLabels[$feature->feature_key] }}</span>
                @else
                    <svg class="w-5 h-5 {{ $package->is_popular ? 'text-green-400' : 'text-green-500' }} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ $featureLabels[$feature->feature_key] }}:
                    <strong class="ml-1">
                        @if($feature->feature_value === 'unlimited')
                            Unlimited
                        @elseif($feature->feature_key === 'daily_leads_limit')
                            {{ number_format((int)$feature->feature_value) }}/day
                        @elseif($feature->feature_key === 'max_devices')
                            {{ $feature->feature_value }} {{ (int)$feature->feature_value === 1 ? 'Device' : 'Devices' }}
                        @elseif($feature->feature_key === 'export_leads')
                            {{ number_format((int)$feature->feature_value) }}/month
                        @else
                            {{ $feature->is_unlimited ? 'Unlimited' : number_format((int)$feature->feature_value) }}
                        @endif
                    </strong>
                @endif
            </li>
        @endif
    @endforeach
</ul>
