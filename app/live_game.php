<?php
function pabetas_avatar_options(): array {
    return [
        'boy_hero' => ['emoji'=>'🧒','label'=>'Anak Hebat','color'=>'#38bdf8'],
        'girl_hero' => ['emoji'=>'👧','label'=>'Anak Pintar','color'=>'#fb7185'],
        'boy_cap' => ['emoji'=>'🧢','label'=>'Si Topi Biru','color'=>'#60a5fa'],
        'girl_bow' => ['emoji'=>'🎀','label'=>'Si Pita Ceria','color'=>'#f472b6'],
        'star' => ['emoji'=>'⭐','label'=>'Bintang Ceria','color'=>'#facc15'],
        'rocket' => ['emoji'=>'🚀','label'=>'Roket Cepat','color'=>'#38bdf8'],
        'cat' => ['emoji'=>'🐱','label'=>'Kucing Pintar','color'=>'#fb7185'],
        'robot' => ['emoji'=>'🤖','label'=>'Robot Teliti','color'=>'#8b5cf6'],
        'lion' => ['emoji'=>'🦁','label'=>'Singa Berani','color'=>'#f59e0b'],
        'rabbit' => ['emoji'=>'🐰','label'=>'Kelinci Cepat','color'=>'#f9a8d4'],
        'owl' => ['emoji'=>'🦉','label'=>'Burung Hantu Cerdas','color'=>'#a78bfa'],
        'panda' => ['emoji'=>'🐼','label'=>'Panda Tenang','color'=>'#94a3b8'],
        'book' => ['emoji'=>'📚','label'=>'Buku Hebat','color'=>'#22c55e'],
        'pencil' => ['emoji'=>'✏️','label'=>'Pensil Juara','color'=>'#f97316'],
        'trophy' => ['emoji'=>'🏆','label'=>'Juara Kelas','color'=>'#facc15'],
        'wizard' => ['emoji'=>'🧙','label'=>'Ahli Satuan','color'=>'#7c3aed'],
    ];
}
function pabetas_avatar($key): array {
    $options = pabetas_avatar_options();
    return $options[$key] ?? $options['rocket'];
}
function live_game_question_ids(array $game): array {
    $ids = json_decode($game['question_ids_json'] ?? '[]', true);
    if (!is_array($ids)) return [];
    return array_values(array_map('intval', $ids));
}
function live_game_fetch($gameId): ?array {
    $stmt = db()->prepare('SELECT * FROM live_games WHERE id=? LIMIT 1');
    $stmt->execute([(int)$gameId]);
    $game = $stmt->fetch();
    return $game ?: null;
}
function live_game_fetch_by_code($code): ?array {
    $stmt = db()->prepare('SELECT * FROM live_games WHERE code=? LIMIT 1');
    $stmt->execute([strtoupper(trim($code))]);
    $game = $stmt->fetch();
    return $game ?: null;
}
function live_game_elapsed_seconds(array $game): int {
    if (empty($game['phase_started_at'])) return 0;
    $stmt = db()->prepare('SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, phase_started_at, NOW())) FROM live_games WHERE id=? LIMIT 1');
    $stmt->execute([(int)$game['id']]);
    return max(0, (int)$stmt->fetchColumn());
}
function live_game_bonus_active(array $game): bool {
    if (($game['phase'] ?? '') !== 'question') return false;
    $stmt = db()->prepare("SELECT COUNT(*) FROM live_game_powerup_uses WHERE live_game_id=? AND question_index=? AND powerup_type='bonus_time'");
    $stmt->execute([(int)$game['id'], (int)$game['current_index']]);
    return (int)$stmt->fetchColumn() > 0;
}
function live_game_question_limit_seconds(array $game): int {
    $base = (int)($game['question_seconds'] ?? 20);
    return $base + (live_game_bonus_active($game) ? 10 : 0);
}
function live_game_remaining_seconds(array $game): int {
    $elapsed = live_game_elapsed_seconds($game);
    $limit = ($game['phase'] === 'ranking') ? (int)$game['ranking_seconds'] : live_game_question_limit_seconds($game);
    return max(0, $limit - $elapsed);
}
function live_game_current_question(array $game): ?array {
    $ids = live_game_question_ids($game);
    $index = (int)$game['current_index'];
    if (!isset($ids[$index])) return null;
    $stmt = db()->prepare('SELECT * FROM questions WHERE id=? LIMIT 1');
    $stmt->execute([$ids[$index]]);
    $q = $stmt->fetch();
    return $q ?: null;
}
function live_game_ensure_player(array $game, array $user): array {
    $pdo = db();
    $teamId = null;
    $display = $user['name'];
    if (($game['mode'] ?? 'individu') === 'tim' && !empty($game['room_id'])) {
        $stmt = $pdo->prepare('SELECT t.id, t.name FROM teams t JOIN team_members tm ON tm.team_id=t.id JOIN students s ON s.id=tm.student_id WHERE t.room_id=? AND s.user_id=? LIMIT 1');
        $stmt->execute([(int)$game['room_id'], (int)$user['id']]);
        if ($team = $stmt->fetch()) {
            $teamId = (int)$team['id'];
            $display = $team['name'] . ' - ' . $user['name'];
        }
    }
    $stmt = $pdo->prepare('SELECT * FROM live_game_players WHERE live_game_id=? AND user_id=? LIMIT 1');
    $stmt->execute([(int)$game['id'], (int)$user['id']]);
    $player = $stmt->fetch();
    if ($player) {
        $pdo->prepare('UPDATE live_game_players SET last_seen=NOW(), avatar_key=COALESCE(NULLIF(?, ""), avatar_key) WHERE id=?')->execute([$user['avatar_key'] ?? 'rocket', (int)$player['id']]);
        $stmt = $pdo->prepare('SELECT * FROM live_game_players WHERE id=? LIMIT 1');
        $stmt->execute([(int)$player['id']]);
        return $stmt->fetch();
    }
    $avatar = $user['avatar_key'] ?? 'rocket';
    $pdo->prepare('INSERT INTO live_game_players(live_game_id,user_id,team_id,display_name,avatar_key,joined_at,last_seen) VALUES(?,?,?,?,?,NOW(),NOW())')->execute([(int)$game['id'], (int)$user['id'], $teamId, $display, $avatar]);
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM live_game_players WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function live_game_player_answer(array $game, array $player): ?array {
    $question = live_game_current_question($game);
    if (!$question) return null;
    $stmt = db()->prepare('SELECT * FROM live_game_answers WHERE live_game_id=? AND player_id=? AND question_index=? LIMIT 1');
    $stmt->execute([(int)$game['id'], (int)$player['id'], (int)$game['current_index']]);
    $answer = $stmt->fetch();
    return $answer ?: null;
}
function live_game_ranking(int $gameId, int $limit = 50): array {
    $stmt = db()->prepare('SELECT id, user_id, display_name, avatar_key, score, total_correct, total_wrong, last_seen FROM live_game_players WHERE live_game_id=? ORDER BY score DESC, total_correct DESC, total_wrong ASC, last_seen DESC LIMIT ?');
    $stmt->bindValue(1, $gameId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $avatar = pabetas_avatar($r['avatar_key'] ?? 'rocket');
        $r['avatar_emoji'] = $avatar['emoji'];
        $r['avatar_label'] = $avatar['label'];
    }
    return $rows;
}
function live_game_players(int $gameId): array {
    return live_game_ranking($gameId, 100);
}
function live_game_all_joined_answered(array $game): bool {
    if (($game['phase'] ?? '') !== 'question') return false;
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM live_game_players WHERE live_game_id=?');
    $stmt->execute([(int)$game['id']]);
    $players = (int)$stmt->fetchColumn();
    if ($players <= 0) return false;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM live_game_answers WHERE live_game_id=? AND question_index=?');
    $stmt->execute([(int)$game['id'], (int)$game['current_index']]);
    $answers = (int)$stmt->fetchColumn();
    return $answers >= $players;
}
function live_game_advance(array $game): array {
    if (($game['status'] ?? '') !== 'running') return $game;
    if (($game['control_mode'] ?? 'auto') === 'manual') return $game;
    $pdo = db();

    // Sinkronisasi fase dilakukan beberapa kali agar tidak macet ketika browser sempat berhenti polling.
    for ($i = 0; $i < 4; $i++) {
        $game = live_game_fetch((int)$game['id']) ?: $game;
        if (($game['status'] ?? '') !== 'running') break;
        if (($game['control_mode'] ?? 'auto') === 'manual') break;

        $phase = $game['phase'] ?? 'waiting';
        if ($phase === 'question') {
            $elapsed = live_game_elapsed_seconds($game);
            $limit = live_game_question_limit_seconds($game);
            if ($elapsed >= $limit || live_game_all_joined_answered($game)) {
                $pdo->prepare("UPDATE live_games SET phase='ranking', phase_started_at=NOW() WHERE id=? AND phase='question'")->execute([(int)$game['id']]);
                continue;
            }
            break;
        }

        if ($phase === 'ranking') {
            $elapsed = live_game_elapsed_seconds($game);
            $limit = max(1, (int)$game['ranking_seconds']);
            if ($elapsed >= $limit) {
                $ids = live_game_question_ids($game);
                $next = (int)$game['current_index'] + 1;
                if ($next >= count($ids)) {
                    $pdo->prepare("UPDATE live_games SET status='finished', phase='final', finished_at=NOW(), phase_started_at=NOW() WHERE id=?")->execute([(int)$game['id']]);
                } else {
                    $pdo->prepare("UPDATE live_games SET current_index=?, phase='question', phase_started_at=NOW() WHERE id=?")->execute([$next, (int)$game['id']]);
                }
                continue;
            }
            break;
        }

        break;
    }
    return live_game_fetch((int)$game['id']) ?: $game;
}
function live_game_move_ranking(array $game): array {
    if (($game['status'] ?? '') !== 'running') return $game;
    db()->prepare("UPDATE live_games SET phase='ranking', phase_started_at=NOW() WHERE id=?")->execute([(int)$game['id']]);
    return live_game_fetch((int)$game['id']);
}
function live_game_move_next(array $game): array {
    $ids = live_game_question_ids($game);
    $total = count($ids);
    $next = (int)$game['current_index'] + 1;
    if ($next >= $total) {
        db()->prepare("UPDATE live_games SET status='finished', phase='final', finished_at=NOW(), phase_started_at=NOW() WHERE id=?")->execute([(int)$game['id']]);
    } else {
        db()->prepare("UPDATE live_games SET current_index=?, phase='question', phase_started_at=NOW() WHERE id=?")->execute([$next, (int)$game['id']]);
    }
    return live_game_fetch((int)$game['id']);
}
function live_game_grade_answer(array $question, string $answer): array {
    if ($question['type'] === 'essay') return [null, 0];
    $correct = normalize_answer($answer) === normalize_answer($question['answer_key']);
    return [$correct, $correct ? (int)$question['score'] : 0];
}
function live_game_powerup_uses(int $gameId, int $playerId): array {
    $stmt = db()->prepare('SELECT powerup_type, question_index, data_json FROM live_game_powerup_uses WHERE live_game_id=? AND player_id=?');
    $stmt->execute([$gameId, $playerId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['powerup_type']] = [
            'question_index'=>(int)$row['question_index'],
            'data'=>json_decode($row['data_json'] ?: '{}', true) ?: []
        ];
    }
    return $out;
}
function live_game_recap_rounds(int $gameId): array {
    $stmt = db()->prepare('SELECT a.question_index, a.answer, a.is_correct, a.score, a.response_time_ms, a.answered_at, p.display_name, p.avatar_key, q.prompt, q.answer_key FROM live_game_answers a JOIN live_game_players p ON p.id=a.player_id JOIN questions q ON q.id=a.question_id WHERE a.live_game_id=? ORDER BY a.question_index ASC, a.score DESC, a.response_time_ms ASC');
    $stmt->execute([$gameId]);
    $rounds = [];
    foreach ($stmt->fetchAll() as $r) {
        $idx = (int)$r['question_index'];
        if (!isset($rounds[$idx])) $rounds[$idx] = ['prompt'=>$r['prompt'], 'answer_key'=>$r['answer_key'], 'answers'=>[]];
        $avatar = pabetas_avatar($r['avatar_key'] ?? 'rocket');
        $r['avatar_emoji'] = $avatar['emoji'];
        $rounds[$idx]['answers'][] = $r;
    }
    return $rounds;
}
