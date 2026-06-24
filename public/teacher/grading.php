<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo=db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $score=(int)($_POST['teacher_score'] ?? 0);
    $pdo->prepare("UPDATE responses SET teacher_score=?, score=?, feedback=?, status='reviewed', updated_at=NOW() WHERE id=?")->execute([$score,$score,trim($_POST['feedback'] ?? ''),(int)$_POST['response_id']]);
    flash('success','Nilai esai disimpan.'); redirect('teacher/grading.php');
}
$rows=$pdo->query("SELECT r.*, q.prompt, u.name FROM responses r JOIN questions q ON q.id=r.question_id JOIN users u ON u.id=r.user_id WHERE q.type='essay' ORDER BY r.status ASC, r.updated_at DESC")->fetchAll();
render_header('Penilaian Esai','grading');
?>
<div class="page-title"><h1>Penilaian Esai Manual</h1><p>Guru memberi skor dan umpan balik untuk jawaban terbuka siswa.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="panel-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Murid</th><th>Soal & Jawaban</th><th>Status</th><th>Nilai</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><b><?= e($r['prompt']) ?></b><br><span class="answer-preview"><?= e($r['answer']) ?></span><?php if($r['feedback']): ?><br><small class="text-success">Feedback: <?= e($r['feedback']) ?></small><?php endif; ?></td><td><span class="badge <?= $r['status']==='pending'?'text-bg-warning':'text-bg-success' ?>"><?= e($r['status']) ?></span></td><td><form method="post" class="grade-form"><?= csrf_field() ?><input type="hidden" name="response_id" value="<?= $r['id'] ?>"><input type="number" name="teacher_score" min="0" max="100" class="form-control form-control-sm mb-1" value="<?= e($r['teacher_score'] ?? '') ?>" placeholder="Skor"><input name="feedback" class="form-control form-control-sm mb-1" placeholder="Feedback singkat" value="<?= e($r['feedback'] ?? '') ?>"><button class="btn btn-sm btn-primary">Simpan</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php render_footer(); ?>
