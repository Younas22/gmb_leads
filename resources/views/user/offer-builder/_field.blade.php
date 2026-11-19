{{--
    One Offer Builder field: a dropdown of ready-made choices (profile values first) with a
    "Custom" option, plus the text box that holds the value (.ob-input, read by the scripts).
    Expects: $f (field def), $value (string), $options (list<string>), $idPrefix
--}}
@php
    $id = $idPrefix . $f['key'];
    $long = ($f['type'] ?? '') === 'textarea';
    $isSelect = ($f['type'] ?? '') === 'select';
    $value = (string) ($value ?? '');
    $options = $options ?? [];
    $inList = $value !== '' && in_array($value, $options, true);
    $showText = $options === [] || ($value !== '' && !$inList);
    $cls = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-200 focus:border-orange-500';
@endphp
<div class="ob-field {{ $long ? 'sm:col-span-2' : '' }}" data-key="{{ $f['key'] }}">
    <label for="{{ $options !== [] ? $id . '-pick' : $id }}" class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-gray-700 mb-1">
        {{ $f['label'] }} @if(!empty($f['required']))<span class="text-red-500" aria-label="required">*</span>@endif
        <span class="ob-from-profile hidden text-[10px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded px-1.5" data-for="{{ $f['key'] }}">From AI Sales Profile</span>
    </label>
    @if($options !== [])
        <select id="{{ $id }}-pick" class="ob-pick {{ $cls }}" data-key="{{ $f['key'] }}" aria-label="{{ $f['label'] }}">
            <option value="">Choose…</option>
            @foreach($options as $opt)
                <option value="{{ $opt }}" @selected($inList && $opt === $value)>{{ \Illuminate\Support\Str::limit($opt, 90) }}</option>
            @endforeach
            @unless($isSelect)
                <option value="__custom" @selected($value !== '' && !$inList)>✎ Custom (type your own)</option>
            @endunless
        </select>
    @endif
    @if($long)
        <textarea id="{{ $id }}" data-key="{{ $f['key'] }}" rows="2" maxlength="{{ (int) config('offer_builder.limits.long_field') }}"
                  placeholder="e.g. {{ $f['example'] ?? '' }}" aria-label="{{ $f['label'] }}"
                  class="ob-input {{ $cls }} {{ $options !== [] ? 'mt-1.5' : '' }} {{ $showText ? '' : 'hidden' }}">{{ $value }}</textarea>
    @else
        <input id="{{ $id }}" data-key="{{ $f['key'] }}" type="text" aria-label="{{ $f['label'] }}"
               maxlength="{{ (int) config('offer_builder.limits.field') }}" value="{{ $value }}" placeholder="e.g. {{ $f['example'] ?? '' }}"
               class="ob-input {{ $cls }} {{ $options !== [] ? 'mt-1.5' : '' }} {{ $showText ? '' : 'hidden' }}">
    @endif
</div>
