<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$pdo=db();
$rows=$pdo->query("SELECT u.name, gs.score, gs.total_correct, gs.total_wrong, gs.finished_at FROM game_sessions gs JOIN users u ON u.id=gs.user_id WHERE gs.status='finished' ORDER BY gs.score DESC, gs.finished_at DESC")->fetchAll();
$logoPath = url(SCHOOL_LOGO);
ob_start();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Laporan PABETAS</title><style>body{font-family:Arial,sans-serif;color:#1f2937}.kop{display:flex;align-items:center;gap:16px;border-bottom:4px solid #2563eb;padding-bottom:12px;margin-bottom:20px}.kop img{width:72px;height:72px}.title{font-size:22px;font-weight:800}.sub{color:#555}table{width:100%;border-collapse:collapse;margin-top:16px}th{background:#2563eb;color:#fff}td,th{border:1px solid #cbd5e1;padding:8px;text-align:left}.footer{margin-top:32px;font-size:12px;color:#64748b}.badge{background:#facc15;padding:4px 8px;border-radius:10px}@media print{button{display:none}}</style></head><body>
<button onclick="window.print()">Cetak / Save as PDF</button>
<div class="kop"><img src="<?= e($logoPath) ?>"><div><div class="title">Laporan Hasil Belajar PABETAS</div><div class="sub"><?= e(SCHOOL_NAME) ?> | Materi Konversi Satuan Panjang Kelas III SD</div><div class="sub">Tanggal cetak: <?= date('d-m-Y H:i') ?></div></div></div>
<p>Laporan ini memuat rekap nilai, jumlah jawaban benar, dan jumlah jawaban salah pada sesi kuis individu.</p>
<table><thead><tr><th>Peringkat</th><th>Nama Murid</th><th>Skor</th><th>Benar</th><th>Salah</th><th>Selesai</th></tr></thead><tbody><?php $rank=1; foreach($rows as $r): ?><tr><td><?= $rank++ ?></td><td><?= e($r['name']) ?></td><td><span class="badge"><?= e($r['score']) ?></span></td><td><?= e($r['total_correct']) ?></td><td><?= e($r['total_wrong']) ?></td><td><?= e($r['finished_at']) ?></td></tr><?php endforeach; ?></tbody></table>
<div class="footer">Dokumen dihasilkan otomatis oleh Sistem PABETAS. Jika Dompdf terpasang melalui Composer, halaman ini dapat di-render langsung sebagai PDF server-side.</div>
</body></html>
<?php
$html=ob_get_clean();
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new Dompdf\Dompdf(['isRemoteEnabled'=>true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('laporan_pabetas.pdf', ['Attachment'=>false]);
        exit;
    }
}
echo $html;
