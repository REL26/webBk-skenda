<?php
session_start();
include '../koneksi.php';

if (!isset($_GET['action']) || $_GET['action'] !== 'export_all_cv') {
    die("Invalid access");
}

$tahun_map = [
    "1" => "2024/2025",
    "2" => "2025/2026",
    "3" => "2026/2027",
    "4" => "2027/2028"
];

$where = [];
$where[] = "s.kelas != 'LULUS'";

$filters = [
    "nama" => "s.nama",
    "kelas" => "s.kelas",
    "jurusan" => "s.jurusan",
    "nis" => "s.nis",
    "gender" => "s.jenis_kelamin",
    "tahun" => "s.tahun_ajaran_id"
];

$filter_label = [];

foreach ($filters as $key => $field) {
    if (!empty($_GET[$key])) {
        $val = mysqli_real_escape_string($koneksi, $_GET[$key]);
        $where[] = "$field LIKE '%$val%'";

        if ($key === "tahun" && isset($tahun_map[$val])) {
            $filter_label[] = $tahun_map[$val];
        } else {
            $filter_label[] = strtoupper($key) . "-" . $val;
        }
    }
}

$whereSQL = implode(" AND ", $where);

$folderName = "CV_SISWA";
if (!empty($filter_label)) {
    $folderName .= "_" . implode("_", $filter_label);
}

$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $folderName);

$query = mysqli_query($koneksi, "
    SELECT id_siswa, nama, kelas, jurusan 
    FROM siswa s 
    WHERE $whereSQL
");

if (!$query) die(mysqli_error($koneksi));

$tempDir = __DIR__ . "/temp_pdf/";
if (!is_dir($tempDir)) mkdir($tempDir);

$zipName = $folderName . ".zip";
$zipPath = $tempDir . $zipName;

$zip = new ZipArchive;
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

$nodePath   = "C:\\Program Files\\nodejs\\node.exe"; 
$scriptPath = __DIR__ . "\\export_cv.js";

while ($s = mysqli_fetch_assoc($query)) {

    $id = $s['id_siswa'];

    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $s['nama']);
    $pdfName  = "{$safeName}_{$s['kelas']}_{$s['jurusan']}.pdf";

    $pdfPath  = $tempDir . $pdfName;

    $cmd = "\"$nodePath\" \"$scriptPath\" $id \"$pdfPath\"";
    exec($cmd);

    if (file_exists($pdfPath)) {
        $zip->addFile($pdfPath, $pdfName);
    }
}

$zip->close();

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=$zipName");
header("Content-Length: " . filesize($zipPath));

register_shutdown_function(function() use ($tempDir, $zipPath) {
    foreach (glob($tempDir . "*.pdf") as $f) {
        @unlink($f);
    }
    @unlink($zipPath);
});


readfile($zipPath);

foreach (glob($tempDir . "*.pdf") as $f) unlink($f);
unlink($zipPath);

exit;
?>
