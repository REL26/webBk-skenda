<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

/*
 * PENTING: Sesuaikan path ini jika nama folder Guru BK di server Anda BUKAN "guru".
 * File gambar/PPT yang diunggah Guru BK disimpan relatif terhadap folder Guru BK
 * (mis. guru/uploads/bimbingan_klasikal/gambar/xxx.jpg), sedangkan halaman ini
 * berada di folder siswa. Variabel ini menjembatani path tersebut supaya file
 * bisa tetap tampil di sisi siswa tanpa memindahkan/duplikasi file apa pun.
 */
$FOLDER_GURU_BK = '../guru/';

$q_siswa = mysqli_query($koneksi, "SELECT id_siswa, nama, kelas, jurusan FROM siswa WHERE id_siswa = $id_siswa LIMIT 1");
$siswa = $q_siswa ? mysqli_fetch_assoc($q_siswa) : null;
if (!$siswa) {
    header("Location: ../login.php");
    exit;
}
$nama_siswa   = $siswa['nama'] ?? 'Siswa';
$kelas_siswa  = trim($siswa['kelas'] ?? '');
$jurusan_siswa = trim($siswa['jurusan'] ?? '');

/* ==========================================================================
   FUNGSI BANTUAN (dipakai backend AJAX)
   ========================================================================== */

// Ambil daftar id_materi yang menjadi sasaran siswa ini (berdasar kelas & jurusan siswa
// yang login, dicocokkan dengan bk_materi_sasaran yang dipilih Guru BK).
function ambil_daftar_materi_siswa($koneksi, $id_siswa, $kelas, $jurusan)
{
    $kelasEsc   = mysqli_real_escape_string($koneksi, $kelas);
    $jurusanEsc = mysqli_real_escape_string($koneksi, $jurusan);

    $sql = "SELECT DISTINCT bm.* FROM bk_materi bm
            INNER JOIN bk_materi_sasaran bms ON bms.id_materi = bm.id_materi
            WHERE bm.status_aktif = 1
              AND bms.kelas = '$kelasEsc' AND bms.jurusan = '$jurusanEsc'
            ORDER BY bm.urutan ASC, bm.id_materi ASC";
    $data = [];
    $q = mysqli_query($koneksi, $sql);
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
    }
    return $data;
}

// Hitung status penyelesaian sebuah materi untuk siswa tertentu.
// Mengembalikan: ['jumlah_slide' => int, 'jumlah_selesai' => int, 'selesai' => bool]
function hitung_progress_materi($koneksi, $id_siswa, $id_materi)
{
    $idm = (int) $id_materi;
    $jmlSlide = 0;
    $q1 = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1");
    if ($q1) $jmlSlide = (int) mysqli_fetch_assoc($q1)['jml'];

    $jmlSelesai = 0;
    $q2 = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_progress_slide ps
                                   INNER JOIN bk_slide sl ON sl.id_slide = ps.id_slide
                                   WHERE ps.id_siswa = $id_siswa AND ps.id_materi = $idm
                                     AND ps.status_selesai = 1 AND sl.status_aktif = 1");
    if ($q2) $jmlSelesai = (int) mysqli_fetch_assoc($q2)['jml'];

    // Materi tanpa slide aktif dianggap otomatis "selesai" agar tidak memblokir urutan.
    $selesai = ($jmlSlide > 0) ? ($jmlSelesai >= $jmlSlide) : true;

    return ['jumlah_slide' => $jmlSlide, 'jumlah_selesai' => $jmlSelesai, 'selesai' => $selesai];
}

// Bangun daftar materi milik siswa lengkap dengan status kunci berurutan.
// Mengembalikan array terurut, masing-masing punya key tambahan: 'status' (terkunci|tersedia|berlangsung|selesai)
function bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas, $jurusan)
{
    $daftarMateri = ambil_daftar_materi_siswa($koneksi, $id_siswa, $kelas, $jurusan);
    $hasil = [];
    $materiSebelumnyaSelesai = true; // materi pertama selalu terbuka

    foreach ($daftarMateri as $m) {
        $progress = hitung_progress_materi($koneksi, $id_siswa, $m['id_materi']);
        $terkunci = !$materiSebelumnyaSelesai;

        if ($terkunci) {
            $status = 'terkunci';
        } elseif ($progress['selesai']) {
            $status = 'selesai';
        } elseif ($progress['jumlah_selesai'] > 0) {
            $status = 'berlangsung';
        } else {
            $status = 'tersedia';
        }

        $m['jumlah_slide']   = $progress['jumlah_slide'];
        $m['jumlah_selesai'] = $progress['jumlah_selesai'];
        $m['status']         = $status;
        $hasil[] = $m;

        $materiSebelumnyaSelesai = $progress['selesai'];
    }
    return $hasil;
}

// Cek apakah siswa berhak & sudah waktunya membuka materi ini (tidak terkunci).
function materi_boleh_diakses($koneksi, $id_siswa, $kelas, $jurusan, $id_materi)
{
    $daftar = bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas, $jurusan);
    foreach ($daftar as $m) {
        if ((int) $m['id_materi'] === (int) $id_materi) {
            return $m['status'] !== 'terkunci' ? $m : false;
        }
    }
    return false;
}

