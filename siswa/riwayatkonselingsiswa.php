<?php
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
$stmt_individu->close();

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
        .primary-bg { background-color: #2F6C6E; }
        .primary-color { color: #2F6C6E; }
        .accent-color { color: #5FA8A1; }
        body { background-color: #F9FAFB; }

        .fade-slide { transition: all 0.3s ease-in-out; transform-origin: top; }
        .hidden-transition { opacity: 0; transform: scaleY(0.95); max-height: 0; overflow: hidden; pointer-events: none; }
        .visible-transition { opacity: 1; transform: scaleY(1); max-height: 500px; pointer-events: auto; }

        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            opacity: 0;
        }
        .modal.open {
            visibility: visible;
            opacity: 1;
        }

        .rating-input:checked + .rating-option {
            background-color: #4C8E89; 
            color: white;
            border-color: #0F3A3A; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        
        .rating-option-disabled {
            background-color: #F9FAFB; 
            color: #4b5563; 
            cursor: default;
            opacity: 0.7;
        }
        .rating-option-disabled.selected {
            background-color: #10b981; 
            color: white;
            border-color: #059669; 
            opacity: 1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        const pageUrl = '<?= basename($_SERVER['PHP_SELF']) ?>';
        let currentSesiId = null;
        let currentSesiType = null; 

        function toggleMenu() {
            const menu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('menuOverlay');
            const body = document.body;

            const isClosed = menu.classList.contains('hidden-transition');

            if (isClosed) {
                menu.classList.remove('hidden-transition');
                menu.classList.add('visible-transition');
                overlay.classList.remove('hidden');
                body.classList.add('overflow-hidden');
            } else {
                menu.classList.remove('visible-transition');
                menu.classList.add('hidden-transition');
                overlay.classList.add('hidden');
                body.classList.remove('overflow-hidden');
            }
        }
        
        function openKepuasanModal(id, type) {
            currentSesiId = id;
            currentSesiType = type;
            const modal = $('#kepuasanModal');
            const title = modal.find('#kepuasanModalTitle');
            const form = $('#kepuasanForm');

            form.trigger('reset');
            $('.rating-input').prop('checked', false);
            $('.rating-option').removeClass('rating-option-disabled selected').prop('disabled', false).parent().removeClass('cursor-default');
            $('#submitKepuasanBtn').show().prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Penilaian');
            
            form.find('#id_sesi').val(id);
            form.find('#sesi_type').val(type);
            
            title.text('Penilaian Kepuasan Konseling ' + (type === 'individu' ? 'Individu' : 'Kelompok'));
            modal.find('#jenisSesiText').text(type === 'individu' ? 'Individu' : 'Kelompok');
            modal.find('#statusPenilaian').addClass('hidden');

            const action = type === 'individu' ? 'fetch_kepuasan_individu' : 'fetch_kepuasan_kelompok';

            $.ajax({
                url: pageUrl + '?action=' + action + '&id=' + id,
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        const data = res.data;
                        const ratings = [data.aspek_penerimaan, data.aspek_kemudahan_curhat, data.aspek_kepercayaan, data.aspek_pemecahan_masalah];
                        
                        for (let i = 0; i < ratings.length; i++) {
                            const value = ratings[i];
                            const aspek = 'r' + (i + 1);
                            
                            if (value) {
                                const selectedInput = $('#' + aspek + '_' + value);
                                selectedInput.prop('checked', true);
                                
                                selectedInput.next('.rating-option').addClass('rating-option-disabled selected');
                            }
                            
                            $('input[name="' + aspek + '"]').prop('disabled', true);
                            $('input[name="' + aspek + '"]').next('.rating-option').addClass('rating-option-disabled').removeClass('cursor-pointer').parent().addClass('cursor-default');
                        }
                        
                        modal.find('#submitKepuasanBtn').hide();
                        modal.find('#statusPenilaian').removeClass('hidden').html('<i class="fas fa-check-circle mr-1 text-green-600"></i> Penilaian telah diisi pada ' + (data.tanggal_isi ? new Date(data.tanggal_isi).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : 'sebelumnya') + ' dan tidak dapat diubah.');

                    } else if (res.status === 'not_found') {
                        modal.find('#submitKepuasanBtn').show();
                        $('.rating-input').prop('disabled', false);
                        $('.rating-option').removeClass('rating-option-disabled').addClass('cursor-pointer').parent().removeClass('cursor-default');
                        modal.find('#statusPenilaian').removeClass('hidden').html('<i class="fas fa-exclamation-triangle mr-1 text-red-600"></i> Anda belum mengisi penilaian untuk sesi ini.');
                    }
                },
                error: function() {
                    alert("Gagal mengambil data kepuasan. Silakan coba lagi.");
                },
                complete: function() {
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
                $('#tab-' + tab).removeClass('border-[#123E44] text-[#123E44] font-semibold')
                                 .addClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
                $('#content-' + tab).addClass('hidden');
            });

            $('#tab-' + type).removeClass('border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300')
                             .addClass('border-[#123E44] text-[#123E44] font-semibold');
            $('#content-' + type).removeClass('hidden');
        }


        $(document).ready(function() {
            const initialTab = '<?= isset($_GET['tab']) && $_GET['tab'] === 'kelompok' ? 'kelompok' : 'individu' ?>';
            switchTab(initialTab);

            $("#kepuasanForm").submit(function(e) {
                e.preventDefault();
                
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
                            const $btn_state = $(`.btn-kepuasan[data-id="${currentSesiId}"][data-type="${currentSesiType}"]`);
                            if ($btn_state.length) {
                                $btn_state.removeClass('bg-[#0F3A3A] hover:bg-[#123E44]').addClass('bg-green-600 hover:bg-green-700').html('<i class="fas fa-check-circle mr-1"></i> Lihat Penilaian');
                            }
                            
                            closeKepuasanModal();
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
            
            $(document).on('change', '.rating-input', function() {
                const name = $(this).attr('name');
                const is_disabled = $(this).prop('disabled');
                
                if (!is_disabled) {
                    $('input[name="' + name + '"]').next('.rating-option').removeClass('bg-[#4C8E89] text-white border-[#0F3A3A] shadow');
                    if (this.checked) {
                         $(this).next('.rating-option').addClass('bg-[#4C8E89] text-white border-[#0F3A3A] shadow');
                    }
                }
            });
        });
    </script>
</head>
<body class="bg-[#F9FAFB] text-gray-800 min-h-screen flex flex-col">

 <header class="flex justify-between items-center px-4 md:px-8 py-3 bg-white shadow-lg relative z-30">
        <a href="dashboard.php" class="flex items-center space-x-2">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-8 w-8">
            <div>
                <strong class="text-base md:text-xl primary-color font-extrabold">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-gray-600">Bimbingan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Beranda</a>
            <a href="data_profiling.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Data Profiling</a>
            <a href="riwayatkonselingsiswa.php" class="primary-color font-bold border-b-2 border-primary-color pb-1 transition underline">Riwayat</a>
            <a href="ganti_password.php" class="text-gray-600 hover:primary-color hover:border-b-2 hover:border-primary-color pb-1 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition text-sm font-semibold shadow-md">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-gray-800 text-2xl p-2 z-40 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20 transition-opacity duration-300" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="fade-slide hidden-transition absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex flex-col text-left text-base border-t border-gray-100">
        <a href="dashboard.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 primary-color bg-gray-100 font-bold transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>

<section class="text-center py-8 md:py-12 primary-bg text-white shadow-xl">
    <h1 class="text-2xl md:text-4xl font-extrabold mb-1">
        <i class="fas fa-history mr-2"></i> Riwayat Konseling Siswa
    </h1>
    <p class="text-gray-200 max-w-4xl mx-auto text-sm md:text-lg px-4">
        Daftar riwayat bimbingan dan konseling yang telah Anda ikuti.
    </p>
</section>


    <main class="flex-1 p-4 md:p-8 w-full">
        <div class="bg-white p-4 md:p-6 rounded-xl shadow-lg">
            <div class="mb-6 border-b pb-4">
                <h2 class="text-3xl font-bold text-gray-800">Riwayat Layanan BK</h2>
                <p class="text-gray-600">Selamat datang, <span class="font-semibold accent-color"><?= htmlspecialchars($siswa_data['nama']) ?></span>!</p>
            </div>

            <div class="mb-6 border-b border-gray-200">
                <ul class="flex flex-wrap -mb-px">
                    <li class="mr-2 flex-grow sm:flex-grow-0">
                        <a href="javascript:void(0)" onclick="switchTab('individu')" id="tab-individu" 
                           class="inline-block w-full text-center p-4 border-b-2 font-medium text-sm rounded-t-lg transition duration-200 
                                  <?php if (!isset($_GET['tab']) || $_GET['tab'] !== 'kelompok'): ?> 
                                      text-[#123E44] border-[#123E44] font-semibold 
                                  <?php else: ?> 
                                      text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300
                                  <?php endif; ?>"> 
                            Konseling Individu (<?= $riwayat_individu_count ?>)
                        </a>
                    </li>
                    <li class="mr-2 flex-grow sm:flex-grow-0">
                        <a href="javascript:void(0)" onclick="switchTab('kelompok')" id="tab-kelompok" 
                           class="inline-block w-full text-center p-4 border-b-2 font-medium text-sm rounded-t-lg transition duration-200 
                                  <?php if (isset($_GET['tab']) && $_GET['tab'] === 'kelompok'): ?> 
                                      text-[#123E44] border-[#123E44] font-semibold 
                                  <?php else: ?> 
                                      text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 
                                  <?php endif; ?>">
                            Konseling Kelompok (<?= $riwayat_kelompok_count ?>)
                        </a>
                    </li>
                </ul>
            </div>

            <div id="content-individu" class="space-y-4">
                <?php if ($riwayat_individu_count > 0): ?> 
                    <?php mysqli_data_seek($result_individu, 0); while ($data = mysqli_fetch_assoc($result_individu)): ?>
                        <div class="p-4 border border-gray-300 rounded-lg shadow-md bg-white hover:shadow-lg transition duration-200">
                            <div class="flex justify-between items-start mb-3 border-b pb-2">
                                <span class="text-sm font-medium text-gray-700 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-calendar-alt mr-1"></i> <?= tgl_indo($data['tanggal_pelaksanaan']) ?>
                                </span>
                                <span class="text-xs font-bold bg-gray-100 text-[#0F3A3A] px-3 py-1 rounded-full">
                                    Pertemuan Ke-<?= htmlspecialchars($data['pertemuan_ke']) ?>
                                </span>
                            </div>

                            <div class="text-sm mb-3">
                                <p class="font-medium text-gray-700">Gejala Awal:</p>
                                <p class="font-semibold text-gray-900 line-clamp-2"><?= htmlspecialchars($data['gejala_nampak']) ?></p>
                            </div>

                            <div class="pt-3 border-t flex justify-end space-x-3">
                                <?php 
                                    $btn_class = empty($data['id_kepuasan']) ? 'bg-[#0F3A3A] hover:bg-[#123E44]' : 'bg-green-600 hover:bg-green-700';
                                    $btn_text = empty($data['id_kepuasan']) ? 'Beri Penilaian' : 'Lihat Penilaian';
                                    $btn_icon = empty($data['id_kepuasan']) ? 'fa-star' : 'fa-check-circle';
                                ?>
                                
                                <button
                                    onclick="openKepuasanModal(<?= $data['id_konseling'] ?>, 'individu')"
                                    class="btn-kepuasan px-4 py-2 <?= $btn_class ?> text-white rounded-lg transition duration-200 font-semibold text-sm"
                                    data-id="<?= $data['id_konseling'] ?>"
                                    data-type="individu">
                                    <i class="fas <?= $btn_icon ?> mr-1"></i> <?= $btn_text ?>
                                </button>
                                
                                
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-6 text-center border border-gray-300 rounded-lg bg-[#F9FAFB]">
                        <i class="fas fa-info-circle text-2xl text-gray-500 mb-2"></i>
                        <p class="text-base text-gray-700 font-medium">
                            Anda belum memiliki riwayat Konseling Individu.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="content-kelompok" class="space-y-4 hidden">
                <?php if ($riwayat_kelompok_count > 0): ?>
                    <?php mysqli_data_seek($result_kelompok, 0); while ($data = mysqli_fetch_assoc($result_kelompok)): ?>
                        <div class="p-4 border border-gray-300 rounded-lg shadow-md bg-white hover:shadow-lg transition duration-200">
                            <div class="flex justify-between items-start mb-3 border-b pb-2">
                                <span class="text-sm font-medium text-gray-700 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                                    <i class="fas fa-calendar-alt mr-1"></i> <?= tgl_indo($data['tanggal_pelaksanaan']) ?>
                                </span>
                                <span class="text-xs font-bold bg-green-100 text-green-800 px-3 py-1 rounded-full">
                                    <i class="fas fa-users mr-1"></i> Total Peserta: <?= $data['total_siswa_kelompok'] ?>
                                </span>
                            </div>

                            <div class="text-sm mb-3">
                                <p class="font-medium text-gray-700">Topik Kelompok:</p>
                                <p class="font-semibold text-gray-900 line-clamp-2"><?= htmlspecialchars($data['topik_masalah']) ?></p>
                            </div>

                            <div class="pt-3 border-t flex justify-end space-x-3">
                                <?php 
                                    $btn_class_k = empty($data['id_kepuasan_kelompok']) ? 'bg-[#0F3A3A] hover:bg-[#123E44]' : 'bg-green-600 hover:bg-green-700';
                                    $btn_text_k = empty($data['id_kepuasan_kelompok']) ? 'Beri Penilaian' : 'Lihat Penilaian';
                                    $btn_icon_k = empty($data['id_kepuasan_kelompok']) ? 'fa-star' : 'fa-check-circle';
                                ?>
                                
                                <button
                                    onclick="openKepuasanModal(<?= $data['id_kelompok'] ?>, 'kelompok')"
                                    class="btn-kepuasan px-4 py-2 <?= $btn_class_k ?> text-white rounded-lg transition duration-200 font-semibold text-sm"
                                    data-id="<?= $data['id_kelompok'] ?>"
                                    data-type="kelompok">
                                    <i class="fas <?= $btn_icon_k ?> mr-1"></i> <?= $btn_text_k ?>
                                </button>
                                
    
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-6 text-center border border-gray-300 rounded-lg bg-[#F9FAFB]">
                        <i class="fas fa-info-circle text-2xl text-gray-500 mb-2"></i>
                        <p class="text-base text-gray-700 font-medium">
                            Anda belum memiliki riwayat Konseling Kelompok.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
    
    <div id="kepuasanModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl transform scale-100 transition-all max-h-[90vh] flex flex-col">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-20 rounded-t-xl">
                <h3 id="kepuasanModalTitle" class="text-xl font-bold text-gray-800">Penilaian Kepuasan Konseling</h3>
                <button onclick="closeKepuasanModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="kepuasanForm" class="flex flex-col flex-grow overflow-hidden">
                <input type="hidden" name="id_sesi" id="id_sesi" value="">
                <input type="hidden" name="sesi_type" id="sesi_type" value="">
                
                <div class="p-6 space-y-6 overflow-y-auto">
                    <div class="mb-4 p-3 rounded-lg border border-[#4C8E89] bg-[#E5F5F4] text-[#0F3A3A] text-sm font-medium">
                        <p class="font-semibold text-base mb-1">Penilaian Konseling <span id="jenisSesiText" class="font-extrabold"></span></p>
                        <p>Berikan penilaian Anda hanya sekali. Penilaian yang sudah diisi tidak dapat diubah lagi. <span class="text-red-600">*Wajib Diisi</span></p>
                    </div>

                    <p id="statusPenilaian" class="text-sm font-medium p-2 bg-gray-100 rounded-md hidden"></p>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-base">1. Aspek Penerimaan Konselor (Kehangatan, Empati)</h4>
                            <div class="flex flex-wrap justify-between gap-2 text-center text-sm font-medium">
                                <input type="radio" name="r1" id="r1_3" value="3" class="rating-input hidden" required>
                                <label for="r1_3" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-smile-beam text-xl mb-1"></i><br>
                                    <span>Sangat Memuaskan</span>
                                </label>
                                <input type="radio" name="r1" id="r1_2" value="2" class="rating-input hidden">
                                <label for="r1_2" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-meh text-xl mb-1"></i><br>
                                    <span>Memuaskan</span>
                                </label>
                                <input type="radio" name="r1" id="r1_1" value="1" class="rating-input hidden">
                                <label for="r1_1" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-frown-open text-xl mb-1"></i><br>
                                    <span>Kurang Memuaskan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-base">2. Aspek Kemudahan Curhat (Keterbukaan, Kenyamanan)</h4>
                            <div class="flex flex-wrap justify-between gap-2 text-center text-sm font-medium">
                                <input type="radio" name="r2" id="r2_3" value="3" class="rating-input hidden" required>
                                <label for="r2_3" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-smile-beam text-xl mb-1"></i><br>
                                    <span>Sangat Memuaskan</span>
                                </label>
                                <input type="radio" name="r2" id="r2_2" value="2" class="rating-input hidden">
                                <label for="r2_2" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-meh text-xl mb-1"></i><br>
                                    <span>Memuaskan</span>
                                </label>
                                <input type="radio" name="r2" id="r2_1" value="1" class="rating-input hidden">
                                <label for="r2_1" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-frown-open text-xl mb-1"></i><br>
                                    <span>Kurang Memuaskan</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-base">3. Aspek Kerahasiaan & Kepercayaan</h4>
                            <div class="flex flex-wrap justify-between gap-2 text-center text-sm font-medium">
                                <input type="radio" name="r3" id="r3_3" value="3" class="rating-input hidden" required>
                                <label for="r3_3" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-smile-beam text-xl mb-1"></i><br>
                                    <span>Sangat Memuaskan</span>
                                </label>
                                <input type="radio" name="r3" id="r3_2" value="2" class="rating-input hidden">
                                <label for="r3_2" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-meh text-xl mb-1"></i><br>
                                    <span>Memuaskan</span>
                                </label>
                                <input type="radio" name="r3" id="r3_1" value="1" class="rating-input hidden">
                                <label for="r3_1" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-frown-open text-xl mb-1"></i><br>
                                    <span>Kurang Memuaskan</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2 text-base">4. Aspek Pemecahan Masalah (Bantuan Solusi)</h4>
                            <div class="flex flex-wrap justify-between gap-2 text-center text-sm font-medium">
                                <input type="radio" name="r4" id="r4_3" value="3" class="rating-input hidden" required>
                                <label for="r4_3" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-smile-beam text-xl mb-1"></i><br>
                                    <span>Sangat Memuaskan</span>
                                </label>
                                <input type="radio" name="r4" id="r4_2" value="2" class="rating-input hidden">
                                <label for="r4_2" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-meh text-xl mb-1"></i><br>
                                    <span>Memuaskan</span>
                                </label>
                                <input type="radio" name="r4" id="r4_1" value="1" class="rating-input hidden">
                                <label for="r4_1" class="rating-option p-3 rounded-xl border border-gray-300 cursor-pointer flex-1 max-w-[140px] hover:bg-gray-100 transition duration-150">
                                    <i class="fas fa-frown-open text-xl mb-1"></i><br>
                                    <span>Kurang Memuaskan</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end bg-gray-50 sticky bottom-0 z-20 rounded-b-xl space-x-3 shadow-inner">
                    <button type="button" onclick="closeKepuasanModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 font-medium transition duration-200 text-sm">
                        <i class="fas fa-times mr-1"></i> Tutup
                    </button>
                    <button type="submit" id="submitKepuasanBtn" class="px-4 py-2 bg-[#4C8E89] text-white rounded-md hover:bg-[#0F3A3A] font-medium transition duration-200 shadow-md text-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="pdfViewerModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-7xl max-h-[90vh] flex flex-col transform scale-100 transition-all">
            
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="pdfIframeTitle" class="text-xl font-semibold text-gray-800">Laporan Konseling</h3>
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
    <footer class="text-center py-4 primary-bg text-white text-xs md:text-sm mt-auto">
    <p>&copy; <?php echo date('Y'); ?> Bimbingan Konseling - SMKN 2 Banjarmasin. All rights reserved.</p>
</footer>
</body>
</html>

