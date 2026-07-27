<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    exit;
}

$id_siswa = $_POST['id_siswa'];
$jenis_tes = $_POST['jenis_tes'];


if ($jenis_tes == "kemampuan") {

    mysqli_query($koneksi,"
        DELETE FROM hasil_kecerdasan
        WHERE id_siswa='$id_siswa'
    ");

}


if ($jenis_tes == "gayabelajar") {

    mysqli_query($koneksi,"
        DELETE FROM hasil_gayabelajar
        WHERE id_siswa='$id_siswa'
    ");

}


echo json_encode([
    "status"=>"success"
]);