<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];
$daftar_guru_bk = [
    'Pahrurazi, S.Pd', 'Dian Riyani, S.Pd', 'Putri Hidayatie, S.Pd', 'Rini Rodhiati, S.Pd',
    'Gusti Muhammad Fajri Ramadhan, S.Pd', 'Desy Arianti, S.Pd', "Khalisatun Ni'mah, S.Pd",
    'Tiara Wulansari, S.Pd', 'Dhea Nur Aziza, S.Pd', 'Abdul Basith, S.Pd',
];
$nama_kepsek = "Novie Bambang Rumadi, S.T., M.Pd";
$nip_kepsek  = "19781102006041005";
$bulan_indo  = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
$tgl_sekarang = date('d') . ' ' . $bulan_indo[date('F')] . ' ' . date('Y');
$base_url_folder = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'cari_siswa') {
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $q = mysqli_query($koneksi, "SELECT nama, kelas, jurusan, nama_ayah, nama_ibu, no_hp_ayah, no_hp_ibu FROM siswa WHERE nis = '$nis' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        echo json_encode(['success' => (bool) $row, 'data' => $row]);
        exit;
    }

    if ($action === 'list') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR jurusan LIKE '%$keyword%' OR nama_ortu LIKE '%$keyword%' OR permasalahan LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM konsultasi_ortu $where ORDER BY tanggal_pemanggilan DESC, id_konsultasi DESC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan') {
        $id = (int) ($_POST['id_konsultasi'] ?? 0);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan'] ?? '');
        $nama_ortu = mysqli_real_escape_string($koneksi, $_POST['nama_ortu'] ?? '');
        $no_telp = mysqli_real_escape_string($koneksi, $_POST['no_telp'] ?? '');
        $nama_guru_bk = mysqli_real_escape_string($koneksi, $_POST['nama_guru_bk'] ?? '');
        $tgl_panggil = mysqli_real_escape_string($koneksi, $_POST['tanggal_pemanggilan'] ?? '');
        $tgl_datang = mysqli_real_escape_string($koneksi, $_POST['tanggal_kedatangan'] ?? '');
        $permasalahan = mysqli_real_escape_string($koneksi, $_POST['permasalahan'] ?? '');
        $hasil = mysqli_real_escape_string($koneksi, $_POST['hasil_konsultasi'] ?? '');
        $kesepakatan = mysqli_real_escape_string($koneksi, $_POST['kesepakatan'] ?? '');
        $tindak_lanjut = mysqli_real_escape_string($koneksi, $_POST['tindak_lanjut'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }

        $uploadDir = __DIR__ . '/uploads/konsultasi_ortu/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fotoLamaRaw = json_decode($_POST['foto_lama'] ?? '[]', true);
        $fotoFinal = [];
        if (is_array($fotoLamaRaw)) {
            foreach ($fotoLamaRaw as $p) {
                if (is_string($p) && strpos($p, 'uploads/konsultasi_ortu/') === 0) $fotoFinal[] = $p;
            }
        }
        $fotoFinal = array_slice($fotoFinal, 0, 1);
        if (isset($_FILES['foto_baru']) && is_array($_FILES['foto_baru']['name'])) {
            $allowedExt = ['jpg','jpeg','png','webp','gif'];
            $maxSize = 2 * 1024 * 1024;
            $total = count($_FILES['foto_baru']['name']);
            $fotoBaru = [];
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['foto_baru']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (count($fotoBaru) >= 1) break;
                $tmp = $_FILES['foto_baru']['tmp_name'][$i];
                $orig = $_FILES['foto_baru']['name'][$i];
                $size = $_FILES['foto_baru']['size'][$i];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt) || $size > $maxSize) continue;
                $namaBaru = 'ko' . $id_guru_login . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $namaBaru)) {
                    $fotoBaru[] = 'uploads/konsultasi_ortu/' . $namaBaru;
                }
            }
            if (count($fotoBaru) > 0) $fotoFinal = $fotoBaru;
        }
        $dokumentasi = mysqli_real_escape_string($koneksi, json_encode($fotoFinal));

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM konsultasi_ortu WHERE id_konsultasi = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $tgl_datang_sql = $tgl_datang !== '' ? "'$tgl_datang'" : 'NULL';
            $query = "UPDATE konsultasi_ortu SET
                        nis='$nis', nama_siswa='$nama_siswa', kelas='$kelas', jurusan='$jurusan', nama_ortu='$nama_ortu', no_telp='$no_telp',
                        nama_guru_bk='$nama_guru_bk',
                        tanggal_pemanggilan='$tgl_panggil', tanggal_kedatangan=$tgl_datang_sql,
                        permasalahan='$permasalahan', hasil_konsultasi='$hasil', kesepakatan='$kesepakatan',
                        tindak_lanjut='$tindak_lanjut', dokumentasi='$dokumentasi'
                      WHERE id_konsultasi = $id";
        } else {
            $tgl_datang_sql = $tgl_datang !== '' ? "'$tgl_datang'" : 'NULL';
            $query = "INSERT INTO konsultasi_ortu
                        (nis, nama_siswa, kelas, jurusan, nama_ortu, no_telp, nama_guru_bk, tanggal_pemanggilan, tanggal_kedatangan, permasalahan, hasil_konsultasi, kesepakatan, tindak_lanjut, dokumentasi, id_guru)
                      VALUES
                        ('$nis','$nama_siswa','$kelas','$jurusan','$nama_ortu','$no_telp','$nama_guru_bk','$tgl_panggil',$tgl_datang_sql,'$permasalahan','$hasil','$kesepakatan','$tindak_lanjut','$dokumentasi',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Data konsultasi berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus') {
        $id = (int) ($_POST['id_konsultasi'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru, dokumentasi FROM konsultasi_ortu WHERE id_konsultasi = $id");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
            exit;
        }
        $foto = json_decode($row['dokumentasi'] ?? '[]', true);
        if (is_array($foto)) {
            foreach ($foto as $f) {
                $path = __DIR__ . '/' . $f;
                if (is_file($path)) @unlink($path);
            }
        }
        if (mysqli_query($koneksi, "DELETE FROM konsultasi_ortu WHERE id_konsultasi = $id")) {
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}

$daftar_awal = mysqli_query($koneksi, "SELECT * FROM konsultasi_ortu WHERE id_guru = $id_guru_login ORDER BY tanggal_pemanggilan DESC, id_konsultasi DESC");
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sistem Bimbingan Kelompok - SMKN 2 Banjarmasin" />
    <title class="no-print">Konsultasi Orang Tua | Program BK | BK SMKN 2 Banjarmasin</title>
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
      .card-hover { transition: all 0.3s ease; }
      .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }
      .btn-action { transition: all 0.2s ease; }
      .btn-action:hover { transform: scale(1.05); }
      .stat-card { background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%); backdrop-filter: blur(10px); border: 1px solid rgba(47, 108, 110, 0.1); }

      @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
      .animate-slide-in { animation: slideIn 0.5s ease-out; }

      main { box-sizing: border-box; overflow-x: hidden; }
      @media (max-width: 767px) {
        main { margin-left: 0 !important; padding-left: 1rem; padding-right: 1rem; width: 100%; padding-top: 4.5rem; }
        body.overflow-hidden { overflow: hidden; width: 100vw; position: fixed; height: 100vh; }
      }
      @media (min-width: 768px) { main { margin-left: 260px; } }
      .grid { width: 100%; box-sizing: border-box; }
      .grid > * { overflow-x: hidden; }
      .primary-color { color: var(--primary); }
      .primary-bg { background-color: var(--primary-light); }
      .secondary-bg { background-color: #E6EEF0; }

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

      #printAreaKonsultasi { display: none; }
      body.mode-cetak > *:not(#printAreaKonsultasi) { display: none !important; }
      body.mode-cetak #printAreaKonsultasi { display: block !important; }

      @media print {
        @page { size: A4 landscape; margin: 10mm 8mm; }
        body { background: #fff !important; }
        #printAreaKonsultasi table { width: 100%; border-collapse: collapse; font-size: 8.5pt; font-family: 'Times New Roman', serif; table-layout: fixed; }
        #printAreaKonsultasi thead { display: table-header-group; }
        #printAreaKonsultasi tr { page-break-inside: avoid; }
        #printAreaKonsultasi th, #printAreaKonsultasi td { border: 0.75pt solid #000; padding: 4px 5px; text-align: left; vertical-align: middle; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.3; }
        #printAreaKonsultasi th { background: #eaeaea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; text-align: center; font-size: 8.5pt; font-weight: bold; padding: 5px 4px; line-height: 1.25; }
        #printAreaKonsultasi td:first-child, #printAreaKonsultasi th:first-child { text-align: center; }
        #printAreaKonsultasi td:last-child { text-align: center; }
        #printAreaKonsultasi .foto-frame-pdf { width: 100px; height: 78px; max-width: 100%; background: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto; overflow: hidden; }
        #printAreaKonsultasi .foto-frame-pdf img { max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto; }
      }
      .print-kop { text-align: center; font-family: 'Times New Roman', serif; margin-bottom: 10px; }
      .print-kop h2 { margin: 0 0 2px; letter-spacing: 0.3pt; }
      .print-kop p { margin: 0; color: #333; }
    </style>
  </head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8 flex flex-col">

  <div class="no-print mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
      <i class="fas fa-users-cog text-blue-600 mr-2"></i> Konsultasi Orang Tua
    </h1>
    <p class="text-sm text-gray-600">Catat dan kelola kegiatan konsultasi dengan orang tua/wali siswa. Data Anda tersimpan otomatis dan bisa dibuka lagi kapan saja.</p>
  </div>

  <div class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="kotakCari" placeholder="Cari nama siswa, kelas, atau orang tua..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <div class="flex gap-2">
        <button onclick="cetakRekap()" class="btn-action bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Cetak rekap seluruh data konsultasi ke PDF">
          <i class="fas fa-file-pdf mr-1"></i> Export PDF (Rekap)
        </button>
        <button onclick="bukaModalTambah()" class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Tambah catatan konsultasi orang tua baru">
          <i class="fas fa-plus mr-1"></i> Tambah Data
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm" id="tabelKonsultasi">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas/Jurusan</th>
            <th class="px-3 py-2 border-b">Orang Tua/Wali</th>
            <th class="px-3 py-2 border-b">No Telp/HP</th>
            <th class="px-3 py-2 border-b">Guru BK</th>
            <th class="px-3 py-2 border-b">Tgl Pemanggilan</th>
            <th class="px-3 py-2 border-b">Tgl Kedatangan</th>
            <th class="px-3 py-2 border-b">Permasalahan</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelKonsultasi">
          <tr><td colspan="10" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

  <div id="modalForm" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModal" class="text-lg font-bold text-gray-800">Tambah Konsultasi Orang Tua</h2>
        <button onclick="tutupModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 space-y-4">
        <input type="hidden" id="fId">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Bantuan: cari data siswa berdasarkan NIS</label>
          <div class="flex gap-2">
            <input type="text" id="fNis" placeholder="Ketik NIS lalu klik Cari (boleh dikosongkan)" class="flex-grow px-3 py-2 border rounded text-sm">
            <button type="button" onclick="cariSiswa()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-search"></i> Cari</button>
          </div>
          <p class="text-xs text-gray-400 mt-1">Kalau ditemukan, Nama & Kelas akan otomatis terisi. Kalau tidak ada NIS-nya, isi manual saja di bawah.</p>
        </div>

        <h3 class="text-sm font-bold text-gray-700 pt-2 border-t flex items-center gap-2"><i class="fas fa-people-arrows text-blue-500"></i> Data Konsultasi</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
            <input type="text" id="fNamaSiswa" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <input type="text" id="fKelas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
            <input type="text" id="fJurusan" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua/Wali</label>
            <input type="text" id="fNamaOrtu" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">No. Telp/HP</label>
            <input type="text" id="fNoTelp" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Guru BK</label>
            <select id="fGuruBK" class="w-full px-3 py-2 border rounded text-sm">
              <option value="">Pilih Nama Guru</option>
              <?php foreach ($daftar_guru_bk as $nama_guru_opt): ?>
                <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pemanggilan</label>
            <input type="date" id="fTglPanggil" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kedatangan</label>
            <input type="date" id="fTglDatang" class="w-full px-3 py-2 border rounded text-sm">
            <p class="text-xs text-gray-400 mt-1">Kosongkan dulu kalau orang tua belum datang.</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Permasalahan</label>
          <textarea id="fPermasalahan" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Hasil Konsultasi</label>
          <textarea id="fHasil" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Kesepakatan</label>
          <textarea id="fKesepakatan" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tindak Lanjut</label>
          <textarea id="fTindakLanjut" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Foto Dokumentasi <span class="text-gray-400 font-normal">(maks. 1 foto, 2MB)</span></label>
          <input type="file" id="fFotoInput" accept="image/*" onchange="previewFotoKO(event)" class="mb-3 text-sm border rounded-lg px-3 py-2 w-full">
          <div id="boxFotoKO" class="grid grid-cols-3 gap-3"></div>
        </div>
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModal()" class="px-4 py-2 rounded-lg border hover:bg-gray-50 text-sm">Batal</button>
        <button onclick="simpanKonsultasi()" id="btnSimpanKO" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm">
          <i class="fas fa-save mr-1"></i> Simpan
        </button>
      </div>
    </div>
  </div>

  <div id="modalDetail" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Konsultasi</h2>
        <button onclick="document.getElementById('modalDetail').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2" id="isiDetailKO"></div>
    </div>
  </div>

  <div id="printAreaKonsultasi">
    <div class="print-kop">
      <h2 style="font-weight:bold; font-size:14pt;">REKAP KONSULTASI ORANG TUA</h2>
      <p style="font-size:10pt;">SMK Negeri 2 Banjarmasin</p>
    </div>
    <table>
      <colgroup>
        <col style="width:3%;"><col style="width:10%;"><col style="width:5%;"><col style="width:7%;">
        <col style="width:10%;"><col style="width:9%;"><col style="width:16%;"><col style="width:8%;">
        <col style="width:8%;"><col style="width:9%;"><col style="width:15%;">
      </colgroup>
      <thead>
        <tr>
          <th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Jurusan</th><th>Orang Tua/Wali</th>
          <th>No Telp/HP</th><th>Guru BK</th>
          <th>Tgl.<br>Pemanggilan</th><th>Tgl.<br>Kedatangan</th>
          <th>Permasalahan</th><th>Foto Dokumentasi</th>
        </tr>
      </thead>
      <tbody id="isiPrintKonsultasi"></tbody>
    </table>
  </div>

<script>
  const BASE_URL = "<?php echo htmlspecialchars($base_url_folder, ENT_QUOTES); ?>";
  let dataKonsultasi = [];
  let fotoBaruKO = [];
  let fotoLamaKO = [];

  function muatData(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          dataKonsultasi = data.data;
          renderTabel();
        }
      });
  }

  function renderTabel() {
    const tbody = document.getElementById('isiTabelKonsultasi');
    if (dataKonsultasi.length === 0) {
      tbody.innerHTML = `<tr><td colspan="10">
        <div class="empty-state">
          <i class="fas fa-comments"></i>
          <p class="empty-title">Belum ada data Konsultasi Orang Tua</p>
          <p class="empty-desc">Klik tombol "Tambah Data" di atas untuk mulai mencatat.</p>
        </div>
      </td></tr>`;
      return;
    }
    tbody.innerHTML = dataKonsultasi.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}${d.jurusan ? ' / ' + escapeHtml(d.jurusan) : ''}</td>
        <td class="px-3 py-2">${escapeHtml(d.nama_ortu || '-')}</td>
        <td class="px-3 py-2">${escapeHtml(d.no_telp || '-')}</td>
        <td class="px-3 py-2">${escapeHtml(d.nama_guru_bk || '-')}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_pemanggilan)}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_kedatangan)}</td>
        <td class="px-3 py-2 max-w-xs truncate" title="${escapeHtml(d.permasalahan || '')}">${escapeHtml(d.permasalahan || '-')}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetail(${d.id_konsultasi})" class="action-btn action-btn-view mr-1" title="Lihat detail & cetak PDF"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEdit(${d.id_konsultasi})" class="action-btn action-btn-edit mr-1" title="Edit data ini"><i class="fas fa-pen"></i></button>
          <button onclick="hapusData(${d.id_konsultasi})" class="action-btn action-btn-delete" title="Hapus data ini"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
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
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  document.getElementById('kotakCari').addEventListener('input', function () {
    muatData(this.value);
  });

  function kosongkanForm() {
    document.getElementById('fId').value = '';
    ['fNis','fNamaSiswa','fKelas','fJurusan','fNamaOrtu','fNoTelp','fGuruBK','fTglPanggil','fTglDatang','fPermasalahan','fHasil','fKesepakatan','fTindakLanjut'].forEach(id => {
      document.getElementById(id).value = '';
    });
    fotoBaruKO = [];
    fotoLamaKO = [];
    document.getElementById('boxFotoKO').innerHTML = '<p class="text-xs text-gray-400 col-span-3">Belum ada foto.</p>';
  }

  function bukaModalTambah() {
    kosongkanForm();
    document.getElementById('judulModal').textContent = 'Tambah Konsultasi Orang Tua';
    document.getElementById('modalForm').classList.add('open');
  }

  function bukaModalEdit(id) {
    const d = dataKonsultasi.find(x => x.id_konsultasi == id);
    if (!d) return;
    kosongkanForm();
    document.getElementById('judulModal').textContent = 'Edit Konsultasi Orang Tua';
    document.getElementById('fId').value = d.id_konsultasi;
    document.getElementById('fNis').value = d.nis || '';
    document.getElementById('fNamaSiswa').value = d.nama_siswa || '';
    document.getElementById('fKelas').value = d.kelas || '';
    document.getElementById('fJurusan').value = d.jurusan || '';
    document.getElementById('fNamaOrtu').value = d.nama_ortu || '';
    document.getElementById('fNoTelp').value = d.no_telp || '';
    document.getElementById('fGuruBK').value = d.nama_guru_bk || '';
    document.getElementById('fTglPanggil').value = d.tanggal_pemanggilan || '';
    document.getElementById('fTglDatang').value = d.tanggal_kedatangan || '';
    document.getElementById('fPermasalahan').value = d.permasalahan || '';
    document.getElementById('fHasil').value = d.hasil_konsultasi || '';
    document.getElementById('fKesepakatan').value = d.kesepakatan || '';
    document.getElementById('fTindakLanjut').value = d.tindak_lanjut || '';

    try {
      const foto = JSON.parse(d.dokumentasi || '[]');
      if (foto.length > 0) renderFotoLamaKO(foto);
    } catch (e) {}

    document.getElementById('modalForm').classList.add('open');
  }

  function tutupModal() {
    document.getElementById('modalForm').classList.remove('open');
  }

  function cariSiswa() {
    const nis = document.getElementById('fNis').value.trim();
    if (!nis) { alert('Isi NIS dulu ya, baru klik Cari.'); return; }
    const fd = new FormData();
    fd.append('action', 'cari_siswa');
    fd.append('nis', nis);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) {
          document.getElementById('fNamaSiswa').value = data.data.nama || '';
          document.getElementById('fKelas').value = data.data.kelas || '';
          document.getElementById('fJurusan').value = data.data.jurusan || '';
          if (!document.getElementById('fNamaOrtu').value) {
            document.getElementById('fNamaOrtu').value = data.data.nama_ayah || data.data.nama_ibu || '';
          }
          if (!document.getElementById('fNoTelp').value) {
            document.getElementById('fNoTelp').value = data.data.no_hp_ayah || data.data.no_hp_ibu || '';
          }
        } else {
          alert('NIS tidak ditemukan. Silakan isi Nama & Kelas secara manual.');
        }
      });
  }

  function simpanKonsultasi() {
    const namaSiswa = document.getElementById('fNamaSiswa').value.trim();
    if (!namaSiswa) { alert('Nama Siswa wajib diisi.'); return; }

    const btn = document.getElementById('btnSimpanKO');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

    const fd = new FormData();
    fd.append('action', 'simpan');
    fd.append('id_konsultasi', document.getElementById('fId').value || 0);
    fd.append('nis', document.getElementById('fNis').value);
    fd.append('nama_siswa', namaSiswa);
    fd.append('kelas', document.getElementById('fKelas').value);
    fd.append('jurusan', document.getElementById('fJurusan').value);
    fd.append('nama_ortu', document.getElementById('fNamaOrtu').value);
    fd.append('no_telp', document.getElementById('fNoTelp').value);
    fd.append('nama_guru_bk', document.getElementById('fGuruBK').value);
    fd.append('tanggal_pemanggilan', document.getElementById('fTglPanggil').value);
    fd.append('tanggal_kedatangan', document.getElementById('fTglDatang').value);
    fd.append('permasalahan', document.getElementById('fPermasalahan').value);
    fd.append('hasil_konsultasi', document.getElementById('fHasil').value);
    fd.append('kesepakatan', document.getElementById('fKesepakatan').value);
    fd.append('tindak_lanjut', document.getElementById('fTindakLanjut').value);
    fd.append('foto_lama', JSON.stringify(fotoLamaKO));
    fotoBaruKO.forEach(f => { if (f) fd.append('foto_baru[]', f); });

    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          tutupModal();
          muatData(document.getElementById('kotakCari').value);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusData(id) {
    if (!confirm('Yakin ingin menghapus data konsultasi ini? Tindakan ini tidak bisa dibatalkan.')) return;
    const fd = new FormData();
    fd.append('action', 'hapus');
    fd.append('id_konsultasi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) muatData(document.getElementById('kotakCari').value);
      });
  }

  function lihatDetail(id) {
    const d = dataKonsultasi.find(x => x.id_konsultasi == id);
    if (!d) return;
    let foto = [];
    try { foto = JSON.parse(d.dokumentasi || '[]'); } catch (e) {}
    const fotoHtml = foto.length
      ? `<img src="${BASE_URL}${foto[0]}" class="w-full max-w-xs h-48 object-contain bg-white rounded-lg border shadow-sm mt-2 p-1">`
      : `<div class="mt-2 border border-dashed rounded-lg py-6 text-center text-gray-300"><i class="fas fa-image text-2xl mb-1"></i><p class="text-xs text-gray-400">Belum ada foto dokumentasi</p></div>`;

    const item = (label, value, full) => `
      <div class="${full ? 'col-span-2' : ''}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">${label}</p>
        <p class="text-sm text-gray-800 leading-relaxed">${value || '<span class="text-gray-300">-</span>'}</p>
      </div>`;

    document.getElementById('isiDetailKO').innerHTML = `
      <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
        ${item('Nama Siswa', escapeHtml(d.nama_siswa))}
        ${item('Kelas', escapeHtml(d.kelas || '-'))}
        ${item('Jurusan', escapeHtml(d.jurusan || '-'))}
        ${item('Orang Tua/Wali', escapeHtml(d.nama_ortu || '-'))}
        ${item('No Telp/HP', escapeHtml(d.no_telp || '-'))}
        ${item('Guru BK', escapeHtml(d.nama_guru_bk || '-'))}
        ${item('Tanggal Pemanggilan', formatTgl(d.tanggal_pemanggilan))}
        ${item('Tanggal Kedatangan', formatTgl(d.tanggal_kedatangan))}
      </div>
      <div class="space-y-4 py-4 border-b border-gray-100">
        ${item('Permasalahan', escapeHtml(d.permasalahan || '-'), true)}
        ${item('Hasil Konsultasi', escapeHtml(d.hasil_konsultasi || '-'), true)}
        ${item('Kesepakatan', escapeHtml(d.kesepakatan || '-'), true)}
        ${item('Tindak Lanjut', escapeHtml(d.tindak_lanjut || '-'), true)}
      </div>
      <div class="pt-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">Foto Dokumentasi</p>
        ${fotoHtml}
      </div>
    `;
    document.getElementById('modalDetail').classList.add('open');
  }

  function previewFotoKO(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { alert('File ' + file.name + ' bukan foto.'); event.target.value = ''; return; }
    if (file.size > 2 * 1024 * 1024) { alert('Foto ' + file.name + ' terlalu besar (maks 2MB).'); event.target.value = ''; return; }

    fotoBaruKO = [file];
    fotoLamaKO = [];
    const box = document.getElementById('boxFotoKO');
    box.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'relative group';
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.className = 'w-full h-28 object-contain bg-white rounded-lg border shadow-sm p-1';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.innerHTML = '<i class="fas fa-times"></i>';
    btn.title = 'Hapus foto';
    btn.className = 'absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 text-xs shadow';
    btn.onclick = () => { fotoBaruKO = []; wrap.remove(); tampilkanKosongFotoKOJikaPerlu(); };
    wrap.appendChild(img); wrap.appendChild(btn);
    box.appendChild(wrap);
    event.target.value = '';
  }

  function tampilkanKosongFotoKOJikaPerlu() {
    const box = document.getElementById('boxFotoKO');
    if (!box.querySelector('img')) {
      box.innerHTML = '<p class="text-xs text-gray-400 col-span-3">Belum ada foto.</p>';
    }
  }

  function renderFotoLamaKO(paths) {
    const box = document.getElementById('boxFotoKO');
    box.innerHTML = '';
    fotoLamaKO = paths.slice(0, 1);
    fotoLamaKO.forEach(path => {
      const wrap = document.createElement('div');
      wrap.className = 'relative group';
      const img = document.createElement('img');
      img.src = BASE_URL + path;
      img.className = 'w-full h-28 object-contain bg-white rounded-lg border shadow-sm p-1';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.innerHTML = '<i class="fas fa-times"></i>';
      btn.title = 'Hapus foto';
      btn.className = 'absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 text-xs shadow';
      btn.onclick = () => { fotoLamaKO = fotoLamaKO.filter(p => p !== path); wrap.remove(); tampilkanKosongFotoKOJikaPerlu(); };
      wrap.appendChild(img); wrap.appendChild(btn);
      box.appendChild(wrap);
    });
  }

  function cetakRekap() {
    if (dataKonsultasi.length === 0) { alert('Tidak ada data untuk dicetak.'); return; }
    const tbody = document.getElementById('isiPrintKonsultasi');
    tbody.innerHTML = dataKonsultasi.map((d, i) => {
      let foto = [];
      try { foto = JSON.parse(d.dokumentasi || '[]'); } catch (e) {}
      const fotoHtml = foto.length ? `<div class="foto-frame-pdf"><img src="${BASE_URL}${foto[0]}"></div>` : '-';
      return `
        <tr>
          <td style="text-align:center;">${i + 1}</td>
          <td>${escapeHtml(d.nama_siswa)}</td>
          <td>${escapeHtml(d.kelas || '-')}</td>
          <td>${escapeHtml(d.jurusan || '-')}</td>
          <td>${escapeHtml(d.nama_ortu || '-')}</td>
          <td>${escapeHtml(d.no_telp || '-')}</td>
          <td>${escapeHtml(d.nama_guru_bk || '-')}</td>
          <td>${formatTgl(d.tanggal_pemanggilan)}</td>
          <td>${formatTgl(d.tanggal_kedatangan)}</td>
          <td>${escapeHtml(d.permasalahan || '-')}</td>
          <td style="text-align:center;">${fotoHtml}</td>
        </tr>
      `;
    }).join('');

    document.body.classList.add('mode-cetak');
    const imgs = Array.from(tbody.querySelectorAll('img'));
    if (imgs.length === 0) { window.print(); return; }
    Promise.all(imgs.map(img => new Promise(resolve => {
      if (img.complete && img.naturalWidth > 0) { resolve(); return; }
      img.onload = resolve;
      img.onerror = resolve;
    }))).then(() => window.print());
  }

  window.addEventListener('afterprint', () => {
    document.body.classList.remove('mode-cetak');
  });

  document.addEventListener('DOMContentLoaded', () => {
    muatData();
  });
</script>
    </div>
  </body>
</html>