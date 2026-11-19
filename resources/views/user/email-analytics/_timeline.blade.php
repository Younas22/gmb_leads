{{-- Activity timeline. Expects $timeline (collection from ActivityTimeline). --}}
@if($timeline->isEmpty())
    <p class="text-sm text-gray-500 py-6 text-center">No activity recorded yet.</p>
@else
    <ol class="relative border-l-2 border-orange-100 ml-2 space-y-4" data-testid="timeline">
        @foreach($timeline as $item)
            <li class="ml-4">
                <span class="absolute -left-[7px] mt-1.5 w-3 h-3 rounded-full {{ $item['upcoming'] ? 'bg-white border-2 border-orange-400' : 'bg-orange-500' }}"></span>
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <time class="text-xs font-medium text-gray-500 whitespace-nowrap">
                        {{ $item['upcoming'] ? $item['at']->format('D, M j, Y') : $item['at']->format('M j, Y · g:i A') }}
                    </time>
                    <span class="text-sm font-semibold text-gray-900">{{ $item['title'] }}</span>
                    @if($item['upcoming'])<span class="text-[11px] px-1.5 py-0.5 rounded bg-orange-50 text-orange-700 border border-orange-200">Upcoming</span>@endif
                </div>
                @if($item['detail'])<div class="text-sm text-gray-600 break-words">{{ $item['detail'] }}</div>@endif
                <div class="text-[11px] text-gray-400 mt-0.5">Source: {{ $item['source'] }}@if($item['user']) · {{ $item['user'] }}@endif</div>
            </li>
        @endforeach
    </ol>
@endif
