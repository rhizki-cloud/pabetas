<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
render_header('Ranking Live','student');
?>
<div class="page-title"><h1>🏆 Ranking Live PABETAS</h1><p>Papan skor diperbarui otomatis seperti game online.</p></div>
<div class="panel-card ranking-game"><table class="table table-borderless align-middle" id="rankingTable"><thead><tr><th>Rank</th><th>Nama</th><th>Skor</th><th>Benar</th><th>Status</th></tr></thead><tbody><tr><td colspan="5">Memuat ranking...</td></tr></tbody></table></div>
<script src="<?= url('assets/js/dashboard.js') ?>"></script>
<?php render_footer(); ?>
