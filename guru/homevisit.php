<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];
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
        $q = mysqli_query($koneksi, "SELECT nama, kelas, alamat_lengkap, nama_ayah, nama_ibu, no_hp_ayah, no_hp_ibu FROM siswa WHERE nis = '$nis' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        echo json_encode(['success' => (bool) $row, 'data' => $row]);
        exit;
    }

    if ($action === 'list') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR nama_ortu_wali LIKE '%$keyword%' OR alamat LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM home_visit $where ORDER BY hari_tanggal DESC, id_visit DESC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan') {
        $id = (int) ($_POST['id_visit'] ?? 0);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $hari_tanggal = mysqli_real_escape_string($koneksi, $_POST['hari_tanggal'] ?? '');
        $nama_ortu_wali = mysqli_real_escape_string($koneksi, $_POST['nama_ortu_wali'] ?? '');
        $masalah = mysqli_real_escape_string($koneksi, $_POST['masalah'] ?? '');
        $hasil_dicapai = mysqli_real_escape_string($koneksi, $_POST['hasil_dicapai'] ?? '');
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
        $tindak_lanjut = mysqli_real_escape_string($koneksi, $_POST['tindak_lanjut'] ?? '');
        $no_surat = mysqli_real_escape_string($koneksi, $_POST['no_surat_tugas'] ?? '');
        $tgl_dinas = mysqli_real_escape_string($koneksi, $_POST['tanggal_dinas'] ?? '');
        $nama_petugas = mysqli_real_escape_string($koneksi, $_POST['nama_petugas'] ?? '');
        $nip_petugas = mysqli_real_escape_string($koneksi, $_POST['nip_petugas'] ?? '');
        $hasil_dinas = mysqli_real_escape_string($koneksi, $_POST['hasil_dinas'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }

        $uploadDir = __DIR__ . '/uploads/home_visit/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fotoLamaRaw = json_decode($_POST['foto_lama'] ?? '[]', true);
        $fotoFinal = [];
        if (is_array($fotoLamaRaw)) {
            foreach ($fotoLamaRaw as $p) {
                if (is_string($p) && strpos($p, 'uploads/home_visit/') === 0) $fotoFinal[] = $p;
            }
        }
        if (isset($_FILES['foto_baru']) && is_array($_FILES['foto_baru']['name'])) {
            $allowedExt = ['jpg','jpeg','png','webp','gif'];
            $maxSize = 2 * 1024 * 1024;
            $total = count($_FILES['foto_baru']['name']);
            for ($i = 0; $i < $total; $i++) {
                if ($_FILES['foto_baru']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (count($fotoFinal) >= 8) break;
                $tmp = $_FILES['foto_baru']['tmp_name'][$i];
                $orig = $_FILES['foto_baru']['name'][$i];
                $size = $_FILES['foto_baru']['size'][$i];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt) || $size > $maxSize) continue;
                $namaBaru = 'hv' . $id_guru_login . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($tmp, $uploadDir . $namaBaru)) {
                    $fotoFinal[] = 'uploads/home_visit/' . $namaBaru;
                }
            }
        }
        $dokumentasi = mysqli_real_escape_string($koneksi, json_encode($fotoFinal));

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM home_visit WHERE id_visit = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $tgl_dinas_sql = $tgl_dinas !== '' ? "'$tgl_dinas'" : 'NULL';
            $query = "UPDATE home_visit SET
                        nis='$nis', nama_siswa='$nama_siswa', kelas='$kelas', alamat='$alamat', hari_tanggal='$hari_tanggal',
                        nama_ortu_wali='$nama_ortu_wali', masalah='$masalah', hasil_dicapai='$hasil_dicapai', keterangan='$keterangan',
                        tindak_lanjut='$tindak_lanjut', dokumentasi='$dokumentasi',
                        no_surat_tugas='$no_surat', tanggal_dinas=$tgl_dinas_sql, nama_petugas='$nama_petugas', nip_petugas='$nip_petugas', hasil_dinas='$hasil_dinas'
                      WHERE id_visit = $id";
        } else {
            $tgl_dinas_sql = $tgl_dinas !== '' ? "'$tgl_dinas'" : 'NULL';
            $query = "INSERT INTO home_visit
                        (nis, nama_siswa, kelas, alamat, hari_tanggal, nama_ortu_wali, masalah, hasil_dicapai, keterangan, tindak_lanjut, dokumentasi, no_surat_tugas, tanggal_dinas, nama_petugas, nip_petugas, hasil_dinas, id_guru)
                      VALUES
                        ('$nis','$nama_siswa','$kelas','$alamat','$hari_tanggal','$nama_ortu_wali','$masalah','$hasil_dicapai','$keterangan','$tindak_lanjut','$dokumentasi','$no_surat',$tgl_dinas_sql,'$nama_petugas','$nip_petugas','$hasil_dinas',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Data home visit berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus') {
        $id = (int) ($_POST['id_visit'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru, dokumentasi FROM home_visit WHERE id_visit = $id");
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
        if (mysqli_query($koneksi, "DELETE FROM home_visit WHERE id_visit = $id")) {
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
    <title class="no-print">Home Visit | Program BK | BK SMKN 2 Banjarmasin</title>
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

      #printAreaVisit, #printAreaDinas { display: none; }
      body.mode-cetak > *:not(#printAreaVisit):not(#printAreaDinas) { display: none !important; }
      body.mode-cetak.cetak-visit #printAreaVisit { display: block !important; }
      body.mode-cetak.cetak-dinas #printAreaDinas { display: block !important; }

      @media print {
        @page { size: A4; margin: 15mm; }
        body { background: #fff !important; }
        .kertas { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.35; color: #000; width: 100%; max-width: 100%; margin: 0 auto; }
        .kop-surat {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 10px;
          border-bottom: 3px solid #000;
          padding-bottom: 6px;
          margin-bottom: 4px;
        }
        .kop-surat img { height: 72px; width: auto; flex-shrink: 0; }
        .kop-surat .kop-tengah { flex-grow: 1; text-align: center; line-height: 1.25; }
        .kop-surat .kop-tengah p.baris1 { font-size: 12pt; font-weight: normal; margin: 0; }
        .kop-surat .kop-tengah h3.nama-sekolah { font-size: 15pt; font-weight: bold; margin: 1px 0; }
        .kop-surat .kop-tengah p.alamat { font-size: 9pt; margin: 0; }
        .judul-dok-kop { text-align: center; font-weight: bold; margin: 16px 0 18px; font-size: 12pt; text-transform: uppercase; }
        table.ttd-tabel { width: 100%; border-collapse: collapse; margin-top: 30px; table-layout: fixed; }
        table.ttd-tabel td { border: none; text-align: center; font-size: 11pt; padding: 2px 6px; vertical-align: top; word-wrap: break-word; }
        table.ttd-tabel .ttd-spasi { height: 55px; }
        table.ttd-tabel .garis-ttd { border-bottom: 1px solid #000; width: 80%; margin: 0 auto; }
        .judul-dok { text-align: center; font-weight: bold; text-decoration: underline; margin: 14px 0; font-size: 12pt; }
        table.form-info { width: 100%; table-layout: fixed; border-collapse: collapse; }
        table.form-info td { padding: 2px 4px; vertical-align: top; font-size: 11pt; line-height: 1.35; word-wrap: break-word; overflow-wrap: break-word; }
        table.form-info-rapat { width: 100%; table-layout: fixed; border-collapse: collapse; }
        table.form-info-rapat td { padding: 0 4px; line-height: 1.5; vertical-align: middle; font-size: 11pt; word-wrap: break-word; overflow-wrap: break-word; }
        table.tabel-visit { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.tabel-visit th, table.tabel-visit td { border: 1px solid #000; padding: 6px; font-size: 10pt; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        table.tabel-visit th { text-align: center; background: #eee !important; -webkit-print-color-adjust: exact; }
        table.tabel-visit td.col-no { text-align: center; width: 5%; }
        table.tabel-visit th.col-no { width: 5%; }
        table.tabel-visit th.col-ortu, table.tabel-visit td.col-ortu { width: 17%; }
        table.tabel-visit th.col-masalah, table.tabel-visit td.col-masalah,
        table.tabel-visit th.col-hasil, table.tabel-visit td.col-hasil { width: 24%; }
        table.tabel-visit th.col-ket, table.tabel-visit td.col-ket { width: 30%; }
        .foto-wrap { margin-top: 12px; page-break-before: always; break-before: page; }
        .foto-wrap p.foto-judul { font-weight: bold; font-size: 11pt; margin-bottom: 10px; }
        .foto-grid { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 12px !important; width: 100%; }
        .foto-grid img { width: 100% !important; height: 160px !important; object-fit: cover; border: 1px solid #000; page-break-inside: avoid; }
        .ttd-wrap { display: flex; justify-content: space-between; margin-top: 40px; }
        .ttd-box { text-align: center; width: 45%; }
        .ttd-space { height: 55px; }
        .mengetahui-box { text-align: center; margin-top: 30px; }
        .garis-ttd-inline { display: inline-block; border-bottom: 1px solid #000; min-width: 260px; height: 10px; line-height: 1; vertical-align: bottom; }
        .pd-ttd-tanggal { text-align: right; margin-top: 40px; margin-right: 5px; padding-right: 5px; }
      }
    </style>
  </head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8 flex flex-col">

  <div class="no-print mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
      <i class="fas fa-house-user text-blue-600 mr-2"></i> Home Visit
    </h1>
    <p class="text-sm text-gray-600">Catat kegiatan kunjungan rumah siswa. Data tersimpan otomatis dan bisa dibuka lagi kapan saja.</p>
  </div>

  <div class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="kotakCari" placeholder="Cari nama siswa, kelas, atau alamat..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <button onclick="bukaModalTambah()" class="btn-action bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
        <i class="fas fa-plus mr-1"></i> Tambah Data
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas</th>
            <th class="px-3 py-2 border-b">Hari/Tanggal</th>
            <th class="px-3 py-2 border-b">Orang Tua/Wali</th>
            <th class="px-3 py-2 border-b">Alamat</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelVisit">
          <tr><td colspan="7" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

  <div id="modalForm" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModal" class="text-lg font-bold text-gray-800">Tambah Home Visit</h2>
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
        </div>

        <h3 class="text-sm font-bold text-gray-700 pt-2 border-t">Data Kunjungan</h3>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Hari/Tanggal Kunjungan</label>
            <input type="date" id="fHariTanggal" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua/Wali</label>
            <input type="text" id="fNamaOrtuWali" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
            <textarea id="fAlamat" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Masalah</label>
          <textarea id="fMasalah" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Hasil yang Dicapai</label>
          <textarea id="fHasilDicapai" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
          <textarea id="fKeterangan" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tindak Lanjut</label>
          <textarea id="fTindakLanjut" rows="2" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>

        <h3 class="text-sm font-bold text-gray-700 pt-2 border-t">Data untuk Laporan Perjalanan Dinas</h3>
        <p class="text-xs text-gray-400">Bagian ini dipakai khusus saat mencetak Laporan Hasil Perjalanan Dinas.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Petugas</label>
            <input type="text" id="fNamaPetugas" class="w-full px-3 py-2 border rounded text-sm" placeholder="Nama guru yang bertugas">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">NIP Petugas</label>
            <input type="text" id="fNipPetugas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Surat Tugas</label>
            <input type="text" id="fNoSurat" class="w-full px-3 py-2 border rounded text-sm" placeholder="Contoh: 800.1.11.1/12/SMKN2BJM/2026">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pelaksanaan Dinas</label>
            <input type="date" id="fTglDinas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Hasil (untuk Laporan Perjalanan Dinas)</label>
          <textarea id="fHasilDinas" rows="4" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Foto Dokumentasi (maks. 8 foto, @2MB)</label>
          <input type="file" id="fFotoInput" accept="image/*" multiple onchange="previewFotoHV(event)" class="mb-3 text-sm border rounded-lg px-3 py-2 w-full">
          <div id="boxFotoHV" class="grid grid-cols-3 gap-3"></div>
        </div>
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModal()" class="px-4 py-2 rounded-lg border text-sm">Batal</button>
        <button onclick="simpanVisit()" id="btnSimpanHV" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">
          <i class="fas fa-save mr-1"></i> Simpan
        </button>
      </div>
    </div>
  </div>

  <div id="modalDetail" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Home Visit</h2>
        <button onclick="document.getElementById('modalDetail').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2" id="isiDetailHV"></div>
      <div class="px-6 py-4 border-t flex flex-wrap justify-end gap-2">
        <button onclick="cetakKunjungan()" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold">
          <i class="fas fa-file-pdf mr-1"></i> Cetak Form Kunjungan Rumah
        </button>
        <button onclick="cetakDinas()" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold">
          <i class="fas fa-file-pdf mr-1"></i> Cetak Laporan Perjalanan Dinas
        </button>
      </div>
    </div>
  </div>

  <div id="printAreaVisit">
    <div class="kertas">
      <div class="kop-surat">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8dzqL0l1c4CGbGxZHXxQsPJv58W89Ha-md1QD-EYxjA&s=10" alt="Logo Provinsi Kalimantan Selatan">
        <div class="kop-tengah">
          <p class="baris1">PEMERINTAH PROVINSI KALIMANTAN SELATAN</p>
          <p class="baris1">DINAS PENDIDIKAN DAN KEBUDAYAAN</p>
          <h3 class="nama-sekolah">SMK NEGERI 2 BANJARMASIN</h3>
          <p class="alamat">Jalan Hasan Basri Nomor 6, Banjarmasin, Kalimantan Selatan 70123</p>
          <p class="alamat">Telepon (0511) 3304677</p>
          <p class="alamat">NPSN: 30304268, Laman: https://smkn2-bjm.sch.id, Pos-el: surel@smkn2-bjm.sch.id</p>
        </div>
        <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo SMKN 2 Banjarmasin">
      </div>

      <div class="judul-dok-kop">KUNJUNGAN RUMAH (HOME VISIT)</div>

      <table class="form-info" style="width:100%; margin-bottom:8px;">
        <tr><td style="width:110px;">Nama</td><td style="width:15px;">:</td><td id="pvNama"></td></tr>
        <tr><td>Kelas</td><td>:</td><td id="pvKelas"></td></tr>
        <tr><td>Hari/Tanggal</td><td>:</td><td id="pvTanggal"></td></tr>
        <tr><td>Alamat</td><td>:</td><td id="pvAlamat"></td></tr>
      </table>

      <table class="tabel-visit">
        <thead>
          <tr><th class="col-no">No</th><th class="col-ortu">Orang Tua/Wali</th><th class="col-masalah">Masalah</th><th class="col-hasil">Hasil Yang Dicapai</th><th class="col-ket">Keterangan</th></tr>
        </thead>
        <tbody>
          <tr>
            <td class="col-no">1</td>
            <td id="pvOrtu" class="col-ortu"></td>
            <td id="pvMasalah" class="col-masalah"></td>
            <td id="pvHasil" class="col-hasil"></td>
            <td id="pvKet" class="col-ket"></td>
          </tr>
        </tbody>
      </table>

      <table class="ttd-tabel">
        <tr>
          <td style="width:50%;"></td>
          <td id="pvKotaTgl" style="width:50%;">Banjarmasin, ..........................</td>
        </tr>
        <tr>
          <td>Orang Tua/Wali</td>
          <td>Guru Bimbingan Konseling</td>
        </tr>
        <tr>
          <td class="ttd-spasi"></td>
          <td class="ttd-spasi"></td>
        </tr>
        <tr>
          <td><div class="garis-ttd">&nbsp;</div></td>
          <td><div class="garis-ttd">&nbsp;</div></td>
        </tr>
      </table>

      <div class="mengetahui-box">
        Mengetahui,<br>Kepala Sekolah,
        <div class="ttd-space"></div>
        <div style="font-weight:bold; text-decoration: underline;">
          <?php echo $nama_kepsek; ?>
        </div>
        <div style="font-weight:normal;">NIP. <?php echo $nip_kepsek; ?></div>
      </div>

      <div id="pvFotoWrap" class="foto-wrap" style="display:none;">
        <p class="foto-judul">Dokumentasi Foto:</p>
        <div id="pvFotoGrid" class="foto-grid"></div>
      </div>
    </div>
  </div>

  <div id="printAreaDinas">
    <div class="kertas">
      <div class="judul-dok" style="margin-top:30px;">LAPORAN HASIL PERJALANAN DINAS</div>

      <table class="form-info-rapat" style="width:100%; margin-top:20px;">
        <colgroup>
          <col style="width:110px;">
          <col style="width:14px;">
          <col>
        </colgroup>
        <tr><td colspan="3">Yang Melakukan Perjalanan Dinas :</td></tr>
        <tr><td>Nama</td><td>:</td><td id="pdNamaPetugas"></td></tr>
        <tr><td>NIP</td><td>:</td><td id="pdNipPetugas"></td></tr>
        <tr><td>Jabatan</td><td>:</td><td>Guru Bimbingan dan Konseling</td></tr>
      </table>

      <p style="margin-top:14px;">Menyampaikan Laporan Perjalanan Dinas sebagai berikut :</p>

      <p style="margin-top:10px;"><b>Dasar</b></p>
      <p id="pdDasar">Surat Tugas Kepala SMK Negeri 2 Banjarmasin Nomor : </p>

      <p style="margin-top:10px;">Perjalanan Dinas dilakukan pada tanggal <span id="pdTanggal">.............................</span></p>

      <p style="margin-top:10px;"><b>Keperluan :</b></p>
      <p>Home Visit</p>

      <p style="margin-top:10px;"><b>Hasil :</b></p>
      <p id="pdHasil" style="white-space: pre-line;"></p>

      <div class="pd-ttd-tanggal">
        <p id="pdKotaTgl">Banjarmasin, .......................... 2026</p>
        <div class="ttd-space"></div>
        <div class="garis-ttd-inline">&nbsp;</div>
      </div>
    </div>
  </div>

<script>
  const BASE_URL = "<?php echo htmlspecialchars($base_url_folder, ENT_QUOTES); ?>";
  let dataVisit = [];
  let fotoBaruHV = [];
  let fotoLamaHV = [];
  let idDetailAktif = null;

  function muatData(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { dataVisit = data.data; renderTabel(); } });
  }

  function renderTabel() {
    const tbody = document.getElementById('isiTabelVisit');
    if (dataVisit.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-gray-400">Belum ada data. Klik "Tambah Data" untuk mulai mencatat.</td></tr>';
      return;
    }
    tbody.innerHTML = dataVisit.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}</td>
        <td class="px-3 py-2">${formatTgl(d.hari_tanggal)}</td>
        <td class="px-3 py-2">${escapeHtml(d.nama_ortu_wali || '-')}</td>
        <td class="px-3 py-2 max-w-xs truncate" title="${escapeHtml(d.alamat || '')}">${escapeHtml(d.alamat || '-')}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetail(${d.id_visit})" class="text-gray-600 hover:text-gray-900 mr-2" title="Lihat Detail & Cetak"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEdit(${d.id_visit})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusData(${d.id_visit})" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></button>
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
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
  }

  document.getElementById('kotakCari').addEventListener('input', function () { muatData(this.value); });

  function kosongkanForm() {
    document.getElementById('fId').value = '';
    ['fNis','fNamaSiswa','fKelas','fHariTanggal','fNamaOrtuWali','fAlamat','fMasalah','fHasilDicapai','fKeterangan','fTindakLanjut','fNamaPetugas','fNipPetugas','fNoSurat','fTglDinas','fHasilDinas'].forEach(id => {
      document.getElementById(id).value = '';
    });
    document.getElementById('fHasilDinas').value =
      '1. Menjalin komunikasi langsung dengan orang tua/wali siswa.\n2. Mengetahui kondisi lingkungan tempat tinggal siswa.\n3. Menyampaikan informasi terkait perkembangan belajar dan sikap siswa di sekolah serta di tempat PK.\n4. Mencari solusi atas permasalahan yang dialami siswa.';
    fotoBaruHV = [];
    fotoLamaHV = [];
    document.getElementById('boxFotoHV').innerHTML = '<p class="text-xs text-gray-400 col-span-3">Belum ada foto.</p>';
  }

  function bukaModalTambah() {
    kosongkanForm();
    document.getElementById('judulModal').textContent = 'Tambah Home Visit';
    document.getElementById('modalForm').classList.add('open');
  }

  function bukaModalEdit(id) {
    const d = dataVisit.find(x => x.id_visit == id);
    if (!d) return;
    kosongkanForm();
    document.getElementById('judulModal').textContent = 'Edit Home Visit';
    document.getElementById('fId').value = d.id_visit;
    document.getElementById('fNis').value = d.nis || '';
    document.getElementById('fNamaSiswa').value = d.nama_siswa || '';
    document.getElementById('fKelas').value = d.kelas || '';
    document.getElementById('fHariTanggal').value = d.hari_tanggal || '';
    document.getElementById('fNamaOrtuWali').value = d.nama_ortu_wali || '';
    document.getElementById('fAlamat').value = d.alamat || '';
    document.getElementById('fMasalah').value = d.masalah || '';
    document.getElementById('fHasilDicapai').value = d.hasil_dicapai || '';
    document.getElementById('fKeterangan').value = d.keterangan || '';
    document.getElementById('fTindakLanjut').value = d.tindak_lanjut || '';
    document.getElementById('fNamaPetugas').value = d.nama_petugas || '';
    document.getElementById('fNipPetugas').value = d.nip_petugas || '';
    document.getElementById('fNoSurat').value = d.no_surat_tugas || '';
    document.getElementById('fTglDinas').value = d.tanggal_dinas || '';
    if (d.hasil_dinas) document.getElementById('fHasilDinas').value = d.hasil_dinas;

    try {
      const foto = JSON.parse(d.dokumentasi || '[]');
      if (foto.length > 0) renderFotoLamaHV(foto);
    } catch (e) {}

    document.getElementById('modalForm').classList.add('open');
  }

  function tutupModal() { document.getElementById('modalForm').classList.remove('open'); }

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
          if (!document.getElementById('fAlamat').value) document.getElementById('fAlamat').value = data.data.alamat_lengkap || '';
          if (!document.getElementById('fNamaOrtuWali').value) document.getElementById('fNamaOrtuWali').value = data.data.nama_ayah || data.data.nama_ibu || '';
        } else {
          alert('NIS tidak ditemukan. Silakan isi data secara manual.');
        }
      });
  }

  function simpanVisit() {
    const namaSiswa = document.getElementById('fNamaSiswa').value.trim();
    if (!namaSiswa) { alert('Nama Siswa wajib diisi.'); return; }

    const btn = document.getElementById('btnSimpanHV');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

    const fd = new FormData();
    fd.append('action', 'simpan');
    fd.append('id_visit', document.getElementById('fId').value || 0);
    fd.append('nis', document.getElementById('fNis').value);
    fd.append('nama_siswa', namaSiswa);
    fd.append('kelas', document.getElementById('fKelas').value);
    fd.append('hari_tanggal', document.getElementById('fHariTanggal').value);
    fd.append('nama_ortu_wali', document.getElementById('fNamaOrtuWali').value);
    fd.append('alamat', document.getElementById('fAlamat').value);
    fd.append('masalah', document.getElementById('fMasalah').value);
    fd.append('hasil_dicapai', document.getElementById('fHasilDicapai').value);
    fd.append('keterangan', document.getElementById('fKeterangan').value);
    fd.append('tindak_lanjut', document.getElementById('fTindakLanjut').value);
    fd.append('nama_petugas', document.getElementById('fNamaPetugas').value);
    fd.append('nip_petugas', document.getElementById('fNipPetugas').value);
    fd.append('no_surat_tugas', document.getElementById('fNoSurat').value);
    fd.append('tanggal_dinas', document.getElementById('fTglDinas').value);
    fd.append('hasil_dinas', document.getElementById('fHasilDinas').value);
    fd.append('foto_lama', JSON.stringify(fotoLamaHV));
    fotoBaruHV.forEach(f => { if (f) fd.append('foto_baru[]', f); });

    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) { tutupModal(); muatData(document.getElementById('kotakCari').value); }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusData(id) {
    if (!confirm('Yakin ingin menghapus data home visit ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus');
    fd.append('id_visit', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { alert(data.message); if (data.success) muatData(document.getElementById('kotakCari').value); });
  }

  function lihatDetail(id) {
    idDetailAktif = id;
    const d = dataVisit.find(x => x.id_visit == id);
    if (!d) return;
    let foto = [];
    try { foto = JSON.parse(d.dokumentasi || '[]'); } catch (e) {}
    const fotoHtml = foto.length
      ? `<div class="grid grid-cols-2 gap-2 mt-2">${foto.map(f => `<img src="${BASE_URL}${f}" class="w-full h-48 object-cover rounded border">`).join('')}</div>`
      : '<p class="text-gray-400 text-xs mt-1">Tidak ada foto.</p>';

    document.getElementById('isiDetailHV').innerHTML = `
      <p><b>Nama Siswa:</b> ${escapeHtml(d.nama_siswa)}</p>
      <p><b>Kelas:</b> ${escapeHtml(d.kelas || '-')}</p>
      <p><b>Hari/Tanggal:</b> ${formatTgl(d.hari_tanggal)}</p>
      <p><b>Orang Tua/Wali:</b> ${escapeHtml(d.nama_ortu_wali || '-')}</p>
      <p><b>Alamat:</b> ${escapeHtml(d.alamat || '-')}</p>
      <p><b>Masalah:</b> ${escapeHtml(d.masalah || '-')}</p>
      <p><b>Hasil yang Dicapai:</b> ${escapeHtml(d.hasil_dicapai || '-')}</p>
      <p><b>Keterangan:</b> ${escapeHtml(d.keterangan || '-')}</p>
      <p><b>Tindak Lanjut:</b> ${escapeHtml(d.tindak_lanjut || '-')}</p>
      <p><b>Foto Dokumentasi:</b></p>
      ${fotoHtml}
    `;
    document.getElementById('modalDetail').classList.add('open');
  }

  function previewFotoHV(event) {
    const box = document.getElementById('boxFotoHV');
    if (box.querySelector('p')) box.innerHTML = '';
    Array.from(event.target.files).forEach(file => {
      if (!file.type.startsWith('image/')) { alert('File ' + file.name + ' bukan foto.'); return; }
      if (file.size > 2 * 1024 * 1024) { alert('Foto ' + file.name + ' terlalu besar (maks 2MB).'); return; }
      if (document.querySelectorAll('#boxFotoHV img').length >= 8) { alert('Maksimal 8 foto.'); return; }
      fotoBaruHV.push(file);
      const idx = fotoBaruHV.length - 1;
      const wrap = document.createElement('div');
      wrap.className = 'relative group';
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.className = 'w-full h-24 object-cover rounded border';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.innerHTML = '<i class="fas fa-times"></i>';
      btn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs';
      btn.onclick = () => { fotoBaruHV[idx] = null; wrap.remove(); };
      wrap.appendChild(img); wrap.appendChild(btn);
      box.appendChild(wrap);
    });
    event.target.value = '';
  }

  function renderFotoLamaHV(paths) {
    const box = document.getElementById('boxFotoHV');
    if (box.querySelector('p')) box.innerHTML = '';
    fotoLamaHV = paths.slice();
    paths.forEach(path => {
      const wrap = document.createElement('div');
      wrap.className = 'relative group';
      const img = document.createElement('img');
      img.src = BASE_URL + path;
      img.className = 'w-full h-24 object-cover rounded border';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.innerHTML = '<i class="fas fa-times"></i>';
      btn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs';
      btn.onclick = () => { fotoLamaHV = fotoLamaHV.filter(p => p !== path); wrap.remove(); };
      wrap.appendChild(img); wrap.appendChild(btn);
      box.appendChild(wrap);
    });
  }

  function cetakKunjungan() {
    const d = dataVisit.find(x => x.id_visit == idDetailAktif);
    if (!d) return;

    document.getElementById('pvNama').textContent = d.nama_siswa || '-';
    document.getElementById('pvKelas').textContent = d.kelas || '-';
    document.getElementById('pvTanggal').textContent = formatTgl(d.hari_tanggal);
    document.getElementById('pvAlamat').textContent = d.alamat || '-';
    document.getElementById('pvOrtu').textContent = d.nama_ortu_wali || '-';
    document.getElementById('pvMasalah').textContent = d.masalah || '-';
    document.getElementById('pvHasil').textContent = d.hasil_dicapai || '-';
    document.getElementById('pvKet').textContent = d.keterangan || '-';
    document.getElementById('pvKotaTgl').textContent = 'Banjarmasin, ' + formatTgl(d.hari_tanggal || new Date().toISOString().slice(0,10));

    let foto = [];
    try { foto = JSON.parse(d.dokumentasi || '[]'); } catch (e) {}
    console.log('DEBUG dokumentasi mentah dari database:', d.dokumentasi);
    console.log('DEBUG hasil parsing (daftar path foto):', foto);
    console.log('DEBUG BASE_URL terhitung:', BASE_URL);
    const fotoWrap = document.getElementById('pvFotoWrap');
    const fotoGrid = document.getElementById('pvFotoGrid');
    fotoGrid.innerHTML = '';
    if (foto.length > 0) {
      fotoWrap.style.display = 'block';
      foto.forEach(f => {
        const img = document.createElement('img');
        img.src = BASE_URL + f;
        img.style.marginRight = '5px';
        fotoGrid.appendChild(img);
      });
    } else {
      fotoWrap.style.display = 'none';
      console.warn('DEBUG data dokumentasi kosong untuk id_visit ini, jadi tidak ada foto untuk ditampilkan.');
    }

    document.body.classList.add('mode-cetak', 'cetak-visit');

    const imgs = Array.from(fotoGrid.querySelectorAll('img'));
    if (imgs.length === 0) {
      window.print();
      return;
    }
    Promise.all(imgs.map(img => new Promise(resolve => {
      if (img.complete && img.naturalWidth > 0) { resolve(); return; }
      img.onload = resolve;
      img.onerror = function () {
        console.error('DEBUG gagal memuat foto, URL yang dicoba:', img.src);
        const errBox = document.createElement('div');
        errBox.style.cssText = 'width:100%;height:160px;border:2px dashed red;display:flex;align-items:center;justify-content:center;font-size:8pt;color:red;text-align:center;padding:4px;word-break:break-all;';
        errBox.textContent = 'Foto tidak ditemukan: ' + img.src;
        img.replaceWith(errBox);
        resolve();
      };
    }))).then(() => window.print());
  }

  function cetakDinas() {
    const d = dataVisit.find(x => x.id_visit == idDetailAktif);
    if (!d) return;

    document.getElementById('pdNamaPetugas').textContent = d.nama_petugas || '.......................................';
    document.getElementById('pdNipPetugas').textContent = d.nip_petugas || '.......................................';
    document.getElementById('pdDasar').textContent = 'Surat Tugas Kepala SMK Negeri 2 Banjarmasin Nomor : ' + (d.no_surat_tugas || '.......................................');
    document.getElementById('pdTanggal').textContent = formatTgl(d.tanggal_dinas || d.hari_tanggal);
    document.getElementById('pdHasil').textContent = d.hasil_dinas || '-';
    document.getElementById('pdKotaTgl').textContent = 'Banjarmasin, ' + formatTgl(d.tanggal_dinas || d.hari_tanggal || new Date().toISOString().slice(0,10));

    document.body.classList.add('mode-cetak', 'cetak-dinas');
    window.print();
  }

  window.addEventListener('afterprint', () => {
    document.body.classList.remove('mode-cetak', 'cetak-visit', 'cetak-dinas');
  });

  document.addEventListener('DOMContentLoaded', () => { muatData(); });
</script>
    </div>
  </body>
</html>