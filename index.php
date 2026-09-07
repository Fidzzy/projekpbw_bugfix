<?php
require_once 'config/dbconn.php';
require_once 'includes/auth.php';
require_once 'includes/bps_client.php';
requireLogin();

// Widget dashboard sebelumnya tidak pernah terisi karena script pengisi
// datanya (di bagian <script> bawah) di-comment total, jadi macet
// selamanya di teks "Menghubungkan ke API BPS...". Widget ini diisi dengan
// ringkasan data publikasi milik aplikasi sendiri supaya dashboard selalu
// tampil terisi walau indikator BPS di bawah belum/gagal dikonfigurasi.
$totalPublikasi = (int) $pdo->query('SELECT COUNT(*) FROM publikasi')->fetchColumn();
$publikasiTahunIni = (int) $pdo->query('SELECT COUNT(*) FROM publikasi WHERE YEAR(tanggal_rilis) = YEAR(CURDATE())')->fetchColumn();
$totalDilihat = (int) $pdo->query('SELECT COALESCE(SUM(dilihat), 0) FROM publikasi')->fetchColumn();
$totalGaleri = (int) $pdo->query('SELECT COUNT(*) FROM galeri')->fetchColumn();

$dashboardWidgets = [
    ['title' => 'Total Publikasi', 'value' => number_format($totalPublikasi, 0, ',', '.')],
    ['title' => 'Publikasi Tahun Ini', 'value' => number_format($publikasiTahunIni, 0, ',', '.')],
    ['title' => 'Total Dilihat', 'value' => number_format($totalDilihat, 0, ',', '.')],
    ['title' => 'Foto Galeri Kegiatan', 'value' => number_format($totalGaleri, 0, ',', '.')],
];

// ---- Indikator BPS (opsional) ----
// Dikonfigurasi lewat config/bps_indicators.php. Kalau kosong atau API key
// belum diisi, bagian ini otomatis disembunyikan (lihat pengecekan
// $bpsIndicatorCards/$bpsTrendCard di bawah), dashboard tidak akan rusak.
$bpsIndicatorConfigs = require __DIR__ . '/config/bps_indicators.php';
$bpsIndicatorCards = [];
$bpsTrendCard = null; // cuma 1 grafik tren ditampilkan, biar dashboard tidak penuh

foreach ($bpsIndicatorConfigs as $cfg) {
    $varId = trim((string) ($cfg['var_id'] ?? ''));
    // Cuma minta tren panjang (lebih dari 1 jendela 2-tahunan) untuk
    // indikator yang benar-benar akan ditampilkan sebagai grafik -
    // supaya tidak boros request BPS untuk kartu angka tunggal biasa.
    $maxYears = !empty($cfg['show_trend']) ? 6 : 2;

    if ($varId !== '') {
        // Cara B: var_id presisi, hasil pencarian manual admin.
        $result = bpsFetchIndicatorCached(
            BPS_DOMAIN,
            $varId,
            isset($cfg['vervar_id']) ? (string) $cfg['vervar_id'] : null,
            isset($cfg['turvar_id']) ? (string) $cfg['turvar_id'] : null,
            $cfg['title'] ?? '',
            $cfg['unit'] ?? '',
            $maxYears
        );
    } else {
        // Cara A: auto-discovery lewat kata kunci.
        $result = bpsFetchIndicatorByKeywordCached(
            BPS_DOMAIN,
            (string) ($cfg['keyword'] ?? ''),
            $cfg['title'] ?? '',
            $cfg['unit'] ?? '',
            $maxYears
        );
    }

    $card = [
        'title'       => $cfg['title'] ?? $result['title'],
        'unit'        => $cfg['unit'] ?? $result['unit'],
        'ok'          => $result['ok'],
        'error'       => $result['error'],
        'value'       => $result['latestValue'],
        'year'        => $result['latestYear'],
        'trend'       => $result['trend'],
        'vervarLabel' => $result['vervarLabel'] ?? '',
        'turvarLabel' => $result['turvarLabel'] ?? '',
    ];

    if (!empty($cfg['show_trend']) && $bpsTrendCard === null && $result['ok'] && count($result['trend']) >= 2) {
        $bpsTrendCard = $card;
    } else {
        $bpsIndicatorCards[] = $card;
    }
}

/**
 * Render grafik tren sederhana (line chart) sebagai SVG, dibuat langsung
 * di server dari data BPS - tidak butuh library chart JS eksternal.
 */
