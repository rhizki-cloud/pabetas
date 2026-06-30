<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();

function ensure_question_v3_columns(PDO $pdo): void {
    $hasGroup = $pdo->query("SHOW COLUMNS FROM questions LIKE 'group_label'")->fetch();
    if (!$hasGroup) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN group_label VARCHAR(80) NOT NULL DEFAULT 'Umum' AFTER type");
    }
    $hasDifficulty = $pdo->query("SHOW COLUMNS FROM questions LIKE 'difficulty'")->fetch();
    if (!$hasDifficulty) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN difficulty ENUM('mudah','sedang','sulit') NOT NULL DEFAULT 'mudah' AFTER group_label");
    }
    $pdo->exec("UPDATE questions SET group_label = CASE
        WHEN mode='individu' AND type='essay' THEN 'Individu Esai'
        WHEN mode='individu' AND type='multiple' THEN 'Individu Pilihan Ganda'
        WHEN mode='individu' AND type='short' THEN 'Individu Jawaban Singkat'
        WHEN mode='tim' AND type='essay' THEN 'Tim Esai'
        WHEN mode='tim' AND type='multiple' THEN 'Tim Pilihan Ganda'
        WHEN mode='tim' AND type='short' THEN 'Tim Jawaban Singkat'
        ELSE 'Umum'
    END WHERE group_label='Umum' OR group_label='' OR group_label IS NULL");
}
ensure_question_v3_columns($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save_template') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['assessment_type'] ?? 'quiz';
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        $questionIds = array_values(array_filter(array_map('intval', $_POST['question_ids'] ?? [])));
        if ($id > 0) {
            $pdo->prepare('UPDATE assessment_templates SET title=?, assessment_type=?, description=?, status=?, updated_at=NOW() WHERE id=?')
                ->execute([$title, $type, $description, $status, $id]);
            $templateId = $id;
            $pdo->prepare('DELETE FROM assessment_template_questions WHERE template_id=?')->execute([$templateId]);
        } else {
            $pdo->prepare('INSERT INTO assessment_templates(title,assessment_type,description,status,created_by,created_at) VALUES(?,?,?,?,?,NOW())')
                ->execute([$title, $type, $description, $status, current_user()['id']]);
            $templateId = (int)$pdo->lastInsertId();
        }
        $order = 1;
        $stmt = $pdo->prepare('INSERT INTO assessment_template_questions(template_id,question_id,sort_order) VALUES(?,?,?)');
        foreach ($questionIds as $qid) $stmt->execute([$templateId, $qid, $order++]);
        flash('success', 'Template kuis/tes berhasil disimpan.');
        redirect('teacher/quizzes.php');
    }
    if ($action === 'delete_template') {
        $pdo->prepare('UPDATE assessment_templates SET status=0, updated_at=NOW() WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success', 'Template dinonaktifkan.');
        redirect('teacher/quizzes.php');
    }
}

$edit = null; $editIds = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM assessment_templates WHERE id=? LIMIT 1');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
    if ($edit) {
        $stmt = $pdo->prepare('SELECT question_id FROM assessment_template_questions WHERE template_id=? ORDER BY sort_order ASC');
        $stmt->execute([(int)$edit['id']]);
        $editIds = array_map('intval', array_column($stmt->fetchAll(), 'question_id'));
    }
}

