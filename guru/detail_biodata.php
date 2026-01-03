<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa     = mysqli_real_escape_string($koneksi, $_GET['id_siswa']);
$pesan_sukses = "";
$pesan_error  = "";
$target_dir   = "../uploads/foto_siswa/";

$current_page           = basename($_SERVER['PHP_SELF']);
$is_profiling_active  = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php', 'detail_biodata.php']);

if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0777, true)) {
        $pesan_error .= "Gagal membuat direktori upload: " . $target_dir;
    }
}

$daftar_agama = ['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Hindu', 'Buddha', 'Konghucu'];
$daftar_kepemilikan_gadget = ['HP Saja', 'Laptop Saja', 'Keduanya', 'Tidak Ada'];
$daftar_status_tinggal = ['Bersama Orang Tua', 'Kost', 'Asrama', 'Lainnya'];
$daftar_jarak = ['< 1 km', '1 - 5 km', '6 - 10 km', '> 10 km'];
$daftar_transportasi = ['Jalan Kaki', 'Kendaraan Pribadi', 'Angkutan Umum', 'Antar Jemput'];
$daftar_fasilitas_internet = ['Pribadi (HP/Modem)', 'WiFi Rumah', 'WiFi Sekolah', 'Tidak Ada'];
$daftar_fasilitas_belajar = ['Meja Belajar', 'Ruang Khusus', 'Tidak Ada'];
$daftar_buku_pelajaran = ['Semua Dimiliki', 'Sebagian Dimiliki', 'Minim/Tidak Ada'];

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
$skor_kecerdasan = [];
$map_kecerdasan = [
    'A' => 'Linguistik (Bahasa)',
    'B' => 'Logis-Matematis',
    'C' => 'Spasial-Visual',
    'D' => 'Kinestetik-Jasmani',
    'E' => 'Musikal',
    'F' => 'Interpersonal',
    'G' => 'Intrapersonal',
    'H' => 'Naturalis',
];

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
            $tipe_dominan_kecerdasan_raw = [];
            foreach ($kode_tertinggi as $kode) {
                $tipe_dominan_kecerdasan_raw[] = $map_kecerdasan[$kode] ?? $kode;
            }
            $hasil_tes_kemampuan_calculated = implode(" & ", $tipe_dominan_kecerdasan_raw);
        }
        
    } else {
        $hasil_tes_kemampuan_calculated = "Tes Kecerdasan Telah Dilakukan (Semua Skor 0)";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    

    $nama_panggilan             = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
    $jenis_kelamin              = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin'] ?? '');
    $tempat_lahir               = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir'] ?? '');
    $tanggal_lahir              = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir'] ?? '');
    $agama                      = mysqli_real_escape_string($koneksi, $_POST['agama'] ?? '');
    $tinggi_badan               = mysqli_real_escape_string($koneksi, $_POST['tinggi_badan'] ?? '');
    $berat_badan                = mysqli_real_escape_string($koneksi, $_POST['berat_badan'] ?? '');
    $alamat_lengkap             = mysqli_real_escape_string($koneksi, $_POST['alamat_lengkap'] ?? '');
    $no_telp                    = mysqli_real_escape_string($koneksi, $_POST['no_telp'] ?? '');
    $email                      = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $instagram                  = mysqli_real_escape_string($koneksi, $_POST['instagram'] ?? '');
    $hobi_kegemaran             = mysqli_real_escape_string($koneksi, $_POST['hobi_kegemaran'] ?? '');
    $tentang_saya_singkat       = mysqli_real_escape_string($koneksi, $_POST['tentang_saya_singkat'] ?? '');
    $riwayat_sma_smk_ma         = mysqli_real_escape_string($koneksi, $_POST['riwayat_sma_smk_ma'] ?? '');
    $riwayat_smp_mts            = mysqli_real_escape_string($koneksi, $_POST['riwayat_smp_mts'] ?? '');
    $riwayat_sd_mi              = mysqli_real_escape_string($koneksi, $_POST['riwayat_sd_mi'] ?? '');
    $prestasi_pengalaman        = mysqli_real_escape_string($koneksi, $_POST['prestasi_pengalaman'] ?? '');
    $organisasi                 = mysqli_real_escape_string($koneksi, $_POST['organisasi'] ?? '');
    $anak_ke                    = mysqli_real_escape_string($koneksi, $_POST['anak_ke'] ?? '');
    $suku                       = mysqli_real_escape_string($koneksi, $_POST['suku'] ?? '');
    $cita_cita                  = mysqli_real_escape_string($koneksi, $_POST['cita_cita'] ?? '');
    $riwayat_penyakit           = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit'] ?? '');
    $nama_ayah                  = mysqli_real_escape_string($koneksi, $_POST['nama_ayah'] ?? '');
    $pekerjaan_ayah             = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ayah'] ?? '');
    $nama_ibu                   = mysqli_real_escape_string($koneksi, $_POST['nama_ibu'] ?? '');
    $pekerjaan_ibu              = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ibu'] ?? '');
    $no_hp_ortu                 = mysqli_real_escape_string($koneksi, $_POST['no_hp_ortu'] ?? '');
    $status_tempat_tinggal      = mysqli_real_escape_string($koneksi, $_POST['status_tempat_tinggal'] ?? '');
    $jarak_ke_sekolah           = mysqli_real_escape_string($koneksi, $_POST['jarak_ke_sekolah'] ?? '');
    $transportasi_ke_sekolah    = mysqli_real_escape_string($koneksi, $_POST['transportasi_ke_sekolah'] ?? '');
    $memiliki_hp_laptop         = mysqli_real_escape_string($koneksi, $_POST['memiliki_hp_laptop'] ?? '');
    $fasilitas_internet         = mysqli_real_escape_string($koneksi, $_POST['fasilitas_internet'] ?? '');
    $fasilitas_belajar_dirumah  = mysqli_real_escape_string($koneksi, $_POST['fasilitas_belajar_dirumah'] ?? '');
    $buku_pelajaran_dimiliki    = mysqli_real_escape_string($koneksi, $_POST['buku_pelajaran_dimiliki'] ?? '');
    $bahasa_sehari_hari         = mysqli_real_escape_string($koneksi, $_POST['bahasa_sehari_hari'] ?? '');
    $bahasa_asing_dikuasai      = mysqli_real_escape_string($koneksi, $_POST['bahasa_asing_dikuasai'] ?? '');
    $pelajaran_disenangi        = mysqli_real_escape_string($koneksi, $_POST['pelajaran_disenangi'] ?? '');
    $pelajaran_tdk_disenangi    = mysqli_real_escape_string($koneksi, $_POST['pelajaran_tdk_disenangi'] ?? '');
    $tempat_curhat              = mysqli_real_escape_string($koneksi, $_POST['tempat_curhat'] ?? '');
    $kelebihan_diri             = mysqli_real_escape_string($koneksi, $_POST['kelebihan_diri'] ?? '');
    $kekurangan_diri            = mysqli_real_escape_string($koneksi, $_POST['kekurangan_diri'] ?? '');

    $url_foto_baru = $siswa['url_foto'];

    if (isset($_FILES['url_foto']) && $_FILES['url_foto']['error'] == 0) {
        $file_name          = $_FILES['url_foto']['name'];
        $file_tmp           = $_FILES['url_foto']['tmp_name'];
        $file_ext           = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name      = "foto_" . $siswa['nis'] . "_" . time() . "." . $file_ext;
        $upload_path        = $target_dir . $new_file_name;

        $allowed_extensions = array("jpg", "jpeg", "png");
        $max_file_size      = 5 * 1024 * 1024; 
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
                nama_panggilan              = '$nama_panggilan',
                jenis_kelamin               = '$jenis_kelamin',
                tempat_lahir                = '$tempat_lahir',
                tanggal_lahir               = '$tanggal_lahir',
                agama                       = '$agama',
                tinggi_badan                = " . (empty($tinggi_badan) ? 'NULL' : "'$tinggi_badan'") . ",
                berat_badan                 = " . (empty($berat_badan) ? 'NULL' : "'$berat_badan'") . ",
                alamat_lengkap              = '$alamat_lengkap',
                no_telp                     = '$no_telp',
                email                       = '$email',
                instagram                   = '$instagram',
                hobi_kegemaran              = '$hobi_kegemaran',
                tentang_saya_singkat        = '$tentang_saya_singkat',
                riwayat_sma_smk_ma          = '$riwayat_sma_smk_ma',
                riwayat_smp_mts             = '$riwayat_smp_mts',
                riwayat_sd_mi               = '$riwayat_sd_mi',
                prestasi_pengalaman         = '$prestasi_pengalaman',
                organisasi                  = '$organisasi',
                url_foto                    = '$url_foto_baru',
                anak_ke                     = '$anak_ke',
                suku                        = '$suku',
                cita_cita                   = '$cita_cita',
                riwayat_penyakit            = '$riwayat_penyakit',
                nama_ayah                   = '$nama_ayah',
                pekerjaan_ayah              = '$pekerjaan_ayah',
                nama_ibu                    = '$nama_ibu',
                pekerjaan_ibu               = '$pekerjaan_ibu',
                no_hp_ortu                  = '$no_hp_ortu',
                status_tempat_tinggal       = '$status_tempat_tinggal',
                jarak_ke_sekolah            = '$jarak_ke_sekolah',
                transportasi_ke_sekolah     = '$transportasi_ke_sekolah',
                memiliki_hp_laptop          = '$memiliki_hp_laptop',
                fasilitas_internet          = '$fasilitas_internet',
                fasilitas_belajar_dirumah   = '$fasilitas_belajar_dirumah',
                buku_pelajaran_dimiliki     = '$buku_pelajaran_dimiliki',
                bahasa_sehari_hari          = '$bahasa_sehari_hari',
                bahasa_asing_dikuasai       = '$bahasa_asing_dikuasai',
                pelajaran_disenangi         = '$pelajaran_disenangi',
                pelajaran_tdk_disenangi     = '$pelajaran_tdk_disenangi',
                tempat_curhat               = '$tempat_curhat',
                kelebihan_diri              = '$kelebihan_diri',
                kekurangan_diri             = '$kekurangan_diri'
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
            $skor_kecerdasan = [];
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
                        $tipe_dominan_kecerdasan_raw = [];
                        foreach ($kode_tertinggi as $kode) {
                            $tipe_dominan_kecerdasan_raw[] = $map_kecerdasan[$kode] ?? $kode;
                        }
                        $hasil_tes_kemampuan_calculated = implode(" & ", $tipe_dominan_kecerdasan_raw);
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #0F3A3A;
            --primary-dark: #0B2E2E;
            --primary-light: #123E44;
            --accent: #5FA8A1;
            --accent-dark: #4C8E89;
            --accent-light: #7BC4BE;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-700: #374151;
            --success: #10B981;
            --danger: #EF4444;
        }

        body {
            background: #0F3A3A;
            min-height: 100vh;
        }

        header {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .photo-container {
            position: relative;
            animation: fadeInScale 0.6s ease-out;
        }

        .photo-container::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: linear-gradient(45deg, var(--accent), var(--accent-light), var(--primary));
            border-radius: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .photo-container:hover::before {
            opacity: 0.7;
        }

        @keyframes rotate {
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .stat-card {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            animation: slideUp 0.6s ease-out backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-item {
            position: relative;
            padding: 12px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--accent);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .nav-item:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(95, 168, 161, 0.1) 0%, rgba(95, 168, 161, 0.05) 100%);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-item.active::before {
            width: 100%;
        }

        .mobile-tabs {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .mobile-tabs::-webkit-scrollbar {
            display: none;
        }

        .mobile-tab-item {
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .mobile-tab-item.active {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(95, 168, 161, 0.3);
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(95, 168, 161, 0.1);
            transform: translateY(-1px);
        }

        input, textarea, select {
            transition: all 0.3s ease;
        }

        .btn-primary {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(95, 168, 161, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.4);
        }

        .tooltip-container {
            position: relative;
            cursor: pointer;
        }

        .tooltip {
            visibility: hidden;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            text-align: left;
            border-radius: 12px;
            padding: 12px 16px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            margin-left: -140px;
            opacity: 0;
            transition: all 0.3s ease;
            font-size: 0.75rem;
            line-height: 1.5;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .tooltip-container:hover .tooltip {
            visibility: visible;
            opacity: 1;
            bottom: 130%;
        }

        .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -8px;
            border-width: 8px;
            border-style: solid;
            border-color: var(--primary) transparent transparent transparent;
        }

        .tab-content {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-animate {
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .section-header {
            position: relative;
            padding-left: 16px;
        }

        .section-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: linear-gradient(180deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 2px;
        }

        .form-divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }

        .form-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gray-200), transparent);
        }

        .form-divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: var(--gray-700);
            font-weight: 600;
        }

        .progress-ring {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .sticky-sidebar {
            position: sticky;
            top: 5.5rem;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .sticky-sidebar {
                position: relative;
                top: 0;
            }

            .nav-item {
                padding: 10px 16px;
            }
        }

        .icon-bounce {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-dark);
        }
    </style>

    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('previewFoto');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('photo-container');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function changeTab(tabId) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.add('hidden');
                content.style.opacity = 0;
            });

            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.remove('active');
            });

            const selectedContent = document.getElementById(tabId);
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
                setTimeout(() => {
                    selectedContent.style.opacity = 1;
                }, 10);
            }

            const selectedNav = document.querySelector(`.nav-item[onclick="changeTab('${tabId}')"]`);
            if (selectedNav) {
                selectedNav.classList.add('active');
            }

            const mobileNavItems = document.querySelectorAll('.mobile-tab-item');
            mobileNavItems.forEach(item => {
                item.classList.remove('active');
            });
            
            const selectedMobileNav = document.querySelector(`.mobile-tab-item[onclick="changeTab('${tabId}')"]`);
            if (selectedMobileNav) {
                selectedMobileNav.classList.add('active');

                selectedMobileNav.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            changeTab('data-pribadi');

            const alerts = document.querySelectorAll('.alert-animate');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideInRight 0.5s ease-out';
                }, 100);
            });
        });
    </script>
