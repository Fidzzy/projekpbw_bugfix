# Catatan Migrasi Struktur Folder

Nama file asli **tidak diubah sama sekali**. Yang berubah hanya lokasi
foldernya, plus path relatif (include, href, src, action) menyesuaikan
kedalaman folder yang baru.

```
projekpbw/
├── config/
│   └── dbconn.php            (nama tetap sama, hanya dipindah)
├── modules/
│   ├── publikasi/
│   │   ├── page09A.php        - Daftar Publikasi
│   │   ├── page09C.php        - Form Tambah Publikasi
│   │   ├── page09C_action.php - Proses Tambah
│   │   ├── page09E.php        - Form Edit Publikasi
│   │   ├── page09E_action.php - Proses Update
│   │   └── page09F.php        - Proses Hapus
│   └── galeri/
│       └── page09G.php        - Galeri Kegiatan
├── assets/
│   ├── css/myCSS2.css
│   ├── js/validasiForm.js
│   └── img/                   (isi folder asset/ lama, nama file gambar tetap sama)
├── latihan/                   (php09A.php - php09D.php, latihan terpisah,
│                                tidak berhubungan dengan web BPS tiruan)
├── database/
│   └── hafidz_db.sql
└── README.md
```

## Apa yang diubah (hanya path, bukan nama file)

1. **Path include/require ke `dbconn.php`** disesuaikan jadi
   `../../config/dbconn.php` (nama file tetap `dbconn.php`).
2. **Path CSS/JS/gambar** disesuaikan ke `../../assets/...` karena file
   halaman sekarang 2 level lebih dalam dari root.
3. **Link antar-halaman dalam satu folder** (misal `page09A.php` ke
   `page09C.php`, sama-sama di `modules/publikasi/`) **tidak perlu diubah**
   karena masih satu folder — cukup nama file seperti aslinya.
4. **Link lintas-folder** (dari `modules/publikasi/` ke `page09G.php` di
   `modules/galeri/`, dan sebaliknya) diarahkan pakai `../galeri/page09G.php`
   dan `../publikasi/page09A.php` / `page09C.php`.
5. **Perbaikan keamanan kecil**: nama file dari `$_GET`/`$_FILES` di
   `page09F.php` dan `page09E_action.php` sekarang difilter `basename()`
   sebelum dipakai untuk `unlink()`/upload, supaya tidak bisa disalahgunakan
   untuk path traversal (`?sampul=../../config/dbconn.php`).
   `page09C_action.php` sudah pakai `basename()` sejak awal, tidak diubah.
6. **`php09A.php` s/d `php09D.php`** dipindah ke folder `latihan/` apa
   adanya (tanpa ubah nama maupun isi) karena itu latihan terpisah yang
   tidak terhubung ke database publikasi.

## Yang perlu kamu lakukan setelah pull perubahan ini

- Sesuaikan kredensial di `config/dbconn.php` kalau berbeda dari lokal kamu.
- Jalankan dari root project (misal `http://localhost/projekpbw/`), lalu
  akses lewat `modules/publikasi/page09A.php`.
- Folder `latihan/` masih pakai path lama (relatif ke root) karena memang
  berdiri sendiri, tidak terhubung ke modul publikasi/galeri.

## Fitur Login & Role (Admin vs User)

Fitur baru yang ditambahkan:

```
config/
├── dbconn.php
includes/
└── auth.php              (baru) helper session, isLoggedIn(), isAdmin(),
                            requireLogin(), requireAdmin()
modules/
├── auth/                  (baru)
│   ├── login.php
│   ├── login_action.php
│   ├── register.php
│   ├── register_action.php
│   └── logout.php
├── publikasi/              (sudah dipasangi guard login/admin)
└── galeri/                 (sudah dipasangi guard login)
database/
└── users.sql               (baru) tabel users + 2 akun contoh
```

### Cara pakai

1. Import `database/hafidz_db.sql` dulu (kalau belum), lalu import
   `database/users.sql` ke database `projekpbw` yang sama.
2. Buka `modules/auth/login.php`. Dua akun contoh siap dipakai:
   - `admin` / `admin123` → role **admin**, bisa CRUD penuh.
   - `user` / `user123` → role **user**, cuma bisa lihat data.
3. User baru yang daftar lewat `register.php` otomatis dapat role `user`.
   Untuk menaikkan jadi admin, ubah manual kolom `role` di tabel `users`
   lewat phpMyAdmin (tidak ada UI kelola user khusus admin — bisa
   ditambahkan kalau perlu).

### Aturan akses

| Halaman | Tanpa login | Role user | Role admin |
|---|---|---|---|
| `page09A.php` (Daftar Publikasi) | Redirect ke login | Lihat saja (tombol Edit/Hapus disembunyikan) | Lihat + tombol Edit/Hapus |
| `page09C.php` (Tambah Publikasi) | Redirect ke login | 403 Akses ditolak | Bisa akses |
| `page09E.php` (Edit Publikasi) | Redirect ke login | 403 Akses ditolak | Bisa akses |
| `page09F.php` (Hapus Publikasi) | Redirect ke login | 403 Akses ditolak | Bisa akses |
| `page09G.php` (Galeri) | Redirect ke login | Bisa akses | Bisa akses |

Proteksi dipasang di **dua lapis**: halaman form (`page09C.php`/`page09E.php`)
maupun proses backend-nya (`page09C_action.php`/`page09E_action.php`/`page09F.php`).
Jadi walau seseorang coba akses langsung file `_action.php` tanpa lewat form,
tetap ditolak kalau bukan admin.

### Detail teknis

- Password disimpan pakai `password_hash()` (bcrypt), diverifikasi dengan
  `password_verify()` — bukan plaintext maupun MD5/SHA1.
- Session di-regenerate ID saat login berhasil (`session_regenerate_id()`)
  untuk mencegah session fixation.
- Logout benar-benar menghapus session (`session_destroy()` + hapus cookie),
  bukan cuma redirect kosong seperti sebelumnya.
