<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [(int)$_POST['step_order'], trim($_POST['title']), trim($_POST['description']), $_POST['action_type'], trim($_POST['action_payload'] ?? ''), isset($_POST['is_required'])?1:0, isset($_POST['status'])?1:0];
        if ($id) {
            $pdo->prepare('UPDATE learning_flow_steps SET step_order=?, title=?, description=?, action_type=?, action_payload=?, is_required=?, status=?, updated_at=NOW() WHERE id=?')->execute([...$data,$id]);
            flash('success','Langkah alur diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO learning_flow_steps(step_order,title,description,action_type,action_payload,is_required,status,created_at) VALUES(?,?,?,?,?,?,?,NOW())')->execute($data);
            flash('success','Langkah alur ditambahkan.');
        }
        redirect('teacher/learning_flow.php');
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM learning_flow_steps WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success','Langkah alur dihapus.');
        redirect('teacher/learning_flow.php');
    }
}
$edit = null;
if (isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM learning_flow_steps WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch(); }
$steps = $pdo->query('SELECT * FROM learning_flow_steps ORDER BY step_order ASC, id ASC')->fetchAll();
$templates = $pdo->query("SELECT id,title,assessment_type FROM assessment_templates WHERE status=1 ORDER BY assessment_type,title")->fetchAll();
render_header('Alur Belajar', 'learning_flow');
?>
<div class="page-title"><h1>Kelola Alur Belajar</h1><p>Guru menentukan urutan belajar siswa: pretest, materi, live game, posttest, remedial, refleksi, atau kuis tambahan.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="panel-card"><h3><?= $edit?'Edit Langkah':'Tambah Langkah' ?></h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<label class="form-label">Urutan</label><input type="number" class="form-control mb-3" name="step_order" value="<?= e($edit['step_order'] ?? 1) ?>">
<label class="form-label">Judul</label><input class="form-control mb-3" name="title" required value="<?= e($edit['title'] ?? '') ?>">
<label class="form-label">Deskripsi</label><textarea class="form-control mb-3" name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea>
<label class="form-label">Jenis Aksi</label><select class="form-select mb-3" name="action_type" id="actionType">
<?php foreach(['pretest'=>'Pretest','material'=>'Papan Belajar','live_game'=>'Live Game','posttest'=>'Posttest','remedial'=>'Remedial','quiz'=>'Kuis dari Template','reflection'=>'Refleksi Murid','custom'=>'Link Custom'] as $k=>$v): ?><option value="<?= $k ?>" <?= (($edit['action_type'] ?? '')===$k)?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
</select>
<label class="form-label">Payload / Link</label><input class="form-control mb-2" name="action_payload" value="<?= e($edit['action_payload'] ?? '') ?>" placeholder="Untuk kuis: isi ID template. Untuk custom: isi URL.">
<div class="small text-muted mb-3">Template tersedia: <?php foreach($templates as $t): ?><span class="badge text-bg-light">#<?= (int)$t['id'] ?> <?= e($t['assessment_type']) ?>: <?= e($t['title']) ?></span> <?php endforeach; ?></div>
<label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_required" <?= ($edit ? ((int)$edit['is_required']?'checked':'') : 'checked') ?>> Wajib</label>
<label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="status" <?= ($edit ? ((int)$edit['status']?'checked':'') : 'checked') ?>> Tampil ke siswa</label>
<button class="btn btn-primary rounded-pill">Simpan Alur</button> <?php if($edit): ?><a class="btn btn-light rounded-pill" href="<?= url('teacher/learning_flow.php') ?>">Batal</a><?php endif; ?>
</form></div></div><div class="col-lg-7"><div class="panel-card"><h3>Urutan Saat Ini</h3><div class="flow-admin-list">
<?php foreach($steps as $s): ?><div class="flow-admin-item"><div><b><?= (int)$s['step_order'] ?>. <?= e($s['title']) ?></b><br><small><?= e($s['action_type']) ?> <?= (int)$s['is_required']?'• wajib':'• opsional' ?> <?= (int)$s['status']?'• tampil':'• sembunyi' ?></small><p><?= e($s['description']) ?></p></div><div><a class="btn btn-sm btn-warning" href="?edit=<?= (int)$s['id'] ?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Hapus langkah ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></div></div><?php endforeach; ?>
</div></div></div></div>
<?php render_footer(); ?>
