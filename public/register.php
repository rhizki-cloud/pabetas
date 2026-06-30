<?php
require_once __DIR__ . '/../app/init.php';
if (current_user()) redirect($_SESSION['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
$error = '';
$success = '';
$selectedRole = ($_GET['role'] ?? ($_POST['role'] ?? 'murid')) === 'guru' ? 'guru' : 'murid';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $role = ($_POST['role'] ?? 'murid') === 'guru' ? 'guru' : 'murid';
    $selectedRole = $role;
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $nis = trim($_POST['nis'] ?? '');
    $class = trim($_POST['class_name'] ?? 'III A');

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Nama, username, dan password wajib diisi.';
    } elseif (!preg_match('/^[a-z0-9_.-]{3,30}$/', $username)) {
        $error = 'Username minimal 3 karakter dan hanya boleh huruf kecil, angka, titik, strip, atau underscore.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password belum sama.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah dipakai. Gunakan username lain.';
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $avatar = $role === 'guru' ? 'teacher' : 'rocket';
                $pdo->prepare("INSERT INTO users(name,username,password,role,status,avatar_key) VALUES(?,?,?,?,1,?)")->execute([$name,$username,$hash,$role,$avatar]);
                $uid = (int)$pdo->lastInsertId();
                if ($role === 'murid') {
                    $pdo->prepare('INSERT INTO students(user_id,nis,class_name) VALUES(?,?,?)')->execute([$uid,$nis,$class ?: 'III A']);
                }
                $pdo->commit();
                $success = 'Akun ' . ($role === 'guru' ? 'guru' : 'murid') . ' berhasil dibuat. Silakan login memakai username dan password baru.';
                $_POST = [];
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Akun gagal dibuat. Coba lagi.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun PABETAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=9') ?>">
</head>
<body class="login-body auth-page" data-sound-context="home">
<div class="auth-bg-bubble bubble-a"></div>
<div class="auth-bg-bubble bubble-b"></div>
<div class="container auth-container py-4 py-md-5">
    <div class="auth-shell auth-shell-register">
        <section class="auth-hero-panel">
            <img src="<?= url(SCHOOL_LOGO) ?>" alt="Logo" class="auth-logo mb-3">
            <span class="auth-badge">Daftar Akun Baru</span>
            <h1>Pilih peran akun</h1>
            <p>Guru mengelola materi, soal, kuis, live game, nilai, dan laporan. Murid belajar, menjawab kuis, ikut game, dan melihat hasil.</p>
            <div class="role-info-card"><b>Catatan keamanan:</b> untuk sekolah sungguhan, pendaftaran guru sebaiknya diverifikasi admin.</div>
        </section>
        <section class="auth-form-panel">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="mb-1">Buat Akun</h2>
                    <p class="text-muted mb-0">Pilih daftar sebagai guru atau murid.</p>
                </div>
                <button type="button" class="btn btn-outline-primary rounded-pill" id="soundToggle">🔊 Musik</button>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <form method="post" class="auth-form" id="registerForm">
                <?= csrf_field() ?>
                <div class="role-picker mb-3">
                    <label class="role-card <?= $selectedRole === 'murid' ? 'selected' : '' ?>">
                        <input type="radio" name="role" value="murid" <?= $selectedRole === 'murid' ? 'checked' : '' ?>>
                        <span>👧</span>
                        <b>Murid</b>
                        <small>Ikut belajar dan game</small>
                    </label>
                    <label class="role-card <?= $selectedRole === 'guru' ? 'selected' : '' ?>">
                        <input type="radio" name="role" value="guru" <?= $selectedRole === 'guru' ? 'checked' : '' ?>>
                        <span>👨‍🏫</span>
                        <b>Guru</b>
                        <small>Kelola kelas dan laporan</small>
                    </label>
                </div>
                <label class="form-label fw-bold">Nama lengkap</label>
                <input class="form-control form-control-lg mb-3" name="name" placeholder="Nama lengkap" required value="<?= e($_POST['name'] ?? '') ?>">
                <label class="form-label fw-bold">Username</label>
                <input class="form-control form-control-lg mb-3" name="username" placeholder="huruf kecil, angka, titik, strip, underscore" required value="<?= e($_POST['username'] ?? '') ?>">
                <div id="studentFields" class="student-fields <?= $selectedRole === 'guru' ? 'd-none' : '' ?>">
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label fw-bold">NIS</label><input class="form-control mb-3" name="nis" placeholder="Boleh kosong" value="<?= e($_POST['nis'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Kelas</label><input class="form-control mb-3" name="class_name" placeholder="III A" value="<?= e($_POST['class_name'] ?? 'III A') ?>"></div>
                    </div>
                </div>
                <label class="form-label fw-bold">Password</label>
                <input type="password" class="form-control form-control-lg mb-3" name="password" placeholder="Minimal 6 karakter" required>
                <label class="form-label fw-bold">Ulangi password</label>
                <input type="password" class="form-control form-control-lg mb-4" name="confirm_password" placeholder="Ulangi password" required>
                <button class="btn btn-primary btn-lg w-100 rounded-pill" id="registerButton">Buat Akun <?= $selectedRole === 'guru' ? 'Guru' : 'Murid' ?></button>
            </form>
            <div class="text-center mt-3"><a class="fw-bold" href="<?= url('login.php') ?>">Sudah punya akun? Login</a></div>
        </section>
    </div>
</div>
<script src="<?= url('assets/js/sounds.js?v=9') ?>"></script>
<script>
(function(){
  const fields = document.getElementById('studentFields');
  const btn = document.getElementById('registerButton');
  document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.role-card').forEach(card => card.classList.remove('selected'));
      radio.closest('.role-card').classList.add('selected');
      const isGuru = radio.value === 'guru';
      fields.classList.toggle('d-none', isGuru);
      btn.textContent = 'Buat Akun ' + (isGuru ? 'Guru' : 'Murid');
    });
  });
})();
</script>
</body>
</html>
