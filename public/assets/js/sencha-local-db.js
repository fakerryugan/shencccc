/**
 * Shim Firestore → API PHP + PostgreSQL (Sencha Recruitment LAN)
 */
const API = '/api/v1';
const POLL_MS = 6000;

let _sessionId = sessionStorage.getItem('sencha_session_id') || '';
let _db = null;
let _currentUser = null;

async function apiFetch(path, opts = {}) {
    const headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
    if (_sessionId) headers['X-Session-Id'] = _sessionId;
    const res = await fetch(API + path, { ...opts, headers });
    const json = await res.json().catch(() => ({}));
    if (!res.ok && !json.ok) {
        const err = new Error(json.error || res.statusText || 'Permintaan gagal');
        err.code = res.status;
        throw err;
    }
    return json;
}

export function initializeApp() {
    return {};
}

export function getAuth() {
    return {
        get currentUser() {
            return _currentUser;
        },
    };
}

export async function signInAnonymously(_authIgnored) {
    const r = await apiFetch('/anon', { method: 'POST' });
    if (r.sessionId) {
        _sessionId = r.sessionId;
        sessionStorage.setItem('sencha_session_id', _sessionId);
    }
    _currentUser = { uid: _sessionId || 'local' };
    return _currentUser;
}

export function getFirestore() {
    if (!_db) _db = {};
    return _db;
}

export class Timestamp {
    constructor(seconds, nanoseconds = 0) {
        if (seconds && typeof seconds === 'object' && 'seconds' in seconds) {
            this.seconds = seconds.seconds;
            this.nanoseconds = seconds.nanoseconds || 0;
        } else if (seconds instanceof Date) {
            this.seconds = Math.floor(seconds.getTime() / 1000);
            this.nanoseconds = 0;
        } else if (typeof seconds === 'string') {
            const d = new Date(seconds);
            this.seconds = Math.floor(d.getTime() / 1000);
            this.nanoseconds = 0;
        } else {
            this.seconds = Number(seconds) || 0;
            this.nanoseconds = Number(nanoseconds) || 0;
        }
    }
    toDate() {
        return new Date(this.seconds * 1000);
    }
    toMillis() {
        return this.seconds * 1000 + Math.floor(this.nanoseconds / 1000000);
    }
    static now() {
        return Timestamp.fromDate(new Date());
    }
    static fromDate(d) {
        return new Timestamp(Math.floor(d.getTime() / 1000), 0);
    }
}

function pathJoin(...parts) {
    return parts.filter(Boolean).join('/');
}

function encodePath(ref) {
    if (ref._path) return ref._path;
    if (ref.path) return ref.path;
    return '';
}

export function collection(db, ...segments) {
    const path = pathJoin(...segments);
    return { _type: 'collection', _path: path };
}

export function doc(dbOrRef, ...segments) {
    if (dbOrRef && dbOrRef._type === 'doc') {
        return { _type: 'doc', _path: pathJoin(dbOrRef._path, ...segments) };
    }
    if (dbOrRef && dbOrRef._type === 'collection') {
        return { _type: 'doc', _path: pathJoin(dbOrRef._path, ...segments) };
    }
    const path = pathJoin(...segments);
    return { _type: 'doc', _path: path };
}

function wrapDoc(json) {
    const exists = json.exists !== false && json.ok !== false;
    const raw = json.data || {};
    const id = json.id || (json.path ? json.path.split('/').pop() : '');
    return {
        exists: () => exists,
        id,
        data: () => deserializeData({ ...raw }),
        ref: { _type: 'doc', _path: json.path || '' },
    };
}

function wrapQueryDocs(json) {
    const docs = (json.docs || []).map((d) => ({
        id: d.id,
        data: () => deserializeData({ ...(d.data || {}) }),
        ref: { _type: 'doc', _path: d.path },
    }));
    return {
        docs,
        empty: docs.length === 0,
        hasMore: json.hasMore === true,
        forEach(fn) {
            docs.forEach(fn);
        },
    };
}

export async function getDoc(ref) {
    const p = encodePath(ref);
    try {
        const json = await apiFetch('/doc/' + p);
        return wrapDoc(json);
    } catch (e) {
        if (e.code === 404) {
            return { exists: () => false, id: p.split('/').pop(), data: () => undefined, ref };
        }
        throw e;
    }
}

export async function getDocs(q) {
    const path = q._collectionPath || encodePath(q);
    let url = '/collection/' + path;
    const qs = [];
    if (q._wheres?.length) qs.push('where=' + encodeURIComponent(JSON.stringify(q._wheres)));
    if (q._limit) qs.push('limit=' + q._limit);
    if (q._offset) qs.push('offset=' + q._offset);
    if (q._order) {
        qs.push('order=' + encodeURIComponent(q._order));
        if (q._orderDir) qs.push('order_dir=' + encodeURIComponent(q._orderDir));
    }
    if (q._light) qs.push('light=1');
    if (qs.length) url += '?' + qs.join('&');
    const json = await apiFetch(url);
    return wrapQueryDocs(json);
}

