<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa = mysqli_real_escape_string($koneksi, $_GET['id_siswa']);
$pesan_sukses = "";
$pesan_error = "";
$target_dir = "../uploads/foto_siswa/";

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php', 'detail_biodata.php']);

if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        $pesan_error .= "Gagal membuat direktori upload: " . $target_dir;
    }
}

$daftar_agama = ['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Hindu', 'Buddha', 'Konghucu'];
$daftar_kepemilikan_gadget = ['HP Saja', 'Laptop Saja', 'Keduanya', 'Tidak Ada'];

$query_siswa = mysqli_query($koneksi, "
    SELECT 
        s.*,
        t.tahun AS tahun_ajaran,
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
    WHERE s.id_siswa='$id_siswa'
");
$siswa = mysqli_fetch_assoc($query_siswa);

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

$query_kecerdasan = mysqli_query($koneksi, "
    SELECT *
    FROM hasil_kecerdasan
    WHERE id_siswa='$id_siswa'
    ORDER BY tanggal_tes DESC 
    LIMIT 1
");
$hasil_kecerdasan = mysqli_fetch_assoc($query_kecerdasan);

$gaya_belajar = "Belum Mengisi";
if ($siswa['skor_visual'] !== null) {
    $skor_v = $siswa['skor_visual'];
    $skor_a = $siswa['skor_auditori'];
    $skor_k = $siswa['skor_kinestetik'];
    $skor_tertinggi = max($skor_v, $skor_a, $skor_k);

    $tipe_dominan = [];
    if ($skor_v == $skor_tertinggi) $tipe_dominan[] = 'Visual';
    if ($skor_a == $skor_tertinggi) $tipe_dominan[] = 'Auditorial';
    if ($skor_k == $skor_tertinggi) $tipe_dominan[] = 'Kinestetik';
    
    $gaya_belajar = implode(" & ", $tipe_dominan);
}

$hasil_tes_kemampuan_calculated = "Belum Mengisi";
if ($hasil_kecerdasan) {
    $skor_kecerdasan = [
        'A' => $hasil_kecerdasan['skor_A'] ?? 0,
        'B' => $hasil_kecerdasan['skor_B'] ?? 0,
        'C' => $hasil_kecerdasan['skor_C'] ?? 0,
        'D' => $hasil_kecerdasan['skor_D'] ?? 0,
        'E' => $hasil_kecerdasan['skor_E'] ?? 0,
        'F' => $hasil_kecerdasan['skor_F'] ?? 0,
        'G' => $hasil_kecerdasan['skor_G'] ?? 0,
        'H' => $hasil_kecerdasan['skor_H'] ?? 0,
    ];

    $skor_tertinggi_kecerdasan = max($skor_kecerdasan);

    if ($skor_tertinggi_kecerdasan > 0) {
        $kode_tertinggi = [];
        foreach ($skor_kecerdasan as $kode => $skor) {
            if ($skor == $skor_tertinggi_kecerdasan) {
                $kode_tertinggi[] = $kode;
            }
        }
        
        $kode_list = "'" . implode("','", $kode_tertinggi) . "'";
        $query_tipe = mysqli_query($koneksi, "
            SELECT nama_tipe 
            FROM keterangan_kecerdasan 
            WHERE kode_tipe IN ($kode_list)
        ");

        $tipe_dominan_kecerdasan = [];
        while ($tipe = mysqli_fetch_assoc($query_tipe)) {
            $tipe_dominan_kecerdasan[] = $tipe['nama_tipe'];
        }
        
        if (!empty($tipe_dominan_kecerdasan)) {
            $hasil_tes_kemampuan_calculated = implode(" & ", $tipe_dominan_kecerdasan);
        } else {
            $hasil_tes_kemampuan_calculated = implode(" & ", $kode_tertinggi) . " (Keterangan tipe belum terdaftar)";
        }
        
    } else {
        $hasil_tes_kemampuan_calculated = "Tes Kecerdasan Telah Dilakukan (Semua Skor 0)";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama_panggilan     = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
    $jenis_kelamin      = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin'] ?? '');
    $tempat_lahir       = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir'] ?? '');
    $tanggal_lahir      = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir'] ?? '');
    $agama              = mysqli_real_escape_string($koneksi, $_POST['agama'] ?? '');
    $tinggi_badan       = mysqli_real_escape_string($koneksi, $_POST['tinggi_badan'] ?? '');
    $berat_badan        = mysqli_real_escape_string($koneksi, $_POST['berat_badan'] ?? '');
    $alamat_lengkap     = mysqli_real_escape_string($koneksi, $_POST['alamat_lengkap'] ?? '');
    $no_telp            = mysqli_real_escape_string($koneksi, $_POST['no_telp'] ?? '');
    $email              = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $instagram          = mysqli_real_escape_string($koneksi, $_POST['instagram'] ?? '');
    $hobi_kegemaran     = mysqli_real_escape_string($koneksi, $_POST['hobi_kegemaran'] ?? '');
    $tentang_saya_singkat = mysqli_real_escape_string($koneksi, $_POST['tentang_saya_singkat'] ?? '');
    $riwayat_sma_smk_ma = mysqli_real_escape_string($koneksi, $_POST['riwayat_sma_smk_ma'] ?? '');
    $riwayat_smp_mts    = mysqli_real_escape_string($koneksi, $_POST['riwayat_smp_mts'] ?? '');
    $riwayat_sd_mi      = mysqli_real_escape_string($koneksi, $_POST['riwayat_sd_mi'] ?? '');
    $prestasi_pengalaman = mysqli_real_escape_string($koneksi, $_POST['prestasi_pengalaman'] ?? '');
    $organisasi         = mysqli_real_escape_string($koneksi, $_POST['organisasi'] ?? '');
    $anak_ke            = mysqli_real_escape_string($koneksi, $_POST['anak_ke'] ?? '');
    $suku               = mysqli_real_escape_string($koneksi, $_POST['suku'] ?? '');
    $cita_cita          = mysqli_real_escape_string($koneksi, $_POST['cita_cita'] ?? '');
    $riwayat_penyakit   = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit'] ?? '');
    $nama_ayah          = mysqli_real_escape_string($koneksi, $_POST['nama_ayah'] ?? '');
    $pekerjaan_ayah     = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ayah'] ?? '');
    $nama_ibu           = mysqli_real_escape_string($koneksi, $_POST['nama_ibu'] ?? '');
    $pekerjaan_ibu      = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ibu'] ?? '');
    $no_hp_ortu         = mysqli_real_escape_string($koneksi, $_POST['no_hp_ortu'] ?? '');
    $status_tempat_tinggal = mysqli_real_escape_string($koneksi, $_POST['status_tempat_tinggal'] ?? '');
    $jarak_ke_sekolah   = mysqli_real_escape_string($koneksi, $_POST['jarak_ke_sekolah'] ?? '');
    $transportasi_ke_sekolah = mysqli_real_escape_string($koneksi, $_POST['transportasi_ke_sekolah'] ?? '');
    $memiliki_hp_laptop = mysqli_real_escape_string($koneksi, $_POST['memiliki_hp_laptop'] ?? '');
    $fasilitas_internet = mysqli_real_escape_string($koneksi, $_POST['fasilitas_internet'] ?? '');
    $fasilitas_belajar_dirumah = mysqli_real_escape_string($koneksi, $_POST['fasilitas_belajar_dirumah'] ?? '');
    $buku_pelajaran_dimiliki = mysqli_real_escape_string($koneksi, $_POST['buku_pelajaran_dimiliki'] ?? '');
    $bahasa_sehari_hari = mysqli_real_escape_string($koneksi, $_POST['bahasa_sehari_hari'] ?? '');
    $bahasa_asing_dikuasai = mysqli_real_escape_string($koneksi, $_POST['bahasa_asing_dikuasai'] ?? '');
    $pelajaran_disenangi = mysqli_real_escape_string($koneksi, $_POST['pelajaran_disenangi'] ?? '');
    $pelajaran_tdk_disenangi = mysqli_real_escape_string($koneksi, $_POST['pelajaran_tdk_disenangi'] ?? '');
    $tempat_curhat      = mysqli_real_escape_string($koneksi, $_POST['tempat_curhat'] ?? '');
    $kelebihan_diri     = mysqli_real_escape_string($koneksi, $_POST['kelebihan_diri'] ?? '');
    $kekurangan_diri    = mysqli_real_escape_string($koneksi, $_POST['kekurangan_diri'] ?? '');

    $url_foto_baru = $siswa['url_foto'];

    if (isset($_FILES['url_foto']) && $_FILES['url_foto']['error'] == 0) {
        $file_name = $_FILES['url_foto']['name'];
        $file_tmp = $_FILES['url_foto']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = "foto_" . $siswa['nis'] . "_" . time() . "." . $file_ext;
        $upload_path = $target_dir . $new_file_name;

        $allowed_extensions = array("jpg", "jpeg", "png");
        $max_file_size = 5 * 1024 * 1024;

        if (!in_array($file_ext, $allowed_extensions)) {
            $pesan_error .= "Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG. ";
        } elseif ($_FILES['url_foto']['size'] > $max_file_size) {
            $pesan_error .= "Ukuran file terlalu besar. Maksimal 5MB. ";
        } else {
            if (move_uploaded_file($file_tmp, $upload_path)) {
                if (!empty($siswa['url_foto']) && file_exists('../' . $siswa['url_foto'])) {
                    @unlink('../' . $siswa['url_foto']); 
                }
                $url_foto_baru = 'uploads/foto_siswa/' . $new_file_name;
            } else {
                $pesan_error .= "Gagal mengupload foto. ";
            }
        }
    }

    if (empty($pesan_error)) {
        $update_query = "
            UPDATE siswa SET
                nama_panggilan = '$nama_panggilan',
                jenis_kelamin = '$jenis_kelamin',
                tempat_lahir = '$tempat_lahir',
                tanggal_lahir = '$tanggal_lahir',
                agama = '$agama',
                tinggi_badan = " . (empty($tinggi_badan) ? 'NULL' : "'$tinggi_badan'") . ",
                berat_badan = " . (empty($berat_badan) ? 'NULL' : "'$berat_badan'") . ",
                alamat_lengkap = '$alamat_lengkap',
                no_telp = '$no_telp',
                email = '$email',
                instagram = '$instagram',
                hobi_kegemaran = '$hobi_kegemaran',
                tentang_saya_singkat = '$tentang_saya_singkat',
                riwayat_sma_smk_ma = '$riwayat_sma_smk_ma',
                riwayat_smp_mts = '$riwayat_smp_mts',
                riwayat_sd_mi = '$riwayat_sd_mi',
                prestasi_pengalaman = '$prestasi_pengalaman',
                organisasi = '$organisasi',
                url_foto = '$url_foto_baru',
                anak_ke = '$anak_ke',
                suku = '$suku',
                cita_cita = '$cita_cita',
                riwayat_penyakit = '$riwayat_penyakit',
                nama_ayah = '$nama_ayah',
                pekerjaan_ayah = '$pekerjaan_ayah',
                nama_ibu = '$nama_ibu',
                pekerjaan_ibu = '$pekerjaan_ibu',
                no_hp_ortu = '$no_hp_ortu',
                status_tempat_tinggal = '$status_tempat_tinggal',
                jarak_ke_sekolah = '$jarak_ke_sekolah',
                transportasi_ke_sekolah = '$transportasi_ke_sekolah',
                memiliki_hp_laptop = '$memiliki_hp_laptop',
                fasilitas_internet = '$fasilitas_internet',
                fasilitas_belajar_dirumah = '$fasilitas_belajar_dirumah',
                buku_pelajaran_dimiliki = '$buku_pelajaran_dimiliki',
                bahasa_sehari_hari = '$bahasa_sehari_hari',
                bahasa_asing_dikuasai = '$bahasa_asing_dikuasai',
                pelajaran_disenangi = '$pelajaran_disenangi',
                pelajaran_tdk_disenangi = '$pelajaran_tdk_disenangi',
                tempat_curhat = '$tempat_curhat',
                kelebihan_diri = '$kelebihan_diri',
                kekurangan_diri = '$kekurangan_diri'
            WHERE id_siswa = '$id_siswa'
        ";

        if (mysqli_query($koneksi, $update_query)) {
            $pesan_sukses = "Data profil siswa berhasil diperbarui.";
            $query_siswa = mysqli_query($koneksi, "
                SELECT 
                    s.*,
                    t.tahun AS tahun_ajaran,
                    hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
                FROM siswa s
                JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
                LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
                WHERE s.id_siswa='$id_siswa'
            ");
            $siswa = mysqli_fetch_assoc($query_siswa);

            $query_kecerdasan = mysqli_query($koneksi, "
                SELECT *
                FROM hasil_kecerdasan
                WHERE id_siswa='$id_siswa'
                ORDER BY tanggal_tes DESC 
                LIMIT 1
            ");
            $hasil_kecerdasan = mysqli_fetch_assoc($query_kecerdasan);

            $gaya_belajar = "Belum Mengisi";
            if ($siswa['skor_visual'] !== null) {
                $skor_v = $siswa['skor_visual'];
                $skor_a = $siswa['skor_auditori'];
                $skor_k = $siswa['skor_kinestetik'];
                $skor_tertinggi = max($skor_v, $skor_a, $skor_k);

                $tipe_dominan = [];
                if ($skor_v == $skor_tertinggi) $tipe_dominan[] = 'Visual';
                if ($skor_a == $skor_tertinggi) $tipe_dominan[] = 'Auditorial';
                if ($skor_k == $skor_tertinggi) $tipe_dominan[] = 'Kinestetik';
                
                $gaya_belajar = implode(" & ", $tipe_dominan);
            }

            $hasil_tes_kemampuan_calculated = "Belum Mengisi";
            if ($hasil_kecerdasan) {
                $skor_kecerdasan = [
                    'A' => $hasil_kecerdasan['skor_A'] ?? 0,
                    'B' => $hasil_kecerdasan['skor_B'] ?? 0,
                    'C' => $hasil_kecerdasan['skor_C'] ?? 0,
                    'D' => $hasil_kecerdasan['skor_D'] ?? 0,
                    'E' => $hasil_kecerdasan['skor_E'] ?? 0,
                    'F' => $hasil_kecerdasan['skor_F'] ?? 0,
                    'G' => $hasil_kecerdasan['skor_G'] ?? 0,
                    'H' => $hasil_kecerdasan['skor_H'] ?? 0,
                ];

                $skor_tertinggi_kecerdasan = max($skor_kecerdasan);

                if ($skor_tertinggi_kecerdasan > 0) {
                    $kode_tertinggi = [];
                    foreach ($skor_kecerdasan as $kode => $skor) {
                        if ($skor == $skor_tertinggi_kecerdasan) {
                            $kode_tertinggi[] = $kode;
                        }
                    }
                    
                    $kode_list = "'" . implode("','", $kode_tertinggi) . "'";
                    $query_tipe = mysqli_query($koneksi, "
                        SELECT nama_tipe 
                        FROM keterangan_kecerdasan 
                        WHERE kode_tipe IN ($kode_list)
                    ");

                    $tipe_dominan_kecerdasan = [];
                    while ($tipe = mysqli_fetch_assoc($query_tipe)) {
                        $tipe_dominan_kecerdasan[] = $tipe['nama_tipe'];
                    }
                    
                    if (!empty($tipe_dominan_kecerdasan)) {
                        $hasil_tes_kemampuan_calculated = implode(" & ", $tipe_dominan_kecerdasan);
                    } else {
                        $hasil_tes_kemampuan_calculated = implode(" & ", $kode_tertinggi) . " (Keterangan tipe belum terdaftar)";
                    }
                    
                } else {
                    $hasil_tes_kemampuan_calculated = "Tes Kecerdasan Telah Dilakukan (Semua Skor 0)";
                }
            }

        } else {
            $pesan_error = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}

$url_foto_display = $siswa['url_foto'] ? '../' . $siswa['url_foto'] : 'https://www.gravatar.com/avatar/?d=mp&s=200';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Profil Siswa | Data <?php echo htmlspecialchars($siswa['nama']); ?></title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .primary-color {
            color: #2F6C6E;
        }
        .primary-bg {
            background-color: #2F6C6E;
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

        @media (min-width: 1024px) {
            .sticky-element {
                position: sticky;
                top: 24px;
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
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
        <div>
            <strong class="text-base font-semibold primary-color">Guru BK</strong><br>
            <small class="text-xs text-gray-500">SMKN 2 BJM</small>
        </div>
        <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 z-40 hover:bg-gray-100 rounded-lg transition">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="fade-slide hidden-transition fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Beranda
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
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
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
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
        
        <aside id="sidebar" class="sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-tie text-lg"></i>
                    </div>
                    <div>
                        <strong class="text-base font-semibold block">Guru BK</strong>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                
                <div class="nav-item cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex items-center">
                            <i class="fas fa-user-check mr-3"></i> Data & Laporan Siswa
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
                        <span class="flex items-center">
                            <i class="fas fa-calendar-alt mr-3"></i> Program BK
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

        <main class="flex-grow p-4 sm:p-6 lg:p-8 content-wrapper max-w-full">
            <h1 class="text-2xl md:text-3xl font-bold color-black mb-6">BIODATA SISWA</h1>
            
            <?php if ($pesan_sukses): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                <p class="font-bold">Sukses!</p>
                <p><?php echo $pesan_sukses; ?></p>
            </div>
            <?php endif; ?>

            <?php if ($pesan_error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md" role="alert">
                <p class="font-bold">Error!</p>
                <p><?php echo $pesan_error; ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="detail_biodata.php?id_siswa=<?php echo $id_siswa; ?>">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-lg border border-gray-200 h-fit sticky-element">
                        <h4 class="text-lg font-bold primary-color mb-4 border-b pb-2 flex items-center">
                            <i class="fas fa-camera mr-2"></i> Foto Profil
                        </h4>
                        <div class="flex flex-col items-center">
                            <img src="<?php echo $url_foto_display; ?>" alt="Foto Siswa" class="w-64 h-64 object-cover rounded-xl shadow-lg border-4 border-[#2F6C6E] mb-4" id="previewFoto">
                            <label for="url_foto" class="cursor-pointer bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold py-2 px-4 rounded-lg transition flex items-center shadow-md">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> Ubah Foto
                            </label>
                            <input type="file" name="url_foto" id="url_foto" class="hidden" accept="image/jpeg,image/png" onchange="previewImage(event)">
                            <p class="text-xs text-gray-500 mt-2">Max 5MB (JPG, JPEG, PNG)</p>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-6">

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-user-circle mr-2"></i> Data Pribadi
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" id="nama" value="<?php echo htmlspecialchars($siswa['nama'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border text-sm bg-gray-100 cursor-not-allowed" readonly>
                                </div>
                                <div>
                                    <label for="nis" class="block text-sm font-medium text-gray-700">NIS</label>
                                    <input type="text" id="nis" value="<?php echo htmlspecialchars($siswa['nis'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border text-sm bg-gray-100 cursor-not-allowed" readonly>
                                </div>
                                <div>
                                    <label for="kelas_jurusan" class="block text-sm font-medium text-gray-700">Kelas / Jurusan</label>
                                    <input type="text" id="kelas_jurusan" value="<?php echo htmlspecialchars($siswa['kelas'] . " / " . $siswa['jurusan']); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border text-sm bg-gray-100 cursor-not-allowed" readonly>
                                </div>
                                <div>
                                    <label for="tahun_ajaran" class="block text-sm font-medium text-gray-700">Tahun Ajaran</label>
                                    <input type="text" id="tahun_ajaran" value="<?php echo htmlspecialchars($siswa['tahun_ajaran'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border text-sm bg-gray-100 cursor-not-allowed" readonly>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="nama_panggilan" class="block text-sm font-medium text-gray-700">Nama Panggilan</label>
                                    <input type="text" name="nama_panggilan" id="nama_panggilan" value="<?php echo htmlspecialchars($siswa['nama_panggilan'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: Budi">
                                </div>

                                <div>
                                    <label for="jenis_kelamin" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" id="jenis_kelamin" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                                        <option value="">-- Pilih Jenis Kelamin --</option>
                                        <option value="L" <?php echo ($siswa['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo ($siswa['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="tempat_lahir" class="block text-sm font-medium text-gray-700">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" id="tempat_lahir" value="<?php echo htmlspecialchars($siswa['tempat_lahir'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: Banjarmasin">
                                </div>
                                
                                <div>
                                    <label for="tanggal_lahir" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                                </div>
                                
                                <div>
                                    <label for="agama" class="block text-sm font-medium text-gray-700">Agama</label>
                                    <select name="agama" id="agama" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($daftar_agama as $agama): ?>
                                        <option value="<?php echo $agama; ?>" <?php echo ($siswa['agama'] == $agama) ? 'selected' : ''; ?>><?php echo $agama; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="tinggi_badan" class="block text-sm font-medium text-gray-700">Tinggi Badan (cm)</label>
                                    <input type="number" name="tinggi_badan" id="tinggi_badan" value="<?php echo htmlspecialchars($siswa['tinggi_badan'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: 170">
                                </div>
                                
                                <div>
                                    <label for="berat_badan" class="block text-sm font-medium text-gray-700">Berat Badan (kg)</label>
                                    <input type="number" name="berat_badan" id="berat_badan" value="<?php echo htmlspecialchars($siswa['berat_badan'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: 60">
                                </div>
                                
                                <div>
                                    <label for="anak_ke" class="block text-sm font-medium text-gray-700">Anak Ke- (dari jumlah bersaudara)</label>
                                    <input type="text" id="anak_ke" name="anak_ke" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['anak_ke'] ?? ''); ?>" placeholder="Cth: 2 dari 3">
                                </div>

                                <div>
                                    <label for="suku" class="block text-sm font-medium text-gray-700">Suku</label>
                                    <input type="text" id="suku" name="suku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['suku'] ?? ''); ?>" placeholder="Cth: Banjar / Jawa">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="cita_cita" class="block text-sm font-medium text-gray-700">Cita-Cita / Tujuan</label>
                                    <input type="text" id="cita_cita" name="cita_cita" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['cita_cita'] ?? ''); ?>" placeholder="Cth: Programmer / Kuliah ke luar negeri">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="riwayat_penyakit" class="block text-sm font-medium text-gray-700">Riwayat Penyakit (Serius/Kronis)</label>
                                    <textarea id="riwayat_penyakit" name="riwayat_penyakit" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Kosongkan jika tidak ada/tidak punya"><?php echo htmlspecialchars($siswa['riwayat_penyakit'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="alamat_lengkap" class="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                                    <textarea name="alamat_lengkap" id="alamat_lengkap" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Masukkan alamat lengkap (Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten)"><?php echo htmlspecialchars($siswa['alamat_lengkap'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="tentang_saya_singkat" class="block text-sm font-medium text-gray-700">Tentang Saya Singkat (Personal Branding)</label>
                                    <textarea name="tentang_saya_singkat" id="tentang_saya_singkat" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Jelaskan diri Anda dalam 2-3 kalimat..."><?php echo htmlspecialchars($siswa['tentang_saya_singkat'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="hobi_kegemaran" class="block text-sm font-medium text-gray-700">Hobi & Kegemaran</label>
                                    <textarea name="hobi_kegemaran" id="hobi_kegemaran" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: Membaca novel, bermain futsal, melukis."><?php echo htmlspecialchars($siswa['hobi_kegemaran'] ?? ''); ?></textarea>
                                </div>

                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-users mr-2"></i> Data Orang Tua & Keluarga
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-700 border-b pb-2">Ayah</h4>
                                    <div>
                                        <label for="nama_ayah" class="block text-sm font-medium text-gray-700">Nama Ayah</label>
                                        <input type="text" id="nama_ayah" name="nama_ayah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['nama_ayah'] ?? ''); ?>" placeholder="Nama Ayah Kandung/Wali">
                                    </div>
                                    <div>
                                        <label for="pekerjaan_ayah" class="block text-sm font-medium text-gray-700">Pekerjaan Ayah</label>
                                        <input type="text" id="pekerjaan_ayah" name="pekerjaan_ayah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['pekerjaan_ayah'] ?? ''); ?>" placeholder="Contoh: Wiraswasta">
                                    </div>
                                </div>
                                
                                <div class="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <h4 class="text-lg font-semibold text-gray-700 border-b pb-2">Ibu</h4>
                                    <div>
                                        <label for="nama_ibu" class="block text-sm font-medium text-gray-700">Nama Ibu</label>
                                        <input type="text" id="nama_ibu" name="nama_ibu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['nama_ibu'] ?? ''); ?>" placeholder="Nama Ibu Kandung/Wali">
                                    </div>
                                    <div>
                                        <label for="pekerjaan_ibu" class="block text-sm font-medium text-gray-700">Pekerjaan Ibu</label>
                                        <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['pekerjaan_ibu'] ?? ''); ?>" placeholder="Contoh: Ibu Rumah Tangga">
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="no_hp_ortu" class="block text-sm font-medium text-gray-700">No. HP Orang Tua / Wali</label>
                                    <input type="text" id="no_hp_ortu" name="no_hp_ortu" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['no_hp_ortu'] ?? ''); ?>" placeholder="Cth: 0812xxxxxxxx">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i> Data Tempat Tinggal & Fasilitas
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="status_tempat_tinggal" class="block text-sm font-medium text-gray-700">Status Tempat Tinggal</label>
                                    <input type="text" id="status_tempat_tinggal" name="status_tempat_tinggal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['status_tempat_tinggal'] ?? ''); ?>" placeholder="Cth: Rumah Sendiri / Kost / Kontrak">
                                </div>
                                <div>
                                    <label for="jarak_ke_sekolah" class="block text-sm font-medium text-gray-700">Jarak ke Sekolah (km)</label>
                                    <input type="text" id="jarak_ke_sekolah" name="jarak_ke_sekolah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['jarak_ke_sekolah'] ?? ''); ?>" placeholder="Cth: 2 km / >5 km">
                                </div>
                                <div>
                                    <label for="transportasi_ke_sekolah" class="block text-sm font-medium text-gray-700">Transportasi ke Sekolah</label>
                                    <input type="text" id="transportasi_ke_sekolah" name="transportasi_ke_sekolah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['transportasi_ke_sekolah'] ?? ''); ?>" placeholder="Cth: Sepeda Motor / Angkutan Umum / Jalan Kaki">
                                </div>
                                <div>
                                    <label for="memiliki_hp_laptop" class="block text-sm font-medium text-gray-700">Kepemilikan Gadget (HP/Laptop)</label>
                                    <select name="memiliki_hp_laptop" id="memiliki_hp_laptop" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm">
                                        <option value="">-- Pilih Kepemilikan --</option>
                                        <?php foreach ($daftar_kepemilikan_gadget as $gadget): ?>
                                            <option value="<?php echo $gadget; ?>" <?php echo ($siswa['memiliki_hp_laptop'] == $gadget) ? 'selected' : ''; ?>><?php echo $gadget; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="fasilitas_internet" class="block text-sm font-medium text-gray-700">Fasilitas Internet di Rumah</label>
                                    <input type="text" id="fasilitas_internet" name="fasilitas_internet" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['fasilitas_internet'] ?? ''); ?>" placeholder="Cth: Ada (Wifi) / Tidak Ada">
                                </div>
                                <div>
                                    <label for="fasilitas_belajar_dirumah" class="block text-sm font-medium text-gray-700">Fasilitas Belajar di Rumah</label>
                                    <input type="text" id="fasilitas_belajar_dirumah" name="fasilitas_belajar_dirumah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['fasilitas_belajar_dirumah'] ?? ''); ?>" placeholder="Cth: Meja Belajar, Ruangan Sendiri">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="buku_pelajaran_dimiliki" class="block text-sm font-medium text-gray-700">Buku Pelajaran yang Dimiliki</label>
                                    <textarea id="buku_pelajaran_dimiliki" name="buku_pelajaran_dimiliki" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Contoh: Buku Paket, Buku LKS, Buku Catatan"><?php echo htmlspecialchars($siswa['buku_pelajaran_dimiliki'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label for="bahasa_sehari_hari" class="block text-sm font-medium text-gray-700">Bahasa Sehari-hari</label>
                                    <input type="text" id="bahasa_sehari_hari" name="bahasa_sehari_hari" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['bahasa_sehari_hari'] ?? ''); ?>" placeholder="Cth: Indonesia, Banjar">
                                </div>
                                <div>
                                    <label for="bahasa_asing_dikuasai" class="block text-sm font-medium text-gray-700">Bahasa Asing yang Dikuasai</label>
                                    <input type="text" id="bahasa_asing_dikuasai" name="bahasa_asing_dikuasai" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['bahasa_asing_dikuasai'] ?? ''); ?>" placeholder="Cth: Inggris, Jepang">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-bullseye mr-2"></i> Preferensi Belajar & Diri
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="pelajaran_disenangi" class="block text-sm font-medium text-gray-700">Pelajaran yang Disenangi</label>
                                    <input type="text" id="pelajaran_disenangi" name="pelajaran_disenangi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['pelajaran_disenangi'] ?? ''); ?>" placeholder="Cth: Matematika, Olahraga, Produktif">
                                </div>
                                <div>
                                    <label for="pelajaran_tdk_disenangi" class="block text-sm font-medium text-gray-700">Pelajaran yang Tidak Disenangi</label>
                                    <input type="text" id="pelajaran_tdk_disenangi" name="pelajaran_tdk_disenangi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['pelajaran_tdk_disenangi'] ?? ''); ?>" placeholder="Cth: Sejarah, Bahasa Inggris">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="tempat_curhat" class="block text-sm font-medium text-gray-700">Tempat Curhat / Berbagi Cerita</label>
                                    <textarea id="tempat_curhat" name="tempat_curhat" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Cth: Orang Tua, Teman Dekat, Pacar, Tidak Ada"><?php echo htmlspecialchars($siswa['tempat_curhat'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label for="kelebihan_diri" class="block text-sm font-medium text-gray-700">Kelebihan yang Dimiliki</label>
                                    <textarea id="kelebihan_diri" name="kelebihan_diri" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Cth: Disiplin, Mudah bergaul, Cepat tanggap..."><?php echo htmlspecialchars($siswa['kelebihan_diri'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label for="kekurangan_diri" class="block text-sm font-medium text-gray-700">Kekurangan yang Dimiliki</label>
                                    <textarea id="kekurangan_diri" name="kekurangan_diri" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Cth: Kurang percaya diri, Sering menunda, Pelupa..."><?php echo htmlspecialchars($siswa['kekurangan_diri'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-graduation-cap mr-2"></i> Riwayat Pendidikan
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="riwayat_sma_smk_ma" class="block text-sm font-medium text-gray-700">Riwayat SMA/SMK/MA</label>
                                    <input type="text" id="riwayat_sma_smk_ma" name="riwayat_sma_smk_ma" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? ''); ?>" placeholder="Contoh: SMKN 2 Banjarmasin (2023-Sekarang)">
                                </div>
                                <div>
                                    <label for="riwayat_smp_mts" class="block text-sm font-medium text-gray-700">Riwayat SMP/MTs</label>
                                    <input type="text" id="riwayat_smp_mts" name="riwayat_smp_mts" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['riwayat_smp_mts'] ?? ''); ?>" placeholder="Contoh: SMPN 1 Banjarmasin (2020-2023)">
                                </div>
                                <div>
                                    <label for="riwayat_sd_mi" class="block text-sm font-medium text-gray-700">Riwayat SD/MI</label>
                                    <input type="text" id="riwayat_sd_mi" name="riwayat_sd_mi" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" value="<?php echo htmlspecialchars($siswa['riwayat_sd_mi'] ?? ''); ?>" placeholder="Contoh: SDN 1 Banjarmasin (2014-2020)">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
                            <h3 class="text-xl font-bold text-gray-700 primary-color mb-6 border-b pb-2 flex items-center">
                                <i class="fas fa-award mr-2"></i> Prestasi & Organisasi
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="prestasi_pengalaman" class="block text-sm font-medium text-gray-700">Prestasi dan Pengalaman</label>
                                    <textarea id="prestasi_pengalaman" name="prestasi_pengalaman" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Tuliskan prestasi atau pengalaman, pisahkan dengan baris baru. (Kosongkan jika tidak ada/tidak punya)"><?php echo htmlspecialchars($siswa['prestasi_pengalaman'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label for="organisasi" class="block text-sm font-medium text-gray-700">Organisasi di Sekolah / Eksternal</label>
                                    <textarea id="organisasi" name="organisasi" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2 border text-sm" placeholder="Tuliskan daftar organisasi yang diikuti, pisahkan dengan baris baru. (Kosongkan jika tidak ada/tidak punya)"><?php echo htmlspecialchars($siswa['organisasi'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row justify-between sm:justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <button type="submit" class="w-full sm:w-auto primary-bg text-white px-8 py-3 rounded-lg font-bold hover:bg-[#3C7F81] transition flex items-center justify-center text-sm sm:text-base">
                        <i class="fas fa-save mr-2"></i> SIMPAN PERUBAHAN
                    </button>
                    
                    <a href="cv_template.php" id="btnExportCV" data-idsiswa="<?php echo $id_siswa; ?>" class="w-full sm:w-auto bg-green-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-600 transition flex items-center justify-center text-sm sm:text-base">
                        <i class="fas fa-file-pdf mr-2"></i> EKSPOR (PDF)
                    </a>
                    <script>
document.getElementById('btnExportCV').addEventListener('click', function(e) {
    e.preventDefault();

    const idSiswa = this.getAttribute('data-idsiswa'); 

    const urlExport = `cv_template.php?id_siswa=${idSiswa}`;

    fetch(urlExport) 
    .then(response => response.text())
    .then(html => {
        const printWindow = window.open('', '_blank', 'width=900,height=1200');
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
            }, 800);
        };
    });
});
</script>

                    <a href="hasil_tes.php" class="w-full sm:w-auto bg-gray-500 text-white px-8 py-3 rounded-lg font-bold hover:bg-gray-600 transition flex items-center justify-center text-sm sm:text-base">
                        <i class="fas fa-angle-left mr-2"></i> KEMBALI
                    </a>
                </div>
            </form>
        </main>
    </div>

    <footer class="text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>
    <script>
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>