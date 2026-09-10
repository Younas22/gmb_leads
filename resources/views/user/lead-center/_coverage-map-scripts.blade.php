<!-- Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
// ===== Lead Center: Coverage Map =====
const MAP_DATA_URL = '{{ route("user.lead-center.resources.map-data") }}';
const MAP_BOUNDARY_URL = '{{ route("user.lead-center.resources.boundary") }}';
const LEAD_CENTER_INDEX_URL = '{{ route("user.lead-center.index") }}';

let _leafletMap = null;
let _mapDataLoaded = false;
let _lastMapData = null;
let _boundaryLayerGroup = null;
let _boundaryLayerIds = new Set(); // "type:id" already drawn, to avoid duplicates
let _boundaryLevel = 'state'; // default, per request
let _boundaryLoc = null; // lcCascadingLocation instance for the boundary control

function openMapModal() {
    document.getElementById('mapModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    if (!_mapDataLoaded) {
        loadCoverageMapData();
    } else if (_leafletMap) {
        setTimeout(() => _leafletMap.invalidateSize(), 50);
    }

    if (!_boundaryLoc) {
        _boundaryLoc = lcCascadingLocation('boundary_country_select', 'boundary_state_select', 'boundary_city_select');
        // Manually picking a location ADDS its outline on top of whatever's already shown.
        $('#boundary_country_select, #boundary_state_select, #boundary_city_select').on('change', onBoundarySelectChange);
    }
}

function closeMapModal() {
    document.getElementById('mapModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function loadCoverageMapData() {
    fetch(MAP_DATA_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            _mapDataLoaded = true;
            document.getElementById('mapLoadingState').classList.add('hidden');

            const total = (data.targeted?.length || 0) + (data.pending?.length || 0) + (data.active?.length || 0);
            if (total === 0) {
                document.getElementById('mapEmptyState').classList.remove('hidden');
                return;
            }

            initLeafletMap(data);
            setBoundaryLevel('state'); // draws the default level's targeted-location outlines
        })
        .catch(() => {
            document.getElementById('mapLoadingState').innerHTML =
                '<p class="text-sm text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>Failed to load map data.</p>';
        });
}

function initLeafletMap(data) {
    _lastMapData = data;
    _leafletMap = L.map('leafletMap').setView([20, 0], 2);
    _boundaryLayerGroup = L.layerGroup().addTo(_leafletMap);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(_leafletMap);

    const bounds = [];
    addMapMarkers(data.targeted, '#3b82f6', 'Targeted', bounds);
    addMapMarkers(data.pending, '#f97316', 'Pending', bounds);
    addMapMarkers(data.active, '#22c55e', 'In Progress / Converted', bounds);

    if (bounds.length) {
        _leafletMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 });
    }

    setTimeout(() => _leafletMap.invalidateSize(), 50);
}

function addMapMarkers(points, color, categoryLabel, bounds) {
    (points || []).forEach(p => {
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 9,
            fillColor: color,
            color: '#ffffff',
            weight: 2,
            fillOpacity: 0.9,
        }).addTo(_leafletMap);

        let popupHtml = `<div style="font-size:13px;min-width:150px">
            <strong>${mapEscapeHtml(p.label)}</strong><br>
            <span style="color:${color};font-weight:600">${mapEscapeHtml(categoryLabel)}</span>`;
        if (p.count) popupHtml += `<br>${p.count} lead${p.count !== 1 ? 's' : ''}`;
        if (p.notes) popupHtml += `<br><em>${mapEscapeHtml(p.notes)}</em>`;

        const filterUrl = buildLeadCenterFilterUrl(p.country_id, p.state_id, p.city_id);
        if (filterUrl) {
            popupHtml += `<br><a href="${filterUrl}" style="color:#2563eb;font-weight:600;display:inline-block;margin-top:4px">View these leads &rarr;</a>`;
        }
        popupHtml += `</div>`;

        marker.bindPopup(popupHtml);
        bounds.push([p.lat, p.lng]);
    });
}

