<?php
session_start();

ini_set('memory_limit', '512M'); 
ini_set('max_execution_time', '300');
error_reporting(E_ALL);
ini_set('display_errors', 0); 

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "System Error: " . $error['message'], 
            "pdf_url" => null
        ]);
        exit;
    }
});

include '../koneksi.php'; 
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan.", "pdf_url" => null]);
    exit;
}

header('Content-Type: application/json');

// --- FUNGSI GD UNTUK MENYERAGAMKAN UKURAN & RASIO GAMBAR (AGAR TIDAK LONJONG) ---
function resize_crop_image($max_w, $max_h, $source_file, $target_file, $file_ext) {
    list($orig_w, $orig_h) = getimagesize($source_file);
    
    if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
        $img = imagecreatefromjpeg($source_file);
    } elseif ($file_ext == 'png') {
        $img = imagecreatefrompng($source_file);
    } elseif ($file_ext == 'webp') {
        $img = imagecreatefromwebp($source_file);
    } else {
        return false;
    }
    
    $target_ratio = $max_w / $max_h;
    $orig_ratio = $orig_w / $orig_h;
    
    if ($orig_ratio > $target_ratio) {
        $h = $orig_h;
        $w = $orig_h * $target_ratio;
        $x = ($orig_w - $w) / 2;
        $y = 0;
    } else {
        $w = $orig_w;
        $h = $orig_w / $target_ratio;
        $x = 0;
        $y = ($orig_h - $h) / 2;
    }
    
    $new_img = imagecreatetruecolor($max_w, $max_h);
    
    if ($file_ext == 'png' || $file_ext == 'webp') {
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        $transparent = imagecolorallocatealpha($new_img, 255, 255, 255, 127);
        imagefilledrectangle($new_img, 0, 0, $max_w, $max_h, $transparent);
    }
    
    imagecopyresampled($new_img, $img, 0, 0, $x, $y, $max_w, $max_h, $w, $h);
    
    if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
        imagejpeg($new_img, $target_file, 90);
    } elseif ($file_ext == 'png') {
        imagepng($new_img, $target_file, 8);
    } elseif ($file_ext == 'webp') {
        imagewebp($new_img, $target_file, 90);
    }
    
    imagedestroy($img);
    imagedestroy($new_img);
    return true;
}

// --- VALIDASI DOKUMENTASI ---
$files_to_upload = [];
if (isset($_FILES['dokumentasi']) && !empty($_FILES['dokumentasi']['name'][0])) {
    $total_files = count($_FILES['dokumentasi']['name']);
    
    if ($total_files > 12) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Maksimal 12 gambar dokumentasi diperbolehkan.", "pdf_url" => null]);
        exit;
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    
    for ($i = 0; $i < $total_files; $i++) {
        $tmp_name = $_FILES['dokumentasi']['tmp_name'][$i];
        $file_name = $_FILES['dokumentasi']['name'][$i];
        $file_size = $_FILES['dokumentasi']['size'][$i];
        $file_error = $_FILES['dokumentasi']['error'][$i];

        if ($file_error === UPLOAD_ERR_OK) {
            if ($file_size > 2 * 1024 * 1024) { 
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Ukuran gambar '$file_name' melebihi 2 MB.", "pdf_url" => null]);
                exit;
            }
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Format gambar '$file_name' tidak diperbolehkan.", "pdf_url" => null]);
                exit;
            }
            $files_to_upload[] = [
                'tmp_name' => $tmp_name,
                'ext' => $file_ext
            ];
        }
    }
}

// --- AMBIL VARIABEL INPUT ---
$id_konseling_edit   = isset($_POST['id_konseling']) ? intval($_POST['id_konseling']) : 0;
$is_edit             = ($id_konseling_edit > 0);

