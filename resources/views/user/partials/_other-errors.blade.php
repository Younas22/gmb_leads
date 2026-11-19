{{-- Validation errors for fields the page does not show next to the input ($inline = keys shown inline). --}}
@php
    $otherErrors = collect($errors->getMessages())->except($inline ?? [])->flatten()->unique();
@endphp
@if($otherErrors->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700" role="alert" data-testid="form-errors">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($otherErrors as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    </div>
@endif
