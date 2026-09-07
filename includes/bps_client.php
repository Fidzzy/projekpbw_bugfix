<?php
/**
 * Helper untuk konsumsi BPS Web API (dynamic table / Data Statistik Terkini).
 *
 * Struktur response di sini mengikuti implementasi resmi BPS di package
 * Python "stadata" (https://github.com/bps-statistics/stadata), supaya
 * parsing-nya sesuai dokumentasi API yang sebenarnya, bukan tebakan.
 *
 * Referensi struktur:
 * - model=var  -> list variabel dinamis yang tersedia untuk suatu domain.
 *   Response: { status, data: [ {page,pages,...}, [ {var_id,title,unit,...}, ... ] ] }
 * - model=data -> nilai data untuk satu var_id.
 *   Response: {
 *     status, datacontent: { "<vervar><var><turvar><tahun>": "nilai", ... },
 *     vervar: [{val,label}, ...],   // wilayah (kab/kota/provinsi)
 *     turvar: [{val,label}, ...],   // turunan variabel (mis. laki-laki/perempuan)
 *     tahun:  [{val,label}, ...],   // tahun data tersedia
 *     turtahun: [{val,label}, ...]  // opsional, turunan periode (bulan/triwulan)
 *   }
 *
 * PENTING: var_id BERBEDA-BEDA per tabel/subjek dan tidak universal antar
 * domain. Jangan menebak var_id — cari dulu var_id yang benar lewat
 * bpsFetchVariableList() (bisa dipakai admin lewat
 * modules/publikasi/bps_variable_search.php) sebelum memasukkannya ke
 * config/bps_indicators.php.
 */

require_once __DIR__ . '/../config/bps_api.php';

/**
 * Panggil BPS Web API mentah lewat cURL, dengan pengaturan aman
 * (SSL verification aktif, timeout, dsb.) konsisten dengan proxy lain
 * di project ini (bps_search.php, bps_detail.php).
 *
 * @return array{ok: bool, json: array|null, httpCode: int, error: string}
 */
function bpsApiCall(string $url, int $timeoutSeconds = 15): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'json' => null, 'httpCode' => $httpCode, 'error' => $error];
    }

    $json = json_decode($response, true);
    if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
        return ['ok' => false, 'json' => $json, 'httpCode' => $httpCode, 'error' => $json['message'] ?? 'Respons tidak valid dari BPS.'];
    }

    return ['ok' => true, 'json' => $json, 'httpCode' => $httpCode, 'error' => ''];
}

/**
 * Cari daftar variabel dinamis yang tersedia untuk domain tertentu,
 * opsional difilter kata kunci judul. Dipakai untuk MENEMUKAN var_id yang
 * benar sebelum dikonfigurasi di config/bps_indicators.php.
 *
 * @return array{ok: bool, items: array, error: string}
 *         items: list of ['var_id'=>, 'title'=>, 'unit'=>, 'subject'=>]
 */
function bpsFetchVariableList(string $domain, string $keyword = '', int $page = 1): array
{
    if (BPS_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA' || BPS_API_KEY === '') {
        return ['ok' => false, 'items' => [], 'error' => 'API key BPS belum dikonfigurasi di config/bps_api.php.'];
    }

    $url = BPS_API_BASE . 'list/model/var/lang/ind/domain/' . urlencode($domain)
        . '/keyword/' . urlencode($keyword)
        . '/page/' . $page
        . '/key/' . urlencode(BPS_API_KEY) . '/';

    $result = bpsApiCall($url);
    if (!$result['ok']) {
        return ['ok' => false, 'items' => [], 'error' => $result['error'] ?: 'Gagal mengambil daftar variabel dari BPS.'];
    }

    $rows = $result['json']['data'][1] ?? [];
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'var_id'  => $row['var_id'] ?? '',
            'title'   => $row['title'] ?? '',
            'unit'    => $row['unit'] ?? '',
            'subject' => $row['sub_name'] ?? '',
        ];
    }

    return ['ok' => true, 'items' => $items, 'error' => ''];
}

