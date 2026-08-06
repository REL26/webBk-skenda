<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Konselor Sekolah';

if (!isset($_SESSION['filter_kelompok_bk'])) {
    $_SESSION['filter_kelompok_bk'] = [
        'search'  => '',
        'kelas'   => '',
        'jurusan' => ''
    ];
}

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $_SESSION['filter_kelompok_bk'] = [
        'search'  => '',
        'kelas'   => '',
        'jurusan' => ''
    ];
    header("Location: konselingkelompok.php");
    exit;
}

if (isset($_GET['search']))  $_SESSION['filter_kelompok_bk']['search']  = trim($_GET['search']);
if (isset($_GET['kelas']))   $_SESSION['filter_kelompok_bk']['kelas']   = trim($_GET['kelas']);
if (isset($_GET['jurusan'])) $_SESSION['filter_kelompok_bk']['jurusan'] = trim($_GET['jurusan']);

$filter_search  = mysqli_real_escape_string($koneksi, $_SESSION['filter_kelompok_bk']['search']);
$filter_kelas   = mysqli_real_escape_string($koneksi, $_SESSION['filter_kelompok_bk']['kelas']);
$filter_jurusan = mysqli_real_escape_string($koneksi, $_SESSION['filter_kelompok_bk']['jurusan']);

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
        s.jurusan,
        s.jenis_kelamin
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

$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas";
$result_kelas = mysqli_query($koneksi, $query_kelas);
$kelas_options = mysqli_fetch_all($result_kelas, MYSQLI_ASSOC);
$kelas_options = array_column($kelas_options, 'kelas');

$query_jurusan = "SELECT DISTINCT jurusan FROM siswa WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan";
$result_jurusan = mysqli_query($koneksi, $query_jurusan);
$jurusan_options = mysqli_fetch_all($result_jurusan, MYSQLI_ASSOC);
$jurusan_options = array_column($jurusan_options, 'jurusan');

$current_filters = [
    'limit' => $limit_per_page
];

function get_pagination_url($page, $filters) {
    $query_params = array_filter($filters);
    $query_params['page'] = $page;
    return 'konselingkelompok.php?' . http_build_query($query_params);
}

