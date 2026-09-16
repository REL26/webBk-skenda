<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];

// Tabel tanggapan bintang (1-5) per jawaban LKPD siswa
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bk_tanggapan_lkpd (
    id_tanggapan INT(11) NOT NULL AUTO_INCREMENT,
    id_siswa INT(11) NOT NULL,
    id_pertanyaan INT(11) NOT NULL,
    id_materi INT(11) NOT NULL DEFAULT 0,
    rating TINYINT(1) NOT NULL DEFAULT 0,
    catatan TEXT NULL,
    id_guru INT(11) DEFAULT NULL,
    nama_guru VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tanggapan),
    UNIQUE KEY uniq_siswa_pertanyaan (id_siswa, id_pertanyaan),
    KEY id_materi (id_materi),
    KEY id_siswa (id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Pastikan kolom nama_guru ada (tabel lama)
$qColNg = mysqli_query($koneksi, "SHOW COLUMNS FROM bk_tanggapan_lkpd LIKE 'nama_guru'");
if (!$qColNg || mysqli_num_rows($qColNg) === 0) {
    @mysqli_query($koneksi, "ALTER TABLE bk_tanggapan_lkpd ADD COLUMN nama_guru VARCHAR(150) DEFAULT NULL AFTER id_guru");
}
// Unique key per siswa+materi+pertanyaan (id_pertanyaan=0 = tanggapan seluruh LKPD materi)
$qIdxTg = mysqli_query($koneksi, "SHOW INDEX FROM bk_tanggapan_lkpd WHERE Key_name = 'uniq_siswa_materi_pertanyaan'");
if (!$qIdxTg || mysqli_num_rows($qIdxTg) === 0) {
    @mysqli_query($koneksi, "ALTER TABLE bk_tanggapan_lkpd DROP INDEX uniq_siswa_pertanyaan");
    @mysqli_query($koneksi, "ALTER TABLE bk_tanggapan_lkpd ADD UNIQUE KEY uniq_siswa_materi_pertanyaan (id_siswa, id_materi, id_pertanyaan)");
}

$WARNA_FUNGSI_PHP = [
    'Pemahaman' => 'bg-blue-50 text-blue-700',
    'Pencegahan (Preventif)' => 'bg-green-50 text-green-700',
    'Pengentasan (Kuratif)' => 'bg-orange-50 text-orange-700',
    'Pemeliharaan dan Pengembangan' => 'bg-purple-50 text-purple-700',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $idm = (int) ($_POST['id_materi'] ?? 0);

    if ($action === 'list_progress') {
        
        $sasaran = [];
        $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
        if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran[] = $s;

        $jmlSlide = 0;
        $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1");
        if ($qc) $jmlSlide = (int) mysqli_fetch_assoc($qc)['jml'];

        $data = [];
        if (count($sasaran) > 0) {
            $kondisi = [];
            foreach ($sasaran as $s) {
                $kls = mysqli_real_escape_string($koneksi, $s['kelas']);
                $jur = mysqli_real_escape_string($koneksi, $s['jurusan']);
                $kondisi[] = "(s.kelas = '$kls' AND s.jurusan = '$jur')";
            }
            $where = implode(' OR ', $kondisi);

            $sql = "SELECT s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan,
                        COUNT(DISTINCT CASE WHEN ps.status_selesai = 1 AND sl.status_aktif = 1 THEN ps.id_slide END) AS jumlah_selesai,
                        MAX(ps.waktu_selesai) AS waktu_terakhir
                    FROM siswa s
                    LEFT JOIN bk_progress_slide ps ON ps.id_siswa = s.id_siswa AND ps.id_materi = $idm
                    LEFT JOIN bk_slide sl ON sl.id_slide = ps.id_slide
                    WHERE ($where)
                    GROUP BY s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan
                    ORDER BY s.kelas ASC, s.jurusan ASC, s.nama ASC";
            $q = mysqli_query($koneksi, $sql);
            if ($q) {
                while ($r = mysqli_fetch_assoc($q)) {
                    $selesai = (int) $r['jumlah_selesai'];
                    $status = 'belum_mulai';
                    if ($jmlSlide > 0 && $selesai >= $jmlSlide) $status = 'selesai';
                    else if ($selesai > 0) $status = 'berjalan';

                    $idSiswaRow = (int) $r['id_siswa'];
                    $punyaTanggapan = 0;
                    $qtg = mysqli_query($koneksi, "SELECT id_tanggapan FROM bk_tanggapan_lkpd WHERE id_siswa = $idSiswaRow AND id_materi = $idm AND id_pertanyaan = 0 AND rating >= 1 LIMIT 1");
                    if ($qtg && mysqli_fetch_assoc($qtg)) $punyaTanggapan = 1;

                    $data[] = [
                        'id_siswa' => $idSiswaRow,
                        'nis' => $r['nis'],
                        'nama' => $r['nama'],
                        'kelas' => $r['kelas'],
                        'jurusan' => $r['jurusan'],
                        'jumlah_selesai' => $selesai,
                        'jumlah_slide' => $jmlSlide,
                        'persen' => $jmlSlide > 0 ? round(($selesai / $jmlSlide) * 100) : 100,
                        'status' => $status,
                        'waktu_terakhir' => $r['waktu_terakhir'],
                        'punya_tanggapan' => $punyaTanggapan,
                    ];
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $data, 'jumlah_slide' => $jmlSlide]);
        exit;
    }

    if ($action === 'detail_siswa') {
        $id_siswa = (int) ($_POST['id_siswa'] ?? 0);

        $slides = [];
        $q = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1 ORDER BY urutan ASC, id_slide ASC");
        if ($q) {
            while ($sl = mysqli_fetch_assoc($q)) {
                $ids = (int) $sl['id_slide'];

                $selesai = false;
                $waktu = null;
                $qp = mysqli_query($koneksi, "SELECT status_selesai, waktu_selesai FROM bk_progress_slide WHERE id_siswa = $id_siswa AND id_slide = $ids LIMIT 1");
                if ($qp && ($rp = mysqli_fetch_assoc($qp))) {
                    $selesai = ((int) $rp['status_selesai']) === 1;
                    $waktu = $rp['waktu_selesai'];
                }

                $pertanyaan = [];
                if ((int) $sl['butuh_lkpd'] === 1) {
                    $qq = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = $ids ORDER BY urutan ASC, id_pertanyaan ASC");
                    if ($qq) {
                        while ($p = mysqli_fetch_assoc($qq)) {
                            $idp = (int) $p['id_pertanyaan'];
                            $jawaban = '';
                            $qj = mysqli_query($koneksi, "SELECT jawaban FROM bk_jawaban_lkpd WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp LIMIT 1");
                            if ($qj && ($rj = mysqli_fetch_assoc($qj))) $jawaban = $rj['jawaban'];
                            $pertanyaan[] = [
                                'id_pertanyaan' => $idp,
                                'teks_pertanyaan' => $p['teks_pertanyaan'],
                                'tipe_jawaban' => $p['tipe_jawaban'],
                                'jawaban' => $jawaban,
                            ];
                        }
                    }
                }

                $slides[] = [
                    'judul_slide' => $sl['judul_slide'],
                    'urutan' => (int) $sl['urutan'],
                    'selesai' => $selesai,
                    'waktu_selesai' => $waktu,
                    'butuh_lkpd' => (int) $sl['butuh_lkpd'] === 1,
                    'pertanyaan' => $pertanyaan,
                ];
            }
        }

        $qs = mysqli_query($koneksi, "SELECT nama, nis, kelas, jurusan FROM siswa WHERE id_siswa = $id_siswa LIMIT 1");
        $siswa = $qs ? mysqli_fetch_assoc($qs) : null;

        // Tanggapan bintang per LKPD (seluruh materi), id_pertanyaan = 0
        $tanggapan_lkpd = ['rating' => 0, 'catatan' => '', 'nama_guru' => ''];
        $qt = mysqli_query($koneksi, "SELECT t.rating, t.catatan, t.nama_guru, g.nama AS nama_guru_tbl
            FROM bk_tanggapan_lkpd t
            LEFT JOIN guru g ON g.id_guru = t.id_guru
            WHERE t.id_siswa = $id_siswa AND t.id_materi = $idm AND t.id_pertanyaan = 0 LIMIT 1");
        if ($qt && ($rt = mysqli_fetch_assoc($qt))) {
            $tanggapan_lkpd['rating'] = (int) $rt['rating'];
            $tanggapan_lkpd['catatan'] = $rt['catatan'] ?? '';
            $nm = trim((string) ($rt['nama_guru'] ?? ''));
            if ($nm === '') $nm = trim((string) ($rt['nama_guru_tbl'] ?? ''));
            $tanggapan_lkpd['nama_guru'] = $nm;
        }

        echo json_encode(['success' => true, 'siswa' => $siswa, 'slides' => $slides, 'tanggapan_lkpd' => $tanggapan_lkpd]);
        exit;
    }

    if ($action === 'simpan_tanggapan') {
        $id_siswa = (int) ($_POST['id_siswa'] ?? 0);
        // id_pertanyaan = 0 → tanggapan untuk seluruh LKPD materi (bukan per soal)
        $id_pertanyaan = 0;
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $catatan = trim((string) ($_POST['catatan'] ?? ''));
        $nama_guru = trim((string) ($_POST['nama_guru'] ?? ''));
        if ($id_siswa <= 0 || $idm <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
            exit;
        }
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Rating harus 1 sampai 5.']);
            exit;
        }
        if ($nama_guru === '') {
            echo json_encode(['success' => false, 'message' => 'Pilih nama guru terlebih dahulu.']);
            exit;
        }
        $catEsc = mysqli_real_escape_string($koneksi, $catatan);
        $namaEsc = mysqli_real_escape_string($koneksi, $nama_guru);
        $qCek = mysqli_query($koneksi, "SELECT id_tanggapan FROM bk_tanggapan_lkpd WHERE id_siswa = $id_siswa AND id_materi = $idm AND id_pertanyaan = 0 LIMIT 1");
        if ($qCek && mysqli_fetch_assoc($qCek)) {
            $ok = mysqli_query($koneksi, "UPDATE bk_tanggapan_lkpd SET rating = $rating, catatan = '$catEsc', id_guru = $id_guru_login, nama_guru = '$namaEsc' WHERE id_siswa = $id_siswa AND id_materi = $idm AND id_pertanyaan = 0");
        } else {
            $ok = mysqli_query($koneksi, "INSERT INTO bk_tanggapan_lkpd (id_siswa, id_pertanyaan, id_materi, rating, catatan, id_guru, nama_guru) VALUES ($id_siswa, 0, $idm, $rating, '$catEsc', $id_guru_login, '$namaEsc')");
        }
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan tanggapan: ' . mysqli_error($koneksi)]);
            exit;
        }
        echo json_encode(['success' => true, 'rating' => $rating, 'catatan' => $catatan, 'nama_guru' => $nama_guru]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    exit;
}

$id_materi = (int) ($_GET['id_materi'] ?? 0);

$materi = null;
if ($id_materi > 0) {
    $q = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $id_materi LIMIT 1");
    $materi = $q ? mysqli_fetch_assoc($q) : null;
}

$sasaran_list = [];
if ($materi) {
    $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $id_materi ORDER BY kelas ASC, jurusan ASC");
    if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran_list[] = $s['kelas'] . ' ' . $s['jurusan'];
}

$jumlah_slide_materi = 0;
if ($materi) {
    $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $id_materi AND status_aktif = 1");
    if ($qc) $jumlah_slide_materi = (int) mysqli_fetch_assoc($qc)['jml'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring Bimbingan Klasikal - Sistem BK</title>
  <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    html { overflow-y: scroll; }
    body { min-height: 100vh; max-width: 100%; overflow-x: hidden; }
    main { box-sizing: border-box; overflow-x: hidden; }
    @media (max-width: 767px) {
      main { margin-left: 0 !important; padding-left: 1rem; padding-right: 1rem; width: 100%; padding-top: 4.5rem; }
    }
    @media (min-width: 768px) { main { margin-left: 260px; } }

    table { min-width: 640px; }
    .badge-status-selesai { background: #dcfce7; color: #166534; }
    .badge-status-berjalan { background: #fef3c7; color: #92400e; }
    .badge-status-belum { background: #f3f4f6; color: #6b7280; }
    .progress-track { background: #e5e7eb; border-radius: 9999px; overflow: hidden; height: .5rem; }
    .progress-fill { height: 100%; border-radius: 9999px; transition: width .3s ease; }
    .action-btn { padding: .35rem; font-size: .95rem; }
    .star-rating-wrap { display: inline-flex; align-items: center; gap: .15rem; }
    .star-rating-wrap .star-btn { color: #d1d5db; transition: color .12s ease; cursor: pointer; }
    .star-rating-wrap .star-btn.is-on,
    .star-rating-wrap:hover .star-btn.is-preview {
      color: #fbbf24;
    }
    /* Saat hover: bintang sampai posisi hover menyala; yang setelahnya tetap abu */
    .star-rating-wrap:hover .star-btn { color: #d1d5db; }
    .star-rating-wrap:hover .star-btn.is-preview { color: #fbbf24; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="flex-grow p-4 md:p-8 flex flex-col">

<?php if (!$materi): ?>

  <div class="bg-white rounded-xl shadow-md p-8 text-center max-w-lg mx-auto mt-10">
    <i class="fas fa-circle-exclamation text-amber-500 text-4xl mb-3"></i>
    <h1 class="text-lg font-bold text-gray-800 mb-1">Materi Tidak Ditemukan</h1>
    <p class="text-sm text-gray-500 mb-5">Materi Bimbingan Klasikal yang Anda cari tidak tersedia atau sudah dihapus.</p>
    <a href="bimbinganklasikal.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
      <i class="fas fa-arrow-left"></i> Kembali ke Bimbingan Klasikal
    </a>
  </div>

<?php else: ?>

  <div class="mb-5">
    <a href="bimbinganklasikal.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 mb-3">
      <i class="fas fa-arrow-left"></i> Kembali ke Bimbingan Klasikal
    </a>
    <div class="bg-white rounded-xl shadow-md p-5 md:p-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-1.5">
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">
              <i class="fas fa-chart-line text-indigo-600 mr-1"></i> <?php echo htmlspecialchars($materi['judul']); ?>
            </h1>
            <?php if (!empty($materi['fungsi_layanan'])): ?>
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $WARNA_FUNGSI_PHP[$materi['fungsi_layanan']] ?? 'bg-gray-100 text-gray-600'; ?>">
              <?php echo htmlspecialchars($materi['fungsi_layanan']); ?>
            </span>
            <?php endif; ?>
          </div>
          <?php if (!empty($materi['deskripsi'])): ?>
          <p class="text-sm text-gray-500 mb-2 max-w-2xl"><?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?></p>
          <?php endif; ?>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
            <span><i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($materi['nama_guru_pembuat'] ?: '-'); ?></span>
            <span><i class="fas fa-layer-group mr-1"></i> <?php echo $jumlah_slide_materi; ?> slide</span>
            <span><i class="fas fa-users mr-1"></i>
              <?php echo count($sasaran_list) > 0 ? htmlspecialchars(implode(', ', $sasaran_list)) : 'Belum ada sasaran kelas'; ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="ringkasanStatMonitoring" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Total Siswa Sasaran</p>
      <p class="text-2xl font-bold text-gray-800" id="statTotalSiswa">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Sudah Selesai</p>
      <p class="text-2xl font-bold text-green-600" id="statSelesai">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Sedang Mengerjakan</p>
      <p class="text-2xl font-bold text-amber-600" id="statBerjalan">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Belum Mulai</p>
      <p class="text-2xl font-bold text-gray-500" id="statBelumMulai">-</p>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-md p-4 md:p-5 mb-4">
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-grow min-w-[200px]">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariSiswa" placeholder="Cari nama atau NIS siswa..." class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm" oninput="onFilterProgressBerubah()">
      </div>
      <select id="filterStatusProgress" class="px-3 py-2 border rounded-lg text-sm bg-white" onchange="onFilterProgressBerubah()">
        <option value="">Semua Status</option>
        <option value="selesai">Sudah Selesai</option>
        <option value="berjalan">Sedang Mengerjakan</option>
        <option value="belum_mulai">Belum Mulai</option>
      </select>
      <select id="filterKelasProgress" class="px-3 py-2 border rounded-lg text-sm bg-white" onchange="onFilterProgressBerubah()">
        <option value="">Semua Kelas</option>
      </select>
      <select id="filterTanggapanProgress" class="px-3 py-2 border rounded-lg text-sm bg-white" onchange="onFilterProgressBerubah()">
        <option value="">Semua Tanggapan</option>
        <option value="sudah">Sudah diberi tanggapan</option>
        <option value="belum">Belum diberi tanggapan</option>
      </select>
      <button type="button" onclick="resetFilterProgress()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-800 whitespace-nowrap" title="Reset semua filter">
        <i class="fas fa-rotate-left mr-1"></i> Reset Filter
      </button>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas</th>
            <th class="px-3 py-2 border-b">Progress</th>
            <th class="px-3 py-2 border-b text-center">Status</th>
            <th class="px-3 py-2 border-b">Aktivitas Terakhir</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelProgress">
          <tr><td colspan="6" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
    <div id="paginasiProgress" class="flex flex-wrap items-center justify-between gap-2 mt-3"></div>
  </div>

<?php endif; ?>

</main>

<div id="modalDetailSiswa" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b shrink-0 bg-white z-10">
      <h2 class="text-base font-bold text-gray-800"><i class="fas fa-user-graduate text-indigo-600 mr-1"></i> <span id="judulModalDetailSiswa">Detail Progress Siswa</span></h2>
      <button type="button" onclick="tutupModalDetailSiswa()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
    </div>
    <div class="p-5 overflow-y-auto flex-grow" id="isiModalDetailSiswa">
      <p class="text-center text-gray-400 py-6">Memuat data...</p>
    </div>
    <div class="px-5 py-3 border-t bg-gray-50 shrink-0 flex items-center justify-between gap-3 flex-wrap">
      <p class="text-xs text-gray-400" id="pesanSimpanTanggapan"></p>
      <div class="flex items-center gap-2 ml-auto">
        <button type="button" onclick="tutupModalDetailSiswa()" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-600 hover:bg-white">Tutup</button>
        <button type="button" id="btnSimpanTanggapan" onclick="simpanSemuaTanggapan()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">
          <i class="fas fa-save mr-1"></i> Simpan
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  const ID_MATERI = <?php echo (int) $id_materi; ?>;
  const JUMLAH_SLIDE_MATERI = <?php echo (int) $jumlah_slide_materi; ?>;
  const BARIS_PER_HALAMAN_PROGRESS = 15;
  const DAFTAR_GURU_TANGGAPAN = [
    'Pahrurazi, S.Pd',
    'Dian Riyani, S.Pd',
    'Putri Hidayatie, S.Pd',
    'Rini Rodhiati, S.Pd',
    'Gusti Muhammad Fajri Ramadhan, S.Pd',
    'Desy Arianti, S.Pd',
    "Khalisatun Ni'mah, S.Pd",
    'Tiara Wulansari, S.Pd',
    'Dhea Nur Aziza, S.Pd',
    'Abdul Basith, S.Pd'
  ];
  let halamanProgress = 1;
  let daftarProgressSiswa = [];

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function formatWaktu(waktu) {
    if (!waktu) return '<span class="text-gray-400">Belum ada aktivitas</span>';
    // Tampilkan angka jam dari DB apa adanya
    const m = String(waktu).trim().match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    if (!m) return escapeHtml(String(waktu));
    const bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return parseInt(m[3], 10) + ' ' + (bulan[parseInt(m[2], 10)] || m[2]) + ' ' + m[1] + ', ' + m[4] + ':' + m[5] + ' WITA';
  }

  const LABEL_STATUS = {
    selesai: { teks: 'Selesai', kelas: 'badge-status-selesai', warna_progress: '#16a34a' },
    berjalan: { teks: 'Sedang Mengerjakan', kelas: 'badge-status-berjalan', warna_progress: '#d97706' },
    belum_mulai: { teks: 'Belum Mulai', kelas: 'badge-status-belum', warna_progress: '#9ca3af' },
  };

  function kunciFilterProgress() {
    return 'bk_mon_filter_' + ID_MATERI;
  }

  function simpanFilterProgressKeUrl() {
    if (!ID_MATERI) return;
    const params = new URLSearchParams(window.location.search);
    params.set('id_materi', String(ID_MATERI));
    const q = (document.getElementById('cariSiswa')?.value || '').trim();
    const st = document.getElementById('filterStatusProgress')?.value || '';
    const kl = document.getElementById('filterKelasProgress')?.value || '';
    const tg = document.getElementById('filterTanggapanProgress')?.value || '';
    if (q) params.set('q', q); else params.delete('q');
    if (st) params.set('status', st); else params.delete('status');
    if (kl) params.set('kelas', kl); else params.delete('kelas');
    if (tg) params.set('tanggapan', tg); else params.delete('tanggapan');
    const url = window.location.pathname + '?' + params.toString();
    history.replaceState(null, document.title, url);
    try {
      localStorage.setItem(kunciFilterProgress(), JSON.stringify({ q, status: st, kelas: kl, tanggapan: tg }));
    } catch (e) {}
  }

  function muatFilterProgressTersimpan() {
    const params = new URLSearchParams(window.location.search);
    let q = params.get('q') || '';
    let st = params.get('status') || '';
    let kl = params.get('kelas') || '';
    let tg = params.get('tanggapan') || '';
    // Fallback localStorage jika URL kosong
    if (!q && !st && !kl && !tg) {
      try {
        const raw = localStorage.getItem(kunciFilterProgress());
        if (raw) {
          const o = JSON.parse(raw) || {};
          q = o.q || '';
          st = o.status || '';
          kl = o.kelas || '';
          tg = o.tanggapan || '';
        }
      } catch (e) {}
    }
    const elQ = document.getElementById('cariSiswa');
    const elSt = document.getElementById('filterStatusProgress');
    const elKl = document.getElementById('filterKelasProgress');
    const elTg = document.getElementById('filterTanggapanProgress');
    if (elQ) elQ.value = q;
    if (elSt) elSt.value = st;
    if (elTg) elTg.value = tg;
    // kelas di-set setelah opsi diisi di isiFilterKelas
    return { q, status: st, kelas: kl, tanggapan: tg };
  }

  function onFilterProgressBerubah() {
    simpanFilterProgressKeUrl();
    renderTabelProgress();
  }

  function resetFilterProgress() {
    const elQ = document.getElementById('cariSiswa');
    const elSt = document.getElementById('filterStatusProgress');
    const elKl = document.getElementById('filterKelasProgress');
    const elTg = document.getElementById('filterTanggapanProgress');
    if (elQ) elQ.value = '';
    if (elSt) elSt.value = '';
    if (elKl) elKl.value = '';
    if (elTg) elTg.value = '';
    try { localStorage.removeItem(kunciFilterProgress()); } catch (e) {}
    simpanFilterProgressKeUrl();
    renderTabelProgress();
  }

  function muatDataProgress() {
    if (!ID_MATERI) return;
    const filterAwal = muatFilterProgressTersimpan();
    const fd = new FormData();
    fd.append('action', 'list_progress');
    fd.append('id_materi', ID_MATERI);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;
        daftarProgressSiswa = data.data;
        isiFilterKelas(filterAwal.kelas || '');
        renderStatistikProgress();
        // Pastikan nilai filter masih terpasang setelah opsi kelas diisi
        const elQ = document.getElementById('cariSiswa');
        const elSt = document.getElementById('filterStatusProgress');
        if (elQ && filterAwal.q) elQ.value = filterAwal.q;
        if (elSt && filterAwal.status) elSt.value = filterAwal.status;
        const elTg = document.getElementById('filterTanggapanProgress');
        if (elTg && filterAwal.tanggapan) elTg.value = filterAwal.tanggapan;
        renderTabelProgress();
        simpanFilterProgressKeUrl();
      });
  }

  function isiFilterKelas(kelasTerpilih) {
    const select = document.getElementById('filterKelasProgress');
    const kelasUnik = [...new Set(daftarProgressSiswa.map(s => `${s.kelas} ${s.jurusan}`))].sort();
    select.innerHTML = '<option value="">Semua Kelas</option>' +
      kelasUnik.map(k => `<option value="${escapeHtml(k)}">${escapeHtml(k)}</option>`).join('');
    if (kelasTerpilih && kelasUnik.includes(kelasTerpilih)) {
      select.value = kelasTerpilih;
    }
  }

  function renderStatistikProgress() {
    document.getElementById('statTotalSiswa').textContent = daftarProgressSiswa.length;
    document.getElementById('statSelesai').textContent = daftarProgressSiswa.filter(s => s.status === 'selesai').length;
    document.getElementById('statBerjalan').textContent = daftarProgressSiswa.filter(s => s.status === 'berjalan').length;
    document.getElementById('statBelumMulai').textContent = daftarProgressSiswa.filter(s => s.status === 'belum_mulai').length;
  }

  function ambilDataTersaring() {
    const kataCari = (document.getElementById('cariSiswa').value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('filterStatusProgress').value;
    const kelasFilter = document.getElementById('filterKelasProgress').value;
    const tanggapanFilter = document.getElementById('filterTanggapanProgress')?.value || '';

    return daftarProgressSiswa.filter(s => {
      if (kataCari && !(`${s.nama} ${s.nis}`.toLowerCase().includes(kataCari))) return false;
      if (statusFilter && s.status !== statusFilter) return false;
      if (kelasFilter && `${s.kelas} ${s.jurusan}` !== kelasFilter) return false;
      if (tanggapanFilter === 'sudah' && !(s.punya_tanggapan == 1)) return false;
      if (tanggapanFilter === 'belum' && s.punya_tanggapan == 1) return false;
      return true;
    });
  }

  function renderTabelProgress() {
    halamanProgress = 1;
    gambarUlangTabelProgress();
  }

  function gambarUlangTabelProgress() {
    const tbody = document.getElementById('isiTabelProgress');
    const data = ambilDataTersaring();

    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-gray-400">
        ${daftarProgressSiswa.length === 0 ? 'Belum ada siswa sasaran untuk materi ini, atau belum ada aktivitas.' : 'Tidak ada siswa yang cocok dengan pencarian/filter.'}
      </td></tr>`;
      document.getElementById('paginasiProgress').innerHTML = '';
      return;
    }

    const totalHalaman = Math.max(1, Math.ceil(data.length / BARIS_PER_HALAMAN_PROGRESS));
    if (halamanProgress > totalHalaman) halamanProgress = totalHalaman;
    const mulai = (halamanProgress - 1) * BARIS_PER_HALAMAN_PROGRESS;
    const dataHalaman = data.slice(mulai, mulai + BARIS_PER_HALAMAN_PROGRESS);

    tbody.innerHTML = dataHalaman.map(s => {
      const lbl = LABEL_STATUS[s.status] || LABEL_STATUS.belum_mulai;
      return `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">
          <p class="font-medium text-gray-800">${escapeHtml(s.nama)}</p>
          <p class="text-xs text-gray-400">${escapeHtml(s.nis || '-')}</p>
        </td>
        <td class="px-3 py-2 text-gray-600">${escapeHtml(s.kelas)} ${escapeHtml(s.jurusan)}</td>
        <td class="px-3 py-2 min-w-[160px]">
          <div class="flex items-center gap-2">
            <div class="progress-track flex-grow">
              <div class="progress-fill" style="width:${s.persen}%;background:${lbl.warna_progress};"></div>
            </div>
            <span class="text-xs font-semibold text-gray-600 w-16 text-right">${s.jumlah_selesai}/${s.jumlah_slide} slide</span>
          </div>
        </td>
        <td class="px-3 py-2 text-center">
          <span class="px-2 py-1 rounded-full text-xs font-semibold ${lbl.kelas}">${lbl.teks}</span>
        </td>
        <td class="px-3 py-2 text-xs text-gray-500">${formatWaktu(s.waktu_terakhir)}</td>
        <td class="px-3 py-2 text-center">
          <button onclick="bukaModalDetailSiswa(${s.id_siswa}, ${JSON.stringify(s.nama).replace(/"/g, '&quot;')})" class="action-btn text-indigo-600 hover:text-indigo-800" title="Lihat detail progress & jawaban LKPD">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>`;
    }).join('');

    renderPaginasiProgress(data.length, totalHalaman);
  }

  function renderPaginasiProgress(totalItems, totalHalaman) {
    const el = document.getElementById('paginasiProgress');
    const mulai = (halamanProgress - 1) * BARIS_PER_HALAMAN_PROGRESS + 1;
    const akhir = Math.min(halamanProgress * BARIS_PER_HALAMAN_PROGRESS, totalItems);

    let tombolHalaman = '';
    const batasBawah = Math.max(1, halamanProgress - 2);
    const batasAtas = Math.min(totalHalaman, halamanProgress + 2);
    if (batasBawah > 1) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;
    for (let p = batasBawah; p <= batasAtas; p++) {
      tombolHalaman += `<button type="button" onclick="gantiHalamanProgress(${p})" class="min-w-[2rem] px-2 py-1.5 rounded-lg text-xs font-semibold border ${p === halamanProgress ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">${p}</button>`;
    }
    if (batasAtas < totalHalaman) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;

    el.innerHTML = `
      <p class="text-xs text-gray-500">Menampilkan ${mulai}-${akhir} dari ${totalItems} siswa</p>
      <div class="flex items-center gap-1">
        <button type="button" onclick="gantiHalamanProgress(${halamanProgress - 1})" ${halamanProgress <= 1 ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>
        ${tombolHalaman}
        <button type="button" onclick="gantiHalamanProgress(${halamanProgress + 1})" ${halamanProgress >= totalHalaman ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>
      </div>
    `;
  }

  function gantiHalamanProgress(p) {
    const totalHalaman = Math.max(1, Math.ceil(ambilDataTersaring().length / BARIS_PER_HALAMAN_PROGRESS));
    if (p < 1 || p > totalHalaman) return;
    halamanProgress = p;
    gambarUlangTabelProgress();
  }

  let idSiswaDetailAktif = 0;

  function bukaModalDetailSiswa(id_siswa, nama) {
    idSiswaDetailAktif = id_siswa;
    document.getElementById('judulModalDetailSiswa').textContent = 'Detail Progress - ' + nama;
    document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-gray-400 py-6">Memuat data...</p>';
    const pesan = document.getElementById('pesanSimpanTanggapan');
    if (pesan) pesan.textContent = '';
    document.getElementById('modalDetailSiswa').classList.remove('hidden');

    const fd = new FormData();
    fd.append('action', 'detail_siswa');
    fd.append('id_materi', ID_MATERI);
    fd.append('id_siswa', id_siswa);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-red-500 py-6">Gagal memuat detail siswa.</p>';
          return;
        }
        renderIsiModalDetailSiswa(data.slides, data.tanggapan_lkpd || null);
      });
  }

  // Satu blok bintang untuk seluruh LKPD materi (bukan per soal)
  function htmlBintangPilih(ratingAktif, namaGuru) {
    const rating = ratingAktif || 0;
    const selected = namaGuru || '';
    let h = '<div class="mt-2" data-id-pertanyaan="0" data-rating-aktif="' + rating + '">';
    h += '<div class="flex items-center gap-2 flex-wrap">';
    h += '<span class="text-xs text-gray-500">Tanggapan LKPD:</span>';
    h += '<span class="star-rating-wrap" onmouseleave="starHoverLeave(this)">';
    for (let i = 1; i <= 5; i++) {
      const on = i <= rating ? ' is-on' : '';
      h += `<button type="button" class="star-btn text-lg leading-none focus:outline-none${on}" data-rating="${i}" onmouseenter="starHoverEnter(this, ${i})" onclick="simpanTanggapanBintang(0, ${i})" title="${i} bintang"><i class="fas fa-star"></i></button>`;
    }
    h += '</span>';
    h += `<select id="selGuru_0" class="text-xs border border-gray-300 rounded-lg px-2 py-1 bg-white max-w-[220px]" title="Pilih guru pemberi tanggapan">`;
    h += '<option value="">— Pilih guru —</option>';
    DAFTAR_GURU_TANGGAPAN.forEach(n => {
      const sel = (selected === n) ? ' selected' : '';
      h += '<option value="' + escapeHtml(n) + '"' + sel + '>' + escapeHtml(n) + '</option>';
    });
    if (selected && !DAFTAR_GURU_TANGGAPAN.includes(selected)) {
      h += '<option value="' + escapeHtml(selected) + '" selected>' + escapeHtml(selected) + '</option>';
    }
    h += '</select>';
    h += `<span class="text-xs text-gray-400 star-status" id="starStatus_0">${rating ? rating + '/5' : ''}</span>`;
    h += '</div>';
    h += '</div>';
    return h;
  }

  function starHoverEnter(btn, n) {
    const wrap = btn.closest('.star-rating-wrap');
    if (!wrap) return;
    wrap.querySelectorAll('.star-btn').forEach(b => {
      const r = parseInt(b.dataset.rating, 10);
      b.classList.toggle('is-preview', r <= n);
    });
  }

  function starHoverLeave(wrap) {
    if (!wrap) return;
    wrap.querySelectorAll('.star-btn').forEach(b => b.classList.remove('is-preview'));
  }

  function terapkanBintangUI(idPertanyaan, rating, namaGuru) {
    const box = document.querySelector('[data-id-pertanyaan="' + idPertanyaan + '"]');
    if (!box) return;
    box.setAttribute('data-rating-aktif', String(rating));
    const wrap = box.querySelector('.star-rating-wrap');
    if (wrap) {
      wrap.querySelectorAll('.star-btn').forEach(btn => {
        const r = parseInt(btn.dataset.rating, 10);
        btn.classList.toggle('is-on', r <= rating);
        btn.classList.remove('is-preview');
      });
    }
    const statusEl = document.getElementById('starStatus_' + idPertanyaan);
    if (statusEl) statusEl.textContent = rating + '/5 tersimpan';
    const sel = document.getElementById('selGuru_' + idPertanyaan);
    if (sel && namaGuru) {
      // Pastikan opsi ada
      let found = false;
      for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === namaGuru) { found = true; break; }
      }
      if (!found) {
        const opt = document.createElement('option');
        opt.value = namaGuru;
        opt.textContent = namaGuru;
        sel.appendChild(opt);
      }
      sel.value = namaGuru;
    }
  }

  // Klik bintang hanya menandai UI; penyimpanan lewat tombol Simpan di bawah modal
  // Catatan: idPertanyaan = 0 untuk tanggapan seluruh LKPD (jangan pakai !idPertanyaan karena 0 = falsy)
  function simpanTanggapanBintang(idPertanyaan, rating) {
    if (idPertanyaan === null || idPertanyaan === undefined || idPertanyaan < 0 || rating < 1 || rating > 5) return;
    const box = document.querySelector('[data-id-pertanyaan="' + idPertanyaan + '"]');
    if (box) box.setAttribute('data-rating-aktif', String(rating));
    const wrap = box ? box.querySelector('.star-rating-wrap') : null;
    if (wrap) {
      wrap.querySelectorAll('.star-btn').forEach(btn => {
        const r = parseInt(btn.dataset.rating, 10);
        btn.classList.toggle('is-on', r <= rating);
        btn.classList.remove('is-preview');
      });
    }
    const statusEl = document.getElementById('starStatus_' + idPertanyaan);
    if (statusEl) statusEl.textContent = rating + '/5';
    const pesan = document.getElementById('pesanSimpanTanggapan');
    if (pesan) pesan.textContent = 'Ada perubahan belum disimpan.';
  }

  function simpanSatuTanggapan(idPertanyaan, rating, namaGuru) {
    const fd = new FormData();
    fd.append('action', 'simpan_tanggapan');
    fd.append('id_materi', ID_MATERI);
    fd.append('id_siswa', idSiswaDetailAktif);
    fd.append('id_pertanyaan', idPertanyaan);
    fd.append('rating', rating);
    fd.append('nama_guru', namaGuru);
    return fetch(window.location.pathname, { method: 'POST', body: fd }).then(res => res.json());
  }

  async function simpanSemuaTanggapan() {
    if (!idSiswaDetailAktif) return;
    const box = document.querySelector('#isiModalDetailSiswa [data-id-pertanyaan="0"]');
    const pesan = document.getElementById('pesanSimpanTanggapan');
    const btn = document.getElementById('btnSimpanTanggapan');
    if (!box) {
      if (pesan) pesan.textContent = 'Tidak ada LKPD yang bisa dinilai.';
      return;
    }
    const rating = parseInt(box.getAttribute('data-rating-aktif') || '0', 10);
    const sel = document.getElementById('selGuru_0');
    const namaGuru = (sel && sel.value) ? sel.value.trim() : '';
    if (rating < 1) {
      if (pesan) pesan.textContent = 'Pilih jumlah bintang terlebih dahulu.';
      return;
    }
    if (!namaGuru) {
      if (pesan) pesan.textContent = 'Pilih nama guru terlebih dahulu.';
      const statusEl = document.getElementById('starStatus_0');
      if (statusEl) statusEl.textContent = 'Pilih nama guru';
      if (sel) sel.focus();
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    }
    if (pesan) pesan.textContent = 'Menyimpan tanggapan LKPD...';

    try {
      const data = await simpanSatuTanggapan(0, rating, namaGuru);
      if (data && data.success) {
        terapkanBintangUI(0, rating, namaGuru);
        if (pesan) pesan.textContent = 'Tanggapan LKPD berhasil disimpan.';
      } else {
        if (pesan) pesan.textContent = (data && data.message) ? data.message : 'Gagal menyimpan.';
      }
    } catch (e) {
      if (pesan) pesan.textContent = 'Gagal jaringan.';
    }

    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
    }
  }

  function renderIsiModalDetailSiswa(slides, tanggapanLkpd) {
    if (!slides || slides.length === 0) {
      document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-gray-400 py-6">Materi ini belum memiliki slide aktif.</p>';
      return;
    }

    let adaJawabanLkpd = false;
    const html = slides.map((sl, i) => {
      let jawabanHtml = '';
      if (sl.butuh_lkpd && sl.pertanyaan.length > 0) {
        jawabanHtml = `<div class="mt-3 space-y-2">` + sl.pertanyaan.map((p, pi) => {
          if (p.jawaban) adaJawabanLkpd = true;
          return `
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <p class="text-xs font-semibold text-gray-700 mb-1">${pi + 1}. ${escapeHtml(p.teks_pertanyaan)}</p>
            <p class="text-sm text-gray-600 whitespace-pre-wrap break-words">${p.jawaban ? escapeHtml(p.jawaban) : '<span class="text-gray-400 italic">Belum dijawab</span>'}</p>
          </div>`;
        }).join('') + `</div>`;
      }

      return `
      <div class="border border-gray-200 rounded-xl p-4 mb-3">
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <p class="text-sm font-bold text-gray-800"><i class="fas fa-layer-group text-gray-400 mr-1"></i> Slide ${i + 1}: ${escapeHtml(sl.judul_slide || '(tanpa judul)')}</p>
          <span class="px-2 py-1 rounded-full text-xs font-semibold ${sl.selesai ? 'badge-status-selesai' : 'badge-status-belum'}">
            ${sl.selesai ? 'Selesai' : 'Belum Selesai'}
          </span>
        </div>
        ${sl.selesai && sl.waktu_selesai ? `<p class="text-xs text-gray-400 mt-1">Diselesaikan: ${formatWaktu(sl.waktu_selesai)}</p>` : ''}
        ${jawabanHtml}
      </div>`;
    }).join('');

    // Satu blok bintang untuk seluruh LKPD (bukan per soal)
    let bintangHtml = '';
    if (adaJawabanLkpd) {
      const tg = tanggapanLkpd || {};
      bintangHtml = `
      <div class="border border-indigo-200 bg-indigo-50/50 rounded-xl p-4 mb-1">
        <p class="text-sm font-bold text-gray-800 mb-1"><i class="fas fa-star text-amber-400 mr-1"></i> Tanggapan Tugas / Kuis (LKPD)</p>
        <p class="text-xs text-gray-500 mb-2">Berikan satu penilaian bintang untuk keseluruhan LKPD siswa ini.</p>
        ${htmlBintangPilih(tg.rating || 0, tg.nama_guru || '')}
      </div>`;
    }

    document.getElementById('isiModalDetailSiswa').innerHTML = html + bintangHtml;
  }

  function tutupModalDetailSiswa() {
    document.getElementById('modalDetailSiswa').classList.add('hidden');
  }

  document.addEventListener('DOMContentLoaded', muatDataProgress);
</script>
</body>
</html>