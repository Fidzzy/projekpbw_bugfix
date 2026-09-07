<?php require_once '../../includes/auth.php';
requireAdmin(); ?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Form Publikasi Baru</title>
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
        <button class="nav-dropbtn">Data & Publikasi ▾</button>
        <div class="nav-dropdown-content">
          <a href="page09A.php">Daftar Publikasi</a>
          <a href="berita.php">Berita BPS</a>
          <a href="katalog.php">Katalog Data</a>
        </div>
      </div>

      <a class="active" href="page09C.php">Tambah Publikasi</a>
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
    <h2 style="text-align: left; padding-left: 10%; color: #333">
      Form Tambah Publikasi Baru
    </h2>

    <?php if (!empty($_GET['status'])): ?>
      <?php if ($_GET['status'] === 'sukses'): ?>
        <p id="pesanSuksesDb" style="display:block; width:60%; margin:0 auto 15px auto; padding:12px 16px; background-color:#d4edda; border:1px solid #28a745; border-radius:4px; color:#155724; font-size:14px;">
          Publikasi <strong><?= htmlspecialchars($_GET['judul'] ?? '') ?></strong> berhasil ditambahkan!
        </p>

        <script>
          setTimeout(function() {
            var pesanSukses = document.getElementById("pesanSuksesDb");
            if (pesanSukses) {
              pesanSukses.style.transition = "opacity 0.6s";
              pesanSukses.style.opacity = "0";
              setTimeout(function() {
                pesanSukses.style.display = "none";
              }, 600);
            }
          }, 4000);
        </script>

      <?php elseif ($_GET['status'] === 'gagal'): ?>
        <p id="pesanErrorDb" style="display:block; width:60%; margin:0 auto 15px auto; padding:12px 16px; background-color:#fff3f3; border-radius:4px; color:#c0392b; font-size:14px;">
          <?= htmlspecialchars($_GET['pesan'] ?? 'Terjadi kesalahan.') ?>
        </p>
      <?php endif; ?>
    <?php endif; ?>

    <div style="width: 60%; margin: 0 auto 15px auto; text-align: right;">
      <button type="button" onclick="bukaModalBPS()" style="background-color: rgba(0, 43, 106, 0.70); color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background-color 0.2s;">
        Tarik Data Publikasi
      </button>
    </div>

    <!-- Modal pencarian publikasi BPS Pusat -->
    <div id="modalBPS" class="modal-bps-overlay" style="display:none;">
      <div class="modal-bps-box">
        <div class="modal-bps-header">
          <h3 style="margin:0;">Tarik Data dari BPS Pusat</h3>
          <button type="button" onclick="tutupModalBPS()" class="modal-bps-close">&times;</button>
        </div>

        <div class="modal-bps-body">
          <div style="display:flex; gap:8px; margin-bottom:14px;">
            <input
              type="text"
              id="bpsKeyword"
              placeholder="Cari judul publikasi... (mis. Sumatera Utara Dalam Angka)"
              style="flex:1; padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px;"
              onkeydown="if(event.key==='Enter'){event.preventDefault(); cariPublikasiBPS();}" />
            <button type="button" onclick="cariPublikasiBPS()" style="background-color:#034f84; color:#fff; border:none; padding:0 18px; border-radius:6px; cursor:pointer; font-weight:bold;">
              Cari
            </button>
          </div>

          <div id="bpsStatus" style="font-size:13px; color:#777; margin-bottom:10px;"></div>
          <div id="bpsHasil" class="modal-bps-hasil"></div>
        </div>
      </div>
    </div>

    <style>
      .modal-bps-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .modal-bps-box {
        background: #fff;
        width: 90%;
        max-width: 560px;
        max-height: 80vh;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      }

      .modal-bps-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
        background-color: #034f84;
        color: #fff;
      }

      .modal-bps-header h3 {
        color: #fff;
      }

      .modal-bps-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
      }

      .modal-bps-body {
        padding: 20px;
        overflow-y: auto;
      }

      .modal-bps-hasil {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      .bps-item {
        border: 1px solid #e2e2e2;
        border-radius: 6px;
        padding: 10px 14px;
        cursor: pointer;
        transition: background-color 0.15s, border-color 0.15s;
      }

      .bps-item:hover {
        background-color: #f0f6fb;
        border-color: #034f84;
      }

      .bps-item-judul {
        font-weight: bold;
        color: #034f84;
        font-size: 14px;
      }

      .bps-item-tanggal {
        font-size: 12px;
        color: #777;
        margin-top: 3px;
      }

      .bps-sampul-preview {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 8px;
      }

      .bps-sampul-preview img {
        border: 1px solid #ccc;
        border-radius: 4px;
      }
    </style>

    <script>
      function bukaModalBPS() {
        document.getElementById("modalBPS").style.display = "flex";
        document.getElementById("bpsKeyword").focus();
      }

      function tutupModalBPS() {
        document.getElementById("modalBPS").style.display = "none";
      }

      function cariPublikasiBPS() {
        var keyword = document.getElementById("bpsKeyword").value.trim();
        var statusEl = document.getElementById("bpsStatus");
        var hasilEl = document.getElementById("bpsHasil");

        if (keyword === "") {
          statusEl.textContent = "Masukkan kata kunci dulu.";
          return;
        }

        statusEl.textContent = "Mencari di BPS Pusat...";
        hasilEl.innerHTML = "";

        fetch("bps_search.php?keyword=" + encodeURIComponent(keyword))
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            statusEl.textContent = json.message || "";

            if (!json.success || !json.data || json.data.length === 0) {
              hasilEl.innerHTML = "";
              return;
            }

            hasilEl.innerHTML = "";
            json.data.forEach(function(item) {
              var div = document.createElement("div");
              div.className = "bps-item";
              div.innerHTML =
                '<div class="bps-item-judul"></div>' +
                '<div class="bps-item-tanggal"></div>';
              div.querySelector(".bps-item-judul").textContent = item.title;
              div.querySelector(".bps-item-tanggal").textContent = item.issued || "";
              div.addEventListener("click", function() {
                pilihPublikasiBPS(item.pub_id);
              });
              hasilEl.appendChild(div);
            });
          })
          .catch(function() {
            statusEl.textContent = "Gagal terhubung ke server. Coba lagi.";
          });
      }

      function pilihPublikasiBPS(pubId) {
        var statusEl = document.getElementById("bpsStatus");
        statusEl.textContent = "Mengambil detail publikasi...";

        fetch("bps_detail.php?pub_id=" + encodeURIComponent(pubId))
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json.success) {
              statusEl.textContent = json.message || "Gagal mengambil detail.";
              return;
            }

            var data = json.data;

            // Isi otomatis field form (Nomor tetap diisi manual oleh admin)
            document.getElementById("judul").value = data.title || "";
            if (data.rl_date) {
              document.getElementById("tanggal").value = data.rl_date;
            }
            if (data.pdf) {
              document.getElementById("link_publikasi").value = data.pdf;
            }

            // Sampul sudah otomatis diunduh ke server, tampilkan preview
            // dan simpan nama filenya lewat hidden field (tidak bisa
            // mengisi <input type="file"> langsung karena dibatasi browser).
            var previewArea = document.getElementById("bpsSampulPreview");
            var hiddenSampul = document.getElementById("sampul_otomatis");

            if (data.sampul) {
              hiddenSampul.value = data.sampul;
              previewArea.innerHTML =
                '<img src="../../assets/img/' + data.sampul + '" width="70" alt="Sampul otomatis">' +
                '<span style="font-size:13px; color:#28a745;">Sampul otomatis berhasil diunduh dari BPS Pusat.</span>';
            } else {
              hiddenSampul.value = "";
              previewArea.innerHTML =
                '<span style="font-size:13px; color:#e67e22;">Sampul tidak tersedia dari BPS Pusat, silakan upload manual.</span>';
            }

            // Tampilkan respons mentah dari BPS supaya kalau ada field yang
            // tidak terisi (title/tanggal/pdf/sampul kosong), bisa dicek
            // struktur JSON aslinya. Hapus blok ini setelah pemetaan field
            // dipastikan benar semua.
            /* if (data.raw || data.sampul_debug) {
              var debugBox = document.createElement("details");
              debugBox.style.marginTop = "12px";
              debugBox.style.fontSize = "12px";
              var summary = document.createElement("summary");
              summary.textContent = "🔍 Info debug (respons mentah BPS) — klik untuk lihat";
              summary.style.cursor = "pointer";
              summary.style.color = "#034f84";
              var pre = document.createElement("pre");
              pre.style.background = "#f5f5f5";
              pre.style.padding = "10px";
              pre.style.borderRadius = "6px";
              pre.style.overflowX = "auto";
              pre.style.maxHeight = "250px";
              pre.textContent = JSON.stringify({ data: data.raw, sampul_debug: data.sampul_debug }, null, 2);
              debugBox.appendChild(summary);
              debugBox.appendChild(pre);
              previewArea.appendChild(debugBox);
            } */

            tutupModalBPS();
          })
          .catch(function() {
            statusEl.textContent = "Gagal terhubung ke server. Coba lagi.";
          });
      }
    </script>

    <div class="kotak-form">
      <form
        name="formTambahPublikasi"
        action="page09C_action.php"
        method="post"
        enctype="multipart/form-data"
        onsubmit="return validate09C();">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>" />
        <input type="hidden" name="sampul_otomatis" id="sampul_otomatis" value="" />
        <table border="0" cellpadding="8" cellspacing="0">
          <tr>
            <td width="150"><label for="nomor">Nomor:</label></td>
            <td><input type="text" id="nomor" name="nomor" required /></td>
          </tr>
          <tr>
            <td><label for="judul">Judul:</label></td>
            <td><input type="text" id="judul" name="judul" required /></td>
          </tr>
          <tr>
            <td><label for="tanggal">Tanggal Rilis:</label></td>
            <td><input type="date" id="tanggal" name="tanggal" required /></td>
          </tr>
          <tr>
            <td><label for="link_publikasi">Link Publikasi:</label></td>
            <td><input type="url" id="link_publikasi" name="link_publikasi" placeholder="https://sumut.bps.go.id/..." required style="width: 100%; box-sizing: border-box;" /></td>
          </tr>
          <tr>
            <td><label for="sampul">Sampul:</label></td>
            <td>
              <input
                type="file"
                id="sampul"
                name="sampul"
                accept=".jpg,.jpeg,.png,.gif,.webp" />
              <br /><small style="color:#888;">Opsional. Format: .jpg, .jpeg, .png, .gif, .webp</small>
              <div id="bpsSampulPreview" class="bps-sampul-preview"></div>
            </td>
          </tr>
          <tr>
            <td></td>
            <td><input type="submit" value="Tambah" /></td>
          </tr>
        </table>
      </form>
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
            <a href="https://manual-website-bps.readthedocs.io/" target="_blank">Manual</a>
            <a href="https://sumut.bps.go.id/id/term-of-use" target="_blank">S&K</a>
            <a href="https://sumut.bps.go.id/id/tautan" target="_blank">Daftar Tautan</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Tentang Kami</h4>
          <ul>
            <li><a href="https://ppid.bps.go.id/app/konten/1200/Profil-BPS.html" target="_blank">Profil BPS</a></li>
            <li><a href="https://ppid.bps.go.id/?mfd=1200" target="_blank">PPID</a></li>
            <li><a href="https://ppid.bps.go.id/app/konten/0000/Layanan-BPS.html#pills-3" target="_blank">Kebijakan Diseminasi</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Tautan Lainnya</h4>
          <ul>
            <li><a href="https://www.aseanstats.org/" target="_blank">ASEAN Stats</a></li>
            <li><a href="https://rb.bps.go.id/" target="_blank">Reformasi Birokrasi</a></li>
            <li><a href="https://lpse.bps.go.id/" target="_blank">Layanan Pengadaan Secara Elektronik</a></li>
            <li><a href="https://stis.ac.id/" target="_blank">Politeknik Statistika STIS</a></li>
            <li><a href="https://pusdiklat.bps.go.id/" target="_blank">Pusdiklat BPS</a></li>
            <li><a href="https://jdih.bps.go.id/" target="_blank">JDIH BPS</a></li>
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
    document.addEventListener("DOMContentLoaded", function() {
      var inputSampul = document.getElementById("sampul");
      if (inputSampul) {
        inputSampul.addEventListener("change", function() {
          if (this.files.length > 0) {
            document.getElementById("sampul_otomatis").value = "";
            document.getElementById("bpsSampulPreview").innerHTML = "";
          }
        });
      }
    });
  </script>
</body>

</html>