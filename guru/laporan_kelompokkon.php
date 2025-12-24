<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

include '../koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metode tidak diizinkan"]);
    exit;
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

$ids_input = $_POST['selected_student_ids'] ?? '';
if (empty($ids_input)) {
    echo json_encode(["status" => "error", "message" => "Siswa belum dipilih"]);
    exit;
}

$selected_siswa = array_map('intval', explode(',', $ids_input));
$tgl_input = $_POST['tanggal_pelaksanaan'] ?? date('Y-m-d');
$pertemuan_ke = $_POST['pertemuan_ke'] ?? 1;
$tempat = $_POST['tempat'] ?? '-';
$pendekatan = $_POST['pendekatan'] ?? '-';
$waktu_durasi = $_POST['waktu_durasi'] ?? '-';
$teknik_konseling = $_POST['teknik'] ?? '-';
$hasil_layanan = $_POST['hasil_yang_dicapai'] ?? '-';
$nama_guru = $_POST['guru_pembimbing'] ?? '-';

$timestamp = strtotime($tgl_input);
$hari_indo = translateDay(date('l', $timestamp));
$tgl_format_indo = translateMonth(date('d F Y', $timestamp));

$stmt = $koneksi->prepare(
    "INSERT INTO kelompok (tanggal_pelaksanaan, pertemuan_ke, catatan_khusus, tempat, waktu_durasi, nama_guru, hasil_layanan, teknik_konseling) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sissssss", 
    $tgl_input, 
    $pertemuan_ke, 
    $pendekatan, 
    $tempat, 
    $waktu_durasi, 
    $nama_guru,      
    $hasil_layanan,  
    $teknik_konseling
);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan data"]);
    exit;
}

$id_kelompok = $koneksi->insert_id;

foreach ($selected_siswa as $id_siswa) {
    $koneksi->query("INSERT INTO detail_kelompok (id_kelompok, id_siswa) VALUES ($id_kelompok, $id_siswa)");
}

$ids_list = implode(',', $selected_siswa);
$result_siswa = $koneksi->query("
    SELECT nama, kelas, jurusan
    FROM siswa
    WHERE id_siswa IN ($ids_list)
    ORDER BY kelas ASC, nama ASC
");

$siswa_list_html = '';
$kelas_arr = [];

while ($row = $result_siswa->fetch_assoc()) {
    $siswa_list_html .= "<li>{$row['nama']} ({$row['kelas']} - {$row['jurusan']})</li>";
    if (!in_array($row['kelas'], $kelas_arr)) {
        $kelas_arr[] = $row['kelas'];
    }
}

sort($kelas_arr);
$rangkuman_kelas = implode(', ', $kelas_arr);

$nama_kepala_sekolah = 'Novie Bambang Rumadi, S.T., M.Pd';
$tanggal_cetak = translateMonth(date('d F Y'));

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
<tr><td class="label">Kelas</td><td>: ' . $rangkuman_kelas . '</td></tr>
<tr><td class="label">Hari & Tanggal</td><td>: ' . $hari_indo . ', ' . $tgl_format_indo . '</td></tr>
<tr><td class="label">Pertemuan Ke</td><td>: ' . $pertemuan_ke . '</td></tr>
<tr><td class="label">Waktu / Durasi</td><td>: ' . $waktu_durasi . '</td></tr>
<tr><td class="label">Tempat</td><td>: ' . $tempat . '</td></tr>
<tr><td class="label">Pendekatan Konseling</td><td>: ' . nl2br($pendekatan) . '</td></tr>
<tr><td class="label">Teknik Konseling</td><td>: ' . nl2br($teknik_konseling) . '</td></tr>
</table>

<div class="section-title">Hasil yang Dicapai:</div>
<div class="content-box">
' . nl2br($hasil_layanan) . '
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
' . $tempat . ', ' . $tanggal_cetak . '<br>
Guru Bimbingan Konseling<br>
<div class="spacer"></div>
( <u>' . $nama_guru . '</u> )
</td>
</tr>
</table>

</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Times New Roman');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "laporan_kelompok_" . time() . ".pdf";
$dir = __DIR__ . '/../uploads/konseling/';
$path_db = "uploads/konseling/" . $filename;

if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

file_put_contents($dir . $filename, $dompdf->output());

$stmt_r = $koneksi->prepare("INSERT INTO riwayat_kelompok (id_kelompok, file_pdf) VALUES (?, ?)");
$stmt_r->bind_param("is", $id_kelompok, $path_db);
$stmt_r->execute();

echo json_encode([
    "status" => "success",
    "message" => "Laporan berhasil dibuat",
    "pdf_url" => "../" . $path_db
]);
