<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM academic_assessments WHERE id=? AND user_id=? LIMIT 1');
$stmt->execute([$id, (int)current_user()['id']]);
$a = $stmt->fetch();
if (!$a) redirect('student/academic.php');
$status = academic_stage_status((int)current_user()['id']);
render_header('Hasil Penilaian','academic');
?>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<div class="result-hero">
    <h1><?= strtoupper(e($a['assessment_type'])) ?> Selesai 🎉</h1>
    <div class="score-big"><?= e($a['score']) ?></div>
    <p>Benar <?= e($a['total_correct']) ?> dari <?= e($a['total_questions']) ?> soal.</p>
</div>
<div class="row g-4 mt-2">
    <div class="col-md-4"><div class="stat-card"><span>✅</span><b><?= e($a['total_correct']) ?></b><small>Jawaban benar</small></div></div>
    <div class="col-md-4"><div class="stat-card"><span>❌</span><b><?= e($a['total_wrong']) ?></b><small>Jawaban belum tepat</small></div></div>
    <div class="col-md-4"><div class="stat-card"><span>📈</span><b><?= $status['improvement'] !== null ? (($status['improvement']>=0?'+':'') . e($status['improvement'])) : '-' ?></b><small>Peningkatan pre-post</small></div></div>
</div>
<div class="panel-card mt-4">
    <?php if($a['assessment_type']==='posttest' && (int)$a['score'] < REMEDIAL_MIN_SCORE): ?>
        <h3>💪 Perlu Remedial Penguatan</h3><p>Nilai kamu belum mencapai <?= REMEDIAL_MIN_SCORE ?>. Buka menu remedial untuk latihan tambahan.</p><a class="btn btn-warning rounded-pill" href="<?= url('student/remedial.php') ?>">Buka Remedial</a>
    <?php else: ?>
        <h3>Langkah Berikutnya</h3><p>Lanjutkan alur belajar PABETAS.</p><a class="btn btn-primary rounded-pill" href="<?= url('student/academic.php') ?>">Kembali ke Alur Belajar</a>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
