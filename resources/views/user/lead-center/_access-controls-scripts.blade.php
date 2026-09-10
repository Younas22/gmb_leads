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
        $('<div>').css({ fontWeight: 600, fontSize: '12.5px', color: '#111827', lineHeight: '1.3' }).text(name).appendTo($wrapper);
        if (email) {
            $('<div>').css({ fontSize: '11px', color: '#6b7280', lineHeight: '1.3' }).text(email).appendTo($wrapper);
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
