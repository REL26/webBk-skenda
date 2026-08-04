<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';
if (isset($_GET['reset'])) {
    unset($_SESSION['filter_ht_nama']);
    unset($_SESSION['filter_ht_kelas']);
    unset($_SESSION['filter_ht_jurusan']);
    unset($_SESSION['filter_ht_tahun']);
    unset($_SESSION['filter_ht_nis']);
    unset($_SESSION['filter_ht_gb_status']);
    unset($_SESSION['filter_ht_kc_status']);
    unset($_SESSION['filter_ht_kp_status']);
    unset($_SESSION['filter_ht_asesmen_status']);
    unset($_SESSION['filter_ht_gender']);
    header("Location: hasil_tes.php");
    exit;
}

if (
    isset($_GET['nama']) || isset($_GET['kelas']) || isset($_GET['jurusan']) || 
    isset($_GET['tahun']) || isset($_GET['nis']) || isset($_GET['gb_status']) || 
    isset($_GET['kc_status']) || isset($_GET['kp_status']) || isset($_GET['asesmen_status']) || 
    isset($_GET['gender'])
) {
    $_SESSION['filter_ht_nama']           = trim($_GET['nama'] ?? '');
    $_SESSION['filter_ht_kelas']          = trim($_GET['kelas'] ?? '');
    $_SESSION['filter_ht_jurusan']        = trim($_GET['jurusan'] ?? '');
    $_SESSION['filter_ht_tahun']          = trim($_GET['tahun'] ?? '');
    $_SESSION['filter_ht_nis']            = trim($_GET['nis'] ?? '');
    $_SESSION['filter_ht_gb_status']      = trim($_GET['gb_status'] ?? '');
    $_SESSION['filter_ht_kc_status']      = trim($_GET['kc_status'] ?? '');
    $_SESSION['filter_ht_kp_status']      = trim($_GET['kp_status'] ?? '');
    $_SESSION['filter_ht_asesmen_status'] = trim($_GET['asesmen_status'] ?? '');
    $_SESSION['filter_ht_gender']         = trim($_GET['gender'] ?? '');
}

$filter_nama           = isset($_SESSION['filter_ht_nama'])           ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_nama'])           : '';
$filter_kelas          = isset($_SESSION['filter_ht_kelas'])          ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_kelas'])          : '';
$filter_jurusan        = isset($_SESSION['filter_ht_jurusan'])        ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_jurusan'])        : '';
$filter_tahun          = isset($_SESSION['filter_ht_tahun'])          ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_tahun'])          : '';
$filter_nis            = isset($_SESSION['filter_ht_nis'])            ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_nis'])            : '';
$filter_gb_status      = isset($_SESSION['filter_ht_gb_status'])      ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_gb_status'])      : '';
$filter_kc_status      = isset($_SESSION['filter_ht_kc_status'])      ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_kc_status'])      : '';
$filter_kp_status      = isset($_SESSION['filter_ht_kp_status'])      ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_kp_status'])      : '';
$filter_asesmen_status = isset($_SESSION['filter_ht_asesmen_status']) ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_asesmen_status']) : '';
$filter_gender         = isset($_SESSION['filter_ht_gender'])         ? mysqli_real_escape_string($koneksi, $_SESSION['filter_ht_gender'])         : '';
$where_clauses = [];
$where_clauses[] = "s.kelas != 'LULUS'";
if (!empty($filter_nama))    $where_clauses[] = "s.nama LIKE '%$filter_nama%'";
if (!empty($filter_kelas))   $where_clauses[] = "s.kelas = '$filter_kelas'";
if (!empty($filter_jurusan)) $where_clauses[] = "s.jurusan = '$filter_jurusan'";
if (!empty($filter_nis))     $where_clauses[] = "s.nis = '$filter_nis'";
if (!empty($filter_tahun))   $where_clauses[] = "t.id_tahun = '$filter_tahun'";
if (!empty($filter_gender))  $where_clauses[] = "s.jenis_kelamin = '$filter_gender'";

if ($filter_gb_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_gayabelajar hg WHERE hg.id_siswa = s.id_siswa)";
} elseif ($filter_gb_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_gayabelajar hg WHERE hg.id_siswa = s.id_siswa)";
}

if ($filter_kc_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_kecerdasan hk WHERE hk.id_siswa = s.id_siswa)";
} elseif ($filter_kc_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_kecerdasan hk WHERE hk.id_siswa = s.id_siswa)";
}

if ($filter_kp_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_kepribadian hp WHERE hp.id_siswa = s.id_siswa)";
} elseif ($filter_kp_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_kepribadian hp WHERE hp.id_siswa = s.id_siswa)";
}

