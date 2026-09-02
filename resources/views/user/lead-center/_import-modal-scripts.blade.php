<script>
// ===== Lead Center: Import Leads =====
let _importValidRows = [];   // rows returned by the preview step, ready to persist
let _importTab = 'csv';
let _importLocation = null;  // lcCascadingLocation instance, created lazily (needs jQuery/select2 loaded first)

function openImportModal() {
    if (!_importLocation) {
        _importLocation = lcCascadingLocation('import_country_select', 'import_state_select', 'import_city_select');
    }
    document.getElementById('importModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    resetImportModal();
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function resetImportModal() {
    _importValidRows = [];
    document.getElementById('csvFileInput').value = '';
    document.getElementById('csvFileLabel').textContent = 'Click to choose a CSV file, or drag it here';
    document.getElementById('pasteTextarea').value = '';
    document.getElementById('importSourceError').classList.add('hidden');
    document.getElementById('importPreviewSection').classList.add('hidden');
    document.getElementById('importSourceSection').classList.remove('hidden');
    document.getElementById('confirmImportBtn').classList.add('hidden');
    document.getElementById('previewBtn').classList.remove('hidden');
    if (_importLocation) _importLocation.reset();
    switchImportTab('csv');
}

function switchImportTab(tab) {
    _importTab = tab;
    const isCsv = tab === 'csv';
    document.getElementById('importTabCsv').classList.toggle('hidden', !isCsv);
    document.getElementById('importTabPaste').classList.toggle('hidden', isCsv);
    document.getElementById('tabBtnCsv').className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors ' + (isCsv ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500');
    document.getElementById('tabBtnPaste').className = 'px-3 py-1.5 rounded-md text-xs font-medium transition-colors ' + (!isCsv ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500');
}

function onCsvFileChosen(input) {
    if (input.files && input.files[0]) {
        document.getElementById('csvFileLabel').textContent = input.files[0].name;
    }
}

function importSourceError(msg) {
    const el = document.getElementById('importSourceError');
    el.textContent = msg;
    el.classList.remove('hidden');
}

function runImportPreview() {
    const errEl = document.getElementById('importSourceError');
    errEl.classList.add('hidden');

    const formData = new FormData();

    if (_importTab === 'csv') {
        const file = document.getElementById('csvFileInput').files[0];
        if (!file) { importSourceError('Please choose a CSV file first.'); return; }
        formData.append('source', 'csv');
        formData.append('csv_file', file);
    } else {
        const text = document.getElementById('pasteTextarea').value.trim();
        if (!text) { importSourceError('Please paste at least one lead.'); return; }
        formData.append('source', 'paste');
        formData.append('pasted_text', text);
    }

    const btn = document.getElementById('previewBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing…';

    fetch(LC_ROUTES.importPreview, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(async r => {
        const data = await r.json().catch(() => null);
        if (!r.ok || !data || !data.success) {
            throw new Error((data && data.message) || 'Could not process that file.');
        }
        return data;
    })
    .then(renderImportPreview)
    .catch(err => importSourceError(err.message || 'Something went wrong while analyzing your leads.'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

function renderImportPreview(data) {
    _importValidRows = data.valid;

    document.getElementById('previewValidCount').textContent = data.valid_count;
    document.getElementById('previewDuplicateCount').textContent = data.duplicate_count;
    document.getElementById('previewInvalidCount').textContent = data.invalid_count;

    const body = document.getElementById('previewTableBody');
    const showRows = data.valid.slice(0, 200);
    body.innerHTML = showRows.map(row => `
        <tr>
            <td class="px-3 py-1.5 text-gray-800">${escapeHtml(row.company_name)}</td>
            <td class="px-3 py-1.5 text-gray-500">${escapeHtml(row.website || '—')}</td>
        </tr>
    `).join('') || `<tr><td colspan="2" class="px-3 py-4 text-center text-gray-400">No valid leads found.</td></tr>`;

    const moreNote = document.getElementById('previewMoreNote');
    moreNote.textContent = data.valid.length > 200 ? `Showing first 200 of ${data.valid.length} valid leads.` : '';

    const issues = [...(data.duplicates || []), ...(data.invalid || [])];
    const issuesWrap = document.getElementById('previewIssuesWrap');
    const issuesList = document.getElementById('previewIssuesList');
    if (issues.length) {
        issuesWrap.classList.remove('hidden');
        issuesList.innerHTML = issues.map(i => `<li>Row ${i.row}: ${escapeHtml(i.company_name || '(blank)')} — ${escapeHtml(i.reason)}</li>`).join('');
    } else {
        issuesWrap.classList.add('hidden');
    }

    document.getElementById('importSourceSection').classList.add('hidden');
    document.getElementById('importPreviewSection').classList.remove('hidden');

    const confirmBtn = document.getElementById('confirmImportBtn');
    document.getElementById('previewBtn').classList.add('hidden');
    confirmBtn.classList.remove('hidden');
    confirmBtn.disabled = data.valid_count === 0;
    document.getElementById('confirmImportCount').textContent = data.valid_count;
}

function confirmImport() {
    if (!_importValidRows.length) return;

    const btn = document.getElementById('confirmImportBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing…';

    fetch(LC_ROUTES.importStore, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({
            rows: _importValidRows,
            ..._importLocation.values(),
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = `${data.imported} lead(s) imported.`;
            if (data.skipped_duplicate > 0) msg += ` ${data.skipped_duplicate} skipped (duplicate).`;
            if (data.failed > 0) msg += ` ${data.failed} failed.`;
            showToast(msg, 'success');
            closeImportModal();
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.message || 'Import failed', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(() => {
        showToast('Import failed', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeImportModal(); });
</script>
