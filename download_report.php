<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Validasi apakah user sudah login dan merupakan penjual
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'penjual') {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$username = $user['username'];

$period = $_GET['period'] ?? 'daily';
$date = $_GET['date'] ?? date('Y-m-d');
if ($period == 'monthly') {
    $date = date('Y-m', strtotime($date));
} elseif ($period == 'yearly') {
    $date = intval($date);
}

$reports = getReports($conn, $username, $period, $date);

if (!empty($reports)) {
    $pdf_content = generateReportPDF($reports, $period, $date, $username);

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="laporan_' . $period . '_' . $date . '.pdf"');
    header('Content-Length: ' . strlen($pdf_content));

    echo $pdf_content;
    exit();
} else {
    echo "Tidak ada data laporan untuk periode yang dipilih.";
}
?>
