<?php
$db_hostname = "localhost";
$db_database = "projekpbw";
$db_username = "root";
$db_password = "";
$db_charset  = "utf8mb4";

$dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
$opt = array(
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
);

try {
    $pdo = new PDO($dsn, $db_username, $db_password, $opt);
} catch (PDOException $e) {
    // Jangan tampilkan detail error koneksi DB ke pengunjung (bisa membocorkan
    // info struktur/kredensial), cukup dicatat di server log.
    error_log('PDO Connection Error: ' . $e->getMessage());
    http_response_code(500);
    exit('Maaf, sedang terjadi gangguan pada server. Silakan coba lagi nanti.');
}
