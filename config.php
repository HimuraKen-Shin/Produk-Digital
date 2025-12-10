<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_pemesanan_Produk_Digital_melalui_whatsapp');

// Membuat koneksi ke database menggunakan mysqli
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi gagal, jika ya maka hentikan script
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset ke utf8 untuk mendukung karakter Unicode
$conn->set_charset("utf8");

// Inisialisasi session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
