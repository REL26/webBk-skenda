<?php
session_start();

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');
error_reporting(E_ALL);
ini_set('display_errors', 0);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        echo json_encode([
            "status" => "error",
            "message" => "System Error: " . $error['message'] . " (di " . $error['file'] . " baris " . $error['line'] . ")"
        ]);
        exit;
    }
});

header('Content-Type: application/json');

include '../koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
    exit;
}

// --- FUNGSI GD UNTUK MENYERAGAMKAN UKURAN & RASIO GAMBAR (AGAR TIDAK LONJONG) ---
function resize_crop_image($max_w, $max_h, $source_file, $target_file, $file_ext) {
    $size = @getimagesize($source_file);
    if ($size === false) {
        throw new Exception("Gagal membaca dimensi gambar (file kemungkinan rusak).");
    }
    list($orig_w, $orig_h) = $size;

    if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
        if (!function_exists('imagecreatefromjpeg')) {
            throw new Exception("Dukungan GD untuk JPEG tidak tersedia di server ini.");
        }
        $img = @imagecreatefromjpeg($source_file);
    } elseif ($file_ext == 'png') {
        if (!function_exists('imagecreatefrompng')) {
            throw new Exception("Dukungan GD untuk PNG tidak tersedia di server ini.");
        }
        $img = @imagecreatefrompng($source_file);
    } elseif ($file_ext == 'webp') {
        if (!function_exists('imagecreatefromwebp')) {
            throw new Exception("Dukungan GD untuk WEBP tidak tersedia di server ini.");
        }
        $img = @imagecreatefromwebp($source_file);
    } else {
        throw new Exception("Format gambar '$file_ext' tidak didukung untuk diproses.");
    }

    if (!$img) {
        throw new Exception("Gagal membuka gambar sumber (format file mungkin korup atau tidak sesuai ekstensi).");
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

    $ok = false;
    if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
        $ok = @imagejpeg($new_img, $target_file, 90);
    } elseif ($file_ext == 'png') {
        $ok = @imagepng($new_img, $target_file, 8);
    } elseif ($file_ext == 'webp') {
        $ok = @imagewebp($new_img, $target_file, 90);
    }

    imagedestroy($img);
    imagedestroy($new_img);

    if (!$ok) {
        throw new Exception("Gagal menyimpan hasil resize/crop gambar ke server.");
    }
    return true;
}

function translateDay($dayName) {
    $days = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $days[$dayName] ?? $dayName;
}

function translateMonth($dateString) {
    $bulan = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli',
        'August' => 'Agustus', 'September' => 'September', 'October' => 'Oktober',
        'November' => 'November', 'December' => 'Desember'
    ];
    return strtr($dateString, $bulan);
}

// --- DETEKSI MODE EDIT ---
$id_kelompok_edit = isset($_POST['id_kelompok']) ? intval($_POST['id_kelompok']) : 0;
$is_edit = ($id_kelompok_edit > 0);

$ids_input = $_POST['selected_student_ids'] ?? '';
if (empty($ids_input) && !$is_edit) {
    echo json_encode(["status" => "error", "message" => "Siswa belum dipilih"]);
    exit;
}

// --- VALIDASI DOKUMENTASI ---
$files_to_upload = [];
if (isset($_FILES['dokumentasi']) && !empty($_FILES['dokumentasi']['name'][0])) {
    $total_files = count($_FILES['dokumentasi']['name']);

    if ($total_files > 12) {
        echo json_encode(["status" => "error", "message" => "Maksimal 12 gambar dokumentasi diperbolehkan."]);
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
                echo json_encode(["status" => "error", "message" => "Ukuran gambar '$file_name' melebihi 2 MB."]);
                exit;
            }
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (!in_array($file_ext, $allowed_ext)) {
                echo json_encode(["status" => "error", "message" => "Format gambar '$file_name' tidak diperbolehkan."]);
                exit;
            }
            if (!is_uploaded_file($tmp_name)) {
                echo json_encode(["status" => "error", "message" => "File '$file_name' bukan hasil upload yang sah."]);
                exit;
            }
            $files_to_upload[] = [
                'tmp_name' => $tmp_name,
                'ext' => $file_ext,
                'name' => $file_name
            ];
        } elseif ($file_error !== UPLOAD_ERR_NO_FILE) {
            echo json_encode(["status" => "error", "message" => "Gagal upload file '$file_name' (kode error: $file_error)."]);
            exit;
        }
    }
}

