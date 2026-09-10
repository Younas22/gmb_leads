<script>
// ===== Lead Center: Share Access =====
const LC_ACCESS_ROUTES = {
    share: '{{ route("user.lead-center.access.share") }}',
    respondBase: '{{ url("/user/lead-center/access") }}',
    revokeBase: '{{ url("/user/lead-center/access") }}',
    switchTo: '{{ route("user.lead-center.access.switch") }}',
};

function openShareAccessModal() {
    document.getElementById('shareAccessModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('shareAccessEmail').value = '';
    document.getElementById('shareAccessEmail').focus();
}

function closeShareAccessModal() {
    document.getElementById('shareAccessModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function sendShareAccessRequest() {
    const email = document.getElementById('shareAccessEmail').value.trim();
    if (!email) { showToast('Please enter an email address', 'error'); return; }

    fetch(LC_ACCESS_ROUTES.share, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ email })
    })
    .then(async r => {
        const data = await r.json().catch(() => null);
        if (!r.ok || !data || !data.success) throw new Error((data && data.message) || 'Failed to send request');
        return data;
    })
    .then(data => {
        showToast(data.message, 'success');
        document.getElementById('noSharedGrants')?.remove();
        const g = data.grant;
        const html = `
            <div class="flex items-center justify-between p-2.5 border border-gray-100 rounded-xl" data-shared-grant-id="${g.id}">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(g.grantee.name)}</p>
                    <p class="text-xs text-gray-400 truncate">${escapeHtml(g.grantee.email)}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                    <button onclick="revokeSharedGrant(${g.id})" title="Remove access" class="text-gray-300 hover:text-red-500">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            </div>`;
        document.getElementById('mySharedGrantsList').insertAdjacentHTML('afterbegin', html);
        document.getElementById('shareAccessEmail').value = '';
    })
    .catch(err => showToast(err.message || 'Failed to send request', 'error'));
}

function revokeSharedGrant(id) {
    if (!confirm('Remove this person\'s access to your Lead Center?')) return;
    fetch(`${LC_ACCESS_ROUTES.revokeBase}/${id}/revoke`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-shared-grant-id="${id}"]`)?.remove();
            showToast('Access removed', 'success');
        } else {
            showToast(data.message || 'Failed to remove access', 'error');
        }
    })
    .catch(() => showToast('Failed to remove access', 'error'));
}

function lcRespondGrant(id, action) {
    fetch(`${LC_ACCESS_ROUTES.respondBase}/${id}/respond`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ action })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            document.querySelector(`[data-grant-id="${id}"]`)?.remove();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to respond', 'error');
        }
    })
    .catch(() => showToast('Failed to respond', 'error'));
}

function lcSwitchAccount(ownerId) {
    fetch(LC_ACCESS_ROUTES.switchTo, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ owner_id: ownerId || null })
    })
    .then(async r => {
        const data = await r.json().catch(() => null);
        if (!r.ok || !data || !data.success) throw new Error((data && data.message) || 'Failed to switch');
        return data;
    })
    .then(data => {
        showToast(data.message, 'success');
        setTimeout(() => location.href = '{{ route("user.lead-center.index") }}', 500);
    })
    .catch(err => showToast(err.message || 'Failed to switch account', 'error'));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeShareAccessModal(); });
</script>
