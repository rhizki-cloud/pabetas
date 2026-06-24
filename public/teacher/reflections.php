<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS reflection_prompts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, question_text TEXT NOT NULL, status TINYINT(1) NOT NULL DEFAULT 1, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS learning_reflections (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, prompt_id INT NULL, mood VARCHAR(40) NOT NULL, difficulty_level ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang', obstacle_text TEXT NULL, teacher_note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_prompt') {
        $pdo->prepare('UPDATE reflection_prompts SET status=0 WHERE status=1')->execute();
        $pdo->prepare('INSERT INTO reflection_prompts(title,question_text,status,created_by) VALUES(?,?,1,?)')->execute([trim($_POST['title'] ?? 'Refleksi Belajar'), trim($_POST['question_text'] ?? 'Apa kendalamu hari ini?'), current_user()['id']]);
        flash('success','Pertanyaan refleksi aktif diperbarui.');
        redirect('teacher/reflections.php');
    }
    if ($action === 'teacher_note') {
        $id = (int)($_POST['reflection_id'] ?? 0);
        $note = trim($_POST['teacher_note'] ?? '');
        $pdo->prepare('UPDATE learning_reflections SET teacher_note=?, updated_at=NOW() WHERE id=?')->execute([$note, $id]);
        flash('success','Catatan guru disimpan.');
        redirect('teacher/reflections.php');
    }
}
$prompt = $pdo->query('SELECT * FROM reflection_prompts WHERE status=1 ORDER BY id DESC LIMIT 1')->fetch();
$rows = $pdo->query('SELECT lr.*, u.name, u.username FROM learning_reflections lr JOIN users u ON u.id=lr.user_id ORDER BY lr.id DESC LIMIT 100')->fetchAll();
render_header('Refleksi Murid','reflections');
?>
<div class="page-title compact-title"><h1>Refleksi Murid 💬</h1><p>Guru memberi pertanyaan evaluasi, murid memilih perasaan hari ini, lalu menulis kendala belajar.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-4"><div class="panel-card"><h3>Pertanyaan Aktif</h3>
    <?php if($prompt): ?><div class="teacher-prompt-preview"><b><?= e($prompt['title']) ?></b><p><?= e($prompt['question_text']) ?></p></div><?php endif; ?>
    <form method="post" class="mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="save_prompt"><label class="form-label">Judul</label><input class="form-control mb-2" name="title" value="<?= e($prompt['title'] ?? 'Refleksi Belajar Hari Ini') ?>"><label class="form-label">Pertanyaan untuk murid</label><textarea class="form-control mb-3" name="question_text" rows="4"><?= e($prompt['question_text'] ?? 'Bagian mana yang masih membuatmu bingung setelah belajar satuan panjang?') ?></textarea><button class="btn btn-primary rounded-pill w-100">Aktifkan Pertanyaan</button></form>
  </div></div>
  <div class="col-lg-8"><div class="panel-card"><h3>Jawaban Refleksi Terbaru</h3>
    <?php if(!$rows): ?><div class="alert alert-info">Belum ada refleksi murid.</div><?php endif; ?>
    <div class="reflection-list">
    <?php foreach($rows as $r): ?>
      <div class="reflection-row"><div class="reflection-mood"><?= e($r['mood']) ?></div><div class="flex-grow-1"><b><?= e($r['name']) ?></b><small class="text-muted ms-2"><?= e($r['created_at']) ?></small><p class="mb-1">Kesulitan: <b><?= e($r['difficulty_level']) ?></b></p><p><?= nl2br(e($r['obstacle_text'] ?: '-')) ?></p><form method="post" class="reflection-note-form"><?= csrf_field() ?><input type="hidden" name="action" value="teacher_note"><input type="hidden" name="reflection_id" value="<?= (int)$r['id'] ?>"><input class="form-control" name="teacher_note" value="<?= e($r['teacher_note'] ?? '') ?>" placeholder="Catatan atau tindak lanjut guru"><button class="btn btn-sm btn-success rounded-pill mt-2">Simpan Catatan</button></form></div></div>
    <?php endforeach; ?>
    </div>
  </div></div>
</div>
<?php render_footer(); ?>
