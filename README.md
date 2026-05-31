# Sencha Recruitment (PHP + PostgreSQL LAN)

Aplikasi rekrutmen Sencha — migrasi dari Firebase Firestore ke **PHP 8 + PostgreSQL**, pola deploy sama seperti **Keuangan** (PM2 + `START.bat`, offline/LAN).

## Persiapan

1. PostgreSQL terpasang, ekstensi PHP **pdo_pgsql** aktif.
2. Salin `.env.example` → `.env`, isi `DB_PASS` (dan `DB_HOST` jika DB di PC lain).
3. Buat database & skema:
   ```bat
   npm install
   npm run db:schema
   ```
4. Generate `public/app.html` dari **HRD SENCHA perfect.html** (Firebase):
   ```bat
   npm run migrate:html
   ```
   Sumber: `source/HRD-SENCHA-perfect.html` (salin dari Downloads jika perlu).
5. (Opsional) Impor backup Firestore JSON:
   ```bat
   php scripts/import-firebase-json.php "..\Sencha html\data\export-firestore.json"
   ```
   Atau gunakan menu **Import backup** di dashboard HRD setelah server jalan.

## Menjalankan

```bat
START.bat
```

Buka: **http://127.0.0.1:2022/public/app.html**

- Portal pelamar: default (tanpa login)
- Login pelamar: tombol **Login** (nama + WhatsApp)
- Login HRD: klik teks **SENCHA** di header → `admin` / `admin123*`

## Tes koneksi DB

```bat
php scripts\cek-koneksi.php
```

Harus menampilkan `TERHUBUNG OK`.

## Arsitektur

| Lapisan | File |
|---------|------|
| UI | `public/app.html` (dari `Sencha_Recruitment.html`) |
| Shim Firestore | `public/assets/js/sencha-local-db.js` |
| API | `api/index.php` → PostgreSQL `fs_documents` |
| Skema | `schema.pgsql.sql` |

Tidak ada dependency Firebase CDN di versi LAN ini.

## Port

Port standar **2022** (urutan LAN, lihat `Prompt & AI Information\PORT-REGISTRY-LAN.md`). Override di `.env` (`SENCHA_RECRUITMENT_PORT`) dan `ecosystem.config.cjs` jika bentrok.

## Rollback

Simpan salinan `Sencha html\Sencha_Recruitment.html` (masih Firebase). Folder ini independen; hapus folder `Sencha Recruitment` jika ingin kembali ke Firebase saja.
# shencccc
