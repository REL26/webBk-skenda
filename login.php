<!--
Sistem ini dikembangkan sebagai proyek internal sekolah
oleh tim siswa (2025–2026)
-->


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login BK - SMKN 2 BJM</title>

  <link rel="icon" type="image/png"
        href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">

  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    function togglePassword() {
      const p = document.getElementById('password');
      const i = document.getElementById('toggleIcon');
      if (p.type === 'password') {
        p.type = 'text';
        i.src = 'https://cdn-icons-png.flaticon.com/512/565/565655.png';
      } else {
        p.type = 'password';
        i.src = 'https://cdn-icons-png.flaticon.com/512/709/709612.png';
      }
    }
  </script>
</head>

<body class="bg-gray-100">

  <div class="min-h-screen flex items-center justify-center px-4 py-10">

    <div class="bg-white rounded-2xl shadow-2xl
                w-full max-w-6xl
                grid grid-cols-1 md:grid-cols-2
                overflow-hidden">

      <div class="p-8 md:p-12 flex flex-col justify-center">

        <div class="flex justify-center mb-8">
          <img src="https://epjj.smkn2-bjm.sch.id/pluginfile.php/1/core_admin/logo/0x200/1758083167/ELEARNINGok2.png"
               class="w-56">
        </div>

        <h2 class="text-2xl font-bold text-gray-800 text-center">
          Login Bimbingan Konseling
        </h2>
        <p class="text-center text-gray-500 mb-10">
          SMKN 2 Banjarmasin
        </p>

        <form action="proses_login.php" method="POST" class="space-y-6">

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">
              NIS (Siswa) / Email (Guru)
            </label>
            <input type="text" name="nis"
              placeholder="Masukkan NIS atau Email"
              class="w-full px-4 py-3 rounded-xl border border-gray-300
                     focus:ring-2 focus:ring-gray-700 focus:outline-none">
          </div>

          <div class="relative">
            <label class="block text-sm font-semibold text-gray-700 mb-1">
              Password
            </label>
            <input type="password" id="password" name="password"
              placeholder="Masukkan password"
              class="w-full px-4 py-3 rounded-xl border border-gray-300
                     focus:ring-2 focus:ring-gray-700 focus:outline-none pr-12">

            <img id="toggleIcon"
              onclick="togglePassword()"
              src="https://cdn-icons-png.flaticon.com/512/709/709612.png"
              class="absolute right-4 top-[42px] w-5 h-5 cursor-pointer opacity-70">
          </div>

          <button type="submit"
            class="w-full bg-gray-800 text-white py-3 rounded-xl
                   font-semibold hover:bg-gray-900 transition">
            Login
          </button>

          <div class="text-center flex justify-around px-4">
            <a href="lupa_password.php"
              class="text-sm text-gray-600 underline">
              Lupa Password?
            </a>
            <a href="https://wa.me/62088245604845?text=Halo%20Admin,%20saya%20membutuhkan%20bantuan%20untuk%20akun%20saya." 
               target="_blank"
               class="text-sm text-gray-600 underline">
               Hubungi Admin
            </a>
  
          </div>

        </form>
      </div>

      <div class="bg-gradient-to-br from-gray-900 to-gray-800
                  text-white p-8 md:p-12 flex flex-col justify-center">

        <div class="w-16 h-1 bg-white/60 mb-6 rounded-full"></div>

        <h1 class="text-3xl font-bold leading-tight mb-5">
          Layanan Bimbingan Konseling Digital
        </h1>

        <p class="text-gray-300 mb-8 leading-relaxed">
          Platform resmi Bimbingan Konseling SMKN 2 Banjarmasin
          yang membantu siswa dan guru dalam pendampingan akademik,
          pribadi, sosial, serta perencanaan karier secara aman
          dan terstruktur.
        </p>

        <div class="grid gap-4 text-sm">

        <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3">
          <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path d="M12 20l9-5-9-5-9 5 9 5z"/>
            <path d="M12 12l9-5-9-5-9 5 9 5z"/>
          </svg>
          Konsultasi BK online & terjadwal
        </div>

        <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3">
          <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path d="M12 2a7 7 0 017 7c0 2.5-1.5 4.5-3 6H8c-1.5-1.5-3-3.5-3-6a7 7 0 017-7z"/>
            <path d="M9 18h6"/>
          </svg>
          Tes minat, bakat & gaya belajar
        </div>

        <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3">
          <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <rect x="3" y="3" width="18" height="14" rx="2"/>
            <path d="M3 7h18"/>
          </svg>
          Riwayat layanan tersimpan aman
        </div>

        <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3">
          <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <circle cx="9" cy="7" r="4"/>
            <path d="M17 11a4 4 0 11-8 0"/>
            <path d="M3 21v-2a4 4 0 014-4h4"/>
          </svg>
          Akses khusus siswa & guru BK
        </div>

        </div>

      </div>

    </div>
  </div>

</body>
</html>

