<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bimbingan Konseling SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .primary-color { color: #2F6C6E; }
        .primary-bg { background-color: #2F6C6E; }
        .primary-border { border-color: #2F6C6E; }

        /* Transisi menu */
        .fade-slide {
            transition: all 0.24s ease-in-out;
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

        /* Card interactions */
        .card-link:hover .card-icon { transform: scale(1.08); }
        .test-card-locked {
            filter: grayscale(100%);
            opacity: 0.75;
            cursor: not-allowed;
            transition: all 0.25s;
        }
        .test-card-locked:hover {
            box-shadow: none !important;
            transform: none !important;
        }
    </style>

    <script>
        const TRANSITION_DURATION = 240; // ms, sinkron dengan CSS

        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;
            if (!menu || !overlay) return;

            const isClosed = menu.classList.contains('hidden-transition');

            if (isClosed) {
                // buka
                menu.classList.remove('hidden-transition');
                menu.classList.add('visible-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            } else {
                // tutup
                menu.classList.remove('visible-transition');
                menu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // test-card locked clicks
            document.querySelectorAll('.test-card-locked').forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Maaf, tes ini belum tersedia atau masih dalam tahap pengembangan.');
                });
            });

            // pasang click handler overlay (defensive: cek dulu elemen ada)
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);

            // optional: tutup menu saat resize > md (agar tidak tersisa terbuka jika resize)
            window.addEventListener('resize', () => {
                const menu = document.getElementById('mobileMenu');
                const overlay = document.getElementById('menuOverlay');
                const body = document.body;
                if (!menu || !overlay) return;
                // jika layar menjadi desktop, pastikan menu off
                if (window.innerWidth >= 768) {
                    menu.classList.remove('visible-transition');
                    menu.classList.add('hidden-transition');
                    overlay.classList.add('hidden');
                    body.classList.remove('overflow-hidden');
                }
            });
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

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center space-x-6">
            <a href="login.php" class="primary-color font-semibold hover:text-green-700 transition">Beranda</a>
            <a href="login.php" class="primary-color hover:text-green-700 transition">Data Profiling</a>
            <button onclick="window.location.href='login.php'" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Login</button>
        </nav>

        <!-- Mobile hamburger -->
        <button aria-label="Buka menu" onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <!-- Overlay (awalnya hidden) -->
    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20"></div>

    <!-- Mobile Menu (status awal: tertutup secara visual via hidden-transition) -->
    <!-- jangan tambahkan class 'hidden' di sini supaya transisi scaleY bisa bekerja -->
    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[64px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-left text-base">
        <a href="login.php" class="py-3 px-4 primary-color font-semibold transition">Beranda</a>
        <hr class="border-gray-200 w-full">
        <a href="login.php" class="py-3 px-4 primary-color transition">Data Profiling</a>
        <hr class="border-gray-200 w-full">
        <button onclick="window.location.href='login.php'" class="bg-blue-500 text-white py-3 hover:bg-blue-700 transition text-sm">Login</button>
    </div>

    <!-- Hero -->
    <section class="text-center py-12 md:py-20 primary-bg text-white">
        <h1 class="text-2xl md:text-4xl font-extrabold mb-3 px-4">
            Selamat Datang di Layanan Bimbingan Konseling
        </h1>
        <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg mb-6 px-4">
            Bimbingan Konseling SMKN 2 Banjarmasin hadir untuk mendukung perkembangan pribadi, sosial, dan kariermu. Masuk untuk mulai menggunakan layanan kami!
        </p>
        <button onclick="window.location.href='login.php'" class="bg-white primary-color font-bold px-6 py-2 md:px-8 md:py-3 rounded-lg hover:bg-gray-100 transition text-sm md:text-base">Mulai Sekarang</button>
    </section>

    <!-- Pilihan Tes -->
    <section id="tes-minat-bakat" class="py-12 md:py-16 px-4 bg-white">
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-8 primary-color">Pilihan Tes Minat Bakat</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
            <a href="login.php" class="card-link flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg hover:shadow-xl transition bg-white hover:bg-gray-50 transform hover:-translate-y-1">
                <i class="fas fa-brain primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kemampuan</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kecerdasan majemuk dan memilih jurusan yang tepat.</p>
                <div class="mt-auto text-sm md:text-base font-bold primary-color">Lihat</div>
            </a>

            <a href="login.php" class="card-link flex flex-col items-center p-6 md:p-8 h-full rounded-xl border primary-border shadow-lg hover:shadow-xl transition bg-white hover:bg-gray-50 transform hover:-translate-y-1">
                <i class="fas fa-palette primary-color text-6xl md:text-7xl mb-4 md:mb-6 card-icon transition-transform"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Gaya Belajar</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik). Dengan mengetahui gaya belajar, proses belajar Anda akan lebih maksimal.</p>
                <div class="mt-auto text-sm md:text-base font-bold primary-color">Lihat</div>
            </a>

            <div class="test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-user-shield primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Kepribadian</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">Lorem ipsum dolor sit amet consectetur adipisicing elit. Hic aperiam veritatis, dolore iste laborum sapiente deleniti possimus sunt dolorum quas.</p>
                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
            </div>

            <div class="test-card-locked flex flex-col items-center p-6 md:p-8 h-full rounded-xl border border-gray-400 shadow-md bg-white relative">
                <i class="fas fa-clipboard-list primary-color text-6xl md:text-7xl mb-4 md:mb-6"></i>
                <h4 class="text-lg md:text-xl font-bold mb-2 text-gray-800 text-center">Tes Asesmen Awal</h4>
                <p class="text-xs md:text-sm text-gray-600 text-center mb-3 flex-grow">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Iste eveniet et perferendis dignissimos molestiae cum harum animi, quisquam nemo? Incidun.</p>
                <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
            </div>
        </div>
    </section>

    <section class="text-center py-12 md:py-16 px-4 bg-gray-50">
        <h2 class="text-2xl md:text-3xl font-bold mb-8 primary-color">Langkah Penggunaan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 max-w-7xl mx-auto">
            <div class="bg-white shadow-lg rounded-xl p-5 flex flex-col justify-between h-auto min-h-[300px] border border-gray-200 hover:shadow-xl transition">
                <div>
                    <i class="fas fa-sign-in-alt primary-color text-5xl md:text-6xl mx-auto mt-2 mb-3"></i>
                    <h3 class="text-lg md:text-xl font-bold mb-2 text-gray-800">1. Login atau Registrasi</h3>
                    <p class="text-xs md:text-base text-gray-600">Masuk menggunakan akun sekolah untuk mulai menggunakan layanan Bimbingan Konseling.</p>
                </div>
                <button onclick="window.location.href='login.php'" class="mt-4 primary-bg text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Akses</button>
            </div>

            <div class="bg-white shadow-lg rounded-xl p-5 flex flex-col justify-between h-auto min-h-[300px] border border-gray-200 hover:shadow-xl transition">
                <div>
                    <i class="fas fa-file-signature primary-color text-5xl md:text-6xl mx-auto mt-2 mb-3"></i>
                    <h3 class="text-lg md:text-xl font-bold mb-2 text-gray-800">2. Isi Tes Minat Bakat</h3>
                    <p class="text-xs md:text-base text-gray-600">Jawab pertanyaan Tes Kemampuan, Gaya Belajar, atau Tes lainnya sesuai dengan instruksi yang diberikan.</p>
                </div>
                <button onclick="window.location.href='login.php'" class="mt-4 primary-bg text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Mulai Tes</button>
            </div>

            <div class="bg-white shadow-lg rounded-xl p-5 flex flex-col justify-between h-auto min-h-[300px] border border-gray-200 hover:shadow-xl transition">
                <div>
                    <i class="fas fa-chart-bar primary-color text-5xl md:text-6xl mx-auto mt-2 mb-3"></i>
                    <h3 class="text-lg md:text-xl font-bold mb-2 text-gray-800">3. Lihat Hasil dan Saran</h3>
                    <p class="text-xs md:text-base text-gray-600">Dapatkan hasil tes yang akurat dan saran yang sesuai untuk mendukung pengembangan akademik dan karir Anda.</p>
                </div>
                <button onclick="window.location.href='login.php'" class="mt-4 primary-bg text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Lihat</button>
            </div>
        </div>
    </section>

    <footer class="text-center py-3 primary-bg text-white text-xs md:text-sm">
        © 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.
    </footer>
</body>
</html>
