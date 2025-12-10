<?php
require_once 'config.php';
require_once 'functions.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$id_transaksi = intval($_GET['id']);

// Ambil data transaksi dengan join untuk mendapatkan deskripsi produk dan WhatsApp penjual
$sql = "SELECT t.*, p.deskripsi_produk, u.nomor_whatsapp as seller_whatsapp 
        FROM transaksi_pembayaran t 
        LEFT JOIN produk p ON t.id_produk = p.id_produk 
        LEFT JOIN users u ON t.id_penjual = u.username 
        WHERE t.id_transaksi = $id_transaksi";

$result = mysqli_query($conn, $sql);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    echo json_encode(['success' => false]);
    exit();
}

echo json_encode([
    'success' => true,
    'transaction' => $transaction
]);
?>
