<?php
$rows = q("SELECT w.*, k.plat, k.merk, k.tipe, p.nama pelanggan, m.nama mekanik FROM work_order w JOIN kendaraan k ON k.id = w.kendaraan_id
           JOIN pelanggan p ON p.id = k.pelanggan_id LEFT JOIN mekanik m ON m.id = w.mekanik_id
           WHERE w.status IN ('antri','dikerjakan','selesai') OR (w.status = 'lunas' AND DATE(w.lunas_at) = CURDATE())
           ORDER BY w.masuk_at")->fetchAll();
$lanes = ['antri' => ['Antri', '#94a3b8'], 'dikerjakan' => ['Dikerjakan', '#f97316'], 'selesai' => ['Siap Diambil', '#3b82f6'], 'lunas' => ['Lunas Hari Ini', '#16a34a']];
$by = array_fill_keys(array_keys($lanes), []);
foreach ($rows as $r) $by[$r['status']][] = $r;

$title = 'Antrean Servis';
$sub = tgl(date('Y-m-d')) . ' · klik kartu untuk membuka work order';
$actions = '<a class="btn btn-primary" href="' . url('servis.baru') . '">' . icon('plus', 16) . ' Motor Masuk</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="board">
    <?php foreach ($lanes as $st => [$label, $color]): ?>
        <div class="lane">
            <div class="lane-head"><div><i style="background:<?= $color ?>"></i><?= $label ?></div><span><?= count($by[$st]) ?></span></div>
            <?php foreach ($by[$st] as $w): ?>
                <a class="job" href="<?= url('servis.detail', ['id' => $w['id']]) ?>">
                    <div class="top"><span class="plat"><?= e($w['plat']) ?></span><span class="kode"><?= e(substr($w['kode'], -4)) ?></span></div>
                    <strong><?= e($w['merk'] . ' ' . $w['tipe']) ?></strong>
                    <small><?= e($w['pelanggan']) ?> · <?= e($w['keluhan']) ?></small>
                    <div class="foot"><span class="muted"><?= icon('wrench', 13) ?> <?= e($w['mekanik'] ?? 'Belum ada mekanik') ?></span><b><?= $w['total'] ? rupiah($w['total']) : date('H.i', strtotime($w['masuk_at'])) ?></b></div>
                </a>
            <?php endforeach; ?>
            <?php if (!$by[$st]): ?><p class="muted" style="text-align:center;font-size:13px">Kosong</p><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
