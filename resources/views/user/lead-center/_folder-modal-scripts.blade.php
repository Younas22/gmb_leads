<script>
// ===== Lead Center: Folder System =====
let _folderLeadIds = [];
let _folderMode = 'move'; // 'move' | 'create'
let _selectedColor = 'blue';

function openFolderModal(leadIds, mode) {
    _folderLeadIds = leadIds || [];
    _folderMode = mode || 'move';
    _selectedColor = 'blue';

    const modal = document.getElementById('folderModal');
    const title = document.getElementById('folderModalTitle');
    const sub = document.getElementById('folderModalSub');
    const createBtn = document.getElementById('createFolderAndMove');

    if (mode === 'move') {
        title.textContent = 'Move to Folder';
        sub.textContent = _folderLeadIds.length + ' lead(s) selected';
        createBtn.classList.remove('hidden');
    } else {
        title.textContent = 'Create Folder';
        sub.textContent = '';
        createBtn.classList.add('hidden');
    }

    document.querySelectorAll('.color-dot').forEach(d => {
        d.classList.remove('ring-gray-500');
        d.classList.add('ring-transparent');
        if (d.dataset.color === 'blue') {
            d.classList.remove('ring-transparent');
            d.classList.add('ring-gray-500');
        }
    });
    document.getElementById('newFolderName').value = '';

    loadLcFolders();
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeFolderModal() {
    document.getElementById('folderModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function selectColor(btn) {
    _selectedColor = btn.dataset.color;
    document.querySelectorAll('.color-dot').forEach(d => {
        d.classList.remove('ring-gray-500');
        d.classList.add('ring-transparent');
    });
    btn.classList.remove('ring-transparent');
    btn.classList.add('ring-gray-500');
}

function loadLcFolders() {
    const list = document.getElementById('folderList');
    list.innerHTML = '<p class="text-sm text-gray-400 italic">Loading…</p>';

    fetch(LC_ROUTES.foldersIndex, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(folders => {
            if (!folders.length) {
                list.innerHTML = '<p class="text-sm text-gray-400 italic">No folders yet. Create one below.</p>';
                return;
            }
            const colorMap = {
                blue:'bg-blue-100 text-blue-700', green:'bg-green-100 text-green-700',
                purple:'bg-purple-100 text-purple-700', red:'bg-red-100 text-red-700',
                orange:'bg-orange-100 text-orange-700', pink:'bg-pink-100 text-pink-700',
                teal:'bg-teal-100 text-teal-700'
            };
            list.innerHTML = folders.map(f => {
                const cc = colorMap[f.color] || colorMap.blue;
                return `<div class="flex items-center justify-between p-2.5 border border-gray-100 rounded-xl hover:bg-gray-50 transition-colors group">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg ${cc} text-xs font-bold">
                            <i class="fas fa-folder"></i>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">${f.name}</p>
                            <p class="text-xs text-gray-400">${f.leads_count} lead${f.leads_count !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        ${_folderMode === 'move' ? `<button onclick="moveLeadsToFolder(${f.id}, '${f.name.replace(/'/g, "\\'")}')"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                            Move Here
                        </button>` : ''}
                        <button onclick="deleteLcFolder(${f.id}, '${f.name.replace(/'/g, "\\'")}')"
                            class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 w-6 h-6 flex items-center justify-center rounded transition-all"
                            title="Delete folder">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(() => { list.innerHTML = '<p class="text-sm text-red-400">Failed to load folders.</p>'; });
}

function moveLeadsToFolder(folderId, folderName) {
    if (!_folderLeadIds.length) { showToast('No leads selected.', 'error'); return; }
    bulkPost({ action: 'move_to_folder', lead_ids: _folderLeadIds, folder_id: folderId }, () => {
        closeFolderModal();
        setTimeout(() => location.reload(), 800);
    });
}

function createFolder() {
    const name = document.getElementById('newFolderName').value.trim();
    if (!name) { document.getElementById('newFolderName').focus(); return; }

    fetch(LC_ROUTES.foldersStore, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ name, color: _selectedColor })
    })
    .then(r => r.json())
    .then(folder => {
        if (_folderMode === 'move' && _folderLeadIds.length) {
            moveLeadsToFolder(folder.id, folder.name);
        } else {
            showToast(`Folder "${folder.name}" created!`, 'success');
            closeFolderModal();
            setTimeout(() => location.reload(), 800);
        }
    })
    .catch(() => showToast('Failed to create folder.', 'error'));
}

function deleteLcFolder(id, name) {
    if (!confirm(`Delete folder "${name}"? Leads will not be deleted.`)) return;
    fetch(`${LC_ROUTES.foldersDeleteBase}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`Folder "${name}" deleted.`, 'success');
            loadLcFolders();
            setTimeout(() => location.reload(), 800);
        }
    })
    .catch(() => showToast('Failed to delete folder.', 'error'));
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFolderModal(); });
</script>
