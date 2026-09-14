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
        $q = mysqli_query($koneksi, "SELECT nama, kelas, jurusan, tempat_lahir, tanggal_lahir FROM siswa WHERE nis = '$nis' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if ($row) {
            $ttl = '';
            if (!empty($row['tempat_lahir'])) $ttl .= $row['tempat_lahir'];
            if (!empty($row['tanggal_lahir'])) {
                $ttl .= ($ttl !== '' ? ', ' : '') . date('d-m-Y', strtotime($row['tanggal_lahir']));
            }
            $row['ttl'] = $ttl;
        }
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

    // ===== PENGUNDURAN DIRI =====
    if ($action === 'list_pd') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR nama_wali LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR jurusan LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM surat_pengunduran_diri $where ORDER BY id_pd ASC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_pd') {
        $id = (int) ($_POST['id_pd'] ?? 0);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $ttl_siswa = mysqli_real_escape_string($koneksi, $_POST['ttl_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan'] ?? '');
        $nama_wali = mysqli_real_escape_string($koneksi, $_POST['nama_wali'] ?? '');
        $alamat_wali = mysqli_real_escape_string($koneksi, $_POST['alamat_wali'] ?? '');
        $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan'] ?? '');
        $tanggal_ttd = mysqli_real_escape_string($koneksi, $_POST['tanggal_ttd'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_pengunduran_diri WHERE id_pd = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $query = "UPDATE surat_pengunduran_diri SET nis='$nis', nama_siswa='$nama_siswa', ttl_siswa='$ttl_siswa',
                        kelas='$kelas', jurusan='$jurusan', nama_wali='$nama_wali', alamat_wali='$alamat_wali',
                        alasan='$alasan', tanggal_ttd='$tanggal_ttd'
                      WHERE id_pd = $id";
        } else {
            $query = "INSERT INTO surat_pengunduran_diri (nis, nama_siswa, ttl_siswa, kelas, jurusan, nama_wali, alamat_wali, alasan, tanggal_ttd, id_guru)
                      VALUES ('$nis','$nama_siswa','$ttl_siswa','$kelas','$jurusan','$nama_wali','$alamat_wali','$alasan','$tanggal_ttd',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Surat pengunduran diri berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus_pd') {
        $id = (int) ($_POST['id_pd'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_pengunduran_diri WHERE id_pd = $id");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
            exit;
        }
        if (mysqli_query($koneksi, "DELETE FROM surat_pengunduran_diri WHERE id_pd = $id")) {
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    // ===== PINDAH SEKOLAH =====
    if ($action === 'list_ps') {
        $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
        $where = "WHERE id_guru = $id_guru_login";
        if ($keyword !== '') {
            $where .= " AND (nama_siswa LIKE '%$keyword%' OR nama_wali LIKE '%$keyword%' OR kelas LIKE '%$keyword%' OR jurusan LIKE '%$keyword%' OR sekolah_tujuan LIKE '%$keyword%')";
        }
        $q = mysqli_query($koneksi, "SELECT * FROM surat_pindah_sekolah $where ORDER BY id_pindah ASC");
        $data = [];
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_ps') {
        $id = (int) ($_POST['id_pindah'] ?? 0);
        $nis = mysqli_real_escape_string($koneksi, $_POST['nis'] ?? '');
        $nama_siswa = mysqli_real_escape_string($koneksi, $_POST['nama_siswa'] ?? '');
        $ttl_siswa = mysqli_real_escape_string($koneksi, $_POST['ttl_siswa'] ?? '');
        $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas'] ?? '');
        $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan'] ?? '');
        $nama_wali = mysqli_real_escape_string($koneksi, $_POST['nama_wali'] ?? '');
        $alamat_wali = mysqli_real_escape_string($koneksi, $_POST['alamat_wali'] ?? '');
        $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan'] ?? '');
        $sekolah_tujuan = mysqli_real_escape_string($koneksi, $_POST['sekolah_tujuan'] ?? '');
        $tanggal_ttd = mysqli_real_escape_string($koneksi, $_POST['tanggal_ttd'] ?? '');

        if ($nama_siswa === '') {
            echo json_encode(['success' => false, 'message' => 'Nama siswa wajib diisi.']);
            exit;
        }

        if ($id > 0) {
            $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_pindah_sekolah WHERE id_pindah = $id");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;
            if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
                exit;
            }
            $query = "UPDATE surat_pindah_sekolah SET nis='$nis', nama_siswa='$nama_siswa', ttl_siswa='$ttl_siswa',
                        kelas='$kelas', jurusan='$jurusan', nama_wali='$nama_wali', alamat_wali='$alamat_wali',
                        alasan='$alasan', sekolah_tujuan='$sekolah_tujuan', tanggal_ttd='$tanggal_ttd'
                      WHERE id_pindah = $id";
        } else {
            $query = "INSERT INTO surat_pindah_sekolah (nis, nama_siswa, ttl_siswa, kelas, jurusan, nama_wali, alamat_wali, alasan, sekolah_tujuan, tanggal_ttd, id_guru)
                      VALUES ('$nis','$nama_siswa','$ttl_siswa','$kelas','$jurusan','$nama_wali','$alamat_wali','$alasan','$sekolah_tujuan','$tanggal_ttd',$id_guru_login)";
        }

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(['success' => true, 'message' => 'Surat pindah sekolah berhasil disimpan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'hapus_ps') {
        $id = (int) ($_POST['id_pindah'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM surat_pindah_sekolah WHERE id_pindah = $id");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;
        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan atau bukan milik Anda.']);
            exit;
        }
        if (mysqli_query($koneksi, "DELETE FROM surat_pindah_sekolah WHERE id_pindah = $id")) {
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

      #printAreaRujukan, #printAreaSP, #printAreaPD, #printAreaPS { display: none; }
      body.mode-cetak > *:not(#printAreaRujukan):not(#printAreaSP):not(#printAreaPD):not(#printAreaPS) { display: none !important; }
      body.mode-cetak.cetak-rujukan #printAreaRujukan { display: block !important; }
      body.mode-cetak.cetak-sp #printAreaSP { display: block !important; }
      body.mode-cetak.cetak-pd #printAreaPD { display: block !important; }
      body.mode-cetak.cetak-ps #printAreaPS { display: block !important; }

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
    <button id="tabBtnPD" class="tab-btn" onclick="gantiTab('pd')"><i class="fas fa-door-open mr-1"></i> Pengunduran Diri</button>
    <button id="tabBtnPS" class="tab-btn" onclick="gantiTab('ps')"><i class="fas fa-right-from-bracket mr-1"></i> Pindah Sekolah</button>
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

  <div id="panelPD" class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow" style="display:none;">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariPD" placeholder="Cari nama siswa, wali, kelas, atau jurusan..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <button onclick="bukaModalTambahPD()" class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Tambah surat pengunduran diri baru">
        <i class="fas fa-plus mr-1"></i> Tambah Surat Pengunduran Diri
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas/Jurusan</th>
            <th class="px-3 py-2 border-b">Nama Wali</th>
            <th class="px-3 py-2 border-b">Tanggal</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelPD"><tr><td colspan="6" class="text-center py-6 text-gray-400">Memuat data...</td></tr></tbody>
      </table>
    </div>
  </div>

  <div id="panelPS" class="no-print bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow" style="display:none;">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="cariPS" placeholder="Cari nama siswa, wali, kelas, atau sekolah tujuan..."
          class="w-full pl-9 pr-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
      </div>
      <button onclick="bukaModalTambahPS()" class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm" title="Tambah surat pindah sekolah baru">
        <i class="fas fa-plus mr-1"></i> Tambah Surat Pindah Sekolah
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="bg-gray-100 text-left text-gray-700">
            <th class="px-3 py-2 border-b">No</th>
            <th class="px-3 py-2 border-b">Nama Siswa</th>
            <th class="px-3 py-2 border-b">Kelas/Jurusan</th>
            <th class="px-3 py-2 border-b">Nama Wali</th>
            <th class="px-3 py-2 border-b">Sekolah Tujuan</th>
            <th class="px-3 py-2 border-b">Tanggal</th>
            <th class="px-3 py-2 border-b text-center">Aksi</th>
          </tr>
        </thead>
        <tbody id="isiTabelPS"><tr><td colspan="7" class="text-center py-6 text-gray-400">Memuat data...</td></tr></tbody>
      </table>
    </div>
  </div>
</main>
<script>
(function () {
  try {
    var t = localStorage.getItem('admBk_tab') || 'rujukan';
    var panel = { rujukan: document.getElementById('panelRujukan'), sp: document.getElementById('panelSP'), pd: document.getElementById('panelPD'), ps: document.getElementById('panelPS') };
    var btn = { rujukan: document.getElementById('tabBtnRujukan'), sp: document.getElementById('tabBtnSP'), pd: document.getElementById('tabBtnPD'), ps: document.getElementById('tabBtnPS') };
    var semuaAda = panel.rujukan && panel.sp && panel.pd && panel.ps && btn.rujukan && btn.sp && btn.pd && btn.ps;
    if (semuaAda) {
      Object.keys(panel).forEach(function (k) {
        panel[k].style.display = (k === t) ? 'block' : 'none';
        btn[k].classList.toggle('active', k === t);
      });
    }
    var kr = localStorage.getItem('admBk_cariRujukan');
    var ks = localStorage.getItem('admBk_cariSP');
    var kpd = localStorage.getItem('admBk_cariPD');
    var kps = localStorage.getItem('admBk_cariPS');
    if (kr !== null) {
      var ir = document.getElementById('cariRujukan');
      if (ir) ir.value = kr;
    }
    if (ks !== null) {
      var is = document.getElementById('cariSP');
      if (is) is.value = ks;
    }
    if (kpd !== null) {
      var ipd = document.getElementById('cariPD');
      if (ipd) ipd.value = kpd;
    }
    if (kps !== null) {
      var ips = document.getElementById('cariPS');
      if (ips) ips.value = kps;
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

  <div id="modalPD" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModalPD" class="text-lg font-bold text-gray-800">Tambah Surat Pengunduran Diri</h2>
        <button onclick="tutupModalPD()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 space-y-4">
        <input type="hidden" id="pdId">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cari data siswa berdasarkan NIS</label>
          <div class="flex gap-2">
            <input type="text" id="pdNis" placeholder="Ketik NIS lalu klik Cari, data siswa terisi otomatis" class="flex-grow px-3 py-2 border rounded text-sm">
            <button type="button" onclick="cariSiswaPD()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-search"></i> Cari</button>
          </div>
          <p class="text-xs text-gray-400 mt-1">Boleh dikosongkan lalu isi data siswa secara manual di bawah.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-gray-400 -mb-2">Data Siswa</div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
            <input type="text" id="pdNamaSiswa" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tempat/Tanggal Lahir</label>
            <input type="text" id="pdTtlSiswa" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <input type="text" id="pdKelas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
            <input type="text" id="pdJurusan" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t">
          <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-gray-400 -mb-2">Data Orang Tua/Wali</div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua/Wali *</label>
            <input type="text" id="pdNamaWali" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Rumah</label>
            <input type="text" id="pdAlamatWali" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pengunduran Diri</label>
          <textarea id="pdAlasan" rows="3" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Surat</label>
          <input type="date" id="pdTanggal" class="w-full px-3 py-2 border rounded text-sm">
        </div>
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModalPD()" class="px-4 py-2 rounded-lg border text-sm">Batal</button>
        <button onclick="simpanPD()" id="btnSimpanPD" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
      </div>
    </div>
  </div>

  <div id="modalDetailPD" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Surat Pengunduran Diri</h2>
        <button onclick="document.getElementById('modalDetailPD').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2 break-words" id="isiDetailPD"></div>
      <div class="px-6 py-4 border-t flex justify-end gap-2">
        <button onclick="cetakPD()" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
      </div>
    </div>
  </div>

  <div id="modalPS" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
        <h2 id="judulModalPS" class="text-lg font-bold text-gray-800">Tambah Surat Pindah Sekolah</h2>
        <button onclick="tutupModalPS()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 space-y-4">
        <input type="hidden" id="psId">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Cari data siswa berdasarkan NIS</label>
          <div class="flex gap-2">
            <input type="text" id="psNis" placeholder="Ketik NIS lalu klik Cari, data siswa terisi otomatis" class="flex-grow px-3 py-2 border rounded text-sm">
            <button type="button" onclick="cariSiswaPS()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-search"></i> Cari</button>
          </div>
          <p class="text-xs text-gray-400 mt-1">Boleh dikosongkan lalu isi data siswa secara manual di bawah.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-gray-400 -mb-2">Data Siswa</div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
            <input type="text" id="psNamaSiswa" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tempat/Tanggal Lahir</label>
            <input type="text" id="psTtlSiswa" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
            <input type="text" id="psKelas" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jurusan</label>
            <input type="text" id="psJurusan" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t">
          <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-gray-400 -mb-2">Data Orang Tua/Wali</div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua/Wali *</label>
            <input type="text" id="psNamaWali" class="w-full px-3 py-2 border rounded text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Rumah</label>
            <input type="text" id="psAlamatWali" class="w-full px-3 py-2 border rounded text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pindah Sekolah</label>
          <textarea id="psAlasan" rows="3" class="w-full px-3 py-2 border rounded text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pindah Sekolah Ke *</label>
          <input type="text" id="psSekolahTujuan" placeholder="Nama sekolah tujuan" class="w-full px-3 py-2 border rounded text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Surat</label>
          <input type="date" id="psTanggal" class="w-full px-3 py-2 border rounded text-sm">
        </div>
      </div>
      <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
        <button onclick="tutupModalPS()" class="px-4 py-2 rounded-lg border text-sm">Batal</button>
        <button onclick="simpanPS()" id="btnSimpanPS" class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
      </div>
    </div>
  </div>

  <div id="modalDetailPS" class="modal no-print fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-lg font-bold text-gray-800">Detail Surat Pindah Sekolah</h2>
        <button onclick="document.getElementById('modalDetailPS').classList.remove('open')" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6 text-sm space-y-2 break-words" id="isiDetailPS"></div>
      <div class="px-6 py-4 border-t flex justify-end gap-2">
        <button onclick="cetakPS()" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
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

  <div id="printAreaPD">
    <div class="kertas">
      <table style="width:100%; margin-bottom:14px;"><tr>
        <td style="vertical-align:top;">Perihal : Pengunduran Diri</td>
        <td id="pdPvTanggal" style="vertical-align:top; text-align:right;">Tanggal , ..........................</td>
      </tr></table>
      <p>Yth.</p>
      <p style="font-weight:bold; margin-bottom:10px;">Kepala SMK Negeri 2 Banjarmasin</p>
      <p>Di &ndash;</p>
      <p style="margin-bottom:14px;">Tempat</p>
      <p style="margin-bottom:6px;">Yang bertanda tangan di bawah ini :</p>
      <table class="form-rj" style="width:100%; margin-bottom:6px;">
        <tr><td style="width:150px;">nama</td><td style="width:15px;">:</td><td id="pdPvNamaWali"></td></tr>
        <tr><td>alamat rumah</td><td>:</td><td id="pdPvAlamatWali"></td></tr>
      </table>
      <p style="margin-bottom:6px;">selaku orang tua/wali siswa dari</p>
      <table class="form-rj" style="width:100%; margin-bottom:10px;">
        <tr><td style="width:150px;">nama</td><td style="width:15px;">:</td><td id="pdPvNamaSiswa"></td></tr>
        <tr><td>tempat/tanggal lahir</td><td>:</td><td id="pdPvTtlSiswa"></td></tr>
        <tr><td>Nomor Induk Siswa</td><td>:</td><td id="pdPvNis"></td></tr>
        <tr><td>Kelas/Jurusan</td><td>:</td><td id="pdPvKelasJurusan"></td></tr>
      </table>
      <p style="text-align:justify;">dengan ini mengajukan permohonan pengunduran diri dari SMK Negeri 2 Banjarmasin Provinsi Kalimantan Selatan karena</p>
      <div id="pdPvAlasan" style="margin:4px 0 20px;"></div>
      <p style="text-align:justify; margin-bottom:24px;">Demikian surat permohonan disampaikan agar menjadi pemakluman dan atas terkabulnya permohonan ini dihaturkan banyak terima kasih.</p>
      <div class="pd-ttd">
        <p>Hormat kami,</p>
        <p>Orang Tua Siswa/Wali Siswa,</p>
        <div class="pd-ruang-materai"></div>
        <p class="pd-label-materai">Materai 10.000</p>
        <div class="garis-ttd-inline"></div>
        <p id="pdPvNamaWaliTtd">&nbsp;</p>
      </div>
    </div>
  </div>

  <div id="printAreaPS">
    <div class="kertas">
      <table style="width:100%; margin-bottom:14px;"><tr>
        <td style="vertical-align:top;">Perihal : Pengunduran Diri ( Pindah Sekolah )</td>
        <td id="psPvTanggal" style="vertical-align:top; text-align:right;">Tanggal , ..........................</td>
      </tr></table>
      <p>Yth.</p>
      <p style="font-weight:bold; margin-bottom:10px;">Kepala SMK Negeri 2 Banjarmasin</p>
      <p>Di &ndash;</p>
      <p style="margin-bottom:14px;">Tempat</p>
      <p style="margin-bottom:6px;">Yang bertanda tangan di bawah ini :</p>
      <table class="form-rj" style="width:100%; margin-bottom:6px;">
        <tr><td style="width:150px;">nama</td><td style="width:15px;">:</td><td id="psPvNamaWali"></td></tr>
        <tr><td>alamat rumah</td><td>:</td><td id="psPvAlamatWali"></td></tr>
      </table>
      <p style="margin-bottom:6px;">selaku orang tua/wali siswa dari</p>
      <table class="form-rj" style="width:100%; margin-bottom:10px;">
        <tr><td style="width:150px;">nama</td><td style="width:15px;">:</td><td id="psPvNamaSiswa"></td></tr>
        <tr><td>tempat/tanggal lahir</td><td>:</td><td id="psPvTtlSiswa"></td></tr>
        <tr><td>Nomor Induk Siswa</td><td>:</td><td id="psPvNis"></td></tr>
        <tr><td>Kelas/Jurusan</td><td>:</td><td id="psPvKelasJurusan"></td></tr>
      </table>
      <p style="text-align:justify;">dengan ini mengajukan permohonan pengunduran diri dari SMK Negeri 2 Banjarmasin Provinsi Kalimantan Selatan karena</p>
      <div id="psPvAlasan" style="margin:4px 0 14px;"></div>
      <p style="margin-bottom:14px;">pindah sekolah ke <span id="psPvSekolahTujuan"></span></p>
      <p style="text-align:justify; margin-bottom:24px;">Demikian surat permohonan disampaikan agar menjadi pemakluman dan atas terkabulnya permohonan ini dihaturkan banyak terima kasih.</p>
      <div class="pd-ttd">
        <p>Hormat kami,</p>
        <p>Orang Tua Siswa/Wali Siswa,</p>
        <div class="pd-ruang-materai"></div>
        <p class="pd-label-materai">Materai 10.000</p>
        <div class="garis-ttd-inline"></div>
        <p id="psPvNamaWaliTtd">&nbsp;</p>
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
  const STORAGE_KEY_CARI_PD = 'admBk_cariPD';
  const STORAGE_KEY_CARI_PS = 'admBk_cariPS';
  let dataPD = [];
  let dataPS = [];
  let idDetailPD = null;
  let idDetailPS = null;

  function simpanStateUI() {
    try {
      localStorage.setItem(STORAGE_KEY_TAB, tabAktif);
      localStorage.setItem(STORAGE_KEY_CARI_RJ, document.getElementById('cariRujukan').value || '');
      localStorage.setItem(STORAGE_KEY_CARI_SP, document.getElementById('cariSP').value || '');
      localStorage.setItem(STORAGE_KEY_CARI_PD, document.getElementById('cariPD').value || '');
      localStorage.setItem(STORAGE_KEY_CARI_PS, document.getElementById('cariPS').value || '');
    } catch (e) {}
  }

  function muatStateUI() {
    try {
      const t = localStorage.getItem(STORAGE_KEY_TAB);
      if (['rujukan', 'sp', 'pd', 'ps'].includes(t)) tabAktif = t;
      const kr = localStorage.getItem(STORAGE_KEY_CARI_RJ);
      const ks = localStorage.getItem(STORAGE_KEY_CARI_SP);
      const kpd = localStorage.getItem(STORAGE_KEY_CARI_PD);
      const kps = localStorage.getItem(STORAGE_KEY_CARI_PS);
      if (kr !== null) document.getElementById('cariRujukan').value = kr;
      if (ks !== null) document.getElementById('cariSP').value = ks;
      if (kpd !== null) document.getElementById('cariPD').value = kpd;
      if (kps !== null) document.getElementById('cariPS').value = kps;
    } catch (e) {}
  }

  function gantiTab(tab) {
    tabAktif = tab;
    document.getElementById('panelRujukan').style.display = tab === 'rujukan' ? 'block' : 'none';
    document.getElementById('panelSP').style.display = tab === 'sp' ? 'block' : 'none';
    document.getElementById('panelPD').style.display = tab === 'pd' ? 'block' : 'none';
    document.getElementById('panelPS').style.display = tab === 'ps' ? 'block' : 'none';
    document.getElementById('tabBtnRujukan').classList.toggle('active', tab === 'rujukan');
    document.getElementById('tabBtnSP').classList.toggle('active', tab === 'sp');
    document.getElementById('tabBtnPD').classList.toggle('active', tab === 'pd');
    document.getElementById('tabBtnPS').classList.toggle('active', tab === 'ps');
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

  // ================= MODUL PENGUNDURAN DIRI =================
  const PRINT_CSS_PD = `
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; }
    body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.4; color: #000; }
    .kertas { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.4; color: #000; }
    table.form-rj { table-layout: fixed; width: 100%; }
    table.form-rj td { padding: 2px 4px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
    p.isi-titik { min-height: 18px; margin: 2px 0; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
    .pd-ttd { text-align: left; margin-top: 20px; width: 260px; }
    .pd-ttd p { margin: 0 0 2px; }
    .pd-ttd .pd-ruang-materai { height: 60px; }
    .pd-ttd .pd-label-materai { font-style: italic; margin: 0 0 2px; }
    .pd-ttd .garis-ttd-inline { display: inline-block; min-width: 220px; height: 14px; margin-top: 2px; }
  `;

  function muatPD(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list_pd');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { dataPD = data.data; renderTabelPD(); } });
  }

  function renderTabelPD() {
    const tbody = document.getElementById('isiTabelPD');
    if (dataPD.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6">
        <div class="empty-state">
          <i class="fas fa-door-open"></i>
          <p class="empty-title">Belum ada Surat Pengunduran Diri</p>
          <p class="empty-desc">Klik tombol "Tambah Surat Pengunduran Diri" untuk mulai mencatat.</p>
        </div>
      </td></tr>`;
      return;
    }
    tbody.innerHTML = dataPD.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}${d.jurusan ? ' / ' + escapeHtml(d.jurusan) : ''}</td>
        <td class="px-3 py-2">${escapeHtml(d.nama_wali || '-')}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_ttd)}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetailPD(${d.id_pd})" class="action-btn action-btn-view mr-1" title="Lihat detail & cetak PDF"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEditPD(${d.id_pd})" class="action-btn action-btn-edit mr-1" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusPD(${d.id_pd})" class="action-btn action-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
  }

  document.getElementById('cariPD').addEventListener('input', function () {
    simpanStateUI();
    muatPD(this.value);
  });

  function kosongkanFormPD() {
    document.getElementById('pdId').value = '';
    ['pdNis','pdNamaWali','pdAlamatWali','pdNamaSiswa','pdTtlSiswa','pdKelas','pdJurusan','pdAlasan','pdTanggal'].forEach(id => document.getElementById(id).value = '');
  }

  function bukaModalTambahPD() {
    kosongkanFormPD();
    document.getElementById('judulModalPD').textContent = 'Tambah Surat Pengunduran Diri';
    document.getElementById('modalPD').classList.add('open');
  }

  function bukaModalEditPD(id) {
    const d = dataPD.find(x => x.id_pd == id);
    if (!d) return;
    kosongkanFormPD();
    document.getElementById('judulModalPD').textContent = 'Edit Surat Pengunduran Diri';
    document.getElementById('pdId').value = d.id_pd;
    document.getElementById('pdNis').value = d.nis || '';
    document.getElementById('pdNamaWali').value = d.nama_wali || '';
    document.getElementById('pdAlamatWali').value = d.alamat_wali || '';
    document.getElementById('pdNamaSiswa').value = d.nama_siswa || '';
    document.getElementById('pdTtlSiswa').value = d.ttl_siswa || '';
    document.getElementById('pdKelas').value = d.kelas || '';
    document.getElementById('pdJurusan').value = d.jurusan || '';
    document.getElementById('pdAlasan').value = d.alasan || '';
    document.getElementById('pdTanggal').value = d.tanggal_ttd || '';
    document.getElementById('modalPD').classList.add('open');
  }

  function tutupModalPD() { document.getElementById('modalPD').classList.remove('open'); }

  function cariSiswaPD() {
    const nis = document.getElementById('pdNis').value.trim();
    if (!nis) { alert('Isi NIS dulu ya, baru klik Cari.'); return; }
    const fd = new FormData();
    fd.append('action', 'cari_siswa');
    fd.append('nis', nis);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) {
          document.getElementById('pdNamaSiswa').value = data.data.nama || '';
          document.getElementById('pdKelas').value = data.data.kelas || '';
          document.getElementById('pdJurusan').value = data.data.jurusan || '';
          if (data.data.ttl) document.getElementById('pdTtlSiswa').value = data.data.ttl;
        } else {
          alert('NIS tidak ditemukan. Silakan isi data secara manual.');
        }
      });
  }

  function simpanPD() {
    const nama = document.getElementById('pdNamaSiswa').value.trim();
    if (!nama) { alert('Nama Siswa wajib diisi.'); return; }
    const btn = document.getElementById('btnSimpanPD');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    const fd = new FormData();
    fd.append('action', 'simpan_pd');
    fd.append('id_pd', document.getElementById('pdId').value || 0);
    fd.append('nis', document.getElementById('pdNis').value);
    fd.append('nama_wali', document.getElementById('pdNamaWali').value);
    fd.append('alamat_wali', document.getElementById('pdAlamatWali').value);
    fd.append('nama_siswa', nama);
    fd.append('ttl_siswa', document.getElementById('pdTtlSiswa').value);
    fd.append('kelas', document.getElementById('pdKelas').value);
    fd.append('jurusan', document.getElementById('pdJurusan').value);
    fd.append('alasan', document.getElementById('pdAlasan').value);
    fd.append('tanggal_ttd', document.getElementById('pdTanggal').value);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          tutupModalPD();
          gantiTab('pd');
          muatPD(document.getElementById('cariPD').value);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusPD(id) {
    if (!confirm('Yakin ingin menghapus surat ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_pd');
    fd.append('id_pd', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          gantiTab('pd');
          muatPD(document.getElementById('cariPD').value);
        }
      });
  }

  function lihatDetailPD(id) {
    idDetailPD = id;
    const d = dataPD.find(x => x.id_pd == id);
    if (!d) return;
    const kelasJurusan = (d.kelas || '') + (d.jurusan ? ' / ' + d.jurusan : '');
    const item = (label, value, full) => `
      <div class="${full ? 'col-span-2' : ''}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">${label}</p>
        <p class="text-sm text-gray-800 leading-relaxed">${value || '<span class="text-gray-300">-</span>'}</p>
      </div>`;
    document.getElementById('isiDetailPD').innerHTML = `
      <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
        ${item('Nama Siswa', escapeHtml(d.nama_siswa))}
        ${item('Tanggal Surat', formatTgl(d.tanggal_ttd))}
        ${item('Kelas/Jurusan', escapeHtml(kelasJurusan || '-'))}
        ${item('Nama Wali', escapeHtml(d.nama_wali || '-'))}
        ${item('Alamat Wali', escapeHtml(d.alamat_wali || '-'), true)}
      </div>
      <div class="space-y-4 pt-4">
        ${item('Alasan', escapeHtml(d.alasan || '-').replace(/\n/g,'<br>'), true)}
      </div>
    `;
    document.getElementById('modalDetailPD').classList.add('open');
  }

  function kapitalSetiapKata(str) {
    if (!str) return '';
    return str.toString().trim().toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
  }

  function kapitalKalimat(str) {
    if (!str) return '';
    const t = str.toString().trim();
    if (!t) return '';
    return t.replace(/([.!?]\s*|^)([a-z])/g, (m, sep, huruf) => sep + huruf.toUpperCase());
  }

  function isiTeksTitikKapital(elId, teks) {
    const el = document.getElementById(elId);
    const baris = (teks || '').split('\n').filter(b => b.trim() !== '');
    if (baris.length === 0) baris.push('-');
    el.innerHTML = baris.map(b => `<p class="isi-titik">${escapeHtml(kapitalKalimat(b))}</p>`).join('');
  }

  function kapitalKelasJurusan(kelas, jurusan) {
    const k = (kelas || '-').toString().trim().replace(/\s*\/\s*/g, ' ').toUpperCase();
    const j = jurusan ? ' ' + kapitalSetiapKata(jurusan).replace(/\s*\/\s*/g, ' ') : '';
    return (k + j).replace(/\s+/g, ' ').trim();
  }

  function cetakPD() {
    const d = dataPD.find(x => x.id_pd == idDetailPD);
    if (!d) return;
    document.getElementById('modalDetailPD').classList.remove('open');
    document.getElementById('pdPvTanggal').textContent = 'Tanggal , ' + formatTgl(d.tanggal_ttd || new Date().toISOString().slice(0,10));
    document.getElementById('pdPvNamaWali').textContent = kapitalSetiapKata(d.nama_wali) || '-';
    document.getElementById('pdPvAlamatWali').textContent = kapitalKalimat(d.alamat_wali) || '-';
    document.getElementById('pdPvNamaSiswa').textContent = kapitalSetiapKata(d.nama_siswa) || '-';
    document.getElementById('pdPvTtlSiswa').textContent = kapitalKalimat(d.ttl_siswa) || '-';
    document.getElementById('pdPvNis').textContent = d.nis || '-';
    document.getElementById('pdPvKelasJurusan').textContent = kapitalKelasJurusan(d.kelas, d.jurusan);
    isiTeksTitikKapital('pdPvAlasan', d.alasan);
    document.getElementById('pdPvNamaWaliTtd').textContent = kapitalSetiapKata(d.nama_wali);
    cetakElemenViaIframe('printAreaPD', '@page { size: A4; margin: 20mm 18mm; }', PRINT_CSS_PD);
  }

  // ================= MODUL PINDAH SEKOLAH =================
  function muatPS(keyword = '') {
    const fd = new FormData();
    fd.append('action', 'list_ps');
    fd.append('keyword', keyword);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { dataPS = data.data; renderTabelPS(); } });
  }

  function renderTabelPS() {
    const tbody = document.getElementById('isiTabelPS');
    if (dataPS.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7">
        <div class="empty-state">
          <i class="fas fa-right-from-bracket"></i>
          <p class="empty-title">Belum ada Surat Pindah Sekolah</p>
          <p class="empty-desc">Klik tombol "Tambah Surat Pindah Sekolah" untuk mulai mencatat.</p>
        </div>
      </td></tr>`;
      return;
    }
    tbody.innerHTML = dataPS.map((d, i) => `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2">${i + 1}</td>
        <td class="px-3 py-2 font-medium">${escapeHtml(d.nama_siswa)}</td>
        <td class="px-3 py-2">${escapeHtml(d.kelas || '-')}${d.jurusan ? ' / ' + escapeHtml(d.jurusan) : ''}</td>
        <td class="px-3 py-2">${escapeHtml(d.nama_wali || '-')}</td>
        <td class="px-3 py-2">${escapeHtml(d.sekolah_tujuan || '-')}</td>
        <td class="px-3 py-2">${formatTgl(d.tanggal_ttd)}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatDetailPS(${d.id_pindah})" class="action-btn action-btn-view mr-1" title="Lihat detail & cetak PDF"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEditPS(${d.id_pindah})" class="action-btn action-btn-edit mr-1" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusPS(${d.id_pindah})" class="action-btn action-btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `).join('');
  }

  document.getElementById('cariPS').addEventListener('input', function () {
    simpanStateUI();
    muatPS(this.value);
  });

  function kosongkanFormPS() {
    document.getElementById('psId').value = '';
    ['psNis','psNamaWali','psAlamatWali','psNamaSiswa','psTtlSiswa','psKelas','psJurusan','psAlasan','psSekolahTujuan','psTanggal'].forEach(id => document.getElementById(id).value = '');
  }

  function bukaModalTambahPS() {
    kosongkanFormPS();
    document.getElementById('judulModalPS').textContent = 'Tambah Surat Pindah Sekolah';
    document.getElementById('modalPS').classList.add('open');
  }

  function bukaModalEditPS(id) {
    const d = dataPS.find(x => x.id_pindah == id);
    if (!d) return;
    kosongkanFormPS();
    document.getElementById('judulModalPS').textContent = 'Edit Surat Pindah Sekolah';
    document.getElementById('psId').value = d.id_pindah;
    document.getElementById('psNis').value = d.nis || '';
    document.getElementById('psNamaWali').value = d.nama_wali || '';
    document.getElementById('psAlamatWali').value = d.alamat_wali || '';
    document.getElementById('psNamaSiswa').value = d.nama_siswa || '';
    document.getElementById('psTtlSiswa').value = d.ttl_siswa || '';
    document.getElementById('psKelas').value = d.kelas || '';
    document.getElementById('psJurusan').value = d.jurusan || '';
    document.getElementById('psAlasan').value = d.alasan || '';
    document.getElementById('psSekolahTujuan').value = d.sekolah_tujuan || '';
    document.getElementById('psTanggal').value = d.tanggal_ttd || '';
    document.getElementById('modalPS').classList.add('open');
  }

  function tutupModalPS() { document.getElementById('modalPS').classList.remove('open'); }

  function cariSiswaPS() {
    const nis = document.getElementById('psNis').value.trim();
    if (!nis) { alert('Isi NIS dulu ya, baru klik Cari.'); return; }
    const fd = new FormData();
    fd.append('action', 'cari_siswa');
    fd.append('nis', nis);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data) {
          document.getElementById('psNamaSiswa').value = data.data.nama || '';
          document.getElementById('psKelas').value = data.data.kelas || '';
          document.getElementById('psJurusan').value = data.data.jurusan || '';
          if (data.data.ttl) document.getElementById('psTtlSiswa').value = data.data.ttl;
        } else {
          alert('NIS tidak ditemukan. Silakan isi data secara manual.');
        }
      });
  }

  function simpanPS() {
    const nama = document.getElementById('psNamaSiswa').value.trim();
    if (!nama) { alert('Nama Siswa wajib diisi.'); return; }
    const btn = document.getElementById('btnSimpanPS');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    const fd = new FormData();
    fd.append('action', 'simpan_ps');
    fd.append('id_pindah', document.getElementById('psId').value || 0);
    fd.append('nis', document.getElementById('psNis').value);
    fd.append('nama_wali', document.getElementById('psNamaWali').value);
    fd.append('alamat_wali', document.getElementById('psAlamatWali').value);
    fd.append('nama_siswa', nama);
    fd.append('ttl_siswa', document.getElementById('psTtlSiswa').value);
    fd.append('kelas', document.getElementById('psKelas').value);
    fd.append('jurusan', document.getElementById('psJurusan').value);
    fd.append('alasan', document.getElementById('psAlasan').value);
    fd.append('sekolah_tujuan', document.getElementById('psSekolahTujuan').value);
    fd.append('tanggal_ttd', document.getElementById('psTanggal').value);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          tutupModalPS();
          gantiTab('ps');
          muatPS(document.getElementById('cariPS').value);
        }
      })
      .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
      });
  }

  function hapusPS(id) {
    if (!confirm('Yakin ingin menghapus surat ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_ps');
    fd.append('id_pindah', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if (data.success) {
          gantiTab('ps');
          muatPS(document.getElementById('cariPS').value);
        }
      });
  }

  function lihatDetailPS(id) {
    idDetailPS = id;
    const d = dataPS.find(x => x.id_pindah == id);
    if (!d) return;
    const kelasJurusan = (d.kelas || '') + (d.jurusan ? ' / ' + d.jurusan : '');
    const item = (label, value, full) => `
      <div class="${full ? 'col-span-2' : ''}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-0.5">${label}</p>
        <p class="text-sm text-gray-800 leading-relaxed">${value || '<span class="text-gray-300">-</span>'}</p>
      </div>`;
    document.getElementById('isiDetailPS').innerHTML = `
      <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100">
        ${item('Nama Siswa', escapeHtml(d.nama_siswa))}
        ${item('Tanggal Surat', formatTgl(d.tanggal_ttd))}
        ${item('Kelas/Jurusan', escapeHtml(kelasJurusan || '-'))}
        ${item('Nama Wali', escapeHtml(d.nama_wali || '-'))}
        ${item('Sekolah Tujuan', escapeHtml(d.sekolah_tujuan || '-'))}
        ${item('Alamat Wali', escapeHtml(d.alamat_wali || '-'), true)}
      </div>
      <div class="space-y-4 pt-4">
        ${item('Alasan', escapeHtml(d.alasan || '-').replace(/\n/g,'<br>'), true)}
      </div>
    `;
    document.getElementById('modalDetailPS').classList.add('open');
  }

  function cetakPS() {
    const d = dataPS.find(x => x.id_pindah == idDetailPS);
    if (!d) return;
    document.getElementById('modalDetailPS').classList.remove('open');
    document.getElementById('psPvTanggal').textContent = 'Tanggal , ' + formatTgl(d.tanggal_ttd || new Date().toISOString().slice(0,10));
    document.getElementById('psPvNamaWali').textContent = kapitalSetiapKata(d.nama_wali) || '-';
    document.getElementById('psPvAlamatWali').textContent = kapitalKalimat(d.alamat_wali) || '-';
    document.getElementById('psPvNamaSiswa').textContent = kapitalSetiapKata(d.nama_siswa) || '-';
    document.getElementById('psPvTtlSiswa').textContent = kapitalKalimat(d.ttl_siswa) || '-';
    document.getElementById('psPvNis').textContent = d.nis || '-';
    document.getElementById('psPvKelasJurusan').textContent = kapitalKelasJurusan(d.kelas, d.jurusan);
    isiTeksTitikKapital('psPvAlasan', d.alasan);
    document.getElementById('psPvSekolahTujuan').textContent = kapitalSetiapKata(d.sekolah_tujuan) || '-';
    document.getElementById('psPvNamaWaliTtd').textContent = kapitalSetiapKata(d.nama_wali);
    cetakElemenViaIframe('printAreaPS', '@page { size: A4; margin: 20mm 18mm; }', PRINT_CSS_PD);
  }

  document.addEventListener('DOMContentLoaded', () => {
    muatStateUI();
    gantiTab(tabAktif);
    muatRujukan(document.getElementById('cariRujukan').value);
    muatSP(document.getElementById('cariSP').value);
    muatPD(document.getElementById('cariPD').value);
    muatPS(document.getElementById('cariPS').value);
  });
</script>
    </div>
  </body>
</html>