/* ==========================================================================
   BACKEND AJAX (action-based, mengikuti pola halaman Guru BK)
   ========================================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'list_materi_siswa') {
        $daftar = bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa);
        $data = array_map(function ($m, $i) {
            return [
                'id_materi'       => (int) $m['id_materi'],
                'nomor'           => $i + 1,
                'judul'           => $m['judul'],
                'deskripsi'       => $m['deskripsi'],
                'nama_guru_pembuat' => $m['nama_guru_pembuat'],
                'jumlah_slide'    => (int) $m['jumlah_slide'],
                'jumlah_selesai'  => (int) $m['jumlah_selesai'],
                'status'          => $m['status'],
            ];
        }, $daftar, array_keys($daftar));

        echo json_encode(['success' => true, 'data' => $data, 'nama_siswa' => $nama_siswa,
            'kelas_jurusan' => trim($kelas_siswa . ' ' . $jurusan_siswa)]);
        exit;
    }

    if ($action === 'lihat_materi_siswa') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $akses = materi_boleh_diakses($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa, $idm);
        if (!$akses) {
            echo json_encode(['success' => false, 'message' => 'Materi ini belum dapat diakses. Selesaikan materi sebelumnya terlebih dahulu.']);
            exit;
        }

        $qm = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $idm LIMIT 1");
        $materi = $qm ? mysqli_fetch_assoc($qm) : null;
        if (!$materi) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            exit;
        }

        $slides = [];
        $qsl = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1 ORDER BY urutan ASC, id_slide ASC");
        $slideSebelumnyaSelesai = true; // slide pertama selalu terbuka
        if ($qsl) {
            while ($sl = mysqli_fetch_assoc($qsl)) {
                $ids = (int) $sl['id_slide'];

                // Status selesai slide ini untuk siswa yang login
                $qp2 = mysqli_query($koneksi, "SELECT status_selesai FROM bk_progress_slide WHERE id_siswa = $id_siswa AND id_slide = $ids LIMIT 1");
                $rowProgress = $qp2 ? mysqli_fetch_assoc($qp2) : null;
                $slideSelesai = $rowProgress ? ((int) $rowProgress['status_selesai'] === 1) : false;

                $sl['status_selesai'] = $slideSelesai ? 1 : 0;
                $sl['terkunci'] = $slideSebelumnyaSelesai ? 0 : 1;

                // Pertanyaan LKPD + jawaban tersimpan sebelumnya (agar siswa bisa lanjut mengisi)
                $pertanyaan = [];
                if ((int) $sl['butuh_lkpd'] === 1) {
                    $qp = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = $ids ORDER BY urutan ASC, id_pertanyaan ASC");
                    if ($qp) {
                        while ($p = mysqli_fetch_assoc($qp)) {
                            $p['opsi_jawaban'] = $p['opsi_jawaban'] ? json_decode($p['opsi_jawaban'], true) : [];
                            $idp = (int) $p['id_pertanyaan'];
                            $qj = mysqli_query($koneksi, "SELECT jawaban FROM bk_jawaban_lkpd WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp LIMIT 1");
                            $rj = $qj ? mysqli_fetch_assoc($qj) : null;
                            $p['jawaban_tersimpan'] = $rj ? $rj['jawaban'] : '';
                            $pertanyaan[] = $p;
                        }
                    }
                }
                $sl['pertanyaan'] = $pertanyaan;
                $slides[] = $sl;

                $slideSebelumnyaSelesai = $slideSelesai;
            }
        }

        $materi['slides'] = $slides;
        $materi['status_materi'] = $akses['status'];
        echo json_encode(['success' => true, 'data' => $materi, 'folder_guru' => $FOLDER_GURU_BK]);
        exit;
    }

    if ($action === 'selesai_slide') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $ids = (int) ($_POST['id_slide'] ?? 0);

        $akses = materi_boleh_diakses($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa, $idm);
        if (!$akses) {
            echo json_encode(['success' => false, 'message' => 'Materi ini belum dapat diakses.']);
            exit;
        }

        $qsl = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_slide = $ids AND id_materi = $idm AND status_aktif = 1 LIMIT 1");
        $slide = $qsl ? mysqli_fetch_assoc($qsl) : null;
        if (!$slide) {
            echo json_encode(['success' => false, 'message' => 'Slide tidak ditemukan.']);
            exit;
        }

        // Validasi server-side: pastikan semua slide SEBELUM slide ini (dalam materi yang sama)
        // sudah berstatus selesai, supaya siswa tidak bisa melompati urutan lewat request langsung.
        $urutanSlideIni = (int) $slide['urutan'];
        $qBelum = mysqli_query($koneksi, "SELECT sl.id_slide FROM bk_slide sl
            LEFT JOIN bk_progress_slide ps ON ps.id_slide = sl.id_slide AND ps.id_siswa = $id_siswa
            WHERE sl.id_materi = $idm AND sl.status_aktif = 1 AND sl.urutan < $urutanSlideIni
              AND (ps.status_selesai IS NULL OR ps.status_selesai = 0)
            LIMIT 1");
        if ($qBelum && mysqli_num_rows($qBelum) > 0) {
            echo json_encode(['success' => false, 'message' => 'Selesaikan slide sebelumnya terlebih dahulu.']);
            exit;
        }

        // Jika slide ini butuh LKPD, jawaban wajib diisi & disimpan sebelum ditandai selesai.
        if ((int) $slide['butuh_lkpd'] === 1) {
            $qp = mysqli_query($koneksi, "SELECT id_pertanyaan, tipe_jawaban FROM bk_lkpd_pertanyaan WHERE id_slide = $ids");
            $daftarPertanyaan = [];
            if ($qp) while ($p = mysqli_fetch_assoc($qp)) $daftarPertanyaan[] = $p;

            $jawabanPost = json_decode($_POST['jawaban'] ?? '{}', true);
            if (!is_array($jawabanPost)) $jawabanPost = [];

            foreach ($daftarPertanyaan as $p) {
                $idp = (int) $p['id_pertanyaan'];
                $jawabanMentah = $jawabanPost[$idp] ?? '';
                if (is_array($jawabanMentah)) $jawabanMentah = implode(', ', array_map('trim', $jawabanMentah));
                $jawabanMentah = trim((string) $jawabanMentah);

                if ($jawabanMentah === '') {
                    echo json_encode(['success' => false, 'message' => 'Lengkapi semua pertanyaan LKPD sebelum melanjutkan.']);
                    exit;
                }
            }

            foreach ($daftarPertanyaan as $p) {
                $idp = (int) $p['id_pertanyaan'];
                $jawabanMentah = $jawabanPost[$idp] ?? '';
                if (is_array($jawabanMentah)) $jawabanMentah = implode(', ', array_map('trim', $jawabanMentah));
                $jawabanEsc = mysqli_real_escape_string($koneksi, trim((string) $jawabanMentah));
                mysqli_query($koneksi, "INSERT INTO bk_jawaban_lkpd (id_siswa, id_pertanyaan, jawaban) VALUES ($id_siswa, $idp, '$jawabanEsc')
                    ON DUPLICATE KEY UPDATE jawaban = '$jawabanEsc', created_at = CURRENT_TIMESTAMP");
            }
        }

        mysqli_query($koneksi, "INSERT INTO bk_progress_slide (id_siswa, id_materi, id_slide, status_selesai, waktu_selesai)
            VALUES ($id_siswa, $idm, $ids, 1, NOW())
            ON DUPLICATE KEY UPDATE status_selesai = 1, waktu_selesai = NOW()");

        $progressMateri = hitung_progress_materi($koneksi, $id_siswa, $idm);
        echo json_encode([
            'success' => true,
            'materi_selesai' => $progressMateri['selesai'],
            'jumlah_slide' => $progressMateri['jumlah_slide'],
            'jumlah_selesai' => $progressMateri['jumlah_selesai'],
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bimbingan Klasikal | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A6163;
            --primary-color-dark: #1F4A4B;
            --secondary-color: #2F9160;
            --navbar-bg: #163B3C;
            --surface-muted: #F4F7F6;
        }
        body { background-color: var(--surface-muted); }
        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .primary-border { border-color: var(--primary-color); }
        .navbar-bg { background-color: var(--navbar-bg); }

        .materi-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid #E5E7EB;
            border-radius: 1rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(22, 59, 60, 0.06);
            background: #fff;
        }
        .materi-card.tersedia:hover, .materi-card.berlangsung:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(22, 59, 60, 0.12);
            border-color: var(--primary-color);
        }
        .materi-card.selesai {
            background-color: #F0FBF5;
            border: 2px solid var(--secondary-color);
        }
        .materi-card.terkunci {
            filter: grayscale(60%);
            opacity: 0.68;
            cursor: not-allowed;
            background-color: #F9FAFB;
        }
        .badge { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 9999px; }
        .badge-selesai { background: var(--secondary-color); color: #fff; }
        .badge-berlangsung { background: #FDE68A; color: #92400E; }
        .badge-tersedia { background: rgba(42,97,99,0.1); color: var(--primary-color); }
        .badge-terkunci { background: #E5E7EB; color: #6B7280; }

        .progress-track { background: #E5E7EB; border-radius: 9999px; height: 6px; overflow: hidden; }
        .progress-fill { background: var(--secondary-color); height: 100%; border-radius: 9999px; transition: width .4s ease; }

        .step-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; cursor: pointer; transition: all .2s ease; }
        .step-item:hover { background: #F4F7F6; }
        .step-item.active { background: rgba(42,97,99,0.1); border: 1px solid var(--primary-color); }
        .step-item.locked { opacity: .5; cursor: not-allowed; }
        .step-dot { width: 26px; height: 26px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; flex-shrink: 0; }
        .step-dot.done { background: var(--secondary-color); color: #fff; }
        .step-dot.current { background: var(--primary-color); color: #fff; }
        .step-dot.locked { background: #E5E7EB; color: #9CA3AF; }

        .yt-wrap { position: relative; width: 100%; padding-top: 56.25%; border-radius: .75rem; overflow: hidden; background: #000; }
        .yt-wrap iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .doc-embed-wrap { width: 100%; height: min(75vh, 560px); border-radius: .75rem; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; }
        .doc-embed-wrap iframe { width: 100%; height: 100%; border: 0; }

        #viewerMateri { display: none; }
        #viewerMateri.aktif { display: block; }
        #listMateriSection.tersembunyi { display: none; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 10px; }
    </style>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="navbar-bg flex justify-between items-center px-4 md:px-8 py-3 shadow-lg relative z-30">
        <a href="dashboard.php" class="flex items-center space-x-2.5">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-9 w-9 bg-white rounded-md p-0.5">
            <div>
                <strong class="text-base md:text-xl text-white font-extrabold tracking-tight">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-teal-100/70">Bimbingan dan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Beranda</a>
            <a href="data_profiling.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Data Profiling</a>
            <a href="riwayatkonselingsiswa.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Riwayat</a>
            <a href="ganti_password.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition text-sm font-semibold shadow-md">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-white text-2xl p-2 z-40 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="hidden absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex-col text-left text-base border-t border-gray-100">
        <a href="dashboard.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>

    <section class="py-10 md:py-14 px-4 text-white" style="background: linear-gradient(135deg, var(--navbar-bg), var(--primary-color));">
        <div class="max-w-5xl mx-auto">
            <a href="dashboard.php" class="inline-flex items-center gap-2 text-teal-100/80 hover:text-white text-sm mb-4 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-chalkboard-user text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold">Bimbingan Klasikal</h1>
                    <p class="text-teal-50/80 text-sm md:text-base mt-1">
                        Materi bimbingan untuk kelas <?php echo htmlspecialchars(trim($kelas_siswa . ' ' . $jurusan_siswa)); ?>. Selesaikan setiap materi secara berurutan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <main class="flex-grow max-w-5xl w-full mx-auto px-4 py-8 md:py-10">

        <!-- ================= DAFTAR MATERI ================= -->
        <div id="listMateriSection">
            <div id="ringkasanProgress" class="hidden bg-white border border-gray-200 rounded-2xl p-5 mb-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-bold text-gray-700">Progres Keseluruhan</p>
                    <p class="text-sm font-bold primary-color"><span id="ringkasanTeks">0/0 materi</span></p>
                </div>
                <div class="progress-track"><div id="ringkasanFill" class="progress-fill" style="width:0%"></div></div>
            </div>

            <div id="daftarMateriWrap" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="col-span-full text-center py-16 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-3"></i>
                    <p class="text-sm">Memuat daftar materi...</p>
                </div>
            </div>
        </div>

        <!-- ================= VIEWER MATERI (SLIDE) ================= -->
        <div id="viewerMateri">
            <button onclick="tutupViewerMateri()" class="inline-flex items-center gap-2 text-sm font-semibold primary-color hover:opacity-75 mb-4 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Materi
            </button>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 md:p-6 border-b border-gray-100">
                    <h2 id="viewerJudulMateri" class="text-xl md:text-2xl font-extrabold text-gray-800"></h2>
                    <p id="viewerDeskripsiMateri" class="text-sm text-gray-500 mt-1"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3">
                    <!-- Daftar slide (stepper) -->
                    <div class="md:col-span-1 border-b md:border-b-0 md:border-r border-gray-100 p-3 md:p-4 max-h-[70vh] overflow-y-auto">
                        <p class="text-xs font-bold uppercase text-gray-400 px-2 mb-2">Daftar Materi Slide</p>
                        <div id="daftarStepSlide" class="space-y-1.5"></div>
                    </div>

                    <!-- Konten slide aktif -->
                    <div class="md:col-span-2 p-6 md:p-8 lg:p-9">
                        <div id="isiSlideAktif"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-5 navbar-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        <p class="text-sm text-gray-200 font-light">
            &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
        </p>
        <p class="text-xs text-gray-400 mt-1">Developed by <span class="font-medium">SahDu Team</span></p>
    </footer>

<script>
function toggleMenu() {
    const m = document.getElementById('mobileMenu');
    const o = document.getElementById('menuOverlay');
    const tampil = !m.classList.contains('hidden');
    if (tampil) { m.classList.add('hidden'); m.classList.remove('flex'); o.classList.add('hidden'); }
    else { m.classList.remove('hidden'); m.classList.add('flex'); o.classList.remove('hidden'); }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function nl2br(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
}

const STATUS_LABEL = {
    selesai: 'Selesai', berlangsung: 'Sedang Berjalan', tersedia: 'Tersedia', terkunci: 'Terkunci'
};
const STATUS_ICON = {
    selesai: 'fa-circle-check', berlangsung: 'fa-play-circle', tersedia: 'fa-lock-open', terkunci: 'fa-lock'
};

let materiAktifId = null;
let slideAktifIndex = 0;
let dataSlideAktif = [];

function muatDaftarMateriSiswa() {
    const fd = new FormData();
    fd.append('action', 'list_materi_siswa');
    fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            renderDaftarMateriSiswa(data.data);
        });
}

function renderDaftarMateriSiswa(daftar) {
    const wrap = document.getElementById('daftarMateriWrap');
    const ringkasan = document.getElementById('ringkasanProgress');

    if (daftar.length === 0) {
        wrap.innerHTML = `
            <div class="col-span-full text-center py-16 bg-white border border-gray-200 rounded-2xl">
                <i class="fas fa-chalkboard text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-semibold">Belum ada materi Bimbingan Klasikal untuk kelas Anda.</p>
                <p class="text-sm text-gray-400 mt-1">Materi akan muncul di sini setelah Guru BK membagikannya.</p>
            </div>`;
        ringkasan.classList.add('hidden');
        return;
    }

    const totalSelesai = daftar.filter(m => m.status === 'selesai').length;
    document.getElementById('ringkasanTeks').textContent = `${totalSelesai}/${daftar.length} materi`;
    document.getElementById('ringkasanFill').style.width = (daftar.length ? (totalSelesai / daftar.length * 100) : 0) + '%';
    ringkasan.classList.remove('hidden');

    wrap.innerHTML = daftar.map(m => {
        const bisaDibuka = m.status !== 'terkunci';
        const persen = m.jumlah_slide > 0 ? Math.round((m.jumlah_selesai / m.jumlah_slide) * 100) : 0;
        const tombolTeks = m.status === 'selesai' ? 'Lihat Kembali' : (m.status === 'berlangsung' ? 'Lanjutkan' : (m.status === 'tersedia' ? 'Mulai Materi' : 'Terkunci'));

        return `
        <div class="materi-card ${m.status} p-5 md:p-6" ${bisaDibuka ? `onclick="bukaMateriSiswa(${m.id_materi})"` : ''}>
            <div class="flex items-start justify-between gap-2 mb-3">
                <span class="w-9 h-9 rounded-lg bg-[#2A6163]/10 text-[#2A6163] font-extrabold text-sm flex items-center justify-center flex-shrink-0">
                    ${m.nomor}
                </span>
                <span class="badge badge-${m.status}"><i class="fas ${STATUS_ICON[m.status]} mr-1"></i>${STATUS_LABEL[m.status]}</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-snug">${escapeHtml(m.judul)}</h3>
            <p class="text-sm text-slate-500 mt-1.5 line-clamp-2">${escapeHtml(m.deskripsi || 'Tidak ada deskripsi.')}</p>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>${m.jumlah_selesai}/${m.jumlah_slide} slide</span>
                    <span>${persen}%</span>
                </div>
                <div class="progress-track"><div class="progress-fill" style="width:${persen}%"></div></div>
            </div>

            <div class="mt-4 text-sm font-bold ${bisaDibuka ? 'primary-color' : 'text-gray-400'}">
                ${tombolTeks} ${bisaDibuka ? '<i class="fas fa-chevron-right ml-1 text-xs"></i>' : ''}
            </div>
        </div>`;
    }).join('');
}

function bukaMateriSiswa(id_materi) {
    const fd = new FormData();
    fd.append('action', 'lihat_materi_siswa');
    fd.append('id_materi', id_materi);
    fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) { alert(data.message || 'Materi belum dapat diakses.'); return; }
            materiAktifId = id_materi;
            window.FOLDER_GURU_BK = data.folder_guru || '../guru/';
            renderViewerMateri(data.data);
        });
}

function renderViewerMateri(materi) {
    document.getElementById('viewerJudulMateri').textContent = materi.judul;
    document.getElementById('viewerDeskripsiMateri').textContent = materi.deskripsi || '';
    dataSlideAktif = materi.slides || [];

    // Tentukan slide yang pertama kali ditampilkan: slide belum selesai pertama, atau slide terakhir jika semua selesai.
    let idxAwal = dataSlideAktif.findIndex(sl => sl.status_selesai != 1);
    if (idxAwal === -1) idxAwal = Math.max(0, dataSlideAktif.length - 1);
    slideAktifIndex = idxAwal;

    renderStepSlide();
    renderIsiSlide();

    document.getElementById('listMateriSection').classList.add('tersembunyi');
    document.getElementById('viewerMateri').classList.add('aktif');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function tutupViewerMateri() {
    document.getElementById('viewerMateri').classList.remove('aktif');
    document.getElementById('listMateriSection').classList.remove('tersembunyi');
    materiAktifId = null;
    muatDaftarMateriSiswa();
}

function renderStepSlide() {
    const wrap = document.getElementById('daftarStepSlide');
    wrap.innerHTML = dataSlideAktif.map((sl, i) => {
        let dotClass = 'locked', dotIcon = i + 1;
        if (sl.status_selesai == 1) { dotClass = 'done'; dotIcon = '<i class="fas fa-check"></i>'; }
        else if (sl.terkunci == 0) { dotClass = (i === slideAktifIndex ? 'current' : 'done'); if (dotClass === 'current') dotIcon = i + 1; }

        const bisaKlik = sl.terkunci == 0;
        const activeClass = i === slideAktifIndex ? 'active' : '';
        const lockedClass = !bisaKlik ? 'locked' : '';

        return `
        <div class="step-item ${activeClass} ${lockedClass}" ${bisaKlik ? `onclick="pindahSlide(${i})"` : ''}>
            <span class="step-dot ${dotClass}">${dotIcon}</span>
            <span class="text-sm ${bisaKlik ? 'text-gray-700' : 'text-gray-400'} font-medium truncate">${escapeHtml(sl.judul_slide || ('Slide ' + (i + 1)))}</span>
            ${!bisaKlik ? '<i class="fas fa-lock text-xs text-gray-300 ml-auto"></i>' : ''}
        </div>`;
    }).join('');
}

function pindahSlide(index) {
    if (dataSlideAktif[index].terkunci == 1) return;
    slideAktifIndex = index;
    renderStepSlide();
    renderIsiSlide();
}

function extractYoutubeId(url) {
    if (!url) return null;
    const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
    return match ? match[1] : null;
}

// Mengenali link Google Drive (file/d/ID/... , ?id=ID , /open?id=ID) untuk dijadikan pratinjau tersemat.
function extractGoogleDriveId(url) {
    if (!url) return null;
    if (!/drive\.google\.com/.test(url)) return null;
    let m = url.match(/\/d\/([a-zA-Z0-9_-]{15,})/);
    if (m) return m[1];
    m = url.match(/[?&]id=([a-zA-Z0-9_-]{15,})/);
    return m ? m[1] : null;
}

// Ambil ekstensi file dari path/URL (tanpa query string), huruf kecil.
function ambilEkstensi(path) {
    if (!path) return '';
    const bersih = path.split('?')[0].split('#')[0];
    const parts = bersih.split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
}

// Ubah path relatif jadi URL absolut (dibutuhkan layanan pratinjau eksternal seperti Office Online).
function urlAbsolut(url) {
    try { return new URL(url, window.location.href).href; } catch (e) { return url; }
}

function urlLengkap(path) {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    return (window.FOLDER_GURU_BK || '../guru/') + path;
}

const IKON_JENIS_FILE = {
    pdf:  { icon: 'fa-file-pdf',        warna: 'red'    },
    doc:  { icon: 'fa-file-word',       warna: 'blue'   },
    docx: { icon: 'fa-file-word',       warna: 'blue'   },
    xls:  { icon: 'fa-file-excel',      warna: 'green'  },
    xlsx: { icon: 'fa-file-excel',      warna: 'green'  },
    ppt:  { icon: 'fa-file-powerpoint', warna: 'orange' },
    pptx: { icon: 'fa-file-powerpoint', warna: 'orange' },
};

// Kartu fallback rapi ketika file tidak bisa dipratinjau langsung (link eksternal tak dikenal, dsb).
function kartuBukaFile(url, namaFile, keterangan) {
    const ext = ambilEkstensi(namaFile);
    const meta = IKON_JENIS_FILE[ext] || { icon: 'fa-file-arrow-down', warna: 'gray' };
    return `<a href="${url}" target="_blank" rel="noopener" class="flex items-center gap-3 bg-${meta.warna}-50 border border-${meta.warna}-200 rounded-xl p-4 mb-5 hover:bg-${meta.warna}-100 transition">
        <i class="fas ${meta.icon} text-${meta.warna}-600 text-2xl"></i>
        <div class="min-w-0">
            <p class="text-sm font-bold text-${meta.warna}-800 truncate">${escapeHtml(namaFile)}</p>
            <p class="text-xs text-${meta.warna}-600">${escapeHtml(keterangan || 'Klik untuk membuka / mengunduh file ini')}</p>
        </div>
    </a>`;
}

// Pratinjau langsung untuk file yang disimpan/di-share lewat Google Drive (gambar, PDF, video, dokumen Office).
function embedGoogleDrive(driveId, urlAsli, labelJenis) {
    return `<div class="doc-embed-wrap mb-2">
        <iframe src="https://drive.google.com/file/d/${driveId}/preview" allow="autoplay" loading="lazy" title="Pratinjau ${escapeHtml(labelJenis)}"></iframe>
    </div>
    <p class="text-xs text-gray-500 mb-5 flex flex-wrap items-center gap-1">
        <i class="fas fa-circle-info"></i> Pratinjau memakai Google Drive. Jika tidak muncul, kemungkinan izin berbagi file belum "Siapa saja yang memiliki link".
        <a href="${urlAsli}" target="_blank" rel="noopener" class="text-blue-600 hover:underline font-semibold">Buka di Google Drive &rarr;</a>
    </p>`;
}

// PDF bisa dirender langsung oleh browser (paling stabil, tak tergantung layanan pihak ketiga).
function embedPdf(url) {
    return `<div class="doc-embed-wrap mb-2">
        <iframe src="${url}#toolbar=1" loading="lazy" title="Pratinjau PDF"></iframe>
    </div>
    <p class="text-xs text-gray-500 mb-5 flex flex-wrap items-center gap-1">
        <i class="fas fa-circle-info"></i> Jika pratinjau PDF tidak muncul di perangkat/browser Anda,
        <a href="${url}" target="_blank" rel="noopener" class="text-blue-600 hover:underline font-semibold">buka atau unduh filenya di sini &rarr;</a>
    </p>`;
}

// Dokumen Office (PPT/Word/Excel) ditampilkan lewat Microsoft Office Online Viewer.
// Layanan ini butuh file bisa diakses lewat internet publik, jadi selalu sediakan tautan cadangan.
function embedOffice(url, ext) {
    const meta = IKON_JENIS_FILE[ext] || { icon: 'fa-file', warna: 'gray' };
    const officeSrc = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(url);
    return `<div class="doc-embed-wrap mb-2">
        <iframe src="${officeSrc}" loading="lazy" title="Pratinjau ${ext.toUpperCase()}"></iframe>
    </div>
    <p class="text-xs text-gray-500 mb-5 flex flex-wrap items-center gap-1">
        <i class="fas fa-circle-info"></i> Pratinjau ${ext.toUpperCase()} memakai layanan Microsoft Office Online, sehingga file harus dapat diakses lewat internet publik.
        Jika pratinjau tidak muncul (misalnya website sedang diakses secara lokal/intranet),
        <a href="${url}" target="_blank" rel="noopener" class="text-${meta.warna}-600 hover:underline font-semibold">buka atau unduh filenya di sini &rarr;</a>
    </p>`;
}

// Titik masuk utama untuk field dokumen/PPT slide: pilih cara tampil terbaik sesuai sumber & formatnya.
function renderDokumenSlide(pathAsli) {
    const url = urlLengkap(pathAsli);
    const namaFile = pathAsli.split('/').pop().split('?')[0];
    const driveId = extractGoogleDriveId(pathAsli);
    if (driveId) return embedGoogleDrive(driveId, url, 'dokumen');

    const ext = ambilEkstensi(pathAsli);
    if (ext === 'pdf') return embedPdf(url);
    if (['ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx'].includes(ext)) {
        // Office Online butuh URL publik yang absolut.
        return embedOffice(urlAbsolut(url), ext);
    }
    // Ekstensi tak dikenal / tidak didukung pratinjau langsung → fallback kartu buka/unduh.
    return kartuBukaFile(url, namaFile, 'Format file ini belum didukung untuk pratinjau langsung. Klik untuk membuka / mengunduh.');
}

let hitungGambarSlide = 0;
window.gambarSlideGagalDimuat = function (imgEl, urlAsli) {
    const namaFile = urlAsli.split('/').pop().split('?')[0];
    const pengganti = document.createElement('div');
    pengganti.innerHTML = kartuBukaFile(urlAsli, namaFile, 'Gambar tidak dapat ditampilkan langsung. Klik untuk membuka.');
    imgEl.replaceWith(pengganti.firstElementChild);
};

// Titik masuk untuk field gambar slide: link Google Drive dipratinjau via iframe,
// sisanya dicoba tampil sebagai <img> langsung dengan fallback rapi bila gagal dimuat.
function renderGambarSlide(pathAsli) {
    const driveId = extractGoogleDriveId(pathAsli);
    if (driveId) return embedGoogleDrive(driveId, urlLengkap(pathAsli), 'gambar');

    const url = urlLengkap(pathAsli);
    hitungGambarSlide++;
    return `<img src="${url}" id="imgSlide${hitungGambarSlide}" class="w-full rounded-xl border border-gray-200 mb-5" alt="Gambar materi"
        onerror="gambarSlideGagalDimuat(this, '${url.replace(/'/g, "\\'")}')">`;
}

function renderIsiSlide() {
    const sl = dataSlideAktif[slideAktifIndex];
    const wrap = document.getElementById('isiSlideAktif');
    if (!sl) { wrap.innerHTML = ''; return; }

    let html = `<h3 class="text-lg md:text-xl font-bold text-gray-800 mb-5 pb-4 border-b border-gray-100">${escapeHtml(sl.judul_slide || ('Slide ' + (slideAktifIndex + 1)))}</h3>`;

    if (sl.konten_teks) {
        html += `<div class="prose prose-sm md:prose-base max-w-none text-gray-700 leading-relaxed md:leading-loose mb-6 break-words [overflow-wrap:anywhere] whitespace-pre-wrap">${nl2br(sl.konten_teks)}</div>`;
    }

    if (sl.gambar) {
        html += renderGambarSlide(sl.gambar);
    }

    if (sl.file_ppt) {
        html += renderDokumenSlide(sl.file_ppt);
    }

    if (sl.link_youtube) {
        const ytId = extractYoutubeId(sl.link_youtube);
        const driveIdVideo = !ytId ? extractGoogleDriveId(sl.link_youtube) : null;
        if (ytId) {
            html += `<div class="yt-wrap mb-6"><iframe src="https://www.youtube.com/embed/${ytId}" title="Video materi" allowfullscreen></iframe></div>`;
        } else if (driveIdVideo) {
            html += embedGoogleDrive(driveIdVideo, urlLengkap(sl.link_youtube), 'video');
        } else {
            html += `<a href="${escapeHtml(sl.link_youtube)}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-red-600 font-semibold mb-6"><i class="fab fa-youtube text-xl"></i> Buka Video</a>`;
        }
    }

    const sudahSelesai = sl.status_selesai == 1;

    if (sl.pertanyaan && sl.pertanyaan.length > 0) {
        html += `<div class="mt-2 pt-5 border-t border-gray-100">
            <p class="text-sm font-bold text-gray-700 mb-4"><i class="fas fa-list-check primary-color mr-1"></i> Lembar Kerja Peserta Didik (LKPD)</p>
            <div id="formLkpd" class="space-y-4">`;

        sl.pertanyaan.forEach((p, pi) => {
            html += `<div class="bg-gray-50 border border-gray-200 rounded-xl p-4 md:p-5">
                <p class="text-sm font-semibold text-gray-800 mb-3">${pi + 1}. ${escapeHtml(p.teks_pertanyaan)}</p>`;

            const namaField = 'lkpd_' + p.id_pertanyaan;
            const disabledAttr = sudahSelesai ? 'disabled' : '';

            if (p.tipe_jawaban === 'pilihan_ganda') {
                (p.opsi_jawaban || []).forEach((opsi, oi) => {
                    const checked = p.jawaban_tersimpan === opsi ? 'checked' : '';
                    html += `<label class="flex items-center gap-2 text-sm text-gray-700 py-1 cursor-pointer">
                        <input type="radio" name="${namaField}" value="${escapeHtml(opsi)}" ${checked} ${disabledAttr} class="lkpd-input" data-id-pertanyaan="${p.id_pertanyaan}"> ${escapeHtml(opsi)}
                    </label>`;
                });
            } else if (p.tipe_jawaban === 'checkbox') {
                const terpilih = (p.jawaban_tersimpan || '').split(',').map(s => s.trim());
                (p.opsi_jawaban || []).forEach((opsi, oi) => {
                    const checked = terpilih.includes(opsi) ? 'checked' : '';
                    html += `<label class="flex items-center gap-2 text-sm text-gray-700 py-1 cursor-pointer">
                        <input type="checkbox" value="${escapeHtml(opsi)}" ${checked} ${disabledAttr} class="lkpd-input-checkbox" data-id-pertanyaan="${p.id_pertanyaan}"> ${escapeHtml(opsi)}
                    </label>`;
                });
            } else if (p.tipe_jawaban === 'isian_singkat') {
                html += `<input type="text" class="lkpd-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" data-id-pertanyaan="${p.id_pertanyaan}" value="${escapeHtml(p.jawaban_tersimpan || '')}" placeholder="Ketik jawaban singkat..." ${disabledAttr}>`;
            } else {
                html += `<textarea class="lkpd-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" rows="3" data-id-pertanyaan="${p.id_pertanyaan}" placeholder="Tulis jawaban Anda..." ${disabledAttr}>${escapeHtml(p.jawaban_tersimpan || '')}</textarea>`;
            }
            html += `</div>`;
        });
        html += `</div></div>`;
    }

    html += `<div id="pesanErrorSlide" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2 mt-5"></div>`;

    html += `<div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-100">
        <button type="button" onclick="pindahSlide(${slideAktifIndex - 1})" class="text-sm font-semibold text-gray-500 hover:text-gray-700 ${slideAktifIndex === 0 ? 'invisible' : ''}">
            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
        </button>`;

    if (sudahSelesai) {
        const adaLanjutan = slideAktifIndex < dataSlideAktif.length - 1;
        html += `<button type="button" onclick="${adaLanjutan ? `pindahSlide(${slideAktifIndex + 1})` : 'tutupViewerMateri()'}" class="bg-[#2F9160] hover:bg-[#26744d] text-white font-bold text-sm px-6 py-2.5 rounded-lg transition">
            <i class="fas fa-check-circle mr-1"></i> ${adaLanjutan ? 'Lanjut ke Slide Berikutnya' : 'Selesai, Kembali ke Daftar Materi'}
        </button>`;
    } else {
        html += `<button type="button" id="btnTandaiSelesai" onclick="tandaiSlideSelesai()" class="bg-[#2A6163] hover:bg-[#1F4A4B] text-white font-bold text-sm px-6 py-2.5 rounded-lg transition">
            <i class="fas fa-arrow-right mr-1"></i> ${slideAktifIndex < dataSlideAktif.length - 1 ? 'Tandai Selesai & Lanjut' : 'Selesaikan Materi'}
        </button>`;
    }
    html += `</div>`;

    wrap.innerHTML = html;
}

function kumpulkanJawabanLkpd() {
    const jawaban = {};
    document.querySelectorAll('#formLkpd .lkpd-input').forEach(el => {
        const id = el.dataset.idPertanyaan;
        if (el.type === 'radio') {
            if (el.checked) jawaban[id] = el.value;
        } else {
            jawaban[id] = el.value.trim();
        }
    });
    document.querySelectorAll('#formLkpd .lkpd-input-checkbox').forEach(el => {
        const id = el.dataset.idPertanyaan;
        if (!jawaban[id]) jawaban[id] = [];
        if (el.checked) jawaban[id].push(el.value);
    });
    return jawaban;
}

function tandaiSlideSelesai() {
    const sl = dataSlideAktif[slideAktifIndex];
    const errBox = document.getElementById('pesanErrorSlide');
    errBox.classList.add('hidden');

    const btn = document.getElementById('btnTandaiSelesai');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...'; }

    const fd = new FormData();
    fd.append('action', 'selesai_slide');
    fd.append('id_materi', materiAktifId);
    fd.append('id_slide', sl.id_slide);
    if (sl.pertanyaan && sl.pertanyaan.length > 0) {
        fd.append('jawaban', JSON.stringify(kumpulkanJawabanLkpd()));
    }

    fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                errBox.textContent = data.message || 'Gagal menyimpan progres.';
                errBox.classList.remove('hidden');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-arrow-right mr-1"></i> Tandai Selesai & Lanjut'; }
                return;
            }
            dataSlideAktif[slideAktifIndex].status_selesai = 1;
            if (slideAktifIndex + 1 < dataSlideAktif.length) {
                dataSlideAktif[slideAktifIndex + 1].terkunci = 0;
            }
            renderStepSlide();
            renderIsiSlide();
        })
        .catch(() => {
            errBox.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
            errBox.classList.remove('hidden');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-arrow-right mr-1"></i> Tandai Selesai & Lanjut'; }
        });
}

document.addEventListener('DOMContentLoaded', muatDaftarMateriSiswa);
</script>
</body>
</html>