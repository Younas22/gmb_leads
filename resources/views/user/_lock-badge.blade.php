{{-- A feature the admin locked (package or per-user). Expects $message; optional $compact (badge only, message as tooltip). --}}
<span class="inline-flex flex-col items-start gap-0.5" data-testid="locked-feature">
    <span class="inline-flex items-center gap-1 h-[21px] px-2 rounded-full border border-slate-200 bg-slate-100 text-slate-600 text-[11px] font-semibold whitespace-nowrap" title="{{ $message }}">
        <i class="fas fa-lock text-[9px]" aria-hidden="true"></i>Locked
    </span>
    @if(empty($compact))
        <span class="text-[11px] text-slate-500 leading-snug">{{ $message }}</span>
    @endif
</span>
