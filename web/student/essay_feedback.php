<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo=db();
$stmt=$pdo->prepare("SELECT r.*, q.prompt, gs.mode, gs.started_at FROM responses r JOIN questions q ON q.id=r.question_id JOIN game_sessions gs ON gs.id=r.session_id WHERE r.user_id=? AND q.type='essay' ORDER BY r.updated_at DESC, r.id DESC");
$stmt->execute([(int)current_user()['id']]);
$rows=$stmt->fetchAll();
render_header('Nilai Esai Saya','essay_feedback');
?>
<div class="page-title"><h1>Nilai Esai Saya</h1><p>Di sini siswa dapat melihat apakah jawaban esai sudah dinilai guru, termasuk skor dan feedback.</p></div>
<?php if(!$rows): ?><div class="alert alert-info">Belum ada jawaban esai. Esai muncul dari menu <b>Kuis Individu</b> atau <b>Game Tim</b> jika guru membuat soal jenis esai.</div><?php endif; ?>
<?php foreach($rows as $r): ?>
<div class="essay-card <?= $r['status']==='reviewed'?'essay-reviewed':'essay-pending' ?>">
  <div class="d-flex justify-content-between flex-wrap gap-2"><h3><?= e($r['prompt']) ?></h3><span class="badge <?= $r['status']==='reviewed'?'text-bg-success':'text-bg-warning' ?>"><?= e($r['status']) ?></span></div>
  <p><b>Jawabanmu:</b><br><?= nl2br(e($r['answer'])) ?></p>
  <?php if($r['status']==='reviewed'): ?><p><b>Nilai Guru:</b> <?= e($r['teacher_score'] ?? $r['score']) ?></p><p><b>Feedback:</b> <?= e($r['feedback'] ?: '-') ?></p><?php else: ?><p class="text-muted">Jawaban menunggu diperiksa guru.</p><?php endif; ?>
</div>
<?php endforeach; ?>
<a class="btn btn-primary rounded-pill" href="<?= url('student/dashboard.php') ?>">Kembali</a>
<?php render_footer(); ?>
