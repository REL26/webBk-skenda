<?php
include 'koneksi.php';

$status = '';
$message = '';
$alert_class = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($koneksi) || mysqli_connect_errno()) {
        header("Location: lupa_password.php?status=error_koneksi");
        exit;
    }

    $identifier = mysqli_real_escape_string($koneksi, trim($_POST["identifier"]));

    $sql_siswa = "SELECT id_siswa, nama, nis FROM siswa WHERE nis = '$identifier' LIMIT 1";
    $result_siswa = mysqli_query($koneksi, $sql_siswa);
    $data = mysqli_fetch_assoc($result_siswa);

    $role = "siswa";

    if (!$data) {
        $sql_guru = "SELECT id_guru, nama, email FROM guru WHERE email = '$identifier' LIMIT 1";
        $result_guru = mysqli_query($koneksi, $sql_guru);
        $data = mysqli_fetch_assoc($result_guru);
        $role = "guru";
    }

    if ($data) {

        $admin = "6287834937238";
        $nama = urlencode($data["nama"]);
        $id_value = urlencode($identifier);
        $role_text = ($role === "siswa") ? "Siswa" : "Guru";

        $pesan = urlencode(
            "Halo Admin BK, saya *$nama* ($role_text) ingin reset password.\n".
            "Data: $identifier"
        );

        header("Location: https://wa.me/$admin?text=$pesan");
        exit;
    }

    header("Location: lupa_password.php?status=notfound");
    exit;
}

if (isset($_GET["status"])) {
    $status = $_GET["status"];

    if ($status === "notfound") {
        $message = "Data tidak ditemukan. Pastikan memasukkan NIS (siswa) atau Email (guru) yang benar.";
        $alert_class = "bg-red-100 border-red-500 text-red-700";
    } elseif ($status === "error_koneksi") {
        $message = "Tidak dapat terhubung ke database. Hubungi admin.";
        $alert_class = "bg-red-100 border-red-500 text-red-700";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - BK SMKN 2 BJM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100 p-4">

<div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-xs md:max-w-md">
    <div class="flex justify-center mb-6">
      <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logo/0x200/1758083167/ELEARNINGok2.png" alt="Logo SMKN 2 BJM" class="w-58 h-auto">
    </div>
    <h2 class="text-xl font-bold text-center mb-1">Lupa Password</h2>
    <p class="text-center text-gray-600 text-sm mb-6">
        Masukkan NIS untuk Siswa dan Email untuk Guru.  
    </p>

    <?php if (!empty($message)): ?>
        <div class="<?= $alert_class ?> border-l-4 p-3 mb-4 text-sm">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-4">

        <div>
            <label class="text-sm font-semibold">NIS / Email:</label>
            <input type="text" required name="identifier"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400"
                   placeholder="Masukkan NIS atau Email">
        </div>

        <button type="submit"
                class="w-full bg-gray-800 text-white py-2 rounded-lg font-semibold hover:bg-gray-900 flex items-center justify-center">
            <i class="fab fa-whatsapp mr-2"></i> Verifikasi & Hubungi Admin
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="login.php" class="text-sm text-blue-700 hover:underline">
            Kembali ke Halaman Login
        </a>
    </div>

</div>

</body>
</html>
