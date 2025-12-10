<?php
// Fungsi untuk format angka menjadi format mata uang Rupiah Indonesia
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Fungsi untuk mengambil data produk dari database dengan filter opsional
 */
function getProducts($conn, $seller_username = null, $search = '', $category = 'all') {
    // Query untuk mengambil produk dengan join ke tabel users untuk info penjual
    $sql = "SELECT p.*, u.nomor_whatsapp as seller_whatsapp, u.username as seller_username FROM produk p LEFT JOIN users u ON p.nomor_whatsapp_penjual = u.nomor_whatsapp WHERE 1=1";
    if ($seller_username) {
        $sql .= " AND p.nomor_whatsapp_penjual = '" . mysqli_real_escape_string($conn, $seller_username) . "'";
    }
    if ($search) {
        $sql .= " AND p.nama_produk LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
    }
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Fungsi untuk mengambil data transaksi berdasarkan username dan role (penjual/pembeli)
 */
function getTransactions($conn, $username, $is_seller = true) {
    if ($is_seller) {
        // Query untuk transaksi penjualan
        $sql = "SELECT * FROM transaksi_pembayaran WHERE id_penjual = '" . mysqli_real_escape_string($conn, $username) . "'";
    } else {
        // Query untuk transaksi pembelian
        $sql = "SELECT * FROM transaksi_pembayaran WHERE id_pembeli = '" . mysqli_real_escape_string($conn, $username) . "'";
    }
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Fungsi untuk menambahkan produk baru ke database
 */
function addProduct($conn, $data) {
    $nama_produk = mysqli_real_escape_string($conn, $data['nama_produk']);
    // Hapus titik dari harga untuk format Indonesia (3.600.000 -> 3600000)
    $harga_clean = str_replace('.', '', $data['harga_produk']);
    $harga_produk = intval($harga_clean);
    $deskripsi_produk = mysqli_real_escape_string($conn, $data['deskripsi_produk']);
    $foto_produk = mysqli_real_escape_string($conn, $data['foto_produk']);
    $nomor_whatsapp_penjual = mysqli_real_escape_string($conn, $data['id_penjual']);

    // Query INSERT untuk menyimpan produk baru
    $sql = "INSERT INTO produk (nama_produk, harga_produk, deskripsi_produk, foto_produk, nomor_whatsapp_penjual)
            VALUES ('$nama_produk', $harga_produk, '$deskripsi_produk', '$foto_produk', '$nomor_whatsapp_penjual')";

    return mysqli_query($conn, $sql);
}

/**
 * Fungsi untuk menghapus produk berdasarkan ID
 */
function deleteProduct($conn, $id_produk) {
    // Query DELETE untuk menghapus produk
    $sql = "DELETE FROM produk WHERE id_produk = " . intval($id_produk);
    return mysqli_query($conn, $sql);
}

// Fungsi untuk mengupdate produk berdasarkan ID
function updateProduct($conn, $id_produk, $data) {
    $id_produk = intval($id_produk);
    $nama_produk = mysqli_real_escape_string($conn, $data['nama_produk']);
    // Hapus titik dari harga untuk format Indonesia (3.600.000 -> 3600000)
    $harga_clean = str_replace('.', '', $data['harga_produk']);
    $harga_produk = intval($harga_clean);
    $deskripsi_produk = mysqli_real_escape_string($conn, $data['deskripsi_produk']);
    $foto_produk = mysqli_real_escape_string($conn, $data['foto_produk']);

    // Query UPDATE untuk mengubah data produk
    $sql = "UPDATE produk SET nama_produk = '$nama_produk', harga_produk = $harga_produk, deskripsi_produk = '$deskripsi_produk', foto_produk = '$foto_produk' WHERE id_produk = $id_produk";

    return mysqli_query($conn, $sql);
}

// Fungsi untuk menambahkan laporan transaksi
function addReport($conn, $id_transaksi, $username_pembeli, $id_produk, $total_harga, $metode_pembayaran, $status, $keterangan = '') {
    $id_transaksi = intval($id_transaksi);
    $username_pembeli = mysqli_real_escape_string($conn, $username_pembeli);
    $id_produk = intval($id_produk);
    $total_harga = intval($total_harga);
    $metode_pembayaran = mysqli_real_escape_string($conn, $metode_pembayaran);
    $status = mysqli_real_escape_string($conn, $status);
    $keterangan = mysqli_real_escape_string($conn, $keterangan);

    $sql = "INSERT INTO laporan_transaksi (id_transaksi, username_pembeli, id_produk, jumlah, total_harga, metode_pembayaran, status, waktu_transaksi, tanggal_laporan, keterangan)
            VALUES ($id_transaksi, '$username_pembeli', $id_produk, 1, $total_harga, '$metode_pembayaran', '$status', NOW(), NOW(), '$keterangan')";

    return mysqli_query($conn, $sql);
}

// Fungsi untuk menambahkan transaksi pembelian baru
function addTransaction($conn, $data) {
    $id_produk = intval($data['id_produk']);
    $id_pembeli = mysqli_real_escape_string($conn, $data['id_pembeli']);
    $id_penjual = mysqli_real_escape_string($conn, $data['id_penjual']);
    $nama_produk = mysqli_real_escape_string($conn, $data['nama_produk']);
    $harga_produk = intval($data['harga_produk']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $data['metode_pembayaran']);
    $status = "Pending";

    // Query INSERT untuk menyimpan transaksi baru dengan status Pending
    $sql = "INSERT INTO transaksi_pembayaran (id_produk, id_pembeli, id_penjual, nama_produk, harga_produk, metode_pembayaran, status, tanggal_transaksi)
            VALUES ($id_produk, '$id_pembeli', '$id_penjual', '$nama_produk', $harga_produk, '$metode_pembayaran', '$status', NOW())";

    if (mysqli_query($conn, $sql)) {
        $new_transaction_id = mysqli_insert_id($conn);
        // Tambahkan laporan transaksi
        addReport($conn, $new_transaction_id, $id_pembeli, $id_produk, $harga_produk, $metode_pembayaran, $status, 'Transaksi baru dibuat');
        return true;
    }
    return false;
}

// Fungsi untuk mengupdate status transaksi
function updateTransactionStatus($conn, $id_transaksi, $new_status) {
    $id_transaksi = intval($id_transaksi);
    $new_status = mysqli_real_escape_string($conn, $new_status);

    // Query UPDATE untuk mengubah status transaksi
    $sql = "UPDATE transaksi_pembayaran SET status = '$new_status' WHERE id_transaksi = $id_transaksi";

    if (mysqli_query($conn, $sql)) {
        // Update juga status di laporan_transaksi
        $update_report_sql = "UPDATE laporan_transaksi SET status = '$new_status', keterangan = CONCAT(keterangan, ' - Status diupdate ke $new_status pada ', NOW()) WHERE id_transaksi = $id_transaksi";
        mysqli_query($conn, $update_report_sql);
        return true;
    }
    return false;
}

// Fungsi untuk mendapatkan data transaksi berdasarkan ID
function getTransactionById($conn, $id_transaksi) {
    $id_transaksi = intval($id_transaksi);
    $sql = "SELECT * FROM transaksi_pembayaran WHERE id_transaksi = $id_transaksi";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Fungsi untuk mendapatkan laporan transaksi berdasarkan periode (harian, bulanan, tahunan) untuk penjual
function getReports($conn, $seller_username, $period = 'daily', $date = null) {
    $seller_username = mysqli_real_escape_string($conn, $seller_username);
    $base_sql = "SELECT t.*, u.username as pembeli_username FROM transaksi_pembayaran t LEFT JOIN users u ON t.id_pembeli = u.username WHERE t.id_penjual = '$seller_username'";

    if ($period == 'yearly' && !$date) {
        return [];
    }

    if ($date) {
        $date = mysqli_real_escape_string($conn, $date);
        switch ($period) {
            case 'daily':
                $base_sql .= " AND DATE(t.tanggal_transaksi) = '$date'";
                break;
            case 'monthly':
                $base_sql .= " AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = '$date'";
                break;
            case 'yearly':
                $base_sql .= " AND YEAR(t.tanggal_transaksi) = '$date'";
                break;
        }
    }

    $result = mysqli_query($conn, $base_sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Fungsi untuk menghasilkan PDF laporan
function generateReportPDF($data, $period, $date, $seller_username) {
    require_once 'vendor/autoload.php';
    $pdf = new \FPDF('L'); // Landscape orientation

    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 15, 'Produk Digital', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Laporan Transaksi Penjualan', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Penjual: ' . $seller_username, 0, 1);
    $pdf->Cell(0, 10, 'Periode: ' . ucfirst($period) . ' - ' . $date, 0, 1);
    $pdf->Ln(10);

    // Header tabel
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 10, 'NO', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Produk', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Pembeli', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Harga', 1, 0, 'C');
    $pdf->Cell(50, 10, 'Metode Pembayaran', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Status', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Tanggal', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 9);
    $total = 0;
    $counter = 1;
    foreach ($data as $row) {
        $start_y = $pdf->GetY();

        // Kolom ID
        $pdf->Cell(20, 8, $counter, 1, 0, 'C');

        // Kolom Produk - potong jika terlalu panjang
        $produk_text = substr($row['nama_produk'], 0, 20);
        $pdf->Cell(40, 8, $produk_text, 1, 0, 'L');

        // Kolom Pembeli - potong jika terlalu panjang
        $pembeli_text = substr($row['pembeli_username'], 0, 15);
        $pdf->Cell(30, 8, $pembeli_text, 1, 0, 'C');

        // Kolom Harga
        $pdf->Cell(30, 8, formatRupiah($row['harga_produk']), 1, 0, 'C');

        // Kolom Metode - potong jika terlalu panjang, tambahkan "..." jika terpotong
        $metode_text = $row['metode_pembayaran'];
        if (strlen($metode_text) > 35) {
            $metode_text = substr($metode_text, 0, 32) . '...';
        }
        $pdf->Cell(50, 8, $metode_text, 1, 0, 'L');

        // Kolom Status
        $pdf->Cell(25, 8, $row['status'], 1, 0, 'C');

        // Kolom Tanggal
        $pdf->Cell(35, 8, $row['tanggal_transaksi'], 1, 1, 'C');

        $total += $row['harga_produk'];
        $counter++;
    }

    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Total Penjualan: ' . formatRupiah($total), 0, 1);

    return $pdf->Output('S');
}
?>
