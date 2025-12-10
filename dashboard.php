<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Validasi login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];
$username = $user['username'];

// Proses aksi POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tambah produk
    if (isset($_POST['add_product'])) {
        $product_data = [
            'nama_produk' => $_POST['nama_produk'],
            'harga_produk' => $_POST['harga_produk'],
            'deskripsi_produk' => $_POST['deskripsi_produk'],
            'foto_produk' => $_POST['foto_produk'],
            'id_penjual' => $user['nomor_whatsapp']
        ];
        if (addProduct($conn, $product_data)) {
            $success_message = "Produk berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan produk: " . mysqli_error($conn);
        }
    // Hapus produk
    } elseif (isset($_POST['delete_product'])) {
        $id_produk = intval($_POST['id_produk']);
        if (deleteProduct($conn, $id_produk)) {
            $success_message = "Produk berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus produk.";
        }
    // Edit produk
    } elseif (isset($_POST['edit_product'])) {
        $id_produk = intval($_POST['id_produk']);
        $product_data = [
            'nama_produk' => $_POST['nama_produk'],
            'harga_produk' => $_POST['harga_produk'],
            'deskripsi_produk' => $_POST['deskripsi_produk'],
            'foto_produk' => $_POST['foto_produk']
        ];
        if (updateProduct($conn, $id_produk, $product_data)) {
            $success_message = "Produk berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate produk: " . mysqli_error($conn);
        }
    // Konfirmasi transaksi (untuk penjual)
    } elseif (isset($_POST['confirm_transaction'])) {
        $id_transaksi = intval($_POST['id_transaksi']);
        if (updateTransactionStatus($conn, $id_transaksi, 'Sukses')) {
            $success_message = "Transaksi berhasil dikonfirmasi!";
        } else {
            $error_message = "Gagal mengkonfirmasi transaksi.";
        }
    }
}