if ($filter_asesmen_status === 'done') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM hasil_asesmen ha WHERE ha.id_siswa = s.id_siswa AND ha.versi = s.kelas)";
} elseif ($filter_asesmen_status === 'not_done') {
    $where_clauses[] = "NOT EXISTS (SELECT 1 FROM hasil_asesmen ha WHERE ha.id_siswa = s.id_siswa AND ha.versi = s.kelas)";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$limit_desktop = 40;
$limit_mobile = 15;

$view_mode = isset($_GET['view']) ? mysqli_real_escape_string($koneksi, trim($_GET['view'])) : 'desktop';
if ($view_mode !== 'mobile') $view_mode = 'desktop';

$limit = ($view_mode === 'mobile') ? $limit_mobile : $limit_desktop;

$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_count = "
    SELECT 
        COUNT(s.id_siswa) AS total_records
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    " . $where_sql;
    
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total_records'];
$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
} elseif ($total_pages == 0) {
    $page = 1;
    $offset = 0;
}

function build_pagination_url($page_num, $view_mode, $filters) {
    $params = $filters;
    $params['page'] = $page_num;
    $params['view'] = $view_mode;
    $params = array_filter($params, function($value) { return $value !== ''; });
    return 'hasil_tes.php?' . http_build_query($params);
}

$current_filters = $_GET;
unset($current_filters['page']);
unset($current_filters['view']);


$mi_mapping = [
    'A' => 'Linguistik', 'B' => 'Logis-Matematis', 'C' => 'Spasial', 'D' => 'Kinestetik-Jasmani',
    'E' => 'Musikal', 'F' => 'Interpersonal', 'G' => 'Intrapersonal', 'H' => 'Naturalis', ''  => 'Belum Tes'
];

$query_siswa = "
    SELECT 
        s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan, s.jenis_kelamin, t.tahun AS tahun_ajaran, 
        (
            SELECT 
                CASE
                    WHEN skor_visual >= skor_auditori AND skor_visual >= skor_kinestetik THEN 'Visual'
                    WHEN skor_auditori >= skor_visual AND skor_auditori >= skor_kinestetik THEN 'Auditori'
                    ELSE 'Kinestetik'
                END 
            FROM hasil_gayabelajar 
            WHERE id_siswa = s.id_siswa 
            ORDER BY tanggal_tes DESC LIMIT 1
        ) AS skor_gb_latest,
        (
            SELECT 
                CASE 
                    WHEN skor_A = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'A'
                    WHEN skor_B = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'B'
                    WHEN skor_C = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'C'
                    WHEN skor_D = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'D'
                    WHEN skor_E = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'E'
                    WHEN skor_F = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'F'
                    WHEN skor_G = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'G'
                    WHEN skor_H = greatest(skor_A, skor_B, skor_C, skor_D, skor_E, skor_F, skor_G, skor_H) THEN 'H'
                    ELSE '' 
                END 
            FROM hasil_kecerdasan 
            WHERE id_siswa = s.id_siswa 
            ORDER BY tanggal_tes DESC LIMIT 1
        ) AS skor_kc_latest,
        (
            SELECT COUNT(*) FROM hasil_kepribadian hp WHERE hp.id_siswa = s.id_siswa
        ) AS status_kepribadian,
        (
            SELECT COUNT(*) FROM hasil_asesmen ha WHERE ha.id_siswa = s.id_siswa AND ha.versi = 'X'
        ) AS status_asesmen_x,
        (
            SELECT COUNT(*) FROM hasil_asesmen ha WHERE ha.id_siswa = s.id_siswa AND ha.versi = 'XI'
        ) AS status_asesmen_xi,
        (
            SELECT COUNT(*) FROM hasil_asesmen ha WHERE ha.id_siswa = s.id_siswa AND ha.versi = 'XII'
        ) AS status_asesmen_xii
    FROM siswa s
    JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    " . $where_sql . " 
    ORDER BY s.nama ASC
    LIMIT $limit OFFSET $offset
";

$result_siswa = mysqli_query($koneksi, $query_siswa);

if (!$result_siswa) {
    die("Query Error: " . mysqli_error($koneksi));
}

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != 'LULUS' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
$kelas_options = mysqli_fetch_all($result_kelas, MYSQLI_ASSOC);
$kelas_options = array_column($kelas_options, 'kelas');

$query_jurusan = "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL ORDER BY jurusan";
$result_jurusan = mysqli_query($koneksi, $query_jurusan);
$jurusan_options = mysqli_fetch_all($result_jurusan, MYSQLI_ASSOC);
$jurusan_options = array_column($jurusan_options, 'jurusan');


$data_siswa = mysqli_fetch_all($result_siswa, MYSQLI_ASSOC);
mysqli_data_seek($result_siswa, 0); 

