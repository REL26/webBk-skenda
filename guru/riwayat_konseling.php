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
        :root {
            --primary-color: #2F6C6E;
            --primary-dark: #1E4647;
            --primary-light: #5FA8A1;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-bg { background-color: var(--primary-color); }
        .primary-color { color: var(--primary-color); }
        .sticky-col { 
            position: sticky; 
            left: 0; 
            z-index: 10; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); 
        }
        .data-table-report thead th.sticky-col { 
            background-color: var(--primary-dark) !important; 
            z-index: 20; 
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
        .modal-content {
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .modal.open .modal-content {
            transform: scale(1);
        }

        .rating-cell {
            transition: background-color 0.15s ease, border-color 0.15s ease;
            cursor: default;
        }
        .rating-cell.selected-rating {
            background-color: #ecfdf5; 
            color: #047857; 
            font-weight: 700;
            border: 2px solid #34d399;
        }

        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none !important;
            }
        }
    </style>

    <script>
        const limit_desktop = <?= $limit_desktop ?>;
        const limit_mobile = <?= $limit_mobile ?>;

        function openKepuasanModal(id_konseling, p_ke, r1, r2, r3, r4, tanggal, nama_siswa) {
            const modal = document.getElementById('kepuasanModal');
            const modalContent = modal.querySelector('.modal-content');

            document.getElementById('modalNamaSiswa').textContent = nama_siswa;
            document.getElementById('kepuasanModalTitle').textContent = `Kepuasan Siswa (Sesi Ke-${p_ke})`;
            
            const isFilled = (parseInt(r1) > 0);
            
            const statusElement = document.getElementById('statusKepuasan');
            const tglDisplay = tanggal ? new Date(tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : 'Belum diisi';
            document.getElementById('tanggalDiisi').textContent = tglDisplay;
            document.getElementById('tanggalIsiCetak').textContent = tanggal ? new Date(tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '...';
            document.querySelectorAll('#kepuasanTable td.rating-cell').forEach(cell => {
                cell.innerHTML = '<i class="far fa-circle text-xl text-gray-300"></i>'; 
                cell.classList.remove('selected-rating');
            });


            if (isFilled) {
                statusElement.innerHTML = `<span class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i> Sudah Diisi</span>`;
                
                function highlightRating(aspectNum, ratingValue) {
                    const ratingId = `aspek${aspectNum}_${ratingValue}`;
                    const cell = document.getElementById(ratingId);
                    if (cell) {
                        cell.innerHTML = '<i class="fas fa-check-circle text-xl"></i>'; 
                        cell.classList.add('selected-rating');
                    }
                }

                highlightRating(1, r1);
                highlightRating(2, r2);
                highlightRating(3, r3);
                highlightRating(4, r4);
                
            } else {
                statusElement.innerHTML = `<span class="text-red-600 font-semibold"><i class="fas fa-times-circle mr-1"></i> Belum Diisi</span>`;
            }

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            modalContent.classList.add('scale-100');
        }

        function closeKepuasanModal() {
            const modal = document.getElementById('kepuasanModal');
            const modalContent = modal.querySelector('.modal-content');
            
            modalContent.classList.remove('scale-100');

            setTimeout(() => {
                modal.classList.remove('open');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }

        function openPdfViewerModal(pdfPath, title) {
            const modal = document.getElementById('pdfViewerModal');
            const modalContent = modal.querySelector('.modal-content');
            const iframe = document.getElementById('pdfIframe');
            
            document.getElementById('pdfIframeTitle').textContent = title;

            const pathSegments = window.location.pathname.split('/');
            pathSegments.pop(); 
            const currentDir = pathSegments.join('/');
            const pdfUrl = pdfPath.startsWith('..') ? `${currentDir}/${pdfPath.replace('../', '')}` : pdfPath;
            iframe.src = pdfPath;

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            modalContent.classList.add('scale-100');
        }

        function closePdfViewerModal() {
            const modal = document.getElementById('pdfViewerModal');
            const modalContent = modal.querySelector('.modal-content');
            const iframe = document.getElementById('pdfIframe');
            
            modalContent.classList.remove('scale-100');

            setTimeout(() => {
                iframe.src = ''; 
                modal.classList.remove('open');
                document.body.classList.remove('overflow-hidden');
            }, 300);
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

            document.addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    if (document.getElementById('kepuasanModal').classList.contains('open')) {
                        closeKepuasanModal();
                    } else if (document.getElementById('pdfViewerModal').classList.contains('open')) {
                        closePdfViewerModal();
                    }
                }
            });
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

    <header class="fixed top-0 left-0 w-full bg-white shadow-lg z-30 flex items-center justify-between h-[64px] px-4 md:px-8 border-b primary-color border-gray-100">
        <a href="#" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-10 w-10">
            <span class="text-xl font-bold primary-color hidden sm:inline">Riwayat Konseling Individu</span>
        </a>
        <a href="konselingindividu.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium flex items-center transition duration-200 shadow-md">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 pt-20 md:pt-24 w-full"> 
        <div class="bg-white p-4 md:p-8 rounded-xl shadow-2xl border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b pb-4">
                <h2 class="text-3xl font-extrabold primary-color mb-2 md:mb-0">
                    <i class="fas fa-clipboard-list mr-2"></i> Riwayat Sesi Konseling
                </h2>
                <div class="text-sm font-medium text-gray-600">
                    Konselor Aktif: <span class="primary-color font-semibold"><?= $nama_konselor ?></span>
                </div>
            </div>

            <div class="mb-8 p-6 border-l-4 border-[#5FA8A1] bg-[#eef5f5] rounded-xl shadow-inner">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-graduate mr-3 text-2xl primary-color"></i> Data Siswa
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-y-4 gap-x-6 text-sm">
                    <div class="space-y-0.5 lg:col-span-1 border-b pb-2 md:border-b-0 md:pb-0">
                        <p class="text-xs font-medium text-gray-600 uppercase">Nama Siswa</p>
                        <p class="text-base font-extrabold text-gray-900"><?= htmlspecialchars($siswa_data['nama']) ?></p>
                    </div>
                    <div class="space-y-0.5 border-b pb-2 md:border-b-0 md:pb-0">
                        <p class="text-xs font-medium text-gray-600 uppercase">NIS</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['nis']) ?></p>
                    </div>
                    <div class="space-y-0.5 border-b pb-2 md:border-b-0 md:pb-0">
                        <p class="text-xs font-medium text-gray-600 uppercase">Kelas</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['kelas']) ?></p>
                    </div>
                    <div class="space-y-0.5 border-b pb-2 md:border-b-0 md:pb-0">
                        <p class="text-xs font-medium text-gray-600 uppercase">Jurusan</p>
                        <p class="text-base font-semibold text-gray-900"><?= htmlspecialchars($siswa_data['jurusan']) ?></p>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                 <i class="fas fa-history mr-2 primary-color"></i> Riwayat Konseling (Total: <?= $total_riwayat ?> Sesi)
            </h3>
            
            <div class="overflow-x-auto table-container shadow-xl rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 data-table-report">
                    <thead class="primary-bg text-white">
                        <tr>
                            <th class="sticky-col px-3 py-3 text-left text-xs font-bold uppercase tracking-wider w-[50px] border-r border-gray-700">No.</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[120px]">Tanggal</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[80px] border-r border-gray-700 hide-on-mobile">Pert. Ke-</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[80px] border-r border-gray-700 hide-on-mobile">Pang. Ke-</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[150px]">Waktu & Tempat</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[150px] hide-on-mobile">Atas Dasar</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[250px] hide-on-mobile">Teknik Pendekatan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[250px] hide-on-mobile">Teknik Konseling</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[350px] hide-on-mobile">Gejala yang Nampak</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[350px] hide-on-mobile">Hasil yang Dicapai</th>
                            
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[120px]">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
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
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-yellow-50 transition duration-150">
                                    <td class="sticky-col px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 w-[50px]"><?= $no++ ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r border-gray-200 w-[120px]"><?= $tanggal_indo ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center border-r border-gray-200 w-[80px] hide-on-mobile"><?= htmlspecialchars($data['pertemuan_ke']) ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center border-r border-gray-200 w-[80px] hide-on-mobile"><?= htmlspecialchars($data['panggilan_ke']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-600 whitespace-normal border-r border-gray-200 w-[150px]">
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($data['waktu_durasi']) ?></div>
                                        <span class="text-xs text-gray-500 italic"><?= htmlspecialchars($data['tempat']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[150px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['atas_dasar']) ?></div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[250px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['pendekatan_konseling']) ?></div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[250px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['teknik_konseling']) ?></div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[350px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['gejala_nampak']) ?></div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[350px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['hasil_dicapai']) ?></div>
                                    </td>

                                    <td class="px-3 py-3 text-center text-sm font-medium w-[120px]">
                                        <div class="flex flex-col space-y-2">
                                            <button 
                                                onclick="openKepuasanModal(
                                                    '<?= htmlspecialchars($data['id_konseling']) ?>',
                                                    '<?= htmlspecialchars($data['pertemuan_ke']) ?>',
                                                    '<?= $js_r1 ?>', '<?= $js_r2 ?>', '<?= $js_r3 ?>', '<?= $js_r4 ?>',
                                                    '<?= $js_tanggal ?>',
                                                    '<?= htmlspecialchars($siswa_data['nama'], ENT_QUOTES) ?>' 
                                                )"
                                                class="w-full text-white px-3 py-1.5 rounded-lg transition duration-200 text-xs font-semibold shadow-md
                                                <?= $has_kepuasan ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-400 hover:bg-gray-500' ?>">
                                                <i class="fas fa-star mr-1"></i> Kepuasan Siswa
                                            </button>
                                            
                                            <?php if ($data['file_pdf']): ?>
                                                <button onclick="openPdfViewerModal('<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>')" class="w-full primary-bg text-white px-3 py-1.5 rounded-lg hover:bg-primary-dark transition duration-200 text-xs font-semibold shadow-md">
                                                    <i class="fas fa-file-pdf mr-1"></i> Lihat Laporan
                                                </button>
                                            <?php else: ?>
                                                <span class="w-full block text-gray-500 text-xs px-3 py-1.5 border border-gray-300 bg-gray-100 rounded-lg">Laporan Belum Ada</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="px-6 py-10 text-center text-lg font-medium text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i><br>
                                    Tidak ada riwayat konseling individu yang ditemukan untuk siswa ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-center mt-6 space-y-4 md:space-y-0">
                <div class="text-sm text-gray-600">
                    Menampilkan <span class="font-semibold"><?= $riwayat_count ?></span> dari <span class="font-semibold"><?= $total_riwayat ?></span> sesi. (Halaman <?= $page ?> dari <?= $total_pages ?>)
                </div>
                
                <nav class="flex items-center space-x-2" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= getPaginationUrl($page - 1, $limit) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            <i class="fas fa-chevron-left mr-2"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                            <i class="fas fa-chevron-left mr-2"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <span class="px-4 py-2 text-sm font-extrabold text-white primary-bg border border-primary-dark rounded-lg shadow-md">
                        <?= $page ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?= getPaginationUrl($page + 1, $limit) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            Berikutnya <i class="fas fa-chevron-right ml-2"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                            Berikutnya <i class="fas fa-chevron-right ml-2"></i>
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
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col transform modal-content max-h-[90vh]">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="kepuasanModalTitle" class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-indigo-600"></i> Detail Kepuasan Siswa
                </h3>
                <button onclick="closeKepuasanModal()" class="text-gray-500 hover:text-gray-800 p-2 rounded-full hover:bg-gray-100 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-6 overflow-y-auto">
                <h4 class="text-xl font-extrabold text-center text-gray-800 border-b pb-3">KEPUASAN KONSELI TERHADAP LAYANAN KONSELING INDIVIDUAL</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-3 rounded-lg border border-indigo-400 bg-indigo-50 text-base font-medium flex items-center">
                        <i class="fas fa-user-tag mr-2 text-indigo-600"></i>
                        <span class="font-bold text-gray-700">Nama Siswa:</span>
                        <span id="modalNamaSiswa" class="font-extrabold text-indigo-800 ml-2">...</span>
                    </div>
                    <div class="p-3 rounded-lg border border-gray-300 bg-gray-100 text-base font-medium flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-gray-600"></i>
                        <span class="font-bold text-gray-700">Tanggal Diisi:</span>
                        <span id="tanggalDiisi" class="font-semibold text-gray-800 ml-2"></span>
                    </div>
                </div>

                <div class="p-3 rounded-lg border border-gray-300 bg-white shadow-sm text-base font-medium">
                    <p class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Status Pengisian: 
                        <span id="statusKepuasan" class="ml-2 font-extrabold"></span>
                    </p>
                </div>


                <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-inner">
                    <table class="min-w-full divide-y divide-gray-200" id="kepuasanTable">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase w-[5%] border-r">No</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase w-[45%] border-r">ASPEK YANG DINILAI</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-green-600 uppercase w-[16%] border-r">3. SANGAT MEMUASKAN</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-yellow-600 uppercase w-[16%] border-r">2. MEMUASKAN</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-red-600 uppercase w-[16%]">1. KURANG MEMUASKAN</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-center text-sm">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">1.</td>
                                <td class="px-6 py-3 text-left border-r font-medium text-gray-700">Penerimaan Guru BK/Konselor (Kehangatan, Empati)</td>
                                <td id="aspek1_3" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek1_2" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek1_1" class="rating-cell py-3 text-gray-400"><i class="far fa-circle text-xl"></i></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">2.</td>
                                <td class="px-6 py-3 text-left border-r font-medium text-gray-700">Kemudahan Guru BK/Konselor untuk diajak curhat</td>
                                <td id="aspek2_3" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek2_2" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek2_1" class="rating-cell py-3 text-gray-400"><i class="far fa-circle text-xl"></i></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">3.</td>
                                <td class="px-6 py-3 text-left border-r font-medium text-gray-700">Kepercayaan anda terhadap Guru BK/Konselor dalam layanan konseling</td>
                                <td id="aspek3_3" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek3_2" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek3_1" class="rating-cell py-3 text-gray-400"><i class="far fa-circle text-xl"></i></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 border-r">4.</td>
                                <td class="px-6 py-3 text-left border-r font-medium text-gray-700">Pelayanan pemecahan masalah bisa tercapai melalui konseling individual</td>
                                <td id="aspek4_3" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek4_2" class="rating-cell py-3 border-r text-gray-400"><i class="far fa-circle text-xl"></i></td>
                                <td id="aspek4_1" class="rating-cell py-3 text-gray-400"><i class="far fa-circle text-xl"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-10">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Banjarmasin, <span id="tanggalIsiCetak" class="font-semibold text-gray-800"></span></p>
                        <p class="font-medium mt-1 text-gray-700">Konseli/Siswa</p>
                        <div class="mt-16 border-t border-gray-400 pt-1">
                            <p class="font-extrabold text-gray-900">( <?= htmlspecialchars($siswa_data['nama']) ?> )</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end bg-gray-50 sticky bottom-0 z-10 rounded-b-xl">
                <button type="button" onclick="closeKepuasanModal()" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold transition duration-150">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
    
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl flex flex-col transform modal-content max-h-[205vh]">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 primary-bg text-white z-10 rounded-t-xl">
                <h3 id="pdfIframeTitle" class="text-xl font-bold flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> Laporan Konseling Individu
                </h3>
                <button onclick="closePdfViewerModal()" class="text-white hover:text-gray-200 p-2 rounded-full hover:bg-white hover:bg-opacity-10 transition duration-150">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-grow overflow-hidden p-2">
    <iframe id="pdfIframe" src="" class="w-full h-full border border-gray-300 rounded-lg" title="PDF Viewer" style="min-height: 55vh;"></iframe>
</div>

            <div class="px-6 py-3 border-t flex justify-end space-x-3 bg-gray-50 sticky bottom-0 z-10 rounded-b-xl">
                <button type="button" onclick="closePdfViewerModal()" class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold transition duration-150 shadow-md">
                    <i class="fas fa-arrow-left mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</body>
</html>