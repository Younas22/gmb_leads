{{--
    "Product or service": only the user's own Services (the first is selected by default; picking one
    also fills the price), plus "+ Add a new service" (saved to the AI Sales Profile Services without
    leaving the form). "Custom" only when the user has no Services yet.
    Expects: $f, $value, $services (list of [title, price, description]), $canAddService
--}}
@php
    $value = (string) ($value ?? '');
    $titles = array_column($services, 'title');
    if ($value === '' && $titles !== []) {
        $value = $titles[0];
    }
    $inList = $value !== '' && in_array($value, $titles, true);
    $allowCustom = $titles === [] || ($value !== '' && !$inList);
    $cls = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500';
@endphp
<div class="ob-field" data-key="product_name">
    <label for="q-product_name-pick" class="flex items-center gap-1.5 text-xs font-medium text-gray-700 mb-1">
        {{ $f['label'] }} <span class="text-red-500" aria-label="required">*</span>
    </label>
    <select id="q-product_name-pick" class="ob-pick ob-product {{ $cls }}" data-key="product_name" aria-label="{{ $f['label'] }}">
        @if($titles === [])
            <option value="">Choose…</option>
        @endif
        @foreach($services as $s)
            <option value="{{ $s['title'] }}" data-price="{{ $s['price'] }}" @selected($value === $s['title'])>{{ $s['title'] }}{{ $s['price'] !== '' ? ' — ' . $s['price'] : '' }}</option>
        @endforeach
        @if($canAddService)
            <option value="__new_service">＋ Add a new service…</option>
        @endif
        @if($allowCustom)
            <option value="__custom" @selected($value !== '' && !$inList)>✎ Custom (type your own)</option>
        @endif
    </select>
    <input id="q-product_name" data-key="product_name" type="text" aria-label="{{ $f['label'] }}"
           maxlength="{{ (int) config('offer_builder.limits.field') }}" value="{{ $value }}" placeholder="e.g. {{ $f['example'] ?? '' }}"
           class="ob-input {{ $cls }} mt-1.5 {{ $value !== '' && !$inList ? '' : 'hidden' }}">

    @if($canAddService)
        {{-- New service: saved to the AI Sales Profile "Services", then selected here --}}
        <div data-url="{{ route('user.ai-profile.offers.store') }}" class="ob-new-service hidden mt-2 bg-orange-50/60 border border-orange-200 rounded-lg p-3 space-y-2" data-testid="new-service-form">
            <p class="text-xs font-semibold text-gray-800">New service <span class="font-normal text-gray-500">(also added to your Services)</span></p>
            <input type="text" class="ob-ns-title {{ $cls }}" maxlength="150" placeholder="Service name, e.g. Website redesign" aria-label="Service name">
            <input type="text" class="ob-ns-price {{ $cls }}" maxlength="100" placeholder="Price, e.g. From $499" aria-label="Service price">
            <textarea class="ob-ns-desc {{ $cls }}" rows="2" maxlength="1000" placeholder="What is included (optional)" aria-label="Service details"></textarea>
            <div class="flex items-center gap-2">
                <button type="button" class="ob-ns-save text-xs font-semibold px-3 py-1.5 rounded-lg bg-orange-500 hover:bg-orange-600 text-white">Save service</button>
                <button type="button" class="ob-ns-cancel text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                <span class="ob-ns-msg text-xs" role="status" aria-live="polite"></span>
            </div>
        </div>
    @endif
</div>
