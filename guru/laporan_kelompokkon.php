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

try {
    $koneksi->begin_transaction();

    $id_guru             = isset($_POST['id_guru']) ? mysqli_real_escape_string($koneksi, $_POST['id_guru']) : ($_SESSION['id_guru'] ?? null);
    
    if (!isset($_POST['selected_student_ids']) || empty($_POST['selected_student_ids'])) {
        throw new Exception("ID Siswa terpilih (selected_student_ids) tidak ditemukan atau kosong. Pastikan Anda memilih minimal 2 siswa.");
    }
    
    $student_ids_string  = mysqli_real_escape_string($koneksi, $_POST['selected_student_ids']); 
    
    if (empty($id_guru)) {
        throw new Exception("ID Guru tidak ditemukan. Harap login ulang.");
    }

    $tanggal_pelaksanaan = mysqli_real_escape_string($koneksi, $_POST['tanggal_pelaksanaan']);
    $waktu_durasi        = mysqli_real_escape_string($koneksi, $_POST['waktu_durasi']);
    $tempat              = mysqli_real_escape_string($koneksi, $_POST['tempat']);
    $pertemuan_ke        = mysqli_real_escape_string($koneksi, $_POST['pertemuan_ke']);
    $status_layanan      = mysqli_real_escape_string($koneksi, $_POST['status_layanan']);
    $jenis_layanan       = mysqli_real_escape_string($koneksi, $_POST['jenis_layanan']);
    $nama_guru           = mysqli_real_escape_string($koneksi, $_POST['nama_guru']);
    $nip_guru_bk         = mysqli_real_escape_string($koneksi, $_POST['nip_guru_bk']);
    $proses_layanan      = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']); 
    $hasil_layanan       = mysqli_real_escape_string($koneksi, $_POST['hasil_dicapai']);
    $catatan_khusus      = mysqli_real_escape_string($koneksi, $_POST['pendekatan_konseling']); 
    $teknik_konseling    = mysqli_real_escape_string($koneksi, $_POST['teknik_konseling']); 

    if (empty($tanggal_pelaksanaan) || empty($hasil_layanan) || empty($catatan_khusus) || empty($teknik_konseling)) {
        throw new Exception("Data form (Tanggal, Masalah/Gejala, Hasil yang dicapai, Pendekatan, atau Teknik Konseling) tidak lengkap. Harap isi semua kolom wajib.");
    }

    $id_siswas = array_filter(array_map('intval', explode(',', $student_ids_string)));
    if (count($id_siswas) < 2) {
        throw new Exception("Laporan Konseling Kelompok membutuhkan minimal 2 siswa.");
    }

    $query_kelompok = "
        INSERT INTO kelompok (
            id_guru, tanggal_pelaksanaan, waktu_durasi, tempat, pertemuan_ke, 
            proses_layanan, hasil_layanan, status_layanan, 
            jenis_layanan, catatan_khusus, nama_guru, nip_guru_bk, teknik_konseling
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt_kelompok = $koneksi->prepare($query_kelompok);
    $stmt_kelompok->bind_param(
        "issssississss", 
        $id_guru, $tanggal_pelaksanaan, $waktu_durasi, $tempat, $pertemuan_ke, 
        $proses_layanan, $hasil_layanan, $status_layanan, 
        $jenis_layanan, $catatan_khusus, $nama_guru, $nip_guru_bk, $teknik_konseling
    );
    
    if (!$stmt_kelompok->execute()) {
        throw new Exception("Gagal menyimpan data kelompok: " . $stmt_kelompok->error);
    }
    $id_kelompok = $stmt_kelompok->insert_id;
    $stmt_kelompok->close();
    
    $tahun = date('y', strtotime($tanggal_pelaksanaan));
    $bulan = date('m', strtotime($tanggal_pelaksanaan));
    $no_laporan_display = "BK-KL-" . $tahun . $bulan . "-" . str_pad($id_kelompok, 3, '0', STR_PAD_LEFT);


    $query_detail = "INSERT INTO detail_kelompok (id_kelompok, id_siswa) VALUES (?, ?)";
    $stmt_detail = $koneksi->prepare($query_detail);
    
    foreach ($id_siswas as $id_siswa) {
        $stmt_detail->bind_param("ii", $id_kelompok, $id_siswa);
        if (!$stmt_detail->execute()) {
            throw new Exception("Gagal menyimpan detail siswa ID: $id_siswa. Error: " . $stmt_detail->error);
        }
    }
    $stmt_detail->close();

    $ids_list = implode(',', $id_siswas);
    $query_siswa = "
        SELECT 
            s.nama, 
            s.kelas, 
            s.jurusan
        FROM 
            siswa s
        WHERE 
            s.id_siswa IN ($ids_list)
        ORDER BY s.kelas ASC, s.nama ASC
    ";
    $result_siswa = mysqli_query($koneksi, $query_siswa);
    $siswa_data = [];
    $kelas_terlibat = [];
    while ($row = mysqli_fetch_assoc($result_siswa)) {
        $siswa_data[] = $row;
        if (!in_array($row['kelas'], $kelas_terlibat)) {
            $kelas_terlibat[] = $row['kelas'];
        }
    }
    
    sort($kelas_terlibat);
    $rangkuman_kelas = implode(', ', $kelas_terlibat); 

    
    $siswa_list_html = '';
    // Penomoran otomatis oleh <ol>
    foreach ($siswa_data as $konseli) {
        $siswa_list_html .= "
            <li>
                " . htmlspecialchars($konseli['nama']) . " 
                (" . htmlspecialchars($konseli['kelas']) . " - " . htmlspecialchars($konseli['jurusan']) . ")
            </li>
        ";
    }
    
    $hari_pelaksanaan = translateDay(date('l', strtotime($tanggal_pelaksanaan)));
    $tanggal_indo_full = translateMonth(date('d F Y', strtotime($tanggal_pelaksanaan)));
    
    // Data untuk Tanda Tangan
    $nama_kepala_sekolah = 'Novie Bambang Rumadi, S.T., M.Pd';
    $tanggal_cetak_lokal = translateMonth(date('d F Y'));

    $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Laporan Konseling Kelompok</title>
            <style>
                body { font-family: "Times New Roman", Times, serif; font-size: 11.5pt; line-height: 1.45; margin: 25px;}
                h2 { text-align: center; font-size: 15.5pt; margin: 0; padding: 0;}
                .title-wrapper { margin-bottom: 10px; }
                .kop-line { border-bottom: 2px solid #000; padding-top: 3px; margin-bottom: 15px; }

                /* Data Table Styling */
                .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
                .data-table td { padding: 3px 0; vertical-align: top; }
                .data-table .label { font-weight: bold; width: 35%; } 

                .section-title { 
                    font-weight: bold; 
                    margin-top: 15px;
                    margin-bottom: 3px;
                    font-size: 12pt;
                }

                .content-box { 
                    border: 1px solid #aaa; 
                    padding: 8px; 
                    min-height: 60px;
                    white-space: pre-wrap;
                    background: #fff;
                    text-align: justify;
                }

                /* Tanda tangan */
                .signature-table { 
                    width: 100%;
                    margin-top: 35px;
                    border-collapse: collapse;
                }
                .signature-table td { 
                    text-align: center;
                    padding-top: 25px;
                    vertical-align: top;
                }
                .spacer { height: 60px; }

                ol.konseli-list { margin-top: 5px; margin-bottom: 5px; padding-left: 30px; }
                ol.konseli-list li { margin-bottom: 2px; }
            </style>
        </head>
        <body>
        
        <div class="title-wrapper">
            <h2>RENCANA PELAKSANAAN LAYANAN</h2>
            <h2>KONSELING KELOMPOK</h2>
        </div>
        
        <div class="kop-line"></div>
        
        <div class="section-title">Nama Konseli:</div>
        <ol class="konseli-list" style="list-style-type: decimal;">
            ' . $siswa_list_html . '
        </ol>

        <table class="data-table">
            <tr>
                <td class="label">Kelas</td>
                <td>: ' . htmlspecialchars($rangkuman_kelas) . '</td>
            </tr>
            <tr>
                <td class="label">Hari & Tanggal Pelaksanaan</td>
                <td>: ' . htmlspecialchars($hari_pelaksanaan) . ', ' . $tanggal_indo_full . '</td>
            </tr>
            <tr>
                <td class="label">Pertemuan Ke-</td>
                <td>: ' . htmlspecialchars($pertemuan_ke) . '</td>
            </tr>
            <tr>
                <td class="label">Waktu / Durasi</td>
                <td>: ' . htmlspecialchars($waktu_durasi) . '</td>
            </tr>
            <tr>
                <td class="label">Tempat</td>
                <td>: ' . htmlspecialchars($tempat) . '</td>
            </tr>
            <tr>
                <td class="label">Teknik Pendekatan</td>
                <td>: ' . nl2br(htmlspecialchars($catatan_khusus)) . '</td>
            </tr>
            <tr>
                <td class="label">Teknik Konseling</td>
                <td>: ' . nl2br(htmlspecialchars($teknik_konseling)) . '</td>
            </tr>
        </table>

        <div class="section-title">Hasil yang Dicapai:</div>
        <div class="content-box">' . nl2br(htmlspecialchars($hasil_layanan)) . '</div>
        

        <table class="signature-table">
            <tr>
                <td width="50%">
                    Mengetahui<br>
                    Kepala Sekolah<br>
                    <div class="spacer"></div>
                    ( <u>' . $nama_kepala_sekolah . '</u> )<br>
                </td>
                <td width="50%">
                    ' . htmlspecialchars($tempat) . ', ' . $tanggal_cetak_lokal . '<br>
                    Guru Bimbingan dan Konseling<br>
                    <div class="spacer"></div>
                    ( <u>' . htmlspecialchars($nama_guru) . '</u> )<br>
                </td>
            </tr>
        </table>

        </body>
        </html>
    ';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'Times New Roman'); 
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = "laporan_kelompok_" . $no_laporan_display . "_" . date('Ymd') . ".pdf";
    $file_path_db = "uploads/konseling/" . $filename;
    $file_path_physical = __DIR__ . "/../uploads/konseling/" . $filename;

    if (!file_exists(__DIR__ . "/../uploads/konseling")) {
        mkdir(__DIR__ . "/../uploads/konseling", 0777, true);
    }
    file_put_contents($file_path_physical, $dompdf->output());

    $query_riwayat = "
        INSERT INTO riwayat_kelompok (id_kelompok, file_pdf)
        VALUES (?, ?)
    ";
    $stmt_riwayat = $koneksi->prepare($query_riwayat);
    $stmt_riwayat->bind_param("is", $id_kelompok, $file_path_db);
    
    if (!$stmt_riwayat->execute()) {
        $koneksi->rollback();
        if (file_exists($file_path_physical)) {
            unlink($file_path_physical);
        }
        throw new Exception("Gagal menyimpan riwayat PDF.");
    }
    $stmt_riwayat->close();

    $koneksi->commit();
    
    echo json_encode([
        "status" => "success", 
        "message" => "Laporan Konseling Kelompok berhasil dibuat dan disimpan.", 
        "pdf_url" => "../" . $file_path_db 
    ]);

} catch (Exception $e) {
    if (isset($koneksi)) {
        $koneksi->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Terjadi kesalahan: " . $e->getMessage(), 
        "pdf_url" => null
    ]);

    exit;
}
?>