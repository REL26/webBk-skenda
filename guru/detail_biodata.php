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
    $nama_ayah           = mysqli_real_escape_string($koneksi, $_POST['nama_ayah'] ?? '');
$tempat_lahir_ayah   = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir_ayah'] ?? '');
$tanggal_lahir_ayah  = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir_ayah'] ?? '');
$pekerjaan_ayah      = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ayah'] ?? '');
$penghasilan_ayah = preg_replace('/[^0-9]/', '', $_POST['penghasilan_ayah'] ?? '');
$penghasilan_ayah = mysqli_real_escape_string($koneksi, $penghasilan_ayah);
$no_hp_ayah          = mysqli_real_escape_string($koneksi, $_POST['no_hp_ayah'] ?? '');

$nama_ibu            = mysqli_real_escape_string($koneksi, $_POST['nama_ibu'] ?? '');
$tempat_lahir_ibu    = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir_ibu'] ?? '');
$tanggal_lahir_ibu   = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir_ibu'] ?? '');
$pekerjaan_ibu       = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ibu'] ?? '');
$penghasilan_ibu = preg_replace('/[^0-9]/', '', $_POST['penghasilan_ibu'] ?? '');
$penghasilan_ibu = mysqli_real_escape_string($koneksi, $penghasilan_ibu);
$no_hp_ibu           = mysqli_real_escape_string($koneksi, $_POST['no_hp_ibu'] ?? '');
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
               nama_ayah              = '$nama_ayah',
tempat_lahir_ayah      = '$tempat_lahir_ayah',
tanggal_lahir_ayah     = " . (empty($tanggal_lahir_ayah) ? "NULL" : "'$tanggal_lahir_ayah'") . ",
pekerjaan_ayah         = '$pekerjaan_ayah',
penghasilan_ayah       = '$penghasilan_ayah',
no_hp_ayah             = '$no_hp_ayah',

