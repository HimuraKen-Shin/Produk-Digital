<?php
require_once 'config.php';

// Query untuk membuat database jika belum ada
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Pilih database yang akan digunakan
$conn->select_db(DB_NAME);

// Query CREATE TABLE untuk tabel users
$sql = "CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(255) PRIMARY KEY,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    role ENUM('penjual', 'pembeli') NOT NULL,
    status VARCHAR(20) DEFAULT 'Tidak Aktif'
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Query CREATE TABLE untuk tabel produk
$sql = "CREATE TABLE IF NOT EXISTS produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(255) NOT NULL,
    harga_produk INT NOT NULL,
    deskripsi_produk TEXT,
    foto_produk VARCHAR(500) NOT NULL,
    id_penjual VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_penjual) REFERENCES users(username) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Produk table created successfully (simplified).<br>";
} else {
    echo "Error creating produk table: " . $conn->error . "<br>";
}

// Query CREATE TABLE untuk tabel transaksi_pembayaran
$sql = "CREATE TABLE IF NOT EXISTS transaksi_pembayaran (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    id_pembeli VARCHAR(255) NOT NULL,
    id_penjual VARCHAR(255) NOT NULL,
    nama_produk VARCHAR(255) NOT NULL,
    harga_produk INT NOT NULL,
    metode_pembayaran VARCHAR(100),
    tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE,
    FOREIGN KEY (id_pembeli) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (id_penjual) REFERENCES users(username) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Transaksi pembayaran table created successfully.<br>";
} else {
    echo "Error creating transaksi_pembayaran table: " . $conn->error . "<br>";
}

// Query CREATE TABLE untuk tabel laporan_transaksi
$sql = "CREATE TABLE IF NOT EXISTS laporan_transaksi (
    id_laporan INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    username_pembeli VARCHAR(255) NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT DEFAULT 1,
    total_harga INT NOT NULL,
    metode_pembayaran VARCHAR(100),
    status VARCHAR(50) DEFAULT 'Pending',
    waktu_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tanggal_laporan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT,
    FOREIGN KEY (id_transaksi) REFERENCES transaksi_pembayaran(id_transaksi) ON DELETE CASCADE,
    FOREIGN KEY (username_pembeli) REFERENCES users(username) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Laporan transaksi table created successfully.<br>";
} else {
    echo "Error creating laporan_transaksi table: " . $conn->error . "<br>";
}

echo "<br>Setup completed! Tables created without data insertion. Use phpMyAdmin to insert test data as needed.";
?>
