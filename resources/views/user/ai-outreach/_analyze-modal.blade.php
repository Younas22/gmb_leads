{{-- "Analyze with AI" confirmation modal for My Leads. Server re-checks everything. --}}
<div id="cnmAnalyzeModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="cnmAnalyzeTitle">
    <div class="absolute inset-0 bg-black/40" onclick="cnmCloseAnalyze()"></div>
    <div class="relative mx-auto mt-16 sm:mt-24 w-[calc(100%-2rem)] max-w-lg bg-white rounded-2xl shadow-xl border border-orange-100">
        <div class="p-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <h2 id="cnmAnalyzeTitle" class="text-lg font-bold text-gray-900 flex items-center gap-2"><i class="fas fa-wand-magic-sparkles text-orange-500"></i> Analyze with AI</h2>
                <p class="text-sm text-gray-600 mt-1">Website check, offer match and an email <strong>draft</strong> per lead. Nothing is sent.</p>
            </div>
            <button type="button" onclick="cnmCloseAnalyze()" class="text-gray-400 hover:text-gray-700" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div id="cnmAnalyzeBody" class="p-5 text-sm text-gray-700">
            <p><i class="fas fa-spinner fa-spin text-orange-500 mr-1"></i> Checking your selection…</p>
        </div>
        <div class="p-5 pt-0 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" onclick="cnmCloseAnalyze()" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">Cancel</button>
            <button type="button" id="cnmAnalyzeConfirm" onclick="cnmConfirmAnalyze()" disabled class="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed">Start analysis</button>
        </div>
    </div>
</div>

