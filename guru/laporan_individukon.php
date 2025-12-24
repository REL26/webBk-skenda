<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);


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

$id_siswa            = mysqli_real_escape_string($koneksi, $_POST['id_siswa']);
$no_input            = mysqli_real_escape_string($koneksi, $_POST['no_input']);
$tanggal_pelaksanaan = mysqli_real_escape_string($koneksi, $_POST['tanggal_pelaksanaan']);
$waktu_durasi        = mysqli_real_escape_string($koneksi, $_POST['waktu_durasi']);
$tempat              = mysqli_real_escape_string($koneksi, $_POST['tempat']);
$atas_dasar          = mysqli_real_escape_string($koneksi, $_POST['atas_dasar']);
$pertemuan_ke        = mysqli_real_escape_string($koneksi, $_POST['pertemuan_ke']);
$panggilan_ke        = mysqli_real_escape_string($koneksi, $_POST['panggilan_ke']);
$gejala_nampak       = mysqli_real_escape_string($koneksi, $_POST['gejala_nampak']);
$pendekatan          = mysqli_real_escape_string($koneksi, $_POST['pendekatan_konseling']);
$teknik              = mysqli_real_escape_string($koneksi, $_POST['teknik_konseling']);
$hasil_dicapai       = mysqli_real_escape_string($koneksi, $_POST['hasil_dicapai']);
$status_konseling    = mysqli_real_escape_string($koneksi, $_POST['status_konseling']); 
$nama_guru_input     = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
$nip_guru_bk_input   = mysqli_real_escape_string($koneksi, $_POST['nip_guru_bk']);
$query = "INSERT INTO konseling_individu 
(id_siswa, no_input, tanggal_pelaksanaan, waktu_durasi, tempat, pertemuan_ke, panggilan_ke, gejala_nampak, pendekatan_konseling, teknik_konseling, hasil_dicapai, status_konseling, nama_guru, atas_dasar, created_at) 
VALUES 
('$id_siswa', '$no_input', '$tanggal_pelaksanaan', '$waktu_durasi', '$tempat', '$pertemuan_ke', '$panggilan_ke', '$gejala_nampak', '$pendekatan', '$teknik', '$hasil_dicapai', '$status_konseling', '$nama_guru_input', '$atas_dasar', NOW())";
if (!mysqli_query($koneksi, $query)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan data konseling: " . mysqli_error($koneksi), "pdf_url" => null]);
    exit;
}
$id_konseling = mysqli_insert_id($koneksi);

$siswa = mysqli_query($koneksi, "SELECT nis, nama, kelas, jurusan FROM siswa WHERE id_siswa = '$id_siswa'");
$d = mysqli_fetch_assoc($siswa);
function tgl_indo($tanggal, $include_day = true){
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
    $hari_indo = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );
	$pecahkan = explode('-', $tanggal);
    $output = $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
    
    if ($include_day) {
        $hari = date('l', strtotime($tanggal));
        return $hari_indo[$hari] . ', ' . $output;
    }
	return $output;
}

$hari_tanggal_pelaksanaan = tgl_indo($tanggal_pelaksanaan);
$nama_guru = htmlspecialchars($nama_guru_input);
$tanggal_cetak_lokal = tgl_indo(date("Y-m-d"), false); 
$nama_kepala_sekolah = 'Novie Bambang Rumadi, S.T., M.Pd'; 
$nip_kepala_sekolah = '___________________________'; 


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
        .colon { width: 15px; }
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
    
    <tr><td class='label'>Teknik Pendekatan</td><td>: " . htmlspecialchars($pendekatan) . "</td></tr>
    <tr><td class='label'>Teknik Konseling</td><td>: " . htmlspecialchars($teknik) . "</td></tr>
</table>

<div class='section-title'>Hasil yang Dicapai:</div>
<div class='content-box' style='min-height: 100px;'>" . htmlspecialchars($hasil_dicapai) . "</div>


<table class='signature-table'>
    <tr>
        <td>
            Mengetahui<br>
            Kepala Sekolah<br>
            <div class='spacer'></div>
            <span class='underline-name'>( Novie Bambang Rumadi, S.T., M.Pd )</span>
        </td>
        <td>
            Banjarmasin, $tgl_cetak<br>
            Guru Bimbingan dan Konseling<br>
            <div class='spacer'></div>
            <span class='underline-name'>( " . htmlspecialchars($nama_guru_input) . " )</span><br>
            " . (!empty($nip_guru_bk_input) ? '<span class="nip-text">NIP: ' . htmlspecialchars($nip_guru_bk_input) . '</span>' : '') . "
        </td>
    </tr>
</table>


</body>
</html>
";


$filename = "konseling_individu_" . $d['nis'] . "_" . $id_konseling . ".pdf";

$base_path = dirname(dirname(__FILE__));
$upload_dir_physical = $base_path . "/uploads/konseling/"; 

if (!is_dir($upload_dir_physical)) {
    mkdir($upload_dir_physical, 0777, true);
}

$file_path_physical = $upload_dir_physical . $filename;
$file_path_db = "../uploads/konseling/" . $filename; 
$pdf_url = dirname(dirname($_SERVER['PHP_SELF'])) . "/uploads/konseling/" . $filename;

$options = new Options();
$options->set('defaultFont', 'Times New Roman');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('tempDir', sys_get_temp_dir());

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

try {
    file_put_contents($file_path_physical, $dompdf->output());
} catch (Exception $e) {
    mysqli_query($koneksi, "DELETE FROM konseling_individu WHERE id_konseling = '$id_konseling'");
    http_response_code(500); 
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan PDF (DOMPDF Error): " . $e->getMessage(), "pdf_url" => null]);
    exit;
}

$query_riwayat = "
INSERT INTO riwayat_konseling (id_konseling, id_siswa, file_pdf)
VALUES ('$id_konseling', '$id_siswa', '$file_path_db')
";

if (!mysqli_query($koneksi, $query_riwayat)) {
    mysqli_query($koneksi, "DELETE FROM konseling_individu WHERE id_konseling = '$id_konseling'");
    http_response_code(500); 
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan riwayat PDF: " . mysqli_error($koneksi), "pdf_url" => null]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Laporan konseling berhasil disimpan dan PDF dibuat.",
    "pdf_url" => $pdf_url
]);
?>