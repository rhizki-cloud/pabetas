<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$data=json_decode(file_get_contents('php://input'), true) ?: [];
$order=$data['order'] ?? [];
$pdo=db(); $i=1;
foreach($order as $id){ $pdo->prepare('UPDATE learning_materials SET step_order=? WHERE id=?')->execute([$i++,(int)$id]); }
json_response(['ok'=>true,'message'=>'Urutan materi tersimpan.']);
