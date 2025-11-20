<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];

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

$id_hasil_gayabelajar = $siswa['id_hasil_gb'] ?? null;
$id_hasil_gb_js = json_encode($id_hasil_gayabelajar);

$id_hasil_kemampuan = $siswa['id_hasil_kemampuan'] ?? null;
$id_hasil_kemampuan_js = json_encode($id_hasil_kemampuan);

$nama_siswa = isset($siswa['nama']) ? $siswa['nama'] : 'Siswa';

$is_biodata_complete = true;
$required_fields = [

    'tempat_lahir', 
    'tanggal_lahir', 
    'alamat_lengkap', 
    'berat_badan', 
    'tinggi_badan', 
    'agama', 

    'tentang_saya_singkat', 
    'no_telp', 
    'email', 
    'instagram', 
    'riwayat_sma_smk_ma', 
    'riwayat_smp_mts', 
    'riwayat_sd_mi'
];

foreach ($required_fields as $field) {
    if (empty($siswa[$field])) {
        $is_biodata_complete = false;
        break;
    }
}

$is_tes_kemampuan_done = !empty($id_hasil_kemampuan); 
$is_tes_gayabelajar_done = $siswa['skor_visual'] !== null;
$is_tes_kepribadian_done = !empty($siswa['hasil_tes_kepribadian']); 

$status_biodata_js = $is_biodata_complete ? 'true' : 'false';
$status_kemampuan_js = $is_tes_kemampuan_done ? 'true' : 'false';
$status_gayabelajar_js = $is_tes_gayabelajar_done ? 'true' : 'false';

