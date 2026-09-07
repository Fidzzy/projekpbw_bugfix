<?php
/**
 * GET modules/publikasi/bps_search.php?keyword=xxx
 *
 * Proxy ke Web API BPS Pusat (webapi.bps.go.id) untuk mencari publikasi
 * berdasarkan kata kunci judul. Dipanggil oleh JS di page09C.php lewat
 * fetch() — dibuat sebagai proxy PHP (bukan fetch langsung dari browser
 * ke BPS) supaya API key tidak terekspos di kode JavaScript publik, dan
 * supaya tidak kena masalah CORS.
 *
 * Response: { success: bool, message: string, data: [{pub_id, title, issued}] }
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
    respond(false, 'API key BPS belum dikonfigurasi. Isi BPS_API_KEY di config/bps_api.php (daftar gratis di webapi.bps.go.id/developer/).');
}

$keyword = trim($_GET['keyword'] ?? '');

if ($keyword === '') {
    respond(false, 'Kata kunci pencarian wajib diisi.');
}

// BPS Web API punya parameter "keyword" bawaan untuk cari judul publikasi,
// jadi tidak perlu lagi ambil banyak halaman lalu filter manual.
$url = BPS_API_BASE . 'list/model/publication/lang/ind/domain/' . BPS_DOMAIN
    . '/keyword/' . urlencode($keyword) . '/key/' . urlencode(BPS_API_KEY) . '/';

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

if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
    respond(false, 'Respons tidak valid dari server BPS (HTTP ' . $httpCode . '). Cek kembali API key kamu.', ['raw' => $json]);
}

// Struktur list BPS: data[0] = info paging, data[1] = array publikasi
$items = $json['data'][1] ?? [];
$hasil = [];

foreach ($items as $item) {
    $hasil[] = [
        'pub_id' => $item['pub_id'] ?? '',
        'title'  => $item['title'] ?? '',
        'issued' => $item['rl_date'] ?? ($item['updt_date'] ?? ''),
    ];
}

respond(true, count($hasil) . ' publikasi ditemukan.', $hasil);
