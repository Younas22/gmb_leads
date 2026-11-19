{{--
    XSS helpers for JavaScript-built HTML. Use these for every value that comes
    from scraped / user-supplied data (lead names, addresses, reviews, notes,
    websites, social links, ...) before putting it into innerHTML or an attribute.
--}}
<script>
    /** Escape a value for use inside HTML text or a quoted attribute. */
    window.cnmEscape = function (value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/`/g, '&#96;');
    };

    /**
     * Return a normalized http(s) URL, or null for anything else
     * (javascript:, data:, vbscript:, malformed values...).
     * Bare domains such as "example.com" are treated as https.
     */
    window.cnmSafeUrl = function (value) {
        if (value === null || value === undefined) return null;
        let raw = String(value).trim();
        if (!raw) return null;
        if (!/^[a-z][a-z0-9+.\-]*:/i.test(raw)) {
            if (raw.startsWith('//')) raw = 'https:' + raw;
            else raw = 'https://' + raw;
        }
        try {
            const url = new URL(raw);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return null;
            if (!url.hostname) return null;
            return url.href;
        } catch (e) {
            return null;
        }
    };

    /** Deep copy of an object/array with every string HTML-escaped. */
    window.cnmEscapeDeep = function (value) {
        if (typeof value === 'string') return window.cnmEscape(value);
        if (Array.isArray(value)) return value.map(window.cnmEscapeDeep);
        if (value && typeof value === 'object') {
            const out = {};
            for (const key of Object.keys(value)) out[key] = window.cnmEscapeDeep(value[key]);
            return out;
        }
        return value;
    };
</script>
