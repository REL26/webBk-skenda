<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bimbingan dan Konseling SMKN 2 Banjarmasin</title>
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
        @keyframes iconWave {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-18deg); }
            40% { transform: rotate(14deg); }
            60% { transform: rotate(-10deg); }
            80% { transform: rotate(6deg); }
        }

        .wave-btn:hover .wave-icon {
            animation: iconWave 0.6s ease-in-out;
            display: inline-block;
        }

        .confetti-piece {
            position: fixed;
            width: 8px;
            height: 8px;
            border-radius: 2px;
            pointer-events: none;
            z-index: 200;
            animation: confettiPop 0.9s ease-out forwards;
        }

        @keyframes confettiPop {
            0% { transform: translate(0,0) rotate(0deg); opacity: 1; }
            100% { transform: translate(var(--tx), var(--ty)) rotate(var(--tr)); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .wave-btn:hover .wave-icon {
                animation: none;
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
                    <h1 class="font-bold text-xl md:text-3xl tracking-tight">Bimbingan dan Konseling</h1>
                    <p class="text-xs md:text-base text-white/70">SMKN 2 Banjarmasin</p>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="relative min-h-screen flex items-center overflow-hidden">
    <div class="absolute inset-0 hero-background" style="background-image:url('https://assets-a1.kompasiana.com/items/album/2016/05/25/1459049shutterstock-140079079780x390-57452e9ef37a6148061f8f95.jpg')"></div>
    <div class="absolute inset-0 bg-gradient-to-tr from-[#123E44] via-[#123E44]/80 to-transparent"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 w-full">
        <div class="max-w-2xl text-white">
            <span data-aos="fade-right" class="inline-block bg-white/10 border border-white/20 text-white/90 text-xs md:text-sm font-semibold px-4 py-1.5 rounded-full mb-5 backdrop-blur-sm">
                Platform Bimbingan dan Konseling Digital
            </span>
            <h2 data-aos="fade-right" data-aos-delay="200" class="hero-glow text-3xl md:text-5xl font-black leading-tight mb-5 float-unified">
                Layanan <span class="text-[var(--accent-color)]">Bimbingan dan Konseling</span><br>
                SMKN 2 Banjarmasin
            </h2>
            <p data-aos="fade-right" data-aos-delay="400" class="text-gray-200 text-base md:text-lg mb-8 leading-relaxed float-unified">
                Mendampingi siswa dalam layanan bimbingan akademik, pribadi, sosial, dan karier, sekaligus membantu Guru BK mengelola data, layanan, dan pelaporan bimbingan konseling secara digital dan terarah.
            </p>
            <div data-aos="fade-up" data-aos-delay="600" class="float-unified flex flex-wrap gap-4">
                <a href="login.php" class="magnetic wave-btn inline-block bg-[var(--accent-color)] text-white px-8 py-3 rounded-full font-bold text-base shadow-xl hover:scale-105 transition-transform">
                    <i class="fas fa-sign-in-alt mr-2 wave-icon"></i>Login
                </a>
                <a href="#tes-minat-bakat" class="magnetic inline-block border-2 border-white/70 text-white px-8 py-3 rounded-full font-bold text-base hover:bg-white hover:text-[var(--nav-green-dark)] transition-all">
                    Lihat Tes & Asesmen
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

<section class="py-16 md:py-24 px-4 bg-white">
    <div class="max-w-4xl mx-auto text-center">
        <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold mb-6 text-[#123E44]">Tentang Sistem</h2>
        <p data-aos="fade-up" data-aos-delay="100" class="text-base md:text-lg text-gray-600 leading-relaxed">
            Sistem Bimbingan dan Konseling SMKN 2 Banjarmasin adalah platform digital resmi yang menghubungkan siswa dan Guru BK dalam satu layanan terpadu. Melalui sistem ini, siswa dapat mengikuti tes dan asesmen, melihat hasil serta rekomendasi, dan mengakses layanan konseling, sementara Guru BK dapat mengelola data siswa, layanan bimbingan, administrasi, hingga laporan secara lebih tertib dan efisien.
        </p>
    </div>
</section>

<section class="py-16 md:py-24 px-4 bg-[#F9FAFB]">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold mb-3 text-[#123E44]">Untuk Siswa</h2>
            <p data-aos="fade-up" data-aos-delay="100" class="text-gray-600 max-w-2xl mx-auto">Manfaatkan layanan berikut untuk mendukung perkembangan akademik, pribadi, sosial, dan karier Anda.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8">
            <div data-aos="fade-up" data-aos-delay="100" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
                <i class="fas fa-pen-to-square text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Tes & Asesmen</h4>
                <p class="text-sm text-gray-600">Mengikuti berbagai tes minat, bakat, dan asesmen kebutuhan konseling secara daring.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="200" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
                <i class="fas fa-id-card text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Profiling Siswa</h4>
                <p class="text-sm text-gray-600">Data diri dan hasil pemetaan yang membantu Guru BK memahami kebutuhan Anda.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="300" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
                <i class="fas fa-chart-bar text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Hasil Tes & Rekomendasi</h4>
                <p class="text-sm text-gray-600">Melihat hasil tes secara personal beserta rekomendasi yang sesuai.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="400" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB]">
                <i class="fas fa-hands-helping text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Layanan Konseling</h4>
                <p class="text-sm text-gray-600">Mengakses layanan konseling individu maupun kelompok bersama Guru BK.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 md:py-24 px-4 bg-white">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold mb-3 text-[#123E44]">Untuk Guru BK</h2>
            <p data-aos="fade-up" data-aos-delay="100" class="text-gray-600 max-w-2xl mx-auto">Kelola seluruh kebutuhan bimbingan dan konseling siswa dalam satu sistem terpadu.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8">
            <div data-aos="fade-up" data-aos-delay="100" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-[#F9FAFB] border border-[#E5E7EB]">
                <i class="fas fa-user-graduate text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Pengelolaan Data Siswa</h4>
                <p class="text-sm text-gray-600">Mengakses data hasil tes per siswa maupun rekap per kelas secara terpusat.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="200" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-[#F9FAFB] border border-[#E5E7EB]">
                <i class="fas fa-chalkboard-teacher text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Pengelolaan Layanan BK</h4>
                <p class="text-sm text-gray-600">Mengelola bimbingan klasikal, bimbingan kelompok, konseling individu dan kelompok, konsultasi orang tua, hingga home visit.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="300" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-[#F9FAFB] border border-[#E5E7EB]">
                <i class="fas fa-folder-open text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Administrasi BK</h4>
                <p class="text-sm text-gray-600">Mengelola kelengkapan administrasi program bimbingan dan konseling.</p>
            </div>
            <div data-aos="fade-up" data-aos-delay="400" class="card-base flex flex-col items-center text-center p-6 md:p-8 h-full rounded-2xl bg-[#F9FAFB] border border-[#E5E7EB]">
                <i class="fas fa-file-invoice text-[#123E44] text-4xl md:text-5xl mb-5 transition-transform duration-300"></i>
                <h4 class="text-lg font-bold mb-2">Laporan & Rekapitulasi</h4>
                <p class="text-sm text-gray-600">Menyusun laporan BK serta rekapitulasi layanan secara rapi dan terdokumentasi.</p>
            </div>
        </div>
    </div>
</section>

<section id="tes-minat-bakat" class="py-16 md:py-28 px-4 bg-[#F9FAFB]">
    <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold text-center mb-12 text-[#123E44]">Tes & Asesmen</h2>
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

        <a data-aos="fade-up" data-aos-delay="300" href="login.php" class="test-card-lockedx card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)]">
            <i class="fas fa-user-shield text-[#123E44] text-5xl md:text-7xl mb-6 transition-transform duration-300"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Kepribadian</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Tes ini akan membantu Anda memahami tipe kepribadian.</p>
            <span class="mt-auto text-sm md:text-base font-bold text-[#123E44] border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
            <div class="locked-tooltip">Akan tersedia setelah login</div>
        </a>

        <a data-aos="fade-up" data-aos-delay="400" href="login.php" class="test-card-lockedx card-base flex flex-col items-center p-6 md:p-8 h-full rounded-2xl bg-white border border-[#E5E7EB] hover:border-[var(--accent-color)]">
            <i class="fas fa-clipboard-list text-[#123E44] text-5xl md:text-7xl mb-6 transition-transform duration-300"></i>
            <h4 class="text-xl font-bold mb-2 text-center">Tes Asesmen Awal</h4>
            <p class="text-sm text-gray-600 text-center mb-4 flex-grow">Asesmen awal untuk kebutuhan konseling siswa.</p>
            <span class="mt-auto text-sm md:text-base font-bold text-[#123E44] border-b-2 border-b-[var(--accent-color)]">Lihat Lebih Lanjut</span>
            <div class="locked-tooltip">Akan tersedia setelah login</div>
        </a>
    </div>
</section>

<section class="text-center py-16 md:py-24 px-4 bg-white">
    <h2 data-aos="fade-up" class="text-3xl md:text-4xl font-bold mb-12 text-[#123E44]">Langkah Penggunaan</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 lg:gap-8 max-w-7xl mx-auto">
        <div data-aos="zoom-in" data-aos-delay="100" class="card-base bg-white rounded-2xl p-8 flex flex-col h-full border border-[#E5E7EB]">
            <div class="flex-grow">
                <i class="fas fa-sign-in-alt text-[#123E44] text-6xl mx-auto mt-2 mb-6"></i>
                <h3 class="text-xl font-bold mb-3">1. Login dan Isi Biodata</h3>
                <p class="text-base text-gray-600 mb-6">Masuk menggunakan NIS dan isi biodata anda dengan lengkap untuk mulai menggunakan layanan Bimbingan dan Konseling.</p>
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

<section class="py-16 md:py-20 px-4 text-center" style="background:linear-gradient(135deg, var(--header-bg-start), var(--header-bg-end));">
    <div class="max-w-2xl mx-auto text-white">
        <h2 data-aos="fade-up" class="text-2xl md:text-4xl font-bold mb-4">Siap Menggunakan Layanan BK?</h2>
        <p data-aos="fade-up" data-aos-delay="100" class="text-white/80 mb-8 text-base md:text-lg">Masuk ke sistem untuk mengakses tes, hasil, dan layanan bimbingan konseling.</p>
        <a id="ctaLoginBtn" data-aos="fade-up" data-aos-delay="200" href="login.php" class="magnetic wave-btn inline-block bg-[var(--accent-color)] text-white px-10 py-3.5 rounded-full font-bold text-base shadow-xl hover:scale-105 transition-transform relative overflow-visible">
            <i class="fas fa-sign-in-alt mr-2 wave-icon"></i>Login Sekarang
        </a>
    </div>
</section>

<footer class="py-10 px-4 bg-[#123E44] text-white text-sm">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
        <div>
            <h5 class="font-bold text-base mb-2">Bimbingan dan Konseling</h5>
            <p class="text-white/60 text-xs leading-relaxed">Platform digital layanan bimbingan dan konseling SMKN 2 Banjarmasin untuk siswa dan Guru BK.</p>
        </div>
        <div>
            <h5 class="font-bold text-base mb-2">Layanan</h5>
            <ul class="text-white/60 text-xs space-y-1.5">
                <li>Tes & Asesmen</li>
                <li>Profiling Siswa</li>
                <li>Layanan Konseling</li>
                <li>Administrasi & Laporan BK</li>
            </ul>
        </div>
        <div>
            <h5 class="font-bold text-base mb-2">Akses</h5>
            <a href="login.php" class="inline-block text-white/80 hover:text-white text-xs border border-white/20 px-4 py-2 rounded-full transition-all hover:border-white/50">
                <i class="fas fa-sign-in-alt mr-1.5"></i>Login ke Sistem
            </a>
        </div>
    </div>
    <div class="max-w-7xl mx-auto border-t border-white/10 mt-8 pt-6 text-center">
        <p class="text-sm text-white/70">
            &copy; <?php echo date("Y"); ?> <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
        </p>
        <p class="text-xs text-gray-400 mt-1">
            Developed by <span class="font-medium">SahDu Team</span>
        </p>
    </div>
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

const ctaLoginBtn = document.getElementById('ctaLoginBtn');
if (ctaLoginBtn) {
    ctaLoginBtn.addEventListener('click', e => {
        e.preventDefault();
        const rect = ctaLoginBtn.getBoundingClientRect();
        const originX = rect.left + rect.width / 2;
        const originY = rect.top + rect.height / 2;
        const colors = ['#5FA8A1', '#F9D65C', '#F97316', '#ffffff', '#73B9B2'];
        for (let i = 0; i < 18; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = originX + 'px';
            piece.style.top = originY + 'px';
            piece.style.background = colors[Math.floor(Math.random() * colors.length)];
            const angle = Math.random() * Math.PI * 2;
            const distance = 60 + Math.random() * 60;
            piece.style.setProperty('--tx', Math.cos(angle) * distance + 'px');
            piece.style.setProperty('--ty', Math.sin(angle) * distance + 'px');
            piece.style.setProperty('--tr', (Math.random() * 360) + 'deg');
            document.body.appendChild(piece);
            setTimeout(() => piece.remove(), 900);
        }
        setTimeout(() => { window.location.href = 'login.php'; }, 350);
    });
}
</script>


</body>
</html>