/**
 * Cari daftar periode (tahun) yang benar-benar tersedia untuk satu var_id,
 * lewat endpoint model=th BPS Web API. WAJIB dipanggil sebelum
 * bpsFetchIndicator(), karena BPS mewajibkan parameter 'th' diisi di
 * request model=data — request tanpa 'th' akan ditolak dengan error
 * "'th' parameter is required...".
 *
 * Referensi struktur & path parameter (bukan query string) dikonfirmasi
 * dari source code client resmi open-source (digimetalab/dml-bps-mcp,
 * lihat src/client/endpoints.ts: PATH_PARAMS termasuk 'th', dan
 * src/client/types.ts: BpsPeriod { th_id, th_name, val }).
 *
 * @return array{ok: bool, items: array<array{th_id:int|string, label:string, val:int|string}>, error: string}
 */
function bpsFetchPeriodList(string $domain, string $varId): array
{
    if (BPS_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA' || BPS_API_KEY === '') {
        return ['ok' => false, 'items' => [], 'error' => 'API key BPS belum dikonfigurasi.'];
    }

    $url = BPS_API_BASE . 'list/model/th/lang/ind/domain/' . urlencode($domain)
        . '/var/' . urlencode($varId)
        . '/key/' . urlencode(BPS_API_KEY) . '/';

    $result = bpsApiCall($url, 8);
    if (!$result['ok']) {
        return ['ok' => false, 'items' => [], 'error' => $result['error'] ?: 'Gagal mengambil daftar periode dari BPS.'];
    }

    $rows = $result['json']['data'][1] ?? [];
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'th_id' => $row['th_id'] ?? '',
            'label' => $row['th_name'] ?? ($row['th_id'] ?? ''),
            'val'   => $row['val'] ?? ($row['th_id'] ?? ''),
        ];
    }

    // Urutkan dari yang paling baru (val terbesar) supaya gampang ambil
    // "N periode terakhir".
    usort($items, fn($a, $b) => (int) $b['val'] <=> (int) $a['val']);

    return ['ok' => true, 'items' => $items, 'error' => ''];
}

/**
 * Versi ter-cache dari bpsFetchPeriodList() (TTL 1 jam) - dipakai supaya
 * bpsFetchIndicator() tidak perlu memanggil model=th berulang-ulang untuk
 * var_id yang sama dalam window waktu singkat (mis. banyak user buka
 * dashboard/katalog dalam 1 jam yang sama).
 */
function bpsFetchPeriodListCached(string $domain, string $varId, int $ttlSeconds = 3600): array
{
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $cacheKey  = md5('period|' . $domain . '|' . $varId);
    $cacheFile = $cacheDir . '/bps_indicator_' . $cacheKey . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSeconds) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $data = bpsFetchPeriodList($domain, $varId);

    if ($data['ok']) {
        @file_put_contents($cacheFile, json_encode($data));
    }

    return $data;
}

/**
 * Ambil data dinamis untuk satu var_id pada domain tertentu, lalu susun
 * jadi bentuk yang gampang dipakai: nilai terbaru + deret waktu (trend)
 * beberapa tahun terakhir.
 *
 * Kalau $vervarId / $turvarId tidak diisi, fungsi ini otomatis memilih
 * kombinasi PERTAMA yang punya data (biasanya representasi paling umum/
 * "total" untuk tabel itu) — cara ini tidak selalu 100% tepat untuk semua
 * tabel (ada tabel yang breakdown-nya bukan "total" dulu), jadi kalau
 * hasilnya kurang pas, isi $vervarId/$turvarId manual di
 * config/bps_indicators.php (bisa dilihat pilihannya lewat
 * bps_variable_search.php atau respons mentah endpoint ini saat testing).
 *
 * @param string      $domain    kode domain BPS, mis. '1200' untuk Sumut
 * @param string      $varId     var_id yang mau diambil datanya
 * @param string|null $vervarId  filter wilayah tertentu (opsional)
 * @param string|null $turvarId  filter turunan variabel tertentu (opsional)
 * @param string      $titleFallback judul yang dipakai kalau var_id ini
 *        tidak terbaca sendiri dari respons (BPS API model=data tidak
 *        selalu menyertakan judul variabel di responsnya) — isi dari
 *        config/bps_indicators.php.
 * @param string      $unitFallback  satuan (mis. "persen", "jiwa") — isi
 *        dari config/bps_indicators.php, karena model=data juga tidak
 *        selalu menyertakan info satuan.
 *
 * @return array{
 *   ok: bool, title: string, unit: string, error: string,
 *   latestYear: string, latestValue: string,
 *   vervarLabel: string, turvarLabel: string,
 *   trend: array<array{year:string, value:string}>
 * }
 */
