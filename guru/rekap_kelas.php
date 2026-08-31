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

$filter_kelas   = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, trim($_GET['kelas'])) : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, trim($_GET['jurusan'])) : '';

$where_clauses = [];
if (!empty($filter_kelas)) {
    $where_clauses[] = "s.kelas = '$filter_kelas'";
}
if (!empty($filter_jurusan)) {
    $where_clauses[] = "s.jurusan = '$filter_jurusan'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$gb_colors = [
    'Visual' => '#5FA8A1',
    'Auditori' => '#4C8E89',
    'Kinestetik' => '#123E44',
    'Belum Tes' => '#E5E7EB'
];

$query_siswa = "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nama,
        s.jenis_kelamin,
        s.kelas,
        s.jurusan,
        t.tahun AS tahun_ajaran,
        (
            SELECT 
                CASE
                    WHEN skor_visual >= skor_auditori AND skor_visual >= skor_kinestetik THEN 'Visual'
                    WHEN skor_auditori >= skor_visual AND skor_auditori >= skor_kinestetik THEN 'Auditori'
                    ELSE 'Kinestetik'
                END
            FROM hasil_gayabelajar
            WHERE id_siswa = s.id_siswa
            ORDER BY tanggal_tes DESC
            LIMIT 1
        ) AS skor_gb_latest,
        (
            SELECT skor_visual
            FROM hasil_gayabelajar
            WHERE id_siswa = s.id_siswa
            ORDER BY tanggal_tes DESC
            LIMIT 1
        ) AS skor_visual_latest,
        (
            SELECT skor_auditori
            FROM hasil_gayabelajar
            WHERE id_siswa = s.id_siswa
            ORDER BY tanggal_tes DESC
            LIMIT 1
        ) AS skor_auditori_latest,
        (
            SELECT skor_kinestetik
            FROM hasil_gayabelajar
            WHERE id_siswa = s.id_siswa
            ORDER BY tanggal_tes DESC
            LIMIT 1
        ) AS skor_kinestetik_latest
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    $where_sql
    ORDER BY s.nama ASC
";

$result_siswa = mysqli_query($koneksi, $query_siswa);
if (!$result_siswa) {
    die("Query Error: " . mysqli_error($koneksi));
}

$data_siswa = mysqli_fetch_all($result_siswa, MYSQLI_ASSOC);

$gb_counts = [
    'Visual' => 0,
    'Auditori' => 0,
    'Kinestetik' => 0,
    'Belum Tes' => 0
];

foreach ($data_siswa as &$siswa) {
    $tipe = empty($siswa['skor_gb_latest']) ? 'Belum Tes' : $siswa['skor_gb_latest'];
    $gb_counts[$tipe]++;

    $sv = (int)($siswa['skor_visual_latest'] ?? 0);
    $sa = (int)($siswa['skor_auditori_latest'] ?? 0);
    $sk = (int)($siswa['skor_kinestetik_latest'] ?? 0);
    $total_skor_siswa = $sv + $sa + $sk;

    if ($tipe === 'Belum Tes' || $total_skor_siswa === 0) {
        $siswa['persentase_gb'] = null;
    } else {
        $skor_dominan = max($sv, $sa, $sk);
        $siswa['persentase_gb'] = round(($skor_dominan / $total_skor_siswa) * 100);
    }
}
unset($siswa);

$gb_belum_tes = $gb_counts['Belum Tes'];
unset($gb_counts['Belum Tes']);
arsort($gb_counts);
$gb_counts['Belum Tes'] = $gb_belum_tes;

$gb_labels = json_encode(array_keys($gb_counts));
$gb_data = json_encode(array_values($gb_counts));
$gb_chart_colors = json_encode(array_values(array_intersect_key($gb_colors, $gb_counts)));

$gb_status_text = ($gb_belum_tes > 0)
    ? "Terdapat {$gb_belum_tes} siswa yang belum menyelesaikan Tes Gaya Belajar."
    : "Tes Gaya Belajar sudah diselesaikan oleh semua siswa.";

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != 'LULUS' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
$kelas_options = array_column(mysqli_fetch_all($result_kelas, MYSQLI_ASSOC), 'kelas');

$query_jurusan = "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL ORDER BY jurusan";
$result_jurusan = mysqli_query($koneksi, $query_jurusan);
$jurusan_options = array_column(mysqli_fetch_all($result_jurusan, MYSQLI_ASSOC), 'jurusan');

$filter_title = '';
if (!empty($filter_kelas) && !empty($filter_jurusan)) {
    $filter_title = 'Kelas ' . htmlspecialchars($filter_kelas) . ' Jurusan ' . htmlspecialchars($filter_jurusan);
}

$get_dominant = function ($counts) {
    $max = 0;
    $types = [];
    $tested = array_diff_key($counts, ['Belum Tes' => 0]);
    $total_tested = array_sum($tested);

    foreach ($tested as $type => $count) {
        if ($count > $max) {
            $max = $count;
            $types = [$type];
        } elseif ($count === $max && $count > 0) {
            $types[] = $type;
        }
    }

    return [
        'types' => $types,
        'count' => $max,
        'total' => $total_tested
    ];
};

