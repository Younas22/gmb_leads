<script>
/**
 * Wires up a Country → State → City cascading Select2 trio.
 * Reused by the Import modal, the Edit Location modal, and the Resources
 * "Targeted Locations" form — one implementation instead of three copies.
 *
 * Requires LC_ROUTES.apiStatesBase / apiCitiesBase to already be defined on the page.
 */
function lcCascadingLocation(countryElId, stateElId, cityElId) {
    const $country = $('#' + countryElId);
    const $state = $('#' + stateElId);
    const $city = $('#' + cityElId);
    let initializing = false;

    const opts = (placeholder) => ({ placeholder, allowClear: true, width: '100%' });

    $country.select2(opts('Country'));
    $state.select2(opts('State'));
    $city.select2(opts('City'));

    function resetSel($el, placeholder) {
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el[0].innerHTML = `<option value="">${placeholder}</option>`;
        $el[0].disabled = true;
        $el.select2(opts(placeholder));
    }

    function loadInto($sel, url, placeholder, preselectId, doneCb) {
        fetch(url)
            .then(r => r.json())
            .then(items => {
                resetSel($sel, placeholder);
                items.forEach(item => {
                    const opt = new Option(item.name, item.id, false, !!(preselectId && String(item.id) === String(preselectId)));
                    $sel[0].add(opt);
                });
                $sel[0].disabled = false;
                $sel.trigger('change.select2');
                if (doneCb) doneCb();
            })
            .catch(() => {
                resetSel($sel, 'Error loading');
                if (doneCb) doneCb();
            });
    }

    $country.on('change', function() {
        if (initializing) return;
        resetSel($state, 'State');
        resetSel($city, 'City');
        if (this.value) loadInto($state, `${LC_ROUTES.apiStatesBase}/${this.value}`, 'State');
    });

    $state.on('change', function() {
        if (initializing) return;
        resetSel($city, 'City');
        if (this.value) loadInto($city, `${LC_ROUTES.apiCitiesBase}/${this.value}`, 'City');
    });

    return {
        setValues(countryId, stateId, cityId) {
            initializing = true;
            resetSel($state, 'State');
            resetSel($city, 'City');
            $country.val(countryId || '').trigger('change.select2');

            if (countryId) {
                loadInto($state, `${LC_ROUTES.apiStatesBase}/${countryId}`, 'State', stateId, () => {
                    if (stateId) {
                        loadInto($city, `${LC_ROUTES.apiCitiesBase}/${stateId}`, 'City', cityId, () => { initializing = false; });
                    } else {
                        initializing = false;
                    }
                });
            } else {
                initializing = false;
            }
        },
        reset() {
            this.setValues(null, null, null);
        },
        values() {
            return {
                country_id: $country.val() || null,
                state_id: $state.val() || null,
                city_id: $city.val() || null,
            };
        },
    };
}
</script>
