<?php
$admin = user()['role'] === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['aksi'] ?? '') === 'restock') {
            $qty = (int) $_POST['qty'];
            $p = q('SELECT * FROM sparepart WHERE id = ?', [(int) $_POST['id']])->fetch();
            if (!$p || $qty < 1) throw new RuntimeException('Pilih sparepart dan jumlah yang valid.');
            $hb = (int) $_POST['harga_beli'] ?: $p['harga_beli'];
            db()->beginTransaction();
            q('INSERT INTO stok_masuk (sparepart_id, qty, harga_beli, supplier, tanggal, user_id) VALUES (?,?,?,?,CURDATE(),?)', [$p['id'], $qty, $hb, trim($_POST['supplier'] ?? ''), user()['id']]);
            q('UPDATE sparepart SET stok = stok + ?, harga_beli = ? WHERE id = ?', [$qty, $hb, $p['id']]);
            db()->commit();
            flash("Stok {$p['nama']} bertambah $qty.");
        } elseif (($_POST['aksi'] ?? '') === 'simpan' && $admin) {
            $id = (int) $_POST['id'];
            $v = [strtoupper(trim($_POST['kode'])), trim($_POST['nama']), trim($_POST['satuan']) ?: 'pcs', max(0, (int) $_POST['stok_min']), max(0, (int) $_POST['harga_beli']), max(0, (int) $_POST['harga_jual'])];
            if ($v[0] === '' || $v[1] === '' || $v[5] <= 0) throw new RuntimeException('Kode, nama, dan harga jual wajib diisi.');
            if ($v[5] < $v[4]) throw new RuntimeException('Harga jual lebih kecil dari harga beli.');
            if ($id) q('UPDATE sparepart SET kode=?, nama=?, satuan=?, stok_min=?, harga_beli=?, harga_jual=? WHERE id=?', [...$v, $id]);
            else q('INSERT INTO sparepart (kode, nama, satuan, stok_min, harga_beli, harga_jual, stok) VALUES (?,?,?,?,?,?,?)', [...$v, max(0, (int) $_POST['stok'])]);
            flash('Sparepart disimpan.');
        }
    } catch (PDOException $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('Kode sparepart sudah dipakai.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('sparepart');
}
$cari = trim($_GET['cari'] ?? '');
$rows = q('SELECT s.*, (SELECT COALESCE(SUM(qty),0) FROM wo_item i JOIN work_order w ON w.id = i.wo_id WHERE i.jenis = "part" AND i.ref_id = s.id AND w.status = "lunas" AND w.lunas_at >= CURDATE() - INTERVAL 30 DAY) terjual
           FROM sparepart s WHERE s.nama LIKE ? OR s.kode LIKE ? ORDER BY s.nama', ["%$cari%", "%$cari%"])->fetchAll();
$edit = isset($_GET['edit']) ? q('SELECT * FROM sparepart WHERE id = ?', [(int) $_GET['edit']])->fetch() : null;
$riwayat = q('SELECT m.*, s.nama FROM stok_masuk m JOIN sparepart s ON s.id = m.sparepart_id ORDER BY m.id DESC LIMIT 6')->fetchAll();
$nilai = array_sum(array_map(fn ($r) => $r['stok'] * $r['harga_beli'], $rows));

$title = 'Sparepart';
$sub = count($rows) . ' item · nilai stok ' . rupiah($nilai) . ' (harga beli)';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Daftar sparepart</h2><form class="filters" method="get"><input type="hidden" name="page" value="sparepart"><input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Cari kode / nama"></form></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Sparepart</th><th>Stok</th><th>Beli</th><th>Jual</th><th>Laku 30 hr</th><th></th></tr></thead>
            <tbody><?php foreach ($rows as $p): ?>
                <tr><td><strong><?= e($p['nama']) ?></strong><small><?= e($p['kode']) ?></small></td>
                    <td><span class="badge <?= $p['stok'] == 0 ? 'badge-red' : ($p['stok'] <= $p['stok_min'] ? 'badge-orange' : 'badge-green') ?>"><?= $p['stok'] ?> <?= e($p['satuan']) ?></span></td>
                    <td><?= rupiah($p['harga_beli']) ?></td><td><strong><?= rupiah($p['harga_jual']) ?></strong><small>margin <?= $p['harga_beli'] ? round(($p['harga_jual'] - $p['harga_beli']) / $p['harga_beli'] * 100) : 0 ?>%</small></td>
                    <td><?= (int) $p['terjual'] ?></td>
                    <td class="actions"><?php if ($admin): ?><a class="icon-btn" href="<?= url('sparepart', ['edit' => $p['id']]) ?>"><?= icon('edit', 14) ?></a><?php endif; ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div>
    <div>
        <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="restock">
            <div class="card-head"><h2>Stok masuk (restock)</h2></div>
            <div class="card-body"><div class="form-grid">
                <div class="full"><label>Sparepart</label><select name="id"><?php foreach ($rows as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama']) ?> (stok <?= $p['stok'] ?>)</option><?php endforeach; ?></select></div>
                <div><label>Jumlah</label><input type="number" name="qty" min="1" value="10" required></div>
                <div><label>Harga beli satuan</label><input type="number" name="harga_beli" min="0" placeholder="kosong = harga lama"></div>
                <div class="full"><label>Supplier</label><input type="text" name="supplier"></div>
            </div><button class="btn btn-primary btn-block mt"><?= icon('plus', 16) ?> Tambah Stok</button></div>
        </form>
        <?php if ($admin): ?>
        <form method="post" class="card mt"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <div class="card-head"><h2><?= $edit ? 'Ubah ' . e($edit['nama']) : 'Sparepart baru' ?></h2></div>
            <div class="card-body"><div class="form-grid">
                <div><label>Kode</label><input type="text" name="kode" value="<?= e($edit['kode'] ?? '') ?>" required></div>
                <div><label>Satuan</label><input type="text" name="satuan" value="<?= e($edit['satuan'] ?? 'pcs') ?>"></div>
                <div class="full"><label>Nama</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
                <div><label>Harga beli</label><input type="number" name="harga_beli" value="<?= (int) ($edit['harga_beli'] ?? 0) ?>" min="0"></div>
                <div><label>Harga jual</label><input type="number" name="harga_jual" value="<?= (int) ($edit['harga_jual'] ?? 0) ?>" min="0" required></div>
                <div><label>Stok minimal</label><input type="number" name="stok_min" value="<?= (int) ($edit['stok_min'] ?? 5) ?>" min="0"></div>
                <?php if (!$edit): ?><div><label>Stok awal</label><input type="number" name="stok" value="0" min="0"></div><?php endif; ?>
            </div><button class="btn btn-dark btn-block mt">Simpan</button></div>
        </form>
        <?php endif; ?>
        <div class="card mt"><div class="card-head"><h2>Riwayat stok masuk</h2></div><div class="table-wrap"><table><tbody>
            <?php foreach ($riwayat as $r): ?><tr><td><strong><?= e($r['nama']) ?></strong><small><?= tgl($r['tanggal']) ?> · <?= e($r['supplier'] ?: '-') ?></small></td><td class="text-right">+<?= $r['qty'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
