<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$type = $_GET['type'] ?? 'pretest';
$templateId = (int)($_GET['template'] ?? 0);
$allowed = ['pretest'=>'Pretest','posttest'=>'Posttest','remedial'=>'Remedial','quiz'=>'Kuis'];
if (!isset($allowed[$type])) redirect('student/academic.php');
$questions = academic_questions($type, ACADEMIC_TEST_LIMIT, $templateId);
$template = $templateId ? academic_template($templateId) : academic_default_template_for_type($type);
render_header($allowed[$type], 'academic');
?>
<div class="page-title"><h1><?= e($template['title'] ?? ($allowed[$type] . ' PABETAS')) ?></h1><p><?= e($template['description'] ?? 'Jawab soal dengan teliti. Hasilnya akan dipakai untuk melihat perkembangan belajarmu.') ?></p></div>
<?php if(!$questions): ?><div class="alert alert-warning">Belum ada soal untuk tes ini. Guru perlu mengatur template kuis di menu Kuis/Alur.</div><?php endif; ?>
<form method="POST" action="<?= url('student/submit_assessment.php') ?>" class="assessment-form">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input type="hidden" name="template_id" value="<?= (int)$templateId ?>">
    <?php foreach($questions as $i=>$q): $opts=json_decode($q['options_json'] ?: '[]', true) ?: []; ?>
    <div class="question-card">
        <div class="question-number">Soal <?= $i+1 ?></div>
        <h3><?= e($q['prompt']) ?></h3>
        <?php if($q['type']==='multiple'): ?>
            <?php foreach($opts as $opt): ?>
                <label class="option-box"><input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="<?= e($opt) ?>" required> <?= e($opt) ?></label>
            <?php endforeach; ?>
        <?php else: ?>
            <input class="form-control form-control-lg" name="answers[<?= (int)$q['id'] ?>]" placeholder="Tulis jawaban, contoh: 3000 m" required>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <button class="btn btn-success btn-lg rounded-pill">Selesai dan Simpan Nilai</button>
    <a href="<?= url('student/academic.php') ?>" class="btn btn-outline-secondary btn-lg rounded-pill">Kembali</a>
</form>
<?php render_footer(); ?>
