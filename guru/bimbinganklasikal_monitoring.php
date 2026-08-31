<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];

$WARNA_FUNGSI_PHP = [
    'Pemahaman' => 'bg-blue-50 text-blue-700',
    'Pencegahan (Preventif)' => 'bg-green-50 text-green-700',
    'Pengentasan (Kuratif)' => 'bg-orange-50 text-orange-700',
    'Pemeliharaan dan Pengembangan' => 'bg-purple-50 text-purple-700',
];

/* ==========================================================================
   AJAX ENDPOINTS
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $idm = (int) ($_POST['id_materi'] ?? 0);

    if ($action === 'list_progress') {
        // Ambil pasangan kelas/jurusan sasaran materi ini.
        $sasaran = [];
        $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
        if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran[] = $s;

        $jmlSlide = 0;
        $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1");
        if ($qc) $jmlSlide = (int) mysqli_fetch_assoc($qc)['jml'];

        $data = [];
        if (count($sasaran) > 0) {
            $kondisi = [];
            foreach ($sasaran as $s) {
                $kls = mysqli_real_escape_string($koneksi, $s['kelas']);
                $jur = mysqli_real_escape_string($koneksi, $s['jurusan']);
                $kondisi[] = "(s.kelas = '$kls' AND s.jurusan = '$jur')";
            }
            $where = implode(' OR ', $kondisi);

            $sql = "SELECT s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan,
                        COUNT(DISTINCT CASE WHEN ps.status_selesai = 1 AND sl.status_aktif = 1 THEN ps.id_slide END) AS jumlah_selesai,
                        MAX(ps.waktu_selesai) AS waktu_terakhir
                    FROM siswa s
                    LEFT JOIN bk_progress_slide ps ON ps.id_siswa = s.id_siswa AND ps.id_materi = $idm
                    LEFT JOIN bk_slide sl ON sl.id_slide = ps.id_slide
                    WHERE ($where)
                    GROUP BY s.id_siswa, s.nis, s.nama, s.kelas, s.jurusan
                    ORDER BY s.kelas ASC, s.jurusan ASC, s.nama ASC";
            $q = mysqli_query($koneksi, $sql);
            if ($q) {
                while ($r = mysqli_fetch_assoc($q)) {
                    $selesai = (int) $r['jumlah_selesai'];
                    $status = 'belum_mulai';
                    if ($jmlSlide > 0 && $selesai >= $jmlSlide) $status = 'selesai';
                    else if ($selesai > 0) $status = 'berjalan';

                    $data[] = [
                        'id_siswa' => (int) $r['id_siswa'],
                        'nis' => $r['nis'],
                        'nama' => $r['nama'],
                        'kelas' => $r['kelas'],
                        'jurusan' => $r['jurusan'],
                        'jumlah_selesai' => $selesai,
                        'jumlah_slide' => $jmlSlide,
                        'persen' => $jmlSlide > 0 ? round(($selesai / $jmlSlide) * 100) : 100,
                        'status' => $status,
                        'waktu_terakhir' => $r['waktu_terakhir'],
                    ];
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $data, 'jumlah_slide' => $jmlSlide]);
        exit;
    }

    if ($action === 'detail_siswa') {
        $id_siswa = (int) ($_POST['id_siswa'] ?? 0);

        $slides = [];
        $q = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1 ORDER BY urutan ASC, id_slide ASC");
        if ($q) {
            while ($sl = mysqli_fetch_assoc($q)) {
                $ids = (int) $sl['id_slide'];

                $selesai = false;
                $waktu = null;
                $qp = mysqli_query($koneksi, "SELECT status_selesai, waktu_selesai FROM bk_progress_slide WHERE id_siswa = $id_siswa AND id_slide = $ids LIMIT 1");
                if ($qp && ($rp = mysqli_fetch_assoc($qp))) {
                    $selesai = ((int) $rp['status_selesai']) === 1;
                    $waktu = $rp['waktu_selesai'];
                }

                $pertanyaan = [];
                if ((int) $sl['butuh_lkpd'] === 1) {
                    $qq = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = $ids ORDER BY urutan ASC, id_pertanyaan ASC");
                    if ($qq) {
                        while ($p = mysqli_fetch_assoc($qq)) {
                            $idp = (int) $p['id_pertanyaan'];
                            $jawaban = '';
                            $qj = mysqli_query($koneksi, "SELECT jawaban FROM bk_jawaban_lkpd WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp LIMIT 1");
                            if ($qj && ($rj = mysqli_fetch_assoc($qj))) $jawaban = $rj['jawaban'];
                            $pertanyaan[] = [
                                'teks_pertanyaan' => $p['teks_pertanyaan'],
                                'tipe_jawaban' => $p['tipe_jawaban'],
                                'jawaban' => $jawaban,
                            ];
                        }
                    }
                }

                $slides[] = [
                    'judul_slide' => $sl['judul_slide'],
                    'urutan' => (int) $sl['urutan'],
                    'selesai' => $selesai,
                    'waktu_selesai' => $waktu,
                    'butuh_lkpd' => (int) $sl['butuh_lkpd'] === 1,
                    'pertanyaan' => $pertanyaan,
                ];
            }
        }

        $qs = mysqli_query($koneksi, "SELECT nama, nis, kelas, jurusan FROM siswa WHERE id_siswa = $id_siswa LIMIT 1");
        $siswa = $qs ? mysqli_fetch_assoc($qs) : null;

        echo json_encode(['success' => true, 'siswa' => $siswa, 'slides' => $slides]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    exit;
}

/* ==========================================================================
   RENDER HALAMAN
   ========================================================================== */