$current_page = basename($_SERVER['PHP_SELF']);
$is_profiling_active = in_array($current_page, ['hasil_tes.php', 'rekap_kelas.php']);
?>
<!DOCTYPE html>
<html lang="id">
<head>      
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Hasil Persiswa | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .primary-gradient { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        
        .fade-slide { transition: opacity 0.3s ease, transform 0.3s ease; }
        .fade-slide.hidden-transition { opacity: 0; transform: translateY(-20px); pointer-events: none; }
        .fade-slide.active-transition { opacity: 1; transform: translateY(0); pointer-events: auto; }
        
        .status-badge { 
            animation: pulse-subtle 2s ease-in-out infinite; 
        }
        
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--accent);
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        @media (min-width: 768px) {
            .sidebar { width: 260px; flex-shrink: 0; transform: translateX(0) !important; position: fixed !important; height: 100vh; top: 0; left: 0; overflow-y: auto; }
            .content-wrapper { margin-left: 260px; }
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
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content-wrapper { 
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 90vh; 
            overflow-y: auto; 
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .table-container {
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) #f1f1f1;
        }
        
        .table-container::-webkit-scrollbar { height: 8px; }
        .table-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .table-container::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        
        @media (max-width: 767px) {
            body { overflow-x: hidden; }
            .student-card { 
                background: white; 
                border-radius: 1rem; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.07); 
                border-left: 4px solid var(--primary);
                transition: all 0.3s ease;
            }
            .student-card:active {
                transform: scale(0.98);
            }
            .table-container { display: none; }
            .card-container { display: block; }
            .modal-content-wrapper { width: 95vw !important; max-width: 95vw !important; }
            main { padding: 1rem !important; }
        }
        
        @media (min-width: 768px) {
            .table-container { display: block; }
            .card-container { display: none; }
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(47, 108, 110, 0.1);
        }
    </style>

    <script>
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            if (mobileMenu.classList.contains('active-transition')) {
                mobileMenu.classList.remove('active-transition');
                mobileMenu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            } else {
                mobileMenu.classList.remove('hidden-transition');
                mobileMenu.classList.add('active-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
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

        function buildResultCards(idSiswa, statusKc, statusGb, statusKepribadian, statusAsesmenX, statusAsesmenXI, statusAsesmenXII) {
            
    const getTestStatus = (isDone) => {
        const isDoneBool = isDone == 1;
        return {
            text: isDoneBool ? 'Sudah Tes' : 'Belum Tes',
            bg: isDoneBool ? 'bg-emerald-50' : 'bg-red-50',
            textCol: isDoneBool ? 'text-emerald-700' : 'text-red-700',
            borderCol: isDoneBool ? 'border-emerald-200' : 'border-red-200',
            icon: isDoneBool ? 'fas fa-check-circle' : 'fas fa-times-circle'
        };
    };

    const status_kc = getTestStatus(statusKc);
    const status_gb = getTestStatus(statusGb);
    const status_kp = getTestStatus(statusKepribadian);
    const status_ax = getTestStatus(statusAsesmenX);
    const status_axi = getTestStatus(statusAsesmenXI);
    const status_axii = getTestStatus(statusAsesmenXII);

    const menuItems = {

        biodata: {
            title: 'Biodata Siswa',
            icon: 'fas fa-address-card',
            url: `detail_biodata.php?id_siswa=${idSiswa}`,
            description: 'Lihat biodata lengkap siswa',
            terkunci: false,
            isTest: false
        },


        kemampuan: {
            title: 'Tes Kemampuan',
            icon: 'fas fa-brain',
            url: statusKc == 1 
                ? `detail_hasil_kemampuan.php?id_siswa=${idSiswa}&type=kecerdasan` 
                : '#',
            description: 'Hasil tes kecerdasan majemuk',
            status: status_kc,
            belumTes: statusKc == 0,
            isTest: true,
            type: 'kemampuan'
        },


        gayabelajar: {
            title: 'Tes Gaya Belajar',
            icon: 'fas fa-lightbulb',
            url: statusGb == 1 
                ? `detail_hasil_gayabelajar.php?id_siswa=${idSiswa}&type=gayabelajar`
                : '#',
            description: 'Analisis karakter belajar siswa',
            status: status_gb,
            belumTes: statusGb == 0,
            isTest: true,
            type: 'gayabelajar'
        },


        kepribadian: {
            title: 'Tes Kepribadian',
            icon: 'fas fa-user-circle',
            url: statusKepribadian == 1
                ? `detail_hasil_kepribadian.php?id_siswa=${idSiswa}`
                : '#',
            description: 'Kecenderungan Introvert - Ekstrovert siswa',
            status: status_kp,
            belumTes: statusKepribadian == 0,
            isTest: true,
            type: 'kepribadian'
        },


        asesmen_x: {
            title: 'Asesmen BK Kelas X',
            icon: 'fas fa-clipboard-check',
            url: statusAsesmenX == 1
                ? `detail_hasil_asesmen.php?id_siswa=${idSiswa}&versi=X`
                : '#',
            description: 'Asesmen Awal BK  Kelas X',
            status: status_ax,
            belumTes: statusAsesmenX == 0,
            isTest: true,
            type: 'asesmen_x'
        },

        asesmen_xi: {
            title: 'Asesmen BK Kelas XI',
            icon: 'fas fa-clipboard-check',
            url: statusAsesmenXI == 1
                ? `detail_hasil_asesmen.php?id_siswa=${idSiswa}&versi=XI`
                : '#',
            description: 'Asesmen Awal BK  Kelas XI',
            status: status_axi,
            belumTes: statusAsesmenXI == 0,
            isTest: true,
            type: 'asesmen_xi'
        },

        asesmen_xii: {
            title: 'Asesmen BK Kelas XII',
            icon: 'fas fa-clipboard-check',
            url: statusAsesmenXII == 1
                ? `detail_hasil_asesmen.php?id_siswa=${idSiswa}&versi=XII`
                : '#',
            description: 'Asesmen Awal BK  Kelas XII',
            status: status_axii,
            belumTes: statusAsesmenXII == 0,
            isTest: true,
            type: 'asesmen_xii'
        }
    };


    const cardsContainer = document.getElementById('resultCardsContainer');
    cardsContainer.innerHTML = ''; 


    Object.keys(menuItems).forEach(key => {

        const item = menuItems[key];

        const lockedClass = item.terkunci 
            ? 'opacity-60 cursor-not-allowed' 
            : 'card-hover cursor-pointer';


        const linkStart = item.terkunci || item.belumTes
    ? '<div'
    : `<a href="${item.url}"`;


const linkEnd = item.terkunci || item.belumTes
    ? '</div>'
    : '</a>';



        const card = `


${linkStart}

class="
block bg-white rounded-xl border border-gray-200
shadow-sm hover:shadow-lg
transition-all duration-300
${lockedClass}
">


<div class="p-4">


<div class="flex items-center gap-3">


<div class="
w-10 h-10 rounded-lg
bg-gradient-to-br from-[#2F6C6E] to-[#5FA8A1]
flex items-center justify-center
shadow-sm
flex-shrink-0
">

<i class="${item.icon} text-white text-base"></i>

</div>



<div class="flex-1 min-w-0">

<h4 class="
text-sm font-bold text-gray-800 truncate
">

${item.title}

</h4>


<p class="
text-xs text-gray-500
mt-0.5 truncate
">

${item.description}

</p>


</div>




${item.terkunci ?

`
<i class="
fas fa-lock
text-gray-400
text-sm
">
</i>
`

:

''}



</div>





${item.status ? `

<div class="mt-3">


<span class="
inline-flex items-center gap-1
text-xs font-semibold
${item.status.bg}
${item.status.textCol}
border
${item.status.borderCol}
px-2 py-1 rounded-full
">


<i class="${item.status.icon} text-[10px]"></i>

${item.status.text}


</span>


</div>

`:''}






${ item.terkunci ?


`

<div class="
mt-3
pt-3
border-t border-gray-100
text-xs text-gray-400
">

Fitur tersedia nanti

</div>


`


:


`

<div class="
mt-3
pt-3
border-t border-gray-100
space-y-2
">


<div class="
flex justify-between items-center
text-xs font-semibold
text-[#2F6C6E]
">

<span>
${item.belumTes ? 'Belum Ada Hasil Tes' : 'Lihat Detail'}
</span>


<i class="fas fa-arrow-right"></i>


</div>

${
item.isTest ?

`

<button
onclick="openResetModal('${idSiswa}','${key}')"
class="
w-full
py-2
rounded-lg
text-xs
font-semibold
bg-red-50
text-red-600
border border-red-200
hover:bg-red-100
transition
flex items-center justify-center gap-2
"
>

<i class="fas fa-rotate"></i>

Reset ${item.title}

</button>

`

:

''

}


</div>


`
}




</div>


${linkEnd}

`;



cardsContainer.innerHTML += card;


});

}

        function showResultModal(idSiswa, nama, nis, kelas, jurusan, isKecerdasanDone = 0, isGayaBelajarDone = 0, isKepribadianDone = 0, isAsesmenXDone = 0, isAsesmenXIDone = 0, isAsesmenXIIDone = 0) {
            document.getElementById('modalTitle').textContent = nama;
            document.getElementById('modalSubtitle').textContent = 'NIS: ' + nis + ' | ' + kelas + ' ' + jurusan;
            
            buildResultCards(idSiswa, isKecerdasanDone, isGayaBelajarDone, isKepribadianDone, isAsesmenXDone, isAsesmenXIDone, isAsesmenXIIDone); 

            document.getElementById('resultModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function openResetModal(idSiswa, jenisTes){

    let namaTes = {
        kemampuan: 'Tes Kemampuan',
        gayabelajar: 'Tes Gaya Belajar',
        kepribadian: 'Tes Kepribadian',
        asesmen_x: 'Asesmen BK Kelas X',
        asesmen_xi: 'Asesmen BK Kelas XI',
        asesmen_xii: 'Asesmen BK Kelas XII'
    }[jenisTes] || 'Tes ini';


    if(confirm(
        `Reset ${namaTes} siswa ini?\n\nJawaban lama akan dihapus dan siswa harus mengerjakan ulang.`
    )){


        fetch('reset_tes.php',{
            method:'POST',
            headers:{
                'Content-Type':'application/x-www-form-urlencoded'
            },
            body:
            `id_siswa=${idSiswa}&jenis_tes=${jenisTes}`
        })


        .then(res=>res.json())

        .then(data=>{


            if(data.status=="success"){

                alert(
                    namaTes+
                    " berhasil direset"
                );

                location.reload();

            }

        });

    }

}

        function hideResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function checkScreenAndSetView() {
            const params = new URLSearchParams(window.location.search);
            const currentView = params.get('view');
            const isMobile = window.matchMedia('(max-width: 767px)').matches;

            let requiredView = isMobile ? 'mobile' : 'desktop';

            if (currentView !== requiredView) {
                params.set('view', requiredView);
                window.location.replace('?' + params.toString());
            }
        }
        
        function confirmExport(url, total) {
            if (total == 0) {
                 alert("Tidak ada siswa yang ditemukan untuk di-export.");
                 return false;
            }
            const konfirmasi = confirm(`Yakin ingin meng-export biodata dari ${total} siswa yang terfilter? Proses ini akan menghasilkan file ZIP dan mungkin memakan waktu.`);
            
            if (konfirmasi) {
                window.location.href = url;
            }
        }

        function toggleFilters() {
            const filterSection = document.getElementById('filterSection');
            const filterIcon = document.getElementById('filterIcon');
            
            if (filterSection.classList.contains('hidden')) {
                filterSection.classList.remove('hidden');
                filterIcon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                filterSection.classList.add('hidden');
                filterIcon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) mobileMenu.classList.add('hidden-transition');
            
            const overlay = document.getElementById('menuOverlay');
            if (overlay) overlay.addEventListener('click', toggleMenu);

            const modalOverlay = document.getElementById('modalOverlay');
            if (modalOverlay) modalOverlay.addEventListener('click', hideResultModal);

            <?php if ($is_profiling_active): ?>
                document.getElementById('profilingSubmenuDesktop')?.classList.remove('hidden');
                document.getElementById('profilingSubmenuDesktopIcon')?.classList.replace('fa-chevron-down', 'fa-chevron-up');
            <?php endif; ?>
            
            checkScreenAndSetView();
        });
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
       <main class="flex-grow p-4 sm:p-6 lg:p-8 content-wrapper max-w-full">

            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-xl primary-gradient flex items-center justify-center shadow-lg">
                        <i class="fas fa-clipboard-list text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800">Data Hasil Per Siswa</h2>
                        <p class="text-sm text-gray-500">Kelola dan pantau hasil tes siswa</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="stat-card p-5 rounded-xl shadow-md border-l-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Siswa</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_records); ?></h3>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Jumlah seluruh data siswa
                        </p>
                </div>
                
                <div class="stat-card p-5 rounded-xl shadow-md border-l-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Halaman Data</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $page; ?> / <?php echo max($total_pages, 1); ?></h3>
                        </div>
                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-file-alt text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Posisi halaman saat ini
                        </p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg mb-6 border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-200 flex items-center justify-between cursor-pointer" onclick="toggleFilters()">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-[#0F3A3A] flex items-center justify-center">
                            <i class="fas fa-filter text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Filter Data</h3>
                            <p class="text-xs text-gray-500">Klik untuk expand/collapse</p>
                        </div>
                    </div>
                    <i id="filterIcon" class="fas fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                </div>
                
                <div id="filterSection" class="p-6">
                    <form method="GET" action="hasil_tes.php" class="space-y-4">
                        
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            
                            <div> 
                                <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama Siswa</label>
                                <div class="relative">
                                    <input type="text" id="nama" name="nama" placeholder="Cari nama..." value="<?php echo htmlspecialchars($filter_nama); ?>" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                    
                            <div>
                                <label for="nis" class="block text-sm font-semibold text-gray-700 mb-2">NIS</label>
                                <div class="relative">
                                    <input type="text" id="nis" name="nis" placeholder="Cari NIS..." value="<?php echo htmlspecialchars($filter_nis); ?>" class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                    
                            <div>
                                <label for="kelas" class="block text-sm font-semibold text-gray-700 mb-2">Kelas</label>
                                <select id="kelas" name="kelas" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php foreach ($kelas_options as $k): ?>
                                        <option value="<?php echo $k; ?>" <?php echo ($filter_kelas == $k) ? 'selected' : ''; ?>>Kelas <?php echo $k; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    
                            <div>
                                <label for="jurusan" class="block text-sm font-semibold text-gray-700 mb-2">Jurusan</label>
                                <select id="jurusan" name="jurusan" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Jurusan</option>
                                    <?php foreach ($jurusan_options as $j): ?>
                                        <option value="<?php echo $j; ?>" <?php echo ($filter_jurusan == $j) ? 'selected' : ''; ?>><?php echo $j; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                                <select id="gender" name="gender" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Gender</option>
                                    <option value="L" <?php echo ($filter_gender == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="P" <?php echo ($filter_gender == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                    
                            <div>
                                <label for="gb_status" class="block text-sm font-semibold text-gray-700 mb-2">Status Gaya Belajar</label>
                                <select id="gb_status" name="gb_status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="done" <?php echo ($filter_gb_status == 'done') ? 'selected' : ''; ?>>Sudah Tes</option>
                                    <option value="not_done" <?php echo ($filter_gb_status == 'not_done') ? 'selected' : ''; ?>>Belum Tes</option>
                                </select>
                            </div>

                            <div>
                                <label for="kc_status" class="block text-sm font-semibold text-gray-700 mb-2">Status Kemampuan</label>
                                <select id="kc_status" name="kc_status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="done" <?php echo ($filter_kc_status == 'done') ? 'selected' : ''; ?>>Sudah Tes</option>
                                    <option value="not_done" <?php echo ($filter_kc_status == 'not_done') ? 'selected' : ''; ?>>Belum Tes</option>
                                </select>
                            </div>

                            <div>
                                <label for="kp_status" class="block text-sm font-semibold text-gray-700 mb-2">Status Kepribadian</label>
                                <select id="kp_status" name="kp_status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="done" <?php echo ($filter_kp_status == 'done') ? 'selected' : ''; ?>>Sudah Tes</option>
                                    <option value="not_done" <?php echo ($filter_kp_status == 'not_done') ? 'selected' : ''; ?>>Belum Tes</option>
                                </select>
                            </div>

                            <div>
                                <label for="asesmen_status" class="block text-sm font-semibold text-gray-700 mb-2">Status Asesmen BK</label>
                                <select id="asesmen_status" name="asesmen_status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                                    <option value="">Semua Status</option>
                                    <option value="done" <?php echo ($filter_asesmen_status == 'done') ? 'selected' : ''; ?>>Sudah Selesai</option>
                                    <option value="not_done" <?php echo ($filter_asesmen_status == 'not_done') ? 'selected' : ''; ?>>Belum Selesai</option>
                                </select>
                                <p class="text-[11px] text-gray-400 mt-1">Berdasarkan asesmen versi kelas siswa saat ini</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition duration-200 flex items-center gap-2">
                                <i class="fas fa-search"></i> Terapkan Filter
                            </button>
                           <a href="hasil_tes.php?view=<?php echo $view_mode; ?>&reset=1" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg shadow-md hover:shadow-lg transition duration-200 flex items-center gap-2">
    <i class="fas fa-redo"></i> Reset
</a>
                         <?php if ($total_records > 0): ?>
    <div class="flex justify-start md:justify-end justify-end"> 
        <button type="button" 
    onclick="confirmExport('exportsekaligus_cv.php?action=export_all_cv&<?php echo http_build_query($current_filters); ?>', <?php echo $total_records; ?>)" 
    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-200 transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95 group">
    
    <div class="bg-white/20 p-1.5 rounded-lg group-hover:rotate-12 transition-transform">
        <i class="fas fa-file-archive text-lg"></i>
    </div>
    
    <div class="flex flex-col text-left leading-tight">
        <span>Export Biodata Sekaligus</span>
        <span class="text-[10px] font-medium opacity-80 uppercase tracking-wider">Format ZIP (PDF)</span>
    </div>
</button>
    </div>
<?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php 
            $active_filters = [];
            if (!empty($filter_nama)) $active_filters[] = ['label' => 'Nama', 'value' => $filter_nama];
            if (!empty($filter_nis)) $active_filters[] = ['label' => 'NIS', 'value' => $filter_nis];
            if (!empty($filter_kelas)) $active_filters[] = ['label' => 'Kelas', 'value' => $filter_kelas];
            if (!empty($filter_jurusan)) $active_filters[] = ['label' => 'Jurusan', 'value' => $filter_jurusan];
            if (!empty($filter_gender)) $active_filters[] = ['label' => 'Gender', 'value' => $filter_gender == 'L' ? 'Laki-laki' : 'Perempuan'];
            if (!empty($filter_gb_status)) $active_filters[] = ['label' => 'Gaya Belajar', 'value' => $filter_gb_status == 'done' ? 'Sudah Tes' : 'Belum Tes'];
            if (!empty($filter_kc_status)) $active_filters[] = ['label' => 'Kemampuan', 'value' => $filter_kc_status == 'done' ? 'Sudah Tes' : 'Belum Tes'];
            if (!empty($filter_kp_status)) $active_filters[] = ['label' => 'Kepribadian', 'value' => $filter_kp_status == 'done' ? 'Sudah Tes' : 'Belum Tes'];
            if (!empty($filter_asesmen_status)) $active_filters[] = ['label' => 'Asesmen BK', 'value' => $filter_asesmen_status == 'done' ? 'Sudah Selesai' : 'Belum Selesai'];
            
            if (count($active_filters) > 0): ?>
                <div class="mb-6 flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Filter Aktif:</span>
                    <?php foreach ($active_filters as $filter): ?>
                        <span class="filter-tag text-white bg-blue-600 px-3 py-1 rounded-full text-sm flex items-center gap-2">
                            <span class="font-semibold"><?php echo $filter['label']; ?>:</span>
                            <span><?php echo $filter['value']; ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="table-container bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                <?php if (mysqli_num_rows($result_siswa) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-200">
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">NIS</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Jurusan</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Gender</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Gaya Belajar</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Kemampuan</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                $no = $offset + 1;
                                while ($siswa = mysqli_fetch_assoc($result_siswa)): 
                                    $isKecerdasanDone = !empty($siswa['skor_kc_latest']) ? 1 : 0;
                                    $isGayaBelajarDone = !empty($siswa['skor_gb_latest']) ? 1 : 0;
                                    $isKepribadianDone = !empty($siswa['status_kepribadian']) ? 1 : 0;
                                    $isAsesmenXDone = !empty($siswa['status_asesmen_x']) ? 1 : 0;
                                    $isAsesmenXIDone = !empty($siswa['status_asesmen_xi']) ? 1 : 0;
                                    $isAsesmenXIIDone = !empty($siswa['status_asesmen_xii']) ? 1 : 0;
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $no++; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono"><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($siswa['nama']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($siswa['kelas']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($siswa['jurusan']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($siswa['jenis_kelamin'] == 'L'): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">
                                                    <i class="fas fa-mars mr-1"></i> Laki-laki
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-700 border border-pink-200">
                                                    <i class="fas fa-venus mr-1"></i> Perempuan
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($isGayaBelajarDone): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($siswa['skor_gb_latest']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-200">
                                                    <i class="fas fa-times-circle mr-1"></i> Belum Tes
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($isKecerdasanDone): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($mi_mapping[$siswa['skor_kc_latest']]); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-200">
                                                    <i class="fas fa-times-circle mr-1"></i> Belum Tes
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <button onclick="showResultModal(
                                                <?php echo $siswa['id_siswa']; ?>, 
                                                '<?php echo addslashes($siswa['nama']); ?>', 
                                                '<?php echo addslashes($siswa['nis']); ?>', 
                                                '<?php echo addslashes($siswa['kelas']); ?>', 
                                                '<?php echo addslashes($siswa['jurusan']); ?>', 
                                                <?php echo $isKecerdasanDone; ?>, 
                                                <?php echo $isGayaBelajarDone; ?>, 
                                                <?php echo $isKepribadianDone; ?>, 
                                                <?php echo $isAsesmenXDone; ?>, 
                                                <?php echo $isAsesmenXIDone; ?>, 
                                                <?php echo $isAsesmenXIIDone; ?>
                                            )" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-xs font-semibold rounded-lg shadow-md hover:shadow-lg transition duration-200 gap-2">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Tidak Ada Data</h3>
                        <p class="text-sm text-gray-500">Tidak ada siswa ditemukan dengan filter yang dipilih</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-container space-y-4">
                <?php 
                mysqli_data_seek($result_siswa, 0);
                if (mysqli_num_rows($result_siswa) > 0): 
                    while ($siswa = mysqli_fetch_assoc($result_siswa)): 
                        $isKecerdasanDone = !empty($siswa['skor_kc_latest']) ? 1 : 0;
                        $isGayaBelajarDone = !empty($siswa['skor_gb_latest']) ? 1 : 0;
                        $isKepribadianDone = !empty($siswa['status_kepribadian']) ? 1 : 0;
                        $isAsesmenXDone = !empty($siswa['status_asesmen_x']) ? 1 : 0;
                        $isAsesmenXIDone = !empty($siswa['status_asesmen_xi']) ? 1 : 0;
                        $isAsesmenXIIDone = !empty($siswa['status_asesmen_xii']) ? 1 : 0;
                ?>
                    <div class="student-card p-4" onclick="showResultModal(
                        <?php echo $siswa['id_siswa']; ?>, 
                        '<?php echo addslashes($siswa['nama']); ?>', 
                        '<?php echo addslashes($siswa['nis']); ?>', 
                        '<?php echo addslashes($siswa['kelas']); ?>', 
                        '<?php echo addslashes($siswa['jurusan']); ?>', 
                        <?php echo $isKecerdasanDone; ?>, 
                        <?php echo $isGayaBelajarDone; ?>, 
                        <?php echo $isKepribadianDone; ?>, 
                        <?php echo $isAsesmenXDone; ?>, 
                        <?php echo $isAsesmenXIDone; ?>, 
                        <?php echo $isAsesmenXIIDone; ?>
                    )">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-12 h-12 rounded-xl primary-gradient flex items-center justify-center text-white font-bold shadow-md">
                                <?php echo strtoupper(substr($siswa['nama'], 0, 2)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-base font-bold text-gray-800 truncate"><?php echo htmlspecialchars($siswa['nama']); ?></h4>
                                <p class="text-xs text-gray-500 mt-0.5">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($siswa['kelas']); ?> <?php echo htmlspecialchars($siswa['jurusan']); ?>
                                    • 
                                    <?php if ($siswa['jenis_kelamin'] == 'L'): ?>
                                        <span class="text-blue-600"><i class="fas fa-mars"></i> L</span>
                                    <?php else: ?>
                                        <span class="text-pink-600"><i class="fas fa-venus"></i> P</span>
                                    <?php endif; ?>
                                </p>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <?php if ($isGayaBelajarDone): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <i class="fas fa-check-circle mr-1"></i> GB: <?php echo htmlspecialchars($siswa['skor_gb_latest']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                            <i class="fas fa-times-circle mr-1"></i> GB: Belum
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($isKecerdasanDone): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 border border-purple-200">
                                            <i class="fas fa-check-circle mr-1"></i> KC: <?php echo htmlspecialchars(substr($mi_mapping[$siswa['skor_kc_latest']], 0, 8)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                            <i class="fas fa-times-circle mr-1"></i> KC: Belum
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white p-12 rounded-xl shadow-lg text-center border border-gray-200">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Tidak Ada Data</h3>
                        <p class="text-sm text-gray-500">Tidak ada siswa ditemukan dengan filter yang dipilih</p>
                    </div>
                <?php endif; ?>
            </div>
    
            <?php if ($total_pages > 1): ?>
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600 text-center sm:text-left">
                            Menampilkan <span class="font-bold text-gray-800"><?php echo min($limit, $total_records - $offset); ?></span> dari 
                            <span class="font-bold text-gray-800"><?php echo number_format($total_records); ?></span> total data
                        </div>
                        
                        <div class="flex items-center gap-2">

                            <a href="<?php echo build_pagination_url(1, $view_mode, $current_filters); ?>" 
                               class="px-3 py-2 rounded-lg border transition-all duration-200 <?php echo ($page == 1) ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 hover:border-blue-500'; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
   
                            <a href="<?php echo build_pagination_url(max(1, $page - 1), $view_mode, $current_filters); ?>" 
                               class="px-3 py-2 rounded-lg border transition-all duration-200 <?php echo ($page == 1) ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 hover:border-blue-500'; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                            
                            <div class="hidden sm:flex items-center gap-2">
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="<?php echo build_pagination_url($i, $view_mode, $current_filters); ?>" 
                                       class="px-4 py-2 rounded-lg border transition-all duration-200 <?php echo ($i == $page) ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white border-blue-700 font-bold shadow-md' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 hover:border-blue-500'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="sm:hidden px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-bold shadow-md">
                                <?php echo $page; ?> / <?php echo $total_pages; ?>
                            </div>
   
                            <a href="<?php echo build_pagination_url(min($total_pages, $page + 1), $view_mode, $current_filters); ?>" 
                               class="px-3 py-2 rounded-lg border transition-all duration-200 <?php echo ($page == $total_pages) ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 hover:border-blue-500'; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            
                            <a href="<?php echo build_pagination_url($total_pages, $view_mode, $current_filters); ?>" 
                               class="px-3 py-2 rounded-lg border transition-all duration-200 <?php echo ($page == $total_pages) ? 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200' : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 hover:border-blue-500'; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <a href="alumni.php" class="inline-flex mt-10 items-center gap-2 px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-all shadow-sm text-sm font-medium">
    <i class="fas fa-archive"></i> Lihat Arsip Alumni
</a> 

            <div class="mt-8 bg-gradient-to-br from-orange-50 to-red-50 rounded-xl shadow-lg p-6 border-2 border-orange-200">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
                            Kenaikan Kelas Siswa
                            <span class="px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">Admin</span>
                        </h3>
                        <p class="text-sm text-gray-700 mb-4">
                            Fitur ini akan menaikkan kelas seluruh siswa secara otomatis. 
                            <span class="font-semibold">Kelas X → XI, XI → XII, XII → LULUS</span>
                        </p>
                        <div class="bg-orange-100 border-l-4 border-orange-500 p-4 rounded-lg mb-4">
                            <p class="text-xs text-orange-800 flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle mt-0.5"></i>
                                <span><strong>Perhatian:</strong> Proses ini tidak dapat dibatalkan. Pastikan sudah melakukan backup data sebelum melanjutkan.</span>
                            </p>
                        </div>
                        <form method="POST" action="proses_naik_kelas.php" onsubmit="return confirm('⚠️ KONFIRMASI KENAIKAN KELAS\n\nAnda yakin ingin menaikkan kelas SEMUA siswa?\n\n✓ Kelas X akan naik ke XI\n✓ Kelas XI akan naik ke XII\n✓ Kelas XII akan naik ke LULUS\n\nTindakan ini TIDAK DAPAT dibatalkan!\n\nKlik OK untuk melanjutkan.')">
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                                <i class="fas fa-arrow-circle-up"></i>
                                Proses Kenaikan Kelas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            
        </main>
    </div>

    <div id="resultModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div id="modalOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="hideResultModal()"></div>

    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden relative z-50 transform transition-all border border-gray-100" onclick="event.stopPropagation()">
        
        <div class="bg-[#0F3A3A] border-b border-gray-900 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 id="modalTitle" class="text-lg font-bold text-white">Detail Hasil Tes</h3>
                <p id="modalSubtitle" class="text-xs text-gray-300 mt-0.5 font-medium uppercase tracking-wider"></p>
            </div>
            <button onclick="hideResultModal()" class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-100 rounded-lg transition-all">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="max-h-[70vh] overflow-y-auto bg-gray-50/50 p-6">
            <div id="resultCardsContainer" class="space-y-4">
                </div>
        </div>

        <div class="px-6 py-4 bg-white border-t border-gray-100 flex justify-end">
            <button onclick="hideResultModal()" 
                class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition-colors flex items-center gap-2 text-sm">
                Tutup
            </button>
        </div>
    </div>
</div>

    <footer class="bg-white border-t border-gray-200 py-6 mt-auto content-wrapper">
        <div class="text-center">
            <p class="text-sm text-gray-600">
    &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
</p>
<p class="text-xs text-gray-400 mt-1">
    Developed by <span class="font-medium">SahDu Team</span>
</p>

        </div>
    </footer>
</body>
</html>