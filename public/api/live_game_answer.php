<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$raw = json_decode(file_get_contents('php://input'), true) ?: [];
$gameId = (int)($raw['game_id'] ?? 0);
$answerText = trim((string)($raw['answer'] ?? ''));
$game = live_game_fetch($gameId);
if (!$game) json_response(['ok'=>false,'message'=>'Game tidak ditemukan.'], 404);
$game = live_game_advance($game);
if ($game['status'] !== 'running' || $game['phase'] !== 'question') json_response(['ok'=>false,'message'=>'Saat ini bukan fase menjawab.'], 422);
$q = live_game_current_question($game);
if (!$q) json_response(['ok'=>false,'message'=>'Soal tidak ditemukan.'], 404);
$player = live_game_ensure_player($game, current_user());
if (live_game_player_answer($game, $player)) {
    json_response(['ok'=>true,'message'=>'Jawaban sudah tersimpan.', 'ranking'=>live_game_ranking((int)$game['id'])]);
}
[$isCorrect, $baseScore] = live_game_grade_answer($q, $answerText);
$elapsed = live_game_elapsed_seconds($game);
$limit = live_game_question_limit_seconds($game);
$remaining = max(0, $limit - $elapsed);
$speedBonus = $isCorrect ? (int)round(($remaining / max(1, $limit)) * 5) : 0;
$score = (int)$baseScore + $speedBonus;
$responseMs = $elapsed * 1000;
$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO live_game_answers(live_game_id,player_id,user_id,question_id,question_index,answer,is_correct,score,response_time_ms,answered_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())')
        ->execute([(int)$game['id'], (int)$player['id'], current_user()['id'], (int)$q['id'], (int)$game['current_index'], $answerText, $isCorrect ? 1 : 0, $score, $responseMs]);
    $pdo->prepare('UPDATE live_game_players SET score=score+?, total_correct=total_correct+?, total_wrong=total_wrong+?, last_seen=NOW() WHERE id=?')
        ->execute([$score, $isCorrect ? 1 : 0, $isCorrect ? 0 : 1, (int)$player['id']]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['ok'=>false,'message'=>'Jawaban gagal disimpan.'], 500);
}
$game = live_game_fetch((int)$game['id']);
if (($game['control_mode'] ?? 'auto') === 'auto' && live_game_all_joined_answered($game)) {
    db()->prepare("UPDATE live_games SET phase='ranking', phase_started_at=NOW() WHERE id=?")->execute([(int)$game['id']]);
    $game = live_game_fetch((int)$game['id']);
}
json_response([
    'ok'=>true,
    'is_correct'=>(bool)$isCorrect,
    'score'=>$score,
    'speed_bonus'=>$speedBonus,
    'message'=>$isCorrect ? 'Jawaban benar!' : 'Belum tepat, tetap semangat!',
    'correct_answer'=>$q['answer_key'],
    'ranking'=>live_game_ranking((int)$game['id']),
    'game_phase'=>$game['phase']
]);
