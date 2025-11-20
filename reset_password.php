<?php
include 'koneksi.php'; 

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? ''; 
$error_message = '';
$success_message = '';
$is_valid_token = false;
$user_id = 0;
$table_name = '';
$id_column = '';
$password_column = 'password';

if ($type === 'siswa') {
    $table_name = 'siswa';
    $id_column = 'id_siswa';
} elseif ($type === 'guru') {
    $table_name = 'guru';
    $id_column = 'id_guru';
} else {
    $error_message = 'Link reset password tidak valid.';
}

if (empty($token) || empty($table_name)) {
    $error_message = 'Token atau tipe pengguna tidak ditemukan.';
} else {
    $token_safe = mysqli_real_escape_string($koneksi, $token);
    $current_time = date("Y-m-d H:i:s");

    $query_check = "SELECT {$id_column} FROM {$table_name} WHERE reset_token = '$token_safe' AND token_expiry > '$current_time' LIMIT 1";
    $result_check = mysqli_query($koneksi, $query_check);

    if ($row = mysqli_fetch_assoc($result_check)) {
        $is_valid_token = true;
        $user_id = $row[$id_column];
    } else {
        $error_message = 'Token reset password tidak valid atau sudah kadaluarsa.';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_valid_token) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Password baru dan konfirmasi password tidak boleh kosong.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($new_password) < 6) { 
        $error_message = 'Password baru minimal 6 karakter.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE {$table_name} SET {$password_column} = ?, reset_token = NULL, token_expiry = NULL WHERE {$id_column} = ?";
        
        $stmt = mysqli_prepare($koneksi, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id); 

        if (mysqli_stmt_execute($stmt)) {
            $success_message = 'Password Anda berhasil direset! Silakan login.';
            $is_valid_token = false; 
        } else {
            $error_message = 'Gagal memperbarui password. Silakan coba lagi.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BK SMKN 2 BJM</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .primary-color { color: #1a73e8; }
        .bg-primary-color { background-color: #1a73e8; }
        .hover\:bg-primary-dark:hover { background-color: #0d47a1; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">

  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-xs md:max-w-sm">
    <div class="flex justify-center mb-4">
        <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo SMKN 2 Banjarmasin" class="h-16 w-auto">
    </div>
    <h2 class="text-2xl font-bold text-gray-800 mb-1 text-center">Reset Password</h2>
    <p class="text-center font-semibold text-gray-600 mb-6 text-sm">Atur ulang password baru Anda</p>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 text-sm" role="alert">
            <p><?= $error_message; ?></p>
        </div>
    <?php elseif ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 text-sm" role="alert">
            <p><?= $success_message; ?></p>
        </div>
        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm bg-primary-color text-white py-2 px-4 rounded-lg hover:bg-primary-dark font-semibold">
                <i class="fas fa-sign-in-alt mr-1"></i> Kembali ke Halaman Login
            </a>
        </div>
    <?php endif; ?>

    <?php if ($is_valid_token && empty($success_message)): ?>
    <form action="reset_password.php?token=<?= htmlspecialchars($token); ?>&type=<?= htmlspecialchars($type); ?>" method="POST" class="space-y-4">
      <div class="relative">
        <label for="password" class="block text-gray-800 font-semibold mb-1 text-sm">Password Baru:</label>
        <input type="password" id="password" name="password" required 
               placeholder="Masukkan Password Baru" 
               class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm">
      </div>
      <div class="relative">
        <label for="confirm_password" class="block text-gray-800 font-semibold mb-1 text-sm">Konfirmasi Password Baru:</label>
        <input type="password" id="confirm_password" name="confirm_password" required 
               placeholder="Ulangi Password Baru" 
               class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm">
      </div>
      
      <button type="submit" 
              class="w-full bg-primary-color text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition duration-300 shadow-md flex items-center justify-center">
        <i class="fas fa-lock mr-2"></i> Reset Password
      </button>
    </form>
    <?php endif; ?>

    <?php if (!$is_valid_token && empty($success_message)): ?>
    <div class="mt-6 text-center">
        <a href="lupa_password.php" class="text-sm text-gray-600 hover:text-gray-800 font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Ajukan Reset Password Lagi
        </a>
    </div>
    <?php endif; ?>

  </div>
</body>
</html>