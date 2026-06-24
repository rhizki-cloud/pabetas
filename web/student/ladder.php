<?php
require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/layout.php';
require_role('murid');
render_header('Tangga Satuan','ladder');
?>
<div class="page-title compact-title"><h1>Animasi Tangga Satuan Interaktif 🪜</h1><p>Karakter bergerak perlahan. Saat turun, angka mendapat tambahan nol. Saat naik, nol berkurang atau nilai dibagi 10.</p></div>
<div class="row g-4">
  <div class="col-xl-8"><div class="panel-card canvas-panel ladder-full-panel"><canvas id="ladderCanvas" width="1100" height="620"></canvas><div class="zero-animation-track" id="zeroTrack"><span>Langkah perubahan angka akan muncul di sini</span></div><div class="ladder-info-grid mt-3"><div><b>Turun</b><span>Tambah 0 atau ×10 tiap tingkat.</span></div><div><b>Naik</b><span>Kurangi 0 atau ÷10 tiap tingkat.</span></div><div><b>Cek</b><span>Arah gerak menentukan operasi.</span></div></div></div></div>
  <div class="col-xl-4"><div class="panel-card"><h3>Kontrol Tangga</h3><label class="form-label">Angka</label><input type="number" id="convertValue" class="form-control mb-2" value="3"><label class="form-label">Dari</label><select id="fromUnit" class="form-select mb-2"><option>km</option><option>hm</option><option>dam</option><option selected>m</option><option>dm</option><option>cm</option><option>mm</option></select><label class="form-label">Ke</label><select id="toUnit" class="form-select mb-3"><option>km</option><option>hm</option><option>dam</option><option>m</option><option>dm</option><option selected>cm</option><option>mm</option></select><button class="btn btn-primary rounded-pill w-100" id="animateConvert">Gerakkan Perlahan</button><button class="btn btn-warning rounded-pill w-100 mt-2" id="demoConvert">Demo 3 km ke m</button><button class="btn btn-success rounded-pill w-100 mt-2" id="reverseDemo">Demo 3000 mm ke m</button><div class="result-box mt-3" id="convertResult">Hasil akan muncul di sini.</div></div></div>
</div>
<script src="<?= url('assets/js/ladder.js') ?>?v=9.1"></script>
<?php render_footer(); ?>
