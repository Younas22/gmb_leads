<!-- ===== Lead Center: Edit Location Modal ===== -->
<div id="locationModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeLocationModal()"></div>

    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="bg-primary-100 p-2 rounded-lg">
                        <i class="fas fa-map-marker-alt text-primary-600 text-sm"></i>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">Edit Location</h3>
                </div>
                <button onclick="closeLocationModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="px-6 py-4 space-y-2">
                <p class="text-xs text-gray-400 mb-1">
                    Some leads (e.g. saved via the browser extension) don't have a structured location. Set it here.
                </p>
                <select id="loc_country_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer">
                    <option value="">Country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
                <select id="loc_state_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                    <option value="">State</option>
                </select>
                <select id="loc_city_select" class="w-full px-2 py-2 rounded-lg text-sm border border-gray-300 cursor-pointer" disabled>
                    <option value="">City</option>
                </select>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeLocationModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                    Cancel
                </button>
                <button type="button" id="saveLocationBtn" onclick="saveLocation()"
                        class="px-5 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 transition-colors">
                    Save Location
                </button>
            </div>
        </div>
    </div>
</div>
