<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Validasi apakah user sudah login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$username = $user['username'];

// Ambil data transaksi dari GET parameter
if (!isset($_GET['id_transaksi'])) {
    header('Location: dashboard.php');
    exit();
}

$id_transaksi = intval($_GET['id_transaksi']);

// Ambil data transaksi dari database
$sql = "SELECT * FROM transaksi_pembayaran WHERE id_transaksi = $id_transaksi AND id_pembeli = '" . mysqli_real_escape_string($conn, $username) . "'";
$result = mysqli_query($conn, $sql);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    header('Location: dashboard.php');
    exit();
}

// Jika transaksi sudah sukses, redirect ke dashboard dengan pesan sukses
if ($transaction['status'] == 'Sukses') {
    header('Location: dashboard.php?payment_success=1');
    exit();
}

// Simulasi pembayaran (dalam production gunakan gateway seperti Midtrans)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    // Update status transaksi menjadi Menunggu Konfirmasi
    if (updateTransactionStatus($conn, $id_transaksi, 'Menunggu Konfirmasi')) {
        // Redirect ke dashboard dengan parameter pembayaran dikirim
        header('Location: dashboard.php?payment_submitted=1');
        exit();
    } else {
        $error_message = "Gagal memproses pembayaran.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Digital Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-md mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-center mb-6">Pembayaran</h1>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Detail Transaksi</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p><strong>Produk:</strong> <?php echo htmlspecialchars($transaction['nama_produk']); ?></p>
                    <p><strong>Harga:</strong> <?php echo formatRupiah($transaction['harga_produk']); ?></p>
                    <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($transaction['metode_pembayaran']); ?></p>
                    <p><strong>Status:</strong> <span class="text-yellow-600"><?php echo $transaction['status']; ?></span></p>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Instruksi Pembayaran</h2>
                <div class="bg-blue-50 p-4 rounded-lg text-sm">
                    <?php if ($transaction['metode_pembayaran'] == 'Transfer Bank (BCA, Mandiri, BRI)'): ?>
                        <p>Silakan transfer ke rekening berikut:</p>
                        <p><strong>BCA:</strong> 1234567890 a.n. Digital Marketplace</p>
                        <p><strong>Mandiri:</strong> 0987654321 a.n. Digital Marketplace</p>
                        <p><strong>BRI:</strong> 1122334455 a.n. Digital Marketplace</p>
                        <p class="mt-2">Nominal: <strong><?php echo formatRupiah($transaction['harga_produk']); ?></strong></p>
                    <?php elseif ($transaction['metode_pembayaran'] == 'GoPay'): ?>
                        <p>Scan QR Code atau transfer ke GoPay: 081234567890</p>
                        <p>Nominal: <strong><?php echo formatRupiah($transaction['harga_produk']); ?></strong></p>
                    <?php elseif ($transaction['metode_pembayaran'] == 'OVO'): ?>
                        <p>Transfer ke OVO: 081234567890</p>
                        <p>Nominal: <strong><?php echo formatRupiah($transaction['harga_produk']); ?></strong></p>
                    <?php elseif ($transaction['metode_pembayaran'] == 'DANA'): ?>
                        <p>Transfer ke DANA: 081234567890</p>
                        <p>Nominal: <strong><?php echo formatRupiah($transaction['harga_produk']); ?></strong></p>
                    <?php elseif ($transaction['metode_pembayaran'] == 'ShopeePay'): ?>
                        <p>Transfer ke ShopeePay: 081234567890</p>
                        <p>Nominal: <strong><?php echo formatRupiah($transaction['harga_produk']); ?></strong></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST">
                <button type="submit" name="confirm_payment" class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition-colors font-semibold">
                    Saya Sudah Bayar
                </button>
            </form>

            <script>
                // Jika pembayaran sukses, otomatis buka WhatsApp
                <?php if (isset($_GET['open_whatsapp'])): ?>
                    window.onload = function() {
                        // Ambil data lengkap transaksi dari API
                        fetch('get_transaction.php?id=<?php echo $id_transaksi; ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const transaction = data.transaction;
                                const formattedPrice = new Intl.NumberFormat('id-ID').format(transaction.harga_produk);
                                const message = `🛒 *PESANAN PRODUK DIGITAL*\n\n📦 *Produk:* ${transaction.nama_produk}\n💰 *Harga:* Rp ${formattedPrice}\n🏪 *Penjual:* ${transaction.id_penjual}\n\n📝 *Deskripsi:*\n${transaction.deskripsi_produk}\n\nSaya ingin membeli menggunakan metode pembayaran: ${transaction.metode_pembayaran}\n\nMohon info lebih lanjut untuk proses pemesanan. Terima kasih! 🙏`;
                                const whatsappUrl = `https://wa.me/${transaction.seller_whatsapp}?text=${encodeURIComponent(message)}`;

                                window.open(whatsappUrl, '_blank');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching transaction data:', error);
                        });
                    };
                <?php endif; ?>
            </script>

            <div class="mt-4 text-center">
                <a href="dashboard.php" class="text-blue-500 hover:text-blue-700">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
