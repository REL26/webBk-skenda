<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];

// Ambil data siswa dengan LEFT JOIN untuk hasil tes yang mungkin NULL
$query = mysqli_query($koneksi, "
    SELECT 
        s.*,
        hg.id_hasil AS id_hasil_gb, 
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik,
        hk.id_hasil AS id_hasil_kemampuan
    FROM siswa s
    LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
    LEFT JOIN hasil_kecerdasan hk ON s.id_siswa = hk.id_siswa
    WHERE s.id_siswa='$id_siswa'
");
$siswa = mysqli_fetch_assoc($query);

// Data untuk JavaScript
$id_hasil_gayabelajar = $siswa['id_hasil_gb'] ?? null;
$id_hasil_gb_js = json_encode($id_hasil_gayabelajar);

$id_hasil_kemampuan = $siswa['id_hasil_kemampuan'] ?? null;
$id_hasil_kemampuan_js = json_encode($id_hasil_kemampuan);

$nama_siswa = isset($siswa['nama']) ? $siswa['nama'] : 'Siswa';

// Cek Kelengkapan Biodata
$is_biodata_complete = true;
$required_fields = [
    'nama_panggilan', 'tempat_lahir', 'tanggal_lahir', 'alamat_lengkap', 'berat_badan', 
    'tinggi_badan', 'agama', 'hobi_kegemaran', 'anak_ke', 'suku', 'cita_cita', 
    'no_telp', 'email', 'instagram', 'tentang_saya_singkat', 'riwayat_sma_smk_ma', 
    'riwayat_smp_mts', 'riwayat_sd_mi', 'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 
    'pekerjaan_ibu', 'no_hp_ortu', 'status_tempat_tinggal', 'jarak_ke_sekolah', 
    'transportasi_ke_sekolah', 'memiliki_hp_laptop', 'fasilitas_internet', 
    'fasilitas_belajar_dirumah', 'pelajaran_disenangi', 'pelajaran_tdk_disenangi', 
    'buku_pelajaran_dimiliki', 'bahasa_sehari_hari', 'bahasa_asing_dikuasai', 
    'tempat_curhat', 'kelebihan_diri', 'kekurangan_diri'
];

foreach ($required_fields as $field) {
    if (empty($siswa[$field])) {
        $is_biodata_complete = false;
        break;
    }
}

// Cek Status Tes
$is_tes_kemampuan_done = !empty($id_hasil_kemampuan); 
$is_tes_gayabelajar_done = $siswa['skor_visual'] !== null;
$is_tes_kepribadian_done = !empty($siswa['hasil_tes_kepribadian']); 
$is_tes_asesmen_done = false; 

// Data Status untuk JavaScript
$status_biodata_js = $is_biodata_complete ? 'true' : 'false';
$status_kemampuan_js = $is_tes_kemampuan_done ? 'true' : 'false';
$status_gayabelajar_js = $is_tes_gayabelajar_done ? 'true' : 'false';
$status_kepribadian_js = $is_tes_kepribadian_done ? 'true' : 'false';
$status_asesmen_js = $is_tes_asesmen_done ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Definisi Warna Utama */
        :root {
            --primary-color: #2F6C6E; /* Hijau Tua Khas */
            --secondary-color: #38A169; /* Hijau Sukses */
            --overlay-color: rgba(47, 108, 110, 0.85); /* 85% opacity dari primary-color */
        }

        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .primary-border { border-color: var(--primary-color); }

        /* Style Baru untuk Hero Section dengan Gambar Latar Belakang */
        #hero-section {
            /* ======================================================= */
            /* !!! GANTI 'URL_GAMBAR_LATAR_BELAKANG_ANDA' DI BAWAH !!! */
            /* ======================================================= */
            background-image: url('https://assets-a1.kompasiana.com/items/album/2016/05/25/1459049shutterstock-140079079780x390-57452e9ef37a6148061f8f95.jpg'); 
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        /* Overlay Transparan Gelap */
        #hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-color: var(--overlay-color); 
            z-index: 1; 
        }

        /* Pastikan konten teks berada di atas overlay */
        #hero-content {
            position: relative;
            z-index: 2;
        }
        
        /* Transisi Smooth untuk Menu Mobile */
        .fade-slide {
            transition: all 0.3s ease-in-out;
            transform-origin: top;
        }
        .hidden-transition {
            opacity: 0;
            transform: scaleY(0.95);
            max-height: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .visible-transition {
            opacity: 1;
            transform: scaleY(1);
            max-height: 500px; 
            pointer-events: auto;
        }
        
        /* Kartu Tes */
        .test-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid #E5E7EB; 
            position: relative;
        }

        /* Efek Hover untuk Kartu yang Bisa Diakses */
        .card-active:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        .card-active:hover .card-icon {
            transform: scale(1.05);
            color: var(--primary-color);
        }
        
        /* Status Selesai */
        .test-card-done {
            background-color: #F0FFF4; 
            border: 2px solid var(--secondary-color); 
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .test-card-done .card-icon {
            color: var(--secondary-color) !important; 
        }
        .test-card-done:hover {
            transform: scale(1.01);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }
        .test-card-done .status-label {
            background-color: var(--secondary-color);
            color: white;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: bold;
            font-size: 0.75rem;
            position: absolute;
            top: 15px;
            right: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Status Terkunci */
        .test-card-locked, .test-card-biodata-locked {
            filter: grayscale(80%);
            opacity: 0.7;
            cursor: not-allowed;
            background-color: #F9FAFB;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .test-card-locked:hover, .test-card-biodata-locked:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
            transform: none !important;
        }
        .lock-overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 0.75rem;
            pointer-events: none;
        }
    </style>

    <script>
        // Fungsi untuk mengaktifkan/menonaktifkan menu mobile
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
        
        const IS_BIODATA_COMPLETE = <?php echo $status_biodata_js; ?>;
        const ID_HASIL_GAYABELAJAR = <?php echo $id_hasil_gb_js; ?>;
        const ID_HASIL_KEMAMPUAN = <?php echo $id_hasil_kemampuan_js; ?>;
        const IS_TES_KEPRIBADIAN_DONE = <?php echo $status_kepribadian_js; ?>;
        const IS_TES_ASESMEN_DONE = <?php echo $status_asesmen_js; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.card-link').forEach(card => {
                const isTestDone = card.dataset.testStatus === 'true';
                const testName = card.dataset.testName;
                const cardElement = card.querySelector('.test-card');

                // Tentukan URL/Aksi Berdasarkan Status
                let finalUrl = card.getAttribute('href'); 

                if (isTestDone) {
                    // Update Link ke Halaman Hasil Jika Sudah Selesai
                    if (testName === 'Tes Gaya Belajar' && ID_HASIL_GAYABELAJAR) {
                        finalUrl = 'hasil_gayabelajar.php?id_hasil=' + ID_HASIL_GAYABELAJAR;
                        card.setAttribute('href', finalUrl);
                    } else if (testName === 'Tes Kemampuan' && ID_HASIL_KEMAMPUAN) {
                        finalUrl = 'hasil_kemampuan.php?id_hasil=' + ID_HASIL_KEMAMPUAN;
                        card.setAttribute('href', finalUrl);
                    }
                    
                    // Tambahkan Interaksi Konfirmasi Hasil untuk Tes yang "Sudah Selesai"
                    card.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (confirm('Anda sudah mengerjakan ' + testName + '. Apakah Anda ingin melihat hasil tes Anda sekarang?')) {
                            window.location.href = finalUrl; 
                        }
                    });
                    
                } else if (!IS_BIODATA_COMPLETE && !cardElement.classList.contains('test-card-locked')) {
                    // Blokir Tes Jika Biodata Belum Lengkap (kecuali yang sudah locked)
                    cardElement.classList.add('test-card-biodata-locked');
                } else if (!cardElement.classList.contains('test-card-locked')) {
                     // Tambahkan kelas hover untuk kartu yang aktif
                    cardElement.classList.add('card-active');
                }


                // Tambahkan Interaksi Peringatan Akses Terkunci
                if (cardElement.classList.contains('test-card-biodata-locked')) {
                    card.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        alert('Akses terkunci! Anda wajib melengkapi data di menu "Data Profiling" terlebih dahulu.');
                    });
                }
                
                // Tambahkan Interaksi Peringatan Coming Soon
                if (cardElement.classList.contains('test-card-locked')) {
                     card.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        alert(testName + ' akan segera hadir! Mohon tunggu informasi lebih lanjut.');
                    });
                }
            });
        });
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-lg relative z-30">
        <a href="dashboard.php" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <div>
                <strong class="text-base md:text-xl primary-color font-extrabold">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-gray-600">Bimbingan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="primary-color font-bold border-b-2 border-transparent border-primary-color pb-1 transition underline">Beranda</a>
            <a href="data_profiling.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Data Profiling</a>
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

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20 transition-opacity duration-300" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex flex-col text-left text-base border-t border-gray-100">
        <a href="dashboard.php" class="py-3 px-4 primary-color bg-gray-100 font-bold transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>

    <section id="hero-section" class="text-center py-16 md:py-28 text-white shadow-2xl">
        
        <div id="hero-content" class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl md:text-5xl font-extrabold mb-3 md:mb-4">
                <i class="fas fa-hand-wave mr-2"></i> Selamat Datang, Contoh 1
            </h1>
            <p class="text-lg md:text-xl font-light mb-8 md:mb-10">
                Halo **<?php echo htmlspecialchars($nama_siswa); ?>**, temukan potensi terbaik dan arah masa depan Anda di sini!
            </p>


        </div>
    </section>

    <section id="test-section" class="py-12 md:py-16 px-4 flex-grow container mx-auto">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-10 primary-color border-b-2 border-gray-200 pb-3">
            Pilih Tes Minat Bakat Anda
        </h2>
        
        <?php if (!$is_biodata_complete): ?>
        <div class="max-w-4xl mx-auto bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-10 rounded-lg shadow-xl" role="alert">
            <div class="flex items-start">
                <div class="pt-1"><i class="fas fa-lock mr-3 text-3xl"></i></div>
                <div>
                    <p class="font-bold text-lg">AKSES TES TERKUNCI!</p>
                    <p class="text-sm">Anda **wajib** melengkapi semua data di menu 
                        <a href="data_profiling.php" class="font-extrabold underline text-red-700 hover:text-red-800 transition">Data Profiling</a> sebelum dapat memulai tes minat bakat.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 max-w-7xl mx-auto">
            
            <?php 
            $kemampuan_href = $is_tes_kemampuan_done 
                ? 'hasil_kemampuan.php?id_hasil=' . $id_hasil_kemampuan 
                : 'tes_kemampuan.php';
            $card_class_kemampuan = $is_tes_kemampuan_done ? 'test-card-done' : ($is_biodata_complete ? 'card-active' : 'test-card-biodata-locked'); 
            ?>
            <a href="<?php echo $kemampuan_href; ?>" 
                data-test-name="Tes Kemampuan" 
                data-test-status="<?php echo $status_kemampuan_js; ?>" 
                class="card-link h-full block">
                <div class="test-card flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg bg-white transform <?php echo $card_class_kemampuan; ?>">
                    <i class="fas fa-brain primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                    <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kemampuan</h4>
                    <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                        Mengukur potensi kognitif dan akademik. Membantu Anda memahami kemampuan dasar dan memilih bidang studi/jurusan yang sesuai.
                    </p>
                    
                    <?php if ($is_tes_kemampuan_done): ?>
                        <span class="status-label"><i class="fas fa-check-circle mr-1"></i> Selesai</span>
                        <div class="mt-auto text-sm md:text-base font-bold text-green-600 pt-2">Lihat Hasil <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php elseif (!$is_biodata_complete): ?>
                        <div class="lock-overlay">
                            <div class="text-center">
                                <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Terkunci!</div>
                                <div class="text-xs text-red-800 mt-1 font-semibold">Lengkapi Data Profiling</div>
                            </div>
                        </div>
                        <div class="mt-auto text-sm md:text-base font-bold text-gray-500 pt-2">Mulai <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php else: ?>
                        <div class="mt-auto text-sm md:text-base font-bold primary-color pt-2">Mulai Tes <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php endif; ?>
                </div>
            </a>

            <?php 
            $gayabelajar_href = $is_tes_gayabelajar_done 
                ? 'hasil_gayabelajar.php?id_hasil=' . $id_hasil_gayabelajar 
                : 'tes_gayabelajar.php';
            $card_class_gayabelajar = $is_tes_gayabelajar_done ? 'test-card-done' : ($is_biodata_complete ? 'card-active' : 'test-card-biodata-locked'); 
            ?>
            <a href="<?php echo $gayabelajar_href; ?>" 
                data-test-name="Tes Gaya Belajar" 
                data-test-status="<?php echo $status_gayabelajar_js; ?>"
                class="card-link h-full block">
                <div class="test-card flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg bg-white transform <?php echo $card_class_gayabelajar; ?>">
                    <i class="fas fa-palette primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                    <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Gaya Belajar</h4>
                    <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                        Mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik) untuk memaksimalkan proses belajar.
                    </p>
                    
                    <?php if ($is_tes_gayabelajar_done): ?>
                        <span class="status-label"><i class="fas fa-check-circle mr-1"></i> Selesai</span>
                        <div class="mt-auto text-sm md:text-base font-bold text-green-600 pt-2">Lihat Hasil <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php elseif (!$is_biodata_complete): ?>
                        <div class="lock-overlay">
                            <div class="text-center">
                                <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Terkunci!</div>
                                <div class="text-xs text-red-800 mt-1 font-semibold">Lengkapi Data Profiling</div>
                            </div>
                        </div>
                        <div class="mt-auto text-sm md:text-base font-bold text-gray-500 pt-2">Mulai <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php else: ?>
                        <div class="mt-auto text-sm md:text-base font-bold primary-color pt-2">Mulai Tes <i class="fas fa-chevron-right ml-1 text-sm"></i></div>
                    <?php endif; ?>
                </div>
            </a>

            <a href="#" 
                data-test-name="Tes Kepribadian" 
                data-test-status="<?php echo $status_kepribadian_js; ?>" 
                class="card-link h-full block">
                <div class="test-card test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                    <i class="fas fa-user-shield text-gray-500 text-6xl md:text-7xl mb-4 md:mb-6"></i>
                    <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kepribadian</h4>
                    <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                        Membantu Anda mengenal lebih dalam tipe kepribadian, kekuatan, dan potensi tantangan Anda di masa depan.
                    </p>
                    <div class="lock-overlay">
                        <div class="text-center">
                            <i class="fas fa-hourglass-half text-blue-500 text-3xl mb-1"></i>
                            <div class="mt-auto text-sm md:text-base font-extrabold text-blue-500">Segera Hadir</div>
                            <div class="text-xs text-blue-600 mt-1 font-semibold">Pantau Terus Informasi</div>
                        </div>
                    </div>
                    <div class="mt-auto text-sm md:text-base font-bold text-gray-500 pt-2">Tidak Tersedia</div>
                </div>
            </a>

            <a href="#" 
                data-test-name="Tes Asesmen Awal" 
                data-test-status="<?php echo $status_asesmen_js; ?>" 
                class="card-link h-full block">
                <div class="test-card test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                    <i class="fas fa-clipboard-list text-gray-500 text-6xl md:text-7xl mb-4 md:mb-6"></i>
                    <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Asesmen Awal</h4>
                    <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                        Asesmen awal untuk mengidentifikasi kebutuhan bimbingan dan konseling spesifik Anda.
                    </p>
                    <div class="lock-overlay">
                        <div class="text-center">
                            <i class="fas fa-hourglass-half text-blue-500 text-3xl mb-1"></i>
                            <div class="mt-auto text-sm md:text-base font-extrabold text-blue-500">Segera Hadir</div>
                            <div class="text-xs text-blue-600 mt-1 font-semibold">Pantau Terus Informasi</div>
                        </div>
                    </div>
                    <div class="mt-auto text-sm md:text-base font-bold text-gray-500 pt-2">Tidak Tersedia</div>
                </div>
            </a>

        </div>
    </section>

    <footer class="text-center py-4 primary-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        <p class="mb-1">
            <i class=" mr-1"></i> © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
        </p>

    </footer>
</body>
</html>
