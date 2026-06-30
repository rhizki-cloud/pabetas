<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo=db();
$sessionId=(int)($_GET['session_id'] ?? 0);
$stmt=$pdo->prepare('SELECT gs.*, r.name AS room_name, r.code, t.name AS team_name, t.members_json FROM game_sessions gs LEFT JOIN rooms r ON r.id=gs.room_id LEFT JOIN teams t ON t.id=gs.team_id WHERE gs.id=? AND gs.user_id=? AND gs.mode="tim" LIMIT 1');
$stmt->execute([$sessionId,current_user()['id']]);
$session=$stmt->fetch();
if(!$session) redirect('student/dashboard.php');
$members=json_decode($session['members_json'] ?: '[]', true) ?: [];
$stmt=$pdo->prepare('SELECT r.*, q.prompt, q.answer_key, q.type FROM responses r JOIN questions q ON q.id=r.question_id WHERE r.session_id=? ORDER BY r.id ASC');
$stmt->execute([$sessionId]);
$details=$stmt->fetchAll();
render_header('Hasil Tim','team');
?>
<div class="result-hero"><h1>Hasil <?= e($session['team_name'] ?: 'Tim') ?> 🎉</h1><p><?= e($session['room_name'] ?: 'Room Tim') ?> | Kode: <?= e($session['code'] ?: '-') ?></p><div class="score-big"><?= e($session['score']) ?></div><p>Benar: <b><?= e($session['total_correct']) ?></b> | Salah: <b><?= e($session['total_wrong']) ?></b> | Waktu: <b><?= e($session['duration_seconds']) ?> detik</b></p></div>
<div class="row g-4 mt-1"><div class="col-lg-4"><div class="panel-card"><h3>Anggota dan Peran</h3><?php foreach($members as $m): ?><div class="role-chip"><span><?= e($m['name'] ?? '-') ?></span><b><?= e($m['role'] ?? '-') ?></b></div><?php endforeach; ?></div></div><div class="col-lg-8"><div class="panel-card"><h3>Detail Benar/Salah Jawaban Tim</h3><?php if(!$details): ?><div class="alert alert-info">Belum ada detail jawaban tersimpan.</div><?php endif; ?><div class="team-answer-list"><?php foreach($details as $i=>$d): $ok=(int)$d['is_correct']===1; ?><div class="team-answer-row <?= $d['is_correct']===null?'essay':($ok?'correct':'wrong') ?>"><div><b>Soal <?= $i+1 ?></b><br><small><?= e($d['type']) ?></small></div><div><b><?= e($d['prompt']) ?></b><br>Jawaban tim: <span><?= e($d['answer'] ?: '-') ?></span><br><small>Kunci: <?= e($d['answer_key']) ?></small></div><div><?= $d['is_correct']===null?'<span class="badge text-bg-warning">Esai</span>':($ok?'<span class="badge text-bg-success">Benar</span>':'<span class="badge text-bg-danger">Salah</span>') ?></div></div><?php endforeach; ?></div></div></div></div>
<div class="panel-card mt-4"><a class="btn btn-primary rounded-pill" href="<?= url('student/reflection.php') ?>">Isi Refleksi Belajar</a> <a class="btn btn-outline-secondary rounded-pill" href="<?= url('student/dashboard.php') ?>">Kembali ke Beranda</a></div>
<?php render_footer(); ?>
