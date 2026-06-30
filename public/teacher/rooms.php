<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();

function ensure_team_v6_columns(PDO $pdo) {
    try { $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS question_seconds INT NOT NULL DEFAULT 30 AFTER status"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE rooms ADD COLUMN IF NOT EXISTS team_question_count INT NOT NULL DEFAULT 5 AFTER question_seconds"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE teams ADD COLUMN IF NOT EXISTS members_json TEXT NULL AFTER name"); } catch (Throwable $e) {}
}
ensure_team_v6_columns($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = random_room_code();
        $name = trim($_POST['name'] ?? '');
        $questionSeconds = max(10, min(180, (int)($_POST['question_seconds'] ?? 45)));
        $questionCount = max(1, min(20, (int)($_POST['team_question_count'] ?? 5)));
        $teamNames = $_POST['team_name'] ?? [];
        $memberNames = $_POST['member_name'] ?? [];
        $memberRoles = $_POST['member_role'] ?? [];

        $pdo->prepare('INSERT INTO rooms(code,name,class_name,mode,status,question_seconds,team_question_count,created_by) VALUES(?,?,?,?,?,?,?,?)')
            ->execute([$code, $name ?: 'Game Tim Satuan Panjang', 'III A', 'tim', 'open', $questionSeconds, $questionCount, current_user()['id']]);
        $roomId = (int)$pdo->lastInsertId();

        for ($i = 0; $i < count($teamNames); $i++) {
            $teamName = trim($teamNames[$i] ?? '');
            if ($teamName === '') continue;
            $members = [];
            for ($j = 0; $j < 4; $j++) {
                $studentName = trim($memberNames[$i][$j] ?? '');
                $roleName = trim($memberRoles[$i][$j] ?? '');
                if ($studentName !== '' || $roleName !== '') {
                    $members[] = [
                        'name' => $studentName ?: 'Anggota '.($j + 1),
                        'role' => $roleName ?: ['Pembaca Soal','Penjaga Tangga','Penghitung','Penjawab'][$j]
                    ];
                }
            }
            if (!$members) {
                $members = [
                    ['name'=>'Anggota 1','role'=>'Pembaca Soal'],
                    ['name'=>'Anggota 2','role'=>'Penjaga Tangga'],
                    ['name'=>'Anggota 3','role'=>'Penghitung'],
                    ['name'=>'Anggota 4','role'=>'Penjawab'],
                ];
            }
            $pdo->prepare('INSERT INTO teams(room_id,name,members_json) VALUES(?,?,?)')
                ->execute([$roomId, $teamName, json_encode($members, JSON_UNESCAPED_UNICODE)]);
        }

        flash('success', 'Room tim 1 device dibuat. Kode room: '.$code);
        redirect('teacher/rooms.php');
    }

    if ($action === 'close') {
        $roomId = (int)($_POST['room_id'] ?? 0);
        $pdo->prepare("UPDATE rooms SET status='closed', finish_at=NOW() WHERE id=?")->execute([$roomId]);
        flash('success', 'Room ditutup.');
        redirect('teacher/rooms.php');
    }
}

$rooms = $pdo->query('SELECT r.*, COUNT(t.id) AS total_teams FROM rooms r LEFT JOIN teams t ON t.room_id=r.id GROUP BY r.id ORDER BY r.id DESC')->fetchAll();
render_header('Mode Tim 1 Device', 'rooms');
?>
<div class="page-title compact-title">
  <h1>Mode Tim 1 Device 🤝</h1>
  <p>Satu kelompok memakai satu perangkat. Guru menentukan anggota dan peran manual, misalnya pembaca soal, penjaga tangga, penghitung, dan penjawab.</p>
</div>
<?php if ($msg = flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-xl-5">
    <div class="panel-card">
      <h3>Buat Room Tim</h3>
      <p class="text-muted mb-3">Tidak ada pembagian otomatis. Isi nama tim, anggota, dan tugasnya sesuai keputusan guru.</p>
      <form method="post" id="teamRoomForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <label class="form-label">Nama kegiatan</label>
        <input class="form-control mb-3" name="name" placeholder="Contoh: Tantangan Tangga Satuan" required>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">Waktu per soal</label><input type="number" class="form-control" name="question_seconds" value="45" min="10" max="180"></div>
          <div class="col-6"><label class="form-label">Jumlah soal per tim</label><input type="number" class="form-control" name="team_question_count" value="5" min="1" max="20"></div>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Daftar Tim</h5>
          <button class="btn btn-sm btn-outline-primary rounded-pill" type="button" id="addTeamBtn">+ Tambah Tim</button>
        </div>
        <div id="teamBuilder"></div>
        <button class="btn btn-primary rounded-pill w-100 mt-3">Buat Room Tim</button>
      </form>
    </div>
  </div>
  <div class="col-xl-7">
    <div class="panel-card">
      <h3>Daftar Room</h3>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Kode</th><th>Nama</th><th>Tim</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach ($rooms as $r): ?>
            <tr>
              <td><span class="room-code"><?= e($r['code']) ?></span></td>
              <td><?= e($r['name']) ?></td>
              <td><?= (int)$r['total_teams'] ?> tim</td>
              <td><?= (int)($r['question_seconds'] ?? 30) ?> detik</td>
              <td><span class="badge text-bg-<?= $r['status']==='open'?'success':($r['status']==='running'?'warning':'secondary') ?>"><?= e($r['status']) ?></span></td>
              <td>
                <?php if ($r['status'] !== 'closed'): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Tutup room ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="close"><input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-outline-danger rounded-pill">Tutup</button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const wrap=document.getElementById('teamBuilder'); const btn=document.getElementById('addTeamBtn'); let idx=0;
  const roles=['Pembaca Soal','Penjaga Tangga','Penghitung','Penjawab'];
  function teamCard(){
    const i=idx++;
    const card=document.createElement('div'); card.className='team-builder-card';
    card.innerHTML=`<div class="d-flex justify-content-between align-items-center mb-2"><input class="form-control fw-bold" name="team_name[]" value="Tim ${i+1}" required><button type="button" class="btn btn-sm btn-light remove-team">Hapus</button></div>`+
      roles.map((r,j)=>`<div class="row g-2 mb-2"><div class="col-6"><input class="form-control" name="member_name[${i}][${j}]" placeholder="Nama anak ${j+1}"></div><div class="col-6"><input class="form-control" name="member_role[${i}][${j}]" value="${r}" placeholder="Peran"></div></div>`).join('');
    card.querySelector('.remove-team').addEventListener('click',()=>card.remove());
    wrap.appendChild(card);
  }
  btn?.addEventListener('click',teamCard); teamCard();
})();
</script>
<?php render_footer(); ?>
