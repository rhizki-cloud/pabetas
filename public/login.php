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
    <style>
        body.login-body.auth-page{overflow:auto!important;min-height:100vh;background:radial-gradient(circle at 8% 10%,#dbeafe 0,#f0f9ff 26%,transparent 45%),radial-gradient(circle at 90% 8%,#fef3c7 0,#fff7ed 24%,transparent 42%),linear-gradient(135deg,#eff6ff 0%,#fff7ed 100%);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .auth-container{min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;z-index:2}.auth-shell{width:min(1080px,100%);display:grid;grid-template-columns:1.05fr .95fr;background:rgba(255,255,255,.84);border:1px solid rgba(255,255,255,.7);box-shadow:0 28px 90px rgba(15,23,42,.16);border-radius:38px;overflow:hidden;backdrop-filter:blur(16px)}
        .auth-hero-panel{position:relative;min-height:560px;padding:42px;display:flex;flex-direction:column;justify-content:center;background:linear-gradient(135deg,#1d4ed8,#0ea5e9 52%,#22c55e);color:#fff;overflow:hidden}.auth-hero-panel:before{content:"";position:absolute;inset:auto -70px -90px auto;width:270px;height:270px;background:rgba(255,255,255,.18);border-radius:50%}.auth-hero-panel:after{content:"♪";position:absolute;right:46px;top:36px;font-size:5rem;line-height:1;color:rgba(255,255,255,.18);font-weight:1000}
        .auth-logo{width:92px;height:92px;background:#fff;border-radius:24px;padding:8px;box-shadow:0 14px 35px rgba(15,23,42,.18);object-fit:contain}.auth-badge{display:inline-flex;width:max-content;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);border-radius:999px;padding:.55rem .9rem;font-weight:900;margin-bottom:1rem}.auth-hero-panel h1{font-size:clamp(2.5rem,5vw,4.5rem);font-weight:1000;line-height:1.02;margin-bottom:1rem}.auth-hero-panel p{font-size:1.15rem;line-height:1.65;max-width:560px}
        .auth-feature-list{display:grid;gap:.75rem;margin-top:1rem}.auth-feature-list div{background:rgba(255,255,255,.17);border:1px solid rgba(255,255,255,.25);border-radius:18px;padding:.9rem 1rem;font-weight:800}.auth-form-panel{padding:38px;display:flex;flex-direction:column;justify-content:center;background:#fff}.auth-form-panel h2{font-size:2.2rem;font-weight:1000;color:#0f172a}.auth-form .form-control{border-radius:18px;border:2px solid #e2e8f0}.auth-form .form-control:focus{border-color:#60a5fa;box-shadow:0 0 0 .25rem rgba(96,165,250,.2)}.auth-actions{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}.auth-actions .btn{font-weight:1000;padding:.85rem 1rem}.demo-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;padding:1rem;text-align:center;color:#475569}.auth-bg-bubble{position:fixed;z-index:0;pointer-events:none;border-radius:999px;filter:blur(2px);opacity:.55}.bubble-a{width:210px;height:210px;left:4%;top:8%;background:#bfdbfe}.bubble-b{width:260px;height:260px;right:4%;bottom:6%;background:#fde68a}#soundToggle{font-weight:1000;white-space:nowrap}
        @media(max-width:992px){.auth-shell{grid-template-columns:1fr;max-width:720px}.auth-hero-panel{min-height:auto;padding:30px}.auth-hero-panel h1{font-size:2.7rem}.auth-form-panel{padding:28px}}@media(max-width:576px){.auth-container{align-items:flex-start}.auth-shell{border-radius:28px}.auth-hero-panel{padding:24px}.auth-logo{width:74px;height:74px}.auth-hero-panel h1{font-size:2.15rem}.auth-form-panel{padding:22px}.auth-form-panel h2{font-size:1.8rem}.auth-actions{grid-template-columns:1fr}}
    </style>
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
