<?php
// ... (Bagian PHP Logic Anda tetap sama, tidak ada perubahan di sini)
session_start();
include '../koneksi.php'; 

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];

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

// ==========================================================================================
// KEPUTUSAN INDIVIDU - FETCH (Logika sama)
// ==========================================================================================
if (isset($_GET['action']) && $_GET['action'] === 'fetch_kepuasan_individu') {
    header('Content-Type: application/json');
    $id_konseling = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id_konseling > 0) {
        $query_detail = "
            SELECT 
                aspek_penerimaan, aspek_kemudahan_curhat, aspek_kepercayaan, aspek_pemecahan_masalah, tanggal_isi
            FROM 
                kepuasan_siswa
            WHERE 
                id_konseling = ? AND id_siswa = ?
        ";
        $stmt_detail = $koneksi->prepare($query_detail);
        $stmt_detail->bind_param("ii", $id_konseling, $id_siswa);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        $data_kepuasan = $result_detail->fetch_assoc();
        $stmt_detail->close();

        if ($data_kepuasan) {
            echo json_encode(["status" => "success", "data" => $data_kepuasan]);
        } else {
            echo json_encode(["status" => "not_found", "message" => "Data kepuasan belum diisi."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID Konseling tidak valid."]);
    }
    exit;
}

// ==========================================================================================
// KEPUTUSAN INDIVIDU - SUBMIT (Logika sama)
// ==========================================================================================
if (isset($_GET['action']) && $_GET['action'] === 'submit_kepuasan_individu') {
    header('Content-Type: application/json');

    $id_konseling = isset($_POST['id_sesi']) ? (int)$_POST['id_sesi'] : 0;
    $aspek_penerimaan = isset($_POST['r1']) ? (int)$_POST['r1'] : 0;
    $aspek_kemudahan_curhat = isset($_POST['r2']) ? (int)$_POST['r2'] : 0;
    $aspek_kepercayaan = isset($_POST['r3']) ? (int)$_POST['r3'] : 0;
    $aspek_pemecahan_masalah = isset($_POST['r4']) ? (int)$_POST['r4'] : 0;

    if ($id_konseling > 0 && $aspek_penerimaan > 0 && $aspek_kemudahan_curhat > 0 && $aspek_kepercayaan > 0 && $aspek_pemecahan_masalah > 0) {
        $query_check = "SELECT id_kepuasan FROM kepuasan_siswa WHERE id_konseling = ? AND id_siswa = ?";
        $stmt_check = $koneksi->prepare($query_check);
        $stmt_check->bind_param("ii", $id_konseling, $id_siswa);
        $stmt_check->execute();
        $stmt_check->store_result();
        $is_filled = $stmt_check->num_rows > 0;
        $stmt_check->close();

        if ($is_filled) {
            echo json_encode(["status" => "error", "message" => "Penilaian ini sudah pernah Anda isi dan tidak dapat diubah lagi."]);
            exit;
        } 
        
        $query_submit = "
            INSERT INTO kepuasan_siswa (aspek_penerimaan, aspek_kemudahan_curhat, aspek_kepercayaan, aspek_pemecahan_masalah, id_konseling, id_siswa)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $bind_types = "iiiiii";
        $bind_values = [
            $aspek_penerimaan, 
            $aspek_kemudahan_curhat, 
            $aspek_kepercayaan, 
            $aspek_pemecahan_masalah, 
            $id_konseling, 
            $id_siswa
        ];

        $stmt_submit = $koneksi->prepare($query_submit);
        
        $stmt_submit->bind_param($bind_types, ...$bind_values);

        if ($stmt_submit->execute()) {
            echo json_encode(["status" => "success", "message" => "Terima kasih! Penilaian kepuasan Anda berhasil disimpan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan data: " . $stmt_submit->error]);
        }
        $stmt_submit->close();

    } else {
        echo json_encode(["status" => "error", "message" => "Data penilaian tidak lengkap. Pastikan semua aspek telah diisi."]);
    }
    exit;
}

// ==========================================================================================
// KEPUTUSAN KELOMPOK - FETCH (Logika sama)
// ==========================================================================================
if (isset($_GET['action']) && $_GET['action'] === 'fetch_kepuasan_kelompok') {
    header('Content-Type: application/json');
    $id_kelompok = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id_kelompok > 0) {
        $query_detail = "
            SELECT 
                aspek_penerimaan, aspek_kemudahan_curhat, aspek_kepercayaan, aspek_pemecahan_masalah, tanggal_isi
            FROM 
                kepuasan_kelompok
            WHERE 
                id_kelompok = ? AND id_siswa = ?
        ";
        $stmt_detail = $koneksi->prepare($query_detail);
        $stmt_detail->bind_param("ii", $id_kelompok, $id_siswa);
        $stmt_detail->execute();
        $result_detail = $stmt_detail->get_result();
        $data_kepuasan = $result_detail->fetch_assoc();
        $stmt_detail->close();

        if ($data_kepuasan) {
            echo json_encode(["status" => "success", "data" => $data_kepuasan]);
        } else {
            echo json_encode(["status" => "not_found", "message" => "Data kepuasan kelompok belum diisi."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID Kelompok tidak valid."]);
    }
    exit;
}

// ==========================================================================================
// KEPUTUSAN KELOMPOK - SUBMIT (Logika sama)
// ==========================================================================================
if (isset($_GET['action']) && $_GET['action'] === 'submit_kepuasan_kelompok') {
    header('Content-Type: application/json');

    $id_kelompok = isset($_POST['id_sesi']) ? (int)$_POST['id_sesi'] : 0;
    $aspek_penerimaan = isset($_POST['r1']) ? (int)$_POST['r1'] : 0;
    $aspek_kemudahan_curhat = isset($_POST['r2']) ? (int)$_POST['r2'] : 0;
    $aspek_kepercayaan = isset($_POST['r3']) ? (int)$_POST['r3'] : 0;
    $aspek_pemecahan_masalah = isset($_POST['r4']) ? (int)$_POST['r4'] : 0;

    if ($id_kelompok > 0 && $aspek_penerimaan > 0 && $aspek_kemudahan_curhat > 0 && $aspek_kepercayaan > 0 && $aspek_pemecahan_masalah > 0) {
        $query_check = "SELECT id_kepuasan_kelompok FROM kepuasan_kelompok WHERE id_kelompok = ? AND id_siswa = ?";
        $stmt_check = $koneksi->prepare($query_check);
        $stmt_check->bind_param("ii", $id_kelompok, $id_siswa);
        $stmt_check->execute();
        $stmt_check->store_result();
        $is_filled = $stmt_check->num_rows > 0;
        $stmt_check->close();

        if ($is_filled) {
            echo json_encode(["status" => "error", "message" => "Penilaian ini sudah pernah Anda isi dan tidak dapat diubah lagi."]);
            exit;
        } 
        
        $query_submit = "
            INSERT INTO kepuasan_kelompok (aspek_penerimaan, aspek_kemudahan_curhat, aspek_kepercayaan, aspek_pemecahan_masalah, id_kelompok, id_siswa)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $bind_types = "iiiiii";
        $bind_values = [
            $aspek_penerimaan, 
            $aspek_kemudahan_curhat, 
            $aspek_kepercayaan, 
            $aspek_pemecahan_masalah, 
            $id_kelompok, 
            $id_siswa
        ];

        $stmt_submit = $koneksi->prepare($query_submit);
        
        $stmt_submit->bind_param($bind_types, ...$bind_values);

        if ($stmt_submit->execute()) {
            echo json_encode(["status" => "success", "message" => "Terima kasih! Penilaian kepuasan kelompok Anda berhasil disimpan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan data kelompok: " . $stmt_submit->error]);
        }
        $stmt_submit->close();

    } else {
        echo json_encode(["status" => "error", "message" => "Data penilaian kelompok tidak lengkap. Pastikan semua aspek telah diisi."]);
    }
    exit;
}

// ------------------------------------------------------------------------------------------
// PENGAMBILAN DATA UNTUK TAMPILAN UTAMA (Logika sama)
// ------------------------------------------------------------------------------------------

// Ambil data siswa
$stmt_siswa = $koneksi->prepare("SELECT nama, kelas, jurusan FROM siswa WHERE id_siswa = ?");
$stmt_siswa->bind_param("i", $id_siswa);
$stmt_siswa->execute();
$result_siswa = $stmt_siswa->get_result();
$siswa_data = $result_siswa->fetch_assoc();
$stmt_siswa->close();

if (!$siswa_data) {
    echo "<script>alert('Data Siswa tidak ditemukan!'); window.location.href='../login.php';</script>";
    exit;
}

// Query untuk Riwayat Konseling Individu
$query_individu = "
    SELECT 
        ki.id_konseling, ki.tanggal_pelaksanaan, ki.pertemuan_ke, ki.gejala_nampak,
        rk.file_pdf,
        ks.id_kepuasan
    FROM 
        konseling_individu ki
    LEFT JOIN
        riwayat_konseling rk ON ki.id_konseling = rk.id_konseling
    LEFT JOIN
        kepuasan_siswa ks ON ki.id_konseling = ks.id_konseling AND ks.id_siswa = ?
    WHERE 
        ki.id_siswa = ?
    ORDER BY 
        ki.tanggal_pelaksanaan DESC
";
$stmt_individu = $koneksi->prepare($query_individu);
$stmt_individu->bind_param("ii", $id_siswa, $id_siswa);
$stmt_individu->execute();
$result_individu = $stmt_individu->get_result();
$riwayat_individu_count = $result_individu->num_rows;


// Query untuk Riwayat Konseling Kelompok
$query_kelompok = "
    SELECT 
        kk.id_kelompok, kk.tanggal_pelaksanaan, kk.pertemuan_ke, kk.topik_masalah,
        rk.file_pdf,
        kks.id_kepuasan_kelompok,
        (SELECT COUNT(pk2.id_siswa) FROM detail_kelompok pk2 WHERE pk2.id_kelompok = kk.id_kelompok) AS total_siswa_kelompok
    FROM 
        kelompok kk
    JOIN 
        detail_kelompok pk ON kk.id_kelompok = pk.id_kelompok
    LEFT JOIN
        riwayat_kelompok rk ON kk.id_kelompok = rk.id_kelompok
    LEFT JOIN
        kepuasan_kelompok kks ON kk.id_kelompok = kks.id_kelompok AND kks.id_siswa = ?
    WHERE 
        pk.id_siswa = ?
    ORDER BY 
        kk.tanggal_pelaksanaan DESC
";
$stmt_kelompok = $koneksi->prepare($query_kelompok);
$stmt_kelompok->bind_param("ii", $id_siswa, $id_siswa);
$stmt_kelompok->execute();
$result_kelompok = $stmt_kelompok->get_result();
$riwayat_kelompok_count = $result_kelompok->num_rows;
$stmt_kelompok->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Konseling Siswa | BK</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .primary-bg { background-color: #2F6C6E; }
        .primary-color { color: #2F6C6E; }
        
        /* Transition untuk modal */
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            opacity: 0;
        }
        .modal.open {
            visibility: visible;
            opacity: 1;
        }

        /* Styling radio button untuk Rating Kepuasan */
        .rating-input {
            /* Sembunyikan radio button bawaan */
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
/* Style untuk opsi rating (default mode input) */
.rating-option {
    transition: all 0.2s ease-in-out;
    cursor: pointer;
    /* Ganti @apply border-2 border-gray-300 bg-white text-gray-700 hover:bg-gray-100; */
    border-width: 2px;
    border-color: #d1d5db; /* Tailwind gray-300 */
    background-color: #ffffff;
    color: #4b5563; /* Tailwind gray-700 */
}

.rating-option:hover {
    background-color: #f3f4f6; /* Tailwind gray-100 */
}

/* CSS untuk pilihan rating yang terpilih (Mode Input) */
.rating-input:checked + .rating-option {
    /* Ganti @apply bg-blue-600 text-white border-blue-700 shadow-md; */
    background-color: #2563eb; /* Tailwind blue-600 */
    color: #ffffff;
    border-color: #1d4ed8; /* Tailwind blue-700 */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); /* Tailwind shadow-md */
}

/* --- PERUBAHAN UTAMA UNTUK MODE READ-ONLY (sesuai permintaan) --- */
/* Style untuk semua opsi ketika input dinonaktifkan */
.rating-input:disabled + .rating-option {
    /* Ganti @apply bg-gray-100 text-gray-500 cursor-default opacity-80; */
    background-color: #f3f4f6; /* Tailwind gray-100 */
    color: #6b7280; /* Tailwind gray-500 */
    cursor: default;
    opacity: 0.8;
    border-color: #e5e7eb; /* gray-200, tetap ada di kode lama */
}

/* Style untuk rating yang terpilih di mode Read-Only (Highlight Hijau) */
.rating-input:checked:disabled + .rating-option {
    /* Ganti @apply bg-green-500 text-white border-green-600 opacity-100 shadow-lg; */
    background-color: #10b981; /* Tailwind green-500 */
    color: #ffffff;
    border-color: #059669; /* Tailwind green-600 */
    opacity: 1;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); /* Tailwind shadow-lg */
}
        /* Style untuk card riwayat (tambahan) */
        .counseling-card {
            border-left: 5px solid #2F6C6E; /* Primary color */
        }
        
        /* FIX: Tambahkan max-width untuk konten modal agar bisa di-scroll di HP */
        .modal-content-fix {
             max-height: 90vh; /* Agar konten bisa di-scroll jika terlalu panjang */
        }
    </style>
    <script>
        const pageUrl = '<?= basename($_SERVER['PHP_SELF']) ?>';
        let currentSesiId = null;
        let currentSesiType = null; // 'individu' or 'kelompok'

        // Fungsi untuk mengkonfigurasi Modal Kepuasan
        function openKepuasanModal(id, type) {
            currentSesiId = id;
            currentSesiType = type;
            const modal = $('#kepuasanModal');
            const title = modal.find('#kepuasanModalTitle');
            const form = $('#kepuasanForm');
            const submitBtn = $('#submitKepuasanBtn');
            const statusDiv = modal.find('#statusPenilaian');
            const ratingContainers = modal.find('.rating-container');

            // 1. Reset form and initial state
            form.trigger('reset');
            // Pastikan semua input dienable dan reset styling Read-Only/Selected
            ratingContainers.find('.rating-input').prop('checked', false).prop('disabled', false);
            ratingContainers.find('.rating-option').removeClass('bg-blue-600 text-white border-blue-700 shadow-md bg-green-500 text-white border-green-600 opacity-100 shadow-lg').addClass('cursor-pointer').removeClass('bg-gray-100 text-gray-500 opacity-80 border-gray-200');
            
            submitBtn.show().prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penilaian');
            statusDiv.addClass('hidden').removeClass('bg-red-100 text-red-700 bg-green-100 text-green-700');
            
            form.find('#id_sesi').val(id);
            form.find('#sesi_type').val(type);
            
            title.text('Penilaian Kepuasan Konseling ' + (type === 'individu' ? 'Individu' : 'Kelompok'));

            // 2. Fetch existing satisfaction data
            const action = type === 'individu' ? 'fetch_kepuasan_individu' : 'fetch_kepuasan_kelompok';

            $.ajax({
                url: pageUrl + '?action=' + action + '&id=' + id,
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                   // Tampilkan spinner kecil saat fetch
                   statusDiv.removeClass('hidden').addClass('bg-gray-100 text-gray-700 p-2 rounded-lg text-sm').html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat data penilaian...');
                },
                success: function(res) {
                    if (res.status === 'success') {
                        // --- MODE READ-ONLY (Sudah Diisi) ---
                        const data = res.data;
                        const ratings = [
                            data.aspek_penerimaan, 
                            data.aspek_kemudahan_curhat, 
                            data.aspek_kepercayaan, 
                            data.aspek_pemecahan_masalah
                        ];
                        
                        for (let i = 0; i < ratings.length; i++) {
                            const value = ratings[i];
                            const aspek = 'r' + (i + 1);
                            
                            // Set selected value and disable all options
                            $('input[name="' + aspek + '"]').prop('disabled', true);
                            if (value) {
                                $('#' + aspek + '_' + value).prop('checked', true);
                            }
                        }
                        
                        // Update status dan sembunyikan tombol submit
                        submitBtn.hide();
                        statusDiv.removeClass('hidden').removeClass('bg-gray-100').addClass('bg-green-100 text-green-700 p-2 rounded-lg text-sm').html('<i class="fas fa-check-circle mr-1"></i> Penilaian telah diisi pada ' + (data.tanggal_isi ? new Date(data.tanggal_isi).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : 'sebelumnya') + ' dan tidak dapat diubah.');

                    } else if (res.status === 'not_found') {
                        // --- MODE INPUT (Belum Diisi) ---
                        submitBtn.show();
                        // Pastikan input tidak disabled (sudah dilakukan di langkah 1)
                        statusDiv.removeClass('hidden').removeClass('bg-gray-100').addClass('bg-red-100 text-red-700 p-2 rounded-lg text-sm').html('<i class="fas fa-exclamation-triangle mr-1"></i> Anda belum mengisi penilaian untuk sesi ini. Mohon lengkapi.');
                    }
                },
                error: function() {
                    statusDiv.removeClass('hidden').removeClass('bg-gray-100').addClass('bg-red-100 text-red-700 p-2 rounded-lg text-sm').html('<i class="fas fa-times-circle mr-1"></i> Gagal memuat data kepuasan.');
                    submitBtn.hide();
                },
                complete: function() {
                    // Tampilkan modal setelah proses loading selesai
                    modal.addClass('open');
                    $('body').addClass('overflow-hidden');
                }
            });
        }

        function closeKepuasanModal() {
            $('#kepuasanModal').removeClass('open');
            $('body').removeClass('overflow-hidden');
            currentSesiId = null;
            currentSesiType = null;
            $('#kepuasanForm').trigger('reset');
        }

        function openPdfViewerModal(pdfPath, title) {
            const modal = $('#pdfViewerModal');
            const iframe = $('#pdfIframe');
            
            $('#pdfIframeTitle').text(title);
            // Construct full PDF URL (Adjust this if your path logic is different)
            const pdfUrl = pdfPath.startsWith('..') ? pdfPath.replace('../', '<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/') : pdfPath;
            iframe.attr('src', pdfUrl);

            modal.addClass('open');
            $('body').addClass('overflow-hidden');
        }
        
        function closePdfViewerModal() {
            $('#pdfIframe').attr('src', ''); 
            $('#pdfViewerModal').removeClass('open');
            $('body').removeClass('overflow-hidden');
        }

        function switchTab(type) {
            const tabs = ['individu', 'kelompok'];
            tabs.forEach(tab => {
                $('#tab-' + tab).removeClass('border-blue-600 text-blue-600').addClass('border-transparent text-gray-500 hover:text-gray-700');
                $('#content-' + tab).addClass('hidden');
            });

            $('#tab-' + type).removeClass('border-transparent text-gray-500 hover:text-gray-700').addClass('border-blue-600 text-blue-600');
            $('#content-' + type).removeClass('hidden');
        }


        $(document).ready(function() {
            // Initial tab selection
            const initialTab = '<?= isset($_GET['tab']) && $_GET['tab'] === 'kelompok' ? 'kelompok' : 'individu' ?>';
            switchTab(initialTab);
            
            // AJAX Form Submission for Kepuasan
            $("#kepuasanForm").submit(function(e) {
                e.preventDefault();
                
                // Cek apakah semua radio button sudah dipilih
                const requiredNames = ['r1', 'r2', 'r3', 'r4'];
                let isFormValid = true;
                requiredNames.forEach(name => {
                    if ($('input[name="' + name + '"]:checked').length === 0) {
                        isFormValid = false;
                    }
                });

                if (!isFormValid) {
                    alert("Harap lengkapi semua 4 aspek penilaian kepuasan sebelum menyimpan.");
                    return; 
                }

                const type = $('#sesi_type').val();
                const action = type === 'individu' ? 'submit_kepuasan_individu' : 'submit_kepuasan_kelompok';
                
                const formData = $(this).serialize();
                const btn = $("#submitKepuasanBtn");
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

                $.ajax({
                    url: pageUrl + '?action=' + action,
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(res) {
                        alert(res.message);
                        if (res.status === 'success') {
                            // Update UI state for the specific session's button
                            const $btn_state = $(`.btn-kepuasan[data-id="${currentSesiId}"][data-type="${currentSesiType}"]`);
                            if ($btn_state.length) {
                                // Mengubah ke status "Lihat Penilaian" (Hijau)
                                $btn_state.removeClass('bg-red-500 hover:bg-red-600').addClass('bg-green-600 hover:bg-green-700').html('<i class="fas fa-check-circle mr-1"></i> Lihat Penilaian');
                            }
                            
                            closeKepuasanModal();
                            // Redirect untuk refresh data dan status di daftar riwayat
                            window.location.href = window.location.pathname + '?tab=' + currentSesiType; 
                        }
                    },
                    error: function(xhr) {
                        let error_message = "Terjadi error saat mengirim data.";
                        try {
                            const error_res = JSON.parse(xhr.responseText);
                            if (error_res && error_res.message) {
                                error_message = error_res.message;
                            }
                        } catch (e) {
                            error_message += " (Status: " + xhr.statusText + ")";
                        }
                        
                        alert("Gagal menyimpan data kepuasan. Error: " + error_message);
                        console.error("Error submitting kepuasan:", xhr.responseText);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penilaian');
                    }
                });
            });
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="fixed top-0 left-0 w-full bg-white shadow-lg z-30 flex items-center justify-between h-[56px] px-4">
        <a href="dashboard_siswa.php" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <span class="text-lg font-bold primary-color hidden sm:inline">Riwayat Konseling</span>
        </a>
        <a href="dashboard.php" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm flex items-center transition duration-200 shadow-md">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </header>

    <main class="flex-1 p-4 md:p-8 mt-[56px] w-full">
        <div class="max-w-4xl mx-auto bg-white p-4 md:p-8 rounded-xl shadow-2xl">
            
            <div class="mb-6 border-b pb-4">
                <h2 class="text-xl md:text-3xl font-extrabold text-gray-900 flex items-center">
                    <i class="fas fa-history primary-color mr-3"></i> Riwayat Layanan BK
                </h2>
                <p class="text-gray-600 mt-1 text-sm md:text-base">Halo, <span class="font-bold text-blue-600"><?= htmlspecialchars($siswa_data['nama']) ?></span>. Berikut adalah riwayat sesi Anda.</p>
                <div class="mt-2 text-sm text-gray-500">
                    <p>Kelas: **<?= htmlspecialchars($siswa_data['kelas'] . ' ' . $siswa_data['jurusan']) ?>**</p>
                </div>
            </div>

            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px" role="tablist">
                    <li class="mr-2" role="presentation">
                        <a href="javascript:void(0)" onclick="switchTab('individu')" id="tab-individu" class="inline-block p-4 border-b-2 font-semibold text-sm md:text-base rounded-t-lg transition duration-150">
                            <i class="fas fa-user-circle mr-1"></i> Individu (<span class="font-bold"><?= $riwayat_individu_count ?></span>)
                        </a>
                    </li>
                    <li class="mr-2" role="presentation">
                        <a href="javascript:void(0)" onclick="switchTab('kelompok')" id="tab-kelompok" class="inline-block p-4 border-b-2 font-semibold text-sm md:text-base rounded-t-lg transition duration-150">
                            <i class="fas fa-users-line mr-1"></i> Kelompok (<span class="font-bold"><?= $riwayat_kelompok_count ?></span>)
                        </a>
                    </li>
                </ul>
            </div>

            <div id="content-individu" class="space-y-6" role="tabpanel">
                <?php if ($riwayat_individu_count > 0): ?> 
                    <?php mysqli_data_seek($result_individu, 0); while ($data = mysqli_fetch_assoc($result_individu)): ?>
                        <div class="counseling-card p-4 sm:p-5 border border-gray-200 rounded-xl shadow-lg bg-white hover:shadow-xl transition duration-300">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-3 border-b pb-3">
                                <span class="text-sm font-medium text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200 mb-2 sm:mb-0">
                                    <i class="fas fa-calendar-alt mr-1"></i> **<?= tgl_indo($data['tanggal_pelaksanaan']) ?>**
                                </span>
                                <span class="text-xs font-extrabold bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full w-fit">
                                    Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>
                                </span>
                            </div>

                            <div class="text-sm md:text-base mb-4">
                                <p class="font-bold text-gray-800 mb-1">Gejala Awal / Permasalahan:</p>
                                <p class="text-gray-700 italic line-clamp-3 leading-relaxed">"<?= htmlspecialchars($data['gejala_nampak']) ?>"</p>
                            </div>

                            <div class="pt-4 border-t flex flex-col space-y-2 sm:flex-row sm:justify-end sm:space-x-3 sm:space-y-0">
                                
                               
                                
                                <?php 
                                    $btn_class = empty($data['id_kepuasan']) ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700';
                                    $btn_text = empty($data['id_kepuasan']) ? 'Beri Penilaian' : 'Lihat Penilaian';
                                    $btn_icon = empty($data['id_kepuasan']) ? 'fa-star' : 'fa-check-circle';
                                ?>
                                <button
                                    onclick="openKepuasanModal(<?= $data['id_konseling'] ?>, 'individu')"
                                    class="btn-kepuasan px-4 py-2 <?= $btn_class ?> text-white rounded-lg transition duration-200 font-semibold text-sm shadow-md flex items-center justify-center"
                                    data-id="<?= $data['id_konseling'] ?>"
                                    data-type="individu">
                                    <i class="fas <?= $btn_icon ?> mr-2"></i> <?= $btn_text ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-8 text-center border-dashed border-2 border-gray-300 rounded-lg bg-gray-50">
                        <i class="fas fa-user-circle text-4xl text-gray-500 mb-3"></i>
                        <p class="text-lg text-gray-700 font-medium">
                            Anda belum memiliki riwayat Konseling Individu.
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Silakan hubungi Guru BK Anda untuk menjadwalkan sesi.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="content-kelompok" class="space-y-6 hidden" role="tabpanel">
                <?php if ($riwayat_kelompok_count > 0): ?>
                    <?php mysqli_data_seek($result_kelompok, 0); while ($data = mysqli_fetch_assoc($result_kelompok)): ?>
                        <div class="counseling-card p-4 sm:p-5 border border-gray-200 rounded-xl shadow-lg bg-white hover:shadow-xl transition duration-300">
                            <div class="flex flex-col sm:flex-row justify-between items-start mb-3 border-b pb-3">
                                <span class="text-sm font-medium text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200 mb-2 sm:mb-0">
                                    <i class="fas fa-calendar-alt mr-1"></i> **<?= tgl_indo($data['tanggal_pelaksanaan']) ?>**
                                </span>
                                <div class="flex flex-wrap gap-2">
                                    <span class="text-xs font-extrabold bg-green-100 text-green-800 px-3 py-1 rounded-full w-fit">
                                        <i class="fas fa-users mr-1"></i> Peserta: <?= $data['total_siswa_kelompok'] ?>
                                    </span>
                                    <span class="text-xs font-extrabold bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full w-fit">
                                        Sesi Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="text-sm md:text-base mb-4">
                                <p class="font-bold text-gray-800 mb-1">Topik Masalah:</p>
                                <p class="text-gray-700 italic line-clamp-3 leading-relaxed">"<?= htmlspecialchars($data['topik_masalah']) ?>"</p>
                            </div>

                            <div class="pt-4 border-t flex flex-col space-y-2 sm:flex-row sm:justify-end sm:space-x-3 sm:space-y-0">
                                
                               
                                
                                <?php 
                                    $btn_class = empty($data['id_kepuasan_kelompok']) ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700';
                                    $btn_text = empty($data['id_kepuasan_kelompok']) ? 'Beri Penilaian' : 'Lihat Penilaian';
                                    $btn_icon = empty($data['id_kepuasan_kelompok']) ? 'fa-star' : 'fa-check-circle';
                                ?>
                                <button
                                    onclick="openKepuasanModal(<?= $data['id_kelompok'] ?>, 'kelompok')"
                                    class="btn-kepuasan px-4 py-2 <?= $btn_class ?> text-white rounded-lg transition duration-200 font-semibold text-sm shadow-md flex items-center justify-center"
                                    data-id="<?= $data['id_kelompok'] ?>"
                                    data-type="kelompok">
                                    <i class="fas <?= $btn_icon ?> mr-2"></i> <?= $btn_text ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-8 text-center border-dashed border-2 border-gray-300 rounded-lg bg-gray-50">
                        <i class="fas fa-users-line text-4xl text-gray-500 mb-3"></i>
                        <p class="text-lg text-gray-700 font-medium">
                            Anda belum memiliki riwayat Konseling Kelompok.
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Silakan hubungi Guru BK Anda untuk menjadwalkan sesi kelompok.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>

    <div id="kepuasanModal" class="modal fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-75 flex items-center justify-center p-4">
        <div class="modal-content-fix bg-white rounded-xl shadow-2xl w-full max-w-lg mx-auto overflow-y-auto transform transition-all duration-300">
            <div class="flex justify-between items-center p-5 border-b sticky top-0 bg-white z-10">
                <h3 id="kepuasanModalTitle" class="text-xl font-bold text-gray-800">Penilaian Kepuasan Konseling</h3>
                <button onclick="closeKepuasanModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="kepuasanForm" class="p-5">
                <input type="hidden" name="id_sesi" id="id_sesi">
                <input type="hidden" name="sesi_type" id="sesi_type">
                
                <div id="statusPenilaian" class="hidden p-2 rounded-lg text-sm mb-4"></div>

                <div class="space-y-6">
                    <?php 
                    $aspek_rating = [
                        1 => 'Pelayanan Penerimaan Guru BK',
                        2 => 'Kemudahan dalam Mencurahkan Masalah',
                        3 => 'Kepercayaan terhadap Kerahasiaan Informasi',
                        4 => 'Bantuan dalam Pemecahan Masalah'
                    ];
                    $rating_options = [
                        1 => 'Sangat Tidak Puas',
                        2 => 'Tidak Puas',
                        3 => 'Cukup Puas',
                        4 => 'Puas',
                        5 => 'Sangat Puas'
                    ];
                    ?>

                    <?php foreach ($aspek_rating as $key => $aspek): ?>
                        <div class="rating-container">
                            <p class="font-semibold text-gray-700 mb-2 text-sm md:text-base">Aspek <?= $key ?>: <?= $aspek ?></p>
                            <div class="flex justify-between flex-wrap gap-2">
                                <?php foreach ($rating_options as $value => $label): ?>
                                    <label class="flex-1 min-w-[70px] sm:min-w-[80px] text-center">
                                        <input type="radio" name="r<?= $key ?>" id="r<?= $key ?>_<?= $value ?>" value="<?= $value ?>" class="rating-input">
                                        <span class="rating-option block px-2 py-2 rounded-lg text-xs md:text-sm font-medium">
                                            <?= $value ?>
                                            <span class="block text-gray-500 text-xs mt-1 transition-colors group-hover:text-gray-700 font-normal"><?= $label ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="mt-6 border-t pt-4">
                    <button type="submit" id="submitKepuasanBtn" class="w-full primary-bg text-white px-4 py-2 rounded-lg font-semibold hover:opacity-90 transition duration-200 shadow-lg">
                        <i class="fas fa-save mr-1"></i> Simpan Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-75 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-[90vh] mx-auto overflow-hidden transform transition-all duration-300">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 id="pdfIframeTitle" class="text-lg font-bold text-gray-800 truncate">Lihat Laporan Konseling</h3>
                <button onclick="closePdfViewerModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <iframe id="pdfIframe" src="" frameborder="0" class="w-full h-[calc(100%-60px)]"></iframe>
        </div>
    </div>

</body>
</html>
