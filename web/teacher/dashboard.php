<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();
$stats = [
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='murid' AND status=1")->fetchColumn(),
    'questions' => (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status=1")->fetchColumn(),
    'materials' => (int)$pdo->query("SELECT COUNT(*) FROM learning_materials WHERE status=1")->fetchColumn(),
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM responses r JOIN questions q ON q.id=r.question_id WHERE q.type='essay' AND r.status='pending'")->fetchColumn(),
];
render_header('Dashboard Guru', 'dashboard');
?>
<div class="page-hero teacher-hero dashboard-hero-clean">
    <div class="hero-copy">
        <span class="hero-badge">Panel Guru</span>
        <h1>Dashboard Guru</h1>
        <p>Pantau pembelajaran, ranking live, grafik hasil, dan kesiapan laporan PABETAS.</p>
    </div>
    <div class="hero-action-panel" aria-label="Aksi cepat guru">
        <a href="<?= url('teacher/live_games.php') ?>" class="hero-action-item primary">
            <span>🎮</span>
            <b>Buat Live Game</b>
            <small>Mulai permainan kelas</small>
        </a>
        <a href="<?= url('teacher/academic_reports.php') ?>" class="hero-action-item light">
            <span>📈</span>
            <b>Laporan Akademik</b>
            <small>Pretest, posttest, remedial</small>
        </a>
        <a href="<?= url('teacher/reports.php') ?>" class="hero-action-item warning">
            <span>📄</span>
            <b>Laporan Umum</b>
            <small>PDF, Excel, grafik</small>
        </a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><span>👧👦</span><b><?= $stats['students'] ?></b><small>Murid aktif</small></div></div>
    <div class="col-md-3"><div class="stat-card"><span>🧩</span><b><?= $stats['materials'] ?></b><small>Materi aktif</small></div></div>
    <div class="col-md-3"><div class="stat-card"><span>📝</span><b><?= $stats['questions'] ?></b><small>Soal aktif</small></div></div>
    <div class="col-md-3"><div class="stat-card"><span>⏳</span><b><?= $stats['pending'] ?></b><small>Esai menunggu nilai</small></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">🏆 Ranking Live</h3>
                <span class="badge text-bg-primary" id="liveStatus">Memuat...</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle" id="rankingTable">
                    <thead><tr><th>Rank</th><th>Murid</th><th>Skor</th><th>Benar</th><th>Status</th></tr></thead>
                    <tbody><tr><td colspan="5">Memuat data...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card">
            <h3>📊 Grafik Hasil Otomatis</h3>
            <canvas id="scoreChart" width="520" height="360"></canvas>
            <p class="small text-muted mt-2">Grafik membaca data terbaru dari hasil kuis siswa.</p>
        </div>
    </div>
</div>
<script src="<?= url('assets/js/dashboard.js') ?>"></script>
<?php render_footer(); ?>
