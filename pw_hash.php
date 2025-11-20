<?php
$password_guru = 'bkguru#123'; 
$password_siswa = 'bksmkn2bjm'; 

$hashed_password_guru = password_hash($password_guru, PASSWORD_DEFAULT); 
$hashed_password_siswa = password_hash($password_siswa, PASSWORD_DEFAULT); 

echo "Data Password Guru <br>";
echo "Password Plain: " . $password_guru . "<br>";
echo "Hashed Password: <strong>" . $hashed_password_guru . "</strong><br>";
echo "<hr>";

if (password_verify($password_guru, $hashed_password_guru)) {
    echo "Verifikasi Guru: Hash berhasil dibuat dan diverifikasi. ✅ <br> Copy string BOLD di atas (Hashed Password Guru) ke kolom 'password' di tabel 'guru'.";
} else {
    echo "Verifikasi Guru: Gagal. Ada kesalahan. ❌";
}
echo "<hr>";

echo "Data Password Siswa <br>";
echo "Password Plain: " . $password_siswa . "<br>";
echo "Hashed Password: <strong>" . $hashed_password_siswa . "</strong><br>";
echo "<hr>";

if (password_verify($password_siswa, $hashed_password_siswa)) {
    echo "Verifikasi Siswa: Hash berhasil dibuat dan diverifikasi. ✅ <br> Copy string BOLD di atas (Hashed Password Siswa) ke kolom 'password' di tabel 'siswa'.";
} else {
    echo "Verifikasi Siswa: Gagal. Ada kesalahan. ❌";
}
echo "<hr>";

?>