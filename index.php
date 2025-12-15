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
        /* Skema Warna: Deep Navy Blue (#0F2A44) */
        :root {
            --primary-color: #0F2A44; 
            --primary-dark: #091C2D; 
            --accent-color: #4CAF50; 
            --header-bg-start: #123E44;
            --header-bg-end: #1F5F63;
            --accent-green-light: #7FBF7A; 
            --mobile-drawer-bg: #2C3A50; /* Warna Gelap untuk Mobile Menu Drawer */
        }
        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .accent-bg { background-color: var(--accent-green-light); }
        
        /* Gaya Kartu Modern (Elevated) */
        .card-base {
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 6px 15px -3px rgba(0, 0, 0, 0.15); 
        }
        .card-base:hover {
            transform: translateY(-8px); 
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.2);
        }

        /* Hero Image Placeholder */
        .hero-background {
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        /* Mobile Menu Drawer Styling */
        .mobile-drawer {
            transition: transform 0.3s ease-in-out;
            transform: translateX(100%); 
        }
        .mobile-drawer.open {
            transform: translateX(0); 
        }
        
        /* Kartu Terkunci */
        .test-card-locked {
            filter: grayscale(80%); 
            opacity: 0.7;
            cursor: not-allowed;
        }
        .test-card-locked:hover {
            box-shadow: 0 6px 15px -3px rgba(0, 0, 0, 0.15) !important;
            transform: none !important;
        }
    </style>

    <script>
        const TRANSITION_DURATION = 300; 

        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;
            if (!menu || !overlay) return;

            const isClosed = menu.classList.contains('mobile-drawer');

            if (isClosed) {
                // BUKA MENU
                menu.classList.remove('mobile-drawer');
                menu.classList.add('open');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
                body.classList.add('overflow-hidden'); 
            } else {
                // TUTUP MENU
                menu.classList.remove('open');
                menu.classList.add('mobile-drawer');
                overlay.classList.remove('opacity-100');
                
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    body.classList.remove('overflow-hidden');
                }, TRANSITION_DURATION);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.test-card-locked').forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Maaf, tes ini belum tersedia atau masih dalam tahap pengembangan.');
                });
            });

            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);

            window.addEventListener('resize', () => {
                const menu = document.getElementById('mobileMenu');
                const overlay = document.getElementById('menuOverlay');
                const body = document.body;
                if (!menu || !overlay) return;
                if (window.innerWidth >= 768 && menu.classList.contains('open')) {
                    menu.classList.remove('open');
                    menu.classList.add('mobile-drawer');
                    overlay.classList.add('hidden');
                    overlay.classList.remove('opacity-100');
                    body.classList.remove('overflow-hidden');
                }
            });
        });
    </script>
</head>
<body class="font-sans bg-gray-50 text-gray-800">

<div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-40 opacity-0 transition-opacity duration-300"></div>


<header class="fixed top-0 w-full z-50">
    <div class="bg-gradient-to-r from-[#123E44]/90 to-[#1F5F63]/90 backdrop-blur-md">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4 text-white">

            <div>
                <h1 class="font-bold text-lg">Bimbingan Konseling</h1>
                <p class="text-xs opacity-80">SMKN 2 BJM</p>
            </div>

            <nav class="hidden md:flex items-center gap-7 text-sm">
                <a href="#" class="hover:underline">Beranda</a>
                <a href="#" class="hover:underline">Data Profiling</a>
                <a href="login.php"
                    class="bg-[#7FBF7A] text-[#123E44] px-5 py-2 rounded-full font-semibold hover:opacity-90 transition">
                    Login
                </a>
            </nav>

            <button onclick="toggleMenu()" class="md:hidden text-2xl p-2 focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

        </div>
    </div>
</header>

<div id="mobileMenu" class="mobile-drawer fixed top-0 right-0 h-full w-64 bg-mobile-drawer-bg shadow-2xl z-50 flex flex-col text-left">
    
    <div class="p-4 flex justify-between items-center bg-[#212121]"> 
        <h3 class="text-white font-semibold text-lg">Bimbingan Konseling</h3>
        <button onclick="toggleMenu()" class="text-white bg-transparent border border-white/50 w-8 h-8 rounded flex items-center justify-center text-xl">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <a href="#" class="py-3 px-4 text-white font-semibold hover:bg-[#384660] transition">Beranda</a>
    <a href="#" class="py-3 px-4 text-white hover:bg-[#384660] transition">Data Profiling</a>

    <hr class="border-gray-600 w-full my-4">

    <div class="p-4 mt-auto">
        <button onclick="window.location.href='login.php'" class="bg-[#7FBF7A] text-[#123E44] py-3 hover:opacity-90 transition text-sm w-full font-semibold rounded-full">
            Login
        </button>
    </div>
</div>


