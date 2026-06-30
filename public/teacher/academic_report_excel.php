<?php
require_once __DIR__ . '/../../app/init.php';
require_role('guru');
$rows = academic_class_report();
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_akademik_pabetas.xls"');
echo "Nama\tUsername\tPretest\tPosttest\tPeningkatan\tRemedial\tStatus\n";
foreach ($rows as $r) {
    $imp = ($r['pretest']!==null && $r['posttest']!==null) ? ((int)$r['posttest']-(int)$r['pretest']) : '';
    $rem = ((int)($r['remedial_open'] ?? 0)===1) ? 'Perlu remedial' : 'Tidak';
    $status = ($r['posttest']!==null && (int)$r['posttest']>=REMEDIAL_MIN_SCORE) ? 'Tuntas' : 'Belum lengkap/tidak tuntas';
    echo implode("\t", [$r['name'],$r['username'],$r['pretest'],$r['posttest'],$imp,$rem,$status]) . "\n";
}
