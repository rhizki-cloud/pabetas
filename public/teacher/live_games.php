<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('guru');
$pdo = db();

function live_question_picker_groups(PDO $pdo): array {
    $rows = $pdo->query("SELECT * FROM questions WHERE status=1 AND type IN ('multiple','short') ORDER BY mode ASC, group_label ASC, difficulty ASC, id ASC")->fetchAll();
    $groups = [];
    foreach ($rows as $q) {
        $key = ucfirst($q['mode']) . ' - ' . ($q['group_label'] ?: $q['type']);
        $groups[$key][] = $q;
    }
    return $groups;
}

function build_question_filter_sql(string $mode, array $filters, array &$params): string {
    $where = "status=1 AND mode=? AND type IN ('multiple','short')";
    $params[] = $mode;
    if (!empty($filters['type']) && in_array($filters['type'], ['multiple','short'], true)) {
        $where .= " AND type=?";
        $params[] = $filters['type'];
    }
    if (!empty($filters['difficulty']) && in_array($filters['difficulty'], ['mudah','sedang','sulit'], true)) {
        $where .= " AND difficulty=?";
        $params[] = $filters['difficulty'];
    }
    if (!empty($filters['group_label'])) {
        $where .= " AND group_label=?";
        $params[] = trim($filters['group_label']);
    }
    return $where;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $mode = in_array($_POST['mode'] ?? 'individu', ['individu','tim'], true) ? $_POST['mode'] : 'individu';
        $count = max(1, min(30, (int)($_POST['question_count'] ?? 10)));
        $questionSeconds = max(5, min(120, (int)($_POST['question_seconds'] ?? 20)));
        $rankingSeconds = max(3, min(30, (int)($_POST['ranking_seconds'] ?? 5)));
        $controlMode = in_array($_POST['control_mode'] ?? 'auto', ['auto','manual'], true) ? $_POST['control_mode'] : 'auto';
        $selectMode = in_array($_POST['question_select_mode'] ?? 'auto', ['auto','manual'], true) ? $_POST['question_select_mode'] : 'auto';
        $roomId = null;
        if ($mode === 'tim' && !empty($_POST['room_id'])) $roomId = (int)$_POST['room_id'];

        if ($selectMode === 'manual') {
            $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['question_ids'] ?? []))));
            if (!$ids) {
                flash('error', 'Pilih minimal 1 soal dari Bank Soal untuk mode manual.');
                redirect('teacher/live_games.php');
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$mode], $ids);
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE status=1 AND mode=? AND type IN ('multiple','short') AND id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
            $stmt->execute(array_merge($params, $ids));
            $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
            if (!$ids) {
                flash('error', 'Soal manual tidak sesuai dengan mode game atau belum aktif.');
                redirect('teacher/live_games.php');
            }
        } else {
            $params = [];
            $filters = [
                'type' => $_POST['auto_type'] ?? '',
                'difficulty' => $_POST['auto_difficulty'] ?? '',
                'group_label' => $_POST['auto_group_label'] ?? '',
            ];
            $where = build_question_filter_sql($mode, $filters, $params);
            $poolStmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE $where");
            $poolStmt->execute($params);
            $availableQuestions = (int)$poolStmt->fetchColumn();
            if ($availableQuestions < $count) {
                flash('error', 'Soal aktif sesuai filter hanya ' . $availableQuestions . '. Tambahkan soal, ubah filter, atau turunkan jumlah soal.');
                redirect('teacher/live_games.php');
            }
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE $where ORDER BY RAND() LIMIT $count");
            $stmt->execute($params);
            $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
            if (count($ids) !== $count) {
                flash('error', 'Soal yang terambil tidak sesuai jumlah. Silakan coba lagi atau cek Bank Soal.');
                redirect('teacher/live_games.php');
            }
        }

        do {
            $code = random_room_code(6);
            $check = $pdo->prepare('SELECT id FROM live_games WHERE code=?');
            $check->execute([$code]);
        } while ($check->fetch());
        $name = trim($_POST['name'] ?? '') ?: 'Live Game PABETAS';
        $pdo->prepare("INSERT INTO live_games(code,name,mode,status,phase,current_index,question_ids_json,question_seconds,ranking_seconds,control_mode,created_by,room_id,created_at,phase_started_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
            ->execute([$code,$name,$mode,'waiting','waiting',0,json_encode($ids),$questionSeconds,$rankingSeconds,$controlMode,current_user()['id'],$roomId]);
        flash('success', 'Live game berhasil dibuat dengan ' . count($ids) . ' soal. Kode game: ' . $code);
        redirect('teacher/live_games.php');
    }
    if ($action === 'start') {
        $id = (int)$_POST['game_id'];
        $pdo->prepare('DELETE FROM live_game_powerup_uses WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM live_game_answers WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare('UPDATE live_game_players SET score=0,total_correct=0,total_wrong=0,last_seen=NOW() WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare("UPDATE live_games SET status='running', phase='question', current_index=0, started_at=NOW(), phase_started_at=NOW(), finished_at=NULL WHERE id=?")->execute([$id]);
        flash('success', 'Game dimulai. Siswa bisa masuk memakai kode game.');
        redirect('teacher/live_monitor.php?game='.$id);
    }
    if ($action === 'finish') {
        $id = (int)$_POST['game_id'];
        $pdo->prepare("UPDATE live_games SET status='finished', phase='final', finished_at=NOW(), phase_started_at=NOW() WHERE id=?")->execute([$id]);
        flash('success', 'Game ditutup. Ranking akhir tersedia.');
        redirect('teacher/live_games.php');
    }
    if ($action === 'reset') {
        $id = (int)$_POST['game_id'];
        $pdo->prepare('DELETE FROM live_game_powerup_uses WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM live_game_answers WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM live_game_players WHERE live_game_id=?')->execute([$id]);
        $pdo->prepare("UPDATE live_games SET status='waiting', phase='waiting', current_index=0, started_at=NULL, finished_at=NULL, phase_started_at=NOW() WHERE id=?")->execute([$id]);
        flash('success', 'Game direset. Skor dan peserta dikosongkan.');
        redirect('teacher/live_games.php');
    }
    if ($action === 'delete') {
        $id = (int)$_POST['game_id'];
        $check = $pdo->prepare('SELECT status FROM live_games WHERE id=? LIMIT 1');
        $check->execute([$id]);
        $status = $check->fetchColumn();
        if (!$status) {
            flash('error', 'Game tidak ditemukan.');
            redirect('teacher/live_games.php');
        }
        if ($status !== 'finished') {
            flash('error', 'Game hanya boleh dihapus jika statusnya sudah finished. Gunakan Finish dulu jika game masih berjalan.');
            redirect('teacher/live_games.php');
        }
        $pdo->prepare('DELETE FROM live_games WHERE id=?')->execute([$id]);
        flash('success', 'Game selesai berhasil dihapus.');
        redirect('teacher/live_games.php');
    }
}
$rooms = $pdo->query("SELECT id, code, name FROM rooms ORDER BY id DESC")->fetchAll();
$games = $pdo->query("SELECT lg.*, (SELECT COUNT(*) FROM live_game_players p WHERE p.live_game_id=lg.id) AS players FROM live_games lg ORDER BY lg.id DESC LIMIT 30")->fetchAll();
$questionGroups = live_question_picker_groups($pdo);
$groupLabels = $pdo->query("SELECT DISTINCT group_label FROM questions WHERE status=1 AND group_label IS NOT NULL AND group_label<>'' ORDER BY group_label ASC")->fetchAll(PDO::FETCH_COLUMN);
render_header('Live Game', 'live_games');
?>
<div class="page-title"><h1>⚡ Live Game seperti Quizizz</h1><p>Guru bisa mengambil soal otomatis dari Bank Soal atau memilih soal manual sesuai kebutuhan sesi.</p></div>
<?php if($msg=flash('success')): ?><div class="alert alert-success fw-bold"><?= e($msg) ?></div><?php endif; ?>
<?php if($msg=flash('error')): ?><div class="alert alert-danger fw-bold"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="panel-card">
      <h3>Buat Live Game</h3>
      <form method="post" id="liveGameForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label class="form-label fw-bold">Nama game</label>
        <input class="form-control mb-3" name="name" placeholder="Contoh: Tantangan Tangga Satuan" required>
        <label class="form-label fw-bold">Mode</label>
        <select class="form-select mb-3" name="mode" id="liveModeSelect">
          <option value="individu">Individu</option>
          <option value="tim">Tim</option>
        </select>
        <div id="roomBox" class="mb-3 d-none">
          <label class="form-label fw-bold">Room tim, opsional</label>
          <select class="form-select" name="room_id">
            <option value="">Tanpa room khusus</option>
            <?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['code'].' - '.$r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <label class="form-label fw-bold">Kontrol lanjut soal</label>
        <select class="form-select mb-3" name="control_mode"><option value="auto">Otomatis seperti Quizizz</option><option value="manual">Manual oleh guru</option></select>
        <label class="form-label fw-bold">Cara mengambil soal</label>
        <select class="form-select mb-3" name="question_select_mode" id="questionSelectMode"><option value="auto">Otomatis dari Bank Soal</option><option value="manual">Pilih manual dari Bank Soal</option></select>
        <div class="row g-2">
          <div class="col-4"><label class="form-label">Jumlah</label><input type="number" class="form-control" name="question_count" value="10" min="1" max="30"></div>
          <div class="col-4"><label class="form-label">Detik soal</label><input type="number" class="form-control" name="question_seconds" value="20" min="5" max="120"></div>
          <div class="col-4"><label class="form-label">Detik rank</label><input type="number" class="form-control" name="ranking_seconds" value="5" min="3" max="30"></div>
        </div>
        <div id="autoQuestionBox" class="mt-3 p-3 rounded-4 bg-light">
          <b>Filter otomatis</b>
          <div class="row g-2 mt-1">
            <div class="col-md-4"><select class="form-select" name="auto_type"><option value="">Semua jenis</option><option value="multiple">Pilgan</option><option value="short">Jawaban singkat</option></select></div>
            <div class="col-md-4"><select class="form-select" name="auto_difficulty"><option value="">Semua level</option><option value="mudah">Mudah</option><option value="sedang">Sedang</option><option value="sulit">Sulit</option></select></div>
            <div class="col-md-4"><select class="form-select" name="auto_group_label"><option value="">Semua kelompok</option><?php foreach($groupLabels as $gl): ?><option value="<?= e($gl) ?>"><?= e($gl) ?></option><?php endforeach; ?></select></div>
          </div>
        </div>
        <div id="manualQuestionBox" class="mt-3 d-none">
          <div class="alert alert-info py-2">Centang soal yang ingin dimasukkan. Soal yang berbeda mode akan otomatis ditolak saat disimpan.</div>
          <div class="template-question-picker live-question-picker">
            <?php foreach($questionGroups as $group=>$items): ?>
              <div class="question-group-box">
                <div class="question-group-title"><?= e($group) ?> <span><?= count($items) ?> soal</span></div>
                <?php foreach($items as $q): ?>
                  <label class="question-check" data-mode="<?= e($q['mode']) ?>"><input type="checkbox" name="question_ids[]" value="<?= (int)$q['id'] ?>"> <b>#<?= (int)$q['id'] ?></b> <?= e(mb_strimwidth($q['prompt'],0,62,'...')) ?> <small><?= e($q['type'].' • '.$q['difficulty']) ?></small></label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="btn btn-primary rounded-pill mt-4 w-100">Buat Kode Game</button>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel-card">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><h3>Daftar Live Game</h3><a class="btn btn-outline-primary rounded-pill" href="<?= url('teacher/weekly_leaderboard.php') ?>">🏆 Leaderboard Mingguan</a></div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Kode</th><th>Nama</th><th>Mode</th><th>Status</th><th>Soal</th><th>Peserta</th><th>Aksi</th></tr></thead>
          <tbody>
          <?php foreach($games as $g): $totalQ=count(json_decode($g['question_ids_json'] ?: '[]', true) ?: []); ?>
            <tr>
              <td><span class="room-code"><?= e($g['code']) ?></span></td>
              <td><?= e($g['name']) ?></td>
              <td><?= e($g['mode']) ?><br><small class="text-muted"><?= e($g['control_mode'] ?? 'auto') ?></small></td>
              <td><span class="badge text-bg-<?= $g['status']==='running'?'success':($g['status']==='finished'?'dark':'warning') ?>"><?= e($g['status']) ?></span></td>
              <td><?= (int)$totalQ ?></td>
              <td><?= (int)$g['players'] ?></td>
              <td class="d-flex gap-1 flex-wrap">
                <a class="btn btn-sm btn-primary" href="<?= url('teacher/live_monitor.php?game='.$g['id']) ?>">Monitor</a>
                <a class="btn btn-sm btn-success" href="<?= url('teacher/live_recap.php?game='.$g['id']) ?>">Rekap</a>
                <?php if($g['status']!=='running'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="start"><input type="hidden" name="game_id" value="<?= $g['id'] ?>"><button class="btn btn-sm btn-success">Start</button></form><?php endif; ?>
                <?php if($g['status']==='running'): ?><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="finish"><input type="hidden" name="game_id" value="<?= $g['id'] ?>"><button class="btn btn-sm btn-danger">Finish</button></form><?php endif; ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Reset skor dan peserta game ini?')"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="game_id" value="<?= $g['id'] ?>"><button class="btn btn-sm btn-warning">Reset</button></form>
                <?php if($g['status']==='finished'): ?><form method="post" class="d-inline" onsubmit="return confirm('Hapus game selesai ini beserta rekapnya?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="game_id" value="<?= $g['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(!$games): ?><tr><td colspan="7">Belum ada live game.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
const liveModeSelect = document.getElementById('liveModeSelect');
const roomBox = document.getElementById('roomBox');
const questionSelectMode = document.getElementById('questionSelectMode');
const autoBox = document.getElementById('autoQuestionBox');
const manualBox = document.getElementById('manualQuestionBox');
function syncLiveMode(){
  const mode = liveModeSelect?.value || 'individu';
  roomBox?.classList.toggle('d-none', mode !== 'tim');
  document.querySelectorAll('#manualQuestionBox .question-check').forEach(el => {
    el.style.display = el.dataset.mode === mode ? '' : 'none';
  });
}
function syncPicker(){
  const manual = questionSelectMode?.value === 'manual';
  autoBox?.classList.toggle('d-none', manual);
  manualBox?.classList.toggle('d-none', !manual);
}
liveModeSelect?.addEventListener('change', syncLiveMode);
questionSelectMode?.addEventListener('change', syncPicker);
syncLiveMode(); syncPicker();
</script>
<?php render_footer(); ?>
