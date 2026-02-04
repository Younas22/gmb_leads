<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - Customer Nearme</title>
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
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Reset Password</h1>
                <p class="text-gray-600">Enter your new password below</p>
            </div>

            <!-- Reset Form -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <form id="resetPasswordForm">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" value="{{ $email }}" readonly
                                   class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-600 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="newPassword" required 
                                   class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                   placeholder="Enter new password">
                            <button type="button" onclick="togglePassword('newPassword', 'newPasswordIcon')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="newPasswordIcon"></i>
                            </button>
                        </div>
                        <span class="text-red-500 text-sm hidden" id="passwordError"></span>
                        <p class="mt-2 text-xs text-gray-500">Must be at least 8 characters long</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password_confirmation" id="confirmPassword" required 
                                   class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                   placeholder="Confirm new password">
                            <button type="button" onclick="togglePassword('confirmPassword', 'confirmPasswordIcon')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                        <span class="text-red-500 text-sm hidden" id="passwordConfirmationError"></span>
                    </div>

                    <!-- Password Strength Indicator -->
                    <div class="mb-6">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-xs text-gray-600">Password strength:</span>
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="passwordStrength" class="h-full bg-gray-300 transition-all duration-300" style="width: 0%"></div>
                            </div>
                        </div>
                        <p id="passwordStrengthText" class="text-xs text-gray-500">Enter a password</p>
                    </div>

                    <button type="submit" id="resetButton"
                            class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span id="resetButtonText">
                            <i class="fas fa-check-circle mr-2"></i>Reset Password
                        </span>
                    </button>

                    <div class="mt-6 text-center">
                        <a href="{{ route('auth.show') }}" class="text-sm text-gray-600 hover:text-gray-900 inline-flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            </div>

            <!-- Security Tips -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">Password Security Tips</h3>
                        <ul class="text-xs text-blue-800 space-y-1">
                            <li>✓ Use a unique password you don't use elsewhere</li>
                            <li>✓ Include uppercase, lowercase, numbers & symbols</li>
                            <li>✓ Avoid personal information like names or dates</li>
                            <li>✓ Consider using a password manager</li>
                        </ul>
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

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');

            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;

            strengthBar.style.width = strength + '%';

            if (strength < 30) {
                strengthBar.className = 'h-full bg-red-500 transition-all duration-300';
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs text-red-600';
            } else if (strength < 60) {
                strengthBar.className = 'h-full bg-yellow-500 transition-all duration-300';
                strengthText.textContent = 'Fair password';
                strengthText.className = 'text-xs text-yellow-600';
            } else if (strength < 80) {
                strengthBar.className = 'h-full bg-blue-500 transition-all duration-300';
                strengthText.textContent = 'Good password';
                strengthText.className = 'text-xs text-blue-600';
            } else {
                strengthBar.className = 'h-full bg-green-500 transition-all duration-300';
                strengthText.textContent = 'Strong password';
                strengthText.className = 'text-xs text-green-600';
            }
        }

        $('#newPassword').on('input', function() {
            checkPasswordStrength($(this).val());
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

        $('#resetPasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const password = $('#newPassword').val();
            const confirmPassword = $('#confirmPassword').val();

            if (password !== confirmPassword) {
                showAlert('Passwords do not match!', 'error');
                return;
            }

            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long!', 'error');
                return;
            }

            clearErrors();
            const button = $('#resetButton');
            const buttonText = $('#resetButtonText');
            
            button.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
            buttonText.html('<i class="fas fa-spinner fa-spin mr-2"></i>Resetting...');

            $.ajax({
                url: '{{ route("auth.reset.password.submit") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        setTimeout(() => {
                            window.location.href = '{{ route("auth.show") }}';
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showAlert(response.message || 'Failed to reset password', 'error');
                },
                complete: function() {
                    button.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                    buttonText.html('<i class="fas fa-check-circle mr-2"></i>Reset Password');
                }
            });
        });

        // Show Laravel flash messages
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