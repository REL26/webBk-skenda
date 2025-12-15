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
    if ($_GET["status"] === "notfound") {
        $message = "Data tidak ditemukan. Pastikan NIS (siswa) atau Email (guru) benar.";
        $alert_class = "bg-red-100 border-red-500 text-red-700";
    } elseif ($_GET["status"] === "error_koneksi") {
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

<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">

<!-- ================= MAIN CARD ================= -->
<div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full
            grid grid-cols-1 md:grid-cols-2 overflow-hidden">

  <!-- ================= LEFT : FORM ================= -->
  <div class="p-8 md:p-10 flex flex-col justify-center">

    <div class="flex justify-center mb-6">
      <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logo/0x200/1758083167/ELEARNINGok2.png"
           class="w-44">
    </div>

    <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">
      Lupa Password
    </h2>
    <p class="text-center text-gray-500 mb-6 text-sm">
      Reset akun Bimbingan Konseling
    </p>

    <?php if (!empty($message)): ?>
      <div class="<?= $alert_class ?> border-l-4 p-3 mb-4 text-sm rounded">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">

      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">
          NIS (Siswa) / Email (Guru)
        </label>
        <input type="text" name="identifier" required
          placeholder="Masukkan NIS atau Email"
          class="w-full px-4 py-3 rounded-xl border border-gray-300
                 focus:ring-2 focus:ring-gray-700 focus:outline-none">
      </div>

      <button type="submit"
        class="w-full bg-gray-800 text-white py-3 rounded-xl
               font-semibold hover:bg-gray-900 transition">
        Verifikasi & Hubungi Admin BK
      </button>

    </form>

    <div class="text-center mt-5">
      <a href="login.php"
         class="text-sm text-gray-600 hover:text-gray-900 hover:underline">
        ← Kembali ke Login
      </a>
    </div>
  </div>

  <!-- ================= RIGHT : TATA CARA ================= -->
  <div class="bg-gradient-to-br from-gray-900 to-gray-800
              text-white p-10 flex flex-col justify-center">

    <div class="w-16 h-1 bg-white/60 mb-6 rounded-full"></div>

    <h3 class="text-3xl font-bold mb-6">
      Tata Cara Lupa Password
    </h3>

<div class="space-y-4 text-gray-200 text-sm leading-relaxed">

  <div class="bg-white/5 rounded-xl px-5 py-4 flex gap-3">
    <span>▸</span>
    <span>Masukkan <b>NIS</b> jika Anda adalah <b>Siswa</b>.</span>
  </div>

  <div class="bg-white/5 rounded-xl px-5 py-4 flex gap-3">
    <span>▸</span>
    <span>Masukkan <b>Email</b> jika Anda adalah <b>Guru BK</b>.</span>
  </div>

  <div class="bg-white/5 rounded-xl px-5 py-4 flex gap-3">
    <span>▸</span>
    <span>Sistem akan mengarahkan Anda ke <b>WhatsApp Admin BK</b>.</span>
  </div>

  <div class="bg-white/5 rounded-xl px-5 py-4 flex gap-3">
    <span>▸</span>
    <span>Admin akan membantu proses reset password.</span>
  </div>

</div>


    <p class="text-gray-400 text-xs mt-6">
      *Pastikan data yang dimasukkan benar dan aktif.
    </p>

  </div>

</div>

</body>
</html>
