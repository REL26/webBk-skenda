<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    exit;
}

$id_siswa = intval($_POST['id_siswa']);
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

if ($jenis_tes == "kepribadian") {

    $q_sesi = mysqli_query($koneksi, "
        SELECT id_sesi FROM hasil_kepribadian WHERE id_siswa = $id_siswa
    ");
    $id_sesi_list = [];
    while ($row = mysqli_fetch_assoc($q_sesi)) {
        $id_sesi_list[] = (int) $row['id_sesi'];
    }

    if (!empty($id_sesi_list)) {
        $id_sesi_in = implode(',', $id_sesi_list);
        mysqli_query($koneksi, "DELETE FROM jawaban_kepribadian WHERE id_sesi IN ($id_sesi_in)");
        mysqli_query($koneksi, "DELETE FROM hasil_kepribadian WHERE id_siswa = $id_siswa");
        mysqli_query($koneksi, "DELETE FROM sesi_tes WHERE id_sesi IN ($id_sesi_in)");
    }

}

$versi_map = [
    'asesmen_x'   => 'X',
    'asesmen_xi'  => 'XI',
    'asesmen_xii' => 'XII',
];

if (array_key_exists($jenis_tes, $versi_map)) {
    $versi = $versi_map[$jenis_tes];
    $versi_esc = mysqli_real_escape_string($koneksi, $versi);

    $q_sesi = mysqli_query($koneksi, "
        SELECT id_sesi FROM hasil_asesmen WHERE id_siswa = $id_siswa AND versi = '$versi_esc'
    ");
    $id_sesi_list = [];
    while ($row = mysqli_fetch_assoc($q_sesi)) {
        $id_sesi_list[] = (int) $row['id_sesi'];
    }

    mysqli_query($koneksi, "DELETE FROM hasil_asesmen WHERE id_siswa = $id_siswa AND versi = '$versi_esc'");

    if (!empty($id_sesi_list)) {
        $id_sesi_in = implode(',', $id_sesi_list);
        mysqli_query($koneksi, "DELETE FROM sesi_tes WHERE id_sesi IN ($id_sesi_in)");
    }
}


echo json_encode([
    "status"=>"success"
]);