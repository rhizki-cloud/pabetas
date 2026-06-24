<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();

function ensure_template_tables_for_essay(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS assessment_templates (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(150) NOT NULL,
      assessment_type ENUM('pretest','posttest','remedial','quiz') NOT NULL DEFAULT 'quiz',
      description TEXT NULL,
      status TINYINT(1) NOT NULL DEFAULT 1,
      created_by INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      INDEX idx_template_type_status(assessment_type,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS assessment_template_questions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      template_id INT NOT NULL,
      question_id INT NOT NULL,
      sort_order INT NOT NULL DEFAULT 1,
      UNIQUE KEY uq_template_question(template_id, question_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensure_template_tables_for_essay($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_essay_set') {
        $title = trim($_POST['title'] ?? '') ?: 'Paket Soal Esai PABETAS';
        $description = trim($_POST['description'] ?? '');
        $selectMode = in_array($_POST['select_mode'] ?? 'manual', ['manual','auto'], true) ? $_POST['select_mode'] : 'manual';
        $mode = in_array($_POST['mode'] ?? 'individu', ['individu','tim'], true) ? $_POST['mode'] : 'individu';
        $ids = [];
        if ($selectMode === 'manual') {
            $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['question_ids'] ?? []))));
            if (!$ids) {
                flash('error', 'Pilih minimal 1 soal esai dari Bank Soal.');
                redirect('teacher/essay_sets.php');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE status=1 AND type='essay' AND mode=? AND id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
            $stmt->execute(array_merge([$mode], $ids, $ids));
            $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        } else {
            $count = max(1, min(30, (int)($_POST['auto_count'] ?? 5)));
            $params = [$mode];
            $where = "status=1 AND type='essay' AND mode=?";
            if (!empty($_POST['difficulty']) && in_array($_POST['difficulty'], ['mudah','sedang','sulit'], true)) {
                $where .= " AND difficulty=?"; $params[] = $_POST['difficulty'];
            }
            if (!empty($_POST['group_label'])) {
                $where .= " AND group_label=?"; $params[] = trim($_POST['group_label']);
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE $where");
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() < $count) {
                flash('error', 'Jumlah soal esai sesuai filter belum cukup. Tambahkan soal esai atau turunkan jumlah.');
                redirect('teacher/essay_sets.php');
            }
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE $where ORDER BY RAND() LIMIT $count");
            $stmt->execute($params);
            $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        }
        if (!$ids) {
            flash('error', 'Tidak ada soal esai yang valid untuk paket ini.');
            redirect('teacher/essay_sets.php');
        }
        $fullDesc = "[ESAI] " . $description;
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO assessment_templates(title,assessment_type,description,status,created_by,created_at) VALUES(?,?,?,?,?,NOW())')
                ->execute([$title, 'quiz', $fullDesc, 1, current_user()['id']]);
            $templateId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO assessment_template_questions(template_id,question_id,sort_order) VALUES(?,?,?)');
            $order = 1;
            foreach ($ids as $qid) $stmt->execute([$templateId, $qid, $order++]);
            $pdo->commit();
            flash('success', 'Paket soal esai berhasil dibuat dan muncul di menu Soal Esai siswa.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Gagal membuat paket esai: ' . $e->getMessage());
        }
        redirect('teacher/essay_sets.php');
    }
    if ($action === 'delete_set') {
        $pdo->prepare('UPDATE assessment_templates SET status=0, updated_at=NOW() WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success', 'Paket esai dinonaktifkan.');
        redirect('teacher/essay_sets.php');
    }
}

