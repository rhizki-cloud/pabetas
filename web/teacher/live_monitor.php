<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$gameId = (int)($_GET['game'] ?? 0);
$game = live_game_fetch($gameId);
if (!$game) redirect('teacher/live_games.php');
render_header('Monitor Live Game', 'live_games');
?>
<div class="live-monitor-shell">
  <div class="live-monitor-top">
    <div>
      <div class="small text-uppercase fw-bold opacity-75">Kode Game</div>
      <div class="live-code"><?= e($game['code']) ?></div>
      <p class="mb-0">Siswa masuk dari menu <b>Live Game</b>, lalu isi kode ini.</p>
    </div>
    <div class="text-end">
      <h1><?= e($game['name']) ?></h1>
      <a class="btn btn-light rounded-pill" href="<?= url('teacher/live_games.php') ?>">Kelola Game</a> <a class="btn btn-warning rounded-pill" href="<?= url('teacher/live_recap.php?game='.$game['id']) ?>">Rekap Sesi</a>
    </div>
  </div>
  <div id="liveGameApp" class="live-game-app"></div>
</div>
<script>
window.LIVE_GAME_ID = <?= (int)$game['id'] ?>;
window.LIVE_GAME_ROLE = 'guru';
window.CURRENT_USER_ID = <?= (int)current_user()['id'] ?>;
window.LIVE_STATUS_URL = '<?= url('api/live_game_status.php') ?>';
window.LIVE_ANSWER_URL = '<?= url('api/live_game_answer.php') ?>';
window.LIVE_CONTROL_URL = '<?= url('api/live_game_control.php') ?>';
</script>
<script src="<?= url('assets/js/sounds.js') ?>"></script>
<script src="<?= url('assets/js/live_game.js') ?>"></script>
<?php render_footer(); ?>
