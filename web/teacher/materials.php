<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM learning_materials WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            trim($_POST['title'] ?? ''),
            trim($_POST['content'] ?? ''),
            (int)($_POST['step_order'] ?? 1),
            $_POST['media_type'] ?? 'text',
            trim($_POST['media_url'] ?? ''),
            isset($_POST['status']) ? 1 : 0
        ];
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE learning_materials SET title=?, content=?, step_order=?, media_type=?, media_url=?, status=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([...$data, $id]);
            flash('success','Materi berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO learning_materials(title,content,step_order,media_type,media_url,status,created_by) VALUES(?,?,?,?,?,?,?)');
            $stmt->execute([...$data, current_user()['id']]);
            flash('success','Materi baru berhasil ditambahkan.');
        }
        redirect('teacher/materials.php');
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare('UPDATE learning_materials SET status=0 WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success','Materi dinonaktifkan.');
        redirect('teacher/materials.php');
    }
}
$materials = $pdo->query('SELECT * FROM learning_materials ORDER BY step_order ASC, id ASC')->fetchAll();
render_header('CRUD Papan Belajar', 'materials');
?>
<div class="page-title"><h1>Papan Belajar Guru</h1><p>Kelola materi utama yang tampil pada halaman Papan Belajar siswa. Materi dibuat pendek, bertahap, dan mudah dibaca anak SD.</p></div>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="panel-card teacher-material-simple">
            <h3><?= $edit ? 'Edit Materi' : 'Tambah Materi Baru' ?></h3>
            <div class="teacher-simple-tip">💡 Tulis materi satu konsep per bagian. Contoh: urutan satuan, aturan turun, aturan naik, atau contoh soal.</div>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                <label class="form-label">Judul Materi</label>
                <input class="form-control mb-3" name="title" required placeholder="Contoh: Turun Tangga Berarti Dikali 10" value="<?= e($edit['title'] ?? '') ?>">

                <label class="form-label">Urutan Tampil</label>
                <input type="number" class="form-control mb-3" name="step_order" min="1" value="<?= e($edit['step_order'] ?? 1) ?>">

                <label class="form-label">Isi Materi</label>
                <textarea class="form-control mb-3" name="content" rows="8" required placeholder="Tuliskan penjelasan singkat, contoh soal, dan langkah pengerjaan."><?= e($edit['content'] ?? '') ?></textarea>

                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label">Media Tambahan</label>
                        <select name="media_type" class="form-select mb-3">
                            <option value="text">Tanpa media</option>
                            <option value="image" <?= (($edit['media_type'] ?? '')==='image')?'selected':'' ?>>Gambar</option>
                            <option value="video" <?= (($edit['media_type'] ?? '')==='video')?'selected':'' ?>>Video</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">URL Media</label>
                        <input class="form-control mb-3" name="media_url" placeholder="Opsional" value="<?= e($edit['media_url'] ?? '') ?>">
                    </div>
                </div>

                <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="status" <?= ($edit ? ($edit['status']?'checked':'') : 'checked') ?>> Aktif / tampil ke murid</label>
                <button class="btn btn-primary rounded-pill">Simpan Materi</button>
                <?php if ($edit): ?><a href="<?= url('teacher/materials.php') ?>" class="btn btn-light rounded-pill">Batal</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel-card">
            <h3>Daftar Materi</h3>
            <p class="small text-muted">Urutan materi bisa digeser agar tampilan siswa lebih runtut.</p>
            <div id="materialList" class="drag-list">
                <?php foreach($materials as $m): ?>
                <div class="drag-row material-row-clean" draggable="true" data-id="<?= $m['id'] ?>">
                    <div><span class="grab">☰</span> <b><?= e($m['step_order']) ?>. <?= e($m['title']) ?></b><br><small><?= $m['status'] ? 'Aktif' : 'Nonaktif' ?> · <?= e($m['media_type'] ?: 'text') ?></small></div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-warning rounded-pill" href="?edit=<?= $m['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Nonaktifkan materi ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-outline-danger rounded-pill">Hapus</button></form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="reorderInfo" class="small text-success mt-2"></div>
        </div>
    </div>
</div>
<script src="<?= url('assets/js/materials.js') ?>"></script>
<?php render_footer(); ?>
