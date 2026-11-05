<?php
$aktif = (int) q("SELECT COUNT(*) FROM work_order WHERE status IN ('antri','dikerjakan','selesai')")->fetchColumn();
$menu = [
    'Operasional' => [['dashboard', 'Beranda', 'home', 0], ['antrean', 'Antrean Servis', 'wrench', $aktif], ['transaksi', 'Riwayat Transaksi', 'receipt', 0], ['kendaraan', 'Pelanggan & Motor', 'bike', 0]],
    'Gudang & Laporan' => [['sparepart', 'Sparepart', 'box', 0], ['laporan', 'Laporan', 'chart', 0]],
];
if (user()['role'] === 'admin') $menu['Gudang & Laporan'][] = ['master', 'Pengaturan', 'settings', 0];
$current = explode('.', $_GET['page'] ?? 'dashboard')[0];
if ($current === 'servis') $current = 'antrean';
$flash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Beranda') ?> · <?= e(setting()['nama_bengkel']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="brand-mark"><?= icon('wrench', 22) ?></span><div><strong><?= e(setting()['nama_bengkel']) ?></strong><small>Kasir & Servis</small></div></div>
        <nav>
            <?php foreach ($menu as $sec => $links): ?>
                <div class="sec"><?= $sec ?></div>
                <?php foreach ($links as [$key, $label, $ic, $n]): ?>
                    <a href="<?= url($key) ?>" class="<?= $current === $key ? 'active' : '' ?>"><?= icon($ic) ?> <?= $label ?><?php if ($n): ?><span class="count"><?= $n ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="grow"></div>
        <div class="me"><span class="avatar"><?= e(strtoupper(substr(user()['nama'], 0, 1))) ?></span><div class="grow"><strong><?= e(user()['nama']) ?></strong><small><?= e(ucfirst(user()['role'])) ?></small></div>
            <a class="icon-btn" href="<?= url('logout') ?>" title="Keluar"><?= icon('logout', 16) ?></a></div>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><?= icon('menu') ?></button>
            <h1><?= e($title ?? 'Beranda') ?><?php if (!empty($sub)): ?><span class="sub"><?= $sub ?></span><?php endif; ?></h1>
            <div class="topbar-actions"><?= $actions ?? '' ?></div>
        </header>
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