<script>
window.cnmAiOutreachEnabled = true;
(function () {
    const estimateUrl = @js(route('user.ai-outreach.estimate'));
    const startUrl = @js(route('user.ai-outreach.start'));
    let selectedIds = [];
    let reanalyze = null; // leads already analysed with AI: null = not asked yet, true = analyse again, false = skip them

    function csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; }
    function esc(v) { return window.cnmEscape ? window.cnmEscape(v) : String(v); }
    function quotaLine(label, q) {
        const left = q.remaining === null ? 'unlimited' : q.remaining + ' left';
        return '<li class="flex justify-between"><span>' + esc(label) + '</span><span class="font-medium text-gray-900">' + parseInt(q.needed, 10) + ' needed · ' + esc(left) + '</span></li>';
    }

    async function post(url, ids) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({ lead_ids: ids })
        });
        let data = {};
        try { data = await res.json(); } catch (e) {}
        return { ok: res.ok, status: res.status, data };
    }

    window.cnmOpenAnalyze = async function (ids) {
        selectedIds = (ids || []).map(n => parseInt(n, 10)).filter(n => n > 0);
        reanalyze = null;
        await check();
    };

    // Answer to "already analysed: analyse again or skip?"
    window.cnmAnalyzeChoice = async function (again, analyzedIds) {
        reanalyze = again;
        if (!again) {
            const skip = new Set(analyzedIds);
            selectedIds = selectedIds.filter(id => !skip.has(id));
            if (selectedIds.length === 0) {
                document.getElementById('cnmAnalyzeBody').innerHTML = '<p class="text-gray-700"><i class="fas fa-circle-check text-emerald-500 mr-1"></i> All selected leads are already analysed with AI. Nothing new to analyse.</p>';
                document.getElementById('cnmAnalyzeConfirm').disabled = true;
                return;
            }
        }
        await check();
    };

    async function check() {
        const modal = document.getElementById('cnmAnalyzeModal');
        const body = document.getElementById('cnmAnalyzeBody');
        const btn = document.getElementById('cnmAnalyzeConfirm');
        btn.disabled = true;
        modal.classList.remove('hidden');

        if (selectedIds.length === 0) {
            body.innerHTML = '<p>Select at least one lead first.</p>';
            return;
        }
        body.innerHTML = '<p><i class="fas fa-spinner fa-spin text-orange-500 mr-1"></i> Checking your selection…</p>';

        const { ok, data } = await post(estimateUrl, selectedIds);
        if (!ok) {
            body.innerHTML = '<p class="text-red-600">' + esc(data.message || 'Could not check the selection.') + '</p>';
            return;
        }

        let html = '<ul class="space-y-1.5">'
            + '<li class="flex justify-between"><span>Selected leads</span><span class="font-medium text-gray-900">' + parseInt(data.selected, 10) + '</span></li>'
            + '<li class="flex justify-between"><span>Will be analysed</span><span class="font-medium text-gray-900">' + parseInt(data.to_queue, 10) + '</span></li>'
            + '<li class="flex justify-between"><span>With a website URL</span><span class="font-medium text-gray-900">' + parseInt(data.with_website, 10) + '</span></li>';
        if (data.already_running > 0) {
            html += '<li class="flex justify-between"><span>Already being analysed</span><span class="font-medium text-gray-900">' + parseInt(data.already_running, 10) + '</span></li>';
        }
        html += '</ul><h3 class="mt-4 mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estimated AI usage</h3><ul class="space-y-1.5">'
            + quotaLine('AI analyses', data.quota.website_audit)
            + quotaLine('AI email drafts', data.quota.email_draft)
            + '</ul>';

        if (data.problems && data.problems.length) {
            html += '<div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-red-700"><ul class="list-disc pl-5 space-y-1">'
                + data.problems.map(p => '<li>' + esc(p) + '</li>').join('') + '</ul></div>';
        } else {
            html += '<p class="mt-4 text-xs text-gray-500">Analyses run in the background. Failed analyses do not use your allowance.</p>';
        }
        // Some leads were analysed before: ask once whether to analyse them again or skip them
        const analyzed = (data.analyzed_ids || []).map(n => parseInt(n, 10));
        if (analyzed.length && reanalyze === null) {
            const n = analyzed.length, all = n === selectedIds.length;
            html = '<div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5" data-testid="ai-reanalyze-question">'
                + '<p class="font-semibold text-gray-900"><i class="fas fa-wand-magic-sparkles text-emerald-600 mr-1"></i> '
                + (all ? (n === 1 ? 'This lead was already analysed with AI.' : 'All ' + n + ' selected leads were already analysed with AI.')
                       : n + ' of the ' + selectedIds.length + ' selected leads ' + (n === 1 ? 'was' : 'were') + ' already analysed with AI.')
                + '</p><p class="text-gray-600 mt-1">Analysing again uses your AI allowance again and creates a new email draft.</p>'
                + '<div class="mt-3 flex flex-col sm:flex-row gap-2">'
                + '<button type="button" onclick="cnmAnalyzeChoice(false, ' + JSON.stringify(analyzed) + ')" class="flex-1 px-3 py-2 rounded-lg bg-white border border-gray-300 text-gray-800 text-sm font-semibold hover:bg-gray-50 cursor-pointer">'
                + (all ? 'Skip, do not analyse again' : 'Skip analysed leads') + '</button>'
                + '<button type="button" onclick="cnmAnalyzeChoice(true, [])" class="flex-1 px-3 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold cursor-pointer">Analyse again</button>'
                + '</div></div>';
            body.innerHTML = html;
            btn.disabled = true;
            return;
        }
        if (analyzed.length && reanalyze === true) {
            html = '<p class="mb-3 text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2"><i class="fas fa-rotate mr-1"></i> '
                + analyzed.length + ' already analysed ' + (analyzed.length === 1 ? 'lead' : 'leads') + ' will be analysed again.</p>' + html;
        }
        body.innerHTML = html;
        btn.disabled = !data.can_start;
    }

    window.cnmCloseAnalyze = function () {
        document.getElementById('cnmAnalyzeModal').classList.add('hidden');
    };

    window.cnmConfirmAnalyze = async function () {
        const btn = document.getElementById('cnmAnalyzeConfirm');
        btn.disabled = true;
        btn.textContent = 'Starting…';
        const { ok, data } = await post(startUrl, selectedIds);
        if (ok && data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        const body = document.getElementById('cnmAnalyzeBody');
        const problems = data.problems || [data.message || 'Could not start the analysis.'];
        body.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700"><ul class="list-disc pl-5">' + problems.map(p => '<li>' + esc(p) + '</li>').join('') + '</ul></div>';
        btn.textContent = 'Start analysis';
    };
})();
</script>
