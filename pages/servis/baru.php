<?php
$error = null;
$plat = strtoupper(trim($_GET['plat'] ?? $_POST['plat'] ?? ''));
$kendaraan = $plat !== '' ? q('SELECT k.*, p.nama, p.telepon FROM kendaraan k JOIN pelanggan p ON p.id = k.pelanggan_id WHERE REPLACE(k.plat, " ", "") = REPLACE(?, " ", "")', [$plat])->fetch() : null;
$riwayat = $kendaraan ? q("SELECT kode, masuk_at, km, total, keluhan FROM work_order WHERE kendaraan_id = ? AND status = 'lunas' ORDER BY masuk_at DESC LIMIT 3", [$kendaraan['id']])->fetchAll() : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        db()->beginTransaction();
        if (!$kendaraan) {
            // Motor baru: daftarkan pelanggan (berdasarkan no. HP) dan kendaraannya
            foreach (['nama', 'telepon', 'merk', 'tipe'] as $f) if (trim($_POST[$f] ?? '') === '') throw new RuntimeException('Lengkapi data pelanggan dan motor baru.');
            if (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{0,3}$/', $plat)) throw new RuntimeException('Format plat tidak valid. Contoh: AB 1234 CD');
            $pid = q('SELECT id FROM pelanggan WHERE telepon = ?', [trim($_POST['telepon'])])->fetchColumn();
            if (!$pid) {
                q('INSERT INTO pelanggan (nama, telepon) VALUES (?,?)', [trim($_POST['nama']), trim($_POST['telepon'])]);
                $pid = db()->lastInsertId();
            }
            q('INSERT INTO kendaraan (pelanggan_id, plat, merk, tipe, tahun) VALUES (?,?,?,?,?)', [$pid, $plat, trim($_POST['merk']), trim($_POST['tipe']), (int) $_POST['tahun'] ?: null]);
            $kid = (int) db()->lastInsertId();
        } else {
            $kid = (int) $kendaraan['id'];
        }
        $km = max(0, (int) $_POST['km']);
        q('INSERT INTO work_order (kode, kendaraan_id, km, keluhan, user_id) VALUES (?,?,?,?,?)', ['TMP-' . bin2hex(random_bytes(4)), $kid, $km, trim($_POST['keluhan'] ?? '') ?: 'Servis rutin', user()['id']]);
        $id = (int) db()->lastInsertId();
        q('UPDATE work_order SET kode = ? WHERE id = ?', ['WO-' . date('ymd') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT), $id]);
        if ($km) q('UPDATE kendaraan SET km_terakhir = GREATEST(km_terakhir, ?) WHERE id = ?', [$km, $kid]);
        db()->commit();
        flash('Motor masuk antrean. Tambahkan jasa & sparepart di work order.');
        redirect('servis.detail', ['id' => $id]);
    } catch (RuntimeException $ex) {
        db()->rollBack();
        $error = $ex->getMessage();
    }
}
$title = 'Motor Masuk';
$sub = 'Cari plat nomor, kalau belum terdaftar isi data motor baru';
require __DIR__ . '/../../includes/header.php';
?>
<form method="get" class="card" style="margin-bottom:20px"><div class="card-body filters">
    <input type="hidden" name="page" value="servis.baru">
    <input type="text" name="plat" value="<?= e($plat) ?>" placeholder="Plat nomor, contoh AB 1234 CD" style="flex:1;font-size:17px;text-transform:uppercase" autofocus required>
    <button class="btn btn-dark"><?= icon('search', 16) ?> Cek Plat</button>
</div></form>
<?php if ($plat !== ''): ?>
<form method="post" class="grid-2">
    <?= csrf_field() ?><input type="hidden" name="plat" value="<?= e($plat) ?>">
    <div class="card">
        <div class="card-head"><h2><?= $kendaraan ? 'Motor terdaftar' : 'Motor baru' ?> <span class="plat"><?= e($plat) ?></span></h2></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($kendaraan): ?>
                <p style="margin:0 0 4px;font-size:16px"><b><?= e($kendaraan['merk'] . ' ' . $kendaraan['tipe']) ?></b> <?= $kendaraan['tahun'] ? '(' . $kendaraan['tahun'] . ')' : '' ?></p>
                <p class="muted" style="margin:0 0 16px"><?= e($kendaraan['nama']) ?> · <?= e($kendaraan['telepon']) ?> · KM terakhir <?= number_format($kendaraan['km_terakhir'], 0, ',', '.') ?></p>
            <?php else: ?>
                <div class="form-grid" style="margin-bottom:16px">
                    <div><label>Nama pelanggan</label><input type="text" name="nama" required></div>
                    <div><label>No. WhatsApp</label><input type="text" name="telepon" required></div>
                    <div><label>Merk</label><select name="merk"><?php foreach (['Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'TVS', 'Lainnya'] as $m): ?><option><?= $m ?></option><?php endforeach; ?></select></div>
                    <div><label>Tipe</label><input type="text" name="tipe" placeholder="Beat, NMAX, ..." required></div>
                    <div><label>Tahun</label><input type="number" name="tahun" min="1990" max="<?= date('Y') + 1 ?>"></div>
                </div>
            <?php endif; ?>
            <div class="form-grid">
                <div><label>KM saat ini</label><input type="number" name="km" min="0" value="<?= $kendaraan ? (int) $kendaraan['km_terakhir'] : '' ?>"></div>
                <div class="full"><label>Keluhan</label><textarea name="keluhan" rows="3" placeholder="Servis rutin, rem bunyi, mesin brebet, ..."></textarea></div>
            </div>
            <button class="btn btn-primary btn-block mt"><?= icon('arrow', 16) ?> Masukkan ke Antrean</button>
        </div>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-head"><h2>Riwayat servis</h2></div>
        <div class="table-wrap"><table><tbody>
            <?php foreach ($riwayat as $r): ?><tr><td><strong><?= tgl($r['masuk_at']) ?></strong><small><?= e($r['keluhan']) ?> · KM <?= number_format($r['km'], 0, ',', '.') ?></small></td><td class="text-right"><?= rupiah($r['total']) ?></td></tr><?php endforeach; ?>
            <?php if (!$riwayat): ?><tr><td class="empty">Belum ada riwayat servis.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
