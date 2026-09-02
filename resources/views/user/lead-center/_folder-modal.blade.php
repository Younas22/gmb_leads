<!-- ===== Lead Center: Move to Folder / Create Folder Modal ===== -->
<div id="folderModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeFolderModal()"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-100 p-2 rounded-lg">
                        <i class="fas fa-folder-open text-indigo-600 text-sm"></i>
                    </div>
                    <div>
                        <h3 id="folderModalTitle" class="text-base font-semibold text-gray-900">Move to Folder</h3>
                        <p id="folderModalSub" class="text-xs text-gray-400"></p>
                    </div>
                </div>
                <button onclick="closeFolderModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-4">
                <div id="folderListSection">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Your Folders</p>
                    <div id="folderList" class="space-y-2 max-h-52 overflow-y-auto pr-1">
                        <p class="text-sm text-gray-400 italic">Loading folders…</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 my-4">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-xs text-gray-400 font-medium">or create new</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">New Folder</p>
                    <div class="flex gap-2 mb-3">
                        <input id="newFolderName" type="text" placeholder="Folder name…"
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400"
                               maxlength="100" onkeydown="if(event.key==='Enter') createFolder()">
                        <div class="flex gap-1.5 items-center">
                            @foreach(['blue','green','purple','red','orange','pink','teal'] as $clr)
                            @php
                                $dotColors = [
                                    'blue'=>'bg-blue-500','green'=>'bg-green-500','purple'=>'bg-purple-500',
                                    'red'=>'bg-red-500','orange'=>'bg-orange-500','pink'=>'bg-pink-500','teal'=>'bg-teal-500'
                                ];
                            @endphp
                            <button type="button" data-color="{{ $clr }}"
                                    onclick="selectColor(this)"
                                    class="color-dot w-5 h-5 rounded-full {{ $dotColors[$clr] }} ring-2 ring-transparent hover:ring-gray-300 transition-all {{ $clr==='blue' ? 'ring-gray-500' : '' }}"
                                    title="{{ ucfirst($clr) }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <button onclick="createFolder()"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i> Create Folder
                        <span id="createFolderAndMove" class="hidden"> &amp; Move Here</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
