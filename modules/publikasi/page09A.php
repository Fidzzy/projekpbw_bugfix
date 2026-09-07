<?php
include '../../config/dbconn.php';
require_once '../../includes/auth.php';
requireLogin();

// Baca parameter filter dari query string (?q=&judul_only=&tahun=&urutan=) 
$formSubmitted = isset($_GET['filtered']);

$q      = trim($_GET['q'] ?? '');
$tahun  = trim($_GET['tahun'] ?? '');
$urutan = $_GET['urutan'] ?? 'terbaru';

// Checkbox GET tidak terkirim kalau di-uncheck, jadi status default (belum
// pernah submit form) dibedakan dari "sengaja di-uncheck lalu submit".
$judulOnly = $formSubmitted ? isset($_GET['judul_only']) : true;

if (!in_array($urutan, ['terbaru', 'terlama', 'terpopuler'], true)) {
  $urutan = 'terbaru';
}

// Bangun query dinamis (tetap prepared statement, aman dari SQL injection)
$where  = [];
$params = [];

if ($q !== '') {
  if ($judulOnly) {
    $where[] = 'judul LIKE :q';
  } else {
    $where[] = '(judul LIKE :q OR link_publikasi LIKE :q)';
  }
  $params[':q'] = '%' . $q . '%';
}

if ($tahun !== '' && ctype_digit($tahun)) {
  $where[] = 'YEAR(tanggal_rilis) = :tahun';
  $params[':tahun'] = $tahun;
}

$sql = 'SELECT * FROM publikasi';
if (!empty($where)) {
  $sql .= ' WHERE ' . implode(' AND ', $where);
}

switch ($urutan) {
  case 'terlama':
    $sql .= ' ORDER BY tanggal_rilis ASC';
    break;
  case 'terpopuler':
    $sql .= ' ORDER BY dilihat DESC, tanggal_rilis DESC';
    break;
  default: // terbaru
    $sql .= ' ORDER BY tanggal_rilis DESC';
    break;
}

/** @var PDO $pdo */
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll();

// Daftar tahun yang tersedia untuk dropdown (dari data yang ada)
$tahunList = $pdo->query('SELECT DISTINCT YEAR(tanggal_rilis) AS thn FROM publikasi ORDER BY thn DESC')
  ->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Daftar Publikasi BPS Sumatera Utara</title>
  <link rel="icon" href="../../assets/img/logo_(1)_1643969039217.png" type="image/png" />
  <link rel="stylesheet" href="../../assets/css/myCSS2.css" />
</head>

