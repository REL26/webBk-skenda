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

$query_hasil = mysqli_query($koneksi, "
    SELECT
        hk.skor_total, hk.tipe, hk.tanggal_tes,
        s.nama, s.kelas, s.jurusan
    FROM hasil_kepribadian hk
    JOIN siswa s ON hk.id_siswa = s.id_siswa
    WHERE hk.id_hasil = $id_hasil AND hk.id_siswa = {$_SESSION['id_siswa']}
");

if (!$query_hasil || mysqli_num_rows($query_hasil) == 0) {
    die("Error: Data hasil tes tidak ditemukan atau tidak memiliki akses.");
}

$hasil = mysqli_fetch_assoc($query_hasil);

$keterangan = null;
if (!empty($hasil['tipe'])) {
    $kode_tipe_esc = mysqli_real_escape_string($koneksi, $hasil['tipe']);
    $q_ket = mysqli_query($koneksi, "SELECT * FROM keterangan_kepribadian WHERE kode_tipe = '$kode_tipe_esc' LIMIT 1");
    $keterangan = $q_ket ? mysqli_fetch_assoc($q_ket) : null;
}

$nama_tipe   = $keterangan['nama_tipe'] ?? 'Belum dapat ditentukan';
$deskripsi   = $keterangan['deskripsi'] ?? '';
$saran       = $keterangan['saran'] ?? '';
$kelas_jurusan = htmlspecialchars($hasil['kelas'] . ' ' . $hasil['jurusan']);
$tanggal_laporan = !empty($hasil['tanggal_tes']) ? date('d F Y', strtotime($hasil['tanggal_tes'])) : date('d F Y');

// Skala 10-40 dipakai untuk bar visual skor.
$skor_total = (int) $hasil['skor_total'];
$persen_skor = round((($skor_total - 10) / (40 - 10)) * 100);
$persen_skor = max(0, min(100, $persen_skor));

$localStorageKey = 'testAnswers_kepribadian_siswa' . $_SESSION['id_siswa'];
$do_cleanup = isset($_GET['cleanup']) && $_GET['cleanup'] === 'true';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hasil Tes Kepribadian | BK SMKN 2 Banjarmasin</title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { background-color: #f3f4f6; }
    @media print {
        .no-print { display: none !important; }
        body { background-color: #ffffff; }
        #report-card { box-shadow: none !important; }
    }
</style>
</head>
<body class="min-h-screen p-4 sm:p-8">

    <div id="report-card" class="max-w-3xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden">

        <div class="bg-gradient-to-br from-[#0F3A3A] to-[#2F6C6E] px-6 sm:px-10 py-8 text-white">
            <p class="text-xs uppercase tracking-widest text-white/70 font-semibold">Hasil Tes Kepribadian</p>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-1"><?= htmlspecialchars($hasil['nama']); ?></h1>
            <p class="text-sm text-white/80 mt-1"><?= $kelas_jurusan; ?> &middot; <?= $tanggal_laporan; ?></p>
        </div>

        <div class="px-6 sm:px-10 py-8">

            <div class="flex flex-col sm:flex-row items-center gap-6 pb-8 border-b border-gray-100">
                <div class="flex-shrink-0 w-24 h-24 rounded-full bg-emerald-50 border-4 border-emerald-500 flex items-center justify-center">
                    <span class="text-3xl font-extrabold text-emerald-600"><?= $skor_total; ?></span>
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Tipe Kepribadian Anda</p>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-1"><?= htmlspecialchars($nama_tipe); ?></h2>
                    <div class="mt-3 bg-gray-100 rounded-full h-2 w-full max-w-sm mx-auto sm:mx-0">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $persen_skor; ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Skor total: <?= $skor_total; ?> dari rentang 10–40</p>
                </div>
            </div>

            <?php if ($deskripsi): ?>
            <div class="mt-8">
                <h3 class="text-sm font-bold text-[#2F6C6E] uppercase tracking-wide mb-3">Karakteristik Anda</h3>
                <ul class="space-y-2">
                    <?php foreach (explode("\n", $deskripsi) as $poin):
                        $poin = trim($poin);
                        if ($poin === '') continue; ?>
                    <li class="flex items-start gap-2 text-sm text-gray-700">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                        <span><?= htmlspecialchars($poin); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($saran): ?>
            <div class="mt-6 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg p-4">
                <h3 class="text-sm font-bold text-emerald-700 mb-1">Saran Pengembangan Diri</h3>
                <p class="text-sm text-emerald-800"><?= htmlspecialchars($saran); ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-xs sm:text-sm rounded-lg p-4">
                Hasil ini bersifat gambaran umum kecenderungan kepribadian, bukan diagnosis psikologis formal. Diskusikan lebih lanjut dengan Guru BK jika Anda ingin memahami hasil ini lebih dalam.
            </div>

            <div class="no-print mt-8 flex flex-col sm:flex-row gap-3">
                <button onclick="window.print()" class="flex-1 flex items-center justify-center gap-2 px-5 py-3 bg-[#0F3A3A] text-white rounded-lg font-semibold hover:bg-[#123E44] transition text-sm">
                    <i class="fas fa-print"></i> Cetak / Simpan PDF
                </button>
                <a href="dashboard.php" class="flex-1 flex items-center justify-center gap-2 px-5 py-3 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition text-sm">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

<script>
    // Bersihkan progres localStorage tes ini karena sudah selesai & terkunci.
    <?php if ($do_cleanup): ?>
    localStorage.removeItem('<?= $localStorageKey; ?>');
    <?php endif; ?>
</script>
</body>
</html>