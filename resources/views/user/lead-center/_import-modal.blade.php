<!-- ===== Lead Center: Import Leads Modal ===== -->
<div id="importModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeImportModal()"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="bg-primary-100 p-2 rounded-lg">
                        <i class="fas fa-file-import text-primary-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Import Leads into Lead Center</h3>
                </div>
                <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto">

                <!-- Step 1: Location -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i> 1. Choose a location for this batch
                    </p>
                    <p class="text-xs text-gray-400 mb-2">Every lead you import will be tagged with this Country / State / City.</p>
                    <div class="grid grid-cols-3 gap-2">
                        <select id="import_country_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer">
                            <option value="">Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                        <select id="import_state_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                            <option value="">State</option>
                        </select>
                        <select id="import_city_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                            <option value="">City</option>
                        </select>
                    </div>
                </div>

                <!-- Step 2: Source -->
                <div class="mt-5" id="importSourceSection">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        <i class="fas fa-upload mr-1"></i> 2. Add your leads
                    </p>

                    <div class="flex gap-1 mb-3 bg-gray-100 p-1 rounded-lg w-fit">
                        <button type="button" id="tabBtnCsv" onclick="switchImportTab('csv')"
                                class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors bg-white text-gray-800 shadow-sm">
                            <i class="fas fa-file-csv mr-1"></i> Upload CSV
                        </button>
                        <button type="button" id="tabBtnPaste" onclick="switchImportTab('paste')"
                                class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors text-gray-500">
                            <i class="fas fa-paste mr-1"></i> Paste Leads
                        </button>
                    </div>

                    <!-- CSV Tab -->
                    <div id="importTabCsv">
                        <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl px-4 py-6 cursor-pointer hover:border-primary-400 hover:bg-primary-50/30 transition-colors">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                            <span class="text-sm text-gray-600" id="csvFileLabel">Click to choose a CSV file, or drag it here</span>
                            <input type="file" id="csvFileInput" accept=".csv,text/csv" class="hidden" onchange="onCsvFileChosen(this)">
                        </label>
                        <p class="text-[11px] text-gray-400 mt-2">
                            Expected columns: <code class="bg-gray-100 px-1 rounded">Company Name,Website</code> — a header row is optional. Max 10MB.
                        </p>
                    </div>

                    <!-- Paste Tab -->
                    <div id="importTabPaste" class="hidden">
                        <textarea id="pasteTextarea" rows="6" placeholder="ABC Company,https://abc.com&#10;XYZ Business,https://xyz.com&#10;Demo Company,https://demo.com"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-primary-400"></textarea>
                        <p class="text-[11px] text-gray-400 mt-2">One lead per line: <code class="bg-gray-100 px-1 rounded">Company Name,Website</code></p>
                    </div>

                    <button type="button" id="previewBtn" onclick="runImportPreview()"
                            class="mt-3 w-full bg-gray-800 hover:bg-gray-900 text-white py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-magnifying-glass"></i> Preview Import
                    </button>
                    <p id="importSourceError" class="text-xs text-red-600 mt-2 hidden"></p>
                </div>

                <!-- Step 3: Preview -->
                <div class="mt-5 hidden" id="importPreviewSection">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        <i class="fas fa-list-check mr-1"></i> 3. Review before importing
                    </p>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-2.5 text-center">
                            <p class="text-lg font-bold text-green-700" id="previewValidCount">0</p>
                            <p class="text-[11px] text-green-700">valid leads</p>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2.5 text-center">
                            <p class="text-lg font-bold text-yellow-700" id="previewDuplicateCount">0</p>
                            <p class="text-[11px] text-yellow-700">duplicates skipped</p>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-2.5 text-center">
                            <p class="text-lg font-bold text-red-700" id="previewInvalidCount">0</p>
                            <p class="text-[11px] text-red-700">invalid rows</p>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="max-h-56 overflow-y-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold text-gray-600">Company Name</th>
                                        <th class="text-left px-3 py-2 font-semibold text-gray-600">Website</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1" id="previewMoreNote"></p>

                    <div id="previewIssuesWrap" class="mt-2 hidden">
                        <button type="button" onclick="document.getElementById('previewIssuesList').classList.toggle('hidden')"
                                class="text-xs text-gray-500 hover:text-gray-700 underline">
                            View skipped rows
                        </button>
                        <ul id="previewIssuesList" class="hidden mt-1.5 space-y-1 max-h-32 overflow-y-auto text-[11px] text-gray-500 list-disc list-inside"></ul>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0 flex items-center justify-end gap-2">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                    Cancel
                </button>
                <button type="button" id="confirmImportBtn" onclick="confirmImport()" disabled
                        class="hidden px-5 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Import <span id="confirmImportCount">0</span> Leads
                </button>
            </div>
        </div>
    </div>
</div>
