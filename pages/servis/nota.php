<?php
$id = (int) ($_GET['id'] ?? 0);
$w = q('SELECT w.*, k.plat, k.merk, k.tipe, p.nama pelanggan, m.nama mekanik, u.nama kasir FROM work_order w JOIN kendaraan k ON k.id = w.kendaraan_id
        JOIN pelanggan p ON p.id = k.pelanggan_id LEFT JOIN mekanik m ON m.id = w.mekanik_id LEFT JOIN users u ON u.id = w.user_id WHERE w.id = ?', [$id])->fetch();
if (!$w) redirect('antrean');
$items = q("SELECT * FROM wo_item WHERE wo_id = ? ORDER BY jenis = 'part', id", [$id])->fetchAll();
$set = setting();
$title = 'Nota ' . $w['kode'];
$actions = '<a class="btn" href="' . url('servis.detail', ['id' => $id]) . '">Kembali</a><button class="btn btn-primary" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card nota">
    <h2><?= e($set['nama_bengkel']) ?></h2>
    <div class="c"><?= e($set['alamat']) ?><br><?= e($set['telepon']) ?></div>
    <hr>
    <div class="row"><span><?= e($w['kode']) ?></span><span><?= date('d/m/Y H.i', strtotime($w['lunas_at'] ?? $w['masuk_at'])) ?></span></div>
    <div class="row"><span><?= e($w['plat']) ?></span><span><?= e($w['merk'] . ' ' . $w['tipe']) ?></span></div>
    <div class="row"><span><?= e($w['pelanggan']) ?></span><span>KM <?= number_format($w['km'], 0, ',', '.') ?></span></div>
    <hr>
    <?php foreach ($items as $it): ?>
        <div><?= e($it['nama']) ?></div>
        <div class="row"><span>&nbsp;&nbsp;<?= (int) $it['qty'] ?> x <?= number_format($it['harga'], 0, ',', '.') ?></span><span><?= number_format($it['qty'] * $it['harga'], 0, ',', '.') ?></span></div>
    <?php endforeach; ?>
    <hr>
    <div class="row"><span>Jasa</span><span><?= number_format($w['total_jasa'], 0, ',', '.') ?></span></div>
    <div class="row"><span>Sparepart</span><span><?= number_format($w['total_part'], 0, ',', '.') ?></span></div>
    <?php if ($w['diskon']): ?><div class="row"><span>Diskon</span><span>-<?= number_format($w['diskon'], 0, ',', '.') ?></span></div><?php endif; ?>
    <div class="row" style="font-weight:800;font-size:15px"><span>TOTAL</span><span><?= number_format($w['total'], 0, ',', '.') ?></span></div>
    <?php if ($w['status'] === 'lunas'): ?>
        <div class="row"><span>Bayar (<?= e($w['metode']) ?>)</span><span><?= number_format($w['bayar'], 0, ',', '.') ?></span></div>
        <div class="row"><span>Kembali</span><span><?= number_format($w['bayar'] - $w['total'], 0, ',', '.') ?></span></div>
    <?php else: ?><div class="c" style="margin-top:6px">** BELUM DIBAYAR **</div><?php endif; ?>
    <hr>
    <div class="c">Mekanik: <?= e($w['mekanik'] ?? '-') ?> · Kasir: <?= e($w['kasir'] ?? '-') ?><br>Servis berikutnya: KM <?= number_format($w['km'] + $set['interval_servis_km'], 0, ',', '.') ?> atau <?= tgl(date('Y-m-d', strtotime(($w['lunas_at'] ?? 'now') . ' +' . $set['interval_servis_hari'] . ' days'))) ?><br>Terima kasih atas kepercayaan Anda</div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
