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
$role = $user['role'];
$username = $user['username'];

// Validasi ID produk
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$id_produk = intval($_GET['id']);

// Ambil data produk
$products = getProducts($conn);
$product = null;
foreach ($products as $p) {
    if ($p['id_produk'] == $id_produk) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: dashboard.php');
    exit();
}

// Proses pembelian jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase'])) {
    $metode_pembayaran = $_POST['metode_pembayaran'];
    if (!$metode_pembayaran) {
        $error_message = "Silakan pilih metode pembayaran.";
    } else {
        // Buat transaksi Pending
        $transaction_data = [
            'id_produk' => $product['id_produk'],
            'id_pembeli' => $username,
            'id_penjual' => $product['seller_username'],
            'nama_produk' => $product['nama_produk'],
            'harga_produk' => $product['harga_produk'],
            'metode_pembayaran' => $metode_pembayaran
        ];

        if (addTransaction($conn, $transaction_data)) {
            $result = mysqli_query($conn, "SELECT LAST_INSERT_ID() as id");
            $row = mysqli_fetch_assoc($result);
            $id_transaksi = $row['id'];
            header('Location: payment.php?id_transaksi=' . $id_transaksi);
            exit();
        } else {
            $error_message = "Gagal memulai transaksi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['nama_produk']); ?> - Digital Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .detail-bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="detail-bg">
    <nav class="bg-white shadow-lg p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Digital Marketplace</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Halo, <?php echo htmlspecialchars($user['username']); ?> (<?php echo ucfirst($role); ?>)</span>
                <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">Kembali ke Dashboard</a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-8 px-4">
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 fade-in">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card p-8 fade-in">
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <img src="<?php echo htmlspecialchars($product['foto_produk']); ?>" 
                         alt="<?php echo htmlspecialchars($product['nama_produk']); ?>" 
                         class="w-full h-96 object-cover rounded-lg shadow-lg">
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($product['nama_produk']); ?></h1>
                    <p class="text-4xl font-bold text-green-600 mb-4"><?php echo formatRupiah($product['harga_produk']); ?></p>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Penjual</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($product['seller_username']); ?></p>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Deskripsi Produk</h3>
                        <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['deskripsi_produk'])); ?></p>
                    </div>

                    <?php if ($role != 'penjual'): ?>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Metode Pembayaran</label>
                                <select name="metode_pembayaran" required 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="">Pilih metode</option>
                                    <option value="Transfer Bank (BCA, Mandiri, BRI)">Transfer Bank</option>
                                    <option value="GoPay">GoPay</option>
                                    <option value="OVO">OVO</option>
                                    <option value="DANA">DANA</option>
                                    <option value="ShopeePay">ShopeePay</option>
                                </select>
                            </div>
                            <input type="hidden" name="purchase" value="1">
                            <button type="submit" class="w-full btn-success text-white py-3 px-6 rounded-lg text-lg font-semibold hover:opacity-90 transition-opacity">
                                Beli Sekarang
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
