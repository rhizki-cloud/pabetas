<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$rows=db()->query("SELECT u.name, MAX(gs.score) score FROM users u LEFT JOIN game_sessions gs ON gs.user_id=u.id AND gs.status='finished' WHERE u.role='murid' GROUP BY u.id,u.name ORDER BY score DESC LIMIT 10")->fetchAll();
json_response(['ok'=>true,'rows'=>$rows]);
