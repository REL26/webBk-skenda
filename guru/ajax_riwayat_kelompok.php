<?php
session_start();
include '../koneksi.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['id_guru'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Harap login.']);
    exit;
}

$action = $_GET['action'] ?? '';
$id_kelompok = $_GET['id_kelompok'] ?? 0;

if (!is_numeric($id_kelompok) || $id_kelompok <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Kelompok tidak valid.']);
    exit;
}

try {
    if ($action === 'get_report_full_detail') {
        $query_report = "
            SELECT 
                tanggal_pelaksanaan, pertemuan_ke, waktu_durasi, tempat, 
                catatan_khusus, teknik_konseling, proses_layanan, hasil_layanan
            FROM 
                kelompok
            WHERE 
                id_kelompok = ?
        ";
        $stmt_report = $koneksi->prepare($query_report);
        $stmt_report->bind_param("i", $id_kelompok);
        $stmt_report->execute();
        $result_report = $stmt_report->get_result();
        $report_data = $result_report->fetch_assoc();
        $stmt_report->close();
        
        if (!$report_data) {
            echo json_encode(['status' => 'error', 'message' => 'Laporan kelompok tidak ditemukan.']);
            exit;
        }
        $query_siswa = "
            SELECT 
                s.nama, s.kelas, s.jurusan, s.nis
            FROM 
                detail_kelompok dk
            JOIN 
                siswa s ON dk.id_siswa = s.id_siswa
            WHERE 
                dk.id_kelompok = ?
            ORDER BY s.kelas ASC, s.nama ASC
        ";
        $stmt_siswa = $koneksi->prepare($query_siswa);
        $stmt_siswa->bind_param("i", $id_kelompok);
        $stmt_siswa->execute();
        $result_siswa = $stmt_siswa->get_result();
        $siswa_data = $result_siswa->fetch_all(MYSQLI_ASSOC);
        $stmt_siswa->close();
        echo json_encode([
            'status' => 'success', 
            'data' => [
                'report' => $report_data,
                'students' => $siswa_data
            ]
        ]);
        
    } elseif ($action === 'get_kepuasan') {

        $query = "
            SELECT 
    s.nama,
    s.kelas,
    s.jurusan,
    kk.aspek_penerimaan,
    kk.aspek_kemudahan_curhat,
    kk.aspek_kepercayaan,
    kk.aspek_pemecahan_masalah,
    kk.tanggal_isi

            FROM 
                detail_kelompok dk
            JOIN 
                siswa s ON dk.id_siswa = s.id_siswa
            LEFT JOIN
                kepuasan_kelompok kk ON dk.id_kelompok = kk.id_kelompok AND dk.id_siswa = kk.id_siswa
            WHERE 
                dk.id_kelompok = ?
            ORDER BY s.kelas ASC, s.nama ASC
        ";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id_kelompok);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['status' => 'success', 'data' => $data]);
        
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Database Error in ajax_riwayat_kelompok: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan Database. Silakan cek log server.']);
}

$koneksi->close();
?>