<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];

/* ==========================================================
   HAPUS DOKUMEN (hanya draft milik sendiri yang boleh dihapus)
   ========================================================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    header('Content-Type: application/json');
    $id_laporan = (int) ($_POST['id_laporan'] ?? 0);

    $cek = mysqli_query($koneksi, "SELECT status, id_guru FROM laporan_bk WHERE id_laporan = $id_laporan");
    $row = $cek ? mysqli_fetch_assoc($cek) : null;

    if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
        echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan atau bukan milik Anda.']);
        exit;
    }
    if ($row['status'] === 'final') {
        echo json_encode(['success' => false, 'message' => 'Dokumen final tidak bisa dihapus. Buka kembali sebagai draft terlebih dahulu.']);
        exit;
    }

    mysqli_query($koneksi, "DELETE FROM riwayat_laporan WHERE id_laporan = $id_laporan");
    if (mysqli_query($koneksi, "DELETE FROM laporan_bk WHERE id_laporan = $id_laporan")) {
        echo json_encode(['success' => true, 'message' => 'Dokumen berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($koneksi)]);
    }
    exit;
}

/* ==========================================================
   FILTER STATUS (opsional lewat query string ?status=draft|final)
   ========================================================== */
$filter_status = $_GET['status'] ?? '';
$where = "WHERE id_guru = $id_guru_login";
if ($filter_status === 'draft' || $filter_status === 'final') {
    $where .= " AND status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

$daftar = mysqli_query($koneksi, "SELECT * FROM laporan_bk $where ORDER BY updated_at DESC");
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Dokumen | Laporan BK | BK SMKN 2 Banjarmasin</title>
  <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --primary: #0F3A3A;
      --primary-dark: #0B2E2E;
      --accent: #5FA8A1;
    }
    body { background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%); min-height: 100vh; }
    .primary-bg { background-color: var(--primary); }
    .primary-color { color: var(--primary); }
    tr:hover { background-color: #f9fafb; }
  </style>
</head>
<body class="text-gray-800">
  <header class="primary-bg shadow-md">
    <div class="max-w-6xl mx-auto px-4 md:px-8 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3 text-white">
        <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
          <i class="fas fa-clock-rotate-left"></i>
        </div>
        <div>
          <strong class="block text-sm md:text-base">Riwayat Dokumen Laporan BK</strong>
          <p class="text-xs text-white/70">SMKN 2 Banjarmasin</p>
        </div>
      </div>
      <a href="laporanbk.php" class="bg-white/10 hover:bg-white/20 text-white text-sm px-4 py-2 rounded-lg transition">
        <i class="fas fa-plus mr-2"></i> Buat Laporan Baru
      </a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 md:px-8 py-8">
    <div class="bg-white rounded-xl shadow-md p-4 md:p-6">

      <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h1 class="text-xl font-bold text-gray-800">Daftar Dokumen Saya</h1>
        <div class="flex gap-2 text-sm">
          <a href="riwayat_dokumen.php" class="px-3 py-1.5 rounded-lg border <?php echo $filter_status === '' ? 'primary-bg text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'; ?>">Semua</a>
          <a href="riwayat_dokumen.php?status=draft" class="px-3 py-1.5 rounded-lg border <?php echo $filter_status === 'draft' ? 'bg-yellow-500 text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'; ?>">🟡 Draft</a>
          <a href="riwayat_dokumen.php?status=final" class="px-3 py-1.5 rounded-lg border <?php echo $filter_status === 'final' ? 'bg-green-600 text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'; ?>">🟢 Final</a>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="bg-gray-100 text-gray-700 text-left">
              <th class="px-3 py-2 border-b">Nama Dokumen</th>
              <th class="px-3 py-2 border-b">Semester / Tahun Pelajaran</th>
              <th class="px-3 py-2 border-b">Tanggal</th>
              <th class="px-3 py-2 border-b">Status</th>
              <th class="px-3 py-2 border-b">Terakhir Diubah</th>
              <th class="px-3 py-2 border-b text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($daftar && mysqli_num_rows($daftar) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($daftar)): ?>
                <?php $isFinal = $row['status'] === 'final'; ?>
                <tr class="border-b last:border-0">
                  <td class="px-3 py-3 font-medium text-gray-800">
                    <?php echo htmlspecialchars($row['nama_dokumen']); ?>
                  </td>
                  <td class="px-3 py-3 text-gray-600">
                    <?php echo htmlspecialchars($row['semester'] . ' - ' . $row['tahun_pelajaran']); ?>
                  </td>
                  <td class="px-3 py-3 text-gray-600">
                    <?php echo $row['tanggal'] ? date('d/m/Y', strtotime($row['tanggal'])) : '-'; ?>
                  </td>
                  <td class="px-3 py-3">
                    <?php if ($isFinal): ?>
                      <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">🟢 Final</span>
                    <?php else: ?>
                      <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">🟡 Draft</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-3 text-gray-500 text-xs">
                    <?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?>
                  </td>
                  <td class="px-3 py-3 text-center whitespace-nowrap">
                    <a href="laporanbk.php?id=<?php echo (int) $row['id_laporan']; ?>"
                       class="text-blue-600 hover:text-blue-800 mr-3" title="<?php echo $isFinal ? 'Lihat / Cetak' : 'Buka & Edit'; ?>">
                      <i class="fas <?php echo $isFinal ? 'fa-eye' : 'fa-pen'; ?>"></i>
                    </a>
                    <?php if (!$isFinal): ?>
                      <button onclick="hapusDokumen(<?php echo (int) $row['id_laporan']; ?>)"
                        class="text-red-500 hover:text-red-700" title="Hapus Draft">
                        <i class="fas fa-trash"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                  Belum ada dokumen. <a href="laporanbk.php" class="text-blue-600 hover:underline">Buat laporan baru</a>.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    function hapusDokumen(id) {
      if (!confirm('Yakin ingin menghapus dokumen draft ini? Tindakan ini tidak bisa dibatalkan.')) return;

      const fd = new FormData();
      fd.append('action', 'hapus');
      fd.append('id_laporan', id);

      fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          alert(data.message);
          if (data.success) window.location.reload();
        })
        .catch(() => alert('Terjadi kesalahan koneksi saat menghapus.'));
    }
  </script>
</body>
</html>