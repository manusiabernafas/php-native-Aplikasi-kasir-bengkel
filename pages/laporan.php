<?php
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$r = [$dari, $sampai . ' 23:59:59'];
$ring = q("SELECT COUNT(*) n, COALESCE(SUM(total_jasa),0) jasa, COALESCE(SUM(total_part),0) part, COALESCE(SUM(diskon),0) diskon, COALESCE(SUM(total),0) total
           FROM work_order WHERE status = 'lunas' AND lunas_at BETWEEN ? AND ?", $r)->fetch();
$modal = (int) q("SELECT COALESCE(SUM(i.qty * i.harga_beli),0) FROM wo_item i JOIN work_order w ON w.id = i.wo_id WHERE i.jenis = 'part' AND w.status = 'lunas' AND w.lunas_at BETWEEN ? AND ?", $r)->fetchColumn();
$laba = $ring['jasa'] + $ring['part'] - $modal - $ring['diskon'];
$top = q("SELECT i.nama, SUM(i.qty) qty, SUM(i.qty * i.harga) omzet, SUM(i.qty * (i.harga - i.harga_beli)) laba FROM wo_item i JOIN work_order w ON w.id = i.wo_id
          WHERE i.jenis = 'part' AND w.status = 'lunas' AND w.lunas_at BETWEEN ? AND ? GROUP BY i.ref_id, i.nama ORDER BY qty DESC LIMIT 8", $r)->fetchAll();
$jasa = q("SELECT i.nama, COUNT(*) n, SUM(i.harga) omzet FROM wo_item i JOIN work_order w ON w.id = i.wo_id WHERE i.jenis = 'jasa' AND w.status = 'lunas' AND w.lunas_at BETWEEN ? AND ? GROUP BY i.ref_id, i.nama ORDER BY n DESC LIMIT 8", $r)->fetchAll();
$mek = q("SELECT m.nama, COUNT(w.id) n, COALESCE(SUM(w.total_jasa),0) jasa FROM mekanik m LEFT JOIN work_order w ON w.mekanik_id = m.id AND w.status = 'lunas' AND w.lunas_at BETWEEN ? AND ? GROUP BY m.id ORDER BY n DESC", $r)->fetchAll();
$metode = q("SELECT metode, COUNT(*) n, SUM(total) t FROM work_order WHERE status = 'lunas' AND lunas_at BETWEEN ? AND ? GROUP BY metode", $r)->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"laporan-bengkel-$dari-sd-$sampai.csv\"");
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Kode', 'Tanggal Lunas', 'Plat', 'Mekanik', 'Jasa', 'Sparepart', 'Diskon', 'Total', 'Metode'], ';', '"', '');
    foreach (q("SELECT w.*, k.plat, m.nama mek FROM work_order w JOIN kendaraan k ON k.id = w.kendaraan_id LEFT JOIN mekanik m ON m.id = w.mekanik_id WHERE w.status = 'lunas' AND w.lunas_at BETWEEN ? AND ? ORDER BY w.lunas_at", $r) as $w)
        fputcsv($o, [$w['kode'], $w['lunas_at'], $w['plat'], $w['mek'], $w['total_jasa'], $w['total_part'], $w['diskon'], $w['total'], $w['metode']], ';', '"', '');
    exit;
}
$title = 'Laporan';
$sub = tgl($dari) . ' – ' . tgl($sampai);
$actions = '<a class="btn" href="' . url('laporan', ['dari' => $dari, 'sampai' => $sampai, 'export' => 'csv']) . '">Ekspor CSV</a><button class="btn btn-dark" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../includes/header.php';
?>
<form class="card no-print" method="get" style="margin-bottom:20px"><div class="card-body filters"><input type="hidden" name="page" value="laporan">
    <input type="date" name="dari" value="<?= e($dari) ?>"><input type="date" name="sampai" value="<?= e($sampai) ?>"><button class="btn btn-primary">Tampilkan</button></div></form>
<div class="stats">
    <div class="stat"><div class="ico ico-orange"><?= icon('wallet', 22) ?></div><div><small>Omzet</small><strong><?= rupiah($ring['total']) ?></strong><small><?= $ring['n'] ?> transaksi</small></div></div>
    <div class="stat"><div class="ico ico-blue"><?= icon('wrench', 22) ?></div><div><small>Pendapatan jasa</small><strong><?= rupiah($ring['jasa']) ?></strong></div></div>
    <div class="stat"><div class="ico ico-red"><?= icon('box', 22) ?></div><div><small>Penjualan part</small><strong><?= rupiah($ring['part']) ?></strong><small>modal <?= rupiah($modal) ?></small></div></div>
    <div class="stat"><div class="ico ico-green"><?= icon('chart', 22) ?></div><div><small>Laba kotor</small><strong><?= rupiah($laba) ?></strong><small>jasa + margin part - diskon</small></div></div>
</div>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Sparepart terlaris</h2></div><div class="table-wrap"><table>
        <thead><tr><th>Sparepart</th><th>Qty</th><th>Omzet</th><th class="text-right">Laba</th></tr></thead>
        <tbody><?php foreach ($top as $t): ?><tr><td><strong><?= e($t['nama']) ?></strong></td><td><?= $t['qty'] ?></td><td><?= rupiah($t['omzet']) ?></td><td class="text-right"><strong><?= rupiah($t['laba']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="card"><div class="card-head"><h2>Kinerja mekanik</h2></div><div class="table-wrap"><table><tbody>
        <?php foreach ($mek as $m): ?><tr><td><strong><?= e($m['nama']) ?></strong><small>jasa <?= rupiah($m['jasa']) ?></small></td><td class="text-right"><span class="badge badge-orange"><?= $m['n'] ?> motor</span></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
<div class="grid-2 mt">
    <div class="card"><div class="card-head"><h2>Jasa paling sering</h2></div><div class="table-wrap"><table><tbody>
        <?php foreach ($jasa as $j): ?><tr><td><strong><?= e($j['nama']) ?></strong></td><td><?= $j['n'] ?>x</td><td class="text-right"><?= rupiah($j['omzet']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <div class="card"><div class="card-head"><h2>Metode pembayaran</h2></div><div class="table-wrap"><table><tbody>
        <?php foreach ($metode as $m): ?><tr><td><strong><?= e($m['metode']) ?></strong><small><?= $m['n'] ?> transaksi</small></td><td class="text-right"><?= rupiah($m['t']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
