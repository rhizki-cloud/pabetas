<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$gameId=(int)($_GET['game'] ?? 0);
$game=live_game_fetch($gameId); if(!$game) exit('Game tidak ditemukan');
$ranking=live_game_ranking($gameId,100);
$rounds=live_game_recap_rounds($gameId);
$filename='rekap_live_game_'.$game['code'].'.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo "<table border='1'>";
echo "<tr><th colspan='6'>Rekap Live Game PABETAS</th></tr>";
echo "<tr><td>Nama Game</td><td colspan='5'>".e($game['name'])."</td></tr>";
echo "<tr><td>Kode</td><td>".e($game['code'])."</td><td>Mode</td><td>".e($game['mode'])."</td><td>Status</td><td>".e($game['status'])."</td></tr>";
echo "<tr><th colspan='6'>Ranking Akhir</th></tr><tr><th>Rank</th><th>Peserta</th><th>Skor</th><th>Benar</th><th>Salah</th><th>Avatar</th></tr>";
foreach($ranking as $i=>$r){echo "<tr><td>".($i+1)."</td><td>".e($r['display_name'])."</td><td>".(int)$r['score']."</td><td>".(int)$r['total_correct']."</td><td>".(int)$r['total_wrong']."</td><td>".e($r['avatar_emoji'])."</td></tr>";}
echo "<tr><th colspan='6'>Detail Per Ronde</th></tr><tr><th>Ronde</th><th>Soal</th><th>Peserta</th><th>Jawaban</th><th>Status</th><th>Waktu Detik</th></tr>";
foreach($rounds as $idx=>$round){foreach($round['answers'] as $a){echo "<tr><td>".($idx+1)."</td><td>".e($round['prompt'])."</td><td>".e($a['display_name'])."</td><td>".e($a['answer'])."</td><td>".((int)$a['is_correct']===1?'Benar':'Salah')."</td><td>".number_format(((int)$a['response_time_ms'])/1000,1)."</td></tr>";}}
echo "</table>";