- `basename()` sudah dipakai di semua tempat yang menerima nama file dari
  input user (`$_GET['sampul']`, `$_FILES[...]['name']`) untuk cegah path
  traversal.

## API JSON

Layer API baru ditambahkan di folder `api/`, terpisah dari halaman web biasa
tapi pakai session & aturan role yang sama (admin vs user):

```
api/
├── API.md                  ← dokumentasi lengkap + contoh curl/fetch
├── helpers/
│   └── response.php         json_response(), get_request_method(),
│                             get_json_or_post_body(), apiRequireLogin(),
│                             apiRequireAdmin()
├── auth/
│   ├── login.php    (POST)
│   ├── logout.php   (POST)
│   └── me.php       (GET)   cek status login saat ini
└── publikasi/
    ├── index.php     GET (list/search) & POST (tambah, khusus admin)
    └── detail.php    GET (detail), PUT (update, khusus admin),
                       DELETE (hapus, khusus admin)
```

Baca `api/API.md` untuk dokumentasi lengkap tiap endpoint beserta contoh
`curl` dan `fetch()`. Ringkasannya:

- **GET** endpoint publikasi bisa diakses semua role yang sudah login.
- **POST/PUT/DELETE** cuma bisa diakses role **admin** — kalau bukan admin,
  balas `403` JSON, bukan redirect seperti halaman web biasa.
- Upload file sampul didukung lewat `multipart/form-data`. Untuk update
  (PUT) yang sekalian ganti file, dipakai method override
  (`_method=PUT` di form POST) karena PUT native tidak mudah bawa file.
- Endpoint API ini **tidak menggantikan** halaman web yang sudah ada
  (`page09A.php` dkk. masih jalan seperti biasa, langsung query database).
  API ini lapisan tambahan untuk konsumsi dari AJAX/frontend terpisah/mobile
  app kalau suatu saat dibutuhkan.

Struktur ini disiapkan supaya nanti gampang ditambah tanpa tata ulang lagi:

```
config/
├── dbconn.php
includes/
├── auth.php
api/
├── helpers/response.php
├── auth/
└── publikasi/
modules/
├── auth/
├── publikasi/
└── galeri/
```

## Filter & Pencarian Publikasi

Ditambahkan panel filter di `page09A.php` (Daftar Publikasi), sesuai desain
yang diminta: kata kunci, filter tahun, dan urutan (Terbaru/Terlama/Terpopuler).

```
database/
└── publikasi_dilihat.sql      (baru) tambah kolom `dilihat` ke tabel publikasi
modules/publikasi/
├── page09A.php                 (diubah) panel filter + query dinamis
└── track_view.php              (baru) endpoint hitung klik untuk "Terpopuler"
assets/css/myCSS2.css           (diubah) style panel filter
```

### Cara pakai

1. Jalankan `database/publikasi_dilihat.sql` (setelah `hafidz_db.sql`) untuk
   menambah kolom `dilihat`.
2. Buka `page09A.php` — panel filter otomatis tampil di sebelah kiri tabel.

### Cara kerja

- **Kata Kunci**: cari di kolom `judul` (default, checkbox tercentang) atau
  di `judul` + `link_publikasi` sekaligus (checkbox di-uncheck).
- **Tahun**: dropdown-nya otomatis terisi dari tahun-tahun yang benar-benar
  ada di data (`SELECT DISTINCT YEAR(tanggal_rilis)`), jadi tidak ada
  pilihan tahun kosong.
- **Urutkan Berdasarkan**:
  - *Terbaru* → `ORDER BY tanggal_rilis DESC`
  - *Terlama* → `ORDER BY tanggal_rilis ASC`
  - *Terpopuler* → `ORDER BY dilihat DESC` (jumlah klik judul publikasi)
- Semua kombinasi filter dikirim lewat **GET** (`?q=...&tahun=...&urutan=...`)
  supaya URL hasil filter bisa di-bookmark/dibagikan, dan query tetap pakai
  **prepared statement** (bukan concat string) — aman dari SQL injection.
- **Popularitas** dihitung dari klik judul publikasi: setiap kali link judul
  diklik, JavaScript mengirim `fetch()` ke `track_view.php` di background
  (`keepalive: true`, tidak menghalangi link `target="_blank"` tetap terbuka
  seperti biasa) yang menambah kolom `dilihat` di database.
- Endpoint API (`api/publikasi/index.php`) juga sudah didukung parameter
  yang sama: `?search=&tahun=&urutan=` — jadi konsisten antara halaman web
  dan API.

---

# Catatan Migrasi: Audit Bug & Keamanan (Sesi Kedua)

Nama file dan struktur folder **tidak diubah**. Semua perubahan di bawah ini
sudah lolos `php -l` (syntax check). Ringkasan lengkap tiap poin ada di pesan
chat, di sini hanya daftar file yang berubah + catatan tindak lanjut.

## 🔴 Keamanan Kritis
1. **API key BPS ter-hardcode & terduplikasi** — `config/bps_api.php`,
   `modules/publikasi/berita.php`. Sekarang baca dari environment variable
   `BPS_API_KEY` (fallback placeholder), tidak ada key asli lagi di kode.
   **⚠️ Key lama harus dianggap bocor** (sudah pernah ter-commit ke repo
   publik) — rotate key di akun BPS Web API Anda, lalu set env var baru.
2. **SSL verification dimatikan** di `berita.php` — diaktifkan kembali,
   konsisten dengan `bps_search.php`/`bps_detail.php`.
3. **Delete data lewat GET tanpa CSRF token** — `page09F.php` (publikasi),
   `page09J.php` (galeri). Sekarang wajib POST + `csrf_token` (helper baru:
   `csrfToken()` & `requireValidCsrf()` di `includes/auth.php`). Tombol hapus
   di `page09A.php`/`page09G.php` diganti jadi form kecil.
