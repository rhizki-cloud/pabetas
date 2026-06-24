<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$rows = academic_class_report();
$sum = academic_summary($rows);
$difficulty = academic_question_difficulty();
render_header('Laporan Akademik','academic');
?>
<div class="page-hero teacher-hero dashboard-hero-clean">
    <div class="hero-copy">
        <span class="hero-badge">Laporan Akademik</span>
        <h1>Laporan Akademik PABETAS</h1>
        <p>Memudahkan guru membaca pretest, posttest, peningkatan nilai, remedial otomatis, dan soal yang paling sulit.</p>
    </div>
    <div class="hero-action-panel two-actions">
        <a href="<?= url('teacher/academic_report_pdf.php') ?>" class="hero-action-item light">
            <span>📄</span>
            <b>Cetak PDF</b>
            <small>Untuk lampiran laporan</small>
        </a>
        <a href="<?= url('teacher/academic_report_excel.php') ?>" class="hero-action-item warning">
            <span>📊</span>
            <b>Export Excel</b>
            <small>Untuk olah data nilai</small>
        </a>
    </div>
</div>
<div class="academic-explain-grid mb-4">
    <div><b>Pretest</b><span>Nilai awal sebelum siswa belajar dengan PABETAS.</span></div>
    <div><b>Posttest</b><span>Nilai akhir setelah siswa mengikuti alur belajar.</span></div>
    <div><b>Peningkatan</b><span>Selisih nilai posttest dan pretest.</span></div>
    <div><b>Remedial</b><span>Siswa yang masih perlu latihan tambahan.</span></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-2"><div class="stat-card"><span>👧👦</span><b><?= e($sum['total_students']) ?></b><small>Total murid</small></div></div>
    <div class="col-md-2"><div class="stat-card"><span>🧪</span><b><?= e($sum['avg_pretest']) ?></b><small>Rata-rata pretest</small></div></div>
    <div class="col-md-2"><div class="stat-card"><span>🎯</span><b><?= e($sum['avg_posttest']) ?></b><small>Rata-rata posttest</small></div></div>
    <div class="col-md-2"><div class="stat-card"><span>📈</span><b><?= e($sum['avg_improvement']) ?></b><small>Rata-rata peningkatan</small></div></div>
    <div class="col-md-2"><div class="stat-card"><span>✅</span><b><?= e($sum['mastery_percent']) ?>%</b><small>Ketuntasan</small></div></div>
    <div class="col-md-2"><div class="stat-card"><span>💪</span><b><?= e($sum['remedial_open']) ?></b><small>Remedial aktif</small></div></div>
</div>
<div class="row g-4">
<div class="col-lg-8"><div class="panel-card"><h3>📋 Rekap Siswa</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Pretest</th><th>Posttest</th><th>Naik</th><th>Remedial</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): $imp = ($r['pretest']!==null && $r['posttest']!==null) ? ((int)$r['posttest']-(int)$r['pretest']) : null; ?>
<tr><td><b><?= e($r['name']) ?></b><br><small><?= e($r['username']) ?></small></td><td><?= $r['pretest']!==null?e($r['pretest']):'-' ?></td><td><?= $r['posttest']!==null?e($r['posttest']):'-' ?></td><td><?= $imp!==null?(($imp>=0?'+':'').e($imp)):'-' ?></td><td><?= ((int)$r['remedial_open']===1)?'<span class="badge text-bg-warning">Perlu</span>':'<span class="badge text-bg-success">Aman</span>' ?></td><td><?= ($r['posttest']!==null && (int)$r['posttest']>=REMEDIAL_MIN_SCORE)?'Tuntas':'Belum lengkap/tidak tuntas' ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<div class="col-lg-4"><div class="panel-card"><h3>🔎 Soal Paling Sulit</h3><?php if(!$difficulty): ?><p>Belum ada data jawaban akademik.</p><?php else: ?><ol class="difficulty-list"><?php foreach($difficulty as $d): ?><li><b><?= e($d['wrong_percent']) ?>% salah</b><br><?= e($d['prompt']) ?><br><small><?= e($d['wrong_count']) ?> salah dari <?= e($d['total_answer']) ?> jawaban</small></li><?php endforeach; ?></ol><?php endif; ?></div></div>
</div>
<div class="panel-card mt-4"><h3>🧠 Rekomendasi Guru</h3><p><?= $sum['avg_improvement'] > 0 ? 'Pembelajaran PABETAS menunjukkan peningkatan rata-rata nilai. Guru dapat melanjutkan penguatan pada soal dengan persentase salah tertinggi.' : 'Data peningkatan belum cukup. Jalankan pretest dan posttest pada seluruh siswa agar analisis lebih kuat.' ?></p></div>
<?php render_footer(); ?>
