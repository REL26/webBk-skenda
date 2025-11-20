<?php
include 'koneksi.php'; 

$status = '';
$message = '';
$alert_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($koneksi) || mysqli_connect_errno()) {
        header("Location: lupa_password.php?status=error_koneksi");
        exit;
    }
    
    $identifier = mysqli_real_escape_string($koneksi, trim($_POST['identifier']));

    $user_data = null;
    $user_type = '';
    $email_tujuan = null;

    $query_siswa = "SELECT id_siswa, nis, nama, email FROM siswa WHERE nis = '$identifier' LIMIT 1";
    $result_siswa = mysqli_query($koneksi, $query_siswa);
    $user_data = mysqli_fetch_assoc($result_siswa);
    $user_type = 'siswa';

    if (!$user_data) {
        $query_guru = "SELECT id_guru, email, nama FROM guru WHERE email = '$identifier' LIMIT 1";
        $result_guru = mysqli_query($koneksi, $query_guru);
        $user_data = mysqli_fetch_assoc($result_guru);
        $user_type = 'guru';
    }

    if ($user_data) {
        $token = bin2hex(random_bytes(32)); 
        $expiry = date("Y-m-d H:i:s", time() + 3600); 

        if ($user_type == 'siswa') {
            $id = $user_data['id_siswa'];
            $email_tujuan = $user_data['email'] ?? null;
            $update_query = "UPDATE siswa SET reset_token = '$token', token_expiry = '$expiry' WHERE id_siswa = $id";
        } else { 
            $id = $user_data['id_guru'];
            $email_tujuan = $user_data['email'];
            $update_query = "UPDATE guru SET reset_token = '$token', token_expiry = '$expiry' WHERE id_guru = $id";
        }

        if (mysqli_query($koneksi, $update_query)) {
            
            if (!empty($email_tujuan)) {
                
                $domain = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/";
                $reset_link = $domain . "reset_password.php?token=" . $token . "&type=" . $user_type;
                
                $subject = "Reset Password Akun BK SMKN 2 BJM";
                $message_body = "Yth. " . $user_data['nama'] . ",\n\nSilakan klik link di bawah ini untuk mereset password Anda. Link ini hanya berlaku selama 1 jam.\n\n" . $reset_link . "\n\nJika Anda tidak merasa mengajukan permintaan ini, mohon abaikan email ini.\n\nHormat kami,\nTim BK SMKN 2 BJM";
                $headers = 'From: noreply@websitebk-skenda.com' . "\r\n" .
                           'Reply-To: noreply@websitebk-skenda.com' . "\r\n" .
                           'X-Mailer: PHP/' . phpversion();

                if (mail($email_tujuan, $subject, $message_body, $headers)) {
                    $status = 'success';
                } else {
                    $status = 'mail_fail';
                }

            } else {
                $status = 'noemail'; 
            }
            
        } else {
            $status = 'error';
        }

    } else {
        $status = 'notfound';
    }
    
    header("Location: lupa_password.php?status=" . $status);
    exit;
} 

if (isset($_GET['status'])) {
    $status = htmlspecialchars($_GET['status']);
    if ($status == 'success') {
        $message = 'Link reset password telah dikirim ke email Anda. Silakan cek kotak masuk (termasuk folder spam).';
        $alert_class = 'bg-green-100 border-green-500 text-green-700';
    } elseif ($status == 'noemail') {
        $message = 'Akun ditemukan, tetapi email tidak terdaftar. Mohon hubungi Guru BK Anda untuk reset password secara manual.';
        $alert_class = 'bg-yellow-100 border-yellow-500 text-yellow-800';
    } elseif ($status == 'notfound') {
        $message = 'NIS atau Email tidak ditemukan dalam sistem kami. Pastikan Anda memasukkan NIS untuk Siswa atau Email untuk Guru.';
        $alert_class = 'bg-red-100 border-red-500 text-red-700';
    } elseif ($status == 'error') {
        $message = 'Terjadi kesalahan saat memproses permintaan Anda. (Database error)';
        $alert_class = 'bg-red-100 border-red-500 text-red-700';
    } elseif ($status == 'mail_fail') {
        $message = 'ERROR: Gagal mengirim email. Silakan coba lagi atau hubungi administrator.';
        $alert_class = 'bg-red-100 border-red-500 text-red-700';
    } elseif ($status == 'error_koneksi') {
        $message = 'ERROR: Koneksi database gagal atau file koneksi tidak ditemukan.';
        $alert_class = 'bg-red-100 border-red-500 text-red-700';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - BK SMKN 2 BJM</title>
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
    <h2 class="text-2xl font-bold text-gray-800 mb-1 text-center">Lupa Password</h2>
    <p class="text-center font-semibold text-gray-600 mb-6 text-sm">Masukkan NIS (Siswa) atau Email (Guru) untuk mereset</p>

    <?php if ($message): ?>
        <div class="<?= $alert_class; ?> border-l-4 p-4 mb-4 text-sm" role="alert">
            <p><?= $message; ?></p>
        </div>
    <?php endif; ?>

    <form action="lupa_password.php" method="POST" class="space-y-4">
      <div>
        <label for="identifier" class="block text-gray-800 font-semibold mb-1 text-sm">NIS (Siswa) / Email (Guru):</label>
        <input type="text" id="identifier" name="identifier" required 
               value="<?= htmlspecialchars($_POST['identifier'] ?? ''); ?>"
               placeholder="Masukkan NIS atau Email Anda..." 
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm">
      </div>
      
      <button type="submit" 
              class="w-full bg-primary-color text-white font-bold py-2 px-4 rounded-lg hover:bg-primary-dark transition duration-300 shadow-md flex items-center justify-center">
        <i class="fas fa-paper-plane mr-2"></i> Kirim Link Reset Password
      </button>
    </form>

    <div class="mt-6 text-center">
        <a href="login.php" class="text-sm text-gray-600 hover:text-gray-800 font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Halaman Login
        </a>
    </div>

  </div>
</body>
</html>