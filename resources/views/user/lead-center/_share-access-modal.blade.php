<!-- ===== Lead Center: Share Access Modal ===== -->
<div id="shareAccessModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeShareAccessModal()"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[85vh] flex flex-col">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="bg-primary-100 p-2 rounded-lg">
                        <i class="fas fa-user-plus text-primary-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Share Access to My Lead Center</h3>
                </div>
                <button onclick="closeShareAccessModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto">
                <p class="text-xs text-gray-500 mb-3">
                    Enter the email of another account already registered here. They'll see a request to accept or decline —
                    once accepted, they get full view + edit control over your Lead Center leads, folders and conversations.
                    They will <strong>not</strong> see anything else about your account.
                </p>
                <div class="flex gap-2 mb-4">
                    <input type="email" id="shareAccessEmail" placeholder="teammate@example.com"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-300 focus:border-primary-400"
                           onkeydown="if(event.key==='Enter') sendShareAccessRequest()">
                    <button onclick="sendShareAccessRequest()"
                            class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex-shrink-0">
                        Send Request
                    </button>
                </div>

                <div class="flex items-center gap-3 my-3">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400 font-medium">People you've shared with</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <div id="mySharedGrantsList" class="space-y-2 max-h-52 overflow-y-auto pr-1">
                    @forelse($mySharedGrants ?? [] as $grant)
                        @include('user.lead-center._shared-grant-item', ['grant' => $grant])
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-4" id="noSharedGrants">You haven't shared access with anyone yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
