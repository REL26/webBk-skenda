<?php
session_start();
include 'koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$identity = trim($_POST['nis']); 
$password_plain = trim($_POST['password']);

$login_sukses = false;

$is_email = (filter_var($identity, FILTER_VALIDATE_EMAIL) !== false) || strpos($identity, '@') !== false;

if ($is_email) {
    $stmt = $koneksi->prepare("SELECT id_guru, nip, nama, password, email FROM guru WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $identity); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        if (password_verify($password_plain, $data['password'])) { 
            $_SESSION['id_guru'] = $data['id_guru'];
            $_SESSION['nip'] = $data['nip']; 
            $_SESSION['nama'] = $data['nama'];
            $_SESSION['email'] = $data['email'];
            
            $stmt->close();
            header("Location: guru/dashboard.php");
            exit;
        }
    }
    $stmt->close();
} 

$stmtSiswa = $koneksi->prepare("SELECT id_siswa, nis, nama, password FROM siswa WHERE nis = ? LIMIT 1");
$stmtSiswa->bind_param("s", $identity);
$stmtSiswa->execute();
$resultSiswa = $stmtSiswa->get_result();

if ($resultSiswa->num_rows > 0) {
    $data = $resultSiswa->fetch_assoc();

    if (password_verify($password_plain, $data['password'])) { 
        $_SESSION['id_siswa'] = $data['id_siswa'];
        $_SESSION['nis'] = $data['nis'];
        $_SESSION['nama'] = $data['nama'];
        
        $stmtSiswa->close();
        header("Location: siswa/dashboard.php");
        exit;
    }
}
$stmtSiswa->close();

$stmtCekSiswa = $koneksi->prepare("SELECT 1 FROM siswa WHERE nis = ? LIMIT 1");
$stmtCekSiswa->bind_param("s", $identity);
$stmtCekSiswa->execute();
$adaSiswa = $stmtCekSiswa->get_result()->num_rows;

$stmtCekGuru = $koneksi->prepare("SELECT 1 FROM guru WHERE email = ? LIMIT 1");
$stmtCekGuru->bind_param("s", $identity);
$stmtCekGuru->execute();
$adaGuru = $stmtCekGuru->get_result()->num_rows;

$total_rows = $adaSiswa + $adaGuru;
$pesan = ($total_rows > 0) ? 'Password salah!' : 'Akun tidak ditemukan!';

echo "<script>alert('$pesan'); window.location='login.php';</script>";

$stmtCekSiswa->close();
$stmtCekGuru->close();
exit;
?>