$id_siswa            = mysqli_real_escape_string($koneksi, $_POST['id_siswa'] ?? '');
$no_input            = mysqli_real_escape_string($koneksi, $_POST['no_input'] ?? '');
$tanggal_pelaksanaan = mysqli_real_escape_string($koneksi, $_POST['tanggal_pelaksanaan'] ?? '');
$waktu_durasi        = mysqli_real_escape_string($koneksi, $_POST['waktu_durasi'] ?? '');
$tempat              = mysqli_real_escape_string($koneksi, $_POST['tempat'] ?? '');
$atas_dasar          = mysqli_real_escape_string($koneksi, $_POST['atas_dasar'] ?? '');
$pertemuan_ke        = mysqli_real_escape_string($koneksi, $_POST['pertemuan_ke'] ?? '');
$panggilan_ke        = mysqli_real_escape_string($koneksi, $_POST['panggilan_ke'] ?? '');
$gejala_nampak       = mysqli_real_escape_string($koneksi, $_POST['gejala_nampak'] ?? '');
$pendekatan          = mysqli_real_escape_string($koneksi, $_POST['pendekatan_konseling'] ?? '');
$teknik              = mysqli_real_escape_string($koneksi, $_POST['teknik_konseling'] ?? '');
$hasil_dicapai       = mysqli_real_escape_string($koneksi, $_POST['hasil_dicapai'] ?? '');
$status_konseling    = mysqli_real_escape_string($koneksi, $_POST['status_konseling'] ?? ''); 
$nama_guru_input     = mysqli_real_escape_string($koneksi, $_POST['nama_guru'] ?? '');
$nip_guru_bk_input   = mysqli_real_escape_string($koneksi, $_POST['nip_guru_bk'] ?? '');
$bidang_layanan      = mysqli_real_escape_string($koneksi, $_POST['bidang_layanan'] ?? '');

$deleted_docs_json   = $_POST['deleted_docs'] ?? '[]';
$deleted_docs        = json_decode($deleted_docs_json, true);
if (!is_array($deleted_docs)) $deleted_docs = [];

$siswa = mysqli_query($koneksi, "SELECT nis, nama, kelas, jurusan FROM siswa WHERE id_siswa = '$id_siswa'");
$d = mysqli_fetch_assoc($siswa);

if (!$d) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Data siswa tidak ditemukan di database.", "pdf_url" => null]);
    exit;
}

