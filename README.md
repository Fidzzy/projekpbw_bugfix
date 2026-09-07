# Website Tiruan BPS Provinsi Sumatera Utara

Dibuat oleh : Muhammad Hafidz Ar Rasyid Hutagalung
NIM : 222413683
Kelas : 2KS3

## Struktur File

page09A.php : Halaman Daftar Publikasi BPS Sumatera Utara
page09C.php : Halaman Form Tambah Publikasi Baru
page09C_action.php : Proses Penambahan Publikasi Baru
page09G.php : Halaman Galeri Kegiatan
page09E.php : Halaman Edit Publikasi BPS Sumatera Utara
page09E_action.php : Proses Pembaruan Data Publikasi
page09F.php : Proses Penghapusan Data Publikasi
validasiForm.js : Script validasi form tambah publikasi
myCSS2.css : Stylesheet utama seluruh halaman

## Dokumentasi Kode

### page09A.php

Halaman ini menampilkan daftar publikasi BPS Sumatera Utara.
Terdapat 5 data yang tersedia dalam tabel diaman masing" datanya mengarah pada link publikasi resmi.
Untuk gambar sampulnya mengambil dari url resmi, tetapi ditambahkan juga pencegahan menggunakan onerror untuk mengambil gambar lokal jika gagal memuat gambar dari url.
Untuk tampilan CSS nya mengikuti style pada file myCSS2.css

### page09C.php

Halaman ini menampilkan form tambah publikasi baru.
Pada halaman ini terdapat form yang memiliki input field untuk nomor, judul, tanggal rilis, sampul, dan tombol submit.
Untuk valdiasi form terletak pada validasiForm.js.
Pesan error dan sukses akan muncul selama 4 detik dan akan hilang dengan sendirinya.
Untuk tampilan CSS nya mengikuti style pada file myCSS2.css

### page09C_action.php

File ini digunakan untuk menangani proses penambahan data publikasi baru ke dalam database.
Menerima data dari form tambah publikasi (`page09C.php`) melalui metode POST (nomor, judul, tanggal rilis) dan mengunggah gambar sampul ke folder `asset/`.
Setelah proses berhasil atau gagal, pengguna akan diarahkan kembali ke halaman `page09C.php` dengan membawa parameter URL berupa pesan sukses atau pesan error.

### validasiForm.js

Field nomor diblokir dari karakter non-angka secara real-time saat pengguna mengetik.

- Karakter yang diizinkan saat keydown: angka (0–9), Backspace, Delete, Tab, ArrowLeft, ArrowRight, Home, End
- Semua karakter lain termasuk e, +, -, . diblokir via e.preventDefault()
- Input via paste juga diblokir, karakter non-angka dihapus otomatis menggunakan .replace(/[^0-9]/g, "")

Fungsi validate06C() dipanggil saat form disubmit (onsubmit="return validate06C()").

- Nomor: Tidak boleh kosong; harus berupa angka (/^\d+$/)
- Judul: Tidak boleh kosong; tidak boleh mengandung karakter spesial kecuali spasi, :, dan –.
- Tanggal Rilis: Tidak boleh kosong
- Sampul: Wajib diunggah; ekstensi harus .jpg, .jpeg, .png, atau .gif
- Jika ada error ditampilkan di elemen #pesanError (kotak merah)
- Jika semua valid ditampilkan pesan sukses di #pesanSukses (kotak hijau), form di-reset, halaman tidak di-reload (return false)

Selain validasi form, script ini juga memasang listener pada semua link <nav> yang href-nya "#" (yaitu Home dan Logout).
Ketika diklik, e.preventDefault() mencegah halaman loncat, lalu muncul alert() bawaan browser dengan pesan:
"Halaman ini belum tersedia.\nWeb masih dalam pengembangan."

### page09G.php

Halaman ini menampilkan galeri kegiatan BPS Sumatera Utara.
Preview terletak diatas thumbnail.
Untuk gambar thumbnail terdiri dari 8 gambar yang memuat kegiatan yang dilakukan oleh BPS Sumatera Utara.
Sumber gambar diambil secara resmi dari sensus.bps.go.id/main/index/st2023, dengan menggunakan onerror untuk mengambil gambar lokal jika gagal memuat gambar dari url.
Besar gambar preview menyesuaikan dengan besar asli gambar, sedangkan besar gambar thumbnail mengikuti style pada file myCSS2.css.
Untuk gambar thumbnail ketika diklik akan menampilkan gambar tersebut pada area preview dan juga mengganti judul pada preview.
Fungsi gantiPreview(elThumb, judul) menangani pergantian preview dan penandaan thumbnail aktif (class aktif).
window.onload memastikan thumbnail pertama langsung ditandai aktif saat halaman dimuat.
Alert nav "Halaman Dalam Pengembangan" pada halaman ini ditangani oleh validasiForm.js yang dipanggil di akhir body.

### page09E.php

Halaman ini menampilkan form edit publikasi BPS Sumatera Utara.
Pada halaman ini terdapat form yang memiliki input field untuk nomor, judul, tanggal rilis, sampul, dan tombol submit.
Untuk valdiasi form terletak pada validasiForm.js.
Pesan error dan sukses akan muncul selama 4 detik dan akan hilang dengan sendirinya.
Untuk tampilan CSS nya mengikuti style pada file myCSS2.css

### page09E_action.php

File ini menangani proses pembaruan data publikasi di database.
Menerima data dari form edit (`page09E.php`) melalui metode POST. Jika pengguna mengunggah sampul baru, sampul lama akan dihapus dari server dan diganti dengan file sampul baru di folder `asset/`.
Setelah pembaruan data berhasil dilakukan, pengguna akan diarahkan kembali ke halaman `page09A.php` (Daftar Publikasi) dan memunculkan _alert_ konfirmasi berhasil.

### page09F.php

File ini digunakan untuk menangani proses penghapusan data publikasi dari database.
Menerima parameter `no` dan `sampul` melalui URL (metode GET). File ini akan menghapus data pada database berdasarkan `no`, dan jika ada file sampul, maka file tersebut juga akan dihapus dari direktori server (`asset/`).
Setelah penghapusan selesai, pengguna akan diarahkan kembali ke halaman `page09A.php` dengan memunculkan _alert_ konfirmasi penghapusan.

### myCSS2.css

Terdapat penambahan @media agar website lebih responsif pada perangkat kecil seperti hp

### Lainnya

Untuk header dan footer mengambil referensi warna yang terdapat pada web resmi bps.go.id.
Layout header dan footer juga telah dibuat semirip mungkin dengan web resmi bps.go.id.
Untuk gambar sampul, ikon dll terdapat di folder asset.
