{{-- A sidebar item for a plan feature the admin locked: visible, not clickable through, shows the
     admin's message under the label and opens it in a small dialog. Expects: $icon, $label, $message --}}
<button type="button"
        onclick="cnmShowLockedFeature(this)"
        data-feature="{{ $label }}"
        data-message="{{ $message }}"
        aria-haspopup="dialog"
        title="{{ $message }}"
        class="w-full flex items-start px-3 py-2.5 text-sm text-gray-400 hover:bg-gray-50 rounded-xl font-medium transition-colors text-left cursor-pointer"
        data-testid="locked-nav-item">
    <i class="fas {{ $icon }} w-5 text-center mr-3 mt-0.5" aria-hidden="true"></i>
    <span class="flex-1 min-w-0">
        <span class="flex items-center gap-1.5 text-gray-500">
            {{ $label }}
            <i class="fas fa-lock text-[10px] text-amber-600" aria-hidden="true"></i>
            <span class="sr-only">(locked)</span>
        </span>
        <span class="block text-[11px] leading-snug font-normal text-gray-500 mt-0.5 line-clamp-2">{{ $message }}</span>
    </span>
</button>
