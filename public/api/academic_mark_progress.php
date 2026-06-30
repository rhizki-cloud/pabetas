<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$raw = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$type = $raw['type'] ?? '';
$fieldMap = [
    'learn' => 'learn_completed_at',
];
if (!isset($fieldMap[$type])) json_response(['ok'=>false,'message'=>'Jenis progres tidak valid.'], 422);
academic_update_progress((int)current_user()['id'], $fieldMap[$type]);
json_response(['ok'=>true]);
