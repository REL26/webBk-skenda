<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';
if (!isset($_SESSION['filter_bk'])) {
    $_SESSION['filter_bk'] = [
        'search'  => '',
        'kelas'   => '',
        'jurusan' => '',
        'tahun'   => '',
        'riwayat' => ''
    ];
}

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $_SESSION['filter_bk'] = [
        'search'  => '',
        'kelas'   => '',
        'jurusan' => '',
        'tahun'   => '',
        'riwayat' => ''
    ];
    header("Location: konselingindividu.php");
    exit;
}

if (isset($_GET['search']))  $_SESSION['filter_bk']['search']  = trim($_GET['search']);
if (isset($_GET['kelas']))   $_SESSION['filter_bk']['kelas']   = trim($_GET['kelas']);
if (isset($_GET['jurusan'])) $_SESSION['filter_bk']['jurusan'] = trim($_GET['jurusan']);
if (isset($_GET['tahun']))   $_SESSION['filter_bk']['tahun']   = trim($_GET['tahun']);
if (isset($_GET['riwayat'])) $_SESSION['filter_bk']['riwayat'] = trim($_GET['riwayat']);

$filter_search  = mysqli_real_escape_string($koneksi, $_SESSION['filter_bk']['search']);
$filter_kelas   = mysqli_real_escape_string($koneksi, $_SESSION['filter_bk']['kelas']);
$filter_jurusan = mysqli_real_escape_string($koneksi, $_SESSION['filter_bk']['jurusan']);
$filter_tahun   = mysqli_real_escape_string($koneksi, $_SESSION['filter_bk']['tahun']);
$filter_riwayat = mysqli_real_escape_string($koneksi, $_SESSION['filter_bk']['riwayat']);


