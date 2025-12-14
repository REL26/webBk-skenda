<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id_siswa']) || !is_numeric($_GET['id_siswa'])) {
    echo "<script>alert('ID Siswa tidak valid!'); window.location.href='konselingindividu.php';</script>";
    exit;
}

$id_siswa = mysqli_real_escape_string($koneksi, $_GET['id_siswa']);

$stmt_siswa = $koneksi->prepare("SELECT nis, nama, kelas, jurusan FROM siswa WHERE id_siswa = ?");
$stmt_siswa->bind_param("i", $id_siswa);
$stmt_siswa->execute();
$result_siswa = $stmt_siswa->get_result();
$siswa_data = $result_siswa->fetch_assoc();

if (!$siswa_data) {
    echo "<script>alert('Data Siswa tidak ditemukan!'); window->location.href='konselingindividu.php';</script>";
    exit;
}

$nama_konselor = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru BK (Tidak Diketahui)';

function tgl_indo($tanggal){
	$bulan = array (
		1 => 'Januari',
		'Februari',
		'Maret',
		'April',
		'Mei',
		'Juni',
		'Juli',
		'Agustus',
		'September',
		'Oktober',
		'November',
		'Desember'
	);
    $pecahkan = explode('-', $tanggal);
    
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$limit_desktop = 20;
$limit_mobile = 10;

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : $limit_desktop;
if (!in_array($limit, [$limit_desktop, $limit_mobile])) {
    $limit = $limit_desktop;
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt_count = $koneksi->prepare("
    SELECT COUNT(ki.id_konseling) as total_count
    FROM konseling_individu ki
    WHERE ki.id_siswa = ?
");
$stmt_count->bind_param("i", $id_siswa);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_riwayat = $result_count->fetch_assoc()['total_count'];
$total_pages = ceil($total_riwayat / $limit);

$query_riwayat = "
    SELECT 
        ki.*,
        rk.file_pdf,
        ks.aspek_penerimaan,
        ks.aspek_kemudahan_curhat,
        ks.aspek_kepercayaan,
        ks.aspek_pemecahan_masalah,
        ks.tanggal_isi
    FROM 
        konseling_individu ki
    LEFT JOIN 
        riwayat_konseling rk ON ki.id_konseling = rk.id_konseling
    LEFT JOIN
        kepuasan_siswa ks ON ki.id_konseling = ks.id_konseling
    WHERE 
        ki.id_siswa = ?
    ORDER BY 
        ki.tanggal_pelaksanaan DESC, ki.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt_riwayat = $koneksi->prepare($query_riwayat);
$stmt_riwayat->bind_param("iii", $id_siswa, $limit, $offset);
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$riwayat_count = $result_riwayat->num_rows; 
$start_number = $offset + 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Konseling <?= htmlspecialchars($siswa_data['nama']) ?> | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-color { color: #2F6C6E; }
        .sticky-col { 
            position: sticky; 
            left: 0; 
            z-index: 10; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); 
        }
        .data-table-report thead th.sticky-col { 
            background-color: #2F6C6E !important; 
        }
        .data-table-report tbody td.sticky-col {
            background-color: white; 
        }
        .data-table-report tbody tr:nth-child(even) td.sticky-col {
            background-color: #f9fafb; 
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

        .checked-indicator {
            font-weight: bold;
            color: #10B981; 
        }
        /* Style untuk kolom rating yang dipilih */
        .rating-cell.selected-rating {
            background-color: #d1fae5; /* green-100 */
            color: #065f46; /* green-800 */
            font-weight: 700;
            border: 2px solid #059669; /* green-600 */
        }
    </style>

    <script>
        const limit_desktop = <?= $limit_desktop ?>;
        const limit_mobile = <?= $limit_mobile ?>;

        function openKepuasanModal(id_konseling, p_ke, r1, r2, r3, r4, tanggal, nama_siswa) {
            const modal = document.getElementById('kepuasanModal');
            
            // Set data siswa di modal
            document.getElementById('modalNamaSiswa').textContent = nama_siswa;
            document.getElementById('kepuasanModalTitle').textContent = `Kepuasan Siswa (Sesi Ke-${p_ke})`;
            
            const isFilled = (parseInt(r1) > 0);
            
            const statusElement = document.getElementById('statusKepuasan');
            const tglDisplay = tanggal ? new Date(tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : 'Belum diisi';
            document.getElementById('tanggalDiisi').textContent = tglDisplay;
            document.getElementById('tanggalIsiCetak').textContent = tanggal ? new Date(tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '...';

            // Reset semua cell rating
            document.querySelectorAll('#kepuasanTable td.rating-cell').forEach(cell => {
                cell.innerHTML = '◻'; // Default: kotak kosong
                cell.classList.remove('selected-rating');
            });


            if (isFilled) {
                statusElement.innerHTML = `<span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i> Sudah Diisi</span>`;
                
                // Function untuk menyorot cell yang dipilih
                function highlightRating(aspectNum, ratingValue) {
                    const ratingId = `aspek${aspectNum}_${ratingValue}`; // e.g., 'aspek1_3' for SM
                    const cell = document.getElementById(ratingId);
                    if (cell) {
                        cell.innerHTML = '<i class="fas fa-check-circle text-xl"></i>'; // Ikon cek yang jelas
                        cell.classList.add('selected-rating');
                    }
                }
                
                // Map rating values to their column IDs (3=SM, 2=M, 1=KM)
                highlightRating(1, r1);
                highlightRating(2, r2);
                highlightRating(3, r3);
                highlightRating(4, r4);
                
            } else {
                statusElement.innerHTML = `<span class="text-red-600 font-semibold"><i class="fas fa-times-circle mr-1"></i> Belum Diisi</span>`;
            }

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
        }

        function closeKepuasanModal() {
            const modal = document.getElementById('kepuasanModal');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        }

        function openPdfViewerModal(pdfPath, title) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            
            document.getElementById('pdfIframeTitle').textContent = title;
            const pdfUrl = pdfPath.startsWith('..') ? pdfPath.replace('../', '<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/') : pdfPath;
            iframe.src = pdfUrl;

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

        document.addEventListener('DOMContentLoaded', () => {
            const currentLimit = <?= $limit ?>;
            const urlParams = new URLSearchParams(window.location.search);
            
            function determineLimit() {
                if (window.innerWidth < 640 && currentLimit !== limit_mobile) return limit_mobile;
                if (window.innerWidth >= 640 && currentLimit !== limit_desktop) return limit_desktop;
                return currentLimit;
            }
            
            const responsiveLimit = determineLimit();
            if (currentLimit !== responsiveLimit) {
                urlParams.set('limit', responsiveLimit);
                urlParams.set('page', 1);
                window.location.replace('?' + urlParams.toString());
            }
        });

        function getPaginationUrl(page, limit) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            urlParams.set('limit', limit);
            return '?' + urlParams.toString();
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="fixed top-0 left-0 w-full bg-white shadow-md z-30 flex items-center justify-between h-[56px] px-4">
        <a href="#" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <span class="text-lg font-bold primary-color hidden sm:inline">Riwayat Konseling Individu</span>
        </a>
        <a href="konselingindividu.php" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-gray-600 text-sm flex items-center transition duration-200">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 mt-[56px] w-full"> 
        <div class="bg-white p-4 md:p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    Riwayat Sesi Konseling
                </h2>
            </div>

            <div class="mb-8 p-6 border-l-4 border-indigo-600 bg-indigo-50 rounded-xl shadow-md">
                <h3 class="text-xl font-bold text-indigo-700 mb-4 flex items-center">
                    <i class="fas fa-user-graduate mr-3 text-2xl"></i> Data Siswa
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-y-3 gap-x-6">
                    <div class="space-y-0.5 lg:col-span-1">
                        <p class="text-xs font-medium text-gray-600 uppercase">Nama Siswa</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['nama']) ?></p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs font-medium text-gray-600 uppercase">NIS</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['nis']) ?></p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs font-medium text-gray-600 uppercase">Kelas</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['kelas']) ?></p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs font-medium text-gray-600 uppercase">Jurusan</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['jurusan']) ?></p>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Sesi Konseling</h3>
            
            <div class="overflow-x-auto table-container shadow-md rounded-lg border border-gray-800">
                <table class="min-w-full divide-y divide-gray-800 data-table-report">
                    <thead class="bg-[#2F6C6E] text-white">
                        <tr>
                            <th class="sticky-col px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-[50px] border-r border-gray-700">No.</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[120px]">Tanggal Pelaksanaan</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[80px] border-r border-gray-700">Pertemuan Ke-</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[80px] border-r border-gray-700">Panggilan Ke-</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[150px]">Waktu dan Tempat</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[150px]">Atas Dasar</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[250px]">Teknik Pendekatan</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[250px]">Teknik Konseling</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[350px]">Gejala yang Nampak</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider border-r border-gray-700 w-[350px]">Hasil yang Dicapai</th>
                            
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[120px]">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-800">
                        <?php if ($riwayat_count > 0): ?>
                            <?php $no = $start_number; while ($data = $result_riwayat->fetch_assoc()): ?>
                                <?php
                                    $tanggal_indo = tgl_indo($data['tanggal_pelaksanaan']);
                                    $has_kepuasan = $data['aspek_penerimaan'] > 0;
                                    
                                    $js_r1 = $data['aspek_penerimaan'] ?? 0;
                                    $js_r2 = $data['aspek_kemudahan_curhat'] ?? 0;
                                    $js_r3 = $data['aspek_kepercayaan'] ?? 0;
                                    $js_r4 = $data['aspek_pemecahan_masalah'] ?? 0;
                                    $js_tanggal = htmlspecialchars($data['tanggal_isi'] ?? '', ENT_QUOTES);
                                ?>
                                <tr class="odd:bg-white even:bg-gray-50">
                                    <td class="sticky-col px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r border-gray-700 w-[50px]"><?= $no++ ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 border-r border-gray-700 w-[120px]"><?= $tanggal_indo ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 text-center border-r border-gray-700 w-[80px]"><?= htmlspecialchars($data['pertemuan_ke']) ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 text-center border-r border-gray-700 w-[80px]"><?= htmlspecialchars($data['panggilan_ke']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-500 whitespace-normal border-r border-gray-700 w-[150px]">
                                        <?= htmlspecialchars($data['waktu_durasi']) ?><br>
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($data['tempat']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-500 border-r border-gray-700 w-[150px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5">
                                            <?= htmlspecialchars($data['atas_dasar']) ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-500 border-r border-gray-700 w-[250px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5">
                                            <?= htmlspecialchars($data['pendekatan_konseling']) ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-500 border-r border-gray-700 w-[250px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5">
                                            <?= htmlspecialchars($data['teknik_konseling']) ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-500 border-r border-gray-700 w-[350px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5">
                                            <?= htmlspecialchars($data['gejala_nampak']) ?>
                                        </div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-500 border-r border-gray-700 w-[350px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5">
                                            <?= htmlspecialchars($data['hasil_dicapai']) ?>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 text-center text-sm font-medium w-[120px]">
                                        <div class="flex flex-col space-y-1">
                                            <button 
                                                onclick="openKepuasanModal(
                                                    '<?= htmlspecialchars($data['id_konseling']) ?>',
                                                    '<?= htmlspecialchars($data['pertemuan_ke']) ?>',
                                                    '<?= $js_r1 ?>', '<?= $js_r2 ?>', '<?= $js_r3 ?>', '<?= $js_r4 ?>',
                                                    '<?= $js_tanggal ?>',
                                                    '<?= htmlspecialchars($siswa_data['nama'], ENT_QUOTES) ?>' 
                                                )"
                                                class="w-full text-white px-3 py-1 rounded-lg transition duration-200 text-xs 
                                                <?= $has_kepuasan ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 hover:bg-gray-500' ?>">
                                                <i class="fas fa-star mr-1"></i> Detail Kepuasan
                                            </button>
                                            <?php if ($data['file_pdf']): ?>
                                                <button onclick="openPdfViewerModal('<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>')" class="w-full bg-gray-500 text-white px-3 py-1 rounded-lg hover:bg-gray-600 transition duration-200 text-xs">
                                                    <i class="fas fa-file-pdf mr-1"></i> Lihat Laporan
                                                </button>
                                            <?php else: ?>
                                                <span class="w-full block text-gray-400 text-xs px-3 py-1 border border-gray-300 rounded-lg">PDF Belum Dibuat</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada riwayat konseling individu yang ditemukan untuk siswa ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="flex justify-between items-center mt-6">
                <nav class="flex items-center space-x-2" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= getPaginationUrl($page - 1, $limit) ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= getPaginationUrl($page + 1, $limit) ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>

        </div>
    </main>

    <footer class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>

    <div id="kepuasanModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl flex flex-col transform scale-100 transition-all max-h-[90vh]">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="kepuasanModalTitle" class="text-xl font-semibold text-gray-800">Detail Kepuasan Siswa</h3>
                <button onclick="closeKepuasanModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-4 overflow-y-auto">
                <h4 class="text-lg font-bold text-center border-b pb-2">KEPUASAN KONSELI TERHADAP PROSES LAYANAN KONSELING INDIVIDUAL</h4>
                
                <div class="mb-4 p-3 rounded-lg border border-indigo-300 bg-indigo-50 text-indigo-800 text-base font-medium">
                    <span class="font-bold text-gray-700">Nama Siswa:</span>
                    <span id="modalNamaSiswa" class="font-extrabold text-indigo-800 ml-2">...</span>
                </div>
                
                <div class="flex justify-between items-center text-sm p-2 bg-gray-100 border rounded-lg">
                    <p class="font-medium text-gray-700">Status Pengisian: <span id="statusKepuasan" class="font-semibold"></span></p>
                    <p class="font-medium text-gray-700">Tanggal Diisi: <span id="tanggalDiisi" class="font-semibold text-gray-800"></span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-300" id="kepuasanTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase w-[5%] border-r">No</th>
                                <th class="px-6 py-2 text-left text-xs font-bold text-gray-600 uppercase w-[45%] border-r">ASPEK yang dinilai</th>
                                <th class="px-3 py-2 text-center text-xs font-bold text-green-600 uppercase w-[16%] border-r">3. SANGAT MEMUASKAN</th>
                                <th class="px-3 py-2 text-center text-xs font-bold text-yellow-600 uppercase w-[16%] border-r">2. MEMUASKAN</th>
                                <th class="px-3 py-2 text-center text-xs font-bold text-red-600 uppercase w-[16%]">1. KURANG MEMUASKAN</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-center text-sm">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">1.</td>
                                <td class="px-6 py-3 text-left border-r">Penerimaan Guru BK/Konselor (Kehangatan, Empati)</td>
                                <td id="aspek1_3" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek1_2" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek1_1" class="rating-cell py-2 font-medium">◻</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">2.</td>
                                <td class="px-6 py-3 text-left border-r">Kemudahan Guru BK/Konselor untuk diajak curhat</td>
                                <td id="aspek2_3" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek2_2" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek2_1" class="rating-cell py-2 font-medium">◻</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">3.</td>
                                <td class="px-6 py-3 text-left border-r">Kepercayaan anda terhadap Guru BK/Konselor dalam layanan konseling</td>
                                <td id="aspek3_3" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek3_2" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek3_1" class="rating-cell py-2 font-medium">◻</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">4.</td>
                                <td class="px-6 py-3 text-left border-r">Pelayanan pemecahan masalah bisa tercapai melalui konseling individual</td>
                                <td id="aspek4_3" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek4_2" class="rating-cell py-2 border-r font-medium">◻</td>
                                <td id="aspek4_1" class="rating-cell py-2 font-medium">◻</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-8">
                    <div class="text-center">
                        <p class="text-sm">Banjarmasin, <span id="tanggalIsiCetak" class="font-medium"></span></p>
                        <p class="font-medium mt-1">Konseli/Siswa</p>
                        <div class="mt-12">
                            <p class="font-semibold underline">(&nbsp;<?= htmlspecialchars($siswa_data['nama']) ?>&nbsp;)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-3 border-t flex justify-end bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closeKepuasanModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[905px] flex flex-col transform scale-100 transition-all">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="pdfIframeTitle" class="text-xl font-semibold text-gray-800">Laporan Konseling Individu</h3>
                <button onclick="closePdfViewerModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[65vh] border-0" title="PDF Viewer"></iframe>
            </div>

            <div class="px-6 py-3 border-t flex justify-end space-x-3 bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closePdfViewerModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                    <i class="fas fa-arrow-left mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</body>
</html>