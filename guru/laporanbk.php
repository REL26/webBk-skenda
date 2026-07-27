<?php
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_laporan'])) {
    $semester = mysqli_real_escape_string($koneksi, $_POST['semester']);
    $tahun_pelajaran = mysqli_real_escape_string($koneksi, $_POST['tahun_pelajaran']);
    $sasaran = mysqli_real_escape_string($koneksi, $_POST['sasaran']);
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $koordinator_nip = mysqli_real_escape_string($koneksi, $_POST['koordinator_nip']);
    $gurubk_nip = mysqli_real_escape_string($koneksi, $_POST['gurubk_nip']);
    
    $materi_rekap = implode("\n", array_filter($_POST['rekap']));
    $masalah = implode("\n", array_filter($_POST['masalah']));
    $tindak_lanjut = implode("\n", array_filter($_POST['tindak']));

    $query = "INSERT INTO laporan_bk (semester, tahun_pelajaran, sasaran, tanggal, koordinator_nip, gurubk_nip, materi_rekap, masalah, tindak_lanjut) 
              VALUES ('$semester', '$tahun_pelajaran', '$sasaran', '$tanggal', '$koordinator_nip', '$gurubk_nip', '$materi_rekap', '$masalah', '$tindak_lanjut')";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['pesan_sukses'] = "Laporan berhasil disimpan!";
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Sistem Konseling Kelompok - SMKN 2 Banjarmasin"
    />
    <title class="no-print">Konseling Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link
      rel="icon"
      type="image/png"
      href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
              @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

              * {
                  font-family: 'Inter', sans-serif;
                  margin: 0;
                  padding: 0;
                  box-sizing: border-box;
              }

              :root {
                  --primary: #0F3A3A;
                  --primary-dark: #0B2E2E;
                  --primary-light: #123E44;
                  --accent: #5FA8A1;
                  --accent-dark: #4C8E89;
                  --white: #FFFFFF;
                  --gray-50: #F9FAFB;
                  --gray-200: #E5E7EB;
                  --success: #4C8E89;
                  --warning: #5FA8A1;
                  --danger: #9B2C2C;
              }

              html {
                  overflow-y: scroll;
                  scroll-behavior: smooth;
              }
              #dokumentasi img {
        max-height: 180px;
        object-fit: cover;
      }

              body {
                  background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
                  min-height: 100vh;
                  max-width: 100%;
                  overflow-x: hidden;
              }

              .fade-slide {
                  transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
                  opacity: 0;
                  transform: translateY(-10px);
                  pointer-events: none;
              }

              .fade-slide.active-transition {
                  opacity: 1;
                  transform: translateY(0);
                  pointer-events: auto;
              }

              @media (min-width: 768px) {
                  .sidebar {
                      width: 260px;
                      flex-shrink: 0;
                      transform: translateX(0) !important;
                      position: fixed !important;
                      height: 100vh;
                      top: 0;
                      left: 0;
                      overflow-y: auto;
                  }
              }

              .nav-item {
                  position: relative;
                  overflow: hidden;
                  transition: all 0.3s ease;
              }

              .nav-item::before {
                  content: '';
                  position: absolute;
                  left: 0;
                  top: 0;
                  height: 100%;
                  width: 4px;
                  background: var(--accent);
                  transform: scaleY(0);
                  transition: transform 0.3s ease;
              }

              .nav-item:hover::before,
              .nav-item.active::before {
                  transform: scaleY(1);
              }

              .nav-item.active {
                  background-color: var(--primary-light);
              }

              .nav-item.active > div:first-child,
              .nav-item.active {
                  background-color: #3C7F81 !important;
                  color: white !important;
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

              .data-table-report {
                  min-width: 800px;
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
                  from {
                      opacity: 0;
                      transform: translateY(20px);
                  }
                  to {
                      opacity: 1;
                      transform: translateY(0);
                  }
              }

              .animate-slide-in {
                  animation: slideIn 0.5s ease-out;
              }

              main {
                  width: 100%;
                  box-sizing: border-box;
                  overflow-x: hidden;
                  max-width: 100%;
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

                  .input{
                      margin-top: 20px;
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

              .print-value-proxy {
                display: none;
              }
              @media print {

  /* ═══════════════════════════════════════════
     PAGE SETUP
  ═══════════════════════════════════════════ */
  @page {
    size: A4 portrait;
    margin: 1.5cm 1.8cm;
  }

  /* ═══════════════════════════════════════════
     FONT & COLOR RESET  semua elemen seragam
  ═══════════════════════════════════════════ */
  html, body,
  div, p, span, a, li, ol, ul,
  table, thead, tbody, tr, th, td,
  input, select, label, h1, h2, h3, h4, h5, h6 {
    font-family: "Times New Roman", Times, serif !important;
    font-size: 10pt !important;
    color: #000000 !important;
    background-color: transparent !important;
    box-shadow: none !important;
    text-shadow: none !important;
    border-radius: 0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body {
    background: #ffffff !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* ═══════════════════════════════════════════
     SEMBUNYIKAN ELEMEN NON-CETAK
  ═══════════════════════════════════════════ */
  .no-print,
  button, .btn,
  aside, header, nav,
  #mobileMenu, #menuOverlay,
  input[type="file"],
  .overflow-x-auto > *:not(table) {
    display: none !important;
  }

  /* Sembunyikan icon FontAwesome */
  i[class*="fa-"] {
    display: none !important;
  }

  /* Sembunyikan select di tabel (sasaran) tapi tetap tampilkan nilainya via after trick tidak bisa  hide saja, kolom tetap ada bordernya */
  select {
    display: none !important;
  }

  /* ═══════════════════════════════════════════
     LAYOUT  sidebar hilang, main full width
  ═══════════════════════════════════════════ */
  main {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  #main-content {
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Hilangkan card wrapper styling */
  .bg-white.rounded-xl,
  .bg-white {
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Compress semua margin/padding div wrapper agar tidak ada ruang kosong */
  #main-content > div {
    padding: 0 !important;
    margin: 0 !important;
  }

  .mb-6, .mb-8, .mt-6, .mt-8, .p-6, .p-8, .md\:p-8 {
    margin: 0 !important;
    padding: 0 !important;
  }

  .mb-8 {
    margin-bottom: 10pt !important;
  }

  /* ═══════════════════════════════════════════
     HEADING & PARAGRAPH
  ═══════════════════════════════════════════ */
  h3 {
    font-size: 11pt !important;
    font-weight: bold !important;
    margin-top: 14pt !important;
    margin-bottom: 5pt !important;
    text-transform: uppercase !important;
    letter-spacing: 0.2pt !important;
  }

  p {
    line-height: 1.6 !important;
    margin-bottom: 4pt !important;
    text-align: left !important;
  }

  .text-justify {
    text-align: justify !important;
  }

  ol, ul {
    padding-left: 16pt !important;
    margin-bottom: 6pt !important;
  }

  li {
    line-height: 1.6 !important;
    margin-bottom: 2pt !important;
  }

  /* ═══════════════════════════════════════════
     JUDUL LAPORAN (header dokumen)
  ═══════════════════════════════════════════ */
  .judul {
    display: block !important;
    margin-bottom: 12pt !important;
  }

  .judul h3:first-child {
    font-size: 13pt !important;
    text-align: center !important;
    margin-top: 0 !important;
    margin-bottom: 10pt !important;
    letter-spacing: 0.5pt !important;
    font-weight: bold !important;
  }

  .judul > h3:not(:first-child) {
    font-size: 11pt !important;
    text-align: left !important;
    margin-top: 10pt !important;
    margin-bottom: 4pt !important;
  }

  .judul p {
    line-height: 1.7 !important;
  }

  /* ═══════════════════════════════════════════
     SECTION SPACING
  ═══════════════════════════════════════════ */
  /* ═══════════════════════════════════════════
     TABEL
  ═══════════════════════════════════════════ */
  .overflow-x-auto {
    overflow: visible !important;
    width: 100% !important;
  }

  table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-bottom: 8pt !important;
    table-layout: fixed !important;
    page-break-inside: auto !important;
  }

  th, td {
    border: 1pt solid #000000 !important;
    padding: 4pt 5pt !important;
    vertical-align: middle !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
  }

  th {
    font-weight: bold !important;
    text-align: center !important;
    background-color: #e8e8e8 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  tr {
    page-break-inside: avoid !important;
  }

  /* Kolom Aksi: border tetap ada, isi kosong */
  th:last-child,
  td:last-child {
    border: 1pt solid #000000 !important;
    display: table-cell !important;
  }

  td:last-child > * {
    display: none !important;
  }

  /* ═══════════════════════════════════════════
     INPUT DI DALAM TABEL  tampil sebagai teks
  ═══════════════════════════════════════════ */
  input[type="text"],
  input[type="number"],
  input[type="date"] {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    display: block !important;
    -webkit-appearance: none !important;
    appearance: none !important;
  }

  /* Sembunyikan spinner angka & kalender bawaan browser */
  input[type="number"]::-webkit-inner-spin-button,
  input[type="number"]::-webkit-outer-spin-button,
  input[type="date"]::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none !important;
  }

  input::placeholder {
    color: transparent !important;
  }

  /* ═══════════════════════════════════════════
     PENUTUP + TANDA TANGAN  tidak terpisah
  ═══════════════════════════════════════════ */
  .penutup-ttd-wrap {
    display: block !important;
  }

  .penutup-ttd-wrap .judul {
    page-break-before: avoid !important;
    break-before: avoid !important;
  }

  /* Tabel sebelum penutup tidak boleh dorong penutup ke halaman baru */
  [style*="page-break-after"] {
    page-break-after: avoid !important;
    break-after: avoid !important;
  }

  /* ═══════════════════════════════════════════
     TANDA TANGAN
  ═══════════════════════════════════════════ */
  .signature-area {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 0 30pt !important;
    margin-top: 16pt !important;
    page-break-before: avoid !important;
    break-before: avoid !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    text-align: center !important;
  }

  .signature-area > div {
    display: block !important;
  }

  .signature-area p {
    line-height: 1.5 !important;
    margin-bottom: 2pt !important;
    text-align: center !important;
  }

  .sign-space {
    display: block !important;
    height: 48pt !important;
    margin: 0 !important;
  }

  /* Nama yang dicetak (span#printKoordinator dsb) */
  span[id^="print"] {
    display: block !important;
    font-weight: bold !important;
    text-align: center !important;
    margin-bottom: 0 !important;
  }

  /* Garis tanda tangan */
  .signature-area > div > div.border-t {
    border-top: 1pt solid #000000 !important;
    width: 180pt !important;
    margin: 1pt auto 0 !important;
    display: block !important;
  }

  /* ═══════════════════════════════════════════
     ELEMEN HIDDEN YANG HARUS MUNCUL SAAT PRINT
  ═══════════════════════════════════════════ */
  .hidden {
    display: none !important;
  }

  /* Tailwind print:block utility */
  .print\:block {
    display: block !important;
  }

  .hidden.print\:block {
    display: block !important;
  }

  /* ═══════════════════════════════════════════
     DOKUMENTASI  halaman ke-3, terpisah
  ═══════════════════════════════════════════ */
  #dokumentasi-section {
    page-break-before: always !important;
    break-before: always !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }

  #dokumentasi-section > h3 {
    display: block !important;
    font-size: 12pt !important;
    text-align: center !important;
    text-transform: uppercase !important;
    margin-top: 0 !important;
    margin-bottom: 12pt !important;
    letter-spacing: 0.3pt !important;
  }

  /* Grid foto 3 kolom × 4 baris = 12 foto maks */
  #dokumentasi {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    grid-auto-rows: 178pt !important;
    gap: 6pt !important;
    width: 100% !important;
  }

  #dokumentasi > div {
    display: block !important;
    overflow: hidden !important;
  }

  #dokumentasi img {
    display: block !important;
    width: 100% !important;
    height: 178pt !important;
    object-fit: cover !important;
    border: 1pt solid #888888 !important;
  }

  /* Sembunyikan foto ke-13 dan seterusnya */
  #dokumentasi > div:nth-child(n+13) {
    display: none !important;
  }

  /* Sembunyikan tombol hapus foto */
  #dokumentasi button,
  #dokumentasi .no-print {
    display: none !important;
  }

  /* Nilai select/date yang di-render via JS */
  .print-value-proxy {
    display: block !important;
    font-family: "Times New Roman", Times, serif !important;
    font-size: 10pt !important;
    color: #000 !important;
  }

}


              
    </style>
  </head>
  <body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
    <header
      class="no-print md:hidden flex justify-between items-center px-4 py-3 bg-white shadow-md sticky top-0 z-30"
    >
      <div class="flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-lg primary-bg flex items-center justify-center shadow-md"
        >
          <i class="fas fa-user-tie text-white"></i>
        </div>
        <div class="no-print leading-tight">
          <strong class="no-print text-sm font-bold text-gray-800 block"
            >Guru BK</strong
          >
          <p class="text-xs text-gray-500">SMKN 2 BJM</p>
        </div>
      </div>
      <button
        onclick="toggleMenu()"
        class="text-gray-700 text-xl p-2 hover:bg-gray-100 rounded-lg transition"
        aria-label="Toggle Menu"
      >
        <i class="fas fa-bars"></i>
      </button>
    </header>

    <div
      id="menuOverlay"
      class="no-print hidden fixed inset-0 bg-black/50 z-20 md:hidden"
      onclick="toggleMenu()"
    ></div>

    <div
      id="mobileMenu"
      class="no-print fade-slide hidden fixed top-[56px] left-0 w-full bg-white shadow-lg z-30 md:hidden flex flex-col text-sm"
    >
      <a
        href="dashboard.php"
        class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition"
      >
        <i class="fas fa-home mr-2"></i> Dashboard
      </a>
      <hr class="border-gray-200" />

      <div
        class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_profiling_active ? 'bg-gray-100 font-medium' : ''; ?>"
        onclick="toggleSubMenu('profilingSubmenuMobile')"
      >
        <div class="flex justify-between">
          <span class="flex font-medium">
            <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
          </span>
          <i
            id="profilingSubmenuMobileIcon"
            class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"
          ></i>
        </div>
      </div>
      <div
        id="profilingSubmenuMobile"
        class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_profiling_active ? '' : 'hidden'; ?>"
      >
        <a
          href="hasil_tes.php"
          class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'hasil_tes.php' ? 'text-indigo-600 font-semibold' : ''; ?>"
        >
          <i class="fas fa-list-alt mr-2"></i> Data Hasil Persiswa
        </a>
        <a
          href="rekap_kelas.php"
          class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'rekap_kelas.php' ? 'text-indigo-600 font-semibold' : ''; ?>"
        >
          <i class="fas fa-chart-bar mr-2"></i> Data Hasil Perkelas
        </a>
      </div>
      <hr class="border-gray-200" />

      <div
        class="py-3 px-5 text-gray-700 hover:bg-gray-50 transition cursor-pointer <?php echo $is_program_bk_active ? 'bg-gray-100 font-medium' : ''; ?>"
        onclick="toggleSubMenu('programBkSubmenuMobile')"
      >
        <div class="flex justify-between">
          <span class="flex font-medium">
            <i class="fas fa-calendar-alt mr-2"></i> Program BK
          </span>
          <i
            id="programBkSubmenuMobileIcon"
            class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_program_bk_active ? 'fa-chevron-up' : ''; ?>"
          ></i>
        </div>
      </div>
      <div
        id="programBkSubmenuMobile"
        class="pl-8 space-y-1 py-1 bg-gray-50 border-t border-b border-gray-100 <?php echo $is_program_bk_active ? '' : 'hidden'; ?>"
      >
        <a
          href="konselingindividu.php"
          class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'konselingindividu.php' ? 'text-indigo-600 font-semibold' : ''; ?>"
        >
          <i class="fas fa-user-friends mr-2"></i> Konseling Individu
        </a>
        <a
          href="konselingkelompok.php"
          class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'konselingkelompok.php' ? 'text-indigo-600 font-semibold' : ''; ?>"
        >
          <i class="fas fa-users mr-2"></i> Konseling Kelompok
        </a>
        <a
          href="bimbingankelompok.php"
          class="block py-2 px-5 text-gray-700 hover:bg-gray-100 transition <?php echo $current_page == 'bimbingankelompok.php' ? 'text-indigo-600 font-semibold' : ''; ?>"
        >
          <i class="fas fa-users-cog mr-2"></i> Bimbingan Kelompok
        </a>
      </div>
      <hr class="border-gray-200" />
      <a
        href="logout.php"
        class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-medium flex items-center justify-center"
      >
        <i class="fas fa-sign-out-alt mr-2"></i> Logout
      </a>
    </div>

    <div class="flex flex-grow">
      <aside
        id="sidebar"
        class="no-print sidebar hidden md:flex primary-bg shadow-2xl z-40 flex-col text-white"
      >
        <div class="px-6 py-6 border-b border-white/10">
          <div class="flex items-center space-x-3">
            <div
              class="no-print w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm shadow-lg"
            >
              <i class="no-print fas fa-user-tie text-xl text-white"></i>
            </div>
            <div>
              <strong class="no-print text-base font-bold block"
                >Guru BK</strong
              >
              <span class="no-print text-xs text-white/80"
                >SMKN 2 Banjarmasin</span
              >
            </div>
          </div>
        </div>

        <nav class="flex flex-col flex-grow py-4 space-y-1 px-3">
          <a
            href="dashboard.php"
            class="nav-item flex items-center px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200"
          >
            <i class="fas fa-home mr-3"></i> Dashboard
          </a>

          <div
            class="nav-item cursor-pointer <?php echo $is_profiling_active ? 'active' : ''; ?>"
            onclick="toggleSubMenu('profilingSubmenuDesktop')"
          >
            <div
              class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200"
            >
              <span class="flex-item">
                <i class="fas fa-user-check mr-2"></i> Data & Laporan Siswa
              </span>
              <i
                id="profilingSubmenuDesktopIcon"
                class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_profiling_active ? 'fa-chevron-up' : ''; ?>"
              ></i>
            </div>
          </div>
          <div
            id="profilingSubmenuDesktop"
            class="pl-8 space-y-1 <?php echo $is_profiling_active ? '' : 'hidden'; ?>"
          >
            <a
              href="hasil_tes.php"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'hasil_tes.php' ? 'text-white font-semibold' : ''; ?>"
            >
              <i class="fas fa-list-alt mr-3 w-4"></i> Data Hasil Persiswa
            </a>
            <a
              href="rekap_kelas.php"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'rekap_kelas.php' ? 'text-white font-semibold' : ''; ?>"
            >
              <i class="fas fa-chart-bar mr-3 w-4"></i> Data Hasil Perkelas
            </a>
          </div>

          <div
            class="nav-item cursor-pointer <?php echo $is_program_bk_active ? 'active' : ''; ?>"
            onclick="toggleSubMenu('programBkSubmenuDesktop')"
          >
            <div
              class="flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-200 hover:bg-white/10 rounded-lg transition duration-200"
            >
              <span class="flex-item">
                <i class="fas fa-calendar-alt mr-2"></i> Program BK
              </span>
              <i
                id="programBkSubmenuDesktopIcon"
                class="fas fa-chevron-down text-xs ml-2 transition-transform duration-300 <?php echo $is_program_bk_active ? 'fa-chevron-up' : ''; ?>"
              ></i>
            </div>
          </div>
          <div
            id="programBkSubmenuDesktop"
            class="pl-8 space-y-1 <?php echo $is_program_bk_active ? '' : 'hidden'; ?>"
          >
            <a
              href="konselingindividu.php"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200 <?php echo $current_page == 'konselingindividu.php' ? 'text-white font-semibold' : ''; ?>"
            >
              <i class="fas fa-user-friends mr-3 w-4"></i> Konseling Individu
            </a>
            <a
              href="konselingkelompok.php"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200"
            >
              <i class="fas fa-users mr-3 w-4"></i> Konseling Kelompok
            </a>
            <a
              href="bimbingankelompok.php"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition duration-200"
            >
              <i class="fas fa-users-cog mr-3 w-4"></i> Bimbingan Kelompok
            </a>
            <a
              href="#"
              class="flex items-center px-4 py-2 text-sm text-white hover:bg-white/10 rounded-lg transition duration-200 font-semibold"
            >
              <i class="fas fa-clipboard-list mr-3 w-4"></i> Laporan BK
            </a>
          </div>

          <div class="mt-auto pt-4 border-t border-white/10">
            <a
              href="logout.php"
              class="nav-item flex items-center px-4 py-3 text-sm font-medium text-red-300 hover:bg-red-600/50 rounded-lg transition duration-200"
            >
              <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
          </div>
        </nav>
      </aside>

      <main class="flex-grow p-4 md:p-8">
        <div class="no-print mb-6">
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-clipboard-list primary-color mr-2"></i> Laporan BK
          </h1>
          <p class="text-sm text-gray-600">
            Buat dan kelola Laporan Bimbingan dan Konseling
          </p>
        </div>
    <div id="main-content">
        <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
          <div class="judul hidden print:block mb-6">
            <h3 class="text-xl font-bold mb-4">Bimbingan dan Konseling (BK)</h3>
            <p class="text-sm mb-2">
              Sekolah : SMK Negeri 2 Banjarmasin<br />
              Alamat Sekolah : Jl. Brigjen Hasan Basri No. 6 Banjarmasin<br />
              Bulan / Tahun : <?php
                $bulan_list = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
                echo $bulan_list[date('F')] . ' ' . date('Y');
              ?>
            </p>
            <p class="text-sm mb-4">Disusun oleh:<br />Guru BK / Konselor</p>

            <h3 class="text-lg font-bold mt-6 mb-2">I. PENDAHULUAN</h3>
            <p class="text-sm text-justify mb-4">
              Laporan Bimbingan dan Konseling (BK) ini disusun sebagai
              bentuk pertanggungjawaban pelaksanaan layanan BK di SMK Negeri 2
              Banjarmasin selama bulan <?php echo $bulan_list[date('F')] . ' ' . date('Y'); ?>. Laporan ini memuat kegiatan
              layanan BK, permasalahan peserta didik, serta tindak lanjut yang
              telah dan akan dilakukan.
            </p>

            <h3 class="text-lg font-bold mb-2">II. TUJUAN</h3>
            <ol class="text-sm mb-4 list-decimal list-inside">
              <li>
                Mendokumentasikan seluruh kegiatan layanan BK yang telah
                dilaksanakan.
              </li>
              <li>Mengetahui perkembangan dan permasalahan peserta didik.</li>
              <li>
                Menjadi bahan evaluasi serta dasar penyusunan tindak lanjut
                layanan BK berikutnya.
              </li>
            </ol>
          </div>

          <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
              <i class="no-print fas fa-list-check text-blue-600 mr-2"></i>
              III. REKAPITULASI KEGIATAN LAYANAN BK
            </h3>
            <div class="overflow-x-auto">
              <table
                id="rekapKegiatan"
                class="w-full border-collapse border border-gray-300"
              >
                <thead>
                  <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Jenis Layanan
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Sasaran
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Jumlah Siswa
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Waktu
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Bentuk Kegiatan
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Keterangan
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <button
              onclick="tambahRekap()"
              class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print"
            >
              <i class="fas fa-plus mr-2"></i> Tambah Baris
            </button>
          </div>

          <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
              <i
                class="no-print fas fa-exclamation-triangle text-blue-600 mr-2"
              ></i>
              IV. REKAP PERMASALAHAN PESERTA DIDIK
            </h3>
            <div class="overflow-x-auto">
              <table
                id="rekapMasalah"
                class="w-full border-collapse border border-gray-300"
              >
                <thead>
                  <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Bidang
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Permasalahan
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Jumlah Siswa
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Tindak Awal
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <button
              onclick="tambahMasalah()"
              class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print"
            >
              <i class="fas fa-plus mr-2"></i> Tambah Baris
            </button>
          </div>

          <div class="mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
              <i class="no-print fas fa-tasks text-blue-600 mr-2"></i>
              V. TINDAK LANJUT
            </h3>
            <div class="overflow-x-auto">
              <table
                id="tindakLanjut"
                class="w-full border-collapse border border-gray-300"
              >
                <thead>
                  <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-3 py-2 text-sm">No</th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Permasalahan
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Layanan BK
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Tindak Lanjut
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Waktu
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Pihak Terkait
                    </th>
                    <th class="border border-gray-300 px-3 py-2 text-sm">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <button
              onclick="tambahTindak()"
              class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print"
            >
              <i class="fas fa-plus mr-2"></i> Tambah Baris
            </button>
          </div>

          <div class="penutup-ttd-wrap">
          <div class="judul hidden print:block mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
              <i class="no-print fas fa-flag-checkered text-gray-600 mr-2"></i>
              VI. PENUTUP
            </h3>
            <p class="text-sm text-gray-700 text-justify mb-4">
              Demikian Laporan Bimbingan dan Konseling ini disusun
              sebagai bahan evaluasi dan dokumentasi kegiatan BK di sekolah.
              Diharapkan laporan ini dapat menjadi dasar peningkatan layanan BK
              pada bulan berikutnya.
            </p>
          </div>

          <?php $bulan_indo = [ 'January' => 'Januari', 'February' =>
          'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei',
          'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September'
          => 'September', 'October' => 'Oktober', 'November' => 'November',
          'December' => 'Desember' ]; $tgl_sekarang = date('d') . ' ' .
          $bulan_indo[date('F')] . ' ' . date('Y'); $nama_kepsek = "Novie
          Bambang Rumadi, S.T., M.Pd"; ?>

          <div class="signature-area grid grid-cols-2 gap-16 mt-6 print:mt-8 text-center">

  <div>
    <p class="text-sm font-semibold mb-1">Mengetahui,</p>
    <p class="text-sm mb-4">Kepala Sekolah atau Koordinator BK</p>

    <select
      id="pilihKoordinator"
      class="no-print w-full px-3 py-2 border rounded mb-2 text-sm"
      onchange="syncPrintText(this, 'printKoordinator')"
    >
      <option value="">Pilih Nama Guru</option>
      <option value="<?php echo $nama_kepsek; ?>"><?php echo $nama_kepsek; ?></option>
      <option value="Fahrunazi, S.Pd">Pahrurazi, S.Pd</option>
    </select>

    <input
      id="nipKoordinator"
      type="text"
      class="no-print w-full px-3 py-2 border rounded text-sm"
      placeholder="Masukkan NIP"
      oninput="
        document.getElementById('printNipKoordinator').textContent =
          this.value
      "
    />

    <p class="hidden print:block sign-space">&nbsp;</p>
    <span
      id="printKoordinator"
      class="hidden print:block font-bold"
    ></span>
    <div
      class="hidden print:block border-t border-black w-56 mx-auto mt-1"
    ></div>
    <p class="hidden print:block text-sm mt-1">
      NIP: <span id="printNipKoordinator"></span>
    </p>
  </div>

  <div>
    <p class="text-sm font-semibold mb-1">
      <?php echo $tgl_sekarang?>
    </p>
    <p class="text-sm mb-4">Guru Bimbingan dan Konseling</p>

    <select
      class="input no-print w-full px-3 py-2 border rounded mb-2 text-sm"
      onchange="syncPrintText(this, 'printGuruBK')"
    >
      <option value="">Pilih Nama Guru</option>
    <option value="Pahrurazi, S.Pd">Pahrurazi, S.Pd</option>
    <option value="Dian Riyani, S.Pd">Dian Riyani, S.Pd</option>
    <option value="Putri Hidayatie, S.Pd">Putri Hidayatie, S.Pd</option>
    <option value="Rini Rodhiati, S.Pd">Rini Rodhiati, S.Pd</option>
    <option value="Gusti Muhammad Fajri Ramadhan, S.Pd">Gusti Muhammad Fajri Ramadhan, S.Pd</option>
    <option value="Desy Arianti, S.Pd">Desy Arianti, S.Pd</option>
    <option value="Khalisatun Ni'mah, S.Pd">Khalisatun Ni'mah, S.Pd</option>
    <option value="Tiara Wulansari, S.Pd">Tiara Wulansari, S.Pd</option>
    <option value="Dhea Nur Aziza, S.Pd">Dhea Nur Aziza, S.Pd</option>
    <option value="Abdul Basith, S.Pd">Abdul Basith, S.Pd</option>
    </select>

    <input
      id="nipGuruBK"
      type="text"
      class="no-print w-full px-3 py-2 border rounded text-sm"
      placeholder="Masukkan NIP"
      oninput="
        document.getElementById('printNipGuruBK').textContent =
          this.value
      "
    />

    <p class="hidden print:block sign-space">&nbsp;</p>
    <span
      id="printGuruBK"
      class="hidden print:block font-bold"
    ></span>
    <div
      class="hidden print:block border-t border-black w-56 mx-auto mt-1"
    ></div>
    <p class="hidden print:block text-sm mt-1">
      NIP: <span id="printNipGuruBK"></span>
    </p>
  </div>