$waktu_durasi_options = [15, 30, 45, 60];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konseling Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        html {
            overflow-y: scroll;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
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
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
            will-change: transform;
        }

        .btn-action:hover {
            -webkit-transform: scale(1.05) translateZ(0);
            transform: scale(1.05) translateZ(0);
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

        main {
            box-sizing: border-box;
            overflow-x: hidden;
        }

        @media (max-width: 767px) {
            main {
                margin-left: 0 !important;
                padding-left: 1rem;
                padding-right: 1rem;
                width: 100%;
                padding-top: 4.5rem;
            }

            body.overflow-hidden {
                overflow: hidden;
                width: 100vw;
                position: fixed;
                height: 100vh;
            }
        }

        @media (min-width: 768px) {
            main {
                margin-left: 260px;
            }
        }

        .grid {
            width: 100%;
            box-sizing: border-box;
        }

        .grid>* {
            overflow-x: hidden;
            overflow-y: hidden;
        }
    </style>

    <script src="partials/sidebar-script.js"></script>
    <script>
        const STORAGE_KEY = 'selectedGroupStudents';

        function getSelectedIds() {
            try {
                const stored = sessionStorage.getItem(STORAGE_KEY);
                return stored ? new Set(JSON.parse(stored)) : new Set();
            } catch (e) {
                return new Set();
            }
        }

        function saveSelectedIds(idsSet) {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(idsSet)));
            } catch (e) {
            }
        }

        function fetchAndDisplaySelectedStudents(selectedIds, targetElementId) {
            const targetElement = document.getElementById(targetElementId);
            if (!targetElement) return;

            const selectedCount = selectedIds.size;
            targetElement.innerHTML = '';

            if (selectedCount === 0) {
                if (targetElementId === 'selectedStudentsList') {
                    targetElement.innerHTML = '<span class="text-xs text-gray-500">Belum ada siswa yang dipilih.</span>';
                }
                if (targetElementId === 'selectedStudentsInModal') {
                    targetElement.innerHTML = '<span class="text-xs text-gray-500">Tidak ada siswa yang terpilih.</span>';
                }
                return;
            }

            const loadingMessage = (targetElementId === 'selectedStudentsList') ?
                'Mengambil data siswa terpilih...' :
                'Mohon tunggu, memuat daftar siswa...';

            targetElement.innerHTML = `<span class="text-xs text-blue-500 italic">${loadingMessage}</span>`;

            $.ajax({
                url: "fetch_selected_students.php",
                method: "POST",
                data: { ids: Array.from(selectedIds).join(',') },
                dataType: "json",

                success: function (res) {
                    if (res.status === "success" && res.students.length > 0) {
                        let htmlContent = res.students.map(item =>
                            `<span class="inline-block bg-blue-200 text-blue-800 text-xs px-3 py-1 rounded-full m-1">
                            ${item.name} (${item.kelas})
                         </span>`
                        ).join('');

                        if (res.students.length < selectedCount) {
                            htmlContent += `<span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-3 py-1 rounded-full m-1 font-semibold">
                             Peringatan: ${selectedCount - res.students.length} ID siswa tidak ditemukan.
                         </span>`;
                        }

                        targetElement.innerHTML = htmlContent;
                    } else {
                        targetElement.innerHTML = `<span class="text-xs text-red-500">Gagal memuat nama siswa: ${res.message || 'Data tidak ditemukan.'}</span>`;
                    }
                },
                error: function () {
                    targetElement.innerHTML = `<span class="text-xs text-red-500">Error jaringan saat memuat data siswa.</span>`;
                }
            });
        }

        function updateSelectedStudentsDisplay(isInitialization = false) {
            let selectedIds = getSelectedIds();
            const checkboxes = document.querySelectorAll('input[name="selected_siswa[]"]');
            const submitBtn = document.getElementById('openModalGroupBtn');
            const selectedCountDisplay = document.getElementById('selectedCountDisplay');

            if (!isInitialization) {
                checkboxes.forEach(checkbox => {
                    const id = checkbox.value;
                    if (checkbox.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                saveSelectedIds(selectedIds);
            }

            else {
                checkboxes.forEach(checkbox => {
                    if (selectedIds.has(checkbox.value)) {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });

                const allChecked = Array.from(checkboxes).every(cb => cb.checked && selectedIds.has(cb.value));
                document.getElementById('selectAllSiswa').checked = allChecked && checkboxes.length > 0;
            }

            const finalSelectedCount = selectedIds.size;

            submitBtn.disabled = finalSelectedCount < 2;
            if (finalSelectedCount >= 2) {
                submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            } else {
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            }

            if (selectedCountDisplay) {
                selectedCountDisplay.textContent = finalSelectedCount;
            }

            fetchAndDisplaySelectedStudents(selectedIds, 'selectedStudentsList');
        }

        function openModalGroup() {
            const selectedIds = getSelectedIds();
            const selectedCount = selectedIds.size;

            if (selectedCount < 2) {
                alert("Pilih minimal 2 siswa untuk membuat Laporan Konseling Kelompok. Total terpilih: " + selectedCount);
                return;
            }

            const modal = document.getElementById('konselingGroupModal');
            const idSiswaInput = document.getElementById('selected_student_ids_input');

            document.getElementById('modalTitleGroup').textContent = `Buat Laporan Konseling Kelompok (${selectedCount} Siswa)`;

            fetchAndDisplaySelectedStudents(selectedIds, 'selectedStudentsInModal');

            idSiswaInput.value = Array.from(selectedIds).join(',');

            document.getElementById('tanggal_pelaksanaan').valueAsDate = new Date();

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
        }

        function closeModalGroup() {
            const modal = document.getElementById('konselingGroupModal');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('konselingFormGroup').reset();
        }

        function openPdfViewerModal(pdfUrl) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            const exportBtn = document.getElementById('exportPdfBtn');

            document.getElementById('pdfIframeTitle').textContent = 'Laporan Konseling Kelompok';
            iframe.src = pdfUrl;
            if (exportBtn) exportBtn.href = pdfUrl;

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

            updateSelectedStudentsDisplay(true);

            $(document).on('change', 'input[name="selected_siswa[]"]', function () {
                updateSelectedStudentsDisplay();
            });

            $("#selectAllSiswa").change(function () {
                const isChecked = this.checked;
                $('input[name="selected_siswa[]"]').prop('checked', isChecked);
                updateSelectedStudentsDisplay();
            });

            $("#submitGroupBtn").click(function (e) {
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

                let form = document.getElementById("konselingFormGroup");
                let formData = new FormData(form);
                const submitButton = document.getElementById('submitGroupBtn');
                const originalText = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';

                $.ajax({
                    url: "laporan_kelompokkon.php",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",

                    success: function (res) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                        closeModalGroup();

                        sessionStorage.removeItem(STORAGE_KEY);
                        $('input[name="selected_siswa[]"]').prop('checked', false);
                        $('#selectAllSiswa').prop('checked', false);
                        updateSelectedStudentsDisplay(true);

                        if (res.status === "success") {
                            // Langsung buka preview PDF
                            if (res.pdf_url) {
                                openPdfViewerModal(res.pdf_url);
                            } else {
                                alert("Laporan konseling kelompok berhasil disimpan!");
                            }
                        }
                        else {
                            alert("Gagal menyimpan laporan kelompok: " + res.message);
                        }
                    },

                    error: function (xhr) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;

                        let errorMessage = "Terjadi error saat mengirim data (Kesalahan Jaringan/Server).";
                        try {
                            const errorJson = JSON.parse(xhr.responseText);
                            if (errorJson && errorJson.message) {
                                errorMessage = "Gagal menyimpan: " + errorJson.message;
                            } else {
                                errorMessage += "\n\nDetail Error (Cek Konsol Browser untuk detail penuh).";
                            }
                        } catch (e) {
                            errorMessage += "\n\nTerdeteksi Fatal Error di Server! Cek konsol browser untuk detail.";
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
                    <i class="fas fa-users primary-color mr-3"></i> Konseling Kelompok
                </h2>
                <p class="text-gray-600">Kelola dan buat laporan konseling kelompok untuk siswa</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition">

                    <div class="flex items-start justify-between">

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Total Siswa
                            </p>

                            <h3 class="text-4xl font-bold text-gray-800 mt-2">
                                <?= $row_count ?>
                            </h3>

                            <p class="text-xs text-gray-500 mt-2">
                                Siswa tersedia untuk dipilih
                            </p>
                        </div>


                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>

                    </div>

                </div>

                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition">

                    <div class="flex items-start justify-between">

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Siswa Terpilih
                            </p>


                            <h3 class="text-4xl font-bold mt-2">
                                <span id="selectedCountDisplay">0</span>
                            </h3>


                            <p class="text-xs text-gray-500 mt-2">
                                Sudah masuk kelompok konseling
                            </p>

                        </div>


                        <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user-check text-blue-600 text-2xl"></i>
                        </div>

                    </div>

                </div>
            </div>

            <div class="no-print bg-white p-6 rounded-xl shadow-lg mb-6 border border-gray-200 animate-slide-in">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-filter primary-color mr-2"></i> Filter Pencarian Siswa
                </h3>
                <form method="GET" action="konselingkelompok.php"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
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

                    <div class="flex space-x-2">
                        <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
                            <i class="fas fa-filter mr-2"></i> Terapkan
                        </button>
                        <a href="konselingkelompok.php?reset=1"
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-3 rounded-lg transition duration-200 flex items-center justify-center text-sm font-semibold shadow-md btn-action">
                            <i class="fas fa-sync-alt mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 animate-slide-in">

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 p-5 bg-blue-50 border border-blue-100 rounded-xl">
                    
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-blue-900 mb-1">
                            Pilih Siswa untuk Konseling Kelompok
                        </h3>
                        <p class="text-sm text-blue-700 leading-relaxed">
                            Minimal 2 siswa harus dipilih untuk membuat laporan konseling kelompok.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                        <a href="riwayat_kelompok.php"
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-[#0F3A3A] hover:bg-[#123E44] text-white rounded-lg transition duration-200 text-sm font-medium shadow-md whitespace-nowrap">
                            <i class="fas fa-list-ul mr-2 text-[#5FA8A1]"></i> Riwayat Kelompok
                        </a>

                        <button type="button" onclick="openModalGroup()" id="openModalGroupBtn" disabled
                            class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-400 text-white rounded-lg text-sm font-bold shadow transition-all duration-200 cursor-not-allowed whitespace-nowrap">
                            <i class="fas fa-file-alt mr-2"></i>
                            <span>Buat Laporan Kelompok</span>
                        </button>
                    </div>
                </div>

                <div class="p-4 border rounded-lg mb-6 bg-gray-50" id="selectedStudentsListContainer">
                    <p class="text-xs font-semibold text-gray-600 mb-2">Siswa Terpilih (<span
                            id="selectedCountDisplay">0</span> dari
                        <?php echo $row_count; ?>):
                    </p>
                    <div id="selectedStudentsList" class="flex flex-wrap gap-2">
                        <span class="text-xs text-gray-500">Belum ada siswa yang dipilih.</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 data-table-report">
                        <thead class="primary-bg">
                            <tr>
                                <th
                                    class="px-4 py-4 text-center text-xs font-bold text-white uppercase tracking-wider w-10">
                                    <input type="checkbox" id="selectAllSiswa" class="rounded h-4 w-4">
                                </th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">No
                                </th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Nama Siswa</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Kelas</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Jurusan</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    NIS</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Jenis Kelamin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (mysqli_num_rows($result_siswa) > 0): ?>
                            <?php 
                                $no = $start_from + 1;
                                while($data = mysqli_fetch_assoc($result_siswa)): 
                                ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" name="selected_siswa[]" value="<?= $data['id_siswa'] ?>"
                                        class="rounded h-4 w-4">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-bold text-gray-700">
                                    <?= $no++ ?>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
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

                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <?= htmlspecialchars($data['kelas']) ?>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <?= htmlspecialchars($data['jurusan']) ?>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <?= htmlspecialchars($data['nis']) ?>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">
                                    <?= htmlspecialchars($data['jenis_kelamin']) ?>
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
                <div
                    class="no-print mt-6 flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-700 text-center md:text-left">
                        <p class="font-semibold">Menampilkan <span class="text-blue-600">
                                <?= mysqli_num_rows($result_siswa) ?>
                            </span> dari <span class="text-blue-600">
                                <?= $row_count ?>
                            </span> total siswa</p>
                        <p class="text-xs text-gray-500 mt-1">Halaman
                            <?= $current_page_num ?> dari
                            <?= $total_pages ?>
                        </p>
                    </div>

                    <nav class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px">
                        <?php if ($current_page_num > 1): ?>
                        <a href="<?= get_pagination_url($current_page_num - 1, $current_filters) ?>"
                            class="relative inline-flex items-center px-3 py-2 rounded-l-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_loop = max(1, $current_page_num - 2);
                        $end_loop = min($total_pages, $current_page_num + 2);
                        
                        if ($start_loop > 1) {
                            echo '<a href="' . get_pagination_url(1, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                            if ($start_loop > 2) {
                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm">...</span>';
                            }
                        }

                        for ($i = $start_loop; $i <= $end_loop; $i++):
                        ?>
                        <a href="<?= get_pagination_url($i, $current_filters) ?>"
                            class="relative inline-flex items-center px-4 py-2 border text-sm font-semibold transition
                            <?= ($i == $current_page_num) ? 'z-10 primary-bg text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; 

                        if ($end_loop < $total_pages) {
                            if ($end_loop < $total_pages - 1) {
                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm">...</span>';
                            }
                            echo '<a href="' . get_pagination_url($total_pages, $current_filters) . '" class="relative hidden sm:inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($current_page_num < $total_pages): ?>
                        <a href="<?= get_pagination_url($current_page_num + 1, $current_filters) ?>"
                            class="relative inline-flex items-center px-3 py-2 rounded-r-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer
        class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto md:ml-[260px]">
        <p class="text-sm text-black/70">
            &copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span>
        </p>
        <p class="text-xs text-gray-400 mt-1">
            Developed by <span class="font-medium">SahDu Team</span>
        </p>
    </footer>

    <div id="konselingGroupModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
            <div
                class="sticky top-0 bg-[#0F3A3A] px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="modalTitleGroup" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-users-cog mr-3"></i> Buat Laporan Konseling Kelompok
                </h3>
                <button onclick="closeModalGroup()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="konselingFormGroup" onsubmit="return false;" enctype="multipart/form-data">
                    <input type="hidden" name="selected_student_ids" id="selected_student_ids_input">
                    <input type="hidden" name="status_konseling" value="Terlaksana">
                    <input type="hidden" name="tempat" value="Ruang BK">

                    <div
                        class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8 p-6 border-2 border-indigo-200 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-50">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-2 flex items-center">
                                <i class="fas fa-users mr-2 text-blue-600"></i> Daftar Siswa Terpilih
                            </p>
                            <div id="selectedStudentsInModal"
                                class="flex flex-wrap gap-2 p-3 bg-white border border-blue-200 rounded-lg">
                            </div>
                        </div>
                    </div>

                    <h4 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b-2 border-gray-200 pb-3">
                        <i class="fas fa-edit primary-color mr-2"></i> Detail Pelaksanaan Konseling Kelompok
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="tanggal_pelaksanaan" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1"></i> Tanggal Pelaksanaan
                            </label>
                            <input type="date" name="tanggal_pelaksanaan" id="tanggal_pelaksanaan" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>

                        <div>
                            <label for="waktu_durasi" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-1"></i> Waktu/Durasi
                            </label>
                            <select name="waktu_durasi" id="waktu_durasi" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="">Pilih Durasi</option>
                                <?php 
                                $waktu_durasi_options = [30, 45, 60, 90];
                                foreach($waktu_durasi_options as $durasi): 
                                ?>
                                <option value="<?= $durasi ?> Menit" <?=($durasi==45) ? 'selected' : '' ?>>
                                    <?= $durasi ?> Menit
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="pertemuan_ke" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-list-ol mr-1"></i> Pertemuan Ke-
                            </label>
                            <input type="number" name="pertemuan_ke" id="pertemuan_ke" min="1" required placeholder="Masukkan nomor pertemuan..."
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="catatan_khusus" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-search mr-1"></i> Teknik Pendekatan
                            </label>
                            <input type="text" name="pendekatan" id="catatan_khusus" required
                                placeholder="Teknik pendekatan yang digunakan..."
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                        <div>
                            <label for="teknik_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tools mr-1"></i> Teknik Konseling
                            </label>
                            <input type="text" name="teknik" id="teknik_konseling" required
                                placeholder="Teknik yang digunakan selama sesi konseling..."
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="hasil_layanan" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-1"></i> Gejala dan Hasil yang Dicapai
                        </label>
                        <textarea name="hasil_yang_dicapai" id="hasil_layanan" rows="3" required
                            placeholder="Deskripsikan proses dan hasil progress yang dicapai..."
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                    </div>

                    <div class="mb-6">
                        <label for="dokumentasi" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-camera mr-1"></i> Dokumentasi Kegiatan <span class="text-xs text-gray-500">(Opsional, Maks 12 foto, Max 2MB/foto)</span>
                        </label>
                        <input type="file" name="dokumentasi[]" id="dokumentasi" multiple accept=".jpg,.jpeg,.png,.webp"
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white text-sm">
                        <p class="text-xs text-gray-500 mt-1">Format diperbolehkan: JPG, JPEG, PNG, WEBP.</p>
                    </div>

                    <div class="mb-6">
                        <label for="guru_pembimbing" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tie mr-1"></i> Nama Guru BK
                        </label>
                        <select name="guru_pembimbing" id="guru_pembimbing" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
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

                    <div class="mt-8 pt-6 border-t-2 border-gray-200 flex flex-col md:flex-row justify-end gap-3">
                        <button type="button" onclick="closeModalGroup()"
                            class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md">
                            <i class="fas fa-times mr-2"></i> Batal
                        </button>
                        <button type="submit" id="submitGroupBtn"
                            class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg transition font-semibold shadow-md">
                            <i class="fas fa-save mr-2"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL PDF VIEWER (dengan tombol Lihat Riwayat) ===== -->
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-7xl max-h-[90vh] flex flex-col transform scale-100 transition-all">

            <div
                class="sticky top-0 bg-[#0F3A3A] px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="pdfIframeTitle" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-file-pdf mr-3"></i> Laporan Konseling Kelompok
                </h3>
                <button onclick="closePdfViewerModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[65vh] border-0" title="PDF Viewer"></iframe>
            </div>

            <div
                class="sticky bottom-0 px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-200 rounded-b-2xl">
                <button type="button" onclick="closePdfViewerModal()"
                    class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md btn-action">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </button>
                <a href="riwayat_kelompok.php"
                    class="px-6 py-3 bg-[#0F3A3A] hover:bg-[#123E44] text-white rounded-lg transition font-semibold shadow-md btn-action inline-flex items-center">
                    <i class="fas fa-list-ul mr-2"></i> Lihat Riwayat
                </a>
                <a id="exportPdfBtn" href="#" target="_blank"
                    class="hidden px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition font-semibold shadow-md btn-action inline-flex items-center">
                    <i class="fas fa-download mr-2"></i> Download PDF
                </a>
            </div>
        </div>
    </div>

</body>

</html>