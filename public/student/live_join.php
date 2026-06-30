<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $game = live_game_fetch_by_code($code);
    if (!$game) {
        $error = 'Kode game tidak ditemukan.';
    } elseif ($game['status'] === 'finished') {
        $error = 'Game sudah selesai. Minta kode baru ke guru.';
    } else {
        live_game_ensure_player($game, current_user());
        redirect('student/live_play.php?game='.$game['id']);
    }
}
render_header('Masuk Live Game', 'live');
?>
<div class="page-title"><h1>⚡ Masuk Live Game</h1><p>Masukkan kode dari guru. Setelah mulai, soal akan tampil satu per satu seperti Quizizz.</p></div>
<div class="panel-card col-lg-5 mx-auto text-center">
  <div class="selected-avatar-preview mb-3"><span><?= e(pabetas_avatar(current_user()['avatar_key'] ?? 'rocket')['emoji']) ?></span><div>Avatar kamu: <b><?= e(pabetas_avatar(current_user()['avatar_key'] ?? 'rocket')['label']) ?></b></div><a href="<?= url('student/avatar.php') ?>">Ganti avatar</a></div>
  <?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <input class="form-control form-control-lg text-center room-input mb-3" name="code" placeholder="KODE GAME" required autofocus>
    <button class="btn btn-primary btn-lg rounded-pill w-100">Masuk Game</button>
  </form>
</div>
<?php render_footer(); ?>
