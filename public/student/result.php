<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo=db();
$sessionId=(int)($_GET['session_id'] ?? 0);
$stmt=$pdo->prepare('SELECT * FROM game_sessions WHERE id=? AND user_id=?'); $stmt->execute([$sessionId,current_user()['id']]); $session=$stmt->fetch();
if(!$session) redirect('student/dashboard.php');
if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $pdo->prepare('INSERT INTO evaluations(user_id,session_id,mood,reflection) VALUES(?,?,?,?)')->execute([current_user()['id'],$sessionId,$_POST['mood'],trim($_POST['reflection'])]); flash('success','Refleksi tersimpan. Terima kasih!'); redirect('student/ranking.php'); }
render_header('Hasil Kuis','quiz');
?>
<div class="result-hero"><h1>Hebat! Kuis selesai 🎉</h1><div class="score-big"><?= e($session['score']) ?></div><p>Benar: <b><?= e($session['total_correct']) ?></b> | Salah: <b><?= e($session['total_wrong']) ?></b></p></div>
<div class="panel-card mt-4"><h3>Refleksi Belajar</h3><?php if($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><label class="form-label">Perasaanmu setelah belajar?</label><select name="mood" class="form-select mb-3"><option>Senang</option><option>Biasa saja</option><option>Masih bingung</option><option>Ingin mencoba lagi</option></select><label class="form-label">Catatan singkat</label><textarea name="reflection" class="form-control mb-3" rows="3" placeholder="Contoh: Aku sudah paham turun dikali, naik dibagi."></textarea><button class="btn btn-primary rounded-pill">Kirim Refleksi</button><a class="btn btn-warning rounded-pill" href="<?= url('student/ranking.php') ?>">Lihat Ranking</a></form></div>
<?php render_footer(); ?>
