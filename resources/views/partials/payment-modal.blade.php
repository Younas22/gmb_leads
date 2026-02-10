{{-- Payment Modal Partial --}}
{{-- Include in any page with @include('partials.payment-modal') --}}

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" style="backdrop-filter:blur(2px);">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-100 flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900">Complete Payment</h3>
                <button onclick="closePaymentModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-5 overflow-y-auto flex-1">
                <!-- Package Info -->
                <div class="bg-gradient-to-r from-blue-50 to-orange-50 rounded-xl p-4 mb-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Package</p>
                        <p class="text-base font-bold text-gray-900" id="modalPackageName">-</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Amount</p>
                        <p class="text-xl font-bold text-primary-blue" id="modalPackagePrice">-</p>
                    </div>
                </div>

                <!-- Step 1: Select Payment Method -->
                <div id="step1">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Select a Payment Method</p>

                    <!-- All Payment Methods in One Row -->
                    <div class="flex flex-wrap justify-center gap-1.5 mb-3">
                        @foreach($paymentMethods as $method)
                            @if($method->slug !== 'card')
                            <button type="button"
                                onclick="selectPaymentMethod({{ $method->id }}, '{{ e($method->name) }}', '{{ e($method->slug) }}')"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 border border-gray-200 rounded-full hover:border-primary-blue hover:bg-blue-50 transition-all">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold
                                    {{ $method->slug === 'jazzcash' ? 'bg-green-500 text-white' : '' }}
                                    {{ $method->slug === 'easypaisa' ? 'bg-teal-500 text-white' : '' }}
                                    {{ $method->slug === 'bank' ? 'bg-blue-500 text-white' : '' }}
                                    {{ $method->slug === 'nayapay' ? 'bg-purple-500 text-white' : '' }}
                                    {{ !in_array($method->slug, ['jazzcash','easypaisa','bank','nayapay']) ? 'bg-gray-500 text-white' : '' }}
                                ">{{ strtoupper(substr($method->name, 0, 2)) }}</span>
                                <span class="text-xs font-medium text-gray-700">{{ $method->name }}</span>
                            </button>
                            @endif
                        @endforeach
                    </div>

                    <!-- Divider -->
                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="bg-white px-3 text-gray-400">or pay with card</span>
                        </div>
                    </div>

                    <!-- Card Payment Button -->
                    <button type="button" disabled
                        class="w-full flex items-center justify-center gap-3 py-4 border-2 border-gray-200 rounded-xl bg-gray-50 opacity-60 cursor-not-allowed p-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                            </svg>
                        </div>
                        <div class="text-left">
                            <span class="block text-sm font-semibold text-gray-600">Credit / Debit Card</span>
                            <span class="text-xs text-gray-400">Coming Soon</span>
                        </div>
                        <span class="ml-auto text-xs bg-gray-300 text-gray-600 px-2 py-1 rounded-full">Coming Soon</span>
                    </button>
                </div>

                <!-- Step 2: Instructions + Screenshot Upload -->
                <div id="step2" class="hidden">
                    <div class="flex items-center mb-4">
                        <button onclick="backToStep1()" class="flex items-center text-primary-blue text-sm font-medium hover:text-dark-blue">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            Back
                        </button>
                        <span class="ml-3 text-sm font-semibold text-gray-800" id="selectedMethodLabel">-</span>
                    </div>

                    <!-- Instructions box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <p class="text-sm font-semibold text-blue-800 mb-1">Payment Instructions</p>
                        <p class="text-sm text-blue-700 whitespace-pre-line" id="paymentInstructions">-</p>
                    </div>

                    <!-- Upload Form -->
                    <form id="paymentForm" method="POST" action="{{ route('user.payment.submit') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="package_id" id="formPackageId">
                        <input type="hidden" name="payment_method_id" id="formPaymentMethodId">

                        <label class="block">
                            <span class="text-xs text-gray-600 font-medium">Upload Payment Screenshot *</span>
                            <div id="uploadArea" class="mt-1.5 border-2 border-dashed border-gray-300 rounded-lg p-3 text-center hover:border-primary-blue transition-colors cursor-pointer">
                                <svg class="w-6 h-6 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <p class="text-xs text-gray-500">Click to upload image</p>
                                <p class="text-[10px] text-gray-400">JPG, PNG, GIF — max 5MB</p>
                            </div>
                            <input type="file" name="screenshot" id="screenshotInput" accept="image/*" required class="hidden">
                        </label>

                        <!-- Preview -->
                        <div id="screenshotPreview" class="hidden mt-2 text-center">
                            <div class="relative inline-block">
                                <img id="screenshotPreviewImg" src="" alt="Preview" class="max-h-24 w-auto mx-auto rounded-lg border border-gray-200">
                                <button type="button" onclick="removeScreenshot()" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit"
    class="w-full mt-3 bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-800 transition-colors">
    Submit Payment