</div>
          </div>
    </div>
    
          <div id="dokumentasi-section" class="mb-8 mt-8">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
              <i class="no-print fas fa-images text-purple-600 mr-2"></i>
              DOKUMENTASI KEGIATAN
            </h3>
            <p class="no-print text-red-700 text-sm ms-5 mb-1">Maksimal 12 foto dan maksimal berukuran 2 mb</p>
            <input
              type="file"
              accept="image/*"
              multiple
              onchange="previewFoto(event)"
              class="mb-4 text-sm border border-gray-300 rounded-lg px-3 py-2 w-full no-print"
            />
            
            <div
              id="dokumentasi"
              class="grid grid-cols-2 md:grid-cols-3 gap-4"
            ></div>
          </div>

          <div class="flex justify-center gap-4 no-print">
            <button
              onclick="window.print()"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold"
            >
              <i class="fas fa-file-pdf mr-2"></i> Ekspor ke PDF
            </button>
            <button
              onclick="resetForm()"
              class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition font-semibold"
            >
              <i class="fas fa-redo mr-2"></i> Reset Form
            </button>
          </div>
        </div>

        <script>
          const dataSasaran = [
          <?php
          $q = mysqli_query($koneksi,"
              SELECT DISTINCT jurusan, kelas
              FROM siswa
              WHERE jurusan!='' AND kelas!=''
          ");

          while($d=mysqli_fetch_assoc($q)){
              echo "'".$d['jurusan']." ".$d['kelas']."',";
          }
          ?>
          ];
        </script>

        <script>
          function toggleMenu() {
            const mobileMenu = document.getElementById("mobileMenu");
            const overlay = document.getElementById("menuOverlay");
            const body = document.body;

            if (mobileMenu.classList.contains("active-transition")) {
              mobileMenu.classList.remove("active-transition");
              overlay.classList.add("hidden");

              setTimeout(() => {
                mobileMenu.classList.add("hidden");
                body.classList.remove("overflow-hidden");
              }, 300);
            } else {
              mobileMenu.classList.remove("hidden");
              setTimeout(
                () => mobileMenu.classList.add("active-transition"),
                10,
              );
              overlay.classList.remove("hidden");
              body.classList.add("overflow-hidden");
            }
          }

          function toggleSubMenu(menuId) {
            const submenu = document.getElementById(menuId);
            const icon = document.getElementById(menuId + "Icon");

            if (submenu) {
              if (submenu.classList.contains("hidden")) {
                submenu.classList.remove("hidden");
                if (icon)
                  icon.classList.replace("fa-chevron-down", "fa-chevron-up");
              } else {
                submenu.classList.add("hidden");
                if (icon)
                  icon.classList.replace("fa-chevron-up", "fa-chevron-down");
              }
            }
          }

          function tambahRekap() {
            const table = document.getElementById("rekapKegiatan");
            const tbody = table.querySelector("tbody");
            const row = tbody.insertRow();
            const rowNum = tbody.rows.length;

            let optionSasaran = '<option value="">Pilih Sasaran</option>';
            dataSasaran.forEach((item) => {
              optionSasaran += `<option value="${item}">${item}</option>`;
            });

            row.className = "hover:bg-gray-50 transition-colors";

            row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm font-medium text-gray-700">${rowNum}</td>
            <td class="border border-gray-300 px-1 py-1">
                <input name="jenis_layanan[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Jenis Layanan">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <select name="sasaran_kelas[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none cursor-pointer">
                    ${optionSasaran}
                </select>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="number" name="jumlah_siswa[]" min="0" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none text-center" placeholder="0" oninput="if(this.value<0)this.value=0">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="date" name="waktu[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input name="bentuk_kegiatan[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Bentuk">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input name="keterangan[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Keterangan">
            </td>
            <td class="border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
          }

          function tambahMasalah() {
            const table = document.getElementById("rekapMasalah");
            const tbody = table.querySelector("tbody");
            const row = tbody.insertRow();
            const rowNum = tbody.rows.length;

            row.className = "hover:bg-gray-50 transition-colors";

            row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm font-medium text-gray-700">${rowNum}</td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="bidang[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Bidang">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="masalah[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Deskripsi masalah">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="number" name="jml_siswa_masalah[]" min="0" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none text-center" placeholder="0" oninput="if(this.value<0)this.value=0">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="tindak_awal[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Tindak awal">
            </td>
            <td class="border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
          }

          function tambahTindak() {
            const table = document.getElementById("tindakLanjut");
            const tbody = table.querySelector("tbody");
            const row = tbody.insertRow();
            const rowNum = tbody.rows.length;

            row.className = "hover:bg-gray-50 transition-colors";

            row.innerHTML = `
            <td class="border border-gray-300 px-2 py-2 text-center text-sm font-medium text-gray-700">${rowNum}</td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="tl_permasalahan[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Permasalahan">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="tl_layanan[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Layanan BK">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="tl_tindak_lanjut[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Tindak lanjut">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="date" name="tl_waktu[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="tl_pihak[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Pihak terkait">
            </td>
            <td class="border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
          }

          function previewFoto(event) {
            const box = document.getElementById("dokumentasi");
            const newFiles = Array.from(event.target.files);
            const maxSize = 2 * 1024 * 1024;
            const maxPhotos = 12;

            if (box.querySelector("p")) {
              box.innerHTML = "";
            }

            newFiles.forEach((file) => {
              const currentPhotos = box.querySelectorAll("img").length;

              if (currentPhotos >= maxPhotos) {
                alert("Maksimal hanya boleh 12 foto!");
                return;
              }

              if (!file.type.startsWith("image/")) {
                alert("File " + file.name + " bukan gambar!");
                return;
              }

              if (file.size > maxSize) {
                alert("File " + file.name + " terlalu besar! Maksimal 2MB.");
                return;
              }

              const wrapper = document.createElement("div");
              wrapper.className = "relative group";

              const img = document.createElement("img");
              img.src = URL.createObjectURL(file);
              img.className =
                "w-full h-48 object-cover rounded-lg shadow-md hover:shadow-xl transition border border-gray-200";

              const btnHapus = document.createElement("button");
              btnHapus.type = "button";
              btnHapus.innerHTML = '<i class="fas fa-times"></i>';
              btnHapus.className =
                "absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center opacity-0 group-hover:opacity-100 transition no-print shadow-lg";
              btnHapus.onclick = () => {
                wrapper.remove();
                if (box.querySelectorAll("img").length === 0) {
                  box.innerHTML =
                    '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
                }
              };

              img.onload = () => URL.revokeObjectURL(img.src);

              wrapper.appendChild(img);
              wrapper.appendChild(btnHapus);
              box.appendChild(wrapper);
            });

            event.target.value = "";
          }

          function resetForm() {
            if (
              confirm(
                "Apakah Anda yakin ingin mereset semua data? Semua input akan dikosongkan.",
              )
            ) {
              ["rekapKegiatan", "rekapMasalah", "tindakLanjut"].forEach(
                (tableId) => {
                  const table = document.getElementById(tableId);
                  const tbody = table.querySelector("tbody");
                  if (tbody) {
                    tbody.innerHTML = "";
                  }
                },
              );

              document.querySelectorAll("select").forEach((select) => {
                select.selectedIndex = 0;
              });

              document
                .querySelectorAll('input[type="text"], input[type="number"]')
                .forEach((input) => {
                  input.value = "";
                });

              const fileInput = document.querySelector('input[type="file"]');
              if (fileInput) {
                fileInput.value = "";
              }

              const dokumentasi = document.getElementById("dokumentasi");
              if (dokumentasi) {
                dokumentasi.innerHTML =
                  '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
              }

              alert("Form berhasil direset!");
            }
          }

          document.addEventListener("DOMContentLoaded", () => {
            const overlay = document.getElementById("menuOverlay");
            if (overlay) overlay.addEventListener("click", toggleMenu);
            document
              .querySelectorAll(".animate-slide-in")
              .forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
              });

            tambahRekap();
            tambahMasalah();
            tambahTindak();
          });

          function syncPrintText(selectEl, targetId) {
            const target = document.getElementById(targetId);
            target.textContent = selectEl.value;
          }

          window.addEventListener('beforeprint', function () {
            document.querySelectorAll('table select').forEach(function (sel) {
              if (sel.value) {
                const span = document.createElement('span');
                span.className = 'print-value-proxy';
                span.textContent = sel.options[sel.selectedIndex]?.text || sel.value;
                sel.parentNode.insertBefore(span, sel.nextSibling);
              }
            });

            document.querySelectorAll('input[type="date"]').forEach(function (inp) {
              if (inp.value) {
                const d = new Date(inp.value);
                const bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                               'Juli','Agustus','September','Oktober','November','Desember'];
                const teks = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
                const span = document.createElement('span');
                span.className = 'print-value-proxy';
                span.textContent = teks;
                inp.parentNode.insertBefore(span, inp.nextSibling);
              }
            });
          });

          window.addEventListener('afterprint', function () {
            document.querySelectorAll('.print-value-proxy').forEach(function (el) {
              el.remove();
            });
          });
        </script>
      </main>
    </div>
  </body>
</html>