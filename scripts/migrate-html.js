/**
 * Transform HRD SENCHA perfect.html (Firebase) → public/app.html (LAN + sencha-local-db.js)
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');
const dest = path.join(root, 'public', 'app.html');

const SHIM = '/public/assets/js/sencha-local-db.js';
const FIRESTORE_CDN = 'https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js';
const LOCAL_CFG = "const firebaseConfig = { projectId: 'sencha-recruitment-lan' };";

const SRC_CANDIDATES = [
    path.join(root, 'source', 'HRD-SENCHA-perfect.html'),
    path.join(root, '..', 'Sencha html', 'HRD SENCHA - perfect.html'),
    'C:\\Users\\USER\\Downloads\\HRD SENCHA - perfect.html',
    path.join(root, '..', 'Sencha html', 'Sencha_Recruitment.html'),
];

const src = SRC_CANDIDATES.find((p) => fs.existsSync(p));
if (!src) {
    console.error('Sumber HTML tidak ditemukan. Salin perfect.html ke:', SRC_CANDIDATES[0]);
    process.exit(1);
}

console.log('Sumber:', src);

let html = fs.readFileSync(src, 'utf8');

/** Shell bootstrap: Firebase CDN → shim lokal */
html = html.replace(
    /import \{ initializeApp \} from 'https:\/\/www\.gstatic\.com\/firebasejs\/11\.6\.1\/firebase-app\.js';\s*\n\s*import \{ getAnalytics \}[^;]+;\s*\n\s*import \{ getAuth, signInAnonymously \}[^;]+;\s*\n\s*import \{ getFirestore \}[^;]+;\s*\n\s*const firebaseConfig = \{[\s\S]*?\};/,
    `import { initializeApp, getAuth, signInAnonymously, getFirestore } from '${SHIM}';\n\n        const LOCAL_PROJECT_ID = 'sencha-recruitment-lan';`
);

html = html.replace(
    /async function initFirebase\(\) \{[\s\S]*?readyResolve\(\{ db, auth \}\);\s*\}/,
    `async function initLocalDb() {
            initializeApp({});
            const auth = getAuth();
            await signInAnonymously();
            const db = getFirestore();
            window.__SENCHA__.db = db;
            window.__SENCHA__.auth = auth;
            readyResolve({ db, auth });
        }`
);

html = html.replace(/await initFirebase\(\)/g, 'await initLocalDb()');

/** Ganti inline HEIC besar → file asset (sudah ada di public/assets) */
html = html.replace(
    /<script>\s*\n\s*if \(typeof senchaPrepareImageFile !== 'function'\) \{[\s\S]*?\}\s*\n\s*<\/script>/,
    '<script src="assets/js/sencha-image-heic.js"></script>'
);

/** Import Firestore modul pelamar */
const fsImportPelamar = `        import {
            collection, doc, onSnapshot, addDoc, getDocs, getDoc, setDoc, updateDoc, deleteDoc,
            serverTimestamp, increment, arrayUnion, runTransaction, query, where, limit, orderBy,
            getCountFromServer, writeBatch
        } from '${SHIM}';`;

html = html.replace(
    /import\s*\{\s*getFirestore,\s*collection, doc, onSnapshot[\s\S]*?\}\s*from\s*'https:\/\/www\.gstatic\.com\/firebasejs\/11\.6\.1\/firebase-firestore\.js';/,
    fsImportPelamar
);

/** Import Firestore modul HRD */
const fsImportHrd = `        import {
            collection, doc, onSnapshot, addDoc, updateDoc, deleteDoc,
            serverTimestamp, getDoc, getDocs, setDoc, writeBatch, increment, arrayUnion, arrayRemove,
            runTransaction, deleteField, query, where, limit, orderBy, getCountFromServer, Timestamp,
            signInAnonymously, listLight
        } from '${SHIM}';`;

html = html.replace(
    /import\s*\{\s*getFirestore,\s*collection, doc, onSnapshot[\s\S]*?Timestamp\s*\}\s*from\s*'https:\/\/www\.gstatic\.com\/firebasejs\/11\.6\.1\/firebase-firestore\.js';/,
    fsImportHrd
);

