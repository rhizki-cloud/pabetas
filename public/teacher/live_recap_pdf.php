<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$gameId=(int)($_GET['game'] ?? 0);
$game=live_game_fetch($gameId); if(!$game) exit('Game tidak ditemukan');
$ranking=live_game_ranking($gameId,100);
$rounds=live_game_recap_rounds($gameId);
ob_start();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Rekap Live Game</title><style>body{font-family:Arial,sans-serif;color:#111827}.header{display:flex;gap:14px;align-items:center;border-bottom:4px solid #2563eb;padding-bottom:12px}.logo{width:64px}.muted{color:#64748b}table{width:100%;border-collapse:collapse;margin-top:14px}th,td{border:1px solid #cbd5e1;padding:8px;font-size:12px}th{background:#dbeafe}.section{margin-top:24px}.badge{font-weight:bold}</style></head><body>
<div class="header"><img class="logo" src="<?= e(url(SCHOOL_LOGO)) ?>"><div><h1>Rekap Live Game PABETAS</h1><div class="muted">Kode <?= e($game['code']) ?> | <?= e($game['name']) ?> | Mode <?= e($game['mode']) ?></div></div></div>
<div class="section"><h2>Ranking Akhir</h2><table><tr><th>Rank</th><th>Peserta</th><th>Skor</th><th>Benar</th><th>Salah</th></tr><?php foreach($ranking as $i=>$r): ?><tr><td><?= $i+1 ?></td><td><?= e($r['avatar_emoji']) ?> <?= e($r['display_name']) ?></td><td><?= (int)$r['score'] ?></td><td><?= (int)$r['total_correct'] ?></td><td><?= (int)$r['total_wrong'] ?></td></tr><?php endforeach; ?></table></div>
<div class="section"><h2>Detail Jawaban Per Ronde</h2><?php foreach($rounds as $idx=>$round): ?><h3>Soal <?= $idx+1 ?>: <?= e($round['prompt']) ?></h3><p>Kunci: <b><?= e($round['answer_key']) ?></b></p><table><tr><th>Peserta</th><th>Jawaban</th><th>Status</th><th>Skor</th><th>Waktu</th></tr><?php foreach($round['answers'] as $a): ?><tr><td><?= e($a['display_name']) ?></td><td><?= e($a['answer']) ?></td><td><?= (int)$a['is_correct']===1?'Benar':'Salah' ?></td><td><?= (int)$a['score'] ?></td><td><?= number_format(((int)$a['response_time_ms'])/1000,1) ?> detik</td></tr><?php endforeach; ?></table><?php endforeach; ?></div>
<script>window.print()</script></body></html>
<?php
$html=ob_get_clean();
if (file_exists(__DIR__.'/../../vendor/autoload.php')) {
    require_once __DIR__.'/../../vendor/autoload.php';
    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new Dompdf\Dompdf(['isRemoteEnabled'=>true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        $dompdf->stream('rekap_live_game_'.$game['code'].'.pdf');
        exit;
    }
}
header('Content-Type: text/html; charset=utf-8');
echo $html;
