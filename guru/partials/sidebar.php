<style>
:root {
    --primary: #0F3A3A;
    --primary-dark: #0B2E2E;
    --primary-light: #123E44;
    --accent: #5FA8A1;
    --accent-hover: #73B9B2;
    --active-bg: rgba(255, 255, 255, 0.12);
}

.primary-gradient {
    background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
}

.sidebar {
    transition: width 0.3s ease;
    width: 260px;
}

@media (min-width: 768px) {
    .sidebar {
        width: 260px;
        flex-shrink: 0;
        position: fixed !important;
        height: 100vh;
        top: 0;
        left: 0;
        overflow-y: auto;
    }
}

@media (max-width: 767px) {
    body.overflow-hidden {
        overflow: hidden;
        width: 100vw;
        position: fixed;
        height: 100vh;
    }
}

/* Khusus Sidebar Desktop */
.sidebar .sidebar-link {
    position: relative;
    transition: all 0.2s ease-in-out;
    border-radius: 0.5rem;
}

.sidebar .sidebar-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 15%;
    height: 70%;
    width: 4px;
    background-color: var(--accent);
    border-radius: 0 4px 4px 0;
    transform: scaleY(0);
    transition: transform 0.25s ease-in-out;
}

.sidebar .sidebar-link:hover::before,
.sidebar .sidebar-link.active::before {
    transform: scaleY(1);
}

.sidebar .sidebar-link.active {
    background-color: var(--active-bg);
    color: #ffffff !important;
    font-weight: 600;
}

.submenu-container {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.25s cubic-bezier(0, 1, 0, 1);
}

.submenu-container.open {
    max-height: 500px;
    transition: max-height 0.3s ease-in-out;
}

.sidebar::-webkit-scrollbar,
#mobileMenu::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track,
#mobileMenu::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb,
#mobileMenu::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 10px;
}
</style>

<?php
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

$profiling_pages = ['hasil_tes.php', 'rekap_kelas.php'];
$programbk_pages = ['administrasibk.php', 'bimbinganklasikal.php', 'konselingindividu.php', 'konselingkelompok.php', 'bimbingankelompok.php', 'laporanbk.php', 'konsultasiortu.php', 'homevisit.php'];

if (!isset($is_profiling_active)) {
    $is_profiling_active = in_array($current_page, $profiling_pages, true);
}
if (!isset($is_programbk_active)) {
    $is_programbk_active = in_array($current_page, $programbk_pages, true);
}
?>

<header class="md:hidden flex justify-between items-center px-4 py-2.5 bg-white shadow-sm sticky top-0 z-30 border-b border-gray-200">
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg primary-gradient flex items-center justify-center shadow-sm">
            <i class="fas fa-user-tie text-white text-xs"></i>
        </div>
        <div>
            <strong class="text-xs font-bold text-gray-800 block leading-tight">Guru BK</strong>
            <p class="text-[10px] text-gray-500">SMKN 2 Banjarmasin</p>
        </div>
    </div>
    <button onclick="toggleMenu()" type="button" aria-label="Open Navigation" class="text-gray-700 p-1.5 hover:bg-gray-100 rounded-lg transition focus:outline-none">
        <i class="fas fa-bars text-lg"></i>
    </button>
</header>

<div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 backdrop-blur-xs z-40 md:hidden transition-opacity duration-300" onclick="toggleMenu()"></div>

