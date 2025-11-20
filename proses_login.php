<?php
session_start();
include 'koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$identity = mysqli_real_escape_string($koneksi, trim($_POST['nis'])); 
$password_plain = trim($_POST['password']);

$login_sukses = false;

$is_email = (filter_var($identity, FILTER_VALIDATE_EMAIL) !== false) || strpos($identity, '@') !== false;

if ($is_email) {
    $qGuru = mysqli_query($koneksi, "SELECT id_guru, nip, nama, password, email FROM guru WHERE email='$identity' LIMIT 1");

    if (mysqli_num_rows($qGuru) > 0) {
        $data = mysqli_fetch_assoc($qGuru);
        
        if (password_verify($password_plain, $data['password'])) { 
            $_SESSION['id_guru'] = $data['id_guru'];
            $_SESSION['nip'] = $data['nip']; 
            $_SESSION['nama'] = $data['nama'];
            $_SESSION['email'] = $data['email'];
            
            header("Location: guru/dashboard.php");
            $login_sukses = true;
            exit;
        }
    }
} 

if (!$login_sukses) {
    $qSiswa = mysqli_query($koneksi, "SELECT id_siswa, nis, nama, password FROM siswa WHERE nis='$identity' LIMIT 1");

    if (mysqli_num_rows($qSiswa) > 0) {
        $data = mysqli_fetch_assoc($qSiswa);

        if (password_verify($password_plain, $data['password'])) { 
            $_SESSION['id_siswa'] = $data['id_siswa'];
            $_SESSION['nis'] = $data['nis'];
            $_SESSION['nama'] = $data['nama'];
            
            header("Location: siswa/dashboard.php");
            $login_sukses = true;
            exit;
        }
    }
}

if (!$login_sukses) {
    $qSiswa_check = mysqli_query($koneksi, "SELECT 1 FROM siswa WHERE nis='$identity' LIMIT 1");
    $qGuru_check = mysqli_query($koneksi, "SELECT 1 FROM guru WHERE email='$identity' LIMIT 1");
    
    $total_rows = mysqli_num_rows($qSiswa_check) + mysqli_num_rows($qGuru_check);
    
    $pesan = ($total_rows > 0) ? 'Password salah!' : 'Akun tidak ditemukan!';
    
    echo "<script>alert('$pesan'); window.location='login.php';</script>";
    exit;
}
?>