/**
 * Ambil data untuk SATU jendela periode (maks 2 tahun, batas dari BPS) -
 * helper internal, dipakai oleh bpsFetchIndicator() untuk membangun tren
 * lebih panjang dengan menggabungkan beberapa jendela.
 *
 * @return array sama seperti bpsFetchIndicator(), tanpa field 'trend'
 *         digabung dari luar (trend di sini cuma berisi tahun-tahun dalam
 *         jendela ini saja).
 */
function bpsFetchIndicatorWindow(
    string $domain,
    string $varId,
    string $thValue,
    ?string $vervarId,
    ?string $turvarId,
    string $titleFallback,
    string $unitFallback
): array {
    $empty = [
        'ok' => false, 'title' => $titleFallback, 'unit' => $unitFallback, 'error' => '',
        'vervarLabel' => '', 'turvarLabel' => '', 'trend' => [],
    ];

    $url = BPS_API_BASE . 'list/model/data/lang/ind/domain/' . urlencode($domain)
        . '/var/' . urlencode($varId)
        . '/th/' . urlencode($thValue)
        . '/key/' . urlencode(BPS_API_KEY) . '/';

    $result = bpsApiCall($url, 8);
    if (!$result['ok']) {
        $empty['error'] = $result['error'] ?: 'Gagal mengambil data indikator dari BPS.';
        return $empty;
    }

    $json = $result['json'];
    $datacontent = $json['datacontent'] ?? [];
    $vervarList  = $json['vervar'] ?? [];
    $turvarList  = $json['turvar'] ?? [];
    $tahunList   = $json['tahun'] ?? [];

    if (empty($datacontent) || empty($vervarList) || empty($turvarList) || empty($tahunList)) {
        $empty['error'] = 'Struktur data indikator tidak lengkap/tidak dikenali.';
        return $empty;
    }

    $title = $titleFallback !== '' ? $titleFallback : ('Var ' . $varId);
    $unit  = $unitFallback;

    usort($tahunList, fn($a, $b) => (int) $b['val'] <=> (int) $a['val']);

    $vervarCandidates = $vervarId !== null
        ? array_values(array_filter($vervarList, fn($v) => (string) $v['val'] === (string) $vervarId))
        : $vervarList;
    $turvarCandidates = $turvarId !== null
        ? array_values(array_filter($turvarList, fn($v) => (string) $v['val'] === (string) $turvarId))
        : $turvarList;

    if (empty($vervarCandidates) || empty($turvarCandidates)) {
        $empty['error'] = 'vervar_id/turvar_id yang dikonfigurasi tidak ditemukan di data BPS.';
        return $empty;
    }

    $chosenVervar = null;
    $chosenTurvar = null;
    $trend = [];

    foreach ($vervarCandidates as $vervar) {
        foreach ($turvarCandidates as $turvar) {
            $foundAny = false;
            $series = [];
            foreach ($tahunList as $tahun) {
                $key = $vervar['val'] . $varId . $turvar['val'] . $tahun['val'];
                if (isset($datacontent[$key]) && $datacontent[$key] !== '') {
                    $series[] = ['year' => $tahun['label'], 'value' => $datacontent[$key]];
                    $foundAny = true;
                }
            }
            if ($foundAny) {
                $chosenVervar = $vervar;
                $chosenTurvar = $turvar;
                $trend = array_reverse($series);
                break 2;
            }
        }
    }

    if ($chosenVervar === null) {
        $empty['error'] = 'Tidak ada data untuk kombinasi wilayah/variabel ini.';
        return $empty;
    }

    return [
        'ok'          => true,
        'title'       => $title,
        'unit'        => $unit,
        'error'       => '',
        'vervarLabel' => $chosenVervar['label'] ?? '',
        'turvarLabel' => $chosenTurvar['label'] ?? '',
        'trend'       => $trend,
    ];
}

