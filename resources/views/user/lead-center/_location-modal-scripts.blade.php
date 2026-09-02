<script>
// ===== Lead Center: Edit Location =====
let _locLeadId = null;
let _locInitializing = false;

function openLocationModal(leadId, countryId, stateId, cityId) {
    _locLeadId = leadId;
    _locInitializing = true;

    const countrySelect = document.getElementById('loc_country_select');
    const stateSelect = document.getElementById('loc_state_select');
    const citySelect = document.getElementById('loc_city_select');

    countrySelect.value = countryId || '';
    resetLocSelect(stateSelect, 'State');
    resetLocSelect(citySelect, 'City');

    document.getElementById('locationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    if (countryId) {
        loadLocOptions(`${LC_ROUTES.apiStatesBase}/${countryId}`, stateSelect, 'State', stateId, () => {
            if (stateId) {
                loadLocOptions(`${LC_ROUTES.apiCitiesBase}/${stateId}`, citySelect, 'City', cityId, () => { _locInitializing = false; });
            } else {
                _locInitializing = false;
            }
        });
    } else {
        _locInitializing = false;
    }
}

function closeLocationModal() {
    document.getElementById('locationModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function resetLocSelect(select, label) {
    select.innerHTML = `<option value="">${label}</option>`;
    select.disabled = true;
}

function loadLocOptions(url, select, label, selectedId, doneCb) {
    fetch(url)
        .then(r => r.json())
        .then(items => {
            resetLocSelect(select, label);
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                if (selectedId && String(item.id) === String(selectedId)) opt.selected = true;
                select.appendChild(opt);
            });
            select.disabled = false;
            if (doneCb) doneCb();
        })
        .catch(() => {
            select.innerHTML = `<option value="">Error loading</option>`;
            if (doneCb) doneCb();
        });
}

document.getElementById('loc_country_select').addEventListener('change', function() {
    if (_locInitializing) return;
    resetLocSelect(document.getElementById('loc_state_select'), 'State');
    resetLocSelect(document.getElementById('loc_city_select'), 'City');
    if (this.value) loadLocOptions(`${LC_ROUTES.apiStatesBase}/${this.value}`, document.getElementById('loc_state_select'), 'State');
});

document.getElementById('loc_state_select').addEventListener('change', function() {
    if (_locInitializing) return;
    resetLocSelect(document.getElementById('loc_city_select'), 'City');
    if (this.value) loadLocOptions(`${LC_ROUTES.apiCitiesBase}/${this.value}`, document.getElementById('loc_city_select'), 'City');
});

function saveLocation() {
    if (!_locLeadId) return;
    const btn = document.getElementById('saveLocationBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`${LC_ROUTES.locationBase}/${_locLeadId}/location`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            country_id: document.getElementById('loc_country_select').value || null,
            state_id: document.getElementById('loc_state_select').value || null,
            city_id: document.getElementById('loc_city_select').value || null,
        })
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
