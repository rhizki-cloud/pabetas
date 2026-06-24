<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$pdo=db();
$sessionId=(int)($_POST['session_id'] ?? 0);
$answers=$_POST['answer'] ?? [];
$totalScore=0; $correct=0; $wrong=0;
foreach($answers as $qid=>$answer){
    $qid=(int)$qid; $answer=trim((string)$answer);
    $stmt=$pdo->prepare('SELECT * FROM questions WHERE id=?'); $stmt->execute([$qid]); $q=$stmt->fetch(); if(!$q) continue;
    $isCorrect=null; $score=0; $status='saved';
    if($q['type']==='essay') { $status='pending'; }
    else { $isCorrect = normalize_answer($answer) === normalize_answer($q['answer_key']); $score = $isCorrect ? (int)$q['score'] : 0; $correct += $isCorrect ? 1 : 0; $wrong += $isCorrect ? 0 : 1; }
    $totalScore += $score;
    $sql="INSERT INTO responses(session_id,user_id,question_id,answer,is_correct,score,status,autosaved_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE answer=VALUES(answer), is_correct=VALUES(is_correct), score=VALUES(score), status=VALUES(status), updated_at=NOW()";
    $pdo->prepare($sql)->execute([$sessionId,current_user()['id'],$qid,$answer,$isCorrect,$score,$status]);
}
$pdo->prepare("UPDATE game_sessions SET status='finished', finished_at=NOW(), score=?, total_correct=?, total_wrong=?, duration_seconds=TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE id=? AND user_id=?")->execute([$totalScore,$correct,$wrong,$sessionId,current_user()['id']]);
unset($_SESSION['active_session_id']);
redirect('student/result.php?session_id='.$sessionId);
