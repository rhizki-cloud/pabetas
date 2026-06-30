<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action'] ?? '';
    if ($action==='create') {
        $name=trim($_POST['name']); $username=trim($_POST['username']); $password=password_hash($_POST['password'] ?: 'siswa123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users(name,username,password,role,status) VALUES(?,?,?,?,1)")->execute([$name,$username,$password,'murid']);
        $uid=$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO students(user_id,class_name,nis) VALUES(?,?,?)")->execute([$uid,trim($_POST['class_name']),trim($_POST['nis'])]);
        flash('success','Murid berhasil ditambahkan.'); redirect('teacher/students.php');
    }
    if ($action==='reset') {
        $pdo->prepare('UPDATE users SET password=? WHERE id=? AND role=?')->execute([password_hash('siswa123', PASSWORD_DEFAULT), (int)$_POST['user_id'], 'murid']);
        flash('success','Password murid direset menjadi siswa123.'); redirect('teacher/students.php');
    }
}
$stmt = $pdo->prepare('SELECT u.*, s.class_name, s.nis FROM users u LEFT JOIN students s ON s.user_id = u.id WHERE u.role = ? ORDER BY u.name');
$stmt->execute(['murid']);
$students = $stmt->fetchAll();
render_header('Data Murid','students');
?>
<div class="page-title"><h1>Data Murid</h1><p>Tambah akun murid dan reset password untuk keperluan uji coba kelas.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-4"><div class="panel-card"><h3>Tambah Murid</h3><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create"><input class="form-control mb-2" name="name" placeholder="Nama murid" required><input class="form-control mb-2" name="username" placeholder="Username" required><input class="form-control mb-2" name="nis" placeholder="NIS"><input class="form-control mb-2" name="class_name" value="III A" placeholder="Kelas"><input type="password" class="form-control mb-3" name="password" placeholder="Password, kosong = siswa123"><button class="btn btn-primary rounded-pill">Tambah Murid</button></form></div></div>
<div class="col-lg-8"><div class="panel-card"><h3>Daftar Murid</h3><div class="table-responsive"><table class="table"><thead><tr><th>Nama</th><th>Username</th><th>Kelas</th><th>Aksi</th></tr></thead><tbody><?php foreach($students as $s): ?><tr><td><?= e($s['name']) ?></td><td><?= e($s['username']) ?></td><td><?= e($s['class_name'] ?: '-') ?></td><td><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-warning">Reset Password</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php render_footer(); ?>