<body>
  <header>
    <img
      src="https://ppid.bps.go.id/upload/img/logo_(1)_1643969039217.png"
      alt="Logo Web"
      onerror="this.src = '../../assets/img/logo_(1)_1643969039217.png'" />
    <div class="judulweb">
      BADAN PUSAT STATISTIK<br />
      PROVINSI SUMATERA UTARA
    </div>
    <nav>
      <a href="../../index.php">Home</a>

      <div class="nav-dropdown">
        <button class="nav-dropbtn active">Data & Publikasi ▾</button>
        <div class="nav-dropdown-content">
          <a href="page09A.php" style="background-color: #f2f2f2; font-weight: bold;">Daftar Publikasi</a>
          <a href="berita.php">Berita BPS</a>
          <a href="katalog.php">Katalog Data</a>
        </div>
      </div>

      <?php if (isAdmin()): ?>
        <a href="page09C.php">Tambah Publikasi</a>
      <?php endif; ?>
      <a href="../galeri/page09G.php">Galeri Kegiatan</a>
      <!-- <span style="color:#fff; margin-left:10px;">
        <?= htmlspecialchars($_SESSION['nama']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
      </span>
      <a href="../auth/logout.php">Logout</a> -->
      <div class="profile-dropdown">
        <!-- Ikon profil berbasis SVG agar tidak perlu mengunduh gambar eksternal -->
        <svg class="profile-icon" viewBox="0 0 24 24" fill="white">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
        </svg>

        <div class="dropdown-content">
          <div class="user-info">
            <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>
            <span>role: <?= htmlspecialchars($_SESSION['role']) ?></span>
          </div>
          <a href="../auth/logout.php" class="btn-logout">logout</a>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <h2 style="text-align: center; margin-bottom: 20px; color: #333">
      Daftar Publikasi BPS Provinsi Sumatera Utara
    </h2>

    <div class="publikasi-layout">
      <aside class="filter-panel">
        <h3 class="filter-panel-title">Filter Publikasi</h3>
        <div class="filter-card">
          <form method="get" action="page09A.php">
            <input type="hidden" name="filtered" value="1" />

            <label class="filter-label" for="q">Kata Kunci</label>
            <input
              type="text"
              id="q"
              name="q"
              placeholder="Masukkan kata kunci..."
              autocomplete="off"
              onkeyup="showHint(this.value)"
              value="<?= htmlspecialchars($q) ?>" />
            <!-- Kotak saran pencarian -->
            <div id="txtHint" style="font-size: 13px; color: #034f84; font-weight: bold; padding-top: 5px;"></div>

            <label class="filter-checkbox-row" for="judul_only">
              <input
                type="checkbox"
                id="judul_only"
                name="judul_only"
                value="1"
                <?= $judulOnly ? 'checked' : '' ?> />
              Cari hanya berdasarkan judul
            </label>

            <label class="filter-label" for="tahun">Tahun</label>
            <select id="tahun" name="tahun">
              <option value="">Semua Tahun</option>
              <?php foreach ($tahunList as $t): ?>
                <option value="<?= (int) $t ?>" <?= ($tahun !== '' && (int) $tahun === (int) $t) ? 'selected' : '' ?>>
                  <?= (int) $t ?>
                </option>
              <?php endforeach; ?>
            </select>

            <label class="filter-label" for="urutan">Urutkan Berdasarkan</label>
            <select id="urutan" name="urutan">
              <option value="terbaru" <?= $urutan === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
              <option value="terlama" <?= $urutan === 'terlama' ? 'selected' : '' ?>>Terlama</option>
              <option value="terpopuler" <?= $urutan === 'terpopuler' ? 'selected' : '' ?>>Terpopuler</option>
            </select>

            <button type="submit" class="filter-submit">Tampilkan</button>
          </form>
        </div>
      </aside>

      <div class="publikasi-content">
        <?php if (empty($result)): ?>
          <p class="hasil-kosong">Tidak ada publikasi yang cocok dengan filter ini.</p>
        <?php else: ?>
          <div class="tabel-publikasi">
            <table class="data-tabel">
              <tr>
                <th width="50">No</th>
                <th>Judul</th>
                <th>Tanggal Rilis</th>
                <th width="70">Dilihat</th>
                <th width="80">Sampul</th>
                <?php if (isAdmin()): ?>
                  <th width="80">Aksi</th>
                <?php endif; ?>
              </tr>
              <?php
              $nomorUrut = 1;
              foreach ($result as $row) {

                $link = $row["link_publikasi"];

                echo "<tr>\n";
                echo "  <td align='center'>" . $nomorUrut++ . "</td>\n";
                echo "  <td><a href='" . htmlspecialchars($link) . "' target='_blank' class='judul-link' data-no='" . (int) $row["no"] . "' style='color: #034f84; text-decoration: none; font-weight: bold;' onmouseover=\"this.style.textDecoration='underline'\" onmouseout=\"this.style.textDecoration='none'\">" . htmlspecialchars($row["judul"]) . "</a></td>\n";

                echo "  <td>" . htmlspecialchars($row["tanggal_rilis"]) . "</td>\n";
                echo "  <td align='center'>" . (int) $row["dilihat"] . "</td>\n";
                echo "  <td align='center'> <img src='../../assets/img/" . htmlspecialchars($row["sampul"]) . "' alt='No Image' width='70px'> </td>\n";

                if (isAdmin()) {
                  echo "  <td align='center'>\n";
                  echo "    <div style='display:grid; justify-content:center; gap:15px;'>\n";
                  echo "      <a href='page09E.php?no=" . urlencode($row["no"]) . "&judul=" . urlencode($row["judul"]) . "&tanggal=" . urlencode($row["tanggal_rilis"]) . "&link=" . urlencode($row["link_publikasi"]) . "&sampul=" . urlencode($row["sampul"]) . "' style='display:inline-flex; align-items:center; gap:5px; text-decoration:none; color:#034f84; font-weight:bold;'>\n";
                  echo "        <img src='../../assets/img/edit.png' style='width:15px;height:15px;' alt='Edit'> Edit\n";
                  echo "      </a>\n";
                  echo "      <form method='post' action='page09F.php' onsubmit=\"return confirm('Yakin ingin menghapus?');\" style='margin:0;'>\n";
                  echo "        <input type='hidden' name='csrf_token' value='" . htmlspecialchars(csrfToken()) . "'>\n";
                  echo "        <input type='hidden' name='no' value='" . htmlspecialchars($row["no"]) . "'>\n";
                  echo "        <input type='hidden' name='sampul' value='" . htmlspecialchars($row["sampul"]) . "'>\n";
                  echo "        <button type='submit' style='display:inline-flex; align-items:center; gap:5px; background:none; border:none; padding:0; cursor:pointer; text-decoration:none; color:#d9534f; font-weight:bold; font:inherit;'>\n";
                  echo "          <img src='../../assets/img/delete.png' style='width:15px;height:15px;' alt='Hapus'> Hapus\n";
                  echo "        </button>\n";
                  echo "      </form>\n";
                  echo "    </div>\n";
                  echo "  </td>\n";
                }
                echo "</tr>\n";
              }
              ?>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>


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
  <script src="../../assets/js/validasiForm.js"></script>
  <script>
    document.querySelectorAll('.judul-link').forEach(function(link) {
      link.addEventListener('click', function() {
        var no = this.getAttribute('data-no');
        fetch('track_view.php?no=' + encodeURIComponent(no), {
          method: 'POST',
          keepalive: true
        }).catch(function() {});
      });
    });
  </script>
  <script src="../../assets/js/page11A_suggestion.js"></script>
</body>

</html>