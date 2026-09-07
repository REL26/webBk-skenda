<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$namaBulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$namaBulanSingkat = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

$hasil = mysqli_query($koneksi, "
    SELECT id_laporan, nama_dokumen, semester, tahun_pelajaran, bulan, status, finalized_at, updated_at, materi_rekap, dokumentasi_foto
    FROM laporan_bk
    ORDER BY tahun_pelajaran DESC, bulan DESC
");

$kelompokTahun = [];
$totalLaporan = 0;
$totalFinal = 0;
$totalDraft = 0;
$totalKegiatan = 0;

while ($row = mysqli_fetch_assoc($hasil)) {
    // Hitung jumlah kegiatan layanan dari materi_rekap (JSON)
    $rekap = json_decode($row['materi_rekap'] ?? '[]', true);
    $jumlahKegiatan = is_array($rekap) ? count($rekap) : 0;

    // Hitung jumlah foto dokumentasi (JSON)
    $foto = json_decode($row['dokumentasi_foto'] ?? '[]', true);
    $jumlahFoto = is_array($foto) ? count($foto) : 0;

    $row['jumlah_kegiatan'] = $jumlahKegiatan;
    $row['jumlah_foto'] = $jumlahFoto;

    $kelompokTahun[$row['tahun_pelajaran']][] = $row;
    $totalLaporan++;
    if ($row['status'] === 'final') {
        $totalFinal++;
    } else {
        $totalDraft++;
    }
    $totalKegiatan += $jumlahKegiatan;
}

$daftarTahun = array_keys($kelompokTahun);
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

      .year-nav-link.active {
        background-color: #2563eb;
        color: #fff;
      }

      .laporan-card { transition: box-shadow .15s ease, transform .15s ease; }
      .laporan-card:hover { box-shadow: 0 8px 20px -6px rgba(37, 99, 235, .18); transform: translateY(-1px); }

      .bulan-chip {
        width: 3.25rem;
        height: 3.25rem;
        flex-shrink: 0;
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
        <div class="flex items-start justify-between flex-wrap gap-3">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-1">
              <i class="fas fa-clock-rotate-left primary-color mr-2"></i> Riwayat Laporan BK
            </h1>
            <p class="text-sm text-gray-600">
              Semua Laporan Bulanan BK milikmu  draft maupun yang sudah final &amp; dikunci, dikelompokkan per tahun pelajaran.
            </p>
          </div>
          <a href="laporanbk.php" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-semibold shadow-sm">
            <i class="fas fa-plus"></i> Buat / Lanjutkan Laporan
          </a>
        </div>
      </div>

      <?php if (empty($kelompokTahun)): ?>
        <div class="bg-white rounded-xl shadow-md p-10 text-center text-gray-500">
          <i class="fas fa-folder-open text-5xl mb-3 text-gray-300"></i>
          <p class="font-medium">Belum ada laporan yang dibuat.</p>
          <p class="text-sm text-gray-400 mt-1">Laporan yang kamu buat  baik masih draft maupun sudah final  akan muncul di sini.</p>
        </div>
      <?php else: ?>

        <!-- Ringkasan statistik -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-blue-50 flex items-center justify-center">
              <i class="fas fa-layer-group primary-color"></i>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-800 leading-tight"><?php echo $totalLaporan; ?></p>
              <p class="text-xs text-gray-500">Total Laporan</p>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-green-50 flex items-center justify-center">
              <i class="fas fa-lock text-green-600"></i>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-800 leading-tight"><?php echo $totalFinal; ?></p>
              <p class="text-xs text-gray-500">Sudah Final</p>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-yellow-50 flex items-center justify-center">
              <i class="fas fa-pen text-yellow-600"></i>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-800 leading-tight"><?php echo $totalDraft; ?></p>
              <p class="text-xs text-gray-500">Masih Draft</p>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-amber-50 flex items-center justify-center">
              <i class="fas fa-hands-helping text-amber-600"></i>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-800 leading-tight"><?php echo $totalKegiatan; ?></p>
              <p class="text-xs text-gray-500">Total Kegiatan Layanan</p>
            </div>
          </div>
        </div>

        <?php if ($totalDraft > 0): ?>
          <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3 text-sm flex items-start gap-2">
            <i class="fas fa-circle-info mt-0.5"></i>
            <span>
              Ada <strong><?php echo $totalDraft; ?> laporan</strong> yang masih berstatus <strong>draft</strong> (belum dikunci)  ditandai kuning di bawah.
              Laporan ini masih bisa diedit dan belum bisa dicetak sampai kamu tekan <strong>"Selesaikan &amp; Kunci Laporan"</strong>.
            </span>
          </div>
        <?php endif; ?>

        <!-- Navigasi cepat antar tahun pelajaran -->
        <?php if (count($daftarTahun) > 1): ?>
          <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide flex-shrink-0">Lompat ke:</span>
            <?php foreach ($daftarTahun as $i => $ty): ?>
              <a href="#tp-<?php echo str_replace('/', '-', $ty); ?>"
                 class="year-nav-link <?php echo $i === 0 ? 'active' : ''; ?> flex-shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white transition">
                <?php echo htmlspecialchars($ty); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php foreach ($kelompokTahun as $tahunPelajaran => $daftar): ?>
          <div id="tp-<?php echo str_replace('/', '-', $tahunPelajaran); ?>" class="mb-8 scroll-mt-6">
            <div class="flex items-center justify-between mb-3 px-1">
              <h2 class="font-bold text-gray-700 flex items-center gap-2">
                <i class="fas fa-calendar-days text-gray-400"></i>
                Tahun Pelajaran <?php echo htmlspecialchars($tahunPelajaran); ?>
              </h2>
              <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full">
                <?php echo count($daftar); ?> laporan
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <?php foreach ($daftar as $lap):
                $isGanjil = stripos($lap['semester'], 'ganjil') !== false;
                $isFinal = $lap['status'] === 'final';
              ?>
                <a href="laporanbk.php?id=<?php echo (int) $lap['id_laporan']; ?>"
                   class="laporan-card bg-white rounded-xl shadow-sm border p-4 flex flex-col gap-3 <?php echo $isFinal ? 'border-gray-100' : 'border-yellow-300 bg-yellow-50/40 ring-1 ring-yellow-200'; ?>">
                  <div class="flex items-start gap-3">
                    <div class="bulan-chip rounded-lg <?php echo $isGanjil ? 'bg-indigo-50' : 'bg-purple-50'; ?> flex flex-col items-center justify-center leading-none">
                      <span class="text-[10px] font-semibold <?php echo $isGanjil ? 'text-indigo-400' : 'text-purple-400'; ?> uppercase">
                        <?php echo $namaBulanSingkat[(int) $lap['bulan']]; ?>
                      </span>
                      <span class="text-lg font-bold <?php echo $isGanjil ? 'text-indigo-600' : 'text-purple-600'; ?>">
                        <?php echo (int) $lap['bulan']; ?>
                      </span>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="font-semibold text-gray-800 leading-snug break-words">
                        <?php echo htmlspecialchars($lap['nama_dokumen']); ?>
                      </p>
                      <p class="text-xs text-gray-500 mt-0.5">
                        <?php echo $namaBulanIndo[(int) $lap['bulan']]; ?>
                      </p>
                    </div>
                    <?php if (!$isFinal): ?>
                      <i class="fas fa-triangle-exclamation text-yellow-500 flex-shrink-0" title="Laporan ini masih draft, belum dikunci"></i>
                    <?php endif; ?>
                  </div>

                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full <?php echo $isGanjil ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700'; ?>">
                      Semester <?php echo htmlspecialchars($lap['semester']); ?>
                    </span>
                    <?php if ($isFinal): ?>
                      <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                        <i class="fas fa-lock text-[9px] mr-0.5"></i> Final
                      </span>
                    <?php else: ?>
                      <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-yellow-200 text-yellow-800">
                        <i class="fas fa-pen text-[9px] mr-0.5"></i> Draft &middot; Belum Dikunci
                      </span>
                    <?php endif; ?>
                  </div>

                  <div class="flex items-center gap-4 text-xs text-gray-500 border-t <?php echo $isFinal ? 'border-gray-100' : 'border-yellow-200'; ?> pt-3">
                    <span class="inline-flex items-center gap-1.5" title="Jumlah kegiatan layanan tercatat">
                      <i class="fas fa-hands-helping text-gray-400"></i>
                      <?php echo $lap['jumlah_kegiatan']; ?> kegiatan
                    </span>
                    <span class="inline-flex items-center gap-1.5" title="Jumlah foto dokumentasi">
                      <i class="fas fa-camera text-gray-400"></i>
                      <?php echo $lap['jumlah_foto']; ?> foto
                    </span>
                  </div>

                  <?php if ($isFinal && $lap['finalized_at']): ?>
                    <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                      <i class="fas fa-clock"></i>
                      Dikunci <?php echo date('d M Y, H:i', strtotime($lap['finalized_at'])); ?>
                    </p>
                  <?php elseif (!$isFinal && $lap['updated_at']): ?>
                    <p class="text-[11px] text-yellow-700 flex items-center gap-1.5">
                      <i class="fas fa-clock"></i>
                      Terakhir diubah <?php echo date('d M Y, H:i', strtotime($lap['updated_at'])); ?>
                    </p>
                  <?php endif; ?>

                  <span class="inline-flex items-center justify-center gap-2 <?php echo $isFinal ? 'bg-blue-50 text-blue-700 group-hover:bg-blue-100' : 'bg-yellow-100 text-yellow-800 group-hover:bg-yellow-200'; ?> px-3 py-2 rounded-lg transition text-sm font-semibold mt-auto">
                    <?php if ($isFinal): ?>
                      <i class="fas fa-eye"></i> Lihat / Cetak PDF
                    <?php else: ?>
                      <i class="fas fa-pen"></i> Lanjutkan Mengedit
                    <?php endif; ?>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <script src="partials/sidebar-script.js"></script>
    <script>
      // Sorot tautan tahun pelajaran yang sedang aktif di viewport
      const yearSections = document.querySelectorAll('[id^="tp-"]');
      const yearLinks = document.querySelectorAll('.year-nav-link');

      if (yearSections.length && yearLinks.length) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              yearLinks.forEach((link) => link.classList.remove('active'));
              const activeLink = document.querySelector(`.year-nav-link[href="#${entry.target.id}"]`);
              if (activeLink) activeLink.classList.add('active');
            }
          });
        }, { rootMargin: '-20% 0px -70% 0px' });

        yearSections.forEach((section) => observer.observe(section));
      }
    </script>
  </body>
</html>