4. **Validasi upload hanya cek ekstensi nama file** — ditambahkan
   `getimagesize()` di semua endpoint upload: `page09C_action.php`,
   `page09E_action.php`, `page09H_action.php`, `page09I_action.php`, dan
   `api/publikasi/index.php` + `api/publikasi/detail.php`.

## 🟡 Bug Fungsional
5. **Dashboard `index.php` macet di "Menghubungkan ke API BPS..."** — script
   pengisinya dulu di-comment total. Sekarang widget diisi ringkasan data
   lokal (total publikasi, publikasi tahun ini, total dilihat, jumlah foto
   galeri) langsung dari PHP, tidak bergantung JS/API eksternal lagi.
6. **`katalog.php` error `fetchData is not defined`** — fungsi `fetchData()`
   diaktifkan kembali. Datanya masih data contoh (bukan API BPS live) karena
   itu butuh kode variabel/subjek resmi yang belum tersedia di project ini.

## 🟢 Minor
7. Output `tanggal_rilis` & `sampul` di `page09A.php` sekarang di-escape
   (`htmlspecialchars`), konsisten dengan field lain.
8. Akses `$_GET`/`$_POST` tanpa `?? ''` dirapikan di `page09E_action.php`,
   `page09F.php`, `page11A_gethint.php`.

## File Baru
- `.gitignore` — mencegah `.env`/config lokal ke-commit ke depannya.

## ⚠️ Belum Diperbaiki (Perlu Keputusan Anda)
- **`api/publikasi/index.php` (POST) dan `api/publikasi/detail.php` (PUT/DELETE)**
  memakai autentikasi session cookie tanpa CSRF token, sama seperti bug #3 di
  atas, tapi saya **tidak ubah** karena ini API JSON yang mungkin dipakai
  konsumer non-browser (Postman, aplikasi mobile, dst.) — menambah wajib CSRF
  token bisa mematahkan kontrak API yang sudah ada. Kalau API ini hanya
  dipakai dari halaman web sendiri, saran saya: tambahkan pengecekan header
  custom (mis. `X-Requested-With`) atau CSRF token seperti endpoint web biasa.