$where_clauses = [];
$where_clauses[] = "s.kelas != 'LULUS'";

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
if ($filter_riwayat == 'ada') {
    $where_clauses[] = "
    EXISTS (
        SELECT 1
        FROM konseling_individu k
        WHERE k.id_siswa = s.id_siswa
    )
    ";
}
if ($filter_riwayat == 'belum') {
    $where_clauses[] = "
    NOT EXISTS (
        SELECT 1
        FROM konseling_individu k
        WHERE k.id_siswa = s.id_siswa
    )
    ";
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
            COUNT(*) AS total
        FROM konseling_individu
        WHERE id_siswa = ?
    ";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_siswa);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $total = (int)$data['total'];

    return [
        'pertemuan_ke' => $total,
        'panggilan_ke' => $total
    ];
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
    <link rel="stylesheet" href="partials/sidebar-style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .primary-color {
            color: #0F3A3A;
        }

        .primary-bg {
            background-color: #123E44;
        }

        .secondary-bg {
            background-color: #E6EEF0;
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

        .data-table-report {
            min-width: 800px;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .btn-action {
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
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

    <script src="partials/sidebar-script.js"></script>
    <script>
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
            $('.bidang-layanan-cb').prop('checked', false);
            $('#bidang_layanan_error').addClass('hidden');
        }

        function openPdfViewerModal(pdfUrl) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            const exportBtn = document.getElementById('exportPdfBtn');

            document.getElementById('pdfIframeTitle').textContent = 'Laporan Konseling Individu';
            iframe.src = pdfUrl;
            if(exportBtn) exportBtn.href = pdfUrl;

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

        $(document).ready(function () {
            $("#submitBtn").click(function (e) {
                e.preventDefault();

                // --- VALIDASI GAMBAR FRONTEND ---
                let fileInput = document.getElementById('dokumentasi');
                if (fileInput.files.length > 12) {
                    alert("Maksimal 12 gambar dokumentasi yang diperbolehkan!");
                    return false;
                }
                
                let validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                for (let i = 0; i < fileInput.files.length; i++) {
                    let file = fileInput.files[i];
                    if (file.size > 2 * 1024 * 1024) { 
                        alert("Ukuran gambar '" + file.name + "' melebihi 2 MB!");
                        return false;
                    }
                    if (!validTypes.includes(file.type) && !validTypes.includes('image/' + file.name.split('.').pop().toLowerCase())) {
                        alert("Format gambar '" + file.name + "' tidak didukung! Harap gunakan JPG, JPEG, PNG, atau WEBP.");
                        return false;
                    }
                }
                // ---------------------------------

                // --- VALIDASI & SUSUN BIDANG LAYANAN ---
                let bidangChecked = $('.bidang-layanan-cb:checked').map(function () { return this.value; }).get();
                if (bidangChecked.length < 1) {
                    $('#bidang_layanan_error').removeClass('hidden');
                    document.getElementById('bidang_layanan_group').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                $('#bidang_layanan_error').addClass('hidden');
                document.getElementById('bidang_layanan').value = bidangChecked.join(',');
                // ---------------------------------

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

                    success: function (res) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                        closeModal();

                        if (res.status === "success") {
                            alert("Laporan konseling individu berhasil disimpan!");
                            if (res.pdf_url) {
                                openPdfViewerModal(res.pdf_url);
                            }
                        }
                        else {
                            alert("Gagal menyimpan laporan konseling individu: " + (res.message || "Terjadi kesalahan."));
                        }
                    },

                    error: function (xhr) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;

                        let errorMessage = "Gagal menyimpan laporan konseling individu.";
                        try {
                            const errorJson = JSON.parse(xhr.responseText);
                            if (errorJson && errorJson.message) {
                                errorMessage = "Gagal menyimpan laporan konseling individu: " + errorJson.message;
                            }
                        } catch (e) {
                            // biarkan pesan default
                        }
                        alert(errorMessage);
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.animate-slide-in').forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</head>

<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="flex-grow p-4 sm:p-6 lg:p-8 md:ml-[260px]">

        <div class="mb-8 animate-slide-in">
            <h2 class="text-3xl font-extrabold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-user-friends primary-color mr-3"></i> Konseling Individu
            </h2>
            <p class="text-gray-600">Kelola dan buat laporan konseling individu untuk siswa</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Siswa</p>
                        <h3 class="text-4xl font-bold mt-2"><?= $row_count ?></h3>
                        <p class="text-xs text-gray-500 mt-2">Jumlah seluruh data siswa</p>
                    </div>
                    <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Halaman Data</p>
                        <h3 class="text-4xl font-bold mt-2">
                            <?= $current_page_num ?>
                            <span class="text-xl"> / <?= $total_pages ?></span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-2">Posisi halaman saat ini</p>
                    </div>
                    <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-file-lines text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

        </div>

        <div class="no-print bg-white p-6 rounded-xl shadow-lg mb-6 border border-gray-200 animate-slide-in">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-filter primary-color mr-2"></i> Filter Pencarian Siswa
            </h3>
            <form method="GET" action="konselingindividu.php"
                class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 items-end">
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
                    <select name="kelas" id="kelas"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Semua Kelas</option>
                        <?php foreach($kelas_options as $kelas):
                            if (strtoupper($kelas) === 'LULUS') continue;
                        ?>
                        <option value="<?= $kelas ?>" <?=($filter_kelas==$kelas) ? 'selected' : '' ?>>
                            Kelas <?= htmlspecialchars($kelas) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="jurusan" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-graduation-cap mr-1"></i> Jurusan
                    </label>
                    <select name="jurusan" id="jurusan"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Semua Jurusan</option>
                        <?php foreach($jurusan_options as $jurusan): ?>
                        <option value="<?= $jurusan ?>" <?=($filter_jurusan==$jurusan) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jurusan) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-history mr-1"></i> Riwayat Konseling
                    </label>
                    <select name="riwayat" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <option value="">Semua Siswa</option>
                        <option value="ada" <?=($filter_riwayat=="ada" )?'selected':'' ?>>Pernah Dilayani</option>
                        <option value="belum" <?=($filter_riwayat=="belum" )?'selected':'' ?>>Belum Pernah Dilayani</option>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
                        <i class="fas fa-filter mr-2"></i> Terapkan
                    </button>
                    <a href="konselingindividu.php?reset=1"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
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
                            <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Kelas / Jurusan</th>
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
                                $session_data = get_latest_session_data($koneksi, $data['id_siswa']);
                                $pertemuan_ke = $session_data['pertemuan_ke'];
                                $panggilan_ke = $session_data['panggilan_ke'];
                        ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-700">
                                <?= $no++ ?>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                            <?= strtoupper(substr($data['nama'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900">
                                            <?= htmlspecialchars($data['nama']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($data['kelas']) ?> <?= htmlspecialchars($data['jurusan']) ?>
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                <?= htmlspecialchars($data['nis']) ?>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-purple-100 text-purple-800">
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
                                    <button onclick="openModal(
                                                    '<?= $data['id_siswa'] ?>', 
                                                    '<?= htmlspecialchars($data['nama'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['kelas'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['jurusan'], ENT_QUOTES) ?>', 
                                                    '<?= htmlspecialchars($data['nis'], ENT_QUOTES) ?>',
                                                    '<?= $pertemuan_ke ?>',
                                                    '<?= $panggilan_ke ?>'
                                                )"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200 text-xs font-semibold shadow-md btn-action">
                                        <i class="fas fa-file-alt mr-1"></i> Buat Laporan
                                    </button>
                                    <a href="riwayat_konseling.php?id_siswa=<?= $data['id_siswa'] ?>"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition duration-200 text-xs font-semibold shadow-md btn-action inline-flex items-center justify-center">
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
                    <p class="text-xs text-gray-500 mt-1">Halaman <?= $current_page_num ?> dari <?= $total_pages ?>
                        <span class="hidden md:inline">(<?= $limit_per_page ?> baris per halaman)</span>
                        <span class="md:hidden">(<?= $limit_per_page ?> data - Mode Mobile)</span>
                    </p>
                </div>

                <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($current_page_num > 1): ?>
                    <a href="<?= get_pagination_url($current_page_num - 1, $current_filters) ?>" class="relative inline-flex items-center px-2 md:px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition">
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
                    <a href="<?= get_pagination_url($i, $current_filters) ?>" class="relative inline-flex items-center px-3 md:px-4 py-2 border text-sm font-semibold transition <?= ($i == $current_page_num) ? 'z-10 primary-bg text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
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
                    <a href="<?= get_pagination_url($current_page_num + 1, $current_filters) ?>" class="relative inline-flex items-center px-2 md:px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition">
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
    <p class="text-sm text-black/70">
        &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
    </p>
    <p class="text-xs text-gray-400 mt-1">
        Developed by <span class="font-medium">SahDu Team</span>
    </p>
</footer>

<div id="konselingModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
        <div class="sticky top-0 bg-[#0F3A3A] px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
            <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center">
                <i class="fas fa-clipboard-list mr-3"></i> Buat Laporan Konseling
            </h3>
            <button onclick="closeModal()" class="text-white hover:text-gray-200 transition">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <div class="p-8">
            <form id="konselingForm" onsubmit="return false;" enctype="multipart/form-data">
                <input type="hidden" name="id_siswa" id="id_siswa">

                <div
                    class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 border-2 border-indigo-200 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600 flex items-center">
                            <i class="fas fa-user mr-2 text-indigo-600"></i> Nama Siswa
                        </p>
                        <p id="siswa_nama" class="text-xl font-bold text-gray-900"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600 flex items-center">
                            <i class="fas fa-school mr-2 text-indigo-600"></i> Kelas & Jurusan
                        </p>
                        <p id="siswa_kelas_jurusan" class="text-xl font-bold text-gray-900"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600 flex items-center">
                            <i class="fas fa-id-card mr-2 text-indigo-600"></i> NIS
                        </p>
                        <p id="siswa_nis" class="text-xl font-bold text-gray-900"></p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-indigo-600 flex items-center">
                            <i class="fas fa-calendar-check mr-2 text-indigo-600"></i> Sesi Berikutnya
                        </p>
                        <p class="text-xl font-bold text-gray-900">
                            Pertemuan <span id="pertemuan_display" class="text-indigo-600">1</span> |
                            Panggilan <span id="panggilan_display" class="text-indigo-600">1</span>
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
                            <option value="<?= $durasi ?> Menit" <?=($durasi==45) ? 'selected' : '' ?>>
                                <?= $durasi ?> Menit
                            </option>
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-layer-group mr-1"></i> Bidang Layanan
                    </label>
                    <div id="bidang_layanan_group" class="flex flex-wrap gap-4 p-3 border-2 border-gray-300 rounded-lg">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="bidang-layanan-cb" value="Pribadi"> Pribadi
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="bidang-layanan-cb" value="Sosial"> Sosial
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="bidang-layanan-cb" value="Belajar"> Belajar
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" class="bidang-layanan-cb" value="Karir"> Karir
                        </label>
                    </div>
                    <input type="hidden" name="bidang_layanan" id="bidang_layanan">
                    <p id="bidang_layanan_error" class="text-xs text-red-500 mt-1 hidden">Pilih minimal 1 bidang layanan.</p>
                </div>

                <div class="mb-6">
                    <label for="gejala_nampak" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-eye mr-1"></i> Gejala yang Nampak
                    </label>
                    <textarea name="gejala_nampak" id="gejala_nampak" rows="3" required
                        placeholder="Deskripsikan gejala atau perilaku yang terlihat..."
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
                        <input type="text" name="pendekatan_konseling" id="pendekatan_konseling" required
                            placeholder="Teknik pendekatan apa yang digunakan?"
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>

                    <div class="md:col-span-2">
                        <label for="teknik_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tools mr-1"></i> Teknik Konseling
                        </label>
                        <input type="text" name="teknik_konseling" id="teknik_konseling" required
                            placeholder="Teknik konseling apa yang digunakan?"
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="hasil_dicapai" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-circle mr-1"></i> Hasil yang Dicapai
                    </label>
                    <textarea name="hasil_dicapai" id="hasil_dicapai" rows="3" required
                        placeholder="Deskripsikan hasil atau progress yang dicapai dalam sesi ini..."
                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"></textarea>
                </div>

                <div class="mb-6">
                    <label for="dokumentasi" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-camera mr-1"></i> Dokumentasi Kegiatan <span class="text-xs text-gray-500">(Opsional, Maks 12 foto, Max 2MB/foto)</span>
                    </label>
                    <input type="file" name="dokumentasi[]" id="dokumentasi" multiple accept=".jpg,.jpeg,.png,.webp"
                        class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white text-sm">
                    <p class="text-xs text-gray-500 mt-1">Format diperbolehkan: JPG, JPEG, PNG, WEBP.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tie mr-1"></i> Nama Guru
                        </label>
                        <select name="nama_guru" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="Pahrurazi, S.Pd">Pahrurazi, S.Pd</option>
                            <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
                            <option value="Putri Hidayatie, S.Pd">Putri Hidayatie, S.Pd</option>
                            <option value="Rini Rodhiati, S.Pd">Rini Rodhiati, S.Pd</option>
                            <option value="Gusti Muhammad Fajri Ramadhan, S.Pd">Gusti Muhammad Fajri Ramadhan, S.Pd
                            </option>
                            <option value="Desy Arianti, S.Pd">Desy Arianti, S.Pd</option>
                            <option value="Khalisatun Ni'mah, S.Pd">Khalisatun Ni'mah, S.Pd</option>
                            <option value="Tiara Wulansari, S.Pd">Tiara Wulansari, S.Pd</option>
                            <option value="Dhea Nur Aziza, S.Pd">Dhea Nur Aziza, S.Pd</option>
                            <option value="Abdul Basith, S.Pd">Abdul Basith, S.Pd</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-id-badge mr-1"></i> NIP Guru BK <span
                                class="text-xs text-gray-500">(Opsional)</span>
                        </label>
                        <input type="text" name="nip_guru_bk" placeholder="Boleh dikosongkan"
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>

                <input type="hidden" name="status_konseling" value="Proses">
                <input type="hidden" name="no_input" value="AUTO-GENERATED">

                <div class="mt-8 pt-6 border-t-2 border-gray-200 flex flex-col md:flex-row justify-end gap-3">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md btn-action">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="submit" id="submitBtn"
                        class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg transition font-semibold shadow-md btn-action">
                        <i class="fas fa-save mr-2"></i> Simpan Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>