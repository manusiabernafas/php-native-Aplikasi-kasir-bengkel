<?php
$dari = $_GET['dari'] ?? date('Y-m-d', strtotime('-6 days'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$cari = trim($_GET['cari'] ?? '');
$params = [$dari, $sampai . ' 23:59:59'];
$extra = '';
if ($cari !== '') { $extra = ' AND (w.kode LIKE ? OR k.plat LIKE ? OR p.nama LIKE ?)'; array_push($params, "%$cari%", "%$cari%", "%$cari%"); }
$rows = q("SELECT w.*, k.plat, k.merk, k.tipe, p.nama pelanggan, m.nama mekanik FROM work_order w JOIN kendaraan k ON k.id = w.kendaraan_id
           JOIN pelanggan p ON p.id = k.pelanggan_id LEFT JOIN mekanik m ON m.id = w.mekanik_id
           WHERE w.status IN ('lunas','batal') AND COALESCE(w.lunas_at, w.masuk_at) BETWEEN ? AND ? $extra ORDER BY COALESCE(w.lunas_at, w.masuk_at) DESC LIMIT 300", $params)->fetchAll();
$total = array_sum(array_map(fn ($r) => $r['status'] === 'lunas' ? $r['total'] : 0, $rows));
$title = 'Riwayat Transaksi';
$sub = count($rows) . ' transaksi · ' . rupiah($total);
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-head"><form class="filters" method="get"><input type="hidden" name="page" value="transaksi">
        <input type="date" name="dari" value="<?= e($dari) ?>"><input type="date" name="sampai" value="<?= e($sampai) ?>">
        <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Kode, plat, pelanggan"><button class="btn btn-dark btn-sm"><?= icon('search', 14) ?></button></form></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Work Order</th><th>Motor</th><th>Mekanik</th><th>Jasa</th><th>Part</th><th class="text-right">Total</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?>
            <tr><td><strong><?= e($r['kode']) ?></strong><small><?= tgl($r['lunas_at'] ?? $r['masuk_at']) ?> · <?= e($r['metode'] ?? '') ?></small></td>
                <td><span class="plat"><?= e($r['plat']) ?></span><small><?= e($r['merk'] . ' ' . $r['tipe']) ?> · <?= e($r['pelanggan']) ?></small></td>
                <td><?= e($r['mekanik'] ?? '-') ?></td><td><?= rupiah($r['total_jasa']) ?></td><td><?= rupiah($r['total_part']) ?></td>
                <td class="text-right"><?= $r['status'] === 'batal' ? status_badge('batal') : '<strong>' . rupiah($r['total']) . '</strong>' ?></td>
                <td class="actions"><a class="icon-btn" href="<?= url('servis.nota', ['id' => $r['id']]) ?>" title="Nota"><?= icon('printer', 14) ?></a></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty">Tidak ada transaksi.</td></tr><?php endif; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