function renderBpsTrendSvg(array $trend, int $width = 560, int $height = 220): string
{
    $points = array_map(fn($p) => (float) str_replace(',', '.', (string) $p['value']), $trend);
    $labels = array_map(fn($p) => (string) $p['year'], $trend);

    $min = min($points);
    $max = max($points);
    $range = ($max - $min) ?: 1; // hindari pembagian dengan nol kalau semua nilai sama

    $padding = 30;
    $chartW  = $width - ($padding * 2);
    $chartH  = $height - ($padding * 2);
    $count   = count($points);

    $coords = [];
    foreach ($points as $i => $val) {
        $x = $padding + ($count > 1 ? ($i / ($count - 1)) * $chartW : $chartW / 2);
        $y = $padding + $chartH - (($val - $min) / $range) * $chartH;
        $coords[] = [$x, $y];
    }

    $polylinePoints = implode(' ', array_map(fn($c) => round($c[0], 1) . ',' . round($c[1], 1), $coords));

    $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:auto;">';
    $svg .= '<polyline fill="none" stroke="#034f84" stroke-width="3" points="' . htmlspecialchars($polylinePoints) . '" />';

    foreach ($coords as $i => [$x, $y]) {
        $svg .= '<circle cx="' . round($x, 1) . '" cy="' . round($y, 1) . '" r="4" fill="#002b6a" />';
        $svg .= '<text x="' . round($x, 1) . '" y="' . ($height - 8) . '" font-size="11" fill="#666" text-anchor="middle">' . htmlspecialchars($labels[$i]) . '</text>';
        $svg .= '<text x="' . round($x, 1) . '" y="' . round($y - 10, 1) . '" font-size="11" fill="#002b6a" text-anchor="middle" font-weight="bold">' . htmlspecialchars(number_format($points[$i], 2, ',', '.')) . '</text>';
    }

    $svg .= '</svg>';
    return $svg;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BPS Sumatera Utara - Dashboard</title>
  <link rel="icon" href="assets/img/logo_(1)_1643969039217.png" type="image/png" />
  <link rel="stylesheet" href="assets/css/myCSS2.css" />
  <style>
    .dashboard-hero {
      background: linear-gradient(135deg, #002b6a 0%, #034f84 100%);
      color: white;
      padding: 60px 20px;
      text-align: center;
      border-radius: 8px;
      margin-top: 20px;
      margin-bottom: 50px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .dashboard-hero h1 {
      margin: 0 0 10px 0;
      font-size: 28px;
    }

    .dashboard-hero p {
      margin: 0;
      font-size: 16px;
      opacity: 0.9;
    }

    .widget-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: -30px;
    }

    .widget-card {
      background: white;
      border-radius: 10px;
      padding: 25px 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .widget-card:hover {
      transform: translateY(-5px);
    }

    .widget-value {
      font-size: 32px;
      font-weight: bold;
      color: #002b6a;
      margin: 10px 0;
    }

    .widget-title {
      color: #666;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: bold;
    }

    .bps-section {
      margin-top: 50px;
    }

    .bps-section h2 {
      color: #002b6a;
      font-size: 20px;
      margin-bottom: 5px;
    }

    .bps-section .bps-subtitle {
      color: #777;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .bps-indicator-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .bps-indicator-card {
      background: white;
      border-radius: 10px;
      padding: 22px 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      border-left: 4px solid #034f84;
    }

    .bps-indicator-card .bps-title {
      color: #555;
      font-size: 13px;
      font-weight: bold;
      margin-bottom: 8px;
    }

    .bps-indicator-card .bps-value {
      font-size: 30px;
      font-weight: bold;
      color: #002b6a;
    }

    .bps-indicator-card .bps-unit {
      font-size: 14px;
      color: #888;
      font-weight: normal;
      margin-left: 4px;
    }

    .bps-indicator-card .bps-meta {
      font-size: 12px;
      color: #999;
      margin-top: 6px;
    }

    .bps-indicator-card.bps-error {
      border-left-color: #e74c3c;
      color: #c0392b;
      font-size: 13px;
    }

    .bps-trend-card {
      background: white;
      border-radius: 10px;
      padding: 25px 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .bps-trend-card h3 {
      color: #002b6a;
      font-size: 16px;
      margin: 0 0 15px 0;
    }
  </style>
</head>

<body>
  <header>
    <img src="https://ppid.bps.go.id/upload/img/logo_(1)_1643969039217.png" alt="Logo Web" onerror="this.src = 'assets/img/logo_(1)_1643969039217.png'" />
    <div class="judulweb">BADAN PUSAT STATISTIK<br />PROVINSI SUMATERA UTARA</div>
    <nav>
      <a href="index.php" class="active">Home</a>
      <div class="nav-dropdown">
        <button class="nav-dropbtn">Data & Publikasi ▾</button>
        <div class="nav-dropdown-content">
          <a href="modules/publikasi/page09A.php">Daftar Publikasi</a>
          <a href="modules/publikasi/berita.php">Berita BPS</a>
          <a href="modules/publikasi/katalog.php">Katalog Data</a>
        </div>
      </div>
      <?php if (isAdmin()): ?>
        <a href="modules/publikasi/page09C.php">Tambah Publikasi</a>
      <?php endif; ?>
      <a href="modules/galeri/page09G.php">Galeri Kegiatan</a>

      <div class="profile-dropdown">
        <svg class="profile-icon" viewBox="0 0 24 24" fill="white">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
        </svg>
        <div class="dropdown-content">
          <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
            <span>role: <?= htmlspecialchars($_SESSION['role']) ?></span>
          </div>
          <a href="modules/auth/logout.php" class="btn-logout">logout</a>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <div class="dashboard-hero">
      <h1>Portal Data & Publikasi Statistik</h1>
      <p>Indikator Strategis Terkini Provinsi Sumatera Utara</p>
    </div>

    <div class="widget-container" id="api-widgets">
      <?php foreach ($dashboardWidgets as $widget): ?>
        <div class="widget-card">
          <div class="widget-title"><?= htmlspecialchars($widget['title']) ?></div>
          <div class="widget-value"><?= htmlspecialchars($widget['value']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($bpsIndicatorCards) || $bpsTrendCard !== null): ?>
      <section class="bps-section">
        <h2>Indikator Statistik BPS Provinsi Sumatera Utara</h2>
        <p class="bps-subtitle">Data diambil langsung dari Web API BPS (webapi.bps.go.id), diperbarui otomatis setiap 1 jam.</p>

        <?php if (!empty($bpsIndicatorCards)): ?>
          <div class="bps-indicator-grid">
            <?php foreach ($bpsIndicatorCards as $card): ?>
              <?php if ($card['ok']): ?>
                <div class="bps-indicator-card">
                  <div class="bps-title"><?= htmlspecialchars($card['title']) ?></div>
                  <div class="bps-value">
                    <?= htmlspecialchars($card['value']) ?><?php if ($card['unit']): ?><span class="bps-unit"><?= htmlspecialchars($card['unit']) ?></span><?php endif; ?>
                  </div>
                  <div class="bps-meta">
                    Tahun <?= htmlspecialchars($card['year']) ?>
                    <?php if ($card['vervarLabel'] && $card['vervarLabel'] !== 'Sumatera Utara'): ?>
                      &middot; <?= htmlspecialchars($card['vervarLabel']) ?>
                    <?php endif; ?>
                    <?php if ($card['turvarLabel'] && strtolower($card['turvarLabel']) !== 'total'): ?>
                      &middot; <?= htmlspecialchars($card['turvarLabel']) ?>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="bps-indicator-card bps-error">
                  <div class="bps-title"><?= htmlspecialchars($card['title'] ?: 'Indikator BPS') ?></div>
                  Data tidak tersedia (<?= htmlspecialchars($card['error']) ?>)
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($bpsTrendCard !== null): ?>
          <div class="bps-trend-card">
            <h3><?= htmlspecialchars($bpsTrendCard['title']) ?><?php if ($bpsTrendCard['unit']): ?> (<?= htmlspecialchars($bpsTrendCard['unit']) ?>)<?php endif; ?></h3>
            <?= renderBpsTrendSvg($bpsTrendCard['trend']) ?>
          </div>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <!--
        Belum ada indikator BPS yang dikonfigurasi (atau API key belum
        diisi), jadi bagian ini disembunyikan daripada menampilkan kartu
        kosong/error ke semua user. Isi config/bps_indicators.php untuk
        mengaktifkan - lihat komentar di file itu untuk caranya, atau
        gunakan modules/publikasi/bps_variable_search.php (admin-only)
        untuk mencari var_id yang benar.
      -->
    <?php endif; ?>
  </main>

  <footer>
    <div class="footer-main">
      <div class="footer-logo">
        <img
          src="https://ppid.bps.go.id/upload/img/logo_(1)_1643969039217.png"
          alt="Logo BPS"
          onerror="this.src = '../../assets/img/logo_(1)_1643969039217.png'" />
        <span>BADAN PUSAT STATISTIK</span>
      </div>
      <div class="footer-cols">
        <div class="footer-col footer-info">
          <p>
            Badan Pusat Statistik Provinsi Sumatera Utara<br />
            Jl. Asrama No. 179 Medan 20123 Indonesia<br />
            Telp (62-61) 8452343<br />
            Faks (62-61) 8452773<br />
            Mailbox : pst1200@bps.go.id
          </p>
          <img
            id="berakhlak"
            src="https://sumut.bps.go.id/_next/image?url=https%3A%2F%2Fweb-api.bps.go.id%2Fcover.php%3Ff%3DEYdggRLWtt0DRl%2FoNNVbSGM0U1RrVDVtNUlXSWpBS3J0SXpLbUpNUXhXcnkzMUN5aE4rZDIxL0pGeDg5VXVaOGJGdHErblhTdk8ydmJIR2lKWHUxaHExTzkvZWdNaWRSUkJvY3V3PT0%3D&w=3840&q=75"
            alt="BerAKHLAK"
            onerror="this.src = '../../assets/img/berakhlak.webp'" />
          <div class="tigaopsi">
            <a
              href="https://manual-website-bps.readthedocs.io/"
              target="_blank">Manual</a>
            <a href="https://sumut.bps.go.id/id/term-of-use" target="_blank">S&K</a>
            <a href="https://sumut.bps.go.id/id/tautan" target="_blank">Daftar Tautan</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Tentang Kami</h4>
          <ul>
            <li>
              <a
                href="https://ppid.bps.go.id/app/konten/1200/Profil-BPS.html?_gl=1*1cldsba*_ga*NjQ4ODE2NDI5LjE3NDE3OTI0NDE.*_ga_XXTTVXWHDB*czE3Nzc3MzQwNDgkbzE2JGcwJHQxNzc3NzM0MDQ4JGo2MCRsMCRoMA.."
                target="_blank">Profil BPS</a>
            </li>
            <li>
              <a
                href="https://ppid.bps.go.id/?mfd=1200&_gl=1*hh7m93*_ga*NjQ4ODE2NDI5LjE3NDE3OTI0NDE.*_ga_XXTTVXWHDB*czE3Nzc3MzQwNDgkbzE2JGcwJHQxNzc3NzM0MDQ4JGo2MCRsMCRoMA.."
                target="_blank">PPID</a>
            </li>
            <li>
              <a
                href="https://ppid.bps.go.id/app/konten/0000/Layanan-BPS.html?_gl=1*hh7m93*_ga*NjQ4ODE2NDI5LjE3NDE3OTI0NDE.*_ga_XXTTVXWHDB*czE3Nzc3MzQwNDgkbzE2JGcwJHQxNzc3NzM0MDQ4JGo2MCRsMCRoMA..#pills-3"
                target="_blank">Kebijakan Diseminasi</a>
            </li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Tautan Lainnya</h4>
          <ul>
            <li>
              <a href="https://www.aseanstats.org/" target="_blank">ASEAN Stats</a>
            </li>
            <li>
              <a href="https://rb.bps.go.id/" target="_blank">Reformasi Birokrasi</a>
            </li>
            <li>
              <a href="https://lpse.bps.go.id/" target="_blank">Layanan Pengadaan Secara Elektronik</a>
            </li>
            <li>
              <a href="https://stis.ac.id/" target="_blank">Politeknik Statistika STIS</a>
            </li>
            <li>
              <a href="https://pusdiklat.bps.go.id/" target="_blank">Pusdiklat BPS</a>
            </li>
            <li>
              <a href="https://jdih.bps.go.id/" target="_blank">JDIH BPS</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>
        Copyright &copy; 2026 Politeknik Statistika STIS &nbsp;|&nbsp; Created
        by Muhammad Hafidz Ar Rasyid Hutagalung (222413683@stis.ac.id)
      </p>
    </div>
  </footer>

  <!--
    Catatan: widget di atas sekarang dirender langsung dari PHP (data
    publikasi/galeri lokal), jadi tidak perlu lagi diisi lewat JS setelah
    halaman dimuat. Kalau nanti ingin menampilkan indikator makro BPS
    (inflasi, pertumbuhan ekonomi, dsb.), buat proxy endpoint PHP baru
    seperti modules/publikasi/bps_search.php - jangan panggil API BPS
    langsung dari browser karena API key akan terekspos di JS publik.
  -->
</body>

</html>