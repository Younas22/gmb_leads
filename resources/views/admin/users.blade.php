@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<main class="p-4 lg:p-8">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $users->total() }}</p>
                </div>
                <div class="bg-primary-100 rounded-lg p-3">
                    <i class="fas fa-users text-primary-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                    <p class="text-3xl font-bold text-green-600">{{ \App\Models\User::where('status', 'active')->count() }}</p>
                </div>
                <div class="bg-green-100 rounded-lg p-3">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Admins</p>
                    <p class="text-3xl font-bold text-purple-600">{{ \App\Models\User::where('user_type', 'admin')->count() }}</p>
                </div>
                <div class="bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-user-shield text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">New This Month</p>
                    <p class="text-3xl font-bold text-orange-600">{{ \App\Models\User::whereMonth('created_at', now()->month)->count() }}</p>
                </div>
                <div class="bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-user-plus text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-800">All Users</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search users..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Login Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $u)
                    <tr class="hover:bg-gray-50 transition-colors user-row" data-user-id="{{ $u->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                @if($u->avatar)
                                    <img src="{{ asset('public/' . $u->avatar) }}" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ strtoupper(substr($u->first_name ?? $u->name, 0, 1)) }}{{ strtoupper(substr($u->last_name ?? '', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-800 user-name">
                                        {{ $u->first_name ? $u->first_name . ' ' . $u->last_name : $u->name }}
                                    </p>
                                    <p class="text-xs text-gray-500">ID: {{ $u->id }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-600 user-email">{{ $u->email }}</span>
                                @if($u->email_verified)
                                    <i class="fas fa-check-circle text-green-500 ml-2" title="Verified"></i>
                                @else
                                    <i class="fas fa-exclamation-circle text-yellow-500 ml-2" title="Not Verified"></i>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($u->user_type === 'admin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-crown mr-1"></i> Admin
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-user mr-1"></i> User
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($u->login_type === 'google')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fab fa-google mr-1"></i> Google
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-envelope mr-1"></i> Email
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($u->status === 'active')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="text-sm text-gray-600">{{ $u->created_at->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-400">{{ $u->created_at->diffForHumans() }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($u->last_login)
                                <div>
                                    <p class="text-sm text-gray-600">{{ $u->last_login->format('M d, Y') }}</p>
                                    <p class="text-xs text-gray-400">{{ $u->last_login->diffForHumans() }}</p>
                                </div>
                            @else
                                <span class="text-sm text-gray-400">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-2">
                                <button onclick="viewUser({{ $u->id }})" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editUser({{ $u->id }})" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($u->id !== auth()->id())
                                <button onclick="confirmDelete({{ $u->id }}, '{{ addslashes($u->first_name ? $u->first_name . ' ' . $u->last_name : $u->name) }}')" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-gray-100 rounded-full p-4 mb-4">
                                    <i class="fas fa-users text-gray-400 text-3xl"></i>
                                </div>
                                <p class="text-gray-500 font-medium">No users found</p>
                                <p class="text-gray-400 text-sm">Users will appear here once they register</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</main>

<!-- View User Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">User Details</h3>
                    <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6" id="viewModalContent">
                <div class="flex justify-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Edit User</h3>
                    <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form id="editForm" onsubmit="submitEdit(event)">
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editUserId">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" id="editFirstName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" id="editLastName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="editEmail" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                            <select id="editUserType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="editStatus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="editSubmitBtn" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 rounded-full p-3 mr-4">
                        <i class="fas fa-trash text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Delete User</h3>
                </div>
                <p class="text-gray-600 mb-2">Are you sure you want to delete this user?</p>
                <p class="text-sm font-medium text-gray-800 mb-4" id="deleteUserName"></p>
                <p class="text-xs text-red-600 mb-6">This action cannot be undone. All user data will be permanently removed.</p>
                <input type="hidden" id="deleteUserId">
                <div class="flex space-x-3">
                    <button onclick="closeModal('deleteModal')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button onclick="deleteUser()" id="deleteBtn" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 z-50 hidden">
    <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
        <i id="toastIcon" class="fas fa-check-circle text-green-400"></i>
        <span id="toastMessage"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

// Simple search filter
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.user-row');

    rows.forEach(row => {
        const name = row.querySelector('.user-name').textContent.toLowerCase();
        const email = row.querySelector('.user-email').textContent.toLowerCase();

        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// View User
function viewUser(userId) {
    document.getElementById('viewModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('viewModalContent').innerHTML = '<div class="flex justify-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i></div>';

    fetch(`{{ url('admin/users') }}/${userId}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        const avatar = data.avatar
            ? `<img src="${data.avatar}" class="w-20 h-20 rounded-full object-cover mx-auto">`
            : `<div class="w-20 h-20 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-2xl mx-auto">${(data.first_name || data.name || '?')[0].toUpperCase()}</div>`;

        document.getElementById('viewModalContent').innerHTML = `
            <div class="text-center mb-6">
                ${avatar}
                <h4 class="text-xl font-semibold text-gray-800 mt-3">${data.first_name ? data.first_name + ' ' + (data.last_name || '') : data.name}</h4>
                <p class="text-gray-500">${data.email}</p>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">User Type</p>
                    <p class="font-medium text-gray-800">${data.user_type === 'admin' ? '<i class="fas fa-crown text-purple-600 mr-1"></i> Admin' : '<i class="fas fa-user text-blue-600 mr-1"></i> User'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Status</p>
                    <p class="font-medium ${data.status === 'active' ? 'text-green-600' : 'text-red-600'}">${data.status === 'active' ? '<i class="fas fa-check-circle mr-1"></i> Active' : '<i class="fas fa-times-circle mr-1"></i> Inactive'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Login Type</p>
                    <p class="font-medium text-gray-800">${data.login_type === 'google' ? '<i class="fab fa-google text-red-600 mr-1"></i> Google' : '<i class="fas fa-envelope mr-1"></i> Email'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Email Verified</p>
                    <p class="font-medium ${data.email_verified ? 'text-green-600' : 'text-yellow-600'}">${data.email_verified ? '<i class="fas fa-check-circle mr-1"></i> Yes' : '<i class="fas fa-exclamation-circle mr-1"></i> No'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Joined</p>
                    <p class="font-medium text-gray-800">${data.created_at}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Last Login</p>
                    <p class="font-medium text-gray-800">${data.last_login}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Saved Leads</p>
                    <p class="font-medium text-gray-800">${data.saved_leads_count}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-gray-500 mb-1">Searches</p>
                    <p class="font-medium text-gray-800">${data.search_histories_count}</p>
                </div>
            </div>
        `;
    })
    .catch(error => {
        document.getElementById('viewModalContent').innerHTML = '<p class="text-red-600 text-center">Error loading user data</p>';
    });
}

// Edit User
function editUser(userId) {
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    fetch(`{{ url('admin/users') }}/${userId}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('editUserId').value = data.id;
        document.getElementById('editFirstName').value = data.first_name || '';
        document.getElementById('editLastName').value = data.last_name || '';
        document.getElementById('editEmail').value = data.email;
        document.getElementById('editUserType').value = data.user_type;
        document.getElementById('editStatus').value = data.status;
    });
}

// Submit Edit
function submitEdit(e) {
    e.preventDefault();
    const userId = document.getElementById('editUserId').value;
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

    fetch(`{{ url('admin/users') }}/${userId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            first_name: document.getElementById('editFirstName').value,
            last_name: document.getElementById('editLastName').value,
            email: document.getElementById('editEmail').value,
            user_type: document.getElementById('editUserType').value,
            status: document.getElementById('editStatus').value
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Save Changes';
        if (data.success) {
            closeModal('editModal');
            showToast('User updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Error updating user', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = 'Save Changes';
        showToast('Error updating user', 'error');
    });
}

// Confirm Delete
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Delete User
function deleteUser() {
    const userId = document.getElementById('deleteUserId').value;
    const btn = document.getElementById('deleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';

    fetch(`{{ url('admin/users') }}/${userId}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Delete';
        if (data.success) {
            closeModal('deleteModal');
            showToast('User deleted successfully', 'success');
            // Remove row from table
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (row) row.remove();
        } else {
            showToast(data.error || 'Error deleting user', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = 'Delete';
        showToast('Error deleting user', 'error');
    });
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on backdrop click
['viewModal', 'editModal', 'deleteModal'].forEach(modalId => {
    document.getElementById(modalId).addEventListener('click', function(e) {
        if (e.target === this) closeModal(modalId);
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        ['viewModal', 'editModal', 'deleteModal'].forEach(modalId => {
            if (!document.getElementById(modalId).classList.contains('hidden')) {
                closeModal(modalId);
            }
        });
    }
});

// Show Toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMessage');

    msg.textContent = message;
    icon.className = type === 'success'
        ? 'fas fa-check-circle text-green-400'
        : 'fas fa-exclamation-circle text-red-400';

    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}
</script>
@endpush
