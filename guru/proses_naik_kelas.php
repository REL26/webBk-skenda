<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$query = "
    UPDATE siswa
    SET kelas = CASE
        WHEN kelas = 'X' THEN 'XI'
        WHEN kelas = 'XI' THEN 'XII'
        WHEN kelas = 'XII' THEN 'LULUS'
        ELSE kelas
    END
";

if (mysqli_query($koneksi, $query)) {
    $_SESSION['pesan_sukses'] = "Kenaikan kelas berhasil dilakukan!";
} else {
    $_SESSION['pesan_error'] = "Gagal menaikkan kelas: " . mysqli_error($koneksi);
}

header("Location: hasil_tes.php");
exit;
?>