## Tindakan yang Wajib Anda Lakukan
1. **Rotate API key BPS** (poin #1) — paling mendesak, key lama sudah bocor.
2. Set environment variable `BPS_API_KEY` (dan opsional `BPS_DOMAIN`) di
   server/hosting.
3. Test alur hapus publikasi & galeri (sekarang lewat form, bukan link biasa).
4. Test upload gambar publikasi & galeri dengan file asli.
5. Buka `index.php` dan `katalog.php`, pastikan sudah tidak macet lagi.

---

# Catatan Migrasi: Audit Kesiapan Hosting (Sesi Ketiga)

Sesi ini fokus menutup celah yang relevan begitu website online/publik.

## 🔴 PALING KRITIS — Wajib Dilakukan SEBELUM Online

**Password akun contoh ditulis polos di repo publik.** `database/users.sql`
punya akun `admin` dengan password `admin123` yang **ditulis jelas di
komentar file**, dan file ini ada di GitHub publik — siapa pun yang buka
repo langsung tahu password admin-nya. Ini bukan sekadar "celah", ini kunci
pintu depan yang ditinggal nyantol di gemboknya.

**WAJIB:** begitu database di-deploy ke server produksi, **segera ganti
password akun `admin`** (lewat query manual di phpMyAdmin/mysql client,
karena aplikasi ini belum punya fitur ganti password sendiri). Beri tahu
saya kalau Anda mau saya buatkan fitur ganti password — supaya ke depannya
tidak perlu ubah manual lewat database.

## 🔴 Keamanan Kritis Lain

1. **CSRF di endpoint API JSON** (`api/publikasi/index.php` POST,
   `api/publikasi/detail.php` PUT/DELETE) — sebelumnya cuma dilindungi
   session cookie, sehingga bisa dipicu dari form di situs lain (termasuk
   lewat `_method=PUT`/`_method=DELETE` override yang tetap bisa dikirim
   sebagai form POST biasa). Sekarang wajib token CSRF (`X-CSRF-Token`),
   didapat dari response `/api/auth/login.php` atau `/api/auth/me.php`.
   Dokumentasi `api/API.md` sudah diperbarui dengan contoh lengkap.
2. **Pesan error database bocor ke pengunjung** — `config/dbconn.php`,
   `page09E_action.php`, `page09F.php`, `page09C_action.php`,
   `page11A_gethint.php` sebelumnya menampilkan `$e->getMessage()` PDO
   langsung ke browser (bisa membocorkan nama tabel/kolom/struktur query).
   Sekarang pesan asli dicatat ke `error_log()` server, pengunjung cuma
   lihat pesan generik.
3. **Session cookie belum di-hardening** — ditambahkan `httponly` (cookie
   tidak bisa dibaca lewat JavaScript → mitigasi XSS), `samesite=Lax`
   (mitigasi CSRF di request cross-site), dan `secure` otomatis aktif kalau
   request lewat HTTPS. Dipusatkan di `includes/auth.php` (sebelumnya
   `index.php` juga panggil `session_start()` sendiri secara terpisah tanpa
   parameter apa pun — sudah dirapikan jadi satu titik).
4. **Folder sensitif bisa diakses langsung lewat browser** — ditambahkan
   `.htaccess` di `config/`, `includes/`, `database/`, dan root project,
   supaya file `.sql` (skema + akun contoh), `.md` (dokumentasi internal),
   `.env`, `.gitignore`, `.patch`, dan folder `.git` (kalau ikut ter-upload)
   tidak bisa diunduh langsung lewat URL. **Catatan:** ini proteksi khusus
   Apache (`.htaccess`) — kalau hosting Anda pakai Nginx, beri tahu saya,
   akan saya buatkan konfigurasi setara di `nginx.conf`.

## File Baru (Sesi Ini)
- `.htaccess` (root, `config/`, `includes/`, `database/`)

## Tindak Lanjut yang Masih Terbuka
- Fitur ganti password untuk akun admin/user (lihat poin paling kritis di
  atas) — belum ada, disarankan dibuat sebelum atau segera setelah go-live.
- Kalau hosting pakai Nginx (bukan Apache), `.htaccess` yang baru ditambahkan
  tidak akan berfungsi — perlu konfigurasi block-access setara di level
  server.

## ✅ Tambahan: Rate-Limiting Login (Mitigasi Brute-Force)

Sebelumnya `login_action.php` (web) dan `api/auth/login.php` (API) tidak
membatasi jumlah percobaan login sama sekali — password bisa ditebak
berulang kali tanpa batas. Sekarang:

- Tabel baru `login_attempts` (migrasi: `database/login_attempts.sql`,
  **jalankan setelah `users.sql`**) mencatat setiap percobaan login gagal
  per kombinasi username + alamat IP.
- Kalau sudah ≥5 percobaan gagal dalam 15 menit terakhir untuk kombinasi
  yang sama, login diblokir sementara dengan pesan
  "Terlalu banyak percobaan login gagal. Coba lagi dalam beberapa menit."
  (helper: `tooManyLoginAttempts()`, `recordFailedLogin()`,
  `clearLoginAttempts()` di `includes/auth.php`).
- Riwayat percobaan gagal otomatis dibersihkan begitu login berhasil.
- **Catatan:** fungsi `clientIp()` di `includes/auth.php` cuma pakai
  `REMOTE_ADDR` (bukan header `X-Forwarded-For` yang gampang dipalsukan).
  Kalau hosting Anda ada di belakang reverse proxy/CDN (Cloudflare, dll)
  sehingga `REMOTE_ADDR` yang tercatat jadi IP proxy-nya (bukan IP asli
  pengunjung), beri tahu saya supaya disesuaikan.

**Migrasi database yang perlu dijalankan (urutan):**
```
database/hafidz_db.sql
database/galeri.sql
database/publikasi_dilihat.sql
database/users.sql
database/login_attempts.sql   <-- baru
```

## ✅ Tambahan: CSRF Belum Lengkap di Form Tambah & Edit

Setelah ditelusuri ulang, token CSRF sebelumnya **cuma dipasang di endpoint
hapus** (`page09F.php`, `page09J.php`). Form tambah & edit (yang sama-sama
mengubah data) belum dilindungi — celah yang sama persis, cuma beda aksi.
Sudah dilengkapi di:

- `page09C.php` / `page09C_action.php` (tambah publikasi)
- `page09E.php` / `page09E_action.php` (edit publikasi)
- `page09H.php` / `page09H_action.php` (tambah foto galeri)
- `page09I.php` / `page09I_action.php` (edit foto galeri)

Pola sama seperti delete: hidden input `csrf_token` di form, divalidasi
lewat `requireValidCsrf()` di awal file `*_action.php`-nya.

**Sengaja tidak ditambahkan CSRF di:**
- `modules/auth/register_action.php` — pendaftaran akun baru, bukan aksi
  admin, dan dampak CSRF di sini minimal (paling attacker cuma bisa bikin
  akun atas nama korban tanpa akses ke apa pun).
- `modules/publikasi/track_view.php` — cuma nambah counter "dilihat",
  dampak forge-nya sepele (angka pengunjung sedikit meleset), tidak
  sepadan dengan menambah kerumitan.

---

# Catatan Migrasi: Audit Mendalam Terakhir (Sesi Keempat)

Penelusuran ulang khusus mencari XSS & SSRF yang mungkin terlewat di
sesi-sesi sebelumnya.

## 🔴 XSS Tersimpan (Stored XSS) — `assets/js/page11A_suggestion.js`

**Ini yang paling serius ditemukan di sesi ini.** Fitur autocomplete
pencarian publikasi (dipakai semua user yang login, bukan cuma admin)
punya dua bug sekaligus:

1. Judul publikasi dari database dimasukkan **mentah-mentah ke `innerHTML`**
   tanpa escaping sama sekali.
2. Ada usaha "escape" tanda kutip untuk atribut `onclick`, tapi caranya
   salah (`.replace(/'/g, "\'")` di JavaScript itu **tidak melakukan
   apa-apa** — `"\'"` cuma menghasilkan `'` lagi, bukan versi ter-escape).

Kalau ada judul publikasi yang mengandung tag HTML/script (misalnya lewat
fitur "Tarik Data Publikasi" dari BPS Pusat yang auto-fill judul, atau
kalau akun admin suatu saat disalahgunakan), skrip itu akan tereksekusi di
browser **user lain** yang mengetik kata kunci yang cocok di kotak
pencarian — termasuk berpotensi membajak sesi admin lain yang sedang login
(mengambil token CSRF dari halaman, lalu memakainya untuk melakukan aksi
admin atas nama korban).

**Perbaikan:** fungsi `showHint()` ditulis ulang total — sekarang membangun
elemen DOM lewat `createElement()` + `textContent` (bukan menempel string
HTML ke `innerHTML`), yang secara otomatis membuat teks apa pun aman
ditampilkan tanpa pernah ditafsirkan sebagai HTML/script.

## 🟡 Validasi Tambahan — `bps_detail.php`

Gambar sampul yang diunduh otomatis dari BPS Pusat sebelumnya langsung
disimpan ke `assets/img/` tanpa memastikan isinya benar-benar gambar
(beda dengan upload manual yang sudah divalidasi `getimagesize()` di sesi
sebelumnya). Sekarang konten yang diunduh divalidasi dulu sebelum disimpan;
kalau bukan gambar valid, tidak disimpan dan ada catatan di `sampul_debug`.

## 🟡 XSS di Atribut HTML — `modules/galeri/page09G.php`

Judul galeri dimasukkan ke atribut `onclick` lewat `json_encode()` tanpa
`htmlspecialchars()` tambahan — kalau judul mengandung tanda kutip ganda
(`"`), itu bisa memutus atribut HTML lebih awal dan menyuntik atribut/skrip
baru. Risiko rendah (cuma admin yang bisa isi judul galeri), tapi tetap
diperbaiki untuk konsistensi — sekarang pakai kombinasi
`JSON_HEX_QUOT|JSON_HEX_APOS|JSON_HEX_TAG|JSON_HEX_AMP` + `htmlspecialchars()`.

## ✅ Yang Sudah Dicek dan Aman (Tidak Perlu Perubahan)
- Semua endpoint admin (`page09C_action.php`, `page09E_action.php`,
  `page09F.php`, `page09H_action.php`, `page09I_action.php`, `page09J.php`,
  `bps_search.php`, `bps_detail.php`) sudah pasti memanggil `requireAdmin()`.
- Semua endpoint API write (`POST`/`PUT`/`DELETE` di `api/publikasi/`)
  sudah pasti memanggil `apiRequireAdmin()` **dan** `apiRequireCsrf()`.
- Output pencarian (`$q` di `page09A.php`), pesan error di `login.php`/
  `register.php`, dan data dari API eksternal di `berita.php` semuanya
  sudah lewat `htmlspecialchars()` dengan benar.
- `bps_search.php` tidak rawan SSRF (base URL tetap ke domain BPS resmi,
  cuma `keyword` yang di-urlencode ke dalamnya).
- `innerHTML` lain di `katalog.php` dan `page09C.php` sudah aman: di
  `katalog.php` cuma data contoh statis (bukan input pengguna), dan di
  `page09C.php` data dari BPS dimasukkan lewat `textContent`/nama file
  yang sudah disanitasi server, bukan ditempel langsung ke `innerHTML`.
- `validasiForm.js` — pesan error yang masuk ke `innerHTML` semuanya string
  statis (hardcoded), tidak ada input pengguna yang diselipkan langsung.

Sejauh ini semua celah yang ditemukan lewat penelusuran manual sudah
ditutup. Kalau nanti Anda mau, saya bisa jalankan static analysis tool
otomatis (mis. `phpstan` + custom security rules) untuk verifikasi
tambahan, tapi untuk sekarang cakupan manual sudah mencakup: SQL injection,
XSS (reflected, stored, DOM-based), CSRF, SSRF, path traversal, insecure
file upload, hardcoded secrets, error/info disclosure, dan session
hardening.

---

# Fitur Baru: Dashboard Indikator BPS (Sesi Kelima)

Halaman `index.php` sekarang bisa menampilkan **angka statistik & grafik
tren** langsung dari Web API BPS (bukan cuma ringkasan data publikasi
lokal seperti sebelumnya), memakai API key yang sudah dikonfigurasi di
`config/bps_api.php`.

## Kenapa Tidak Langsung Diisi Var_id Jadi?

BPS Web API punya konsep `var_id` (ID variabel statistik) yang **beda-beda
per tabel/subjek dan tidak bisa ditebak** — saya cek struktur resminya
lewat kode sumber Python client resmi BPS (`stadata`,
github.com/bps-statistics/stadata) supaya parsing-nya akurat, tapi saya
tidak punya akses jaringan ke `webapi.bps.go.id` dari lingkungan kerja saya
untuk mencoba API key Anda secara langsung dan menemukan var_id yang benar
untuk domain Sumut (1200). Kalau saya asal menebak var_id, dashboard bisa
menampilkan **angka yang salah** tanpa ada yang sadar — itu lebih
berbahaya daripada dashboard kosong. Jadi yang saya siapkan adalah
infrastruktur lengkapnya + alat bantu pencarian, tinggal Anda isi var_id
yang benar (5 menit kerja).

## File Baru

- **`includes/bps_client.php`** — helper inti pemanggilan BPS Web API:
  - `bpsFetchVariableList()` — cari variabel berdasarkan kata kunci.
  - `bpsFetchIndicator()` — ambil nilai terbaru + tren beberapa tahun
    untuk satu var_id.
  - `bpsFetchIndicatorCached()` — sama seperti di atas, tapi di-cache ke
    file selama 1 jam supaya dashboard yang dibuka berkali-kali oleh
    banyak user tidak membombardir API BPS (dan berisiko kena rate limit).
  - Sudah diuji offline dengan data tiruan yang strukturnya meniru respons
    resmi BPS (lihat komentar di file) — hasil parsing, urutan tren, dan
    penanganan kasus gagal semuanya terverifikasi benar sebelum diserahkan.
- **`modules/publikasi/bps_variable_search.php`** (admin-only) — alat
  bantu cari `var_id` yang benar. Login sebagai admin, lalu buka di
  browser:
  ```
  https://domain-anda/projekpbw/modules/publikasi/bps_variable_search.php?keyword=inflasi
  ```
  Ganti `keyword` sesuai indikator yang dicari (mis. "penduduk",
  "pengangguran", "kemiskinan", "IPM", "pertumbuhan ekonomi"). Hasilnya
  daftar `var_id` + judul + satuan yang bisa langsung dipakai.
- **`config/bps_indicators.php`** — daftar indikator yang ditampilkan di
  dashboard. **Kosong secara default** (biar tidak menampilkan angka
  salah), diisi array `var_id` + `title` untuk tiap indikator yang mau
  ditampilkan. Ada 1 opsi `show_trend` untuk menampilkan grafik tren
  (SVG, dibuat langsung di server, tidak butuh library chart eksternal).
  Instruksi lengkap ada di komentar file itu sendiri.
- **`cache/`** — folder cache hasil fetch BPS API (TTL 1 jam), dibuat
  otomatis. Sudah diberi `.htaccess` (blokir akses langsung) dan masuk
  `.gitignore` (isinya tidak perlu di-commit, cuma foldernya).

## Cara Mengaktifkan (Langkah Anda)

1. Pastikan `BPS_API_KEY` sudah diisi (lihat poin API key di sesi-sesi
   sebelumnya).
2. Login sebagai admin, cari `var_id` indikator yang Anda mau lewat
   `bps_variable_search.php?keyword=...` (lihat di atas).
3. Buka `config/bps_indicators.php`, tambahkan satu entri array per
   indikator, isi `var_id` dan `title` dari hasil pencarian.
4. Refresh `index.php` — kartu indikator akan muncul otomatis. Kalau ada
   yang menampilkan "Data tidak tersedia", baca pesan errornya (biasanya
   karena `var_id` salah, atau kombinasi wilayah/turunan variabelnya perlu
   diisi manual — lihat instruksi di `config/bps_indicators.php`).
5. Pastikan folder `cache/` bisa ditulis oleh web server (`chmod 775
   cache/` kalau perlu) setelah deploy, supaya fitur cache-nya jalan.

## Batasan yang Perlu Diketahui

- **Belum diuji dengan API key & data BPS sungguhan** (keterbatasan
  jaringan saya, dijelaskan di atas). Logic parsing-nya sudah diverifikasi
  offline dengan data tiruan yang strukturnya sesuai dokumentasi resmi,
  tapi tetap disarankan Anda coba dulu dengan 1 indikator setelah deploy
  sebelum menambah banyak-banyak.
- **Auto-pilih wilayah/turunan variabel** (kalau `vervar_id`/`turvar_id`
  tidak diisi manual) mengambil kombinasi PERTAMA yang datanya ada — untuk
  sebagian besar tabel provinsi ini biasanya representasi paling umum,
  tapi tidak dijamin selalu "Total". Kalau angkanya terlihat aneh, cek
  instruksi di `config/bps_indicators.php` untuk isi manual.
- Kalau banyak indikator dikonfigurasi sekaligus dan cache-nya baru
  kedaluwarsa, halaman `index.php` bisa terasa lambat sesaat (curl
  berurutan, bukan paralel) — sekali ter-cache, load berikutnya cepat lagi
  selama 1 jam. Kalau ini jadi masalah nyata, beri tahu saya, bisa
  dioptimasi pakai `curl_multi`.

---

# Penyempurnaan: Auto-Discovery + Katalog Data Live (Sesi Keenam)

## Home (`index.php`) — Sekarang Langsung Jalan Tanpa Setup Manual

Sebelumnya `config/bps_indicators.php` kosong (butuh admin cari `var_id`
manual dulu lewat `bps_variable_search.php`). Sekarang ditambahkan **mode
auto-discovery**: cukup isi kata kunci (`'keyword' => 'inflasi'`), sistem
otomatis mencari & memilih variabel BPS yang paling cocok, tanpa perlu tahu
`var_id`-nya sama sekali.

**Cara kerja pemilihan otomatis** (`bpsPickHeadlineVariable()` di
`includes/bps_client.php`): dari hasil pencarian kata kunci, variabel yang
judulnya mengandung "menurut"/"berdasarkan" (biasanya tabel breakdown/
silang, bukan angka ringkasan) diturunkan prioritasnya; dari sisanya,
dipilih yang judulnya paling pendek (biasanya indikator paling umum).
Heuristik ini **sudah diuji offline** dengan data tiruan (lihat bagian
"Cara Saya Menguji" di bawah) dan terbukti benar memilih "Jumlah Penduduk"
dibanding "Jumlah Penduduk Menurut Jenis Kelamin dan Kelompok Umur" pada
kasus uji. Tapi ini tetap **heuristik, bukan jaminan 100% akurat** untuk
semua kemungkinan judul variabel BPS yang belum pernah diuji.

**Default yang sudah diisi di `config/bps_indicators.php`:**
- Jumlah Penduduk (dengan grafik tren)
- Inflasi
- Tingkat Pengangguran Terbuka
- Penduduk Miskin

**Kalau hasilnya kurang presisi:** upgrade indikator itu ke mode manual —
isi `var_id` (didapat dari `bps_variable_search.php`, admin-only), yang
otomatis didahulukan dibanding `keyword`. Instruksi lengkap ada di
komentar `config/bps_indicators.php`.

**Transparansi di kartu:** kalau wilayah/turunan variabel yang terpilih
BUKAN "Sumatera Utara"/"Total" (kemungkinan auto-discovery kurang tepat),
kartu di dashboard menampilkan label wilayah/breakdown-nya supaya
kejanggalan langsung kelihatan, bukan diam-diam menampilkan angka yang
salah konteks.

## Katalog Data (`katalog.php`) — Sekarang Menampilkan Data BPS Sungguhan

Sebelumnya tabel di halaman ini cuma data contoh statis. Sekarang:

- **File baru: `modules/publikasi/bps_katalog_data.php`** — proxy
  read-only (login biasa, BUKAN admin-only, beda dengan
  `bps_variable_search.php` yang memang alat konfigurasi khusus admin).
  Klik subjek di sidebar → cari variabel BPS yang cocok dengan kata kunci
  subjek itu → tampilkan sampai 6 indikator sekaligus sebagai baris tabel
  (nilai terbaru + satuan + tahun), bukan cuma 1 angka.
- Sidebar sekarang punya 5 subjek (sebelumnya 3): Sosial & Kependudukan,
  Ekonomi & Perdagangan, Pertanian & Pertambangan, **Inflasi & Harga**
  (baru), **Kemiskinan** (baru). Kata kunci pencarian yang dikirim ke API
  dipisah dari label yang ditampilkan (mis. tombol "Sosial &
  Kependudukan" mengirim kata kunci `penduduk` ke API, karena judul
  variabel BPS tidak akan pernah literally mengandung frasa "Sosial &
  Kependudukan").
- JS-nya ditulis ulang total: sebelumnya (di sesi sebelumnya) sempat
  memakai data contoh yang di-hardcode; sekarang betulan `fetch()` ke
  proxy di atas, dan hasilnya dirender ke tabel lewat
  `createElement()`/`textContent` (bukan menyisipkan string HTML ke
  `innerHTML`) — konsisten dengan perbaikan XSS yang sama di
  `assets/js/page11A_suggestion.js` pada sesi audit sebelumnya.

## Cara Saya Menguji (Tanpa Akses Jaringan ke BPS)

Saya tetap tidak punya akses jaringan ke `webapi.bps.go.id` dari
lingkungan kerja saya (di luar domain yang di-whitelist), jadi saya tidak
bisa mencoba API key Anda yang sesungguhnya secara langsung. Yang sudah
saya uji **secara offline** dengan data tiruan berstruktur sama persis
seperti respons resmi BPS (berdasarkan kode sumber client Python resmi
BPS, `stadata`):

1. **Parsing respons `model=data`** — ekstraksi nilai terbaru & tren
   beberapa tahun dari struktur `datacontent`/`vervar`/`turvar`/`tahun`,
   termasuk kasus gagal (filter salah, struktur kosong).
2. **Heuristik pemilihan indikator** (`bpsPickHeadlineVariable`) — kasus
   campuran breakdown+headline, kasus semua breakdown, kasus array kosong.
3. **Render grafik tren SVG** — validasi output adalah XML valid, termasuk
   edge case 1 titik data dan semua nilai sama (hindari divide-by-zero).
4. Semua PHP (termasuk 2 file baru sesi ini) lolos `php -l` penuh di
   seluruh project.

**Yang BELUM bisa saya uji:** respons API BPS yang sesungguhnya untuk
domain 1200 dengan API key asli Anda — termasuk apakah heuristik
auto-discovery benar-benar memilih variabel yang tepat untuk kata kunci
seperti "inflasi"/"kemiskinan" di data BPS nyata (bukan cuma data tiruan
yang saya buat sendiri). **Sangat disarankan** begitu di-deploy, buka
`index.php` dan `katalog.php`, cek apakah angka yang muncul masuk akal;
kalau ada yang aneh, itu kemungkinan besar soal heuristik pemilihan
variabel, bukan bug di parsing — upgrade indikator itu ke mode `var_id`
manual sesuai instruksi di `config/bps_indicators.php`.


---

# Perbaikan Bug: Parameter 'th' Wajib Diisi (Sesi Ketujuh)

## Bug yang Ditemukan

Begitu Anda coba dengan API key asli, BPS API menolak semua request
dengan error:
```
'th' parameter is required and must be an integer, separated by colon (:)
for range or semicolon (;) for multiple values.
```

**Akar masalah:** kode saya di sesi-sesi sebelumnya sama sekali tidak
mengirim parameter `th` (periode/tahun) saat meminta data ke BPS
(`model=data`), padahal BPS **mewajibkan** parameter ini. Referensi yang
saya pakai sebelumnya (package Python `stadata`) mengizinkan `th` kosong
di kode client-nya, tapi ternyata BPS API sendiri tetap menolak kalau
benar-benar dikirim kosong.

**Kenapa `katalog.php` sampai macet total** (bukan cuma "Data tidak
tersedia" seperti di Home): karena SEMUA percobaan ambil data gagal
dengan error yang sama, ditambah setiap indikator butuh waktu proses
sebelum akhirnya gagal — kombinasi banyak percobaan gagal berurutan bikin
halaman terasa macet lama sebelum akhirnya (seharusnya) menampilkan pesan
"tidak ada data". Ini sudah diperbaiki sekaligus dengan optimasi performa
di bawah.

## Cara Saya Menemukan Format yang Benar

Karena saya tetap tidak bisa mengakses `webapi.bps.go.id` langsung, saya
telusuri kode sumber **3 proyek open-source** yang benar-benar berhasil
mengonsumsi API BPS ini secara production (bukan dokumentasi resmi BPS
yang memblokir crawler):
- `digimetalab/dml-bps-mcp` (TypeScript, MCP server BPS yang sudah
  di-deploy publik) — ini yang paling krusial, menunjukkan persis
  bagaimana parameter `th` dikonstruksi sebagai **path parameter**
  (`/th/nilai/`), bukan query string (`?th=nilai`), dan struktur field
  asli periode: `{th_id, th_name, val}`.
- `bps-statistics/stadata` (Python, dikelola BPS sendiri) — struktur
  umum response `model=data`.
- `ekotwidodo/webapi-bps-study-case` — contoh implementasi JS lain.

## Perbaikan

**File baru: `includes/bps_client.php` → `bpsFetchPeriodList()` /
`bpsFetchPeriodListCached()`** — memanggil endpoint `model=th` (path:
`/list/model/th/domain/{domain}/var/{var_id}/key/{key}/`) untuk
menemukan periode yang BENAR-BENAR tersedia untuk suatu var_id, sebelum
minta datanya. Ini alur 2 langkah yang resmi (ditemukan konsisten di
ketiga referensi di atas): **cari var_id → cari th (periode) yang
tersedia → baru ambil data**.

**`bpsFetchIndicator()` diperbarui:** sekarang otomatis memanggil
`bpsFetchPeriodListCached()` dulu, ambil sampai 6 periode terbaru,
gabungkan nilainya jadi satu parameter `th` dipisah titik koma (`;` —
sesuai persis format yang disebutkan BPS sendiri di pesan error untuk
"multiple values"), lalu sertakan sebagai path parameter di request
`model=data`. Kalau pencarian periode itu sendiri gagal (mis. timeout),
ada fallback ke rentang tahun kalender 10 tahun terakhir dengan format
range (`:`, juga sesuai pesan error BPS).

**Sudah diuji offline** dengan data tiruan yang field-nya PERSIS
sesuai nama field asli yang saya temukan (`th_id`, `th_name`, `val`,
bukan `val`/`label` generik seperti asumsi saya sebelumnya) — urutan
periode, format penggabungan `th`, dan pencocokan `datacontent` semuanya
terverifikasi benar.

## Optimasi Performa (Karena Sekarang Butuh 2x Request per Indikator)

Karena tiap indikator sekarang perlu 2 panggilan API berurutan (cari
periode dulu, baru data), saya sesuaikan supaya halaman tidak terasa
lambat:
- Timeout tiap panggilan API yang ada di jalur render halaman (dashboard,
  katalog) dipersingkat dari 15 detik jadi 8 detik (alat admin seperti
  `bps_variable_search.php`/`bps_debug.php` tetap 15 detik karena itu aksi
  manual sekali klik, bukan bagian dari load halaman).
- Jumlah indikator yang diambil per subjek di `katalog.php` dikurangi
  dari 6 jadi 4.
- Kedua langkah (pencarian periode & data) sama-sama di-cache 1 jam,
  jadi cuma lambat di percobaan pertama; setelah itu cepat lagi.

## File Baru: Alat Debug (Admin-Only)

**`modules/publikasi/bps_debug.php?var_id=<var_id>`** — menampilkan
respons **mentah** (belum diolah sama sekali) dari BPS untuk `model=th`
dan `model=data` suatu var_id. Kalau setelah perbaikan ini masih ada
indikator yang hasilnya aneh, buka alat ini dan kirimkan hasilnya ke saya
— itu akan menghilangkan seluruh sisa tebakan yang masih ada (terutama
soal field apa persis yang dipakai BPS di bagian `tahun` pada respons
`model=data`, yang sejauh ini saya asumsikan `{val, label}` konsisten
dengan `vervar`/`turvar`, tapi belum 100% terverifikasi dengan data
sungguhan).

## Yang Masih Perlu Diverifikasi Live

1. Apakah field `tahun` di respons `model=data` benar-benar `{val,
   label}` (asumsi saya) — kalau BPS memakai `{th_id, th_name}` di sana
   juga (konsisten dengan `model=th`), pencocokan `datacontent` perlu
   disesuaikan. `bps_debug.php` akan langsung memperlihatkan ini.
2. Apakah `th` yang diterima BPS itu memang nilai `val` (tahun kalender)
   dan bukan `th_id` — saya cukup yakin ini benar berdasarkan deskripsi
   parameter di proyek MCP yang saya temukan (contohnya persis
   `"2020,2021,2022,2023"`, format tahun kalender), tapi tetap best-effort
   tanpa uji langsung.
3. **Catatan tambahan yang saya temukan saat riset** (belum tentu relevan
   untuk Anda): BPS API dilindungi Cloudflare yang kadang memblokir
   request dari IP cloud/VPS (AWS, GCP, dst.) dengan 403 — kalau nanti
   di-hosting di cloud VPS dan tiba-tiba semua request gagal dengan pola
   403 (beda dari error "th required" yang sudah kita tangani), ini
   kemungkinan penyebabnya, bukan bug di kode.

---

# Perbaikan Bug: Batas Maksimal 2 Tahun per Request (Sesi Kedelapan)

## Bug yang Ditemukan

Setelah perbaikan sesi 7 (menambahkan parameter `th` yang wajib), error
berubah jadi lebih spesifik dan lebih dekat ke solusi:
```
The maximum allowed number of years for the 'th' parameter is 2.
You provided 6. Please reduce the number of years accordingly.
```

**Konfirmasi penting:** ini justru bukti parameter `th` sudah terkirim
dan **diterima** dengan benar oleh BPS (format path parameter, dipisah
titik koma — semua sudah benar). Masalahnya cuma jumlahnya: saya minta 6
tahun sekaligus, padahal BPS membatasi maksimal **2 tahun per request**.

## Perbaikan

- Request tunggal ke `model=data` sekarang selalu maksimal 2 tahun,
  sesuai batas yang dikonfirmasi BPS sendiri.
- **Untuk kartu angka biasa** (bukan grafik tren): cukup 2 tahun (nilai
  terbaru + 1 pembanding), 1 kali request data (+ 1 request cari
  periode = 2 total, sudah paling efisien).
- **Untuk indikator dengan `show_trend => true`** (grafik tren, dipakai
  "Jumlah Penduduk" secara default): sekarang otomatis memecah jadi
  beberapa request 2-tahunan (mis. 3 request untuk 6 tahun data), lalu
  menggabungkan hasilnya jadi satu deret tren utuh. Fungsi baru:
  `bpsFetchIndicatorWindow()` (1 jendela) dipanggil berulang oleh
  `bpsFetchIndicator()` yang sekarang punya parameter `$maxYears`.
- **Sudah diuji offline**: pembagian jendela (`array_chunk`), penggabungan
  hasil tiap jendela jadi satu tren tanpa duplikat, pengurutan lama→baru,
  dan pengambilan nilai terbaru dari hasil gabungan — semua terverifikasi
  benar dengan data tiruan.
- `bps_debug.php` diperbarui: sekarang default minta 2 tahun (sesuai
  batas asli), dengan opsi `?years=N` kalau mau coba angka lain secara
  manual saat debugging.

## Dampak Performa

Kartu angka biasa: tetap 2 request per indikator (cari periode + data),
tidak berubah dari sesi sebelumnya. Indikator dengan grafik tren: nambah
jadi ~4 request (1 cari periode + 3 jendela data) untuk 6 tahun — tapi ini
cuma berlaku untuk maksimal 1 indikator (yang `show_trend`), jadi dampak
totalnya masih terkendali. Semua tetap di-cache 1 jam seperti biasa.

## Mohon Dicoba Lagi

Kombinasi perbaikan sesi 7 (parameter `th` wajib ada) + sesi 8 (maksimal 2
tahun) ini seharusnya sudah menyelesaikan error yang muncul di
screenshot. Kalau masih ada masalah setelah ini, kemungkinan besar
soal lain lagi (mis. field response yang beda dari asumsi saya) — pakai
`bps_debug.php?var_id=<id>` untuk lihat respons mentahnya dan kirimkan ke
saya.
