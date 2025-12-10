<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Set header response sebagai JSON
header('Content-Type: application/json');

// Validasi apakah user sudah login
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Validasi metode request harus POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Ambil data JSON dari body request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data yang diperlukan ada
if (!$data || !isset($data['id_produk']) || !isset($data['metode_pembayaran'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Ambil data user dari session
$user = $_SESSION['user'];

// Siapkan data transaksi
$transaction_data = [
    'id_produk' => $data['id_produk'],
    'id_pembeli' => $user['username'],
    'id_penjual' => $data['id_penjual'],
    'nama_produk' => $data['nama_produk'],
    'harga_produk' => $data['harga_produk'],
    'metode_pembayaran' => $data['metode_pembayaran']
];

// Panggil fungsi addTransaction untuk menyimpan transaksi
if (addTransaction($conn, $transaction_data)) {
    // Ambil ID transaksi yang baru dibuat
    $new_transaction_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'message' => 'Transaction added successfully', 'id_transaksi' => $new_transaction_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add transaction: ' . mysqli_error($conn)]);
}
?>
