<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

<!-- ===== Lead Center: Full-Screen Coverage Map ===== -->
<div id="mapModal" class="fixed inset-0 z-[100] hidden bg-white">
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 flex-shrink-0 shadow-sm relative z-10 flex-wrap gap-2">
            <div class="flex items-center gap-4 flex-wrap">
                <h3 class="text-base font-semibold text-gray-900">
                    <i class="fas fa-earth-americas text-primary-600 mr-1.5"></i> Lead Center Coverage Map
                </h3>
                <div class="flex items-center gap-3 text-xs text-gray-600">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Targeted</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-500 inline-block"></span> Pending Leads</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span> In Progress / Converted</span>
                </div>
            </div>
            <button onclick="closeMapModal()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="flex-1 relative">
            <div id="mapLoadingState" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm bg-white">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading map…
            </div>
            <div id="mapEmptyState" class="hidden absolute inset-0 flex items-center justify-center text-center px-4">
                <div>
                    <i class="fas fa-map-location-dot text-gray-300 text-4xl mb-3"></i>
                    <p class="text-sm font-medium text-gray-600">Nothing to show on the map yet.</p>
                    <p class="text-xs text-gray-400 mt-1">Add a targeted location, or leads with a Country set, to see pins here.</p>
                </div>
            </div>
            <div id="leafletMap" class="absolute inset-0"></div>

            <!-- Boundary drawing control -->
            <div id="boundaryControl" class="absolute top-3 left-3 z-[1000] bg-white rounded-xl shadow-lg border border-gray-200 p-3 w-64">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                    <i class="fas fa-draw-polygon mr-1"></i> Boundaries
                </p>
                <p class="text-[10px] text-gray-400 mb-2">Shows every Targeted Location at this level. Pick one below to add an extra outline.</p>
                <div class="flex gap-1 mb-2 bg-gray-100 p-1 rounded-lg">
                    <button type="button" id="boundaryLevelCountry" onclick="setBoundaryLevel('country')" class="flex-1 px-2 py-1 rounded-md text-[11px] font-medium transition-colors text-gray-500">Country</button>
                    <button type="button" id="boundaryLevelState" onclick="setBoundaryLevel('state')" class="flex-1 px-2 py-1 rounded-md text-[11px] font-medium transition-colors bg-white text-gray-800 shadow-sm">State</button>
                    <button type="button" id="boundaryLevelCity" onclick="setBoundaryLevel('city')" class="flex-1 px-2 py-1 rounded-md text-[11px] font-medium transition-colors text-gray-500">City</button>
                </div>
                <select id="boundary_country_select" class="w-full px-2 py-1.5 rounded-lg text-xs border border-gray-300 cursor-pointer mb-1.5">
                    <option value="">Country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
                <select id="boundary_state_select" class="w-full px-2 py-1.5 rounded-lg text-xs border border-gray-300 cursor-pointer mb-1.5" disabled>
                    <option value="">State</option>
                </select>
                <select id="boundary_city_select" class="w-full px-2 py-1.5 rounded-lg text-xs border border-gray-300 cursor-pointer" disabled>
                    <option value="">City</option>
                </select>
                <p id="boundaryStatusText" class="text-[10px] text-gray-400 mt-1.5 min-h-[14px]"></p>
                <button type="button" onclick="clearBoundary()" class="w-full mt-1 text-[11px] text-gray-400 hover:text-red-500 transition-colors text-left">
                    <i class="fas fa-eraser mr-1"></i>Clear outlines
                </button>
            </div>
        </div>
    </div>
</div>
