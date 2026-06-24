<?php
// Salin file ini menjadi config.php, lalu sesuaikan koneksi database.
define('APP_NAME', 'PABETAS');
define('APP_ENV', 'local');
define('APP_DEBUG', true);
define('APP_URL', ''); // contoh: /pabetas_advanced_system/public jika pakai XAMPP subfolder

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'pabetas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SCHOOL_NAME', 'SD Contoh Nusantara');
define('SCHOOL_LOGO', 'assets/img/logo-pabetas.svg');
define('SESSION_TIMEOUT', 2700); // 45 menit

// Batas ketuntasan minimum untuk alur Academic Evaluation
define('REMEDIAL_MIN_SCORE', 70);
define('ACADEMIC_TEST_LIMIT', 5);
