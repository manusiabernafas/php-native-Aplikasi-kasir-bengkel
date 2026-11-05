<?php
$hari = q("SELECT COUNT(*) n, COALESCE(SUM(total),0) t, COALESCE(SUM(total_jasa),0) j FROM work_order WHERE status = 'lunas' AND DATE(lunas_at) = CURDATE()")->fetch();
$bulan = q("SELECT COALESCE(SUM(total),0) t FROM work_order WHERE status = 'lunas' AND lunas_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$labaPart = q("SELECT COALESCE(SUM(i.qty * (i.harga - i.harga_beli)),0) FROM wo_item i JOIN work_order w ON w.id = i.wo_id
               WHERE i.jenis = 'part' AND w.status = 'lunas' AND w.lunas_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$aktif = q("SELECT status, COUNT(*) n FROM work_order WHERE status IN ('antri','dikerjakan','selesai') GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$menipis = q('SELECT * FROM sparepart WHERE stok <= stok_min ORDER BY stok - stok_min, nama')->fetchAll();

$grafik = [];
for ($i = 13; $i >= 0; $i--) $grafik[date('Y-m-d', strtotime("-$i day"))] = ['j' => 0, 'p' => 0];
foreach (q("SELECT DATE(lunas_at) d, SUM(total_jasa) j, SUM(total_part) p FROM work_order WHERE status = 'lunas' AND lunas_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d")->fetchAll() as $r) {
    if (isset($grafik[$r['d']])) $grafik[$r['d']] = ['j' => (int) $r['j'], 'p' => (int) $r['p']];
}
$max = max(1, ...array_values(array_map(fn ($g) => max($g['j'], $g['p']), $grafik)));
$mekanik = q("SELECT m.nama, COUNT(w.id) n, COALESCE(SUM(w.total_jasa),0) jasa FROM mekanik m LEFT JOIN work_order w ON w.mekanik_id = m.id AND w.status = 'lunas'
              AND w.lunas_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') WHERE m.aktif = 1 GROUP BY m.id ORDER BY n DESC")->fetchAll();

$title = 'Halo, ' . explode(' ', user()['nama'])[0] . '!';
$sub = tgl(date('Y-m-d')) . ' · ' . (($aktif['antri'] ?? 0) + ($aktif['dikerjakan'] ?? 0)) . ' motor sedang di bengkel';
$actions = '<a class="btn" href="' . url('antrean') . '">' . icon('clipboard', 16) . ' Lihat Antrean</a><a class="btn btn-primary" href="' . url('servis.baru') . '">' . icon('plus', 16) . ' Motor Masuk</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <div class="stat"><div class="ico ico-orange"><?= icon('wallet', 22) ?></div><div><small>Pendapatan hari ini</small><strong><?= rupiah($hari['t']) ?></strong><small><?= $hari['n'] ?> motor dilayani</small></div></div>
    <div class="stat"><div class="ico ico-blue"><?= icon('chart', 22) ?></div><div><small>Pendapatan bulan ini</small><strong><?= rupiah($bulan) ?></strong></div></div>
    <div class="stat"><div class="ico ico-green"><?= icon('box', 22) ?></div><div><small>Laba sparepart bulan ini</small><strong><?= rupiah($labaPart) ?></strong></div></div>
    <div class="stat"><div class="ico ico-red"><?= icon('alert', 22) ?></div><div><small>Stok menipis</small><strong><?= count($menipis) ?> item</strong><small>perlu restock</small></div></div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Pendapatan 14 hari</h2><div class="legend"><span><i style="background:var(--orange)"></i>Jasa</span><span><i style="background:var(--ink)"></i>Sparepart</span></div></div>
        <div class="card-body"><div class="bars">
            <?php foreach ($grafik as $d => $g): ?>
                <div class="bar-col" title="<?= tgl($d) ?>: jasa <?= rupiah($g['j']) ?>, part <?= rupiah($g['p']) ?>">
                    <div class="bar-pair"><div class="bar" style="height:<?= round($g['j'] / $max * 100) ?>%"></div><div class="bar part" style="height:<?= round($g['p'] / $max * 100) ?>%"></div></div>
                    <small><?= date('d', strtotime($d)) ?></small>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Mekanik bulan ini</h2></div>
        <div class="table-wrap"><table><tbody>
            <?php foreach ($mekanik as $m): ?><tr><td><strong><?= e($m['nama']) ?></strong><small>jasa <?= rupiah($m['jasa']) ?></small></td><td class="text-right"><span class="badge badge-orange"><?= $m['n'] ?> motor</span></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>
<div class="card mt">
    <div class="card-head"><h2>Stok sparepart menipis</h2><a href="<?= url('sparepart') ?>">Kelola stok</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Kode</th><th>Sparepart</th><th>Stok</th><th>Minimal</th><th class="text-right">Harga jual</th></tr></thead>
        <tbody><?php foreach ($menipis as $p): ?>
            <tr><td><span class="plat" style="background:var(--soft);color:var(--ink)"><?= e($p['kode']) ?></span></td><td><strong><?= e($p['nama']) ?></strong></td>
                <td><span class="badge <?= $p['stok'] == 0 ? 'badge-red' : 'badge-orange' ?>"><?= $p['stok'] ?> <?= e($p['satuan']) ?></span></td><td><?= $p['stok_min'] ?></td><td class="text-right"><?= rupiah($p['harga_jual']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$menipis): ?><tr><td colspan="5" class="empty">Semua stok aman.</td></tr><?php endif; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
