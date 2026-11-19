{{--
    Shared fields for adding / editing a skill, service or offer.
    Expects: $type, $prefix (unique id prefix), $values (array), $errorsBag (MessageBag|null)
--}}
@php
    $err = fn ($field) => $errorsBag && $errorsBag->has($field) ? $errorsBag->first($field) : null;
    $val = fn ($field) => $values[$field] ?? '';
    $inputCls = 'asp-field is-sm';
    $isSkill = $type === 'skill';
@endphp

@if($errorsBag && $errorsBag->any())
    <div class="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700" role="alert">
        {{ $errorsBag->first() }}
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @if($isSkill)
        @php
            // Main skill → sub skill (dependent). No empty option: the first entry is always selected.
            $catalog = \App\Support\SkillCatalog::all();
            $mainSel = $values['main_skill'] ?? \App\Support\SkillCatalog::mainOf($val('title'));
            if (!isset($catalog[$mainSel])) $mainSel = array_key_first($catalog);
            $subSel = $val('title');
        @endphp
        <div>
            <label for="{{ $prefix }}-main" class="asp-label">Main skill <span class="text-red-500">*</span></label>
            <select id="{{ $prefix }}-main" name="main_skill" required data-sub="{{ $prefix }}-title" onchange="cnmSkillSubs(this)"
                    class="{{ $inputCls }} {{ $err('main_skill') ? 'is-invalid' : '' }}">
                @foreach($catalog as $main => $subs)
                    <option value="{{ $main }}" @selected($main === $mainSel)>{{ $main }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="{{ $prefix }}-title" class="asp-label">Sub skill <span class="text-red-500">*</span></label>
            <select id="{{ $prefix }}-title" name="title" required
                    class="{{ $inputCls }} {{ $err('title') ? 'is-invalid' : '' }}">
                @foreach($catalog[$mainSel] as $sub)
                    <option value="{{ $sub }}" @selected(strcasecmp($sub, (string) $subSel) === 0)>{{ $sub }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div>
            <label for="{{ $prefix }}-title" class="asp-label">Title <span class="text-red-500">*</span></label>
            <input id="{{ $prefix }}-title" name="title" type="text" required maxlength="150" value="{{ $val('title') }}"
                   class="{{ $inputCls }} {{ $err('title') ? 'is-invalid' : '' }}">
        </div>
    @endif
    @unless($isSkill)
        <div>
            <label for="{{ $prefix }}-price" class="asp-label">Price hint</label>
            <input id="{{ $prefix }}-price" name="price_hint" type="text" maxlength="100" value="{{ $val('price_hint') }}"
                   placeholder="{{ $type === 'offer' ? 'e.g. Free / 20% off' : 'e.g. From $299' }}"
                   class="{{ $inputCls }} {{ $err('price_hint') ? 'is-invalid' : '' }}">
        </div>
    @endunless
    <div class="sm:col-span-2">
        <label for="{{ $prefix }}-description" class="asp-label">Description</label>
        <textarea id="{{ $prefix }}-description" name="description" rows="2" maxlength="1000"
                  class="{{ $inputCls }} {{ $err('description') ? 'is-invalid' : '' }}">{{ $val('description') }}</textarea>
    </div>
    @unless($isSkill)
        <div class="sm:col-span-2">
            <label for="{{ $prefix }}-ideal" class="asp-label">Ideal for</label>
            <input id="{{ $prefix }}-ideal" name="ideal_for" type="text" maxlength="255" value="{{ $val('ideal_for') }}"
                   placeholder="e.g. Businesses with an outdated website or no online booking"
                   class="{{ $inputCls }} {{ $err('ideal_for') ? 'is-invalid' : '' }}">
        </div>
    @endunless
    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="inline-flex items-center gap-2.5 text-sm text-slate-700 min-h-[32px] cursor-pointer">
            <input type="checkbox" name="is_active" value="1" @checked((bool) ($values['is_active'] ?? true))
                   class="asp-check">
            Active (the AI may suggest it)
        </label>
    </div>
</div>