<div id="mobileMenu" class="fixed top-0 left-0 w-[280px] max-w-[85vw] bg-white shadow-2xl z-50 md:hidden flex flex-col h-full overflow-y-auto transform -translate-x-full opacity-0 transition-all duration-300 pointer-events-none">
    
    <div class="primary-gradient text-white px-3.5 py-3 flex justify-between items-center shadow-sm sticky top-0 z-10">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center border border-white/20">
                <i class="fas fa-user-tie text-white text-xs"></i>
            </div>
            <div>
                <strong class="text-xs font-bold block leading-tight">Navigasi Utama</strong>
                <p class="text-[10px] text-teal-100/80">SMKN 2 Banjarmasin</p>
            </div>
        </div>
        <button onclick="closeMobileMenu()" type="button" aria-label="Close Navigation" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <div class="p-2 space-y-1 flex-grow text-xs">
        <a href="dashboard.php" onclick="closeMobileMenu()"
            class="flex items-center px-3 py-2 rounded-md transition <?php echo $current_page == 'dashboard.php' ? 'bg-teal-50 text-teal-900 font-bold border-l-3 border-teal-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
            <i class="fas fa-th-large w-4 text-center mr-2.5 text-teal-600 text-xs"></i>
            <span>Dashboard</span>
        </a>

        <div>
            <button type="button" onclick="toggleSubMenu('profilingMobile', 'Mobile')" 
                class="w-full flex items-center justify-between px-3 py-2 rounded-md transition <?php echo $is_profiling_active ? 'bg-teal-50 text-teal-900 font-bold border-l-3 border-teal-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <span class="flex items-center">
                    <i class="fas fa-user-graduate w-4 text-center mr-2.5 text-teal-600 text-xs"></i>
                    <span>Data & Laporan Siswa</span>
                </span>
                <i id="profilingMobileIcon" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-300 <?php echo $is_profiling_active ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="profilingMobile" class="submenu-container pl-3 ml-2 border-l border-gray-200 space-y-0.5 mt-0.5 <?php echo $is_profiling_active ? 'open' : ''; ?>">
                <a href="hasil_tes.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'hasil_tes.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-id-card w-3.5 text-center mr-2"></i> Data Hasil Persiswa
                </a>
                <a href="rekap_kelas.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'rekap_kelas.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-chalkboard w-3.5 text-center mr-2"></i> Data Hasil Perkelas
                </a>
            </div>
        </div>

        <div>
            <button type="button" onclick="toggleSubMenu('programBkMobile', 'Mobile')" 
                class="w-full flex items-center justify-between px-3 py-2 rounded-md transition <?php echo $is_programbk_active ? 'bg-teal-50 text-teal-900 font-bold border-l-3 border-teal-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <span class="flex items-center">
                    <i class="fas fa-hands-helping w-4 text-center mr-2.5 text-teal-600 text-xs"></i>
                    <span>Program BK</span>
                </span>
                <i id="programBkMobileIcon" class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-300 <?php echo $is_programbk_active ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="programBkMobile" class="submenu-container pl-3 ml-2 border-l border-gray-200 space-y-0.5 mt-0.5 <?php echo $is_programbk_active ? 'open' : ''; ?>">
                <a href="administrasibk.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'administrasibk.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-folder-open w-3.5 text-center mr-2"></i> Administrasi BK
                </a>
                <a href="bimbinganklasikal.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'bimbinganklasikal.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-chalkboard-teacher w-3.5 text-center mr-2"></i> Bimbingan Klasikal
                </a>
                <a href="konselingindividu.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'konselingindividu.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-user-shield w-3.5 text-center mr-2"></i> Konseling Individu
                </a>
                <a href="konselingkelompok.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'konselingkelompok.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-users w-3.5 text-center mr-2"></i> Konseling Kelompok
                </a>
                <a href="bimbingankelompok.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'bimbingankelompok.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-user-friends w-3.5 text-center mr-2"></i> Bimbingan Kelompok
                </a>
                <a href="laporanbk.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'laporanbk.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-file-invoice w-3.5 text-center mr-2"></i> Laporan BK
                </a>
                <a href="konsultasiortu.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'konsultasiortu.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-handshake w-3.5 text-center mr-2"></i> Konsultasi Ortu
                </a>
                <a href="homevisit.php" onclick="closeMobileMenu()" class="flex items-center px-2.5 py-1.5 text-[11px] rounded-md transition <?php echo $current_page == 'homevisit.php' ? 'bg-teal-600 text-white font-semibold shadow-xs' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    <i class="fas fa-house-user w-3.5 text-center mr-2"></i> Home Visit
                </a>
            </div>
        </div>
    </div>

    <div class="p-2 border-t border-gray-100 bg-gray-50/50 mt-auto">
        <a href="logout.php" 
            class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-2 px-3 rounded-md transition text-xs font-semibold flex items-center justify-center gap-2 shadow-xs">
            <i class="fas fa-sign-out-alt text-xs"></i> Logout
        </a>
    </div>
</div>

