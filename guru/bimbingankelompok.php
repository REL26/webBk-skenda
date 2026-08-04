<?php
session_start();
include '../koneksi.php';
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sistem Bimbingan Kelompok - SMKN 2 Banjarmasin" />
    <title class="no-print">Bimbingan Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

      /* Style di bawah ini KHUSUS halaman Bimbingan Kelompok saja.
         Style sidebar (.primary-gradient, .sidebar, .nav-item, .fade-slide, dll)
         sudah digabung ke dalam partials/sidebar.php supaya konsisten
         di semua halaman - jangan didefinisikan ulang di sini. */

      * {
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      html {
        overflow-y: scroll;
        scroll-behavior: smooth;
      }

      body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
      }

      .modal {
        transition: opacity 0.3s ease, visibility 0.3s ease;
        visibility: hidden;
        opacity: 0;
      }

      .modal.open {
        visibility: visible;
        opacity: 1;
      }

      .card-hover {
        transition: all 0.3s ease;
      }

      .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.15);
      }

      .btn-action {
        transition: all 0.2s ease;
      }

      .btn-action:hover {
        transform: scale(1.05);
      }

      .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(47, 108, 110, 0.1);
      }

      .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
      }

      @keyframes slideIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .animate-slide-in {
        animation: slideIn 0.5s ease-out;
      }

      main {
        box-sizing: border-box;
        overflow-x: hidden;
      }

      @media (max-width: 767px) {
        main {
          margin-left: 0 !important;
          padding-left: 1rem;
          padding-right: 1rem;
          width: 100%;
          padding-top: 4.5rem;
        }

        body.overflow-hidden {
          overflow: hidden;
          width: 100vw;
          position: fixed;
          height: 100vh;
        }
      }

      @media (min-width: 768px) {
        main {
          margin-left: 260px;
        }
      }

      .grid {
        width: 100%;
        box-sizing: border-box;
      }

      .grid > * {
        overflow-x: hidden;
      }

      .primary-color { color: var(--primary); }
      .primary-bg { background-color: var(--primary-light); }
      .secondary-bg { background-color: #E6EEF0; }
    </style>
  </head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8 flex flex-col">
  <div class="no-print mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
      <i class="fas fa-users-cog text-blue-600 mr-2"></i> Bimbingan Kelompok
    </h1>
    <p class="text-sm text-gray-600">Kelola kegiatan Bimbingan Kelompok</p>
  </div>

  <div class="flex-grow flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-md p-10 md:p-14 flex flex-col items-center text-center max-w-md w-full">
      <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-5">
        <i class="fas fa-code text-gray-400 text-3xl"></i>
      </div>

      <span class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 text-xs font-semibold px-4 py-1.5 rounded-full mb-4">
        <i class="fas fa-circle-info text-xs"></i> Sedang Dikembangkan
      </span>

      <h2 class="text-xl font-bold text-gray-800 mb-2">Fitur Bimbingan Kelompok</h2>
      <p class="text-sm text-gray-500 mb-6 leading-relaxed">
        Halaman ini sedang dalam tahap pengembangan aktif. Fitur Bimbingan Kelompok akan segera tersedia.
      </p>

      <p class="text-xs text-gray-400 border-t border-gray-100 pt-4 w-full">
        Hubungi tim pengembang jika ada pertanyaan lebih lanjut.
      </p>
    </div>
  </div>
</main>
    </div>
  </body>
</html>