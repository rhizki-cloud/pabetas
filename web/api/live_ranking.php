<?php
require_once __DIR__ . '/../../app/init.php';
require_login();
$rows = db()->query("SELECT u.id AS user_id, u.name,
    COALESCE(MAX(gs.score),0) AS score,
    COALESCE(MAX(gs.total_correct),0) AS total_correct,
    CASE WHEN SUM(CASE WHEN gs.status='running' THEN 1 ELSE 0 END) > 0 THEN 'running' ELSE 'finished' END AS status,
    MAX(gs.finished_at) AS finished_at
    FROM users u
    LEFT JOIN game_sessions gs ON gs.user_id=u.id AND gs.status IN ('running','finished')
    WHERE u.role='murid' AND u.status=1
    GROUP BY u.id, u.name
    HAVING score > 0 OR total_correct > 0 OR status='running'
    ORDER BY score DESC, total_correct DESC, finished_at ASC
    LIMIT 20")->fetchAll();
json_response(['ok'=>true,'updated_at'=>date('H:i:s'),'rows'=>$rows]);