$id_materi = (int) ($_GET['id_materi'] ?? 0);

$materi = null;
if ($id_materi > 0) {
    $q = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $id_materi LIMIT 1");
    $materi = $q ? mysqli_fetch_assoc($q) : null;
}

$sasaran_list = [];
if ($materi) {
    $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $id_materi ORDER BY kelas ASC, jurusan ASC");
    if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran_list[] = $s['kelas'] . ' ' . $s['jurusan'];
}

$jumlah_slide_materi = 0;
if ($materi) {
    $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $id_materi AND status_aktif = 1");
    if ($qc) $jumlah_slide_materi = (int) mysqli_fetch_assoc($qc)['jml'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring Bimbingan Klasikal - Sistem BK</title>
  <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    html { overflow-y: scroll; }
    body { min-height: 100vh; max-width: 100%; overflow-x: hidden; }
    main { box-sizing: border-box; overflow-x: hidden; }
    @media (max-width: 767px) {
      main { margin-left: 0 !important; padding-left: 1rem; padding-right: 1rem; width: 100%; padding-top: 4.5rem; }
    }
    @media (min-width: 768px) { main { margin-left: 260px; } }

    table { min-width: 640px; }
    .badge-status-selesai { background: #dcfce7; color: #166534; }
    .badge-status-berjalan { background: #fef3c7; color: #92400e; }
    .badge-status-belum { background: #f3f4f6; color: #6b7280; }
    .progress-track { background: #e5e7eb; border-radius: 9999px; overflow: hidden; height: .5rem; }
    .progress-fill { height: 100%; border-radius: 9999px; transition: width .3s ease; }
    .action-btn { padding: .35rem; font-size: .95rem; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="flex-grow p-4 md:p-8 flex flex-col">

<?php if (!$materi): ?>

  <div class="bg-white rounded-xl shadow-md p-8 text-center max-w-lg mx-auto mt-10">
    <i class="fas fa-circle-exclamation text-amber-500 text-4xl mb-3"></i>
    <h1 class="text-lg font-bold text-gray-800 mb-1">Materi Tidak Ditemukan</h1>
    <p class="text-sm text-gray-500 mb-5">Materi Bimbingan Klasikal yang Anda cari tidak tersedia atau sudah dihapus.</p>
    <a href="bimbinganklasikal.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
      <i class="fas fa-arrow-left"></i> Kembali ke Bimbingan Klasikal
    </a>
  </div>

<?php else: ?>

  <div class="mb-5">
    <a href="bimbinganklasikal.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 mb-3">
      <i class="fas fa-arrow-left"></i> Kembali ke Bimbingan Klasikal
    </a>
    <div class="bg-white rounded-xl shadow-md p-5 md:p-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-1.5">
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">
              <i class="fas fa-chart-line text-indigo-600 mr-1"></i> <?php echo htmlspecialchars($materi['judul']); ?>
            </h1>
            <?php if (!empty($materi['fungsi_layanan'])): ?>
            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $WARNA_FUNGSI_PHP[$materi['fungsi_layanan']] ?? 'bg-gray-100 text-gray-600'; ?>">
              <?php echo htmlspecialchars($materi['fungsi_layanan']); ?>
            </span>
            <?php endif; ?>
          </div>
          <?php if (!empty($materi['deskripsi'])): ?>
          <p class="text-sm text-gray-500 mb-2 max-w-2xl"><?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?></p>
          <?php endif; ?>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400">
            <span><i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($materi['nama_guru_pembuat'] ?: '-'); ?></span>
            <span><i class="fas fa-layer-group mr-1"></i> <?php echo $jumlah_slide_materi; ?> slide</span>
            <span><i class="fas fa-users mr-1"></i>
              <?php echo count($sasaran_list) > 0 ? htmlspecialchars(implode(', ', $sasaran_list)) : 'Belum ada sasaran kelas'; ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ringkasan statistik -->
  <div id="ringkasanStatMonitoring" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Total Siswa Sasaran</p>
      <p class="text-2xl font-bold text-gray-800" id="statTotalSiswa">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Sudah Selesai</p>
      <p class="text-2xl font-bold text-green-600" id="statSelesai">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Sedang Mengerjakan</p>
      <p class="text-2xl font-bold text-amber-600" id="statBerjalan">-</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <p class="text-xs text-gray-500 mb-1">Belum Mulai</p>
      <p class="text-2xl font-bold text-gray-500" id="statBelumMulai">-</p>
    </div>
  </div>

  <!-- Filter -->
  <div class="bg-white rounded-xl shadow-md p-4 md:p-5 mb-4">
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-grow min-w-[200px]">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariSiswa" placeholder="Cari nama atau NIS siswa..." class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm" oninput="renderTabelProgress()">
      </div>
      <select id="filterStatusProgress" class="px-3 py-2 border rounded-lg text-sm bg-white" onchange="renderTabelProgress()">
        <option value="">Semua Status</option>
        <option value="selesai">Sudah Selesai</option>
        <option value="berjalan">Sedang Mengerjakan</option>
        <option value="belum_mulai">Belum Mulai</option>
      </select>
      <select id="filterKelasProgress" class="px-3 py-2 border rounded-lg text-sm bg-white" onchange="renderTabelProgress()">
        <option value="">Semua Kelas</option>
      </select>
    </div>
  </div>

  <!-- Tabel progress siswa -->
  <div class="bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas</th>
            <th class="px-3 py-2 border-b">Progress</th>
            <th class="px-3 py-2 border-b text-center">Status</th>
            <th class="px-3 py-2 border-b">Aktivitas Terakhir</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelProgress">
          <tr><td colspan="6" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
    <div id="paginasiProgress" class="flex flex-wrap items-center justify-between gap-2 mt-3"></div>
  </div>

<?php endif; ?>

</main>

<!-- Modal Detail Siswa -->
<div id="modalDetailSiswa" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
    <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
      <h2 class="text-base font-bold text-gray-800"><i class="fas fa-user-graduate text-indigo-600 mr-1"></i> <span id="judulModalDetailSiswa">Detail Progress Siswa</span></h2>
      <button type="button" onclick="tutupModalDetailSiswa()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
    </div>
    <div class="p-5" id="isiModalDetailSiswa">
      <p class="text-center text-gray-400 py-6">Memuat data...</p>
    </div>
  </div>
</div>

<script>
  const ID_MATERI = <?php echo (int) $id_materi; ?>;
  const JUMLAH_SLIDE_MATERI = <?php echo (int) $jumlah_slide_materi; ?>;
  const BARIS_PER_HALAMAN_PROGRESS = 15;
  let halamanProgress = 1;
  let daftarProgressSiswa = [];

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function formatWaktu(waktu) {
    if (!waktu) return '<span class="text-gray-400">Belum ada aktivitas</span>';
    const d = new Date(waktu.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) +
      ', ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  }

  const LABEL_STATUS = {
    selesai: { teks: 'Selesai', kelas: 'badge-status-selesai', warna_progress: '#16a34a' },
    berjalan: { teks: 'Sedang Mengerjakan', kelas: 'badge-status-berjalan', warna_progress: '#d97706' },
    belum_mulai: { teks: 'Belum Mulai', kelas: 'badge-status-belum', warna_progress: '#9ca3af' },
  };

  function muatDataProgress() {
    if (!ID_MATERI) return;
    const fd = new FormData();
    fd.append('action', 'list_progress');
    fd.append('id_materi', ID_MATERI);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;
        daftarProgressSiswa = data.data;
        isiFilterKelas();
        renderStatistikProgress();
        renderTabelProgress();
      });
  }

  function isiFilterKelas() {
    const select = document.getElementById('filterKelasProgress');
    const kelasUnik = [...new Set(daftarProgressSiswa.map(s => `${s.kelas} ${s.jurusan}`))].sort();
    select.innerHTML = '<option value="">Semua Kelas</option>' +
      kelasUnik.map(k => `<option value="${escapeHtml(k)}">${escapeHtml(k)}</option>`).join('');
  }

  function renderStatistikProgress() {
    document.getElementById('statTotalSiswa').textContent = daftarProgressSiswa.length;
    document.getElementById('statSelesai').textContent = daftarProgressSiswa.filter(s => s.status === 'selesai').length;
    document.getElementById('statBerjalan').textContent = daftarProgressSiswa.filter(s => s.status === 'berjalan').length;
    document.getElementById('statBelumMulai').textContent = daftarProgressSiswa.filter(s => s.status === 'belum_mulai').length;
  }

  function ambilDataTersaring() {
    const kataCari = (document.getElementById('cariSiswa').value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('filterStatusProgress').value;
    const kelasFilter = document.getElementById('filterKelasProgress').value;

    return daftarProgressSiswa.filter(s => {
      if (kataCari && !(`${s.nama} ${s.nis}`.toLowerCase().includes(kataCari))) return false;
      if (statusFilter && s.status !== statusFilter) return false;
      if (kelasFilter && `${s.kelas} ${s.jurusan}` !== kelasFilter) return false;
      return true;
    });
  }

  function renderTabelProgress() {
    halamanProgress = 1;
    gambarUlangTabelProgress();
  }

  function gambarUlangTabelProgress() {
    const tbody = document.getElementById('isiTabelProgress');
    const data = ambilDataTersaring();

    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-gray-400">
        ${daftarProgressSiswa.length === 0 ? 'Belum ada siswa sasaran untuk materi ini, atau belum ada aktivitas.' : 'Tidak ada siswa yang cocok dengan pencarian/filter.'}
      </td></tr>`;
      document.getElementById('paginasiProgress').innerHTML = '';
      return;
    }

    const totalHalaman = Math.max(1, Math.ceil(data.length / BARIS_PER_HALAMAN_PROGRESS));
    if (halamanProgress > totalHalaman) halamanProgress = totalHalaman;
    const mulai = (halamanProgress - 1) * BARIS_PER_HALAMAN_PROGRESS;
    const dataHalaman = data.slice(mulai, mulai + BARIS_PER_HALAMAN_PROGRESS);

    tbody.innerHTML = dataHalaman.map(s => {
      const lbl = LABEL_STATUS[s.status] || LABEL_STATUS.belum_mulai;
      return `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">
          <p class="font-medium text-gray-800">${escapeHtml(s.nama)}</p>
          <p class="text-xs text-gray-400">${escapeHtml(s.nis || '-')}</p>
        </td>
        <td class="px-3 py-2 text-gray-600">${escapeHtml(s.kelas)} ${escapeHtml(s.jurusan)}</td>
        <td class="px-3 py-2 min-w-[160px]">
          <div class="flex items-center gap-2">
            <div class="progress-track flex-grow">
              <div class="progress-fill" style="width:${s.persen}%;background:${lbl.warna_progress};"></div>
            </div>
            <span class="text-xs font-semibold text-gray-600 w-16 text-right">${s.jumlah_selesai}/${s.jumlah_slide} slide</span>
          </div>
        </td>
        <td class="px-3 py-2 text-center">
          <span class="px-2 py-1 rounded-full text-xs font-semibold ${lbl.kelas}">${lbl.teks}</span>
        </td>
        <td class="px-3 py-2 text-xs text-gray-500">${formatWaktu(s.waktu_terakhir)}</td>
        <td class="px-3 py-2 text-center">
          <button onclick="bukaModalDetailSiswa(${s.id_siswa}, '${escapeHtml(s.nama).replace(/'/g, "&#39;")}')" class="action-btn text-indigo-600 hover:text-indigo-800" title="Lihat detail progress & jawaban LKPD">
            <i class="fas fa-eye"></i>
          </button>
        </td>
      </tr>`;
    }).join('');

    renderPaginasiProgress(data.length, totalHalaman);
  }

  function renderPaginasiProgress(totalItems, totalHalaman) {
    const el = document.getElementById('paginasiProgress');
    const mulai = (halamanProgress - 1) * BARIS_PER_HALAMAN_PROGRESS + 1;
    const akhir = Math.min(halamanProgress * BARIS_PER_HALAMAN_PROGRESS, totalItems);

    let tombolHalaman = '';
    const batasBawah = Math.max(1, halamanProgress - 2);
    const batasAtas = Math.min(totalHalaman, halamanProgress + 2);
    if (batasBawah > 1) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;
    for (let p = batasBawah; p <= batasAtas; p++) {
      tombolHalaman += `<button type="button" onclick="gantiHalamanProgress(${p})" class="min-w-[2rem] px-2 py-1.5 rounded-lg text-xs font-semibold border ${p === halamanProgress ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">${p}</button>`;
    }
    if (batasAtas < totalHalaman) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;

    el.innerHTML = `
      <p class="text-xs text-gray-500">Menampilkan ${mulai}-${akhir} dari ${totalItems} siswa</p>
      <div class="flex items-center gap-1">
        <button type="button" onclick="gantiHalamanProgress(${halamanProgress - 1})" ${halamanProgress <= 1 ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>
        ${tombolHalaman}
        <button type="button" onclick="gantiHalamanProgress(${halamanProgress + 1})" ${halamanProgress >= totalHalaman ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>
      </div>
    `;
  }

  function gantiHalamanProgress(p) {
    const totalHalaman = Math.max(1, Math.ceil(ambilDataTersaring().length / BARIS_PER_HALAMAN_PROGRESS));
    if (p < 1 || p > totalHalaman) return;
    halamanProgress = p;
    gambarUlangTabelProgress();
  }

  function bukaModalDetailSiswa(id_siswa, nama) {
    document.getElementById('judulModalDetailSiswa').textContent = 'Detail Progress - ' + nama;
    document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-gray-400 py-6">Memuat data...</p>';
    document.getElementById('modalDetailSiswa').classList.remove('hidden');

    const fd = new FormData();
    fd.append('action', 'detail_siswa');
    fd.append('id_materi', ID_MATERI);
    fd.append('id_siswa', id_siswa);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-red-500 py-6">Gagal memuat detail siswa.</p>';
          return;
        }
        renderIsiModalDetailSiswa(data.slides);
      });
  }

  function renderIsiModalDetailSiswa(slides) {
    if (!slides || slides.length === 0) {
      document.getElementById('isiModalDetailSiswa').innerHTML = '<p class="text-center text-gray-400 py-6">Materi ini belum memiliki slide aktif.</p>';
      return;
    }

    const html = slides.map((sl, i) => {
      let jawabanHtml = '';
      if (sl.butuh_lkpd && sl.pertanyaan.length > 0) {
        jawabanHtml = `<div class="mt-3 space-y-2">` + sl.pertanyaan.map((p, pi) => `
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
            <p class="text-xs font-semibold text-gray-700 mb-1">${pi + 1}. ${escapeHtml(p.teks_pertanyaan)}</p>
            <p class="text-sm text-gray-600 whitespace-pre-wrap break-words">${p.jawaban ? escapeHtml(p.jawaban) : '<span class="text-gray-400 italic">Belum dijawab</span>'}</p>
          </div>`).join('') + `</div>`;
      }

      return `
      <div class="border border-gray-200 rounded-xl p-4 mb-3">
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <p class="text-sm font-bold text-gray-800"><i class="fas fa-layer-group text-gray-400 mr-1"></i> Slide ${i + 1}: ${escapeHtml(sl.judul_slide || '(tanpa judul)')}</p>
          <span class="px-2 py-1 rounded-full text-xs font-semibold ${sl.selesai ? 'badge-status-selesai' : 'badge-status-belum'}">
            ${sl.selesai ? 'Selesai' : 'Belum Selesai'}
          </span>
        </div>
        ${sl.selesai && sl.waktu_selesai ? `<p class="text-xs text-gray-400 mt-1">Diselesaikan: ${formatWaktu(sl.waktu_selesai)}</p>` : ''}
        ${jawabanHtml}
      </div>`;
    }).join('');

    document.getElementById('isiModalDetailSiswa').innerHTML = html;
  }

  function tutupModalDetailSiswa() {
    document.getElementById('modalDetailSiswa').classList.add('hidden');
  }

  document.addEventListener('DOMContentLoaded', muatDataProgress);
</script>
</body>
</html>