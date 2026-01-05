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
    <title>Dashboard Guru | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

.nav-item {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.nav-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;             
    height: 100%;
    background: #5FA8A1;     
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.nav-item:hover::before,
.nav-item.active::before {
    transform: scaleY(1);
}

        
       .primary-gradient {
    background: linear-gradient(
        180deg,
        #0F3A3A 0%,
        #123E44 100%
    );
}

        .primary-color { color: #2F6C6E; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #2F6C6E; border-radius: 10px; }

        .sidebar { transition: all 0.3s ease; width: 280px; }

        .fade-slide.hidden-transition {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .fade-slide.active-transition {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            transition: all 0.3s ease;
        }

      @media (min-width: 768px) {
            .sidebar { width: 260px; flex-shrink: 0; transform: translateX(0) !important; position: fixed !important; height: 100vh; top: 0; left: 0; overflow-y: auto; }
            .main-content { margin-left: 260px; }
        }
    </style>

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            if (mobileMenu.classList.contains('active-transition')) {
                mobileMenu.classList.remove('active-transition');
                mobileMenu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                mobileMenu.classList.remove('hidden-transition');
                mobileMenu.classList.add('active-transition');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
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
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <header class="md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg primary-gradient flex items-center justify-center shadow-md">
                <i class="fas fa-user-tie text-white"></i>
            </div>
            <div>
                <strong class="text-sm font-bold text-gray-800">Guru BK</strong>
                <p class="text-xs text-gray-500">SMKN 2 BJM</p>
            </div>
        </div>
        <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="fade-slide hidden-transition fixed top-[64px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm max-h-[calc(100vh-64px)] overflow-y-auto">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition flex items-center gap-2 <?php echo $current_page == 'dashboard.php' ? 'bg-gray-100 font-bold' : ''; ?>">
            <i class="fas fa-home w-5"></i> Dashboard
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 font-medium">
                    <i class="fas fa-user-check w-5"></i> Data & Laporan Siswa
                </span>
                <i id="profilingSubmenuMobileIcon" class="fas fa-chevron-down text-xs transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
            </div>
        </div>
        <div id="profilingSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
            <a href="hasil_tes.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition flex items-center gap-2 <?php echo $current_page == 'hasil_tes.php' ? 'text-teal-600 font-semibold' : ''; ?>">
                <i class="fas fa-list-alt w-4"></i> Data Hasil Persiswa
            </a>
            <a href="rekap_kelas.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition flex items-center gap-2 <?php echo $current_page == 'rekap_kelas.php' ? 'text-teal-600 font-semibold' : ''; ?>">
                <i class="fas fa-chart-bar w-4"></i> Data Hasil Perkelas
            </a>
        </div>
        <hr class="border-gray-200">

        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer" onclick="toggleSubMenu('programBkSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 font-medium">
                    <i class="fas fa-calendar-alt w-5"></i> Program BK
                </span>
                <i id="programBkSubmenuMobileIcon" class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
            </div>
        </div>
        <div id="programBkSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 hidden">
            <a href="konselingindividu.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition flex items-center gap-2">
                <i class="fas fa-user-friends w-4"></i> Konseling Individu
            </a>
            <a href="konselingkelompok.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition flex items-center gap-2">
                <i class="fas fa-users w-4"></i> Konseling Kelompok
            </a>
            <a href="#" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition flex items-center gap-2">
                <i class="fas fa-users w-4"></i> Bimbingan Kelompok
            </a>
        </div>
        <hr class="border-gray-200">

        <a href="logout.php" class="bg-gradient-to-r from-red-500 to-red-600 text-white py-4 hover:from-red-600 hover:to-red-700 transition text-sm font-medium flex items-center justify-center gap-2">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <div class="flex">
        
        <aside id="sidebar" class="sidebar hidden md:flex primary-gradient shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-lg">
                        <i class="fas fa-user-tie text-xl text-white"></i>
                    </div>
                    <div>
                        <strong class="text-base font-bold block">Guru BK</strong>
                        <span class="text-xs text-white/80">SMKN 2 Banjarmasin</span>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'dashboard.php' ? 'bg-white/10 text-white shadow-inner' : ''; ?>">
                    <i class="fas fa-home mr-3 w-5"></i> Dashboard
                </a>
                
                <div class="nav-item cursor-pointer" onclick="toggleSubMenu('profilingSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200 <?php echo $is_profiling_active ? 'text-white' : ''; ?>">
                        <span class="flex items-center">
                            <i class="fas fa-user-check mr-3 w-5"></i> Data & Laporan Siswa
                        </span>
                        <i id="profilingSubmenuDesktopIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
                    </div>
                </div>
                <div id="profilingSubmenuDesktop" class="pl-8 space-y-1 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
                    <a href="hasil_tes.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'hasil_tes.php' ? 'text-white font-semibold bg-white/10' : ''; ?>">
                        <i class="fas fa-list-alt mr-3 w-4"></i> Data Hasil Persiswa
                    </a>
                    <a href="rekap_kelas.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'rekap_kelas.php' ? 'text-white font-semibold bg-white/10' : ''; ?>">
                        <i class="fas fa-chart-bar mr-3 w-4"></i> Data Hasil Perkelas
                    </a>
                </div>
                
                <div class="nav-item cursor-pointer" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-calendar-alt mr-3 w-5"></i> Program BK
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
                    <a href="logout.php" class="flex items-center px-4 py-3 text-sm font-medium text-red-200 hover:bg-red-600/30 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-3 w-5"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content flex-grow min-h-screen">
            <section class="relative overflow-hidden bg-white border-b border-slate-200">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-teal-50 rounded-full blur-3xl opacity-50"></div>
                <div class="py-12 px-6 md:px-12 max-w-7xl mx-auto relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-3">Selamat Datang Kembali</span>
                            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                                Halo, <span class="primary-color"><?php echo htmlspecialchars($nama_pengguna); ?></span>!
                            </h1>
                            <p class="mt-2 text-slate-500 max-w-lg">
                                Siap membantu siswa hari ini? Pantau perkembangan siswa SMKN 2 Banjarmasin di sini.
                            </p>
                        </div>
                        <div class="hidden lg:block">
                            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" class="h-24 opacity-20 grayscale" alt="">
                        </div>
                    </div>
                </div>
            </section>

            <section class="p-6 md:p-12 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="group bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-teal-100 transition-all duration-300 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-user-graduate text-8xl"></i>
                        </div>
                        <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-address-book text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">Data Hasil Persiswa</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            Lihat profil mendalam tiap siswa, hasil tes gaya belajar, dan biodata lengkap secara individu.
                        </p>
                        <a href="hasil_tes.php" class="inline-flex items-center font-bold text-teal-700 hover:text-teal-900 transition gap-2">
                            Buka Data <i class="fas fa-arrow-right text-xs group-hover:translate-x-2 transition-transform"></i>
                        </a>
                    </div>

                    <div class="group bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-indigo-100 transition-all duration-300 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-chart-area text-8xl"></i>
                        </div>
                        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-users-rectangle text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">Data Hasil Perkelas</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            Pantau statistik kelas. Lihat gaya belajar yang dominan untuk membantu guru menyesuaikan metode ajar.
                        </p>
                        <a href="rekap_kelas.php" class="inline-flex items-center font-bold text-indigo-700 hover:text-indigo-900 transition gap-2">
                            Lihat Rekap <i class="fas fa-arrow-right text-xs group-hover:translate-x-2 transition-transform"></i>
                        </a>
                    </div>

                    <div class="group bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-2xl hover:border-amber-100 transition-all duration-300 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fas fa-comments text-8xl"></i>
                        </div>
                        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-headset text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">Konseling Individu</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            Kelola jadwal dan catatan hasil bimbingan konseling tatap muka dengan siswa secara rahasia.
                        </p>
                        <a href="konselingindividu.php" class="inline-flex items-center font-bold text-amber-700 hover:text-amber-900 transition gap-2">
                            Kelola Konseling <i class="fas fa-arrow-right text-xs group-hover:translate-x-2 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="text-center">
            <p class="text-sm text-black/70">
    &copy; 2025 <span class="font-semibold">Bimbingan Konseling SMKN 2 Banjarmasin</span>
</p>
<p class="text-xs text-gray-400 mt-1">
    Developed by <span class="font-medium">SahDu Team</span>
</p>
        </div>
    </footer>

</body>
</html>