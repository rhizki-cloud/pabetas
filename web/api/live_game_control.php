<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
verify_csrf();
$raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$gameId = (int)($raw['game_id'] ?? 0);
$action = $raw['action'] ?? '';
$game = live_game_fetch($gameId);
if (!$game) json_response(['ok'=>false,'message'=>'Game tidak ditemukan.'], 404);
$pdo = db();
if ($action === 'start') {
    $pdo->prepare('DELETE FROM live_game_answers WHERE live_game_id=?')->execute([$gameId]);
    $pdo->prepare('DELETE FROM live_game_powerup_uses WHERE live_game_id=?')->execute([$gameId]);
    $pdo->prepare('UPDATE live_game_players SET score=0,total_correct=0,total_wrong=0,last_seen=NOW() WHERE live_game_id=?')->execute([$gameId]);
    $pdo->prepare("UPDATE live_games SET status='running', phase='question', current_index=0, started_at=NOW(), phase_started_at=NOW(), finished_at=NULL WHERE id=?")->execute([$gameId]);
} elseif ($action === 'show_ranking') {
    live_game_move_ranking($game);
} elseif ($action === 'next_question') {
    live_game_move_next($game);
} elseif ($action === 'finish') {
    $pdo->prepare("UPDATE live_games SET status='finished', phase='final', finished_at=NOW(), phase_started_at=NOW() WHERE id=?")->execute([$gameId]);
} elseif ($action === 'back_to_question') {
    $pdo->prepare("UPDATE live_games SET phase='question', phase_started_at=NOW() WHERE id=?")->execute([$gameId]);
} else {
    json_response(['ok'=>false,'message'=>'Aksi kontrol tidak valid.'], 422);
}
json_response(['ok'=>true,'game'=>live_game_fetch($gameId)]);
