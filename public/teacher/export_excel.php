<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$pdo=db();
$rows=$pdo->query("SELECT u.name, u.username, gs.score, gs.total_correct, gs.total_wrong, gs.started_at, gs.finished_at FROM game_sessions gs JOIN users u ON u.id=gs.user_id WHERE gs.status='finished' ORDER BY gs.score DESC")->fetchAll();
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_pabetas.xls"');
echo "<table border='1'>";
echo "<tr><th colspan='7'>Laporan Hasil Belajar PABETAS - ".e(SCHOOL_NAME)."</th></tr>";
echo "<tr><th>Nama</th><th>Username</th><th>Skor</th><th>Benar</th><th>Salah</th><th>Mulai</th><th>Selesai</th></tr>";
foreach($rows as $r){ echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['username']).'</td><td>'.e($r['score']).'</td><td>'.e($r['total_correct']).'</td><td>'.e($r['total_wrong']).'</td><td>'.e($r['started_at']).'</td><td>'.e($r['finished_at']).'</td></tr>'; }
echo "</table>";
