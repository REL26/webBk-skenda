<?php
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$nama_konselor = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru BK (Tidak Diketahui)';

function tgl_indo($tanggal){
	$bulan = array (
		1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
		'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
	);
    $pecahkan = explode('-', $tanggal);
    
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$filter_search  = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_tgl_start = isset($_GET['tgl_start']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_start'])) : '';
$filter_guru    = isset($_GET['guru']) ? mysqli_real_escape_string($koneksi, trim($_GET['guru'])) : '';

$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where_clauses = [];
$bind_params = '';
$bind_values = [];

if (!empty($filter_tgl_start)) {
    $where_clauses[] = "k.tanggal_pelaksanaan >= ?";
    $bind_params .= 's';
    $bind_values[] = $filter_tgl_start;
}

if (!empty($filter_guru)) {
    $where_clauses[] = "k.nama_guru LIKE ?";
    $bind_params .= 's';
    $bind_values[] = "%$filter_guru%";
}

if (!empty($filter_search)) {
    // Penyesuaian kueri untuk pencarian
    // Perhatikan penambahan placeholder 's' untuk nama_guru dalam tabel kelompok
    $where_clauses[] = " (
        k.nama_guru LIKE ?
        OR k.id_kelompok IN (
            SELECT dk.id_kelompok 
            FROM detail_kelompok dk
            JOIN siswa s ON dk.id_siswa = s.id_siswa
            WHERE s.nama LIKE ? OR s.nis LIKE ?
        )
    ) ";
    $bind_params .= 'sss'; // Menghapus satu 's' yang berlebih dari kode asli, karena $search_term hanya 3 kali digunakan
    $search_term = "%$filter_search%";
    $bind_values[] = $search_term;
    $bind_values[] = $search_term;
    $bind_values[] = $search_term;
}


$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$query_count = "
    SELECT COUNT(k.id_kelompok) as total_count
    FROM kelompok k
    " . $where_sql;

$stmt_count = $koneksi->prepare($query_count);
if ($bind_params) {
    // Perbaikan: mysqli_stmt::bind_param membutuhkan referensi, namun untuk array unpack (...) PHP 5.6+ dapat melakukannya.
    // Jika ada error di sini, gunakan call_user_func_array jika menggunakan PHP versi lama.
    $stmt_count->bind_param($bind_params, ...$bind_values); 
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_riwayat = $result_count->fetch_assoc()['total_count'];
$total_pages = ceil($total_riwayat / $limit);
$stmt_count->close();

$query_riwayat = "
    SELECT 
        k.*, 
        rk.file_pdf
    FROM 
        kelompok k
    LEFT JOIN 
        riwayat_kelompok rk ON k.id_kelompok = rk.id_kelompok
    " . $where_sql . "
    ORDER BY 
        k.tanggal_pelaksanaan DESC, k.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt_riwayat = $koneksi->prepare($query_riwayat);

$final_bind_params = $bind_params . 'ii';
$final_bind_values = array_merge($bind_values, [$limit, $offset]);

if ($final_bind_params) {
    $stmt_riwayat->bind_param($final_bind_params, ...$final_bind_values);
}
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$riwayat_count = $result_riwayat->num_rows; 
$no = $offset + 1; 
// $stmt_riwayat->close(); // Ditutup setelah loop while
// $koneksi->close(); // Ditutup setelah mengambil semua data yang dibutuhkan

$query_gurus = "SELECT DISTINCT nama_guru FROM kelompok ORDER BY nama_guru ASC";
$result_gurus = $koneksi->query($query_gurus);

$koneksi->close(); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Konseling Kelompok | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-color { color: #2F6C6E; }
        .modal { transition: opacity 0.3s ease, visibility 0.3s ease; visibility: hidden; opacity: 0; }
        .modal.open { visibility: visible; opacity: 1; }
        .checked-indicator { font-weight: bold; color: #10B981; }
        .data-table-report th, .data-table-report td { white-space: nowrap; }
        /* Style untuk status kepuasan yang baru */
        .status-sm { color: #16A34A; font-weight: 600; } /* Hijau tua */
        .status-m { color: #3B82F6; font-weight: 500; }  /* Biru */
        .status-km { color: #F59E0B; font-weight: 500; } /* Kuning/Oranye */
        .status-na { color: #9CA3AF; font-weight: 400; } /* Abu-abu */
    </style>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
            document.body.classList.remove('overflow-hidden');
        }

        function openPdfViewerModal(pdfPath, title) {
            const modal = document.getElementById('pdfViewerModal');
            const iframe = document.getElementById('pdfIframe');
            
            document.getElementById('pdfIframeTitle').textContent = title;
            const fixedPath = '../' + pdfPath; 
            iframe.src = fixedPath;

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
        }
        
        // Fungsi untuk mendapatkan status kepuasan yang lebih jelas
        function getRatingStatus(score) {
            score = parseInt(score);
            if (score === 3) return { text: 'Sangat Memuaskan (3)', class: 'status-sm' };
            if (score === 2) return { text: 'Memuaskan (2)', class: 'status-m' };
            if (score === 1) return { text: 'Kurang Memuaskan (1)', class: 'status-km' };
            return { text: 'Belum Diisi', class: 'status-na' };
        }

        function generateKepuasanTable(data) {
            let tableHtml = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase w-[20%] border-r">Nama Siswa</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Penerimaan</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Kemudahan Curhat</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Kepercayaan</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Pemecahan Masalah</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 uppercase w-[20%]">Status Pengisian</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-center text-sm">
            `;
            // Mengurutkan data berdasarkan nama siswa (pilihan tambahan untuk kemudahan melihat)
            data.sort((a, b) => a.nama.localeCompare(b.nama));
            
            data.forEach(item => {
                const statusPenerimaan = getRatingStatus(item.aspek_penerimaan);
                const statusCurhat = getRatingStatus(item.aspek_kemudahan_curhat);
                const statusKepercayaan = getRatingStatus(item.aspek_kepercayaan);
                const statusPemecahan = getRatingStatus(item.aspek_pemecahan_masalah);
                
                const overallStatus = parseInt(item.aspek_penerimaan) > 0 ? 
                                    `<span class="text-green-600 font-semibold">Sudah mengisi ${item.tanggal_isi ? new Date(item.tanggal_isi).toLocaleDateString('id-ID') : ''}</span>` : 
                                    '<span class="text-red-600">Belum Diisi</span>';
                
                tableHtml += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-left border-r text-gray-800 font-medium">${item.nama}</td>
                        <td class="px-4 py-3 border-r"><span class="${statusPenerimaan.class}">${statusPenerimaan.text}</span></td>
                        <td class="px-4 py-3 border-r"><span class="${statusCurhat.class}">${statusCurhat.text}</span></td>
                        <td class="px-4 py-3 border-r"><span class="${statusKepercayaan.class}">${statusKepercayaan.text}</span></td>
                        <td class="px-4 py-3 border-r"><span class="${statusPemecahan.class}">${statusPemecahan.text}</span></td>
                        <td class="px-4 py-3">${overallStatus}</td>
                    </tr>
                `;
            });
            tableHtml += `
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-3 text-center">Penjelasan Skala: Sangat Memuaskan (3), Memuaskan (2), Kurang Memuaskan (1).</p>
            `;
            return tableHtml;
        }

        
        function openKepuasanModal(id_kelompok, pertemuan_ke) {
            const modal = $('#kepuasanModal');
            $('#kepuasanModalTitle').text(`Kepuasan Konseli (Sesi Kelompok Ke-${pertemuan_ke})`);
            $('#kepuasanListContainer').html('<div class="text-center py-8 text-gray-500"><i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat data kepuasan siswa...</div>');

            $.ajax({
                url: 'ajax_riwayat_kelompok.php', 
                method: 'GET',
                data: { action: 'get_kepuasan', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        let html = '';
                        if (response.data.length > 0) {
                            html = generateKepuasanTable(response.data);
                        } else {
                            html = '<div class="text-center py-8 text-gray-500">Belum ada data kepuasan yang diisi untuk sesi ini.</div>';
                        }
                        $('#kepuasanListContainer').html(html);
                    } else {
                        $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600">Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600">Terjadi kesalahan koneksi saat memuat data kepuasan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
        }

        function openReportDetailModal(id_kelompok, pertemuan_ke) {
            const modal = $('#reportDetailModal');
            $('#reportDetailModalTitle').text(`Detail Laporan Konseling Kelompok Ke-${pertemuan_ke}`);
            $('#reportContentContainer').html('<div class="text-center py-8 text-gray-500"><i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat detail laporan...</div>');

            $.ajax({
                url: 'ajax_riwayat_kelompok.php', 
                method: 'GET',
                data: { action: 'get_report_full_detail', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const report = response.data.report;
                        const students = response.data.students;

                        let reportHtml = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6 p-4 border rounded-lg bg-indigo-50">
                                <p><strong>Tanggal Pelaksanaan:</strong> ${report.tanggal_pelaksanaan ? new Date(report.tanggal_pelaksanaan).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : '-'}</p>
                                <p><strong>Pertemuan Ke-:</strong> ${report.pertemuan_ke}</p>
                                <p><strong>Waktu & Durasi:</strong> ${report.waktu_durasi}</p>
                                <p><strong>Tempat:</strong> ${report.tempat}</p>   
                                <p class="md:col-span-2"><strong>Teknik Pendekatan:</strong> ${report.catatan_khusus}</p>
                                <p class="md:col-span-2"><strong>Teknik Konseling:</strong> ${report.teknik_konseling}</p>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-inner mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1">Hasil yang dicapai:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.hasil_layanan || 'Tidak ada catatan Hasil yang dicapai.'}</p>
                            </div>
                        `;

                        let studentHtml = '<h4 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2"><i class="fas fa-users mr-2"></i> Siswa yang Terlibat:</h4>';
                        if (students.length > 0) {
                            studentHtml += '<ul class="list-disc pl-5 space-y-1">';
                            students.forEach((s, index) => {
                                studentHtml += `<li>${s.nama} <span class="text-sm text-gray-500">(${s.kelas} - ${s.jurusan})</span></li>`;
                            });
                            studentHtml += '</ul>';
                        } else {
                            studentHtml += '<p class="text-gray-500">Tidak ada siswa yang terdaftar dalam sesi ini.</p>';
                        }
                        
                        $('#reportContentContainer').html(studentHtml + reportHtml );

                    } else {
                        $('#reportContentContainer').html('<div class="text-center py-8 text-red-600">Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#reportContentContainer').html('<div class="text-center py-8 text-red-600">Terjadi kesalahan koneksi saat memuat detail laporan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
        }

        function getPaginationUrl(page) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            urlParams.delete('limit'); 
            return '?' + urlParams.toString();
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="fixed top-0 left-0 w-full bg-white shadow-md z-30 flex items-center justify-between h-[56px] px-4">
        <a href="#" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <span class="text-lg font-bold primary-color hidden sm:inline">Riwayat Konseling Kelompok</span>
        </a>
        <a href="konselingkelompok.php" class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-gray-600 text-sm flex items-center transition duration-200">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Data Siswa
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 mt-[56px] w-full"> 
        <div class="bg-white p-4 md:p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    Daftar Semua Laporan Konseling Kelompok
                </h2>
            </div>
            
            <form method="GET" class="mb-6 p-4 border rounded-lg bg-gray-50 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari (Topik / Siswa / Guru)</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Masukkan kata kunci..." class="w-full p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label for="guru" class="block text-sm font-medium text-gray-700 mb-1">Guru BK Pelaksana</label>
                        <select name="guru" id="guru" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Semua Guru --</option>
                            <?php if ($result_gurus && $result_gurus->num_rows > 0): ?>
                                <?php while($guru = $result_gurus->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($guru['nama_guru']) ?>" <?= $filter_guru == $guru['nama_guru'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($guru['nama_guru']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label for="tgl_start" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Pelaksanaan</label>
                        <input type="date" name="tgl_start" id="tgl_start" value="<?= htmlspecialchars($filter_tgl_start) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-150 flex items-center shadow-md">
                        <i class="fas fa-filter mr-2"></i> Terapkan Filter
                    </button>
                    <a href="riwayat_kelompok.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-150 flex items-center shadow-md">
                        <i class="fas fa-redo mr-2"></i> Reset Filter
                    </a>
                </div>
            </form>
            
            <p class="text-sm text-gray-600 mb-4">Ditemukan <?= $total_riwayat ?> laporan Konseling Kelompok.</p>

            <div class="overflow-x-auto shadow-md rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 data-table-report">
                    <thead class="bg-[#2F6C6E] text-white">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-[50px]">No.</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-[120px]">Tanggal</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[100px]">Pertemuan Ke-</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-[250px]">Waktu/Tempat</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-[200px]">Guru BK Pelaksana</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[100px]">Detail Laporan</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[100px]">Kepuasan Siswa</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider w-[100px]">Laporan PDF</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($riwayat_count > 0): ?>
                            <?php while ($data = $result_riwayat->fetch_assoc()): ?>
                                <?php
                                    $tanggal_indo = tgl_indo($data['tanggal_pelaksanaan']);
                                ?>
                                <tr class="odd:bg-white even:bg-gray-50">
                                    <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?= $no++ ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500"><?= $tanggal_indo ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500 text-center"><?= htmlspecialchars($data['pertemuan_ke']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-500 whitespace-normal">
                                        <?= htmlspecialchars($data['waktu_durasi']) ?><br>
                                        <span class="font-medium text-gray-700"><?= htmlspecialchars($data['tempat']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-500 whitespace-normal"><?= htmlspecialchars($data['nama_guru']) ?></td>
                                    
                                    <td class="px-3 py-3 text-center">
                                        <button onclick="openReportDetailModal('<?= htmlspecialchars($data['id_kelompok']) ?>', '<?= htmlspecialchars($data['pertemuan_ke']) ?>')" class="bg-indigo-500 text-white px-3 py-1 rounded-lg hover:bg-indigo-600 transition duration-200 text-xs">
                                            <i class="fas fa-file-lines mr-1"></i> Detail Laporan
                                        </button>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-center">
                                        <button onclick="openKepuasanModal('<?= htmlspecialchars($data['id_kelompok']) ?>', '<?= htmlspecialchars($data['pertemuan_ke']) ?>')" class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600 transition duration-200 text-xs">
                                            <i class="fas fa-star mr-1"></i> Kepuasan
                                        </button>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-center">
                                        <?php if ($data['file_pdf']): ?>
                                            <button onclick="openPdfViewerModal('<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Kelompok Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>')" class="bg-gray-500 text-white px-3 py-1 rounded-lg hover:bg-gray-600 transition duration-200 text-xs">
                                                <i class="fas fa-file-pdf mr-1"></i> Lihat
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Tidak ada laporan konseling kelompok yang ditemukan dengan filter ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($result_riwayat) $result_riwayat->close(); // Tutup result set setelah digunakan ?>
            <?php if ($stmt_riwayat) $stmt_riwayat->close(); // Tutup statement setelah result set ditutup ?>

            <div class="flex justify-between items-center mt-6">
                <div class="text-sm text-gray-700">
                    Menampilkan <?= min($limit, $total_riwayat - $offset) ?> dari <?= $total_riwayat ?> laporan.
                </div>
                <nav class="flex items-center space-x-2" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= getPaginationUrl($page - 1) ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-2 text-sm font-semibold text-indigo-600 bg-indigo-50 border border-indigo-300 rounded-lg shadow-sm">
                        Halaman <?= $page ?> dari <?= $total_pages ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= getPaginationUrl($page + 1) ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                            Berikutnya <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

        </div>
    </main>

    <footer class="text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto">
        &copy; 2025 Bimbingan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>

    <div id="reportDetailModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl flex flex-col transform scale-100 transition-all max-h-[90vh]">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="reportDetailModalTitle" class="text-xl font-semibold text-gray-800">Detail Laporan Kelompok</h3>
                <button onclick="closeModal('reportDetailModal')" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="reportContentContainer" class="p-6 space-y-4 overflow-y-auto">
                </div>
            <div class="px-6 py-3 border-t flex justify-end bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closeModal('reportDetailModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Tutup</button>
            </div>
        </div>
    </div>

    <div id="kepuasanModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl flex flex-col transform scale-100 transition-all max-h-[90vh]">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="kepuasanModalTitle" class="text-xl font-semibold text-gray-800">Detail Kepuasan Konseli</h3>
                <button onclick="closeModal('kepuasanModal')" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="kepuasanListContainer" class="p-6 space-y-4 overflow-y-auto">
                </div>
            <div class="px-6 py-3 border-t flex justify-end bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closeModal('kepuasanModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Tutup</button>
            </div>
        </div>
    </div>

    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[905px] flex flex-col transform scale-100 transition-all">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="pdfIframeTitle" class="text-xl font-semibold text-gray-800">Laporan Konseling Kelompok</h3>
                <button onclick="closeModal('pdfViewerModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[65vh] border-0" title="PDF Viewer"></iframe>
            </div>
            <div class="px-6 py-3 border-t flex justify-end space-x-3 bg-gray-50 sticky bottom-0 z-10">
                <button type="button" onclick="closeModal('pdfViewerModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400"><i class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>

</body>
</html>