</head>

<body class="bg-gradient-to-br from-blue-50 via-cyan-50 to-teal-50 min-h-screen">
    <header class="top-0 left-0 w-full shadow-lg z-30 flex items-center justify-between h-16 px-6 sticky">
        <a href="#" class="flex items-center space-x-3 group">
            <div class="relative">
                <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-10 w-10 transition-transform group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-r from-teal-400 to-cyan-400 rounded-full opacity-0 group-hover:opacity-20 transition-opacity"></div>
            </div>
            <div>
                <span class="text-xl font-bold gradient-text block">Detail Siswa</span>
            </div>
        </a>
        <a href="javascript:void(0);" onclick="goBack()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all font-bold text-sm">
    <i class="fas fa-arrow-left"></i> Kembali
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
    </header>
    
    <div class="p-4 md:p-8"> 
        <div class="max-w-7xl mx-auto">

            <div class="mb-8">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-2">
                    <i class="fas fa-user-graduate text-teal-600 mr-3"></i>
                    <?php echo htmlspecialchars($siswa['nama']); ?>
                </h2>
                <p class="text-gray-600 ml-12">NIS: <?php echo htmlspecialchars($siswa['nis']); ?> • <?php echo htmlspecialchars($siswa['kelas'] . " - " . $siswa['jurusan']); ?></p>
            </div>

            <?php if ($pesan_sukses): ?>
            <div class="alert-animate bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 p-5 mb-6 rounded-xl shadow-lg" role="alert">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-2xl text-green-500 mr-4 mt-1"></i>
                    <div>
                        <p class="font-bold text-lg">Berhasil!</p>
                        <p class="text-sm mt-1"><?php echo $pesan_sukses; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($pesan_error): ?>
            <div class="alert-animate bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 p-5 mb-6 rounded-xl shadow-lg" role="alert">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-500 mr-4 mt-1"></i>
                    <div>
                        <p class="font-bold text-lg">Terjadi Kesalahan!</p>
                        <p class="text-sm mt-1"><?php echo $pesan_error; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="detail_biodata.php?id_siswa=<?php echo $id_siswa; ?>">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <div class="lg:col-span-1 space-y-6 sticky-sidebar">

                        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 card-hover">
                            <h4 class="text-lg font-bold text-gray-800 mb-5 section-header flex items-center">
                                <i class="fas fa-camera text-teal-600 mr-2"></i> Foto Profil
                            </h4>
                            <div class="flex flex-col items-center">
                                <div class="photo-container mb-5">
                                    <img src="<?php echo $url_foto_display; ?>" alt="Foto Siswa" class="w-48 h-48 md:w-56 md:h-56 object-cover rounded-2xl shadow-2xl border-4 border-white ring-4 ring-teal-100" id="previewFoto">
                                </div>
                                <label for="url_foto" class="cursor-pointer btn-primary text-white text-sm font-semibold py-3 px-6 rounded-xl flex items-center shadow-lg">
                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Ubah Foto
                                </label>
                                <input type="file" name="url_foto" id="url_foto" class="hidden" accept="image/jpeg,image/png" onchange="previewImage(event)">
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Max 5MB (JPG, PNG)
                                </p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 card-hover">
                            <h4 class="text-lg font-bold text-gray-800 mb-5 section-header flex items-center">
                                <i class="fas fa-chart-line text-teal-600 mr-2"></i> Hasil Tes:
                            </h4>
                            <div class="space-y-4">
                                <div class="stat-card p-4 rounded-xl text-white shadow-lg">
                                    <p class="text-sm font-medium opacity-90 mb-1">Gaya Belajar</p>
                                    <p class="font-bold text-lg"><?php echo htmlspecialchars($gaya_belajar); ?></p>
                                </div>
                                <div class="stat-card p-4 rounded-xl text-white shadow-lg tooltip-container">
                                    <p class="text-sm font-medium opacity-90 mb-1 flex items-center">
                                        Kecerdasan Majemuk
                                    </p>
                                    <p class="font-bold text-lg"><?php echo htmlspecialchars($hasil_tes_kemampuan_calculated); ?></p>
                                    
                                    <div class="tooltip">
                                        <h5 class="font-bold mb-3 text-sm">📊 Detail Skor Kecerdasan</h5>
                                        <div class="space-y-2">
                                        <?php 
                                            foreach ($skor_kecerdasan as $kode => $skor) {
                                                $nama = $map_kecerdasan[$kode] ?? 'Tidak Dikenal';
                                                $persen = $skor > 0 ? ($skor / max($skor_kecerdasan) * 100) : 0;
                                                echo "<div class='flex justify-between items-center text-xs'>";
                                                echo "<span class='font-medium'>$nama</span>";
                                                echo "<span class='font-bold text-teal-300'>$skor</span>";
                                                echo "</div>";
                                                echo "<div class='w-full bg-white bg-opacity-20 rounded-full h-1.5'>";
                                                echo "<div class='bg-teal-300 h-1.5 rounded-full' style='width: {$persen}%'></div>";
                                                echo "</div>";
                                            }
                                        ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-3">

                        <div class="hidden md:flex bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700" onclick="changeTab('data-pribadi')">
                                <i class="fas fa-user-circle mr-2"></i> Data Pribadi
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700" onclick="changeTab('riwayat-pendidikan')">
                                <i class="fas fa-graduation-cap mr-2"></i> Riwayat
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700" onclick="changeTab('data-orang-tua')">
                                <i class="fas fa-users mr-2"></i> Orang Tua
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700" onclick="changeTab('data-pendukung')">
                                <i class="fas fa-home mr-2"></i> Pendukung
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700" onclick="changeTab('profil-psikologis')">
                                <i class="fas fa-brain mr-2"></i> Psikologis
                            </button>
                        </div>
                        <div class="md:hidden mb-6 overflow-x-auto mobile-tabs bg-white border border-gray-100 shadow-xl rounded-2xl p-2">
                            <div class="inline-flex space-x-2">
                                <button type="button" class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700" onclick="changeTab('data-pribadi')">
                                    <i class="fas fa-user-circle mr-1"></i> Pribadi
                                </button>
                                <button type="button" class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700" onclick="changeTab('riwayat-pendidikan')">
                                    <i class="fas fa-graduation-cap mr-1"></i> Riwayat
                                </button>
                                <button type="button" class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700" onclick="changeTab('data-orang-tua')">
                                    <i class="fas fa-users mr-1"></i> Orang Tua
                                </button>
                                <button type="button" class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700" onclick="changeTab('data-pendukung')">
                                    <i class="fas fa-home mr-1"></i> Pendukung
                                </button>
                                <button type="button" class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700" onclick="changeTab('profil-psikologis')">
                                    <i class="fas fa-brain mr-1"></i> Psikologis
                                </button>
                            </div>
                        </div>
                        <div id="data-pribadi" class="tab-content bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-8">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-teal-100 section-header">
                                <i class="fas fa-user-circle text-teal-600 mr-3"></i> Data Pribadi
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-id-card text-teal-600 mr-2"></i>Nama Lengkap
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['nama'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium" readonly>
                                </div>
                                
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-hashtag text-teal-600 mr-2"></i>NIS
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['nis'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium" readonly>
                                </div>
                                
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-teal-600 mr-2"></i>Kelas / Jurusan
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['kelas'] . " / " . $siswa['jurusan']); ?>" class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium" readonly>
                                </div>
                                
                                <!-- <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt text-teal-600 mr-2"></i>Tahun Ajaran
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['tahun_ajaran'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium" readonly>
                                </div> -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user-tag text-teal-600 mr-2"></i>Nama Panggilan
                                    </label>
                                    <input type="text" name="nama_panggilan" value="<?php echo htmlspecialchars($siswa['nama_panggilan'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Contoh: Budi">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-venus-mars text-teal-600 mr-2"></i>Jenis Kelamin
                                    </label>
                                    <select name="jenis_kelamin" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih --</option>
                                        <option value="L" <?php echo ($siswa['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo ($siswa['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-map-marker-alt text-teal-600 mr-2"></i>Tempat Lahir
                                    </label>
                                    <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($siswa['tempat_lahir'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Banjarmasin">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-birthday-cake text-teal-600 mr-2"></i>Tanggal Lahir
                                    </label>
                                    <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-pray text-teal-600 mr-2"></i>Agama
                                    </label>
                                    <select name="agama" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($daftar_agama as $agama): ?>
                                        <option value="<?php echo $agama; ?>" <?php echo ($siswa['agama'] == $agama) ? 'selected' : ''; ?>><?php echo $agama; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-ruler-vertical text-teal-600 mr-2"></i>Tinggi Badan (cm)
                                    </label>
                                    <input type="number" name="tinggi_badan" value="<?php echo htmlspecialchars($siswa['tinggi_badan'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="165">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-weight text-teal-600 mr-2"></i>Berat Badan (kg)
                                    </label>
                                    <input type="number" name="berat_badan" value="<?php echo htmlspecialchars($siswa['berat_badan'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="55">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-flag text-teal-600 mr-2"></i>Suku
                                    </label>
                                    <input type="text" name="suku" value="<?php echo htmlspecialchars($siswa['suku'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Banjar">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-child text-teal-600 mr-2"></i>Anak Ke-
                                    </label>
                                    <input type="text" name="anak_ke" value="<?php echo htmlspecialchars($siswa['anak_ke'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="1/3">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-notes-medical text-teal-600 mr-2"></i>Riwayat Penyakit
                                    </label>
                                    <textarea name="riwayat_penyakit" rows="2" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Alergi, penyakit kronis, dll"><?php echo htmlspecialchars($siswa['riwayat_penyakit'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-map-marked-alt text-teal-600 mr-2"></i>Alamat Lengkap
                                    </label>
                                    <textarea name="alamat_lengkap" rows="3" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Alamat lengkap saat ini"><?php echo htmlspecialchars($siswa['alamat_lengkap'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <div class="form-divider">
                                        <span><i class="fas fa-phone mr-2"></i>Informasi Kontak</span>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-mobile-alt text-teal-600 mr-2"></i>Nomor HP Siswa
                                    </label>
                                    <input type="tel" name="no_telp" value="<?php echo htmlspecialchars($siswa['no_telp'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="08123456789">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope text-teal-600 mr-2"></i>Email Siswa
                                    </label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="nama@mail.com">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fab fa-instagram text-teal-600 mr-2"></i>Instagram
                                    </label>
                                    <input type="text" name="instagram" value="<?php echo htmlspecialchars($siswa['instagram'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="@username">
                                </div>
                            </div>
                        </div>
                        <div id="riwayat-pendidikan" class="tab-content bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-teal-100 section-header">
                                <i class="fas fa-graduation-cap text-teal-600 mr-3"></i> Riwayat Pendidikan & Prestasi
                            </h3>
                            
                            <div class="space-y-6">
                                <div class="bg-gradient-to-r from-teal-50 to-cyan-50 p-5 rounded-xl border-l-4 border-teal-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-teal-600 mr-2"></i>Asal SD/MI
                                    </label>
                                    <input type="text" name="riwayat_sd_mi" value="<?php echo htmlspecialchars($siswa['riwayat_sd_mi'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm bg-white" placeholder="...">
                                </div>
                                
                                <div class="bg-gradient-to-r from-cyan-50 to-blue-50 p-5 rounded-xl border-l-4 border-cyan-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-cyan-600 mr-2"></i>Asal SMP/MTs
                                    </label>
                                    <input type="text" name="riwayat_smp_mts" value="<?php echo htmlspecialchars($siswa['riwayat_smp_mts'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm bg-white" placeholder="...">
                                </div>
                                
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border-l-4 border-blue-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-blue-600 mr-2"></i>Asal SMK
                                    </label>
                                    <input type="text" name="riwayat_sma_smk_ma" value="<?php echo htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm bg-white" placeholder="...">
                                </div>
                                
                                <div class="form-divider">
                                    <span><i class="fas fa-trophy mr-2"></i>Prestasi & Organisasi</span>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-medal text-yellow-600 mr-2"></i>Prestasi & Pengalaman
                                    </label>
                                    <textarea name="prestasi_pengalaman" rows="5" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Juara lomba, pengalaman magang, sertifikat, dll"><?php echo htmlspecialchars($siswa['prestasi_pengalaman'] ?? ''); ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-users-cog text-purple-600 mr-2"></i>Riwayat Organisasi
                                    </label>
                                    <textarea name="organisasi" rows="3" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="OSIS, Pramuka, komunitas, dll"><?php echo htmlspecialchars($siswa['organisasi'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div id="data-orang-tua" class="tab-content bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-teal-100 section-header">
                                <i class="fas fa-users text-teal-600 mr-3"></i> Data Orang Tua/Wali
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-2xl border-2 border-blue-200 shadow-md">
                                    <h4 class="font-bold text-blue-800 mb-4 flex items-center text-lg">
                                        <i class="fas fa-male text-2xl mr-3"></i> Data Ayah
                                    </h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Ayah</label>
                                            <input type="text" name="nama_ayah" value="<?php echo htmlspecialchars($siswa['nama_ayah'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-3 text-sm bg-white" placeholder="Nama Lengkap Ayah">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pekerjaan</label>
                                            <input type="text" name="pekerjaan_ayah" value="<?php echo htmlspecialchars($siswa['pekerjaan_ayah'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-3 text-sm bg-white" placeholder="Wiraswasta, PNS, dll">
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-pink-50 to-rose-50 p-6 rounded-2xl border-2 border-pink-200 shadow-md">
                                    <h4 class="font-bold text-pink-800 mb-4 flex items-center text-lg">
                                        <i class="fas fa-female text-2xl mr-3"></i> Data Ibu
                                    </h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Ibu</label>
                                            <input type="text" name="nama_ibu" value="<?php echo htmlspecialchars($siswa['nama_ibu'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-pink-500 focus:ring focus:ring-pink-200 p-3 text-sm bg-white" placeholder="Nama Lengkap Ibu">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Pekerjaan</label>
                                            <input type="text" name="pekerjaan_ibu" value="<?php echo htmlspecialchars($siswa['pekerjaan_ibu'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-pink-500 focus:ring focus:ring-pink-200 p-3 text-sm bg-white" placeholder="IRT, Guru, dll">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="md:col-span-2 mt-4">
                                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-2xl border-2 border-green-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-phone-volume text-green-600 mr-2"></i>Nomor HP Orang Tua/Wali
                                        </label>
                                        <input type="tel" name="no_hp_ortu" value="<?php echo htmlspecialchars($siswa['no_hp_ortu'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-3 text-sm bg-white" placeholder="0812xxxxxxxx">
                                        <p class="text-xs text-gray-600 mt-2"><i class="fas fa-info-circle mr-1"></i>Nomor yang aktif dan dapat dihubungi</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="data-pendukung" class="tab-content bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-teal-100 section-header">
                                <i class="fas fa-home text-teal-600 mr-3"></i> Data Pendukung Belajar
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-house-user text-teal-600 mr-2"></i>Status Tempat Tinggal
                                    </label>
                                    <select name="status_tempat_tinggal" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Status --</option>
                                        <?php foreach ($daftar_status_tinggal as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($siswa['status_tempat_tinggal'] == $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-route text-teal-600 mr-2"></i>Jarak ke Sekolah
                                    </label>
                                    <select name="jarak_ke_sekolah" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Jarak --</option>
                                        <?php foreach ($daftar_jarak as $jarak): ?>
                                        <option value="<?php echo $jarak; ?>" <?php echo ($siswa['jarak_ke_sekolah'] == $jarak) ? 'selected' : ''; ?>><?php echo $jarak; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-bus text-teal-600 mr-2"></i>Transportasi
                                    </label>
                                    <select name="transportasi_ke_sekolah" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Transportasi --</option>
                                        <?php foreach ($daftar_transportasi as $transportasi): ?>
                                        <option value="<?php echo $transportasi; ?>" <?php echo ($siswa['transportasi_ke_sekolah'] == $transportasi) ? 'selected' : ''; ?>><?php echo $transportasi; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-laptop text-teal-600 mr-2"></i>Kepemilikan Gadget
                                    </label>
                                    <select name="memiliki_hp_laptop" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($daftar_kepemilikan_gadget as $gadget): ?>
                                        <option value="<?php echo $gadget; ?>" <?php echo ($siswa['memiliki_hp_laptop'] == $gadget) ? 'selected' : ''; ?>><?php echo $gadget; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-wifi text-teal-600 mr-2"></i>Fasilitas Internet
                                    </label>
                                    <select name="fasilitas_internet" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Akses --</option>
                                        <?php foreach ($daftar_fasilitas_internet as $internet): ?>
                                        <option value="<?php echo $internet; ?>" <?php echo ($siswa['fasilitas_internet'] == $internet) ? 'selected' : ''; ?>><?php echo $internet; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-chair text-teal-600 mr-2"></i>Fasilitas Belajar
                                    </label>
                                    <select name="fasilitas_belajar_dirumah" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih Fasilitas --</option>
                                        <?php foreach ($daftar_fasilitas_belajar as $belajar): ?>
                                        <option value="<?php echo $belajar; ?>" <?php echo ($siswa['fasilitas_belajar_dirumah'] == $belajar) ? 'selected' : ''; ?>><?php echo $belajar; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-book text-teal-600 mr-2"></i>Kepemilikan Buku Pelajaran
                                    </label>
                                    <select name="buku_pelajaran_dimiliki" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($daftar_buku_pelajaran as $buku): ?>
                                        <option value="<?php echo $buku; ?>" <?php echo ($siswa['buku_pelajaran_dimiliki'] == $buku) ? 'selected' : ''; ?>><?php echo $buku; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <div class="form-divider">
                                        <span><i class="fas fa-language mr-2"></i>Kemampuan Bahasa</span>
                                    </div>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-comments text-teal-600 mr-2"></i>Bahasa Sehari-hari
                                    </label>
                                    <input type="text" name="bahasa_sehari_hari" value="<?php echo htmlspecialchars($siswa['bahasa_sehari_hari'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Banjar, Indonesia">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-globe text-teal-600 mr-2"></i>Bahasa Asing yang Dikuasai
                                    </label>
                                    <textarea name="bahasa_asing_dikuasai" rows="2" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Inggris (Menengah), Jepang (Dasar)"><?php echo htmlspecialchars($siswa['bahasa_asing_dikuasai'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div id="profil-psikologis" class="tab-content bg-white p-8 rounded-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-teal-100 section-header">
                                <i class="fas fa-brain text-teal-600 mr-3"></i> Profil Psikologis & Minat
                            </h3>
                            
                            <div class="space-y-6">
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-2xl border-l-4 border-purple-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-star text-purple-600 mr-2"></i>Cita-cita
                                    </label>
                                    <input type="text" name="cita_cita" value="<?php echo htmlspecialchars($siswa['cita_cita'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 p-3 text-sm bg-white" placeholder="Programmer, Designer, dll">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-heart text-red-600 mr-2"></i>Hobi & Kegemaran
                                    </label>
                                    <textarea name="hobi_kegemaran" rows="3" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Gaming, membaca, olahraga, dll"><?php echo htmlspecialchars($siswa['hobi_kegemaran'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-green-50 p-4 rounded-xl border-2 border-green-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-thumbs-up text-green-600 mr-2"></i>Pelajaran Disenangi
                                        </label>
                                        <input type="text" name="pelajaran_disenangi" value="<?php echo htmlspecialchars($siswa['pelajaran_disenangi'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-3 text-sm bg-white" placeholder="Matematika, Fisika">
                                    </div>
                                    
                                    <div class="bg-red-50 p-4 rounded-xl border-2 border-red-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-thumbs-down text-red-600 mr-2"></i>Pelajaran Kurang Disenangi
                                        </label>
                                        <input type="text" name="pelajaran_tdk_disenangi" value="<?php echo htmlspecialchars($siswa['pelajaran_tdk_disenangi'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 p-3 text-sm bg-white" placeholder="Sejarah, Seni">
                                    </div>
                                </div>
                                
                                <div class="form-divider">
                                    <span><i class="fas fa-user-circle mr-2"></i>Tentang Diri</span>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-pen-fancy text-teal-600 mr-2"></i>Deskripsi Singkat
                                    </label>
                                    <textarea name="tentang_saya_singkat" rows="4" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-teal-500 focus:ring focus:ring-teal-200 p-3 text-sm" placeholder="Ceritakan tentang diri Anda dalam beberapa kalimat..."><?php echo htmlspecialchars($siswa['tentang_saya_singkat'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="bg-gradient-to-r from-emerald-50 to-green-50 p-6 rounded-xl border-2 border-emerald-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-plus-circle text-emerald-600 mr-2"></i>Kelebihan Diri
                                    </label>
                                    <textarea name="kelebihan_diri" rows="3" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-emerald-500 focus:ring focus:ring-emerald-200 p-3 text-sm bg-white" placeholder="Teliti, kerja sama tim, cepat belajar"><?php echo htmlspecialchars($siswa['kelebihan_diri'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-6 rounded-xl border-2 border-orange-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-exclamation-circle text-orange-600 mr-2"></i>Kekurangan Diri
                                    </label>
                                    <textarea name="kekurangan_diri" rows="3" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 p-3 text-sm bg-white" placeholder="Kurang percaya diri, sering menunda"><?php echo htmlspecialchars($siswa['kekurangan_diri'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border-2 border-blue-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user-friends text-blue-600 mr-2"></i>Tempat Curhat
                                    </label>
                                    <input type="text" name="tempat_curhat" value="<?php echo htmlspecialchars($siswa['tempat_curhat'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-3 text-sm bg-white" placeholder="Orang tua, sahabat, kakak">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-10 pt-8 border-t-2 border-gray-200 bg-gradient-to-r from-teal-50 to-cyan-50 p-8 rounded-2xl shadow-xl">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-teal-100 p-3 rounded-xl">
                                        <i class="fas fa-info-circle text-teal-600 text-2xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg mb-1">Simpan Perubahan</h4>
                                        <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar sebelum menyimpan</p>
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row items-center gap-4 mt-10 no-print">
    <button type="submit" class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1.5rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
        <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
        <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
    </button>

    <a href="#" id="btnExportCV" class="w-full md:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-white border-2 border-gray-100 text-gray-700 rounded-[1.5rem] hover:border-blue-200 hover:bg-blue-50 transition-all font-black text-sm shadow-sm group">
        <i class="fas fa-print text-blue-600 group-hover:scale-110 transition-transform text-lg"></i>
        <span class="text-sm uppercase tracking-widest">Cetak CV</span>
    </a>
</div>
<script>
document.getElementById('btnExportCV').addEventListener('click', function(e) {
    e.preventDefault();
    const idSiswa = "<?php echo $id_siswa; ?>";
    const btn = this;
    const originalContent = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    btn.classList.add('opacity-50', 'pointer-events-none');

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = 'cv_template.php?id_siswa=' + idSiswa;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        btn.innerHTML = originalContent;
        btn.classList.remove('opacity-50', 'pointer-events-none');
        
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        
        setTimeout(() => { document.body.removeChild(iframe); }, 1000);
    };
});
</script>
</div>
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
            </form>
        </div>
    </div>
    <div class="fixed bottom-6 right-6 md:hidden z-40">
        <button type="button" onclick="document.querySelector('form').scrollIntoView({behavior: 'smooth', block: 'end'})" class="bg-gradient-to-r from-teal-500 to-cyan-500 text-white p-4 rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 hover:scale-110 icon-bounce">
            <i class="fas fa-arrow-down text-xl"></i>
        </button>
    </div>
    <button id="backToTop" class="hidden fixed bottom-6 left-6 bg-gray-800 text-white p-4 rounded-full shadow-2xl hover:bg-gray-900 transition-all duration-300 hover:scale-110 z-40">
        <i class="fas fa-arrow-up text-xl"></i>
    </button>

    <script>

        const backToTopBtn = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.remove('hidden');
            } else {
                backToTopBtn.classList.add('hidden');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        const inputs = document.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() !== '' && !this.readOnly) {
                    this.classList.add('border-green-400');
                    this.classList.remove('border-gray-300');
                } else if (!this.readOnly) {
                    this.classList.remove('border-green-400');
                    this.classList.add('border-gray-300');
                }
            });
        });
        const mobileTabsContainer = document.querySelector('.mobile-tabs');
        if (mobileTabsContainer) {
            let isDown = false;
            let startX;
            let scrollLeft;

            mobileTabsContainer.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - mobileTabsContainer.offsetLeft;
                scrollLeft = mobileTabsContainer.scrollLeft;
            });

            mobileTabsContainer.addEventListener('mouseleave', () => {
                isDown = false;
            });

            mobileTabsContainer.addEventListener('mouseup', () => {
                isDown = false;
            });

            mobileTabsContainer.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - mobileTabsContainer.offsetLeft;
                const walk = (x - startX) * 2;
                mobileTabsContainer.scrollLeft = scrollLeft - walk;
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert-animate');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
        const form = document.querySelector('form');
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;

        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menyimpan...</span>
            `;
        });
        if (window.innerWidth < 768) {
            const tooltipContainers = document.querySelectorAll('.tooltip-container');
            tooltipContainers.forEach(container => {
                container.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const tooltip = this.querySelector('.tooltip');
                    const allTooltips = document.querySelectorAll('.tooltip');
                    
                    allTooltips.forEach(t => {
                        if (t !== tooltip) {
                            t.style.visibility = 'hidden';
                            t.style.opacity = '0';
                        }
                    });
                    
                    if (tooltip.style.visibility === 'visible') {
                        tooltip.style.visibility = 'hidden';
                        tooltip.style.opacity = '0';
                    } else {
                        tooltip.style.visibility = 'visible';
                        tooltip.style.opacity = '1';
                    }
                });
            });

            document.addEventListener('click', () => {
                const allTooltips = document.querySelectorAll('.tooltip');
                allTooltips.forEach(tooltip => {
                    tooltip.style.visibility = 'hidden';
                    tooltip.style.opacity = '0';
                });
            });
        }
        function updateProgressIndicator() {
            const allInputs = document.querySelectorAll('input:not([readonly]), textarea, select');
            let filledInputs = 0;
            
            allInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    filledInputs++;
                }
            });
            
            const progress = Math.round((filledInputs / allInputs.length) * 100);

            console.log(`Form completion: ${progress}%`);
        }

        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('change', updateProgressIndicator);
        });

        updateProgressIndicator();

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card-hover').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>