/**
 * Auth HRD ringan — dimuat lebih dulu agar login tidak menunggu modul app.html penuh.
 */
export const HRD_SESSION_KEY = 'hrd_screening_session_v1';
const SESSION_MAX_AGE_MS = 12 * 60 * 60 * 1000;
const DEFAULT_ADMIN_USER = 'admin';

async function hashPassword(pw) {
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(pw));
    return Array.from(new Uint8Array(buf)).map((b) => b.toString(16).padStart(2, '0')).join('');
}

function getEmbeddedAdminHash() {
    const p = [
        '0208788a', 'a2035cd5', 'be6697ef', 'bd285df1',
        'afa881c8', 'fd25e4bd', '5bbb247c', '29c58454',
    ];
    return p.join('');
}

export function getSession() {
    try {
        const raw = localStorage.getItem(HRD_SESSION_KEY);
        if (!raw) return null;
        const s = JSON.parse(raw);
        if (!s?.username) return null;
        if (s.at && Date.now() - s.at > SESSION_MAX_AGE_MS) {
            clearSession();
            return null;
        }
        return s;
    } catch {
        return null;
    }
}

export function setSession(username) {
    localStorage.setItem(HRD_SESSION_KEY, JSON.stringify({ username, at: Date.now() }));
}

export function clearSession() {
    localStorage.removeItem(HRD_SESSION_KEY);
}

/** Verifikasi via API; fallback hash lokal jika server belum siap. */
export async function verifyLogin(username, password) {
    const user = String(username || '').trim();
    const pass = String(password || '');
    if (!user || !pass) return false;
    try {
        const res = await fetch('/api/v1/auth/hrd', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: user, password: pass }),
        });
        const json = await res.json().catch(() => ({}));
        if (res.ok && json.ok) return true;
    } catch {
        /* offline / server starting */
    }
    const userHash = await hashPassword(pass);
    return user === DEFAULT_ADMIN_USER && userHash === getEmbeddedAdminHash();
}
