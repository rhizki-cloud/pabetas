<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$pdo = db();
try { $pdo->exec("ALTER TABLE game_sessions ADD COLUMN IF NOT EXISTS team_id INT NULL AFTER room_id"); } catch (Throwable $e) {}
$roomId = (int)($_POST['room_id'] ?? 0);
$teamId = (int)($_POST['team_id'] ?? 0);
$sessionId = (int)($_POST['session_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE id=? AND user_id=? AND room_id=? AND mode='tim' LIMIT 1");
$stmt->execute([$sessionId, current_user()['id'], $roomId]);
$session = $stmt->fetch();
if (!$session) redirect('student/team_join.php');
$answers = $_POST['answer'] ?? [];
$qids = $_SESSION['team_questions_'.$roomId.'_'.$teamId] ?? [];
if (!$qids) $qids = array_map('intval', array_keys($answers));
$totalScore=0; $correct=0; $wrong=0;
foreach($qids as $qid){
    $qid=(int)$qid; $answer=trim((string)($answers[$qid] ?? ''));
    $stmt=$pdo->prepare('SELECT * FROM questions WHERE id=? AND mode="tim" LIMIT 1');
    $stmt->execute([$qid]);
    $q=$stmt->fetch();
    if(!$q) continue;
    $isCorrect=null; $score=0; $status='saved';
    if($q['type']==='essay') { $status='pending'; }
    else {
        $isCorrect = ($answer !== '') && normalize_answer($answer) === normalize_answer($q['answer_key']);
        $score = $isCorrect ? (int)$q['score'] : 0;
        if ($isCorrect) $correct++; else $wrong++;
    }
    $totalScore += $score;
    $sql="INSERT INTO responses(session_id,user_id,question_id,answer,is_correct,score,status,autosaved_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE answer=VALUES(answer), is_correct=VALUES(is_correct), score=VALUES(score), status=VALUES(status), updated_at=NOW()";
    $pdo->prepare($sql)->execute([$sessionId,current_user()['id'],$qid,$answer,$isCorrect,$score,$status]);
}
$pdo->prepare("UPDATE game_sessions SET status='finished', finished_at=NOW(), score=?, total_correct=?, total_wrong=?, duration_seconds=TIMESTAMPDIFF(SECOND, started_at, NOW()), team_id=? WHERE id=? AND user_id=?")->execute([$totalScore,$correct,$wrong,$teamId,$sessionId,current_user()['id']]);
unset($_SESSION['team_session_'.$roomId.'_'.$teamId], $_SESSION['team_questions_'.$roomId.'_'.$teamId]);
redirect('student/team_result.php?session_id='.$sessionId);
