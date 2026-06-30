<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $avatar = $_POST['avatar_key'] ?? 'rocket';
    if (!array_key_exists($avatar, pabetas_avatar_options())) $avatar = 'rocket';
    db()->prepare('UPDATE users SET avatar_key=? WHERE id=?')->execute([$avatar, $user['id']]);
    flash('success', 'Avatar berhasil disimpan. Avatar ini akan tampil di lobby dan ranking live game.');
    redirect('student/avatar.php');
}
$user = current_user();
$options = pabetas_avatar_options();
$current = $user['avatar_key'] ?? 'rocket';
render_header('Pilih Avatar', 'avatar');
?>
<div class="page-title"><h1>🎭 Pilih Avatar Murid</h1><p>Pilih karakter lucu yang akan tampil di lobby, ranking sementara, dan ranking akhir.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<form method="post">
<?= csrf_field() ?>
<div class="avatar-grid">
<?php foreach($options as $key=>$a): ?>
<label class="avatar-choice <?= $current===$key?'selected':'' ?>" style="--avatar-color: <?= e($a['color']) ?>">
  <input type="radio" name="avatar_key" value="<?= e($key) ?>" <?= $current===$key?'checked':'' ?>>
  <span class="avatar-emoji"><?= e($a['emoji']) ?></span>
  <b><?= e($a['label']) ?></b>
</label>
<?php endforeach; ?>
</div>
<button class="btn btn-primary btn-lg rounded-pill mt-4">Simpan Avatar</button>
<a class="btn btn-light btn-lg rounded-pill mt-4" href="<?= url('student/dashboard.php') ?>">Kembali</a>
</form>
<?php render_footer(); ?>
