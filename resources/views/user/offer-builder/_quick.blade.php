{{--
    Short offer form inside the AI Sales Profile "Offers" section (loaded on demand).
    Expects: $strategy, $fields, $essential (keys), $suggestions, $values (profile prefill), $canGenerate
--}}
@php
    $main = array_values(array_filter($fields, fn ($f) => in_array($f['key'], $essential, true)));
    $more = array_values(array_filter($fields, fn ($f) => !in_array($f['key'], $essential, true)));
    $filledFromProfile = array_keys($values);
@endphp
<div class="ob-quick" data-strategy="{{ $strategy->slug }}" data-testid="quick-offer-form">
    <div class="bg-orange-50/70 border border-orange-100 rounded-xl p-4 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-white border border-orange-200 text-orange-700">{{ $strategy->category->name }}</span>
            <h3 class="text-sm font-bold text-gray-900">{{ $strategy->name }}</h3>
        </div>
        <p class="text-sm text-gray-700 mt-1.5">{{ $strategy->purpose }}</p>
        <p class="text-xs font-semibold text-gray-700 mt-2">Use it when:</p>
        <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
            @foreach($strategy->when_to_use ?? [] as $w)
                <li class="flex gap-1.5"><i class="fas fa-check text-orange-500 mt-0.5 text-[10px]"></i><span>{{ $w }}</span></li>
            @endforeach
        </ul>
        <div class="flex flex-wrap gap-2 mt-3">
            @if($strategy->exampleList() !== [])
                <button type="button" class="ob-q-examples text-xs font-semibold px-3 py-1.5 rounded-lg border border-orange-300 bg-white text-orange-700 hover:bg-orange-100" aria-expanded="false">
                    <i class="fas fa-lightbulb mr-1"></i> Examples ({{ count($strategy->exampleList()) }})
                </button>
            @endif
            <button type="button" class="ob-q-prompt text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" aria-expanded="false">
                <i class="fas fa-terminal mr-1"></i> Prompt
            </button>
        </div>
        @if($strategy->exampleList() !== [])
            <ol class="ob-q-examples-box hidden mt-3 space-y-2" data-testid="offer-examples">
                @foreach($strategy->exampleList() as $ex)
                    <li class="bg-white border border-orange-100 rounded-lg px-3 py-2 text-sm text-gray-700 whitespace-pre-line"><span class="block text-[11px] font-semibold text-orange-600 mb-1">Example {{ $loop->iteration }}</span>{{ $ex }}</li>
                @endforeach
            </ol>
        @endif
        <div class="ob-q-prompt-box hidden mt-3">
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <p class="text-xs text-gray-600">The prompt from your answers. Empty fields stay in <span class="font-mono bg-orange-100 text-orange-800 rounded px-1">[brackets]</span>.</p>
                <button type="button" class="ob-q-prompt-copy text-xs font-medium px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"><i class="fas fa-copy mr-1"></i>Copy</button>
            </div>
            <pre class="ob-q-prompt-text max-h-72 overflow-y-auto whitespace-pre-wrap break-words text-xs leading-relaxed font-mono bg-white border border-gray-200 rounded-lg p-3 text-gray-700"></pre>
        </div>
    </div>

    @if($filledFromProfile !== [])
        <p class="text-xs text-green-700 mb-3"><i class="fas fa-wand-magic-sparkles mr-1"></i> Filled from your AI Sales Profile. Change anything you like.</p>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($main as $f)
            @if($f['key'] === 'product_name')
                @include('user.offer-builder._product-field', ['f' => $f, 'value' => $values['product_name'] ?? '', 'services' => $services, 'canAddService' => $canAddService])
            @else
                @include('user.offer-builder._field', ['f' => $f, 'value' => $values[$f['key']] ?? '', 'options' => $suggestions[$f['key']] ?? [], 'idPrefix' => 'q-'])
            @endif
        @endforeach
    </div>

    <details class="mt-3 group">
        <summary class="list-none cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900 flex items-center gap-1.5">
            <i class="fas fa-chevron-right text-[10px] transition-transform group-open:rotate-90"></i> More details (optional)
        </summary>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="sm:col-span-2">
                <label for="q-title" class="block text-xs font-medium text-gray-700 mb-1">Offer name <span class="text-gray-400 font-normal">(optional)</span></label>
                <input id="q-title" type="text" maxlength="150" placeholder="Left empty: AI names it" class="ob-title w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            @foreach($more as $f)
                @include('user.offer-builder._field', ['f' => $f, 'value' => $values[$f['key']] ?? '', 'options' => $suggestions[$f['key']] ?? [], 'idPrefix' => 'q-'])
            @endforeach
            <div class="sm:col-span-2">
                <label for="q-additional" class="block text-xs font-medium text-gray-700 mb-1">Anything else for the AI? <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="q-additional" rows="2" maxlength="{{ (int) config('offer_builder.limits.additional_instructions') }}" placeholder="e.g. Focus on first-time customers" class="ob-additional w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
            </div>
        </div>
    </details>

    <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-2">
        @if($canGenerate)
            <button type="button" class="ob-q-generate inline-flex justify-center items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white">
                <i class="fas fa-wand-magic-sparkles"></i> Generate with AI
            </button>
        @endif
        <button type="button" class="ob-q-save inline-flex justify-center items-center gap-2 text-sm font-semibold px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 hover:bg-gray-50">
            <i class="fas fa-floppy-disk"></i> Save without AI
        </button>
    </div>
    @unless($canGenerate)
        <p class="text-xs text-gray-500 mt-2">AI generation is not available on your plan right now. "Save without AI" adds the offer from your answers.</p>
    @endunless
    <p class="ob-q-status text-sm mt-2" role="status" aria-live="polite"></p>
</div>
