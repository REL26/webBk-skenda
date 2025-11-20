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

$daftar_agama = ['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

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
                url_foto = '$url_foto_db'
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
    <title>Biodata | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .primary-border { border-color: #2F6C6E; }
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
             border-color: #2F6C6E; 
             outline: none; 
             box-shadow: 0 0 0 2px rgba(47, 108, 110, 0.5);
        }
        .bg-sidebar { background-color: #2F6C6E; }
        
        .fade-slide { transition: all 0.3s ease-in-out; transform-origin: top; }
        .hidden-transition { opacity: 0; transform: scaleY(0); pointer-events: none; }
        .visible-transition { opacity: 1; transform: scaleY(1); pointer-events: auto; }
        
        .section-title { border-left: 4px solid #2F6C6E; padding-left: 1rem; }
        
        .form-input[readonly] {
            background-color: #F3F4F6;
            border-color: #E5E7EB;
            cursor: not-allowed;
        }

        .faded-input-container {
            opacity: 0.65;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .faded-input-container .form-input {
            background-color: #F3F4F6 !important; 
        }

        #instructionModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none; 
            align-items: center;
            justify-content: center;
        }
        #modalContent {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            max-width: 90%;
            width: 600px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            transform: scale(0.9);
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        #instructionModal.active #modalContent {
            transform: scale(1);
            opacity: 1;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <script>
        const PENDIDIKAN_FLAG = 'profilingInstructionsShown_pendidikan';
        const PRESTASI_FLAG = 'profilingInstructionsShown_prestasi';
        const DELAY_MS = 5000;

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

        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);
            
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
        });
        
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-md relative z-30">
        <div>
            <strong class="text-lg md:text-xl primary-color">Bimbingan Konseling</strong><br>
            <small class="text-xs md:text-sm text-gray-600">SMKN 2 BJM</small>
        </div>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="primary-color hover:text-green-700 transition">Beranda</a>
            <a href="data_profiling.php" class="primary-color font-semibold hover:text-green-700 transition">Data Profiling</a>
            <a href="ganti_password.php" class="primary-color hover:text-green-700 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">Logout</button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20"></div>

    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[60px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-left text-base">
        <a href="dashboard.php" class="py-3 px-4 primary-color hover:bg-gray-100 transition">Beranda</a>
        <hr class="border-gray-200 w-full">
        <a href="data_profiling.php" class="py-3 px-4 primary-color font-semibold bg-gray-50 transition">Data Profiling</a>
        <hr class="border-gray-200 w-full">
        <a href="ganti_password.php" class="py-3 px-4 primary-color transition">Ganti Password</a>
        <hr class="border-gray-200 w-full">
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm">Logout</button>
    </div>
    
    <section class="text-center py-8 md:py-12 primary-bg text-white shadow-xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            Edit Data Profiling Siswa
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Lengkapi dan perbarui biodata Anda.
        </p>
    </section>

    <section class="py-8 md:py-10 px-4 flex-grow">
        <div class="max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-xl shadow-2xl border border-gray-200">

            <?php if ($pesan_sukses): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                    <p class="font-bold"><i class="fas fa-check-circle mr-2"></i> Berhasil!</p>
                    <p><?php echo $pesan_sukses; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($pesan_error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                    <p class="font-bold"><i class="fas fa-times-circle mr-2"></i> Gagal!</p>
                    <p><?php echo $pesan_error; ?></p>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-10">

                <div class="text-sm text-red-700 bg-red-100 p-4 rounded-lg border border-red-400 shadow-sm">
                    <i class="fas fa-info-circle mr-2"></i> Mohon diperhatikan! Isi semua data dengan informasi yang benar dan jujur. Kelengkapan data ini sangat penting!
                </div>
                
                <h3 class="text-xl md:text-2xl font-bold text-gray-700 section-title">
                    <i class="fas fa-user-edit mr-2 primary-color"></i> Data Siswa
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 items-start">
                    
                    <div class="col-span-1 md:col-span-1">
                        <label class="form-label">Foto Profil</label>
                        <div class="bg-gray-50 p-4 rounded-xl shadow-inner border border-gray-200">
                            <?php 
$email_hash = md5(strtolower(trim($siswa['email'] ?? 'default@example.com')));
$gravatar_url = "https://www.gravatar.com/avatar/" . $email_hash . "?d=mp&s=200";

if (!empty($siswa['url_foto'])) {
    $url_foto = '../' . $siswa['url_foto'];
} else {
    $url_foto = $gravatar_url;
}
?>

                            <div class="w-full aspect-square overflow-hidden mb-3 border-4 primary-border border-opacity-50 rounded-lg shadow-md">
                                <img id="fotoPreview" src="<?php echo htmlspecialchars($url_foto); ?>" alt="Foto Profil" class="w-full h-full object-cover">
                            </div>
                            
                            <input type="file" id="url_foto" name="url_foto" 
                            class="hidden" 
                            accept=".png,.jpg,.jpeg"
                            onchange="previewImage(event)">
                            
                            <label for="url_foto" class="cursor-pointer bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold py-2 px-4 rounded-lg transition flex items-center shadow-md justify-center">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> Ubah Foto (Max 5MB)
                            </label>

                            <input type="hidden" name="current_url_foto" value="<?php echo htmlspecialchars($siswa['url_foto']); ?>">
                            <p class="text-xs text-gray-500 mt-2 text-center">Format: JPG, JPEG, PNG</p>
                            <p class="text-xs text-gray-500 mt-2 text-center">Disarankan aspek rasio 1:1</p>
                        </div>
                    </div>

                    <div class="md:col-span-3 space-y-6">
                        <div class="bg-blue-50 p-5 rounded-xl border-2 border-blue-200 shadow-inner">
                            <h4 class="text-lg font-bold text-blue-700 border-b pb-2 mb-3">Data Siswa (Tidak Dapat Diubah)</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div><span class="font-medium text-gray-600">Nama Lengkap:</span> <strong class="text-gray-900"><?php echo $siswa['nama']; ?></strong></div>
                                <div><span class="font-medium text-gray-600">NIS:</span> <strong class="text-gray-900"><?php echo $siswa['nis']; ?></strong></div>
                                <div><span class="font-medium text-gray-600">Kelas/Jurusan:</span> <strong class="text-gray-900"><?php echo $kelas_jurusan; ?></strong></div>
                                <div><span class="font-medium text-gray-600">Jenis Kelamin:</span> <strong class="text-gray-900"><?php echo ($siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'); ?></strong></div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-3">
                            <h4 class="text-lg font-semibold text-gray-700 border-b pb-1">Data Pribadi (Dapat Diubah)</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="nama_panggilan" class="form-label">Nama Panggilan <span class="text-red-500">*</span></label>
                                    <input type="text" id="nama_panggilan" name="nama_panggilan" class="form-input" value="<?php echo htmlspecialchars($siswa['nama_panggilan'] ?? ''); ?>" placeholder="Contoh: Budi" required>
                                </div>
                                <div>
                                    <label for="agama" class="form-label">Agama <span class="text-red-500">*</span></label>
                                    <select id="agama" name="agama" class="form-select" required>
                                        <option value="">-- Pilih Agama --</option>
                                        <?php foreach ($daftar_agama as $agama_item): ?>
                                            <option value="<?php echo $agama_item; ?>" <?php echo ($agama_item == $agama_val) ? 'selected' : ''; ?>>
                                                <?php echo $agama_item; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="tempat_lahir" class="form-label">Tempat Lahir <span class="text-red-500">*</span></label>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-input" value="<?php echo $tempat_lahir_val; ?>" placeholder="Contoh: Banjarmasin" required>
                                </div>
                                <div>
                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-red-500">*</span></label>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-input" value="<?php echo $tanggal_lahir_val; ?>" title="Pilih tanggal lahir Anda" required>
                                </div>
                            </div>
                            <div>
                                <label for="alamat_lengkap" class="form-label">Alamat Lengkap <span class="text-red-500">*</span></label>
                                <textarea id="alamat_lengkap" name="alamat_lengkap" class="form-textarea min-h-[80px] max-h-[150px]" placeholder="Masukkan alamat lengkap Anda (Jalan, RT/RW, Kelurahan, Kecamatan)..." required><?php echo htmlspecialchars($siswa['alamat_lengkap'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-4">
                        <h3 class="text-xl font-bold text-gray-700 section-title">
                            <i class="fas fa-phone mr-2 primary-color"></i> Kontak & Medsos
                        </h3>
                        <div>
                            <label for="no_telp" class="form-label"><i class="fab fa-whatsapp text-green-500 mr-1"></i> No. HP / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" id="no_telp" name="no_telp" class="form-input" value="<?php echo htmlspecialchars($siswa['no_telp'] ?? ''); ?>" placeholder="Cth: 0812xxxxxxxx" required>
                        </div>
                        <div>
                            <label for="email" class="form-label"><i class="fas fa-envelope text-blue-500 mr-1"></i> Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($siswa['email'] ?? ''); ?>" placeholder="email@contoh.com" required>
                        </div>
                        <div>
                            <label for="instagram" class="form-label"><i class="fab fa-instagram text-pink-500 mr-1"></i> Instagram Username <span class="text-red-500">*</span></label>
                            <input type="text" id="instagram" name="instagram" class="form-input" value="<?php echo htmlspecialchars($siswa['instagram'] ?? ''); ?>" placeholder="Cth: @usernameanda" required>
                        </div>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <h3 class="text-xl font-bold text-gray-700 section-title">
                            <i class="fas fa-ruler-vertical mr-2 primary-color"></i> Fisik & Hobi
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="tinggi_badan" class="form-label">Tinggi Badan (cm) <span class="text-red-500">*</span></label>
                                <input type="number" step="1" id="tinggi_badan" name="tinggi_badan" class="form-input" value="<?php echo htmlspecialchars($siswa['tinggi_badan'] ?? ''); ?>" placeholder="Cth: 170" required>
                            </div>
                            <div>
                                <label for="berat_badan" class="form-label">Berat Badan (kg) <span class="text-red-500">*</span></label>
                                <input type="number" step="1" id="berat_badan" name="berat_badan" class="form-input" value="<?php echo htmlspecialchars($siswa['berat_badan'] ?? ''); ?>" placeholder="Cth: 65" required>
                            </div>
                        </div>
                        <div>
                            <label for="hobi_kegemaran" class="form-label">Hobi / Kegemaran <span class="text-red-500">*</span></label>
                            <input type="text" id="hobi_kegemaran" name="hobi_kegemaran" class="form-input" value="<?php echo htmlspecialchars($siswa['hobi_kegemaran'] ?? ''); ?>" placeholder="Cth: Membaca, Sepak Bola, Coding" required>
                        </div>
                        <div>
                            <label for="tentang_saya_singkat" class="form-label">Tentang Saya (Cerita Singkat - maks 500 karakter) <span class="text-red-500">*</span></label>
                            <textarea id="tentang_saya_singkat" name="tentang_saya_singkat" class="form-textarea min-h-[120px] max-h-[200px]" placeholder="Tuliskan cerita singkat tentang diri Anda, minat, dan tujuan Anda..." required><?php echo htmlspecialchars($siswa['tentang_saya_singkat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                <h3 class="text-xl font-bold text-gray-700 section-title">
                    <i class="fas fa-graduation-cap mr-2 primary-color"></i> Riwayat Pendidikan & Prestasi
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="riwayat_sma_smk_ma" class="form-label">Riwayat SMK <span class="text-red-500">*</span></label>
                        <input type="text" id="riwayat_sma_smk_ma" name="riwayat_sma_smk_ma" class="form-input" 
                                value="<?php echo $riwayat_smk_val; ?>" 
                                placeholder="Contoh: SMKN 2 BJM (2024-2027)"
                                onfocus="showInstructions('pendidikan', event)"
                                required>
                        <p class="text-xs text-gray-500 mt-1">Klik untuk lihat format pengisian.</p>
                    </div>
                    <div>
                        <label for="riwayat_smp_mts" class="form-label">Riwayat SMP/MTs <span class="text-red-500">*</span></label>
                        <input type="text" id="riwayat_smp_mts" name="riwayat_smp_mts" class="form-input" 
                                value="<?php echo htmlspecialchars($siswa['riwayat_smp_mts'] ?? ''); ?>" 
                                placeholder="Contoh: SMPN X (2017-2020)" 
                                onfocus="showInstructions('pendidikan', event)"
                                required>
                    </div>
                    <div>
                        <label for="riwayat_sd_mi" class="form-label">Riwayat SD/MI <span class="text-red-500">*</span></label>
                        <input type="text" id="riwayat_sd_mi" name="riwayat_sd_mi" class="form-input" 
                                value="<?php echo htmlspecialchars($siswa['riwayat_sd_mi'] ?? ''); ?>" 
                                placeholder="Contoh: SDN X (2011-2017)" 
                                onfocus="showInstructions('pendidikan', event)"
                                required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="prestasi_pengalaman" class="form-label">Prestasi & Pengalaman (Lomba/Olimpiade)</label>
                        <textarea id="prestasi_pengalaman" name="prestasi_pengalaman" class="form-textarea min-h-[120px] max-h-[300px]" 
                                  placeholder="Tuliskan daftar prestasi Anda, pisahkan dengan baris baru."
                                  onfocus="showInstructions('prestasi', event)"><?php echo htmlspecialchars($siswa['prestasi_pengalaman'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Opsional: Klik untuk lihat format pengisian. Gunakan Enter untuk baris baru.</p>
                    </div>
                    <div>
                        <label for="organisasi" class="form-label">Organisasi di Sekolah / Eksternal</label>
                        <textarea id="organisasi" name="organisasi" class="form-textarea min-h-[120px] max-h-[300px]" 
                                  placeholder="Tuliskan daftar organisasi yang diikuti, pisahkan dengan baris baru."
                                  onfocus="showInstructions('prestasi', event)"><?php echo htmlspecialchars($siswa['organisasi'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Opsional: Klik untuk lihat format pengisian. Gunakan Enter untuk baris baru.</p>
                    </div>
                </div>

                <hr class="border-gray-200">

                <h3 class="text-xl font-bold text-gray-700 section-title">
                    <i class="fas fa-clipboard-check mr-2 primary-color"></i> Hasil Tes BK
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50 p-5 rounded-xl border border-gray-200 shadow-inner mb-10">
                    <div class="p-3 bg-white rounded-lg border border-gray-300">
                        <label class="form-label text-sm text-gray-500">Hasil Tes Kemampuan</label>
                        <input type="text" class="form-input bg-gray-200 font-medium border-gray-400 text-gray-700" value="<?php echo htmlspecialchars($hasil_tes_kemampuan_calculated); ?>" readonly>
                        <p class="text-xs text-red-500 mt-1">Diambil dari tes yang Anda kerjakan.</p>
                    </div>
                    <div class="p-3 bg-white rounded-lg border border-gray-300">
                        <label class="form-label text-sm text-gray-500">Gaya Belajar Dominan</label>
                        <input type="text" class="form-input bg-gray-200 text-gray-700 font-medium" value="<?php echo htmlspecialchars($gaya_belajar); ?>" readonly>
                        <p class="text-xs text-red-500 mt-1">Diambil dari tes yang Anda kerjakan.</p>
                    </div>
                    
                    <?php $is_kepribadian_empty = empty($siswa['hasil_tes_kepribadian'] ?? ''); ?>
                    <div class="p-3 bg-white rounded-lg border border-gray-300 
                        <?php echo $is_kepribadian_empty ? 'no-print faded-input-container' : ''; ?>">
                        <label class="form-label text-sm text-gray-500">Hasil Tes Kepribadian</label>
                        <input type="text" class="form-input font-medium" 
                               value="<?php echo $is_kepribadian_empty ? 'segera hadir' : htmlspecialchars($siswa['hasil_tes_kepribadian'] ?? ''); ?>" 
                               readonly>
                        <p class="text-xs mt-1 text-red-500">
                            Diambil dari tes yang Anda kerjakan.
                        </p>
                    </div>
                </div>

                <div class="text-center pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-center space-y-4 sm:space-x-4 sm:space-y-0">
                    <button type="submit" class="primary-bg text-white px-8 py-2 sm:px-12 sm:py-3 rounded-full font-bold hover-bg-primary transition shadow-xl text-base lg:text-lg transform hover:scale-[1.02] active:scale-100">
                        <i class="fas fa-save mr-2"></i> SIMPAN PERUBAHAN
                    </button>
                    <a href="#" id="btnExportCV" class="bg-blue-600 text-white px-8 py-2 sm:px-12 sm:py-3 rounded-full font-bold hover:bg-blue-700 transition shadow-xl text-base lg:text-lg transform hover:scale-[1.02] active:scale-100 flex items-center justify-center">
    <i class="fas fa-file-pdf mr-2"></i> EKSPOR (PDF)
</a>

<script>
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
</script>

                </div>

            </form>

        </div>
    </section>

    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
    
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
</body>
</html>