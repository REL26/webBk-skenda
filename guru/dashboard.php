<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}
$id_guru = (int) $_SESSION['id_guru'];
$query_guru = mysqli_query($koneksi, "SELECT nama FROM guru WHERE id_guru = $id_guru LIMIT 1");
$guru = mysqli_fetch_assoc($query_guru);
$nama_pengguna = isset($guru['nama']) ? $guru['nama'] : '';

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .nav-item {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: #5FA8A1;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            transform: scaleY(1);
        }


        .primary-gradient {
            background: linear-gradient(180deg,
                    #0F3A3A 0%,
                    #123E44 100%);
        }

        .primary-color {
            color: #2F6C6E;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #2F6C6E;
            border-radius: 10px;
        }

        .sidebar {
            transition: all 0.3s ease;
            width: 280px;
        }

        .fade-slide.hidden-transition {
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .fade-slide.active-transition {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            transition: all 0.3s ease;
        }

        @media (min-width: 768px) {
            .sidebar {
                width: 260px;
                flex-shrink: 0;
                transform: translateX(0) !important;
                position: fixed !important;
                height: 100vh;
                top: 0;
                left: 0;
                overflow-y: auto;
            }

            .main-content {
                margin-left: 260px;
            }
        }
    </style>

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            if (mobileMenu.classList.contains('active-transition')) {
                mobileMenu.classList.remove('active-transition');
                mobileMenu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                mobileMenu.classList.remove('hidden-transition');
                mobileMenu.classList.add('active-transition');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function toggleSubMenu(menuId) {
            const submenu = document.getElementById(menuId);
            const icon = document.getElementById(menuId + 'Icon');
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                submenu.classList.add('hidden');
                if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }
    </script>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
        <main class="main-content flex-grow min-h-screen">
            <section class="relative overflow-hidden bg-white border-b border-slate-200">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-teal-50 rounded-full blur-3xl opacity-50">
                </div>
                <div class="py-12 px-6 md:px-12 max-w-7xl mx-auto relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <span
                                class="inline-block px-3 py-1 rounded-full bg-teal-100 text-teal-700 text-xs font-bold uppercase tracking-wider mb-3">Selamat
                                Datang Kembali</span>
                            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                                Halo, <span class="primary-color">
                                    <?php echo htmlspecialchars($nama_pengguna); ?>
                                </span>!
                            </h1>
                            <p class="mt-2 text-slate-500 max-w-lg">
                                Siap membantu siswa hari ini? Pantau perkembangan siswa SMKN 2 Banjarmasin di sini.
                            </p>
                        </div>
                        <div class="hidden lg:block">
                            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png"
                                class="h-24 opacity-20 grayscale" alt="">
                        </div>
                    </div>
                </div>
            </section>

            <section class="p-6 md:p-12 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">





                    <a href="hasil_tes.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-clipboard-list text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Data Hasil Tes
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Kelola hasil tes kemampuan, gaya belajar, dan profiling siswa.
                        </p>


                    </a>





                    <a href="rekap_kelas.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-chart-column text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Rekap Kelas
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Lihat statistik siswa berdasarkan kelas dan hasil profiling.
                        </p>


                    </a>







                    <a href="konselingindividu.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-user-group text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Konseling Individu
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Catat dan kelola layanan konseling siswa secara pribadi.
                        </p>


                    </a>






                    <a href="konselingkelompok.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-users text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Konseling Kelompok
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Kelola kegiatan konseling kelompok siswa.
                        </p>


                    </a>







                    <a href="bimbingankelompok.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-folder-open text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Bimbingan Kelompok
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Kelola program dan layanan Bimbingan Kelompok.
                        </p>


                    </a>






                    <a href="laporanbk.php"
                        class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition">


                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-teal-700 mb-5">

                            <i class="fas fa-file-lines text-xl"></i>

                        </div>


                        <h3 class="font-bold text-slate-800 text-lg">
                            Laporan BK
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Cetak dan lihat laporan kegiatan Bimbingan dan Konseling.
                        </p>


                    </a>





                </div>


            </section>
        </main>
    </div>

    <footer class="bg-white border-t border-gray-200 py-6 ms-48 mt-auto">
        <div class="text-center">
            <p class="text-sm text-black/70">
                &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Developed by <span class="font-medium">SahDu Team</span>
            </p>
        </div>
    </footer>

</body>

</html>