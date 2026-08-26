<?php
session_start();
include '../koneksi.php';
if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}
if (isset($_GET['hapus'])) {

    $id_kelompok = intval($_GET['hapus']);

    // --- HAPUS FILE DOKUMENTASI FISIK DAN DATA DARI DATABASE ---
    $stmt_docs = $koneksi->prepare("SELECT file_path FROM dokumentasi_kelompok WHERE id_kelompok = ?");
    $stmt_docs->bind_param("i", $id_kelompok);
    $stmt_docs->execute();
    $res_docs = $stmt_docs->get_result();

    while($doc = $res_docs->fetch_assoc()) {
        $file_path = dirname(dirname(__FILE__)) . '/' . str_replace('../', '', $doc['file_path']);
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $stmt_del_docs = $koneksi->prepare("DELETE FROM dokumentasi_kelompok WHERE id_kelompok = ?");
    $stmt_del_docs->bind_param("i", $id_kelompok);
    $stmt_del_docs->execute();
    // -----------------------------------------------------------

    $stmt = $koneksi->prepare(
        "DELETE FROM detail_kelompok WHERE id_kelompok=?"
    );
    $stmt->bind_param("i", $id_kelompok);
    $stmt->execute();

    $stmt = $koneksi->prepare(
        "DELETE FROM riwayat_kelompok WHERE id_kelompok=?"
    );
    $stmt->bind_param("i", $id_kelompok);
    $stmt->execute();

    $stmt = $koneksi->prepare(
        "DELETE FROM kelompok WHERE id_kelompok=?"
    );
    $stmt->bind_param("i", $id_kelompok);
    $stmt->execute();


    header("Location: riwayat_kelompok.php");
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
    // Pencarian gabungan: Siswa (nama/NIS) ATAU Guru (nama_guru) ATAU Topik
    // (teknik_pendekatan/catatan_khusus, teknik_konseling, gejala/hasil_layanan)
    $where_clauses[] = " (
        k.id_kelompok IN (
            SELECT dk.id_kelompok
            FROM detail_kelompok dk
            JOIN siswa s ON dk.id_siswa = s.id_siswa
            WHERE s.nama LIKE ? OR s.nis LIKE ?
        )
        OR k.nama_guru LIKE ?
        OR k.catatan_khusus LIKE ?
        OR k.teknik_konseling LIKE ?
        OR k.hasil_layanan LIKE ?
        OR k.gejala LIKE ?
        OR k.hasil_dicapai LIKE ?
    ) ";

    $bind_params .= 'ssssssss';
    $search_term = "%$filter_search%";
    $bind_values[] = $search_term; // siswa.nama
    $bind_values[] = $search_term; // siswa.nis
    $bind_values[] = $search_term; // nama_guru
    $bind_values[] = $search_term; // catatan_khusus (teknik pendekatan)
    $bind_values[] = $search_term; // teknik_konseling
    $bind_values[] = $search_term; // hasil_layanan (kolom lama)
    $bind_values[] = $search_term; // gejala
    $bind_values[] = $search_term; // hasil_dicapai
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$query_count = "
    SELECT COUNT(k.id_kelompok) as total_count
    FROM kelompok k
    INNER JOIN riwayat_kelompok rk ON k.id_kelompok = rk.id_kelompok
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
    INNER JOIN 
        riwayat_kelompok rk ON k.id_kelompok = rk.id_kelompok
    " . $where_sql . "
    ORDER BY 
        k.tanggal_pelaksanaan ASC, k.created_at ASC
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
$start_number = $offset + 1; 

$query_gurus = "SELECT DISTINCT nama_guru FROM kelompok ORDER BY nama_guru ASC";
$result_gurus = $koneksi->query($query_gurus);

// ===== AJAX ENDPOINT UNTUK EDIT =====
if (isset($_GET['action']) && $_GET['action'] === 'get_edit_data') {
    header('Content-Type: application/json');
    $id_kelompok = (int)$_GET['id_kelompok'];

    $stmt = $koneksi->prepare("SELECT k.*, rk.file_pdf FROM kelompok k LEFT JOIN riwayat_kelompok rk ON k.id_kelompok = rk.id_kelompok WHERE k.id_kelompok = ?");
    $stmt->bind_param("i", $id_kelompok);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if (!$report) {
        echo json_encode(["status" => "error", "message" => "Data tidak ditemukan"]);
        exit;
    }

    // Siswa
    $stmt_s = $koneksi->prepare("SELECT s.id_siswa, s.nama, s.kelas, s.jurusan FROM detail_kelompok dk JOIN siswa s ON dk.id_siswa = s.id_siswa WHERE dk.id_kelompok = ? ORDER BY s.kelas, s.nama");
    $stmt_s->bind_param("i", $id_kelompok);
    $stmt_s->execute();
    $res_s = $stmt_s->get_result();
    $students = [];
    $student_ids = [];
    while ($row = $res_s->fetch_assoc()) {
        $students[] = $row;
        $student_ids[] = $row['id_siswa'];
    }

    // Dokumentasi
    $stmt_d = $koneksi->prepare("SELECT file_path FROM dokumentasi_kelompok WHERE id_kelompok = ?");
    $stmt_d->bind_param("i", $id_kelompok);
    $stmt_d->execute();
    $res_d = $stmt_d->get_result();
    $docs = [];
    while ($row = $res_d->fetch_assoc()) {
        $docs[] = ['file_path' => $row['file_path']];
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "report" => $report,
            "students" => $students,
            "student_ids" => $student_ids,
            "docs" => $docs
        ]
    ]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_report_full_detail') {
    header('Content-Type: application/json');
    $id_kelompok = (int)$_GET['id_kelompok'];
    $query = "SELECT * FROM kelompok WHERE id_kelompok = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_kelompok);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    $query_siswa = "SELECT s.nama, s.kelas, s.jurusan 
                FROM detail_kelompok dk
                JOIN siswa s ON dk.id_siswa = s.id_siswa 
                WHERE dk.id_kelompok = ?";
    $stmt_s = $koneksi->prepare($query_siswa);
    $stmt_s->bind_param("i", $id_kelompok);
    $stmt_s->execute();
    $res_siswa = $stmt_s->get_result();
    
    $students = [];
    while($row = $res_siswa->fetch_assoc()) { 
        $students[] = $row; 
    }

    echo json_encode([
    "status" => "success",
    "data" => [
        "report" => $report,
        "students" => $students,
        "pdf_url" => $report['file_pdf'] ?? null
    ]
]);

    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'fetch_detail') {
    $id = (int)$_GET['id'];

    $query = "
    SELECT 
        k.*,
        rk.file_pdf
    FROM kelompok k
    LEFT JOIN riwayat_kelompok rk 
        ON k.id_kelompok = rk.id_kelompok
    WHERE k.id_kelompok = ?
";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $query_siswa = "SELECT s.nama, s.kelas, s.jurusan 
                FROM detail_kelompok dk
                JOIN siswa s ON dk.id_siswa = s.id_siswa 
                WHERE dk.id_kelompok = ?";

    $stmt2 = $koneksi->prepare($query_siswa);
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $res_siswa = $stmt2->get_result();

    $siswa_list = [];
    while ($row = $res_siswa->fetch_assoc()) {
        $siswa_list[] = $row['nama'];
    }

    $data['siswa_terlibat'] = implode(', ', $siswa_list);

    echo json_encode($data);
    exit;
}

