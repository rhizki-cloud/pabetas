<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$raw = json_decode(file_get_contents('php://input'), true) ?: [];
$gameId = (int)($raw['game_id'] ?? 0);
$type = $raw['type'] ?? '';
$allowed = ['ladder_help','fifty','bonus_time'];
if (!in_array($type, $allowed, true)) json_response(['ok'=>false,'message'=>'Power-up tidak valid.'], 422);
$game = live_game_fetch($gameId);
if (!$game) json_response(['ok'=>false,'message'=>'Game tidak ditemukan.'], 404);
$game = live_game_advance($game);
if ($game['status'] !== 'running' || $game['phase'] !== 'question') json_response(['ok'=>false,'message'=>'Power-up hanya bisa dipakai saat soal berjalan.'], 422);
$q = live_game_current_question($game);
$player = live_game_ensure_player($game, current_user());
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM live_game_powerup_uses WHERE live_game_id=? AND player_id=? AND powerup_type=? LIMIT 1');
$stmt->execute([(int)$game['id'], (int)$player['id'], $type]);
if ($stmt->fetch()) json_response(['ok'=>false,'message'=>'Power-up ini sudah dipakai dalam game ini.'], 422);
$data = [];
$message = '';
if ($type === 'ladder_help') {
    $message = 'Bantuan: turun tangga berarti dikali 10, naik tangga berarti dibagi 10. Hitung jumlah tingkat dari satuan asal ke satuan tujuan.';
    $data = ['hint'=>$message];
} elseif ($type === 'bonus_time') {
    $message = 'Bonus waktu aktif. Ronde ini mendapat tambahan 10 detik.';
    $data = ['bonus_seconds'=>10];
} elseif ($type === 'fifty') {
    $options = json_decode($q['options_json'] ?: '[]', true) ?: [];
    $wrong = [];
    foreach ($options as $i=>$opt) if (normalize_answer($opt) !== normalize_answer($q['answer_key'])) $wrong[] = $i;
    shuffle($wrong);
    $removed = array_slice($wrong, 0, min(2, count($wrong)));
    $message = 'Dua pilihan yang kurang tepat sudah disembunyikan.';
    $data = ['removed_options'=>$removed];
}
try {
    $pdo->prepare('INSERT INTO live_game_powerup_uses(live_game_id,player_id,user_id,question_index,powerup_type,data_json,used_at) VALUES(?,?,?,?,?,?,NOW())')
        ->execute([(int)$game['id'], (int)$player['id'], current_user()['id'], (int)$game['current_index'], $type, json_encode($data)]);
} catch (Throwable $e) {
    json_response(['ok'=>false,'message'=>'Power-up gagal dipakai.'], 500);
}
json_response(['ok'=>true,'type'=>$type,'message'=>$message,'data'=>$data]);
