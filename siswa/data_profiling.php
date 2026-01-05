<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];
$pesan_sukses = "";
$pesan_error = "";
$target_dir = "../uploads/foto_siswa/";

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
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
    FROM siswa s
    LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
    WHERE s.id_siswa='$id_siswa'
");
$siswa = mysqli_fetch_assoc($query_siswa);

if (!$siswa) {
    die("Data siswa tidak ditemukan atau sesi bermasalah.");
}

$query_kecerdasan = mysqli_query($koneksi, "
    SELECT *
    FROM hasil_kecerdasan
    WHERE id_siswa='$id_siswa'
    ORDER BY tanggal_tes DESC 
    LIMIT 1
");
$hasil_kecerdasan = mysqli_fetch_assoc($query_kecerdasan);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama_panggilan = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
    $tempat_lahir = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = mysqli_real_escape_string($koneksi, $_POST['tanggal_lahir'] ?? '');

    $alamat_lengkap = mysqli_real_escape_string($koneksi, $_POST['alamat_lengkap'] ?? '');
    $berat_badan = mysqli_real_escape_string($koneksi, $_POST['berat_badan'] ?? '');
    $tinggi_badan = mysqli_real_escape_string($koneksi, $_POST['tinggi_badan'] ?? '');
    $agama = mysqli_real_escape_string($koneksi, $_POST['agama'] ?? '');
    $hobi_kegemaran = mysqli_real_escape_string($koneksi, $_POST['hobi_kegemaran'] ?? '');
    $tentang_saya_singkat = mysqli_real_escape_string($koneksi, $_POST['tentang_saya_singkat'] ?? '');
    $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp'] ?? '');
    $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
    $instagram = mysqli_real_escape_string($koneksi, $_POST['instagram'] ?? '');
    
    $riwayat_sma_smk_ma = mysqli_real_escape_string($koneksi, $_POST['riwayat_sma_smk_ma'] ?? '');

    $riwayat_smp_mts = mysqli_real_escape_string($koneksi, $_POST['riwayat_smp_mts'] ?? '');
    $riwayat_sd_mi = mysqli_real_escape_string($koneksi, $_POST['riwayat_sd_mi'] ?? '');
    $prestasi_pengalaman = mysqli_real_escape_string($koneksi, $_POST['prestasi_pengalaman'] ?? '');
    $organisasi = mysqli_real_escape_string($koneksi, $_POST['organisasi'] ?? '');
    
    $url_foto_db = $_POST['current_url_foto'] ?? '';

    $anak_ke = mysqli_real_escape_string($koneksi, $_POST['anak_ke'] ?? '');
    $suku = mysqli_real_escape_string($koneksi, $_POST['suku'] ?? '');
    $cita_cita = mysqli_real_escape_string($koneksi, $_POST['cita_cita'] ?? '');
    $riwayat_penyakit = mysqli_real_escape_string($koneksi, $_POST['riwayat_penyakit'] ?? '');

    $nama_ayah = mysqli_real_escape_string($koneksi, $_POST['nama_ayah'] ?? '');
    $pekerjaan_ayah = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ayah'] ?? '');
    $nama_ibu = mysqli_real_escape_string($koneksi, $_POST['nama_ibu'] ?? '');
    $pekerjaan_ibu = mysqli_real_escape_string($koneksi, $_POST['pekerjaan_ibu'] ?? '');
    $no_hp_ortu = mysqli_real_escape_string($koneksi, $_POST['no_hp_ortu'] ?? '');

    $status_tempat_tinggal = mysqli_real_escape_string($koneksi, $_POST['status_tempat_tinggal'] ?? '');
    $jarak_ke_sekolah = mysqli_real_escape_string($koneksi, $_POST['jarak_ke_sekolah'] ?? '');
    $transportasi_ke_sekolah = mysqli_real_escape_string($koneksi, $_POST['transportasi_ke_sekolah'] ?? '');
    $memiliki_hp_laptop = mysqli_real_escape_string($koneksi, $_POST['memiliki_hp_laptop'] ?? '');
    $fasilitas_internet = mysqli_real_escape_string($koneksi, $_POST['fasilitas_internet'] ?? '');
    $fasilitas_belajar_dirumah = mysqli_real_escape_string($koneksi, $_POST['fasilitas_belajar_dirumah'] ?? '');
    $buku_pelajaran_dimiliki = mysqli_real_escape_string($koneksi, $_POST['buku_pelajaran_dimiliki'] ?? '');
    $bahasa_sehari_hari = mysqli_real_escape_string($koneksi, $_POST['bahasa_sehari_hari'] ?? '');
    $bahasa_asing_dikuasai = mysqli_real_escape_string($koneksi, $_POST['bahasa_asing_dikuasai'] ?? '');
    
    $pelajaran_disenangi = mysqli_real_escape_string($koneksi, $_POST['pelajaran_disenangi'] ?? '');
    $pelajaran_tdk_disenangi = mysqli_real_escape_string($koneksi, $_POST['pelajaran_tdk_disenangi'] ?? '');
    $tempat_curhat = mysqli_real_escape_string($koneksi, $_POST['tempat_curhat'] ?? '');
    $kelebihan_diri = mysqli_real_escape_string($koneksi, $_POST['kelebihan_diri'] ?? '');
    $kekurangan_diri = mysqli_real_escape_string($koneksi, $_POST['kekurangan_diri'] ?? '');

    if (isset($_FILES["url_foto"]) && $_FILES["url_foto"]["error"] == 0) {
        $file_name = basename($_FILES["url_foto"]["name"]);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = "foto_" . $id_siswa . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        $uploadOk = 1;

        if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
            $pesan_error .= "Maaf, hanya file JPG, JPEG, & PNG yang diizinkan. ";
            $uploadOk = 0;
        }

        if ($_FILES["url_foto"]["size"] > 5000000) {
            $pesan_error .= "Maaf, ukuran file terlalu besar (maks 5MB). ";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["url_foto"]["tmp_name"], $target_file)) {
                if (!empty($url_foto_db) && file_exists('../' . $url_foto_db) && strpos($url_foto_db, 'placeholder') === false) {
                    unlink('../' . $url_foto_db);
                }
                $url_foto_db = str_replace('../', '', $target_file); 
            } else {
                $pesan_error .= "Maaf, terjadi kesalahan saat mengupload file. ";
            }
        }
    }
    
    if (empty($pesan_error)) {
        $update_query = "
            UPDATE siswa SET 
                nama_panggilan = '$nama_panggilan',
                tempat_lahir = '$tempat_lahir',
                tanggal_lahir = '$tanggal_lahir', 
                alamat_lengkap = '$alamat_lengkap',
                berat_badan = '$berat_badan',
                tinggi_badan = '$tinggi_badan',
                agama = '$agama',
                hobi_kegemaran = '$hobi_kegemaran',
                tentang_saya_singkat = '$tentang_saya_singkat',
                no_telp = '$no_telp',
                email = '$email',
                instagram = '$instagram',
                riwayat_sma_smk_ma = '$riwayat_sma_smk_ma', 
                riwayat_smp_mts = '$riwayat_smp_mts',
                riwayat_sd_mi = '$riwayat_sd_mi',
                prestasi_pengalaman = '$prestasi_pengalaman',
                organisasi = '$organisasi',
                url_foto = '$url_foto_db',
                
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
            $pesan_sukses = "Data biodata berhasil diperbarui!";
            $query_siswa = mysqli_query($koneksi, "
                SELECT 
                    s.*,
                    hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
                FROM siswa s
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
            
        } else {
            $pesan_error = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
    }
}

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