$waktu_durasi_options = [30, 45, 60, 90];
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
        :root {
            --primary-color: #2F6C6E;
            --primary-dark: #1E4647;
            --primary-light: #5FA8A1;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .primary-bg {
            background-color: var(--primary-color);
        }

        .primary-color {
            color: var(--primary-color);
        }

        .primary-border-left {
            border-left-color: var(--primary-light);
        }

        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 10;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
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

        .status-sm {
            color: #16A34A;
            font-weight: 600;
        }

        .status-m {
            color: #3B82F6;
            font-weight: 500;
        }

        .status-km {
            color: #F59E0B;
            font-weight: 500;
        }

        .status-na {
            color: #9CA3AF;
            font-weight: 400;
        }

        @media (max-width: 768px) {
            .hide-on-mobile {
                display: none !important;
            }
        }

        .doc-preview-item {
            position: relative;
            display: inline-block;
            margin: 4px;
        }
        .doc-preview-item img {
            width: 100px;
            height: 75px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
        }
        .doc-preview-item .btn-remove-doc {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .doc-preview-item.marked-delete {
            opacity: 0.4;
        }
        .doc-preview-item.marked-delete::after {
            content: 'HAPUS';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #dc2626;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
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

            let tableHtml = `
                <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-inner">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase w-[20%] border-r">Nama Siswa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase w-[15%] border-r">Kelas/Jurusan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold border-r">Penerimaan</th>
    <th class="px-4 py-3 text-center text-xs font-bold border-r">Kemudahan Curhat</th>
    <th class="px-4 py-3 text-center text-xs font-bold border-r">Kepercayaan</th>
    <th class="px-4 py-3 text-center text-xs font-bold border-r">Pemecahan Masalah</th>

    <th class="px-4 py-3 text-center text-xs font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
            `;
            data.sort((a, b) => a.nama.localeCompare(b.nama));

            data.forEach(item => {

                const penerimaan = getRatingStatus(item.aspek_penerimaan);
                const curhat = getRatingStatus(item.aspek_kemudahan_curhat);
                const kepercayaan = getRatingStatus(item.aspek_kepercayaan);
                const pemecahan = getRatingStatus(item.aspek_pemecahan_masalah);

                const overallStatus = parseInt(item.aspek_penerimaan) > 0 ?
                    `<span class="text-green-600 font-semibold text-xs">Sudah diisi ${item.tanggal_isi ? new Date(item.tanggal_isi).toLocaleDateString('id-ID') : ''}</span>` :
                    '<span class="text-red-600 font-semibold text-xs">Belum Diisi</span>';

                tableHtml += `
                    <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 border-r font-medium">${item.nama}</td>
            <td class="px-4 py-3 border-r text-xs">${item.kelas} - ${item.jurusan}</td>

            <td class="px-4 py-3 text-center border-r"><span class="${penerimaan.class}">${penerimaan.text}</span></td>
            <td class="px-4 py-3 text-center border-r"><span class="${curhat.class}">${curhat.text}</span></td>
            <td class="px-4 py-3 text-center border-r"><span class="${kepercayaan.class}">${kepercayaan.text}</span></td>
            <td class="px-4 py-3 text-center border-r"><span class="${pemecahan.class}">${pemecahan.text}</span></td>

            <td class="px-4 py-3 text-center">${overallStatus}</td>
        </tr>
                `;
            });
            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            return tableHtml;
        }


        function openKepuasanModal(id_kelompok, pertemuan_ke) {
            const modal = $('#kepuasanModal');
            const modalContent = modal.find('.modal-content');

            $('#kepuasanModalTitle').text(`Detail Kepuasan Konseli (Sesi Kelompok Ke-${pertemuan_ke})`);
            $('#kepuasanListContainer').html('<div class="text-center py-8 text-gray-500 text-lg"><i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat data kepuasan siswa...</div>');

            $.ajax({
                url: 'ajax_riwayat_kelompok.php',
                method: 'GET',
                data: { action: 'get_kepuasan', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        let html = generateKepuasanTable(response.data);
                        $('#kepuasanListContainer').html(html);
                    } else {
                        $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-triangle mr-2"></i> Error: ' + response.message + '</div>');
                    }
                },
                error: function () {
                    $('#kepuasanListContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-circle mr-2"></i> Terjadi kesalahan koneksi saat memuat data kepuasan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
            modalContent.addClass('scale-100');
        }

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
                success: function (response) {
                    if (response.status === 'success') {
                        const report = response.data.report;
                        const students = response.data.students;

                        let reportHtml = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6 p-4 border border-primary-light primary-border-left bg-[#eef5f5] rounded-lg">
                                <p><strong>Tanggal Pelaksanaan:</strong> ${report.tanggal_pelaksanaan ? new Date(report.tanggal_pelaksanaan).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}</p>
                                <p><strong>Pertemuan Ke-:</strong> <span class="font-medium primary-color">${report.pertemuan_ke}</span></p>
                                <p><strong>Waktu & Durasi:</strong> ${report.waktu_durasi}</p>
                                <p><strong>Tempat:</strong> ${report.tempat}</p> 	
                                <p class="md:col-span-2"><strong>Bidang Layanan:</strong> ${(report.bidang_layanan || '').split(',').filter(Boolean).join(', ') || '-'}</p>
                                <p class="md:col-span-2"><strong>Teknik Pendekatan:</strong> ${report.catatan_khusus}</p>
                                <p class="md:col-span-2"><strong>Teknik Konseling:</strong> ${report.teknik_konseling}</p>
                            </div>
                            
                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1 primary-color"><i class="fas fa-user-tag mr-1"></i> Topik / Masalah:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.topik || 'Tidak ada catatan Topik.'}</p>
                            </div>

                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1 primary-color"><i class="fas fa-eye mr-1"></i> Gejala yang Nampak:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.gejala || 'Tidak ada catatan Gejala yang Nampak.'}</p>
                            </div>

                            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                                <h4 class="font-semibold text-gray-700 mb-2 border-b pb-1 primary-color"><i class="fas fa-bullseye mr-1"></i> Hasil yang Dicapai:</h4>
                                <p class="whitespace-pre-wrap text-sm text-gray-600">${report.hasil_dicapai || report.hasil_layanan || 'Tidak ada catatan Hasil yang dicapai.'}</p>
                            </div>
                        `;

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

                        $('#reportContentContainer').html(studentHtml + reportHtml);

                    } else {
                        $('#reportContentContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-triangle mr-2"></i> Error: ' + response.message + '</div>');
                    }
                },
                error: function () {
                    $('#reportContentContainer').html('<div class="text-center py-8 text-red-600 text-lg"><i class="fas fa-exclamation-circle mr-2"></i> Terjadi kesalahan koneksi saat memuat detail laporan.</div>');
                }
            });

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
            modalContent.addClass('scale-100');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
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
            iframe.src = pdfPath;
            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
            if (modalContent) {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }
        }

        function closePdfViewerModal() {
            closeModal('pdfViewerModal');
            document.getElementById('pdfIframe').src = '';
        }

        // ===== EDIT MODAL =====
        let deletedDocs = [];

        function openEditModal(id_kelompok) {
            const modal = document.getElementById('editKelompokModal');
            document.getElementById('editModalTitle').textContent = 'Edit Laporan Konseling Kelompok';
            document.getElementById('editExistingDocs').innerHTML = '<p class="text-sm text-gray-500 italic">Memuat data...</p>';
            deletedDocs = [];
            document.getElementById('edit_deleted_docs').value = '[]';

            $.ajax({
                url: 'riwayat_kelompok.php',
                method: 'GET',
                data: { action: 'get_edit_data', id_kelompok: id_kelompok },
                dataType: 'json',
                success: function (res) {
                    if (res.status !== 'success') {
                        alert('Gagal memuat data: ' + (res.message || 'Unknown error'));
                        return;
                    }
                    const r = res.data.report;
                    const students = res.data.students;
                    const student_ids = res.data.student_ids;
                    const docs = res.data.docs || [];

                    document.getElementById('edit_id_kelompok').value = r.id_kelompok;
                    document.getElementById('edit_selected_student_ids').value = student_ids.join(',');

                    // Tampilkan daftar siswa
                    let siswaHtml = '';
                    if (students.length > 0) {
                        siswaHtml = students.map(s => 
                            `<span class="inline-block bg-blue-200 text-blue-800 text-xs px-3 py-1 rounded-full m-1">${s.nama} (${s.kelas})</span>`
                        ).join('');
                    } else {
                        siswaHtml = '<span class="text-xs text-gray-500">Tidak ada siswa.</span>';
                    }
                    document.getElementById('editSelectedStudentsInModal').innerHTML = siswaHtml;

                    document.getElementById('edit_tanggal_pelaksanaan').value = r.tanggal_pelaksanaan || '';
                    document.getElementById('edit_waktu_durasi').value = r.waktu_durasi || '';
                    document.getElementById('edit_pertemuan_ke').value = r.pertemuan_ke || '';
                    document.getElementById('edit_catatan_khusus').value = r.catatan_khusus || '';
                    document.getElementById('edit_teknik_konseling').value = r.teknik_konseling || '';
                    document.getElementById('edit_gejala').value = r.gejala || '';
                    document.getElementById('edit_hasil_layanan').value = r.hasil_dicapai || r.hasil_layanan || '';
                    document.getElementById('edit_guru_pembimbing').value = r.nama_guru || '';
                    document.getElementById('edit_tempat').value = r.tempat || 'Ruang BK';

                    // Set Bidang Layanan checkboxes
                    let selectedBidang = (r.bidang_layanan || '').split(',').map(s => s.trim()).filter(Boolean);
                    $('.edit-bidang-layanan-cb').each(function () {
                        $(this).prop('checked', selectedBidang.includes(this.value));
                    });
                    document.getElementById('edit_bidang_layanan').value = selectedBidang.join(',');
                    $('#edit_bidang_layanan_error').addClass('hidden');

                    // Dokumentasi lama
                    const container = document.getElementById('editExistingDocs');
                    container.innerHTML = '';
                    if (docs.length > 0) {
                        docs.forEach((doc, idx) => {
                            const div = document.createElement('div');
                            div.className = 'doc-preview-item';
                            div.dataset.path = doc.file_path;
                            // path di DB biasanya "../uploads/..." atau "uploads/..."
                            let src = doc.file_path;
                            if (src.startsWith('../')) src = src; // biarkan relatif
                            div.innerHTML = `
                                <img src="${src}" alt="Doc ${idx+1}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2275%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%2275%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%239ca3af%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2210%22%3ENo Preview%3C/text%3E%3C/svg%3E'">
                                <span class="btn-remove-doc" onclick="toggleDeleteDoc(this)" title="Hapus foto ini"><i class="fas fa-times"></i></span>
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<p class="text-sm text-gray-500 italic">Tidak ada dokumentasi sebelumnya.</p>';
                    }

                    document.getElementById('edit_dokumentasi').value = '';

                    modal.classList.add('open');
                    document.body.classList.add('overflow-hidden');
                },
                error: function () {
                    alert('Gagal memuat data laporan. Coba lagi.');
                }
            });
        }

        function toggleDeleteDoc(btn) {
            const item = btn.closest('.doc-preview-item');
            const path = item.dataset.path;
            if (item.classList.contains('marked-delete')) {
                item.classList.remove('marked-delete');
                deletedDocs = deletedDocs.filter(p => p !== path);
            } else {
                item.classList.add('marked-delete');
                if (!deletedDocs.includes(path)) deletedDocs.push(path);
            }
            document.getElementById('edit_deleted_docs').value = JSON.stringify(deletedDocs);
        }

        function closeEditModal() {
            const modal = document.getElementById('editKelompokModal');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('editKelompokForm').reset();
            deletedDocs = [];
        }

        $(document).ready(function () {
            $("#editSubmitBtn").click(function (e) {
                e.preventDefault();

                let fileInput = document.getElementById('edit_dokumentasi');
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

                // --- VALIDASI & SUSUN BIDANG LAYANAN ---
                let editBidangChecked = $('.edit-bidang-layanan-cb:checked').map(function () { return this.value; }).get();
                if (editBidangChecked.length < 1) {
                    $('#edit_bidang_layanan_error').removeClass('hidden');
                    document.getElementById('edit_bidang_layanan_group').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                $('#edit_bidang_layanan_error').addClass('hidden');
                document.getElementById('edit_bidang_layanan').value = editBidangChecked.join(',');
                // ---------------------------------

                let form = document.getElementById("editKelompokForm");
                let formData = new FormData(form);
                formData.set('deleted_docs', document.getElementById('edit_deleted_docs').value || '[]');

                const submitButton = document.getElementById('editSubmitBtn');
                const originalText = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan Perubahan...';

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
                        closeEditModal();

                        if (res.status === "success") {
                            alert("Laporan konseling kelompok berhasil diperbarui.");
                            window.location.reload();
                        } else {
                            alert("Gagal memperbarui laporan: " + (res.message || "Terjadi kesalahan."));
                        }
                    },

                    error: function (xhr) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;

                        let errorMessage = "Gagal memperbarui laporan konseling kelompok.";
                        try {
                            const errorJson = JSON.parse(xhr.responseText);
                            if (errorJson && errorJson.message) {
                                errorMessage = "Gagal memperbarui: " + errorJson.message;
                            }
                        } catch (e) {}
                        alert(errorMessage);
                    }
                });
            });
        });

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
                    if (document.getElementById('kepuasanModal')?.classList.contains('open')) {
                        closeModal('kepuasanModal');
                    } else if (document.getElementById('pdfViewerModal')?.classList.contains('open')) {
                        closePdfViewerModal();
                    } else if (document.getElementById('editKelompokModal')?.classList.contains('open')) {
                        closeEditModal();
                    } else if (document.getElementById('reportDetailModal')?.classList.contains('open')) {
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
        <a href="konselingkelompok.php"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium flex items-center transition duration-200 shadow-md">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 pt-20 md:pt-24 w-full">
        <div class="bg-white p-4 md:p-8 rounded-xl shadow-2xl border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b pb-4">
                <h2 class="text-3xl font-extrabold primary-color mb-2 md:mb-0">
                    <i class="fas fa-users mr-2"></i> Daftar Laporan Konseling Kelompok
                </h2>
            </div>

            <form method="GET" class="mb-6 p-4 border border-gray-300 rounded-lg bg-gray-50 shadow-inner">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari (Siswa / Guru /
                            Topik)</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filter_search) ?>"
                            placeholder="Masukkan nama siswa, guru, atau topik..."
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
                    </div>

                    <div>
                        <label for="guru" class="block text-sm font-medium text-gray-700 mb-1">Guru BK Pelaksana</label>
                        <select name="guru" id="guru"
                            class="w-full p-[11px] border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">

                            <option value="">Semua Guru</option>

                            <option value="Pahrurazi, S.Pd" <?=$filter_guru=="Pahrurazi, S.Pd" ? 'selected' : '' ?>>
                                Pahrurazi, S.Pd
                            </option>

                            <option value="Dian Riyani, S.Pd" <?=$filter_guru=="Dian Riyani, S.Pd" ? 'selected' : '' ?>>
                                Dian Riyani, S.Pd
                            </option>

                            <option value="Putri Hidayatie, S.Pd" <?=$filter_guru=="Putri Hidayatie, S.Pd" ? 'selected'
                                : '' ?>>
                                Putri Hidayatie, S.Pd
                            </option>

                            <option value="Rini Rodhiati, S.Pd" <?=$filter_guru=="Rini Rodhiati, S.Pd" ? 'selected' : ''
                                ?>>
                                Rini Rodhiati, S.Pd
                            </option>

                            <option value="Gusti Muhammad Fajri Ramadhan, S.Pd"
                                <?=$filter_guru=="Gusti Muhammad Fajri Ramadhan, S.Pd" ? 'selected' : '' ?>>
                                Gusti Muhammad Fajri Ramadhan, S.Pd
                            </option>

                            <option value="Desy Arianti, S.Pd" <?=$filter_guru=="Desy Arianti, S.Pd" ? 'selected' : ''
                                ?>>
                                Desy Arianti, S.Pd
                            </option>

                            <option value="Khalisatun Ni'mah, S.Pd" <?=$filter_guru=="Khalisatun Ni'mah, S.Pd"
                                ? 'selected' : '' ?>>
                                Khalisatun Ni'mah, S.Pd
                            </option>

                            <option value="Tiara Wulansari, S.Pd" <?=$filter_guru=="Tiara Wulansari, S.Pd" ? 'selected'
                                : '' ?>>
                                Tiara Wulansari, S.Pd
                            </option>

                            <option value="Dhea Nur Aziza, S.Pd" <?=$filter_guru=="Dhea Nur Aziza, S.Pd" ? 'selected'
                                : '' ?>>
                                Dhea Nur Aziza, S.Pd
                            </option>

                            <option value="Abdul Basith, S.Pd" <?=$filter_guru=="Abdul Basith, S.Pd" ? 'selected' : ''
                                ?>>
                                Abdul Basith, S.Pd
                            </option>

                        </select>
                    </div>

                    <div>
                        <label for="tgl_start" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal
                            Pelaksanaan</label>
                        <input type="date" name="tgl_start" id="tgl_start"
                            value="<?= htmlspecialchars($filter_tgl_start) ?>"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-primary-color focus:border-primary-color">
                    </div>
                </div>

                <div class="flex space-x-3 mt-4">
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-150 flex items-center shadow-md text-sm font-medium">
                        <i class="fas fa-magnifying-glass mr-2"></i> Terapkan Filter
                    </button>
                    <a href="riwayat_kelompok.php"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-150 flex items-center shadow-md text-sm font-medium">
                        <i class="fas fa-redo mr-2"></i> Reset Filter
                    </a>
                </div>
            </form>

            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history mr-2 primary-color"></i> Riwayat Konseling (Total:
                <?= $total_riwayat ?> Sesi)
            </h3>

            <div class="overflow-x-auto table-container shadow-xl rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 data-table-report">
                    <thead class="primary-bg text-white">
                        <tr>
                            <th
                                class="sticky-col px-3 py-3 text-left text-xs font-bold uppercase tracking-wider w-[50px] border-r border-gray-700">
                                No.</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[120px]">
                                Tanggal</th>
                            <th
                                class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[80px] border-r border-gray-700">
                                Pert. Ke-</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[150px] hide-on-mobile">
                                Tempat</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[200px]">
                                Guru BK Pelaksana</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[150px] hide-on-mobile">
                                Bidang Layanan</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[280px] hide-on-mobile">
                                Gejala yang Nampak</th>
                            <th
                                class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-gray-700 w-[280px] hide-on-mobile">
                                Hasil yang Dicapai</th>

                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[140px]">Aksi
                                / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($riwayat_count > 0): ?>
                        <?php $no = $start_number; while ($data = $result_riwayat->fetch_assoc()): ?>
                        <?php
                                    $tanggal_indo = tgl_indo($data['tanggal_pelaksanaan']);
                                ?>
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-yellow-50 transition duration-150">
                            <td
                                class="sticky-col px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 w-[50px]">
                                <?= $no++ ?>
                            </td>
                            <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r border-gray-200 w-[120px]">
                                <?= $tanggal_indo ?>
                            </td>
                            <td
                                class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center font-bold primary-color border-r border-gray-200 w-[80px]">
                                <?= htmlspecialchars($data['pertemuan_ke']) ?>
                            </td>

                            <td
                                class="px-3 py-3 text-sm text-gray-600 whitespace-normal border-r border-gray-200 w-[150px] hide-on-mobile">
                                <div class="font-medium text-gray-800">
                                    <?= htmlspecialchars($data['waktu_durasi']) ?>
                                </div>
                                <span class="text-xs text-gray-500 italic">
                                    <?= htmlspecialchars($data['tempat']) ?>
                                </span>
                            </td>
                            <td
                                class="px-3 py-3 text-sm text-gray-700 whitespace-normal font-semibold border-r border-gray-200 w-[200px]">
                                <?= htmlspecialchars($data['nama_guru']) ?>
                            </td>
                            <td
                                class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[150px] hide-on-mobile">
                                <div class="flex flex-wrap gap-1">
                                    <?php
                                    $bidang_list = !empty($data['bidang_layanan']) ? array_filter(array_map('trim', explode(',', $data['bidang_layanan']))) : [];
                                    if (count($bidang_list) > 0):
                                        foreach ($bidang_list as $bidang):
                                    ?>
                                        <span class="inline-block bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-semibold"><?= htmlspecialchars($bidang) ?></span>
                                    <?php
                                        endforeach;
                                    else:
                                        echo '<span class="text-gray-400 italic">-</span>';
                                    endif;
                                    ?>
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[280px] hide-on-mobile">
                                <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs">
                                    <?= nl2br(htmlspecialchars($data['gejala'] ?? '')) ?>
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 text-sm text-gray-600 border-r border-gray-200 w-[280px] hide-on-mobile">
                                <div class="max-h-[80px] overflow-y-auto p-0.5 text-xs">
                                    <?= nl2br(htmlspecialchars($data['hasil_dicapai'] ?? $data['hasil_layanan'] ?? '')) ?>
                                </div>
                            </td>

                            <td class="px-3 py-3 text-center text-sm font-medium w-[140px]">
                                <div class="flex flex-col space-y-2">

                                    <button
                                        onclick="openKepuasanModal('<?= htmlspecialchars($data['id_kelompok']) ?>', '<?= htmlspecialchars($data['pertemuan_ke']) ?>')"
                                        class="w-full text-white px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 transition duration-200 text-xs font-semibold shadow-md">
                                        <i class="fas fa-star mr-1"></i> Kepuasan Siswa
                                    </button>

                                    <?php if ($data['file_pdf']): ?>
                                    <button
                                        onclick="openPdfViewerModal('../<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Kelompok Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke'], ENT_QUOTES) ?>')"
                                        class="w-full bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition duration-200 text-xs font-semibold shadow-md">
                                        <i class="fas fa-file-pdf mr-1"></i> Lihat PDF
                                    </button>

                                    <button
                                        onclick="openEditModal(<?= (int)$data['id_kelompok'] ?>)"
                                        class="w-full bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition duration-200 text-xs font-semibold shadow-md">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </button>

                                    <a href="?hapus=<?= $data['id_kelompok'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus riwayat konseling ini?')"
                                        class="w-full bg-red-700 text-white px-3 py-1.5 rounded-lg hover:bg-red-800 transition duration-200 text-xs font-semibold shadow-md">

                                        <i class="fas fa-trash mr-1"></i>
                                        Hapus

                                    </a>

                                    <?php else: ?>
                                    <span
                                        class="w-full block text-gray-500 text-xs px-3 py-1.5 border border-gray-300 bg-gray-100 rounded-lg">Laporan
                                        Belum Ada</span>
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
                    Menampilkan <span class="font-semibold">
                        <?= $riwayat_count ?>
                    </span> dari <span class="font-semibold">
                        <?= $total_riwayat ?>
                    </span> sesi. (Halaman
                    <?= $page ?> dari
                    <?= $total_pages ?>)
                </div>

                <nav class="flex items-center space-x-2" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?= getPaginationUrl($page - 1, $limit) ?>"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                        <i class="fas fa-chevron-left mr-2"></i> Sebelumnya
                    </a>
                    <?php else: ?>
                    <span
                        class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                        <i class="fas fa-chevron-left mr-2"></i> Sebelumnya
                    </span>
                    <?php endif; ?>

                    <span
                        class="px-4 py-2 text-sm font-extrabold text-white primary-bg border border-primary-dark rounded-lg shadow-md">
                        <?= $page ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                    <a href="<?= getPaginationUrl($page + 1, $limit) ?>"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition duration-150 flex items-center shadow-sm">
                        Berikutnya <i class="fas fa-chevron-right ml-2"></i>
                    </a>
                    <?php else: ?>
                    <span
                        class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center shadow-sm">
                        Berikutnya <i class="fas fa-chevron-right ml-2"></i>
                    </span>
                    <?php endif; ?>
                </nav>
            </div>

        </div>
    </main>

    <footer class="no-print text-center py-4 bg-white border-t border-gray-200 text-gray-600 text-xs mt-auto">
        &copy; 2025 Bimbingan dan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>

    <!-- Modal Report Detail -->
    <div id="reportDetailModal"
        class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col transform modal-content max-h-[90vh] border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="reportDetailModalTitle" class="text-xl font-bold primary-color flex items-center"><i
                        class="fas fa-file-lines mr-2"></i> Detail Laporan Kelompok</h3>
                <button onclick="closeModal('reportDetailModal')"
                    class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="reportContentContainer" class="p-6 space-y-4 overflow-y-auto">
            </div>
            <div class="px-6 py-3 border-t flex justify-end sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closeModal('reportDetailModal')"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i
                        class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>


    <div id="kepuasanModal"
        class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-5xl flex flex-col transform modal-content max-h-[95vh] border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="kepuasanModalTitle" class="text-xl font-bold text-green-700 flex items-center"><i
                        class="fas fa-face-smile mr-2"></i> Rekap Kepuasan Konseli Kelompok</h3>
                <button onclick="closeModal('kepuasanModal')"
                    class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-6 overflow-y-auto">
                <h4 class="text-xl font-extrabold text-center text-gray-800 border-b pb-3">DAFTAR KEPENILAIAN KONSELI
                    PER SESI</h4>
                <div id="kepuasanListContainer" class="p-0">
                </div>
            </div>
            <div class="px-6 py-3 border-t flex justify-end sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closeModal('kepuasanModal')"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i
                        class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>

    <div id="pdfViewerModal"
        class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-7xl max-h-[95vh] flex flex-col transform modal-content border border-gray-300">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-xl">
                <h3 id="pdfIframeTitle" class="text-xl font-bold primary-color flex items-center"><i
                        class="fas fa-file-pdf mr-2"></i> Laporan Konseling Kelompok</h3>
                <button onclick="closePdfViewerModal()"
                    class="text-gray-500 hover:text-gray-700 p-2 rounded-full hover:bg-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-grow overflow-hidden">
                <iframe id="pdfIframe" src="" class="w-full h-[85vh] border-0" title="PDF Viewer"></iframe>
            </div>
            <div class="px-6 py-3 border-t flex justify-end space-x-3 sticky bottom-0 z-10 rounded-b-xl bg-gray-50">
                <button type="button" onclick="closePdfViewerModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium"><i
                        class="fas fa-arrow-left mr-1"></i> Tutup</button>
            </div>
        </div>
    </div>

    <!-- ===== MODAL EDIT LAPORAN KELOMPOK (layout identik dengan form Buat Laporan) ===== -->
    <div id="editKelompokModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
            <div
                class="sticky top-0 bg-[#0F3A3A] px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="editModalTitle" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-edit mr-3"></i> Edit Laporan Konseling Kelompok
                </h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="editKelompokForm" onsubmit="return false;" enctype="multipart/form-data">
                    <input type="hidden" name="id_kelompok" id="edit_id_kelompok">
                    <input type="hidden" name="selected_student_ids" id="edit_selected_student_ids">
                    <input type="hidden" name="deleted_docs" id="edit_deleted_docs" value="[]">
                    <input type="hidden" name="status_konseling" value="Terlaksana">
                    <input type="hidden" name="tempat" id="edit_tempat" value="Ruang BK">

                    <div
                        class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8 p-6 border-2 border-indigo-200 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-50">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-2 flex items-center">
                                <i class="fas fa-users mr-2 text-blue-600"></i> Daftar Siswa Terpilih
                            </p>
                            <div id="editSelectedStudentsInModal"
                                class="flex flex-wrap gap-2 p-3 bg-white border border-blue-200 rounded-lg">
                            </div>
                        </div>
                    </div>

                    <h4 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b-2 border-gray-200 pb-3">
                        <i class="fas fa-edit primary-color mr-2"></i> Detail Pelaksanaan Konseling Kelompok
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="edit_tanggal_pelaksanaan" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1"></i> Tanggal Pelaksanaan
                            </label>
                            <input type="date" name="tanggal_pelaksanaan" id="edit_tanggal_pelaksanaan" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>

                        <div>
                            <label for="edit_waktu_durasi" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-1"></i> Waktu/Durasi
                            </label>
                            <select name="waktu_durasi" id="edit_waktu_durasi" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="">Pilih Durasi</option>
                                <?php foreach($waktu_durasi_options as $durasi): ?>
                                <option value="<?= $durasi ?> Menit"><?= $durasi ?> Menit</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="edit_pertemuan_ke" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-list-ol mr-1"></i> Pertemuan Ke-
                            </label>
                            <input type="number" name="pertemuan_ke" id="edit_pertemuan_ke" placeholder="Masukkan nomor pertemuan..." min="1" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-layer-group mr-1"></i> Bidang Layanan
                        </label>
                        <div id="edit_bidang_layanan_group" class="flex flex-wrap gap-4 p-3 border-2 border-gray-300 rounded-lg">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="edit-bidang-layanan-cb" value="Pribadi"> Pribadi
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="edit-bidang-layanan-cb" value="Sosial"> Sosial
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="edit-bidang-layanan-cb" value="Belajar"> Belajar
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" class="edit-bidang-layanan-cb" value="Karir"> Karir
                            </label>
                        </div>
                        <input type="hidden" name="bidang_layanan" id="edit_bidang_layanan">
                        <p id="edit_bidang_layanan_error" class="text-xs text-red-500 mt-1 hidden">Pilih minimal 1 bidang layanan.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_catatan_khusus" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-search mr-1"></i> Teknik Pendekatan
                            </label>
                            <input type="text" name="pendekatan" id="edit_catatan_khusus" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                        <div>
                            <label for="edit_teknik_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tools mr-1"></i> Teknik Konseling
                            </label>
                            <input type="text" name="teknik" id="edit_teknik_konseling" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="edit_gejala" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-eye mr-1"></i> Gejala yang Nampak
                        </label>
                        <textarea name="gejala" id="edit_gejala" rows="3" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                    </div>

                    <div class="mb-6">
                        <label for="edit_hasil_layanan" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-1"></i> Hasil yang Dicapai
                        </label>
                        <textarea name="hasil_dicapai" id="edit_hasil_layanan" rows="3" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"></textarea>
                    </div>

                    <!-- Dokumentasi Lama -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-images mr-1"></i> Dokumentasi Saat Ini
                        </label>
                        <div id="editExistingDocs" class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200 min-h-[60px]">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Klik tombol × pada foto untuk menandai dihapus. Foto yang ditandai akan dihapus saat disimpan.</p>
                    </div>

                    <div class="mb-6">
                        <label for="edit_dokumentasi" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-camera mr-1"></i> Tambah Dokumentasi Baru <span class="text-xs text-gray-500">(Opsional, Maks 12 foto, Max 2MB/foto)</span>
                        </label>
                        <input type="file" name="dokumentasi[]" id="edit_dokumentasi" multiple accept=".jpg,.jpeg,.png,.webp"
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white text-sm">
                        <p class="text-xs text-gray-500 mt-1">Format diperbolehkan: JPG, JPEG, PNG, WEBP. Foto lama yang tidak dihapus tetap dipertahankan.</p>
                    </div>

                    <div class="mb-6">
                        <label for="edit_guru_pembimbing" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user-tie mr-1"></i> Nama Guru BK
                        </label>
                        <select name="guru_pembimbing" id="edit_guru_pembimbing" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                            <option value="Pahrurazi, S.Pd">Pahrurazi, S.Pd</option>
                            <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
                            <option value="Putri Hidayatie, S.Pd">Putri Hidayatie, S.Pd</option>
                            <option value="Rini Rodhiati, S.Pd">Rini Rodhiati, S.Pd</option>
                            <option value="Gusti Muhammad Fajri Ramadhan, S.Pd">Gusti Muhammad Fajri Ramadhan, S.Pd</option>
                            <option value="Desy Arianti, S.Pd">Desy Arianti, S.Pd</option>
                            <option value="Khalisatun Ni'mah, S.Pd">Khalisatun Ni'mah, S.Pd</option>
                            <option value="Tiara Wulansari, S.Pd">Tiara Wulansari, S.Pd</option>
                            <option value="Dhea Nur Aziza, S.Pd">Dhea Nur Aziza, S.Pd</option>
                            <option value="Abdul Basith, S.Pd">Abdul Basith, S.Pd</option>
                        </select>
                    </div>

                    <div class="mt-8 pt-6 border-t-2 border-gray-200 flex flex-col md:flex-row justify-end gap-3">
                        <button type="button" onclick="closeEditModal()"
                            class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md">
                            <i class="fas fa-times mr-2"></i> Batal
                        </button>
                        <button type="submit" id="editSubmitBtn"
                            class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg transition font-semibold shadow-md>
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>