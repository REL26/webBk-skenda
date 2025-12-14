<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';

$filter_nama    = isset($_GET['nama'])      ? mysqli_real_escape_string($koneksi, trim($_GET['nama']))      : '';
$filter_kelas   = isset($_GET['kelas'])     ? mysqli_real_escape_string($koneksi, trim($_GET['kelas']))     : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, trim($_GET['jurusan'])) : '';
$filter_tahun   = isset($_GET['tahun'])   ? mysqli_real_escape_string($koneksi, trim($_GET['tahun']))   : '';
$filter_nis     = isset($_GET['nis'])     ? mysqli_real_escape_string($koneksi, trim($_GET['nis']))     : '';
$filter_gb_status = isset($_GET['gb_status']) ? mysqli_real_escape_string($koneksi, trim($_GET['gb_status'])) : '';
$filter_kc_status = isset($_GET['kc_status']) ? mysqli_real_escape_string($koneksi, trim($_GET['kc_status'])) : '';
$filter_gender  = isset($_GET['gender'])  ? mysqli_real_escape_string($koneksi, trim($_GET['gender']))  : '';

$where_clauses = [];
$where_clauses[] = "s.kelas != 'LULUS'";
if (!empty($filter_nama))    $where_clauses[] = "s.nama LIKE '%$filter_nama%'";
if (!empty($filter_kelas))   $where_clauses[] = "s.kelas = '$filter_kelas'";
if (!empty($filter_jurusan)) $where_clauses[] = "s.jurusan = '$filter_jurusan'";
if (!empty($filter_nis))     $where_clauses[] = "s.nis = '$filter_nis'";
if (!empty($filter_tahun))   $where_clauses[] = "t.id_tahun = '$filter_tahun'";
if (!empty($filter_gender))  $where_clauses[] = "s.jenis_kelamin = '$filter_gender'";

if ($filter_gb_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_gayabelajar hg WHERE hg.id_siswa = s.id_siswa)";
} elseif ($filter_gb_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_gayabelajar hg WHERE hg.id_siswa = s.id_siswa)";
}

if ($filter_kc_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_kecerdasan hk WHERE hk.id_siswa = s.id_siswa)";
} elseif ($filter_kc_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_kecerdasan hk WHERE hk.id_siswa = s.id_siswa)";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$limit_desktop = 40;
$limit_mobile = 15;

$view_mode = isset($_GET['view']) ? mysqli_real_escape_string($koneksi, trim($_GET['view'])) : 'desktop';
if ($view_mode !== 'mobile') $view_mode = 'desktop';

$limit = ($view_mode === 'mobile') ? $limit_mobile : $limit_desktop;

$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_count = "
    SELECT 
        COUNT(s.id_siswa) AS total_records
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    " . $where_sql;
    
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total_records'];
$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} elseif ($total_pages == 0) {
    $page = 1;
    $offset = 0;
}

function build_pagination_url($page_num, $view_mode, $filters) {
    $params = $filters;
    $params['page'] = $page_num;
    $params['view'] = $view_mode;
    $params = array_filter($params, function($value) { return $value !== ''; });
    return 'hasil_tes.php?' . http_build_query($params);
}

$current_filters = $_GET;
unset($current_filters['page']);
unset($current_filters['view']);


$mi_mapping = [
    'A' => 'Linguistik', 'B' => 'Logis-Matematis', 'C' => 'Spasial', 'D' => 'Kinestetik-Jasmani',
    'E' => 'Musikal', 'F' => 'Interpersonal', 'G' => 'Intrapersonal', 'H' => 'Naturalis', ''  => 'Belum Tes'
];

