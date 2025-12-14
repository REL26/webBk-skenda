<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);

$filter_kelas   = isset($_GET['kelas'])     ? mysqli_real_escape_string($koneksi, trim($_GET['kelas']))     : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, trim($_GET['jurusan'])) : '';
$filter_tahun   = isset($_GET['tahun'])   ? mysqli_real_escape_string($koneksi, trim($_GET['tahun']))   : '';

$where_clauses = [];
if (!empty($filter_kelas))   $where_clauses[] = "s.kelas = '$filter_kelas'";
if (!empty($filter_jurusan)) $where_clauses[] = "s.jurusan = '$filter_jurusan'";
if (!empty($filter_tahun))   $where_clauses[] = "t.id_tahun = '$filter_tahun'";

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$gb_mapping = [
    'Visual' => 'Visual', 'Auditori' => 'Auditori', 'Kinestetik' => 'Kinestetik', '' => 'Belum Tes'
];
$gb_colors = [
    'Visual' => '#FFC107', 'Auditori' => '#03A9F4', 'Kinestetik' => '#8BC34A', 'Belum Tes' => '#BDBDBD'
];

$query_siswa = "
    SELECT 
        s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan, t.tahun AS tahun_ajaran, 
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
        ) AS skor_gb_latest
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    " . $where_sql . " 
    ORDER BY s.nama ASC
";

$result_siswa = mysqli_query($koneksi, $query_siswa);

if (!$result_siswa) {
    die("Query Error: " . mysqli_error($koneksi));
}

$data_siswa = mysqli_fetch_all($result_siswa, MYSQLI_ASSOC);

$gb_counts = [
    'Visual' => 0, 'Auditori' => 0, 'Kinestetik' => 0, 'Belum Tes' => 0
];

foreach ($data_siswa as $siswa) {
    $gb_type = empty($siswa['skor_gb_latest']) ? 'Belum Tes' : $siswa['skor_gb_latest'];
    $gb_counts[$gb_type] = ($gb_counts[$gb_type] ?? 0) + 1;
}

$gb_belum_tes = $gb_counts['Belum Tes'] ?? 0;
unset($gb_counts['Belum Tes']);

arsort($gb_counts);

$gb_counts['Belum Tes'] = $gb_belum_tes;

$gb_status_text = ($gb_belum_tes > 0) 
    ? "Terdapat {$gb_belum_tes} siswa yang belum menyelesaikan Tes Gaya Belajar." 
    : "Tes Gaya Belajar sudah diselesaikan oleh semua siswa.";

$gb_labels = json_encode(array_keys($gb_counts));
$gb_data = json_encode(array_values($gb_counts));
$gb_chart_colors = json_encode(array_values(array_intersect_key($gb_colors, $gb_counts)));

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas";
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


$display_kelas = !empty($filter_kelas) ? ' Kelas ' . htmlspecialchars($filter_kelas) : '';
$display_jurusan = !empty($filter_jurusan) ? ' Jurusan ' . htmlspecialchars($filter_jurusan) : '';

$display_tahun = '';
if (!empty($filter_tahun)) {
    $current_tahun = array_filter($data_tahun, function($t) use ($filter_tahun) { return $t['id_tahun'] == $filter_tahun; });
    $display_tahun = ' Tahun Ajaran ' . htmlspecialchars(reset($current_tahun)['tahun']);
} elseif (!empty($data_siswa)) {
    $display_tahun = ' (Tahun Ajaran ' . htmlspecialchars($data_siswa[0]['tahun_ajaran']) . ')';
}

$filter_title = trim($display_kelas . $display_jurusan . $display_tahun);

$get_dominant = function($counts) {
    $max_count = 0;
    $dominant_type = [];
    $total = array_sum($counts);
    $counts_without_belum = array_diff_key($counts, ['Belum Tes' => 0]);
    $total_tested = array_sum($counts_without_belum);

    foreach ($counts_without_belum as $type => $count) {
        if ($count > $max_count) {
            $max_count = $count;
            $dominant_type = [$type];
        } elseif ($count == $max_count && $count > 0) {
            $dominant_type[] = $type;
        }
    }
    
    // Logika untuk mendeteksi semua Belum Tes
    if ($max_count == 0 && ($counts['Belum Tes'] ?? 0) == $total) {
        return [
            'types' => [], 
            'count' => 0, 
            'total' => $total_tested
        ];
    }

    return [
        'types' => $dominant_type, 
        'count' => $max_count, 
        'total' => $total_tested
    ];
};

