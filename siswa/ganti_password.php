<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_siswa'];
$message = '';
$table_name = 'siswa';
$id_column = 'id_siswa';
$password_column = 'password';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    $query_get_pass = "SELECT {$password_column} FROM {$table_name} WHERE {$id_column} = ?";
    $stmt = mysqli_prepare($koneksi, $query_get_pass);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    $hashed_password_db = $user_data[$password_column];
    mysqli_stmt_close($stmt);

    if (!password_verify($password_lama, $hashed_password_db)) {
        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Password Lama Salah.</div>';
    } elseif ($password_baru !== $konfirmasi_password) {
        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Konfirmasi Password tidak cocok.</div>';
    } elseif (strlen($password_baru) < 6) {
        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-exclamation-triangle mr-2"></i> Password baru minimal 6 karakter.</div>';
    } else {
        $hashed_password_new = password_hash($password_baru, PASSWORD_DEFAULT);
        
        $query_update = "UPDATE {$table_name} SET {$password_column} = ? WHERE {$id_column} = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "si", $hashed_password_new, $id_user);

        if (mysqli_stmt_execute($stmt_update)) {
            $message = '<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-check-circle mr-2"></i> Password Anda berhasil diubah!</div>';
        } else {
            $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Gagal mengubah password: ' . mysqli_error($koneksi) . '</div>';
        }
        mysqli_stmt_close($stmt_update);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .primary-border { border-color: #2F6C6E; }
        .hover-bg-primary:hover { background-color: #1F4C4E; }
        .form-input { 
            width: 100%; 
            padding: 10px 12px; 
            border: 1px solid #D1D5DB; 
            border-radius: 8px; 
            transition: border-color 0.2s, box-shadow 0.2s; 
            background-color: #FFFFFF;
        }
        .form-input:focus {
             border-color: #2F6C6E; 
             outline: none; 
             box-shadow: 0 0 0 2px rgba(47, 108, 110, 0.5);
        }
        .fade-slide { transition: all 0.3s ease-in-out; transform-origin: top; }
        .hidden-transition { opacity: 0; transform: scaleY(0); pointer-events: none; }
        .visible-transition { opacity: 1; transform: scaleY(1); pointer-events: auto; }
    </style>
    <script>
        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            const isClosed = menu.classList.contains('hidden-transition');

            if (isClosed) {
                menu.classList.remove('hidden-transition');
                menu.classList.add('visible-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            } else {
                menu.classList.remove('visible-transition');
                menu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);
        });
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-md relative z-30">
        <div>
            <strong class="text-lg md:text-xl primary-color">Bimbingan Konseling</strong><br>
            <small class="text-xs md:text-sm text-gray-600">SMKN 2 BJM</small>
        </div>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="primary-color hover:text-green-700 transition">Beranda</a>
            <a href="data_profiling.php" class="primary-color hover:text-green-700 transition">Data Profiling</a>
            <a href="ganti_password.php" class="primary-color font-semibold hover:text-green-700 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm shadow-md">Logout</button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20"></div>

    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[60px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-left text-base">
        <a href="dashboard.php" class="py-3 px-4 primary-color hover:bg-gray-100 transition">Beranda</a>
        <hr class="border-gray-200 w-full">
        <a href="data_profiling.php" class="py-3 px-4 primary-color hover:bg-gray-100 transition">Data Profiling</a>
        <hr class="border-gray-200 w-full">
        <a href="ganti_password.php" class="py-3 px-4 primary-color font-semibold bg-gray-50 transition">Ganti Password</a>
        <hr class="border-gray-200 w-full">
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm">Logout</button>
    </div>
    
    <section class="text-center py-8 md:py-12 primary-bg text-white shadow-xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            <i class="fas fa-lock mr-2"></i> Ganti Password
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Perbarui kata sandi akun Anda secara berkala untuk keamanan.
        </p>
    </section>

    <section class="py-10 px-4 flex-grow flex items-start justify-center">
        <div class="w-full max-w-lg mx-auto bg-white mt-5 p-6 sm:p-8 rounded-xl shadow-2xl border border-gray-200 transform transition-all">

            <?php echo $message; ?>

            <form action="ganti_password.php" method="POST" class="space-y-6">
                <div>
                    <label for="password_lama" class="block text-gray-700 font-semibold mb-2 text-sm">
                        <i class="fas fa-key mr-1 text-gray-400"></i> Password Lama:
                    </label>
                    <input type="password" id="password_lama" name="password_lama" required 
                            class="form-input" placeholder="Masukkan password lama Anda">
                </div>
                <div>
                    <label for="password_baru" class="block text-gray-700 font-semibold mb-2 text-sm">
                        <i class="fas fa-unlock-alt mr-1 text-gray-400"></i> Password Baru:
                    </label>
                    <input type="password" id="password_baru" name="password_baru" required 
                            class="form-input"
                            placeholder="Minimal 6 karakter"
                            minlength="6">
                </div>
                <div>
                    <label for="konfirmasi_password" class="block text-gray-700 font-semibold mb-2 text-sm">
                        <i class="fas fa-check-circle mr-1 text-gray-400"></i> Konfirmasi Password Baru:
                    </label>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" required 
                            class="form-input" placeholder="Ulangi password baru Anda">
                </div>
                
                <button type="submit" 
                        class="w-full primary-bg text-white font-bold py-3 px-4 rounded-xl hover-bg-primary transition duration-300 shadow-lg hover:shadow-xl transform hover:scale-[1.01] flex items-center justify-center text-lg mt-8">
                    <i class="fas fa-save mr-2"></i> SIMPAN PASSWORD BARU
                </button>
            </form>
        </div>
    </section>

    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>