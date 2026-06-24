<?php
require_once __DIR__ . '/../../app/init.php';
require_login();
$gameId = (int)($_GET['game'] ?? 0);
$game = live_game_fetch($gameId);
if (!$game) json_response(['ok'=>false,'message'=>'Game tidak ditemukan.'], 404);
$game = live_game_advance($game);
$user = current_user();
$player = null;
$answer = null;
$powerups = [];
if ($user['role'] === 'murid') {
    $player = live_game_ensure_player($game, $user);
    $answer = live_game_player_answer($game, $player);
    $powerups = live_game_powerup_uses((int)$game['id'], (int)$player['id']);
}
$ids = live_game_question_ids($game);
$total = count($ids);
$question = null;
$correctAnswer = null;
if ($game['phase'] === 'question' || $game['phase'] === 'ranking') {
    $q = live_game_current_question($game);
    if ($q) {
        $question = [
            'id' => (int)$q['id'],
            'type' => $q['type'],
            'prompt' => $q['prompt'],
            'options' => json_decode($q['options_json'] ?: '[]', true) ?: []
        ];
        if ($game['phase'] === 'ranking' || $user['role'] === 'guru') $correctAnswer = $q['answer_key'];
    }
}
$json = [
    'ok' => true,
    'server_time' => date('H:i:s'),
    'game' => [
        'id' => (int)$game['id'],
        'code' => $game['code'],
        'name' => $game['name'],
        'mode' => $game['mode'],
        'status' => $game['status'],
        'phase' => $game['phase'],
        'control_mode' => $game['control_mode'] ?? 'auto',
        'current_index' => (int)$game['current_index'],
        'total_questions' => $total,
        'question_number' => min($total, (int)$game['current_index'] + 1),
        'remaining_seconds' => live_game_remaining_seconds($game),
        'question_seconds' => live_game_question_limit_seconds($game),
        'base_question_seconds' => (int)$game['question_seconds'],
        'ranking_seconds' => (int)$game['ranking_seconds'],
        'bonus_time_active' => live_game_bonus_active($game)
    ],
    'question' => $question,
    'correct_answer' => $correctAnswer,
    'has_answered' => (bool)$answer,
    'my_answer' => $answer ? ['is_correct'=>$answer['is_correct'], 'score'=>(int)$answer['score'], 'answer'=>$answer['answer']] : null,
    'my_powerups' => $powerups,
    'players' => live_game_players((int)$game['id']),
    'ranking' => live_game_ranking((int)$game['id'], 50)
];
json_response($json);
