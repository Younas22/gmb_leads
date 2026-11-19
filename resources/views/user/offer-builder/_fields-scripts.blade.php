<script>
    // Offer Builder fields: picking a ready-made choice fills the value; "Custom" shows the text box.
    if (!window.obPickWired) {
        window.obPickWired = true;
        document.addEventListener('change', e => {
            const pick = e.target.closest('.ob-pick');
            if (!pick) return;
            const field = pick.closest('.ob-field');
            const box = field.querySelector('.ob-input');
            const newBox = field.querySelector('.ob-new-service');
            if (newBox) newBox.classList.toggle('hidden', pick.value !== '__new_service');
            if (pick.value === '__new_service') {
                box.classList.add('hidden');
                newBox.querySelector('.ob-ns-title').focus();
                return;
            }
            // Picking one of the user's services also fills its price
            const price = pick.selectedOptions[0] && pick.selectedOptions[0].dataset.price;
            if (price) obSetField(pick.closest('.ob-quick') || document, 'price', price);
            if (pick.value === '__custom') {
                if (box.dataset.fromPick === '1') box.value = '';
                box.dataset.fromPick = '';
                box.classList.remove('hidden');
                box.focus();
            } else {
                box.value = pick.value;
                box.dataset.fromPick = '1';
                box.classList.add('hidden');
            }
            box.dispatchEvent(new Event('input', { bubbles: true }));
        });
        document.addEventListener('input', e => {
            if (e.target.classList && e.target.classList.contains('ob-input')) {
                const badge = document.querySelector('.ob-from-profile[data-for="' + e.target.dataset.key + '"]');
                if (badge && e.isTrusted) badge.classList.add('hidden');
            }
        });
    }
    // "+ Add a new service": save it to the AI Sales Profile Services, then select it here
    if (!window.obNewServiceWired) {
        window.obNewServiceWired = true;
        document.addEventListener('click', async e => {
            const save = e.target.closest('.ob-ns-save'), cancel = e.target.closest('.ob-ns-cancel');
            if (!save && !cancel) return;
            const box = e.target.closest('.ob-new-service'), field = box.closest('.ob-field');
            const pick = field.querySelector('.ob-pick'), input = field.querySelector('.ob-input'), msg = box.querySelector('.ob-ns-msg');
            if (cancel) {
                box.classList.add('hidden');
                const match = [...pick.options].some(o => o.value === input.value && input.value !== '');
                pick.value = match ? input.value : (input.value ? '__custom' : '');
                return;
            }
            const title = box.querySelector('.ob-ns-title').value.trim();
            if (!title) { msg.className = 'ob-ns-msg text-xs text-red-600'; msg.textContent = 'Please enter the service name.'; return; }
            save.disabled = true;
            try {
                const res = await fetch(box.dataset.url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ type: 'service', title, price_hint: box.querySelector('.ob-ns-price').value.trim(),
                        description: box.querySelector('.ob-ns-desc').value.trim(), is_active: 1 }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.ok === false) {
                    const first = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(first || data.message || 'Could not save the service.');
                }
                const opt = new Option(data.title + (data.price_hint ? ' — ' + data.price_hint : ''), data.title);
                opt.dataset.price = data.price_hint || '';
                pick.insertBefore(opt, pick.querySelector('option[value="__new_service"]'));
                const empty = pick.querySelector('option[value=""]');
                if (empty) empty.remove();
                pick.value = data.title;
                pick.dispatchEvent(new Event('change', { bubbles: true }));
                box.querySelectorAll('input, textarea').forEach(el => el.value = '');
                box.classList.add('hidden');
                if (window.cnmToast) cnmToast('Service added to your profile.');
            } catch (err) {
                msg.className = 'ob-ns-msg text-xs text-red-600';
                msg.textContent = err.message;
            } finally { save.disabled = false; }
        });
    }

    // Set a field's value from code (profile prefill): select the matching choice or switch to Custom.
    window.obSetField = function (root, key, value) {
        const field = root.querySelector('.ob-field[data-key="' + key + '"]');
        if (!field) return false;
        const box = field.querySelector('.ob-input'), pick = field.querySelector('.ob-pick');
        box.value = value;
        if (pick) {
            const match = [...pick.options].some(o => o.value === value && o.value !== '' && o.value !== '__custom');
            pick.value = match ? value : (value ? '__custom' : '');
            box.classList.toggle('hidden', match || !value);
            box.dataset.fromPick = match ? '1' : '';
        }
        box.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    };
    window.obValues = function (root) {
        const v = {};
        root.querySelectorAll('.ob-input').forEach(el => { if (el.value.trim() !== '') v[el.dataset.key] = el.value.trim(); });
        return v;
    };
</script>
