<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
render_header('Laporan Umum','reports');
?>
<div class="page-title report-title-clean">
    <span class="hero-badge blue">Laporan Umum</span>
    <h1>Pusat Laporan PABETAS</h1>
    <p>Pilih jenis laporan sesuai kebutuhan. Laporan umum berisi hasil kuis, skor, benar/salah, dan grafik ringkas. Untuk analisis pretest dan posttest, gunakan menu Laporan Akademik.</p>
</div>

<div class="report-help-grid mb-4">
    <div class="report-help-card">
        <span>📄</span>
        <h3>Laporan PDF</h3>
        <p>Dipakai untuk dicetak atau dilampirkan pada dokumen sekolah. Isinya ringkasan hasil belajar dengan identitas PABETAS.</p>
        <a class="btn btn-primary rounded-pill" target="_blank" href="<?= url('teacher/report_pdf.php') ?>">Buka PDF</a>
    </div>
    <div class="report-help-card">
        <span>📊</span>
        <h3>Export Excel</h3>
        <p>Dipakai ketika guru ingin mengolah nilai lagi di Microsoft Excel atau LibreOffice Calc.</p>
        <a class="btn btn-success rounded-pill" href="<?= url('teacher/export_excel.php') ?>">Download Excel</a>
    </div>
    <div class="report-help-card">
        <span>📈</span>
        <h3>Grafik Otomatis</h3>
        <p>Dipakai untuk melihat perkembangan nilai secara visual di dashboard guru.</p>
        <a class="btn btn-warning rounded-pill" href="<?= url('teacher/dashboard.php') ?>">Lihat Grafik</a>
    </div>
</div>

<div class="panel-card report-guide-card">
    <h3>🧭 Panduan singkat</h3>
    <div class="row g-3">
        <div class="col-md-4"><div class="guide-step"><b>1</b><p>Gunakan <strong>PDF</strong> untuk laporan siap cetak.</p></div></div>
        <div class="col-md-4"><div class="guide-step"><b>2</b><p>Gunakan <strong>Excel</strong> untuk rekap angka dan arsip nilai.</p></div></div>
        <div class="col-md-4"><div class="guide-step"><b>3</b><p>Gunakan <strong>Laporan Akademik</strong> untuk pretest, posttest, peningkatan, dan remedial.</p></div></div>
    </div>
    <div class="mt-3 d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary rounded-pill" href="<?= url('teacher/academic_reports.php') ?>">Buka Laporan Akademik</a>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= url('teacher/live_games.php') ?>">Buka Rekap Live Game</a>
    </div>
</div>
<?php render_footer(); ?>
