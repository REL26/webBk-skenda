<?php
session_start();
include 'koneksi.php'; 

// Cek apakah data form sudah dikirim
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Ambil data dari form dan lindungi dari SQL Injection
$nis = mysqli_real_escape_string($koneksi, trim($_POST['nis']));
$password_plain = trim($_POST['password']);

// Hash password yang dimasukkan pengguna (HARUS MD5)
$password_hash = MD5($password_plain);

$login_sukses = false;

// --- 1. Cek di tabel siswa ---
$qSiswa = mysqli_query($koneksi, "SELECT id_siswa, nis, nama, password FROM siswa WHERE nis='$nis' LIMIT 1");

if (mysqli_num_rows($qSiswa) > 0) {
    $data = mysqli_fetch_assoc($qSiswa);
    
    // Verifikasi Password menggunakan hash
    if ($password_hash === $data['password']) { 
        $_SESSION['id_siswa'] = $data['id_siswa'];
        $_SESSION['nis'] = $data['nis'];
        $_SESSION['nama'] = $data['nama'];
        
        header("Location: siswa/dashboard.php");
        $login_sukses = true;
        exit;
    }
}

// Jika login siswa gagal atau tidak ditemukan, coba cek di guru
if (!$login_sukses) {
    // --- 2. Cek di tabel guru ---
    $qGuru = mysqli_query($koneksi, "SELECT id_guru, nip, nama, password FROM guru WHERE nip='$nis' LIMIT 1");

    if (mysqli_num_rows($qGuru) > 0) {
        $data = mysqli_fetch_assoc($qGuru);
        
        // Verifikasi Password menggunakan hash
        if ($password_hash === $data['password']) {
            $_SESSION['id_guru'] = $data['id_guru'];
            $_SESSION['nip'] = $data['nip'];
            $_SESSION['nama'] = $data['nama'];
            
            header("Location: guru/dashboard.php");
            $login_sukses = true;
            exit;
        }
    }
}

// --- 3. Jika tidak ada di keduanya atau password salah ---
if (!$login_sukses) {
    $total_rows = mysqli_num_rows($qSiswa) + mysqli_num_rows($qGuru);
    
    $pesan = ($total_rows > 0) ? 'Password salah!' : 'Akun tidak ditemukan!';
    
    // Tampilkan pesan error dan kembali ke login.php
    echo "<script>alert('$pesan'); window.location='login.php';</script>";
    exit;
}
?>