$nama_siswa = htmlspecialchars($siswa['nama']);
$kelas_jurusan = htmlspecialchars($siswa['kelas'] . " " . $siswa['jurusan']);
$tempat_lahir_val = htmlspecialchars($siswa['tempat_lahir'] ?? '');
$tanggal_lahir_val = htmlspecialchars($siswa['tanggal_lahir'] ?? '');
$agama_val = htmlspecialchars($siswa['agama'] ?? '');

$riwayat_smk_val = htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Profiling | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #2F6C6E;
            --secondary-color: #38A169;
        }
        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .primary-border { border-color: var(--primary-color); }
        .hover-bg-primary:hover { background-color: #1F4C4E; }
        
        .form-label { font-weight: 600; color: #374151; margin-bottom: 4px; display: block; font-size: 0.95rem; }
        .form-input, .form-textarea, .form-select { 
            width: 100%; 
            padding: 10px 12px; 
            border: 1px solid #D1D5DB; 
            border-radius: 8px; 
            transition: border-color 0.2s, box-shadow 0.2s; 
            background-color: #FFFFFF;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
             border-color: var(--primary-color); 
             outline: none; 
             box-shadow: 0 0 0 2px rgba(47, 108, 110, 0.5);
        }
        .form-input[readonly] {
            background-color: #F3F4F6;
            border-color: #E5E7EB;
            cursor: not-allowed;
        }
        .collapsed {
    display: none;
}

.accordion-icon {
    transition: transform 0.3s ease;
}

.accordion-icon.rotated {
    transform: rotate(180deg);
}

        .fade-slide { transition: all 0.3s ease-in-out; transform-origin: top; }
        .hidden-transition { opacity: 0; transform: scaleY(0.95); max-height: 0; overflow: hidden; pointer-events: none; }
        .visible-transition { opacity: 1; transform: scaleY(1); max-height: 500px; pointer-events: auto; }
        
        .accordion-header {
            cursor: pointer;
            padding: 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s, box-shadow 0.3s;
            background-color: #f7f7f7;
            border: 1px solid #e5e5e5;
        }
        .accordion-header:hover {
            background-color: #f0f0f0;
        }
        .accordion-content {
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, opacity 0.4s ease-in-out;
        }
        .accordion-content.collapsed {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
        }
        .accordion-content:not(.collapsed) {
            max-height: 2000px;
            opacity: 1;
            padding-top: 1.5rem;
        }
        .accordion-icon {
            transition: transform 0.3s;
        }
        .accordion-icon.rotated {
            transform: rotate(180deg);
        }
        
        #instructionModal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-color: rgba(0, 0, 0, 0.7); z-index: 9999; 
            display: none; align-items: center; justify-content: center;
        }
        #modalContent {
            background-color: #fff; padding: 30px; border-radius: 12px; 
            max-width: 90%; width: 600px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5); 
            transform: scale(0.9); opacity: 0; transition: all 0.3s ease-in-out;
        }
        #instructionModal.active #modalContent {
            transform: scale(1); opacity: 1;
        }

        @media print {
            .no-print { display: none !important; }
            body { background-color: white; }
            .print-area { box-shadow: none; border: none; padding: 0; margin: 0; }
            .accordion-content { max-height: fit-content !important; opacity: 1 !important; padding: 0 !important; }
            .accordion-header { display: none; }
            .accordion-group { border-bottom: 2px solid #ccc; padding-bottom: 15px; margin-bottom: 20px; }
            .print-hidden-title { display: block !important; }
            .print-hidden-title h3 {
                border-bottom: 2px solid #ccc;
                padding-bottom: 5px;
                margin-top: 15px;
                font-size: 1.1rem;
                color: #333;
            }
        }
        .print-hidden-title { display: none; }
    </style>

    <script>
        const PENDIDIKAN_FLAG = 'profilingInstructionsShown_pendidikan';
        const PRESTASI_FLAG = 'profilingInstructionsShown_prestasi';

        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            const isClosed = menu.classList.contains('hidden-transition');

            if (isClosed) {
                menu.classList.remove('hidden-transition');
                menu.classList.add('visible-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            } else {
                menu.classList.remove('visible-transition');
                menu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            }
        }
        
        function previewImage(event) {
            const input = event.target;
            const imagePreview = document.getElementById('fotoPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function toggleAccordion(header) {
    const currentGroup = header.closest('.accordion-group');
    const currentContent = currentGroup.querySelector('.accordion-content');
    const currentIcon = header.querySelector('.accordion-icon');

    const isCollapsed = currentContent.classList.contains('collapsed');

    // 1. Tutup semua accordion
    document.querySelectorAll('.accordion-group').forEach(group => {
        group.querySelector('.accordion-content').classList.add('collapsed');
        group.querySelector('.accordion-icon').classList.remove('rotated');
    });

    // 2. Jika sebelumnya tertutup, buka yang diklik
    if (isCollapsed) {
        currentContent.classList.remove('collapsed');
        currentIcon.classList.add('rotated');
    }
}

        function showInstructions(type, event) {
            const instructionFlag = type === 'pendidikan' ? PENDIDIKAN_FLAG : PRESTASI_FLAG;
            if (sessionStorage.getItem(instructionFlag) === 'true') {
                return;
            }
            
            if (event && event.target) {
                event.target.blur(); 
            }
            
            const modal = document.getElementById('instructionModal');
            const pendInst = document.getElementById('PendidikanInstructions');
            const presInst = document.getElementById('PrestasiInstructions');
            const closeBtn = document.getElementById('closeButtonFooter');
            const closeBtnIcon = document.getElementById('closeModalButton');
            
            if (window.closeCountdownInterval) {
                 clearInterval(window.closeCountdownInterval);
            }

            if (type === 'pendidikan') {
                pendInst.style.display = 'block';
                presInst.style.display = 'none';
            } else if (type === 'prestasi') {
                pendInst.style.display = 'none';
                presInst.style.display = 'block';
            } else {
                return;
            }

            modal.classList.add('active');
            modal.style.display = 'flex'; 
            
            closeBtn.disabled = true;
            closeBtn.classList.add('disabled:bg-gray-400', 'disabled:cursor-wait');
            closeBtnIcon.disabled = true;
            closeBtnIcon.classList.add('disabled:opacity-50', 'disabled:cursor-wait');

            let countdown = 5;
            closeBtn.textContent = `Tutup (Tunggu ${countdown} detik)`;

            window.closeCountdownInterval = setInterval(() => {
                countdown--;
                closeBtn.textContent = `Tutup (Tunggu ${countdown} detik)`;
                if (countdown <= 0) {
                    clearInterval(window.closeCountdownInterval);
                    closeBtn.disabled = false;
                    closeBtn.textContent = 'Mengerti, Lanjutkan Mengisi';
                    closeBtnIcon.disabled = false;
                    closeBtn.classList.remove('disabled:bg-gray-400', 'disabled:cursor-wait');
                    closeBtnIcon.classList.remove('disabled:opacity-50', 'disabled:cursor-wait');
                }
            }, 1000);

            sessionStorage.setItem(instructionFlag, 'true');
        }
        
        document.getElementById('btnExportCV').addEventListener('click', function(e) {
            e.preventDefault();
            fetch('cv_template.php')
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

        function closeInstructions() {
            const modal = document.getElementById('instructionModal');
            const closeBtn = document.getElementById('closeButtonFooter');
            
            if (closeBtn.disabled) return; 
            
            modal.classList.remove('active');
            if (window.closeCountdownInterval) {
                 clearInterval(window.closeCountdownInterval);
            }
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="no-print flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-lg relative z-30">
        <a href="dashboard.php" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <div>
                <strong class="text-base md:text-xl primary-color font-extrabold">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-gray-600">Bimbingan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Beranda</a>
            <a href="data_profiling.php" class="primary-color font-bold border-b-2 border-transparent border-primary-color pb-1 transition underline">Data Profiling</a>
            <a href="riwayatkonselingsiswa.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Riwayat</a>
            <a href="ganti_password.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition text-sm font-semibold shadow-md">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="no-print hidden fixed inset-0 bg-black/50 z-20 transition-opacity duration-300" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="no-print fade-slide hidden-transition absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex flex-col text-left text-base border-t border-gray-100">
        <a href="dashboard.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 primary-color bg-gray-100 font-bold transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>
    
    <section class="no-print text-center py-8 md:py-12 primary-bg text-white shadow-xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            <i class="fas fa-id-card-alt mr-2"></i> Edit Data Profiling Siswa
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Lengkapi dan perbarui biodata Anda. Kelengkapan data ini wajib untuk membuka akses ke semua tes.
        </p>
    </section>

    <section class="py-8 md:py-10 px-4 flex-grow">
        <div class="max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-xl shadow-2xl border border-gray-200 print-area">

            <?php if ($pesan_sukses): ?>
                <div class="no-print bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                    <p class="font-bold"><i class="fas fa-check-circle mr-2"></i> Berhasil!</p>
                    <p><?php echo $pesan_sukses; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($pesan_error): ?>
                <div class="no-print bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                    <p class="font-bold"><i class="fas fa-times-circle mr-2"></i> Gagal!</p>
                    <p><?php echo $pesan_error; ?></p>
                </div>
            <?php endif; ?>

            <div class="no-print mb-8 flex flex-wrap justify-between items-center gap-3">
                <button type="button" id="btnSiswaExport" class="w-full md:w-auto px-8 py-4 bg-white border-2 border-blue-400 text-gray-700 font-bold rounded-2xl hover:bg-gray-50 shadow-sm flex items-center justify-center gap-2">
        <i class="fas fa-print text-blue-600"></i> Cetak CV
    </button>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCetak = document.getElementById('btnSiswaExport');
    
    if (btnCetak) {
        btnCetak.addEventListener('click', function(e) {
            e.preventDefault();
            
            const btn = this;
            const originalContent = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Loading...';
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';

            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = 'cv_template.php';
            document.body.appendChild(iframe);

            iframe.onload = function() {
                btn.innerHTML = originalContent;
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';

                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
     
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 1000);
                }, 500);
            };

      
            iframe.onerror = function() {
                alert('Gagal memuat dokumen cetak.');
                btn.innerHTML = originalContent;
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
            };
        });
    }
});
</script>

                <div class="text-sm text-red-700 bg-red-100 p-3 rounded-lg border border-red-400 shadow-sm">
                    <i class="fas fa-info-circle mr-2"></i> Lengkapi semua data dengan jujur.
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start mb-10">
                    
                    <div class="col-span-1 md:col-span-1 no-print">
                        <label class="form-label">Foto Profil</label>
                        <div class="bg-gray-50 p-4 rounded-xl shadow-inner border border-gray-200">
                            <?php 
                            $email_hash = md5(strtolower(trim($siswa['email'] ?? 'default@example.com')));
                            $gravatar_url = "https://www.gravatar.com/avatar/" . $email_hash . "?d=mp&s=200";

                            if (!empty($siswa['url_foto']) && file_exists('../' . $siswa['url_foto'])) {
                                $url_foto = '../' . $siswa['url_foto'];
                            } else {
                                $url_foto = $gravatar_url;
                            }
                            ?>
                            <div class="w-full aspect-square overflow-hidden mb-3 border-4 primary-border border-opacity-50 rounded-lg shadow-md">
                                <img id="fotoPreview" src="<?php echo htmlspecialchars($url_foto); ?>" alt="Foto Profil" class="w-full h-full object-cover">
                            </div>
                            
                            <input type="file" id="url_foto" name="url_foto" class="hidden" accept=".png,.jpg,.jpeg" onchange="previewImage(event)">
                            <label for="url_foto" class="cursor-pointer bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold py-2 px-4 rounded-lg transition flex items-center shadow-md justify-center">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> Ubah Foto (Max 5MB)
                            </label>
                            <input type="hidden" name="current_url_foto" value="<?php echo htmlspecialchars($siswa['url_foto']); ?>">
                            <p class="text-xs text-gray-500 mt-2 text-center">Format: JPG, JPEG, PNG (Disarankan 1:1)</p>
                        </div>
                    </div>
                    
                    <div class="md:col-span-3 space-y-6">
                        <div class="bg-blue-50 p-5 rounded-xl border-2 border-blue-200 shadow-inner">
                            <h4 class="text-lg font-bold text-blue-700 border-b pb-2 mb-3">Data Siswa (Wajib & Tetap)</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div><span class="font-medium text-gray-600">Nama Lengkap:</span> <strong class="text-gray-900"><?php echo $siswa['nama']; ?></strong></div>
                                <div><span class="font-medium text-gray-600">NIS:</span> <strong class="text-gray-900"><?php echo $siswa['nis']; ?></strong></div>
                                <div><span class="font-medium text-gray-600">NISN:</span> <strong class="text-gray-900"><?php echo htmlspecialchars($siswa['nisn'] ?? '-'); ?></strong></div>
                                <div><span class="font-medium text-gray-600">Kelas & Jurusan:</span> <strong class="text-gray-900"><?php echo $kelas_jurusan; ?></strong></div>
                                <!-- <div><span class="font-medium text-gray-600">ID Siswa (Internal):</span> <strong class="text-gray-900"><?php echo $siswa['id_siswa']; ?></strong></div> -->
                            </div>
                        </div>

                        <div class="bg-indigo-50 p-5 rounded-xl border-2 border-indigo-200 shadow-inner">
                            <h4 class="text-lg font-bold text-indigo-700 border-b pb-2 mb-3">Hasil Tes (Baca Saja)</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div><span class="font-medium text-gray-600">Gaya Belajar Dominan:</span> <strong class="text-indigo-900"><?php echo $gaya_belajar; ?></strong></div>
                                <div><span class="font-medium text-gray-600">Tipe Kemampuan Dominan:</span> <strong class="text-indigo-900"><?php echo $hasil_tes_kemampuan_calculated; ?></strong></div>
                            </div>
                        </div>

                    </div>
                    
                </div>
                
                <div class="space-y-4">
                
                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-user-circle mr-2"></i> 1. Data Diri Personal
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="nama_panggilan" class="form-label"><i class="fas fa-user-tag mr-1"></i> Nama Panggilan</label>
                                    <input type="text" id="nama_panggilan" name="nama_panggilan" class="form-input" value="<?php echo htmlspecialchars($siswa['nama_panggilan'] ?? ''); ?>" maxlength="50" placeholder="Contoh: Budi">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="tempat_lahir" class="form-label"><i class="fas fa-city mr-1"></i> Tempat Lahir</label>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-input" value="<?php echo htmlspecialchars($siswa['tempat_lahir'] ?? ''); ?>" maxlength="100" placeholder="Contoh: Banjarmasin">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="tanggal_lahir" class="form-label"><i class="fas fa-calendar-alt mr-1"></i> Tanggal Lahir</label>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-input" value="<?php echo htmlspecialchars($siswa['tanggal_lahir'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="agama" class="form-label"><i class="fas fa-praying-hands mr-1"></i> Agama</label>
                                    <select id="agama" name="agama" class="form-select">
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($daftar_agama as $opt): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>" 
                                                <?php echo ($siswa['agama'] == $opt) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($opt); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="berat_badan" class="form-label"><i class="fas fa-weight mr-1"></i> Berat Badan (kg)</label>
                                    <input type="number" id="berat_badan" name="berat_badan" class="form-input" value="<?php echo htmlspecialchars($siswa['berat_badan'] ?? ''); ?>" min="1" max="300" placeholder="Cth: 55">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="tinggi_badan" class="form-label"><i class="fas fa-ruler-vertical mr-1"></i> Tinggi Badan (cm)</label>
                                    <input type="number" id="tinggi_badan" name="tinggi_badan" class="form-input" value="<?php echo htmlspecialchars($siswa['tinggi_badan'] ?? ''); ?>" min="1" max="300" placeholder="Cth: 165">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="anak_ke" class="form-label">Anak ke... dari ...</label>
                                    <input type="text" id="anak_ke" name="anak_ke" class="form-input" value="<?php echo htmlspecialchars($siswa['anak_ke'] ?? ''); ?>" placeholder="Cth: 1 dari 3">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="suku" class="form-label"><i class="fas fa-hands-helping mr-1"></i> Suku</label>
                                    <input type="text" id="suku" name="suku" class="form-input" value="<?php echo htmlspecialchars($siswa['suku'] ?? ''); ?>" maxlength="100" placeholder="Cth: Banjar">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="cita_cita" class="form-label"><i class="fas fa-rocket mr-1"></i> Cita-Cita</label>
                                    <input type="text" id="cita_cita" name="cita_cita" class="form-input" value="<?php echo htmlspecialchars($siswa['cita_cita'] ?? ''); ?>" maxlength="255" placeholder="Cth: Programmer, Dokter">
                                </div>
                                
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="riwayat_penyakit" class="form-label"><i class="fas fa-medkit mr-1"></i> Riwayat Penyakit Serius</label>
                                    <input type="text" id="riwayat_penyakit" name="riwayat_penyakit" class="form-input" value="<?php echo htmlspecialchars($siswa['riwayat_penyakit'] ?? ''); ?>" maxlength="255" placeholder="Cth: Asma, dll (jika tidak ada kosongkan saja)">
                                </div>
                                
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="hobi_kegemaran" class="form-label"><i class="fas fa-heart mr-1"></i> Hobi/Kegemaran</label>
                                    <textarea id="hobi_kegemaran" name="hobi_kegemaran" rows="3" class="form-textarea" placeholder="Cth: Membaca buku fiksi, Bermain futsal, Menggambar digital."><?php echo htmlspecialchars($siswa['hobi_kegemaran'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="tentang_saya_singkat" class="form-label"><i class="fas fa-comment-dots mr-1"></i> Deskripsi Diri Singkat (Self Introduction)</label>
                                    <textarea id="tentang_saya_singkat" name="tentang_saya_singkat" rows="4" class="form-textarea" placeholder="Tuliskan tentang diri Anda dalam 1-2 paragraf, termasuk minat atau hal yang disukai."><?php echo htmlspecialchars($siswa['tentang_saya_singkat'] ?? ''); ?></textarea>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-phone-alt mr-2"></i> 2. Data Kontak dan Alamat
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8 collapsed">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="no_telp" class="form-label"><i class="fas fa-mobile-alt mr-1"></i> Nomor HP Siswa</label>
                                    <input type="number" id="no_telp" name="no_telp" class="form-input" value="<?php echo htmlspecialchars($siswa['no_telp'] ?? ''); ?>" maxlength="20" placeholder="Cth: 08123456789">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="email" class="form-label"><i class="fas fa-envelope mr-1"></i> Email Siswa</label>
                                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>" maxlength="100" placeholder="Cth: namaanda@gmail.com">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="instagram" class="form-label"><i class="fab fa-instagram mr-1"></i> Akun Instagram</label>
                                    <input type="text" id="instagram" name="instagram" class="form-input" value="<?php echo htmlspecialchars($siswa['instagram'] ?? ''); ?>" maxlength="100" placeholder="Cth: @usernamemu">
                                </div>
                                
                                <div class="md:col-span-2 lg:col-span-3">
                                    <label for="alamat_lengkap" class="form-label"><i class="fas fa-map-marked-alt mr-1"></i> Alamat Lengkap Saat Ini</label>
                                    <textarea id="alamat_lengkap" name="alamat_lengkap" rows="3" class="form-textarea" placeholder="Tuliskan alamat lengkap Anda (Jalan/Gang, RT/RW, Kelurahan, Kecamatan)."><?php echo htmlspecialchars($siswa['alamat_lengkap'] ?? ''); ?></textarea>
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-graduation-cap mr-2"></i> 3. Riwayat Pendidikan & Prestasi
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8 collapsed">
                            
                            <div class="print-hidden-title"><h3>Riwayat Pendidikan</h3></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                            
                                <div class="col-span-1">
                                    <label for="riwayat_sma_smk_ma" class="form-label"><i class="fas fa-school mr-1"></i> SMK</label>
                                    <textarea id="riwayat_sma_smk_ma" name="riwayat_sma_smk_ma" rows="3" class="form-textarea" placeholder="Contoh: SMKN 2 Banjarmasin (Tahun Masuk-Sekarang)"><?php echo htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="riwayat_smp_mts" class="form-label"><i class="fas fa-school mr-1"></i> SMP/MTs</label>
                                    <textarea id="riwayat_smp_mts" name="riwayat_smp_mts" rows="3" class="form-textarea" placeholder="Contoh: SMPN 5 Banjarmasin (Tahun Masuk-Tahun Lulus)"><?php echo htmlspecialchars($siswa['riwayat_smp_mts'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="riwayat_sd_mi" class="form-label"><i class="fas fa-school mr-1"></i> SD/MI</label>
                                    <textarea id="riwayat_sd_mi" name="riwayat_sd_mi" rows="3" class="form-textarea" placeholder="Contoh: SDN Seberang Mesjid 2 (Tahun Masuk-Tahun Lulus)"><?php echo htmlspecialchars($siswa['riwayat_sd_mi'] ?? ''); ?></textarea>
                                </div>
                                
                            </div>
                            
                            <div class="print-hidden-title"><h3>Prestasi & Organisasi</h3></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="prestasi_pengalaman" class="form-label"><i class="fas fa-medal mr-1"></i> Prestasi/Pengalaman Non-Akademik</label>
                                    <textarea id="prestasi_pengalaman" name="prestasi_pengalaman" rows="5" class="form-textarea" placeholder="Tuliskan prestasi, penghargaan, atau pengalaman lain (Lomba, magang, dll). Pisahkan per baris."><?php echo htmlspecialchars($siswa['prestasi_pengalaman'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="organisasi" class="form-label"><i class="fas fa-users-cog mr-1"></i> Riwayat Organisasi</label>
                                    <textarea id="organisasi" name="organisasi" rows="5" class="form-textarea" placeholder="Tuliskan organisasi yang pernah diikuti (OSIS, Pramuka, dll.) beserta posisi Anda. Pisahkan per baris."><?php echo htmlspecialchars($siswa['organisasi'] ?? ''); ?></textarea>
                                </div>
                            
                            </div>

                        </div>
                    </div>
                    
                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-user-friends mr-2"></i> 4. Data Orang Tua/Wali
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8 collapsed">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="nama_ayah" class="form-label"><i class="fas fa-male mr-1"></i> Nama Ayah</label>
                                    <input type="text" id="nama_ayah" name="nama_ayah" class="form-input" value="<?php echo htmlspecialchars($siswa['nama_ayah'] ?? ''); ?>" maxlength="100" placeholder="Nama Lengkap Ayah">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="pekerjaan_ayah" class="form-label"><i class="fas fa-briefcase mr-1"></i> Pekerjaan Ayah</label>
                                    <input type="text" id="pekerjaan_ayah" name="pekerjaan_ayah" class="form-input" value="<?php echo htmlspecialchars($siswa['pekerjaan_ayah'] ?? ''); ?>" maxlength="100" placeholder="Cth: PNS, Wiraswasta">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="nama_ibu" class="form-label"><i class="fas fa-female mr-1"></i> Nama Ibu</label>
                                    <input type="text" id="nama_ibu" name="nama_ibu" class="form-input" value="<?php echo htmlspecialchars($siswa['nama_ibu'] ?? ''); ?>" maxlength="100" placeholder="Nama Lengkap Ibu">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="pekerjaan_ibu" class="form-label"><i class="fas fa-briefcase mr-1"></i> Pekerjaan Ibu</label>
                                    <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" class="form-input" value="<?php echo htmlspecialchars($siswa['pekerjaan_ibu'] ?? ''); ?>" maxlength="100" placeholder="Cth: Ibu Rumah Tangga, Guru">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="no_hp_ortu" class="form-label"><i class="fas fa-mobile-alt mr-1"></i> Nomor HP Orang Tua</label>
                                    <input type="number" id="no_hp_ortu" name="no_hp_ortu" class="form-input" value="<?php echo htmlspecialchars($siswa['no_hp_ortu'] ?? ''); ?>" maxlength="20" placeholder="Nomor yang bisa dihubungi">
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-laptop-house mr-2"></i> 5. Data Fasilitas dan Lingkungan Belajar
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8 collapsed">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="status_tempat_tinggal" class="form-label"><i class="fas fa-home mr-1"></i> Status Tempat Tinggal</label>
                                    <input type="text" id="status_tempat_tinggal" name="status_tempat_tinggal" class="form-input" value="<?php echo htmlspecialchars($siswa['status_tempat_tinggal'] ?? ''); ?>" placeholder="Cth: Tinggal dengan Orang Tua / Kos">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="jarak_ke_sekolah" class="form-label"><i class="fas fa-route mr-1"></i> Jarak ke Sekolah</label>
                                    <input type="text" id="jarak_ke_sekolah" name="jarak_ke_sekolah" class="form-input" value="<?php echo htmlspecialchars($siswa['jarak_ke_sekolah'] ?? ''); ?>" placeholder="Cth: 5 km / 30 menit">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="transportasi_ke_sekolah" class="form-label"><i class="fas fa-bus mr-1"></i> Transportasi ke Sekolah</label>
                                    <input type="text" id="transportasi_ke_sekolah" name="transportasi_ke_sekolah" class="form-input" value="<?php echo htmlspecialchars($siswa['transportasi_ke_sekolah'] ?? ''); ?>" placeholder="Cth: Sepeda Motor / Angkutan Umum">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="memiliki_hp_laptop" class="form-label"><i class="fas fa-laptop-code mr-1"></i> Kepemilikan Gadget</label>
                                    <select id="memiliki_hp_laptop" name="memiliki_hp_laptop" class="form-select">
                            <option value="">-- Pilih Kepemilikan --</option>
                            <?php foreach ($daftar_kepemilikan_gadget as $gadget): ?>
                                <option value="<?php echo $gadget; ?>" <?php echo ($gadget == ($siswa['memiliki_hp_laptop'] ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo $gadget; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="fasilitas_internet" class="form-label"><i class="fas fa-wifi mr-1"></i> Akses Internet di Rumah</label>
                                    <input type="text" id="fasilitas_internet" name="fasilitas_internet" class="form-input" value="<?php echo htmlspecialchars($siswa['fasilitas_internet'] ?? ''); ?>" maxlength="255" placeholder="Cth: WiFi, Data Seluler, Tidak Ada">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="fasilitas_belajar_dirumah" class="form-label"><i class="fas fa-book-open mr-1"></i> Fasilitas Belajar di Rumah</label>
                                    <input type="text" id="fasilitas_belajar_dirumah" name="fasilitas_belajar_dirumah" class="form-input" value="<?php echo htmlspecialchars($siswa['fasilitas_belajar_dirumah'] ?? ''); ?>" maxlength="255" placeholder="Cth: Meja Belajar, Ruangan Khusus, dll">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="buku_pelajaran_dimiliki" class="form-label"><i class="fas fa-book mr-1"></i> Buku Pelajaran yang Dimiliki</label>
                                    <input type="text" id="buku_pelajaran_dimiliki" name="buku_pelajaran_dimiliki" class="form-input" value="<?php echo htmlspecialchars($siswa['buku_pelajaran_dimiliki'] ?? ''); ?>" maxlength="255" placeholder="Cth: Buku Paket, LKS, Buku Catatan">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="bahasa_sehari_hari" class="form-label"><i class="fas fa-comments mr-1"></i> Bahasa Sehari-hari</label>
                                    <input type="text" id="bahasa_sehari_hari" name="bahasa_sehari_hari" class="form-input" value="<?php echo htmlspecialchars($siswa['bahasa_sehari_hari'] ?? ''); ?>" maxlength="100" placeholder="Cth: Banjar, Indonesia">
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="bahasa_asing_dikuasai" class="form-label"><i class="fas fa-language mr-1"></i> Bahasa Asing Dikuasai</label>
                                    <input type="text" id="bahasa_asing_dikuasai" name="bahasa_asing_dikuasai" class="form-input" value="<?php echo htmlspecialchars($siswa['bahasa_asing_dikuasai'] ?? ''); ?>" maxlength="255" placeholder="Cth: Inggris (Pasif)">
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <div class="accordion-group border rounded-xl shadow-lg">
                        <div class="accordion-header flex justify-between items-center bg-gray-50 p-4 primary-border border-b-2">
                            <h3 class="text-lg font-semibold primary-color">
                                <i class="fas fa-brain mr-2"></i> 6. Data Akademik dan Kepribadian
                            </h3>
                            <i class="fas fa-chevron-down accordion-icon text-lg primary-color"></i>
                        </div>
                        <div class="accordion-content p-6 md:p-8 collapsed">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                                <div class="col-span-1">
                                    <label for="pelajaran_disenangi" class="form-label"><i class="fas fa-thumbs-up mr-1"></i> Mata Pelajaran yang Disenangi</label>
                                    <textarea id="pelajaran_disenangi" name="pelajaran_disenangi" rows="3" class="form-textarea" placeholder="Cth: Pemrograman Web, Bahasa Inggris"><?php echo htmlspecialchars($siswa['pelajaran_disenangi'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="pelajaran_tdk_disenangi" class="form-label"><i class="fas fa-thumbs-down mr-1"></i> Mata Pelajaran yang Tidak Disenangi</label>
                                    <textarea id="pelajaran_tdk_disenangi" name="pelajaran_tdk_disenangi" rows="3" class="form-textarea" placeholder="Cth: Matematika, Fisika"><?php echo htmlspecialchars($siswa['pelajaran_tdk_disenangi'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="kelebihan_diri" class="form-label"><i class="fas fa-star mr-1"></i> Kelebihan Diri</label>
                                    <textarea id="kelebihan_diri" name="kelebihan_diri" rows="4" class="form-textarea" placeholder="Cth: Cepat belajar, Komunikatif, Bersemangat"><?php echo htmlspecialchars($siswa['kelebihan_diri'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-span-1">
                                    <label for="kekurangan_diri" class="form-label"><i class="fas fa-frown mr-1"></i> Kekurangan Diri</label>
                                    <textarea id="kekurangan_diri" name="kekurangan_diri" rows="4" class="form-textarea" placeholder="Cth: Sulit fokus, Kurang percaya diri, Sering menunda"><?php echo htmlspecialchars($siswa['kekurangan_diri'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="tempat_curhat" class="form-label"><i class="fas fa-user-shield mr-1"></i> Tempat Curhat Utama (Saat Ada Masalah)</label>
                                    <input type="text" id="tempat_curhat" name="tempat_curhat" class="form-input" value="<?php echo htmlspecialchars($siswa['tempat_curhat'] ?? ''); ?>" maxlength="255" placeholder="Cth: Ibu, Kakak, Sahabat, Guru BK">
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="no-print mt-8 flex justify-end">
                    <button type="submit" class="primary-bg text-white px-8 py-3 rounded-xl hover-bg-primary font-bold text-lg transition shadow-xl">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan Data Profiling
                    </button>
                </div>
                
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', () => toggleAccordion(header));
            });
            
            document.querySelector('input[name="url_foto"]').addEventListener('change', previewImage);
            
            document.getElementById('instructionModal').addEventListener('click', (e) => {
                if (e.target.id === 'instructionModal') {
                    closeInstructions();
                }
            });
            
            document.getElementById('riwayat_sma_smk_ma').onfocus = (e) => showInstructions('pendidikan', e);
            document.getElementById('riwayat_smp_mts').onfocus = (e) => showInstructions('pendidikan', e);
            document.getElementById('riwayat_sd_mi').onfocus = (e) => showInstructions('pendidikan', e);
            document.getElementById('prestasi_pengalaman').onfocus = (e) => showInstructions('prestasi', e);
            document.getElementById('organisasi').onfocus = (e) => showInstructions('prestasi', e);

            const firstHeader = document.querySelector('.accordion-header');
            if(firstHeader) {
                 firstHeader.nextElementSibling.classList.remove('collapsed');
                 firstHeader.querySelector('.accordion-icon').classList.add('rotated');
            }
        });
            </script>
            

        </div>
    </section>

    <div id="instructionModal" class="fixed inset-0 hidden items-center justify-center z-[9999]">
        <div id="modalContent" class="bg-white p-6 md:p-8 rounded-xl shadow-2xl max-w-lg w-full transform scale-90 opacity-0 transition-all duration-300">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h4 class="text-xl font-bold text-gray-800 primary-color"><i class="fas fa-book-open mr-2"></i> Tata Cara Pengisian</h4>
                <button id="closeModalButton" onclick="closeInstructions()" class="text-gray-500 hover:text-red-500 text-2xl disabled:opacity-50 disabled:cursor-wait" disabled>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="PendidikanInstructions" style="display:none;">
                <p class="text-gray-700 mb-4 text-sm font-semibold">Anda sedang mengisi Riwayat Pendidikan. Gunakan format berikut:</p>
                
                <div class="space-y-4">
                    <div>
                        <strong class="text-base text-gray-800 block mb-1">Format Riwayat Pendidikan (SD, SMP, SMK):</strong>
                        <p class="text-sm pl-4 border-l-4 border-indigo-400">Nama Sekolah (Tahun Masuk-Tahun Lulus).<br>Contoh: SMPN 2 Banjarmasin (2020-2023)</p>
                    </div>
                </div>
            </div>

            <div id="PrestasiInstructions" style="display:none;">
                <p class="text-gray-700 mb-4 text-sm font-semibold">Anda sedang mengisi Prestasi atau Pengalaman/Organisasi. Perhatikan format input berikut:</p>
                
                <div class="space-y-4">
                    <div>
                        <strong class="text-base text-gray-800 block mb-1">Format Prestasi / Pengalaman / Organisasi:</strong>
                        <p class="text-sm pl-4 border-l-4 border-indigo-400">Tuliskan setiap Prestasi/Pengalaman/Organisasi dalam baris baru.<br>Gunakan tombol Enter untuk membuat baris baru.<br><br>Contoh Prestasi:<br>Juara 1 Lomba Web Design 2024<br>Juara 3 Olimpiade Matematika 2023</p>
                    </div>
                    <div>
                        <strong class="text-base text-gray-800 block mb-1">Catatan:</strong>
                        <p class="text-sm pl-4 border-l-4 border-indigo-400">Kosongkan jika tidak memiliki riwayat.</p>
                    </div>
                </div>
            </div>

            <button id="closeButtonFooter" onclick="closeInstructions()" class="mt-6 w-full primary-bg text-white py-2 rounded-lg font-semibold transition disabled:bg-gray-400 disabled:cursor-wait" disabled>
                Tutup (Tunggu 5 detik...)
            </button>
        </div>
    </div>

    <footer class="no-print primary-bg text-white text-center py-4 mt-auto">
        <p class="text-sm text-gray-200 font-light">
    &copy; 2025 <span class="font-semibold">Bimbingan Konseling SMKN 2 Banjarmasin</span>
</p>
<p class="text-xs text-gray-400 mt-1">
    Developed by <span class="font-medium">SahDu Team</span>
</p>
    </footer>

</body>
</html>