</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Payment Modal Script --}}
<script>
(function() {
    // ── Payment Modal ──
    var currentPackagePrice = 0;
    var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    var currencySymbol = '{{ $currency["symbol"] ?? "$" }}';
    var currencyCode = '{{ $currency["code"] ?? "USD" }}';

    var paymentDetailsMap = {
        'jazzcash':  'Send ' + currencyCode + ' [AMOUNT] to JazzCash account:\nNumber: 03047222723\nName: Muhammad Yousaf',
        'easypaisa': 'Send ' + currencyCode + ' [AMOUNT] to Easypaisa account:\nNumber: 03174340853\nName: Muhammad Younas',
        'bank':      'Transfer ' + currencyCode + ' [AMOUNT] to:\nBank: Meezan Bank\nAccount Title: MUHAMMAD YOUNAS\nAccount No: 26980113699163\nIBAN: PK38MEZN0026980113699163',
        'nayapay':   'Send ' + currencyCode + ' [AMOUNT] to NayaPay:\nNumber: 03174340853\nName: Muhammad Younas'
    };

    // For home page - handles both auth check and modal open
    window.handleGetStarted = function(btn) {
        if (!isAuthenticated) {
            window.location.href = '{{ route("auth.show") }}';
            return;
        }
        openPaymentModal(btn.dataset.packageId, btn.dataset.packageName, btn.dataset.packagePrice);
    };

    // Open modal function (for both pages)
    window.openPaymentModal = function(packageId, packageName, packagePrice) {
        currentPackagePrice = packagePrice;
        document.getElementById('modalPackageName').textContent  = packageName;
        document.getElementById('modalPackagePrice').textContent = currencySymbol + ' ' + Number(currentPackagePrice).toLocaleString();
        document.getElementById('formPackageId').value           = packageId;

        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('hidden');

        // Reset file input when opening modal
        resetScreenshotUpload();
    };

    window.closePaymentModal = function() {
        document.getElementById('paymentModal').classList.add('hidden');
    };

    window.selectPaymentMethod = function(methodId, methodName, methodSlug) {
        document.getElementById('formPaymentMethodId').value     = methodId;
        document.getElementById('selectedMethodLabel').textContent = methodName;

        var tpl = paymentDetailsMap[methodSlug] || 'Please complete your payment and upload a screenshot.';
        document.getElementById('paymentInstructions').textContent = tpl.replace(/\[AMOUNT\]/g, Number(currentPackagePrice).toLocaleString());

        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.remove('hidden');

        // Reset file & preview
        resetScreenshotUpload();
    };

    window.backToStep1 = function() {
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('step2').classList.add('hidden');
    };

    function resetScreenshotUpload() {
        var input = document.getElementById('screenshotInput');
        var preview = document.getElementById('screenshotPreview');
        var uploadArea = document.getElementById('uploadArea');

        if (input) input.value = '';
        if (preview) preview.classList.add('hidden');
        if (uploadArea) uploadArea.classList.remove('hidden');
    }

    window.removeScreenshot = function() {
        resetScreenshotUpload();
    };

    // Initialize after DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        var uploadArea = document.getElementById('uploadArea');
        var screenshotInput = document.getElementById('screenshotInput');
        var paymentModal = document.getElementById('paymentModal');

        // Handle upload area click - use event listener instead of inline onclick
        if (uploadArea && screenshotInput) {
            uploadArea.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                screenshotInput.click();
            });

            // Handle file selection - FIX: prevent double file dialog
            screenshotInput.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent bubbling to uploadArea
            });

            // Handle file change
            screenshotInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('screenshotPreviewImg').src = e.target.result;
                        document.getElementById('screenshotPreview').classList.remove('hidden');
                        document.getElementById('uploadArea').classList.add('hidden');
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Close modal on backdrop click
        if (paymentModal) {
            paymentModal.addEventListener('click', function(e) {
                if (e.target === this) closePaymentModal();
            });
        }
    });
    // ── End Payment Modal ──
})();
</script>
