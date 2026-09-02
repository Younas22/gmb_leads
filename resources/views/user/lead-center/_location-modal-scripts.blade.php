<script>
// ===== Lead Center: Edit Location =====
let _locLeadId = null;
let _locHelper = null; // lcCascadingLocation instance, created lazily

function openLocationModal(leadId, countryId, stateId, cityId) {
    _locLeadId = leadId;

    if (!_locHelper) {
        _locHelper = lcCascadingLocation('loc_country_select', 'loc_state_select', 'loc_city_select');
    }

    document.getElementById('locationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    _locHelper.setValues(countryId, stateId, cityId);
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function saveLocation() {
    if (!_locLeadId) return;
    const btn = document.getElementById('saveLocationBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`${LC_ROUTES.locationBase}/${_locLeadId}/location`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(_locHelper.values())
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeLocationModal();
            setTimeout(() => location.reload(), 700);
        } else {
            showToast(data.message || 'Failed to update location', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(() => {
        showToast('Failed to update location', 'error');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLocationModal(); });
</script>
