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
    'Visual' => '#5FA8A1',
    'Auditori' => '#4C8E89',
    'Kinestetik' => '#123E44',
    'Belum Tes' => '#E5E7EB'
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * { 
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-y: scroll;
        }

:root {
    /* PRIMARY */
    --primary: #0F3A3A;
    --primary-dark: #0B2E2E;
    --primary-light: #123E44;

    /* ACCENT */
    --accent: #5FA8A1;
    --accent-dark: #4C8E89;

    /* NEUTRAL */
    --white: #FFFFFF;
    --gray-50: #F9FAFB;
    --gray-200: #E5E7EB;

    /* STATUS (DISESUAIKAN TEMA) */
    --success: #4C8E89;
    --warning: #5FA8A1;
    --danger: #9B2C2C;
}

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
        }

        .primary-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
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
            .sidebar { width: 260px; flex-shrink: 0; transform: translateX(0) !important; position: fixed !important; height: 100vh; top: 0; left: 0; overflow-y: auto; }
            .content-wrapper { margin-left: 260px; }
        }
            .content-wrapper {
                margin-left: 280px;
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
            background-color: var(--primary-light) !important; 
            color: white !important;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
background: linear-gradient(135deg, #0F3A3A 0%, #123E44 100%);
            color: white;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3);
            animation: slideIn 0.5s ease-out;
            margin: 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s ease;
        }

        .show-on-print-only {
            display: none; 
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
                margin-left: 280px;
            }
        }

        .grid {
            width: 100%;
            box-sizing: border-box;
        }

        .grid > * {
            overflow-x: hidden;
        }

        .data-table-report {
            width: 100%;
            box-sizing: border-box;
            table-layout: fixed;
        }

        .chart-container {
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        footer {
            width: 100%;
            box-sizing: border-box;
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
    background-color: #0F3A3A !important;
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
                           borderColor: '#123E44',
                            borderWidth: 2,
                            borderRadius: 8,
                            barThickness: 60
                        }]
                    },
                    options: {
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                display: false 
                            },
                            title: { 
                                display: true, 
                                text: 'Distribusi Gaya Belajar Siswa',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: 20
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    stepSize: 1,
                                    font: {
                                        size: 12
                                    }
                                },
                                title: { 
                                    display: true, 
                                    text: 'Jumlah Siswa',
                                    font: {
                                        size: 13,
                                        weight: 'bold'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }

            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="md:hidden flex justify-between items-center px-4 py-3 glass-effect shadow-lg sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl primary-gradient flex items-center justify-center shadow-lg">
                <i class="fas fa-user-tie text-white text-lg"></i>
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
        
        <aside id="sidebar" class="no-print sidebar hidden md:flex primary-gradient shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-lg animated-icon">
                        <i class="fas fa-user-tie text-xl text-white"></i>
                    </div>
                    <div>
                        <strong class="text-base font-bold block">Guru BK</strong>
                        <span class="text-xs text-white/80">SMKN 2 Banjarmasin</span>
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

        <main class="flex-grow p-4 sm:p-6 lg:p-8 md:ml-[280px] w-full">
            
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

            <!-- Page Header with Animation -->
            <div class="no-print mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-4xl font-extrabold text-gray-800 flex items-center gap-3">
                            <div class="w-12 h-12 primary-gradient rounded-xl flex items-center justify-center shadow-lg animated-icon">
                                <i class="fas fa-chart-bar text-white text-xl"></i>
                            </div>
                            Data Hasil Per Kelas
                        </h1>
                        <p class="text-gray-500 mt-2 ml-16">Analisis komprehensif gaya belajar siswa per kelas</p>
                    </div>
                </div>

                <?php if (!empty($filter_kelas) && !empty($filter_jurusan) && !empty($filter_tahun)) : ?>
                <div class="flex flex-wrap gap-2 ml-16">
                    <span class="filter-badge">
                        <i class="fas fa-users"></i>
                        <?php echo htmlspecialchars($filter_kelas); ?>
                    </span>
                    <span class="filter-badge">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo htmlspecialchars($filter_jurusan); ?>
                    </span>
                    <span class="filter-badge">
                        <i class="fas fa-calendar"></i>
                        <?php 
                        $current_tahun = array_filter($data_tahun, function($t) use ($filter_tahun) { 
                            return $t['id_tahun'] == $filter_tahun; 
                        });
                        echo htmlspecialchars(reset($current_tahun)['tahun']);
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Filter Section with Modern Design -->
            <div class="no-print glass-effect p-6 rounded-2xl shadow-xl mb-8 border border-gray-200 card-hover">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br  rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Filter Data</h3>
                </div>

                <form method="GET" action="rekap_kelas.php" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    
                    <div class="group">
                        <label for="kelas" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-users"></i>
                            Kelas
                        </label>
                        <select id="kelas" name="kelas" class="w-full rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-300 p-3 text-sm font-medium shadow-sm" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelas_options as $k): ?>
                                <option value="<?php echo $k; ?>" <?php echo ($filter_kelas == $k) ? 'selected' : ''; ?>>Kelas <?php echo $k; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    <div class="group">
                        <label for="jurusan" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-graduation-cap"></i>
                            Jurusan
                        </label>
                        <select id="jurusan" name="jurusan" class="w-full rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-100 transition-all duration-300 p-3 text-sm font-medium shadow-sm" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($jurusan_options as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo ($filter_jurusan == $j) ? 'selected' : ''; ?>><?php echo $j; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="group">
                        <label for="tahun" class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-calendar-alt "></i>
                            Tahun Ajaran
                        </label>
                        <select id="tahun" name="tahun" class="w-full rounded-xl border-2 border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 p-3 text-sm font-medium shadow-sm" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php foreach ($data_tahun as $tahun): ?>
                                <option value="<?php echo $tahun['id_tahun']; ?>" <?php echo ($filter_tahun == $tahun['id_tahun']) ? 'selected' : ''; ?>><?php echo $tahun['tahun']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
            
                    <div class="flex gap-2 col-span-1 md:col-span-2 lg:col-span-1 overflow-hidden">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fas fa-filter"></i>
                            <span>Filter</span>
                        </button>
                        <a href="rekap_kelas.php" class="flex-1 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($filter_kelas) && !empty($filter_jurusan) && !empty($filter_tahun)) : ?>
            
                <!-- Summary Card with Export -->
                <div class="no-print glass-effect p-6 rounded-2xl shadow-xl mb-8 border border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-2xl font-bold bg-gradient-to-r  bg-clip-text text mb-2">
                                Data <?php echo $filter_title; ?>
                            </h3>
                            <div class="flex items-center gap-2 text-gray-600">
                                <div class="w-10 h-10 bg-gradient-to-br rounded-lg flex items-center justify-center shadow-md">
                                    <i class="fas fa-users text"></i>
                                </div>
                                <span class="text-3xl font-bold text-gray-800"><?php echo count($data_siswa); ?></span>
                                <span class="text-gray-600 font-medium">Siswa Terdaftar</span>
                            </div>
                        </div>
                        <button onclick="exportToPdf()" class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center gap-3">
                            <i class="fas fa-file-pdf text-xl"></i>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Ekspor Laporan</div>
                                <div class="text-xs opacity-90">Format PDF</div>
                            </div>
                        </button>
                    </div>
                </div>
            
                <?php if (count($data_siswa) > 0) : ?>
                
                <!-- Stats Cards -->
                <div class="no-print grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <?php 
                    $stats = [
                        ['type' => 'Visual', 'icon' => 'fa-eye', 'gradient' => 'from-yellow-400 to-orange-500'],
                        ['type' => 'Auditori', 'icon' => 'fa-headphones', 'gradient' => 'from-blue-400 to-cyan-500'],
                        ['type' => 'Kinestetik', 'icon' => 'fa-hand-rock', 'gradient' => 'from-green-400 to-emerald-500'],
                        ['type' => 'Belum Tes', 'icon' => 'fa-hourglass-half', 'gradient' => 'from-gray-400 to-gray-500']
                    ];
                    
                    foreach ($stats as $stat) :
                        $count = $gb_counts[$stat['type']] ?? 0;
                        $percentage = $total_siswa > 0 ? round(($count / $total_siswa) * 100) : 0;
                    ?>
                    <div class="stat-card glass-effect p-6 rounded-2xl shadow-lg border border-gray-200 card-hover">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br <?php echo $stat['gradient']; ?> rounded-xl flex items-center justify-center shadow-md">
                                <i class="fas <?php echo $stat['icon']; ?> text-white text-xl"></i>
                            </div>
                            <span class="text-3xl font-bold text-gray-800"><?php echo $count; ?></span>
                        </div>
                        <h4 class="font-bold text-gray-700 mb-2"><?php echo $stat['type']; ?></h4>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r <?php echo $stat['gradient']; ?> h-2 rounded-full transition-all duration-1000" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2 font-medium"><?php echo $percentage; ?>% dari total</p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chart Section with Enhanced Design -->
                <div class="no-print mb-8">
                    <div class="chart-container glass-effect p-8 rounded-2xl shadow-2xl border border-gray-200 card-hover">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-chart-bar text-white text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-2xl font-bold text-gray-800">Visualisasi Data</h4>
                                <p class="text-sm text-gray-500">Distribusi gaya belajar siswa</p>
                            </div>
                        </div>
                        <div class="h-96">
                            <canvas id="gbChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Summary Section with Modern Card -->
                <div class="mt-8 report-section glass-effect p-8 rounded-2xl shadow-2xl border border-gray-200">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg animated-icon">
                            <i class="fas fa-lightbulb text-white text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold text-gray-800">Rangkuman Analisis</h4>
                            <p class="text-sm text-gray-500">Insight dari data yang tersedia</p>
                        </div>
                    </div>
                    
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
                    
                    <div class="wawasan-data-web hide-on-print space-y-4">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-indigo-500 p-6 rounded-xl">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-trophy text-indigo-600 text-2xl mt-1"></i>
                                <div>
                                    <h5 class="font-bold text-gray-800 mb-2">Gaya Belajar Dominan</h5>
                                    <p class="text-gray-700">
                                        <span class="font-bold text-indigo-600 text-xl">
                                            <?php echo empty($dominant_gb['types']) ? 'Tidak Teridentifikasi' : implode(' & ', $dominant_gb['types']); ?>
                                        </span>
                                        <?php if (!empty($dominant_gb['types'])) : ?>
                                        <br>
                                        <span class="text-sm">
                                            <i class="fas fa-users text-gray-500 mr-1"></i>
                                            <?php echo $dominant_gb['count']; ?> siswa 
                                            (<strong><?php echo $gb_percentage; ?>%</strong> dari yang sudah tes)
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 p-6 rounded-xl">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-clipboard-check text-green-600 text-2xl mt-1"></i>
                                <div>
                                    <h5 class="font-bold text-gray-800 mb-2">Status Tes</h5>
                                    <p class="text-gray-700"><?php echo $gb_status_text; ?></p>
                                    <?php if ($gb_belum_tes > 0) : ?>
                                    <div class="mt-3 inline-flex items-center gap-2 bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg font-medium text-sm">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Perlu Follow-up</span>
                                    </div>
                                    <?php else : ?>
                                    <div class="mt-3 inline-flex items-center gap-2 bg-green-100 text-green-800 px-4 py-2 rounded-lg font-medium text-sm">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Semua Lengkap</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Print Only Table -->
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
                <div class="glass-effect p-8 rounded-2xl shadow-lg text-center border border-gray-200">
                    <div class="w-24 h-24 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-search-minus text-white text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Tidak Ada Data</h3>
                    <p class="text-gray-500">Tidak ada data siswa ditemukan untuk kriteria filter tersebut.</p>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="glass-effect p-8 rounded-2xl shadow-lg border-l-4 border-green-500">
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 bg-gradient-to-br  rounded-xl flex items-center justify-center shadow-lg flex-shrink-0 animated-icon">
                            <i class="fas fa-filter text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Pilih Kriteria Filter</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Silakan pilih kombinasi <strong>Kelas</strong>, <strong>Jurusan</strong>, dan <strong>Tahun Ajaran</strong> di atas untuk menampilkan analisis data gaya belajar siswa yang dominan dalam satu kelas.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-2 bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-users"></i> Kelas
                                </span>
                                <span class="inline-flex items-center gap-2 bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-graduation-cap"></i> Jurusan
                                </span>
                                <span class="inline-flex items-center gap-2 bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-calendar"></i> Tahun Ajaran
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

 <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="text-center">
            <p class="text-sm text-gray-600">
                &copy; 2025 <span class="font-semibold">Bimbingan Konseling SMKN 2 Banjarmasin</span>
            </p>
        </div>
    </footer>
</body>
</html>