$dominant_gb = $get_dominant($gb_counts);
$total_tested_gb = $dominant_gb['total'];
$gb_percentage = ($dominant_gb['count'] > 0 && $total_tested_gb > 0)
    ? round(($dominant_gb['count'] / $total_tested_gb) * 100)
    : 0;

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
html,
body {
    overflow-x: hidden;
    max-width: 100%;
}

*,
*::before,
*::after {
    box-sizing: border-box;
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
                margin: 0.5cm;
            }

            .no-print,
            nav,
            aside,
            header,
            #sidebar,
            .sidebar {
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
            .wawasan-data-pdf {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .report-section {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .data-table-report {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .data-table-report tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .data-table-report thead {
                display: table-header-group;
            }

            .print-header {
                display: block !important;
                margin-bottom: 6px;
                padding-top: 2px;
                border-bottom: 2px double #333;
                padding-bottom: 4px;
            }

            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 5px;
            }

            .header-logo {
                height: 45px;
                width: 45px;
                margin-right: 10px;
            }

            .header-title {
                text-align: center;
                flex-grow: 1;
                line-height: 1.1;
                padding-top: 2px;
            }

            .header-title h1 {
                font-size: 1.1rem;
                font-weight: 800;
                margin: 0;
                color: #333;
                text-transform: uppercase;
            }

            .header-title h2 {
                font-size: 0.85rem;
                font-weight: 600;
                margin: 2px 0 3px;
                color: #555;
            }

            .header-title p {
                font-size: 0.7rem;
                margin: 0;
                color: #555;
            }

            .report-section {
                padding: 0 !important;
                margin-top: 6px !important;
                box-shadow: none !important;
                border: none !important;
            }

            .report-section h4 {
                margin-bottom: 2px !important;
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

            .print-mt-tight {
                margin-top: 6px !important;
            }

            .data-table-siswa th,
            .data-table-siswa td {
                padding: 1.5px 4px;
                font-size: 0.62rem;
                line-height: 1.15;
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

    <!-- JS BERSAMA untuk toggle sidebar/submenu - WAJIB ada di semua halaman -->
    <script src="partials/sidebar-script.js"></script>
    <script>
        function exportToPdf() {
            window.print();
        }
        
        let gbChartInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
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
<div class="no-print">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
</div>
<main class="flex-1 p-4 sm:p-4 lg:p-6 md:ml-[260px]">
            
            <div class="print-header hidden">
                <div class="header-content">
                    <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo Sekolah" class="header-logo" style="float: left;">
                    <div class="header-title">
                        <p>Bimbingan dan Konseling</p>
                        <h2>SMKN 2 BANJARMASIN</h2>
                        <h1>LAPORAN HASIL TES PERKELAS</h1>
                        <p>Data: <?php echo $filter_title; ?> (Total Siswa: <?php echo $total_siswa; ?>)</p>
                    </div>
                    <div style="width: 70px;"></div>
                </div>
            </div>

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

                <?php if (!empty($filter_kelas) && !empty($filter_jurusan)) : ?>

                <div class="flex flex-wrap gap-2 ml-16">
                    <span class="filter-badge">
                        <i class="fas fa-users"></i>
                        <?php echo htmlspecialchars($filter_kelas); ?>
                    </span>
                    <span class="filter-badge">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo htmlspecialchars($filter_jurusan); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

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

            
                    <div class="flex gap-2 col-span-1 md:col-span-2 lg:col-span-1 overflow-hidden">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
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
            
            <?php if (!empty($filter_kelas) && !empty($filter_jurusan)) : ?>

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
                        <button onclick="exportToPdf()" class="bg-[#0F3A3A] hover:from-green-700 hover:to-green-800 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center gap-3">
                            <i class="fas fa-file-pdf text-xl"></i>
                            <div class="text-left">
                                <div class="text-sm font-semibold">Ekspor Laporan</div>
                                <div class="text-xs opacity-90">Format PDF</div>
                            </div>
                        </button>
                    </div>
                </div>
            
                <?php if (count($data_siswa) > 0) : ?>
                
                

                <div class="no-print mb-8">
                    <div class="chart-container glass-effect p-8 rounded-2xl shadow-2xl border border-gray-200 card-hover">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
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

                <div class="mt-8 report-section glass-effect p-8 rounded-2xl shadow-2xl border border-gray-200">
    <div class="flex items-center gap-3 mb-6">
        <div>
            <h4 class="text-2xl font-bold text-gray-800">Rangkuman Analisis</h4>
            <p class="text-sm text-gray-500">Insight dari data yang tersedia</p>
        </div>
    </div>

    <div class="wawasan-data-pdf show-on-print-only">
        <p>
            Berdasarkan data profil siswa <?php echo $filter_title; ?>
            (Total <?php echo $total_siswa; ?> Siswa), diperoleh ringkasan data sebagai berikut:
        </p>
        <ul style="list-style-type: disc; margin-left: 20px; padding-left: 0;">
            <li>
                Gaya Belajar Dominan:
                <strong>
                    <?php
                        echo empty($dominant_gb['types'])
                            ? 'Tidak Teridentifikasi (Semua Belum Tes)'
                            : implode(' dan ', $dominant_gb['types']);
                    ?>
                </strong>
                (<?php echo $dominant_gb['count']; ?> siswa,
                <?php echo $gb_percentage; ?>%)
            </li>
            <li>
                Status Tes Siswa:
                <strong><?php echo $gb_status_text; ?></strong>
            </li>
        </ul>
    </div>

    <div class="wawasan-data-web hide-on-print space-y-4">
        <div class="bg-gradient-to-r from-indigo-50 to-indigo-50 border-l-4 border-indigo-500 p-6 rounded-xl">
            <div class="flex items-start gap-3">
                <i class="fas fa-trophy text-indigo-600 text-2xl mt-1"></i>
                <div>
                    <h5 class="font-bold text-gray-800 mb-2">Gaya Belajar Dominan</h5>
                    <p class="text-gray-700">
                        <span class="font-bold text-indigo-600 text-xl">
                            <?php
                                echo empty($dominant_gb['types'])
                                    ? 'Tidak Teridentifikasi'
                                    : implode(' & ', $dominant_gb['types']);
                            ?>
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

        <div class="bg-gradient-to-r from-indigo-50 to-indigo-50 border-l-4 border-indigo-500 p-6 rounded-xl">
            <div class="flex items-start gap-3">
                <i class="fas fa-clipboard-check text-indigo-600 text-2xl mt-1"></i>
                <div>
                    <h5 class="font-bold text-gray-800 mb-2">Status Tes</h5>
                    <p class="text-gray-700"><?php echo $gb_status_text; ?></p>

                    <?php if ($gb_belum_tes > 0) : ?>
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

                
                <div class="report-section show-on-print-only print-mt-tight">
                    <h4 style="font-size: 0.8rem; font-weight: 700; color: #333; margin-bottom: 2px;">
                        1. Daftar Siswa dan Hasil Gaya Belajar
                    </h4>
                    <table class="data-table-report data-table-siswa">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 5%; text-align:center;">No</th>
                                <th rowspan="2" style="width: 22%;">Nama Siswa</th>
                                <th rowspan="2" style="width: 12%; text-align:center;">NIS</th>
                                <th rowspan="2" style="width: 6%; text-align:center;">JK</th>
                                <th colspan="3" style="text-align:center;">Jumlah Skor</th>
                                <th rowspan="2" style="width: 16%; text-align:center;">Hasil</th>
                            </tr>
                            <tr>
                                <th style="width: 11%; text-align:center;">Visual</th>
                                <th style="width: 11%; text-align:center;">Auditorial</th>
                                <th style="width: 11%; text-align:center;">Kinestetik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no_urut = 1; foreach ($data_siswa as $siswa_row):
                                $hasil_gb = empty($siswa_row['skor_gb_latest']) ? 'Belum Tes' : strtoupper($siswa_row['skor_gb_latest']);
                                $sv = $siswa_row['skor_visual_latest'];
                                $sa = $siswa_row['skor_auditori_latest'];
                                $sk = $siswa_row['skor_kinestetik_latest'];
                                $has_score = !is_null($sv) && !is_null($sa) && !is_null($sk);
                            ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $no_urut++; ?></td>
                                    <td><?php echo htmlspecialchars($siswa_row['nama']); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($siswa_row['nis']); ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($siswa_row['jenis_kelamin'] ?? ''); ?></td>
                                    <td style="text-align: center;"><?php echo $has_score ? (int)$sv : '-'; ?></td>
                                    <td style="text-align: center;"><?php echo $has_score ? (int)$sa : '-'; ?></td>
                                    <td style="text-align: center;"><?php echo $has_score ? (int)$sk : '-'; ?></td>
                                    <td style="text-align: center;"><?php echo $hasil_gb; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section show-on-print-only print-mt-tight">
                    <h4 style="font-size: 0.8rem; font-weight: 700; color: #333; margin-bottom: 2px;">
                        2. Rekap Kategori Gaya Belajar
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
                <div class="glass-effect p-8 rounded-2xl shadow-lg border-l-4 border-indigo-500">
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
                                <span class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-users"></i> Kelas
                                </span>
                                <span class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <i class="fas fa-graduation-cap"></i> Jurusan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="hide-on-print bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="text-center">
            <p class="text-sm text-gray-600">
    &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
</p>
<p class="text-xs text-gray-400 mt-1">
    Developed by <span class="font-medium">SahDu Team</span>
</p>

        </div>
    </footer>
</body>
</html>