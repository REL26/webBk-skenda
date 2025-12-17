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

    $password_lama = trim($_POST['password_lama'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');
    $konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');

    $query_get_pass = "SELECT {$password_column} FROM {$table_name} WHERE {$id_column} = ?";
    $stmt = mysqli_prepare($koneksi, $query_get_pass);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        $hashed_password_db = $user_data[$password_column] ?? null;
        mysqli_stmt_close($stmt);
    } else {
         $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Error: Gagal menyiapkan query database.</div>';
         $hashed_password_db = null;
    }

    if ($hashed_password_db === null) {
        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> User tidak ditemukan atau database error.</div>';
    } elseif (!password_verify($password_lama, $hashed_password_db)) {

        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Password Lama Salah.</div>';
    } elseif ($password_baru !== $konfirmasi_password) {

        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Konfirmasi Password tidak cocok.</div>';
    } elseif (strlen($password_baru) < 6) {

        $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-exclamation-triangle mr-2"></i> Password baru minimal 6 karakter.</div>';
    } else {

        $hashed_password_new = password_hash($password_baru, PASSWORD_DEFAULT);
        
        $query_update = "UPDATE {$table_name} SET {$password_column} = ? WHERE {$id_column} = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "si", $hashed_password_new, $id_user);

            if (mysqli_stmt_execute($stmt_update)) {
                $message = '<div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-check-circle mr-2"></i> Password Anda berhasil diubah!</div>';
            } else {
                $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Gagal mengubah password: ' . mysqli_error($koneksi) . '</div>';
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $message = '<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md font-medium"><i class="fas fa-times-circle mr-2"></i> Error: Gagal menyiapkan query update.</div>';
        }
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

        .fade-slide { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); transform-origin: top; }
        .hidden-transition { opacity: 0; transform: scaleY(0); pointer-events: none; }
        .visible-transition { opacity: 1; transform: scaleY(1); pointer-events: auto; }

        .tata-cara-bg {
            background-color: #242A38; 
            color: #E5E7EB;/
        }
        .list-tata-cara li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .list-tata-cara li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #2F6C6E;
            font-size: 1.25rem;
            line-height: 1;
        }
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

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.parentNode.querySelector('.password-toggle i'); 
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const successMessage = document.querySelector('.auto-dismiss');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.transition = 'opacity 0.5s ease-out';
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.style.display = 'none', 500);
                }, 5000);
            }
        });
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="no-print flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-lg sticky top-0 z-30">
        <a href="dashboard.php" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <div>
                <strong class="text-base md:text-xl primary-color font-extrabold">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-gray-600">Bimbingan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Beranda</a>
            <a href="data_profiling.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Data Profiling</a>
            <a href="riwayatkonselingsiswa.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Riwayat</a>
            <a href="ganti_password.php" class="primary-color font-bold border-b-2 border-primary-color pb-1 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition text-sm font-semibold shadow-md">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="no-print hidden fixed inset-0 bg-black/50 z-20 transition-opacity duration-300" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="no-print fade-slide hidden-transition absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex flex-col text-left text-base divide-y divide-gray-200">
        <a href="dashboard.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 primary-color font-bold bg-gray-100 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>

    <section class="no-print text-center py-8 md:py-12 primary-bg text-white shadow-xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            <i class="fas fa-key mr-2"></i> Perbarui Kata Sandi Anda
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Perbarui kata sandi Anda untuk menjaga keamanan akun Anda.
        </p>
    </section>
    
    <section class="py-10 px-4 flex-grow flex items-start justify-center">
        <div class="w-full max-w-4xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col md:flex-row border border-gray-200">

            <div class="w-full md:w-1/2 p-6 sm:p-8">
                <h2 class="text-2xl font-extrabold primary-color mb-6 border-b-2 border-gray-200 pb-2 flex items-center">
                    <i class="fas fa-lock mr-2"></i> Perbarui Kata Sandi
                </h2>

                <?php echo $message; ?>

                <form action="ganti_password.php" method="POST" class="space-y-6">
                    
                    <div>
                        <label for="password_lama" class="block text-gray-700 font-semibold mb-2 text-sm">
                            Password Lama:
                        </label>
                        <div class="relative">
                            <input type="password" id="password_lama" name="password_lama" required 
                                    class="form-input" placeholder="Masukkan password lama Anda">
                            <span class="password-toggle absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 cursor-pointer text-gray-500 hover:text-gray-700" 
                                  onclick="togglePasswordVisibility('password_lama')">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>

                    <div>
                        <label for="password_baru" class="block text-gray-700 font-semibold mb-2 text-sm">
                            Password Baru:
                        </label>
                        <div class="relative">
                            <input type="password" id="password_baru" name="password_baru" required 
                                    class="form-input"
                                    placeholder="Minimal 6 karakter"
                                    minlength="6">
                            <span class="password-toggle absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 cursor-pointer text-gray-500 hover:text-gray-700" 
                                  onclick="togglePasswordVisibility('password_baru')">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Pastikan password baru memiliki minimal 6 karakter.</p>
                    </div>

                    <div>
                        <label for="konfirmasi_password" class="block text-gray-700 font-semibold mb-2 text-sm">
                            Konfirmasi Password Baru:
                        </label>
                        <div class="relative">
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required 
                                    class="form-input" placeholder="Ulangi password baru Anda">
                            <span class="password-toggle absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 cursor-pointer text-gray-500 hover:text-gray-700" 
                                  onclick="togglePasswordVisibility('konfirmasi_password')">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full primary-bg text-white font-bold py-3 px-4 rounded-xl hover-bg-primary transition duration-300 shadow-lg hover:shadow-xl flex items-center justify-center text-base focus:outline-none focus:ring-4 focus:ring-offset-2 primary-border">
                        <i class="fas fa-save mr-2"></i> SIMPAN PASSWORD BARU
                    </button>
                </form>
            </div>

            <div class="w-full md:w-1/2 tata-cara-bg p-6 sm:p-8 rounded-b-xl md:rounded-l-none md:rounded-r-xl shadow-inner">
                <h2 class="text-2xl font-extrabold text-white mb-6 border-b-2 border-gray-700 pb-2">
                    <i class="fas fa-info-circle mr-2"></i> Tata Cara Ganti Password
                </h2>
                <ul class="space-y-4 list-tata-cara text-gray-300 text-sm">
                    <li>Masukkan Password Lama Anda yang masih berlaku.</li>
                    <li>Masukkan Password Baru dengan panjang minimal 6 karakter.</li>
                    <li>Masukkan kembali Konfirmasi Password Baru yang harus sama persis dengan Password Baru.</li>
                    <li>Klik tombol SIMPAN PASSWORD BARU.</li>
                    <li>Jika berhasil, Anda akan melihat notifikasi sukses. Jika gagal, periksa kembali input Anda atau pastikan Anda memasukkan Password Lama dengan benar.</li>
                </ul>
                <div class="mt-8 pt-4 border-t border-gray-700">
                    <p class="text-xs text-red-400 italic">
                        *Jika Anda lupa password lama, silakan hubungi Guru BK Anda untuk proses reset password secara manual.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>