$query_siswa = "
    SELECT 
        s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan, s.jenis_kelamin, t.tahun AS tahun_ajaran, 
        (
            SELECT 
                CASE
                    WHEN skor_visual >= skor_auditori AND skor_visual >= skor_kinestetik THEN 'Visual'
                    WHEN skor_auditori >= skor_visual AND skor_auditori >= skor_kinestetik THEN 'Auditori'
                    ELSE 'Kinestetik'
                END 
            FROM hasil_gayabelajar 
            WHERE id_siswa = s.id_siswa 
            ORDER BY tanggal_tes DESC LIMIT 1
        ) AS skor_gb_latest,
        (
            SELECT 
                CASE 
                    WHEN skor_A = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'A'
                    WHEN skor_B = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'B'
                    WHEN skor_C = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'C'
                    WHEN skor_D = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'D'
                    WHEN skor_E = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'E'
                    WHEN skor_F = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'F'
                    WHEN skor_G = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'G'
                    WHEN skor_H = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'H'
                    ELSE '' 
                END 
            FROM hasil_kecerdasan 
            WHERE id_siswa = s.id_siswa 
            ORDER BY tanggal_tes DESC LIMIT 1
        ) AS skor_kc_latest
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    " . $where_sql . " 
    ORDER BY s.nama ASC
    LIMIT $limit OFFSET $offset
";

$result_siswa = mysqli_query($koneksi, $query_siswa);

if (!$result_siswa) {
    die("Query Error: " . mysqli_error($koneksi));
}

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != 'LULUS' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
$kelas_options = mysqli_fetch_all($result_kelas, MYSQLI_ASSOC);
$kelas_options = array_column($kelas_options, 'kelas');

$query_jurusan = "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL ORDER BY jurusan";
$result_jurusan = mysqli_query($koneksi, $query_jurusan);
$jurusan_options = mysqli_fetch_all($result_jurusan, MYSQLI_ASSOC);
$jurusan_options = array_column($jurusan_options, 'jurusan');

$query_tahun = "SELECT id_tahun, tahun FROM tahun_ajaran ORDER BY tahun DESC";
$result_tahun = mysqli_query($koneksi, $query_tahun);
$data_tahun = mysqli_fetch_all($result_tahun, MYSQLI_ASSOC);

