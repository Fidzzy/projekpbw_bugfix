<?php
/**
 * Konfigurasi API BPS.
 *
 * PENTING - JANGAN commit API key asli ke repo publik.
 * Isi lewat environment variable BPS_API_KEY (mis. di .env / server config),
 * atau kalau terpaksa untuk development lokal, ganti nilai fallback di bawah
 * dan pastikan config/bps_api.php di-.gitignore.
 *
 * Key lama yang sebelumnya ter-commit di file ini sudah pernah terekspos di
 * riwayat git repo publik. Segera generate/rotate key baru di
 * https://webapi.bps.go.id/developer/ dan JANGAN pakai key lama lagi.
 */
define('BPS_API_KEY', getenv('BPS_API_KEY') ?: 'GANTI_DENGAN_API_KEY_ANDA');
define('BPS_DOMAIN', getenv('BPS_DOMAIN') ?: '1200');
define('BPS_API_BASE', 'https://webapi.bps.go.id/v1/api/');