function buildLeadCenterFilterUrl(countryId, stateId, cityId) {
    if (!countryId && !stateId && !cityId) return null;
    const params = new URLSearchParams();
    if (countryId) params.set('country_id', countryId);
    if (stateId) params.set('state_id', stateId);
    if (cityId) params.set('city_id', cityId);
    return `${LEAD_CENTER_INDEX_URL}?${params.toString()}`;
}

function mapEscapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

// ===== Boundary drawing =====
// Switching level shows the outline of EVERY Targeted Location at that level, all at once
// (e.g. every state you've added) — not just one picked from the dropdown. The dropdown is
// there for adding one extra, ad-hoc outline on top of that, without needing to add it as a
// Targeted Location first.
function setBoundaryLevel(level) {
    _boundaryLevel = level;

    ['country', 'state', 'city'].forEach(l => {
        const btn = document.getElementById('boundaryLevel' + l.charAt(0).toUpperCase() + l.slice(1));
        btn.className = 'flex-1 px-2 py-1 rounded-md text-[11px] font-medium transition-colors ' +
            (l === level ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500');
    });

    drawAllTargetedBoundariesForLevel(level);
}

async function drawAllTargetedBoundariesForLevel(level) {
    clearBoundary();

    if (!_leafletMap) return;

    const idKey = { country: 'country_id', state: 'state_id', city: 'city_id' }[level];
    const seen = new Set();
    const items = (_lastMapData?.targeted || []).filter(p => {
        const id = p[idKey];
        if (!id || seen.has(id)) return false;
        seen.add(id);
        return true;
    });

    const statusEl = document.getElementById('boundaryStatusText');

    if (!items.length) {
        statusEl.textContent = 'No targeted locations at this level yet.';
        return;
    }

    for (let i = 0; i < items.length; i++) {
        statusEl.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Loading outline ${i + 1}/${items.length}…`;
        await addBoundaryLayer(level, items[i][idKey]);
    }

    statusEl.textContent = `${items.length} outline${items.length !== 1 ? 's' : ''} shown.`;
}

function onBoundarySelectChange() {
    const values = _boundaryLoc.values();
    const idForLevel = { country: values.country_id, state: values.state_id, city: values.city_id }[_boundaryLevel];
    if (idForLevel) {
        addBoundaryLayer(_boundaryLevel, idForLevel).then(() => {
            document.getElementById('boundaryStatusText').textContent = 'Outline added.';
        });
    }
}

function addBoundaryLayer(type, id) {
    const key = `${type}:${id}`;
    if (_boundaryLayerIds.has(key)) {
        return Promise.resolve(); // already drawn
    }

    if (!_leafletMap) {
        return new Promise(resolve => setTimeout(() => addBoundaryLayer(type, id).then(resolve), 300));
    }

    return fetch(`${MAP_BOUNDARY_URL}?type=${type}&id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(async r => {
            const data = await r.json().catch(() => null);
            if (!r.ok || !data || !data.success) throw new Error((data && data.message) || 'No outline found.');
            return data;
        })
        .then(data => {
            const layer = L.geoJSON(data.geojson, {
                style: { color: '#6366f1', weight: 2, fillColor: '#6366f1', fillOpacity: 0.08 },
            });
            _boundaryLayerGroup.addLayer(layer);
            _boundaryLayerIds.add(key);

            const groupBounds = _boundaryLayerGroup.getBounds();
            if (groupBounds.isValid()) {
                _leafletMap.fitBounds(groupBounds, { padding: [30, 30] });
            }
        })
        .catch(err => {
            document.getElementById('boundaryStatusText').innerHTML =
                `<span class="text-red-400">${mapEscapeHtml(err.message || 'Could not load outline.')}</span>`;
        });
}

function clearBoundary() {
    if (_boundaryLayerGroup) {
        _boundaryLayerGroup.clearLayers();
    }
    _boundaryLayerIds.clear();
    document.getElementById('boundaryStatusText').textContent = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMapModal(); });
</script>
