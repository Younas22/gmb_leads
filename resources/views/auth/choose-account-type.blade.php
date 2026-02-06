<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Choose Account Type - Customer Nearme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-orange-50">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
            <i class="fas fa-spinner fa-spin text-primary-600 text-xl"></i>
            <span class="text-gray-700">Setting up your account...</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer" class="fixed top-4 right-4 z-40"></div>

    <div class="min-h-screen flex items-center justify-center p-8">
        <div class="w-full max-w-2xl">
            <!-- Logo -->
            <div class="flex items-center justify-center space-x-3 mb-8">
                <div class="bg-primary-600 rounded-lg p-3">
                    <i class="fas fa-search text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">BusinessFinder</h1>
            </div>

            <!-- Form Container -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 md:p-12">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mb-4">
                        <i class="fas fa-user-cog text-primary-600 text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ $user->first_name ?? $user->name }}!</h2>
                    <p class="text-gray-600 text-lg">Choose your account type to get started</p>
                </div>

                <!-- Account Type Selection Form -->
                <form id="accountTypeForm" class="space-y-6">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Individual Option -->
                        <label class="relative flex flex-col p-6 border-3 border-gray-300 rounded-2xl cursor-pointer hover:border-primary-500 hover:shadow-lg transition-all duration-200 account-type-card group">
                            <input type="radio" name="user_type" value="user" checked class="peer sr-only">

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-user text-primary-600 text-3xl"></i>
                                </div>

                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Individual</h3>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Perfect for freelancers, entrepreneurs, and solo professionals looking to grow their network.
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-gray-200 w-full">
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Personal dashboard
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Basic lead exports
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Standard support
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Selected Indicator -->
                            <div class="absolute top-4 right-4 w-6 h-6 bg-white border-2 border-gray-300 rounded-full peer-checked:bg-primary-600 peer-checked:border-primary-600 transition-all">
                                <i class="fas fa-check text-white text-xs absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100"></i>
                            </div>
                        </label>

                        <!-- Company Option -->
                        <label class="relative flex flex-col p-6 border-3 border-gray-300 rounded-2xl cursor-pointer hover:border-orange-500 hover:shadow-lg transition-all duration-200 account-type-card group">
                            <input type="radio" name="user_type" value="company" class="peer sr-only">

                            <div class="flex flex-col items-center text-center space-y-4">
                                <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-building text-orange-600 text-3xl"></i>
                                </div>

                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">Company</h3>
                                    <p class="text-gray-600 text-sm leading-relaxed">
                                        Ideal for businesses, teams, and organizations that need advanced features and collaboration.
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-gray-200 w-full">
                                    <ul class="space-y-2 text-sm text-gray-700">
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Team collaboration
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Bulk lead exports
                                        </li>
                                        <li class="flex items-center">
                                            <i class="fas fa-check text-green-500 mr-2"></i>
                                            Priority support
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Selected Indicator -->
                            <div class="absolute top-4 right-4 w-6 h-6 bg-white border-2 border-gray-300 rounded-full peer-checked:bg-orange-600 peer-checked:border-orange-600 transition-all">
                                <i class="fas fa-check text-white text-xs absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
                            </div>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit" id="submitBtn"
                                class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 text-lg">
                            <span id="submitBtnText">
                                <i class="fas fa-arrow-right mr-2"></i>Continue to Dashboard
                            </span>
                        </button>
                    </div>

                    <!-- Info Note -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <p class="text-sm text-blue-800">
                                <strong>Note:</strong> You can change your account type later from your profile settings.
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-gray-600 text-sm">
                <p>Need help? <a href="mailto:support@businessfinder.com" class="text-primary-600 hover:text-primary-700 font-medium">Contact Support</a></p>
            </div>
        </div>
    </div>

    <script>
        // Setup CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();

            const alertClass = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alertHtml = `
                <div id="${alertId}" class="${alertClass} text-white px-6 py-4 rounded-lg shadow-lg mb-4 animate-slide-in">
                    <div class="flex items-center">
                        <i class="fas ${iconClass} mr-3"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;

            alertContainer.insertAdjacentHTML('beforeend', alertHtml);

            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) alert.remove();
            }, 5000);
        }

        function showLoading(show = true) {
            document.getElementById('loadingOverlay').classList.toggle('hidden', !show);
        }

        function setButtonLoading(loading = true) {
            const button = document.getElementById('submitBtn');
            const buttonText = document.getElementById('submitBtnText');

            if (loading) {
                button.disabled = true;
                button.classList.add('opacity-75', 'cursor-not-allowed');
                buttonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Setting up your account...';
            } else {
                button.disabled = false;
                button.classList.remove('opacity-75', 'cursor-not-allowed');
                buttonText.innerHTML = '<i class="fas fa-arrow-right mr-2"></i>Continue to Dashboard';
            }
        }

        // Account type selection styling
        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.account-type-card').forEach(card => {
                    card.classList.remove('border-primary-600', 'border-orange-600', 'bg-primary-50', 'bg-orange-50');
                    card.classList.add('border-gray-300');
                });

                const selectedCard = this.closest('.account-type-card');
                selectedCard.classList.remove('border-gray-300');

                if (this.value === 'user') {
                    selectedCard.classList.add('border-primary-600', 'bg-primary-50');
                } else {
                    selectedCard.classList.add('border-orange-600', 'bg-orange-50');
                }
            });
        });

        // Auto-select first option on load
        document.querySelector('input[name="user_type"]:checked').dispatchEvent(new Event('change'));

        // Form submission
        $('#accountTypeForm').on('submit', function(e) {
            e.preventDefault();

            setButtonLoading(true);

            $.ajax({
                url: '{{ route("auth.save.account.type") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        showLoading(true);

                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        showAlert(response.message || 'Something went wrong', 'error');
                        setButtonLoading(false);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response?.message || 'Failed to save account type. Please try again.', 'error');
                    setButtonLoading(false);
                }
            });
        });
    </script>

    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }

        /* Account type card hover effects */
        .account-type-card {
            transition: all 0.3s ease;
        }

        .account-type-card:has(input:checked) {
            transform: translateY(-4px);
        }

        .peer:checked ~ div i {
            opacity: 1;
        }
    </style>
</body>
</html>
