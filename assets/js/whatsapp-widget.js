/**
 * WhatsApp Paste Widget
 * Drop-in for any form that needs WhatsApp message parsing.
 *
 * Usage:
 *   WhatsAppWidget.init({
 *     pasteTarget : '#description',          // textarea to watch for paste
 *     fields      : {
 *       work_done      : '#work_done',
 *       materials_used : '#materials_used',
 *       issues_faced   : '#issues_faced',
 *     },
 *     onParsed    : function(data) {},       // optional callback
 *   });
 */
const WhatsAppWidget = (() => {
    const API = '/ergon/api/parse-whatsapp';

    function init(opts = {}) {
        const pasteEl = document.querySelector(opts.pasteTarget);
        if (!pasteEl) return;

        // Inject the banner once
        _injectBanner(pasteEl);

        pasteEl.addEventListener('paste', (e) => {
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!text || text.trim().length < 20) return;
            _process(text, opts);
        });
    }

    function _process(text, opts) {
        const banner = document.getElementById('wa-parse-banner');
        if (banner) { banner.textContent = '⏳ Detecting WhatsApp content…'; banner.className = 'wa-banner wa-banner--loading'; banner.style.display = 'block'; }

        fetch(API, {
            method  : 'POST',
            headers : { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body    : JSON.stringify({ text }),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.is_whatsapp) {
                if (banner) { banner.style.display = 'none'; }
                return; // plain text — leave as-is
            }

            // Populate mapped fields
            const fields = opts.fields || {};
            _fill(fields.work_done,      data.work_done);
            _fill(fields.materials_used, data.materials_used);
            _fill(fields.issues_faced,   data.issues_faced);

            // Replace paste target with cleaned text if no specific mapping
            if (!fields.work_done) {
                const el = document.querySelector(opts.pasteTarget);
                if (el) el.value = data.raw_cleaned;
            }

            if (banner) {
                if (data.success) {
                    banner.textContent = '✅ WhatsApp message parsed — fields populated automatically.';
                    banner.className = 'wa-banner wa-banner--success';
                } else {
                    banner.textContent = '⚠️ Parsed with warnings: ' + (data.errors || []).join(', ');
                    banner.className = 'wa-banner wa-banner--warning';
                }
                setTimeout(() => { banner.style.display = 'none'; }, 5000);
            }

            if (typeof opts.onParsed === 'function') opts.onParsed(data);
        })
        .catch(() => {
            if (banner) { banner.style.display = 'none'; }
        });
    }

    function _fill(selector, value) {
        if (!selector || !value) return;
        const el = document.querySelector(selector);
        if (el) el.value = value;
    }

    function _injectBanner(anchorEl) {
        if (document.getElementById('wa-parse-banner')) return;
        const div = document.createElement('div');
        div.id        = 'wa-parse-banner';
        div.className = 'wa-banner';
        div.style.cssText = 'display:none;padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:8px;font-weight:500;';
        anchorEl.parentNode.insertBefore(div, anchorEl);

        // Inline minimal styles
        const style = document.createElement('style');
        style.textContent = `
            .wa-banner--loading { background:#fef9c3; color:#854d0e; border:1px solid #fde047; }
            .wa-banner--success { background:#f0fdf4; color:#166534; border:1px solid #86efac; }
            .wa-banner--warning { background:#fff7ed; color:#9a3412; border:1px solid #fdba74; }
        `;
        document.head.appendChild(style);
    }

    return { init };
})();