/**
 * Ambil data dinamis untuk satu var_id pada domain tertentu, lalu susun
 * jadi bentuk yang gampang dipakai: nilai terbaru + deret waktu (trend)
 * beberapa tahun terakhir.
 *
 * Kalau $vervarId / $turvarId tidak diisi, fungsi ini otomatis memilih
 * kombinasi PERTAMA yang punya data (biasanya representasi paling umum/
 * "total" untuk tabel itu) — cara ini tidak selalu 100% tepat untuk semua
 * tabel (ada tabel yang breakdown-nya bukan "total" dulu), jadi kalau
 * hasilnya kurang pas, isi $vervarId/$turvarId manual di
 * config/bps_indicators.php (bisa dilihat pilihannya lewat
 * bps_variable_search.php atau respons mentah endpoint ini saat testing).
 *
 * PENTING: BPS membatasi parameter 'th' maksimal 2 tahun PER REQUEST
 * (dikonfirmasi dari pesan error BPS sendiri). Kalau $maxYears > 2,
 * fungsi ini otomatis memecah jadi beberapa request 2-tahunan dan
 * menggabungkan hasilnya — dipakai seperlunya (cuma untuk indikator yang
 * benar-benar butuh grafik tren) supaya tidak boros request ke BPS.
 *
 * @param string      $domain    kode domain BPS, mis. '1200' untuk Sumut
 * @param string      $varId     var_id yang mau diambil datanya
 * @param string|null $vervarId  filter wilayah tertentu (opsional)
 * @param string|null $turvarId  filter turunan variabel tertentu (opsional)
 * @param string      $titleFallback judul yang dipakai kalau var_id ini
 *        tidak terbaca sendiri dari respons (BPS API model=data tidak
 *        selalu menyertakan judul variabel di responsnya) — isi dari
 *        config/bps_indicators.php.
 * @param string      $unitFallback  satuan (mis. "persen", "jiwa") — isi
 *        dari config/bps_indicators.php, karena model=data juga tidak
 *        selalu menyertakan info satuan.
 * @param int         $maxYears  jumlah tahun yang diinginkan untuk tren
 *        (default 2 = cukup untuk kartu angka tunggal + 1 pembanding).
 *        Isi lebih besar (mis. 6) untuk indikator dengan show_trend.
 *
 * @return array{
 *   ok: bool, title: string, unit: string, error: string,
 *   latestYear: string, latestValue: string,
 *   vervarLabel: string, turvarLabel: string,
 *   trend: array<array{year:string, value:string}>
 * }
 */
