<?php
// PHP BLOCK - Logika Data dan Query
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

// --- Logika Filter ---
$filter_search 	= isset($_GET['search']) ? mysqli_real_escape_string($koneksi, trim($_GET['search'])) : '';
$filter_tgl_start = isset($_GET['tgl_start']) ? mysqli_real_escape_string($koneksi, trim($_GET['tgl_start'])) : '';
$filter_guru 	= isset($_GET['guru']) ? mysqli_real_escape_string($koneksi, trim($_GET['guru'])) : '';

$limit_desktop = 20;
$limit_mobile = 10;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : $limit_desktop;
if (!in_array($limit, [$limit_desktop, $limit_mobile])) {
    $limit = $limit_desktop;
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Logika Query (menggunakan prepared statement) ---

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
    // Mencari berdasarkan nama guru, topik, atau siswa yang terlibat
    $where_clauses[] = " (
        k.nama_guru LIKE ?
        OR k.topik LIKE ?
        OR k.id_kelompok IN (
            SELECT dk.id_kelompok 
            FROM detail_kelompok dk
            JOIN siswa s ON dk.id_siswa = s.id_siswa
            WHERE s.nama LIKE ? OR s.nis LIKE ?
        )
    ) ";
    $bind_params .= 'ssss';
    $search_term = "%$filter_search%";
    $bind_values[] = $search_term;
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
    // Menggunakan call_user_func_array jika PHP versi lama (<5.6)
    // Untuk PHP modern, array unpack (...) sudah cukup
    $stmt_riwayat->bind_param($final_bind_params, ...$final_bind_values);
}
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$riwayat_count = $result_riwayat->num_rows; 
$start_number = $offset + 1; 

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
        /* Tetapkan warna utama: teal/hijau gelap */
        :root {
            --primary-color: #2F6C6E;
            --primary-dark: #1E4647;
            --primary-light: #5FA8A1;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-bg { background-color: var(--primary-color); }
        .primary-color { color: var(--primary-color); }
        .primary-border-left { border-left-color: var(--primary-light); }

        /* Style untuk kolom lengket (Sticky Column) */
        .sticky-col { 
            position: sticky; 
            left: 0; 
            z-index: 10; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1); 
        }
        .data-table-report thead th.sticky-col { 
            background-color: var(--primary-dark) !important; /* Warna Header yang lebih gelap */
            z-index: 20; /* Pastikan di atas thead yang lain saat digulir */
        }
        .data-table-report tbody td.sticky-col {
            background-color: white; 
        }
        .data-table-report tbody tr:nth-child(even) td.sticky-col {
            background-color: #f9fafb; /* gray-50 */
        }
        
        /* Styling Modal: Lebih interaktif */
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

        /* Gaya Khusus Tabel Status Kepuasan */
        .status-sm { color: #16A34A; font-weight: 600; } /* Sangat Memuaskan (Hijau tua) */
        .status-m { color: #3B82F6; font-weight: 500; } 	/* Memuaskan (Biru) */
        .status-km { color: #F59E0B; font-weight: 500; } /* Kurang Memuaskan (Oranye) */
        .status-na { color: #9CA3AF; font-weight: 400; } /* Belum Diisi (Abu-abu) */

        /* Responsif: Sembunyikan kolom prioritas rendah di Mobile */
        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none !important;
            }
        }
    </style>

    <script>
        const limit_desktop = <?= $limit_desktop ?>;
        const limit_mobile = <?= $limit_mobile ?>;
        
        function getRatingStatus(score) {
            score = parseInt(score);
            if (score === 3) return { text: 'Sangat Memuaskan', class: 'status-sm' };
            if (score === 2) return { text: 'Memuaskan', class: 'status-m' };
            if (score === 1) return { text: 'Kurang Memuaskan', class: 'status-km' };
            return { text: 'Belum Diisi', class: 'status-na' };
        }

        function generateKepuasanTable(data) {
            if (data.length === 0) {
                return '<div class="text-center py-8 text-lg font-medium text-gray-500">Belum ada data kepuasan yang diisi untuk sesi ini.</div>';
            }
            
            // Logika generate tabel kepuasan
            let tableHtml = `
                <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-inner">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase w-[20%] border-r">Nama Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase w-[15%] border-r">Kelas/Jurusan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Rata-Rata Kepuasan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Penerimaan (3)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-green-700 uppercase w-[15%] border-r">Kepercayaan (3)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase w-[20%]">Status Pengisian</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
            `;
            data.sort((a, b) => a.nama.localeCompare(b.nama));
            
            data.forEach(item => {
                const statusPenerimaan = getRatingStatus(item.aspek_penerimaan);
                const statusKepercayaan = getRatingStatus(item.aspek_kepercayaan);
                
                const avgScore = (parseInt(item.aspek_penerimaan || 0) + parseInt(item.aspek_kemudahan_curhat || 0) + parseInt(item.aspek_kepercayaan || 0) + parseInt(item.aspek_pemecahan_masalah || 0)) / 4;
                const avgText = isNaN(avgScore) || avgScore === 0 ? 'N/A' : avgScore.toFixed(1);

                const overallStatus = parseInt(item.aspek_penerimaan) > 0 ? 
                                        `<span class="text-green-600 font-semibold text-xs">Diisi: ${item.tanggal_isi ? new Date(item.tanggal_isi).toLocaleDateString('id-ID') : ''}</span>` : 
                                        '<span class="text-red-600 font-semibold text-xs">Belum Diisi</span>';
                
                tableHtml += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-left border-r text-gray-800 font-medium whitespace-nowrap">${item.nama}</td>
                        <td class="px-4 py-3 text-left border-r text-gray-600 text-xs">${item.kelas} - ${item.jurusan}</td>
                        <td class="px-4 py-3 text-center border-r font-extrabold text-lg text-indigo-600">${avgText}</td>
                        <td class="px-4 py-3 text-center border-r"><span class="${statusPenerimaan.class} text-xs">${statusPenerimaan.text}</span></td>
                        <td class="px-4 py-3 text-center border-r"><span class="${statusKepercayaan.class} text-xs">${statusKepercayaan.text}</span></td>
                        <td class="px-4 py-3 text-center">${overallStatus}</td>
                    </tr>
                `;
            });
            tableHtml += `
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-3 text-center">Rata-Rata Kepuasan dihitung dari 4 aspek. Skala: Sangat Memuaskan (3), Memuaskan (2), Kurang Memuaskan (1).</p>
            `;
            return tableHtml;
        }


        // MODAL KEPUASAN (Diubah untuk Kelompok)
        function openKepuasanModal(id_kelompok, pertemuan_ke) {
            const modal = $('#kepuasanModal');
            const modalContent = modal.find('.modal-content');

            $('#kepuasanModalTitle').text(`Detail Kepuasan Konseli (Sesi Kelompok Ke-${pertemuan_ke})`);
            $('#kepuasanListContainer').html('<div class="text-center py-8 text-gray-500 text-lg"><i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat data kepuasan siswa...</div>');

            $.ajax({
                url: 'ajax_riwayat_kelompok.php', // Pastikan file ini ada dan berisi logika query
                method: 'GET',
                data: { action: 'get_kepuasan', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        let html = generateKepuasanTable(response.data);
                        $('#kepuasanListContainer').html(html);
                    } else {
                        $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-triangle mr-2"></i> Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-circle mr-2"></i> Terjadi kesalahan koneksi saat memuat data kepuasan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
            modalContent.addClass('scale-100');
        }

        // MODAL DETAIL LAPORAN (Diperlukan untuk menampilkan detail full)
        function openReportDetailModal(id_kelompok, pertemuan_ke) {
            const modal = $('#reportDetailModal');
            const modalContent = modal.find('.modal-content');
            $('#reportDetailModalTitle').text(`Detail Laporan Konseling Kelompok Ke-${pertemuan_ke}`);
            $('#reportContentContainer').html('<div class="text-center py-8 text-gray-500 text-lg"><i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat detail laporan...</div>');

            $.ajax({
                url: 'ajax_riwayat_kelompok.php', 
                method: 'GET',
                data: { action: 'get_report_full_detail', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const report = response.data.report;
                        const students = response.data.students;

                        // Detail Laporan
                        let reportHtml = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6 p-4 border border-primary-light primary-border-left bg-[#eef5f5] rounded-lg">
                                <p><strong>Tanggal Pelaksanaan:</strong> ${report.tanggal_pelaksanaan ? new Date(report.tanggal_pelaksanaan).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : '-'}</p>
                                <p><strong>Pertemuan Ke-:</strong> <span class="font-medium primary-color">${report.pertemuan_ke}</span></p>
                                <p><strong>Waktu & Durasi:</strong> ${report.waktu_durasi}</p>
                                <p><strong>Tempat:</strong> ${report.tempat}</p> 	
                                <p class="md:col-span-2"><strong>Teknik Pendekatan:</strong> ${report.catatan_khusus}</p>
                                <p class="md:col-span-2"><strong>Teknik Konseling:</strong> ${report.teknik_konseling}</p>
                            </div>
                            
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1 primary-color"><i class="fas fa-user-tag mr-1"></i> Topik / Masalah:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.topik || 'Tidak ada catatan Topik.'}</p>
                            </div>

                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1 primary-color"><i class="fas fa-bullseye mr-1"></i> Hasil yang Dicapai:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.hasil_layanan || 'Tidak ada catatan Hasil yang dicapai.'}</p>
                            </div>
                        `;

                        // Daftar Siswa
                        let studentHtml = '<h4 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2 primary-color"><i class="fas fa-users-viewfinder mr-2"></i> Siswa yang Terlibat:</h4>';
                        if (students.length > 0) {
                            studentHtml += '<ul class="list-disc pl-5 space-y-1 text-sm text-gray-700 mb-6">';
                            students.forEach((s) => {
                                studentHtml += `<li>${s.nama} <span class="text-xs text-gray-500">(${s.kelas} - ${s.jurusan})</span></li>`;
                            });
                            studentHtml += '</ul>';
                        } else {
                            studentHtml += '<p class="text-gray-500 mb-6">Tidak ada siswa yang terdaftar dalam sesi ini.</p>';
                        }
                        
                        $('#reportContentContainer').html(studentHtml + reportHtml );

                    } else {
                        $('#reportContentContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-triangle mr-2"></i> Error: ' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#reportContentContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-circle mr-2"></i> Terjadi kesalahan koneksi saat memuat detail laporan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
            modalContent.addClass('scale-100');
        }

        // Fungsi untuk menutup Modal (Global)
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const modalContent = modal.querySelector('.modal-content');

            modalContent.classList.remove('scale-100');
            
            // Tunggu transisi selesai sebelum menghilangkan visibilitas
            setTimeout(() => {
                modal.classList.remove('open');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }

        // Fungsi untuk membuka Modal PDF Viewer
        function openPdfViewerModal(pdfPath, title) {
            const modal = document.getElementById('pdfViewerModal');
            const modalContent = modal.querySelector('.modal-content');
            const iframe = document.getElementById('pdfIframe');
            
            document.getElementById('pdfIframeTitle').textContent = title;
            const fixedPath = '../' + pdfPath; 
            iframe.src = fixedPath;

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            modalContent.classList.add('scale-100');
        }

        // Fungsi untuk menutup Modal PDF Viewer
        function closePdfViewerModal() {
            closeModal('pdfViewerModal');
            document.getElementById('pdfIframe').src = ''; 
        }

        document.addEventListener('DOMContentLoaded', () => {
            const currentLimit = <?= $limit ?>;
            const urlParams = new URLSearchParams(window.location.search);
            
            // Logic Responsif Limit
            function determineLimit() {
                if (window.innerWidth < 640 && currentLimit !== limit_mobile) return limit_mobile;
                if (window.innerWidth >= 640 && currentLimit !== limit_desktop) return limit_desktop;
                return currentLimit;
            }
            
            const responsiveLimit = determineLimit();
            if (currentLimit !== responsiveLimit) {
                urlParams.set('limit', responsiveLimit);
                urlParams.set('page', 1);
                // Hanya redirect jika ada perubahan limit
                window.location.replace('?' + urlParams.toString());
            }

            // Fungsi Penutup Modal Global (Esc Key)
            document.addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    if (document.getElementById('kepuasanModal').classList.contains('open')) {
                        closeModal('kepuasanModal');
                    } else if (document.getElementById('pdfViewerModal').classList.contains('open')) {
                        closeModal('pdfViewerModal');
                    } else if (document.getElementById('reportDetailModal').classList.contains('open')) {
                        closeModal('reportDetailModal');
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
            <span class="text-xl font-bold primary-color hidden sm:inline">Riwayat Konseling Kelompok</span>
        </a>
        <a href="konselingkelompok.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium flex items-center transition duration-200 shadow-md">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 pt-20 md:pt-24 w-full"> 
        <div class="bg-white p-4 md:p-8 rounded-xl shadow-2xl border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b pb-4">
                <h2 class="text-3xl font-extrabold primary-color mb-2 md:mb-0">
                    <i class="fas fa-users mr-2"></i> Daftar Laporan Konseling Kelompok
                </h2>
                <div class="text-sm font-medium text-gray-600">
                    Konselor Aktif: <span class="primary-color font-semibold"><?= $nama_konselor ?></span>
                </div>
            </div>

            <form method="GET" class="mb-6 p-4 border border-gray-300 rounded-lg bg-gray-50 shadow-inner">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari (Siswa / Guru / Topik)</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Masukkan nama siswa, guru, atau topik..." class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
                    </div>
                    
                    <div>
                        <label for="guru" class="block text-sm font-medium text-gray-700 mb-1">Guru BK Pelaksana</label>
                        <select name="guru" id="guru" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
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
                        <label for="tgl_start" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal Pelaksanaan</label>
                        <input type="date" name="tgl_start" id="tgl_start" value="<?= htmlspecialchars($filter_tgl_start) ?>" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
                    </div>
                </div>

                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-150 flex items-center shadow-md text-sm font-medium">
                        <i class="fas fa-magnifying-glass mr-2"></i> Terapkan Filter
                    </button>
                    <a href="riwayat_kelompok.php" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-150 flex items-center shadow-md text-sm font-medium">
                        <i class="fas fa-redo mr-2"></i> Reset Filter
                    </a>
                </div>
            </form>

            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                 <i class="fas fa-history mr-2 primary-color"></i> Riwayat Konseling (Total: <?= $total_riwayat ?> Sesi)
            </h3>
            
            <div class="overflow-x-auto table-container shadow-xl rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 data-table-report">
                    <thead class="primary-bg text-white">
                        <tr>
                            <th class="sticky-col px-3 py-3 text-left text-xs font-bold uppercase tracking-wider w-[50px] border-r border-gray-700">No.</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[120px]">Tanggal</th>
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[80px] border-r border-gray-700">Pert. Ke-</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[150px] hide-on-mobile">Waktu & Tempat</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[200px]">Guru BK Pelaksana</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[300px]">Topik / Masalah</th>
                            <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[350px] hide-on-mobile">Hasil yang Dicapai</th>
                            
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[120px]">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($riwayat_count > 0): ?>
                            <?php $no = $start_number; while ($data = $result_riwayat->fetch_assoc()): ?>
                                <?php
                                    $tanggal_indo = tgl_indo($data['tanggal_pelaksanaan']);
                                ?>
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-yellow-50 transition duration-150">
                                    <td class="sticky-col px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 w-[50px]"><?= $no++ ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r border-gray-200 w-[120px]"><?= $tanggal_indo ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center font-bold primary-color border-r border-gray-200 w-[80px]"><?= htmlspecialchars($data['pertemuan_ke']) ?></td>
                                    
                                    <td class="px-3 py-3 text-sm text-gray-600 whitespace-normal border-r border-gray-200 w-[150px] hide-on-mobile">
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($data['waktu_durasi']) ?></div>
                                        <span class="text-xs text-gray-500 italic"><?= htmlspecialchars($data['tempat']) ?></span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700 whitespace-normal font-semibold border-r border-gray-200 w-[200px]"><?= htmlspecialchars($data['nama_guru']) ?></td>
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[300px]">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs font-medium primary-color"><?= htmlspecialchars($data['topik']) ?></div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[350px] hide-on-mobile">
                                        <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs"><?= htmlspecialchars($data['hasil_layanan']) ?></div>
                                    </td>
                                    
                                    <td class="px-3 py-3 text-center text-sm font-medium w-[120px]">
                                        <div class="flex flex-col space-y-2">
                                            <button 
                                                onclick="openReportDetailModal('<?= htmlspecialchars($data['id_kelompok']) ?>', '<?= htmlspecialchars($data['pertemuan_ke']) ?>')" 
                                                class="w-full text-white px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 transition duration-200 text-xs font-semibold shadow-md">
                                                <i class="fas fa-eye mr-1"></i> Detail Laporan
                                            </button>
                                            
                                            <button 
                                                onclick="openKepuasanModal('<?= htmlspecialchars($data['id_kelompok']) ?>', '<?= htmlspecialchars($data['pertemuan_ke']) ?>')" 
                                                class="w-full text-white px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 transition duration-200 text-xs font-semibold shadow-md">
                                                <i class="fas fa-star mr-1"></i> Kepuasan Siswa
                                            </button>
                                            
                                            <?php if ($data['file_pdf']): ?>
                                                <button 
                                                    onclick="openPdfViewerModal('<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Kelompok Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>')" 
                                                    class="w-full bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition duration-200 text-xs font-semibold shadow-md">
                                                    <i class="fas fa-file-pdf mr-1"></i> Lihat PDF
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
                                <td colspan="8" class="px-6 py-10 text-center text-lg font-medium text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i><br>
                                    Tidak ada laporan konseling kelompok yang ditemukan.
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

    <div id="reportDetailModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col transform modal-content max-h-[90vh] border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="reportDetailModalTitle" class="text-xl font-bold primary-color flex items-center"><i class="fas fa-file-lines mr-2"></i> Detail Laporan Kelompok</h3>
                <button onclick="closeModal('reportDetailModal')" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="reportContentContainer" class="p-6 space-y-4 overflow-y-auto">
                </div>
            <div class="px-6 py-3 border-t flex justify-end sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closeModal('reportDetailModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>


    <div id="kepuasanModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl flex flex-col transform modal-content max-h-[95vh] border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="kepuasanModalTitle" class="text-xl font-bold text-green-700 flex items-center"><i class="fas fa-face-smile mr-2"></i> Rekap Kepuasan Konseli Kelompok</h3>
                <button onclick="closeModal('kepuasanModal')" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-6 overflow-y-auto">
                 <h4 class="text-xl font-extrabold text-center text-gray-800 border-b pb-3">DAFTAR KEPENILAIAN KONSELI PER SESI</h4>
                 <div id="kepuasanListContainer" class="p-0">
                    </div>
            </div>
            <div class="px-6 py-3 border-t flex justify-end sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closeModal('kepuasanModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>

    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl max-h-[95vh] flex flex-col transform modal-content border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="pdfIframeTitle" class="text-xl font-bold text-red-700 flex items-center"><i class="fas fa-file-pdf mr-2"></i> Laporan Konseling Kelompok</h3>
                <button onclick="closePdfViewerModal()" class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[75vh] border-0" title="PDF Viewer"></iframe>
            </div>
            <div class="px-6 py-3 border-t flex justify-end space-x-3 sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closePdfViewerModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>

</body>
</html>
