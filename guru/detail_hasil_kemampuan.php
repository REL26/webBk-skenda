<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa_int = (int) $_GET['id_siswa'];

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru Bimbingan Konseling';
$nama_kepsek = "Novie Bambang Rumadi, S.T., M.Pd"; 
$nama_guru_bk = "...";      
$kota = "Banjarmasin"; 

$q_data_utama = "
    SELECT 
        s.nama AS nama_siswa, 
        s.kelas,          
        s.jurusan,            
        s.jenis_kelamin, 
        s.tanggal_lahir, 
        hk.skor_A, hk.skor_B, hk.skor_C, hk.skor_D, 
        hk.skor_E, hk.skor_F, hk.skor_G, hk.skor_H, 
        hk.tanggal_tes
    FROM 
        hasil_kecerdasan hk
    JOIN 
        siswa s ON hk.id_siswa = s.id_siswa
    WHERE 
        hk.id_siswa = ?
    ORDER BY hk.tanggal_tes DESC
    LIMIT 1
"; 

$stmt = mysqli_prepare($koneksi, $q_data_utama);
if (!$stmt) die("Error prepare query hasil: " . mysqli_error($koneksi));

mysqli_stmt_bind_param($stmt, "i", $id_siswa_int);
mysqli_stmt_execute($stmt);
$result_data_utama = mysqli_stmt_get_result($stmt);
$data_utama = mysqli_fetch_assoc($result_data_utama);
$tanggal_laporan = date('d F Y', strtotime($data_utama['tanggal_tes']));
mysqli_stmt_close($stmt);

if (!$data_utama) {
    die("Error: Data hasil tes tidak ditemukan untuk siswa ID: {$id_siswa_int}.");
}

$skor_tipe = [];
$tipe_dominan = [];
$max_skor = 0;

foreach(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $tipe) {
    $skor = $data_utama['skor_' . $tipe];
    $skor_tipe[$tipe] = $skor;
    if ($skor > $max_skor) {
        $max_skor = $skor;
    }
}

foreach($skor_tipe as $tipe => $skor) {
    if ($skor == $max_skor) {
        $tipe_dominan[] = $tipe;
    }
}

$tipe_list_str = "'" . implode("', '", $tipe_dominan) . "'";
if(empty($tipe_list_str)) $tipe_list_str = "'NO_TIPE'";

$q_data_detail = "
    SELECT 
        nama_tipe, 
        deskripsi, 
        bidang_studi 
    FROM 
        keterangan_kecerdasan 
    WHERE 
        kode_tipe IN ($tipe_list_str)
";

$result_data_detail = mysqli_query($koneksi, $q_data_detail);
$keterangan_lengkap = mysqli_fetch_all($result_data_detail, MYSQLI_ASSOC);

$nama_tipe_dominan = array_column($keterangan_lengkap, 'nama_tipe');
$judul_hasil_dominan = implode(" & ", $nama_tipe_dominan);