function bpsFetchIndicator(
    string $domain,
    string $varId,
    ?string $vervarId = null,
    ?string $turvarId = null,
    string $titleFallback = '',
    string $unitFallback = '',
    int $maxYears = 2
): array {
    $empty = [
        'ok' => false, 'title' => $titleFallback, 'unit' => $unitFallback, 'error' => '',
        'latestYear' => '', 'latestValue' => '',
        'vervarLabel' => '', 'turvarLabel' => '', 'trend' => [],
    ];

    if (BPS_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA' || BPS_API_KEY === '') {
        $empty['error'] = 'API key BPS belum dikonfigurasi.';
        return $empty;
    }

    // BPS Web API MEWAJIBKAN parameter 'th' (periode/tahun) diisi di
    // request model=data, jadi cari dulu periode yang benar-benar
    // tersedia lewat model=th, baru minta datanya. Tanpa ini, BPS
    // menolak dengan error "'th' parameter is required...".
    $periodResult = bpsFetchPeriodListCached($domain, $varId);

    if ($periodResult['ok'] && !empty($periodResult['items'])) {
        // Periode sudah diurutkan baru->lama oleh bpsFetchPeriodListCached.
        $recentPeriods = array_slice($periodResult['items'], 0, max(2, $maxYears));
        $periodVals = array_map(fn($p) => (string) $p['val'], $recentPeriods);
    } else {
        // Fallback kalau pencarian periode sendiri gagal (mis. timeout) -
        // tebak N tahun kalender terakhir. Best-effort; kalau variabelnya
        // sudah lama tidak update, ini mungkin meleset dan hasilnya kosong.
        $currentYear = (int) date('Y');
        $periodVals = [];
        for ($i = 0; $i < max(2, $maxYears); $i++) {
            $periodVals[] = (string) ($currentYear - $i);
        }
    }

    // Pecah jadi kelompok berisi maksimal 2 tahun (batas BPS per request).
    $windows = array_chunk($periodVals, 2);

    $mergedTrend = [];
    $lastResult = null;
    $anyOk = false;

    foreach ($windows as $windowVals) {
        $thValue = implode(';', $windowVals);
        $windowResult = bpsFetchIndicatorWindow($domain, $varId, $thValue, $vervarId, $turvarId, $titleFallback, $unitFallback);
        $lastResult = $windowResult;

        if ($windowResult['ok']) {
            $anyOk = true;
            foreach ($windowResult['trend'] as $point) {
                // Dedupe kalau ada tahun yang kebetulan muncul di lebih
                // dari satu jendela (seharusnya tidak terjadi, tapi jaga-jaga).
                $mergedTrend[$point['year']] = $point;
            }
        }
    }

    if (!$anyOk) {
        $empty['error'] = $lastResult['error'] ?? 'Gagal mengambil data indikator dari BPS.';
        return $empty;
    }

    // Urutkan gabungan tren dari lama ke baru berdasarkan tahun (label
    // tahun biasanya berupa string angka, urutkan numerik).
    uksort($mergedTrend, fn($a, $b) => (int) $a <=> (int) $b);
    $trend = array_values($mergedTrend);
    $latest = end($trend);

    return [
        'ok'          => true,
        'title'       => $lastResult['title'],
        'unit'        => $lastResult['unit'],
        'error'       => '',
        'latestYear'  => $latest['year'] ?? '',
        'latestValue' => $latest['value'] ?? '',
        'vervarLabel' => $lastResult['vervarLabel'],
        'turvarLabel' => $lastResult['turvarLabel'],
        'trend'       => $trend,
    ];
}

/**
 * Dari daftar hasil pencarian variabel (bpsFetchVariableList), pilih yang
 * paling mungkin jadi "indikator utama/headline" untuk suatu kata kunci.
 *
 * Heuristik (tidak sempurna, tapi cukup masuk akal tanpa akses API
 * langsung untuk verifikasi): variabel dengan kata "menurut"/"berdasarkan"
 * di judulnya biasanya tabel breakdown/silang (mis. "Penduduk Menurut Jenis
 * Kelamin dan Kelompok Umur"), bukan angka ringkasan tunggal — jadi
 * diprioritaskan yang TIDAK mengandung kata itu. Dari sisa kandidat,
 * ambil yang judulnya paling pendek (biasanya indikator paling umum/dasar,
 * bukan yang sudah dipecah ke sub-kategori spesifik).
 */
function bpsPickHeadlineVariable(array $items): ?array
{
    if (empty($items)) {
        return null;
    }

    $breakdownWords = ['menurut', 'berdasarkan', 'per kabupaten', 'per kecamatan'];
    $general = array_values(array_filter($items, function ($item) use ($breakdownWords) {
        // strtolower/strpos (bukan mb_strtolower/mb_strpos) sengaja dipakai
        // supaya tidak butuh extension mbstring (belum tentu aktif di semua
        // hosting) - aman dipakai di sini karena cuma mencocokkan kata
        // kunci ASCII, dan strtolower() tidak merusak byte lanjutan UTF-8
        // (huruf non-ASCII tidak akan pernah "match" kata kunci ASCII ini).
        $titleLower = strtolower($item['title'] ?? '');
        foreach ($breakdownWords as $word) {
            if (strpos($titleLower, $word) !== false) {
                return false;
            }
        }
        return true;
    }));

    $candidates = !empty($general) ? $general : $items;

    usort($candidates, fn($a, $b) => strlen($a['title'] ?? '') <=> strlen($b['title'] ?? ''));

    return $candidates[0];
}

