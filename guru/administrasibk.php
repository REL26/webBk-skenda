<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];
$nama_waka = "Yani Silawati, S.Pd";
$nip_waka  = "19800930206042016";
$base_url_folder = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'cari_siswa') {
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $q = mysqli_query($koneksi, "SELECT nama, kelas, jurusan FROM siswa WHERE nis = '$nis' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        echo json_encode(['success' => (bool) $row, 'data' => $row]);
        exit;
    }

    if ($action === 'list_rujukan') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR jurusan LIKE '%$keyword%' OR permasalahan LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM lembar_rujukan $where ORDER BY id_rujukan ASC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_rujukan') {
        $id = (int) ($_POST['id_rujukan'] ?? 0);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan'] ?? '');
        $permasalahan = mysqli_real_escape_string($koneksi, $_POST['permasalahan'] ?? '');
        $alternatif = mysqli_real_escape_string($koneksi, $_POST['alternatif'] ?? '');
        $tanggal_ttd = mysqli_real_escape_string($koneksi, $_POST['tanggal_ttd'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM lembar_rujukan WHERE id_rujukan = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $query = "UPDATE lembar_rujukan SET nis='$nis', nama_siswa='$nama_siswa', kelas='$kelas', jurusan='$jurusan',
                        permasalahan='$permasalahan', alternatif='$alternatif', tanggal_ttd='$tanggal_ttd'
                      WHERE id_rujukan = $id";
        } else {
            $query = "INSERT INTO lembar_rujukan (nis, nama_siswa, kelas, jurusan, permasalahan, alternatif, tanggal_ttd, id_guru)
                      VALUES ('$nis','$nama_siswa','$kelas','$jurusan','$permasalahan','$alternatif','$tanggal_ttd',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Lembar rujukan berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus_rujukan') {
        $id = (int) ($_POST['id_rujukan'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM lembar_rujukan WHERE id_rujukan = $id");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
            exit;
        }
        if (mysqli_query($koneksi, "DELETE FROM lembar_rujukan WHERE id_rujukan = $id")) {
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'list_sp') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR jurusan LIKE '%$keyword%' OR jenis_sp LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM surat_peringatan $where ORDER BY id_sp ASC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_sp') {
        $id = (int) ($_POST['id_sp'] ?? 0);
        $jenis_sp = mysqli_real_escape_string($koneksi, $_POST['jenis_sp'] ?? 'SP I');
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan'] ?? '');
        $pelanggaran = mysqli_real_escape_string($koneksi, $_POST['pelanggaran'] ?? '');
        $tanggal_ttd = mysqli_real_escape_string($koneksi, $_POST['tanggal_ttd'] ?? '');
        $nama_guru = mysqli_real_escape_string($koneksi, $_POST['nama_guru'] ?? '');
        $nip_guru = mysqli_real_escape_string($koneksi, $_POST['nip_guru'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }
        if (!in_array($jenis_sp, ['SP I', 'SP II', 'SP III'])) $jenis_sp = 'SP I';

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_peringatan WHERE id_sp = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $query = "UPDATE surat_peringatan SET jenis_sp='$jenis_sp', nis='$nis', nama_siswa='$nama_siswa', kelas='$kelas', jurusan='$jurusan',
                        pelanggaran='$pelanggaran', tanggal_ttd='$tanggal_ttd', nama_guru='$nama_guru', nip_guru='$nip_guru'
                      WHERE id_sp = $id";
        } else {
            $query = "INSERT INTO surat_peringatan (jenis_sp, nis, nama_siswa, kelas, jurusan, pelanggaran, tanggal_ttd, nama_guru, nip_guru, id_guru)
                      VALUES ('$jenis_sp','$nis','$nama_siswa','$kelas','$jurusan','$pelanggaran','$tanggal_ttd','$nama_guru','$nip_guru',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Surat peringatan berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus_sp') {
        $id = (int) ($_POST['id_sp'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_peringatan WHERE id_sp = $id");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
            exit;
        }
        if (mysqli_query($koneksi, "DELETE FROM surat_peringatan WHERE id_sp = $id")) {
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sistem Bimbingan Kelompok - SMKN 2 Banjarmasin" />
    <title class="no-print">Administrasi BK | Program BK | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
      * { font-family: 'Inter', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
      html { overflow-y: scroll; scroll-behavior: smooth; }
      body { background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%); min-height: 100vh; max-width: 100%; overflow-x: hidden; }
      .modal { transition: opacity 0.3s ease, visibility 0.3s ease; visibility: hidden; opacity: 0; }
      .modal.open { visibility: visible; opacity: 1; }
      .btn-action { transition: all 0.2s ease; }
      .btn-action:hover { transform: scale(1.05); }
      main { box-sizing: border-box; overflow-x: hidden; }
      @media (max-width: 767px) {
        main { margin-left: 0 !important; padding-left: 1rem; padding-right: 1rem; width: 100%; padding-top: 4.5rem; }
        body.overflow-hidden { overflow: hidden; width: 100vw; position: fixed; height: 100vh; }
      }
      @media (min-width: 768px) { main { margin-left: 260px; } }
      .grid { width: 100%; box-sizing: border-box; }
      .grid > * { overflow-x: hidden; }

      .tab-btn { padding: 0.65rem 1.3rem; border-radius: 0.6rem; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; color: #64748b; border: 1.5px solid transparent; }
      .tab-btn.active { background: #2563eb; color: #fff; box-shadow: 0 4px 10px rgba(37,99,235,0.3); border-color: #2563eb; }
      .tab-btn:not(.active) { border-color: #e2e8f0; background: #fff; }
      .tab-btn:not(.active):hover { background: #f1f5f9; border-color: #cbd5e1; }

      .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; transition: all 0.15s ease; }
      .action-btn:hover { transform: translateY(-1px); }
      .action-btn-view { color: #475569; background: #f1f5f9; }
      .action-btn-view:hover { background: #e2e8f0; }
      .action-btn-edit { color: #2563eb; background: #eff6ff; }
      .action-btn-edit:hover { background: #dbeafe; }
      .action-btn-delete { color: #dc2626; background: #fef2f2; }
      .action-btn-delete:hover { background: #fee2e2; }
      .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: #94a3b8; }
      .empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; color: #cbd5e1; }
      .empty-state p.empty-title { font-weight: 600; color: #64748b; margin-bottom: 0.25rem; }
      .empty-state p.empty-desc { font-size: 0.8rem; }
      .sp-badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
      .sp-1 { background: #fef9c3; color: #854d0e; }
      .sp-2 { background: #fed7aa; color: #9a3412; }
      .sp-3 { background: #fecaca; color: #991b1b; }

      #printAreaRujukan, #printAreaSP { display: none; }
      body.mode-cetak > *:not(#printAreaRujukan):not(#printAreaSP) { display: none !important; }
      body.mode-cetak.cetak-rujukan #printAreaRujukan { display: block !important; }
      body.mode-cetak.cetak-sp #printAreaSP { display: block !important; }

      @media print {
        @page { size: A4; margin: 20mm 18mm; }
        body { background: #fff !important; }
        .kertas { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.4; color: #000; }
        .judul-polos { text-align: center; font-weight: bold; margin-bottom: 22px; font-size: 13pt; letter-spacing: 1px; }
        table.form-rj { table-layout: fixed; }
        table.form-rj td { padding: 2px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        p.isi-titik { min-height: 18px; margin: 2px 0; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        .rj-ttd { text-align: right; margin-top: 34px; }
        .rj-ttd p { margin-bottom: 6px; }
        .rj-ttd .garis-ttd-inline { display: inline-block; border-bottom: 1px solid #000; min-width: 220px; height: 55px; margin-top: 4px; }

        .kop-surat { display: flex; align-items: center; justify-content: space-between; gap: 10px; border-bottom: 3px solid #000; padding-bottom: 6px; margin-bottom: 4px; }
        .kop-surat img { height: 72px; width: auto; flex-shrink: 0; }
        .kop-surat .kop-tengah { flex-grow: 1; text-align: center; line-height: 1.25; }
        .kop-surat .kop-tengah p.baris1 { font-size: 12pt; font-weight: normal; margin: 0; }
        .kop-surat .kop-tengah h3.nama-sekolah { font-size: 15pt; font-weight: bold; margin: 1px 0; }
        .kop-surat .kop-tengah p.alamat { font-size: 9pt; margin: 0; }
        .judul-dok-kop { text-align: center; font-weight: bold; text-decoration: underline; margin: 16px 0 14px; font-size: 12pt; letter-spacing: 2px; }
        .sp-tabel-nama { border-collapse: collapse; margin: 10px 0 14px; table-layout: fixed; }
        .sp-tabel-nama td, .sp-tabel-nama th { border: 1px solid #000; padding: 6px 10px; font-size: 11pt; word-wrap: break-word; overflow-wrap: break-word; }
        .sp-tabel-nama th { background: #eee !important; -webkit-print-color-adjust: exact; }
        .sp-judul-tingkat { text-align: center; font-weight: bold; font-size: 13pt; margin: 18px 0; }
        .sp-halaman-2 { page-break-before: auto; break-before: auto; padding-top: 0; }
        .sp-penutup { text-align: justify; margin-bottom: 20px; text-indent: 36px; }
        table.sp-ttd-tabel { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.sp-ttd-tabel td { border: none; font-size: 11pt; padding: 2px 6px; vertical-align: top; text-align: center; }
        table.sp-ttd-tabel .ttd-spasi { height: 48px; }
        table.sp-ttd-tabel .garis-ttd { border-bottom: 1px solid #000; width: 75%; margin: 0 auto; }
        .sp-mengetahui { text-align: center; margin-top: 24px; }
        .sp-mengetahui > div:first-of-type { height: 72px; }
        .sp-tembusan { margin-top: 14px; font-size: 11pt; }
        .sp-tembusan ol { margin-left: 20px; margin-top: 4px; list-style: decimal; }
        .sp-tembusan ol li { display: list-item; }
        ol#spPvPelanggaran { list-style: decimal; }
        ol#spPvPelanggaran li { display: list-item; }
      }
    </style>
  </head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8 flex flex-col">

  <div class="no-print mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
      <i class="fas fa-folder-open text-blue-600 mr-2"></i> Administrasi BK
    </h1>
    <p class="text-sm text-gray-600">Kelola Lembar Rujukan dan Surat Peringatan siswa. Data tersimpan otomatis dan bisa dibuka lagi kapan saja.</p>
  </div>

  <div class="no-print flex gap-2 mb-4 p-1.5 bg-gray-100 rounded-xl w-fit">
    <button id="tabBtnRujukan" class="tab-btn" onclick="gantiTab('rujukan')"><i class="fas fa-file-signature mr-1"></i> Lembar Rujukan</button>
    <button id="tabBtnSP" class="tab-btn" onclick="gantiTab('sp')"><i class="fas fa-triangle-exclamation mr-1"></i> Surat Peringatan</button>
  </div>

  <div id="panelRujukan" class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow" style="display:none;">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariRujukan" placeholder="Cari nama siswa, kelas, atau permasalahan..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <button onclick="bukaModalTambahRujukan()" class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Tambah lembar rujukan baru">
        <i class="fas fa-plus mr-1"></i> Tambah Lembar Rujukan
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas/Jurusan</th>
            <th class="px-3 py-2 border-b">Permasalahan</th>
            <th class="px-3 py-2 border-b">Tanggal</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelRujukan"><tr><td colspan="6" class="text-center py-6 text-gray-400">Memuat data...</td></tr></tbody>
      </table>
    </div>
  </div>

  <div id="panelSP" class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow" style="display:none;">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariSP" placeholder="Cari nama siswa, kelas, atau jenis SP..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <button onclick="bukaModalTambahSP()" class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Tambah surat peringatan baru">
        <i class="fas fa-plus mr-1"></i> Tambah Surat Peringatan
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Jenis</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas/Jurusan</th>
            <th class="px-3 py-2 border-b">Tanggal</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelSP"><tr><td colspan="6" class="text-center py-6 text-gray-400">Memuat data...</td></tr></tbody>
      </table>
    </div>
  </div>
</main>
<script>
(function () {
  try {
    var t = localStorage.getItem('admBk_tab') || 'rujukan';
    var pr = document.getElementById('panelRujukan');
    var ps = document.getElementById('panelSP');
    var br = document.getElementById('tabBtnRujukan');
    var bs = document.getElementById('tabBtnSP');
    if (pr && ps && br && bs) {
      if (t === 'sp') {
        pr.style.display = 'none';
        ps.style.display = 'block';
        br.classList.remove('active');
        bs.classList.add('active');
      } else {
        pr.style.display = 'block';
        ps.style.display = 'none';
        br.classList.add('active');
        bs.classList.remove('active');
      }
    }
    var kr = localStorage.getItem('admBk_cariRujukan');
    var ks = localStorage.getItem('admBk_cariSP');
    if (kr !== null) {
      var ir = document.getElementById('cariRujukan');
      if (ir) ir.value = kr;
    }
    if (ks !== null) {
      var is = document.getElementById('cariSP');
      if (is) is.value = ks;
    }
  } catch (e) {}
})();
</script>

  <div id="modalRujukan" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModalRujukan" class="text-lg font-bold text-gray-800">Tambah Lembar Rujukan</h2>
        <button onclick="tutupModalRujukan()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 space-y-4">
        <input type="hidden" id="rjId">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bantuan: cari data siswa berdasarkan NIS</label>
          <div class="flex gap-2">
            <input type="text" id="rjNis" placeholder="Ketik NIS lalu klik Cari (boleh dikosongkan)" class="flex-grow px-3 py-2 border rounded text-sm">
            <button type="button" onclick="cariSiswaRujukan()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-search"></i> Cari</button>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
            <input type="text" id="rjNama" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <input type="text" id="rjKelas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
            <input type="text" id="rjJurusan" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Permasalahan</label>
          <textarea id="rjPermasalahan" rows="3" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Alternatif Penyelesaian</label>
          <textarea id="rjAlternatif" rows="3" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal TTD</label>
          <input type="date" id="rjTanggal" class="w-full px-3 py-2 border rounded text-sm">
        </div>
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModalRujukan()" class="px-4 py-2 rounded-lg border text-sm">Batal</button>
        <button onclick="simpanRujukan()" id="btnSimpanRujukan" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
      </div>
    </div>
  </div>

  <div id="modalDetailRujukan" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto overflow-x-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Lembar Rujukan</h2>
        <button onclick="document.getElementById('modalDetailRujukan').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2 break-words" id="isiDetailRujukan"></div>
      <div class="px-6 py-4 border-t flex justify-end gap-2">
        <button onclick="cetakRujukan()" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
      </div>
    </div>
  </div>

  <div id="modalSP" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModalSP" class="text-lg font-bold text-gray-800">Tambah Surat Peringatan</h2>
        <button onclick="tutupModalSP()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 space-y-4">
        <input type="hidden" id="spId">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Surat Peringatan *</label>
          <select id="spJenis" class="w-full px-3 py-2 border rounded text-sm">
            <option value="SP I">SP I (Peringatan Ke I / Satu)</option>
            <option value="SP II">SP II (Peringatan Ke II / Dua)</option>
            <option value="SP III">SP III (Peringatan Ke III / Tiga)</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bantuan: cari data siswa berdasarkan NIS</label>
          <div class="flex gap-2">
            <input type="text" id="spNis" placeholder="Ketik NIS lalu klik Cari (boleh dikosongkan)" class="flex-grow px-3 py-2 border rounded text-sm">
            <button type="button" onclick="cariSiswaSP()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-search"></i> Cari</button>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
            <input type="text" id="spNama" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas/Jurusan</label>
            <input type="text" id="spKelasJurusan" placeholder="Contoh: XI TKJ 2 / Teknik Komputer dan Jaringan" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pelanggaran (satu poin per baris)</label>
          <textarea id="spPelanggaran" rows="4" placeholder="Contoh:&#10;Terlambat masuk sekolah lebih dari 5 kali&#10;Tidak mengerjakan tugas berulang kali" class="w-full px-3 py-2 border rounded text-sm"></textarea>
          <p class="text-xs text-gray-400 mt-1">Tiap baris otomatis jadi poin bernomor 1, 2, 3, dst saat dicetak.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal TTD</label>
            <input type="date" id="spTanggal" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <input type="hidden" id="spNamaGuru">
        <input type="hidden" id="spNipGuru">
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModalSP()" class="px-4 py-2 rounded-lg border text-sm">Batal</button>
        <button onclick="simpanSP()" id="btnSimpanSP" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
      </div>
    </div>
  </div>

  <div id="modalDetailSP" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Surat Peringatan</h2>
        <button onclick="document.getElementById('modalDetailSP').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2" id="isiDetailSP"></div>
      <div class="px-6 py-4 border-t flex justify-end gap-2">
        <button onclick="cetakSP()" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
      </div>
    </div>
  </div>

  <div id="printAreaRujukan">
    <div class="kertas">
      <div class="judul-polos">LEMBAR RUJUKAN</div>
      <p style="margin-bottom:14px;">Kepada Yth.<br>Guru bimbingan konseling</p>
      <p style="margin-bottom:8px;">Dengan ini kami kirimkan siswa :</p>
      <table class="form-rj" style="width:100%; margin-bottom:10px;">
        <tr><td style="width:130px;">Nama</td><td style="width:15px;">:</td><td id="rjPvNama"></td></tr>
        <tr><td>Kelas/Jurusan</td><td>:</td><td id="rjPvKelas"></td></tr>
      </table>
      <p style="margin-bottom:4px;">Pada pengamatan kami, siswa tersebut mempunyai permasalahan sebagai berikut :</p>
      <div id="rjPvPermasalahan"></div>
      <p style="margin:10px 0 4px;">Alternatif pemecahan masalah sementara yang kami berikan adalah sebagai berikut :</p>
      <div id="rjPvAlternatif"></div>
      <p style="margin-top:10px;">Demikian atas perhatian dan bantuannya, kami ucapkan terima kasih.</p>
      <div class="rj-ttd">
        <p id="rjPvKotaTgl">Banjarmasin, ..........................</p>
        <p>Wali Kelas / Guru Mata Pelajaran</p>
        <div class="garis-ttd-inline"></div>
      </div>
    </div>
  </div>

  <div id="printAreaSP">
    <div class="kertas">
      <div class="kop-surat">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8dzqL0l1c4CGbGxZHXxQsPJv58W89Ha-md1QD-EYxjA&s=10" alt="Logo Provinsi Kalimantan Selatan">
        <div class="kop-tengah">
          <p class="baris1">PEMERINTAH PROVINSI KALIMANTAN SELATAN</p>
          <p class="baris1">DINAS PENDIDIKAN DAN KEBUDAYAAN</p>
          <h3 class="nama-sekolah">SMK NEGERI 2 BANJARMASIN</h3>
          <p class="alamat">Jl. Brigjen H. Hasan Basri No. 6 Telp/Fsx. 0511-3304677 Banjarmasin 70123</p>
          <p class="alamat">NPSN: 30304268 Website: http://www.smkn2-bjm.sch.id Email : surel@smkn2-bjm.sch.id</p>
        </div>
        <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo SMKN 2 Banjarmasin">
      </div>
      <div class="judul-dok-kop">SURAT PERINGATAN</div>
      <p style="text-align:justify;">Kepala Sekolah Bidang Kesiswaan Menengah Kejuruan (SMK) Negeri 2 Banjarmasin dengan ini memberikan peringatan/sanksi kepada :</p>
      <table class="sp-tabel-nama" style="width:100%;">
        <tr><th style="width:45%;">Nama</th><th>Kelas/Jurusan</th></tr>
        <tr><td id="spPvNama" style="text-align:center;">&nbsp;</td><td id="spPvKelasJurusan" style="text-align:center;">&nbsp;</td></tr>
      </table>
      <p style="text-align:justify;">Setelah mengumpulkan data-data, fakta dan keterangan-keterangan serta penyelidikan bahwa siswa sebagaimana tersebut di atas telah melakukan pelanggaran disiplin tata tertib sekolah sebagai berikut :</p>
      <p style="margin-top:10px;">Melanggar tata tertib SMK Negeri 2 Banjarmasin,</p>
      <ol id="spPvPelanggaran" style="margin-left:24px; margin-top:4px;"></ol>
      <p style="margin-top:12px; text-align:justify;">Berdasarkan atas pelanggaran yang telah dilakukan tersebut di atas, maka demi menegakkan disiplin tata tertib siswa dan untuk langkah-langkah pembinaan, diberikan sanksi berupa.</p>
      <div class="sp-judul-tingkat" id="spPvJudulTingkat"></div>
      <p id="spPvKonsekuensi" style="text-align:justify;"></p>

      <div class="sp-halaman-2">
        <p class="sp-penutup" id="spPvPenutup"></p>
        <table class="sp-ttd-tabel">
          <tr>
            <td style="width:50%;">Wali Kelas</td>
            <td style="width:50%;" id="spPvKotaTgl">Banjarmasin, .............. 2026</td>
          </tr>
          <tr><td></td><td>Ketua Program Keahlian</td></tr>
          <tr><td class="ttd-spasi"></td><td class="ttd-spasi"></td></tr>
          <tr><td><div class="garis-ttd">&nbsp;</div></td><td><div class="garis-ttd">&nbsp;</div></td></tr>
        </table>
        <div class="sp-mengetahui">
          Mengetahui,<br>Waka Kesiswaan
          <div style="height:72px;"></div>
          <div style="font-weight:bold; text-decoration:underline;"><?php echo $nama_waka; ?></div>
          <div>NIP. <?php echo $nip_waka; ?></div>
        </div>
        <div class="sp-tembusan">
          Tembusan Yth :
          <ol>
            <li>Wali Kelas</li>
            <li>Guru BK</li>
            <li>Ketua Program Keahlian</li>
            <li>Wakakesiswaan</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

<script>
  const BASE_URL = "<?php echo htmlspecialchars($base_url_folder, ENT_QUOTES); ?>";
  let dataRujukan = [];
  let dataSP = [];
  let idDetailRujukan = null;
  let idDetailSP = null;
  let tabAktif = 'rujukan';

  const STORAGE_KEY_TAB = 'admBk_tab';
  const STORAGE_KEY_CARI_RJ = 'admBk_cariRujukan';
  const STORAGE_KEY_CARI_SP = 'admBk_cariSP';

  function simpanStateUI() {
    try {
      localStorage.setItem(STORAGE_KEY_TAB, tabAktif);
      localStorage.setItem(STORAGE_KEY_CARI_RJ, document.getElementById('cariRujukan').value || '');
      localStorage.setItem(STORAGE_KEY_CARI_SP, document.getElementById('cariSP').value || '');
    } catch (e) {}
  }

  function muatStateUI() {
    try {
      const t = localStorage.getItem(STORAGE_KEY_TAB);
      if (t === 'rujukan' || t === 'sp') tabAktif = t;
      const kr = localStorage.getItem(STORAGE_KEY_CARI_RJ);
      const ks = localStorage.getItem(STORAGE_KEY_CARI_SP);
      if (kr !== null) document.getElementById('cariRujukan').value = kr;
      if (ks !== null) document.getElementById('cariSP').value = ks;
    } catch (e) {}
  }

  function gantiTab(tab) {
    tabAktif = tab;
    document.getElementById('panelRujukan').style.display = tab === 'rujukan' ? 'block' : 'none';
    document.getElementById('panelSP').style.display = tab === 'sp' ? 'block' : 'none';
    document.getElementById('tabBtnRujukan').classList.toggle('active', tab === 'rujukan');
    document.getElementById('tabBtnSP').classList.toggle('active', tab === 'sp');
    simpanStateUI();
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function formatTgl(tgl) {
    if (!tgl) return '-';
    const d = new Date(tgl + 'T00:00:00');
    if (isNaN(d)) return '-';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
  }

  function muatRujukan(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list_rujukan');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { dataRujukan = data.data; renderTabelRujukan(); } });
  }

  function renderTabelRujukan() {
    const tbody = document.getElementById('isiTabelRujukan');
    if (dataRujukan.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6">
        <div class="empty-state">
          <i class="fas fa-file-signature"></i>
          <p class="empty-title">Belum ada Lembar Rujukan</p>
          <p class="empty-desc">Klik tombol "Tambah Lembar Rujukan" untuk mulai mencatat.</p>
        </div>
      </td></tr>`;
      return;
    }
    tbody.innerHTML = dataRujukan.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}${d.jurusan ? ' / ' + escapeHtml(d.jurusan) : ''}</td>
        <td class="px-3 py-2 max-w-xs truncate" title="${escapeHtml(d.permasalahan || '')}">${escapeHtml(d.permasalahan || '-')}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_ttd)}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetailRujukan(${d.id_rujukan})" class="action-btn action-btn-view mr-1" title="Lihat detail & cetak PDF"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEditRujukan(${d.id_rujukan})" class="action-btn action-btn-edit mr-1" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusRujukan(${d.id_rujukan})" class="action-btn action-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
  }

  document.getElementById('cariRujukan').addEventListener('input', function () {
    simpanStateUI();
    muatRujukan(this.value);
  });

  function kosongkanFormRujukan() {
    document.getElementById('rjId').value = '';
    ['rjNis','rjNama','rjKelas','rjJurusan','rjPermasalahan','rjAlternatif','rjTanggal'].forEach(id => document.getElementById(id).value = '');
  }

  function bukaModalTambahRujukan() {
    kosongkanFormRujukan();
    document.getElementById('judulModalRujukan').textContent = 'Tambah Lembar Rujukan';
    document.getElementById('modalRujukan').classList.add('open');
  }

  function bukaModalEditRujukan(id) {
    const d = dataRujukan.find(x => x.id_rujukan == id);
    if (!d) return;
    kosongkanFormRujukan();
    document.getElementById('judulModalRujukan').textContent = 'Edit Lembar Rujukan';
    document.getElementById('rjId').value = d.id_rujukan;
    document.getElementById('rjNis').value = d.nis || '';
    document.getElementById('rjNama').value = d.nama_siswa || '';
    document.getElementById('rjKelas').value = d.kelas || '';
    document.getElementById('rjJurusan').value = d.jurusan || '';
    document.getElementById('rjPermasalahan').value = d.permasalahan || '';
    document.getElementById('rjAlternatif').value = d.alternatif || '';
    document.getElementById('rjTanggal').value = d.tanggal_ttd || '';
    document.getElementById('modalRujukan').classList.add('open');
  }

  function tutupModalRujukan() { document.getElementById('modalRujukan').classList.remove('open'); }

  function cariSiswaRujukan() {
    const nis = document.getElementById('rjNis').value.trim();
    if (!nis) { alert('Isi NIS dulu ya, baru klik Cari.'); return; }
    const fd = new FormData();
    fd.append('action', 'cari_siswa');
    fd.append('nis', nis);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) {
          document.getElementById('rjNama').value = data.data.nama || '';
          document.getElementById('rjKelas').value = data.data.kelas || '';
          document.getElementById('rjJurusan').value = data.data.jurusan || '';
        } else {
          alert('NIS tidak ditemukan. Silakan isi data secara manual.');
        }
      });
  }

  function simpanRujukan() {
    const nama = document.getElementById('rjNama').value.trim();
    if (!nama) { alert('Nama Siswa wajib diisi.'); return; }
    const btn = document.getElementById('btnSimpanRujukan');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    const fd = new FormData();
    fd.append('action', 'simpan_rujukan');
    fd.append('id_rujukan', document.getElementById('rjId').value || 0);
    fd.append('nis', document.getElementById('rjNis').value);
    fd.append('nama_siswa', nama);
    fd.append('kelas', document.getElementById('rjKelas').value);
    fd.append('jurusan', document.getElementById('rjJurusan').value);
    fd.append('permasalahan', document.getElementById('rjPermasalahan').value);
    fd.append('alternatif', document.getElementById('rjAlternatif').value);
    fd.append('tanggal_ttd', document.getElementById('rjTanggal').value);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          tutupModalRujukan();
          gantiTab('rujukan');
          muatRujukan(document.getElementById('cariRujukan').value);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusRujukan(id) {
    if (!confirm('Yakin ingin menghapus lembar rujukan ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_rujukan');
    fd.append('id_rujukan', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          gantiTab('rujukan');
          muatRujukan(document.getElementById('cariRujukan').value);
        }
      });
  }

  function lihatDetailRujukan(id) {
    idDetailRujukan = id;
    const d = dataRujukan.find(x => x.id_rujukan == id);
    if (!d) return;
    const item = (label, value, full) => `
      <div class="${full ? 'col-span-2' : ''}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">${label}</p>
        <p class="text-sm text-gray-800 leading-relaxed">${value || '<span class="text-gray-300">-</span>'}</p>
      </div>`;
    document.getElementById('isiDetailRujukan').innerHTML = `
      <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
        ${item('Nama Siswa', escapeHtml(d.nama_siswa))}
        ${item('Tanggal TTD', formatTgl(d.tanggal_ttd))}
        ${item('Kelas', escapeHtml(d.kelas || '-'))}
        ${item('Jurusan', escapeHtml(d.jurusan || '-'))}
      </div>
      <div class="space-y-4 pt-4">
        ${item('Permasalahan', escapeHtml(d.permasalahan || '-').replace(/\n/g,'<br>'), true)}
        ${item('Alternatif Penyelesaian', escapeHtml(d.alternatif || '-').replace(/\n/g,'<br>'), true)}
      </div>
    `;
    document.getElementById('modalDetailRujukan').classList.add('open');
  }

  function isiTeksTitik(elId, teks) {
    const el = document.getElementById(elId);
    const baris = (teks || '').split('\n').filter(b => b.trim() !== '');
    if (baris.length === 0) baris.push('-');
    el.innerHTML = baris.map(b => `<p class="isi-titik">${escapeHtml(b)}</p>`).join('');
  }

  function cetakRujukan() {
    const d = dataRujukan.find(x => x.id_rujukan == idDetailRujukan);
    if (!d) return;
    document.getElementById('modalDetailRujukan').classList.remove('open');
    document.getElementById('rjPvNama').textContent = d.nama_siswa || '-';
    document.getElementById('rjPvKelas').textContent = (d.kelas || '-') + (d.jurusan ? ' / ' + d.jurusan : '');
    isiTeksTitik('rjPvPermasalahan', d.permasalahan);
    isiTeksTitik('rjPvAlternatif', d.alternatif);
    document.getElementById('rjPvKotaTgl').textContent = 'Banjarmasin, ' + formatTgl(d.tanggal_ttd || new Date().toISOString().slice(0,10));
    cetakElemenViaIframe('printAreaRujukan', '@page { size: A4; margin: 20mm 18mm; }', PRINT_CSS_RUJUKAN);
  }

  // ---------- CETAK VIA IFRAME TERSEMBUNYI (halaman utama TIDAK pernah berubah tampilan) ----------
  // CSS cetak Lembar Rujukan — ukuran teks asli (12pt)
  const PRINT_CSS_RUJUKAN = `
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; }
    body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.4; color: #000; }
    .kertas { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.4; color: #000; }
    .judul-polos { text-align: center; font-weight: bold; margin-bottom: 22px; font-size: 13pt; letter-spacing: 1px; }
    table.form-rj { table-layout: fixed; width: 100%; }
    table.form-rj td { padding: 2px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
    p.isi-titik { min-height: 18px; margin: 2px 0; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
    .rj-ttd { text-align: right; margin-top: 34px; }
    .rj-ttd p { margin-bottom: 6px; }
    .rj-ttd .garis-ttd-inline { display: inline-block; border-bottom: 1px solid #000; min-width: 220px; height: 55px; margin-top: 4px; }
  `;

  // CSS cetak Surat Peringatan — dipadatkan agar muat 1 halaman A4
  const PRINT_CSS_SP = `
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; }
    body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.35; color: #000; }
    .kertas { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.35; color: #000; }
    .kop-surat { display: flex; align-items: center; justify-content: space-between; gap: 8px; border-bottom: 2.5px solid #000; padding-bottom: 4px; margin-bottom: 2px; }
    .kop-surat img { height: 58px; width: auto; flex-shrink: 0; }
    .kop-surat .kop-tengah { flex-grow: 1; text-align: center; line-height: 1.2; }
    .kop-surat .kop-tengah p.baris1 { font-size: 10.5pt; font-weight: normal; margin: 0; }
    .kop-surat .kop-tengah h3.nama-sekolah { font-size: 13.5pt; font-weight: bold; margin: 1px 0; }
    .kop-surat .kop-tengah p.alamat { font-size: 8pt; margin: 0; }
    .judul-dok-kop { text-align: center; font-weight: bold; text-decoration: underline; margin: 10px 0 8px; font-size: 12pt; letter-spacing: 1.5px; }
    .sp-tabel-nama { border-collapse: collapse; margin: 6px 0 8px; table-layout: fixed; width: 100%; }
    .sp-tabel-nama td, .sp-tabel-nama th { border: 1px solid #000; padding: 4px 8px; font-size: 10.5pt; word-wrap: break-word; overflow-wrap: break-word; }
    .sp-tabel-nama th { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .sp-judul-tingkat { text-align: center; font-weight: bold; font-size: 12pt; margin: 10px 0 6px; }
    .sp-halaman-2 { page-break-before: auto; break-before: auto; padding-top: 0; margin-top: 8px; }
    .sp-penutup { text-align: justify; margin-bottom: 12px; text-indent: 28px; font-size: 10.5pt; line-height: 1.35; }
    table.sp-ttd-tabel { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
    table.sp-ttd-tabel td { border: none; font-size: 10.5pt; padding: 1px 4px; vertical-align: top; text-align: center; }
    table.sp-ttd-tabel .ttd-spasi { height: 42px; }
    table.sp-ttd-tabel .garis-ttd { border-bottom: 1px solid #000; width: 70%; margin: 0 auto; }
    .sp-mengetahui { text-align: center; margin-top: 22px; font-size: 10.5pt; }
    .sp-mengetahui > div:first-of-type { height: 72px !important; }
    .sp-tembusan { margin-top: 10px; font-size: 10pt; }
    .sp-tembusan ol { margin-left: 18px; margin-top: 2px; list-style: decimal; }
    .sp-tembusan ol li { display: list-item; margin-bottom: 0; }
    #spPvPelanggaran { list-style: decimal; margin-left: 20px; margin-top: 2px; margin-bottom: 4px; padding-left: 4px; }
    #spPvPelanggaran li { display: list-item; margin-bottom: 1px; font-size: 10.5pt; }
  `;

  let hiddenPrintFrame = null;
  function ambilFramePrintTersembunyi() {
    if (hiddenPrintFrame && document.body.contains(hiddenPrintFrame)) return hiddenPrintFrame;
    hiddenPrintFrame = document.createElement('iframe');
    hiddenPrintFrame.setAttribute('aria-hidden', 'true');
    hiddenPrintFrame.style.position = 'fixed';
    hiddenPrintFrame.style.top = '-9999px';
    hiddenPrintFrame.style.left = '-9999px';
    hiddenPrintFrame.style.width = '0';
    hiddenPrintFrame.style.height = '0';
    hiddenPrintFrame.style.border = '0';
    document.body.appendChild(hiddenPrintFrame);
    return hiddenPrintFrame;
  }

  function cetakElemenViaIframe(idElemenSumber, aturanPage, cssCetak) {
    const sumber = document.getElementById(idElemenSumber);
    if (!sumber) return;
    const frame = ambilFramePrintTersembunyi();
    const idoc = frame.contentWindow.document;
    idoc.open();
    idoc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title></title><style>' + (aturanPage || '') + (cssCetak || '') + '</style></head><body>' + sumber.innerHTML + '</body></html>');
    idoc.close();

    const gambar = Array.from(idoc.images || []);
    const tungguSemuaGambar = Promise.all(gambar.map(img => (img.complete
      ? Promise.resolve()
      : new Promise(resolve => { img.addEventListener('load', resolve, { once: true }); img.addEventListener('error', resolve, { once: true }); })
    )));

    Promise.race([ tungguSemuaGambar, new Promise(resolve => setTimeout(resolve, 1200)) ]).then(() => {
      frame.contentWindow.focus();
      frame.contentWindow.print();
    });
  }

  function muatSP(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list_sp');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { dataSP = data.data; renderTabelSP(); } });
  }

  function badgeSP(jenis) {
    const cls = jenis === 'SP III' ? 'sp-3' : (jenis === 'SP II' ? 'sp-2' : 'sp-1');
    return `<span class="sp-badge ${cls}">${jenis}</span>`;
  }

  function renderTabelSP() {
    const tbody = document.getElementById('isiTabelSP');
    if (dataSP.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6">
        <div class="empty-state">
          <i class="fas fa-triangle-exclamation"></i>
          <p class="empty-title">Belum ada Surat Peringatan</p>
          <p class="empty-desc">Klik tombol "Tambah Surat Peringatan" untuk mulai mencatat.</p>
        </div>
      </td></tr>`;
      return;
    }
    tbody.innerHTML = dataSP.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2">${badgeSP(d.jenis_sp)}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}${d.jurusan ? ' / ' + escapeHtml(d.jurusan) : ''}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_ttd)}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetailSP(${d.id_sp})" class="action-btn action-btn-view mr-1" title="Lihat detail & cetak PDF"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEditSP(${d.id_sp})" class="action-btn action-btn-edit mr-1" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusSP(${d.id_sp})" class="action-btn action-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
  }

  document.getElementById('cariSP').addEventListener('input', function () {
    simpanStateUI();
    muatSP(this.value);
  });

  function kosongkanFormSP() {
    document.getElementById('spId').value = '';
    document.getElementById('spJenis').value = 'SP I';
    ['spNis','spNama','spKelasJurusan','spPelanggaran','spTanggal','spNamaGuru','spNipGuru'].forEach(id => document.getElementById(id).value = '');
  }

  function bukaModalTambahSP() {
    kosongkanFormSP();
    document.getElementById('judulModalSP').textContent = 'Tambah Surat Peringatan';
    document.getElementById('modalSP').classList.add('open');
  }

  function bukaModalEditSP(id) {
    const d = dataSP.find(x => x.id_sp == id);
    if (!d) return;
    kosongkanFormSP();
    document.getElementById('judulModalSP').textContent = 'Edit Surat Peringatan';
    document.getElementById('spId').value = d.id_sp;
    document.getElementById('spJenis').value = d.jenis_sp || 'SP I';
    document.getElementById('spNis').value = d.nis || '';
    document.getElementById('spNama').value = d.nama_siswa || '';
    document.getElementById('spKelasJurusan').value = (d.kelas || '') + (d.jurusan ? ' / ' + d.jurusan : '');
    document.getElementById('spPelanggaran').value = d.pelanggaran || '';
    document.getElementById('spTanggal').value = d.tanggal_ttd || '';
    document.getElementById('spNamaGuru').value = d.nama_guru || '';
    document.getElementById('spNipGuru').value = d.nip_guru || '';
    document.getElementById('modalSP').classList.add('open');
  }

  function tutupModalSP() { document.getElementById('modalSP').classList.remove('open'); }

  function cariSiswaSP() {
    const nis = document.getElementById('spNis').value.trim();
    if (!nis) { alert('Isi NIS dulu ya, baru klik Cari.'); return; }
    const fd = new FormData();
    fd.append('action', 'cari_siswa');
    fd.append('nis', nis);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) {
          document.getElementById('spNama').value = data.data.nama || '';
          document.getElementById('spKelasJurusan').value = (data.data.kelas || '') + (data.data.jurusan ? ' / ' + data.data.jurusan : '');
        } else {
          alert('NIS tidak ditemukan. Silakan isi data secara manual.');
        }
      });
  }

  function simpanSP() {
    const nama = document.getElementById('spNama').value.trim();
    if (!nama) { alert('Nama Siswa wajib diisi.'); return; }
    const btn = document.getElementById('btnSimpanSP');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    const fd = new FormData();
    fd.append('action', 'simpan_sp');
    fd.append('id_sp', document.getElementById('spId').value || 0);
    fd.append('jenis_sp', document.getElementById('spJenis').value);
    fd.append('nis', document.getElementById('spNis').value);
    fd.append('nama_siswa', nama);
    fd.append('kelas', document.getElementById('spKelasJurusan').value);
    fd.append('jurusan', '');
    fd.append('pelanggaran', document.getElementById('spPelanggaran').value);
    fd.append('tanggal_ttd', document.getElementById('spTanggal').value);
    fd.append('nama_guru', document.getElementById('spNamaGuru').value);
    fd.append('nip_guru', document.getElementById('spNipGuru').value);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          tutupModalSP();
          gantiTab('sp');
          muatSP(document.getElementById('cariSP').value);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusSP(id) {
    if (!confirm('Yakin ingin menghapus surat peringatan ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_sp');
    fd.append('id_sp', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          gantiTab('sp');
          muatSP(document.getElementById('cariSP').value);
        }
      });
  }

  function lihatDetailSP(id) {
    idDetailSP = id;
    const d = dataSP.find(x => x.id_sp == id);
    if (!d) return;
    const kelasJurusan = (d.kelas || '') + (d.jurusan ? ' / ' + d.jurusan : '');
    const item = (label, value, full) => `
      <div class="${full ? 'col-span-2' : ''}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">${label}</p>
        <p class="text-sm text-gray-800 leading-relaxed">${value || '<span class="text-gray-300">-</span>'}</p>
      </div>`;
    document.getElementById('isiDetailSP').innerHTML = `
      <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
        ${item('Jenis', badgeSP(d.jenis_sp))}
        ${item('Tanggal TTD', formatTgl(d.tanggal_ttd))}
        ${item('Nama Siswa', escapeHtml(d.nama_siswa))}
        ${item('Kelas/Jurusan', escapeHtml(kelasJurusan || '-'))}
      </div>
      <div class="space-y-4 pt-4">
        ${item('Pelanggaran', escapeHtml(d.pelanggaran || '-').replace(/\n/g,'<br>'), true)}
      </div>
    `;
    document.getElementById('modalDetailSP').classList.add('open');
  }

  function konsekuensiSP(jenis) {
    if (jenis === 'SP I') return 'Jika selama menjalani peringatan ke I (satu) ini dan melakukan pelanggaran disiplin tata tertib sekolah, Maka akan diberikan sanksi Peringatan Ke II (dua) hingga ke III (tiga) dan jika melakukan pelanggaran disiplin tata tertib siswa yang dikatagorikan pelanggaran berat, maka dapat dikembalikan kepada orang tua/wali siswa.';
    if (jenis === 'SP II') return 'Jika selama menjalani peringatan ke II (dua) ini dan melakukan pelanggaran disiplin tata tertib sekolah, Maka akan diberikan sanksi Peringatan ke III (tiga) dan jika melakukan pelanggaran disiplin tata tertib siswa yang dikatagorikan pelanggaran berat, maka dapat dikembalikan kepada orang tua/wali siswa.';
    return 'Jika selama menjalani peringatan ke III (tiga) ini dan melakukan kembali pelanggaran disiplin tata tertib sekolah, yang dikatagorikan pelanggaran berat, maka dapat dikembalikan kepada orang tua/wali siswa.';
  }

  function judulTingkatSP(jenis) {
    if (jenis === 'SP I') return 'PERINGATAN KE I ( SATU )';
    if (jenis === 'SP II') return 'PERINGATAN KE II ( DUA )';
    return 'PERINGATAN KE III ( TIGA )';
  }

  function romawiSP(jenis) {
    if (jenis === 'SP I') return 'I (satu)';
    if (jenis === 'SP II') return 'II (dua)';
    return 'III (tiga)';
  }

  function cetakSP() {
    const d = dataSP.find(x => x.id_sp == idDetailSP);
    if (!d) return;
    document.getElementById('modalDetailSP').classList.remove('open');
    document.getElementById('spPvNama').textContent = d.nama_siswa || '-';
    const kelasJurusan = (d.kelas || '') + (d.jurusan ? ' / ' + d.jurusan : '');
    document.getElementById('spPvKelasJurusan').innerHTML = kelasJurusan.trim() ? escapeHtml(kelasJurusan) : '&nbsp;';
    const poin = (d.pelanggaran || '').split('\n').filter(p => p.trim() !== '');
    document.getElementById('spPvPelanggaran').innerHTML = poin.length
      ? poin.map(p => `<li>${escapeHtml(p)}</li>`).join('')
      : '<li>-</li>';
    document.getElementById('spPvJudulTingkat').textContent = judulTingkatSP(d.jenis_sp);
    document.getElementById('spPvKonsekuensi').textContent = konsekuensiSP(d.jenis_sp);
    document.getElementById('spPvPenutup').textContent = 'Demikian surat peringatan ke ' + romawiSP(d.jenis_sp) + ' ini diberikan agar diperhatikan dan semoga Allah SWT. Selalu membimbing kejalan yang benar dan memberikan taufik dan hidayahNya kepada kita semua.';
    document.getElementById('spPvKotaTgl').textContent = 'Banjarmasin, ' + formatTgl(d.tanggal_ttd || new Date().toISOString().slice(0,10));
    cetakElemenViaIframe('printAreaSP', '@page { size: A4; margin: 12mm 14mm; }', PRINT_CSS_SP);
  }

  document.addEventListener('DOMContentLoaded', () => {
    muatStateUI();
    gantiTab(tabAktif);
    muatRujukan(document.getElementById('cariRujukan').value);
    muatSP(document.getElementById('cariSP').value);
  });
</script>
    </div>
  </body>
</html>