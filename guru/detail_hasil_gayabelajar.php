<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa_int = (int) $_GET['id_siswa'];

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru Bimbingan Konseling';
$kota = "Banjarmasin";
$nama_kepsek = "Novie Bambang Rumadi, S.T., M.Pd"; 
$nama_guru_bk = "..."; 

function format_date_indo($date_str) {
    if ($date_str == '0000-00-00' || !$date_str) return date('d F Y');
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    try {
        $date = new DateTime($date_str);
    } catch (Exception $e) {
        return date('d F Y');
    }
    return $date->format('d') . ' ' . $months[(int)$date->format('m') - 1] . ' ' . $date->format('Y');
}

$query_hasil = "
    SELECT 
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik, hg.tanggal_tes,
        s.nama, s.kelas, s.jenis_kelamin, s.tahun_ajaran_id, s.jurusan 
    FROM hasil_gayabelajar hg
    JOIN siswa s ON hg.id_siswa = s.id_siswa
    WHERE hg.id_siswa = ? 
    ORDER BY hg.tanggal_tes DESC
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_hasil);
if (!$stmt) die("Error prepare query hasil: " . mysqli_error($koneksi));

mysqli_stmt_bind_param($stmt, "i", $id_siswa_int);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Error: Data hasil tes tidak ditemukan untuk siswa ID: {$id_siswa_int}.");
}

$hasil = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$skor_v = $hasil['skor_visual'];
$skor_a = $hasil['skor_auditori'];
$skor_k = $hasil['skor_kinestetik'];

$total_skor = $skor_v + $skor_a + $skor_k;

$persen_v = ($total_skor > 0) ? round(($skor_v / $total_skor) * 100, 1) : 0;
$persen_a = ($total_skor > 0) ? round(($skor_a / $total_skor) * 100, 1) : 0;
$persen_k = ($total_skor > 0) ? round(($skor_k / $total_skor) * 100, 1) : 0;

