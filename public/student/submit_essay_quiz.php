<?php
require_once __DIR__ . '/../../app/init.php';
require_role('murid');
verify_csrf();
$pdo = db();
$templateId = (int)($_POST['template_id'] ?? 0);
$answers = $_POST['answers'] ?? [];
if ($templateId <= 0) redirect('student/essay_quiz.php');

$stmt = $pdo->prepare("SELECT q.* FROM assessment_template_questions tq JOIN questions q ON q.id=tq.question_id WHERE tq.template_id=? AND q.status=1 AND q.type='essay' ORDER BY tq.sort_order ASC, q.id ASC");
$stmt->execute([$templateId]);
$questions = $stmt->fetchAll();
if (!$questions) redirect('student/essay_quiz.php');

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO game_sessions(user_id,mode,status,started_at,finished_at,score,total_correct,total_wrong,duration_seconds) VALUES(?,?,?,NOW(),NOW(),0,0,0,0)")
        ->execute([current_user()['id'], 'individu', 'finished']);
    $sessionId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO responses(session_id,user_id,question_id,answer,is_correct,score,status,autosaved_at,created_at,updated_at) VALUES(?,?,?,?,NULL,0,'pending',NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE answer=VALUES(answer), status='pending', updated_at=NOW()");
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $answer = trim((string)($answers[$qid] ?? ''));
        if ($answer === '') continue;
        $stmt->execute([$sessionId, current_user()['id'], $qid, $answer]);
    }
    $pdo->commit();
    flash('success', 'Jawaban esai berhasil dikirim. Tunggu penilaian guru di menu Esai Saya.');
    redirect('student/essay_feedback.php');
} catch (Throwable $e) {
    $pdo->rollBack();
    exit('Gagal menyimpan jawaban esai. ' . e($e->getMessage()));
}
