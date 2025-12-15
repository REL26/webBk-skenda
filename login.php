<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login BK - SMKN 2 BJM</title>
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

<body class="min-h-screen bg-gray-100">

  <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">

    <!-- LEFT SIDE : LOGIN -->
    <div class="flex items-center justify-center px-6">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-8">

        <div class="flex justify-center mb-6">
          <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logo/0x200/1758083167/ELEARNINGok2.png" class="w-52">
        </div>

        <h2 class="text-2xl font-bold text-gray-800 text-center">
          Login Bimbingan Konseling
        </h2>
        <p class="text-center text-gray-500 mb-8">
          SMKN 2 Banjarmasin
        </p>

        <form action="proses_login.php" method="POST" class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
              NIS / Email Guru
            </label>
            <input type="text" name="nis"
              placeholder="Masukkan NIS atau Email"
              class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-700 focus:outline-none">
          </div>

          <div class="relative">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
              Password
            </label>
            <input type="password" id="password" name="password"
              placeholder="Masukkan password"
              class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-gray-700 focus:outline-none pr-12">
            <img id="toggleIcon"
              onclick="togglePassword()"
              src="https://cdn-icons-png.flaticon.com/512/709/709612.png"
              class="absolute right-4 top-[42px] w-5 h-5 cursor-pointer opacity-70 hover:opacity-100">
          </div>

          <button type="submit"
            class="w-full bg-gray-800 text-white py-3 rounded-xl font-semibold hover:bg-gray-900 transition">
            Login
          </button>

          <div class="text-center">
            <a href="lupa_password.php"
              class="text-sm text-gray-600 hover:text-gray-900 hover:underline">
              Lupa Password?
            </a>
          </div>
        </form>

      </div>
    </div>

    <!-- RIGHT SIDE : DESCRIPTION (IMPROVED) -->
    <div class="hidden md:flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 px-16">
      <div class="text-white max-w-xl text-center">

        <!-- Small Accent -->
        <div class="w-16 h-1 bg-white/60 mx-auto mb-6 rounded-full"></div>

        <h1 class="text-4xl font-bold leading-tight mb-6">
          Layanan Bimbingan Konseling<br>
          Digital
        </h1>

        <p class="text-gray-300 text-lg mb-10 leading-relaxed">
          Platform resmi Bimbingan Konseling SMKN 2 Banjarmasin
          yang membantu siswa dan guru dalam pendampingan akademik,
          pribadi, sosial, serta perencanaan karier secara aman
          dan terstruktur.
        </p>

        <div class="grid gap-4 text-gray-200 text-base">
          <div class="bg-white/5 rounded-xl py-3 px-5">
            Konsultasi BK online & terjadwal
          </div>
          <div class="bg-white/5 rounded-xl py-3 px-5">
            Tes minat, bakat, dan gaya belajar
          </div>
          <div class="bg-white/5 rounded-xl py-3 px-5">
            Riwayat layanan tersimpan rapi & aman
          </div>
          <div class="bg-white/5 rounded-xl py-3 px-5">
            Akses khusus siswa & guru BK
          </div>
        </div>

      </div>
    </div>

  </div>

</body>
</html>

