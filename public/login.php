<?php
require_once __DIR__ . '/../app/init.php';

$user = current_user();
if ($user) {
    redirect($user['role'] === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
}

$error = '';
$debugInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login dibuat tanpa CSRF session karena Vercel serverless dapat membuat token session hilang antar-request.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login_user($username, $password)) {
        $role = $_SESSION['role'] ?? 'murid';
        redirect($role === 'guru' ? 'teacher/dashboard.php' : 'student/dashboard.php');
    }

    $error = 'Username atau password salah.';

    if (defined('APP_DEBUG') && APP_DEBUG) {
        try {
            $stmt = db()->prepare('SELECT username, role, status FROM users WHERE username=? LIMIT 1');
            $stmt->execute([$username]);
            $found = $stmt->fetch();
            if (!$found) {
                $debugInfo = 'Debug: username tidak ditemukan di tabel users.';
            } elseif ((int) $found['status'] !== 1) {
                $debugInfo = 'Debug: username ditemukan, tetapi status user tidak aktif.';
            } else {
                $debugInfo = 'Debug: username ditemukan dan aktif. Password tidak cocok.';
            }
        } catch (Throwable $e) {
            $debugInfo = 'Debug login gagal dibaca: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login PABETAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{--blue:#1d4ed8;--cyan:#0ea5e9;--green:#22c55e;--dark:#0f172a;--muted:#64748b;--yellow:#fbbf24}
        *{box-sizing:border-box}body.login-body{min-height:100vh;margin:0;overflow:auto;background:radial-gradient(circle at 8% 12%,#dbeafe 0,#eef6ff 24%,transparent 44%),radial-gradient(circle at 92% 8%,#fef3c7 0,#fff7ed 26%,transparent 46%),linear-gradient(135deg,#eff6ff 0%,#fff7ed 100%);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--dark)}
        .auth-bg-bubble{position:fixed;z-index:0;pointer-events:none;border-radius:999px;filter:blur(2px);opacity:.55}.bubble-a{width:210px;height:210px;left:4%;top:8%;background:#bfdbfe}.bubble-b{width:260px;height:260px;right:4%;bottom:6%;background:#fde68a}
        .auth-container{min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;padding:48px 18px}.auth-shell{width:min(1080px,100%);display:grid;grid-template-columns:1.05fr .95fr;background:rgba(255,255,255,.86);border:1px solid rgba(255,255,255,.7);box-shadow:0 28px 90px rgba(15,23,42,.16);border-radius:38px;overflow:hidden;backdrop-filter:blur(16px)}
        .auth-hero-panel{position:relative;min-height:560px;padding:42px;display:flex;flex-direction:column;justify-content:center;background:linear-gradient(135deg,var(--blue),var(--cyan) 52%,var(--green));color:#fff;overflow:hidden}.auth-hero-panel:before{content:"";position:absolute;right:-70px;bottom:-90px;width:270px;height:270px;background:rgba(255,255,255,.18);border-radius:50%}.auth-hero-panel:after{content:"♪";position:absolute;right:46px;top:36px;font-size:5rem;line-height:1;color:rgba(255,255,255,.18);font-weight:1000}
        .auth-logo-mark{width:92px;height:92px;background:#fff;border-radius:24px;box-shadow:0 14px 35px rgba(15,23,42,.18);display:grid;place-items:center;margin-bottom:20px;position:relative;overflow:hidden}.auth-logo-mark:before{content:"";width:58px;height:58px;border-radius:18px;background:linear-gradient(135deg,#2563eb,#0ea5e9);position:absolute}.auth-logo-mark:after{content:"P";position:relative;color:#fff;font-weight:1000;font-size:42px;letter-spacing:-2px}.auth-logo-line{position:absolute;width:44px;height:6px;background:#facc15;border-radius:999px;left:24px;bottom:23px;z-index:3}
        .auth-badge{display:inline-flex;width:max-content;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);border-radius:999px;padding:.55rem .9rem;font-weight:900;margin-bottom:1rem}.auth-hero-panel h1{font-size:clamp(2.5rem,5vw,4.5rem);font-weight:1000;line-height:1.02;margin-bottom:1rem}.auth-hero-panel p{font-size:1.15rem;line-height:1.65;max-width:560px}.auth-feature-list{display:grid;gap:.75rem;margin-top:1rem}.auth-feature-list div{background:rgba(255,255,255,.17);border:1px solid rgba(255,255,255,.25);border-radius:18px;padding:.9rem 1rem;font-weight:800}
        .auth-form-panel{padding:38px;display:flex;flex-direction:column;justify-content:center;background:#fff}.auth-form-panel h2{font-size:2.2rem;font-weight:1000;color:#0f172a}.auth-form .form-control{border-radius:18px;border:2px solid #e2e8f0;padding:.85rem 1rem}.auth-form .form-control:focus{border-color:#60a5fa;box-shadow:0 0 0 .25rem rgba(96,165,250,.2)}.auth-actions{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}.auth-actions .btn{font-weight:1000;padding:.85rem 1rem}.demo-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;padding:1rem;text-align:center;color:#475569}#soundToggle{font-weight:1000;white-space:nowrap}.btn-primary{background:#1673ff;border-color:#1673ff}.btn-primary:hover{background:#075dda;border-color:#075dda}
        .debug-box{font-size:.9rem;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;border-radius:14px;padding:.75rem 1rem;margin-top:.75rem}
        @media(max-width:992px){.auth-shell{grid-template-columns:1fr;max-width:720px}.auth-hero-panel{min-height:auto;padding:30px}.auth-hero-panel h1{font-size:2.7rem}.auth-form-panel{padding:28px}}@media(max-width:576px){.auth-container{align-items:flex-start;padding:20px 12px}.auth-shell{border-radius:28px}.auth-hero-panel{padding:24px}.auth-logo-mark{width:74px;height:74px}.auth-hero-panel h1{font-size:2.15rem}.auth-form-panel{padding:22px}.auth-form-panel h2{font-size:1.8rem}.auth-actions{grid-template-columns:1fr}}
    </style>
</head>
<body class="login-body">
<div class="auth-bg-bubble bubble-a"></div>
<div class="auth-bg-bubble bubble-b"></div>
<div class="auth-container">
    <div class="auth-shell">
        <section class="auth-hero-panel">
            <div class="auth-logo-mark" aria-label="Logo PABETAS"><span class="auth-logo-line"></span></div>
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
                <button type="button" class="btn btn-outline-primary rounded-pill" id="soundToggle">🔇 Musik Mati</button>
            </div>

            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">Sesi habis. Silakan login ulang.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php if ($debugInfo): ?><div class="debug-box"><?= e($debugInfo) ?></div><?php endif; ?>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login.php')) ?>" class="auth-form" autocomplete="on">
                <label class="form-label fw-bold">Username</label>
                <input class="form-control form-control-lg mb-3" name="username" required autofocus placeholder="Contoh: siswa" value="<?= e($_POST['username'] ?? '') ?>">

                <label class="form-label fw-bold">Password</label>
                <input type="password" class="form-control form-control-lg mb-4" name="password" required placeholder="Masukkan password">

                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Masuk Belajar</button>
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
<script>
(function(){
    const btn = document.getElementById('soundToggle');
    let ctx = null;
    let timer = null;
    let enabled = localStorage.getItem('pabetas_sound_enabled') === '1';
    const notes = [392,440,494,392,330,392,440,392];
    let step = 0;

    function audio(){
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        if (!ctx) ctx = new AC();
        if (ctx.state === 'suspended') ctx.resume().catch(function(){});
        return ctx;
    }

    function note(freq, dur, vol){
        const c = audio(); if (!c) return;
        const o = c.createOscillator();
        const g = c.createGain();
        const now = c.currentTime;
        o.type = 'sine';
        o.frequency.setValueAtTime(freq, now);
        g.gain.setValueAtTime(0.0001, now);
        g.gain.exponentialRampToValueAtTime(vol || 0.045, now + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, now + (dur || .28));
        o.connect(g); g.connect(c.destination);
        o.start(now); o.stop(now + (dur || .28) + .04);
    }

    function tick(){
        if (!enabled || document.hidden) return;
        note(notes[step % notes.length], .30, .045);
        if (step % 2 === 0) note(196, .48, .025);
        step++;
    }

    function start(){
        enabled = true;
        localStorage.setItem('pabetas_sound_enabled','1');
        stop(false);
        tick();
        timer = setInterval(tick, 780);
        update();
    }

    function stop(save){
        if (timer) clearInterval(timer);
        timer = null;
        if (save !== false) localStorage.setItem('pabetas_sound_enabled','0');
        update(false);
    }

    function update(force){
        const active = typeof force === 'boolean' ? force : enabled;
        if (!btn) return;
        btn.textContent = active ? '🔊 Musik Aktif' : '🔇 Musik Mati';
        btn.classList.toggle('btn-warning', active);
        btn.classList.toggle('btn-outline-primary', !active);
    }

    if (btn) {
        btn.addEventListener('click', function(){
            if (enabled && timer) { enabled = false; stop(true); return; }
            start();
        });
    }
    document.addEventListener('click', function(e){
        if (e.target.closest('button,a,input')) note(620, .10, .025);
    }, true);
    update(enabled);
})();
</script>
</body>
</html>
