<?php
function academic_latest_attempt(int $userId, string $type): ?array {
    $stmt = db()->prepare('SELECT * FROM academic_assessments WHERE user_id=? AND assessment_type=? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId, $type]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function academic_attempts_by_user(int $userId): array {
    $stmt = db()->prepare('SELECT * FROM academic_assessments WHERE user_id=? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function academic_stage_status(int $userId): array {
    $pre = academic_latest_attempt($userId, 'pretest');
    $post = academic_latest_attempt($userId, 'posttest');
    $remedial = academic_latest_attempt($userId, 'remedial');
    $stmt = db()->prepare('SELECT * FROM student_learning_progress WHERE user_id=? LIMIT 1');
    $stmt->execute([$userId]);
    $progress = $stmt->fetch() ?: [];
    $needsRemedial = false;
    if ($post && (int)$post['score'] < REMEDIAL_MIN_SCORE) $needsRemedial = true;
    return [
        'pretest' => $pre,
        'posttest' => $post,
        'remedial' => $remedial,
        'progress' => $progress,
        'needs_remedial' => $needsRemedial,
        'improvement' => ($pre && $post) ? ((int)$post['score'] - (int)$pre['score']) : null,
    ];
}

function academic_flow_steps(): array {
    $defaultSteps = [
        ['step_order'=>1,'title'=>'Pretest','description'=>'Tes awal untuk mengetahui pemahaman sebelum belajar.','action_type'=>'pretest','action_payload'=>'','is_required'=>1],
        ['step_order'=>2,'title'=>'Pembelajaran Papan Belajar','description'=>'Baca materi satuan panjang dan contoh bertahap.','action_type'=>'material','action_payload'=>'','is_required'=>1],
        ['step_order'=>3,'title'=>'Live Game','description'=>'Masuk game dengan kode dari guru.','action_type'=>'live_game','action_payload'=>'','is_required'=>1],
        ['step_order'=>4,'title'=>'Posttest','description'=>'Tes akhir untuk melihat peningkatan hasil belajar.','action_type'=>'posttest','action_payload'=>'','is_required'=>1],
        ['step_order'=>5,'title'=>'Remedial Otomatis','description'=>'Latihan penguatan jika belum tuntas.','action_type'=>'remedial','action_payload'=>'','is_required'=>0],
        ['step_order'=>6,'title'=>'Refleksi Belajar','description'=>'Ceritakan perasaan dan kendala setelah belajar.','action_type'=>'reflection','action_payload'=>'','is_required'=>0],
    ];
    try {
        $rows = db()->query("SELECT * FROM learning_flow_steps WHERE status=1 ORDER BY step_order ASC, id ASC")->fetchAll();
        // Jika guru baru mengisi satu langkah saja, sistem tetap menampilkan alur lengkap agar siswa tidak bingung.
        if (count($rows) >= 3) return $rows;
    } catch (Throwable $e) {}
    return $defaultSteps;
}

function academic_action_url(string $actionType, string $payload = ''): string {
    $payload = trim($payload);
    return match ($actionType) {
        'pretest' => url('student/assessment.php?type=pretest'),
        'posttest' => url('student/assessment.php?type=posttest'),
        'remedial' => url('student/remedial.php'),
        'material' => url('student/learn.php'),
        'live_game' => url('student/live_join.php'),
        'quiz' => $payload !== '' ? url('student/assessment.php?type=quiz&template='.(int)$payload) : url('student/quiz.php'),
        'reflection' => url('student/reflection.php'),
        default => $payload !== '' ? $payload : url('student/academic.php'),
    };
}

function academic_action_button(string $actionType): string {
    return match ($actionType) {
        'pretest' => 'Kerjakan Pretest',
        'posttest' => 'Kerjakan Posttest',
        'remedial' => 'Cek Remedial',
        'material' => 'Buka Materi',
        'live_game' => 'Masuk Live Game',
        'quiz' => 'Kerjakan Kuis',
        'reflection' => 'Isi Refleksi',
        default => 'Buka',
    };
}

function academic_template(int $templateId): ?array {
    try {
        $stmt = db()->prepare('SELECT * FROM assessment_templates WHERE id=? AND status=1 LIMIT 1');
        $stmt->execute([$templateId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function academic_default_template_for_type(string $type): ?array {
    try {
        $stmt = db()->prepare('SELECT * FROM assessment_templates WHERE assessment_type=? AND status=1 ORDER BY id DESC LIMIT 1');
        $stmt->execute([$type]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function academic_questions(string $type, int $limit = 5, int $templateId = 0): array {
    $pdo = db();
    try {
        $template = $templateId > 0 ? academic_template($templateId) : academic_default_template_for_type($type);
        if ($template) {
            $stmt = $pdo->prepare("SELECT q.* FROM assessment_template_questions tq JOIN questions q ON q.id=tq.question_id WHERE tq.template_id=? AND q.status=1 ORDER BY tq.sort_order ASC, q.id ASC LIMIT ?");
            $stmt->bindValue(1, (int)$template['id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if ($rows) return $rows;
        }
    } catch (Throwable $e) {}
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE status=1 AND mode='individu' AND type IN ('multiple','short','essay') ORDER BY id ASC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function academic_update_progress(int $userId, string $field): void {
    $allowed = ['learn_completed_at','pretest_completed_at','posttest_completed_at','remedial_completed_at'];
    if (!in_array($field, $allowed, true)) return;
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM student_learning_progress WHERE user_id=? LIMIT 1');
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE student_learning_progress SET {$field}=NOW(), updated_at=NOW() WHERE user_id=?")->execute([$userId]);
    } else {
        $pdo->prepare("INSERT INTO student_learning_progress(user_id, {$field}, updated_at) VALUES(?, NOW(), NOW())")->execute([$userId]);
    }
}

function academic_create_remedial_if_needed(int $userId, int $posttestScore): void {
    if ($posttestScore >= REMEDIAL_MIN_SCORE) return;
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id FROM remedial_assignments WHERE user_id=? AND status='open' LIMIT 1");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) return;
    $reason = 'Nilai posttest masih di bawah ' . REMEDIAL_MIN_SCORE . '. Siswa perlu latihan penguatan.';
    $material = 'Ulangi materi naik tangga berarti dibagi 10 dan turun tangga berarti dikali 10. Fokus pada konversi dari cm/mm ke m.';
    $pdo->prepare('INSERT INTO remedial_assignments(user_id, reason, recommended_material, status, created_at) VALUES(?,?,?,?,NOW())')
        ->execute([$userId, $reason, $material, 'open']);
}

function academic_mark_remedial_done(int $userId): void {
    db()->prepare("UPDATE remedial_assignments SET status='done', completed_at=NOW() WHERE user_id=? AND status='open'")->execute([$userId]);
}

function academic_class_report(): array {
    $pdo = db();
    $sql = "SELECT u.id, u.name, u.username,
        MAX(CASE WHEN a.assessment_type='pretest' THEN a.score END) AS pretest,
        MAX(CASE WHEN a.assessment_type='posttest' THEN a.score END) AS posttest,
        MAX(CASE WHEN a.assessment_type='remedial' THEN a.score END) AS remedial,
        MAX(CASE WHEN rp.status='open' THEN 1 ELSE 0 END) AS remedial_open
        FROM users u
        LEFT JOIN academic_assessments a ON a.user_id=u.id
        LEFT JOIN remedial_assignments rp ON rp.user_id=u.id
        WHERE u.role='murid' AND u.status=1
        GROUP BY u.id, u.name, u.username
        ORDER BY u.name ASC";
    return $pdo->query($sql)->fetchAll();
}

function academic_summary(array $rows): array {
    $pre = []; $post = []; $improvements = []; $complete = 0; $remedial = 0;
    foreach ($rows as $r) {
        if ($r['pretest'] !== null) $pre[] = (int)$r['pretest'];
        if ($r['posttest'] !== null) $post[] = (int)$r['posttest'];
        if ($r['pretest'] !== null && $r['posttest'] !== null) {
            $complete++;
            $improvements[] = (int)$r['posttest'] - (int)$r['pretest'];
        }
        if ((int)($r['remedial_open'] ?? 0) === 1) $remedial++;
    }
    $avg = fn($arr) => count($arr) ? round(array_sum($arr)/count($arr), 1) : 0;
    return [
        'avg_pretest' => $avg($pre),
        'avg_posttest' => $avg($post),
        'avg_improvement' => $avg($improvements),
        'complete_students' => $complete,
        'remedial_open' => $remedial,
        'total_students' => count($rows),
        'mastery_percent' => count($post) ? round((count(array_filter($post, fn($v)=>$v>=REMEDIAL_MIN_SCORE))/count($post))*100, 1) : 0,
    ];
}

function academic_question_difficulty(): array {
    $stmt = db()->query("SELECT q.prompt, COUNT(a.id) AS total_answer, SUM(CASE WHEN a.is_correct=0 THEN 1 ELSE 0 END) AS wrong_count,
        ROUND((SUM(CASE WHEN a.is_correct=0 THEN 1 ELSE 0 END) / GREATEST(COUNT(a.id),1)) * 100, 1) AS wrong_percent
        FROM academic_assessment_answers a
        JOIN questions q ON q.id=a.question_id
        GROUP BY q.id, q.prompt
        HAVING total_answer > 0
        ORDER BY wrong_percent DESC, total_answer DESC
        LIMIT 10");
    return $stmt->fetchAll();
}
