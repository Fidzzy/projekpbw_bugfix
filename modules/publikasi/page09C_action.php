<?php
require_once '../../config/dbconn.php';
require_once '../../includes/auth.php';
requireAdmin();
requireValidCsrf($_POST['csrf_token'] ?? null);

try {
    $no            = trim($_POST['nomor'] ?? '');
    $judul         = trim($_POST['judul'] ?? '');
    $tanggal_rilis = trim($_POST['tanggal'] ?? '');
    $link_publikasi = trim($_POST['link_publikasi'] ?? '');

    if ($no === '' || $judul === '' || $tanggal_rilis === '') {
        header("Location: page09C.php?status=gagal&pesan=" . urlencode("Nomor, Judul, dan Tanggal Rilis wajib diisi."));
        exit;
    }
    $namaFile = '';
    if (isset($_FILES['sampul']) && $_FILES['sampul']['error'] === UPLOAD_ERR_OK) {
        $namaFile        = basename($_FILES['sampul']['name']);
        $lokasiSementara = $_FILES['sampul']['tmp_name'];

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext        = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            header("Location: page09C.php?status=gagal&pesan=" . urlencode("Format file tidak didukung. Gunakan .jpg, .jpeg, .png, .gif, atau .webp"));
            exit;
        }

        // Cek isi file benar-benar gambar (bukan cuma nama filenya berekstensi
        // gambar) - cegah upload file berbahaya menyamar sebagai gambar.
        if (@getimagesize($lokasiSementara) === false) {
            header("Location: page09C.php?status=gagal&pesan=" . urlencode("File yang diunggah bukan file gambar yang valid."));
            exit;
        }

        $dirUpload = __DIR__ . "/../../assets/img/";
        if (!is_dir($dirUpload)) {
            mkdir($dirUpload, 0755, true);
        }

        if (!move_uploaded_file($lokasiSementara, $dirUpload . $namaFile)) {
            header("Location: page09C.php?status=gagal&pesan=" . urlencode("Gagal mengupload file sampul."));
            exit;
        }
    } elseif (!empty($_POST['sampul_otomatis'])) {
        $namaFileOtomatis = basename($_POST['sampul_otomatis']);
        $dirUpload        = __DIR__ . "/../../assets/img/";

        if (file_exists($dirUpload . $namaFileOtomatis)) {
            $namaFile = $namaFileOtomatis;
        }
    }

    $sql  = "INSERT INTO publikasi (no, judul, tanggal_rilis, link_publikasi, sampul) VALUES (:no, :judul, :tanggal_rilis, :link_publikasi, :sampul)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':no'            => $no,
        ':judul'         => $judul,
        ':tanggal_rilis' => $tanggal_rilis,
        ':link_publikasi' => $link_publikasi,
        ':sampul'        => $namaFile,
    ]);

    $pdo = null;

    header("Location: page09C.php?status=sukses&judul=" . urlencode($judul));
    exit;
} catch (PDOException $e) {
    error_log('PDO Error (page09C_action.php): ' . $e->getMessage());
    header("Location: page09C.php?status=gagal&pesan=" . urlencode("Terjadi kesalahan sistem, silakan coba lagi."));
    exit;
}
