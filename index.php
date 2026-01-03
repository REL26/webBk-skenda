<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bimbingan Konseling SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
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
            --nav-green-dark: #0F3A3A;
        }

        html {
            scroll-behavior: smooth;
        }

        #scrollProgress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: var(--accent-color);
            z-index: 100;
            transition: width 0.1s ease;
        }

        header.scrolled {
            background: rgba(15, 58, 58, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }

        header.compact {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }

        .card-base {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s, background-color 0.35s;
            box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.1);
        }

        .card-base:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.18);
            background-color: #ffffff;
        }

        .card-base:hover h4 {
            color: var(--accent-color);
        }

        .card-base:hover i {
            transform: scale(1.15) rotate(6deg);
        }

        .hero-background {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: brightness(0.55) contrast(1.05) saturate(0.9);
            animation: heroZoom 18s ease-in-out infinite;
        }

        @keyframes heroZoom {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        @keyframes floatUnified {
            0% { transform: translateY(0) scale(1) rotate(0deg); }
            50% { transform: translateY(-12px) scale(1.015) rotate(0.3deg); }
            100% { transform: translateY(0) scale(1) rotate(0deg); }
        }

        .float-unified {
            animation: floatUnified 6s ease-in-out infinite;
            will-change: transform;
        }

        .hero-glow span {
            transition: text-shadow 0.4s ease;
        }

        .hero-glow:hover span {
            text-shadow: 0 0 18px rgba(72, 203, 184, 0.6);
        }

        .magnetic {
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        .test-card-locked {
            filter: grayscale(60%);
            opacity: 0.8;
            cursor: not-allowed;
            position: relative;
        }

        .locked-tooltip {
            position: absolute;
            bottom: 1rem;
            background: rgba(0,0,0,0.8);
            color: #fff;
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
            border-radius: 0.5rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .test-card-locked:hover .locked-tooltip,
        .test-card-lockedx:hover .locked-tooltip {
            opacity: 1;
        }

        .scroll-cue {
            animation: fadeBounce 2.5s ease-in-out infinite;
        }

        @keyframes fadeBounce {
            0%,100% { opacity: 0.3; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(10px); }
        }

        .scroll-cue.hide {
            opacity: 0;
            transform: translateY(20px);
        }

        @media (hover: none) {
            .card-base:active,
            .magnetic:active {
                transform: scale(0.97);
            }
        }
        /* focus-visible / accessibility */
        :focus {
            outline: 3px solid rgba(95,168,161,0.4);
            outline-offset: 3px;
        }

        /* Respect user's reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>
<body class="font-sans bg-gray-50 text-[#1F2937]">

<div id="scrollProgress"></div>

<header id="mainHeader" class="fixed top-0 w-full z-50 transition-all duration-500">
    <div class="relative">
        <div class="max-w-7xl mx-auto flex items-center px-6 py-4 text-white">
            <div class="flex items-center group cursor-pointer">
                <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logocompact/300x300/1758083167/SMK2.png" alt="Logo SMKN 2 Banjarmasin" loading="lazy" class="h-12 w-12 mr-4 transform group-hover:rotate-12 transition-transform"/>
                <div class="leading-tight">
                    <h1 class="font-bold text-xl md:text-3xl tracking-tight">Bimbingan Konseling</h1>
                    <p class="text-xs md:text-base text-white/70">SMKN 2 Banjarmasin</p>
                </div>
            </div>
            <a href="login.php" class="ml-auto magnetic border-2 border-white px-7 py-2.5 rounded-full font-bold text-base hover:bg-white hover:text-[var(--nav-green-dark)] transition-all">
                Login
            </a>
        </div>
    </div>
</header>

<section class="relative min-h-screen flex items-center overflow-hidden">
    <div class="absolute inset-0 hero-background" style="background-image:url('https://assets-a1.kompasiana.com/items/album/2016/05/25/1459049shutterstock-140079079780x390-57452e9ef37a6148061f8f95.jpg')"></div>
    <div class="absolute inset-0 bg-gradient-to-tr from-[#123E44] via-[#123E44]/80 to-transparent"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 w-full">
        <div class="max-w-2xl text-white">
            <h2 data-aos="fade-right" data-aos-delay="200" class="hero-glow text-3xl md:text-5xl font-black leading-tight mb-5 float-unified">
                Selamat Datang di Layanan <br>
                <span class="text-[var(--accent-color)]">Bimbingan Konseling</span>
            </h2>
            <p data-aos="fade-right" data-aos-delay="400" class="text-gray-200 text-base md:text-lg mb-8 leading-relaxed float-unified">
                Mendampingi siswa dalam layanan bimbingan akademik, pribadi, sosial, dan karier secara digital dan terarah.
            </p>
            <div data-aos="fade-up" data-aos-delay="600" class="float-unified">
                <a href="#tes-minat-bakat" class="magnetic inline-block bg-[var(--accent-color)] text-white px-8 py-3 rounded-full font-bold text-base shadow-xl hover:scale-105 transition-transform">
                    Mulai Eksplorasi
                </a>
            </div>
        </div>
    </div>

    <div id="scrollCue" class="fixed bottom-12 inset-x-0 flex justify-center text-white/70 text-sm text-center scroll-cue z-40 pointer-events-none transition-all duration-500">
        <div>
            <!-- <div>Scroll untuk melihat layanan</div> -->
            <div class="text-xl">↓</div>
        </div>
    </div>
</section>

<section id="tes-minat-bakat" class="py-16 md:py-28 px-4 bg-[#F9FAFB]">
    <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold text-center mb-12 text-[#123E44]">Pilihan Tes Minat Bakat</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        <a data-aos="fade-up" data-aos-delay="100" href="login.php" class="test-card-lockedx card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)]">
            <i class="fas fa-brain text-[#123E44] text-5xl md:text-7xl mb-6 transition-transform duration-300"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Kemampuan</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini mengukur potensi kognitif dan akademik Anda. Hasilnya akan membantu Anda memahami kemampuan dan memilih jurusan yang tepat.</p>
            <span class="mt-auto text-sm md:text-base font-bold text-[#123E44] border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
            <div class="locked-tooltip">Akan tersedia setelah login</div>
        </a>

        <a data-aos="fade-up" data-aos-delay="200" href="login.php" class="test-card-lockedx card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)]">
            <i class="fas fa-palette text-[#123E44] text-5xl md:text-7xl mb-6 transition-transform duration-300"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Gaya Belajar</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini bertujuan mengidentifikasi cara belajar paling efektif Anda.</p>
            <span class="mt-auto text-sm md:text-base font-bold text-[#123E44] border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
            <div class="locked-tooltip">Akan tersedia setelah login</div>
        </a>

        <div data-aos="fade-up" data-aos-delay="300" class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
            <i class="fas fa-user-shield text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Kepribadian</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini akan membantu Anda memahami tipe kepribadian.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
            <div class="locked-tooltip">Tes ini sedang dalam perkembangan</div>
        </div>

        <div data-aos="fade-up" data-aos-delay="400" class="test-card-locked card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
            <i class="fas fa-clipboard-list text-gray-500 text-5xl md:text-7xl mb-6"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Asesmen Awal</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Asesmen awal untuk kebutuhan konseling siswa.</p>
            <div class="mt-auto text-sm md:text-base font-extrabold text-red-600"><i class="fas fa-lock mr-2"></i> Segera Hadir</div>
            <div class="locked-tooltip">Tes ini sedang dalam perkembangan</div>
        </div>
    </div>
</section>

<section class="text-center py-16 md:py-24 px-4 bg-white">
    <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold mb-12 text-[#123E44]">Langkah Penggunaan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        <div data-aos="zoom-in" data-aos-delay="100" class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-sign-in-alt text-[#123E44] text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3">1. Login dan Isi Biodata</h3>
                <p class="text-base text-gray-600 mb-6">Masuk menggunakan NIS dan isi biodata anda dengan lengkap untuk mulai menggunakan layanan Bimbingan Konseling.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="magnetic mt-auto bg-[var(--accent-color)] text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition-all duration-300 text-base font-semibold shadow-md transform hover:scale-105">Akses</button>
        </div>

        <div data-aos="zoom-in" data-aos-delay="200" class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-file-signature text-[#123E44] text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3">2. Isi Tes Minat Bakat</h3>
                <p class="text-base text-gray-600 mb-6">Jawab pertanyaan Tes Kemampuan, Gaya Belajar, atau Tes lainnya sesuai dengan instruksi yang diberikan.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="magnetic mt-auto bg-[var(--accent-color)] text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition-all duration-300 text-base font-semibold shadow-md transform hover:scale-105">Mulai Tes</button>
        </div>

        <div data-aos="zoom-in" data-aos-delay="300" class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-chart-bar text-[#123E44] text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3">3. Lihat Hasil dan Saran</h3>
                <p class="text-base text-gray-600 mb-6">Dapatkan hasil tes yang akurat dan saran yang sesuai untuk mendukung pengembangan akademik dan karir Anda.</p>
            </div>
            <button onclick="window.location.href='login.php'" class="magnetic mt-auto bg-[var(--accent-color)] text-white px-6 py-3 rounded-full hover:bg-[var(--accent-dark-hover)] transition-all duration-300 text-base font-semibold shadow-md transform hover:scale-105">Lihat</button>
        </div>
    </div>
</section>

<footer class="text-center py-6 bg-[#123E44] text-white text-sm">
    <p class="px-4">© 2025 Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.</p>
</footer>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration: 800, once: true });

const scrollProgress = document.getElementById('scrollProgress');
const header = document.getElementById('mainHeader');
const scrollCue = document.getElementById('scrollCue');

window.addEventListener('scroll', () => {
    const winScroll = document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    if (height > 0) scrollProgress.style.width = (winScroll / height) * 100 + '%';

    if (winScroll > 80) header.classList.add('scrolled','compact');
    else header.classList.remove('scrolled','compact');

    scrollCue.classList.toggle('hide', winScroll > 40);
});

document.querySelectorAll('.magnetic').forEach(el => {
    el.addEventListener('mousemove', e => {
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        el.style.transform = `translate(${x * 0.15}px, ${y * 0.15}px)`;
    });
    el.addEventListener('mouseleave', () => el.style.transform = 'translate(0,0)');
});

document.querySelectorAll('.test-card-locked').forEach(card => {
    card.addEventListener('click', e => {
        e.preventDefault();
        alert('Maaf, tes ini belum tersedia.');
    });
});

document.querySelectorAll('.test-card-lockedx').forEach(card => {
    card.addEventListener('click', e => {
        e.preventDefault();
        if (confirm('Login diperlukan untuk mengakses tes. Login sekarang?')) {
            window.location.href = 'login.php';
        }
    });
});
</script>


</body>
</html>