$jenis_kelamin = ($hasil['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';
$kelas_jurusan = htmlspecialchars($hasil['kelas']) . " " . htmlspecialchars($hasil['jurusan']); 

$id_tahun_ajaran = intval($hasil['tahun_ajaran_id'] ?? 0);
$tahun_ajaran = "T.A. Tidak Diketahui";

if ($id_tahun_ajaran > 0) {
    $query_ta = mysqli_query($koneksi, "SELECT tahun FROM tahun_ajaran WHERE id_tahun = $id_tahun_ajaran");
    if ($query_ta && mysqli_num_rows($query_ta) > 0) {
        $data_ta = mysqli_fetch_assoc($query_ta);
        $tahun_ajaran = htmlspecialchars($data_ta['tahun'] ?? "T.A. Tidak Diketahui");
    }
}

$tanggal_laporan = format_date_indo($hasil['tanggal_tes'] ?? date('Y-m-d')); 

$skor_tertinggi = max($skor_v, $skor_a, $skor_k);

$tipe_dominan = [];
if ($skor_v == $skor_tertinggi) $tipe_dominan[] = 'V';
if ($skor_a == $skor_tertinggi) $tipe_dominan[] = 'A';
if ($skor_k == $skor_tertinggi) $tipe_dominan[] = 'K';

$tipe_dominan_string = implode("','", $tipe_dominan);
$nama_tipe_dominan = [];
$keterangan_lengkap = [];

if (empty($tipe_dominan_string)) {
    $tipe_dominan_string = "''";
}

$query_keterangan = mysqli_query($koneksi, "
    SELECT kode_tipe, nama_tipe, deskripsi, saran 
    FROM keterangan_gaya_belajar 
    WHERE kode_tipe IN ('$tipe_dominan_string')
");

while ($row = mysqli_fetch_assoc($query_keterangan)) {
    $nama_tipe_dominan[] = $row['nama_tipe'];
    $keterangan_lengkap[] = $row; 
}

$judul_hasil = implode(" & ", $nama_tipe_dominan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Laporan Hasil Tes Gaya Belajar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <style>
        html { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 10.5pt; 
        }

        #report-overlay {
            display: none;
            position: fixed; 
            top: 0; 
            left: 0;
            width: 100%; 
            height: 100%;
            background-color: #f0f0f0;
            overflow-y: auto;
            z-index: 50;
        }

        #report-content {
            background-color: white;
            padding: 1rem;
            margin: 0 auto;
        }

        .data-siswa-table { 
            flex-grow: 1; 
        }
        
        .chart-border {
            border: none; 
            padding: 0; 
            border-radius: 0;
        }

        .score-box {
            display: flex;
            flex-direction: row; 
            justify-content: space-around;
            align-items: flex-start; 
            width: 100%;
            padding-bottom: 20px;
            border-bottom: 1px solid #ccc; 
        }
        
        .chart-visual-container {
             width: 50%;
             max-width: 350px;
             min-width: 300px;
        }
        
        .data-visual-container {
             width: 40%;
             min-width: 250px;
             margin-top: 10px;
        }
        .data-visual-table td {
             padding: 4px 8px;
             border-bottom: 1px dashed #ddd;
        }
        .data-visual-table th {
             padding: 6px 8px;
             background-color: #f0f0f0;
             border-bottom: 2px solid #aaa;
        }

        @media (min-width: 1024px) {
            #report-content {
                max-width: 1000px; 
                padding: 3rem; 
                margin: 2rem auto;
                box-shadow: 0 0 15px rgba(0,0,0,0.2); 
                border-radius: 0.5rem;
            }
        }
        
        @media (max-width: 1023px) {
             #report-content {
                width: 800px; 
                margin: 1rem auto;
                padding: 1.5rem;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            #report-overlay {
                overflow-x: auto; 
            }
            .score-box {
                flex-direction: column;
                align-items: center;
            }
            .chart-visual-container, .data-visual-container {
                width: 90%;
                margin-top: 20px;
            }
        }


       @media print {
        body > *:not(#report-overlay) { 
            display: none !important; 
        }
        
        #report-overlay {
            display: block !important;
            position: absolute !important;
            top: 0; 
            left: 0;
            width: 100% !important;
            height: auto !important; 
            background-color: white !important;
            overflow: visible !important;
        }
        
        #report-content {
            max-width: 21cm !important; 
            width: 21cm !important;
            height: auto !important;
            padding: 0.8cm 1cm !important; 
            margin: 0 auto !important; 
            box-shadow: none !important; 
            border-radius: 0 !important;
        }
        
        @page { 
            size: A4; 
            margin: 0; 
        }

        .header-laporan {
            margin-bottom: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        .data-siswa-table {
            margin-bottom: 0.8rem !important;
        }
        .section-title {
            margin-top: 0.8rem !important; 
            border-bottom: 1px solid black !important; 
            padding-bottom: 5px !important;
        }

        .score-box {
            display: flex !important; 
            flex-direction: row !important; 
            width: 100% !important; 
            justify-content: space-between !important; 
            align-items: center !important;
            margin: 0 !important;
            padding-top: 15px !important;
            padding-bottom: 20px !important;
            border-bottom: 1px solid black !important; 
        }
        
        .chart-visual-container {
            width: 50% !important; 
            height: 250px !important;
            max-width: 350px !important; 
            margin: 0 !important;
            display: block !important;
        }
        
        .data-visual-container {
            width: 45% !important; 
            margin: 0 !important;
        }
        .data-visual-table {
            width: 100% !important;
            font-size: 10pt !important;
        }
        .data-visual-table th, .data-visual-table td {
             padding: 3px 6px !important;
        }
        .data-visual-table td {
             border-bottom: 1px dashed #ccc !important;
        }
        
        .chart-border {
            border: none !important;
            padding: 0 !important;
        }
        
        .dominan-text {
            display: none !important; 
        }
        
        .tanda-tangan {
            margin-top: 30px !important; 
            text-align: justify;
            justify-content: space-between !important;
        }
        .action-buttons-surat { display: none !important; }

        .h-10 { height: 70px; } 

    }
    </style>
