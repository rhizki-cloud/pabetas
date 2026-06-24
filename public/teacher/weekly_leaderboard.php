<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$stmt = db()->query("SELECT u.name, u.avatar_key, SUM(p.score) AS total_score, SUM(p.total_correct) AS total_correct, COUNT(DISTINCT p.live_game_id) AS games_played FROM live_game_players p JOIN users u ON u.id=p.user_id JOIN live_games g ON g.id=p.live_game_id WHERE g.status='finished' AND YEARWEEK(COALESCE(g.finished_at, NOW()), 1)=YEARWEEK(NOW(), 1) GROUP BY u.id, u.name, u.avatar_key ORDER BY total_score DESC, total_correct DESC LIMIT 50");
$rows = $stmt->fetchAll();
render_header('Leaderboard Mingguan', 'weekly');
?>
<div class="page-title"><h1>🏆 Leaderboard Kelas Mingguan</h1><p>Skor dari beberapa live game yang selesai pada minggu berjalan dikumpulkan menjadi ranking mingguan.</p></div>
<div class="panel-card">
<?php if(!$rows): ?><div class="alert alert-info">Belum ada live game yang selesai pada minggu ini.</div><?php else: ?>
<div class="ranking-list weekly-rank">
<?php foreach($rows as $i=>$r): $av=pabetas_avatar($r['avatar_key'] ?? 'rocket'); ?>
  <div class="rank-item <?= $i===0?'rank-gold':'' ?>"><div class="rank-no"><?= $i===0?'👑':$i+1 ?></div><div class="rank-name"><?= e($av['emoji']) ?> <?= e($r['name']) ?></div><div class="rank-score"><?= (int)$r['total_score'] ?> poin</div><div class="rank-correct">✅ <?= (int)$r['total_correct'] ?> | 🎮 <?= (int)$r['games_played'] ?></div></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php render_footer(); ?>
