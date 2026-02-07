<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Under Maintenance - {{ $siteName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-4">
        <div class="bg-white rounded-2xl shadow-lg p-10 text-center">
            <div class="mb-6">
                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
                    <i class="fas fa-wrench text-yellow-600 text-3xl"></i>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-3">We'll Be Right Back</h1>

            <p class="text-gray-600 mb-6 leading-relaxed">
                We're currently performing scheduled maintenance to improve your experience.
                Please check back shortly.
            </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center gap-2 text-yellow-800 text-sm">
                    <i class="fas fa-clock"></i>
                    <span>This won't take long. Thank you for your patience!</span>
                </div>
            </div>

            <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
