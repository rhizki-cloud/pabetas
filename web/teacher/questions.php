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
$edit = null;
if (isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM questions WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $options = array_values(array_filter(array_map('trim', explode("\n", $_POST['options_text'] ?? ''))));
        $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
        $mode = $_POST['mode']; $type = $_POST['type'];
        $defaultGroup = ucfirst($mode) . ' ' . ($type === 'multiple' ? 'Pilihan Ganda' : ($type === 'short' ? 'Jawaban Singkat' : 'Esai'));
        $group = trim($_POST['group_label'] ?? '') ?: $defaultGroup;
        $difficulty = $_POST['difficulty'] ?? 'mudah';
        $data = [$mode, $type, $group, $difficulty, trim($_POST['prompt']), $optionsJson, trim($_POST['answer_key']), (int)$_POST['score'], isset($_POST['status'])?1:0];
        if ($id) {
            $stmt=$pdo->prepare('UPDATE questions SET mode=?, type=?, group_label=?, difficulty=?, prompt=?, options_json=?, answer_key=?, score=?, status=? WHERE id=?');
            $stmt->execute([...$data,$id]); flash('success','Soal diperbarui.');
        } else {
            $stmt=$pdo->prepare('INSERT INTO questions(mode,type,group_label,difficulty,prompt,options_json,answer_key,score,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([...$data,current_user()['id']]); flash('success','Soal ditambahkan.');
        }
        redirect('teacher/questions.php');
    }
    if ($action === 'delete') { $stmt=$pdo->prepare('UPDATE questions SET status=0 WHERE id=?'); $stmt->execute([(int)$_POST['id']]); flash('success','Soal dinonaktifkan.'); redirect('teacher/questions.php'); }
}
$questions = $pdo->query('SELECT * FROM questions ORDER BY mode ASC, type ASC, group_label ASC, id DESC')->fetchAll();
$groups=[]; $counts=[];
foreach($questions as $q){
    $key=$q['mode'].'|'.$q['type'].'|'.($q['group_label'] ?: 'Umum');
    $groups[$key][]=$q;
    $counts[$q['mode'].' '.$q['type']] = ($counts[$q['mode'].' '.$q['type']] ?? 0) + ((int)$q['status']===1 ? 1 : 0);
}
$optionsText = $edit ? implode("\n", json_decode($edit['options_json'] ?: '[]', true) ?: []) : "3000 m\n300 m\n30 m\n3 m";
render_header('Bank Soal', 'questions');
?>
<div class="page-title"><h1>Bank Soal Berkelompok</h1><p>Soal dipisah berdasarkan mode dan jenis: individu esai, individu pilgan, tim pilgan, tim jawaban singkat, dan lainnya.</p></div>
<?php if ($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="question-summary-grid mb-4">
<?php foreach(['individu multiple'=>'Individu Pilgan','individu short'=>'Individu Singkat','individu essay'=>'Individu Esai','tim multiple'=>'Tim Pilgan','tim short'=>'Tim Singkat','tim essay'=>'Tim Esai'] as $k=>$label): ?>
  <div class="question-summary-card"><span><?= e($label) ?></span><b><?= (int)($counts[$k] ?? 0) ?></b></div>
<?php endforeach; ?>
</div>
<div class="row g-4">
<div class="col-lg-5"><div class="panel-card"><h3><?= $edit?'Edit Soal':'Tambah Soal' ?></h3><form method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<div class="row"><div class="col-md-6"><label class="form-label">Mode</label><select class="form-select mb-3" name="mode"><option value="individu" <?= (($edit['mode'] ?? '')==='individu')?'selected':'' ?>>Individu</option><option value="tim" <?= (($edit['mode'] ?? '')==='tim')?'selected':'' ?>>Tim</option></select></div><div class="col-md-6"><label class="form-label">Jenis</label><select class="form-select mb-3" name="type"><option value="multiple" <?= (($edit['type'] ?? '')==='multiple')?'selected':'' ?>>Pilihan Ganda</option><option value="short" <?= (($edit['type'] ?? '')==='short')?'selected':'' ?>>Jawaban Singkat</option><option value="essay" <?= (($edit['type'] ?? '')==='essay')?'selected':'' ?>>Esai</option></select></div></div>
<div class="row"><div class="col-md-7"><label class="form-label">Kelompok Soal</label><input class="form-control mb-3" name="group_label" value="<?= e($edit['group_label'] ?? '') ?>" placeholder="Contoh: Tim Pilihan Ganda"></div><div class="col-md-5"><label class="form-label">Tingkat</label><select class="form-select mb-3" name="difficulty"><option value="mudah" <?= (($edit['difficulty'] ?? '')==='mudah')?'selected':'' ?>>Mudah</option><option value="sedang" <?= (($edit['difficulty'] ?? '')==='sedang')?'selected':'' ?>>Sedang</option><option value="sulit" <?= (($edit['difficulty'] ?? '')==='sulit')?'selected':'' ?>>Sulit</option></select></div></div>
<label class="form-label">Teks Soal</label><textarea class="form-control mb-3" name="prompt" rows="4" required><?= e($edit['prompt'] ?? '') ?></textarea>
<label class="form-label">Opsi Pilihan Ganda, satu baris satu opsi</label><textarea class="form-control mb-3" name="options_text" rows="4"><?= e($optionsText) ?></textarea>
<label class="form-label">Kunci Jawaban</label><input class="form-control mb-3" name="answer_key" required value="<?= e($edit['answer_key'] ?? '') ?>">
<label class="form-label">Bobot Skor</label><input type="number" class="form-control mb-3" name="score" value="<?= e($edit['score'] ?? 10) ?>">
<label class="form-check mb-3"><input type="checkbox" name="status" class="form-check-input" <?= ($edit ? ($edit['status']?'checked':'') : 'checked') ?>> Aktif</label>
<button class="btn btn-primary rounded-pill">Simpan Soal</button><?php if($edit): ?><a href="<?= url('teacher/questions.php') ?>" class="btn btn-light rounded-pill">Batal</a><?php endif; ?>
</form></div></div>
<div class="col-lg-7"><div class="panel-card"><h3>Daftar Soal per Kelompok</h3>
<div class="accordion" id="questionAccordion">
<?php $n=0; foreach($groups as $key=>$items): $n++; [$mode,$type,$label]=explode('|',$key,3); ?>
  <div class="accordion-item question-accordion-item"><h2 class="accordion-header"><button class="accordion-button <?= $n>1?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#qg<?= $n ?>"><b><?= e(ucfirst($mode)) ?> • <?= e($label) ?></b><span class="badge text-bg-primary ms-2"><?= count($items) ?> soal</span></button></h2>
  <div id="qg<?= $n ?>" class="accordion-collapse collapse <?= $n===1?'show':'' ?>" data-bs-parent="#questionAccordion"><div class="accordion-body p-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>ID</th><th>Soal</th><th>Kunci</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  <?php foreach($items as $q): ?><tr><td>#<?= (int)$q['id'] ?><br><small><?= e($q['type']) ?> • <?= e($q['difficulty'] ?? 'mudah') ?></small></td><td><?= e(mb_strimwidth($q['prompt'],0,90,'...')) ?></td><td><?= e($q['answer_key']) ?></td><td><?= (int)$q['status']===1?'<span class="badge text-bg-success">Aktif</span>':'<span class="badge text-bg-secondary">Nonaktif</span>' ?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= (int)$q['id'] ?>">Edit</a><form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div></div>
<?php endforeach; ?>
</div></div></div>
</div>
<?php render_footer(); ?>
