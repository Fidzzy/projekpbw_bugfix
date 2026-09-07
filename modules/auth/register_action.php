<?php
require_once '../../config/dbconn.php';

try {
    $nama        = trim($_POST['nama'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $konfirmasi  = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $username === '' || $password === '' || $konfirmasi === '') {
        header('Location: register.php?status=gagal&pesan=' . urlencode('Semua field wajib diisi.'));
        exit;
    }

    if (strlen($username) < 4) {
        header('Location: register.php?status=gagal&pesan=' . urlencode('Username minimal 4 karakter.'));
        exit;
    }

    if (strlen($password) < 6) {
        header('Location: register.php?status=gagal&pesan=' . urlencode('Password minimal 6 karakter.'));
        exit;
    }

    if ($password !== $konfirmasi) {
        header('Location: register.php?status=gagal&pesan=' . urlencode('Konfirmasi password tidak cocok.'));
        exit;
    }

    // Cek username sudah dipakai atau belum
    /** @var PDO $pdo */
    $cek = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $cek->execute([':username' => $username]);
    if ($cek->fetch()) {
        header('Location: register.php?status=gagal&pesan=' . urlencode('Username sudah dipakai, pilih yang lain.'));
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (username, password, nama, role) VALUES (:username, :password, :nama, "user")');
    $stmt->execute([
        ':username' => $username,
        ':password' => $hash,
        ':nama'     => $nama,
    ]);

    header('Location: login.php?status=sukses&pesan=' . urlencode('Pendaftaran berhasil, silakan login.'));
    exit;
} catch (PDOException $e) {
    header('Location: register.php?status=gagal&pesan=' . urlencode('Terjadi kesalahan sistem, coba lagi.'));
    exit;
}
