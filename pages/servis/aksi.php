<?php
// Semua aksi work order: tambah/hapus item (stok ikut berubah), mulai, selesai, diskon, bayar, batal
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('antrean');
$id = (int) ($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';

try {
    db()->beginTransaction();
    $w = q('SELECT * FROM work_order WHERE id = ? FOR UPDATE', [$id])->fetch();
    if (!$w) throw new RuntimeException('Work order tidak ditemukan.');
    $terbuka = in_array($w['status'], ['antri', 'dikerjakan', 'selesai'], true);
    $pesan = 'Tersimpan.';

    switch ($aksi) {
        case 'tambah_jasa':
            if (!$terbuka) throw new RuntimeException('Work order sudah ditutup.');
            $j = q('SELECT * FROM jasa WHERE id = ?', [(int) $_POST['ref_id']])->fetch();
            if (!$j) throw new RuntimeException('Jasa tidak ditemukan.');
            q("INSERT INTO wo_item (wo_id, jenis, ref_id, nama, qty, harga) VALUES (?, 'jasa', ?, ?, 1, ?)", [$id, $j['id'], $j['nama'], $j['tarif']]);
            $pesan = "Jasa {$j['nama']} ditambahkan.";
            break;

        case 'tambah_part':
            if (!$terbuka) throw new RuntimeException('Work order sudah ditutup.');
            $qty = max(1, (int) ($_POST['qty'] ?? 1));
            // Kunci baris sparepart agar stok tidak minus bila dua kasir memakai part yang sama
            $p = q('SELECT * FROM sparepart WHERE id = ? FOR UPDATE', [(int) $_POST['ref_id']])->fetch();
            if (!$p) throw new RuntimeException('Sparepart tidak ditemukan.');
            if ($p['stok'] < $qty) throw new RuntimeException("Stok {$p['nama']} tinggal {$p['stok']}.");
            q("INSERT INTO wo_item (wo_id, jenis, ref_id, nama, qty, harga, harga_beli) VALUES (?, 'part', ?, ?, ?, ?, ?)", [$id, $p['id'], $p['nama'], $qty, $p['harga_jual'], $p['harga_beli']]);
            q('UPDATE sparepart SET stok = stok - ? WHERE id = ?', [$qty, $p['id']]);
            $pesan = "{$qty} x {$p['nama']} ditambahkan, stok berkurang.";
            break;

        case 'hapus_item':
            if (!$terbuka) throw new RuntimeException('Work order sudah ditutup.');
            $it = q('SELECT * FROM wo_item WHERE id = ? AND wo_id = ?', [(int) $_POST['item_id'], $id])->fetch();
            if ($it) {
                if ($it['jenis'] === 'part') q('UPDATE sparepart SET stok = stok + ? WHERE id = ?', [$it['qty'], $it['ref_id']]);
                q('DELETE FROM wo_item WHERE id = ?', [$it['id']]);
                $pesan = "{$it['nama']} dihapus" . ($it['jenis'] === 'part' ? ', stok dikembalikan.' : '.');
            }
            break;

        case 'mulai':
            if ($w['status'] !== 'antri') throw new RuntimeException('Hanya work order antri yang bisa dimulai.');
            $mek = (int) ($_POST['mekanik_id'] ?? 0);
            if (!q('SELECT 1 FROM mekanik WHERE id = ? AND aktif = 1', [$mek])->fetchColumn()) throw new RuntimeException('Pilih mekanik.');
            q("UPDATE work_order SET status = 'dikerjakan', mekanik_id = ? WHERE id = ?", [$mek, $id]);
            $pesan = 'Servis mulai dikerjakan.';
            break;

        case 'selesai':
            if ($w['status'] !== 'dikerjakan') throw new RuntimeException('Servis belum dikerjakan.');
            if (!q('SELECT 1 FROM wo_item WHERE wo_id = ?', [$id])->fetchColumn()) throw new RuntimeException('Tambahkan minimal satu jasa atau sparepart.');
            q("UPDATE work_order SET status = 'selesai', selesai_at = NOW() WHERE id = ?", [$id]);
            $pesan = 'Servis selesai, motor siap diambil.';
            break;

        case 'diskon':
            if (!$terbuka) throw new RuntimeException('Work order sudah ditutup.');
            q('UPDATE work_order SET diskon = ? WHERE id = ?', [max(0, (int) $_POST['diskon']), $id]);
            $pesan = 'Diskon diperbarui.';
            break;

        case 'bayar':
            if ($w['status'] !== 'selesai') throw new RuntimeException('Servis harus selesai sebelum dibayar.');
            hitung_ulang_wo($id);
            $total = (int) q('SELECT total FROM work_order WHERE id = ?', [$id])->fetchColumn();
            $bayar = (int) $_POST['bayar'];
            if ($bayar < $total) throw new RuntimeException('Uang diterima kurang dari total.');
            $metode = in_array($_POST['metode'] ?? '', ['Tunai', 'Transfer', 'QRIS'], true) ? $_POST['metode'] : 'Tunai';
            q("UPDATE work_order SET status = 'lunas', bayar = ?, metode = ?, lunas_at = NOW(), user_id = ? WHERE id = ?", [$bayar, $metode, user()['id'], $id]);
            db()->commit();
            flash('Pembayaran diterima. Kembalian ' . rupiah($bayar - $total) . '.');
            redirect('servis.nota', ['id' => $id]);

        case 'batal':
            if (!$terbuka) throw new RuntimeException('Work order sudah ditutup.');
            foreach (q("SELECT * FROM wo_item WHERE wo_id = ? AND jenis = 'part'", [$id])->fetchAll() as $it) {
                q('UPDATE sparepart SET stok = stok + ? WHERE id = ?', [$it['qty'], $it['ref_id']]);
            }
            q("UPDATE work_order SET status = 'batal' WHERE id = ?", [$id]);
            $pesan = 'Work order dibatalkan, stok sparepart dikembalikan.';
            break;

        default:
            throw new RuntimeException('Aksi tidak dikenal.');
    }
    hitung_ulang_wo($id);
    db()->commit();
    flash($pesan);
} catch (RuntimeException $ex) {
    if (db()->inTransaction()) db()->rollBack();
    flash($ex->getMessage(), 'error');
}
redirect('servis.detail', ['id' => $id]);
