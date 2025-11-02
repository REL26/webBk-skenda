<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];
$query = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa='$id_siswa'");
$siswa = mysqli_fetch_assoc($query);

$nama_siswa = isset($siswa['nama']) ? $siswa['nama'] : 'Siswa';
?>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bimbingan Konseling SMKN 2 Banjarmasin</title>
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

        /* Transisi buka tutup menu */
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

        /* Tes yang belum aktif */
        .test-card-locked {
            filter: grayscale(100%);
            opacity: 0.7;
            cursor: not-allowed;
            transition: all 0.3s;
        }
        .test-card-locked:hover {
            box-shadow: none !important;
            transform: none !important;
        }
        .card-link:hover .card-icon {
            transform: scale(1.1);
        }
    </style>

    <script>
        const TRANSITION_DURATION = 250;

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

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.test-card-locked').forEach(card => {
                card.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Maaf, tes ini belum tersedia atau masih dalam tahap pengembangan.');
                });
            });

            document.getElementById('menuOverlay').addEventListener('click', toggleMenu);
        });
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800">

    <!-- Header -->
    <header class="flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-md relative z-30">
        <div>
            <strong class="text-lg md:text-xl primary-color">Bimbingan Konseling</strong><br>
            <small class="text-xs md:text-sm text-gray-600">SMKN 2 BJM</small>
        </div>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="primary-color font-semibold hover:text-green-700 transition">Beranda</a>
            <a href="#" class="primary-color hover:text-green-700 transition">Data Profiling</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">Logout</button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <!-- Overlay -->
    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20"></div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[60px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-left text-base">
        <a href="dashboard.php" class="py-3 px-4 primary-color font-semibold transition">Beranda</a>
        <hr class="border-gray-200 w-full">
        <a href="#" class="py-3 px-4 primary-color transition">Data Profiling</a>
        <hr class="border-gray-200 w-full">
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm">Logout</button>
    </div>

    <!-- Hero Section -->
    <section class="text-center py-10 md:py-16 primary-bg text-white">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
            Selamat Datang, <?php echo htmlspecialchars($nama_siswa); ?>!
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
            Bimbingan Konseling SMKN 2 Banjarmasin hadir untuk mendukung perkembangan pribadi, sosial, dan kariermu.
        </p>
    </section>

    <!-- Tes Section -->
    <section class="py-12 md:py-16 px-4">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-8 primary-color">Pilih Tes Minat Bakat Anda</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">

            <a href="tes_kemampuan.php" class="card-link flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg hover:shadow-xl transition bg-white hover:bg-gray-50 transform hover:-translate-y-1">
                <i class="fas fa-brain primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kemampuan</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kecerdasan majemuk dan memilih jurusan yang tepat.
                </p>
                <div class="mt-auto text-sm md:text-base font-bold primary-color">Mulai</div>
            </a>

            <a href="tes_gayabelajar.php" class="card-link flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg hover:shadow-xl transition bg-white hover:bg-gray-50 transform hover:-translate-y-1">
                <i class="fas fa-palette primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Gaya Belajar</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik). Dengan mengetahui gaya belajar, proses belajar Anda akan lebih maksimal.
                </p>
                <div class="mt-auto text-sm md:text-base font-bold primary-color">Mulai</div>
            </a>

            <div class="test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-user-shield primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kepribadian</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Est sit error vero odit voluptatibus minima, quo tempore exercitationem magni reiciendis.
                </p>
                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">
                    <i class="fas fa-lock mr-2"></i> Segera Hadir
                </div>
            </div>

            <div class="test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-clipboard-list primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Asesmen Awal</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">
                    Lorem ipsum dolor, sit amet consectetur adipisicing elit. Itaque voluptates quisquam minima fugiat ea, tempora ducimus consequatur.
                </p>
                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600">
                    <i class="fas fa-lock mr-2"></i> Segera Hadir
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>