// Ambil data produk dan transaksi berdasarkan role
if ($role == 'penjual') {
    // Untuk penjual: ambil produk miliknya dan transaksi penjualan
    $products = getProducts($conn, $user['nomor_whatsapp']);
    $transactions = getTransactions($conn, $username, true);
} else {
    // Untuk pembeli: ambil semua produk, transaksi pembelian, dan handle pencarian
    $products = getProducts($conn);
    $transactions = getTransactions($conn, $username, false);
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    if ($search) {
        $products = getProducts($conn, null, $search);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Digital Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .dashboard-bg {
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
<body class="dashboard-bg">
    <nav class="bg-white shadow-lg p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Digital Marketplace</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-700">Halo, <?php echo htmlspecialchars($user['username']); ?> (<?php echo ucfirst($role); ?>)</span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4">
        <?php if (isset($_GET['payment_success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 fade-in">
                Pembayaran berhasil! Silakan lanjutkan ke WhatsApp untuk konfirmasi pesanan.
                <button onclick="openWhatsAppAfterPayment(<?php echo intval($_GET['open_whatsapp'] ?? 0); ?>)" class="ml-4 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Buka WhatsApp
                </button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['payment_submitted'])): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6 fade-in">
                Pembayaran telah dikirim! Menunggu konfirmasi dari penjual.
            </div>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 fade-in">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 fade-in">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Bagian Dashboard Penjual -->
        <?php if ($role == 'penjual'): ?>
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="card p-6 fade-in">
                    <h2 class="text-xl font-semibold mb-4">Produk Saya</h2>
                    <p class="text-gray-600 mb-4">Kelola produk digital Anda.</p>
                    <a href="#add-product" class="btn-primary text-white px-4 py-2 rounded-lg">Tambah Produk</a>
                </div>
                <div class="card p-6 fade-in">
                    <h2 class="text-xl font-semibold mb-4">Transaksi</h2>
                    <p class="text-gray-600 mb-4">Lihat transaksi penjualan.</p>
                    <span class="text-2xl font-bold text-green-600"><?php echo count($transactions); ?> Transaksi</span>
                </div>
            </div>

            <div id="add-product" class="card p-6 mb-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Tambah Produk Baru</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_product" value="1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                        <input type="text" name="nama_produk" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                        <input type="number" name="harga_produk" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea name="deskripsi_produk" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL Gambar</label>
                        <input type="url" name="foto_produk" required class="w-full px-3 py-2 border rounded-lg" placeholder="https://example.com/image.jpg">
                    </div>
                    <button type="submit" class="btn-success text-white px-6 py-2 rounded-lg">Tambah Produk</button>
                </form>
            </div>

            <?php
            $edit_product = null;
            if (isset($_GET['edit'])) {
                $edit_id = intval($_GET['edit']);
                foreach ($products as $product) {
                    if ($product['id_produk'] == $edit_id) {
                        $edit_product = $product;
                        break;
                    }
                }
            }
            ?>

            <?php if ($edit_product): ?>
            <div id="edit-product" class="card p-6 mb-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Edit Produk</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="edit_product" value="1">
                    <input type="hidden" name="id_produk" value="<?php echo $edit_product['id_produk']; ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Produk</label>
                        <input type="text" name="nama_produk" value="<?php echo htmlspecialchars($edit_product['nama_produk']); ?>" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Harga (Rp)</label>
                        <input type="number" name="harga_produk" value="<?php echo $edit_product['harga_produk']; ?>" required class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea name="deskripsi_produk" rows="3" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($edit_product['deskripsi_produk']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL Gambar</label>
                        <input type="url" name="foto_produk" value="<?php echo htmlspecialchars($edit_product['foto_produk']); ?>" required class="w-full px-3 py-2 border rounded-lg" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="btn-success text-white px-6 py-2 rounded-lg">Update Produk</button>
                        <a href="dashboard.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">Batal</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="card p-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Daftar Produk</h3>
                <?php if (empty($products)): ?>
                    <p class="text-gray-500">Belum ada produk. Tambahkan sekarang!</p>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($products as $product): ?>
                            <div class="border rounded-lg p-4">
                                <img src="<?php echo htmlspecialchars($product['foto_produk']); ?>" alt="<?php echo htmlspecialchars($product['nama_produk']); ?>" class="w-full h-32 object-cover rounded mb-2">
                                <h4 class="font-semibold"><?php echo htmlspecialchars($product['nama_produk']); ?></h4>
                                <p class="text-gray-600"><?php echo formatRupiah($product['harga_produk']); ?></p>
                                <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars(substr($product['deskripsi_produk'], 0, 100)); ?>...</p>
                                <div class="flex space-x-2">
                                    <a href="?edit=<?php echo $product['id_produk']; ?>#edit-product" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Edit</a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="delete_product" value="1">
                                        <input type="hidden" name="id_produk" value="<?php echo $product['id_produk']; ?>">
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600" onclick="return confirm('Hapus produk ini?')">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-6 mt-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Riwayat Transaksi</h3>
                <?php if (empty($transactions)): ?>
                    <p class="text-gray-500">Belum ada transaksi.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 text-left">Produk</th>
                                    <th class="px-4 py-2 text-left">Pembeli</th>
                                    <th class="px-4 py-2 text-left">Harga</th>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($trans['nama_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($trans['id_pembeli']); ?></td>
                                        <td class="px-4 py-2"><?php echo formatRupiah($trans['harga_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo $trans['tanggal_transaksi']; ?></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded text-sm <?php
                                                if ($trans['status'] == 'Sukses') echo 'bg-green-100 text-green-800';
                                                elseif ($trans['status'] == 'Menunggu Konfirmasi') echo 'bg-orange-100 text-orange-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                            ?>">
                                                <?php echo $trans['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <?php if ($trans['status'] == 'Pending' || $trans['status'] == 'Menunggu Konfirmasi'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="confirm_transaction" value="1">
                                                    <input type="hidden" name="id_transaksi" value="<?php echo $trans['id_transaksi']; ?>">
                                                    <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600" onclick="return confirm('Konfirmasi pembayaran ini?')">Konfirmasi</button>
                                                </form>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-6 mt-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Laporan Penjualan</h3>
                <form method="GET" class="mb-4">
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periode</label>
                            <select name="period" class="px-3 py-2 border rounded-lg">
                                <option value="daily" <?php echo (isset($_GET['period']) && $_GET['period'] == 'daily') ? 'selected' : ''; ?>>Harian</option>
                                <option value="monthly" <?php echo (isset($_GET['period']) && $_GET['period'] == 'monthly') ? 'selected' : ''; ?>>Bulanan</option>
                                <option value="yearly" <?php echo (isset($_GET['period']) && $_GET['period'] == 'yearly') ? 'selected' : ''; ?>>Tahunan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 date-label">
                                <?php
                                $selected_period = $_GET['period'] ?? 'daily';
                                if ($selected_period == 'monthly') {
                                    echo 'Bulan';
                                } elseif ($selected_period == 'yearly') {
                                    echo 'Tahun';
                                } else {
                                    echo 'Tanggal';
                                }
                                ?>
                            </label>
                            <div class="date-input-container">
                                <?php if ($selected_period == 'monthly'): ?>
                                    <input type="month" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? date('Y-m')); ?>" class="px-3 py-2 border rounded-lg">
                                <?php elseif ($selected_period == 'yearly'): ?>
                                    <input type="number" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>" min="2000" max="2100" class="px-3 py-2 border rounded-lg">
                                <?php else: ?>
                                    <input type="date" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>" class="px-3 py-2 border rounded-lg">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Tampilkan Laporan</button>
                        </div>
                    </div>
                </form>

                <?php
                $period = $_GET['period'] ?? 'daily';
                $date_param = $_GET['date'] ?? null;
                if ($period == 'monthly') {
                    $date = $date_param ? date('Y-m', strtotime($date_param)) : date('Y-m');
                } elseif ($period == 'yearly') {
                    $date = $date_param ? intval($date_param) : null;
                } else {
                    $date = $date_param ?: date('Y-m-d');
                }
                $reports = getReports($conn, $username, $period, $date);
                ?>

                <?php if (!empty($reports)): ?>
                    <div class="mb-4">
                        <a href="download_report.php?period=<?php echo $period; ?>&date=<?php echo $date; ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Download PDF</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 text-left">ID Transaksi</th>
                                    <th class="px-4 py-2 text-left">Produk</th>
                                    <th class="px-4 py-2 text-left">Pembeli</th>
                                    <th class="px-4 py-2 text-left">Harga</th>
                                    <th class="px-4 py-2 text-left">Metode Pembayaran</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                foreach ($reports as $report):
                                    $total += $report['harga_produk'];
                                ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo $report['id_transaksi']; ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($report['nama_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($report['pembeli_username']); ?></td>
                                        <td class="px-4 py-2"><?php echo formatRupiah($report['harga_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($report['metode_pembayaran']); ?></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded text-sm <?php
                                                if ($report['status'] == 'Sukses') echo 'bg-green-100 text-green-800';
                                                elseif ($report['status'] == 'Menunggu Konfirmasi') echo 'bg-orange-100 text-orange-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                            ?>">
                                                <?php echo $report['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2"><?php echo $report['tanggal_transaksi']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="mt-4 text-right">
                            <strong>Total Penjualan: <?php echo formatRupiah($total); ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">Tidak ada data laporan untuk periode yang dipilih.</p>
                <?php endif; ?>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Handle period change for report form
                    const periodSelect = document.querySelector('select[name="period"]');
                    const dateInputContainer = document.querySelector('.date-input-container');
                    const dateLabel = document.querySelector('.date-label');

                    function updateDateInput() {
                        const period = periodSelect.value;
                        const input = dateInputContainer.querySelector('input');
                        if (!input) return;

                        let newValue = input.value;

                        if (period === 'monthly') {
                            dateLabel.textContent = 'Bulan';
                            input.type = 'month';
                            input.removeAttribute('min');
                            input.removeAttribute('max');
                            if (newValue) {
                                const date = new Date(newValue);
                                if (!isNaN(date)) {
                                    newValue = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                                } else {
                                    newValue = '<?php echo date('Y-m'); ?>';
                                }
                            } else {
                                newValue = '<?php echo date('Y-m'); ?>';
                            }
                        } else if (period === 'yearly') {
                            dateLabel.textContent = 'Tahun';
                            input.type = 'number';
                            input.min = '2000';
                            input.max = '2100';
                            if (newValue) {
                                const date = new Date(newValue);
                                if (!isNaN(date)) {
                                    newValue = date.getFullYear().toString();
                                } else if (/^\d{4}$/.test(newValue)) {
                                    newValue = newValue;
                                } else {
                                    newValue = '<?php echo date('Y'); ?>';
                                }
                            } else {
                                newValue = '<?php echo date('Y'); ?>';
                            }
                        } else {
                            dateLabel.textContent = 'Tanggal';
                            input.type = 'date';
                            input.removeAttribute('min');
                            input.removeAttribute('max');
                            if (newValue) {
                                if (/^\d{4}$/.test(newValue)) {
                                    newValue = newValue + '-01-01';
                                } else if (/^\d{4}-\d{2}$/.test(newValue)) {
                                    newValue = newValue + '-01';
                                }
                            } else {
                                newValue = '';
                            }
                        }

                        input.value = newValue;
                    }

                    if (periodSelect) {
                        periodSelect.addEventListener('change', function() {
                            updateDateInput();
                            const period = periodSelect.value;
                            if (period !== 'yearly') {
                                const form = this.closest('form');
                                form.submit();
                            }
                        });
                        updateDateInput(); // Initialize on load
                    }

                    // Auto-submit form when date input changes or loses focus
                    const dateInput = dateInputContainer.querySelector('input');
                    if (dateInput) {
                        dateInput.addEventListener('change', function() {
                            if (dateInput.type !== 'number') {
                                const form = this.closest('form');
                                form.submit();
                            }
                        });
                        dateInput.addEventListener('blur', function() {
                            if (dateInput.type !== 'number') {
                                const form = this.closest('form');
                                form.submit();
                            }
                        });
                        dateInput.addEventListener('keydown', function(e) {
                            if (dateInput.type === 'number' && e.key === 'Enter') {
                                const form = this.closest('form');
                                form.submit();
                            }
                        });
                    }
                });
            </script>

        <!-- Bagian Dashboard Pembeli -->
        <?php else: ?>
            <div class="card p-6 mb-6 fade-in">
                <h2 class="text-xl font-semibold mb-4">Selamat Datang, Pembeli!</h2>
                <p class="text-gray-600 mb-4">Telusuri produk digital berkualitas.</p>
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari produk..." class="flex-1 px-3 py-2 border rounded-lg">
                    <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600">Cari</button>
                </form>
            </div>

            <div class="card p-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Produk Tersedia</h3>
                <?php if (empty($products)): ?>
                    <p class="text-gray-500">Tidak ada produk ditemukan.</p>
                <?php else: ?>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($products as $product): ?>
                            <a href="product_detail.php?id=<?php echo $product['id_produk']; ?>" class="block border rounded-lg p-4 hover:shadow-lg transition-shadow cursor-pointer">
                                <img src="<?php echo htmlspecialchars($product['foto_produk']); ?>" alt="<?php echo htmlspecialchars($product['nama_produk']); ?>" class="w-full h-48 object-cover rounded mb-3">
                                <h4 class="font-semibold mb-1"><?php echo htmlspecialchars($product['nama_produk']); ?></h4>
                                <p class="text-2xl font-bold text-green-600 mb-2"><?php echo formatRupiah($product['harga_produk']); ?></p>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($product['deskripsi_produk'], 0, 150)); ?>...</p>
                                <p class="text-xs text-gray-500">Klik untuk detail lengkap</p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card p-6 mt-6 fade-in">
                <h3 class="text-lg font-semibold mb-4">Riwayat Pembelian</h3>
                <?php if (empty($transactions)): ?>
                    <p class="text-gray-500">Belum ada riwayat pembelian.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 text-left">Produk</th>
                                    <th class="px-4 py-2 text-left">Penjual</th>
                                    <th class="px-4 py-2 text-left">Harga</th>
                                    <th class="px-4 py-2 text-left">Tanggal</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $trans): ?>
                                    <tr>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($trans['nama_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($trans['id_penjual']); ?></td>
                                        <td class="px-4 py-2"><?php echo formatRupiah($trans['harga_produk']); ?></td>
                                        <td class="px-4 py-2"><?php echo $trans['tanggal_transaksi']; ?></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded text-sm <?php
                                                if ($trans['status'] == 'Sukses') echo 'bg-green-100 text-green-800';
                                                elseif ($trans['status'] == 'Menunggu Konfirmasi') echo 'bg-orange-100 text-orange-800';
                                                else echo 'bg-yellow-100 text-yellow-800';
                                            ?>">
                                                <?php echo $trans['status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <?php if ($trans['status'] == 'Sukses'): ?>
                                                <button onclick="openWhatsAppAfterPayment(<?php echo $trans['id_transaksi']; ?>)" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">WhatsApp</button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>



    <?php if ($role != 'penjual'): ?>
    <script>
        const currentUser = '<?php echo $username; ?>';
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[id^="payment-"]');
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    const productId = this.id.split('-')[1];
                    const btn = document.getElementById('whatsapp-btn-' + productId);
                    if (this.value) {
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        btn.disabled = false;
                    } else {
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                        btn.disabled = true;
                    }
                });
            });
        });

        function initiatePurchase(productId) {
            const select = document.getElementById('payment-' + productId);
            if (!select || !select.value) {
                alert('Silakan pilih metode pembayaran terlebih dahulu.');
                return false;
            }

            const idProduk = productId;
            const idPenjual = select.dataset.seller;
            const namaProduk = select.dataset.productName;
            const hargaProduk = select.dataset.price;
            const metodePembayaran = select.value;

            // Buat transaksi Pending terlebih dahulu
            fetch('add_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_produk: idProduk,
                    id_penjual: idPenjual,
                    nama_produk: namaProduk,
                    harga_produk: hargaProduk,
                    metode_pembayaran: metodePembayaran
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect ke halaman pembayaran dengan ID transaksi
                    window.location.href = 'payment.php?id_transaksi=' + data.id_transaksi;
                } else {
                    alert('Gagal memulai transaksi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saat memulai transaksi: ' + error.message);
                console.error('Error:', error);
            });

            return false;
        }

        // Fungsi untuk membuka WhatsApp setelah pembayaran sukses
        function openWhatsAppAfterPayment(transactionId) {
            if (!transactionId) {
                alert('ID transaksi tidak valid.');
                return false;
            }

            // Ambil data lengkap transaksi dari API
            fetch('get_transaction.php?id=' + transactionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transaction = data.transaction;
                    const formattedPrice = new Intl.NumberFormat('id-ID').format(transaction.harga_produk);
                    const message = `🛒 *PESANAN PRODUK DIGITAL*\n\n📦 *Produk:* ${transaction.nama_produk}\n💰 *Harga:* Rp ${formattedPrice}\n🏪 *Penjual:* ${transaction.id_penjual}\n\n📝 *Deskripsi:*\n${transaction.deskripsi_produk}\n\nSaya ingin membeli menggunakan metode pembayaran: ${transaction.metode_pembayaran}\n\nMohon info lebih lanjut untuk proses pemesanan. Terima kasih! 🙏`;
                    const whatsappUrl = `https://wa.me/${transaction.seller_whatsapp}?text=${encodeURIComponent(message)}`;

                    window.open(whatsappUrl, '_blank');
                } else {
                    alert('Gagal mengambil data transaksi.');
                }
            })
            .catch(error => {
                alert('Error saat mengambil data transaksi: ' + error.message);
                console.error('Error:', error);
            });

            return false;
        }
    </script>
    <?php endif; ?>
</body>
</html>
