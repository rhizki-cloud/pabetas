<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo = db();
$templateId = (int)($_GET['template'] ?? 0);

if ($templateId <= 0) {
    $sets = $pdo->query("SELECT t.*, COUNT(tq.id) AS total_questions
        FROM assessment_templates t
        JOIN assessment_template_questions tq ON tq.template_id=t.id
        JOIN questions q ON q.id=tq.question_id
        WHERE t.status=1 AND t.assessment_type='quiz'
        GROUP BY t.id
        HAVING SUM(CASE WHEN q.type='essay' THEN 1 ELSE 0 END)=COUNT(q.id)
        ORDER BY t.id DESC")->fetchAll();
    render_header('Soal Esai', 'essay_quiz');
    ?>
    <div class="page-title"><h1>✍️ Soal Esai</h1><p>Pilih paket soal esai dari guru. Jawaban akan masuk ke menu Nilai Esai untuk diperiksa guru.</p></div>
    <?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
    <div class="row g-4">
      <?php foreach($sets as $s): ?>
        <div class="col-md-6 col-lg-4"><div class="feature-card h-100">
          <div class="feature-icon">📝</div><h3><?= e($s['title']) ?></h3>
          <p><?= e(trim(str_replace('[ESAI]','',$s['description'] ?? '')) ?: 'Jawab pertanyaan dengan kalimat sendiri.') ?></p>
          <p><b><?= (int)$s['total_questions'] ?></b> soal esai</p>
          <a class="btn btn-primary rounded-pill" href="<?= url('student/essay_quiz.php?template='.(int)$s['id']) ?>">Kerjakan</a>
        </div></div>
      <?php endforeach; ?>
      <?php if(!$sets): ?><div class="col-12"><div class="alert alert-warning">Belum ada paket soal esai aktif. Guru perlu membuat paket di menu Paket Esai.</div></div><?php endif; ?>
    </div>
    <?php render_footer(); exit; ?>
    <?php
}

$stmt = $pdo->prepare("SELECT * FROM assessment_templates WHERE id=? AND status=1 LIMIT 1");
$stmt->execute([$templateId]);
$template = $stmt->fetch();
if (!$template) redirect('student/essay_quiz.php');
$stmt = $pdo->prepare("SELECT q.* FROM assessment_template_questions tq JOIN questions q ON q.id=tq.question_id WHERE tq.template_id=? AND q.status=1 AND q.type='essay' ORDER BY tq.sort_order ASC, q.id ASC");
$stmt->execute([$templateId]);
$questions = $stmt->fetchAll();
render_header('Kerjakan Esai', 'essay_quiz');
?>
<div class="page-title"><h1><?= e($template['title']) ?></h1><p><?= e(trim(str_replace('[ESAI]','',$template['description'] ?? '')) ?: 'Tulis jawaban dengan jelas. Guru akan menilai setelah kamu mengirim jawaban.') ?></p></div>
<?php if(!$questions): ?><div class="alert alert-warning">Paket ini belum memiliki soal esai aktif.</div><?php else: ?>
<form method="post" action="<?= url('student/submit_essay_quiz.php') ?>">
  <?= csrf_field() ?><input type="hidden" name="template_id" value="<?= (int)$templateId ?>">
  <?php foreach($questions as $i=>$q): ?>
    <div class="question-card">
      <div class="question-number">Esai <?= $i+1 ?> • <?= e($q['difficulty'] ?? 'mudah') ?></div>
      <h3><?= e($q['prompt']) ?></h3>
      <textarea class="form-control form-control-lg" name="answers[<?= (int)$q['id'] ?>]" rows="5" placeholder="Tulis jawabanmu di sini..." required></textarea>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-success btn-lg rounded-pill">Kirim Jawaban Esai</button>
  <a href="<?= url('student/essay_quiz.php') ?>" class="btn btn-outline-secondary btn-lg rounded-pill">Kembali</a>
</form>
<?php endif; ?>
<?php render_footer(); ?>
