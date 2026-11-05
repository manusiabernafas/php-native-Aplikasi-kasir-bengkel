<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    'login'         => 'pages/login.php',
    'logout'        => 'pages/logout.php',
    'dashboard'     => 'pages/dashboard.php',
    'antrean'       => 'pages/servis/antrean.php',
    'servis.baru'   => 'pages/servis/baru.php',
    'servis.detail' => 'pages/servis/detail.php',
    'servis.aksi'   => 'pages/servis/aksi.php',
    'servis.nota'   => 'pages/servis/nota.php',
    'transaksi'     => 'pages/servis/transaksi.php',
    'kendaraan'     => 'pages/kendaraan.php',
    'sparepart'     => 'pages/master/sparepart.php',
    'master'        => 'pages/master/index.php',
    'laporan'       => 'pages/laporan.php',
];

$page = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}
if ($page !== 'login') require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