// =====================================================
// MODE EDIT
// =====================================================
if ($is_edit) {
    // Pastikan record exists & milik siswa yang sama
    $check = mysqli_query($koneksi, "SELECT id_konseling, id_siswa FROM konseling_individu WHERE id_konseling = '$id_konseling_edit' AND id_siswa = '$id_siswa'");
    if (mysqli_num_rows($check) === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Data konseling tidak ditemukan.", "pdf_url" => null]);
        exit;
    }

    // UPDATE data utama
    $query = "UPDATE konseling_individu SET 
        tanggal_pelaksanaan = '$tanggal_pelaksanaan',
        waktu_durasi = '$waktu_durasi',
        tempat = '$tempat',
        gejala_nampak = '$gejala_nampak',
        pendekatan_konseling = '$pendekatan',
        teknik_konseling = '$teknik',
        hasil_dicapai = '$hasil_dicapai',
        status_konseling = '$status_konseling',
        nama_guru = '$nama_guru_input',
        atas_dasar = '$atas_dasar',
        bidang_layanan = '$bidang_layanan'
        WHERE id_konseling = '$id_konseling_edit'";

    if (!mysqli_query($koneksi, $query)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui data konseling: " . mysqli_error($koneksi), "pdf_url" => null]);
        exit;
    }

    $id_konseling = $id_konseling_edit;

    // --- HAPUS DOKUMENTASI YANG DITANDAI ---
    $base_dir = dirname(dirname(__FILE__));
    if (!empty($deleted_docs)) {
        foreach ($deleted_docs as $del_path) {
            $del_path = mysqli_real_escape_string($koneksi, $del_path);
            // Hapus fisik
            $physical = $base_dir . '/' . str_replace('../', '', $del_path);
            if (file_exists($physical)) {
                @unlink($physical);
            }
            // Hapus dari DB
            mysqli_query($koneksi, "DELETE FROM dokumentasi_konseling WHERE id_konseling = '$id_konseling' AND file_path = '$del_path'");
        }
    }

    // --- UPLOAD DOKUMENTASI BARU (jika ada) ---
    $uploaded_docs = [];
    if (!empty($files_to_upload)) {
        $doc_dir = $base_dir . "/uploads/dokumentasi/individu/";
        
        if (!is_dir($doc_dir)) {
            mkdir($doc_dir, 0777, true);
        }
        
        foreach ($files_to_upload as $index => $file) {
            $new_file_name = "doc_" . $id_konseling . "_" . time() . "_" . $index . "." . $file['ext'];
            $destination = $doc_dir . $new_file_name;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                resize_crop_image(600, 450, $destination, $destination, $file['ext']);
                
                $db_path = "../uploads/dokumentasi/individu/" . $new_file_name;
                $uploaded_docs[] = [
                    'physical_path' => $destination,
                    'db_path' => $db_path
                ];
                mysqli_query($koneksi, "INSERT INTO dokumentasi_konseling (id_konseling, file_path) VALUES ('$id_konseling', '$db_path')");
            }
        }
    }

    // Ambil SEMUA dokumentasi yang tersisa (untuk PDF)
    $uploaded_docs = []; // reset, ambil ulang dari DB
    $res_all_docs = mysqli_query($koneksi, "SELECT file_path FROM dokumentasi_konseling WHERE id_konseling = '$id_konseling'");
    while ($row = mysqli_fetch_assoc($res_all_docs)) {
        $phys = $base_dir . '/' . str_replace('../', '', $row['file_path']);
        $uploaded_docs[] = [
            'physical_path' => $phys,
            'db_path' => $row['file_path']
        ];
    }

} else {
    // =====================================================
    // MODE CREATE (tetap sama seperti sebelumnya)
    // =====================================================
    $query = "INSERT INTO konseling_individu 
    (id_siswa, no_input, tanggal_pelaksanaan, waktu_durasi, tempat, pertemuan_ke, panggilan_ke, gejala_nampak, pendekatan_konseling, teknik_konseling, hasil_dicapai, status_konseling, nama_guru, atas_dasar, bidang_layanan, created_at) 
    VALUES 
    ('$id_siswa', '$no_input', '$tanggal_pelaksanaan', '$waktu_durasi', '$tempat', '$pertemuan_ke', '$panggilan_ke', '$gejala_nampak', '$pendekatan', '$teknik', '$hasil_dicapai', '$status_konseling', '$nama_guru_input', '$atas_dasar', '$bidang_layanan', NOW())";

    if (!mysqli_query($koneksi, $query)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan data konseling: " . mysqli_error($koneksi), "pdf_url" => null]);
        exit;
    }
    $id_konseling = mysqli_insert_id($koneksi);

    // --- UPLOAD, CROP/RESIZE DENGAN GD, DAN SIMPAN KE DB ---
    $uploaded_docs = [];
    if (!empty($files_to_upload)) {
        $base_dir = dirname(dirname(__FILE__));
        $doc_dir = $base_dir . "/uploads/dokumentasi/individu/";
        
        if (!is_dir($doc_dir)) {
            mkdir($doc_dir, 0777, true);
        }
        
        foreach ($files_to_upload as $index => $file) {
            $new_file_name = "doc_" . $id_konseling . "_" . time() . "_" . $index . "." . $file['ext'];
            $destination = $doc_dir . $new_file_name;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                resize_crop_image(600, 450, $destination, $destination, $file['ext']);
                
                $db_path = "../uploads/dokumentasi/individu/" . $new_file_name;
                $uploaded_docs[] = [
                    'physical_path' => $destination,
                    'db_path' => $db_path
                ];
                mysqli_query($koneksi, "INSERT INTO dokumentasi_konseling (id_konseling, file_path) VALUES ('$id_konseling', '$db_path')");
            }
        }
    }
}

if (!function_exists('tgl_indo')) {
    function tgl_indo($tanggal, $include_day = true){
        $bulan = array (
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );
        $hari_indo = array(
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        );
        $pecahkan = explode('-', $tanggal);
        $output = $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
        
        if ($include_day) {
            $hari = date('l', strtotime($tanggal));
            return $hari_indo[$hari] . ', ' . $output;
        }
        return $output;
    }
}

$hari_tanggal_pelaksanaan = tgl_indo($tanggal_pelaksanaan);
$tanggal_cetak_lokal = tgl_indo(date("Y-m-d"), false); 