$data_siswa = mysqli_fetch_all($result_siswa, MYSQLI_ASSOC);
mysqli_data_seek($result_siswa, 0); 

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Hasil Persiswa | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { font-family: 'Inter', sans-serif; }
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .secondary-bg { background-color: #E6EEF0; }
        
        .fade-slide.hidden-transition { opacity: 0; transform: translateY(-20px); pointer-events: none; transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; }
        .fade-slide.active-transition { opacity: 1; transform: translateY(0); pointer-events: auto; transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out; }
        
        @media (min-width: 768px) {
            .sidebar { width: 260px; flex-shrink: 0; transform: translateX(0) !important; position: fixed !important; height: 100vh; top: 0; left: 0; overflow-y: auto; }
            .content-wrapper { margin-left: 260px; }
        }
        
        .nav-item { position: relative; overflow: hidden; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: #D9F0F4; transform: scaleY(0); transition: transform 0.3s ease; }
        .nav-item:hover::before, .nav-item.active::before { transform: scaleY(1); }
        .nav-item.active { background-color: #3C7F81; }
        
        .modal-content-wrapper { max-height: 90vh; overflow-y: auto; }
        
        @media (max-width: 767px) {
            body {
                overflow-x: hidden;
            }
            .student-card { 
                padding: 1rem; 
                background: white; 
                border-radius: 0.75rem; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
                border: 1px solid #e0e0e0;
                width: 100%;
                box-sizing: border-box;
                overflow-wrap: break-word;
            }
            .table-container { 
                display: none; 
            }
            .card-container { 
                display: block; 
                padding: 0;
                width: 100%;
            }
            .modal-content-wrapper {
                width: 95vw !important;
                max-width: 95vw !important;
                margin: 0 auto !important;
                border-radius: 0.75rem !important;
                max-height: 90vh;
            }
            main {
                padding: 1rem !important;
                width: 100%;
                max-width: 100vw;
                box-sizing: border-box;
            }
        }
        
        @media (min-width: 768px) {
            .table-container { 
                display: block; 
            }
            .card-container { 
                display: none; 
            }
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

        function buildResultCards(idSiswa, statusKc, statusGb) {
            
            const getTestStatus = (isDone) => {
                const isDoneBool = isDone == 1;
                return {
                    text: isDoneBool ? 'Sudah Tes' : 'Belum Tes',
                    bg: isDoneBool ? 'bg-green-100' : 'bg-red-100',
                    textCol: isDoneBool ? 'text-green-700' : 'text-red-700',
                    icon: isDoneBool ? 'fas fa-check-circle' : 'fas fa-times-circle'
                };
            };

            const status_kc = getTestStatus(statusKc);
            const status_gb = getTestStatus(statusGb);

            const menuItems = {
                biodata: {
                    title: 'Biodata',
                    icon: 'fas fa-address-card',
                    color: 'primary-color',
                    border: 'border-[#2F6C6E]',
                    url: `detail_biodata.php?id_siswa=${idSiswa}`,
                    description: 'Lihat biodata Siswa dan Export CV',
                    terkunci: false
                },
                kemampuan: {
                    title: 'Tes Kemampuan',
                    icon: 'fas fa-brain',
                    color: 'green-600',
                    border: 'border-[#2F6C6E]',
                    url: statusKc == 1 ? `detail_hasil_kemampuan.php?id_siswa=${idSiswa}&type=kecerdasan` : '#',
                    description: 'Lihat laporan resmi Kemampuan dan Export PDF',
                    status: status_kc,
                    terkunci: statusKc == 0 
                },
                gayabelajar: {
                    title: 'Tes Gaya Belajar',
                    icon: 'fas fa-lightbulb',
                    color: 'yellow-600',
                    border: 'border-[#2F6C6E]',
                    url: statusGb == 1 ? `detail_hasil_gayabelajar.php?id_siswa=${idSiswa}&type=gayabelajar` : '#',
                    description: 'Lihat laporan resmi Gaya Belajar dan Export PDF',
                    status: status_gb,
                    terkunci: statusGb == 0
                },
                kepribadian: {
                    title: 'Tes Kepribadian (Terkunci)',
                    icon: 'fas fa-lock',
                    color: 'gray-700',
                    border: 'border-[#2F6C6E]',
                    url: '#',
                    description: 'Data hasil tes belum tersedia',
                    terkunci: true
                },
                asesmen: {
                    title: 'Asesmen Awal BK (Terkunci)',
                    icon: 'fas fa-lock',
                    color: 'gray-700',
                    border: 'border-[#2F6C6E]',
                    url: '#',
                    description: 'Data asesmen awal belum tersedia',
                    terkunci: true
                }
            };
            
            const cardsContainer = document.getElementById('resultCardsContainer');
            cardsContainer.innerHTML = ''; 

            Object.keys(menuItems).forEach(key => {
                const item = menuItems[key];
                const lockedClass = item.terkunci ? 'opacity-60 cursor-not-allowed' : 'hover:shadow-xl hover:bg-gray-50 transition duration-300 cursor-pointer';
                const linkStart = item.terkunci ? '<div' : `<a href="${item.url}"`;
                const linkEnd = item.terkunci ? '</div>' : '</a>';

                const statusBadge = item.status ? 
                    `<span class="text-xs font-medium ${item.status.bg} ${item.status.textCol} px-2 py-0.5 rounded-full ml-2 flex items-center">
                        <i class="${item.status.icon} text-xs mr-1"></i> ${item.status.text}
                    </span>` : '';

                const card = `
                    ${linkStart} class="bg-white p-4 rounded-xl shadow-md border-t-4 ${item.border} ${lockedClass}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <i class="${item.icon} text-2xl text-${item.color} mr-3"></i>
                                <h4 class="text-sm md:text-base font-semibold text-gray-800">${item.title}</h4>
                            </div>
                            ${statusBadge}
                        </div>
                        <p class="text-xs text-gray-500 mt-2">${item.description}</p>
                    ${linkEnd}
                `;
                cardsContainer.innerHTML += card;
            });
        }

        function showResultModal(idSiswa, nama, nis, kelas, jurusan, isKecerdasanDone = 0, isGayaBelajarDone = 0) {
            document.getElementById('modalTitle').textContent = nama;
            document.getElementById('modalSubtitle').textContent = 'NIS: ' + nis + ' | ' + kelas + ' ' + jurusan;
            
            buildResultCards(idSiswa, isKecerdasanDone, isGayaBelajarDone); 

            document.getElementById('resultModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function hideResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function checkScreenAndSetView() {
            const params = new URLSearchParams(window.location.search);
            const currentView = params.get('view');
            const isMobile = window.matchMedia('(max-width: 767px)').matches;

            let requiredView = isMobile ? 'mobile' : 'desktop';

            if (currentView !== requiredView) {
                params.set('view', requiredView);
                window.location.replace('?' + params.toString());
            }
        }
        
        function confirmExport(url, total) {
            if (total == 0) {
                 alert("Tidak ada siswa yang ditemukan untuk di-export.");
                 return false;
            }
            const konfirmasi = confirm(`Yakin ingin meng-export biodata dari ${total} siswa yang terfilter? Proses ini akan menghasilkan file ZIP dan mungkin memakan waktu.`);
            
            if (konfirmasi) {
                window.location.href = url;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) mobileMenu.classList.add('hidden-transition');
            
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);

            const modalOverlay = document.getElementById('modalOverlay');
            if (modalOverlay) modalOverlay.addEventListener('click', hideResultModal);

            <?php if ($is_profiling_active): ?>
                document.getElementById('profilingSubmenuDesktop').classList.remove('hidden');
                document.getElementById('profilingSubmenuDesktopIcon').classList.replace('fa-chevron-down', 'fa-chevron-up');
            <?php endif; ?>
            
            checkScreenAndSetView();
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
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Beranda
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
                <a href="dashboard.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
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
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
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

       <main class="flex-grow p-4 sm:p-6 lg:p-8 content-wrapper max-w-full">
    <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-clipboard-list primary-color mr-3"></i> Data Hasil Per Siswa
    </h2>
    <div class="bg-white p-4 rounded-xl shadow-lg mb-6 border border-gray-200">
        <form method="GET" action="hasil_tes.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">

            <div> 
                <label for="nama" class="block text-sm font-medium text-gray-700">Nama Siswa:</label>
                <input type="text" id="nama" name="nama" placeholder="Cari berdasarkan nama..." value="<?php echo htmlspecialchars($filter_nama); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
            </div>
    
            <div>
                <label for="nis" class="block text-sm font-medium text-gray-700">NIS:</label>
                <input type="text" id="nis" name="nis" placeholder="Cari NIS..." value="<?php echo htmlspecialchars($filter_nis); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
            </div>
    
            <div>
                <label for="kelas" class="block text-sm font-medium text-gray-700">Kelas:</label>
                <select id="kelas" name="kelas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_options as $k): ?>
                        <option value="<?php echo $k; ?>" <?php echo ($filter_kelas == $k) ? 'selected' : ''; ?>>Kelas <?php echo $k; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
    
            <div>
                <label for="jurusan" class="block text-sm font-medium text-gray-700">Jurusan:</label>
                <select id="jurusan" name="jurusan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Jurusan</option>
                    <?php foreach ($jurusan_options as $j): ?>
                        <option value="<?php echo $j; ?>" <?php echo ($filter_jurusan == $j) ? 'selected' : ''; ?>><?php echo $j; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
    
            <div>
                <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun Ajaran:</label>
                <select id="tahun" name="tahun" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($data_tahun as $tahun): ?>
                        <option value="<?php echo $tahun['id_tahun']; ?>" <?php echo ($filter_tahun == $tahun['id_tahun']) ? 'selected' : ''; ?>><?php echo $tahun['tahun']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700">Gender:</label>
                <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Gender</option>
                    <option value="L" <?php echo ($filter_gender == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?php echo ($filter_gender == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
    
            <div>
                <label for="gb_status" class="block text-sm font-medium text-gray-700">Gaya Belajar:</label>
                <select id="gb_status" name="gb_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Status</option>
                    <option value="done" <?php echo ($filter_gb_status == 'done') ? 'selected' : ''; ?>>Sudah Tes</option>
                    <option value="not_done" <?php echo ($filter_gb_status == 'not_done') ? 'selected' : ''; ?>>Belum Tes</option>
                </select>
            </div>
    
            <div class="lg:col-span-1">
                <label for="kc_status" class="block text-sm font-medium text-gray-700">Tes Kecerdasan:</label>
                <select id="kc_status" name="kc_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                    <option value="">Semua Status</option>
                    <option value="done" <?php echo ($filter_kc_status == 'done') ? 'selected' : ''; ?>>Sudah Tes</option>
                    <option value="not_done" <?php echo ($filter_kc_status == 'not_done') ? 'selected' : ''; ?>>Belum Tes</option>
                </select>
            </div>
    
            <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row gap-2 pt-2">
                <button type="submit" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm">
                    <i class="fas fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a href="hasil_tes.php?view=<?php echo htmlspecialchars($view_mode); ?>" class="w-full sm:w-auto bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm">
                    <i class="fas fa-undo mr-2"></i> Reset
                </a>
                
                <?php
                $export_url = 'exportsekaligus_cv.php?' . http_build_query(array_merge($current_filters, ['action' => 'export_all_cv']));
                ?>
                <a href="#" 
                   onclick="confirmExport('<?php echo htmlspecialchars($export_url); ?>', '<?php echo $total_records; ?>')"
                   class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm"
                   title="Mengunduh semua biodata siswa yang tampil (<?php echo $total_records; ?> Siswa) sesuai filter sebagai file ZIP.">
                    <i class="fas fa-file-export mr-2"></i> Export Semua Biodata (<?php echo $total_records; ?>)
                </a>
                </div>
        </form>
    </div>

    <div class="table-container bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-100 sticky top-0">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-[50px] border border-gray-200">No</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[200px] border border-gray-200">NIS & Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[150px] border border-gray-200">Kelas & Jurusan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[120px] border border-gray-200">Tahun Ajaran</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider w-[150px] border border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $no = 1;
                    if (mysqli_num_rows($result_siswa) > 0) :
                        while ($siswa = mysqli_fetch_assoc($result_siswa)) :
                            $is_gb_done = !empty($siswa['skor_gb_latest']);
                            $is_kc_done = !empty($siswa['skor_kc_latest']);
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 text-center border border-gray-200"><?php echo $offset + $no++; ?></td>
                        <td class="px-6 py-3 whitespace-nowrap border border-gray-200">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($siswa['nama']); ?></div>
                            <div class="text-xs text-gray-500">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></div>
                            <div class="text-xs text-gray-500 italic"><?php echo htmlspecialchars($siswa['jenis_kelamin']); ?></div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700 border border-gray-200">
                            <?php echo htmlspecialchars($siswa['kelas']) . ' ' . htmlspecialchars($siswa['jurusan']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-500 border border-gray-200">
                            <?php echo htmlspecialchars($siswa['tahun_ajaran']); ?>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-center border border-gray-200">
                            <button onclick="showResultModal('<?php echo htmlspecialchars($siswa['id_siswa']); ?>', '<?php echo htmlspecialchars($siswa['nama']); ?>', '<?php echo htmlspecialchars($siswa['nis']); ?>', '<?php echo htmlspecialchars($siswa['kelas']); ?>', '<?php echo htmlspecialchars($siswa['jurusan']); ?>', <?php echo $is_kc_done ? 1 : 0; ?>, <?php echo $is_gb_done ? 1 : 0; ?>)" 
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition duration-150 ease-in-out flex items-center justify-center text-xs mx-auto shadow-md">
                                <i class="fas fa-search mr-1"></i> Detail
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else : ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 whitespace-nowrap text-center text-sm font-medium text-gray-500 border border-gray-200">
                            <i class="fas fa-info-circle mr-2"></i> Tidak ada data siswa ditemukan dengan filter tersebut.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-container space-y-3 w-full">
        <?php if (!empty($data_siswa)) : ?>
            <?php foreach ($data_siswa as $siswa) :
                $is_gb_done = !empty($siswa['skor_gb_latest']);
                $is_kc_done = !empty($siswa['skor_kc_latest']);
            ?>
            <div class="student-card cursor-pointer" 
                    onclick="showResultModal('<?php echo htmlspecialchars($siswa['id_siswa']); ?>', '<?php echo htmlspecialchars($siswa['nama']); ?>', '<?php echo htmlspecialchars($siswa['nis']); ?>', '<?php echo htmlspecialchars($siswa['kelas']); ?>', '<?php echo htmlspecialchars($siswa['jurusan']); ?>', <?php echo $is_kc_done ? 1 : 0; ?>, <?php echo $is_gb_done ? 1 : 0; ?>)">
                <div class="flex justify-between items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-base font-semibold primary-color truncate"><?php echo htmlspecialchars($siswa['nama']); ?></div>
                        <div class="text-xs text-gray-500 mt-0.5">NIS: <?php echo htmlspecialchars($siswa['nis']); ?> (<?php echo htmlspecialchars($siswa['jenis_kelamin']); ?>)</div>
                        <div class="text-xs text-gray-500 mt-0.5">Kelas: <?php echo htmlspecialchars($siswa['kelas']) . ' ' . htmlspecialchars($siswa['jurusan']); ?></div>
                        <div class="text-xs text-gray-400 mt-1">TA: <?php echo htmlspecialchars($siswa['tahun_ajaran']); ?></div>
                    </div>
                    <div class="flex-shrink-0 text-indigo-600 hover:text-indigo-800 p-2 rounded-full bg-indigo-50 hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="bg-white p-6 rounded-xl shadow-lg text-center text-sm font-medium text-gray-500 border border-gray-200">
                <i class="fas fa-search-minus mr-2"></i> Tidak ada data siswa ditemukan.
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_pages > 1) : ?>
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 p-4 bg-white rounded-xl shadow-lg border border-gray-200">
            <div class="text-xs sm:text-sm text-gray-700 text-center sm:text-left">
                Menampilkan <strong><?php echo min($limit, $total_records - $offset); ?></strong> dari total <strong><?php echo $total_records; ?></strong> siswa.
                <span class="block sm:inline mt-1 sm:mt-0">(Per halaman: <?php echo $view_mode == 'mobile' ? '15 (Mobile)' : '40 (Desktop)'; ?>)</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="<?php echo build_pagination_url(1, $view_mode, $current_filters); ?>" 
                   class="px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-lg border <?php echo ($page == 1) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'; ?>">
                    <i class="fas fa-angle-double-left"></i>
                </a>

                <a href="<?php echo build_pagination_url($page > 1 ? $page - 1 : 1, $view_mode, $current_filters); ?>" 
                   class="px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-lg border <?php echo ($page == 1) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'; ?>">
                    <i class="fas fa-angle-left"></i>
                </a>

                <span class="text-xs sm:text-sm text-gray-700 px-1"><?php echo $page; ?>/<?php echo $total_pages; ?></span>

                <a href="<?php echo build_pagination_url($page < $total_pages ? $page + 1 : $total_pages, $view_mode, $current_filters); ?>" 
                   class="px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-lg border <?php echo ($page == $total_pages) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'; ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
                
                <a href="<?php echo build_pagination_url($total_pages, $view_mode, $current_filters); ?>" 
                   class="px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-lg border <?php echo ($page == $total_pages) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white hover:bg-gray-100 text-gray-700 border-gray-300'; ?>">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-6 bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-200">
        <h3 class="text-base sm:text-lg font-semibold mb-3 text-gray-800 flex items-center">
            <i class="fas fa-level-up-alt primary-color mr-2"></i> Kenaikan Kelas Siswa
        </h3>
        <p class="text-gray-600 text-xs sm:text-sm mb-4">
            Gunakan tombol di bawah untuk menaikkan kelas seluruh siswa sesuai jenjang.  
            X ke XI, XI ke XII, XII ke LULUS.
        </p>

        <form method="POST" action="proses_naik_kelas.php"
            onsubmit="return confirm('Yakin ingin menaikkan semua kelas siswa? Tindakan ini tidak bisa dibatalkan.')">

            <button class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 primary-bg text-white text-xs sm:text-sm font-semibold rounded-lg hover:bg-[#3C7F81] transition shadow-md flex items-center justify-center">
                <i class="fas fa-arrow-up mr-2"></i> Naikkan Semua Kelas
            </button>
        </form>
    </div>
    
</main>


    </div>

    <div id="resultModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div id="modalOverlay" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-50 rounded-xl shadow-2xl w-full max-w-lg transform transition-all relative z-50 modal-content-wrapper" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center p-4 sm:p-5 border-b border-gray-200 bg-white rounded-t-xl sticky top-0 z-10">
                    <div class="flex-1 min-w-0 pr-2">
                        <h3 id="modalTitle" class="text-base sm:text-lg font-bold primary-color truncate">Detail Hasil Tes Siswa</h3>
                        <p id="modalSubtitle" class="text-xs text-gray-600 mt-0.5"></p>
                    </div>
                    <button onclick="hideResultModal()" class="flex-shrink-0 text-gray-500 hover:text-gray-800 text-xl p-2 rounded-full hover:bg-gray-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4 sm:p-6">
                    <div id="resultCardsContainer" class="grid grid-cols-1 gap-3 sm:gap-4">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto content-wrapper">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>