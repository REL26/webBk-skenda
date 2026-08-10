<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$namaBulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hasil = mysqli_query($koneksi, "
    SELECT id_laporan, nama_dokumen, semester, tahun_pelajaran, bulan, status, finalized_at
    FROM laporan_bk
    WHERE status = 'final'
    ORDER BY tahun_pelajaran DESC, bulan DESC
");

$kelompokTahun = [];
while ($row = mysqli_fetch_assoc($hasil)) {
    $kelompokTahun[$row['tahun_pelajaran']][] = $row;
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Laporan BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="../assets/logo.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <style>
      .primary-color { color: #2563eb; }

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
    </style>
  </head>
  <body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
    <?php $current_page = 'laporanbk.php'; include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="flex-grow p-4 md:p-8">
      <div class="mb-6">
        <a href="laporanbk.php" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition mb-3">
          <i class="fas fa-arrow-left"></i> Kembali ke Laporan BK
        </a>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
          <i class="fas fa-clock-rotate-left primary-color mr-2"></i> Riwayat Laporan BK
        </h1>
        <p class="text-sm text-gray-600">
          Kumpulan Laporan Bulanan BK yang sudah diselesaikan & dikunci, dikelompokkan per tahun pelajaran.
        </p>
      </div>

      <div class="mb-6">
        <a href="laporanbk.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
          <i class="fas fa-plus"></i> Buat / Lanjutkan Laporan Bulan Ini
        </a>
      </div>

      <?php if (empty($kelompokTahun)): ?>
        <div class="bg-white rounded-xl shadow-md p-8 text-center text-gray-500">
          <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
          <p>Belum ada laporan yang diselesaikan & dikunci.</p>
        </div>
      <?php else: ?>
        <?php foreach ($kelompokTahun as $tahunPelajaran => $daftar): ?>
          <div class="bg-white rounded-xl shadow-md mb-6 overflow-hidden">
            <div class="bg-gray-100 px-6 py-3 border-b border-gray-200">
              <h2 class="font-bold text-gray-700">
                <i class="fas fa-calendar-days mr-2 text-gray-500"></i>
                Tahun Pelajaran <?php echo htmlspecialchars($tahunPelajaran); ?>
              </h2>
            </div>
            <div class="divide-y divide-gray-100">
              <?php foreach ($daftar as $lap): ?>
                <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition flex-wrap gap-3">
                  <div>
                    <p class="font-semibold text-gray-800">
                      <?php echo htmlspecialchars($lap['nama_dokumen']); ?>
                    </p>
                    <p class="text-sm text-gray-500">
                      <?php echo $namaBulanIndo[(int) $lap['bulan']]; ?>
                      &middot; Semester <?php echo htmlspecialchars($lap['semester']); ?>
                      <?php if ($lap['finalized_at']): ?>
                        &middot; Dikunci pada <?php echo date('d M Y, H:i', strtotime($lap['finalized_at'])); ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <a href="laporanbk.php?id=<?php echo (int) $lap['id_laporan']; ?>"
                     class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition text-sm font-semibold">
                    <i class="fas fa-eye"></i> Lihat / Cetak PDF
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <script src="partials/sidebar-script.js"></script>
  </body>
</html>