$essayQuestions = $pdo->query("SELECT * FROM questions WHERE status=1 AND type='essay' ORDER BY mode ASC, group_label ASC, difficulty ASC, id ASC")->fetchAll();
$groups = [];
foreach ($essayQuestions as $q) {
    $key = ucfirst($q['mode']) . ' - ' . ($q['group_label'] ?: 'Esai');
    $groups[$key][] = $q;
}
$groupLabels = $pdo->query("SELECT DISTINCT group_label FROM questions WHERE status=1 AND type='essay' AND group_label IS NOT NULL AND group_label<>'' ORDER BY group_label ASC")->fetchAll(PDO::FETCH_COLUMN);
$sets = $pdo->query("SELECT t.*, COUNT(tq.id) AS total_questions
    FROM assessment_templates t
    JOIN assessment_template_questions tq ON tq.template_id=t.id
    JOIN questions q ON q.id=tq.question_id
    WHERE t.assessment_type='quiz'
    GROUP BY t.id
    HAVING SUM(CASE WHEN q.type='essay' THEN 1 ELSE 0 END)=COUNT(q.id)
    ORDER BY t.id DESC")->fetchAll();
render_header('Paket Soal Esai', 'essay_sets');
?>
<div class="page-title"><h1>✍️ Paket Soal Esai</h1><p>Guru membuat paket esai dari Bank Soal. Siswa mengerjakan di menu <b>Soal Esai</b>, lalu guru menilai di menu <b>Nilai Esai</b>.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<?php if($msg=flash('error')): ?><div class="alert alert-danger fw-bold"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-5"><div class="panel-card"><h3>Buat Paket Esai</h3>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_essay_set">
      <label class="form-label">Nama Paket</label><input class="form-control mb-3" name="title" required placeholder="Contoh: Esai Pemahaman Tangga Satuan">
      <label class="form-label">Deskripsi</label><textarea class="form-control mb-3" name="description" rows="3" placeholder="Instruksi singkat untuk siswa"></textarea>
      <label class="form-label">Mode Soal</label><select class="form-select mb-3" name="mode" id="essayMode"><option value="individu">Individu</option><option value="tim">Tim</option></select>
      <label class="form-label">Cara Ambil Soal</label><select class="form-select mb-3" name="select_mode" id="essaySelectMode"><option value="manual">Pilih manual dari Bank Soal</option><option value="auto">Ambil otomatis</option></select>
      <div id="essayAutoBox" class="p-3 rounded-4 bg-light mb-3 d-none">
        <b>Filter otomatis</b>
        <div class="row g-2 mt-1">
          <div class="col-md-4"><input type="number" class="form-control" name="auto_count" value="5" min="1" max="30"></div>
          <div class="col-md-4"><select class="form-select" name="difficulty"><option value="">Semua level</option><option value="mudah">Mudah</option><option value="sedang">Sedang</option><option value="sulit">Sulit</option></select></div>
          <div class="col-md-4"><select class="form-select" name="group_label"><option value="">Semua kelompok</option><?php foreach($groupLabels as $gl): ?><option value="<?= e($gl) ?>"><?= e($gl) ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div id="essayManualBox" class="template-question-picker">
        <h4>Pilih Soal Esai</h4>
        <?php foreach($groups as $group=>$items): ?>
          <div class="question-group-box">
            <div class="question-group-title"><?= e($group) ?> <span><?= count($items) ?> soal</span></div>
            <?php foreach($items as $q): ?>
              <label class="question-check" data-mode="<?= e($q['mode']) ?>"><input type="checkbox" name="question_ids[]" value="<?= (int)$q['id'] ?>"> <b>#<?= (int)$q['id'] ?></b> <?= e(mb_strimwidth($q['prompt'],0,72,'...')) ?> <small><?= e($q['difficulty'] ?? 'mudah') ?></small></label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <?php if(!$groups): ?><div class="alert alert-warning">Belum ada soal esai aktif. Tambahkan dahulu di menu Bank Soal.</div><?php endif; ?>
      </div>
      <button class="btn btn-primary rounded-pill mt-3">Simpan Paket Esai</button>
    </form>
  </div></div>
  <div class="col-lg-7"><div class="panel-card"><h3>Daftar Paket Esai</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama</th><th>Soal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($sets as $s): ?><tr><td><b><?= e($s['title']) ?></b><br><small><?= e(str_replace('[ESAI]','',$s['description'] ?? '')) ?></small></td><td><?= (int)$s['total_questions'] ?></td><td><?= (int)$s['status']===1?'Aktif':'Nonaktif' ?></td><td><form method="post" onsubmit="return confirm('Nonaktifkan paket esai ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_set"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-sm btn-outline-danger">Nonaktifkan</button></form></td></tr><?php endforeach; ?>
    <?php if(!$sets): ?><tr><td colspan="4">Belum ada paket esai.</td></tr><?php endif; ?>
  </tbody></table></div></div></div>
</div>
<script>
const essayMode = document.getElementById('essayMode');
const essaySelectMode = document.getElementById('essaySelectMode');
const essayAutoBox = document.getElementById('essayAutoBox');
const essayManualBox = document.getElementById('essayManualBox');
function syncEssayBoxes(){
  const auto = essaySelectMode.value === 'auto';
  essayAutoBox.classList.toggle('d-none', !auto);
  essayManualBox.classList.toggle('d-none', auto);
}
function syncEssayMode(){
  const mode = essayMode.value;
  document.querySelectorAll('#essayManualBox .question-check').forEach(el => el.style.display = el.dataset.mode === mode ? '' : 'none');
}
essaySelectMode.addEventListener('change', syncEssayBoxes);
essayMode.addEventListener('change', syncEssayMode);
syncEssayBoxes(); syncEssayMode();
</script>
<?php render_footer(); ?>
