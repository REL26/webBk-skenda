<?php
session_start();
include '../koneksi.php';
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sistem Bimbingan Kelompok - SMKN 2 Banjarmasin" />
    <title class="no-print">Bimbingan Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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

      .modal {
        transition: opacity 0.3s ease, visibility 0.3s ease;
        visibility: hidden;
        opacity: 0;
      }

      .modal.open {
        visibility: visible;
        opacity: 1;
      }

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

      @keyframes slideIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .animate-slide-in {
        animation: slideIn 0.5s ease-out;
      }

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

      .primary-color { color: var(--primary); }
      .primary-bg { background-color: var(--primary-light); }
      .secondary-bg { background-color: #E6EEF0; }
    </style>
  </head>
  <body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

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
      <hr class="border-gray-200" />

      <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
        <div class="flex justify-between">
          <span class="flex font-medium"><i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa</span>
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
      <hr class="border-gray-200" />

      <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_program_bk_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('programBkSubmenuMobile')">
        <div class="flex justify-between">
          <span class="flex font-medium"><i class="fas fa-calendar-alt mr-2"></i> Program BK</span>
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
      <hr class="border-gray-200" />
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
              <span class="flex-item"><i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa</span>
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
              <span class="flex-item"><i class="fas fa-calendar-alt mr-2"></i> Program BK</span>
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
            <a href="bimbingankelompok.php" class="flex items-center px-4 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition duration-200 font-semibold">
              <i class="fas fa-users-cog mr-3 w-4"></i> Bimbingan Kelompok
            </a>
            <a href="laporanbk.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
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

      <main class="flex-grow p-4 md:p-8 flex flex-col">
  <div class="no-print mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
      <i class="fas fa-users-cog text-blue-600 mr-2"></i> Bimbingan Kelompok
    </h1>
    <p class="text-sm text-gray-600">Kelola kegiatan Bimbingan Kelompok</p>
  </div>

  <div class="flex-grow flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-md p-10 md:p-14 flex flex-col items-center text-center max-w-md w-full">
      <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-5">
        <i class="fas fa-code text-gray-400 text-3xl"></i>
      </div>

      <span class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 text-xs font-semibold px-4 py-1.5 rounded-full mb-4">
        <i class="fas fa-circle-info text-xs"></i> Sedang Dikembangkan
      </span>

      <h2 class="text-xl font-bold text-gray-800 mb-2">Fitur Bimbingan Kelompok</h2>
      <p class="text-sm text-gray-500 mb-6 leading-relaxed">
        Halaman ini sedang dalam tahap pengembangan aktif. Fitur Bimbingan Kelompok akan segera tersedia.
      </p>

      <p class="text-xs text-gray-400 border-t border-gray-100 pt-4 w-full">
        Hubungi tim pengembang jika ada pertanyaan lebih lanjut.
      </p>
    </div>
  </div>
</main>
    </div>

    <script>
      function toggleMenu() {
        const mobileMenu = document.getElementById("mobileMenu");
        const overlay = document.getElementById("menuOverlay");
        const body = document.body;

        if (mobileMenu.classList.contains("active-transition")) {
          mobileMenu.classList.remove("active-transition");
          overlay.classList.add("hidden");
          setTimeout(() => {
            mobileMenu.classList.add("hidden");
            body.classList.remove("overflow-hidden");
          }, 300);
        } else {
          mobileMenu.classList.remove("hidden");
          setTimeout(() => mobileMenu.classList.add("active-transition"), 10);
          overlay.classList.remove("hidden");
          body.classList.add("overflow-hidden");
        }
      }

      function toggleSubMenu(menuId) {
        const submenu = document.getElementById(menuId);
        const icon = document.getElementById(menuId + "Icon");
        if (submenu) {
          if (submenu.classList.contains("hidden")) {
            submenu.classList.remove("hidden");
            if (icon) icon.classList.replace("fa-chevron-down", "fa-chevron-up");
          } else {
            submenu.classList.add("hidden");
            if (icon) icon.classList.replace("fa-chevron-up", "fa-chevron-down");
          }
        }
      }

      document.addEventListener("DOMContentLoaded", () => {
        const overlay = document.getElementById("menuOverlay");
        if (overlay) overlay.addEventListener("click", toggleMenu);
      });
    </script>
  </body>
</html>