</head>
<body class="bg-gray-50 antialiased leading-relaxed">

    <div id="main-content" class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-extrabold text-gray-800 text-center mb-6">Laporan Siswa Ditemukan!</h1>
            
            <p class="text-lg text-gray-600 text-center mb-10">
                Anda sedang melihat laporan Gaya Belajar untuk siswa: <span class="font-bold text-indigo-800"><?= htmlspecialchars($hasil['nama']); ?></span>.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <button id="openReportButton" class="block p-6 bg-indigo-600 text-white rounded-xl shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                    <div class="flex flex-col items-center justify-center h-full">
                        <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h2 class="text-xl font-bold mb-1">Cek Hasil dan Pratinjau</h2>
                        <p class="text-sm text-center opacity-90">Lihat laporan lengkap.</p>
                    </div>
                </button>
                
                <a href="javascript:void(0);" onclick="goBack()" class="block p-6 bg-white border-2 border-gray-100 text-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-blue-200 transition-all group">
    <div class="flex flex-col items-center justify-center h-full">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
        </div>
        <h2 class="text-lg font-black mb-1 group-hover:text-blue-600 transition-colors">Kembali ke Daftar</h2>
        <p class="text-xs text-center text-gray-400 font-medium">Klik untuk kembali ke halaman sebelumnya.</p>
    </div>
</a>

<script>
function goBack() {
    const previousPage = document.referrer;
    if (previousPage.includes('alumni.php')) {
        window.location.href = 'alumni.php';
    } else {
        window.location.href = 'hasil_tes.php';
    }
}
</script>

            </div>
        </div>
    </div>
    
    <div id="report-overlay">
        
        <div id="report-content" class="shadow-lg">
            
            <div class="header-laporan text-center mb-6 border-b-[3px] border-double border-black pb-3">
                <h2 class="m-0 text-[13pt] font-bold">LAPORAN HASIL TES GAYA BELAJAR</h2>
                <h2 class="m-0 text-[13pt] font-bold">BIMBINGAN KONSELING SMKN 2 BANJARMASIN</h2>
            </div>
            
            <table class="data-siswa-table mb-5">
                <tr><td class="label w-[140px] p-0 align-top">NAMA</td><td class="p-0">: <?= htmlspecialchars($hasil['nama']); ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">KELAS</td><td class="p-0">: <?= $kelas_jurusan; ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">JENIS KELAMIN</td><td class="p-0">: <?= htmlspecialchars($jenis_kelamin); ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">TAHUN PELAJARAN</td><td class="p-0">: <?= $tahun_ajaran; ?></td></tr>
            </table>

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-5 pb-1 border-b border-black">1. HASIL SKOR GAYA BELAJAR:</h4> 
            
                        <div class="score-box mt-5">

    <div class="chart-visual-container chart-border" style="height: 300px !important;"> 
        <canvas id="skorChart"></canvas>
    </div>

    <?php
    $data_gaya = [
        ['nama' => 'Visual (V)', 'skor' => $skor_v, 'persen' => $persen_v],
        ['nama' => 'Auditori (A)', 'skor' => $skor_a, 'persen' => $persen_a],
        ['nama' => 'Kinestetik (K)', 'skor' => $skor_k, 'persen' => $persen_k],
    ];

    usort($data_gaya, function ($a, $b) {
        return $b['skor'] <=> $a['skor'];
    });

    $skor_tertinggi = $data_gaya[0]['skor'];
    ?>

    <div class="data-visual-container mt-4">
        <table class="data-visual-table w-full text-left border border-black border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="text-center py-2">Gaya Belajar</th>
                    <th class="text-center py-2">Skor (Poin)</th>
                    <th class="text-center py-2">Persentase (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_gaya as $row): ?>
                    <?php 
                        $highlight = ($row['skor'] == $skor_tertinggi) ? 'bg-gray-200 font-semibold' : '';
                    ?>
                    <tr class="<?= $highlight; ?>">
                        <td class="text-center py-2"><?= htmlspecialchars($row['nama']); ?></td>
                        <td class="text-center py-2"><?= htmlspecialchars($row['skor']); ?></td>
                        <td class="text-center py-2"><?= htmlspecialchars($row['persen']); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

            <p class="text-center mt-10 font-bold text-gray-800 dominan-text">Gaya Belajar Dominan: <?= $judul_hasil; ?></p>

            <div class="tanda-tangan mt-10 flex justify-between text-[10.5pt]">
                <div class="text-center w-[45%]">
                    Mengetahui,<br>Kepala Sekolah SMKN 2 Banjarmasin     
                    <div style="height: 70px;"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_kepsek; ?></div>
                </div>
                <div class="text-center w-[45%]">
                    <?= $kota; ?>, <?= $tanggal_laporan; ?><br>Guru Bimbingan Konseling
                    <div style="height: 70px;"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_guru_bk; ?></div>
                </div>
            </div>

            <div class="action-buttons-surat mt-8 pt-4 border-t border-gray-300 flex justify-center space-x-4">
                <button class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold transition duration-300 hover:bg-indigo-700 w-1/2" onclick="window.print()">
                    Simpan / Cetak Laporan (ke PDF)
                </button>
                <button class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold transition duration-300 hover:bg-gray-300 w-1/2" onclick="closeReport()">
                    Kembali
                </button>
            </div>
            
        </div>
    </div>

