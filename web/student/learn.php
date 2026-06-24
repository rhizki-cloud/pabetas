<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
$pdo = db();
$materials=$pdo->query('SELECT * FROM learning_materials WHERE status=1 ORDER BY step_order ASC')->fetchAll();
render_header('Papan Belajar','learn');
?>
<div class="page-title"><h1>Papan Belajar Satuan Panjang</h1><p>Belajar konversi satuan panjang dengan tabel ke bawah, contoh soal, dan latihan tambah atau kurangi nol secara bertahap.</p></div>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="table-lesson-card mb-4">
            <div class="lesson-intro-copy">
                <span class="lesson-step">Papan Tabel Interaktif</span>
                <h2>Mengubah Satuan Panjang dengan Tabel</h2>
                <p>Ikuti langkahnya seperti di papan tulis: tentukan satuan asal, lihat arah perpindahan, lalu tambahkan nol saat turun atau kurangi nol saat naik.</p>
            </div>

            <div class="conversion-board">
                <div class="conversion-controls">
                    <div>
                        <label class="form-label">Angka</label>
                        <input type="number" id="tableValue" class="form-control" value="3">
                    </div>
                    <div>
                        <label class="form-label">Dari satuan</label>
                        <select id="tableFrom" class="form-select"><option>km</option><option>hm</option><option>dam</option><option selected>m</option><option>dm</option><option>cm</option><option>mm</option></select>
                    </div>
                    <div>
                        <label class="form-label">Ke satuan</label>
                        <select id="tableTo" class="form-select"><option>km</option><option>hm</option><option>dam</option><option>m</option><option>dm</option><option selected>cm</option><option>mm</option></select>
                    </div>
                    <button type="button" class="btn btn-primary rounded-pill" id="startTableLesson">Tampilkan Langkah</button>
                </div>

                <div class="vertical-table-wrap">
                    <table class="vertical-unit-table" aria-label="Tabel konversi satuan panjang ke bawah">
                        <thead><tr><th>Satuan</th><th>Nama</th><th>Angka</th><th>Langkah</th></tr></thead>
                        <tbody id="verticalTableBody"></tbody>
                    </table>
                </div>

                <div class="zero-stepper-card mt-3">
                    <div>
                        <small>Nilai saat ini</small>
                        <strong id="currentValueLabel">3</strong>
                    </div>
                    <div>
                        <small>Arah</small>
                        <strong id="directionLabel">Turun / naik</strong>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-warning rounded-pill" id="applyZeroStep">Tambah/Kurangi 0</button>
                        <button type="button" class="btn btn-success rounded-pill" id="autoZeroStep">Jalankan Otomatis</button>
                    </div>
                </div>

                <div class="worked-example-box mt-3" id="tableExplanation">
                    Pilih contoh, lalu tekan <b>Tampilkan Langkah</b>. Sistem akan menunjukkan angka bergerak dari satuan asal ke satuan tujuan.
                </div>
            </div>
        </div>

        <div class="unit-visual-card mb-4">
            <h2>🌈 Aturan Utama</h2>
            <div class="unit-ladder-strip clean-unit-strip">
                <?php foreach(['km','hm','dam','m','dm','cm','mm'] as $i=>$u): ?><div class="unit-step step-<?= $i ?>"><b><?= $u ?></b><small><?= $i<3?'besar':($i===3?'acuan':'kecil') ?></small></div><?php endforeach; ?>
            </div>
            <div class="conversion-rule-grid">
                <div><b>Turun tangga</b><span>nilai dikali 10 setiap langkah</span><strong>×10</strong></div>
                <div><b>Naik tangga</b><span>nilai dibagi 10 setiap langkah</span><strong>÷10</strong></div>
            </div>
        </div>

        <?php foreach($materials as $m): ?>
        <div class="lesson-card">
            <span class="lesson-step">Langkah <?= e($m['step_order']) ?></span>
            <h2><?= e($m['title']) ?></h2>
            <p><?= nl2br(e($m['content'])) ?></p>
            <div class="example-conversion-box">
                <div><b>Contoh turun</b><br>3 km = 3.000 m karena turun 3 tingkat: km → hm → dam → m.</div>
                <div><b>Contoh naik</b><br>3.000 mm = 3 m karena naik 3 tingkat: mm → cm → dm → m.</div>
            </div>
            <?php if($m['media_url'] && $m['media_type']==='video'): ?><div class="ratio ratio-16x9"><iframe src="<?= e($m['media_url']) ?>" allowfullscreen></iframe></div><?php endif; ?>
            <?php if($m['media_url'] && $m['media_type']==='image'): ?><img class="img-fluid rounded-4" src="<?= e($m['media_url']) ?>" alt="Media materi"><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="col-xl-4">
        <div class="panel-card sticky-lg-top sticky-offset">
            <h3>📌 Ringkasan</h3>
            <ul class="kid-list"><li>Urutan satuan: km → hm → dam → m → dm → cm → mm</li><li>Turun satu tingkat: ×10 atau tambah satu nol</li><li>Naik satu tingkat: ÷10 atau kurangi satu nol</li><li>Jumlah tingkat menentukan 10, 100, 1.000, dan seterusnya.</li></ul>
            <div class="quick-example-buttons">
                <button type="button" class="btn btn-outline-primary rounded-pill w-100" data-demo="3|km|m">Contoh 3 km ke m</button>
                <button type="button" class="btn btn-outline-success rounded-pill w-100" data-demo="3000|mm|m">Contoh 3000 mm ke m</button>
                <button type="button" class="btn btn-outline-warning rounded-pill w-100" data-demo="45|m|cm">Contoh 45 m ke cm</button>
            </div>
            <button class="btn btn-success rounded-pill mt-3 w-100" id="markLearnCompleted">Saya Sudah Paham Materi</button>
            <a href="<?= url('student/academic.php') ?>" class="btn btn-warning rounded-pill mt-3 w-100">Kembali ke Alur Belajar</a>
        </div>
    </div>
</div>
<script src="<?= url('assets/js/table_lesson.js') ?>?v=8.5"></script>
<script>
document.getElementById('markLearnCompleted')?.addEventListener('click', async()=>{
  await fetch('../api/academic_mark_progress.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]')?.content||''},body:JSON.stringify({type:'learn'})}).catch(()=>{});
  alert('Progres belajar tersimpan. Mantap!');
});
</script>
<?php render_footer(); ?>