?>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
        .primary-color {
            color: #2F6C6E;
        }
        .primary-bg {
            background-color: #2F6C6E;
        }
        .primary-border {
            border-color: #2F6C6E;
        }

        .fade-slide {
            transition: all 0.25s ease-in-out;
            transform-origin: top;
        }
        .hidden-transition {
            opacity: 0;
            transform: scaleY(0);
            pointer-events: none;
        }
        .visible-transition {
            opacity: 1;
            transform: scaleY(1);
            pointer-events: auto;
        }
        
        .test-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid #E5E7EB; 
            position: relative;
        }

        .test-card:not(.test-card-locked):not(.test-card-done):not(.test-card-biodata-locked):hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: #2F6C6E;
        }
        
        .card-link {
            display: block;
            text-decoration: none;
        }
        .card-link:hover .card-icon {
            transform: scale(1.05);
        }
        
        .test-card-done {
            background-color: #F0FFF4; 
            border: 2px solid #38A169; 
            cursor: pointer;
        }
        .test-card-done .card-icon {
            color: #38A169; 
        }
        .test-card-done:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .test-card-done .status-label {
            background-color: #38A169;
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
        
        .test-card-locked, .test-card-biodata-locked {
            filter: grayscale(80%);
            opacity: 0.6;
            cursor: not-allowed;
            transition: all 0.3s;
            background-color: #F9FAFB;
        }
        .test-card-locked:hover, .test-card-biodata-locked:hover {
            box-shadow: none !important;
            transform: none !important;
        }
        .lock-overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 0.75rem;
            pointer-events: none;
        }
        @media (max-width: 767px) {
            .py-3.px-4.primary-color {
                font-size: 1rem;
            }
        }
    </style>

    <script>
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

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.card-link').forEach(card => {
                const isTestDone = card.dataset.testStatus === 'true';
                const testName = card.dataset.testName;
                
                if (isTestDone && testName === 'Tes Gaya Belajar') {
                    
                    let finalUrl = 'tes_gayabelajar.php'; 

                    if (ID_HASIL_GAYABELAJAR) {
                        finalUrl = 'hasil_gayabelajar.php?id_hasil=' + ID_HASIL_GAYABELAJAR;
                    } 

                    card.setAttribute('href', finalUrl); 

                    if (finalUrl.includes('hasil_gayabelajar.php')) {
                        return; 
                    } 
                } 
                
                if (isTestDone && testName === 'Tes Kemampuan') {
                    
                    let finalUrl = 'tes_kemampuan.php'; 

                    if (ID_HASIL_KEMAMPUAN) {
                        finalUrl = 'hasil_kemampuan.php?id_hasil=' + ID_HASIL_KEMAMPUAN;
                    } 

                    card.setAttribute('href', finalUrl); 

                    if (finalUrl.includes('hasil_kemampuan.php')) {
                        return; 
                    } 
                }
                
                if (isTestDone && (testName === 'Tes Kepribadian' || testName === 'Tes Asesmen Awal')) { 
                    card.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (confirm('Anda sudah mengerjakan ' + testName + '. Apakah Anda ingin melihat hasil tes Anda sekarang?')) {
                            let redirectUrl = card.getAttribute('href'); 
                            window.location.href = redirectUrl; 
                        }
                    });
                } 
                
                if (!IS_BIODATA_COMPLETE) {
                    card.addEventListener('click', e => {
                        if (card.classList.contains('test-card-biodata-locked')) {
                            e.preventDefault();
                            e.stopPropagation();
                            alert('Akses terkunci. Harap lengkapi Data Profiling Anda terlebih dahulu.');
                        }
                    });
                }
            });
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
            <a href="dashboard.php" class="primary-color font-semibold hover:text-green-700 transition">Beranda</a>
            <a href="data_profiling.php" class="primary-color hover:text-green-700 transition">Data Profiling</a>
            <a href="ganti_password.php" class="primary-color hover:text-green-700 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">Logout</button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20"></div>

    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[60px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-left text-base">
        <a href="dashboard.php" class="py-3 px-4 primary-color bg-gray-50 font-semibold transition">Beranda</a>
        <hr class="border-gray-200 w-full">
        <a href="data_profiling.php" class="py-3 px-4 primary-color transition">Data Profiling</a>
        <hr class="border-gray-200 w-full">
        <a href="ganti_password.php" class="py-3 px-4 primary-color transition">Ganti Password</a>
        <hr class="border-gray-200 w-full">
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm">Logout</button>
    </div>

    <section class="text-center py-10 md:py-16 primary-bg text-white shadow-xl">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            Selamat Datang, <?php echo htmlspecialchars($nama_siswa); ?>!
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Akses cepat ke Tes Minat Bakat Anda. Mari kita mulai!
        </p>
    </section>

    <section class="py-12 md:py-16 px-4 flex-grow">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-8 primary-color">Pilih Tes Minat Bakat Anda</h2>
        
        <?php if (!$is_biodata_complete): ?>
        <div class="max-w-7xl mx-auto bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-8 rounded-lg shadow-xl" role="alert">
            <div class="flex items-start">
                <div class="py-1"><i class="fas fa-exclamation-triangle mr-3 text-2xl"></i></div>
                <div>
                    <p class="font-bold text-lg">AKSES TES TERKUNCI!</p>
                    <p class="text-sm">Anda wajib melengkapi/mengisi data data anda dahulu di menu 
                        <a href="data_profiling.php" class="font-bold underline text-red-800 hover:text-red-900 transition">Data Profiling</a> sebelum dapat mengakses tes.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
            
            <?php 
            $kemampuan_href = $is_tes_kemampuan_done 
                ? 'hasil_kemampuan.php?id_hasil=' . $id_hasil_kemampuan 
                : 'tes_kemampuan.php';
            $card_class_kemampuan = $is_tes_kemampuan_done ? 'test-card-done' : ($is_biodata_complete ? 'primary-border' : 'test-card-biodata-locked'); 
            ?>
            <a href="<?php echo $kemampuan_href; ?>" 
                data-test-name="Tes Kemampuan" 
                data-test-status="<?php echo $status_kemampuan_js; ?>" 
                class="card-link test-card flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg transition bg-white transform <?php echo $card_class_kemampuan; ?>">
                <i class="fas fa-brain primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kemampuan</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kemampuan dan memilih jurusan yang tepat.
                </p>
                
                <?php if ($is_tes_kemampuan_done): ?>
                    <span class="status-label"><i class="fas fa-check-circle mr-1"></i> Selesai</span>
                    <div class="mt-auto text-sm md:text-base font-bold text-green-600">Lihat Hasil</div>
                <?php elseif (!$is_biodata_complete): ?>
                    <div class="lock-overlay">
                        <div class="text-center">
                            <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Terkunci!</div>
                            <div class="text-xs text-red-800 mt-1 font-semibold">Lengkapi Data Profiling</div>
                        </div>
                    </div>
                    <div class="mt-auto text-sm md:text-base font-bold text-gray-500">Mulai</div>
                <?php else: ?>
                    <div class="mt-auto text-sm md:text-base font-bold primary-color">Mulai</div>
                <?php endif; ?>
            </a>

            <?php $card_class_gayabelajar = $is_tes_gayabelajar_done ? 'test-card-done' : ($is_biodata_complete ? 'primary-border' : 'test-card-biodata-locked'); ?>
            <a href="tes_gayabelajar.php" 
                data-test-name="Tes Gaya Belajar" 
                data-test-status="<?php echo $status_gayabelajar_js; ?>"
                class="card-link test-card flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg transition bg-white transform <?php echo $card_class_gayabelajar; ?>">
                <i class="fas fa-palette primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Gaya Belajar</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik). Dengan mengetahui gaya belajar, proses belajar Anda akan lebih maksimal.
                </p>
                
                <?php if ($is_tes_gayabelajar_done): ?>
                    <span class="status-label"><i class="fas fa-check-circle mr-1"></i> Selesai</span>
                    <div class="mt-auto text-sm md:text-base font-bold text-green-600">Lihat Hasil</div>
                <?php elseif (!$is_biodata_complete): ?>
                    <div class="lock-overlay">
                        <div class="text-center">
                            <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Terkunci!</div>
                            <div class="text-xs text-red-800 mt-1 font-semibold">Lengkapi Data Profiling</div>
                        </div>
                    </div>
                    <div class="mt-auto text-sm md:text-base font-bold text-gray-500">Mulai</div>
                <?php else: ?>
                    <div class="mt-auto text-sm md:text-base font-bold primary-color">Mulai</div>
                <?php endif; ?>
            </a>

            <a href="#" 
                class="card-link test-card test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-user-shield primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kepribadian</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Tes ini bertujuan membantu Anda mengenal lebih dalam tipe kepribadian, kekuatan, dan potensi tantangan Anda.
                </p>
                <div class="lock-overlay">
                    <div class="text-center">
                        <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                        <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Segera Hadir</div>
                    </div>
                </div>
            </a>

            <a href="#" 
                class="card-link test-card test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-clipboard-list primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Asesmen Awal</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Asesmen awal untuk mengidentifikasi kebutuhan bimbingan dan konseling spesifik Anda.
                </p>
                <div class="lock-overlay">
                    <div class="text-center">
                        <i class="fas fa-lock text-red-600 text-3xl mb-1"></i>
                        <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">Segera Hadir</div>
                    </div>
                </div>
            </a>

        </div>
    </section>

    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>