<script>
    Chart.register(ChartDataLabels);

    const reportOverlay = document.getElementById('report-overlay');
    const openReportButton = document.getElementById('openReportButton');

    function openReport() {
        reportOverlay.style.display = 'block';
        document.body.style.overflow = 'hidden'; 
        reportOverlay.scrollTop = 0; 
        setTimeout(renderChart, 50); 
    }

    function closeReport() {
        reportOverlay.style.display = 'none';
        document.body.style.overflow = ''; 
    }

    if(openReportButton) {
        openReportButton.addEventListener('click', openReport);
    }
    
    let skorChartInstance = null;

    function renderChart() {
        if (skorChartInstance) {
            skorChartInstance.destroy();
        }
        
        const ctx = document.getElementById('skorChart').getContext('2d');
        const data = {
            labels: ['Visual (V)', 'Auditori (A)', 'Kinestetik (K)'],
            datasets: [{
                label: 'Skor',
                data: [<?= $skor_v; ?>, <?= $skor_a; ?>, <?= $skor_k; ?>],
                backgroundColor: [
                    'rgba(0, 150, 136, 0.9)', 
                    'rgba(255, 152, 0, 0.9)', 
                    'rgba(3, 169, 244, 0.9)'  
                ],
                borderColor: 'white', 
                borderWidth: 2,
            }]
        };
        
        const totalSkor = <?= $total_skor; ?>;

        skorChartInstance = new Chart(ctx, {
            type: 'pie', 
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true, 
                        position: 'bottom', 
                        labels: {
                             font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    const percentage = (context.parsed / totalSkor * 100).toFixed(1) + '%';
                                    label += percentage + ' (' + context.parsed + ' Pts)';
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: { 
                        formatter: (value, context) => {
                            const percentage = (value / totalSkor * 100).toFixed(1) + '%';
                            return percentage + '\n(' + value + ')'; 
                        },
                        color: 'white', 
                        font: { 
                            weight: 'bold', 
                            size: 11
                        },
                        anchor: 'center', 
                        align: 'center'
                    }
                }
            }
        });
    }
    
    window.onload = function() {
        openReport();
    };
</script>

</body>
</html>