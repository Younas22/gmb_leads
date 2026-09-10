<style>
    /* Name/email must switch to white on Select2's blue "highlighted" row — a plain inline
       color on these child divs would otherwise win over the row's own color, leaving
       dark text on a blue background (low contrast). */
    .lc-account-name { font-weight: 600; font-size: 12.5px; color: #111827; line-height: 1.3; }
    .lc-account-email { font-size: 11px; color: #6b7280; line-height: 1.3; }
    .select2-results__option--highlighted .lc-account-name,
    .select2-results__option--highlighted .lc-account-email {
        color: #ffffff !important;
    }
</style>
<script>
// Switch-account dropdown: each option shows Name on top, Email underneath — needs Select2's
// templateResult since a plain native <option> can only ever render a single line of text.
$(function() {
    const $sel = $('#lcSwitchAccountSelect');
    if (!$sel.length) return;

    function renderOption(state) {
        if (!state.id) return state.text; // "My Own Account"

        const $opt = $(state.element);
        const name = $opt.data('name') || state.text;
        const email = $opt.data('email') || '';

        const $wrapper = $('<div>');
        $('<div class="lc-account-name">').text(name).appendTo($wrapper);
        if (email) {
            $('<div class="lc-account-email">').text(email).appendTo($wrapper);
        }
        return $wrapper;
    }

    function renderSelection(state) {
        if (!state.id) return state.text;
        const $opt = $(state.element);
        return $opt.data('name') || state.text;
    }

    $sel.select2({
        width: '150px',
        minimumResultsForSearch: 5,
        templateResult: renderOption,
        templateSelection: renderSelection,
    });
});
</script>
