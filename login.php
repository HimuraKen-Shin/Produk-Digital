<?php
session_start();
require_once 'config.php';

$message = '';

// Proses login jika request method POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Query untuk mengambil data user berdasarkan username
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password menggunakan password_verify
        if (password_verify($password, $user['password'])) {

            // Cek apakah akun sudah aktif
            if ($user['status'] === 'Aktif') {

                // Set session dan redirect ke dashboard
                $_SESSION['user'] = $user;
                header('Location: dashboard.php');
                exit();
            } else {

                $message = "<div class='bg-yellow-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Akun Anda belum diverifikasi. Silakan cek email untuk link aktivasi!</div>";
            }
        } else {

            $message = "<div class='bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Username atau password salah!</div>";
        }
    } else {

        $message = "<div class='bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Username atau password salah!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Marketplace - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .auth-bg {
            background: linear-gradient(135deg, #1cafe9ff 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
    </style>
</head>
<body class="auth-bg">
    <div id="notification" class="notification">
        <?php if ($message) echo $message; ?>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="glass-effect rounded-2xl p-8 w-full max-w-md fade-in">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Digital Marketplace</h1>
                <p class="text-gray-600">Masuk ke akun Anda</p>
            </div>

            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold btn-hover">
                        Masuk
                    </button>
                </div>
            </form>
            <p class="text-center text-gray-600 mt-6">
                Belum punya akun? 
                <a href="register.php" class="text-purple-600 hover:text-purple-800 font-medium">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
