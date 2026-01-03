<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$query_hapus = "DELETE FROM siswa WHERE kelas = 'Lulus Tahun 3'";
mysqli_query($koneksi, $query_hapus);

$query_update = "
    UPDATE siswa
    SET kelas = CASE
        WHEN kelas = 'X' THEN 'XI'
        WHEN kelas = 'XI' THEN 'XII'
        WHEN kelas = 'XII' THEN 'LULUS'
        WHEN kelas = 'LULUS' THEN 'Lulus Tahun 2'
        WHEN kelas = 'Lulus Tahun 2' THEN 'Lulus Tahun 3'
        ELSE kelas
    END
    WHERE kelas IN ('X', 'XI', 'XII', 'LULUS', 'Lulus Tahun 2')
";

if (mysqli_query($koneksi, $query_update)) {
    $_SESSION['pesan_sukses'] = "Berhasil menaikkan kelas seluruh siswa.";
} else {
    $_SESSION['pesan_error'] = "Gagal: " . mysqli_error($koneksi);
}

header("Location: hasil_tes.php");
exit;
?>