<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$data=json_decode(file_get_contents('php://input'), true) ?: [];
$sessionId=(int)($data['session_id'] ?? 0); $questionId=(int)($data['question_id'] ?? 0); $answer=trim((string)($data['answer'] ?? ''));
if(!$sessionId || !$questionId) json_response(['ok'=>false,'message'=>'Data tidak lengkap'],422);
$pdo=db();
$stmt=$pdo->prepare('SELECT * FROM questions WHERE id=?'); $stmt->execute([$questionId]); $q=$stmt->fetch(); if(!$q) json_response(['ok'=>false,'message'=>'Soal tidak ditemukan'],404);
$isCorrect=null; $score=0; $status='saved';
if($q['type']==='essay'){ $status='pending'; }
elseif($answer!==''){ $isCorrect=normalize_answer($answer)===normalize_answer($q['answer_key']); $score=$isCorrect?(int)$q['score']:0; }
$sql="INSERT INTO responses(session_id,user_id,question_id,answer,is_correct,score,status,autosaved_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE answer=VALUES(answer), is_correct=VALUES(is_correct), score=VALUES(score), status=VALUES(status), autosaved_at=NOW(), updated_at=NOW()";
$pdo->prepare($sql)->execute([$sessionId,current_user()['id'],$questionId,$answer,$isCorrect,$score,$status]);
json_response(['ok'=>true,'saved_at'=>date('H:i:s'),'status'=>$status]);
