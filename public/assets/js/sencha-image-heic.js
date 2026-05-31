/**
 * Konversi HEIC/HEIF (iPhone) → JPEG sebelum canvas compress.
 * LAN: muat heic2any dari assets/js/; fallback CDN jika file lokal tidak ada.
 */
(function (global) {
    'use strict';

    let loadPromise = null;
    const scriptBase = (function () {
        const cur = document.currentScript;
        if (!cur || !cur.src) return null;
        try {
            return new URL('.', cur.src).href;
        } catch (_) {
            return null;
        }
    })();

    function isHeicOrHeifFile(file) {
        if (!file) return false;
        const t = String(file.type || '').toLowerCase();
        const n = String(file.name || '').toLowerCase();
        return t.includes('heic') || t.includes('heif') || /\.(heic|heif)$/.test(n);
    }

    function heic2anyScriptCandidates() {
        const list = [];
        if (scriptBase) {
            try {
                list.push(new URL('heic2any.min.js', scriptBase).href);
            } catch (_) { /* ignore */ }
        }
        try {
            list.push(new URL('assets/js/heic2any.min.js', global.location.href).href);
        } catch (_) { /* ignore */ }
        list.push('https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js');
        return [...new Set(list)];
    }

    function loadScriptOnce(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-sencha-heic2any][src="' + src + '"]');
            if (existing && typeof global.heic2any !== 'undefined') return resolve();
            const s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.dataset.senchaHeic2any = '1';
            s.onload = () => (typeof global.heic2any !== 'undefined' ? resolve() : reject(new Error('empty')));
            s.onerror = () => reject(new Error('load'));
            document.head.appendChild(s);
        });
    }

    function loadHeic2anyLib() {
        if (typeof global.heic2any !== 'undefined') return Promise.resolve();
        if (loadPromise) return loadPromise;
        const urls = heic2anyScriptCandidates();
        loadPromise = (async () => {
            for (const url of urls) {
                try {
                    await loadScriptOnce(url);
                    if (typeof global.heic2any !== 'undefined') return;
                } catch (_) { /* coba URL berikutnya */ }
            }
            throw new Error('HEIC_LIB');
        })();
        return loadPromise;
    }

    async function normalizeImageFileForBrowser(file) {
        if (!file || !isHeicOrHeifFile(file)) return file;
        await loadHeic2anyLib();
        let converted;
        try {
            converted = await global.heic2any({ blob: file, toType: 'image/jpeg', quality: 0.92 });
        } catch (_) {
            throw new Error('HEIC_CONVERT');
        }
        const blob = Array.isArray(converted) ? converted[0] : converted;
        if (!blob) throw new Error('HEIC_CONVERT');
        const baseName = String(file.name || 'foto').replace(/\.(heic|heif)$/i, '') || 'foto';
        try {
            return new File([blob], baseName + '.jpg', { type: 'image/jpeg', lastModified: file.lastModified || Date.now() });
        } catch (_) {
            blob.name = baseName + '.jpg';
            return blob;
        }
    }

    global.senchaNormalizeImageFile = normalizeImageFileForBrowser;
    global.senchaIsHeicOrHeifFile = isHeicOrHeifFile;
})(typeof window !== 'undefined' ? window : globalThis);
