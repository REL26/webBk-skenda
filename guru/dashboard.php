<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}
$id_guru = (int) $_SESSION['id_guru'];
$query_guru = mysqli_query($koneksi, "SELECT nama FROM guru WHERE id_guru = $id_guru LIMIT 1");
$guru = mysqli_fetch_assoc($query_guru);
$nama_pengguna = isset($guru['nama']) ? $guru['nama'] : '';

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Guru | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { font-family: 'Inter', sans-serif; }
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .secondary-bg { background-color: #E6EEF0; }
        
        .fade-slide.hidden-transition {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        
        .fade-slide.active-transition {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        
        @media (min-width: 768px) {
            .sidebar {
                width: 260px;
                flex-shrink: 0;
                transform: translateX(0) !important;
                position: fixed !important;
                height: 100vh;
                top: 0;
                left: 0;
                overflow-y: auto;
            }
            .content-wrapper {
                margin-left: 260px;
            }
        }
        
        .nav-item {
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #D9F0F4;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .nav-item:hover::before,
        .nav-item.active::before {
            transform: scaleY(1);
        }
        
        .nav-item.active {
            background-color: #3C7F81;
        }
    </style>

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            if (mobileMenu.classList.contains('active-transition')) {
                mobileMenu.classList.remove('active-transition');
                mobileMenu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            } else {
                mobileMenu.classList.remove('hidden-transition');
                mobileMenu.classList.add('active-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            }
        }
        
        function toggleSubMenu(menuId) {
            const submenu = document.getElementById(menuId);
            const icon = document.getElementById(menuId + 'Icon');
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                submenu.classList.add('hidden');
                if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) mobileMenu.classList.add('hidden-transition');
            
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);
            
            
            <?php if ($is_profiling_active): ?>
                document.getElementById('profilingSubmenuDesktop').classList.remove('hidden');
                document.getElementById('profilingSubmenuDesktopIcon').classList.replace('fa-chevron-down', 'fa-chevron-up');
            <?php endif; ?>
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
        <div>
            <strong class="text-base font-semibold primary-color">Guru BK</strong><br>
            <small class="text-xs text-gray-500">SMKN 2 BJM</small>
        </div>
        <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 z-40 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="fade-slide hidden-transition fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 primary-color bg-gray-100 font-medium transition">
            <i class="fas fa-home mr-2"></i> Dashbaord
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
                    <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
                </span>
                <i id="profilingSubmenuMobileIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
            </div>
        </div>
        <div id="profilingSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
            <a href="hasil_tes.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'hasil_tes.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-list-alt mr-2"></i> Data Hasil Persiswa
            </a>
            <a href="rekap_kelas.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'rekap_kelas.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-chart-bar mr-2"></i> Data Hasil Perkelas
            </a>
        </div>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer" onclick="toggleSubMenu('programBkSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i> Program BK
                </span>
                <i id="programBkSubmenuMobileIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300"></i>
            </div>
        </div>
        <div id="programBkSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 hidden">
            <a href="konselingindividu.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-user-friends mr-2"></i> Konseling Individu
            </a>
            <a href="konselingkelompok.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-users mr-2"></i> Konseling Kelompok
            </a>
            <a href="#" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-users mr-2"></i> Bimbingan Kelompok
            </a>
        </div>
        <hr class="border-gray-200">

        <a href="logout.php" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-medium flex items-center justify-center">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>

    <div class="flex flex-grow">
        
        <aside id="sidebar" class="sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-tie text-lg"></i>
                    </div>
                    <div>
                        <strong class="text-base font-semibold block">Guru BK</strong>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="nav-item active flex items-center px-4 py-3 text-sm font-medium rounded-lg transition duration-200">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                
                <div class="nav-item cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-user-check mr-3"></i> Data & Laporan Siswa
                        </span>
                        <i id="profilingSubmenuDesktopIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
                    </div>
                </div>
                <div id="profilingSubmenuDesktop" class="pl-8 space-y-1 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
                    <a href="hasil_tes.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'hasil_tes.php' ? 'text-white font-semibold' : ''; ?>">
                        <i class="fas fa-list-alt mr-3 w-4"></i> Data Hasil Persiswa
                    </a>
                    <a href="rekap_kelas.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'rekap_kelas.php' ? 'text-white font-semibold' : ''; ?>">
                        <i class="fas fa-chart-bar mr-3 w-4"></i> Data Hasil Perkelas
                    </a>
                </div>
                <div class="nav-item cursor-pointer" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-calendar-alt mr-3"></i> Program BK
                        </span>
                        <i id="programBkSubmenuDesktopIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300"></i>
                    </div>
                </div>
                <div id="programBkSubmenuDesktop" class="pl-8 space-y-1 hidden">
                    <a href="konselingindividu.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-user-friends mr-3 w-4"></i> Konseling Individu
                    </a>
                    <a href="konselingkelompok.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 w-4"></i> Konseling Kelompok
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 w-4"></i> Bimbingan Kelompok
                    </a>
                </div>

                <div class="mt-auto pt-4 border-t border-white/10">
                     <a href="logout.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-600/50 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex-grow content-wrapper md:ml-[260px] md:max-w-full mx-auto">
            
            <section class="secondary-bg border-b border-gray-200 shadow-sm">
                <div class="py-8 px-4 sm:px-6 md:py-10 md:px-8 max-w-7xl mx-auto">
                    <h1 class="text-3xl md:text-4xl font-extrabold mb-2 primary-color">
                        Beranda Guru BK
                    </h1>
                    <p class="text-gray-600 text-sm md:text-base">
                        Pantau rekap data profiling siswa SMKN 2 Banjarmasin.
                    </p>
                </div>
            </section>
            
            <section class="p-4 sm:p-6 md:p-8 max-w-7xl mx-auto">
                
                <div class="bg-white p-6 mb-4 rounded-xl shadow-lg border border-gray-200 md:hidden">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800 flex items-center">
                        <i class="fas fa-database primary-color mr-2"></i> Data Hasil Persiswa
                    </h3>
                    <p class="text-gray-600 mb-4 text-sm">
                        Lihat daftar lengkap siswa dan kelola detail hasil tes siswa dan biodata siswa.
                    </p>
                    <a href="hasil_tes.php" class="inline-flex items-center px-5 py-2.5 primary-bg text-white text-sm font-semibold rounded-lg hover:bg-[#3C7F81] transition shadow-md w-full justify-center">
                        <i class="fas fa-list-alt mr-2"></i> Lihat Data Hasil Persiswa
                    </a>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 md:hidden">
                    <h3 class="text-lg font-semibold mb-3 text-gray-800 flex items-center">
                        <i class="fas fa-chart-bar primary-color mr-2"></i> Data Hasil Perkelas
                    </h3>
                    <p class="text-gray-600 mb-4 text-sm">
                        Lihat hasil tes gaya belajar yang dominan per kelas.
                    </p>
                    <a href="rekap_kelas.php" class="inline-flex items-center px-5 py-2.5 primary-bg text-white text-sm font-semibold rounded-lg hover:bg-[#3C7F81] transition shadow-md w-full justify-center">
                        <i class="fas fa-chart-bar mr-2"></i> Lihat Data Hasil Perkelas
                    </a>
                </div>
            </section>
        </main>
    </div>

    <footer class="text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>