<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo = db();
$error = '';
$room = null;
$teams = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE code=? AND mode='tim' AND status='open' LIMIT 1");
    $stmt->execute([$code]);
    $room = $stmt->fetch();
    if ($room) {
        $stmt = $pdo->prepare('SELECT * FROM teams WHERE room_id=? ORDER BY id ASC');
        $stmt->execute([$room['id']]);
        $teams = $stmt->fetchAll();
    } else {
        $error = 'Kode room tidak ditemukan atau sudah ditutup.';
    }
}
render_header('Masuk Mode Tim', 'team');
?>
<div class="page-title compact-title"><h1>Mode Tim 1 Device 🤝</h1><p>Satu kelompok memakai satu perangkat. Pilih tim sesuai pembagian dari guru.</p></div>
<?php if($error): ?><div class="alert alert-danger fw-bold"><?= e($error) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-card">
      <h3>Masukkan Kode Room</h3>
      <form method="post"><?= csrf_field() ?><input class="form-control form-control-lg text-center room-input mb-3" name="code" value="<?= e($_POST['code'] ?? '') ?>" placeholder="CONTOH: ABC123" required><button class="btn btn-primary btn-lg rounded-pill w-100">Cari Room</button></form>
    </div>
  </div>
  <div class="col-lg-8">
    <?php if($room): ?>
      <div class="panel-card"><h3><?= e($room['name']) ?></h3><p>Kode: <span class="room-code"><?= e($room['code']) ?></span> | Waktu: <?= (int)($room['question_seconds'] ?? 45) ?> detik per soal</p>
      <?php if(!$teams): ?><div class="alert alert-warning">Guru belum membuat daftar tim.</div><?php endif; ?>
      <div class="row g-3">
        <?php foreach($teams as $t): $members=json_decode($t['members_json'] ?: '[]', true) ?: []; ?>
          <div class="col-md-6"><div class="team-select-card h-100"><h4><?= e($t['name']) ?></h4><ul class="mb-3">
            <?php foreach($members as $m): ?><li><b><?= e($m['name'] ?? '-') ?></b> <small><?= e($m['role'] ?? '-') ?></small></li><?php endforeach; ?>
          </ul><a class="btn btn-success rounded-pill w-100" href="<?= url('student/team_play.php?room='.(int)$room['id'].'&team='.(int)$t['id']) ?>">Pakai Device untuk Tim Ini</a></div></div>
        <?php endforeach; ?>
      </div></div>
    <?php else: ?>
      <div class="team-help-visual"><div class="big-emoji">👨‍👩‍👧‍👦</div><h3>Cara Bermain Tim</h3><ol><li>Guru membagi anggota dan peran.</li><li>Satu tim membuka satu HP/laptop.</li><li>Tim berdiskusi, lalu penjawab mengirim jawaban.</li></ol></div>
    <?php endif; ?>
  </div>
</div>
<?php render_footer(); ?>