<section class="relative h-[640px] w-full pt-20">

    <div class="absolute inset-0 bg-cover bg-center hero-background"
        style="background-image: url('https://assets-a1.kompasiana.com/items/album/2016/05/25/1459049shutterstock-140079079780x390-57452e9ef37a6148061f8f95.jpg');"></div>

    <div class="absolute inset-0 bg-gradient-to-r from-[#123E44]/90 via-[#1F5F63]/80 to-[#7FBF7A]/70"></div>

    <div class="relative z-10 max-w-7xl mx-auto h-full flex items-center px-6">
        <div class="max-w-xl text-white">

            <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-6">
                Selamat Datang di Layanan Bimbingan Konseling
            </h2>

            <p class="text-gray-200 text-lg mb-8">
                Mendampingi siswa dalam layanan bimbingan akademik,
                pribadi, sosial, dan karier secara digital dan terarah.
            </p>

            <a href="login.php"
                class="inline-block border-2 border-white px-8 py-3 rounded-full font-semibold
                        hover:bg-white hover:text-[#1F5F63] transition">
                Mulai Sekarang
            </a>

        </div>
    </div>
</section>

<section id="tes-minat-bakat" class="py-16 md:py-24 px-4 bg-gray-50">
    <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 primary-color">Pilihan Tes Minat Bakat</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        
        <a href="login.php" class="card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border primary-border hover:border-2 transition-all">
            <i class="fas fa-brain primary-color text-5xl md:text-7xl mb-6 transition-transform"></i>
            <h4 class="text-xl font-bold mb-2 text-gray-800 text-center">Tes Kemampuan</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kemampuan dan memilih jurusan yang tepat.</p>
            <span class="mt-auto text-sm md:text-base font-bold primary-color border-b-2 primary-border">Lihat Lebih Lanjut</span>
        </a>

        <a href="login.php" class="card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border primary-border hover:border-2 transition-all">
            <i class="fas fa-palette primary-color text-5xl md:text-7xl mb-6 transition-transform"></i>
            <h4 class="text-xl font-bold mb-2 text-gray-800 text-center">Tes Gaya Belajar</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik). Dengan mengetahui gaya belajar, proses belajar Anda akan lebih maksimal.</p>
            <span class="mt-auto text-sm md:text-base font-bold primary-color border-b-2 primary-border">Lihat Lebih Lanjut</span>
        </a>

        <div class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-gray-300">
            <i class="fas fa-user-shield text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-gray-800 text-center">Tes Kepribadian</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini akan membantu Anda memahami tipe kepribadian dan dampaknya pada pilihan karir dan interaksi sosial.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
        </div>

        <div class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-gray-300">
            <i class="fas fa-clipboard-list text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-gray-800 text-center">Tes Asesmen Awal</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Asesmen awal untuk mengidentifikasi kebutuhan spesifik bimbingan dan konseling siswa.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
        </div>
    </div>
</section>

<section class="text-center py-16 md:py-24 px-4 bg-white">
    <h2 class="text-3xl md:text-4xl font-bold mb-12 primary-color">Langkah Penggunaan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        
        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-gray-100">
            <div class="flex-grow">
                <i class="fas fa-sign-in-alt primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-gray-800">1. Login atau Registrasi</h3>
                <p class="text-base text-gray-600 mb-6">Masuk menggunakan akun sekolah untuk mulai menggunakan layanan Bimbingan Konseling.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto bg-[#7FBF7A] text-[#123E44] px-6 py-3 rounded-full hover:bg-green-600 transition text-base font-semibold shadow-md">Akses</button>
        </div>

        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-gray-100">
            <div class="flex-grow">
                <i class="fas fa-file-signature primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-gray-800">2. Isi Tes Minat Bakat</h3>
                <p class="text-base text-gray-600 mb-6">Jawab pertanyaan Tes Kemampuan, Gaya Belajar, atau Tes lainnya sesuai dengan instruksi yang diberikan.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto bg-[#7FBF7A] text-[#123E44] px-6 py-3 rounded-full hover:bg-green-600 transition text-base font-semibold shadow-md">Mulai Tes</button>
        </div>

        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-gray-100">
            <div class="flex-grow">
                <i class="fas fa-chart-bar primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-gray-800">3. Lihat Hasil dan Saran</h3>
                <p class="text-base text-gray-600 mb-6">Dapatkan hasil tes yang akurat dan saran yang sesuai untuk mendukung pengembangan akademik dan karir Anda.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto bg-[#7FBF7A] text-[#123E44] px-6 py-3 rounded-full hover:bg-green-600 transition text-base font-semibold shadow-md">Lihat</button>
        </div>
    </div>
</section>

<footer class="text-center py-6 primary-bg text-white text-sm">
    <p class="px-4">© 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.</p>
</footer>

</body>
</html>