$questions = $pdo->query("SELECT * FROM questions WHERE status=1 ORDER BY mode ASC, type ASC, group_label ASC, id ASC")->fetchAll();
$groups = [];
foreach ($questions as $q) {
    $key = ucfirst($q['mode']).' - '.($q['group_label'] ?: $q['type']);
    $groups[$key][] = $q;
}
$templates = $pdo->query('SELECT t.*, COUNT(tq.id) AS total_questions FROM assessment_templates t LEFT JOIN assessment_template_questions tq ON tq.template_id=t.id GROUP BY t.id ORDER BY t.assessment_type ASC, t.id DESC')->fetchAll();
render_header('Kuis dan Tes', 'quizzes');
?>
<div class="page-title"><h1>Kelola Kuis, Pretest, Posttest, dan Remedial</h1><p>Guru bisa memilih soal dari Bank Soal untuk dijadikan template tes atau kuis khusus.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-12"><div class="panel-card quiz-editor-clean"><h3><?= $edit ? 'Edit Template' : 'Tambah Template' ?></h3>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_template"><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
      <label class="form-label">Nama Template</label><input class="form-control mb-3" name="title" required value="<?= e($edit['title'] ?? '') ?>" placeholder="Contoh: Pretest Bab Satuan Panjang">
      <label class="form-label">Jenis</label><select class="form-select mb-3" name="assessment_type">
        <?php foreach(['pretest'=>'Pretest','posttest'=>'Posttest','remedial'=>'Remedial','quiz'=>'Kuis Biasa'] as $k=>$v): ?><option value="<?= $k ?>" <?= (($edit['assessment_type'] ?? '')===$k)?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
      </select>
      <label class="form-label">Deskripsi</label><textarea class="form-control mb-3" name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea>
      <div class="quiz-auto-picker p-3 rounded-4 bg-light mb-3">
        <h4 class="mb-2">Ambil Soal Otomatis</h4>
        <div class="row g-2 align-items-end">
          <div class="col-lg-3 col-md-6"><select class="form-select" id="autoPickMode"><option value="">Semua mode</option><option value="individu">Individu</option><option value="tim">Tim</option></select></div>
          <div class="col-lg-3 col-md-6"><select class="form-select" id="autoPickType"><option value="">Semua jenis</option><option value="multiple">Pilgan</option><option value="short">Jawaban singkat</option><option value="essay">Esai</option></select></div>
          <div class="col-lg-3 col-md-6"><select class="form-select" id="autoPickDifficulty"><option value="">Semua level</option><option value="mudah">Mudah</option><option value="sedang">Sedang</option><option value="sulit">Sulit</option></select></div>
          <div class="col-lg-3 col-md-6"><input type="number" class="form-control" id="autoPickCount" value="10" min="1" max="50" placeholder="Jumlah"></div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2">
          <button type="button" class="btn btn-sm btn-primary rounded-pill" id="btnAutoPickQuestions">Pilih Otomatis</button>
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnClearPickQuestions">Kosongkan Pilihan</button>
        </div>
        <small class="text-muted d-block mt-2">Fitur ini memilih checkbox dari Bank Soal di bawah. Guru tetap bisa menambah atau mengurangi manual.</small>
      </div>
      <div class="manual-picker-card">
        <div class="manual-picker-head">
          <div>
            <h4 class="mb-1">Pilih Soal Manual</h4>
            <small>Centang soal dari Bank Soal. Kelompok soal dibuat ringkas agar tidak terlalu panjang.</small>
          </div>
          <span class="selected-pill"><b id="selectedQuestionCount">0</b> dipilih</span>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-md-8"><input class="form-control form-control-lg" id="questionSearch" type="search" placeholder="Cari teks soal, nomor, jenis, atau level..."></div>
          <div class="col-md-4 d-grid"><button type="button" class="btn btn-outline-primary rounded-pill" data-bs-toggle="collapse" data-bs-target="#manualQuestionCollapse">Tampilkan / Sembunyikan Bank Soal</button></div>
        </div>
        <div id="manualQuestionCollapse" class="collapse <?= $edit ? 'show' : '' ?>">
          <div class="template-question-picker accordion" id="questionPickerAccordion">
            <?php $gIndex = 0; foreach($groups as $group=>$items): $collapseId = 'group'.(++$gIndex); ?>
              <div class="accordion-item question-accordion-item question-group-box">
                <h2 class="accordion-header">
                  <button class="accordion-button <?= $gIndex === 1 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= e($collapseId) ?>">
                    <span class="question-group-title w-100"><span><?= e($group) ?></span><span><?= count($items) ?> soal</span></span>
                  </button>
                </h2>
                <div id="<?= e($collapseId) ?>" class="accordion-collapse collapse <?= $gIndex === 1 ? 'show' : '' ?>" data-bs-parent="#questionPickerAccordion">
                  <div class="accordion-body p-2">
                    <div class="question-check-grid">
                    <?php foreach($items as $q): ?>
                      <label class="question-check compact" data-mode="<?= e($q['mode']) ?>" data-type="<?= e($q['type']) ?>" data-difficulty="<?= e($q['difficulty'] ?? 'mudah') ?>" data-prompt="<?= e(strtolower('#'.$q['id'].' '.$q['prompt'].' '.$q['mode'].' '.$q['type'].' '.($q['difficulty'] ?? 'mudah').' '.($q['group_label'] ?? ''))) ?>">
                        <input type="checkbox" name="question_ids[]" value="<?= (int)$q['id'] ?>" <?= in_array((int)$q['id'],$editIds,true)?'checked':'' ?>>
                        <span class="q-id">#<?= (int)$q['id'] ?></span>
                        <span class="q-text"><?= e(mb_strimwidth($q['prompt'],0,88,'...')) ?></span>
                        <small><?= e($q['type'].' • '.($q['difficulty'] ?? 'mudah')) ?></small>
                      </label>
                    <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <label class="form-check mt-3 mb-3"><input type="checkbox" name="status" class="form-check-input" <?= ($edit ? ((int)$edit['status']===1?'checked':'') : 'checked') ?>> Aktif</label>
      <button class="btn btn-primary rounded-pill">Simpan Template</button>
      <?php if($edit): ?><a href="<?= url('teacher/quizzes.php') ?>" class="btn btn-light rounded-pill">Batal</a><?php endif; ?>
    </form>
  </div></div>
  <div class="col-12"><div class="panel-card"><h3>Daftar Template</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Jenis</th><th>Nama</th><th>Soal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($templates as $t): ?><tr><td><span class="badge text-bg-primary"><?= e($t['assessment_type']) ?></span></td><td><b><?= e($t['title']) ?></b><br><small><?= e($t['description']) ?></small></td><td><?= (int)$t['total_questions'] ?></td><td><?= (int)$t['status']===1?'Aktif':'Nonaktif' ?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= (int)$t['id'] ?>">Edit</a> <form class="d-inline" method="post" onsubmit="return confirm('Nonaktifkan template ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_template"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-sm btn-outline-danger">Nonaktifkan</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
<script>
function updateSelectedQuestionCount() {
  const total = document.querySelectorAll('.template-question-picker input[type="checkbox"]:checked').length;
  const el = document.getElementById('selectedQuestionCount');
  if (el) el.textContent = total;
}
document.getElementById('btnAutoPickQuestions')?.addEventListener('click', () => {
  const mode = document.getElementById('autoPickMode').value;
  const type = document.getElementById('autoPickType').value;
  const difficulty = document.getElementById('autoPickDifficulty').value;
  const count = parseInt(document.getElementById('autoPickCount').value || '10', 10);
  const checks = Array.from(document.querySelectorAll('.template-question-picker .question-check'));
  let candidates = checks.filter(label => {
    return (!mode || label.dataset.mode === mode) && (!type || label.dataset.type === type) && (!difficulty || label.dataset.difficulty === difficulty);
  });
  candidates.sort(() => Math.random() - 0.5);
  candidates.slice(0, count).forEach(label => { const input = label.querySelector('input[type="checkbox"]'); if (input) input.checked = true; });
  updateSelectedQuestionCount();
});
document.getElementById('btnClearPickQuestions')?.addEventListener('click', () => {
  document.querySelectorAll('.template-question-picker input[type="checkbox"]').forEach(cb => cb.checked = false);
  updateSelectedQuestionCount();
});
document.getElementById('questionSearch')?.addEventListener('input', function () {
  const keyword = this.value.trim().toLowerCase();
  document.querySelectorAll('.template-question-picker .question-check').forEach(label => {
    label.style.display = !keyword || (label.dataset.prompt || '').includes(keyword) ? '' : 'none';
  });
});
document.querySelectorAll('.template-question-picker input[type="checkbox"]').forEach(cb => cb.addEventListener('change', updateSelectedQuestionCount));
updateSelectedQuestionCount();
</script>
<?php render_footer(); ?>
