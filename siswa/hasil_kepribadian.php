<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id_hasil']) || empty($_GET['id_hasil'])) {
    die("Error: ID Hasil tidak ditemukan.");
}

$id_hasil = intval($_GET['id_hasil']);
$id_siswa_int = (int) $_SESSION['id_siswa'];

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
    return $date->format('d') . ' ' . $months[(int) $date->format('m') - 1] . ' ' . $date->format('Y');
}

// Kepemilikan dicek lewat id_siswa sesi -- siswa hanya bisa lihat hasilnya sendiri.
$query_hasil = mysqli_query($koneksi, "
    SELECT
        hk.skor_total, hk.tipe, hk.tanggal_tes,
        s.nama, s.kelas, s.jurusan, s.jenis_kelamin, s.tahun_ajaran_id
    FROM hasil_kepribadian hk
    JOIN siswa s ON hk.id_siswa = s.id_siswa
    WHERE hk.id_hasil = $id_hasil AND hk.id_siswa = $id_siswa_int
");

if (!$query_hasil || mysqli_num_rows($query_hasil) == 0) {
    die("Error: Data hasil tes tidak ditemukan atau tidak memiliki akses.");
}

$hasil = mysqli_fetch_assoc($query_hasil);

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

// Interpretasi (tipe, deskripsi, saran) -- sama seperti detail_hasil_kepribadian.php
$nama_tipe = 'Belum dapat ditentukan';
$deskripsi = '';
$saran = '';
if (!empty($hasil['tipe'])) {
    $kode_tipe_esc = mysqli_real_escape_string($koneksi, $hasil['tipe']);
    $q_ket = mysqli_query($koneksi, "SELECT * FROM keterangan_kepribadian WHERE kode_tipe = '$kode_tipe_esc' LIMIT 1");
    $keterangan = $q_ket ? mysqli_fetch_assoc($q_ket) : null;
    if ($keterangan) {
        $nama_tipe = $keterangan['nama_tipe'];
        $deskripsi = $keterangan['deskripsi'];
        $saran     = $keterangan['saran'];
    }
}

$skor_total = (int) $hasil['skor_total'];
$persen_skor = round((($skor_total - 10) / (40 - 10)) * 100);
$persen_skor = max(0, min(100, $persen_skor));

// Warna badge per tipe -- identik dengan detail_hasil_kepribadian.php supaya tampilan konsisten guru & siswa.
$badge_color_map = [
    'ekstrovert'        => ['bg' => '#FFF3E0', 'text' => '#E65100', 'border' => '#FFB74D'],
    'ambivert'          => ['bg' => '#E3F2FD', 'text' => '#1565C0', 'border' => '#64B5F6'],
    'introvert'         => ['bg' => '#F3E5F5', 'text' => '#6A1B9A', 'border' => '#BA68C8'],
    'sangat_hati_hati'  => ['bg' => '#ECEFF1', 'text' => '#37474F', 'border' => '#90A4AE'],
];
$badge_color = $badge_color_map[$hasil['tipe']] ?? ['bg' => '#F1F5F9', 'text' => '#334155', 'border' => '#94A3B8'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Tes Kepribadian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <style>
        html {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
        }

        #report-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: #f0f0f0;
            overflow-y: auto;
            z-index: 50;
        }

        #report-content {
            background-color: white;
            padding: 1rem;
            margin: 0 auto;
        }

        .data-siswa-table { flex-grow: 1; }

        @media (min-width: 1024px) {
            #report-content {
                max-width: 1000px; padding: 3rem; margin: 2rem auto;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); border-radius: 0.5rem;
            }
        }

        @media (max-width: 1023px) {
            #report-content {
                width: 800px; margin: 1rem auto; padding: 1.5rem;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            #report-overlay { overflow-x: auto; }
        }

        @media print {
            body > *:not(#report-overlay) { display: none !important; }
            #report-overlay {
                display: block !important; position: absolute !important; top: 0; left: 0;
                width: 100% !important; height: auto !important; background-color: white !important; overflow: visible !important;
            }
            #report-content {
                max-width: 21cm !important; width: 21cm !important; height: auto !important;
                padding: 0.8cm 1cm !important; margin: 0 auto !important; box-shadow: none !important; border-radius: 0 !important;
            }
            .section-title { margin-top: 0.8rem !important; border-bottom: 1px solid black !important; padding-bottom: 5px !important; }
            .tanda-tangan { margin-top: 30px !important; }
            .action-buttons-surat { display: none !important; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body class="bg-gray-50 antialiased leading-relaxed">

    <div id="main-content"
     class="min-h-screen flex items-center justify-center px-4 py-12
            bg-gradient-to-br from-[#0F3A3A] via-[#123E44] to-[#1F5F63]">

    <div class="w-full max-w-4xl
                bg-white/95 backdrop-blur
                rounded-md
                shadow-[0_30px_70px_-25px_rgba(0,0,0,0.4)]
                px-6 py-10 sm:px-10 sm:py-14 md:px-14">

        <div class="text-center mb-12">
            <h1 class="text-3xl sm:text-4xl md:text-5xl
                       font-extrabold
                       text-[#123E44]
                       mb-4">
                Hasil Laporan Tes Kepribadian
            </h1>

            <p class="text-gray-600 text-base sm:text-lg max-w-2xl mx-auto">
                Hasil tes kepribadian Anda telah berhasil diproses.
                Silakan pilih tindakan berikut untuk melanjutkan.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">

            <button id="openReportButton"
                    class="group relative overflow-hidden
                           rounded-3xl
                           px-6 py-10 sm:px-10
                           bg-gradient-to-br from-[#5FA8A1] to-[#4C8E89]
                           text-white
                           shadow-xl
                           hover:shadow-2xl
                           transition-all duration-300
                           focus:outline-none">

                <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition"></div>

                <div class="relative flex flex-col items-center text-center">
                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-6"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>

                    <h2 class="text-xl sm:text-2xl font-bold mb-2">
                        Cek Hasil & Pratinjau
                    </h2>

                    <p class="text-sm sm:text-base opacity-90 max-w-xs">
                        Lihat laporan hasil tes kepribadian secara lengkap dan terperinci.
                    </p>
                </div>
            </button>

            <a href="dashboard.php"
               class="group rounded-3xl
                      px-6 py-10 sm:px-10
                      border-2 border-[#5FA8A1]/50
                      bg-white
                      text-[#123E44]
                      hover:bg-[#5FA8A1]/10
                      hover:border-[#5FA8A1]
                      transition-all duration-300
                      shadow-md hover:shadow-lg">

                <div class="flex flex-col items-center text-center">
                    <svg class="w-14 h-14 sm:w-16 sm:h-16 mb-6 text-[#5FA8A1]"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/>
                    </svg>

                    <h2 class="text-xl sm:text-2xl font-bold mb-2">
                        Kembali ke Beranda
                    </h2>

                    <p class="text-sm sm:text-base opacity-80 max-w-xs">
                        Kembali ke halaman utama dashboard Bimbingan dan Konseling.
                    </p>
                </div>
            </a>

        </div>
    </div>
</div>

    <div id="report-overlay">
        <div id="report-content" class="shadow-lg">

            <div class="header-laporan text-center mb-6 border-b-[3px] border-double border-black pb-3">
                <h2 class="m-0 text-[13pt] font-bold">LAPORAN HASIL TES KEPRIBADIAN</h2>
                <h2 class="m-0 text-[13pt] font-bold">Bimbingan dan Konseling SMKN 2 BANJARMASIN</h2>
            </div>

            <table class="data-siswa-table mb-5">
                <tr><td class="label w-[140px] p-0 align-top">NAMA</td><td class="p-0">: <?= htmlspecialchars($hasil['nama']); ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">KELAS</td><td class="p-0">: <?= $kelas_jurusan; ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">JENIS KELAMIN</td><td class="p-0">: <?= htmlspecialchars($jenis_kelamin); ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">TAHUN PELAJARAN</td><td class="p-0">: <?= $tahun_ajaran; ?></td></tr>
                <tr><td class="label w-[140px] p-0 align-top">TANGGAL TES</td><td class="p-0">: <?= $tanggal_laporan; ?></td></tr>
            </table>

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-5 pb-1 border-b border-black">1. HASIL SKOR KEPRIBADIAN:</h4>

            <div class="mt-4 flex flex-col sm:flex-row items-center gap-5 pb-5 border-b border-gray-300">
                <div class="flex-shrink-0 w-20 h-20 rounded-full border-4 flex items-center justify-center"
                     style="border-color: <?= $badge_color['border']; ?>; background-color: <?= $badge_color['bg']; ?>;">
                    <span class="text-2xl font-extrabold" style="color: <?= $badge_color['text']; ?>;"><?= $skor_total; ?></span>
                </div>
                <div class="flex-1 w-full">
                    <p class="text-[9pt] font-semibold uppercase tracking-wide text-gray-500">Tipe Kepribadian Anda</p>
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-[10.5pt] font-bold"
                          style="background-color: <?= $badge_color['bg']; ?>; color: <?= $badge_color['text']; ?>; border: 1px solid <?= $badge_color['border']; ?>;">
                        <?= htmlspecialchars($nama_tipe); ?>
                    </span>
                    <div class="mt-3 bg-gray-100 rounded-full h-2 w-full max-w-sm">
                        <div class="h-2 rounded-full" style="width: <?= $persen_skor; ?>%; background-color: <?= $badge_color['border']; ?>;"></div>
                    </div>
                    <p class="text-[9pt] text-gray-500 mt-1">Skor total: <?= $skor_total; ?> dari rentang 10–40</p>
                </div>
            </div>

            <table class="data-visual-table w-full text-left border border-black border-collapse mt-4">
                <tbody>
                    <tr class="border-b border-black">
                        <td class="py-2 px-3 font-bold align-top w-[35%] bg-gray-200">Karakteristik</td>
                        <td class="py-2 px-3">
                            <?php foreach (explode("\n", $deskripsi) as $poin):
                                $poin = trim($poin);
                                if ($poin === '') continue; ?>
                                <div>&bull; <?= htmlspecialchars($poin); ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-bold align-top bg-gray-200">Saran</td>
                        <td class="py-2 px-3"><?= htmlspecialchars($saran); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-[9pt] rounded-lg p-3">
                Hasil ini bersifat gambaran umum kecenderungan kepribadian, bukan diagnosis psikologis formal. Diskusikan lebih lanjut dengan Guru BK jika Anda ingin memahami hasil ini lebih dalam.
            </div>

            <div class="tanda-tangan mt-10 flex justify-between text-[10.5pt]">
                <div class="text-center w-[45%]">
                    Mengetahui,<br>
                    Kepala Sekolah SMKN 2 Banjarmasin
                    <div style="height:70px;"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_kepsek; ?></div>
                </div>
                <div class="text-center w-[45%]">
                    <?= $kota; ?>, <?= $tanggal_laporan; ?><br>
                    Guru Bimbingan dan Konseling
                    <div style="height:70px;"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_guru_bk; ?></div>
                </div>
            </div>

            <div class="action-buttons-surat mt-8 pt-4 border-t border-gray-300 flex justify-center space-x-4">
                <button class="px-6 py-2 bg-[#1F5C5B] text-white rounded-lg font-semibold transition duration-300 hover:bg-[#174544] w-1/2" onclick="window.print()">
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

    if (openReportButton) {
        openReportButton.addEventListener('click', openReport);
    }
</script>

</body>
</html>