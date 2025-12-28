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
        .nav-scrolled {
    background-color: rgba(255, 255, 255, 0.95) !important;
    color: #0F172A !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.nav-scrolled a {
    color: #0F172A !important;
    opacity: 0.9;
}

.nav-scrolled a:hover {
    opacity: 1;
}

.nav-scrolled .login-btn {
    background-color: var(--green-soft);
    color: white !important;
}
        :root {
            --primary-color: #123E44; 
            --primary-dark: #0F2A44; 
            --accent-color: #5FA8A1; 
            --accent-dark-hover: #4C8E89; 
            --header-bg-start: #123E44;
            --header-bg-end: #1F5F63;
            --mobile-drawer-bg: #2C3A50; 
            --nav-black: #0F172A;
            --green-soft: #5FA8A1;
            --primary-color: #123E44;
            --primary-dark: #0F2A44;
            --accent-color: #5FA8A1;
            --accent-dark-hover: #4C8E89;
            --nav-green-dark: #0F3A3A;
        }
        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .accent-bg { background-color: var(--accent-color); }
        .accent-bg-hover:hover { background-color: var(--accent-dark-hover); }

        .card-base {
            transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.1);
        }
        .card-base:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
        }
        .primary-border { border-color: var(--accent-color); }

        .hero-background {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    filter: brightness(0.55) contrast(1.05) saturate(0.9);

}

        .mobile-drawer {
            transition: transform 0.3s ease-in-out;
            transform: translateX(100%); 
        }
        .mobile-drawer.open {
            transform: translateX(0); 
        }

        .test-card-locked {
            filter: grayscale(60%); 
            opacity: 0.8; 
            cursor: not-allowed;
        }
        .test-card-locked:hover {
            box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.1) !important;
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
                menu.classList.remove('mobile-drawer');
                menu.classList.add('open');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
                body.classList.add('overflow-hidden'); 
            } else {
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
<body class="font-sans bg-gray-50 text-[#1F2937]">

<div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-40 opacity-0 transition-opacity duration-300"></div>


<header class="fixed top-0 w-full z-50">
    <div class="relative bg-[var(--nav-green-dark)]">
        <div class="absolute bottom-0 left-0 w-full h-10 bg-gradient-to-b from-[var(--nav-green-dark)] to-transparent shadow-[0_10px_30px_-15px_rgba(0,0,0,0.6)]"></div>

        <div class="relative max-w-7xl mx-auto flex items-center px-6 py-6 text-white">
            <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logocompact/300x300/1758083167/SMK2.png" alt="logo-skenda" class="h-10 w-10 mr-4 object-contain"/>
            <div class="leading-tight">
                <h1 class="font-semibold text-lg md:text-2xl tracking-wide">
                    Bimbingan Konseling
                </h1>
                <p class="text-[11px] md:text-sm text-white/70">
                    SMKN 2 Banjarmasin
                </p>
            </div>

            <a href="login.php"
               class="ml-auto
                      bg-[var(--nav-green-dark)] text-white
                      border-2 border-white
                      px-5 py-2 rounded-md
                      font-semibold text-sm
                      hover:bg-white hover:text-[var(--nav-green-dark)]
                      transition">
                Login
            </a>
        </div>
    </div>
</header>

<section class="relative min-h-[70vh] md:min-h-screen pt-24 flex items-center overflow-hidden">
    <div class="absolute inset-0 hero-background"
         style="background-image:url('https://assets-a1.kompasiana.com/items/album/2016/05/25/1459049shutterstock-140079079780x390-57452e9ef37a6148061f8f95.jpg')">
    </div>

    <div class="absolute inset-0 bg-gradient-to-b from-black via-black/70 to-[#5FA8A1]/85"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 w-full">
        <div class="max-w-xl text-white">
            <h2 class="text-3xl md:text-5xl font-extrabold leading-tight mb-6">
                Selamat Datang di Layanan Bimbingan Konseling
            </h2>

            <p class="text-gray-300 text-base md:text-lg mb-8">
                Mendampingi siswa dalam layanan bimbingan akademik,
                pribadi, sosial, dan karier secara digital dan terarah.
            </p>

            <a href="login.php"
               class="inline-block border-2 border-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-black transition">
                Mulai Sekarang
            </a>
        </div>
    </div>
</section>



<section id="tes-minat-bakat" class="py-16 md:py-24 px-4 bg-[#F9FAFB]">
    <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 primary-color">Pilihan Tes Minat Bakat</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        
        <a href="login.php" class="card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)] transition-all">
            <i class="fas fa-brain primary-color text-5xl md:text-7xl mb-6 transition-transform"></i>
            <h4 class="text-xl font-bold mb-2 text-[#1F2937] text-center">Tes Kemampuan</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kemampuan dan memilih jurusan yang tepat.</p>
            <span class="mt-auto text-sm md:text-base font-bold primary-color border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
        </a>

        <a href="login.php" class="card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)] transition-all">
            <i class="fas fa-palette primary-color text-5xl md:text-7xl mb-6 transition-transform"></i>
            <h4 class="text-xl font-bold mb-2 text-[#1F2937] text-center">Tes Gaya Belajar</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda (Visual, Auditorik, Kinestetik). Dengan mengetahui gaya belajar, proses belajar Anda akan lebih maksimal.</p>
            <span class="mt-auto text-sm md:text-base font-bold primary-color border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
        </a>

        <div class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
            <i class="fas fa-user-shield text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-[#1F2937] text-center">Tes Kepribadian</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini akan membantu Anda memahami tipe kepribadian dan dampaknya pada pilihan karir dan interaksi sosial.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
        </div>

        <div class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
            <i class="fas fa-clipboard-list text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-[#1F2937] text-center">Tes Asesmen Awal</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Asesmen awal untuk mengidentifikasi kebutuhan spesifik bimbingan dan konseling siswa.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
        </div>
    </div>
</section>

<section class="text-center py-16 md:py-24 px-4 bg-white">
    <h2 class="text-3xl md:text-4xl font-bold mb-12 primary-color">Langkah Penggunaan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        
        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-sign-in-alt primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-[#1F2937]">1. Login atau Registrasi</h3>
                <p class="text-base text-gray-600 mb-6">Masuk menggunakan akun sekolah untuk mulai menggunakan layanan Bimbingan Konseling.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto accent-bg text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition text-base font-semibold shadow-md">Akses</button>
        </div>

        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-file-signature primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-[#1F2937]">2. Isi Tes Minat Bakat</h3>
                <p class="text-base text-gray-600 mb-6">Jawab pertanyaan Tes Kemampuan, Gaya Belajar, atau Tes lainnya sesuai dengan instruksi yang diberikan.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto accent-bg text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition text-base font-semibold shadow-md">Mulai Tes</button>
        </div>

        <div class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-chart-bar primary-color text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3 text-[#1F2937]">3. Lihat Hasil dan Saran</h3>
                <p class="text-base text-gray-600 mb-6">Dapatkan hasil tes yang akurat dan saran yang sesuai untuk mendukung pengembangan akademik dan karir Anda.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="mt-auto accent-bg text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition text-base font-semibold shadow-md">Lihat</button>
        </div>
    </div>
</section>

<footer class="text-center py-6 primary-bg text-white text-sm">
    <p class="px-4">© 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.</p>
</footer>

</body>
</html>