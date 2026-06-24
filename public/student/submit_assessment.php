<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$type = $_POST['type'] ?? 'pretest';
if (!in_array($type, ['pretest','posttest','remedial','quiz'], true)) redirect('student/academic.php');
$templateId = (int)($_POST['template_id'] ?? 0);
$answers = $_POST['answers'] ?? [];
$questions = academic_questions($type, ACADEMIC_TEST_LIMIT, $templateId);
$total = count($questions);
$correct = 0; $wrong = 0; $score = 0; $essayPending = 0;
$pdo = db();
$pdo->beginTransaction();
try {
    // Pastikan database lama menerima assessment_type quiz.
    try { $pdo->exec("ALTER TABLE academic_assessments MODIFY assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL"); } catch (Throwable $ignore) {}

    $pdo->prepare('INSERT INTO academic_assessments(user_id,assessment_type,total_questions,status,started_at,finished_at) VALUES(?,?,?,?,NOW(),NOW())')
        ->execute([(int)current_user()['id'], $type, $total, 'finished']);
    $assessmentId = (int)$pdo->lastInsertId();
    $essaySessionId = null;

    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $ans = trim((string)($answers[$qid] ?? ''));
        if ($q['type'] === 'essay') {
            $essayPending++;
            if ($essaySessionId === null) {
                $pdo->prepare("INSERT INTO game_sessions(user_id,mode,status,started_at,finished_at,score,total_correct,total_wrong,duration_seconds) VALUES(?,?,?,NOW(),NOW(),0,0,0,0)")
                    ->execute([(int)current_user()['id'], 'individu', 'finished']);
                $essaySessionId = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("INSERT INTO responses(session_id,user_id,question_id,answer,is_correct,score,status,autosaved_at,created_at,updated_at) VALUES(?,?,?,?,NULL,0,'pending',NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE answer=VALUES(answer), status='pending', updated_at=NOW()")
                ->execute([$essaySessionId, (int)current_user()['id'], $qid, $ans]);
            $pdo->prepare('INSERT INTO academic_assessment_answers(assessment_id,user_id,question_id,answer,is_correct,score,created_at) VALUES(?,?,?,?,?,?,NOW())')
                ->execute([$assessmentId, (int)current_user()['id'], $qid, $ans, 0, 0]);
            continue;
        }

        $isCorrect = normalize_answer($ans) === normalize_answer($q['answer_key']);
        $itemScore = $isCorrect ? (int)$q['score'] : 0;
        if ($isCorrect) $correct++; else $wrong++;
        $score += $itemScore;
        $pdo->prepare('INSERT INTO academic_assessment_answers(assessment_id,user_id,question_id,answer,is_correct,score,created_at) VALUES(?,?,?,?,?,?,NOW())')
            ->execute([$assessmentId, (int)current_user()['id'], $qid, $ans, $isCorrect ? 1 : 0, $itemScore]);
    }
    $maxScore = array_sum(array_map(fn($q)=>$q['type']==='essay' ? 0 : (int)$q['score'], $questions));
    $finalScore = $maxScore > 0 ? (int)round(($score / $maxScore) * 100) : 0;
    $pdo->prepare('UPDATE academic_assessments SET score=?, total_correct=?, total_wrong=? WHERE id=?')
        ->execute([$finalScore, $correct, $wrong, $assessmentId]);
    if ($type !== 'quiz') {
        $field = $type === 'pretest' ? 'pretest_completed_at' : ($type === 'posttest' ? 'posttest_completed_at' : 'remedial_completed_at');
        academic_update_progress((int)current_user()['id'], $field);
        if ($type === 'posttest') academic_create_remedial_if_needed((int)current_user()['id'], $finalScore);
        if ($type === 'remedial') academic_mark_remedial_done((int)current_user()['id']);
    }
    if ($essayPending > 0) flash('success', $essayPending . ' jawaban esai dikirim ke guru untuk dinilai.');
    $pdo->commit();
    redirect('student/assessment_result.php?id=' . $assessmentId);
} catch (Throwable $e) {
    $pdo->rollBack();
    exit('Gagal menyimpan nilai. ' . e($e->getMessage()));
}
