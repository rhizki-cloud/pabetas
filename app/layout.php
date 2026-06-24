<?php
function nav_active(array $items, string $active): string {
    return in_array($active, $items, true) ? 'active' : '';
}

function sound_context_from_active(string $active, bool $isGuru): string {
    if ($isGuru) {
        if (in_array($active, ['materials','learning_flow'], true)) return 'materi';
        if (in_array($active, ['questions','quizzes','essay_sets','grading'], true)) return 'teacher';
        if (in_array($active, ['rooms'], true)) return 'tim';
        if (in_array($active, ['live_games','weekly'], true)) return 'live';
        if (in_array($active, ['reflections'], true)) return 'refleksi';
        return 'teacher';
    }
    if (in_array($active, ['learn','academic'], true)) return 'materi';
    if ($active === 'ladder') return 'tangga';
    if (in_array($active, ['quiz','essay_quiz','essay_feedback'], true)) return 'individu';
    if (in_array($active, ['team','team_game'], true)) return 'tim';
    if (in_array($active, ['live','ranking'], true)) return 'live';
    if ($active === 'reflection') return 'refleksi';
    return 'home';
}

function render_header($title, $active = '') {
    $user = current_user();
    $isGuru = $user && $user['role'] === 'guru';
    $soundContext = sound_context_from_active($active, $isGuru);
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> - PABETAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=10.1') ?>">
</head>
<body data-sound-context="<?= e($soundContext) ?>">
<nav class="navbar navbar-expand-lg navbar-pabetas sticky-top">
    <div class="container-fluid px-3 px-md-4">
        <a class="navbar-brand fw-black d-flex align-items-center gap-2" href="<?= url($isGuru ? 'teacher/dashboard.php' : 'student/dashboard.php') ?>">
            <img src="<?= url(SCHOOL_LOGO) ?>" class="logo-sm" alt="Logo"> <span>PABETAS</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 nav-compact">
                <?php if ($isGuru): ?>
                    <li class="nav-item"><a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="<?= url('teacher/dashboard.php') ?>">🏠 Dashboard</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['materials','questions','quizzes','essay_sets','learning_flow','reflections'], $active) ?>" href="#" data-bs-toggle="dropdown">📚 Pembelajaran</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='materials'?'active':'' ?>" href="<?= url('teacher/materials.php') ?>">Papan Belajar</a></li>
                            <li><a class="dropdown-item <?= $active==='questions'?'active':'' ?>" href="<?= url('teacher/questions.php') ?>">Bank Soal</a></li>
                            <li><a class="dropdown-item <?= $active==='quizzes'?'active':'' ?>" href="<?= url('teacher/quizzes.php') ?>">Kuis, Pretest, Posttest</a></li>
                            <li><a class="dropdown-item <?= $active==='essay_sets'?'active':'' ?>" href="<?= url('teacher/essay_sets.php') ?>">Paket Esai</a></li>
                            <li><a class="dropdown-item <?= $active==='learning_flow'?'active':'' ?>" href="<?= url('teacher/learning_flow.php') ?>">Alur Belajar</a></li>
                            <li><a class="dropdown-item <?= $active==='reflections'?'active':'' ?>" href="<?= url('teacher/reflections.php') ?>">Refleksi Murid</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['rooms','live_games','weekly'], $active) ?>" href="#" data-bs-toggle="dropdown">🎮 Game</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='rooms'?'active':'' ?>" href="<?= url('teacher/rooms.php') ?>">Mode Tim</a></li>
                            <li><a class="dropdown-item <?= $active==='live_games'?'active':'' ?>" href="<?= url('teacher/live_games.php') ?>">Live Game</a></li>
                            <li><a class="dropdown-item <?= $active==='weekly'?'active':'' ?>" href="<?= url('teacher/weekly_leaderboard.php') ?>">Leaderboard Mingguan</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['grading','academic','reports'], $active) ?>" href="#" data-bs-toggle="dropdown">📊 Nilai & Laporan</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='grading'?'active':'' ?>" href="<?= url('teacher/grading.php') ?>">Nilai Esai</a></li>
                            <li><a class="dropdown-item <?= $active==='academic'?'active':'' ?>" href="<?= url('teacher/academic_reports.php') ?>">Laporan Akademik</a></li>
                            <li><a class="dropdown-item <?= $active==='reports'?'active':'' ?>" href="<?= url('teacher/reports.php') ?>">Laporan Umum</a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><a class="nav-link <?= $active==='students'?'active':'' ?>" href="<?= url('teacher/students.php') ?>">👥 Murid</a></li>
                <?php elseif ($user): ?>
                    <li class="nav-item"><a class="nav-link <?= $active==='student'?'active':'' ?>" href="<?= url('student/dashboard.php') ?>">🏠 Beranda</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['academic','learn','ladder','reflection'], $active) ?>" href="#" data-bs-toggle="dropdown">📚 Belajar</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='academic'?'active':'' ?>" href="<?= url('student/academic.php') ?>">Alur Belajar</a></li>
                            <li><a class="dropdown-item <?= $active==='learn'?'active':'' ?>" href="<?= url('student/learn.php') ?>">Papan Belajar</a></li>
                            <li><a class="dropdown-item <?= $active==='ladder'?'active':'' ?>" href="<?= url('student/ladder.php') ?>">Tangga Satuan</a></li>
                            <li><a class="dropdown-item <?= $active==='reflection'?'active':'' ?>" href="<?= url('student/reflection.php') ?>">Refleksi</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['quiz','essay_quiz','essay_feedback'], $active) ?>" href="#" data-bs-toggle="dropdown">📝 Latihan</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='quiz'?'active':'' ?>" href="<?= url('student/quiz.php') ?>">Kuis Individu</a></li>
                            <li><a class="dropdown-item <?= $active==='essay_quiz'?'active':'' ?>" href="<?= url('student/essay_quiz.php') ?>">Soal Esai</a></li>
                            <li><a class="dropdown-item <?= $active==='essay_feedback'?'active':'' ?>" href="<?= url('student/essay_feedback.php') ?>">Nilai Esai</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= nav_active(['live','team','team_game','ranking'], $active) ?>" href="#" data-bs-toggle="dropdown">🎮 Game</a>
                        <ul class="dropdown-menu dropdown-menu-pabetas">
                            <li><a class="dropdown-item <?= $active==='live'?'active':'' ?>" href="<?= url('student/live_join.php') ?>">Live Game</a></li>
                            <li><a class="dropdown-item <?= $active==='team'?'active':'' ?>" href="<?= url('student/team_join.php') ?>">Mode Tim 1 Device</a></li>
                            <li><a class="dropdown-item <?= $active==='ranking'?'active':'' ?>" href="<?= url('student/ranking.php') ?>">Ranking</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link <?= $active==='avatar'?'active':'' ?>" href="<?= url('student/avatar.php') ?>">🎨 Avatar</a></li>
                <?php endif; ?>
            </ul>
            <?php if ($user): ?>
            <div class="nav-user-box">
                <span>Halo, <b><?= e($user['name']) ?></b></span>
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill" id="soundToggle" title="Aktifkan suara">🔊 Suara</button>
                <a class="btn btn-light btn-sm rounded-pill" href="<?= url('logout.php') ?>">Logout</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container-fluid px-3 px-md-4 py-4">
    <?php
}

function render_footer() {
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/pabetas.js') ?>"></script>
<script src="<?= url('assets/js/sounds.js?v=9') ?>"></script>
</body>
</html>
    <?php
}
