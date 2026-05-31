-- Sencha Recruitment — PostgreSQL (migrasi dari Firebase Firestore)
-- Jalankan: npm run db:schema  atau  psql -U postgres -d sencha_recruitment -f schema.pgsql.sql

CREATE TABLE IF NOT EXISTS fs_documents (
    path TEXT PRIMARY KEY,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_fs_documents_parent
    ON fs_documents ((regexp_replace(path, '/[^/]+$', '')));

CREATE INDEX IF NOT EXISTS idx_fs_documents_updated
    ON fs_documents (updated_at DESC);

CREATE TABLE IF NOT EXISTS hrd_users (
    username TEXT PRIMARY KEY,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- admin / admin123* (SHA-256, sama konvensi Firebase)
INSERT INTO hrd_users (username, password_hash)
VALUES (
    'admin',
    '0208788aa2035cd5be6697efbd285df1afa881c8fd25e4bd5bbb247c29c58454'
)
ON CONFLICT (username) DO NOTHING;

CREATE TABLE IF NOT EXISTS app_sessions (
    session_id TEXT PRIMARY KEY,
    role TEXT NOT NULL CHECK (role IN ('hrd', 'pelamar', 'anon')),
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_app_sessions_expires ON app_sessions (expires_at);
