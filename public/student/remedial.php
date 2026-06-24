<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$stmt = db()->prepare('SELECT * FROM remedial_assignments WHERE user_id=? ORDER BY id DESC');
$stmt->execute([(int)current_user()['id']]);
$items = $stmt->fetchAll();
render_header('Remedial Otomatis','academic');
?>
<div class="page-title"><h1>Remedial Otomatis</h1><p>Sistem memberi penguatan jika nilai posttest belum mencapai batas ketuntasan.</p></div>
<?php if(!$items): ?>
<div class="panel-card"><h3>Belum Ada Remedial 🎉</h3><p>Kamu belum memiliki tugas remedial. Tetap lanjutkan latihan agar makin lancar.</p><a href="<?= url('student/academic.php') ?>" class="btn btn-primary rounded-pill">Kembali</a></div>
<?php else: foreach($items as $r): ?>
<div class="panel-card mb-3">
    <span class="badge <?= $r['status']==='done'?'text-bg-success':'text-bg-warning' ?>"><?= e($r['status']) ?></span>
    <h3 class="mt-2">Latihan Penguatan Satuan Panjang</h3>
    <p><b>Alasan:</b> <?= e($r['reason']) ?></p>
    <p><b>Saran belajar:</b> <?= e($r['recommended_material']) ?></p>
    <?php if($r['status']==='open'): ?><a class="btn btn-warning rounded-pill" href="<?= url('student/assessment.php?type=remedial') ?>">Kerjakan Remedial</a><?php endif; ?>
</div>
<?php endforeach; endif; ?>
<?php render_footer(); ?>
