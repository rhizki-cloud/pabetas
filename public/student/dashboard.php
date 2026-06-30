<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
render_header('Beranda Murid','student');
?>
<div class="student-hero clean-student-hero">
    <div><h1>Hai, <?= e(current_user()['name']) ?>! <?= e(pabetas_avatar(current_user()['avatar_key'] ?? 'rocket')['emoji']) ?></h1><p>Pilih satu jalur utama agar tidak bingung. Menu lengkap tetap ada di navbar atas.</p></div>
    <img src="<?= url('assets/img/mascot.svg') ?>" alt="Maskot" class="hero-mascot">
</div>
<div class="row g-4">
    <div class="col-lg-6"><a href="<?= url('student/academic.php') ?>" class="mode-card mode-green"><span>🎓</span><h2>Mulai Alur Belajar</h2><p>Pretest, materi, latihan, game, posttest, remedial, dan refleksi.</p></a></div>
    <div class="col-lg-6"><a href="<?= url('student/learn.php') ?>" class="mode-card mode-blue"><span>📚</span><h2>Papan Belajar</h2><p>Tonton video, pelajari tangga satuan, dan latihan drag & drop.</p></a></div>
    <div class="col-md-6"><a href="<?= url('student/live_join.php') ?>" class="mode-card mode-live"><span>⚡</span><h2>Live Game</h2><p>Masuk game seperti Quizizz memakai kode dari guru.</p></a></div>
    <div class="col-md-6"><a href="<?= url('student/team_join.php') ?>" class="mode-card mode-purple"><span>🤝</span><h2>Mode Tim 1 Device</h2><p>Satu kelompok memakai satu perangkat dengan peran dari guru.</p></a></div>
</div>
<?php render_footer(); ?>
