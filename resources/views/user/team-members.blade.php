@extends('layouts.app')

@section('title', 'Team Members Management')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Main Content -->
<div class="p-4 lg:p-8">
    <!-- Plan Limitation Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 sm:p-4 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-base sm:text-lg"></i>
            </div>
            <div class="ml-2 sm:ml-3 flex-1">
                <h3 class="text-xs sm:text-sm font-semibold text-blue-800">Team Members Management</h3>
                <p class="text-xs sm:text-sm text-blue-700 mt-1">
                    Add and manage team members for your company account.
                    @if($teamMembersLimit == -1)
                        Your plan supports <strong>unlimited</strong> team members.
                    @else
                        Your plan allows <strong>{{ $teamMembersLimit }}</strong> team member(s).
                        @if($remainingSlots === 0)
                            <span class="text-red-600 font-semibold">Limit reached!</span>
                        @else
                            Remaining: <strong>{{ $remainingSlots }}</strong>
                        @endif
                    @endif
                </p>
                <p class="text-xs sm:text-sm text-blue-700 mt-2">
                    <i class="fas fa-users mr-1"></i>
                    Team members can access the system using their own credentials.
                </p>
            </div>
        </div>
    </div>

    <!-- Usage Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-primary-100 rounded-lg">
                    <i class="fas fa-users text-primary-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Total Members</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $teamMembers->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Active Members</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $teamMembers->where('status', 'active')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-orange-100 rounded-lg">
                    <i class="fas fa-user-slash text-orange-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Inactive Members</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $teamMembers->where('status', 'inactive')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-2 sm:p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-user-plus text-blue-600 text-base sm:text-xl"></i>
                </div>
                <div class="ml-3 sm:ml-4">
                    <p class="text-xs sm:text-sm font-medium text-gray-600">Available Slots</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $remainingSlots === 'unlimited' ? '∞' : $remainingSlots }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                <div class="w-full sm:w-auto">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Team Members</h3>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">Manage your team members and their access</p>
                </div>
                @if($canAddMore)
                <button id="add-member-btn" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base font-medium text-center">
                    <i class="fas fa-plus mr-1 sm:mr-2 text-xs"></i>Add Team Member
                </button>
                @else
                <button disabled class="w-full sm:w-auto bg-gray-400 cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-lg text-sm sm:text-base font-medium text-center" title="Team member limit reached">
                    <i class="fas fa-lock mr-1 sm:mr-2 text-xs"></i>Limit Reached
                </button>
                @endif
            </div>
        </div>

        <div class="p-6 space-y-4">
            @forelse($teamMembers as $member)
            <!-- Team Member Card -->
            <div class="border border-gray-200 rounded-lg p-3 sm:p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:space-x-3 mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center text-white font-semibold text-sm sm:text-base">
                                    {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="text-sm sm:text-base font-semibold text-gray-800">{{ $member->name }}</h4>
                                    <p class="text-xs sm:text-sm text-gray-600 break-all">{{ $member->email }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-12 sm:ml-0">
                                <span class="px-2 py-0.5 sm:py-1 {{ $member->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }} text-xs font-medium rounded-full whitespace-nowrap">
                                    {{ ucfirst($member->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Email</p>
                                <p class="text-xs sm:text-sm text-gray-700 break-all">{{ $member->email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Password</p>
                                <p class="text-xs sm:text-sm text-gray-700 font-mono">{{ $member->plain_password ?? '••••••' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Created</p>
                                <p class="text-xs sm:text-sm text-gray-700">{{ $member->created_at->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Last Login</p>
                                <p class="text-xs sm:text-sm text-gray-700">{{ $member->last_login ? $member->last_login->format('M d, Y') : 'Never' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 ml-2">
                        <!-- Copy Credentials -->
                        <button onclick="copyCredentials('{{ $member->email }}', '{{ $member->plain_password }}', '{{ url('auth') }}')"
                                class="px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 rounded-lg text-xs font-medium transition-colors"
                                title="Copy Credentials">
                            <i class="fas fa-copy"></i>
                        </button>

                        <!-- Toggle Status -->
                        <button onclick="toggleMemberStatus({{ $member->id }})"
                                class="px-3 py-1.5 {{ $member->status === 'active' ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} rounded-lg text-xs font-medium transition-colors"
                                title="{{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}">
                            <i class="fas fa-{{ $member->status === 'active' ? 'pause' : 'play' }} mr-1"></i>
                            {{ $member->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>

                        <!-- Edit -->
                        <button onclick="editMember({{ $member->id }})"
                                class="px-3 py-1.5 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg text-xs font-medium transition-colors"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>

                        <!-- Delete -->
                        <button onclick="deleteMember({{ $member->id }})"
                                class="px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg text-xs font-medium transition-colors"
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <!-- No Team Members -->
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <i class="fas fa-users text-gray-400 text-2xl"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">No Team Members Yet</h4>
                <p class="text-gray-600 mb-4">Add team members to collaborate with your team</p>
                @if($canAddMore)
                <button onclick="document.getElementById('add-member-btn').click()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-plus mr-2"></i>Add First Team Member
                </button>
                @endif
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Success/Error Toast Notifications -->
<div id="successToast" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 hidden transform transition-all duration-300">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="successToastMessage">Success!</span>
    </div>
</div>

<div id="errorToast" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 hidden transform transition-all duration-300">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span id="errorToastMessage">Error!</span>
    </div>
</div>

<!-- Add/Edit Team Member Modal -->
<div id="member-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-800">Add Team Member</h3>
                <button onclick="closeMemberModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form id="member-form" class="p-6 space-y-4">
            <input type="hidden" id="member-id" value="">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                <input type="text" id="first-name" name="first_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                <input type="text" id="last-name" name="last_name" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div id="password-field">
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input type="text" id="password" name="password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Password will be visible to the team member. They can change it later.</p>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="submit" id="submit-btn"
                        class="flex-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>Save Member
                </button>
                <button type="button" onclick="closeMemberModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Define URLs for API calls
const teamMembersBaseUrl = '{{ url("user/team-members") }}';
const teamMembersStoreUrl = '{{ route("user.team-members.store") }}';

let isEditMode = false;

// Toast notification functions
function showSuccessToast(message) {
    const toast = document.getElementById('successToast');
    document.getElementById('successToastMessage').textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

function showErrorToast(message) {
    const toast = document.getElementById('errorToast');
    document.getElementById('errorToastMessage').textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 4000);
}

// Show add member modal
document.getElementById('add-member-btn')?.addEventListener('click', () => {
    isEditMode = false;
    document.getElementById('modal-title').textContent = 'Add Team Member';
    document.getElementById('member-form').reset();
    document.getElementById('member-id').value = '';
    document.getElementById('password-field').style.display = 'block';
    document.getElementById('password').required = true;
    document.getElementById('member-modal').classList.remove('hidden');
});

// Edit member
function editMember(memberId) {
    isEditMode = true;
    document.getElementById('modal-title').textContent = 'Edit Team Member';
    document.getElementById('member-id').value = memberId;
    document.getElementById('password-field').style.display = 'block';
    document.getElementById('password').required = false;
    document.getElementById('password').placeholder = 'Leave blank to keep current password';

    // Fetch member details
    fetch(`${teamMembersBaseUrl}/${memberId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('first-name').value = data.team_member.first_name;
            document.getElementById('last-name').value = data.team_member.last_name;
            document.getElementById('email').value = data.team_member.email;
            document.getElementById('password').value = data.team_member.password || '';
            document.getElementById('member-modal').classList.remove('hidden');
        } else {
            showErrorToast(data.message || 'Failed to load team member details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('Failed to load team member details');
    });
}

// Close modal
function closeMemberModal() {
    document.getElementById('member-modal').classList.add('hidden');
    document.getElementById('member-form').reset();
}

// Submit form
document.getElementById('member-form').addEventListener('submit', (e) => {
    e.preventDefault();

    const memberId = document.getElementById('member-id').value;
    const formData = {
        first_name: document.getElementById('first-name').value,
        last_name: document.getElementById('last-name').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
    };

    const url = isEditMode ? `${teamMembersBaseUrl}/${memberId}` : teamMembersStoreUrl;
    const method = isEditMode ? 'PUT' : 'POST';

    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeMemberModal();
            showSuccessToast(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showErrorToast(data.message || 'An error occurred');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (error.errors) {
            // Validation errors
            const firstError = Object.values(error.errors)[0][0];
            showErrorToast(firstError);
        } else if (error.message) {
            showErrorToast(error.message);
        } else {
            showErrorToast('Failed to save team member. Please try again.');
        }
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Toggle member status
function toggleMemberStatus(memberId) {
    if (!confirm('Are you sure you want to change this member\'s status?')) {
        return;
    }

    fetch(`${teamMembersBaseUrl}/${memberId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showErrorToast(data.message || 'Failed to update status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast(error.message || 'Failed to update member status');
    });
}

// Delete member
function deleteMember(memberId) {
    if (!confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
        return;
    }

    fetch(`${teamMembersBaseUrl}/${memberId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw err;
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showErrorToast(data.message || 'Failed to delete member');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast(error.message || 'Failed to delete team member');
    });
}

// Close modal on outside click
document.getElementById('member-modal').addEventListener('click', (e) => {
    if (e.target.id === 'member-modal') {
        closeMemberModal();
    }
});

// Copy credentials to clipboard
function copyCredentials(email, password, loginUrl) {
    const credentials = `Email: ${email}\nPassword: ${password}\nLogin URL: ${loginUrl}`;

    navigator.clipboard.writeText(credentials).then(() => {
        showSuccessToast('Credentials copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showErrorToast('Failed to copy credentials');
    });
}
</script>
@endsection
