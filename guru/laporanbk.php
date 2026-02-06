<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Konseling Kelompok - SMKN 2 Banjarmasin">
    <title>Konseling Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    
    <!-- External CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- External JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { 
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0F3A3A;
            --primary-dark: #0B2E2E;
            --primary-light: #123E44;
            --accent: #5FA8A1;
            --accent-dark: #4C8E89;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-200: #E5E7EB;
            --success: #4C8E89;
            --warning: #5FA8A1;
            --danger: #9B2C2C;
        }

        html {
            overflow-y: scroll;
            scroll-behavior: smooth;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
        }

        /* ============ TRANSITIONS ============ */
        .fade-slide {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
        }
        
        .fade-slide.active-transition {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* ============ SIDEBAR ============ */
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
        }
        
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
            height: 100%; 
            width: 4px; 
            background: var(--accent); 
            transform: scaleY(0); 
            transition: transform 0.3s ease; 
        }

        .nav-item:hover::before, 
        .nav-item.active::before { 
            transform: scaleY(1); 
        }

        .nav-item.active { 
            background-color: var(--primary-light);
        }

        .nav-item.active > div:first-child,
        .nav-item.active { 
            background-color: #3C7F81 !important; 
            color: white !important;
        }

        /* ============ MODAL ============ */
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            opacity: 0;
        }
        
        .modal.open {
            visibility: visible;
            opacity: 1;
        }

        /* ============ TABLES ============ */
        .data-table-report { 
            min-width: 800px; 
        }
        
        /* ============ CARDS & BUTTONS ============ */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }

        .btn-action {
            transition: all 0.2s ease;
        }
        
        .btn-action:hover {
            transform: scale(1.05);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(47, 108, 110, 0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }

        /* ============ ANIMATIONS ============ */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out;
        }

        /* ============ LAYOUT ============ */
        main {
            width: 100%;
            box-sizing: border-box;
            overflow-x: hidden;
            max-width: 100%;
        }

        @media (max-width: 767px) {
            main {
                margin-left: 0 !important;
                padding-left: 1rem;
                padding-right: 1rem;
                width: 100%;
                padding-top: 4.5rem;
            }

            body.overflow-hidden {
                overflow: hidden;
                width: 100vw;
                position: fixed;
                height: 100vh;
            }

            .input{
                margin-top: 20px;
            }
        }

        @media (min-width: 768px) {
            main {
                margin-left: 260px;
            }
        }

        .grid {
            width: 100%;
            box-sizing: border-box;
        }

        .grid > * {
            overflow-x: hidden;
        }

        /* ============ UTILITY ============ */
        .primary-color { color: var(--primary); }
        .primary-bg { background-color: var(--primary-light); }
        .secondary-bg { background-color: #E6EEF0; }

        /* ============ PRINT STYLES ============ */
        @media print {
            .no-print {
                display: none !important;
            }

            .logo {
                display: block !important;
            }
            
            body {
                font-family: "Times New Roman", serif !important;
                font-size: 12pt;
                margin: 0;
                background: white !important;
            }
            
            @page {
                size: A4;
                margin: 2cm;
            }
            
            main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .judul {
                display: block !important;
            }
            
            h3 {
                margin: 10px 0;
                font-size: 14pt;
            }
            
            p {
                text-align: justify;
                margin: 6px 0;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            tr {
                page-break-inside: avoid;
            }
            
            .page-break-before {
                page-break-before: always;
            }
            
            input, select {
                border: none !important;
                background: transparent !important;
                font-family: inherit;
                font-size: inherit;
            }
            
            button {
                display: none !important;
            }

            @media print {
    select {
        appearance: none !important;
        border: none !important;
        font-weight: bold !important;
    }
    input::placeholder {
        color: transparent !important;
    }
}
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <!-- ============================================ -->
    <!-- MOBILE HEADER -->
    <!-- ============================================ -->
    <header class="no-print md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg primary-bg flex items-center justify-center shadow-md">
                <i class="fas fa-user-tie text-white"></i>
            </div>
            <div class="no-print leading-tight">
                <strong class="no-print text-sm font-bold text-gray-800 block">Guru BK</strong>
                <p class="text-xs text-gray-500">SMKN 2 BJM</p>
            </div>
        </div>
        <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 hover:bg-gray-100 rounded-lg transition" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
    </header>


    <div id="menuOverlay" class="no-print hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="no-print fade-slide hidden fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Dashboard
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex justify-between">
                <span class="flex font-medium">
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

        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_program_bk_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('programBkSubmenuMobile')">
            <div class="flex justify-between">
                <span class="flex font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i> Program BK
                </span>
                <i id="programBkSubmenuMobileIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_program_bk_active ? 'fa-chevron-up' : ''; ?>"></i>
            </div>
        </div>
        <div id="programBkSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_program_bk_active ? '' : 'hidden'; ?>">
            <a href="konselingindividu.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'konselingindividu.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-user-friends mr-2"></i> Konseling Individu
            </a>
            <a href="konselingkelompok.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'konselingkelompok.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-users mr-2"></i> Konseling Kelompok
            </a>
            <a href="bimbingankelompok.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'bimbingankelompok.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-users-cog mr-2"></i> Bimbingan Kelompok
            </a>
        </div>
        <hr class="border-gray-200">
        <a href="logout.php" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-medium flex items-center justify-center">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>
    
    <div class="flex flex-grow">
        
        <aside id="sidebar" class="no-print sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                  <div class="no-print w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-lg">
                        <i class="no-print fas fa-user-tie text-xl text-white"></i>
                    </div>
                    <div>
                        <strong class="no-print text-base font-bold block">Guru BK</strong>
                        <span class="no-print text-xs text-white/80">SMKN 2 Banjarmasin</span>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                
                <div class="nav-item cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex-item">
                            <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
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

                <div class="nav-item cursor-pointer <?php echo $is_program_bk_active ? 'active' : ''; ?>" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex-item">
                            <i class="fas fa-calendar-alt mr-2"></i> Program BK
                        </span>
                        <i id="programBkSubmenuDesktopIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_program_bk_active ? 'fa-chevron-up' : ''; ?>"></i>
                    </div>
                </div>
                <div id="programBkSubmenuDesktop" class="pl-8 space-y-1 <?php echo $is_program_bk_active ? '' : 'hidden'; ?>">
                    <a href="konselingindividu.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'konselingindividu.php' ? 'text-white font-semibold' : ''; ?>">
                        <i class="fas fa-user-friends mr-3 w-4"></i> Konseling Individu
                    </a>
                    <a href="konselingkelompok.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 w-4"></i> Konseling Kelompok
                    </a>
                    <a href="bimbingankelompok.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users-cog mr-3 w-4"></i> Bimbingan Kelompok
                    </a>
                     <a href="#" class="flex items-center px-4 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition duration-200 font-semibold">
                        <i class="fas fa-clipboard-list mr-3 w-4"></i> Laporan BK
                    </a>
                </div>

                <div class="mt-auto pt-4 border-t border-white/10">
                    <a href="logout.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-600/50 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- ============================================ -->
        <!-- MAIN CONTENT AREA -->
        <!-- ============================================ -->
        <main class="flex-grow p-4 md:p-8">
            
            <!-- Page Header -->
            <div class="no-print mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-clipboard-list text-blue-600 mr-2"></i> Laporan Bulanan BK
                </h1>
                <p class="text-sm text-gray-600">Buat dan kelola laporan bulanan Bimbingan dan Konseling</p>
            </div>

            <!-- Report Form Container -->
            <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
                
                <!-- Hidden Title for Print -->
                <div class="judul hidden print:block mb-6">
                    <h3 class="text-xl font-bold mb-4">BIMBINGAN DAN KONSELING (BK)</h3>
                    <p class="text-sm mb-2">
                        Sekolah : SMK Negeri 2 Banjarmasin<br>
                        Alamat Sekolah : Jl. Brigjen Hasan Basri No. 6 Banjarmasin<br>
                        Bulan / Tahun : Januari 2026
                    </p>
                    <p class="text-sm mb-4">Disusun oleh:<br>Guru BK / Konselor</p>

                    <h3 class="text-lg font-bold mt-6 mb-2">I. PENDAHULUAN</h3>
                    <p class="text-sm text-justify mb-4">
                        Laporan Bulanan Bimbingan dan Konseling (BK) ini disusun sebagai bentuk
                        pertanggungjawaban pelaksanaan layanan BK di SMK Negeri 2 Banjarmasin selama
                        bulan Januari 2026. Laporan ini memuat kegiatan layanan BK, permasalahan peserta
                        didik, serta tindak lanjut yang telah dan akan dilakukan.
                    </p>

                    <h3 class="text-lg font-bold mb-2">II. TUJUAN</h3>
                    <ol class="text-sm mb-4 list-decimal list-inside">
                        <li>Mendokumentasikan seluruh kegiatan layanan BK yang telah dilaksanakan.</li>
                        <li>Mengetahui perkembangan dan permasalahan peserta didik.</li>
                        <li>Menjadi bahan evaluasi serta dasar penyusunan tindak lanjut layanan BK berikutnya.</li>
                    </ol>
                </div>

                <!-- Section III: Rekapitulasi Kegiatan -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="no-print fas fa-list-check text-blue-600 mr-2"></i>
                        III. REKAPITULASI KEGIATAN LAYANAN BK
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="rekapKegiatan" class="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-blue-50">
                                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Jenis Layanan</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Sasaran</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Jumlah Siswa</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Waktu</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Bentuk Kegiatan</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button onclick="tambahRekap()" class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print">
                        <i class="fas fa-plus mr-2"></i> Tambah Baris
                    </button>
                </div>

                <!-- Section IV: Rekap Permasalahan -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="no-print fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        IV. REKAP PERMASALAHAN PESERTA DIDIK
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="rekapMasalah" class="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-yellow-50">
                                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Bidang</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Permasalahan</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Jumlah Siswa</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Tindak Awal</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button onclick="tambahMasalah()" class="mt-3 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm no-print">
                        <i class="fas fa-plus mr-2"></i> Tambah Baris
                    </button>
                </div>

                <!-- Section V: Tindak Lanjut -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="no-print fas fa-tasks text-green-600 mr-2"></i>
                        V. TINDAK LANJUT
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="tindakLanjut" class="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-green-50">
                                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Permasalahan</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Layanan BK</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Tindak Lanjut</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Waktu</th>
                                    <th class="border border-gray-300 px-3 py-2 text-sm">Pihak Terkait</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button onclick="tambahTindak()" class="mt-3 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm no-print">
                        <i class="fas fa-plus mr-2"></i> Tambah Baris
                    </button>
                </div>

                <!-- Section VI: Penutup -->
                <div class="judul hidden print:block mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="no-print fas fa-flag-checkered text-gray-600 mr-2"></i>
                        VI. PENUTUP
                    </h3>
                    <p class="text-sm text-gray-700 text-justify mb-4">
                        Demikian laporan bulanan Bimbingan dan Konseling ini disusun sebagai bahan evaluasi
                        dan dokumentasi kegiatan BK di sekolah. Diharapkan laporan ini dapat menjadi dasar
                        peningkatan layanan BK pada bulan berikutnya.
                    </p>
                    <p class="text-sm text-right text-gray-700">Banjarmasin, 30 Januari 2026</p>
                </div>

                <!-- Signature Section -->
 <!-- Signature Section -->
<div class="grid grid-cols-2 gap-16 mt-16 print:mt-20 text-center">

    <!-- Koordinator BK -->
    <div>
        <p class="text-sm font-semibold mb-1">Mengetahui,</p>
        <p class="text-sm mb-20">Koordinator Bimbingan dan Konseling</p>

        <!-- INPUT (screen only) -->
        <select class="no-print w-full px-3 py-2 border rounded mb-2 text-sm"
                onchange="syncPrintText(this, 'printKoordinator')">
            <option value="">Pilih Nama Guru</option>
            <option value="Fahrunazi, S.Pd">Fahrunazi, S.Pd</option>
            <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
        </select>

        <input type="text"
               class="no-print w-full px-3 py-2 border rounded text-sm"
               placeholder="Masukkan NIP"
               oninput="document.getElementById('printNipKoordinator').textContent = this.value">

        <!-- PRINT -->
        <span id="printKoordinator" class="hidden print:block font-bold"></span>
        <div class="hidden print:block border-t border-black w-56 mx-auto mt-1"></div>
        <p class="hidden print:block text-sm mt-1">
            NIP: <span id="printNipKoordinator"></span>
        </p>
    </div>

    <!-- Guru BK -->
    <div>
        <p class="text-sm mb-1">&nbsp;</p>
        <p class="text-sm mb-20">Guru Bimbingan dan Konseling</p>

        <!-- INPUT (screen only) -->
        <select class="input no-print w-full px-3 py-2 border rounded mb-2 text-sm"
                onchange="syncPrintText(this, 'printGuruBK')">
            <option value="">Pilih Nama Guru</option>
            <option value="Fahrunazi, S.Pd">Fahrunazi, S.Pd</option>
            <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
        </select>

        <input type="text"
               class="no-print w-full px-3 py-2 border rounded text-sm"
               placeholder="Masukkan NIP"
               oninput="document.getElementById('printNipGuruBK').textContent = this.value">

        <!-- PRINT -->
        <span id="printGuruBK" class="hidden print:block font-bold"></span>
        <div class="hidden print:block border-t border-black w-56 mx-auto mt-1"></div>
        <p class="hidden print:block text-sm mt-1">
            NIP: <span id="printNipGuruBK"></span>
        </p>
    </div>

</div>


                

                <!-- Documentation Section -->
                <div class="mb-8 page-break-before mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="no-print fas fa-images text-purple-600 mr-2"></i>
                        DOKUMENTASI KEGIATAN
                    </h3>
                    <input type="file" 
                           accept="image/*" 
                           multiple 
                           onchange="previewFoto(event)" 
                           class="mb-4 text-sm border border-gray-300 rounded-lg px-3 py-2 w-full no-print">
                    <div id="dokumentasi" class="grid grid-cols-2 md:grid-cols-3 gap-4"></div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center gap-4 no-print">
                    <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-file-pdf mr-2"></i> Ekspor ke PDF
                    </button>
                    <button onclick="resetForm()" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition font-semibold">
                        <i class="fas fa-redo mr-2"></i> Reset Form
                    </button>
                </div>

            </div>



    <!-- ============================================ -->
    <!-- JAVASCRIPT -->
    <!-- ============================================ -->
    <script>
    // =============================================
    // MENU FUNCTIONS
    // =============================================
    function toggleMenu() {
        const mobileMenu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('menuOverlay');
        const body = document.body;

        if (mobileMenu.classList.contains('active-transition')) {
            mobileMenu.classList.remove('active-transition');
            overlay.classList.add('hidden');
            
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            }, 300);
        } else {
            mobileMenu.classList.remove('hidden');
            setTimeout(() => mobileMenu.classList.add('active-transition'), 10);
            overlay.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        }
        
    }
    
    function toggleSubMenu(menuId) {
        const submenu = document.getElementById(menuId);
        const icon = document.getElementById(menuId + 'Icon');
        
        if (submenu) {
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                submenu.classList.add('hidden');
                if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }
    }

    // =============================================
    // TABLE MANAGEMENT FUNCTIONS
    // =============================================
    function tambahRekap() {
        const table = document.getElementById("rekapKegiatan");
        const tbody = table.querySelector('tbody') || table;
        const row = tbody.insertRow();
        const rowNum = tbody.rows.length;
        
        row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm">${rowNum}</td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm" placeholder="Contoh: Konseling Individu">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <select class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm">
                    <option value=""></option>
                    <option>X TJKT A</option>
                    <option>X TJKT B</option>
                    <option>XI TJKT A</option>
                    <option>XI TJKT B</option>
                    <option>XII TJKT A</option>
                    <option>XII TJKT B</option>
                    <option>X RPL A</option>
                    <option>XI RPL A</option>
                    <option>XII RPL A</option>
                </select>
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="number" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm" placeholder="0">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm" placeholder="Senin, 15 Jan 2026">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm" placeholder="Tatap Muka">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-blue-500 rounded text-sm" placeholder="Keterangan">
            </td>
        `;
    }

    function tambahMasalah() {
        const table = document.getElementById("rekapMasalah");
        const tbody = table.querySelector('tbody') || table;
        const row = tbody.insertRow();
        const rowNum = tbody.rows.length;
        
        row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm">${rowNum}</td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-yellow-500 rounded text-sm" placeholder="Bidang">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-yellow-500 rounded text-sm" placeholder="Deskripsi masalah">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="number" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-yellow-500 rounded text-sm" placeholder="0">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-yellow-500 rounded text-sm" placeholder="Tindak awal yang dilakukan">
            </td>
        `;
    }

    function tambahTindak() {
        const table = document.getElementById("tindakLanjut");
        const tbody = table.querySelector('tbody') || table;
        const row = tbody.insertRow();
        const rowNum = tbody.rows.length;
        
        row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm">${rowNum}</td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-green-500 rounded text-sm" placeholder="Permasalahan">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-green-500 rounded text-sm" placeholder="Layanan BK">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-green-500 rounded text-sm" placeholder="Tindak lanjut">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-green-500 rounded text-sm" placeholder="Waktu">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" class="w-full px-2 py-1 border-0 focus:ring-2 focus:ring-green-500 rounded text-sm" placeholder="Pihak terkait">
            </td>
        `;
    }

    // =============================================
    // IMAGE PREVIEW FUNCTION
    // =============================================
    function previewFoto(event) {
        const box = document.getElementById("dokumentasi");
        box.innerHTML = "";
        
        const files = Array.from(event.target.files).slice(0, 12);
        
        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                const img = document.createElement("img");
                img.src = URL.createObjectURL(file);
                img.className = "w-full h-48 object-cover rounded-lg shadow-md hover:shadow-xl transition";
                img.onload = () => URL.revokeObjectURL(img.src);
                box.appendChild(img);
            }
        });
        
        if (files.length === 0) {
            box.innerHTML = '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
        }
    }

    // =============================================
    // RESET FORM FUNCTION
    // =============================================
    function resetForm() {
        if (confirm('Apakah Anda yakin ingin mereset semua data? Semua input akan dikosongkan.')) {
            // Reset all tables
            ['rekapKegiatan', 'rekapMasalah', 'tindakLanjut'].forEach(tableId => {
                const table = document.getElementById(tableId);
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                }
            });
            
            // Reset all select elements
            document.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Reset all input elements
            document.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                input.value = '';
            });
            
            // Reset file input and documentation preview
            const fileInput = document.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
            }
            
            const dokumentasi = document.getElementById('dokumentasi');
            if (dokumentasi) {
                dokumentasi.innerHTML = '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
            }
            
            alert('Form berhasil direset!');
        }
    }

    // =============================================
    // PAGE INITIALIZATION
    // =============================================
    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.getElementById('menuOverlay');
        if (overlay) overlay.addEventListener('click', toggleMenu);
        
        // Add animation delay to elements
        document.querySelectorAll('.animate-slide-in').forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Initialize with one row in each table
        tambahRekap();
        tambahMasalah();
        tambahTindak();
    });

    

    document.addEventListener('DOMContentLoaded', function() {
    function autoFillNip(selectId, nipId) {
        const selectEl = document.getElementById(selectId);
        const nipEl = document.getElementById(nipId);

        selectEl.addEventListener('change', function() {
            // Isi NIP berdasarkan value di option
            nipEl.value = this.value;
            
            // Tambahkan tanda visual (warna biru) agar tahu ini baru saja diklik/dipilih
            this.classList.add('text-blue-700');
            setTimeout(() => this.classList.remove('text-blue-700'), 1000);
        });
    }

    autoFillNip('pilihKoordinator', 'nipKoordinator');
    autoFillNip('pilihGuruBK', 'nipGuruBK');
});

function syncPrintText(selectEl, targetId) {
    const target = document.getElementById(targetId);
    target.textContent = selectEl.value;
}
    </script>