$selected_siswa = !empty($ids_input) ? array_map('intval', explode(',', $ids_input)) : [];
$tgl_input = $_POST['tanggal_pelaksanaan'] ?? date('Y-m-d');
$pertemuan_ke = $_POST['pertemuan_ke'] ?? 1;
$tempat = $_POST['tempat'] ?? 'Ruang BK';
$pendekatan = $_POST['pendekatan'] ?? '-';
$waktu_durasi = $_POST['waktu_durasi'] ?? '-';
$teknik_konseling = $_POST['teknik'] ?? '-';
$hasil_layanan = $_POST['hasil_yang_dicapai'] ?? '-';
$nama_guru = $_POST['guru_pembimbing'] ?? '-';

$deleted_docs_json = $_POST['deleted_docs'] ?? '[]';
$deleted_docs = json_decode($deleted_docs_json, true);
if (!is_array($deleted_docs)) $deleted_docs = [];

$timestamp = strtotime($tgl_input);
$hari_indo = translateDay(date('l', $timestamp));
$tgl_format_indo = translateMonth(date('d F Y', $timestamp));

$uploaded_docs = [];
$id_kelompok = null;

try {

    $id_guru = $_SESSION['id_guru'] ?? null;
    if (empty($id_guru)) {
        throw new Exception("Sesi guru tidak ditemukan (id_guru kosong). Silakan login ulang.");
    }

    $atas_dasar = '';
    $topik_masalah = '';
    $proses_layanan = '';

    // =====================================================
    // MODE EDIT
    // =====================================================
    if ($is_edit) {
        // Pastikan record exists
        $check = $koneksi->prepare("SELECT id_kelompok FROM kelompok WHERE id_kelompok = ?");
        $check->bind_param("i", $id_kelompok_edit);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            throw new Exception("Data konseling kelompok tidak ditemukan.");
        }

        $id_kelompok = $id_kelompok_edit;

        // UPDATE data utama
        $stmt = $koneksi->prepare(
            "UPDATE kelompok SET 
                tanggal_pelaksanaan = ?, 
                pertemuan_ke = ?, 
                catatan_khusus = ?, 
                tempat = ?, 
                waktu_durasi = ?, 
                nama_guru = ?, 
                hasil_layanan = ?, 
                teknik_konseling = ?
            WHERE id_kelompok = ?"
        );
        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query update kelompok: " . $koneksi->error);
        }
        $stmt->bind_param("ssssssssi",
            $tgl_input,
            $pertemuan_ke,
            $pendekatan,
            $tempat,
            $waktu_durasi,
            $nama_guru,
            $hasil_layanan,
            $teknik_konseling,
            $id_kelompok
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui data kelompok: " . $stmt->error);
        }

        // --- HAPUS DOKUMENTASI YANG DITANDAI ---
        $base_dir = dirname(dirname(__FILE__));
        if (!empty($deleted_docs)) {
            foreach ($deleted_docs as $del_path) {
                $del_path_esc = mysqli_real_escape_string($koneksi, $del_path);
                $physical = $base_dir . '/' . str_replace('../', '', $del_path);
                if (file_exists($physical)) {
                    @unlink($physical);
                }
                $koneksi->query("DELETE FROM dokumentasi_kelompok WHERE id_kelompok = $id_kelompok AND file_path = '$del_path_esc'");
            }
        }

        // --- UPLOAD DOKUMENTASI BARU ---
        if (!empty($files_to_upload)) {
            $doc_dir = $base_dir . "/uploads/dokumentasi/kelompok/";
            if (!is_dir($doc_dir)) {
                mkdir($doc_dir, 0777, true);
            }

            foreach ($files_to_upload as $index => $file) {
                $new_file_name = "doc_kelompok_" . $id_kelompok . "_" . time() . "_" . $index . "." . $file['ext'];
                $destination = $doc_dir . $new_file_name;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception("Gagal memindahkan file upload '" . $file['name'] . "'.");
                }

                resize_crop_image(600, 450, $destination, $destination, $file['ext']);

                $db_path = "../uploads/dokumentasi/kelompok/" . $new_file_name;

                $stmt_doc = $koneksi->prepare("INSERT INTO dokumentasi_kelompok (id_kelompok, file_path) VALUES (?, ?)");
                $stmt_doc->bind_param("is", $id_kelompok, $db_path);
                $stmt_doc->execute();

                $uploaded_docs[] = [
                    'physical_path' => $destination,
                    'db_path' => $db_path
                ];
            }
        }

        // Ambil SEMUA dokumentasi yang tersisa untuk PDF
        $uploaded_docs = [];
        $res_all = $koneksi->query("SELECT file_path FROM dokumentasi_kelompok WHERE id_kelompok = $id_kelompok");
        while ($row = $res_all->fetch_assoc()) {
            $phys = $base_dir . '/' . str_replace('../', '', $row['file_path']);
            $uploaded_docs[] = [
                'physical_path' => $phys,
                'db_path' => $row['file_path']
            ];
        }

        // Siswa tetap dari detail_kelompok (tidak diubah)
        $result_siswa = $koneksi->query("
            SELECT s.nama, s.kelas, s.jurusan
            FROM detail_kelompok dk
            JOIN siswa s ON dk.id_siswa = s.id_siswa
            WHERE dk.id_kelompok = $id_kelompok
            ORDER BY s.kelas ASC, s.nama ASC
        ");

    } else {
        // =====================================================
        // MODE CREATE (tetap sama)
        // =====================================================
        $stmt = $koneksi->prepare(
            "INSERT INTO kelompok (id_guru, tanggal_pelaksanaan, pertemuan_ke, catatan_khusus, tempat, waktu_durasi, nama_guru, hasil_layanan, teknik_konseling, atas_dasar, topik_masalah, proses_layanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception("Gagal menyiapkan query kelompok: " . $koneksi->error);
        }
        $stmt->bind_param("isssssssssss",
            $id_guru,
            $tgl_input,
            $pertemuan_ke,
            $pendekatan,
            $tempat,
            $waktu_durasi,
            $nama_guru,
            $hasil_layanan,
            $teknik_konseling,
            $atas_dasar,
            $topik_masalah,
            $proses_layanan
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal menyimpan data kelompok: " . $stmt->error);
        }

        $id_kelompok = $koneksi->insert_id;

        // Upload dokumentasi
        if (!empty($files_to_upload)) {
            $base_dir = dirname(dirname(__FILE__));
            $doc_dir = $base_dir . "/uploads/dokumentasi/kelompok/";

            if (!is_dir($doc_dir)) {
                if (!mkdir($doc_dir, 0777, true) && !is_dir($doc_dir)) {
                    throw new Exception("Gagal membuat folder upload dokumentasi.");
                }
            }

            foreach ($files_to_upload as $index => $file) {
                $new_file_name = "doc_kelompok_" . $id_kelompok . "_" . time() . "_" . $index . "." . $file['ext'];
                $destination = $doc_dir . $new_file_name;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception("Gagal memindahkan file upload '" . $file['name'] . "'.");
                }

                resize_crop_image(600, 450, $destination, $destination, $file['ext']);

                $db_path = "../uploads/dokumentasi/kelompok/" . $new_file_name;

                $stmt_doc = $koneksi->prepare("INSERT INTO dokumentasi_kelompok (id_kelompok, file_path) VALUES (?, ?)");
                $stmt_doc->bind_param("is", $id_kelompok, $db_path);
                $stmt_doc->execute();

                $uploaded_docs[] = [
                    'physical_path' => $destination,
                    'db_path' => $db_path
                ];
            }
        }

        // Simpan relasi siswa
        foreach ($selected_siswa as $id_siswa) {
            $stmt_detail = $koneksi->prepare("INSERT INTO detail_kelompok (id_kelompok, id_siswa) VALUES (?, ?)");
            $stmt_detail->bind_param("ii", $id_kelompok, $id_siswa);
            if (!$stmt_detail->execute()) {
                throw new Exception("Gagal menyimpan relasi siswa (id_siswa=$id_siswa)");
            }
        }

        $ids_list = implode(',', $selected_siswa);
        $result_siswa = $koneksi->query("
            SELECT nama, kelas, jurusan
            FROM siswa
            WHERE id_siswa IN ($ids_list)
            ORDER BY kelas ASC, nama ASC
        ");
    }

    // --- Bangun daftar siswa untuk PDF ---
    $siswa_list_html = '';
    $kelas_arr = [];

    while ($row = $result_siswa->fetch_assoc()) {
        $siswa_list_html .= "<li>" . htmlspecialchars($row['nama']) . " (" . htmlspecialchars($row['kelas']) . " - " . htmlspecialchars($row['jurusan']) . ")</li>";
        if (!in_array($row['kelas'], $kelas_arr)) {
            $kelas_arr[] = $row['kelas'];
        }
    }

    if (empty($siswa_list_html)) {
        throw new Exception("Data siswa tidak ditemukan.");
    }

    sort($kelas_arr);
    $rangkuman_kelas = implode(', ', $kelas_arr);

    $nama_kepala_sekolah = 'Novie Bambang Rumadi, S.T., M.Pd';
    $tanggal_cetak = translateMonth(date('d F Y'));

    // --- HTML PDF ---
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <style>
    body { font-family: "Times New Roman", Times, serif; font-size: 11.5pt; line-height: 1.45; margin: 25px;}
    h2 { text-align: center; font-size: 15.5pt; margin: 0;}
    .kop-line { border-bottom: 2px solid #000; margin: 10px 0 15px;}
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table td { padding: 3px 0; vertical-align: top; }
    .data-table .label { width: 35%; font-weight: bold; }
    .section-title { font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
    .content-box { border: 1px solid #aaa; padding: 8px; min-height: 60px; text-align: justify; }
    .signature-table { width: 100%; margin-top: 40px; }
    .signature-table td { text-align: center; vertical-align: top; }
    .spacer { height: 60px; }
    ol { padding-left: 30px; }
    </style>
    </head>
    <body>

    <h2>LAPORAN PELAKSANAAN LAYANAN</h2>
    <h2>KONSELING KELOMPOK</h2>

    <div class="kop-line"></div>

    <div class="section-title">Nama Konseli:</div>
    <ol>' . $siswa_list_html . '</ol>

    <table class="data-table">
    <tr><td class="label">Kelas</td><td>: ' . htmlspecialchars($rangkuman_kelas) . '</td></tr>
    <tr><td class="label">Hari & Tanggal</td><td>: ' . $hari_indo . ', ' . $tgl_format_indo . '</td></tr>
    <tr><td class="label">Pertemuan Ke</td><td>: ' . htmlspecialchars($pertemuan_ke) . '</td></tr>
    <tr><td class="label">Waktu / Durasi</td><td>: ' . htmlspecialchars($waktu_durasi) . '</td></tr>
    <tr><td class="label">Tempat</td><td>: ' . htmlspecialchars($tempat) . '</td></tr>
    <tr><td class="label">Pendekatan Konseling</td><td>: ' . nl2br(htmlspecialchars($pendekatan)) . '</td></tr>
    <tr><td class="label">Teknik Konseling</td><td>: ' . nl2br(htmlspecialchars($teknik_konseling)) . '</td></tr>
    </table>

    <div class="section-title">Gejala dan Hasil yang Dicapai:</div>
    <div class="content-box">
    ' . nl2br(htmlspecialchars($hasil_layanan)) . '
    </div>

    <table class="signature-table">
    <tr>
    <td width="50%">
    Mengetahui<br>
    Kepala Sekolah<br>
    <div class="spacer"></div>
    ( <u>' . $nama_kepala_sekolah . '</u> )
    </td>
    <td width="50%">
    ' . htmlspecialchars($tempat) . ', ' . $tanggal_cetak . '<br>
    Guru Bimbingan dan Konseling<br>
    <div class="spacer"></div>
    ( <u>' . htmlspecialchars($nama_guru) . '</u> )
    </td>
    </tr>
    </table>

    ';

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

    // --- RENDER PDF ---
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Times New Roman');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf_output = $dompdf->output();
    if (empty($pdf_output)) {
        throw new Exception("Gagal generate PDF (output DomPDF kosong).");
    }

    $dir = __DIR__ . '/../uploads/konseling/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    if ($is_edit) {
        // Ambil path PDF lama → timpa
        $res_pdf = $koneksi->query("SELECT file_pdf FROM riwayat_kelompok WHERE id_kelompok = $id_kelompok");
        $row_pdf = $res_pdf->fetch_assoc();
        if ($row_pdf && !empty($row_pdf['file_pdf'])) {
            $path_db = $row_pdf['file_pdf'];
            // path di DB biasanya "uploads/konseling/xxx.pdf"
            $file_path_physical = __DIR__ . '/../' . ltrim(str_replace('../', '', $path_db), '/');
            $filename = basename($path_db);
        } else {
            $filename = "laporan_kelompok_" . time() . ".pdf";
            $path_db = "uploads/konseling/" . $filename;
            $file_path_physical = $dir . $filename;
        }
    } else {
        $filename = "laporan_kelompok_" . time() . ".pdf";
        $path_db = "uploads/konseling/" . $filename;
        $file_path_physical = $dir . $filename;
    }

    if (file_put_contents($file_path_physical, $pdf_output) === false) {
        throw new Exception("Gagal menyimpan file PDF ke server.");
    }

    if (!$is_edit) {
        $stmt_r = $koneksi->prepare("INSERT INTO riwayat_kelompok (id_kelompok, file_pdf) VALUES (?, ?)");
        $stmt_r->bind_param("is", $id_kelompok, $path_db);
        if (!$stmt_r->execute()) {
            throw new Exception("Gagal menyimpan riwayat laporan ke database.");
        }
    }
    // Mode edit: path di DB tidak diubah, file ditimpa saja.

    echo json_encode([
        "status" => "success",
        "message" => $is_edit ? "Laporan konseling kelompok berhasil diperbarui." : "Laporan berhasil dibuat",
        "pdf_url" => "../" . $path_db
    ]);

} catch (Throwable $e) {

    // Rollback hanya untuk create
    if (!$is_edit) {
        foreach ($uploaded_docs as $doc) {
            if (file_exists($doc['physical_path'])) {
                @unlink($doc['physical_path']);
            }
        }
        if (!empty($id_kelompok)) {
            $koneksi->query("DELETE FROM dokumentasi_kelompok WHERE id_kelompok = " . (int)$id_kelompok);
            $koneksi->query("DELETE FROM detail_kelompok WHERE id_kelompok = " . (int)$id_kelompok);
            $koneksi->query("DELETE FROM kelompok WHERE id_kelompok = " . (int)$id_kelompok);
        }
    }

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}