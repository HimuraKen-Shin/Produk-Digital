<?php
session_start();
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/PHPMailer-master/src/PHPMailer.php';
require 'vendor/PHPMailer-master/src/Exception.php';
require 'vendor/PHPMailer-master/src/SMTP.php';

$message = '';

// Proses registrasi jika request method POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $whatsapp_input = $_POST['whatsapp'];

    // Konversi nomor WhatsApp jika dimulai dengan 0
    if (substr($whatsapp_input, 0, 1) == '0' && strlen($whatsapp_input) >= 10) {
        $whatsapp = '62' . substr($whatsapp_input, 1);
    } else {
        $whatsapp = mysqli_real_escape_string($conn, $whatsapp_input);
    }
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = "Tidak Aktif";

    // Query untuk cek apakah username atau email sudah ada
    $check_sql = "SELECT username FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $message = "<div class='bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Username atau email sudah digunakan!</div>";
    } else {
        // Query INSERT untuk menyimpan user baru
        $sql = "INSERT INTO users (username, password, email, nomor_whatsapp, role, status) VALUES ('$username', '$password', '$email', '$whatsapp', '$role', '$status')";
        if (mysqli_query($conn, $sql)) {
            $verification_link = "http://localhost/Produk-Digital/aktivasi.php?username=" . urlencode($username) . "&email=" . urlencode($email);

            // Setup PHPMailer untuk kirim email verifikasi
            $mail = new PHPMailer(true);
            try {

                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'hkenshin7777@gmail.com';
                $mail->Password   = 'hgfnzendvnjyvrbk';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('hkenshin7777@gmail.com', 'Digital Marketplace');
                $mail->addAddress($email, $username);

                $mail->isHTML(true);
                $mail->Subject = 'Verifikasi Akun Digital Marketplace';
                $mail->Body    = "Halo $username,<br><br>Akun Anda telah dibuat. Klik link berikut untuk mengaktifkan akun Anda: <a href='$verification_link'>Aktivasi Akun</a><br><br>Terima kasih!";
                $mail->AltBody = "Halo $username, Akun Anda telah dibuat. Klik link berikut untuk mengaktifkan akun Anda: $verification_link";

                $mail->send();
                $message = "<div class='bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Registrasi berhasil! Link verifikasi telah dikirim ke email Anda. Silakan login setelah verifikasi.</div>";
            } catch (Exception $e) {
                $message = "<div class='bg-yellow-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Registrasi berhasil, tetapi email gagal dikirim. Error: " . $mail->ErrorInfo . ". Gunakan link manual: <a href='$verification_link' class='underline'>$verification_link</a></div>";
            }
        } else {
            $message = "<div class='bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg fade-in'>Registrasi gagal: " . mysqli_error($conn) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Marketplace - Register</title>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Digital Marketplace</h1>
                <p class="text-gray-600">Buat akun baru</p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nomor WhatsApp</label>
                        <input type="tel" name="whatsapp" required placeholder=""
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Sebagai</label>
                        <select name="role" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Pilih Role</option>
                            <option value="pembeli">Pembeli</option>
                            <option value="penjual">Penjual</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-blue-600 text-white py-3 px-6 rounded-lg font-semibold btn-hover">
                        Daftar Sekarang
                    </button>
                </div>
            </form>
            <p class="text-center text-gray-600 mt-6">
                Sudah punya akun? 
                <a href="login.php" class="text-purple-600 hover:text-purple-800 font-medium">Masuk di sini</a>
            </p>
        </div>
    </div>
</body>
</html>
