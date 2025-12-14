<?php
session_start();
// Sesuaikan path ini jika file koneksi Anda berada di lokasi berbeda
include '../koneksi.php'; 

header('Content-Type: application/json');

// Keamanan: Cek otorisasi pengguna
if (!isset($_SESSION['id_guru'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Harap login kembali.']);
    exit;
}

// Cek metode dan data POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ids'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
    exit;
}

$ids_string = $_POST['ids'];
// Bersihkan dan validasi ID, pastikan hanya angka integer
$ids = array_filter(array_map('intval', explode(',', $ids_string)));

if (empty($ids)) {
    echo json_encode(['status' => 'success', 'students' => []]);
    exit;
}

$ids_list = implode(',', $ids);

// Query database untuk mengambil semua data siswa yang terpilih
$query = "
    SELECT 
        id_siswa, 
        nama, 
        kelas, 
        jurusan
    FROM 
        siswa 
    WHERE 
        id_siswa IN ($ids_list)
    ORDER BY 
        kelas ASC, nama ASC
";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database query gagal: ' . mysqli_error($koneksi)]);
    exit;
}

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = [
        'id' => $row['id_siswa'],
        'name' => htmlspecialchars($row['nama']),
        'kelas' => htmlspecialchars($row['kelas']),
        'jurusan' => htmlspecialchars($row['jurusan']),
    ];
}

echo json_encode(['status' => 'success', 'students' => $students]);
?>