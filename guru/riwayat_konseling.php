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
    echo "<script>alert('Data Siswa tidak ditemukan!'); window.location.href='konselingindividu.php';</script>";
    exit;
}

$nama_konselor = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru BK (Tidak Diketahui)';

if (isset($_GET['hapus'])) {

    $id_konseling = intval($_GET['hapus']);

    // --- HAPUS FILE DOKUMENTASI FISIK DAN DATA DARI DATABASE ---
    $stmt_docs = $koneksi->prepare("SELECT file_path FROM dokumentasi_konseling WHERE id_konseling = ?");
    $stmt_docs->bind_param("i", $id_konseling);
    $stmt_docs->execute();
    $res_docs = $stmt_docs->get_result();
    
    while($doc = $res_docs->fetch_assoc()) {
        $file_path = dirname(dirname(__FILE__)) . '/' . str_replace('../', '', $doc['file_path']);
        if(file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt_del_docs = $koneksi->prepare("DELETE FROM dokumentasi_konseling WHERE id_konseling = ?");
    $stmt_del_docs->bind_param("i", $id_konseling);
    $stmt_del_docs->execute();
    // -----------------------------------------------------------

    $stmt = $koneksi->prepare(
        "DELETE FROM konseling_individu WHERE id_konseling=?"
    );
    $stmt->bind_param("i", $id_konseling);
    $stmt->execute();

    $stmt = $koneksi->prepare(
        "DELETE FROM riwayat_konseling WHERE id_konseling=?"
    );
    $stmt->bind_param("i", $id_konseling);
    $stmt->execute();

    $stmt = $koneksi->prepare(
        "DELETE FROM kepuasan_siswa WHERE id_konseling=?"
    );
    $stmt->bind_param("i", $id_konseling);
    $stmt->execute();

    // --- RE-INDEX PERTEMUAN_KE DAN PANGGILAN_KE SECARA OTOMATIS ---
    $stmt_get_remaining = $koneksi->prepare("SELECT id_konseling FROM konseling_individu WHERE id_siswa = ? ORDER BY tanggal_pelaksanaan ASC, id_konseling ASC");
    $stmt_get_remaining->bind_param("i", $id_siswa);
    $stmt_get_remaining->execute();
    $res_remaining = $stmt_get_remaining->get_result();
    
    $new_index = 1;
    while ($rem = $res_remaining->fetch_assoc()) {
        $current_id = $rem['id_konseling'];
        $stmt_update_seq = $koneksi->prepare("UPDATE konseling_individu SET pertemuan_ke = ?, panggilan_ke = ? WHERE id_konseling = ?");
        $stmt_update_seq->bind_param("iii", $new_index, $new_index, $current_id);
        $stmt_update_seq->execute();
        $new_index++;
    }
    // --------------------------------------------------------------

    header("Location: riwayat_konseling.php?id_siswa=" . $id_siswa);
    exit;
}

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
    LEFT JOIN riwayat_konseling rk ON ki.id_konseling = rk.id_konseling
    LEFT JOIN kepuasan_siswa ks ON ki.id_konseling = ks.id_konseling
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
        CAST(ki.pertemuan_ke AS UNSIGNED) ASC, ki.tanggal_pelaksanaan ASC, ki.id_konseling ASC
    LIMIT ? OFFSET ?
";
$stmt_riwayat = $koneksi->prepare($query_riwayat);
$stmt_riwayat->bind_param("iii", $id_siswa, $limit, $offset);
$stmt_riwayat->execute();
$result_riwayat = $stmt_riwayat->get_result();
$riwayat_count = $result_riwayat->num_rows; 
$start_number = $offset + 1;

$waktu_durasi_options = [15, 30, 45, 60];
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

        // ===== EDIT MODAL =====
        let deletedDocs = [];

        function openEditModal(data) {
            const modal = document.getElementById('editKonselingModal');
            document.getElementById('editModalTitle').textContent = `Edit Laporan Sesi Konseling - ${data.nama_siswa}`;

            document.getElementById('edit_id_konseling').value = data.id_konseling;
            document.getElementById('edit_id_siswa').value = data.id_siswa;
            document.getElementById('edit_siswa_nama').textContent = data.nama_siswa;
            document.getElementById('edit_siswa_kelas_jurusan').textContent = `${data.kelas} ${data.jurusan}`;
            document.getElementById('edit_siswa_nis').textContent = data.nis;

            document.getElementById('edit_pertemuan_ke').value = data.pertemuan_ke;
            document.getElementById('edit_panggilan_ke').value = data.panggilan_ke;
            document.getElementById('edit_pertemuan_display').textContent = data.pertemuan_ke;
            document.getElementById('edit_panggilan_display').textContent = data.panggilan_ke;

            document.getElementById('edit_tanggal_pelaksanaan').value = data.tanggal_pelaksanaan;
            document.getElementById('edit_waktu_durasi').value = data.waktu_durasi;
            document.getElementById('edit_tempat').value = data.tempat;
            document.getElementById('edit_gejala_nampak').value = data.gejala_nampak;
            document.getElementById('edit_atas_dasar').value = data.atas_dasar;
            document.getElementById('edit_pendekatan_konseling').value = data.pendekatan_konseling;
            document.getElementById('edit_teknik_konseling').value = data.teknik_konseling;
            document.getElementById('edit_hasil_dicapai').value = data.hasil_dicapai;
            document.getElementById('edit_nama_guru').value = data.nama_guru || '';
            document.getElementById('edit_nip_guru_bk').value = data.nip_guru_bk || '';

            // Reset deleted
            deletedDocs = [];
            document.getElementById('edit_deleted_docs').value = '';

            // Render existing docs
            const container = document.getElementById('editExistingDocs');
            container.innerHTML = '';
            if (data.docs && data.docs.length > 0) {
                data.docs.forEach((doc, idx) => {
                    const div = document.createElement('div');
                    div.className = 'doc-preview-item';
                    div.dataset.path = doc.file_path;
                    div.innerHTML = `
                        <img src="${doc.file_path.replace('../', '../')}" alt="Doc ${idx+1}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2275%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22100%22 height=%2275%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 fill=%22%239ca3af%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2210%22%3ENo Preview%3C/text%3E%3C/svg%3E'">
                        <span class="btn-remove-doc" onclick="toggleDeleteDoc(this)" title="Hapus foto ini"><i class="fas fa-times"></i></span>
                    `;
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = '<p class="text-sm text-gray-500 italic">Tidak ada dokumentasi sebelumnya.</p>';
            }

            // Clear file input
            document.getElementById('edit_dokumentasi').value = '';

            modal.classList.add('open');
            document.body.classList.add('overflow-hidden');
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
            const modal = document.getElementById('editKonselingModal');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('editKonselingForm').reset();
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

                let form = document.getElementById("editKonselingForm");
                let formData = new FormData(form);
                // pastikan deleted_docs terkirim
                formData.set('deleted_docs', document.getElementById('edit_deleted_docs').value || '[]');

                const submitButton = document.getElementById('editSubmitBtn');
                const originalText = submitButton.innerHTML;

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan Perubahan...';

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
                        closeEditModal();

                        if (res.status === "success") {
                            alert("Laporan konseling individu berhasil diperbarui.");
                            window.location.reload();
                        } else {
                            alert("Gagal memperbarui laporan: " + (res.message || "Terjadi kesalahan."));
                        }
                    },

                    error: function (xhr) {
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;

                        let errorMessage = "Gagal memperbarui laporan konseling individu.";
                        try {
                            const errorJson = JSON.parse(xhr.responseText);
                            if (errorJson && errorJson.message) {
                                errorMessage = "Gagal memperbarui laporan: " + errorJson.message;
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
                    if (document.getElementById('kepuasanModal').classList.contains('open')) {
                        closeKepuasanModal();
                    } else if (document.getElementById('pdfViewerModal').classList.contains('open')) {
                        closePdfViewerModal();
                    } else if (document.getElementById('editKonselingModal').classList.contains('open')) {
                        closeEditModal();
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
                            
                            <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider w-[140px]">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($riwayat_count > 0): ?>
                            <?php $no = $start_number; while ($data = $result_riwayat->fetch_assoc()): ?>
                                <?php
                                    $session_no = $no;
                                    $tanggal_indo = tgl_indo($data['tanggal_pelaksanaan']);
                                    $has_kepuasan = $data['aspek_penerimaan'] > 0;
                                    
                                    $js_r1 = $data['aspek_penerimaan'] ?? 0;
                                    $js_r2 = $data['aspek_kemudahan_curhat'] ?? 0;
                                    $js_r3 = $data['aspek_kepercayaan'] ?? 0;
                                    $js_r4 = $data['aspek_pemecahan_masalah'] ?? 0;
                                    $js_tanggal = htmlspecialchars($data['tanggal_isi'] ?? '', ENT_QUOTES);

                                    // Ambil dokumentasi untuk sesi ini
                                    $docs = [];
                                    $stmt_doc = $koneksi->prepare("SELECT file_path FROM dokumentasi_konseling WHERE id_konseling = ?");
                                    $stmt_doc->bind_param("i", $data['id_konseling']);
                                    $stmt_doc->execute();
                                    $res_doc = $stmt_doc->get_result();
                                    while ($drow = $res_doc->fetch_assoc()) {
                                        $docs[] = ['file_path' => $drow['file_path']];
                                    }
                                ?>
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-yellow-50 transition duration-150">
                                    <td class="sticky-col px-3 py-3 whitespace-nowrap text-sm font-bold text-gray-900 border-r border-gray-200 w-[50px]"><?= $no++ ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 border-r border-gray-200 w-[120px]"><?= $tanggal_indo ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center border-r border-gray-200 w-[80px] hide-on-mobile"><?= $session_no ?></td>
                                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-600 text-center border-r border-gray-200 w-[80px] hide-on-mobile"><?= $session_no ?></td>
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

                                    <td class="px-3 py-3 text-center text-sm font-medium w-[140px]">
                                        <div class="flex flex-col space-y-2">
                                            <button 
                                                onclick="openKepuasanModal(
                                                    '<?= htmlspecialchars($data['id_konseling']) ?>',
                                                    '<?= $session_no ?>',
                                                    '<?= $js_r1 ?>', '<?= $js_r2 ?>', '<?= $js_r3 ?>', '<?= $js_r4 ?>',
                                                    '<?= $js_tanggal ?>',
                                                    '<?= htmlspecialchars($siswa_data['nama'], ENT_QUOTES) ?>' 
                                                )"
                                                class="w-full text-white px-3 py-1.5 rounded-lg transition duration-200 text-xs font-semibold shadow-md
                                                <?= $has_kepuasan ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700 transition duration-200' ?>">
                                                <i class="fas fa-star mr-1"></i> Kepuasan Siswa
                                            </button>
                                            
                                            <?php if ($data['file_pdf']): ?>
                                                <button onclick="openPdfViewerModal('<?= htmlspecialchars($data['file_pdf'], ENT_QUOTES) ?>', 'Laporan Sesi Ke-<?= $session_no ?>')" class="w-full bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition duration-200 text-xs font-semibold shadow-md">
                                                    <i class="fas fa-file-pdf mr-1"></i> Lihat Laporan
                                                </button>

                                                <button 
                                                    onclick='openEditModal(<?= json_encode([
                                                        "id_konseling" => $data["id_konseling"],
                                                        "id_siswa" => $id_siswa,
                                                        "nama_siswa" => $siswa_data["nama"],
                                                        "kelas" => $siswa_data["kelas"],
                                                        "jurusan" => $siswa_data["jurusan"],
                                                        "nis" => $siswa_data["nis"],
                                                        "pertemuan_ke" => $data["pertemuan_ke"],
                                                        "panggilan_ke" => $data["panggilan_ke"],
                                                        "tanggal_pelaksanaan" => $data["tanggal_pelaksanaan"],
                                                        "waktu_durasi" => $data["waktu_durasi"],
                                                        "tempat" => $data["tempat"],
                                                        "gejala_nampak" => $data["gejala_nampak"],
                                                        "atas_dasar" => $data["atas_dasar"],
                                                        "pendekatan_konseling" => $data["pendekatan_konseling"],
                                                        "teknik_konseling" => $data["teknik_konseling"],
                                                        "hasil_dicapai" => $data["hasil_dicapai"],
                                                        "nama_guru" => $data["nama_guru"] ?? "",
                                                        "nip_guru_bk" => "",
                                                        "docs" => $docs
                                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                                    class="w-full bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition duration-200 text-xs font-semibold shadow-md">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </button>

                                                <a href="?id_siswa=<?= $id_siswa ?>&hapus=<?= $data['id_konseling'] ?>"
                                                    onclick="return confirm('Yakin ingin menghapus riwayat konseling ini?')"
                                                    class="w-full bg-red-700 text-white px-3 py-1.5 rounded-lg hover:bg-red-800 transition duration-200 text-xs font-semibold shadow-md">
                                                    <i class="fas fa-trash mr-1"></i>
                                                    Hapus
                                                </a>
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
        &copy; 2025 Bimbingan dan Konseling SMKN 2 Banjarmasin. All rights reserved.
    </footer>

    <!-- ===== MODAL KEPUASAN (tidak diubah) ===== -->
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
    
    <!-- ===== MODAL PDF VIEWER (tidak diubah) ===== -->
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

    <!-- ===== MODAL EDIT LAPORAN (menggunakan form yang sama dengan Buat Laporan) ===== -->
    <div id="editKonselingModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto transform scale-100 transition-all">
            <div class="sticky top-0 bg-[#0F3A3A] px-6 py-5 flex justify-between items-center z-10 rounded-t-2xl">
                <h3 id="editModalTitle" class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-edit mr-3"></i> Edit Laporan Sesi Konseling
                </h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <div class="p-8">
                <form id="editKonselingForm" onsubmit="return false;" enctype="multipart/form-data">
                    <input type="hidden" name="id_konseling" id="edit_id_konseling">
                    <input type="hidden" name="id_siswa" id="edit_id_siswa">
                    <input type="hidden" name="deleted_docs" id="edit_deleted_docs" value="[]">
                    <input type="hidden" name="status_konseling" value="Proses">
                    <input type="hidden" name="no_input" value="AUTO-GENERATED">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 p-6 border-2 border-indigo-200 rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100">
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600 flex items-center">
                                <i class="fas fa-user mr-2 text-indigo-600"></i> Nama Siswa
                            </p>
                            <p id="edit_siswa_nama" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600 flex items-center">
                                <i class="fas fa-school mr-2 text-indigo-600"></i> Kelas & Jurusan
                            </p>
                            <p id="edit_siswa_kelas_jurusan" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600 flex items-center">
                                <i class="fas fa-id-card mr-2 text-indigo-600"></i> NIS
                            </p>
                            <p id="edit_siswa_nis" class="text-xl font-bold text-gray-900"></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-600 flex items-center">
                                <i class="fas fa-calendar-check mr-2 text-indigo-600"></i> Sesi Saat Ini
                            </p>
                            <p class="text-xl font-bold text-gray-900">
                                Pertemuan <span id="edit_pertemuan_display" class="text-indigo-600">1</span> |
                                Panggilan <span id="edit_panggilan_display" class="text-indigo-600">1</span>
                            </p>
                            <input type="hidden" name="pertemuan_ke" id="edit_pertemuan_ke">
                            <input type="hidden" name="panggilan_ke" id="edit_panggilan_ke">
                        </div>
                    </div>

                    <h4 class="text-xl font-bold mb-6 text-gray-800 flex items-center border-b-2 border-gray-200 pb-3">
                        <i class="fas fa-edit primary-color mr-2"></i> Detail Pelaksanaan Konseling
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="edit_tanggal_pelaksanaan" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-1"></i> Tanggal Pelaksanaan
                            </label>
                            <input type="date" name="tanggal_pelaksanaan" id="edit_tanggal_pelaksanaan" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div>
                            <label for="edit_waktu_durasi" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-clock mr-1"></i> Waktu/Durasi
                            </label>
                            <select name="waktu_durasi" id="edit_waktu_durasi" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                                <option value="">Pilih Durasi</option>
                                <?php foreach($waktu_durasi_options as $durasi): ?>
                                <option value="<?= $durasi ?> Menit"><?= $durasi ?> Menit</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="edit_tempat" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i> Tempat
                            </label>
                            <input type="text" name="tempat" id="edit_tempat" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="edit_gejala_nampak" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-eye mr-1"></i> Gejala yang Nampak
                        </label>
                        <textarea name="gejala_nampak" id="edit_gejala_nampak" rows="3" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="edit_atas_dasar" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-info-circle mr-1"></i> Atas Dasar
                            </label>
                            <input type="text" name="atas_dasar" id="edit_atas_dasar" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div>
                            <label for="edit_pendekatan_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-users mr-1"></i> Pendekatan Konseling
                            </label>
                            <input type="text" name="pendekatan_konseling" id="edit_pendekatan_konseling" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>

                        <div class="md:col-span-2">
                            <label for="edit_teknik_konseling" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-tools mr-1"></i> Teknik Konseling
                            </label>
                            <input type="text" name="teknik_konseling" id="edit_teknik_konseling" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="edit_hasil_dicapai" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-1"></i> Hasil yang Dicapai
                        </label>
                        <textarea name="hasil_dicapai" id="edit_hasil_dicapai" rows="3" required
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"></textarea>
                    </div>

                    <!-- Dokumentasi Lama -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-images mr-1"></i> Dokumentasi Saat Ini
                        </label>
                        <div id="editExistingDocs" class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200 min-h-[60px]">
                            <!-- diisi via JS -->
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-tie mr-1"></i> Nama Guru
                            </label>
                            <select name="nama_guru" id="edit_nama_guru" required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
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

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-id-badge mr-1"></i> NIP Guru BK <span class="text-xs text-gray-500">(Opsional)</span>
                            </label>
                            <input type="text" name="nip_guru_bk" id="edit_nip_guru_bk" placeholder="Boleh dikosongkan"
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t-2 border-gray-200 flex flex-col md:flex-row justify-end gap-3">
                        <button type="button" onclick="closeEditModal()"
                            class="px-6 py-3 bg-gray-400 hover:bg-gray-500 text-white rounded-lg transition font-semibold shadow-md">
                            <i class="fas fa-times mr-2"></i> Batal
                        </button>
                        <button type="submit" id="editSubmitBtn"
                            class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg transition font-semibold shadow-md btn-action">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>