$data_utama['kelas_jurusan'] = htmlspecialchars($data_utama['kelas']) . " " . htmlspecialchars($data_utama['jurusan']);
$data_utama['jenis_kelamin'] = ($data_utama['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan';

$data_utama['judul_hasil'] = 'Laporan Hasil Tes Kemampuan';

foreach($skor_tipe as $tipe => $skor) {
    unset($data_utama['skor_' . $tipe]);
}
unset($data_utama['kelas']);
unset($data_utama['jurusan']);

$data = array_merge($data_utama, [
    'keterangan_lengkap' => $keterangan_lengkap,
    'judul_hasil_dominan' => $judul_hasil_dominan,
    'skor_tipe' => $skor_tipe,
    'nama_kepsek' => $nama_kepsek,
    'nama_guru_bk' => $nama_guru_bk,
    'kota' => $kota
]);
extract($data); 

$tanggal_laporan = $tanggal_laporan ?? date('d F Y'); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Laporan Hasil Tes Kemampuan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <style>
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

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
            position: relative;
        }

        #school-logo {
            display: none; 
            position: absolute;
            width: 70px; 
            height: auto;
            top: 1cm; 
            left: 1cm;
        }

        .data-siswa {
              align-items: flex-start;
        }
        .data-siswa-table {
            flex-grow: 1;
        }
        .tanggal-lahir-box {
            flex-shrink: 0; 
            margin-left: 0.5rem; 
            width: 180px; 
            margin-top: 0 !important; 
        }
    
        @media (min-width: 1024px) {
            .data-siswa-table { 
                width: calc(100% - 200px); 
            }
            #report-content {
                max-width: 1000px; 
                padding: 3rem; 
                margin: 2rem auto;
                box-shadow: 0 0 15px rgba(0,0,0,0.2);
                border-radius: 0.5rem;
            }
            .tanda-tangan {
                flex-direction: row !important;
                gap: 0 !important;
            }
            .data-siswa {
                flex-direction: row !important;
            }
        }

        @media (max-width: 1023px) and (min-width: 641px) {
            #report-content {
                width: 95% !important; 
                margin: 1rem auto;
                padding: 1.5rem;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            #report-overlay {
                overflow-x: auto; 
            }
            .tanda-tangan {
                display: none !important; 
            }
            .data-siswa {
                flex-direction: row !important;
            }
            .tanggal-lahir-box {
                width: 180px !important;
                margin-left: 0.5rem !important;
            }
        }

        @media (max-width: 640px) {
            #report-content {
                width: 100% !important;
                padding: 0.5rem !important; 
                margin: 0 !important;
            }
            .data-siswa {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            .data-siswa-table {
                width: 100% !important;
            }
            .tanggal-lahir-box {
                width: 100% !important;
                margin-left: 0 !important;
                margin-top: 0.5rem !important;
            }
            .tanda-tangan {
                display: none !important; 
            }
            .action-buttons-surat {
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 0.5rem !important;
            }
            .action-buttons-surat button {
                width: 50% !important;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                font-size: 0.9rem;
            }
        }
        
        .h-10 { height: 70px; }


        @media print {
            
            body > *:not(#report-overlay) {
                display: none !important;
            }
            
            #report-overlay {
                display: block !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                padding: 0 !important;
                background-color: white !important;
                overflow: visible !important;
            }

            #report-content {
                max-width: 21cm !important;
                width: 21cm !important;
                height: 29.7cm !important; 
                padding: 1cm !important; 
                margin: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                position: relative !important;
            }

            #school-logo {
                display: block !important;
                position: absolute !important;
                top: 0.5cm !important; 
                left: 1cm !important;
                width: 70px !important;
                height: auto !important;
            }
            
            .header-laporan {
                padding-left: 38px; 
            }

            .tanda-tangan {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                gap: 0 !important;
                width: 100% !important;
            }

            .data-siswa {
                flex-direction: row !important;
            }
            .tanggal-lahir-box {
                width: 180px !important;
                margin-left: 0.5rem !important;
            }

            @page {
                size: A4;
                margin: 0.5cm !important;
            }
            
            .action-buttons-surat { display: none !important; } 
            .hasil-table tr {
                page-break-inside: avoid;
            }
            #report-overlay > .h-10 {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 antialiased leading-relaxed">

    <div id="main-content" class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-2xl bg-white p-8 rounded-xl shadow-2xl">
            <h1 class="text-3xl font-extrabold text-gray-800 text-center mb-6">Laporan Siswa Ditemukan!</h1>
            
            <p class="text-lg text-gray-600 text-center mb-10">
                Anda sedang melihat laporan Tes Kemampuan untuk siswa: <span class="font-bold text-indigo-800"><?= $nama_siswa; ?></span>.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <button id="openReportButton" class="card block p-6 bg-indigo-600 text-white rounded-xl shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                    <div class="flex flex-col items-center justify-center h-full">
                        <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
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
        
        <div id="report-content">
            
            <!-- <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" id="school-logo" alt="Logo SMKN 2 Banjarmasin"> -->
            
            <div class="header-laporan text-center mb-6 border-b-[3px] border-double border-black pb-3">
                <h2 class="m-0 text-[13pt] font-bold">LAPORAN HASIL TES KEMAMPUAN</h2>
                <h2 class="m-0 text-[13pt] font-bold">BIMBINGAN KONSELING SMKN 2 BANJARMASIN</h2>
            </div>
            
            <div class="data-siswa mb-5 overflow-hidden flex items-start gap-2">
                <table class="data-siswa-table">
                    <tr>
                        <td class="label w-[140px] p-0 align-top">NAMA</td>
                        <td class="p-0">: <?= $nama_siswa; ?></td>
                    </tr>
                    <tr>
                        <td class="label w-[140px] p-0 align-top">KELAS & JURUSAN</td>
                        <td class="p-0">: <?= $kelas_jurusan; ?></td>
                    </tr>
                    <tr>
                        <td class="label w-[140px] p-0 align-top">JENIS KELAMIN</td>
                        <td class="p-0">: <?= $jenis_kelamin; ?></td>
                    </tr>
                </table>
                <div class="tanggal-lahir-box border border-black p-2 text-center text-[10.5pt]">
                    TANGGAL LAHIR<br>
                    <div class="mt-1 font-bold"><?= $tanggal_lahir; ?></div>
                </div>
            </div>

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-5 border-b border-black pb-1">1. HASIL ANGKET KEMAMPUAN:</h4>
            <div class="overflow-x-auto">
                <table class="hasil-table w-full border-collapse mt-1 table-fixed text-[10.5pt]">
                    <thead>
                        <tr>
                            <th class="border border-black p-2 align-top text-left bg-gray-100 font-bold w-1/4">Tipe Kemampuan Siswa</th>
                            <th class="border border-black p-2 align-top text-left bg-gray-100 font-bold">Penjelasan Tipe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keterangan_lengkap as $tipe) { ?>
                            <tr>
                                <td class="border border-black p-2 align-top text-left"><?= htmlspecialchars($tipe['nama_tipe']); ?></td>
                                <td class="border border-black p-2 align-top text-left whitespace-normal">
                                    <?= nl2br(htmlspecialchars($tipe['deskripsi'])); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-5 border-b border-black pb-1">2. SARAN PROFESI DI PERGURUAN TINGGI:</h4>
            <div class="overflow-x-auto">
                <table class="hasil-table w-full border-collapse mt-1 table-fixed text-[10.5pt]">
                    <thead>
                        <tr>
                            <th class="border border-black p-2 align-top text-left bg-gray-100 font-bold">Saran Jurusan / Bidang Studi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $semua_saran = [];
                        foreach ($keterangan_lengkap as $tipe) {
                            $semua_saran[] = htmlspecialchars($tipe['bidang_studi']);
                        }
                        $saran_tergabung = implode('<br>', $semua_saran);
                        ?>
                        <tr>
                            <td class="border border-black p-2 align-top text-left whitespace-normal">
                                <?= $saran_tergabung; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="tanda-tangan mt-10 flex justify-between text-[10.5pt] flex-col gap-5">
                <div class="w-full text-center lg:w-[45%]">
                    Mengetahui,<br>
                    Kepala Sekolah SMKN 2 Banjarmasin
                    <div class="h-10"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_kepsek; ?></div>
                </div>
                <div class="w-full text-center lg:w-[45%]">
                    <?= $kota; ?>, <?= $tanggal_laporan; ?><br>
                    Guru Bimbingan Konseling
                    <div class="h-10"></div>
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
    const reportOverlay = document.getElementById('report-overlay');
    const openReportButton = document.getElementById('openReportButton');

    function openReport() {
        reportOverlay.style.display = 'block';
        document.body.style.overflow = 'hidden'; 
        reportOverlay.scrollTop = 0; 
    }

    function closeReport() {
        reportOverlay.style.display = 'none';
        document.body.style.overflow = ''; 
    }

    if(openReportButton) {
        openReportButton.addEventListener('click', openReport);
    }
    
    window.onload = function() {
        openReport();
    };
</script>

</body>
</html>