nama_ibu               = '$nama_ibu',
tempat_lahir_ibu       = '$tempat_lahir_ibu',
tanggal_lahir_ibu      = " . (empty($tanggal_lahir_ibu) ? "NULL" : "'$tanggal_lahir_ibu'") . ",
pekerjaan_ibu          = '$pekerjaan_ibu',
penghasilan_ibu        = '$penghasilan_ibu',
no_hp_ibu              = '$no_hp_ibu',
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
    <title>Manajemen Profil Siswa | Data
        <?php echo htmlspecialchars($siswa['nama']); ?>
    </title>
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
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-200: #E5E7EB;
            --success: #4C8E89;
            --warning: #5FA8A1;
            --danger: #9B2C2C;
        }

        .primary-color {
            color: #0F3A3A;
        }

        .primary-bg {
            background-color: #123E44;
        }

        .secondary-bg {
            background-color: #E6EEF0;
        }

        .btn-print-cv {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 60%, var(--accent-dark) 100%);
            color: var(--white);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-print-cv::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.25) 45%, transparent 70%);
            transform: translateX(-120%);
            transition: transform .6s ease;
        }

        .btn-print-cv:hover::before {
            transform: translateX(120%);
        }

        .btn-print-cv:hover {
            box-shadow: 0 10px 25px -5px rgba(15, 58, 58, 0.45);
            transform: translateY(-2px);
        }

        .btn-print-cv .btn-badge {
            background: var(--accent);
            color: var(--primary-dark);
        }

        .btn-print-data {
            background: linear-gradient(135deg, #2f6fa3 0%, #1a4d73 60%, #123a57 100%);
            color: var(--white);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-print-data::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.25) 45%, transparent 70%);
            transform: translateX(-120%);
            transition: transform .6s ease;
        }

        .btn-print-data:hover::before {
            transform: translateX(120%);
        }

        .btn-print-data:hover {
            box-shadow: 0 10px 25px -5px rgba(26, 77, 115, 0.45);
            transform: translateY(-2px);
        }

        .btn-print-data .btn-badge {
            background: #4a90c4;
            color: #ffffff;
        }

        .btn-back {
            background-color: var(--gray-50);
            color: var(--primary);
            border: 1.5px solid var(--gray-200);
        }

        .btn-back:hover {
            background-color: var(--secondary-bg, #E6EEF0);
            border-color: var(--accent);
            color: var(--primary-dark);
        }


        body {
            background: #F8FAFC;
            min-height: 100vh;
        }


        header {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, .95);
            animation: slideDown .5s ease-out;
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
            transition: .3s ease;
        }


        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, .08);
        }


        .photo-container {
            position: relative;
            animation: fadeInScale .5s ease-out;
        }


        .photo-container::before {
            content: '';
            position: absolute;
            inset: -3px;
            background: var(--accent);
            border-radius: 1rem;
            opacity: .15;
            z-index: -1;
        }


        @keyframes fadeInScale {

            from {
                opacity: 0;
                transform: scale(.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }

        }


        .stat-card {

            background: #FFFFFF;
            border: 1px solid var(--gray-200);
            border-radius: 1rem;
            animation: slideUp .5s ease-out backwards;

        }


        .stat-card:nth-child(1) {
            animation-delay: .1s;
        }


        .stat-card:nth-child(2) {
            animation-delay: .2s;
        }



        @keyframes slideUp {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }



        .nav-item {

            position: relative;
            padding: 12px 20px;
            transition: .3s ease;
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
            transition: .3s ease;
            transform: translateX(-50%);

        }



        .nav-item:hover {

            background: var(--gray-100);
            color: var(--primary);

        }



        .nav-item.active {

            background: #E8F3F1;
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

            transition: .3s ease;
            white-space: nowrap;

        }



        .mobile-tab-item.active {

            background: var(--accent);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(63, 124, 120, .25);

        }



        input:focus,
        textarea:focus,
        select:focus {

            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(63, 124, 120, .12);
            transform: translateY(-1px);

        }



        input,
        textarea,
        select {

            transition: .3s ease;

        }



        .btn-primary {

            position: relative;
            overflow: hidden;
            background: var(--accent);
            transition: .3s ease;

        }



        .btn-primary:hover {

            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(63, 124, 120, .25);

        }



        .btn-primary:active {

            transform: translateY(0);

        }



        .btn-danger {

            transition: .3s ease;

        }



        .btn-danger:hover {

            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, .2);

        }



        .tooltip-container {

            position: relative;
            cursor: pointer;

        }



        .tooltip {

            visibility: hidden;
            width: 280px;
            background: var(--primary-dark);
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
            transition: .3s ease;
            font-size: .75rem;
            line-height: 1.5;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .2);

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
            border-color: var(--primary-dark) transparent transparent transparent;

        }



        .tab-content {

            animation: fadeIn .4s ease-out;

        }



        @keyframes fadeIn {

            from {

                opacity: 0;
                transform: translateY(15px);

            }

            to {

                opacity: 1;
                transform: translateY(0);

            }

        }



        .alert-animate {

            animation: slideInRight .5s ease-out;

        }



        @keyframes slideInRight {

            from {

                opacity: 0;
                transform: translateX(50px);

            }

            to {

                opacity: 1;
                transform: translateX(0);

            }

        }



        .skeleton {

            background: #E2E8F0;
            animation: loading 1.5s infinite;

        }



        @keyframes loading {

            0% {
                opacity: .6;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: .6;
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
            background: var(--accent);
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
            background: var(--gray-200);

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
            transition: .3s ease;

        }



        @media(max-width:768px) {

            .sticky-sidebar {

                position: relative;
                top: 0;

            }


            .nav-item {

                padding: 10px 16px;

            }

        }



        .icon-bounce {

            animation: none;

        }



        .gradient-text {

            color: var(--primary);

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

            background: var(--accent);
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
                reader.onload = function (e) {
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

<body class="min-h-screen" style="background: linear-gradient(135deg, #F9FAFB 0%, #E6EEF0 100%);">
    <header class="top-0 left-0 w-full shadow-lg z-30 flex items-center justify-between h-16 px-6 sticky">
        <a href="#" class="flex items-center space-x-3 group">
            <div class="relative">
                <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo"
                    class="h-10 w-10 transition-transform group-hover:scale-110">
                <div
                    class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-20 transition-opacity"
                    style="background: linear-gradient(90deg, #5FA8A1, #123E44);">
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold gradient-text block">Detail Siswa</span>
            </div>
        </a>
        <a href="javascript:void(0);" onclick="goBack()"
            class="btn-back inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 hover:-translate-x-1 shadow-sm">
            <i class="fas fa-arrow-left text-sm"></i>
            <span>Kembali ke Daftar Siswa</span>
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

            <div class="mb-8 rounded-3xl bg-white/80 border border-slate-200 p-6 shadow-sm backdrop-blur-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="space-y-3">
                        <div>
                            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900">
                                <i class="fas fa-user-graduate text-indigo-600 mr-3"></i>
                                <?php echo htmlspecialchars($siswa['nama']); ?>
                            </h2>
                            <p class="text-sm text-slate-500 mt-2"><?php echo htmlspecialchars($siswa['kelas'] . " - " . $siswa['jurusan']); ?></p>
                        </div>
                        <p class="text-gray-600 text-base">
                            <span class="font-semibold">NIS:</span>
                            <?php echo htmlspecialchars($siswa['nis']); ?>
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <a href="#" id="btnExportCV" class="btn-print-cv group inline-flex items-center gap-4 px-6 py-3.5 rounded-2xl transition-all duration-300">
                            <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/15">
                                <i class="fas fa-file-pdf text-xl"></i>
                            </div>
                            <div class="text-left leading-tight">
                                <span class="block uppercase tracking-widest font-extrabold text-sm">Cetak CV Siswa</span>
                                <span class="block text-[11px] font-medium text-white/75 mt-0.5">Unduh profil lengkap dalam 1 PDF</span>
                            </div>
                            <span class="btn-badge ml-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
                                <i class="fas fa-print"></i>
                            </span>
                        </a>
                        <a href="#" id="btnExportDataLengkap" class="btn-print-data group inline-flex items-center gap-4 px-6 py-3.5 rounded-2xl transition-all duration-300">
                            <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/15">
                                <i class="fas fa-file-lines text-xl"></i>
                            </div>
                            <div class="text-left leading-tight">
                                <span class="block uppercase tracking-widest font-extrabold text-sm">Cetak Data Lengkap</span>
                                <span class="block text-[11px] font-medium text-white/75 mt-0.5">Seluruh data dalam 1 PDF</span>
                            </div>
                            <span class="btn-badge ml-1 px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
                                <i class="fas fa-print"></i>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($pesan_sukses): ?>
            <div class="alert-animate bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 p-5 mb-6 rounded-xl shadow-lg"
                role="alert">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-2xl text-green-500 mr-4 mt-1"></i>
                    <div>
                        <p class="font-bold text-lg">Berhasil!</p>
                        <p class="text-sm mt-1">
                            <?php echo $pesan_sukses; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($pesan_error): ?>
            <div class="alert-animate bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 p-5 mb-6 rounded-xl shadow-lg"
                role="alert">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-500 mr-4 mt-1"></i>
                    <div>
                        <p class="font-bold text-lg">Terjadi Kesalahan!</p>
                        <p class="text-sm mt-1">
                            <?php echo $pesan_error; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data"
                action="detail_biodata.php?id_siswa=<?php echo $id_siswa; ?>">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                    <div class="lg:col-span-1 space-y-6 sticky-sidebar">

                        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 card-hover">
                            <h4 class="text-lg font-bold text-gray-800 mb-5 section-header flex items-center">
                                <i class="fas fa-camera text-indigo-600 mr-2"></i> Foto Profil
                            </h4>
                            <div class="flex flex-col items-center">
                                <div class="photo-container mb-5">
                                    <img src="<?php echo $url_foto_display; ?>" alt="Foto Siswa"
                                        class="w-48 h-48 md:w-56 md:h-56 object-cover rounded-2xl shadow-2xl border-4 border-white ring-4 ring-indigo-100"
                                        id="previewFoto">
                                </div>
                                <label for="url_foto"
                                    class="cursor-pointer btn-primary text-white text-sm font-semibold py-3 px-6 rounded-xl flex items-center shadow-lg">
                                    <i class="fas fa-cloud-upload-alt mr-2"></i> Ubah Foto
                                </label>
                                <input type="file" name="url_foto" id="url_foto" class="hidden"
                                    accept="image/jpeg,image/png" onchange="previewImage(event)">
                                <p class="text-xs text-gray-500 mt-3 text-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Max 5MB (JPG, PNG)
                                </p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 card-hover">
    <h4 class="text-lg font-bold text-gray-800 mb-5 section-header flex items-center">
        <i class="fas fa-chart-line text-indigo-600 mr-2"></i> Hasil Tes
    </h4>

    <div class="space-y-4">

        <!-- Gaya Belajar -->
        <div class="relative overflow-hidden bg-gradient-to-r from-indigo-500 to-blue-500 p-5 rounded-2xl text-white shadow-lg">
            <i class="fas fa-eye absolute -right-3 -bottom-3 text-7xl text-white opacity-10"></i>
            <p class="text-xs font-semibold uppercase tracking-wider opacity-80 mb-1">Gaya Belajar</p>
            <p class="font-bold text-2xl tracking-tight relative z-10">
                <?php echo htmlspecialchars($gaya_belajar); ?>
            </p>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-lg border border-gray-200">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Kecerdasan Majemuk</p>

            <?php
            $icon_kecerdasan = [
                'Linguistik'      => 'fa-book-open',
                'Logis-Matematis' => 'fa-square-root-alt',
                'Visual-Spasial'  => 'fa-drafting-compass',
                'Kinestetik'      => 'fa-running',
                'Musikal'         => 'fa-music',
                'Interpersonal'   => 'fa-people-arrows',
                'Intrapersonal'   => 'fa-brain',
                'Naturalis'       => 'fa-leaf',
            ];
            $daftar_kecerdasan = array_map('trim', explode('&', $hasil_tes_kemampuan_calculated));
            ?>

            <div class="flex flex-wrap gap-2">
                <?php foreach ($daftar_kecerdasan as $k): ?>
                <span class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 border border-indigo-100 px-3 py-1.5 rounded-full text-sm font-semibold">
                    <i class="fas <?php echo $icon_kecerdasan[$k] ?? 'fa-star'; ?> text-indigo-500 text-xs"></i>
                    <?php echo htmlspecialchars($k); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>
                    </div>
                    <div class="lg:col-span-3">

                        <div
                            class="hidden md:flex bg-white rounded-t-2xl shadow-xl border border-gray-100 overflow-hidden">
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700"
                                onclick="changeTab('data-pribadi')">
                                <i class="fas fa-user-circle mr-2"></i> Data Pribadi
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700"
                                onclick="changeTab('riwayat-pendidikan')">
                                <i class="fas fa-graduation-cap mr-2"></i> Riwayat
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700"
                                onclick="changeTab('data-orang-tua')">
                                <i class="fas fa-users mr-2"></i> Orang Tua
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700"
                                onclick="changeTab('data-pendukung')">
                                <i class="fas fa-home mr-2"></i> Pendukung
                            </button>
                            <button type="button" class="nav-item flex-1 text-center text-sm font-medium text-gray-700"
                                onclick="changeTab('profil-psikologis')">
                                <i class="fas fa-brain mr-2"></i> Psikologis
                            </button>
                        </div>
                        <div
                            class="md:hidden mb-6 overflow-x-auto mobile-tabs bg-white border border-gray-100 shadow-xl rounded-t-2xl p-2">
                            <div class="inline-flex space-x-2">
                                <button type="button"
                                    class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700"
                                    onclick="changeTab('data-pribadi')">
                                    <i class="fas fa-user-circle mr-1"></i> Pribadi
                                </button>
                                <button type="button"
                                    class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700"
                                    onclick="changeTab('riwayat-pendidikan')">
                                    <i class="fas fa-graduation-cap mr-1"></i> Riwayat
                                </button>
                                <button type="button"
                                    class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700"
                                    onclick="changeTab('data-orang-tua')">
                                    <i class="fas fa-users mr-1"></i> Orang Tua
                                </button>
                                <button type="button"
                                    class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700"
                                    onclick="changeTab('data-pendukung')">
                                    <i class="fas fa-home mr-1"></i> Pendukung
                                </button>
                                <button type="button"
                                    class="mobile-tab-item py-2.5 px-5 rounded-xl text-xs font-medium text-gray-700"
                                    onclick="changeTab('profil-psikologis')">
                                    <i class="fas fa-brain mr-1"></i> Psikologis
                                </button>
                            </div>
                        </div>
                        <div id="data-pribadi"
                            class="tab-content bg-white p-8 rounded-b-2xl shadow-xl border border-gray-100 space-y-8">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-indigo-100 section-header">
                                <i class="fas fa-user-circle text-indigo-600 mr-3"></i> Data Pribadi
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-id-card text-indigo-600 mr-2"></i>Nama Lengkap
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['nama'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium"
                                        readonly>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-hashtag text-indigo-600 mr-2"></i>NIS
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['nis'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium"
                                        readonly>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-hashtag text-indigo-600 mr-2"></i>NISN
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['nisn'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium"
                                        readonly>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-indigo-600 mr-2"></i>Kelas / Jurusan
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['kelas'] . "
                                        / " . $siswa['jurusan']); ?>"
                                        class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium"
                                        readonly>
                                </div>

                                <!-- <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt text-indigo-600 mr-2"></i>Tahun Ajaran
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($siswa['tahun_ajaran'] ?? ''); ?>" class="w-full rounded-xl border-2 border-gray-200 shadow-sm p-3 text-sm bg-gradient-to-r from-gray-50 to-gray-100 cursor-not-allowed font-medium" readonly>
                                </div> -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user-tag text-indigo-600 mr-2"></i>Nama Panggilan
                                    </label>
                                    <input type="text" name="nama_panggilan"
                                        value="<?php echo htmlspecialchars($siswa['nama_panggilan'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Contoh: Budi">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-venus-mars text-indigo-600 mr-2"></i>Jenis Kelamin
                                    </label>
                                    <select name="jenis_kelamin"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm">
                                        <option value="">-- Pilih --</option>
                                        <option value="L" <?php echo ($siswa['jenis_kelamin']=='L' ) ? 'selected' : '' ;
                                            ?>>Laki-laki</option>
                                        <option value="P" <?php echo ($siswa['jenis_kelamin']=='P' ) ? 'selected' : '' ;
                                            ?>>Perempuan</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-map-marker-alt text-indigo-600 mr-2"></i>Tempat Lahir
                                    </label>
                                    <input type="text" name="tempat_lahir"
                                        value="<?php echo htmlspecialchars($siswa['tempat_lahir'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Banjarmasin">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-birthday-cake text-indigo-600 mr-2"></i>Tanggal Lahir
                                    </label>
                                    <input type="date" name="tanggal_lahir"
                                        value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-pray text-indigo-600 mr-2"></i>Agama
                                    </label>
                                    <select name="agama"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm">
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($daftar_agama as $agama): ?>
                                        <option value="<?php echo $agama; ?>" <?php echo ($siswa['agama']==$agama)
                                            ? 'selected' : '' ; ?>>
                                            <?php echo $agama; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-ruler-vertical text-indigo-600 mr-2"></i>Tinggi Badan (cm)
                                    </label>
                                    <input type="number" name="tinggi_badan"
                                        value="<?php echo htmlspecialchars($siswa['tinggi_badan'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="165">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-weight text-indigo-600 mr-2"></i>Berat Badan (kg)
                                    </label>
                                    <input type="number" name="berat_badan"
                                        value="<?php echo htmlspecialchars($siswa['berat_badan'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="55">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-flag text-indigo-600 mr-2"></i>Suku
                                    </label>
                                    <input type="text" name="suku"
                                        value="<?php echo htmlspecialchars($siswa['suku'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Banjar">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-child text-indigo-600 mr-2"></i>Anak Ke-
                                    </label>
                                    <input type="text" name="anak_ke"
                                        value="<?php echo htmlspecialchars($siswa['anak_ke'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="1/3">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-notes-medical text-indigo-600 mr-2"></i>Riwayat Penyakit
                                    </label>
                                    <textarea name="riwayat_penyakit" rows="2"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Alergi, penyakit kronis, dll"><?php echo htmlspecialchars($siswa['riwayat_penyakit'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-map-marked-alt text-indigo-600 mr-2"></i>Alamat Lengkap
                                    </label>
                                    <textarea name="alamat_lengkap" rows="3"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Alamat lengkap saat ini"><?php echo htmlspecialchars($siswa['alamat_lengkap'] ?? ''); ?></textarea>
                                </div>

                                <div class="md:col-span-2">
                                    <div class="form-divider">
                                        <span><i class="fas fa-phone mr-2"></i>Informasi Kontak</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-mobile-alt text-indigo-600 mr-2"></i>Nomor HP Siswa
                                    </label>
                                    <input type="tel" name="no_telp"
                                        value="<?php echo htmlspecialchars($siswa['no_telp'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="08123456789">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-envelope text-indigo-600 mr-2"></i>Email Siswa
                                    </label>
                                    <input type="email" name="email"
                                        value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="nama@mail.com">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fab fa-instagram text-indigo-600 mr-2"></i>Instagram
                                    </label>
                                    <input type="text" name="instagram"
                                        value="<?php echo htmlspecialchars($siswa['instagram'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="@username">
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-col items-center gap-4 mt-10 no-print">
                                <button type="submit"
                                    class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
                                    <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
                                    <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
                                </button>
                                <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar sebelum
                                    menyimpan</p>
                            </div>
                        </div>
                        <div id="riwayat-pendidikan"
                            class="tab-content bg-white p-8 rounded-b-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-indigo-100 section-header">
                                <i class="fas fa-graduation-cap text-indigo-600 mr-3"></i> Riwayat Pendidikan & Prestasi
                            </h3>

                            <div class="space-y-6">
                                <div
                                    class="bg-gradient-to-r from-red-50 to-red-50 p-5 rounded-xl border-l-4 border-red-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-red-600 mr-2"></i>Asal SD/MI
                                    </label>
                                    <input type="text" name="riwayat_sd_mi"
                                        value="<?php echo htmlspecialchars($siswa['riwayat_sd_mi'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 p-3 text-sm bg-white"
                                        placeholder="...">
                                </div>

                                <div
                                    class="bg-gradient-to-r from-blue-50 to-blue-50 p-5 rounded-xl border-l-4 border-blue-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-blue-600 mr-2"></i>Asal SMP/MTs
                                    </label>
                                    <input type="text" name="riwayat_smp_mts"
                                        value="<?php echo htmlspecialchars($siswa['riwayat_smp_mts'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-3 text-sm bg-white"
                                        placeholder="...">
                                </div>

                                <div
                                    class="bg-gradient-to-r from-indigo-50 to-indigo-50 p-5 rounded-xl border-l-4 border-indigo-500">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-school text-indigo-600 mr-2"></i>Asal SMK
                                    </label>
                                    <input type="text" name="riwayat_sma_smk_ma"
                                        value="<?php echo htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm bg-white"
                                        placeholder="...">
                                </div>

                                <div class="form-divider">
                                    <span><i class="fas fa-trophy mr-2"></i>Prestasi & Organisasi</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-medal text-indigo-600 mr-2"></i>Prestasi & Pengalaman
                                    </label>
                                    <textarea name="prestasi_pengalaman" rows="5"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Juara lomba, pengalaman magang, sertifikat, dll"><?php echo htmlspecialchars($siswa['prestasi_pengalaman'] ?? ''); ?></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-users-cog text-indigo-600 mr-2"></i>Riwayat Organisasi
                                    </label>
                                    <textarea name="organisasi" rows="3"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="OSIS, Pramuka, komunitas, dll"><?php echo htmlspecialchars($siswa['organisasi'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-col items-center gap-4 mt-10 no-print">
                                <button type="submit"
                                    class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
                                    <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
                                    <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
                                </button>
                                <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar sebelum
                                    menyimpan</p>
                            </div>
                        </div>
                        <div id="data-orang-tua"
                            class="tab-content bg-white p-8 rounded-b-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-indigo-100 section-header">
                                <i class="fas fa-users text-indigo-600 mr-3"></i> Data Orang Tua
                            </h3>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- ================= AYAH ================= -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-2xl border-2 border-blue-200 shadow-md">
        <h4 class="font-bold text-blue-800 mb-4 flex items-center text-lg">
            <i class="fas fa-male text-2xl mr-3"></i> Data Ayah
        </h4>

        <div class="space-y-4">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Ayah</label>
                <input type="text" name="nama_ayah"
                    value="<?= htmlspecialchars($siswa['nama_ayah'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Nama Lengkap Ayah">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tempat Lahir</label>
                <input type="text" name="tempat_lahir_ayah"
                    value="<?= htmlspecialchars($siswa['tempat_lahir_ayah'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Contoh: Banjarmasin">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir_ayah"
                    value="<?= htmlspecialchars($siswa['tanggal_lahir_ayah'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pekerjaan</label>
                <input type="text" name="pekerjaan_ayah"
                    value="<?= htmlspecialchars($siswa['pekerjaan_ayah'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Wiraswasta, PNS, dll">
            </div>

           <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penghasilan Ayah</label>
                <input type="text" name="penghasilan_ayah"
                    value="<?= !empty($siswa['penghasilan_ayah']) ? (int)$siswa['penghasilan_ayah'] : '' ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3 input-rupiah"
                    placeholder="Rp 0"
                    inputmode="numeric"
                    autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP Ayah</label>
                <input type="tel" name="no_hp_ayah"
                    value="<?= htmlspecialchars($siswa['no_hp_ayah'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="08xxxxxxxxxx">
            </div>

        </div>
    </div>

    <!-- ================= IBU ================= -->
    <div class="bg-gradient-to-br from-pink-50 to-rose-50 p-6 rounded-2xl border-2 border-pink-200 shadow-md">

        <h4 class="font-bold text-pink-800 mb-4 flex items-center text-lg">
            <i class="fas fa-female text-2xl mr-3"></i> Data Ibu
        </h4>

        <div class="space-y-4">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Ibu</label>
                <input type="text" name="nama_ibu"
                    value="<?= htmlspecialchars($siswa['nama_ibu'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Nama Lengkap Ibu">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tempat Lahir</label>
                <input type="text" name="tempat_lahir_ibu"
                    value="<?= htmlspecialchars($siswa['tempat_lahir_ibu'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Contoh: Banjarmasin">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir_ibu"
                    value="<?= htmlspecialchars($siswa['tanggal_lahir_ibu'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pekerjaan</label>
                <input type="text" name="pekerjaan_ibu"
                    value="<?= htmlspecialchars($siswa['pekerjaan_ibu'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="Ibu Rumah Tangga, Guru, dll">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penghasilan Ibu</label>
                <input type="text" name="penghasilan_ibu"
                    value="<?= !empty($siswa['penghasilan_ibu']) ? (int)$siswa['penghasilan_ibu'] : '' ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3 input-rupiah"
                    placeholder="Rp 0"
                    inputmode="numeric"
                    autocomplete="off">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor HP Ibu</label>
                <input type="tel" name="no_hp_ibu"
                    value="<?= htmlspecialchars($siswa['no_hp_ibu'] ?? '') ?>"
                    class="w-full rounded-xl border-2 border-gray-300 p-3"
                    placeholder="08xxxxxxxxxx">
            </div>

        </div>

    </div>

</div>
                            
                            <div class="flex flex-col md:flex-col items-center gap-4 mt-10 no-print">
                                <button type="submit"
                                    class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
                                    <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
                                    <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
                                </button>
                                <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar sebelum
                                    menyimpan</p>
                            </div>
                        </div>
                        <div id="data-pendukung"
                            class="tab-content bg-white p-8 rounded-b-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-indigo-100 section-header">
                                <i class="fas fa-home text-indigo-600 mr-3"></i> Data Pendukung Belajar
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-house-user text-indigo-600 mr-2"></i>Status Tempat Tinggal
    </label>
    <input
        type="text"
        name="status_tempat_tinggal"
        value="<?php echo htmlspecialchars($siswa['status_tempat_tinggal'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: Tinggal bersama orang tua">
</div>

                                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-route text-indigo-600 mr-2"></i>Jarak ke Sekolah
    </label>
    <input
        type="text"
        name="jarak_ke_sekolah"
        value="<?php echo htmlspecialchars($siswa['jarak_ke_sekolah'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: ±5 km">
</div>

                                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-bus text-indigo-600 mr-2"></i>Transportasi
    </label>
    <input
        type="text"
        name="transportasi_ke_sekolah"
        value="<?php echo htmlspecialchars($siswa['transportasi_ke_sekolah'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: Sepeda motor, Sepeda, Jalan kaki">
</div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-laptop text-indigo-600 mr-2"></i>Kepemilikan Gadget
                                    </label>
                                    <select name="memiliki_hp_laptop"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($daftar_kepemilikan_gadget as $gadget): ?>
                                        <option value="<?php echo $gadget; ?>" <?php echo
                                            ($siswa['memiliki_hp_laptop']==$gadget) ? 'selected' : '' ; ?>>
                                            <?php echo $gadget; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-wifi text-indigo-600 mr-2"></i>Fasilitas Internet
    </label>
    <input
        type="text"
        name="fasilitas_internet"
        value="<?php echo htmlspecialchars($siswa['fasilitas_internet'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: WiFi rumah, Paket data">
</div>

                                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-chair text-indigo-600 mr-2"></i>Fasilitas Belajar
    </label>
    <input
        type="text"
        name="fasilitas_belajar_dirumah"
        value="<?php echo htmlspecialchars($siswa['fasilitas_belajar_dirumah'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: Meja belajar, Laptop, Buku">
</div>

                               <div class="md:col-span-2">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-book text-indigo-600 mr-2"></i>Kepemilikan Buku Pelajaran
    </label>
    <input
        type="text"
        name="buku_pelajaran_dimiliki"
        value="<?php echo htmlspecialchars($siswa['buku_pelajaran_dimiliki'] ?? ''); ?>"
        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
        placeholder="Contoh: Buku paket semua mapel, Modul digital">
</div>

                                <div class="md:col-span-2">
                                    <div class="form-divider">
                                        <span><i class="fas fa-language mr-2"></i>Kemampuan Bahasa</span>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-comments text-indigo-600 mr-2"></i>Bahasa Sehari-hari
                                    </label>
                                    <input type="text" name="bahasa_sehari_hari"
                                        value="<?php echo htmlspecialchars($siswa['bahasa_sehari_hari'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Banjar, Indonesia">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-globe text-indigo-600 mr-2"></i>Bahasa Asing yang Dikuasai
                                    </label>
                                    <textarea name="bahasa_asing_dikuasai" rows="2"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 p-3 text-sm"
                                        placeholder="Inggris (Menengah), Jepang (Dasar)"><?php echo htmlspecialchars($siswa['bahasa_asing_dikuasai'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="flex flex-col md:flex-col items-center gap-4 mt-10 no-print">
                                <button type="submit"
                                    class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
                                    <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
                                    <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
                                </button>
                                <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar sebelum
                                    menyimpan</p>
                            </div>
                        </div>
                        <div id="profil-psikologis"
                            class="tab-content bg-white p-8 rounded-b-2xl shadow-xl border border-gray-100 space-y-8 hidden">
                            <h3 class="text-2xl font-bold text-gray-800 pb-4 border-b-2 border-indigo-100 section-header">
                                <i class="fas fa-brain text-indigo-600 mr-3"></i> Profil Psikologis & Minat
                            </h3>

                            <div class="space-y-6">
                                <div class="bg-slate-50 p-6 rounded-2xl border-l-4 border-slate-400">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Cita-cita
                                    </label>
                                    <input type="text" name="cita_cita"
                                        value="<?php echo htmlspecialchars($siswa['cita_cita'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-slate-500 focus:ring focus:ring-slate-200 p-3 text-sm bg-white"
                                        placeholder="Programmer, Designer, dll">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-heart text-gray-600 mr-2"></i>Hobi & Kegemaran
                                    </label>
                                    <textarea name="hobi_kegemaran" rows="3"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-slate-500 focus:ring focus:ring-slate-200 p-3 text-sm"
                                        placeholder="Gaming, membaca, olahraga, dll"><?php echo htmlspecialchars($siswa['hobi_kegemaran'] ?? ''); ?></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="bg-stone-50 p-4 rounded-xl border-2 border-stone-300">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-thumbs-up text-stone-600 mr-2"></i>Pelajaran Disenangi
                                        </label>
                                        <input type="text" name="pelajaran_disenangi"
                                            value="<?php echo htmlspecialchars($siswa['pelajaran_disenangi'] ?? ''); ?>"
                                            class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-stone-500 focus:ring focus:ring-stone-200 p-3 text-sm bg-white"
                                            placeholder="Matematika, Fisika">
                                    </div>

                                    <div class="bg-amber-50 p-4 rounded-xl border-2 border-amber-300">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-thumbs-down text-amber-600 mr-2"></i>Pelajaran Kurang
                                            Disenangi
                                        </label>
                                        <input type="text" name="pelajaran_tdk_disenangi"
                                            value="<?php echo htmlspecialchars($siswa['pelajaran_tdk_disenangi'] ?? ''); ?>"
                                            class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-amber-500 focus:ring focus:ring-amber-200 p-3 text-sm bg-white"
                                            placeholder="Sejarah, Seni">
                                    </div>
                                </div>

                                <div class="form-divider">
                                    <span><i class="fas fa-user-circle mr-2"></i>Tentang Diri</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-pen-fancy text-gray-600 mr-2"></i>Deskripsi Singkat
                                    </label>
                                    <textarea name="tentang_saya_singkat" rows="4"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-slate-500 focus:ring focus:ring-slate-200 p-3 text-sm"
                                        placeholder="Ceritakan tentang diri Anda dalam beberapa kalimat..."><?php echo htmlspecialchars($siswa['tentang_saya_singkat'] ?? ''); ?></textarea>
                                </div>

                                <div class="bg-lime-50 p-6 rounded-xl border-2 border-lime-300">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-plus-circle text-lime-600 mr-2"></i>Kelebihan Diri
                                    </label>
                                    <textarea name="kelebihan_diri" rows="3"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-lime-500 focus:ring focus:ring-lime-200 p-3 text-sm bg-white"
                                        placeholder="Teliti, kerja sama tim, cepat belajar"><?php echo htmlspecialchars($siswa['kelebihan_diri'] ?? ''); ?></textarea>
                                </div>

                                <div class="bg-orange-50 p-6 rounded-xl border-2 border-orange-300">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-exclamation-circle text-orange-600 mr-2"></i>Kekurangan Diri
                                    </label>
                                    <textarea name="kekurangan_diri" rows="3"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 p-3 text-sm bg-white"
                                        placeholder="Kurang percaya diri, sering menunda"><?php echo htmlspecialchars($siswa['kekurangan_diri'] ?? ''); ?></textarea>
                                </div>

                                <div class="bg-blue-50 p-6 rounded-xl border-2 border-blue-300">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-user-friends text-blue-600 mr-2"></i>Tempat Curhat
                                    </label>
                                    <input type="text" name="tempat_curhat"
                                        value="<?php echo htmlspecialchars($siswa['tempat_curhat'] ?? ''); ?>"
                                        class="w-full rounded-xl border-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 p-3 text-sm bg-white"
                                        placeholder="Orang tua, sahabat, kakak">
                                </div>
                                <div class="flex flex-col md:flex-col items-center gap-4 mt-10 no-print">
                                    <button type="submit"
                                        class="w-full md:flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 px-8 rounded-[1rem] shadow-xl shadow-blue-100 flex items-center justify-center gap-3 group transition-all">
                                        <i class="fas fa-save group-hover:scale-110 transition-transform text-lg"></i>
                                        <span class="text-sm uppercase tracking-widest">Simpan Perubahan</span>
                                    </button>
                                    <p class="text-sm text-gray-600">Pastikan semua data telah terisi dengan benar
                                        sebelum menyimpan</p>
                                </div>
                            </div>
                        </div>

                    </div>
                    <script>
                        document.getElementById('btnExportCV').addEventListener('click', function (e) {
                            e.preventDefault();
                            const idSiswa = "<?php echo $id_siswa; ?>";
                            const btn = this;
                            const originalContent = btn.innerHTML;

                            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
                            btn.classList.add('opacity-50', 'pointer-events-none');

                            const url = 'cv_template.php?id_siswa=' + idSiswa;
                            const printWindow = window.open(url, '_blank');

                            if (!printWindow) {
                                btn.innerHTML = originalContent;
                                btn.classList.remove('opacity-50', 'pointer-events-none');
                                alert('Mohon izinkan pop-up di browser kamu untuk mencetak CV.');
                                return;
                            }

                            const restoreBtn = function () {
                                btn.innerHTML = originalContent;
                                btn.classList.remove('opacity-50', 'pointer-events-none');
                            };

                            let alreadyTriggered = false;
                            const triggerPrint = function () {
                                if (alreadyTriggered) return;
                                alreadyTriggered = true;
                                restoreBtn();
                                try {
                                    printWindow.focus();
                                    printWindow.print();
                                } catch (err) {
                                    console.error('Gagal memicu print otomatis:', err);
                                }
                            };

                            printWindow.addEventListener('load', triggerPrint);

                            setTimeout(triggerPrint, 2500);
                        });

                        document.getElementById('btnExportDataLengkap').addEventListener('click', function (e) {
                            e.preventDefault();
                            const idSiswa = "<?php echo $id_siswa; ?>";
                            const btn = this;
                            const originalContent = btn.innerHTML;

                            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
                            btn.classList.add('opacity-50', 'pointer-events-none');

                            const url = 'cetak_data_lengkap.php?id_siswa=' + idSiswa;
                            const printWindow = window.open(url, '_blank');

                            if (!printWindow) {
                                btn.innerHTML = originalContent;
                                btn.classList.remove('opacity-50', 'pointer-events-none');
                                alert('Mohon izinkan pop-up di browser kamu untuk mencetak data lengkap.');
                                return;
                            }

                            const restoreBtnData = function () {
                                btn.innerHTML = originalContent;
                                btn.classList.remove('opacity-50', 'pointer-events-none');
                            };

                            let alreadyTriggeredData = false;
                            const triggerPrintData = function () {
                                if (alreadyTriggeredData) return;
                                alreadyTriggeredData = true;
                                restoreBtnData();
                                try {
                                    printWindow.focus();
                                    printWindow.print();
                                } catch (err) {
                                    console.error('Gagal memicu print otomatis:', err);
                                }
                            };

                            printWindow.addEventListener('load', triggerPrintData);

                            setTimeout(triggerPrintData, 2500);
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
        <button type="button"
            onclick="document.querySelector('form').scrollIntoView({behavior: 'smooth', block: 'end'})"
            class="bg-gradient-to-r from-indigo-500 to-blue-500 text-white p-4 rounded-full shadow-2xl hover:shadow-3xl transition-all duration-300 hover:scale-110 icon-bounce">
            <i class="fas fa-arrow-down text-xl"></i>
        </button>
    </div>
    <button id="backToTop"
        class="hidden fixed bottom-6 left-6 bg-gray-800 text-white p-4 rounded-full shadow-2xl hover:bg-gray-900 transition-all duration-300 hover:scale-110 z-40">
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
            input.addEventListener('blur', function () {
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
                container.addEventListener('click', function (e) {
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
    document.querySelectorAll('.input-rupiah').forEach(function(input){

    function formatRupiah(value){
        value = value.replace(/[^0-9]/g,'');

        if(value === '') return '';

        return 'Rp ' + value.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    }

    input.value = formatRupiah(input.value);

    input.addEventListener('input', function(){
        this.value = formatRupiah(this.value);
    });

});
    </script>

</script>
</body>

</html>