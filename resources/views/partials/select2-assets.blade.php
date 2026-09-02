<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Custom Select2 Styling — matches the app's input height/border/focus-ring everywhere Select2 is used -->
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        height: 38px !important;
        line-height: 36px !important;
        background: rgba(255,255,255,0.8) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 8px !important;
        padding-right: 20px !important;
        color: #374151 !important;
        font-size: 0.875rem !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 6px !important;
        top: 1px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
        background: white !important;
    }
    .select2-dropdown {
        border: 1px solid #d1d5db !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        margin-top: 4px !important;
    }
    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.875rem !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted[aria-selected]:hover {
        background-color: #3b82f6 !important;
        color: white !important;
    }
    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #dbeafe !important;
        color: #1e40af !important;
    }
    .select2-container--default .select2-search--dropdown {
        padding: 8px !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 6px 12px !important;
        font-size: 0.875rem !important;
        outline: none !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    .select2-container {
        width: 100% !important;
        font-family: inherit !important;
    }
    .select2-selection__placeholder {
        color: #9ca3af !important;
    }
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #f9fafb !important;
        cursor: not-allowed !important;
    }
    .select2-container--default .select2-selection__clear {
        color: #6b7280 !important;
        font-size: 1.2em !important;
        margin-right: 10px !important;
    }
    .select2-container--default .select2-selection__clear:hover {
        color: #ef4444 !important;
    }
</style>
