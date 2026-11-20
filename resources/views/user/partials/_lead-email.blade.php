{{--
    A lead's email, or "No email" + "Add email" (inline). Saves to the lead itself
    (POST user.leads.contact), so My Leads and AI Outreach show the same email.
    Expects $lead (SavedLead with id + email).
--}}
<div class="cnm-lead-email min-w-0" data-lead-email="{{ (int) $lead->id }}" data-url="{{ route('user.leads.contact', $lead->id) }}" onclick="event.stopPropagation()">
    <div class="cnm-le-view flex items-center gap-1.5 text-[12px] min-w-0 leading-5 {{ $lead->email ? '' : 'hidden' }}">
        <i class="fas fa-envelope w-3.5 text-center text-slate-500 text-[10px] flex-shrink-0" aria-hidden="true"></i>
        <span class="cnm-le-value contact-detail break-all text-slate-700" data-type="email" data-original="{{ $lead->email }}">{{ $lead->email }}</span>
        <button type="button" onclick="cnmLeadEmailCopy(this)" class="cnm-copy copy-btn cursor-pointer" title="Copy email" aria-label="Copy email">
            <i class="fas fa-copy text-[10px]"></i>
        </button>
        <button type="button" onclick="cnmLeadEmailEdit(this)" class="text-slate-400 hover:text-orange-600 cursor-pointer" title="Edit email" aria-label="Edit email">
            <i class="fas fa-pen text-[10px]"></i>
        </button>
    </div>
    <div class="cnm-le-missing flex flex-wrap items-center gap-1.5 {{ $lead->email ? 'hidden' : '' }}">
        <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-md bg-red-50 text-red-700" data-testid="no-email-badge">
            <i class="fas fa-envelope text-[10px]" aria-hidden="true"></i> No email
        </span>
        <button type="button" onclick="cnmLeadEmailEdit(this)" data-testid="add-email"
                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-md bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100 cursor-pointer">
            <i class="fas fa-plus text-[9px]" aria-hidden="true"></i> Add email
        </button>
    </div>
    <form class="cnm-le-form hidden flex items-center gap-1 mt-0.5" onsubmit="event.preventDefault(); cnmLeadEmailSave(this)">
        <input type="email" maxlength="255" required placeholder="name@business.com" aria-label="Email address"
               class="cnm-le-input w-44 px-2 py-1 text-[12px] border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400" value="{{ $lead->email }}">
        <button type="submit" class="px-2 py-1 text-[11px] font-semibold rounded-md bg-orange-500 hover:bg-orange-600 text-white cursor-pointer" title="Save"><i class="fas fa-check"></i></button>
        <button type="button" onclick="cnmLeadEmailCancel(this)" class="px-2 py-1 text-[11px] rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 cursor-pointer" title="Cancel"><i class="fas fa-times"></i></button>
    </form>
    <p class="cnm-le-error hidden text-[11px] text-red-600 mt-0.5"></p>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        const box = el => el.closest('.cnm-lead-email');
        window.cnmLeadEmailCopy = function (btn) {
            const v = box(btn).querySelector('.cnm-le-value').textContent.trim();
            if (window.copyToClipboard) { window.copyToClipboard(v, btn); } else { navigator.clipboard?.writeText(v); }
        };
        window.cnmLeadEmailEdit = function (btn) {
            const b = box(btn);
            b.querySelector('.cnm-le-view').classList.add('hidden');
            b.querySelector('.cnm-le-missing').classList.add('hidden');
            b.querySelector('.cnm-le-form').classList.remove('hidden');
            b.querySelector('.cnm-le-error').classList.add('hidden');
            b.querySelector('.cnm-le-input').focus();
        };
        function show(b, email) {
            b.querySelector('.cnm-le-form').classList.add('hidden');
            b.querySelector('.cnm-le-view').classList.toggle('hidden', !email);
            b.querySelector('.cnm-le-missing').classList.toggle('hidden', !!email);
            const val = b.querySelector('.cnm-le-value');
            val.textContent = email || '';
            val.dataset.original = email || '';
            b.querySelector('.cnm-le-input').value = email || '';
        }
        window.cnmLeadEmailCancel = function (btn) {
            const b = box(btn);
            show(b, b.querySelector('.cnm-le-value').textContent.trim());
            b.querySelector('.cnm-le-error').classList.add('hidden');
        };
        window.cnmLeadEmailSave = async function (form) {
            const b = box(form), err = b.querySelector('.cnm-le-error');
            const value = b.querySelector('.cnm-le-input').value.trim();
            form.querySelectorAll('button').forEach(x => x.disabled = true);
            try {
                const res = await fetch(b.dataset.url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                               'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ channel: 'email', value }),
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok || d.success === false) throw new Error(d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Could not save the email.'));
                // Update every place this lead is shown on the page
                document.querySelectorAll('.cnm-lead-email[data-lead-email="' + b.dataset.leadEmail + '"]').forEach(x => show(x, d.email || ''));
                err.classList.add('hidden');
            } catch (e) {
                err.textContent = e.message;
                err.classList.remove('hidden');
            } finally {
                form.querySelectorAll('button').forEach(x => x.disabled = false);
            }
        };
    })();
    </script>
    @endpush
@endonce
