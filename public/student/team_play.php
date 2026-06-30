<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo = db();
try { $pdo->exec("ALTER TABLE game_sessions ADD COLUMN IF NOT EXISTS team_id INT NULL AFTER room_id"); } catch (Throwable $e) {}
$roomId = (int)($_GET['room'] ?? 0);
$teamId = (int)($_GET['team'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE id=? AND mode="tim" LIMIT 1');
$stmt->execute([$roomId]);
$room = $stmt->fetch();
$stmt = $pdo->prepare('SELECT * FROM teams WHERE id=? AND room_id=? LIMIT 1');
$stmt->execute([$teamId, $roomId]);
$team = $stmt->fetch();
if (!$room || !$team) redirect('student/team_join.php');
$members = json_decode($team['members_json'] ?: '[]', true) ?: [];

$key = 'team_session_'.$roomId.'_'.$teamId;
if (empty($_SESSION[$key])) {
    $pdo->prepare("INSERT INTO game_sessions(user_id,room_id,team_id,mode,status,started_at) VALUES(?,?,?,?,?,NOW())")->execute([current_user()['id'],$roomId,$teamId,'tim','running']);
    $_SESSION[$key] = $pdo->lastInsertId();
}
$sessionId = (int)$_SESSION[$key];
$qkey = 'team_questions_'.$roomId.'_'.$teamId;
if (empty($_SESSION[$qkey])) {
    $count = max(1, min(20, (int)($room['team_question_count'] ?? 5)));
    $pool = $pdo->query("SELECT id, score, difficulty FROM questions WHERE status=1 AND mode='tim' ORDER BY score ASC, difficulty ASC, id ASC")->fetchAll();
    $ids = [];
    if ($pool) {
        $offset = $teamId % count($pool);
        $rotated = array_merge(array_slice($pool, $offset), array_slice($pool, 0, $offset));
        foreach ($rotated as $row) { if (count($ids) >= $count) break; $ids[] = (int)$row['id']; }
    }
    $_SESSION[$qkey] = $ids;
}
$ids = $_SESSION[$qkey] ?: [];
$questions = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $q) $byId[(int)$q['id']] = $q;
    foreach ($ids as $id) if (isset($byId[$id])) $questions[] = $byId[$id];
}
$seconds = max(10, (int)($room['question_seconds'] ?? 45));
render_header('Game Tim', 'team_game');
?>
<div class="page-title compact-title"><h1><?= e($team['name']) ?> 🎯</h1><p><?= e($room['name']) ?> | satu device untuk satu kelompok | <?= $seconds ?> detik per soal</p></div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-card team-role-panel sticky-lg-top sticky-offset"><h3>Peran Kelompok</h3>
      <?php foreach($members as $m): ?><div class="role-chip"><span><?= e($m['name'] ?? '-') ?></span><b><?= e($m['role'] ?? '-') ?></b></div><?php endforeach; ?>
      <hr><p class="small text-muted mb-0">Peran ini ditentukan guru. Sistem hanya menampilkan agar diskusi tim lebih terarah.</p>
      <div class="d-grid gap-2 mt-3"><a class="btn btn-warning rounded-pill" href="<?= url('student/learn.php') ?>">Papan Belajar</a><a class="btn btn-primary rounded-pill" href="<?= url('student/ladder.php') ?>">Tangga Satuan</a></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel-card team-game-shell" data-seconds="<?= $seconds ?>">
      <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center"><h3 class="mb-0">Soal Tim Berwaktu</h3><div class="timer-pill"><span id="teamTimer"><?= $seconds ?></span> detik</div></div>
      <?php if(!$questions): ?><div class="alert alert-danger mt-3">Belum ada soal tim aktif. Guru perlu menambahkan bank soal mode tim.</div><?php else: ?>
      <form method="post" action="<?= url('student/submit_team.php') ?>" id="teamAnswerForm">
        <?= csrf_field() ?><input type="hidden" name="room_id" value="<?= $roomId ?>"><input type="hidden" name="team_id" value="<?= $teamId ?>"><input type="hidden" name="session_id" value="<?= $sessionId ?>">
        <?php foreach($questions as $i=>$q): $opts=json_decode($q['options_json']?:'[]',true)?:[]; ?>
        <section class="team-question-slide <?= $i===0?'active':'' ?>" data-index="<?= $i ?>">
          <div class="question-number">Soal <?= $i+1 ?>/<?= count($questions) ?> • Bobot <?= (int)$q['score'] ?> • <?= e($q['difficulty'] ?? 'mudah') ?></div>
          <h2><?= e($q['prompt']) ?></h2>
          <?php if($q['type']==='multiple'): foreach($opts as $opt): ?><label class="option-box"><input type="radio" name="answer[<?= $q['id'] ?>]" value="<?= e($opt) ?>"> <?= e($opt) ?></label><?php endforeach; ?>
          <?php elseif($q['type']==='essay'): ?><textarea class="form-control" name="answer[<?= $q['id'] ?>]" rows="4" placeholder="Tulis jawaban hasil diskusi tim"></textarea>
          <?php else: ?><input class="form-control form-control-lg" name="answer[<?= $q['id'] ?>]" placeholder="Jawaban tim, contoh: 500 dm"><?php endif; ?>
        </section>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-center mt-4"><button type="button" class="btn btn-outline-secondary rounded-pill" id="prevTeamQuestion">Sebelumnya</button><button type="button" class="btn btn-primary rounded-pill" id="nextTeamQuestion">Soal Berikutnya</button><button class="btn btn-success rounded-pill d-none" id="submitTeamBtn">Submit Jawaban Tim</button></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
(function(){
 const shell=document.querySelector('.team-game-shell'); if(!shell) return;
 const slides=[...document.querySelectorAll('.team-question-slide')]; if(!slides.length) return;
 let current=0, seconds=parseInt(shell.dataset.seconds||'45'), left=seconds, timer=null;
 const timerEl=document.getElementById('teamTimer'), next=document.getElementById('nextTeamQuestion'), prev=document.getElementById('prevTeamQuestion'), submit=document.getElementById('submitTeamBtn');
 function show(i){ current=Math.max(0,Math.min(slides.length-1,i)); slides.forEach((s,k)=>s.classList.toggle('active',k===current)); left=seconds; tickLabel(); next.classList.toggle('d-none', current===slides.length-1); submit.classList.toggle('d-none', current!==slides.length-1); prev.disabled=current===0; window.pabetasSound?.('team'); }
 function tickLabel(){ if(timerEl) timerEl.textContent=left; }
 function autoNext(){ if(current<slides.length-1) show(current+1); else { submit.classList.remove('pulse-submit'); void submit.offsetWidth; submit.classList.add('pulse-submit'); window.pabetasSound?.('countdown'); } }
 timer=setInterval(()=>{ left--; tickLabel(); if(left<=5 && left>0) window.pabetasSound?.('countdown'); if(left<=0) autoNext(); },1000);
 next?.addEventListener('click',()=>show(current+1)); prev?.addEventListener('click',()=>show(current-1)); show(0);
})();
</script>
<?php render_footer(); ?>
