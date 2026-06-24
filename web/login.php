<?php
require_once __DIR__ . '/../app/init.php';
if (current_user()) redirect($_SESSION['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login_user($username, $password)) {
        redirect($_SESSION['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
    }
    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login PABETAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=9') ?>">
</head>
<body class="login-body auth-page" data-sound-context="home">
<div class="auth-bg-bubble bubble-a"></div>
<div class="auth-bg-bubble bubble-b"></div>
<div class="container auth-container py-4 py-md-5">
    <div class="auth-shell">
        <section class="auth-hero-panel">
            <img src="<?= url(SCHOOL_LOGO) ?>" alt="Logo" class="auth-logo mb-3">
            <span class="auth-badge">Papan Belajar Tangga Satuan</span>
            <h1>Masuk ke PABETAS</h1>
            <p>Belajar satuan panjang dengan papan tabel, tangga satuan, kuis, tim, dan live game yang ramah anak SD.</p>
            <div class="auth-feature-list">
                <div>🎵 Musik belajar bisa dinyalakan atau dimatikan</div>
                <div>🎮 Mode siswa dan guru terpisah</div>
                <div>📊 Guru bisa melihat nilai dan laporan</div>
            </div>
        </section>
        <section class="auth-form-panel">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="mb-1">Login Akun</h2>
                    <p class="text-muted mb-0">Gunakan username dan password yang sudah terdaftar.</p>
                </div>
                <button type="button" class="btn btn-outline-primary rounded-pill" id="soundToggle">🔊 Musik</button>
            </div>
            <?php if (isset($_GET['timeout'])): ?><div class="alert alert-warning">Sesi habis. Silakan login ulang.</div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="auth-form">
                <?= csrf_field() ?>
                <label class="form-label fw-bold">Username</label>
                <input class="form-control form-control-lg mb-3" name="username" required autofocus placeholder="Contoh: siswa">
                <label class="form-label fw-bold">Password</label>
                <input type="password" class="form-control form-control-lg mb-4" name="password" required placeholder="Masukkan password">
                <button class="btn btn-primary btn-lg w-100 rounded-pill">Masuk Belajar</button>
            </form>
            <div class="auth-actions mt-3">
                <a class="btn btn-light rounded-pill" href="<?= url('register.php?role=murid') ?>">Daftar Murid</a>
                <a class="btn btn-warning rounded-pill" href="<?= url('register.php?role=guru') ?>">Daftar Guru</a>
            </div>
            <div class="demo-box mt-4">
                <b>Akun demo</b><br>
                Guru: <code>guru/guru123</code> · Murid: <code>siswa/siswa123</code>
            </div>
        </section>
    </div>
</div>
<script src="<?= url('assets/js/sounds.js?v=9') ?>"></script>
</body>
</html>
