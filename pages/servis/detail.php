<?php
$id = (int) ($_GET['id'] ?? 0);
$w = q('SELECT w.*, k.plat, k.merk, k.tipe, k.km_terakhir, p.nama pelanggan, p.telepon, m.nama mekanik FROM work_order w
        JOIN kendaraan k ON k.id = w.kendaraan_id JOIN pelanggan p ON p.id = k.pelanggan_id LEFT JOIN mekanik m ON m.id = w.mekanik_id WHERE w.id = ?', [$id])->fetch();
if (!$w) redirect('antrean');
$items = q("SELECT * FROM wo_item WHERE wo_id = ? ORDER BY jenis = 'part', id", [$id])->fetchAll();
$jasa = q('SELECT * FROM jasa ORDER BY nama')->fetchAll();
$part = q('SELECT * FROM sparepart WHERE stok > 0 ORDER BY nama')->fetchAll();
$mekanik = q('SELECT * FROM mekanik WHERE aktif = 1 ORDER BY nama')->fetchAll();
$bisaUbah = in_array($w['status'], ['antri', 'dikerjakan', 'selesai'], true);
$urutan = ['antri', 'dikerjakan', 'selesai', 'lunas'];
$posisi = array_search($w['status'], $urutan, true);

$title = 'Work Order ' . $w['kode'];
$sub = '<span class="plat">' . e($w['plat']) . '</span> ' . e($w['merk'] . ' ' . $w['tipe']) . ' · ' . e($w['pelanggan']) . ' · KM ' . number_format($w['km'], 0, ',', '.');
$actions = ($w['status'] === 'selesai' ? '<a class="btn" target="_blank" href="' . wa_link($w['telepon'], "Halo {$w['pelanggan']}, motor {$w['merk']} {$w['tipe']} ({$w['plat']}) sudah selesai diservis dan siap diambil. Total biaya " . rupiah($w['total']) . '. Terima kasih - ' . setting()['nama_bengkel']) . '">' . icon('message', 16) . ' Kabari Pelanggan</a>' : '')
    . '<a class="btn" href="' . url('servis.nota', ['id' => $id]) . '" target="_blank">' . icon('printer', 16) . ' Nota</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card" style="margin-bottom:20px"><div class="card-body" style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
    <div class="steps">
        <?php if ($w['status'] === 'batal'): ?><span class="on" style="background:var(--red)">Dibatalkan</span>
        <?php else: foreach ($urutan as $i => $s): ?><span class="<?= $i < $posisi ? 'done' : ($i === $posisi ? 'on' : '') ?>"><?= STATUS_WO[$s][0] ?></span><?php endforeach; endif; ?>
    </div>
    <div class="actions">
        <?php if ($w['status'] === 'antri'): ?>
            <form method="post" action="<?= url('servis.aksi') ?>" class="filters"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="mulai">
                <select name="mekanik_id" required><option value="">Pilih mekanik</option><?php foreach ($mekanik as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nama']) ?></option><?php endforeach; ?></select>
                <button class="btn btn-primary"><?= icon('wrench', 16) ?> Mulai Kerjakan</button></form>
        <?php elseif ($w['status'] === 'dikerjakan'): ?>
            <form method="post" action="<?= url('servis.aksi') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="selesai">
                <button class="btn btn-primary" <?= $items ? '' : 'disabled title="Tambahkan item dulu"' ?>><?= icon('check', 16) ?> Servis Selesai</button></form>
        <?php endif; ?>
        <?php if ($bisaUbah): ?>
            <form method="post" action="<?= url('servis.aksi') ?>" data-confirm="Batalkan work order ini? Stok sparepart akan dikembalikan."><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="batal">
                <button class="btn btn-ghost">Batalkan</button></form>
        <?php endif; ?>
    </div>
</div></div>

<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Jasa & Sparepart</h2><span class="muted">Keluhan: <?= e($w['keluhan']) ?><?= $w['mekanik'] ? ' · Mekanik: ' . e($w['mekanik']) : '' ?></span></div>
        <div class="table-wrap"><table class="items">
            <thead><tr><th>Item</th><th>Qty</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr><td><strong><?= e($it['nama']) ?></strong><small><?= $it['jenis'] === 'jasa' ? 'Jasa' : 'Sparepart' ?></small></td><td><?= (int) $it['qty'] ?></td>
                    <td class="text-right"><?= rupiah($it['harga']) ?></td><td class="text-right"><strong><?= rupiah($it['qty'] * $it['harga']) ?></strong></td>
                    <td class="actions"><?php if ($bisaUbah): ?><form method="post" action="<?= url('servis.aksi') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="hapus_item"><input type="hidden" name="item_id" value="<?= $it['id'] ?>"><button class="icon-btn danger" title="Hapus"><?= icon('trash', 14) ?></button></form><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="5" class="empty">Belum ada item. Tambahkan jasa atau sparepart di bawah.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <?php if ($bisaUbah): ?>
            <div class="card-body" style="display:grid;gap:12px;border-top:1px solid var(--line)">
                <form method="post" action="<?= url('servis.aksi') ?>" class="filters"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="tambah_jasa">
                    <select name="ref_id" style="flex:1"><?php foreach ($jasa as $j): ?><option value="<?= $j['id'] ?>"><?= e($j['nama']) ?> · <?= rupiah($j['tarif']) ?></option><?php endforeach; ?></select>
                    <button class="btn btn-dark btn-sm"><?= icon('plus', 14) ?> Jasa</button></form>
                <form method="post" action="<?= url('servis.aksi') ?>" class="filters"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="tambah_part">
                    <select name="ref_id" style="flex:1"><?php foreach ($part as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama']) ?> · <?= rupiah($p['harga_jual']) ?> (stok <?= $p['stok'] ?>)</option><?php endforeach; ?></select>
                    <input type="number" name="qty" value="1" min="1" style="width:70px">
                    <button class="btn btn-dark btn-sm"><?= icon('plus', 14) ?> Part</button></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="align-self:start">
        <div class="card-head"><h2>Pembayaran</h2><?= status_badge($w['status']) ?></div>
        <div class="card-body">
            <div class="sum">
                <div><span class="muted">Jasa</span><span><?= rupiah($w['total_jasa']) ?></span></div>
                <div><span class="muted">Sparepart</span><span><?= rupiah($w['total_part']) ?></span></div>
                <div><span class="muted">Diskon</span><span>- <?= rupiah($w['diskon']) ?></span></div>
                <div class="total"><span>Total</span><span><?= rupiah($w['total']) ?></span></div>
            </div>
            <?php if ($bisaUbah): ?>
                <form method="post" action="<?= url('servis.aksi') ?>" class="filters mt"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="diskon">
                    <input type="number" name="diskon" value="<?= (int) $w['diskon'] ?>" min="0" step="1000" style="flex:1"><button class="btn btn-ghost btn-sm">Set Diskon</button></form>
            <?php endif; ?>
            <?php if ($w['status'] === 'selesai'): ?>
                <form method="post" action="<?= url('servis.aksi') ?>" class="mt" id="form-bayar" data-total="<?= (int) $w['total'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="bayar">
                    <div class="form-grid">
                        <div><label>Metode</label><select name="metode"><option>Tunai</option><option>Transfer</option><option>QRIS</option></select></div>
                        <div><label>Uang diterima</label><input type="number" name="bayar" value="<?= (int) $w['total'] ?>" min="<?= (int) $w['total'] ?>" step="1000" required></div>
                    </div>
                    <p class="muted" style="margin:10px 0 0">Kembalian: <b id="kembali" style="color:var(--ink)">Rp 0</b></p>
                    <button class="btn btn-primary btn-block mt"><?= icon('check', 16) ?> Terima Pembayaran</button>
                </form>
                <script>(function(){var f=document.getElementById('form-bayar'),t=+f.dataset.total;function h(){document.getElementById('kembali').textContent='Rp '+Math.max(0,(+f.bayar.value)-t).toLocaleString('id-ID')}f.bayar.addEventListener('input',h);h()})();</script>
            <?php elseif ($w['status'] === 'lunas'): ?>
                <p class="mt" style="margin-bottom:0">Dibayar <?= rupiah($w['bayar']) ?> via <?= e($w['metode']) ?> · kembali <?= rupiah($w['bayar'] - $w['total']) ?><br><span class="muted"><?= tgl($w['lunas_at']) ?> <?= date('H.i', strtotime($w['lunas_at'])) ?></span></p>
            <?php else: ?>
                <p class="muted mt" style="margin-bottom:0">Pembayaran bisa diterima setelah servis ditandai selesai.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
