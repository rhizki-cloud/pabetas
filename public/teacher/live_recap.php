<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$gameId = (int)($_GET['game'] ?? 0);
$game = live_game_fetch($gameId);
if (!$game) redirect('teacher/live_games.php');
$ranking = live_game_ranking($gameId, 100);
$rounds = live_game_recap_rounds($gameId);
render_header('Rekap Live Game', 'live_games');
?>
<div class="page-title"><h1>📋 Rekap Sesi Live Game</h1><p>Detail jawaban per soal, waktu menjawab, skor ronde, dan ranking akhir.</p></div>
<div class="panel-card mb-4">
  <div class="d-flex justify-content-between flex-wrap gap-2">
    <div><h3><?= e($game['name']) ?></h3><p class="mb-0">Kode: <b><?= e($game['code']) ?></b> | Mode: <?= e($game['mode']) ?> | Kontrol: <?= e($game['control_mode'] ?? 'auto') ?></p></div>
    <div class="recap-actions"><a class="btn btn-success rounded-pill" href="<?= url('teacher/live_recap_excel.php?game='.$game['id']) ?>">Download Excel</a><a class="btn btn-danger rounded-pill" href="<?= url('teacher/live_recap_pdf.php?game='.$game['id']) ?>">Download PDF</a><a class="btn btn-primary rounded-pill" href="<?= url('teacher/live_monitor.php?game='.$game['id']) ?>">Kembali ke Monitor</a></div>
  </div>
</div>
<div class="row g-4 mb-4">
  <div class="col-lg-5"><div class="panel-card"><h3>🏆 Ranking Akhir</h3>
    <div class="ranking-list">
      <?php foreach($ranking as $i=>$r): ?>
      <div class="rank-item <?= $i===0?'rank-gold':'' ?>"><div class="rank-no"><?= $i===0?'👑':$i+1 ?></div><div class="rank-name"><?= e($r['avatar_emoji']) ?> <?= e($r['display_name']) ?></div><div class="rank-score"><?= (int)$r['score'] ?> poin</div><div class="rank-correct">✅ <?= (int)$r['total_correct'] ?></div></div>
      <?php endforeach; ?>
      <?php if(!$ranking): ?><div class="empty-ranking">Belum ada peserta.</div><?php endif; ?>
    </div>
  </div></div>
  <div class="col-lg-7"><div class="panel-card"><h3>📈 Grafik Skor</h3><canvas id="recapChart" height="180"></canvas></div></div>
</div>
<?php if(!$rounds): ?><div class="alert alert-info fw-bold">Belum ada jawaban yang terekam pada game ini.</div><?php endif; ?>
<?php foreach($rounds as $idx=>$round): ?>
<div class="panel-card mb-3">
  <h4>Soal <?= $idx+1 ?>: <?= e($round['prompt']) ?></h4>
  <p class="correct-answer-box">Kunci: <b><?= e($round['answer_key']) ?></b></p>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Ranking ronde</th><th>Peserta</th><th>Jawaban</th><th>Status</th><th>Skor</th><th>Waktu</th></tr></thead><tbody>
  <?php foreach($round['answers'] as $i=>$a): ?><tr><td><?= $i+1 ?></td><td><?= e($a['avatar_emoji']) ?> <?= e($a['display_name']) ?></td><td><?= e($a['answer']) ?></td><td><?= (int)$a['is_correct']===1?'✅ Benar':'❌ Salah' ?></td><td><b><?= (int)$a['score'] ?></b></td><td><?= number_format(((int)$a['response_time_ms'])/1000,1) ?> detik</td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php endforeach; ?>
<script>
const labels = <?= json_encode(array_column($ranking, 'display_name')) ?>;
const scores = <?= json_encode(array_map('intval', array_column($ranking, 'score'))) ?>;
const canvas = document.getElementById('recapChart');
if(canvas){const ctx=canvas.getContext('2d');const w=canvas.width=canvas.offsetWidth;const h=canvas.height=220;const max=Math.max(10,...scores);ctx.clearRect(0,0,w,h);ctx.font='13px system-ui';scores.forEach((v,i)=>{const barW=(w-40)/Math.max(1,scores.length);const bh=(v/max)*(h-60);const x=20+i*barW;const y=h-30-bh;ctx.fillStyle='#2563eb';ctx.fillRect(x,y,Math.max(16,barW-12),bh);ctx.fillStyle='#1e293b';ctx.fillText(v,x,y-6);ctx.save();ctx.translate(x+4,h-8);ctx.rotate(-0.3);ctx.fillText((labels[i]||'').slice(0,12),0,0);ctx.restore();});}
</script>
<?php render_footer(); ?>
