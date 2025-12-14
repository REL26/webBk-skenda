<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);
$is_program_bk_active = in_array($current_page, ['konselingindividu.php', 'konselingkelompok.php', 'bimbingankelompok.php']);

$filter_search  = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_kelas   = isset($_GET['kelas']) ? mysqli_real_escape_string($koneksi, trim($_GET['kelas'])) : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, trim($_GET['jurusan'])) : '';
$filter_tahun   = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, trim($_GET['tahun'])) : '';

$where_clauses = [];

if (!empty($filter_search)) {
    $where_clauses[] = "(s.nama LIKE '%$filter_search%' OR s.nis LIKE '%$filter_search%')";
}
if (!empty($filter_kelas)) {
    $where_clauses[] = "s.kelas = '$filter_kelas'";
}
if (!empty($filter_jurusan)) {
    $where_clauses[] = "s.jurusan = '$filter_jurusan'";
}
if (!empty($filter_tahun)) {
    $where_clauses[] = "s.tahun_ajaran_id = '$filter_tahun'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$limit_per_page = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 40; 
$current_page_num = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($limit_per_page != 20 && $limit_per_page != 40) { $limit_per_page = 40; }
$start_from = ($current_page_num - 1) * $limit_per_page;
if ($start_from < 0) $start_from = 0;

$query_count = "
    SELECT 
        COUNT(s.id_siswa) AS total_rows
    FROM 
        siswa s
    LEFT JOIN 
        tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    {$where_sql}
";
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count)['total_rows'];
$total_pages = ceil($row_count / $limit_per_page);

$query_siswa = "
    SELECT 
        s.id_siswa,
        s.nis, 
        s.nama, 
        s.kelas, 
        s.jurusan
    FROM
        siswa s
    
    {$where_sql}
    ORDER BY 
        s.kelas ASC, s.nama ASC
    
    LIMIT {$start_from}, {$limit_per_page}
";

$result_siswa = mysqli_query($koneksi, $query_siswa);

if (!$result_siswa) {
    die("Query Siswa Gagal: " . mysqli_error($koneksi));
}

function get_latest_session_data($koneksi, $id_siswa) {
    $query = "
        SELECT 
            pertemuan_ke, 
            panggilan_ke
        FROM 
            konseling_individu
        WHERE 
            id_siswa = ?
        ORDER BY 
            tanggal_pelaksanaan DESC, created_at DESC
        LIMIT 1
    ";
    
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return ['pertemuan_ke' => 0, 'panggilan_ke' => 0];
}

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
$kelas_options = mysqli_fetch_all($result_kelas, MYSQLI_ASSOC);
$kelas_options = array_column($kelas_options, 'kelas');

$query_jurusan = "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan";
$result_jurusan = mysqli_query($koneksi, $query_jurusan);
$jurusan_options = mysqli_fetch_all($result_jurusan, MYSQLI_ASSOC);
$jurusan_options = array_column($jurusan_options, 'jurusan');

$query_tahun = "SELECT id_tahun, tahun FROM tahun_ajaran ORDER BY tahun DESC";
$result_tahun = mysqli_query($koneksi, $query_tahun);
$data_tahun = mysqli_fetch_all($result_tahun, MYSQLI_ASSOC);

$current_filters = [
    'search' => $filter_search, 
    'kelas' => $filter_kelas, 
    'jurusan' => $filter_jurusan, 
    'tahun' => $filter_tahun,
    'limit' => $limit_per_page
];

function get_pagination_url($page, $filters) {
    $query_params = array_filter($filters);
    $query_params['page'] = $page;
    return 'konselingindividu.php?' . http_build_query($query_params);
}

