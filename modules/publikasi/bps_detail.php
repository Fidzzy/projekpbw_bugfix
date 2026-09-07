<?php
/**
 * GET modules/publikasi/bps_detail.php?pub_id=xxx
 *
 * Ambil detail satu publikasi dari BPS Pusat (judul, tanggal rilis, link PDF),
 * lalu unduh gambar sampulnya langsung ke assets/img/ supaya bisa dipakai
 * tanpa admin perlu upload manual.
 *
 * Response: { success, message, data: { title, rl_date, pdf, sampul } }
 */

require_once '../../config/bps_api.php';
require_once '../../includes/auth.php';
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message, array $data = []): void
{
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if (BPS_API_KEY === 'GANTI_DENGAN_API_KEY_ANDA' || BPS_API_KEY === '') {
    respond(false, 'API key BPS belum dikonfigurasi. Isi BPS_API_KEY di config/bps_api.php.');
}

$pubId = trim($_GET['pub_id'] ?? '');

if ($pubId === '') {
    respond(false, 'Parameter pub_id wajib diisi.');
}

// PENTING: endpoint DETAIL publikasi BPS pakai path "view/", bukan "list/".
// Sebelumnya kode ini salah pakai "list/model/publication/domain/{d}/id/{id}/",
// yang membuat parameter "id" diabaikan BPS dan malah balik daftar biasa
// halaman 1 (bukan detail publikasi yang diminta).
$url = BPS_API_BASE . 'view/model/publication/lang/ind/domain/' . BPS_DOMAIN
    . '/id/' . urlencode($pubId) . '/key/' . urlencode(BPS_API_KEY) . '/';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    respond(false, 'Gagal menghubungi server BPS Pusat: ' . $curlErr);
}

$json = json_decode($response, true);

if (!is_array($json) || ($json['status'] ?? '') !== 'OK' || empty($json['data'])) {
    respond(false, 'Detail publikasi tidak ditemukan di BPS Pusat (HTTP ' . $httpCode . ').', ['raw' => $json]);
}

$data = $json['data'];

$title  = $data['title'] ?? '';
$rlDate = $data['rl_date'] ?? ($data['sch_date'] ?? '');
$pdf    = $data['pdf'] ?? '';
$cover  = $data['cover'] ?? '';

// rl_date dari BPS biasanya sudah format YYYY-MM-DD (cocok untuk <input type="date">).
// Jaga-jaga kalau formatnya beda, coba parse ulang.
if ($rlDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rlDate)) {
    $timestamp = strtotime($rlDate);
    $rlDate    = $timestamp ? date('Y-m-d', $timestamp) : '';
}

// Kalau field "cover" ternyata path relatif (bukan URL lengkap), coba
// tambahkan base URL BPS di depannya. Kalau field "pdf" juga relatif,
// lakukan hal yang sama.
if ($cover !== '' && !preg_match('/^https?:\/\//i', $cover)) {
    $cover = 'https://webapi.bps.go.id' . (str_starts_with($cover, '/') ? '' : '/') . $cover;
}
if ($pdf !== '' && !preg_match('/^https?:\/\//i', $pdf)) {
    $pdf = 'https://webapi.bps.go.id' . (str_starts_with($pdf, '/') ? '' : '/') . $pdf;
}

$sampulLocal = '';
$sampulDebug = null;

// Unduh gambar sampul ke assets/img/ kalau tersedia, supaya admin tidak
// perlu upload manual. Nama file dibuat unik dari pub_id.
if ($cover !== '') {
    $ext = strtolower(pathinfo(parse_url($cover, PHP_URL_PATH) ?: $cover, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $ext = 'jpg';
    }

    $chImg = curl_init($cover);
    curl_setopt($chImg, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chImg, CURLOPT_TIMEOUT, 15);
    curl_setopt($chImg, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($chImg, CURLOPT_FOLLOWLOCATION, true);
    $imgData     = curl_exec($chImg);
    $imgHttpCode = curl_getinfo($chImg, CURLINFO_HTTP_CODE);
    $imgErr      = curl_error($chImg);
    curl_close($chImg);

    $sampulDebug = [
        'url_dicoba' => $cover,
        'http_code'  => $imgHttpCode,
        'curl_error' => $imgErr,
        'ukuran'     => $imgData !== false ? strlen($imgData) . ' bytes' : 'gagal',
    ];

    if ($imgData !== false && $imgHttpCode === 200 && strlen($imgData) > 500) {
        // Validasi isi file benar-benar gambar sebelum disimpan ke server -
        // jangan percaya begitu saja konten dari URL eksternal (meski
        // sumbernya API resmi BPS), konsisten dengan validasi upload manual
        // di endpoint lain.
        $tmpCheck = tempnam(sys_get_temp_dir(), 'bpscover_');
        file_put_contents($tmpCheck, $imgData);
        $isValidImage = @getimagesize($tmpCheck) !== false;
        unlink($tmpCheck);

        if ($isValidImage) {
            $namaFile  = 'bps_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $pubId) . '.' . $ext;
            $dirUpload = __DIR__ . '/../../assets/img/';
            file_put_contents($dirUpload . $namaFile, $imgData);
            $sampulLocal = $namaFile;
        } else {
            $sampulDebug['validasi_gambar'] = 'Konten yang diunduh bukan file gambar valid, tidak disimpan.';
        }
    }
}

respond(true, 'Detail publikasi berhasil diambil.', [
    'title'   => $title,
    'rl_date' => $rlDate,
    'pdf'     => $pdf,
    'sampul'  => $sampulLocal,
    // Sementara disertakan untuk debug pemetaan field — hapus kalau sudah
    // dipastikan field title/rl_date/pdf/cover di atas sudah benar.
    'raw'          => $data,
    'sampul_debug' => $sampulDebug,
]);
