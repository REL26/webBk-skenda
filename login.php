<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Bimbingan Konseling - SMKN 2 BJM</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.src = 'https://cdn-icons-png.flaticon.com/512/565/565655.png';
            } else {
                passwordField.type = 'password';
                icon.src = 'https://cdn-icons-png.flaticon.com/512/709/709612.png';
            }
        }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">

  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-xs md:max-w-md">
    <div class="flex justify-center mb-6">
      <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logo/0x200/1758083167/ELEARNINGok2.png" alt="Logo SMKN 2 BJM" class="w-32 h-auto">
    </div>
    <h2 class="text-center text-xl font-bold text-gray-800 mb-1">Login Bimbingan Konseling</h2>
    <p class="text-center font-semibold text-gray-600 mb-6 text-sm">SMKN 2 BJM</p>
    <form action="proses_login.php" method="POST" class="space-y-4">
      <div>
        <label for="nis" class="block text-gray-800 font-semibold mb-1 text-sm">NIS:</label>
        <input type="text" id="nis" name="nis" placeholder="Masukkan NIS kamu di sini..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm">
      </div>
      <div class="relative">
        <label for="password" class="block text-gray-800 font-semibold mb-1 text-sm">Password:</label>
        <input type="password" id="password" name="password" placeholder="Masukkan Password kamu di sini..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-gray-400 text-sm">
        <img id="toggleIcon" src="https://cdn-icons-png.flaticon.com/512/709/709612.png" onclick="togglePassword()" class="absolute right-3 top-[33px] w-5 h-5 cursor-pointer opacity-70 hover:opacity-100">
      </div>
      <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-lg hover:bg-gray-900 transition font-semibold">Login</button>
      <div class="text-center pt-1">
        <a href="index.php" class="text-blue-500 text-sm hover:underline">Lupa Password?</a>
      </div>
    </form>
  </div>

</body>
</html>