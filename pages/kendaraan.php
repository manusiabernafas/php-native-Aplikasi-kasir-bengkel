<?php
$set = setting();
$filter = $_GET['filter'] ?? '';
$cari = trim($_GET['cari'] ?? '');
$params = [];
$where = '1=1';
if ($cari !== '') { $where = '(k.plat LIKE ? OR p.nama LIKE ? OR p.telepon LIKE ?)'; $params = ["%$cari%", "%$cari%", "%$cari%"]; }
$rows = q("SELECT k.*, p.nama, p.telepon, MAX(w.lunas_at) terakhir, COUNT(w.id) jml, COALESCE(SUM(w.total),0) belanja
           FROM kendaraan k JOIN pelanggan p ON p.id = k.pelanggan_id LEFT JOIN work_order w ON w.kendaraan_id = k.id AND w.status = 'lunas'
           WHERE $where GROUP BY k.id ORDER BY terakhir IS NULL, terakhir", $params)->fetchAll();
// Jatuh tempo servis berkala: sudah lewat interval hari sejak servis terakhir
foreach ($rows as &$r) {
    $r['due'] = $r['terakhir'] && strtotime($r['terakhir'] . " +{$set['interval_servis_hari']} days") <= time();
    $r['hari'] = $r['terakhir'] ? (int) floor((time() - strtotime($r['terakhir'])) / 86400) : null;
}
unset($r);
if ($filter === 'due') $rows = array_values(array_filter($rows, fn ($r) => $r['due']));
$jmlDue = count(array_filter($rows, fn ($r) => $r['due']));

$title = 'Pelanggan & Motor';
$sub = 'Pengingat servis berkala setiap ' . $set['interval_servis_hari'] . ' hari';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <div class="filters"><a class="btn btn-sm <?= $filter === '' ? 'btn-dark' : 'btn-ghost' ?>" href="<?= url('kendaraan') ?>">Semua motor</a>
            <a class="btn btn-sm <?= $filter === 'due' ? 'btn-primary' : 'btn-ghost' ?>" href="<?= url('kendaraan', ['filter' => 'due']) ?>">Waktunya servis<?= $filter !== 'due' && $jmlDue ? " ($jmlDue)" : '' ?></a></div>
        <form class="filters" method="get"><input type="hidden" name="page" value="kendaraan"><input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Plat, nama, no. HP"></form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Motor</th><th>Pemilik</th><th>KM terakhir</th><th>Servis terakhir</th><th>Total servis</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?>
            <tr><td><span class="plat"><?= e($r['plat']) ?></span><small><?= e($r['merk'] . ' ' . $r['tipe']) ?> <?= $r['tahun'] ?></small></td>
                <td><strong><?= e($r['nama']) ?></strong><small><?= e($r['telepon']) ?></small></td>
                <td><?= number_format($r['km_terakhir'], 0, ',', '.') ?></td>
                <td><?= $r['terakhir'] ? tgl($r['terakhir']) . '<small>' . $r['hari'] . ' hari lalu</small>' : '-' ?> <?= $r['due'] ? '<span class="badge badge-orange">Waktunya servis</span>' : '' ?></td>
                <td><strong><?= $r['jml'] ?>x</strong><small><?= rupiah($r['belanja']) ?></small></td>
                <td class="actions">
                    <?php if ($r['due']): ?><a class="btn btn-sm btn-primary" target="_blank" href="<?= wa_link($r['telepon'], "Halo {$r['nama']}, motor {$r['merk']} {$r['tipe']} ({$r['plat']}) terakhir servis {$r['hari']} hari lalu di {$set['nama_bengkel']}. Yuk servis berkala biar tetap prima. Kami tunggu kedatangannya!") ?>"><?= icon('message', 14) ?> Ingatkan</a><?php endif; ?>
                    <a class="btn btn-sm btn-ghost" href="<?= url('servis.baru', ['plat' => $r['plat']]) ?>">Servis</a>
                </td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada data.</td></tr><?php endif; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
