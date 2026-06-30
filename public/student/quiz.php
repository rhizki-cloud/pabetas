<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo=db();
if (empty($_SESSION['active_session_id'])) {
    $pdo->prepare("INSERT INTO game_sessions(user_id,mode,status,started_at) VALUES(?,?,?,NOW())")->execute([current_user()['id'],'individu','running']);
    $_SESSION['active_session_id']=$pdo->lastInsertId();
}
$sessionId=(int)$_SESSION['active_session_id'];
$questions=$pdo->query("SELECT * FROM questions WHERE status=1 AND mode='individu' ORDER BY RAND() LIMIT 20")->fetchAll();
render_header('Kuis Individu','quiz');
?>
<div class="page-title"><h1>Kuis Individu</h1><p>Jawaban tersimpan otomatis. Jika ada esai, hasilnya akan muncul di menu <b>Esai Saya</b> setelah guru menilai.</p></div>
<div class="quiz-top"><div class="timer-box">⏱ <span id="timer">20:00</span></div><div class="save-status" id="saveStatus">Autosave aktif</div></div>
<form method="post" action="<?= url('student/submit_quiz.php') ?>" id="quizForm">
<?= csrf_field() ?><input type="hidden" name="session_id" value="<?= $sessionId ?>">
<?php foreach($questions as $i=>$q): $opts=json_decode($q['options_json']?:'[]',true)?:[]; ?>
<div class="question-card"><div class="question-number">Soal <?= $i+1 ?> • <?= e($q['type']) ?></div><h3><?= e($q['prompt']) ?></h3>
<?php if($q['type']==='multiple'): foreach($opts as $opt): ?><label class="option-box"><input type="radio" name="answer[<?= $q['id'] ?>]" data-question-id="<?= $q['id'] ?>" value="<?= e($opt) ?>"> <?= e($opt) ?></label><?php endforeach; ?>
<?php elseif($q['type']==='essay'): ?><textarea class="form-control answer-input" rows="3" name="answer[<?= $q['id'] ?>]" data-question-id="<?= $q['id'] ?>" placeholder="Tulis cara berpikirmu di sini..."></textarea>
<?php else: ?><input class="form-control form-control-lg answer-input" name="answer[<?= $q['id'] ?>]" data-question-id="<?= $q['id'] ?>" placeholder="Contoh: 3000 m"><?php endif; ?>
</div>
<?php endforeach; ?>
<button class="btn btn-success btn-lg rounded-pill px-5">Selesai & Lihat Hasil</button>
</form>
<script>window.PABETAS_SESSION_ID = <?= $sessionId ?>;</script>
<script src="<?= url('assets/js/quiz_autosave.js') ?>"></script>
<?php render_footer(); ?>