<aside id="sidebar" class="sidebar hidden md:flex primary-gradient shadow-2xl z-40 flex-col text-white">
    <div class="px-6 py-6 border-b border-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 bg-white/15 rounded-xl flex items-center justify-center backdrop-blur-md border border-white/20 shadow-inner">
                <i class="fas fa-user-tie text-lg text-white"></i>
            </div>
            <div>
                <strong class="text-base font-bold block leading-snug tracking-wide">Guru BK</strong>
                <span class="text-xs text-white/70 font-normal">SMKN 2 Banjarmasin</span>
            </div>
        </div>
    </div>

    <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
        <a href="dashboard.php"
            class="sidebar-link flex items-center px-4 py-3 text-sm text-gray-200 hover:bg-white/10 hover:text-white rounded-lg transition <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large mr-3 w-5 text-center text-teal-300"></i>
            <span>Dashboard</span>
        </a>

        <div>
            <button type="button" onclick="toggleSubMenu('profilingDesktop', 'Desktop')"
                class="sidebar-link w-full flex items-center justify-between px-4 py-3 text-sm text-gray-200 hover:bg-white/10 hover:text-white rounded-lg transition <?php echo $is_profiling_active ? 'active' : ''; ?>">
                <span class="flex items-center">
                    <i class="fas fa-user-graduate mr-3 w-5 text-center text-teal-300"></i>
                    <span>Data & Laporan Siswa</span>
                </span>
                <i id="profilingDesktopIcon" class="fas fa-chevron-down text-xs text-white/60 transition-transform duration-300 <?php echo $is_profiling_active ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="profilingDesktop" class="submenu-container pl-9 space-y-1 mt-1 <?php echo $is_profiling_active ? 'open' : ''; ?>">
                <a href="hasil_tes.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'hasil_tes.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-id-card mr-2.5 w-4 text-center"></i> Data Hasil Persiswa
                </a>
                <a href="rekap_kelas.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'rekap_kelas.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-chalkboard mr-2.5 w-4 text-center"></i> Data Hasil Perkelas
                </a>
            </div>
        </div>

        <div>
            <button type="button" onclick="toggleSubMenu('programBkDesktop', 'Desktop')"
                class="sidebar-link w-full flex items-center justify-between px-4 py-3 text-sm text-gray-200 hover:bg-white/10 hover:text-white rounded-lg transition <?php echo $is_programbk_active ? 'active' : ''; ?>">
                <span class="flex items-center">
                    <i class="fas fa-hands-helping mr-3 w-5 text-center text-teal-300"></i>
                    <span>Program BK</span>
                </span>
                <i id="programBkDesktopIcon" class="fas fa-chevron-down text-xs text-white/60 transition-transform duration-300 <?php echo $is_programbk_active ? 'rotate-180' : ''; ?>"></i>
            </button>
            <div id="programBkDesktop" class="submenu-container pl-9 space-y-1 mt-1 <?php echo $is_programbk_active ? 'open' : ''; ?>">
                <a href="administrasibk.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'administrasibk.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-folder-open mr-2.5 w-4 text-center"></i> Administrasi BK
                </a>
                <a href="bimbinganklasikal.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'bimbinganklasikal.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher mr-2.5 w-4 text-center"></i> Bimbingan Klasikal
                </a>
                <a href="konselingindividu.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'konselingindividu.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-user-shield mr-2.5 w-4 text-center"></i> Konseling Individu
                </a>
                <a href="konselingkelompok.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'konselingkelompok.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-users mr-2.5 w-4 text-center"></i> Konseling Kelompok
                </a>
                <a href="bimbingankelompok.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'bimbingankelompok.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-user-friends mr-2.5 w-4 text-center"></i> Bimbingan Kelompok
                </a>
                <a href="laporanbk.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'laporanbk.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-file-invoice mr-2.5 w-4 text-center"></i> Laporan BK
                </a>
                <a href="konsultasiortu.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'konsultasiortu.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-handshake mr-2.5 w-4 text-center"></i> Konsultasi
                </a>
                <a href="homevisit.php"
                    class="flex items-center px-3 py-2 text-xs text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition <?php echo $current_page == 'homevisit.php' ? 'bg-white/15 text-white font-medium' : ''; ?>">
                    <i class="fas fa-house-user mr-2.5 w-4 text-center"></i> Home Visit
                </a>
            </div>
        </div>

        <div class="mt-auto pt-4 border-t border-white/10">
            <a href="logout.php"
                class="flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-500/20 hover:text-red-100 rounded-lg transition">
                <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i> Logout
            </a>
        </div>
    </nav>
</aside>

<script>
function toggleMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('menuOverlay');
    const body = document.body;

    const isOpen = !mobileMenu.classList.contains('pointer-events-none');

    if (isOpen) {
        closeMobileMenu();
    } else {
        mobileMenu.classList.remove('pointer-events-none', '-translate-x-full', 'opacity-0');
        mobileMenu.classList.add('translate-x-0', 'opacity-100');
        overlay.classList.remove('hidden');
        body.classList.add('overflow-hidden');
    }
}

function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('menuOverlay');
    const body = document.body;

    if (mobileMenu) {
        mobileMenu.classList.add('pointer-events-none', '-translate-x-full', 'opacity-0');
        mobileMenu.classList.remove('translate-x-0', 'opacity-100');
    }
    if (overlay) overlay.classList.add('hidden');
    if (body) body.classList.remove('overflow-hidden');
}

function toggleSubMenu(menuId, mode) {
    const targetSubmenu = document.getElementById(menuId);
    const targetIcon = document.getElementById(menuId + 'Icon');
    if (!targetSubmenu) return;

    const isOpen = targetSubmenu.classList.contains('open');

    const parentContainer = mode === 'Mobile' ? document.getElementById('mobileMenu') : document.getElementById('sidebar');
    const allSubmenus = parentContainer.querySelectorAll('.submenu-container');
    const allIcons = parentContainer.querySelectorAll('.fa-chevron-down');

    allSubmenus.forEach(sub => sub.classList.remove('open'));
    allIcons.forEach(icon => icon.classList.remove('rotate-180'));

    if (!isOpen) {
        targetSubmenu.classList.add('open');
        if (targetIcon) targetIcon.classList.add('rotate-180');
    }
}

window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        closeMobileMenu();
    }
});
</script>