// --- MENYUSUN HTML ---
$html = "
<html>
<head>
    <style>
        body { font-family: 'Times New Roman', Times, serif; margin: 25px; font-size: 11.5pt; line-height: 1.45; color: #000; }
        h2 { text-align: center; font-size: 15.5pt; margin: 0; padding: 0; font-weight: bold; text-transform: uppercase; }
        .title-wrapper { margin-bottom: 10px; }
        .kop-line { border-bottom: 2.5pt solid #000; padding-top: 3px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        .data-table td { padding: 3px 0; vertical-align: top; }
        .data-table .label { font-weight: bold; width: 35%; } 
        .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 3px; font-size: 12pt; }
        .content-box { border: 1px solid #aaa; padding: 8px; min-height: 100px; white-space: pre-wrap; background: #fff; text-align: justify; }
        .signature-table { margin-top: 35px; }
        .signature-table td { text-align: center; padding-top: 25px; vertical-align: top; width: 50%; }
        .signature-table .nip-text { display: block; margin-top: 5px; font-size: 11pt; }
        .spacer { height: 60px; }
        .underline-name { text-decoration: underline;}
    </style>
</head>
<body>

<div class='title-wrapper'>
    <h2>LAPORAN PELAKSANAAN LAYANAN</h2>
    <h2>KONSELING INDIVIDUAL</h2>
</div>
<div class='kop-line'></div>

<table class='data-table'>
    <tr><td class='label'>Nama Siswa</td><td>: " . htmlspecialchars($d['nama']) . "</td></tr>
    <tr><td class='label'>Kelas / Jurusan</td><td>: " . htmlspecialchars($d['kelas']) . " " . htmlspecialchars($d['jurusan']) . "</td></tr>
    <tr><td class='label'>NIS</td><td>: " . htmlspecialchars($d['nis']) . "</td></tr>
    <tr><td class='label'>Hari & Tanggal Pelaksanaan</td><td>: " . $hari_tanggal_pelaksanaan . "</td></tr>
    <tr><td class='label'>Pertemuan Ke-</td><td>: " . htmlspecialchars($pertemuan_ke) . "</td></tr>
    <tr><td class='label'>Waktu / Durasi</td><td>: " . htmlspecialchars($waktu_durasi) . "</td></tr>
    <tr><td class='label'>Tempat</td><td>: " . htmlspecialchars($tempat) . "</td></tr>
    <tr><td class='label'>Bidang Layanan</td><td>: " . htmlspecialchars(str_replace(',', ', ', $bidang_layanan)) . "</td></tr>
    <tr><td class='label'>Teknik Pendekatan</td><td>: " . htmlspecialchars($pendekatan) . "</td></tr>
    <tr><td class='label'>Teknik Konseling</td><td>: " . htmlspecialchars($teknik) . "</td></tr>
</table>

<div class='section-title'>Hasil yang Dicapai:</div>
<div class='content-box' style='min-height: 100px;'>" . htmlspecialchars($hasil_dicapai) . "</div>

<table class='signature-table'>
    <tr>
        <td>
            Mengetahui<br>Kepala Sekolah<br>
            <div class='spacer'></div>
            <span class='underline-name'>( Novie Bambang Rumadi, S.T., M.Pd )</span>
        </td>
        <td>
            Banjarmasin, $tanggal_cetak_lokal<br>Guru Bimbingan dan Konseling<br>
            <div class='spacer'></div>
            <span class='underline-name'>( " . htmlspecialchars($nama_guru_input) . " )</span><br>
            " . (!empty($nip_guru_bk_input) ? '<span class="nip-text">NIP: ' . htmlspecialchars($nip_guru_bk_input) . '</span>' : '') . "
        </td>
    </tr>
</table>
";

// --- TAMPILAN DOKUMENTASI ---
if (count($uploaded_docs) > 0) {
    $html .= "<div style='page-break-before: always;'></div>";
    $html .= "<div class='title-wrapper' style='margin-bottom: 20px;'><h2>DOKUMENTASI KEGIATAN</h2></div>";
    
    $html .= "<table style='width: 100%; border-collapse: separate; border-spacing: 15px 22px; table-layout: fixed;'><tr>";
    
    foreach ($uploaded_docs as $index => $doc) {
        if ($index > 0 && $index % 3 == 0) { $html .= "</tr><tr>"; }
        
        $img_src = '';
        if (file_exists($doc['physical_path'])) {
            $image_data = file_get_contents($doc['physical_path']);
            $file_ext = pathinfo($doc['physical_path'], PATHINFO_EXTENSION);
            if ($file_ext === 'jpg') $file_ext = 'jpeg';
            $base64_data = base64_encode($image_data);
            $img_src = 'data:image/' . $file_ext . ';base64,' . $base64_data;
        }

        $html .= "<td style='width: 33.33%; text-align: center; vertical-align: middle; padding: 0;'>";
        $html .= "    <div style='background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 10px; text-align: center;'>";
        $html .= "        <img src='" . $img_src . "' style='width: 100%; height: 135px; border-radius: 4px; display: block; margin: 0 auto;' />";
        $html .= "    </div></td>";
    }
    
    $remaining = count($uploaded_docs) % 3;
    if ($remaining > 0) {
        for ($j = 0; $j < (3 - $remaining); $j++) { $html .= "<td style='width: 33.33%;'></td>"; }
    }
    
    $html .= "</tr></table>";
}

$html .= "</body></html>";

// --- RENDER PDF & GENERATE URL ---
$base_path = dirname(dirname(__FILE__));
$upload_dir_physical = $base_path . "/uploads/konseling/"; 

if (!is_dir($upload_dir_physical)) { mkdir($upload_dir_physical, 0777, true); }

if ($is_edit) {
    // Ambil path PDF lama agar di-overwrite
    $res_pdf = mysqli_query($koneksi, "SELECT file_pdf FROM riwayat_konseling WHERE id_konseling = '$id_konseling'");
    $row_pdf = mysqli_fetch_assoc($res_pdf);
    if ($row_pdf && !empty($row_pdf['file_pdf'])) {
        $file_path_db = $row_pdf['file_pdf'];
        $file_path_physical = $base_path . '/' . str_replace('../', '', $file_path_db);
        $filename = basename($file_path_db);
    } else {
        // fallback jika tidak ada (seharusnya tidak terjadi)
        $filename = "konseling_individu_" . $d['nis'] . "_" . $id_konseling . ".pdf";
        $file_path_physical = $upload_dir_physical . $filename;
        $file_path_db = "../uploads/konseling/" . $filename;
    }
} else {
    $filename = "konseling_individu_" . $d['nis'] . "_" . $id_konseling . ".pdf";
    $file_path_physical = $upload_dir_physical . $filename;
    $file_path_db = "../uploads/konseling/" . $filename;
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
$script_dir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\'); 
$pdf_url = $base_url . $script_dir . "/uploads/konseling/" . $filename;

try {
    $options = new Options();
    $options->set('defaultFont', 'Times New Roman');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('tempDir', sys_get_temp_dir());

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($file_path_physical, $dompdf->output());

    if (!$is_edit) {
        $query_riwayat = "INSERT INTO riwayat_konseling (id_konseling, id_siswa, file_pdf) VALUES ('$id_konseling', '$id_siswa', '$file_path_db')";
        if (!mysqli_query($koneksi, $query_riwayat)) {
            throw new Exception("Gagal menyimpan riwayat PDF ke database.");
        }
    }
    // Pada mode edit: path PDF di DB tidak diubah, file ditimpa saja.

    echo json_encode([
        "status" => "success",
        "message" => $is_edit ? "Laporan konseling individu berhasil diperbarui." : "Laporan konseling dan dokumentasi berhasil disimpan.",
        "pdf_url" => $pdf_url
    ]);

} catch (Throwable $e) { 
    if (!$is_edit) {
        mysqli_query($koneksi, "DELETE FROM konseling_individu WHERE id_konseling = '$id_konseling'");
    }
    
    http_response_code(500); 
    echo json_encode([
        "status" => "error", 
        "message" => "Terjadi kesalahan saat memproses File/PDF: " . $e->getMessage(), 
        "pdf_url" => null
    ]);
}
?>