html = html.replaceAll(FIRESTORE_CDN, SHIM);
html = html.replace(/import\s*\{\s*getFirestore,\s*/g, 'import { ');

/** Semua firebaseConfig → project LAN */
html = html.replace(
    /\/\*\* Project Firebase: lamaran-sencha \*\/\s*const firebaseConfig = \{[\s\S]*?\};/g,
    LOCAL_CFG
);
html = html.replace(
    /\/\*\* Sama persis dengan NEW HRD\.html[^*]*\*\/\s*const firebaseConfig = \{[\s\S]*?\};/g,
    LOCAL_CFG
);
html = html.replace(
    /const firebaseConfig = \{\s*apiKey:[\s\S]*?measurementId:[^\}]+\};/g,
    LOCAL_CFG
);

/** Performa: hanya loadApplicantsList pakai list ringan (export backup tetap full) */
html = html.replace(
    /const snap = await withTimeout\(\s*\n\s*getDocs\(collection\(db, COL_APPLICANTS\)\),\s*\n\s*FIRESTORE_OP_TIMEOUT_MS,\s*\n\s*'Muat daftar pelamar'/,
    `const snap = await withTimeout(\n                    getDocs(query(collection(db, COL_APPLICANTS), listLight())),\n                    FIRESTORE_OP_TIMEOUT_MS,\n                    'Muat daftar pelamar'`
);

/** Pesan error / boot lokal */
const replacements = [
    ["if (bt) bt.textContent = 'Gagal terhubung Firebase.';", "if (bt) bt.textContent = 'Gagal terhubung ke server lokal.';"],
    ["if (!auth || !db) throw new Error('Firebase belum siap. Muat ulang halaman.');", "if (!db) throw new Error('Database lokal belum siap. Muat ulang halaman.');"],
    ["await withTimeout(signInAnonymously(auth), FIRESTORE_OP_TIMEOUT_MS, 'Login Firebase');", "await withTimeout(signInAnonymously(), FIRESTORE_OP_TIMEOUT_MS, 'Sesi server');"],
    ["if (!db || !auth) throw new Error('Firebase belum siap');", "if (!db) throw new Error('Database lokal belum siap');"],
    ["$('boot-text').textContent = 'Gagal terhubung. Aktifkan Anonymous Auth di Firebase.';", "$('boot-text').textContent = 'Gagal terhubung ke server. Jalankan START.bat lalu buka http://127.0.0.1:2022/';"],
    ["throw new Error('Login Anonymous gagal — tidak ada request.auth. Aktifkan Anonymous Auth di project ini.');", "throw new Error('Sesi server belum aktif. Muat ulang halaman.');"],
    ["if (!db) return Swal.fire('Firebase belum siap', 'Isi firebaseConfig dan muat ulang.', 'error');", "if (!db) return Swal.fire('Database belum siap', 'Pastikan START.bat sudah jalan dan PostgreSQL terhubung.', 'error');"],
    ["if (!db) return Swal.fire('Firebase', 'Belum terhubung.', 'error');", "if (!db) return Swal.fire('Server', 'Belum terhubung. Jalankan START.bat.', 'error');"],
    ['Swal.fire(\'Restore selesai\', `Data dari backup ditulis ke <strong>${escapeHtml(dst)}</strong>. Buka NEW pelamar.html dengan firebaseConfig yang sama.`, \'success\');',
        'Swal.fire(\'Restore selesai\', `Data backup ditulis ke database lokal (<strong>${escapeHtml(dst)}</strong>). Muat ulang halaman jika perlu.`, \'success\');'],
    ['<code class="text-[10px]">lamaran-sencha</code>', '<code class="text-[10px]">sencha-recruitment-lan</code>'],
    ['<code class="text-[10px]">new-hrd-4b8c9</code>', '<code class="text-[10px]">sencha-recruitment-lan</code>'],
];

for (const [from, to] of replacements) {
    html = html.split(from).join(to);
}

if (html.includes('gstatic.com/firebasejs')) {
    console.warn('PERINGATAN: masih ada import Firebase CDN — periksa manual.');
}

if (!html.includes('sencha-local-db.js')) {
    console.error('Gagal mengganti shim — output tidak valid.');
    process.exit(1);
}

if (html.includes('getDocs(query(collection(db, COL_APPLICANTS), listLight()))') === false
    && html.includes('listLight()') === false) {
    console.warn('PERINGATAN: listLight() tidak ditemukan di output.');
}

fs.mkdirSync(path.dirname(dest), { recursive: true });
fs.writeFileSync(dest, html, 'utf8');
console.log('OK:', dest, '(' + Math.round(html.length / 1024) + ' KB)');