$dominant_gb = $get_dominant($gb_counts);

$total_tested_gb = $dominant_gb['total'];

$gb_percentage = ($dominant_gb['count'] > 0 && $total_tested_gb > 0) ? round(($dominant_gb['count'] / $total_tested_gb) * 100) : 0;

$total_siswa = count($data_siswa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Per Kelas | Data & Laporan Siswa | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { font-family: 'Inter', sans-serif; }
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .secondary-bg { background-color: #E6EEF0; }
        
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
            .content-wrapper {
                margin-left: 260px;
            }
        }
        
        .nav-item { position: relative; overflow: hidden; }
        .nav-item::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: #D9F0F4; transform: scaleY(0); transition: transform 0.3s ease; }
        .nav-item:hover::before, .nav-item.active::before { transform: scaleY(1); }
        .nav-item.active { background-color: #3C7F81; }

        .nav-item.active > div:first-child,
        .nav-item.active { 
            background-color: #3C7F81 !important; 
            color: white !important;
        }

        .show-on-print-only {
            display: none; 
        }
        
        @media print {
    * {
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    @page {
        size: A4 portrait;
        margin: 0.8cm;
    }

    .no-print {
        display: none !important;
    }
    .show-on-print {
        display: block !important;
    }
    .hide-on-print {
        display: none !important;
    }
    .show-on-print-only {
        display: block !important;
    }

    body, html {
        background: #fff !important;
        width: 100%;
        margin: 0;
        padding: 0;
        font-size: 11pt;
        height: 100%;
        overflow: hidden !important;
    }

    main {
        margin-left: 0 !important;
        padding: 0 !important;
        max-width: 100%;
        box-sizing: border-box;
    }

    .print-header,
    .report-section,
    .data-table-report,
    .wawasan-data-pdf {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .print-header {
        display: block !important;
        margin-bottom: 15px;
        padding-top: 5px;
        border-bottom: 3px double #333;
        padding-bottom: 8px;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5px;
    }

    .header-logo {
        height: 70px;
        width: 70px;
        margin-right: 15px;
    }

    .header-title {
        text-align: center;
        flex-grow: 1;
        line-height: 1.2;
        padding-top: 5px;
    }

    .header-title h1 {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0;
        color: #333;
        text-transform: uppercase;
    }

    .header-title h2 {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 3px 0 5px;
        color: #555;
    }

    .header-title p {
        font-size: 0.85rem;
        margin: 0;
        color: #555;
    }

    .report-section {
        padding: 0 !important;
        margin-top: 15px !important;
        box-shadow: none !important;
        border: none !important;
    }

    .data-table-report {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
        font-size: 0.9rem;
    }

    .data-table-report th,
    .data-table-report td {
        border: 1px solid #000;
        padding: 6px 10px;
        text-align: left;
    }

    .data-table-report th {
        background-color: #2F6C6E !important;
        font-weight: 700 !important;
        color: #ffffff !important;
    }

    .data-table-report tbody tr:nth-child(even) td {
        background-color: #f2f2f2 !important;
    }

    .data-table-report tbody tr:nth-child(odd) td {
        background-color: #ffffff !important;
    }

    .data-table-report tr.bg-yellow-200 td {
        font-weight: 700 !important;
        background-color: #ffe599 !important;
        color: #000 !important;
    }

    .chart-container,
    .wawasan-data-web {
        display: none !important;
    }

    .wawasan-data-pdf {
        display: block !important;
        margin-bottom: 15px;
        font-size: 0.95rem;
        color: #333;
        line-height: 1.4;
        font-weight: normal;
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
                overlay.classList.add('hidden');
                
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                    body.classList.remove('overflow-hidden');
                }, 300);

            } else {
                mobileMenu.classList.remove('hidden');
                
                setTimeout(() => {
                    mobileMenu.classList.add('active-transition');
                }, 10);
                
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

        function exportToPdf() {
            window.print();
        }
        
        let gbChartInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);
            
            if (document.getElementById('gbChart')) {
                const gbCtx = document.getElementById('gbChart').getContext('2d');
                gbChartInstance = new Chart(gbCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo $gb_labels; ?>,
                        datasets: [{
                            label: 'Jumlah Siswa',
                            data: <?php echo $gb_data; ?>,
                            backgroundColor: <?php echo $gb_chart_colors; ?>,
                            borderColor: '#3C7F81',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Jumlah Siswa' }, ticks: { stepSize: 1 } }
                        },
                        plugins: { legend: { display: false }, title: { display: true, text: 'Hasil Gaya Belajar' } }
                    }
                });
            }
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="no-print md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
        <div>
            <span class="text-base font-semibold primary-color">Guru BK</span><br>
            <small class="text-xs text-gray-500">SMKN 2 BJM</small>
        </div>
        <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 z-40 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="no-print hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="no-print fade-slide hidden fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Beranda
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

        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer" onclick="toggleSubMenu('programBkSubmenuMobile')">
            <div class="flex justify-between">
                <span class="flex font-medium">
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
        
        <aside id="sidebar" class="no-print sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-tie text-lg"></i>
                    </div>
                    <div>
                        <span class="text-base font-semibold block">Guru BK</span>
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
                <div class="nav-item cursor-pointer" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex-item">
                            <i class="fas fa-calendar-alt mr-2"></i> Program BK
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

       <main class="flex-grow p-4 sm:p-6 lg:p-8 md:ml-[260px] w-full">
            
            <div class="print-header hidden">
                <div class="header-content">
                    <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo Sekolah" class="header-logo" style="float: left;">
                    <div class="header-title">
                        <p>BIMBINGAN DAN KONSELING</p>
                        <h2>SMKN 2 BANJARMASIN</h2>
                        <h1>LAPORAN HASIL TES PERKELAS</h1>
                        <p>Data: <?php echo $filter_title; ?> (Total Siswa: <?php echo $total_siswa; ?>)</p>
                    </div>
                    <div style="width: 70px;"></div>
                </div>
            </div>

            <h2 class="no-print text-3xl font-extrabold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-chart-bar primary-color mr-3"></i> Data Hasil Per Kelas
            </h2>
        
            <div class="no-print bg-white p-4 rounded-xl shadow-lg mb-6 border border-gray-200">
                <form method="GET" action="rekap_kelas.php" class="grid grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            
                    <div>
                        <label for="kelas" class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select id="kelas" name="kelas" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_options as $k): ?>
                                <option value="<?php echo $k; ?>" <?php echo ($filter_kelas == $k) ? 'selected' : ''; ?>>Kelas <?php echo $k; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    <div>
                        <label for="jurusan" class="block text-sm font-medium text-gray-700">Jurusan</label>
                        <select id="jurusan" name="jurusan" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($jurusan_options as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo ($filter_jurusan == $j) ? 'selected' : ''; ?>><?php echo $j; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun Ajaran</label>
                        <select id="tahun" name="tahun" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php foreach ($data_tahun as $tahun): ?>
                                <option value="<?php echo $tahun['id_tahun']; ?>" <?php echo ($filter_tahun == $tahun['id_tahun']) ? 'selected' : ''; ?>><?php echo $tahun['tahun']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    <div class="flex gap-2 pt-2 md:pt-0 col-span-2 lg:col-span-1">
                        <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm shadow-md">
                    <i class="fas fa-filter mr-2"></i> Terapkan Filter
                </button>
                <a href="rekap_kelas.php" class="w-full md:w-auto bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm shadow-md">
                    <i class="fas fa-undo mr-2"></i> Reset
                </a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($filter_kelas) && !empty($filter_jurusan) && !empty($filter_tahun)) : ?>
            
                <div class="secondary-bg p-4 rounded-xl shadow-sm mb-6 border border-gray-200 no-print">
                    <h3 class="text-xl font-bold primary-color">
                        Data <?php echo $filter_title; ?> (Total <?php echo count($data_siswa); ?> Siswa)
                    </h3>
                    <div class="mt-4">
                        <button onclick="exportToPdf()" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center justify-center text-sm">
                            <i class="fas fa-file-pdf mr-2"></i> Ekspor Laporan (PDF)
                        </button>
                    </div>
                </div>
            
                <?php if (count($data_siswa) > 0) : ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print mt-6 justify-center">
                    
                    <div class="chart-container bg-white p-5 rounded-xl shadow-lg border border-gray-200 md:col-span-2 lg:col-span-1 mx-auto w-full max-w-lg">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i> Hasil Gaya Belajar
                        </h4>
                        <div class="h-80">
                            <canvas id="gbChart"></canvas>
                        </div>
                    </div>
                    
                </div>

                <div class="mt-6 report-section bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                    <h4 class="text-lg font-semibold mb-3 text-gray-800 flex items-center">
                        <i class="fas fa-info-circle primary-color mr-2"></i>Rangkuman Singkat
                    </h4>
                    
                    <div class="wawasan-data-pdf hidden hide-on-print show-on-print">
                        <p>Berdasarkan data profil siswa <?php echo $filter_title; ?> (Total <?php echo $total_siswa; ?> Siswa), diperoleh ringkasan data sebagai berikut:</p>
                        <ul style="list-style-type: disc; margin-left: 20px; padding-left: 0;">
                            <li>
                                Gaya Belajar Dominan: <span><?php echo empty($dominant_gb['types']) ? 'Tidak Teridentifikasi (Semua Belum Tes)' : implode(' dan ', $dominant_gb['types']); ?></span> (<?php echo $dominant_gb['count']; ?> siswa, <?php echo $gb_percentage; ?>% dari siswa yang sudah tes).
                            </li>
                            <li>
                                Status Tes Siswa: 
                                <span><?php echo $gb_status_text; ?></span>.
                            </li>
                        </ul>
                    </div>
                    
                    <ul class="wawasan-data-web list-disc pl-5 text-gray-600 space-y-2 text-sm hide-on-print">
                        <li>
                            Gaya Belajar Dominan: <span><?php echo empty($dominant_gb['types']) ? 'Tidak Teridentifikasi (Semua Belum Tes)' : implode(' dan ', $dominant_gb['types']); ?></span> (<?php echo $dominant_gb['count']; ?> siswa, <?php echo $gb_percentage; ?>% dari siswa yang sudah tes).
                        </li>
                        <li>
                            Status Tes Siswa: <?php echo $gb_status_text; ?>.
                        </li>
                    </ul>
                </div>
                
                <div class="report-section show-on-print-only mt-6">
                    <h4 style="font-size: 1.1rem; font-weight: 700; color: #333; margin-bottom: 5px;">
                        1. Hasil Tes Gaya Belajar
                    </h4>
                    <table class="data-table-report">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Kategori Hasil</th>
                                <th>Presentase</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_tested_siswa = $total_siswa - ($gb_counts['Belum Tes'] ?? 0);
                            foreach ($gb_counts as $tipe => $jumlah):
                                
                                $tipe_persentase = ($jumlah > 0 && $total_siswa > 0) ? round(($jumlah / $total_siswa) * 100, 1) : 0;
                                $is_dominant = in_array($tipe, $dominant_gb['types']);
                                $row_class = '';
                                
                                
                                if ($is_dominant && $jumlah > 0 && $tipe != 'Belum Tes') {
                                    $row_class = 'bg-yellow-200 font-semibold';
                                }
                                
                            ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td><?php echo $tipe; ?></td>
                                    <td><?php echo $jumlah; ?> (<?php echo $tipe_persentase; ?>%)</td> 
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php else : ?>
                <div class="bg-white p-6 rounded-xl shadow-lg text-center text-sm font-medium text-gray-500 border border-gray-200">
                    <i class="fas fa-search-minus mr-2"></i> Tidak ada data siswa ditemukan untuk kriteria filter tersebut.
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-md shadow-md" role="alert">
                    <p class="font-bold">Pilih Kriteria</p>
                    <p class="text-sm">Silakan pilih kombinasi Kelas, Jurusan, dan Tahun Ajaran di atas untuk menampilkan data gaya belajar siswa yang dominan dalam 1 kelas.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>