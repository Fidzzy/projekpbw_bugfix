<?php
/**
 * Konfigurasi indikator BPS yang ditampilkan di dashboard (index.php).
 *
 * Ada 2 cara mengisi tiap indikator:
 *
 * A) OTOMATIS lewat 'keyword' (default di bawah, langsung jalan begitu
 *    BPS_API_KEY diisi, TANPA perlu cari var_id manual dulu):
 *    - Sistem mencari variabel BPS yang judulnya cocok dengan kata kunci,
 *      lalu otomatis memilih yang paling terlihat seperti "indikator
 *      utama" (bukan tabel breakdown/silang) - lihat bpsPickHeadlineVariable()
 *      di includes/bps_client.php untuk detail heuristiknya.
 *    - PRAKTIS tapi tidak 100% presisi - kadang bisa salah pilih kalau ada
 *      banyak variabel serupa. Cek hasilnya di dashboard; kalau kurang
 *      pas, upgrade ke cara B untuk indikator itu.
 *
 * B) MANUAL lewat 'var_id' (presisi, tapi perlu 1x pencarian dulu):
 *    1. Login sebagai admin, buka di browser:
 *       modules/publikasi/bps_variable_search.php?keyword=<kata kunci>
 *    2. Catat var_id yang paling sesuai dari hasilnya.
 *    3. Isi 'var_id' pada entri terkait (kalau 'var_id' diisi, dia dipakai
 *       duluan, 'keyword' diabaikan).
 *    4. (Opsional) Kalau nilai yang muncul kurang tepat, tambahkan
 *       vervar_id/turvar_id spesifik - lihat instruksi lengkapnya dengan
 *       membuka langsung di browser:
 *       https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/1200/var/VAR_ID_ANDA/key/API_KEY_ANDA/
 *       lalu lihat isi "vervar" dan "turvar" di respons JSON-nya.
 *
 * Field per indikator:
 * - keyword    Kata kunci pencarian (cara A). Diabaikan kalau 'var_id' diisi.
 * - var_id     ID variabel presisi (cara B). Kalau diisi, didahulukan.
 * - title      (wajib) Judul yang ditampilkan di kartu dashboard.
 * - unit       (opsional) Satuan, mis. "persen", "jiwa".
 * - vervar_id  (opsional, cuma untuk cara B) Filter wilayah tertentu.
 * - turvar_id  (opsional, cuma untuk cara B) Filter turunan variabel tertentu.
 * - show_trend (opsional, default false) Tampilkan grafik tren beberapa
 *   tahun terakhir untuk indikator ini (maksimal 1 yang aktif dipakai).
 *
 * Kosongkan array ini (return [];) kalau belum mau menampilkan indikator
 * apa pun — dashboard otomatis menyembunyikan bagian "Indikator BPS" kalau
 * tidak ada yang dikonfigurasi.
 */

return [
    [
        'keyword'    => 'penduduk',
        'title'      => 'Jumlah Penduduk',
        'unit'       => 'jiwa',
        'show_trend' => true,
    ],
    [
        'keyword' => 'inflasi',
        'title'   => 'Inflasi',
        'unit'    => 'persen',
    ],
    [
        'keyword' => 'pengangguran',
        'title'   => 'Tingkat Pengangguran Terbuka',
        'unit'    => 'persen',
    ],
    [
        'keyword' => 'kemiskinan',
        'title'   => 'Penduduk Miskin',
        'unit'    => 'persen',
    ],
];

