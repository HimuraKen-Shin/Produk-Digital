<?php
require_once 'config.php';

$message = '';
$success = false;

// Cek apakah parameter username dan email ada di URL
if (isset($_GET['username']) && isset($_GET['email'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    
    // Query untuk verifikasi apakah user ada dan statusnya 'Tidak Aktif'
    $sql = "SELECT username FROM users WHERE username = '$username' AND email = '$email' AND status = 'Tidak Aktif'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        // Query update untuk mengubah status menjadi 'Aktif'
        $update_sql = "UPDATE users SET status = 'Aktif' WHERE username = '$username' AND email = '$email'";
        if (mysqli_query($conn, $update_sql)) {
            $success = true;
            $message = "Akun Anda telah berhasil diaktifkan! Silakan <a href='login.php'>login</a> sekarang.";
        } else {
            $message = "Gagal mengaktifkan akun: " . mysqli_error($conn);
        }
    } else {
        $message = "Link verifikasi tidak valid atau akun sudah aktif.";
    }
} else {
    $message = "Parameter tidak lengkap.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - Digital Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="glass-effect rounded-2xl p-8 w-full max-w-md fade-in">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Aktivasi Akun</h1>
            <p class="text-gray-600">Verifikasi email Anda</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $message; ?>
            </div>
            <a href="register.php" class="block w-full text-center bg-blue-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-600 transition-colors">
                Kembali ke Registrasi
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
