<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo = db();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS reflection_prompts (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, question_text TEXT NOT NULL, status TINYINT(1) NOT NULL DEFAULT 1, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS learning_reflections (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, prompt_id INT NULL, mood VARCHAR(40) NOT NULL, difficulty_level ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'sedang', obstacle_text TEXT NULL, teacher_note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo->prepare('INSERT INTO learning_reflections(user_id,prompt_id,mood,difficulty_level,obstacle_text,created_at) VALUES(?,?,?,?,?,NOW())')->execute([current_user()['id'], (int)($_POST['prompt_id'] ?? 0) ?: null, $_POST['mood'] ?? '😊', $_POST['difficulty_level'] ?? 'sedang', trim($_POST['obstacle_text'] ?? '')]);
    flash('success','Refleksimu sudah terkirim ke guru. Terima kasih!');
    redirect('student/reflection.php');
}
$prompt = $pdo->query('SELECT * FROM reflection_prompts WHERE status=1 ORDER BY id DESC LIMIT 1')->fetch();
$stmt = $pdo->prepare('SELECT * FROM learning_reflections WHERE user_id=? ORDER BY id DESC LIMIT 10');
$stmt->execute([current_user()['id']]);
$history = $stmt->fetchAll();
render_header('Refleksi Belajar','reflection');
?>
<div class="page-title compact-title"><h1>Refleksi Belajar 🌟</h1><p>Ceritakan perasaan dan kendalamu agar guru bisa membantu dengan tepat.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-7"><div class="panel-card kid-reflection-card"><h3><?= e($prompt['title'] ?? 'Refleksi Belajar Hari Ini') ?></h3><p class="fs-5"><?= e($prompt['question_text'] ?? 'Bagian mana yang masih membuatmu bingung?') ?></p>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="prompt_id" value="<?= (int)($prompt['id'] ?? 0) ?>">
      <label class="form-label fw-bold">Perasaan hari ini</label><div class="mood-grid mood-grid-animated mb-3">
        <?php foreach(['😄 Sangat senang','😊 Senang','😐 Biasa saja','😕 Bingung','😴 Lelah'] as $m): ?><label><input type="radio" name="mood" value="<?= e($m) ?>" required><span><em><?= e(mb_substr($m,0,2)) ?></em><b><?= e(trim(mb_substr($m,2))) ?></b></span></label><?php endforeach; ?>
      </div>
      <label class="form-label fw-bold">Menurutmu materi hari ini</label><select class="form-select mb-3" name="difficulty_level"><option value="mudah">Mudah</option><option value="sedang" selected>Lumayan</option><option value="sulit">Sulit</option></select>
      <label class="form-label fw-bold">Kendala atau pertanyaanmu</label><textarea class="form-control mb-3" name="obstacle_text" rows="4" placeholder="Contoh: saya masih bingung kalau cm diubah ke m"></textarea>
      <button class="btn btn-primary rounded-pill btn-lg">Kirim Refleksi</button>
    </form>
  </div></div>
  <div class="col-lg-5"><div class="panel-card"><h3>Riwayat Refleksiku</h3><?php if(!$history): ?><div class="alert alert-info">Belum ada refleksi.</div><?php endif; ?><?php foreach($history as $h): ?><div class="mini-history"><b><?= e($h['mood']) ?></b><p><?= e($h['obstacle_text'] ?: '-') ?></p><?php if($h['teacher_note']): ?><small>Catatan guru: <?= e($h['teacher_note']) ?></small><?php endif; ?></div><?php endforeach; ?></div></div>
</div>
<?php render_footer(); ?>