/**
 * Cari & ambil indikator otomatis berdasarkan kata kunci (tanpa perlu tahu
 * var_id-nya lebih dulu). Dipakai supaya dashboard "langsung jalan" begitu
 * API key diisi, tanpa perlu admin cari var_id manual satu-satu.
 *
 * Untuk hasil yang presisi (bukan auto-pilih), tetap disarankan isi
 * var_id manual di config/bps_indicators.php setelah tahu var_id yang
 * tepat lewat bps_variable_search.php.
 */
function bpsFetchIndicatorByKeyword(string $domain, string $keyword, string $titleOverride = '', string $unitOverride = '', int $maxYears = 2): array
{
    $empty = [
        'ok' => false, 'title' => $titleOverride ?: $keyword, 'unit' => $unitOverride, 'error' => '',
        'latestYear' => '', 'latestValue' => '',
        'vervarLabel' => '', 'turvarLabel' => '', 'trend' => [], 'varId' => '',
    ];

    $searchResult = bpsFetchVariableList($domain, $keyword);
    if (!$searchResult['ok']) {
        $empty['error'] = $searchResult['error'];
        return $empty;
    }

    $picked = bpsPickHeadlineVariable($searchResult['items']);
    if ($picked === null) {
        $empty['error'] = 'Tidak ada variabel BPS yang cocok dengan kata kunci "' . $keyword . '".';
        return $empty;
    }

    $indicator = bpsFetchIndicator(
        $domain,
        (string) $picked['var_id'],
        null,
        null,
        $titleOverride ?: $picked['title'],
        $unitOverride ?: $picked['unit'],
        $maxYears
    );
    $indicator['varId'] = $picked['var_id'];

    return $indicator;
}

/**
 * Versi ter-cache dari bpsFetchIndicatorByKeyword() (TTL 1 jam, sama
 * seperti bpsFetchIndicatorCached()).
 */
function bpsFetchIndicatorByKeywordCached(string $domain, string $keyword, string $titleOverride = '', string $unitOverride = '', int $maxYears = 2, int $ttlSeconds = 3600): array
{
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $cacheKey  = md5('keyword|' . $domain . '|' . $keyword . '|' . $titleOverride . '|' . $unitOverride . '|' . $maxYears);
    $cacheFile = $cacheDir . '/bps_indicator_' . $cacheKey . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSeconds) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $data = bpsFetchIndicatorByKeyword($domain, $keyword, $titleOverride, $unitOverride, $maxYears);

    if ($data['ok']) {
        @file_put_contents($cacheFile, json_encode($data));
    }

    return $data;
}

/**
 * Bungkus bpsFetchIndicator() dengan cache file sederhana (TTL 1 jam),
 * supaya dashboard yang dibuka berkali-kali oleh banyak user tidak
 * membombardir BPS API dengan request yang sama berulang-ulang (dan kena
 * rate limit).
 */
function bpsFetchIndicatorCached(
    string $domain,
    string $varId,
    ?string $vervarId,
    ?string $turvarId,
    string $titleFallback = '',
    string $unitFallback = '',
    int $maxYears = 2,
    int $ttlSeconds = 3600
): array {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    $cacheKey  = md5($domain . '|' . $varId . '|' . ($vervarId ?? '') . '|' . ($turvarId ?? '') . '|' . $maxYears);
    $cacheFile = $cacheDir . '/bps_indicator_' . $cacheKey . '.json';

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttlSeconds) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $data = bpsFetchIndicator($domain, $varId, $vervarId, $turvarId, $titleFallback, $unitFallback, $maxYears);

    // Cuma simpan ke cache kalau berhasil - kalau gagal (mis. API key
    // salah), jangan cache kegagalannya supaya begitu dibetulkan langsung
    // kepakai tanpa perlu tunggu TTL habis.
    if ($data['ok']) {
        @file_put_contents($cacheFile, json_encode($data));
    }

    return $data;
}
