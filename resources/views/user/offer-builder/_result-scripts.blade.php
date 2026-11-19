<script>
    // Copy helpers for offer results (whole offer as text, or one section)
    async function obCopyString(text, btn) {
        try {
            await navigator.clipboard.writeText(text);
            if (btn) { const old = btn.innerHTML; btn.innerHTML = '<i class="fas fa-check"></i> Copied'; setTimeout(() => btn.innerHTML = old, 1500); }
        } catch { alert('Could not copy automatically. Please select the text and copy it.'); }
    }
    function obCopyText(id, btn) { obCopyString(document.getElementById(id).value, btn); }
    function obCopyEl(btn) {
        const src = btn.closest('article').querySelector('.ob-copy-src');
        obCopyString(src.innerText.trim(), btn);
    }
</script>
