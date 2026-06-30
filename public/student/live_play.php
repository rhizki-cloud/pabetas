<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$gameId = (int)($_GET['game'] ?? 0);
$game = live_game_fetch($gameId);
if (!$game || $game['status'] === 'finished' && $game['phase'] !== 'final') redirect('student/live_join.php');
live_game_ensure_player($game, current_user());
render_header('Live Game', 'live');
?>
<div class="live-student-shell">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
    <div><h1 class="mb-1">⚡ <?= e($game['name']) ?></h1><p class="mb-0 text-muted">Jawab cepat dan tepat. Ranking sementara akan muncul setelah soal dijawab.</p></div>
    <div class="room-code">Kode: <?= e($game['code']) ?></div>
  </div>
  <div id="liveGameApp" class="live-game-app"></div>
</div>
<script>
window.LIVE_GAME_ID = <?= (int)$game['id'] ?>;
window.LIVE_GAME_ROLE = 'murid';
window.CURRENT_USER_ID = <?= (int)current_user()['id'] ?>;
window.LIVE_STATUS_URL = '<?= url('api/live_game_status.php') ?>';
window.LIVE_ANSWER_URL = '<?= url('api/live_game_answer.php') ?>';
window.LIVE_POWERUP_URL = '<?= url('api/live_game_powerup.php') ?>';
</script>
<script src="<?= url('assets/js/sounds.js') ?>"></script>
<script src="<?= url('assets/js/live_game.js') ?>"></script>
<?php render_footer(); ?>
