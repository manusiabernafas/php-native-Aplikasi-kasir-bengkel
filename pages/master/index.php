<?php
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'bengkel':
                q('UPDATE pengaturan SET nama_bengkel=?, alamat=?, telepon=?, interval_servis_hari=?, interval_servis_km=? WHERE id=1', [trim($_POST['nama_bengkel']), trim($_POST['alamat']), trim($_POST['telepon']), max(7, (int) $_POST['interval_servis_hari']), max(500, (int) $_POST['interval_servis_km'])]);
                break;
            case 'jasa':
                if (trim($_POST['nama']) === '' || (int) $_POST['tarif'] <= 0) throw new RuntimeException('Nama dan tarif jasa wajib diisi.');
                q('INSERT INTO jasa (nama, tarif) VALUES (?,?)', [trim($_POST['nama']), (int) $_POST['tarif']]);
                break;
            case 'tarif':
                foreach ($_POST['tarif'] ?? [] as $id => $t) q('UPDATE jasa SET tarif = ? WHERE id = ?', [max(0, (int) $t), (int) $id]);
                break;
            case 'mekanik':
                if (trim($_POST['nama']) === '') throw new RuntimeException('Nama mekanik wajib diisi.');
                q('INSERT INTO mekanik (nama, telepon) VALUES (?,?)', [trim($_POST['nama']), trim($_POST['telepon'])]);
                break;
            case 'mekanik_status':
                q('UPDATE mekanik SET aktif = 1 - aktif WHERE id = ?', [(int) $_POST['id']]);
                break;
            case 'user':
                if (strlen($_POST['password']) < 6) throw new RuntimeException('Password minimal 6 karakter.');
                q('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', [trim($_POST['nama']), trim($_POST['username']), password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'] === 'admin' ? 'admin' : 'kasir']);
                break;
        }
        flash('Perubahan disimpan.');
    } catch (PDOException $e) {
        flash('Gagal: data sudah ada.', 'error');
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('master');
}
$set = setting();
$jasa = q('SELECT * FROM jasa ORDER BY nama')->fetchAll();
$mek = q('SELECT * FROM mekanik ORDER BY aktif DESC, nama')->fetchAll();
$users = q('SELECT * FROM users ORDER BY id')->fetchAll();
$title = 'Pengaturan';
require __DIR__ . '/../../includes/header.php';
?>
<div class="grid-2">
    <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="tarif">
        <div class="card-head"><h2>Daftar jasa & tarif</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($jasa as $j): ?><tr><td><strong><?= e($j['nama']) ?></strong></td><td style="width:170px"><input type="number" name="tarif[<?= $j['id'] ?>]" value="<?= (int) $j['tarif'] ?>" step="5000" min="0"></td></tr><?php endforeach; ?></tbody></table></div>
        <div class="card-body"><button class="btn btn-primary">Simpan Tarif</button></div>
    </form>
    <div>
        <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="bengkel">
            <div class="card-head"><h2>Profil bengkel</h2></div>
            <div class="card-body"><div class="form-grid">
                <div class="full"><label>Nama bengkel</label><input type="text" name="nama_bengkel" value="<?= e($set['nama_bengkel']) ?>"></div>
                <div class="full"><label>Alamat</label><input type="text" name="alamat" value="<?= e($set['alamat']) ?>"></div>
                <div><label>Telepon</label><input type="text" name="telepon" value="<?= e($set['telepon']) ?>"></div>
                <div><label>Servis berkala (hari)</label><input type="number" name="interval_servis_hari" value="<?= (int) $set['interval_servis_hari'] ?>"></div>
                <div><label>Servis berkala (KM)</label><input type="number" name="interval_servis_km" value="<?= (int) $set['interval_servis_km'] ?>" step="500"></div>
            </div><button class="btn btn-dark btn-block mt">Simpan</button></div>
        </form>
        <div class="card mt"><div class="card-head"><h2>Tambah jasa</h2></div><form method="post" class="card-body filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="jasa">
            <input type="text" name="nama" placeholder="Nama jasa" required style="flex:1"><input type="number" name="tarif" placeholder="Tarif" style="width:120px" required><button class="btn btn-primary btn-sm"><?= icon('plus', 14) ?></button></form></div>
    </div>
</div>
<div class="grid-2 mt">
    <div class="card"><div class="card-head"><h2>Mekanik</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($mek as $m): ?><tr><td><strong><?= e($m['nama']) ?></strong><small><?= e($m['telepon']) ?></small></td>
            <td class="actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="mekanik_status"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn btn-sm <?= $m['aktif'] ? 'btn-ghost' : 'btn-primary' ?>"><?= $m['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" class="card-body filters"><?= csrf_field() ?><input type="hidden" name="aksi" value="mekanik"><input type="text" name="nama" placeholder="Nama mekanik" required><input type="text" name="telepon" placeholder="No. HP"><button class="btn btn-dark btn-sm"><?= icon('plus', 14) ?></button></form>
    </div>
    <div class="card"><div class="card-head"><h2>Pengguna</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($users as $u): ?><tr><td><strong><?= e($u['nama']) ?></strong><small><?= e($u['username']) ?></small></td><td class="text-right"><span class="badge <?= $u['role'] === 'admin' ? 'badge-orange' : 'badge-gray' ?>"><?= e(ucfirst($u['role'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" class="card-body"><?= csrf_field() ?><input type="hidden" name="aksi" value="user"><div class="form-grid">
            <div><label>Nama</label><input type="text" name="nama" required></div><div><label>Username</label><input type="text" name="username" required></div>
            <div><label>Password</label><input type="password" name="password" minlength="6" required></div><div><label>Peran</label><select name="role"><option value="kasir">Kasir</option><option value="admin">Admin</option></select></div>
        </div><button class="btn btn-dark btn-block mt"><?= icon('plus', 14) ?> Tambah</button></form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
