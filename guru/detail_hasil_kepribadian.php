<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa_int = (int) $_GET['id_siswa'];

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru Bimbingan dan Konseling';
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

// --- Ambil hasil kepribadian TERBARU milik siswa ini (mengikuti pola detail_hasil_gayabelajar.php) ---
$query_hasil = "
    SELECT
        hk.id_hasil, hk.id_sesi, hk.skor_total, hk.tipe, hk.tanggal_tes,
        s.nama, s.kelas, s.jenis_kelamin, s.tahun_ajaran_id, s.jurusan
    FROM hasil_kepribadian hk
    JOIN siswa s ON hk.id_siswa = s.id_siswa
    WHERE hk.id_siswa = ?
    ORDER BY hk.tanggal_tes DESC
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_hasil);
if (!$stmt) die("Error prepare query hasil: " . mysqli_error($koneksi));

mysqli_stmt_bind_param($stmt, "i", $id_siswa_int);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Error: Data hasil Tes Kepribadian tidak ditemukan untuk siswa ID: {$id_siswa_int}.");
}

$hasil = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

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

// --- Interpretasi (tipe, deskripsi, saran) dari tabel keterangan_kepribadian ---
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

// --- Ambil seluruh jawaban 1-10 (join soal_kepribadian untuk teks soal & opsi) ---
$daftar_jawaban = [];
$q_jawaban = mysqli_query($koneksi, "
    SELECT sk.nomor, sk.pernyataan, sk.opsi_a, sk.opsi_b, sk.opsi_c, sk.opsi_d, jk.jawaban
    FROM jawaban_kepribadian jk
    JOIN soal_kepribadian sk ON jk.id_soal = sk.id_soal
    WHERE jk.id_sesi = " . (int) $hasil['id_sesi'] . "
    ORDER BY sk.nomor ASC
");
if ($q_jawaban) {
    while ($row = mysqli_fetch_assoc($q_jawaban)) {
        $opsi_map = ['A' => $row['opsi_a'], 'B' => $row['opsi_b'], 'C' => $row['opsi_c'], 'D' => $row['opsi_d']];
        $row['teks_jawaban'] = $opsi_map[$row['jawaban']] ?? '-';
        $daftar_jawaban[] = $row;
    }
}

// Warna badge per tipe, biar informatif sekilas pandang.
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

        .jawaban-item { break-inside: avoid; }

        @media print {
            #namaGuru { display: none !important; }
            #namaGuruCetak { margin-top: 75px !important; }

            body > *:not(#report-overlay) { display: none !important; }
            #report-overlay {
                display: block !important; position: absolute !important; top: 0; left: 0;
                width: 100% !important; height: auto !important; background-color: white !important; overflow: visible !important;
            }
            #report-content {
                max-width: 21cm !important; width: 21cm !important; height: auto !important;
                padding: 0.8cm 1cm !important; margin: 0 auto !important; box-shadow: none !important; border-radius: 0 !important;
            }
            @page { size: A4; margin: 0; }
            .header-laporan { margin-bottom: 0.5rem !important; padding-bottom: 0.5rem !important; }
            .data-siswa-table { margin-bottom: 0.8rem !important; }
            .section-title { margin-top: 0.8rem !important; border-bottom: 1px solid black !important; padding-bottom: 5px !important; }
            .action-buttons-surat { display: none !important; }
            .tanda-tangan { margin-top: 30px !important; text-align: justify; justify-content: space-between !important; }
            .h-10 { height: 70px; }
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
                Laporan Hasil Tes Kepribadian
            </h1>

            <p class="text-gray-600 text-base sm:text-lg max-w-2xl mx-auto">
                Anda sedang melihat laporan Tes Kepribadian untuk siswa
                <span class="font-bold text-[#1F5C5B]"><?= htmlspecialchars($hasil['nama']); ?></span>.
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
                        Lihat laporan lengkap beserta rincian jawaban siswa.
                    </p>
                </div>
            </button>

            <a href="hasil_tes.php"
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
                        Kembali ke Daftar
                    </h2>

                    <p class="text-sm sm:text-base opacity-80 max-w-xs">
                        Kembali ke halaman Data Hasil Per Siswa.
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
                <tr>
                    <td class="label w-[140px] p-0 align-top">NAMA</td>
                    <td class="p-0">: <?= htmlspecialchars($hasil['nama']); ?></td>
                </tr>
                <tr>
                    <td class="label w-[140px] p-0 align-top">KELAS</td>
                    <td class="p-0">: <?= $kelas_jurusan; ?></td>
                </tr>
                <tr>
                    <td class="label w-[140px] p-0 align-top">JENIS KELAMIN</td>
                    <td class="p-0">: <?= htmlspecialchars($jenis_kelamin); ?></td>
                </tr>
                <tr>
                    <td class="label w-[140px] p-0 align-top">TAHUN PELAJARAN</td>
                    <td class="p-0">: <?= $tahun_ajaran; ?></td>
                </tr>
                <tr>
                    <td class="label w-[140px] p-0 align-top">TANGGAL TES</td>
                    <td class="p-0">: <?= $tanggal_laporan; ?></td>
                </tr>
            </table>

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-5 pb-1 border-b border-black">1. HASIL SKOR KEPRIBADIAN:</h4>

            <div class="mt-4 flex flex-col sm:flex-row items-center gap-5 pb-5 border-b border-gray-300">
                <div class="flex-shrink-0 w-20 h-20 rounded-full border-4 flex items-center justify-center"
                     style="border-color: <?= $badge_color['border']; ?>; background-color: <?= $badge_color['bg']; ?>;">
                    <span class="text-2xl font-extrabold" style="color: <?= $badge_color['text']; ?>;"><?= $skor_total; ?></span>
                </div>
                <div class="flex-1">
                    <p class="text-[9pt] font-semibold uppercase tracking-wide text-gray-500">Tipe Kepribadian</p>
                    <span class="inline-block mt-1 px-3 py-1 rounded-full text-[10.5pt] font-bold"
                          style="background-color: <?= $badge_color['bg']; ?>; color: <?= $badge_color['text']; ?>; border: 1px solid <?= $badge_color['border']; ?>;">
                        <?= htmlspecialchars($nama_tipe); ?>
                    </span>
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

            <h4 class="section-title text-[11.5pt] font-bold text-left mt-6 pb-1 border-b border-black">2. RINCIAN JAWABAN:</h4>

            <div class="mt-4 space-y-3">
                <?php foreach ($daftar_jawaban as $item): ?>
                <div class="jawaban-item border border-gray-300 rounded-md p-3">
                    <p class="font-semibold text-[10.5pt]">
                        <?= $item['nomor']; ?>. <?= htmlspecialchars($item['pernyataan']); ?>
                    </p>
                    <p class="text-[10.5pt] mt-1">
                        Jawaban : <span class="font-bold"><?= htmlspecialchars($item['jawaban']); ?></span>
                         <?= htmlspecialchars($item['teks_jawaban']); ?>
                    </p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($daftar_jawaban)): ?>
                <p class="text-sm text-gray-500 italic">Detail rincian jawaban tidak ditemukan.</p>
                <?php endif; ?>
            </div>

            <div class="tanda-tangan mt-10 flex justify-between text-[10.5pt]">
                <div class="text-center w-[45%]">
                    Mengetahui,<br>
                    Kepala Sekolah SMKN 2 Banjarmasin
                    <div style="height:70px;"></div>
                    <div class="ttd-placeholder mt-2 leading-loose underline font-bold"><?= $nama_kepsek; ?></div>
                </div>

                <div class="text-center w-[45%]">
                    <?= $kota; ?>, <?= $tanggal_laporan; ?>
                    <br>
                    Guru Bimbingan dan Konseling

                    <select id="namaGuru" onchange="ubahGuru()" class="mt-3 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-200">
                        <option value="Pahrurazi, S.Pd">Pahrurazi, S.Pd</option>
                        <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
                        <option value="Putri Hidayatie, S.Pd">Putri Hidayatie, S.Pd</option>
                        <option value="Rini Rodhiati, S.Pd">Rini Rodhiati, S.Pd</option>
                        <option value="Gusti Muhammad Fajri Ramadhan, S.Pd">Gusti Muhammad Fajri Ramadhan, S.Pd</option>
                        <option value="Desy Arianti, S.Pd">Desy Arianti, S.Pd</option>
                        <option value="Khalisatun Ni'mah, S.Pd">Khalisatun Ni'mah, S.Pd</option>
                        <option value="Tiara Wulansari, S.Pd">Tiara Wulansari, S.Pd</option>
                        <option value="Dhea Nur Aziza, S.Pd">Dhea Nur Aziza, S.Pd</option>
                        <option value="Abdul Basith, S.Pd">Abdul Basith, S.Pd</option>
                    </select>
                    <div id="namaGuruCetak" class="ttd-placeholder mt-11 leading-loose underline font-bold"><?= $nama_guru_bk; ?></div>
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

            <script>
                function ubahGuru() {
                    let guru = document.getElementById("namaGuru").value;
                    if (guru !== "") {
                        document.getElementById("namaGuruCetak").innerHTML = guru;
                    }
                }
            </script>
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
    </div>

</body>

</html>