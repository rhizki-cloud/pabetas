<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$user = current_user();
$status = academic_stage_status((int)$user['id']);
$progress = $status['progress'];
$steps = academic_flow_steps();
render_header('Alur Belajar Akademik','academic');
function stage_badge_v3($actionType, $status, $progress) {
    $done = false;
    if ($actionType === 'pretest') $done = !empty($status['pretest']);
    elseif ($actionType === 'posttest') $done = !empty($status['posttest']);
    elseif ($actionType === 'remedial') $done = !empty($status['remedial']) || !$status['needs_remedial'];
    elseif ($actionType === 'material') $done = !empty($progress['learn_completed_at']);
    return $done ? '<span class="badge text-bg-success">Selesai</span>' : '<span class="badge text-bg-secondary">Belum</span>';
}
?>
<div class="page-hero">
    <div>
        <h1>Alur Belajar PABETAS</h1>
        <p>Urutan belajar ini dibuat dari sisi guru. Ikuti langkahnya agar hasil pretest, latihan, game, posttest, dan remedial tercatat rapi.</p>
    </div>
    <a href="<?= url('student/live_join.php') ?>" class="btn btn-light btn-lg rounded-pill">Masuk Live Game</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="academic-flow">
            <?php foreach($steps as $step): $action=$step['action_type']; ?>
            <div class="academic-step">
                <div class="step-num"><?= (int)$step['step_order'] ?></div>
                <div>
                    <h3><?= e($step['title']) ?> <?= (int)$step['is_required']===1?'<small class="text-danger">*</small>':'' ?></h3>
                    <p><?= e($step['description']) ?></p>
                    <?= stage_badge_v3($action, $status, $progress) ?>
                </div>
                <a class="btn btn-primary rounded-pill" href="<?= e(academic_action_url($action, $step['action_payload'] ?? '')) ?>"><?= e(academic_action_button($action)) ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card sticky-lg-top sticky-offset">
            <h3>📊 Ringkasan Nilai</h3>
            <div class="mini-score"><span>Pretest</span><b><?= $status['pretest'] ? e($status['pretest']['score']) : '-' ?></b></div>
            <div class="mini-score"><span>Posttest</span><b><?= $status['posttest'] ? e($status['posttest']['score']) : '-' ?></b></div>
            <div class="mini-score"><span>Peningkatan</span><b><?= $status['improvement'] !== null ? (($status['improvement']>=0?'+':'') . e($status['improvement'])) : '-' ?></b></div>
            <?php if($status['needs_remedial']): ?><div class="alert alert-warning mt-3">Kamu perlu remedial penguatan. Tetap semangat ya!</div><?php endif; ?>
            <a href="<?= url('student/essay_feedback.php') ?>" class="btn btn-outline-primary rounded-pill w-100 mt-2">Lihat Nilai Esai Saya</a>
        </div>
    </div>
</div>
<?php render_footer(); ?>
