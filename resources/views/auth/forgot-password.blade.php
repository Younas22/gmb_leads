<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - Customer Nearme</title>
        @php
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/png" href="{{ asset('public/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('public/assets/images/favicon.png') }}">
    @endif
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
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-blue-50">
    <!-- Alert Messages -->
    <div id="alertContainer" class="fixed top-4 right-4 z-40"></div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Forgot Password?</h1>
                <p class="text-gray-600">No worries, we'll send you reset instructions</p>
            </div>

            <!-- Reset Request Form -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <form id="forgotPasswordForm">
                    @csrf
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" id="email" required 
                                   class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                   placeholder="Enter your email address">
                        </div>
                        <span class="text-red-500 text-sm hidden" id="emailError"></span>
                        <p class="mt-2 text-xs text-gray-500">We'll send you a password reset link</p>
                    </div>

                    <button type="submit" id="submitButton"
                            class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span id="submitButtonText">
                            <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
                        </span>
                    </button>

                    <div class="mt-6 text-center">
                        <a href="{{ route('auth.show') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Login
                        </a>
                    </div>
                </form>

                <!-- Success Message (Hidden by default) -->
                <div id="successMessage" class="hidden mt-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-green-900 mb-1">Email Sent!</h3>
                            <p class="text-xs text-green-800">
                                If an account exists with that email, you'll receive password reset instructions shortly.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-question-circle text-primary-600 mr-2"></i>
                    Need Help?
                </h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start space-x-2">
                        <i class="fas fa-check text-primary-600 mt-0.5"></i>
                        <p>Check your spam/junk folder if you don't see the email</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fas fa-check text-primary-600 mt-0.5"></i>
                        <p>The reset link will expire in 24 hours</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fas fa-check text-primary-600 mt-0.5"></i>
                        <p>Contact support at <a href="mailto:info@customernearme.com" class="text-primary-600 hover:underline">info@customernearme.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
                        <button onclick="removeAlert('${alertId}')" class="ml-4 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            alertContainer.insertAdjacentHTML('beforeend', alertHtml);
            
            setTimeout(() => {
                removeAlert(alertId);
            }, 5000);
        }

        function removeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) alert.remove();
        }

        function clearErrors() {
            document.querySelectorAll('[id$="Error"]').forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });

            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            });
        }

        $('#forgotPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            clearErrors();
            const button = $('#submitButton');
            const buttonText = $('#submitButtonText');
            const successMessage = $('#successMessage');
            
            button.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
            buttonText.html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending...');

            $.ajax({
                url: '{{ route("auth.send.reset.link") }}',
                method: 'POST',
                data: {
                    email: $('#email').val()
                },
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        successMessage.removeClass('hidden');
                        $('#forgotPasswordForm')[0].reset();
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response.message || 'Failed to send reset link', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    buttonText.html('<i class="fas fa-paper-plane mr-2"></i>Send Reset Link');
                }
            });
        });

        // Clear errors on input
        $('#email').on('input', function() {
            clearErrors();
        });

        // Show Laravel flash messages
        @if(session('success'))
            showAlert('{{ session("success") }}', 'success');
        @endif

        @if(session('error'))
            showAlert('{{ session("error") }}', 'error');
        @endif
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
    </style>
</body>
</html>