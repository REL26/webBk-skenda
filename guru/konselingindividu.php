
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

$is_mobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $is_mobile = preg_match('/(android|iphone|ipad|mobile|tablet)/i', $user_agent);
}

if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
    $limit_per_page = (int)$_GET['limit'];
    if (!in_array($limit_per_page, [15, 20, 40])) {
        $limit_per_page = $is_mobile ? 15 : 40;
    }
} else {
    $limit_per_page = $is_mobile ? 15 : 40;
}

$current_page_num = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
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

function get_latest_session_data($koneksi, $id_siswa){
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konseling Individu | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * { font-family: 'Inter', sans-serif; }

        :root {
    --primary: #0F3A3A;
    --primary-dark: #0B2E2E;
    --primary-light: #123E44;
    --accent: #5FA8A1;
    --accent-dark: #4C8E89;
    --white: #FFFFFF;
    --gray-50: #F9FAFB;
    --gray-200: #E5E7EB;
    --success: #4C8E89;
    --warning: #5FA8A1;
    --danger: #9B2C2C;
}
        .primary-color { color: #0F3A3A; }
        .primary-bg { background-color: #123E44; }
        .secondary-bg { background-color: #E6EEF0; }
        
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
            height: 100%; 
            width: 4px; 
            background: var(--accent); 
            transform: scaleY(0); 
            transition: transform 0.3s ease; 
        }

        .nav-item:hover::before, 
        .nav-item.active::before { 
            transform: scaleY(1); 
        }

        .nav-item.active { 
            background-color: var(--primary-light);
        }

        .nav-item.active > div:first-child,
        .nav-item.active { 
            background-color: #3C7F81 !important; 
            color: white !important;
        }

        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            opacity: 0;
        }
        .modal.open {
            visibility: visible;
            opacity: 1;
        }

        .data-table-report { min-width: 800px; }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }

        .btn-action {
            transition: all 0.2s ease;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }

       .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(47, 108, 110, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out;
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
            document.getElementById('modalTitle').textContent = `Buat Laporan Sesi Konseling - ${nama_siswa}`;
            
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

        function openPdfViewerModal(pdfUrl) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            const exportBtn = document.getElementById('exportPdfBtn');
            
            document.getElementById('pdfIframeTitle').textContent = 'Laporan Konseling Individu';
            iframe.src = pdfUrl;
            exportBtn.href = pdfUrl;

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
                const originalText = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

                $.ajax({
                    url: "laporan_individukon.php",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",

                    success: function(res){
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                        closeModal(); 
                        
                        if(res.status === "success"){
                            if(res.pdf_url) {
                                openPdfViewerModal(res.pdf_url);
                            } else {
                                alert("Laporan konseling berhasil disimpan! Namun, gagal mendapatkan URL PDF.");
                            }
                        }
                        else {
                            alert("Gagal menyimpan laporan: " + res.message);
                        }
                    },

                    error: function(xhr){
                        submitButton.innerHTML = originalText;
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
            
            document.querySelectorAll('.animate-slide-in').forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

   <header class="md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg primary-bg flex items-center justify-center shadow-md">
            <i class="fas fa-user-tie text-white"></i>
        </div>

    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl max-h-[90vh] flex flex-col transform scale-100 transition-all">
            
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="pdfIframeTitle" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-file-pdf mr-3"></i> Laporan Konseling Individu
                </h3>
                <button onclick="closePdfViewerModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-full border-0" title="PDF Viewer"></iframe>
            </div>

            <div class="sticky bottom-0 px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-200 rounded-b-2xl">
                <button type="button" onclick="closePdfViewerModal()" class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md btn-action">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </button>
                <a id="exportPdfBtn" href="#" target="_blank" class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition font-semibold shadow-md btn-action inline-flex items-center">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
            </div>
            
        </div>
    </div>
</body>
</html>
             <div>
            <strong class="text-sm font-bold text-gray-800">Guru BK</strong>
            <p class="text-xs text-gray-500">SMKN 2 BJM</p>
        </div>
    </div>

    <button onclick="toggleMenu()" class="text-gray-700 text-xl p-2 hover:bg-gray-100 rounded-lg transition">
        <i class="fas fa-bars"></i>
    </button>
</header>
    <div id="menuOverlay" class="no-print hidden fixed inset-0 bg-black/50 z-20 md:hidden" onclick="toggleMenu()"></div>
    
    <div id="mobileMenu" class="no-print fade-slide hidden fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm">
        <a href="dashboard.php" class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition">
            <i class="fas fa-home mr-2"></i> Beranda
        </a>
        <hr class="border-gray-200">
        
        <div class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuMobile')">
            <div class="flex justify-between">
                <span class="flex font-medium">
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
            <div class="flex justify-between">
                <span class="flex font-medium">
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
        
        <aside id="sidebar" class="no-print sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white">
            <div class="px-6 py-6 border-b border-white/10">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-lg">
                        <i class="fas fa-user-tie text-xl text-white"></i>
                    </div>
                    <div>
                        <strong class="text-base font-bold block">Guru BK</strong>
                        <span class="text-xs text-white/80">SMKN 2 Banjarmasin</span>
                    </div>
                </div>
            </div>
            
            <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
                <a href="dashboard.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
                
                <div class="nav-item cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>" onclick="toggleSubMenu('profilingSubmenuDesktop')">
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
                
                <div class="nav-item cursor-pointer active" onclick="toggleSubMenu('programBkSubmenuDesktop')">
                    <div class="flex items-center justify-between px-4 py-3 text-sm font-medium text-white hover:bg-white/10 rounded-lg transition duration-200">
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
                    <a href="logout.php" class="nav-item flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-600/50 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex-grow p-4 sm:p-6 lg:p-8 md:ml-[260px] w-full">
            
            <div class="mb-8 animate-slide-in">
                <h2 class="text-3xl font-extrabold text-gray-800 mb-2 flex items-center">
                    <i class="fas fa-user-friends primary-color mr-3"></i> Konseling Individu
                </h2>
                <p class="text-gray-600">Kelola dan buat laporan konseling individu untuk siswa</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stat-card p-5 rounded-xl shadow-md border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Siswa</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $row_count; ?></h3>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                 <div class="stat-card p-5 rounded-xl shadow-md border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Halaman</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $current_page_num; ?>/<?php echo $total_pages; ?></h3>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-file-alt text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card p-5 rounded-xl shadow-md border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Data per Halaman</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $limit_per_page; ?></h3>
                        </div>
                        <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-list text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="no-print bg-white p-6 rounded-xl shadow-lg mb-6 border border-gray-200 animate-slide-in">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-filter primary-color mr-2"></i> Filter Pencarian Siswa
                </h3>
                <form method="GET" action="konselingindividu.php" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <input type="hidden" name="limit" value="<?= $limit_per_page ?>">

                    <div class="md:col-span-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i> Cari Nama / NIS
                        </label>
                        <input type="text" name="search" id="search" placeholder="Masukkan nama atau NIS" 
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            value="<?= htmlspecialchars($filter_search) ?>">
                    </div>

                    <div>
                        <label for="kelas" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-school mr-1"></i> Kelas
                        </label>
                        <select name="kelas" id="kelas" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Kelas</option>
                            <?php foreach($kelas_options as $kelas): ?>
                                <option value="<?= $kelas ?>" <?= ($filter_kelas == $kelas) ? 'selected' : '' ?>>
                                    Kelas <?= htmlspecialchars($kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-graduation-cap mr-1"></i> Jurusan
                        </label>
                        <select name="jurusan" id="jurusan" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Jurusan</option>
                            <?php foreach($jurusan_options as $jurusan): ?>
                                <option value="<?= $jurusan ?>" <?= ($filter_jurusan == $jurusan) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($jurusan) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tahun" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i> Tahun Ajaran
                        </label>
                        <select name="tahun" id="tahun" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="">Semua Tahun</option>
                            <?php foreach($data_tahun as $tahun): ?>
                                <option value="<?= $tahun['id_tahun'] ?>" <?= ($filter_tahun == $tahun['id_tahun']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tahun['tahun']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
                            <i class="fas fa-filter mr-2"></i> Terapkan
                        </button>
                        <a href="konselingindividu.php" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
                            <i class="fas fa-sync-alt mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
                
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 animate-slide-in">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 data-table-report">
                        <thead class="primary-bg">
                            <tr>
                                <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">No</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Siswa</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Kelas & Jurusan</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">NIS</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Pertemuan Ke-</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Panggilan Ke-</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider">Aksi</th>
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
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-700"><?= $no++ ?></td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                                    <?= strtoupper(substr($data['nama'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($data['nama']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars($data['kelas']) ?> <?= htmlspecialchars($data['jurusan']) ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium"><?= htmlspecialchars($data['nis']) ?></td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-green-100 text-green-800">
                                            <?= $pertemuan_ke ?: '0' ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-purple-100 text-purple-800">
                                            <?= $panggilan_ke ?: '0' ?>
                                        </span>
                                    </td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex flex-col md:flex-row gap-2 justify-center">
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
                                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200 text-xs font-semibold shadow-md btn-action">
                                                <i class="fas fa-file-alt mr-1"></i> Buat Laporan
                                            </button>
                                            <a href="riwayat_konseling.php?id_siswa=<?= $data['id_siswa'] ?>" 
                                               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200 text-xs font-semibold shadow-md btn-action inline-flex items-center justify-center">
                                                <i class="fas fa-history mr-1"></i> Riwayat
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-search text-5xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-semibold">Tidak ada data siswa ditemukan</p>
                                            <p class="text-sm mt-2">Coba ubah kriteria filter pencarian Anda</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="no-print mt-6 flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-700 text-center md:text-left">
                        <p class="font-semibold">Menampilkan <span class="text-blue-600"><?= mysqli_num_rows($result_siswa) ?></span> dari <span class="text-blue-600"><?= $row_count ?></span> total siswa</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Halaman <?= $current_page_num ?> dari <?= $total_pages ?> 
                            <span class="hidden md:inline">(<?= $limit_per_page ?> baris per halaman)</span>
                            <span class="md:hidden">(<?= $limit_per_page ?> data - Mode Mobile)</span>
                        </p>
                    </div>
                    
                    <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($current_page_num > 1): ?>
                        <a href="<?= get_pagination_url($current_page_num - 1, $current_filters) ?>" 
                            class="relative inline-flex items-center px-2 md:px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $pages_to_show = $is_mobile ? 1 : 2;
                        $start_loop = max(1, $current_page_num - $pages_to_show);
                        $end_loop = min($total_pages, $current_page_num + $pages_to_show);
                        
                        if ($start_loop > 1) {
                            echo '<a href="' . get_pagination_url(1, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-3 md:px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">1</a>';
                            if ($start_loop > 2) {
                                echo '<span class="relative inline-flex items-center px-2 md:px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            }
                        }

                        for ($i = $start_loop; $i <= $end_loop; $i++):
                        ?>
                        <a href="<?= get_pagination_url($i, $current_filters) ?>" 
                            class="relative inline-flex items-center px-3 md:px-4 py-2 border text-sm font-semibold transition
                            <?= ($i == $current_page_num) ? 'z-10 primary-bg text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; 

                        if ($end_loop < $total_pages) {
                            if ($end_loop < $total_pages - 1) {
                                echo '<span class="relative inline-flex items-center px-2 md:px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            }
                            echo '<a href="' . get_pagination_url($total_pages, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-3 md:px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($current_page_num < $total_pages): ?>
                        <a href="<?= get_pagination_url($current_page_num + 1, $current_filters) ?>" 
                            class="relative inline-flex items-center px-2 md:px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <footer class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin.
    </footer>

    <div id="konselingModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
            <div class="sticky top-0 bg-gradient-to-r from-green-800 to-green-600 px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-clipboard-list mr-3"></i> Buat Laporan Konseling
                </h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div class="p-8">
                <form id="konselingForm" onsubmit="return false;">
                    <input type="hidden" name="id_siswa" id="id_siswa">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 border-2 border-green-200 rounded-xl bg-gradient-to-br from-green-50 to-blue-50">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-600 flex items-center">
                                <i class="fas fa-user mr-2 text-green-600"></i> Nama Siswa
                            </p>
                            <p id="siswa_nama" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-600 flex items-center">
                                <i class="fas fa-school mr-2 text-blue-600"></i> Kelas & Jurusan
                            </p>
                            <p id="siswa_kelas_jurusan" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-600 flex items-center">
                                <i class="fas fa-id-card mr-2 text-purple-600"></i> NIS
                            </p>
                            <p id="siswa_nis" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-gray-600 flex items-center">
                                <i class="fas fa-calendar-check mr-2 text-orange-600"></i> Sesi Berikutnya
                            </p>
                            <p class="text-xl font-bold text-gray-900">
                                Pertemuan <span id="pertemuan_display" class="text-green-600">1</span> | 
                                Panggilan <span id="panggilan_display" class="text-blue-600">1</span>
                            </p>
                            <input type="hidden" name="pertemuan_ke" id="pertemuan_ke">
                            <input type="hidden" name="panggilan_ke" id="panggilan_ke">
                        </div>
                    </div>

                    <h4 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b-2 border-gray-200 pb-3">
                        <i class="fas fa-edit primary-color mr-2"></i> Detail Pelaksanaan Konseling
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="tanggal_pelaksanaan" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1"></i> Tanggal Pelaksanaan
                            </label>
                            <input type="date" name="tanggal_pelaksanaan" id="tanggal_pelaksanaan" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div>
                            <label for="waktu_durasi" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-1"></i> Waktu/Durasi
                            </label>
                            <select name="waktu_durasi" id="waktu_durasi" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                <option value="">Pilih Durasi</option>
                                <?php foreach($waktu_durasi_options as $durasi): ?>
                                    <option value="<?= $durasi ?> Menit" <?= ($durasi == 45) ? 'selected' : '' ?>><?= $durasi ?> Menit</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="tempat" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i> Tempat
                            </label>
                            <input type="text" name="tempat" id="tempat" value="Ruang BK" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="gejala_nampak" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-eye mr-1"></i> Gejala yang Nampak
                        </label>
                        <textarea name="gejala_nampak" id="gejala_nampak" rows="3" required placeholder="Deskripsikan gejala atau perilaku yang terlihat..."
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="atas_dasar" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-info-circle mr-1"></i> Atas Dasar
                            </label>
                            <input type="text" name="atas_dasar" id="atas_dasar" required placeholder="Atas dasar siapa?"
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                        
                        <div>
                            <label for="pendekatan_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-users mr-1"></i> Pendekatan Konseling
                            </label>
                            <input type="text" name="pendekatan_konseling" id="pendekatan_konseling" required placeholder="Teknik pendekatan apa yang digunakan?"
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div class="md:col-span-2">
                            <label for="teknik_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tools mr-1"></i> Teknik Konseling
                            </label>
                            <input type="text" name="teknik_konseling" id="teknik_konseling" required placeholder="Teknik konseling apa yang digunakan?"
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="hasil_dicapai" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-1"></i> Hasil yang Dicapai
                        </label>
                        <textarea name="hasil_dicapai" id="hasil_dicapai" rows="3" required placeholder="Deskripsikan hasil atau progress yang dicapai dalam sesi ini..."
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-tie mr-1"></i> Nama Guru
                            </label>
                            <input type="text" name="nama_guru" placeholder="Nama guru yang melakukan konseling" 
                                   value=""
                                   class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-id-badge mr-1"></i> NIP Guru BK <span class="text-xs text-gray-500">(Opsional)</span>
                            </label>
                            <input type="text" name="nip_guru_bk" placeholder="Boleh dikosongkan"
                                   class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                    </div>
                    
                    <input type="hidden" name="status_konseling" value="Proses">
                    <input type="hidden" name="no_input" value="AUTO-GENERATED">

                    <div class="mt-8 pt-6 border-t-2 border-gray-200 flex flex-col md:flex-row justify-end gap-3">
                        <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md btn-action">
                            <i class="fas fa-times mr-2"></i> Batal
                        </button>
                        <button type="submit" id="submitBtn" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg transition font-semibold shadow-md btn-action">
                            <i class="fas fa-save mr-2"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