export async function setDoc(ref, data, opts = {}) {
    const p = encodePath(ref);
    await apiFetch('/doc/' + p, {
        method: 'PUT',
        body: JSON.stringify({ data: serializeData(data), merge: !!opts.merge }),
    });
}

export async function updateDoc(ref, patch) {
    const p = encodePath(ref);
    await apiFetch('/doc/' + p, {
        method: 'PUT',
        body: JSON.stringify({ patch: serializePatch(patch) }),
    });
}

export async function deleteDoc(ref) {
    const p = encodePath(ref);
    await apiFetch('/doc/' + p, { method: 'DELETE' });
}

export async function addDoc(colRef, data) {
    const p = encodePath(colRef);
    const json = await apiFetch('/collection/' + p, {
        method: 'POST',
        body: JSON.stringify({ data: serializeData(data) }),
    });
    return { _type: 'doc', _path: json.path, id: json.id };
}

export function serverTimestamp() {
    return { __ts: 'server' };
}

export function increment(n) {
    return { __op: 'increment', amount: Number(n) || 0 };
}

export function arrayUnion(...elements) {
    return { __op: 'arrayUnion', elements };
}

export function arrayRemove(...elements) {
    return { __op: 'arrayRemove', elements };
}

export function deleteField() {
    return { __op: 'delete' };
}

function serializeData(obj) {
    if (!obj || typeof obj !== 'object') return obj;
    const out = Array.isArray(obj) ? [] : {};
    for (const [k, v] of Object.entries(obj)) {
        out[k] = serializeValue(v);
    }
    return out;
}

function serializePatch(obj) {
    return serializeData(obj);
}

function serializeValue(v) {
    if (v instanceof Timestamp) {
        return { _seconds: v.seconds, _nanoseconds: v.nanoseconds };
    }
    if (v && typeof v === 'object' && v.__ts === 'server') return { __ts: 'server' };
    if (v && typeof v === 'object' && v.__op) return v;
    if (v && typeof v === 'object' && v._type === 'doc') return v;
    if (Array.isArray(v)) return v.map(serializeValue);
    if (v && typeof v === 'object') {
        const o = {};
        for (const [k, val] of Object.entries(v)) o[k] = serializeValue(val);
        return o;
    }
    return v;
}

export function deserializeData(data) {
    if (!data || typeof data !== 'object') return data;
    if (Array.isArray(data)) return data.map(deserializeData);
    const out = {};
    for (const [k, v] of Object.entries(data)) {
        if (v && typeof v === 'object' && v.__ts === 'server') {
            out[k] = Timestamp.now();
        } else if (v && typeof v === 'object' && '_seconds' in v) {
            out[k] = new Timestamp(v._seconds, v._nanoseconds || 0);
        } else if (v && typeof v === 'object' && 'seconds' in v) {
            out[k] = new Timestamp(v.seconds, v.nanoseconds || 0);
        } else if (v && typeof v === 'object' && '__firestoreTimestamp' in v) {
            out[k] = new Timestamp(v.__firestoreTimestamp);
        } else if (v && typeof v === 'object' && !Array.isArray(v)) {
            out[k] = deserializeData(v);
        } else {
            out[k] = v;
        }
    }
    return out;
}

/** Parse Timestamp / ISO / legacy Firestore ke Date (null jika tidak valid). */
export function tsToDate(ts) {
    if (ts == null || ts === '') return null;
    if (ts instanceof Timestamp) return ts.toDate();
    if (typeof ts === 'object') {
        if (typeof ts.toDate === 'function') {
            const d = ts.toDate();
            return Number.isNaN(d.getTime()) ? null : d;
        }
        if (ts.__ts === 'server') return new Date();
        if (ts.__firestoreTimestamp) return new Date(ts.__firestoreTimestamp);
        const sec = ts._seconds ?? ts.seconds;
        if (typeof sec === 'number') return new Date(sec * 1000);
    }
    if (typeof ts === 'number') return new Date(ts < 1e12 ? ts * 1000 : ts);
    const d = new Date(ts);
    return Number.isNaN(d.getTime()) ? null : d;
}

export function query(colRef, ...constraints) {
    const q = {
        _collectionPath: encodePath(colRef),
        _wheres: [],
        _limit: null,
        _offset: 0,
        _order: null,
        _orderDir: 'DESC',
        _light: false,
    };
    for (const c of constraints) {
        if (c?.type === 'where') q._wheres.push(c);
        if (c?.type === 'limit') q._limit = c.n;
        if (c?.type === 'offset') q._offset = c.n;
        if (c?.type === 'order') {
            q._order = c.by;
            if (c.dir) q._orderDir = c.dir;
        }
        if (c?.type === 'light') q._light = true;
    }
    return q;
}

