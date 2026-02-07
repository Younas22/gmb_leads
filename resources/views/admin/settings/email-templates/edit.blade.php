@extends('layouts.admin')

@section('title', 'Edit Email Template - ' . $template->name)

@section('content')
<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <a href="{{ route('admin.settings.index') }}?tab=email" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                </div>
                <p class="text-sm text-gray-500 ml-8">Edit the subject and body content of this email template</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.settings.email-templates.preview', $template->id) }}" target="_blank"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                    <i class="fas fa-eye mr-1.5"></i>Preview
                </a>
                <form action="{{ route('admin.settings.email-templates.reset', $template->id) }}" method="POST"
                      onsubmit="return confirm('Are you sure? This will reset the template to its original default content.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium">
                        <i class="fas fa-undo mr-1.5"></i>Reset to Default
                    </button>
                </form>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2.5 rounded-lg flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2.5 rounded-lg flex items-center justify-between text-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <form action="{{ route('admin.settings.email-templates.update', $template->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Subject -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-heading text-blue-600 mr-2 text-sm"></i>
                                Email Subject
                            </h2>
                        </div>
                        <div class="p-5">
                            <input type="text" name="subject" id="subject"
                                   value="{{ old('subject', $template->subject) }}"
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                   required>
                            @error('subject')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-gray-500">You can use placeholders like <code class="bg-gray-100 px-1 py-0.5 rounded">{user_name}</code> in the subject</p>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-5 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                            <h2 class="text-base font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-code text-purple-600 mr-2 text-sm"></i>
                                Email Body (HTML)
                            </h2>
                        </div>
                        <div class="p-5">
                            <textarea name="body" id="body" rows="25"
                                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono"
                                      required>{{ old('body', $template->body) }}</textarea>
                            @error('body')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.settings.index') }}?tab=email"
                           class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">
                            <i class="fas fa-save mr-2"></i>Save Template
                        </button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5">
                    <!-- Available Placeholders -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-tags text-green-600 mr-2 text-xs"></i>
                                Available Placeholders
                            </h2>
                        </div>
                        <div class="p-4">
                            <p class="text-[11px] text-gray-500 mb-3">Click to copy. These will be replaced with actual values when the email is sent.</p>
                            <div class="space-y-1.5">
                                @if($template->available_variables)
                                    @foreach($template->available_variables as $var)
                                        <button type="button" onclick="copyPlaceholder('{!! $var !!}')"
                                                class="w-full text-left px-2.5 py-1.5 bg-gray-50 hover:bg-green-50 border border-gray-200 hover:border-green-300 rounded text-xs font-mono text-gray-700 hover:text-green-700 transition-colors">
                                            <i class="fas fa-copy text-gray-400 mr-1.5 text-[10px]"></i>{<span>{{ $var }}</span>}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                            <div id="copy-feedback" class="mt-2 hidden">
                                <div class="bg-green-50 border border-green-200 text-green-700 px-2 py-1 rounded text-xs text-center">
                                    Copied!
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available CSS Classes -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-amber-50">
                            <h2 class="text-sm font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-palette text-orange-600 mr-2 text-xs"></i>
                                Available CSS Classes
                            </h2>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <div class="p-2.5 bg-gray-50 rounded border border-gray-200">
                                <code class="text-xs font-mono text-purple-700">class="button"</code>
                                <p class="text-[10px] text-gray-500 mt-0.5">Styled gradient button (use with &lt;a&gt; tag)</p>
                            </div>
                            <div class="p-2.5 bg-gray-50 rounded border border-gray-200">
                                <code class="text-xs font-mono text-purple-700">class="info-box"</code>
                                <p class="text-[10px] text-gray-500 mt-0.5">Highlighted info box with left border</p>
                            </div>
                            <div class="p-2.5 bg-gray-50 rounded border border-gray-200">
                                <code class="text-xs font-mono text-purple-700">class="divider"</code>
                                <p class="text-[10px] text-gray-500 mt-0.5">Horizontal line separator</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h6 class="font-semibold text-blue-900 mb-2 flex items-center text-xs">
                            <i class="fas fa-info-circle mr-1.5"></i>Quick Tips
                        </h6>
                        <ul class="space-y-1.5 text-[11px] text-blue-800">
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                <span>Header and footer are automatically added</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                <span>Use standard HTML tags (h2, p, ul, li, a, etc.)</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                <span>Preview your changes before saving</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mr-1.5 mt-0.5 text-[10px]"></i>
                                <span>Reset to default if something goes wrong</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function copyPlaceholder(variable) {
        const text = '{' + variable + '}';
        navigator.clipboard.writeText(text).then(() => {
            const feedback = document.getElementById('copy-feedback');
            feedback.classList.remove('hidden');
            setTimeout(() => feedback.classList.add('hidden'), 1500);
        });
    }

    // Auto-switch to email tab when returning to settings
    @if(request()->has('tab'))
    // Tab parameter handling is done by settings page JS
    @endif
</script>
@endsection