$waktu_durasi_options = [15, 30, 45, 60];
$atas_dasar_options = ['Inisiatif Siswa', 'Panggilan Ortu', 'Rujukan Guru Mapel', 'Panggilan Guru BK', 'Lainnya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa untuk Konseling Individu | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-bg { background-color: #2F6C6E; }
        .primary-color { color: #2F6C6E; }
        .data-table-report { min-width: 800px; }
        .sticky-col { position: sticky; left: 0; z-index: 10; background-color: white; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .data-table-report thead th.sticky-col { background-color: #2F6C6E !important; }

        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            opacity: 0;
        }
        .modal.open {
            visibility: visible;
            opacity: 1;
        }
        
        .fade-slide {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
        }
        .fade-slide.active-transition {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
    </style>

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            if (mobileMenu.classList.contains('active-transition')) {
                mobileMenu.classList.remove('active-transition');
                overlay.classList.add('hidden');
                
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                    body.classList.remove('overflow-hidden');
                }, 300);

            } else {
                mobileMenu.classList.remove('hidden');
                
                setTimeout(() => {
                    mobileMenu.classList.add('active-transition');
                }, 10);
                
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            }
        }
        
        function toggleSubMenu(menuId) {
            const submenu = document.getElementById(menuId);
            const icon = document.getElementById(menuId + 'Icon');
            if (submenu) {
                 if (submenu.classList.contains('hidden')) {
                    submenu.classList.remove('hidden');
                    if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    submenu.classList.add('hidden');
                    if (icon) icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            }
        }

        function openModal(id_siswa, nama_siswa, kelas, jurusan, nis, pertemuan_ke, panggilan_ke) {
            const modal = document.getElementById('konselingModal');
            document.getElementById('modalTitle').textContent = `Buat Laporan Sesi Konseling untuk ${nama_siswa}`;
            
            document.getElementById('id_siswa').value = id_siswa;
            
            document.getElementById('siswa_nama').textContent = nama_siswa;
            document.getElementById('siswa_kelas_jurusan').textContent = `${kelas} ${jurusan}`;
            document.getElementById('siswa_nis').textContent = nis;
            
            const next_pertemuan = parseInt(pertemuan_ke) + 1;
            const next_panggilan = parseInt(panggilan_ke) + 1;
            document.getElementById('pertemuan_ke').value = next_pertemuan;
            document.getElementById('panggilan_ke').value = next_panggilan;
            document.getElementById('pertemuan_display').textContent = next_pertemuan;
            document.getElementById('panggilan_display').textContent = next_panggilan;
            
            document.getElementById('tanggal_pelaksanaan').valueAsDate = new Date();
            
            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            const modal = document.getElementById('konselingModal');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('konselingForm').reset();
        }

        // FUNGSI UNTUK MEMBUKA MODAL PDF VIEWER (Diperbarui)
        function openPdfViewerModal(pdfUrl) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            const exportBtn = document.getElementById('exportPdfBtn');
            
            document.getElementById('pdfIframeTitle').textContent = 'Laporan Konseling Individu';
            iframe.src = pdfUrl;
            exportBtn.href = pdfUrl; // Sinkronkan URL ke tombol Ekspor/Download

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
        }
        
        function closePdfViewerModal() {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            
            iframe.src = ''; 
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        }


        $(document).ready(function(){

            $("#submitBtn").click(function(e){
                e.preventDefault();

                let form = document.getElementById("konselingForm");
                let formData = new FormData(form);
                const submitButton = document.getElementById('submitBtn');
                const originalText = submitButton.textContent;

                submitButton.disabled = true;
                submitButton.textContent = 'Menyimpan...';

                $.ajax({
                    url: "laporan_individukon.php",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",

                    success: function(res){
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        closeModal(); 
                        
                        if(res.status === "success"){
                            if(res.pdf_url) {
                                openPdfViewerModal(res.pdf_url);
                                // Optional: reload halaman di background untuk update riwayat
                                // window.location.reload(); 
                            } else {
                                alert("Laporan konseling berhasil disimpan! Namun, gagal mendapatkan URL PDF.");
                            }
                        }
                        else {
                            alert("Gagal menyimpan laporan: " + res.message);
                        }
                    },

                    error: function(xhr){
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        
                        let errorMessage = "Terjadi error saat mengirim data (Kesalahan Jaringan/Server).";
                        try {
                            const errorJson = JSON.parse(xhr.responseText);
                            if (errorJson && errorJson.message) {
                                errorMessage = "Gagal menyimpan: " + errorJson.message;
                            } else {
                                console.error("AJAX Error Response:", xhr.responseText);
                                errorMessage += "\n\nDetail Error (Cek Konsol Browser untuk detail penuh).";
                            }
                        } catch (e) {
                            console.error("AJAX Error Response (Raw):", xhr.responseText);
                            errorMessage += "\n\nTerdeteksi Fatal Error di Server! Cek konsol browser untuk detail.";
                        }
                        alert(errorMessage);
                    }
                });
            });

        });


        document.addEventListener('DOMContentLoaded', () => {
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);
            
            const tableContainer = document.querySelector('.overflow-x-auto');
            if (tableContainer) {
                tableContainer.addEventListener('scroll', function() {
                    const stickyCells = this.querySelectorAll('.sticky-col');
                    const scrollLeft = this.scrollLeft;
                    stickyCells.forEach(cell => {
                        cell.style.transform = `translateX(${scrollLeft}px)`;
                    });
                });
            }
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="md:hidden fixed top-0 left-0 w-full bg-white shadow-md z-50 flex items-center justify-between h-[56px] px-4">
        <div class="flex items-center space-x-2">
            <button onclick="toggleMenu()" class="text-gray-600 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <span class="text-lg font-semibold primary-color">BK SMKN 2</span>
        </div>
        <a href="dashboard.php" class="text-gray-600"><i class="fas fa-home"></i></a>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-40 md:hidden"></div>

    <div id="mobileMenu" class="fade-slide hidden fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Dashboard
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
                    <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
                </span>
                <i id="profilingSubmenuMobileIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
            </div>
        </div>
        <div id="profilingSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
            <a href="hasil_tes.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'hasil_tes.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-list-alt mr-2"></i> Data Hasil Persiswa
            </a>
            <a href="rekap_kelas.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'rekap_kelas.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-chart-bar mr-2"></i> Data Hasil Perkelas
            </a>
        </div>
        <hr class="border-gray-200">

        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_program_bk_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('programBkSubmenuMobile')">
            <div class="flex items-center justify-between">
                <span class="flex items-center font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i> Program BK
                </span>
                <i id="programBkSubmenuMobileIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_program_bk_active ? 'fa-chevron-up' : ''; ?>"></i>
            </div>
        </div>
        <div id="programBkSubmenuMobile" class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_program_bk_active ? '' : 'hidden'; ?>">
            <a href="konselingindividu.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'konselingindividu.php' ? 'text-indigo-600 font-semibold' : ''; ?>">
                <i class="fas fa-user-friends mr-2"></i> Konseling Individu
            </a>
             <a href="konselingkelompok.php" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-users mr-2"></i> Konseling Kelompok
            </a>
            <a href="#" class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-users mr-2"></i> Bimbingan Kelompok
            </a>
        </div>
        <hr class="border-gray-200">

        <a href="logout.php" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-medium flex items-center justify-center">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>

    <div class="flex flex-grow">
        
        <aside id="sidebar" class="no-print hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white w-[260px] flex-shrink-0 fixed h-screen top-0 left-0 overflow-y-auto">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-tie text-lg"></i>
                    </div>
                    <div>
                        <span class="text-base font-semibold block"><?= htmlspecialchars($nama_pengguna) ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                
                <div class="cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                        <span class="flex-item">
                            <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
                        </span>
                        <i id="profilingSubmenuDesktopIcon" class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"></i>
                    </div>
                </div>
                <div id="profilingSubmenuDesktop" class="pl-8 space-y-1 <?php echo $is_profiling_active ? '' : 'hidden'; ?>">
                    <a href="hasil_tes.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'hasil_tes.php' ? 'text-white font-semibold' : ''; ?>">
                        <i class="fas fa-list-alt mr-3 w-4"></i> Data Hasil Persiswa
                    </a>
                    <a href="rekap_kelas.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'rekap_kelas.php' ? 'text-white font-semibold' : ''; ?>">
                        <i class="fas fa-chart-bar mr-3 w-4"></i> Data Hasil Perkelas
                    </a>
                </div>
                
                <div class="cursor-pointer active" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200 primary-bg">
                        <span class="flex-item">
                            <i class="fas fa-calendar-alt mr-2"></i> Program BK
                        </span>
                        <i id="programBkSubmenuDesktopIcon" class="fas fa-chevron-up text-xs ml-2 transition-transform duration-300"></i>
                    </div>
                </div>
                <div id="programBkSubmenuDesktop" class="pl-8 space-y-1">
                    <a href="konselingindividu.php" class="flex items-center px-4 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition duration-200 font-semibold">
                        <i class="fas fa-user-friends mr-3 w-4"></i> Konseling Individu
                    </a>
                    <a href="konselingkelompok.php" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 w-4"></i> Konseling Kelompok
                    </a>
                    <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200">
                        <i class="fas fa-users mr-3 w-4"></i> Bimbingan Kelompok
                    </a>
                </div>

                <div class="mt-auto pt-4 border-t border-white/10">
                    <a href="logout.php" class="flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-600/50 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <main class="content-wrapper flex-grow p-4 md:p-8 w-full md:ml-[260px] pt-16 md:pt-8">
            <div class="max-w-full">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4 md:mb-0">
                        <i class="fas fa-user-friends mr-2 primary-color"></i> Daftar Siswa untuk Konseling Individu
                    </h1>
                </div>

                <div class="no-print bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Filter Siswa</h2>
                    <form method="GET" action="konselingindividu.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <input type="hidden" name="limit" value="<?= $limit_per_page ?>">

                        <div class="md:col-span-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Nama / NIS</label>
                            <input type="text" name="search" id="search" placeholder="Nama atau NIS Siswa" 
                                class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#2F6C6E] focus:border-[#2F6C6E]"
                                value="<?= htmlspecialchars($filter_search) ?>">
                        </div>

                        <div>
                            <label for="kelas" class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                            <select name="kelas" id="kelas" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#2F6C6E] focus:border-[#2F6C6E]">
                                <option value="">Semua Kelas</option>
                                <?php foreach($kelas_options as $kelas): ?>
                                    <option value="<?= $kelas ?>" <?= ($filter_kelas == $kelas) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
                            <select name="jurusan" id="jurusan" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#2F6C6E] focus:border-[#2F6C6E]">
                                <option value="">Semua Jurusan</option>
                                <?php foreach($jurusan_options as $jurusan): ?>
                                    <option value="<?= $jurusan ?>" <?= ($filter_jurusan == $jurusan) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jurusan) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tahun" class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                            <select name="tahun" id="tahun" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#2F6C6E] focus:border-[#2F6C6E]">
                                <option value="">Semua Tahun</option>
                                <?php foreach($data_tahun as $tahun): ?>
                                    <option value="<?= $tahun['id_tahun'] ?>" <?= ($filter_tahun == $tahun['id_tahun']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tahun['tahun']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex space-x-2">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 flex items-center text-sm">
                                <i class="fas fa-filter mr-1"></i> Terapkan Filter
                            </button>
                            <a href="konselingindividu.php" class="bg-gray-400 text-white px-4 py-2 rounded-lg hover:bg-gray-500 transition duration-200 flex items-center text-sm">
                                <i class="fas fa-sync-alt mr-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-md">
                    <div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200"> 
                        <table class="min-w-full divide-y divide-gray-200 data-table-report">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas & Jurusan</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pertemuan Ke-</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Panggilan Ke-</th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (mysqli_num_rows($result_siswa) > 0): ?>
                                    <?php 
                                    $no = $start_from + 1;
                                    while($data = mysqli_fetch_assoc($result_siswa)): 
                                        $latest_session = get_latest_session_data($koneksi, $data['id_siswa']);
                                        $pertemuan_ke = $latest_session['pertemuan_ke'];
                                        $panggilan_ke = $latest_session['panggilan_ke'];
                                    ?>
                                    <tr>
                                        <td class="px-2 py-4 whitespace-nowrap text-center text-sm font-medium"><?= $no++ ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($data['nama']) ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($data['kelas']) ?> <?= htmlspecialchars($data['jurusan']) ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($data['nis']) ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?= $pertemuan_ke ?: '0' ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500"><?= $panggilan_ke ?: '0' ?></td>
                                        
                                        <td class="px-3 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <button 
                                                onclick="openModal(
                                                    '<?= $data['id_siswa'] ?>', 
                                                    '<?= htmlspecialchars($data['nama'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['kelas'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['jurusan'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['nis'], ENT_QUOTES) ?>',
                                                    '<?= $pertemuan_ke ?>',
                                                    '<?= $panggilan_ke ?>'
                                                )"
                                                class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600 transition duration-200 text-xs">
                                                <i class="fas fa-file-alt mr-1"></i> Buat Laporan
                                            </button>
                                            <a href="riwayat_konseling.php?id_siswa=<?= $data['id_siswa'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600 transition duration-200 text-xs mt-1 md:mt-0 md:ml-1 inline-block">
                                                <i class="fas fa-list-ul mr-1"></i> Riwayat
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Tidak ada data siswa yang ditemukan dengan filter ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="no-print mt-6 flex flex-col md:flex-row justify-between items-center space-y-3 md:space-y-0">
                        <span class="text-sm text-gray-700 text-center md:text-left">
                            Menampilkan <?= mysqli_num_rows($result_siswa) ?> dari total <?= $row_count ?> siswa. (Halaman <?= $current_page_num ?>/<?= $total_pages ?>)
                            <span class="block text-xs text-gray-500">(Batas: <?= $limit_per_page ?> baris)</span>
                        </span>
                        
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($current_page_num > 1): ?>
                            <a href="<?= get_pagination_url($current_page_num - 1, $current_filters) ?>" 
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left h-5 w-5"></i>
                            </a>
                            <?php endif; ?>
                            <?php
                            $start_loop = max(1, $current_page_num - 2);
                            $end_loop = min($total_pages, $current_page_num + 2);
                            
                            if ($start_loop > 1) {
                                echo '<a href="' . get_pagination_url(1, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($start_loop > 2) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                            }

                            for ($i = $start_loop; $i <= $end_loop; $i++):
                            ?>
                            <a href="<?= get_pagination_url($i, $current_filters) ?>" 
                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                                <?= ($i == $current_page_num) ? 'z-10 primary-bg border-opacity-70 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; 

                            if ($end_loop < $total_pages) {
                                if ($end_loop < $total_pages - 1) {
                                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                }
                                echo '<a href="' . get_pagination_url($total_pages, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                            }
                            ?>
                            <?php if ($current_page_num < $total_pages): ?>
                            <a href="<?= get_pagination_url($current_page_num + 1, $current_filters) ?>" 
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right h-5 w-5"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <footer class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>

    <div id="konselingModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Buat Laporan Sesi Konseling</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form id="konselingForm" onsubmit="return false;">
                    <input type="hidden" name="id_siswa" id="id_siswa">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 p-4 border rounded-lg bg-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Nama Siswa:</p>
                            <p id="siswa_nama" class="text-lg font-bold text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Kelas & Jurusan:</p>
                            <p id="siswa_kelas_jurusan" class="text-lg font-bold text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">NIS:</p>
                            <p id="siswa_nis" class="text-lg font-bold text-gray-900"></p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Sesi Berikutnya:</p>
                            <p class="text-lg font-bold text-gray-900">
                                Pertemuan Ke-<span id="pertemuan_display">1</span> | 
                                Panggilan Ke-<span id="panggilan_display">1</span>
                            </p>
                            <input type="hidden" name="pertemuan_ke" id="pertemuan_ke">
                            <input type="hidden" name="panggilan_ke" id="panggilan_ke">
                        </div>
                    </div>

                    <h4 class="text-lg font-semibold mb-4 text-gray-800">Detail Pelaksanaan Konseling</h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="tanggal_pelaksanaan" class="block text-sm font-medium text-gray-700">Tanggal Pelaksanaan</label>
                            <input type="date" name="tanggal_pelaksanaan" id="tanggal_pelaksanaan" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="waktu_durasi" class="block text-sm font-medium text-gray-700">Waktu/Durasi</label>
                            <select name="waktu_durasi" id="waktu_durasi" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                <option value="">Pilih Durasi</option>
                                <?php foreach($waktu_durasi_options as $durasi): ?>
                                    <option value="<?= $durasi ?> Menit" <?= ($durasi == 45) ? 'selected' : '' ?>><?= $durasi ?> Menit</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="tempat" class="block text-sm font-medium text-gray-700">Tempat</label>
                            <input type="text" name="tempat" id="tempat" value="Ruang BK" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="gejala_nampak" class="block text-sm font-medium text-gray-700">Gejala yang Nampak</label>
                        <textarea name="gejala_nampak" id="gejala_nampak" rows="3" required
                            class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="atas_dasar" class="block text-sm font-medium text-gray-700">Atas Dasar</label>
                            <input type="text" name="atas_dasar" id="atas_dasar" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="pendekatan_konseling" class="block text-sm font-medium text-gray-700">Pendekatan Konseling</label>
                            <input type="text" name="pendekatan_konseling" id="pendekatan_konseling" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="teknik_konseling" class="block text-sm font-medium text-gray-700">Teknik Konseling</label>
                            <input type="text" name="teknik_konseling" id="teknik_konseling" required
                                class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>

                    </div>
                    
                    <div class="mt-4">
                        <label for="hasil_dicapai" class="block text-sm font-medium text-gray-700">Hasil yang Dicapai</label>
                        <textarea name="hasil_dicapai" id="hasil_dicapai" rows="3" required
                            class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div>
                        <label class="mt-4 font-medium text-sm">Nama Guru:</label>
                        <input type="text" name="nama_guru" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                               placeholder="Isi nama guru yang melakukan laporan"
                               value="<?= htmlspecialchars($nama_pengguna) ?>">
                    </div>

                    <div>
                        <label class="mt-4 font-medium text-sm">NIP Guru BK/Konselor (Opsional):</label>
                        <input type="text" name="nip_guru_bk" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500" 
                               placeholder="Boleh dikosongkan">
                    </div>
                    
                    <input type="hidden" name="status_konseling" value="Proses">
                    <input type="hidden" name="no_input" value="AUTO-GENERATED">


                    <div class="mt-6 pt-4 border-t flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Batal</button>
                        <button type="submit" id="submitBtn" class="px-4 py-2 primary-bg text-white rounded-lg hover:bg-[#3C7F81]">
                            <i class="fas fa-save mr-1"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[905px] flex flex-col transform scale-100 transition-all">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="pdfIframeTitle" class="text-xl font-semibold text-gray-800">Laporan Konseling Individu</h3>
                <div class="space-x-3 flex items-center">
                    <button onclick="closePdfViewerModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[65vh] border-0" title="PDF Viewer"></iframe>
            </div>

            <div class="px-6 py-3 border-t flex justify-end space-x-3 bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closePdfViewerModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </button>
                <a id="exportPdfBtn" href="#" target="_blank" class="px-4 hidden py-2 primary-bg text-white rounded-lg hover:bg-[#3C7F81]">
                    <i class="fas fa-file-pdf mr-1"></i> Ekspor ke PDF
                </a>
            </div>
            
        </div>
    </div>
    </body>
</html>