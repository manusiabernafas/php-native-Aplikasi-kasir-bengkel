# Aplikasi Kasir Bengkel & Servis Motor (PHP Native + MySQL)

Aplikasi Kasir Bengkel dan Servis Motor berbasis PHP Native dan MySQL untuk bengkel motor, AHASS/YSS mandiri, atau bengkel umum. Motor yang masuk langsung tercatat lewat plat nomor, masuk ke papan antrean model kanban (Antri, Dikerjakan, Siap Diambil, Lunas), lalu kasir menambahkan jasa dan sparepart ke work order. Stok sparepart otomatis berkurang saat dipakai dan kembali saat item dihapus atau servis dibatalkan.

Setelah servis selesai, kasir menerima pembayaran (tunai, transfer, QRIS) dan nota thermal langsung tercetak lengkap dengan rekomendasi servis berikutnya. Pemilik bengkel bisa memantau pendapatan jasa dan sparepart, laba sparepart dari selisih harga beli dan jual, kinerja tiap mekanik, sparepart terlaris, stok menipis, serta mengirim pengingat servis berkala ke pelanggan lewat WhatsApp. Tampilannya soft UI yang bersih dan nyaman dipakai seharian.

## Fitur

- Motor masuk cukup dengan plat nomor: data pemilik, KM, dan riwayat servis langsung tampil
- Daftarkan pelanggan dan motor baru langsung dari form motor masuk
- Papan antrean kanban: Antri, Dikerjakan, Siap Diambil, Lunas hari ini
- Work order dengan item jasa dan sparepart, diskon, dan penugasan mekanik
- Stok sparepart otomatis berkurang saat dipakai dan kembali saat item dihapus/dibatalkan
- Anti stok minus dengan penguncian database (aman untuk banyak kasir)
- Snapshot harga beli dan jual per item untuk perhitungan laba yang akurat
- Pembayaran tunai, transfer, QRIS dengan hitung kembalian otomatis
- Nota thermal siap cetak lengkap dengan rekomendasi servis berikutnya (KM & tanggal)
- Tombol kabari pelanggan via WhatsApp saat motor siap diambil
- Pengingat servis berkala: daftar motor yang sudah waktunya servis + tombol WA
- Manajemen sparepart: kode, satuan, stok minimal, harga beli/jual, margin
- Restock (stok masuk) dengan supplier dan riwayat
- Peringatan stok menipis di dashboard
- Dashboard: pendapatan hari ini dan bulan ini, laba sparepart, grafik jasa vs part 14 hari
- Laporan: omzet, laba kotor, sparepart terlaris, jasa terlaris, kinerja mekanik, metode bayar
- Ekspor laporan ke CSV (Excel)
- Pengaturan jasa dan tarif, mekanik (aktif/nonaktif), interval servis, pengguna
- 2 peran: Admin (pemilik) dan Kasir
- Data contoh 3 bulan (500+ work order) dan tampilan soft UI yang responsif

## Instalasi

**Kebutuhan:** PHP 8.0+ dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP, lalu pindahkan folder `kasir-bengkel` ke:
   - XAMPP: `C:\xampp\htdocs\kasir-bengkel`
   - Laragon: `C:\laragon\www\kasir-bengkel`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/kasir_bengkel.sql` → **Import**.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/kasir_bengkel.sql
   ```

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'kasir_bengkel',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Buka `http://localhost/kasir-bengkel` dan login:
   - Admin (pemilik): `admin` / `admin123`
   - Kasir: `kasir` / `admin123`

6. Masuk ke **Pengaturan** untuk mengganti nama bengkel, tarif jasa, mekanik, dan interval servis berkala. Atur harga dan stok di menu **Sparepart**.

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