export function where(field, op, value) {
    return { type: 'where', field, op, value };
}

export function limit(n) {
    return { type: 'limit', n };
}

export function offset(n) {
    return { type: 'offset', n: Math.max(0, Number(n) || 0) };
}

export function orderBy(field, direction) {
    const by = field === 'at' ? 'data_at' : 'updated_at';
    const dir = String(direction || 'desc').toLowerCase() === 'asc' ? 'ASC' : 'DESC';
    return { type: 'order', by, dir };
}

export async function getCountFromServer(colRef) {
    const path = encodePath(colRef);
    const json = await apiFetch('/collection/' + path + '?count=1');
    const count = Number(json.count) || 0;
    return {
        data() {
            return { count };
        },
    };
}

export function listLight() {
    return { type: 'light' };
}

/** Alias kompatibilitas (beberapa deploy/cache lama) */
export const listlight = listLight;

const listeners = new Map();
let pollTimer = null;

function listenerKey(refOrQuery) {
    if (refOrQuery._collectionPath) return 'q:' + refOrQuery._collectionPath + JSON.stringify(refOrQuery._wheres || []);
    return 'd:' + encodePath(refOrQuery);
}

async function emitSnapshot(key) {
    const entry = listeners.get(key);
    if (!entry) return;
    const { ref, onNext, onError, isDoc } = entry;
    try {
        if (isDoc) {
            const snap = await getDoc(ref);
            onNext(snap);
        } else {
            const snap = await getDocs(ref);
            onNext(snap);
        }
    } catch (e) {
        if (onError) onError(e);
    }
}

async function pollListener(key) {
    const entry = listeners.get(key);
    if (!entry) return;
    const { ref, isDoc, lastServerTime } = entry;
    const path = isDoc ? encodePath(ref) : ref._collectionPath || encodePath(ref);
    if (!path) return;

    // Hindari polling real-time untuk applicants agar menghemat bandwidth & CPU
    if (path === 'applicants' || path.startsWith('applicants/')) {
        return;
    }

    try {
        const res = await apiFetch('/changes?since=' + encodeURIComponent(lastServerTime) + '&prefix=' + encodeURIComponent(path));
        entry.lastServerTime = res.serverTime || new Date().toISOString();
        if (res.changes && res.changes.length > 0) {
            await emitSnapshot(key);
        }
    } catch (e) {
        console.error('polling failed', path, e);
    }
}

function ensurePoll() {
    if (pollTimer) return;
    pollTimer = setInterval(() => {
        for (const key of listeners.keys()) {
            pollListener(key);
        }
    }, POLL_MS);
}

export function onSnapshot(refOrQuery, onNext, onError) {
    const isDoc = refOrQuery._type === 'doc';
    const key = listenerKey(refOrQuery);
    listeners.set(key, { 
        ref: refOrQuery, 
        onNext, 
        onError, 
        isDoc, 
        lastServerTime: new Date().toISOString() 
    });
    emitSnapshot(key);
    ensurePoll();
    return () => listeners.delete(key);
}

export function writeBatch() {
    const writes = [];
    const batch = {
        set(ref, data, opts) {
            writes.push({ op: 'set', path: encodePath(ref), data: serializeData(data), merge: !!opts?.merge });
        },
        update(ref, patch) {
            writes.push({ op: 'update', path: encodePath(ref), data: serializePatch(patch) });
        },
        delete(ref) {
            writes.push({ op: 'delete', path: encodePath(ref) });
        },
        async commit() {
            await apiFetch('/batch', { method: 'POST', body: JSON.stringify({ writes }) });
        },
    };
    return batch;
}

export async function runTransaction(db, fn) {
    const tx = {
        async get(ref) {
            return getDoc(ref);
        },
        set(ref, data, opts) {
            return setDoc(ref, data, opts);
        },
        update(ref, patch) {
            return updateDoc(ref, patch);
        },
        delete(ref) {
            return deleteDoc(ref);
        },
    };
    return fn(tx);
}

/** Login HRD / pelamar via API (opsional, dipakai modul jika perlu) */
export async function authHrd(username, password) {
    const r = await apiFetch('/auth/hrd', { method: 'POST', body: JSON.stringify({ username, password }) });
    if (r.sessionId) {
        _sessionId = r.sessionId;
        sessionStorage.setItem('sencha_session_id', _sessionId);
    }
    return r;
}

export async function authPelamar(nama, whatsapp) {
    const r = await apiFetch('/auth/pelamar', {
        method: 'POST',
        body: JSON.stringify({ nama, whatsapp }),
    });
    if (r.sessionId) {
        _sessionId = r.sessionId;
        sessionStorage.setItem('sencha_session_id', _sessionId);
    }
    return r;
}
