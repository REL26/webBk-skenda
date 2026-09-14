<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$id_guru_login = (int) $_SESSION['id_guru'];

$DAFTAR_GURU_BK = [
    'Pahrurazi, S.Pd', 'Dian Riyani, S.Pd', 'Putri Hidayatie, S.Pd', 'Rini Rodhiati, S.Pd',
    'Gusti Muhammad Fajri Ramadhan, S.Pd', 'Desy Arianti, S.Pd', "Khalisatun Ni'mah, S.Pd",
    'Tiara Wulansari, S.Pd', 'Dhea Nur Aziza, S.Pd', 'Abdul Basith, S.Pd',
];
$base_url_folder = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bk_pengaturan (
    id_pengaturan INT(11) NOT NULL AUTO_INCREMENT,
    nama_pengaturan VARCHAR(100) NOT NULL,
    nilai TEXT DEFAULT NULL,
    PRIMARY KEY (id_pengaturan),
    UNIQUE KEY nama_pengaturan (nama_pengaturan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bk_monitoring_lanjutan (
    id_lanjutan INT(11) NOT NULL AUTO_INCREMENT,
    id_materi INT(11) NOT NULL,
    judul VARCHAR(200) NOT NULL,
    catatan TEXT DEFAULT NULL,
    tanggal_rencana DATE DEFAULT NULL,
    status ENUM('rencana','proses','selesai') NOT NULL DEFAULT 'rencana',
    nama_guru VARCHAR(150) DEFAULT NULL,
    id_guru INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY id_materi (id_materi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cekKolomFungsi = mysqli_query($koneksi, "SHOW COLUMNS FROM bk_materi LIKE 'fungsi_layanan'");
if ($cekKolomFungsi && mysqli_num_rows($cekKolomFungsi) === 0) {
    mysqli_query($koneksi, "ALTER TABLE bk_materi ADD COLUMN fungsi_layanan VARCHAR(60) DEFAULT NULL AFTER deskripsi");
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bk_log_aktivitas (
    id_log INT(11) NOT NULL AUTO_INCREMENT,
    id_guru INT(11) DEFAULT NULL,
    aksi VARCHAR(50) NOT NULL,
    tabel_terkait VARCHAR(50) DEFAULT NULL,
    id_terkait INT(11) DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_log),
    KEY id_guru (id_guru),
    KEY tabel_terkait_id (tabel_terkait, id_terkait)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function bk_catatLog($koneksi, $id_guru, $aksi, $tabel, $id_terkait, $keterangan) {
    $aksiEsc = mysqli_real_escape_string($koneksi, $aksi);
    $tabelEsc = mysqli_real_escape_string($koneksi, $tabel);
    $ketEsc = mysqli_real_escape_string($koneksi, $keterangan);
    $idGuruSql = $id_guru ? (int) $id_guru : 'NULL';
    $idTerkaitSql = $id_terkait !== null ? (int) $id_terkait : 'NULL';
    mysqli_query($koneksi, "INSERT INTO bk_log_aktivitas (id_guru, aksi, tabel_terkait, id_terkait, keterangan)
        VALUES ($idGuruSql, '$aksiEsc', '$tabelEsc', $idTerkaitSql, '$ketEsc')");
}

$FUNGSI_LAYANAN_OPSI = ['Pemahaman', 'Pencegahan (Preventif)', 'Pengentasan (Kuratif)', 'Pemeliharaan dan Pengembangan'];

$GOOGLE_DRIVE_URL = 'https://drive.google.com/drive/folders/GANTI_DENGAN_ID_FOLDER_DRIVE';
$qDrive = mysqli_query($koneksi, "SELECT nilai FROM bk_pengaturan WHERE nama_pengaturan = 'google_drive_bk' LIMIT 1");
if ($qDrive && ($rDrive = mysqli_fetch_assoc($qDrive)) && !empty($rDrive['nilai'])) {
    $GOOGLE_DRIVE_URL = $rDrive['nilai'];
}



















function bk_createUploadDir(string $path): array {
    $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    $result = [
        'ok' => false,
        'path' => $path,
        'message' => '',
        'is_dir' => false,
        'is_writable' => false,
        'realpath' => null,
    ];

    if (is_dir($path)) {
        $result['is_dir'] = true;
        $result['realpath'] = realpath($path) ?: null;
        if (!is_writable($path)) {
            @chmod($path, 0775);
            if (!is_writable($path)) {
                @chmod($path, 0755);
            }
        }
        $result['is_writable'] = is_writable($path);
        if ($result['is_writable']) {
            $result['ok'] = true;
            $result['message'] = 'Folder sudah ada dan writable.';
        } else {
            $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
            $result['message'] = 'Folder ada tetapi TIDAK bisa ditulis: ' . $rel
                . ' (absolute: ' . ($result['realpath'] ?: $path) . '). '
                . 'Buat/set permission 775 via FTP/cPanel File Manager, dan pastikan owner-nya user web server (bukan root).';
        }
        return $result;
    }

    $created = @mkdir($path, 0775, true);
    if (!$created && !is_dir($path)) {
        $created = @mkdir($path, 0755, true);
    }

    if (!is_dir($path)) {
        $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
        $parent = dirname(rtrim($path, '/\\'));
        $parentWritable = is_dir($parent) ? (is_writable($parent) ? 'writable' : 'TIDAK writable') : 'tidak ada';
        $result['message'] = 'Gagal membuat folder: ' . $rel
            . ' (absolute: ' . $path . '). Parent (' . $parent . ') status: ' . $parentWritable . '. '
            . 'Buat manual via FTP/cPanel: uploads/bimbingan_klasikal/{ppt,gambar,arsip} dengan permission 775.';
        return $result;
    }

    $result['is_dir'] = true;
    $result['realpath'] = realpath($path) ?: null;
    @chmod($path, 0775);
    if (!is_writable($path)) {
        @chmod($path, 0755);
    }
    $result['is_writable'] = is_writable($path);

    if ($result['is_writable']) {
        $result['ok'] = true;
        $result['message'] = 'Folder berhasil dibuat dan writable.';
    } else {
        $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
        $result['message'] = 'Folder berhasil dibuat tetapi TIDAK bisa ditulis: ' . $rel
            . ' (absolute: ' . ($result['realpath'] ?: $path) . '). '
            . 'Set permission 775 via FTP/cPanel dan pastikan owner = user web server.';
    }
    return $result;
}


function bk_uploadIniInfo(): array {
    return [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'file_uploads' => ini_get('file_uploads'),
        'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        'max_execution_time' => ini_get('max_execution_time'),
    ];
}


function bk_parseSizeToBytes(string $val): int {
    $val = trim($val);
    if ($val === '') return 0;
    $last = strtolower($val[strlen($val) - 1]);
    $num = (float) $val;
    switch ($last) {
        case 'g': $num *= 1024;
        
        case 'm': $num *= 1024;
        
        case 'k': $num *= 1024;
    }
    return (int) $num;
}






function bk_saveUploadedFile(string $tmpName, string $destDir, string $namaBaru, string $label = 'file'): array {
    $destDir = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR;
    $tujuanPenuh = $destDir . $namaBaru;
    $relBase = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $destDir);

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [
            'ok' => false,
            'relative_path' => null,
            'absolute_path' => $tujuanPenuh,
            'message' => "$label: file temporary tidak valid / bukan hasil upload PHP (tmp: $tmpName). Cek upload_max_filesize / post_max_size.",
        ];
    }

    $dirInfo = bk_createUploadDir($destDir);
    if (!$dirInfo['ok']) {
        return [
            'ok' => false,
            'relative_path' => null,
            'absolute_path' => $tujuanPenuh,
            'message' => "$label: " . $dirInfo['message'],
        ];
    }

    $okPindah = @move_uploaded_file($tmpName, $tujuanPenuh);
    if (!$okPindah) {
        $okPindah = @copy($tmpName, $tujuanPenuh);
        if ($okPindah) {
            @unlink($tmpName);
        }
    }

    if ($okPindah && is_file($tujuanPenuh)) {
        @chmod($tujuanPenuh, 0644);
        $rel = $relBase . $namaBaru;
        return [
            'ok' => true,
            'relative_path' => str_replace('\\', '/', $rel),
            'absolute_path' => $tujuanPenuh,
            'message' => 'OK',
        ];
    }

    $lastErr = error_get_last();
    $errHint = $lastErr ? ($lastErr['message'] ?? '') : '';
    return [
        'ok' => false,
        'relative_path' => null,
        'absolute_path' => $tujuanPenuh,
        'message' => "$label: gagal menulis ke disk. Path dicoba: $tujuanPenuh. "
            . 'is_dir=' . (is_dir($destDir) ? '1' : '0')
            . ', is_writable=' . (is_writable($destDir) ? '1' : '0')
            . ($errHint !== '' ? ". PHP: $errHint" : '')
            . '. Buat folder via FTP dan set permission 775.',
    ];
}


function bk_uploadErrorMessage(int $code): string {
    $map = [
        UPLOAD_ERR_INI_SIZE => 'melebihi upload_max_filesize di php.ini hosting (nilai saat ini: ' . ini_get('upload_max_filesize') . ')',
        UPLOAD_ERR_FORM_SIZE => 'melebihi batas ukuran form (MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => 'hanya terunggah sebagian (koneksi putus)',
        UPLOAD_ERR_NO_FILE => 'tidak ada file yang diunggah',
        UPLOAD_ERR_NO_TMP_DIR => 'folder tmp PHP tidak ada di server',
        UPLOAD_ERR_CANT_WRITE => 'PHP gagal menulis ke disk server',
        UPLOAD_ERR_EXTENSION => 'diblokir ekstensi PHP',
    ];
    return $map[$code] ?? ('kode error ' . $code);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
    && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Content-Type: application/json');
    $ini = bk_uploadIniInfo();
    echo json_encode([
        'success' => false,
        'message' => 'Ukuran total request melebihi post_max_size hosting. '
            . 'Naikkan post_max_size dan upload_max_filesize minimal 32M. '
            . 'Nilai saat ini: post_max_size=' . $ini['post_max_size']
            . ', upload_max_filesize=' . $ini['upload_max_filesize']
            . ', memory_limit=' . $ini['memory_limit'] . '. '
            . 'Buat file .user.ini di folder script berisi: upload_max_filesize=32M dan post_max_size=40M.',
        'php_ini' => $ini,
        'content_length' => (int) $_SERVER['CONTENT_LENGTH'],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    
    if ($action === 'debug_upload') {
        $dirs = [
            'uploads' => __DIR__ . '/uploads/',
            'bimbingan_klasikal' => __DIR__ . '/uploads/bimbingan_klasikal/',
            'ppt' => __DIR__ . '/uploads/bimbingan_klasikal/ppt/',
            'gambar' => __DIR__ . '/uploads/bimbingan_klasikal/gambar/',
            'arsip' => __DIR__ . '/uploads/bimbingan_klasikal/arsip/',
        ];
        $dirStatus = [];
        foreach ($dirs as $label => $p) {
            $info = bk_createUploadDir($p);
            $dirStatus[$label] = [
                'path' => $p,
                'realpath' => $info['realpath'],
                'is_dir' => $info['is_dir'],
                'is_writable' => $info['is_writable'],
                'ok' => $info['ok'],
                'message' => $info['message'],
            ];
        }
        echo json_encode([
            'success' => true,
            'script_dir' => __DIR__,
            'realpath_script' => realpath(__DIR__),
            'php_ini' => bk_uploadIniInfo(),
            'directories' => $dirStatus,
            'php_version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
        ]);
        exit;
    }

    if ($action === 'list_arsip') {
        $filterGuru = trim($_POST['guru'] ?? '');
        $filterTipe = trim($_POST['tipe'] ?? '');
        $kondisi = [];
        if ($filterGuru !== '') {
            $filterGuruEsc = mysqli_real_escape_string($koneksi, $filterGuru);
            $kondisi[] = "nama_guru_upload = '$filterGuruEsc'";
        }
        if ($filterTipe !== '') {
            $filterTipeEsc = mysqli_real_escape_string($koneksi, $filterTipe);
            $kondisi[] = "tipe_bahan = '$filterTipeEsc'";
        }
        $where = count($kondisi) > 0 ? 'WHERE ' . implode(' AND ', $kondisi) : '';
        $data = [];
        $q = mysqli_query($koneksi, "SELECT * FROM bk_arsip_materi $where ORDER BY id_arsip DESC");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_arsip') {
        $judul = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $namaGuru = trim($_POST['nama_guru_upload'] ?? '');
        $tipeBahan = trim($_POST['tipe_bahan'] ?? 'gambar');
        if (!in_array($tipeBahan, ['teks','gambar','ppt','lkpd','youtube'])) $tipeBahan = 'gambar';
        $kontenTeks = trim($_POST['konten_teks'] ?? '');
        $lkpdJson = null;
        if ($tipeBahan === 'lkpd') {
            $daftarPertanyaanArsip = json_decode($_POST['lkpd_json'] ?? '[]', true);
            if (is_array($daftarPertanyaanArsip)) {
                $bersih = [];
                foreach ($daftarPertanyaanArsip as $p) {
                    $teksP = trim($p['teks'] ?? '');
                    if ($teksP === '') continue;
                    $tipeP = in_array($p['tipe'] ?? '', ['pilihan_ganda','checkbox','isian_singkat','esai']) ? $p['tipe'] : 'esai';
                    $opsiP = [];
                    if (in_array($tipeP, ['pilihan_ganda','checkbox']) && !empty($p['opsi']) && is_array($p['opsi'])) {
                        $opsiP = array_values(array_filter(array_map('trim', $p['opsi']), fn($o) => $o !== ''));
                    }
                    $bersih[] = ['teks' => $teksP, 'tipe' => $tipeP, 'opsi' => $opsiP];
                }
                $lkpdJson = json_encode($bersih);
            }
        }

        if ($judul === '') {
            echo json_encode(['success' => false, 'message' => 'Judul wajib diisi.']);
            exit;
        }
        if ($tipeBahan === 'teks' && $kontenTeks === '') {
            echo json_encode(['success' => false, 'message' => 'Isi teks wajib diisi.']);
            exit;
        }
        if ($tipeBahan === 'lkpd' && ($lkpdJson === null || $lkpdJson === '[]')) {
            echo json_encode(['success' => false, 'message' => 'Tambahkan minimal satu pertanyaan LKPD.']);
            exit;
        }
        if (in_array($tipeBahan, ['gambar','ppt','youtube']) && $link === '' && (!isset($_FILES['file_lampiran']) || $_FILES['file_lampiran']['error'] !== UPLOAD_ERR_OK)) {
            echo json_encode(['success' => false, 'message' => 'Isi link ATAU unggah file lampiran.']);
            exit;
        }

        $filePath = null;
        $pesanUploadArsip = [];
        if (isset($_FILES['file_lampiran'])) {
            $errFile = (int) ($_FILES['file_lampiran']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errFile === UPLOAD_ERR_OK) {
                $allowedExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION));
                $ukuran = (int) ($_FILES['file_lampiran']['size'] ?? 0);
                $maxUploadBytes = bk_parseSizeToBytes(ini_get('upload_max_filesize') ?: '2M');
                if (!in_array($ext, $allowedExt)) {
                    $pesanUploadArsip[] = 'Ekstensi file tidak diizinkan (.' . $ext . ').';
                } elseif ($ukuran <= 0) {
                    $pesanUploadArsip[] = 'File kosong / gagal diterima server.';
                } elseif ($ukuran > 20 * 1024 * 1024) {
                    $pesanUploadArsip[] = 'File terlalu besar (' . round($ukuran / 1048576, 1) . ' MB). Maksimal aplikasi 20 MB. Limit PHP upload_max_filesize=' . ini_get('upload_max_filesize') . '.';
                } elseif ($maxUploadBytes > 0 && $ukuran > $maxUploadBytes) {
                    $pesanUploadArsip[] = 'File melebihi upload_max_filesize hosting (' . ini_get('upload_max_filesize') . '). Naikkan di .user.ini.';
                } else {
                    $uploadDirArsip = __DIR__ . '/uploads/bimbingan_klasikal/arsip/';
                    $namaBaru = 'arsip' . $id_guru_login . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $save = bk_saveUploadedFile($_FILES['file_lampiran']['tmp_name'], $uploadDirArsip, $namaBaru, 'Arsip');
                    if ($save['ok']) {
                        $filePath = $save['relative_path'];
                    } else {
                        $pesanUploadArsip[] = $save['message'];
                        error_log('[bk_upload] simpan_arsip gagal: ' . $save['message']);
                    }
                }
            } elseif ($errFile !== UPLOAD_ERR_NO_FILE) {
                $pesanUploadArsip[] = 'Upload gagal — ' . bk_uploadErrorMessage($errFile)
                    . '. php_ini: upload_max_filesize=' . ini_get('upload_max_filesize')
                    . ', post_max_size=' . ini_get('post_max_size');
            }
        }

        if (!empty($pesanUploadArsip) && in_array($tipeBahan, ['gambar','ppt']) && $link === '' && $filePath === null) {
            echo json_encode([
                'success' => false,
                'message' => implode("\n", $pesanUploadArsip),
                'php_ini' => bk_uploadIniInfo(),
            ]);
            exit;
        }

        $judulEsc = mysqli_real_escape_string($koneksi, $judul);
        $kategoriEsc = mysqli_real_escape_string($koneksi, $kategori);
        $linkEsc = mysqli_real_escape_string($koneksi, $link);
        $keteranganEsc = mysqli_real_escape_string($koneksi, $keterangan);
        $namaGuruEsc = mysqli_real_escape_string($koneksi, $namaGuru);
        $tipeBahanEsc = mysqli_real_escape_string($koneksi, $tipeBahan);
        $filePathSql = $filePath !== null ? "'" . mysqli_real_escape_string($koneksi, $filePath) . "'" : 'NULL';
        $kontenTeksSql = $tipeBahan === 'teks' ? "'" . mysqli_real_escape_string($koneksi, $kontenTeks) . "'" : 'NULL';
        $lkpdJsonSql = $lkpdJson !== null ? "'" . mysqli_real_escape_string($koneksi, $lkpdJson) . "'" : 'NULL';

        mysqli_query($koneksi, "INSERT INTO bk_arsip_materi (judul, kategori, tipe_bahan, link, file_lampiran, konten_teks, lkpd_json, keterangan, nama_guru_upload, id_guru)
            VALUES ('$judulEsc', '$kategoriEsc', '$tipeBahanEsc', '$linkEsc', $filePathSql, $kontenTeksSql, $lkpdJsonSql, '$keteranganEsc', '$namaGuruEsc', $id_guru_login)");

        $respArsip = ['success' => true, 'id_arsip' => mysqli_insert_id($koneksi)];
        if (!empty($pesanUploadArsip)) {
            $respArsip['upload_warning'] = implode("\n", $pesanUploadArsip);
            $respArsip['php_ini'] = bk_uploadIniInfo();
        }
        echo json_encode($respArsip);
        exit;
    }

    if ($action === 'simpan_pengaturan') {
        $namaPengaturan = trim($_POST['nama_pengaturan'] ?? '');
        $nilai = trim($_POST['nilai'] ?? '');
        if (!in_array($namaPengaturan, ['google_drive_bk'])) {
            echo json_encode(['success' => false, 'message' => 'Pengaturan tidak dikenali.']);
            exit;
        }
        if ($nilai === '') {
            echo json_encode(['success' => false, 'message' => 'Link tidak boleh kosong.']);
            exit;
        }
        $namaEsc = mysqli_real_escape_string($koneksi, $namaPengaturan);
        $nilaiEsc = mysqli_real_escape_string($koneksi, $nilai);
        mysqli_query($koneksi, "INSERT INTO bk_pengaturan (nama_pengaturan, nilai) VALUES ('$namaEsc', '$nilaiEsc')
            ON DUPLICATE KEY UPDATE nilai = '$nilaiEsc'");
        echo json_encode(['success' => true, 'nilai' => $nilai]);
        exit;
    }

    if ($action === 'edit_arsip') {
        $id = (int) ($_POST['id_arsip'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $namaGuru = trim($_POST['nama_guru_upload'] ?? '');
        $kontenTeks = trim($_POST['konten_teks'] ?? '');

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data pustaka bahan tidak ditemukan.']);
            exit;
        }
        if ($judul === '') {
            echo json_encode(['success' => false, 'message' => 'Judul wajib diisi.']);
            exit;
        }

        $qCek = mysqli_query($koneksi, "SELECT tipe_bahan, file_lampiran FROM bk_arsip_materi WHERE id_arsip = $id LIMIT 1");
        $rCek = $qCek ? mysqli_fetch_assoc($qCek) : null;
        if (!$rCek) {
            echo json_encode(['success' => false, 'message' => 'Data pustaka bahan tidak ditemukan.']);
            exit;
        }
        $tipeBahan = $rCek['tipe_bahan'];

        $lkpdJsonSql = null;
        if ($tipeBahan === 'lkpd') {
            $daftarPertanyaanArsip = json_decode($_POST['lkpd_json'] ?? '[]', true);
            if (is_array($daftarPertanyaanArsip) && count($daftarPertanyaanArsip) > 0) {
                $bersih = [];
                foreach ($daftarPertanyaanArsip as $p) {
                    $teksP = trim($p['teks'] ?? '');
                    if ($teksP === '') continue;
                    $tipeP = in_array($p['tipe'] ?? '', ['pilihan_ganda','checkbox','isian_singkat','esai']) ? $p['tipe'] : 'esai';
                    $opsiP = [];
                    if (in_array($tipeP, ['pilihan_ganda','checkbox']) && !empty($p['opsi']) && is_array($p['opsi'])) {
                        $opsiP = array_values(array_filter(array_map('trim', $p['opsi']), fn($o) => $o !== ''));
                    }
                    $bersih[] = ['teks' => $teksP, 'tipe' => $tipeP, 'opsi' => $opsiP];
                }
                $lkpdJsonSql = "'" . mysqli_real_escape_string($koneksi, json_encode($bersih)) . "'";
            }
        }

        $filePath = $rCek['file_lampiran'];
        if (isset($_FILES['file_lampiran'])) {
            $errFile = (int) ($_FILES['file_lampiran']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errFile === UPLOAD_ERR_OK) {
                $allowedExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','jpg','jpeg','png','webp'];
                $ext = strtolower(pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION));
                $ukuran = (int) ($_FILES['file_lampiran']['size'] ?? 0);
                if (in_array($ext, $allowedExt) && $ukuran > 0 && $ukuran <= 20 * 1024 * 1024) {
                    $uploadDirArsip = __DIR__ . '/uploads/bimbingan_klasikal/arsip/';
                    $namaBaru = 'arsip' . $id_guru_login . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $save = bk_saveUploadedFile($_FILES['file_lampiran']['tmp_name'], $uploadDirArsip, $namaBaru, 'Arsip');
                    if ($save['ok']) {
                        if ($filePath && strpos($filePath, 'http') !== 0 && is_file(__DIR__ . '/' . $filePath)) {
                            @unlink(__DIR__ . '/' . $filePath);
                        }
                        $filePath = $save['relative_path'];
                    } else {
                        error_log('[bk_upload] edit_arsip gagal: ' . $save['message']);
                    }
                }
            } elseif ($errFile !== UPLOAD_ERR_NO_FILE) {
                error_log('[bk_upload] edit_arsip error: ' . bk_uploadErrorMessage($errFile));
            }
        }

        $judulEsc = mysqli_real_escape_string($koneksi, $judul);
        $kategoriEsc = mysqli_real_escape_string($koneksi, $kategori);
        $linkEsc = mysqli_real_escape_string($koneksi, $link);
        $keteranganEsc = mysqli_real_escape_string($koneksi, $keterangan);
        $namaGuruEsc = mysqli_real_escape_string($koneksi, $namaGuru);
        $filePathSql = $filePath !== null ? "'" . mysqli_real_escape_string($koneksi, $filePath) . "'" : 'NULL';
        $kontenTeksSql = $tipeBahan === 'teks' ? "'" . mysqli_real_escape_string($koneksi, $kontenTeks) . "'" : 'NULL';

        $setLkpd = $lkpdJsonSql !== null ? ", lkpd_json = $lkpdJsonSql" : '';

        mysqli_query($koneksi, "UPDATE bk_arsip_materi SET
            judul = '$judulEsc', kategori = '$kategoriEsc', link = '$linkEsc', file_lampiran = $filePathSql,
            konten_teks = $kontenTeksSql, keterangan = '$keteranganEsc', nama_guru_upload = '$namaGuruEsc' $setLkpd
            WHERE id_arsip = $id");

        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'hapus_arsip') {
        $id = (int) ($_POST['id_arsip'] ?? 0);
        $konfirmasiHapusArsip = ($_POST['konfirmasi_hapus_dipakai'] ?? '') === '1';
        $q = mysqli_query($koneksi, "SELECT file_lampiran FROM bk_arsip_materi WHERE id_arsip = $id LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;

        $jumlahMateriPakai = 0;
        $filePathFull = null;
        if ($row && !empty($row['file_lampiran'])) {
            $filePathFull = __DIR__ . '/' . $row['file_lampiran'];
            $pathEsc = mysqli_real_escape_string($koneksi, $row['file_lampiran']);
            $qPakai = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_materi) AS jml FROM bk_slide
                WHERE gambar = '$pathEsc' OR file_ppt = '$pathEsc'");
            if ($qPakai) $jumlahMateriPakai = (int) mysqli_fetch_assoc($qPakai)['jml'];
        }

        if ($jumlahMateriPakai > 0 && !$konfirmasiHapusArsip) {
            echo json_encode([
                'success' => false,
                'need_confirmation' => true,
                'message' => "Bahan ini masih dipakai di $jumlahMateriPakai materi aktif. Kalau tetap dihapus, hanya salinan di Pustaka yang hilang — materi yang sudah memakainya tidak akan rusak, tapi Anda tidak bisa memilih bahan ini lagi untuk materi baru. Tetap hapus dari Pustaka?",
            ]);
            exit;
        }

        if ($row && !empty($row['file_lampiran']) && $jumlahMateriPakai === 0 && is_file($filePathFull)) {
            @unlink($filePathFull);
        }
        mysqli_query($koneksi, "DELETE FROM bk_arsip_materi WHERE id_arsip = $id");
        if (function_exists('bk_catatLog')) {
            bk_catatLog($koneksi, $id_guru_login, 'hapus_arsip', 'bk_arsip_materi', $id, 'Hapus bahan dari Pustaka' . ($jumlahMateriPakai > 0 ? " (masih dipakai $jumlahMateriPakai materi, dikonfirmasi guru)" : ''));
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_kelas_jurusan') {
        $daftar = [];
        $q = mysqli_query($koneksi, "SELECT DISTINCT kelas, jurusan FROM siswa WHERE kelas IS NOT NULL AND kelas != '' AND kelas NOT LIKE '%LULUS%' AND jurusan NOT LIKE '%LULUS%' ORDER BY kelas ASC, jurusan ASC");
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $daftar[] = ['kelas' => $r['kelas'], 'jurusan' => $r['jurusan']];
            }
        }
        echo json_encode(['success' => true, 'data' => $daftar]);
        exit;
    }

    if ($action === 'list_materi') {
        $data = [];
        $q = mysqli_query($koneksi, "SELECT * FROM bk_materi ORDER BY urutan ASC, id_materi ASC");
        if ($q) {
            while ($m = mysqli_fetch_assoc($q)) {
                $idm = (int) $m['id_materi'];

                $sasaran = [];
                $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
                if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran[] = $s['kelas'] . ' ' . $s['jurusan'];

                $jmlSlide = 0;
                $qc = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1");
                if ($qc) $jmlSlide = (int) mysqli_fetch_assoc($qc)['jml'];

                $jmlSasaranSiswa = 0;
                if (count($sasaran) > 0) {
                    $kondisi = [];
                    $qs2 = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
                    if ($qs2) {
                        while ($s2 = mysqli_fetch_assoc($qs2)) {
                            $kls = mysqli_real_escape_string($koneksi, $s2['kelas']);
                            $jur = mysqli_real_escape_string($koneksi, $s2['jurusan']);
                            $kondisi[] = "(kelas = '$kls' AND jurusan = '$jur')";
                        }
                    }
                    if (count($kondisi) > 0) {
                        $qcs = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM siswa WHERE " . implode(' OR ', $kondisi));
                        if ($qcs) $jmlSasaranSiswa = (int) mysqli_fetch_assoc($qcs)['jml'];
                    }
                }

                $jmlLanjutan = 0;
                $qcl = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM bk_monitoring_lanjutan WHERE id_materi = $idm");
                if ($qcl) $jmlLanjutan = (int) mysqli_fetch_assoc($qcl)['jml'];

                $data[] = [
                    'id_materi' => $idm,
                    'judul' => $m['judul'],
                    'deskripsi' => $m['deskripsi'],
                    'fungsi_layanan' => $m['fungsi_layanan'] ?? '',
                    'urutan' => (int) $m['urutan'],
                    'nama_guru_pembuat' => $m['nama_guru_pembuat'],
                    'status_aktif' => (int) $m['status_aktif'],
                    'sasaran' => $sasaran,
                    'jumlah_slide' => $jmlSlide,
                    'jumlah_sasaran_siswa' => $jmlSasaranSiswa,
                    'jumlah_lanjutan' => $jmlLanjutan,
                    'created_at' => $m['created_at'],
                ];
            }
        }
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'lihat_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $qm = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $idm LIMIT 1");
        $materi = $qm ? mysqli_fetch_assoc($qm) : null;
        if (!$materi) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            exit;
        }
        $slides = [];
        $qsl = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1 ORDER BY urutan ASC, id_slide ASC");
        if ($qsl) {
            while ($sl = mysqli_fetch_assoc($qsl)) {
                $ids = (int) $sl['id_slide'];
                $pertanyaan = [];
                $qp = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = $ids ORDER BY urutan ASC, id_pertanyaan ASC");
                if ($qp) {
                    while ($p = mysqli_fetch_assoc($qp)) {
                        $p['opsi_jawaban'] = $p['opsi_jawaban'] ? json_decode($p['opsi_jawaban'], true) : [];
                        $pertanyaan[] = $p;
                    }
                }
                $sl['pertanyaan'] = $pertanyaan;
                $slides[] = $sl;
            }
        }
        $sasaran = [];
        $qs = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
        if ($qs) while ($s = mysqli_fetch_assoc($qs)) $sasaran[] = $s;
        $materi['sasaran'] = $sasaran;
        $materi['slides'] = $slides;
        echo json_encode(['success' => true, 'data' => $materi]);
        exit;
    }

    if ($action === 'nonaktifkan_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        mysqli_query($koneksi, "UPDATE bk_materi SET status_aktif = 0 WHERE id_materi = $idm");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'aktifkan_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        mysqli_query($koneksi, "UPDATE bk_materi SET status_aktif = 1 WHERE id_materi = $idm");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'hapus_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        if ($idm <= 0) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            exit;
        }

        $konfirmasiHapusProgres = ($_POST['konfirmasi_reset_progres'] ?? '') === '1';
        $jumlahSiswaProgres = 0;
        $qCekProgres = mysqli_query($koneksi, "SELECT COUNT(DISTINCT ps.id_siswa) AS jml
            FROM bk_progress_slide ps
            INNER JOIN bk_slide s ON s.id_slide = ps.id_slide
            WHERE s.id_materi = $idm");
        if ($qCekProgres) $jumlahSiswaProgres = (int) mysqli_fetch_assoc($qCekProgres)['jml'];

        if ($jumlahSiswaProgres > 0 && !$konfirmasiHapusProgres) {
            echo json_encode([
                'success' => false,
                'need_confirmation' => true,
                'message' => "$jumlahSiswaProgres siswa sudah mengerjakan materi ini. Menghapus materi akan membuat progres & jawaban LKPD mereka tidak lagi terlihat di sistem (disarankan nonaktifkan saja materi ini alih-alih hapus permanen). Tetap hapus permanen?",
            ]);
            exit;
        }

        $qFile = mysqli_query($koneksi, "SELECT gambar, file_ppt FROM bk_slide WHERE id_materi = $idm");
        if ($qFile) {
            while ($rf = mysqli_fetch_assoc($qFile)) {
                foreach (['gambar', 'file_ppt'] as $kolomFile) {
                    $p = $rf[$kolomFile];
                    if ($p && strpos($p, 'http') !== 0 && is_file(__DIR__ . '/' . $p)) @unlink(__DIR__ . '/' . $p);
                }
            }
        }

        if (function_exists('bk_catatLog')) {
            $keteranganLog = $jumlahSiswaProgres > 0
                ? "Hapus materi (id=$idm) dengan $jumlahSiswaProgres siswa sudah progres, dikonfirmasi guru"
                : "Hapus materi (id=$idm)";
            bk_catatLog($koneksi, $id_guru_login, 'hapus_materi', 'bk_materi', $idm, $keteranganLog);
        }

        mysqli_query($koneksi, "DELETE FROM bk_slide WHERE id_materi = $idm");
        mysqli_query($koneksi, "DELETE FROM bk_materi_sasaran WHERE id_materi = $idm");
        mysqli_query($koneksi, "DELETE FROM bk_materi WHERE id_materi = $idm");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'geser_urutan_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $arah = $_POST['arah'] ?? '';
        $qCur = mysqli_query($koneksi, "SELECT id_materi, urutan FROM bk_materi WHERE id_materi = $idm LIMIT 1");
        $cur = $qCur ? mysqli_fetch_assoc($qCur) : null;
        if (!$cur) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            exit;
        }
        $urutanCur = (int) $cur['urutan'];
        if ($arah === 'naik') {
            $qTet = mysqli_query($koneksi, "SELECT id_materi, urutan FROM bk_materi WHERE urutan < $urutanCur ORDER BY urutan DESC, id_materi DESC LIMIT 1");
        } else {
            $qTet = mysqli_query($koneksi, "SELECT id_materi, urutan FROM bk_materi WHERE urutan > $urutanCur ORDER BY urutan ASC, id_materi ASC LIMIT 1");
        }
        $tetangga = $qTet ? mysqli_fetch_assoc($qTet) : null;
        if (!$tetangga) {
            echo json_encode(['success' => false, 'message' => 'Materi sudah berada di posisi paling ' . ($arah === 'naik' ? 'atas' : 'bawah') . '.']);
            exit;
        }
        $idTetangga = (int) $tetangga['id_materi'];
        $urutanTetangga = (int) $tetangga['urutan'];
        mysqli_query($koneksi, "UPDATE bk_materi SET urutan = $urutanTetangga WHERE id_materi = $idm");
        mysqli_query($koneksi, "UPDATE bk_materi SET urutan = $urutanCur WHERE id_materi = $idTetangga");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'duplikat_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $qm = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $idm LIMIT 1");
        $materiAsli = $qm ? mysqli_fetch_assoc($qm) : null;
        if (!$materiAsli) {
            echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']);
            exit;
        }
        $qMax = mysqli_query($koneksi, "SELECT MAX(urutan) AS mx FROM bk_materi");
        $urutanBaru = $qMax ? ((int) mysqli_fetch_assoc($qMax)['mx']) + 1 : 1;
        $judulBaruEsc = mysqli_real_escape_string($koneksi, $materiAsli['judul'] . ' (Salinan)');
        $deskripsiEsc = mysqli_real_escape_string($koneksi, $materiAsli['deskripsi']);
        $fungsiEsc = mysqli_real_escape_string($koneksi, $materiAsli['fungsi_layanan']);
        $guruEsc = mysqli_real_escape_string($koneksi, $materiAsli['nama_guru_pembuat']);
        mysqli_query($koneksi, "INSERT INTO bk_materi (judul, deskripsi, fungsi_layanan, urutan, nama_guru_pembuat, id_guru, status_aktif)
            VALUES ('$judulBaruEsc', '$deskripsiEsc', '$fungsiEsc', $urutanBaru, '$guruEsc', $id_guru_login, 0)");
        $idMateriBaru = mysqli_insert_id($koneksi);

        $qSasaran = mysqli_query($koneksi, "SELECT kelas, jurusan FROM bk_materi_sasaran WHERE id_materi = $idm");
        if ($qSasaran) {
            while ($s = mysqli_fetch_assoc($qSasaran)) {
                $klsEsc = mysqli_real_escape_string($koneksi, $s['kelas']);
                $jurEsc = mysqli_real_escape_string($koneksi, $s['jurusan']);
                mysqli_query($koneksi, "INSERT INTO bk_materi_sasaran (id_materi, kelas, jurusan) VALUES ($idMateriBaru, '$klsEsc', '$jurEsc')");
            }
        }

        $qSlide = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm ORDER BY urutan ASC, id_slide ASC");
        if ($qSlide) {
            while ($sl = mysqli_fetch_assoc($qSlide)) {
                $judulSlideEsc = mysqli_real_escape_string($koneksi, $sl['judul_slide']);
                $kontenEsc = mysqli_real_escape_string($koneksi, $sl['konten_teks']);
                $ytEsc = mysqli_real_escape_string($koneksi, $sl['link_youtube']);

                $gambarBaru = $sl['gambar'];
                if ($gambarBaru && strpos($gambarBaru, 'http') !== 0 && is_file(__DIR__ . '/' . $gambarBaru)) {
                    $ext = pathinfo($gambarBaru, PATHINFO_EXTENSION);
                    $gambarBaru = pathinfo($gambarBaru, PATHINFO_DIRNAME) . '/' . uniqid('salin_') . '.' . $ext;
                    @copy(__DIR__ . '/' . $sl['gambar'], __DIR__ . '/' . $gambarBaru);
                }
                $pptBaru = $sl['file_ppt'];
                if ($pptBaru && strpos($pptBaru, 'http') !== 0 && is_file(__DIR__ . '/' . $pptBaru)) {
                    $ext = pathinfo($pptBaru, PATHINFO_EXTENSION);
                    $pptBaru = pathinfo($pptBaru, PATHINFO_DIRNAME) . '/' . uniqid('salin_') . '.' . $ext;
                    @copy(__DIR__ . '/' . $sl['file_ppt'], __DIR__ . '/' . $pptBaru);
                }
                $gambarEsc = mysqli_real_escape_string($koneksi, $gambarBaru);
                $pptEsc = mysqli_real_escape_string($koneksi, $pptBaru);

                mysqli_query($koneksi, "INSERT INTO bk_slide (id_materi, urutan, judul_slide, konten_teks, gambar, file_ppt, link_youtube, butuh_lkpd, status_aktif)
                    VALUES ($idMateriBaru, {$sl['urutan']}, '$judulSlideEsc', '$kontenEsc', '$gambarEsc', '$pptEsc', '$ytEsc', {$sl['butuh_lkpd']}, {$sl['status_aktif']})");
                $idSlideBaru = mysqli_insert_id($koneksi);

                $qPertanyaan = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = {$sl['id_slide']} ORDER BY urutan ASC, id_pertanyaan ASC");
                if ($qPertanyaan) {
                    while ($p = mysqli_fetch_assoc($qPertanyaan)) {
                        $teksPEsc = mysqli_real_escape_string($koneksi, $p['teks_pertanyaan']);
                        $tipePEsc = mysqli_real_escape_string($koneksi, $p['tipe_jawaban']);
                        $opsiEsc = $p['opsi_jawaban'] !== null ? "'" . mysqli_real_escape_string($koneksi, $p['opsi_jawaban']) . "'" : 'NULL';
                        mysqli_query($koneksi, "INSERT INTO bk_lkpd_pertanyaan (id_slide, urutan, teks_pertanyaan, tipe_jawaban, opsi_jawaban)
                            VALUES ($idSlideBaru, {$p['urutan']}, '$teksPEsc', '$tipePEsc', $opsiEsc)");
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'id_materi_baru' => $idMateriBaru]);
        exit;
    }

    if ($action === 'list_lanjutan') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $data = [];
        $q = mysqli_query($koneksi, "SELECT * FROM bk_monitoring_lanjutan WHERE id_materi = $idm ORDER BY tanggal_rencana ASC, id_lanjutan ASC");
        if ($q) while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    if ($action === 'simpan_lanjutan') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $idLanjutan = (int) ($_POST['id_lanjutan'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');
        $tanggal = trim($_POST['tanggal_rencana'] ?? '');
        $status = trim($_POST['status'] ?? 'rencana');
        $namaGuru = trim($_POST['nama_guru'] ?? '');
        if (!in_array($status, ['rencana', 'proses', 'selesai'])) $status = 'rencana';

        if ($idm <= 0 || $judul === '') {
            echo json_encode(['success' => false, 'message' => 'Judul tindak lanjut wajib diisi.']);
            exit;
        }

        $judulEsc = mysqli_real_escape_string($koneksi, $judul);
        $catatanEsc = mysqli_real_escape_string($koneksi, $catatan);
        $tanggalSql = $tanggal !== '' ? "'" . mysqli_real_escape_string($koneksi, $tanggal) . "'" : 'NULL';
        $statusEsc = mysqli_real_escape_string($koneksi, $status);
        $namaGuruEsc = mysqli_real_escape_string($koneksi, $namaGuru);

        if ($idLanjutan > 0) {
            mysqli_query($koneksi, "UPDATE bk_monitoring_lanjutan SET
                judul = '$judulEsc', catatan = '$catatanEsc', tanggal_rencana = $tanggalSql,
                status = '$statusEsc', nama_guru = '$namaGuruEsc'
                WHERE id_lanjutan = $idLanjutan AND id_materi = $idm");
        } else {
            mysqli_query($koneksi, "INSERT INTO bk_monitoring_lanjutan (id_materi, judul, catatan, tanggal_rencana, status, nama_guru, id_guru)
                VALUES ($idm, '$judulEsc', '$catatanEsc', $tanggalSql, '$statusEsc', '$namaGuruEsc', $id_guru_login)");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'hapus_lanjutan') {
        $id = (int) ($_POST['id_lanjutan'] ?? 0);
        mysqli_query($koneksi, "DELETE FROM bk_monitoring_lanjutan WHERE id_lanjutan = $id");
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'simpan_materi' || $action === 'update_materi') {
        $modeUpdate = $action === 'update_materi';
        $idMateriEdit = $modeUpdate ? (int) ($_POST['id_materi'] ?? 0) : 0;
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $fungsiLayanan = trim($_POST['fungsi_layanan'] ?? '');
        if (!in_array($fungsiLayanan, $FUNGSI_LAYANAN_OPSI)) $fungsiLayanan = '';
        $guruPembuat = trim($_POST['nama_guru_pembuat'] ?? '');
        $urutan = (int) ($_POST['urutan'] ?? 1);
        $sasaranRaw = json_decode($_POST['sasaran'] ?? '[]', true);
        $slideJudul = $_POST['slide_judul'] ?? [];
        $slideTeks = $_POST['slide_teks'] ?? [];
        $slideYoutube = $_POST['slide_youtube'] ?? [];
        $slideButuhLkpd = $_POST['slide_butuh_lkpd'] ?? [];
        $slidePertanyaanJson = $_POST['slide_pertanyaan_json'] ?? [];

        if ($judul === '') {
            echo json_encode(['success' => false, 'message' => 'Judul materi wajib diisi.']);
            exit;
        }
        if (!is_array($sasaranRaw) || count($sasaranRaw) === 0) {
            echo json_encode(['success' => false, 'message' => 'Pilih minimal satu sasaran kelas/jurusan.']);
            exit;
        }
        if (!is_array($slideJudul) || count($slideJudul) === 0) {
            echo json_encode(['success' => false, 'message' => 'Materi harus memiliki minimal satu bagian.']);
            exit;
        }
        if ($modeUpdate && $idMateriEdit <= 0) {
            echo json_encode(['success' => false, 'message' => 'Materi yang diedit tidak ditemukan.']);
            exit;
        }

        $judulEsc = mysqli_real_escape_string($koneksi, $judul);
        $deskripsiEsc = mysqli_real_escape_string($koneksi, $deskripsi);
        $fungsiLayananEsc = mysqli_real_escape_string($koneksi, $fungsiLayanan);
        $guruEsc = mysqli_real_escape_string($koneksi, $guruPembuat);

        if ($modeUpdate) {
            $konfirmasiResetProgres = ($_POST['konfirmasi_reset_progres'] ?? '') === '1';
            $jumlahSiswaProgres = 0;
            $qCekProgres = mysqli_query($koneksi, "SELECT COUNT(DISTINCT ps.id_siswa) AS jml
                FROM bk_progress_slide ps
                INNER JOIN bk_slide s ON s.id_slide = ps.id_slide
                WHERE s.id_materi = $idMateriEdit");
            if ($qCekProgres) $jumlahSiswaProgres = (int) mysqli_fetch_assoc($qCekProgres)['jml'];

            if ($jumlahSiswaProgres > 0 && !$konfirmasiResetProgres) {
                echo json_encode([
                    'success' => false,
                    'need_confirmation' => true,
                    'message' => "$jumlahSiswaProgres siswa sudah mengerjakan materi ini. Menyimpan perubahan akan memutus tautan progres & jawaban LKPD mereka ke slide lama (data lama tidak dihapus dari database, tapi tidak akan tampil lagi karena slide diganti dengan versi baru). Lanjutkan simpan?",
                ]);
                exit;
            }

            mysqli_query($koneksi, "UPDATE bk_materi SET judul = '$judulEsc', deskripsi = '$deskripsiEsc',
                fungsi_layanan = '$fungsiLayananEsc', urutan = $urutan, nama_guru_pembuat = '$guruEsc'
                WHERE id_materi = $idMateriEdit");
            $idMateriBaru = $idMateriEdit;

            if (function_exists('bk_catatLog')) {
                $keteranganLog = $jumlahSiswaProgres > 0
                    ? "Edit materi (id=$idMateriBaru) dengan $jumlahSiswaProgres siswa sudah progres, dikonfirmasi guru untuk tetap disimpan"
                    : "Edit materi (id=$idMateriBaru)";
                bk_catatLog($koneksi, $id_guru_login, 'update_materi', 'bk_materi', $idMateriBaru, $keteranganLog);
            }

            mysqli_query($koneksi, "DELETE FROM bk_slide WHERE id_materi = $idMateriBaru");
            mysqli_query($koneksi, "DELETE FROM bk_materi_sasaran WHERE id_materi = $idMateriBaru");
        } else {
            mysqli_query($koneksi, "INSERT INTO bk_materi (judul, deskripsi, fungsi_layanan, urutan, nama_guru_pembuat, id_guru, status_aktif) VALUES ('$judulEsc', '$deskripsiEsc', '$fungsiLayananEsc', $urutan, '$guruEsc', $id_guru_login, 1)");
            $idMateriBaru = mysqli_insert_id($koneksi);
        }

        foreach ($sasaranRaw as $sas) {
            if (!isset($sas['kelas']) || !isset($sas['jurusan'])) continue;
            $kls = mysqli_real_escape_string($koneksi, $sas['kelas']);
            $jur = mysqli_real_escape_string($koneksi, $sas['jurusan']);
            mysqli_query($koneksi, "INSERT INTO bk_materi_sasaran (id_materi, kelas, jurusan) VALUES ($idMateriBaru, '$kls', '$jur')");
        }

        // Jika kelas/jurusan dihilangkan dari sasaran: reset status selesai siswa terkait
        // agar di halaman siswa tidak tetap "Selesai" bila kelasnya sudah tidak termasuk.
        if (!empty($modeUpdate) && $idMateriBaru > 0) {
            $pairs = [];
            foreach ($sasaranRaw as $sas) {
                if (!isset($sas['kelas'], $sas['jurusan'])) continue;
                $pairs[] = "('" . mysqli_real_escape_string($koneksi, $sas['kelas']) . "','" . mysqli_real_escape_string($koneksi, $sas['jurusan']) . "')";
            }
            if (count($pairs) > 0) {
                $inList = implode(',', $pairs);
                // Hapus progress bagan siswa yang kelas+jurusannya TIDAK lagi di sasaran
                mysqli_query($koneksi, "DELETE pb FROM bk_progress_bagan pb
                    INNER JOIN siswa s ON s.id_siswa = pb.id_siswa
                    WHERE pb.id_materi = $idMateriBaru
                      AND (s.kelas, s.jurusan) NOT IN ($inList)");
            } else {
                // Tidak ada sasaran sama sekali → hapus semua progress materi ini
                mysqli_query($koneksi, "DELETE FROM bk_progress_bagan WHERE id_materi = $idMateriBaru");
            }
        }

        $uploadDirGambar = __DIR__ . '/uploads/bimbingan_klasikal/gambar/';
        $uploadDirPpt = __DIR__ . '/uploads/bimbingan_klasikal/ppt/';
        $pesanUploadError = [];
        $phpIniInfo = bk_uploadIniInfo();
        $maxUploadBytes = bk_parseSizeToBytes($phpIniInfo['upload_max_filesize'] ?: '2M');

        
        foreach ([$uploadDirGambar, $uploadDirPpt] as $dirCek) {
            $dirInfo = bk_createUploadDir($dirCek);
            if (!$dirInfo['ok']) {
                $pesanUploadError[] = $dirInfo['message']
                    . ' | is_dir=' . ($dirInfo['is_dir'] ? '1' : '0')
                    . ' is_writable=' . ($dirInfo['is_writable'] ? '1' : '0')
                    . ' realpath=' . ($dirInfo['realpath'] ?: '-');
            }
        }

        $slideGambarArsip = $_POST['slide_gambar_arsip'] ?? [];
        $slidePptArsip = $_POST['slide_ppt_arsip'] ?? [];
        $slideYoutubeArsip = $_POST['slide_youtube_arsip'] ?? [];
        $slideTeksArsip = $_POST['slide_teks_arsip'] ?? [];
        $slideLkpdArsip = $_POST['slide_lkpd_arsip'] ?? [];
        
        $slideGambarExisting = $_POST['slide_gambar_existing'] ?? [];
        $slidePptExisting = $_POST['slide_ppt_existing'] ?? [];

        $totalSlide = count($slideJudul);
        for ($i = 0; $i < $totalSlide; $i++) {
            $judulSlideRaw = trim($slideJudul[$i] ?? '');
            $judulSlide = mysqli_real_escape_string($koneksi, $judulSlideRaw);
            $teksRaw = trim($slideTeks[$i] ?? '');
            $tekS = mysqli_real_escape_string($koneksi, $teksRaw);
            $ytRaw = trim($slideYoutube[$i] ?? '');
            $ytS = mysqli_real_escape_string($koneksi, $ytRaw);
            $butuhLkpd = !empty($slideButuhLkpd[$i]) ? 1 : 0;
            $labelSlide = 'Slide ' . ($i + 1);

            $gambarArsipId = (int) ($slideGambarArsip[$i] ?? 0);
            $gambarBaruDiupload = false;
            $gambarPath = null;
            
            $errGambar = null;
            if (isset($_FILES['slide_gambar']['error'][$i])) {
                $errGambar = (int) $_FILES['slide_gambar']['error'][$i];
            }
            if ($errGambar === UPLOAD_ERR_OK) {
                $allowedExt = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES['slide_gambar']['name'][$i], PATHINFO_EXTENSION));
                $ukuranGambar = (int) ($_FILES['slide_gambar']['size'][$i] ?? 0);
                $tmpGambar = $_FILES['slide_gambar']['tmp_name'][$i] ?? '';
                if (!in_array($ext, $allowedExt)) {
                    $pesanUploadError[] = "$labelSlide: ekstensi gambar tidak diizinkan (.$ext). Gunakan jpg/png/webp/gif.";
                } elseif ($ukuranGambar <= 0) {
                    $pesanUploadError[] = "$labelSlide: file gambar kosong / gagal diterima server.";
                } elseif ($ukuranGambar > 3 * 1024 * 1024) {
                    $pesanUploadError[] = "$labelSlide: gambar terlalu besar (" . round($ukuranGambar / 1048576, 1) . " MB). Maksimal aplikasi 3 MB. Limit PHP upload_max_filesize=" . $phpIniInfo['upload_max_filesize'] . '.';
                } elseif ($maxUploadBytes > 0 && $ukuranGambar > $maxUploadBytes) {
                    $pesanUploadError[] = "$labelSlide: gambar melebihi upload_max_filesize hosting (" . $phpIniInfo['upload_max_filesize'] . '). Naikkan di .user.ini.';
                } else {
                    $namaBaru = 'bk' . $idMateriBaru . '_' . $i . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $save = bk_saveUploadedFile($tmpGambar, $uploadDirGambar, $namaBaru, $labelSlide . ' gambar');
                    if ($save['ok']) {
                        $gambarPathLama = trim($slideGambarExisting[$i] ?? '');
                        if ($gambarPathLama && strpos($gambarPathLama, 'http') !== 0 && is_file(__DIR__ . '/' . $gambarPathLama)) {
                            @unlink(__DIR__ . '/' . $gambarPathLama);
                        }
                        $gambarPath = $save['relative_path'];
                        $gambarBaruDiupload = true;
                    } else {
                        $pesanUploadError[] = $save['message'];
                        error_log('[bk_upload] gambar gagal: ' . $save['message']);
                    }
                }
            } elseif ($errGambar !== null && $errGambar !== UPLOAD_ERR_NO_FILE) {
                $pesanUploadError[] = "$labelSlide: upload gambar gagal — " . bk_uploadErrorMessage($errGambar)
                    . ' | upload_max_filesize=' . $phpIniInfo['upload_max_filesize']
                    . ' post_max_size=' . $phpIniInfo['post_max_size'];
                if (!empty($slideGambarExisting[$i])) {
                    $gambarPath = trim($slideGambarExisting[$i]);
                }
            } elseif ($gambarArsipId > 0) {
                $qga = mysqli_query($koneksi, "SELECT file_lampiran, link FROM bk_arsip_materi WHERE id_arsip = $gambarArsipId LIMIT 1");
                $rga = $qga ? mysqli_fetch_assoc($qga) : null;
                if ($rga) $gambarPath = $rga['file_lampiran'] ?: $rga['link'];
            } elseif (!empty($slideGambarExisting[$i])) {
                $gambarPath = trim($slideGambarExisting[$i]);
            }

            $pptArsipId = (int) ($slidePptArsip[$i] ?? 0);
            $pptBaruDiupload = false;
            $pptPath = null;
            $errPpt = null;
            if (isset($_FILES['slide_ppt']['error'][$i])) {
                $errPpt = (int) $_FILES['slide_ppt']['error'][$i];
            }
            if ($errPpt === UPLOAD_ERR_OK) {
                $allowedExtPpt = ['ppt','pptx'];
                $ext = strtolower(pathinfo($_FILES['slide_ppt']['name'][$i], PATHINFO_EXTENSION));
                $ukuranPpt = (int) ($_FILES['slide_ppt']['size'][$i] ?? 0);
                $tmpPpt = $_FILES['slide_ppt']['tmp_name'][$i] ?? '';
                if (!in_array($ext, $allowedExtPpt)) {
                    $pesanUploadError[] = "$labelSlide: ekstensi PPT tidak diizinkan (.$ext). Gunakan .ppt atau .pptx.";
                } elseif ($ukuranPpt <= 0) {
                    $pesanUploadError[] = "$labelSlide: file PPT kosong / gagal diterima server.";
                } elseif ($ukuranPpt > 20 * 1024 * 1024) {
                    $pesanUploadError[] = "$labelSlide: file PPT terlalu besar (" . round($ukuranPpt / 1048576, 1) . " MB). Maksimal aplikasi 20 MB. Limit PHP upload_max_filesize=" . $phpIniInfo['upload_max_filesize'] . ', post_max_size=' . $phpIniInfo['post_max_size'] . '.';
                } elseif ($maxUploadBytes > 0 && $ukuranPpt > $maxUploadBytes) {
                    $pesanUploadError[] = "$labelSlide: PPT melebihi upload_max_filesize hosting (" . $phpIniInfo['upload_max_filesize'] . '). Naikkan di .user.ini menjadi minimal 32M.';
                } elseif ($tmpPpt === '' || !is_uploaded_file($tmpPpt)) {
                    $pesanUploadError[] = "$labelSlide: file PPT tidak valid di server (tmp hilang / bukan is_uploaded_file). "
                        . 'Cek upload_max_filesize=' . $phpIniInfo['upload_max_filesize']
                        . ' post_max_size=' . $phpIniInfo['post_max_size']
                        . ' upload_tmp_dir=' . $phpIniInfo['upload_tmp_dir'] . '.';
                } else {
                    $namaBaru = 'bkppt' . $idMateriBaru . '_' . $i . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                    $save = bk_saveUploadedFile($tmpPpt, $uploadDirPpt, $namaBaru, $labelSlide . ' PPT');
                    if ($save['ok']) {
                        $pptPathLama = trim($slidePptExisting[$i] ?? '');
                        if ($pptPathLama && strpos($pptPathLama, 'http') !== 0 && is_file(__DIR__ . '/' . $pptPathLama)) {
                            @unlink(__DIR__ . '/' . $pptPathLama);
                        }
                        $pptPath = $save['relative_path'];
                        $pptBaruDiupload = true;
                    } else {
                        $pesanUploadError[] = $save['message']
                            . ' | absolute=' . ($save['absolute_path'] ?? '-')
                            . ' | php_ini upload_max_filesize=' . $phpIniInfo['upload_max_filesize']
                            . ' post_max_size=' . $phpIniInfo['post_max_size']
                            . ' memory_limit=' . $phpIniInfo['memory_limit'];
                        error_log('[bk_upload] PPT gagal: ' . $save['message']);
                    }
                }
            } elseif ($errPpt !== null && $errPpt !== UPLOAD_ERR_NO_FILE) {
                $pesanUploadError[] = "$labelSlide: upload PPT gagal";
                error_log('[bk_upload] PPT error code ' . $errPpt . ': ' . bk_uploadErrorMessage($errPpt));
                if (!empty($slidePptExisting[$i])) {
                    $pptPath = trim($slidePptExisting[$i]);
                }
            } elseif ($pptArsipId > 0) {
                $qpa = mysqli_query($koneksi, "SELECT file_lampiran, link FROM bk_arsip_materi WHERE id_arsip = $pptArsipId LIMIT 1");
                $rpa = $qpa ? mysqli_fetch_assoc($qpa) : null;
                if ($rpa) $pptPath = $rpa['file_lampiran'] ?: $rpa['link'];
            } elseif (!empty($slidePptExisting[$i])) {
                $pptPath = trim($slidePptExisting[$i]);
            }

            $gambarSql = $gambarPath !== null ? "'" . mysqli_real_escape_string($koneksi, $gambarPath) . "'" : 'NULL';
            $pptSql = $pptPath !== null ? "'" . mysqli_real_escape_string($koneksi, $pptPath) . "'" : 'NULL';

            mysqli_query($koneksi, "INSERT INTO bk_slide (id_materi, urutan, judul_slide, konten_teks, gambar, file_ppt, link_youtube, butuh_lkpd, status_aktif)
                VALUES ($idMateriBaru, $i, '$judulSlide', '$tekS', $gambarSql, $pptSql, '$ytS', $butuhLkpd, 1)");
            $idSlideBaru = mysqli_insert_id($koneksi);

            $daftarPertanyaan = [];
            if ($butuhLkpd && isset($slidePertanyaanJson[$i])) {
                $daftarPertanyaanRaw = json_decode($slidePertanyaanJson[$i], true);
                if (is_array($daftarPertanyaanRaw)) {
                    $urutanP = 0;
                    foreach ($daftarPertanyaanRaw as $p) {
                        $teksP = mysqli_real_escape_string($koneksi, trim($p['teks'] ?? ''));
                        if ($teksP === '') continue;
                        $tipeP = in_array($p['tipe'] ?? '', ['pilihan_ganda','checkbox','isian_singkat','esai']) ? $p['tipe'] : 'esai';
                        $opsiBersih = [];
                        $opsiP = null;
                        if (in_array($tipeP, ['pilihan_ganda','checkbox']) && !empty($p['opsi']) && is_array($p['opsi'])) {
                            $opsiBersih = array_values(array_filter(array_map('trim', $p['opsi']), fn($o) => $o !== ''));
                            $opsiP = mysqli_real_escape_string($koneksi, json_encode($opsiBersih));
                        }
                        $opsiSql = $opsiP !== null ? "'$opsiP'" : 'NULL';
                        mysqli_query($koneksi, "INSERT INTO bk_lkpd_pertanyaan (id_slide, urutan, teks_pertanyaan, tipe_jawaban, opsi_jawaban)
                            VALUES ($idSlideBaru, $urutanP, '$teksP', '$tipeP', $opsiSql)");
                        $urutanP++;
                        $daftarPertanyaan[] = ['teks' => trim($p['teks'] ?? ''), 'tipe' => $tipeP, 'opsi' => $opsiBersih];
                    }
                }
            }

            
            
        }

        $resp = ['success' => true, 'id_materi' => $idMateriBaru];
        if (!empty($pesanUploadError)) {
            $resp['upload_warning'] = implode("\n", $pesanUploadError);
            $resp['message'] = "Materi tersimpan, tetapi ada file yang gagal diunggah:\n" . implode("\n", $pesanUploadError);
            $resp['php_ini'] = $phpIniInfo;
            $resp['upload_dirs'] = [
                'gambar' => [
                    'path' => $uploadDirGambar,
                    'is_dir' => is_dir($uploadDirGambar),
                    'is_writable' => is_dir($uploadDirGambar) && is_writable($uploadDirGambar),
                    'realpath' => is_dir($uploadDirGambar) ? (realpath($uploadDirGambar) ?: null) : null,
                ],
                'ppt' => [
                    'path' => $uploadDirPpt,
                    'is_dir' => is_dir($uploadDirPpt),
                    'is_writable' => is_dir($uploadDirPpt) && is_writable($uploadDirPpt),
                    'realpath' => is_dir($uploadDirPpt) ? (realpath($uploadDirPpt) ?: null) : null,
                ],
            ];
        }
        echo json_encode($resp);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bimbingan Klasikal - Sistem BK</title>
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

    .slide-block { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; margin-bottom: 14px; background: #fafafa; overflow-wrap: anywhere; word-break: break-word; }
    #isiLihatMateri { overflow-wrap: anywhere; word-break: break-word; }
    #isiLihatMateri img { max-width: 100%; height: auto; }
    .slide-block-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; gap: 8px; flex-wrap: wrap; }
    .pertanyaan-block { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 8px; background: #fff; }
    .badge-status-aktif { background: #dcfce7; color: #166534; }
    .badge-status-nonaktif { background: #f3f4f6; color: #6b7280; }
    table { min-width: 640px; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
<?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8 flex flex-col">

      <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
          <i class="fas fa-chalkboard-teacher text-blue-600 mr-2"></i> Bimbingan Klasikal
        </h1>
        <p class="text-sm text-gray-600 mb-4">Kelola materi pembelajaran dan pantau kegiatan siswa.</p>
        <button onclick="bukaModalTambahMateri()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm">
          <i class="fas fa-plus mr-1"></i> Buat Materi
        </button>
      </div>

      <div id="ringkasanStat" class="grid grid-cols-2 gap-3 mb-5 max-w-md">
        <div class="bg-white rounded-xl shadow-sm border p-4">
          <p class="text-xs text-gray-500 mb-1">Total Materi</p>
          <p class="text-2xl font-bold text-gray-800" id="statTotalMateri">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
          <p class="text-xs text-gray-500 mb-1">Total Simpanan Bahan</p>
          <p class="text-2xl font-bold text-gray-800" id="statTotalArsip">-</p>
        </div>
      </div>

      <div class="mb-4 flex gap-2 border-b overflow-x-auto">
        <button type="button" onclick="pindahTab('materi')" id="tabBtnMateri"
          class="tab-btn px-4 py-2 text-sm font-semibold border-b-2 border-blue-600 text-blue-600 whitespace-nowrap">
          <i class="fas fa-layer-group mr-1"></i> Materi Saya
        </button>
        <button type="button" onclick="pindahTab('simpanan')" id="tabBtnSimpanan"
          class="tab-btn px-4 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 whitespace-nowrap">
          <i class="fas fa-archive mr-1"></i> Simpanan Bahan
        </button>
      </div>

      <div id="tabSimpanan" class="bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
          <h2 class="text-base font-bold text-gray-700">Simpanan Bahan</h2>
          <div class="flex flex-wrap gap-2">
            <a id="linkGoogleDrive" href="<?php echo htmlspecialchars($GOOGLE_DRIVE_URL, ENT_QUOTES); ?>" target="_blank" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
              <i class="fab fa-google-drive text-green-600 mr-1"></i> Buka Google Drive BK
            </a>
            <button type="button" onclick="bukaModalDrive()" title="Ubah tujuan link Google Drive BK" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-500 px-3 py-2 rounded-lg text-sm shadow-sm">
              <i class="fas fa-pen"></i>
            </button>
            <button onclick="bukaModalTambahArsip()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
              <i class="fas fa-plus mr-1"></i> Tambah Simpanan
            </button>
          </div>
        </div>
        <p class="text-xs text-gray-400 mb-4">Bahan yang dapat digunakan kembali dalam materi &mdash; simpan teks, gambar, PPT, video, atau LKPD di sini, lalu pilih saat membuat materi tanpa perlu unggah ulang.</p>


        <div class="flex flex-wrap items-end gap-2 mb-4 bg-gray-50 border border-gray-200 rounded-lg p-3">
          <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Filter Guru BK</label>
            <select id="filterGuruArsip" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="muatDaftarArsip()">
              <option value="">Semua Guru BK</option>
              <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt): ?>
              <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Filter Jenis Bahan</label>
            <select id="filterTipeArsip" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="muatDaftarArsip()">
              <option value="">Semua Jenis</option>
              <option value="teks">Teks</option>
              <option value="gambar">Gambar</option>
              <option value="ppt">PPT</option>
              <option value="lkpd">LKPD</option>
              <option value="youtube">Link YouTube</option>
            </select>
          </div>
          <div class="pb-0.5">
            <button type="button" onclick="resetFilterArsip()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-800 whitespace-nowrap" title="Reset semua filter Pustaka Bahan">
              <i class="fas fa-rotate-left mr-1"></i> Reset Filter
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="bg-gray-100 text-left text-gray-700">
                <th class="px-3 py-2 border-b">Judul</th>
                <th class="px-3 py-2 border-b">Jenis</th>
                <th class="px-3 py-2 border-b">Kategori</th>
                <th class="px-3 py-2 border-b">Isi / Link / File</th>
                <th class="px-3 py-2 border-b">Keterangan</th>
                <th class="px-3 py-2 border-b">Diunggah Oleh</th>
                <th class="px-3 py-2 border-b text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="isiTabelArsip">
              <tr><td colspan="7" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
        <div id="paginasiArsip" class="flex flex-wrap items-center justify-between gap-2 mt-3"></div>
      </div>

      <div id="tabMateri" class="bg-white rounded-xl shadow-md p-4 md:p-6 flex-grow">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
          <h2 class="text-base font-bold text-gray-700">Materi Saya</h2>
          <button onclick="bukaModalTambahMateri()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
            <i class="fas fa-plus mr-1"></i> Buat Materi
          </button>
        </div>
        <p class="text-xs text-gray-400 mb-4">Materi ditampilkan ke siswa sesuai urutan dari atas ke bawah. Urutan 1 tampil lebih dulu. Gunakan ikon <i class="fas fa-route"></i> untuk mencatat tindak lanjut/monitoring lanjutan dari materi ini.</p>

        <div class="flex flex-wrap items-center gap-2 mb-3">
          <label class="text-xs font-medium text-gray-600">Filter Kelas:</label>
          <select id="filterKelasMateri" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs bg-white" onchange="halamanMateri=1; renderTabelMateri(); simpanFilterArsipKeUrl()">
            <option value="">Semua Kelas</option>
          </select>
          <button type="button" onclick="resetFilterMateri()" class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs text-gray-600 hover:bg-gray-100 hover:text-gray-800 whitespace-nowrap" title="Reset filter kelas">
            <i class="fas fa-rotate-left mr-1"></i> Reset Filter
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="bg-gray-100 text-left text-gray-700">
                <th class="px-3 py-2 border-b text-center">Urutan</th>
                <th class="px-3 py-2 border-b">Judul Materi</th>
                <th class="px-3 py-2 border-b">Fungsi Layanan</th>
                <th class="px-3 py-2 border-b">Sasaran</th>
                <th class="px-3 py-2 border-b">Pembuat</th>
                <th class="px-3 py-2 border-b text-center">Slide</th>
                <th class="px-3 py-2 border-b text-center">Siswa Sasaran</th>
                <th class="px-3 py-2 border-b text-center">Status</th>
                <th class="px-3 py-2 border-b text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="isiTabelMateri">
              <tr><td colspan="9" class="text-center py-6 text-gray-400">Memuat data...</td></tr>
            </tbody>
          </table>
        </div>
        <div id="paginasiMateri" class="flex flex-wrap items-center justify-between gap-2 mt-3"></div>
      </div>
      </main>

  <div id="modalTambahArsip" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-archive text-blue-600 mr-1"></i> <span id="judulModalArsip">Tambah Simpanan Bahan</span></h2>
        <button type="button" onclick="tutupModalTambahArsip()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <form id="formTambahArsip" class="p-5" onsubmit="return simpanArsip(event)">
        <input type="hidden" id="aIdArsip" value="">
        <div class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">Judul</label>
          <input type="text" id="aJudul" required class="w-full px-3 py-2 border rounded-lg text-sm">
        </div>
        <div class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Bahan</label>
          <select id="aTipeBahan" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="toggleFieldArsip()">
            <option value="gambar">Gambar</option>
            <option value="ppt">PPT</option>
            <option value="teks">Teks</option>
            <option value="lkpd">LKPD</option>
            <option value="youtube">Link YouTube</option>
          </select>
          <p class="text-[11px] text-gray-400 mt-1" id="aCatatanJenis">Jenis bahan tidak bisa diubah saat mengedit simpanan yang sudah ada.</p>
        </div>
        <div class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">Kategori (opsional)</label>
          <input type="text" id="aKategori" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Contoh: Layanan Dasar, Kelas X">
        </div>
        <div id="aWrapTeks" class="mb-3 hidden">
          <label class="block text-xs font-medium text-gray-600 mb-1">Isi Teks</label>
          <textarea id="aKontenTeks" rows="4" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
        </div>
        <div id="aWrapLkpd" class="mb-3 hidden">
          <label class="block text-xs font-medium text-gray-600 mb-1">Pertanyaan LKPD</label>
          <div id="aDaftarPertanyaanLkpd" class="bg-white border rounded-lg p-3"></div>
          <button type="button" onclick="tambahPertanyaanLkpd(this, 'aDaftarPertanyaanLkpd')" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg text-xs mt-2">
            <i class="fas fa-plus mr-1"></i> Tambah Pertanyaan
          </button>
        </div>
        <div id="aWrapLink" class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1" id="aLabelLink">Link (Google Drive, YouTube, atau web lain)</label>
          <input type="text" id="aLink" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="https://drive.google.com/...">
        </div>
        <p id="aTeksAtau" class="text-xs text-gray-400 text-center mb-3">— atau —</p>
        <div id="aWrapFile" class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">Unggah File (opsional, kalau tidak pakai link)</label>
          <input type="file" id="aFile" class="w-full text-sm" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.webp">
        </div>
        <div class="mb-3">
          <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan (opsional)</label>
          <textarea id="aKeterangan" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
        </div>
        <div class="mb-4">
          <label class="block text-xs font-medium text-gray-600 mb-1">Diunggah Oleh (Guru BK)</label>
          <select id="aGuru" class="w-full px-3 py-2 border rounded-lg text-sm">
            <option value="">Pilih Nama Guru</option>
            <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt): ?>
            <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t">
          <button type="button" onclick="tutupModalTambahArsip()" class="px-4 py-2 rounded-lg text-sm border">Batal</button>
          <button type="submit" id="tombolSimpanArsip" class="px-4 py-2 rounded-lg text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold">
            <i class="fas fa-save mr-1"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="modalDrive" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h2 class="text-base font-bold text-gray-800"><i class="fab fa-google-drive text-green-600 mr-1"></i> Ubah Link Google Drive BK</h2>
        <button type="button" onclick="tutupModalDrive()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <form class="p-5" onsubmit="return simpanLinkDrive(event)">
        <label class="block text-xs font-medium text-gray-600 mb-1">Link folder Google Drive tujuan</label>
        <input type="url" id="dLinkDrive" required placeholder="https://drive.google.com/drive/folders/..." class="w-full px-3 py-2 border rounded-lg text-sm">
        <p class="text-[11px] text-gray-400 mt-2">Tombol "Buka Google Drive BK" di tab Simpanan akan menuju ke link ini. Berlaku untuk semua Guru BK.</p>
        <div class="flex justify-end gap-2 pt-4 mt-2 border-t">
          <button type="button" onclick="tutupModalDrive()" class="px-4 py-2 rounded-lg text-sm border">Batal</button>
          <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <div id="modalAksiMateri" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-ellipsis-vertical text-gray-500 mr-1"></i> Aksi Materi</h2>
        <button type="button" onclick="tutupMenuAksiMateri()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <p class="px-5 pt-4 text-xs text-gray-500">Materi: <span class="font-semibold text-gray-700" id="aksiJudulMateri">-</span></p>
      <div class="p-3" id="aksiDaftarMenu"></div>
    </div>
  </div>

  <div id="modalLanjutan" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-route text-purple-600 mr-1"></i> Tindak Lanjut / Monitoring Lanjutan</h2>
        <button type="button" onclick="tutupModalLanjutan()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <div class="p-5">
        <p class="text-xs text-gray-500 mb-3">Catat kelanjutan bimbingan untuk materi: <span class="font-semibold" id="lJudulMateri">-</span></p>
        <div id="isiDaftarLanjutan" class="mb-4"><p class="text-center text-gray-400 py-4 text-sm">Memuat data...</p></div>
        <form id="formLanjutan" class="border-t pt-4" onsubmit="return simpanLanjutan(event)">
          <input type="hidden" id="lIdMateri" value="">
          <input type="hidden" id="lIdLanjutan" value="">
          <p class="text-xs font-semibold text-gray-600 mb-2" id="lJudulForm">Tambah Rencana Tindak Lanjut</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-gray-600 mb-1">Judul Tindak Lanjut</label>
              <input type="text" id="lJudul" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Contoh: Konseling individu lanjutan, Home visit, dsb.">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Rencana</label>
              <input type="date" id="lTanggal" class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
              <select id="lStatus" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="rencana">Rencana</option>
                <option value="proses">Proses</option>
                <option value="selesai">Selesai</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-gray-600 mb-1">Guru BK Penanggung Jawab</label>
              <select id="lGuru" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="">Pilih Nama Guru</option>
                <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt): ?>
                <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-gray-600 mb-1">Catatan (opsional)</label>
              <textarea id="lCatatan" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" id="tombolBatalLanjutan" onclick="resetFormLanjutan()" class="px-4 py-2 rounded-lg text-sm border hidden">Batal Edit</button>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-purple-600 hover:bg-purple-700 text-white font-semibold"><i class="fas fa-save mr-1"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="modalTambahMateri" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-plus text-blue-600 mr-1" id="ikonModalMateri"></i> <span id="judulModalMateri">Tambah Materi Bimbingan Klasikal</span></h2>
        <button type="button" onclick="tutupModalTambahMateri()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <form id="formTambahMateri" class="p-5" onsubmit="return simpanMateri(event)">
        <input type="hidden" id="fIdMateri" value="">
        <input type="hidden" id="fUrutanMateri" value="1">
        <input type="hidden" id="fDeskripsiMateri" value="">

        <div class="flex items-center gap-2 mb-4 text-xs font-semibold">
          <span id="langkahIndikator1" class="flex items-center gap-1.5 text-blue-600"><span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]">1</span> Informasi Materi</span>
          <span class="text-gray-300">&mdash;&mdash;</span>
          <span id="langkahIndikator2" class="flex items-center gap-1.5 text-gray-400"><span class="w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-[10px]">2</span> Susun Materi</span>
        </div>

        <div id="stepInfoMateri" class="bg-blue-50/60 border border-blue-100 rounded-xl p-4 md:p-5 mb-5">
          <h3 class="flex items-center gap-2 text-sm font-bold text-gray-700 mb-4">
            <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs flex items-center justify-center"><i class="fas fa-info"></i></span>
            Informasi Materi
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Judul Materi <span class="text-red-500">*</span></label>
              <input type="text" id="fJudulMateri" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Contoh: Mengenal Diri">
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Fungsi Layanan <span class="text-red-500">*</span></label>
              <select id="fFungsiLayanan" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                <option value="">Pilih Fungsi Layanan</option>
                <?php foreach ($FUNGSI_LAYANAN_OPSI as $opsiFungsi): ?>
                <option value="<?php echo htmlspecialchars($opsiFungsi); ?>"><?php echo htmlspecialchars($opsiFungsi); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="flex items-start gap-1 text-[11px] text-gray-500 mt-1.5"><i class="fas fa-circle-info mt-0.5"></i> <span>Pemahaman, Pencegahan (Preventif), Pengentasan (Kuratif), atau Pemeliharaan dan Pengembangan.</span></p>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Pembuat <span class="text-red-500">*</span></label>
              <select id="fGuruPembuat" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                <option value="">Pilih Nama Guru</option>
                <option value="Tim BK">Tim BK</option>
                <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt): ?>
                <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Sasaran Kelas <span class="text-red-500">*</span></label>
              <div id="daftarSasaranCheckbox" class="border border-gray-300 bg-white rounded-lg p-3 max-h-40 overflow-y-auto text-sm text-gray-500">Memuat daftar kelas...</div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-2 pt-4 border-t" id="footerStep1">
          <button type="button" onclick="tutupModalTambahMateri()" class="px-4 py-2 rounded-lg text-sm border hover:bg-gray-50">Batal</button>
          <button type="button" onclick="lanjutKeSusunMateri()" class="px-4 py-2 rounded-lg text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm">
            Lanjut &rarr; Susun Materi
          </button>
        </div>

        <div id="stepSusunMateri" class="hidden">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 md:p-5">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="flex items-center gap-2 text-sm font-bold text-gray-700">
              <span class="w-6 h-6 rounded-full bg-gray-600 text-white text-xs flex items-center justify-center"><i class="fas fa-layer-group"></i></span>
              Materi Pembelajaran
            </h3>
          </div>
          <p class="text-[11px] text-gray-500 mb-3">Isi materi yang akan dipelajari siswa (satu materi = satu isian).</p>
          <div id="daftarSlideBlock"></div>
        </div>

        <div class="flex justify-between gap-2 mt-6 pt-4 border-t">
          <button type="button" onclick="kembaliKeInfoMateri()" class="px-4 py-2 rounded-lg text-sm border hover:bg-gray-50">&larr; Kembali</button>
          <button type="submit" class="px-4 py-2 rounded-lg text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow-sm">
            <i class="fas fa-save mr-1"></i> <span id="tulisanTombolSimpanMateri">Simpan Materi</span>
          </button>
        </div>
        </div>
      </form>
    </div>
  </div>

  <div id="modalLihatMateri" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9998]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-eye text-blue-600 mr-1"></i> Pratinjau Materi</h2>
        <button type="button" onclick="tutupModalLihatMateri()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <div id="isiLihatMateri" class="p-5 text-sm"></div>
    </div>
  </div>

  <div id="modalPickerSimpanan" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-2 md:p-4 z-[9999]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
      <div class="flex items-center justify-between px-5 py-4 border-b sticky top-0 bg-white z-10">
        <h2 class="text-base font-bold text-gray-800"><i class="fas fa-archive text-blue-600 mr-1"></i> Pilih dari Pustaka Bahan</h2>
        <button type="button" onclick="tutupPickerSimpanan()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
      </div>
      <div id="isiPickerSimpanan" class="p-4 text-sm">
        <p class="text-center text-gray-400 py-6">Memuat data...</p>
      </div>
    </div>
  </div>

<script>
  const BASE_URL = "<?php echo htmlspecialchars($base_url_folder, ENT_QUOTES); ?>";
  let daftarMateri = [];
  let daftarKelasJurusan = [];
  let hitungSlide = 0;

  const BARIS_PER_HALAMAN = 10;
  let halamanArsip = 1;
  let halamanMateri = 1;

  function renderPaginasi(containerId, totalItems, halamanSekarang, onGantiHalaman) {
    const el = document.getElementById(containerId);
    const totalHalaman = Math.max(1, Math.ceil(totalItems / BARIS_PER_HALAMAN));
    if (totalItems === 0) { el.innerHTML = ''; return; }

    const mulai = totalItems === 0 ? 0 : (halamanSekarang - 1) * BARIS_PER_HALAMAN + 1;
    const akhir = Math.min(halamanSekarang * BARIS_PER_HALAMAN, totalItems);

    let tombolHalaman = '';
    const batasBawah = Math.max(1, halamanSekarang - 2);
    const batasAtas = Math.min(totalHalaman, halamanSekarang + 2);
    if (batasBawah > 1) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;
    for (let p = batasBawah; p <= batasAtas; p++) {
      tombolHalaman += `<button type="button" onclick="${onGantiHalaman}(${p})" class="min-w-[2rem] px-2 py-1.5 rounded-lg text-xs font-semibold border ${p === halamanSekarang ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">${p}</button>`;
    }
    if (batasAtas < totalHalaman) tombolHalaman += `<span class="px-2 text-gray-400">...</span>`;

    el.innerHTML = `
      <p class="text-xs text-gray-500">Menampilkan ${mulai}-${akhir} dari ${totalItems} data</p>
      <div class="flex items-center gap-1">
        <button type="button" onclick="${onGantiHalaman}(${halamanSekarang - 1})" ${halamanSekarang <= 1 ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>
        ${tombolHalaman}
        <button type="button" onclick="${onGantiHalaman}(${halamanSekarang + 1})" ${halamanSekarang >= totalHalaman ? 'disabled' : ''} class="px-2 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>
      </div>
    `;
  }

  function gantiHalamanArsip(p) {
    const totalHalaman = Math.max(1, Math.ceil(daftarArsipSaatIni.length / BARIS_PER_HALAMAN));
    if (p < 1 || p > totalHalaman) return;
    halamanArsip = p;
    renderTabelArsip(daftarArsipSaatIni);
  }

  function gantiHalamanMateri(p) {
    const filtered = filterKelasSekarang
      ? daftarMateri.filter(m => (m.sasaran || []).includes(filterKelasSekarang))
      : daftarMateri;
    const totalHalaman = Math.max(1, Math.ceil(filtered.length / BARIS_PER_HALAMAN));
    if (p < 1 || p > totalHalaman) return;
    halamanMateri = p;
    renderTabelMateri();
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
  }

  function pindahTab(tab) {
    document.getElementById('tabSimpanan').classList.toggle('hidden', tab !== 'simpanan');
    document.getElementById('tabMateri').classList.toggle('hidden', tab !== 'materi');
    document.getElementById('tabBtnSimpanan').classList.toggle('border-blue-600', tab === 'simpanan');
    document.getElementById('tabBtnSimpanan').classList.toggle('text-blue-600', tab === 'simpanan');
    document.getElementById('tabBtnSimpanan').classList.toggle('border-transparent', tab !== 'simpanan');
    document.getElementById('tabBtnSimpanan').classList.toggle('text-gray-500', tab !== 'simpanan');
    document.getElementById('tabBtnMateri').classList.toggle('border-blue-600', tab === 'materi');
    document.getElementById('tabBtnMateri').classList.toggle('text-blue-600', tab === 'materi');
    document.getElementById('tabBtnMateri').classList.toggle('border-transparent', tab !== 'materi');
    document.getElementById('tabBtnMateri').classList.toggle('text-gray-500', tab !== 'materi');
    simpanFilterArsipKeUrl();
  }

  const LABEL_TIPE_BAHAN = { teks: 'Teks', gambar: 'Gambar', ppt: 'PPT', lkpd: 'LKPD', youtube: 'Link YouTube' };

  let daftarArsipSaatIni = [];
  let skipSimpanUrl = false;

  function simpanFilterArsipKeUrl() {
    if (skipSimpanUrl) return;
    const params = new URLSearchParams(window.location.search);
    const g = document.getElementById('filterGuruArsip').value;
    const t = document.getElementById('filterTipeArsip').value;
    if (g) params.set('filter_guru', g); else params.delete('filter_guru');
    if (t) params.set('filter_tipe', t); else params.delete('filter_tipe');
    const tabAktif = document.getElementById('tabSimpanan').classList.contains('hidden') ? 'materi' : 'simpanan';
    params.set('tab', tabAktif);
    const fk = (document.getElementById('filterKelasMateri') || {}).value || '';
    if (fk) params.set('filter_kelas', fk); else params.delete('filter_kelas');
    const qs = params.toString();
    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
  }

  function resetFilterArsip() {
    document.getElementById('filterGuruArsip').value = '';
    document.getElementById('filterTipeArsip').value = '';
    muatDaftarArsip();
  }

  function resetFilterMateri() {
    const sel = document.getElementById('filterKelasMateri');
    if (sel) sel.value = '';
    halamanMateri = 1;
    renderTabelMateri();
    simpanFilterArsipKeUrl();
  }

  function muatDaftarArsip() {
    halamanArsip = 1;
    const fd = new FormData();
    fd.append('action', 'list_arsip');
    fd.append('guru', document.getElementById('filterGuruArsip').value);
    fd.append('tipe', document.getElementById('filterTipeArsip').value);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          daftarArsipSaatIni = data.data;
          renderTabelArsip(data.data);
          document.getElementById('statTotalArsip').textContent = data.data.length;
        }
      });
    simpanFilterArsipKeUrl();
  }

  function renderTabelArsip(data) {
    const tbody = document.getElementById('isiTabelArsip');
    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-gray-400">Belum ada bahan di Pustaka. Klik "Tambah Bahan" untuk mulai simpan.</td></tr>`;
      document.getElementById('paginasiArsip').innerHTML = '';
      return;
    }
    const totalHalaman = Math.max(1, Math.ceil(data.length / BARIS_PER_HALAMAN));
    if (halamanArsip > totalHalaman) halamanArsip = totalHalaman;
    const mulai = (halamanArsip - 1) * BARIS_PER_HALAMAN;
    const dataHalaman = data.slice(mulai, mulai + BARIS_PER_HALAMAN);
    tbody.innerHTML = dataHalaman.map(a => {
      let sumberHtml = '-';
      if (a.tipe_bahan === 'teks') sumberHtml = `<span class="text-gray-600">${escapeHtml((a.konten_teks || '').slice(0, 80))}${(a.konten_teks || '').length > 80 ? '...' : ''}</span>`;
      else if (a.tipe_bahan === 'lkpd') sumberHtml = `<span class="text-gray-600">${(JSON.parse(a.lkpd_json || '[]')).length} pertanyaan</span>`;
      else if (a.link) sumberHtml = `<a href="${escapeHtml(a.link)}" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-link mr-1"></i>Buka Link</a>`;
      else if (a.file_lampiran) sumberHtml = `<a href="${BASE_URL}${a.file_lampiran}" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-paperclip mr-1"></i>Buka File</a>`;
      return `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2 font-medium">${escapeHtml(a.judul)}</td>
        <td class="px-3 py-2">${escapeHtml(LABEL_TIPE_BAHAN[a.tipe_bahan] || '-')}</td>
        <td class="px-3 py-2">${escapeHtml(a.kategori || '-')}</td>
        <td class="px-3 py-2">${sumberHtml}</td>
        <td class="px-3 py-2 max-w-xs truncate" title="${escapeHtml(a.keterangan || '')}">${escapeHtml(a.keterangan || '-')}</td>
        <td class="px-3 py-2">${escapeHtml(a.nama_guru_upload || '-')}</td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="bukaModalEditArsip(${a.id_arsip})" class="action-btn text-blue-600 hover:text-blue-800 mr-2" title="Edit simpanan ini"><i class="fas fa-pen"></i></button>
          <button onclick="hapusArsip(${a.id_arsip})" class="action-btn text-red-600 hover:text-red-800" title="Hapus simpanan ini"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `; }).join('');
    renderPaginasi('paginasiArsip', data.length, halamanArsip, 'gantiHalamanArsip');
  }

  function toggleFieldArsip() {
    const tipe = document.getElementById('aTipeBahan').value;
    document.getElementById('aWrapTeks').classList.toggle('hidden', tipe !== 'teks');
    document.getElementById('aWrapLkpd').classList.toggle('hidden', tipe !== 'lkpd');
    const perluLinkFile = tipe === 'gambar' || tipe === 'ppt' || tipe === 'youtube';
    document.getElementById('aWrapLink').classList.toggle('hidden', !perluLinkFile);
    document.getElementById('aWrapFile').classList.toggle('hidden', !perluLinkFile || tipe === 'youtube');
    document.getElementById('aTeksAtau').classList.toggle('hidden', tipe !== 'gambar' && tipe !== 'ppt');
    document.getElementById('aLabelLink').textContent = tipe === 'youtube' ? 'Link YouTube' : 'Link (Google Drive, YouTube, atau web lain)';
  }

  function bukaModalTambahArsip() {
    document.getElementById('formTambahArsip').reset();
    document.getElementById('aIdArsip').value = '';
    document.getElementById('aDaftarPertanyaanLkpd').innerHTML = '';
    document.getElementById('judulModalArsip').textContent = 'Tambah Bahan Baru';
    document.getElementById('tombolSimpanArsip').innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
    document.getElementById('aTipeBahan').disabled = false;
    document.getElementById('aCatatanJenis').classList.add('hidden');
    toggleFieldArsip();
    document.getElementById('modalTambahArsip').classList.remove('hidden');
  }

  function bukaModalEditArsip(id) {
    const a = daftarArsipSaatIni.find(x => x.id_arsip == id);
    if (!a) return;
    document.getElementById('formTambahArsip').reset();
    document.getElementById('aIdArsip').value = a.id_arsip;
    document.getElementById('aJudul').value = a.judul || '';
    document.getElementById('aTipeBahan').value = a.tipe_bahan;
    document.getElementById('aTipeBahan').disabled = true; 
    document.getElementById('aCatatanJenis').classList.remove('hidden');
    document.getElementById('aKategori').value = a.kategori || '';
    document.getElementById('aLink').value = a.link || '';
    document.getElementById('aKontenTeks').value = a.konten_teks || '';
    document.getElementById('aKeterangan').value = a.keterangan || '';
    document.getElementById('aGuru').value = a.nama_guru_upload || '';
    document.getElementById('aDaftarPertanyaanLkpd').innerHTML = '';
    if (a.tipe_bahan === 'lkpd') {
      const pertanyaan = JSON.parse(a.lkpd_json || '[]');
      pertanyaan.forEach(p => tambahPertanyaanDariData(document.getElementById('aDaftarPertanyaanLkpd'), p));
    }
    document.getElementById('judulModalArsip').textContent = 'Edit Bahan';
    document.getElementById('tombolSimpanArsip').innerHTML = '<i class="fas fa-save mr-1"></i> Simpan Perubahan';
    toggleFieldArsip();
    document.getElementById('modalTambahArsip').classList.remove('hidden');
  }

  function tutupModalTambahArsip() {
    document.getElementById('modalTambahArsip').classList.add('hidden');
  }

  function ambilPertanyaanLkpdDari(containerId) {
    const daftarPertanyaan = [];
    document.getElementById(containerId).querySelectorAll('.pertanyaan-block').forEach(pb => {
      const teks = pb.querySelector('.pertanyaan-teks').value.trim();
      const tipe = pb.querySelector('.pertanyaan-tipe').value;
      if (teks === '') return;
      const opsi = Array.from(pb.querySelectorAll('.opsi-teks')).map(o => o.value.trim()).filter(o => o !== '');
      daftarPertanyaan.push({ teks, tipe, opsi });
    });
    return daftarPertanyaan;
  }

  function simpanArsip(e) {
    e.preventDefault();
    const idArsip = document.getElementById('aIdArsip').value;
    const modeEdit = !!idArsip;
    const judul = document.getElementById('aJudul').value.trim();
    const tipeBahan = document.getElementById('aTipeBahan').value;
    const link = document.getElementById('aLink').value.trim();
    const kontenTeks = document.getElementById('aKontenTeks').value.trim();
    const fileInput = document.getElementById('aFile');
    if (judul === '') { alert('Judul wajib diisi.'); return false; }
    if (tipeBahan === 'teks' && kontenTeks === '') { alert('Isi teks wajib diisi.'); return false; }
    if (!modeEdit && ['gambar','ppt','youtube'].includes(tipeBahan) && link === '' && fileInput.files.length === 0) { alert('Isi link ATAU unggah file lampiran.'); return false; }

    const fd = new FormData();
    fd.append('action', modeEdit ? 'edit_arsip' : 'simpan_arsip');
    if (modeEdit) fd.append('id_arsip', idArsip);
    fd.append('judul', judul);
    fd.append('tipe_bahan', tipeBahan);
    fd.append('kategori', document.getElementById('aKategori').value.trim());
    fd.append('link', link);
    fd.append('konten_teks', kontenTeks);
    fd.append('keterangan', document.getElementById('aKeterangan').value.trim());
    fd.append('nama_guru_upload', document.getElementById('aGuru').value);
    if (fileInput.files[0]) fd.append('file_lampiran', fileInput.files[0]);
    if (tipeBahan === 'lkpd') {
      const daftarPertanyaan = ambilPertanyaanLkpdDari('aDaftarPertanyaanLkpd');
      if (daftarPertanyaan.length === 0) { alert('Tambahkan minimal satu pertanyaan LKPD.'); return false; }
      fd.append('lkpd_json', JSON.stringify(daftarPertanyaan));
    }

    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) { tutupModalTambahArsip(); muatDaftarArsip(); }
        else alert(data.message || 'Gagal menyimpan.');
      })
      .catch(() => alert('Terjadi kesalahan saat menyimpan.'));

    return false;
  }

  function bukaModalDrive() {
    document.getElementById('dLinkDrive').value = document.getElementById('linkGoogleDrive').getAttribute('href') || '';
    document.getElementById('modalDrive').classList.remove('hidden');
  }

  function tutupModalDrive() {
    document.getElementById('modalDrive').classList.add('hidden');
  }

  function simpanLinkDrive(e) {
    e.preventDefault();
    const nilai = document.getElementById('dLinkDrive').value.trim();
    if (nilai === '') { alert('Link tidak boleh kosong.'); return false; }
    const fd = new FormData();
    fd.append('action', 'simpan_pengaturan');
    fd.append('nama_pengaturan', 'google_drive_bk');
    fd.append('nilai', nilai);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('linkGoogleDrive').setAttribute('href', data.nilai);
          tutupModalDrive();
        } else alert(data.message || 'Gagal menyimpan link.');
      })
      .catch(() => alert('Terjadi kesalahan saat menyimpan link.'));
    return false;
  }

  function hapusArsip(id, konfirmasiDipakai) {
    if (!konfirmasiDipakai && !confirm('Hapus simpanan ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_arsip');
    fd.append('id_arsip', id);
    if (konfirmasiDipakai) fd.append('konfirmasi_hapus_dipakai', '1');
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          muatDaftarArsip();
        } else if (data.need_confirmation) {
          if (confirm(data.message)) hapusArsip(id, true);
        } else {
          alert(data.message || 'Gagal menghapus.');
        }
      });
  }

  const WARNA_FUNGSI = {
    'Pemahaman': 'bg-blue-50 text-blue-700',
    'Pencegahan (Preventif)': 'bg-green-50 text-green-700',
    'Pengentasan (Kuratif)': 'bg-orange-50 text-orange-700',
    'Pemeliharaan dan Pengembangan': 'bg-purple-50 text-purple-700'
  };

  let filterKelasSekarang = '';

  function muatDaftarMateri() {
    halamanMateri = 1;
    const fd = new FormData();
    fd.append('action', 'list_materi');
    return fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          daftarMateri = data.data;
          isiFilterKelasMateri();
          renderTabelMateri();
          document.getElementById('statTotalMateri').textContent = daftarMateri.length;
        }
      });
  }

  function isiFilterKelasMateri() {
    const semuaKelas = new Set();
    daftarMateri.forEach(m => {
      (m.sasaran || []).forEach(s => semuaKelas.add(s));
    });
    const sel = document.getElementById('filterKelasMateri');
    const valLama = sel.value;
    sel.innerHTML = '<option value="">Semua Kelas</option>' +
      Array.from(semuaKelas).sort().map(k => `<option value="${escapeHtml(k)}" ${k === valLama ? 'selected' : ''}>${escapeHtml(k)}</option>`).join('');
  }

  function renderTabelMateri() {
    filterKelasSekarang = (document.getElementById('filterKelasMateri') || {}).value || '';
    const filtered = filterKelasSekarang
      ? daftarMateri.filter(m => (m.sasaran || []).includes(filterKelasSekarang))
      : daftarMateri;
    const tbody = document.getElementById('isiTabelMateri');
    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-gray-400">Belum ada materi. Klik "Buat Materi Baru" untuk mulai.</td></tr>`;
      document.getElementById('paginasiMateri').innerHTML = '';
      return;
    }
    
    const totalHalaman = Math.max(1, Math.ceil(filtered.length / BARIS_PER_HALAMAN));
    if (halamanMateri > totalHalaman) halamanMateri = totalHalaman;
    const mulai = (halamanMateri - 1) * BARIS_PER_HALAMAN;
    const dataHalaman = filtered.slice(mulai, mulai + BARIS_PER_HALAMAN);
    tbody.innerHTML = dataHalaman.map((m, i) => {
      const posisi = mulai + i;
      const bisaNaik = posisi > 0;
      const bisaTurun = posisi < daftarMateri.length - 1;
      const dibuatBaru = m.created_at && ((Date.now() - new Date(m.created_at.replace(' ', 'T')).getTime()) < 3 * 24 * 60 * 60 * 1000);
      return `
      <tr class="border-b hover:bg-gray-50">
        <td class="px-3 py-2 text-center">
          <div class="flex flex-col items-center gap-0.5">
            <button type="button" onclick="geserUrutanMateri(${m.id_materi}, 'naik')" ${bisaNaik ? '' : 'disabled'} class="text-gray-400 hover:text-blue-600 disabled:opacity-20 disabled:hover:text-gray-400" title="Naikkan urutan"><i class="fas fa-caret-up"></i></button>
            <span class="font-semibold text-gray-500 text-xs">${posisi + 1}</span>
            <button type="button" onclick="geserUrutanMateri(${m.id_materi}, 'turun')" ${bisaTurun ? '' : 'disabled'} class="text-gray-400 hover:text-blue-600 disabled:opacity-20 disabled:hover:text-gray-400" title="Turunkan urutan"><i class="fas fa-caret-down"></i></button>
          </div>
        </td>
        <td class="px-3 py-2 font-medium">${escapeHtml(m.judul)}${dibuatBaru ? ' <span class="inline-flex items-center bg-blue-100 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">BARU</span>' : ''}${m.jumlah_lanjutan > 0 ? ` <span class="inline-flex items-center gap-1 text-[11px] text-purple-600 font-normal"><i class="fas fa-route"></i>${m.jumlah_lanjutan} tindak lanjut</span>` : ''}</td>
        <td class="px-3 py-2">${m.fungsi_layanan ? `<span class="px-2 py-1 rounded-full text-xs font-semibold ${WARNA_FUNGSI[m.fungsi_layanan] || 'bg-gray-100 text-gray-600'}">${escapeHtml(m.fungsi_layanan)}</span>` : '<span class="text-gray-400 text-xs">-</span>'}</td>
        <td class="px-3 py-2">${m.sasaran.map(s => escapeHtml(s)).join(', ') || '-'}</td>
        <td class="px-3 py-2">${escapeHtml(m.nama_guru_pembuat || '-')}</td>
        <td class="px-3 py-2 text-center">${m.jumlah_slide}</td>
        <td class="px-3 py-2 text-center">
          ${m.jumlah_sasaran_siswa > 0
            ? `<a href="bimbinganklasikal_monitoring.php?id_materi=${m.id_materi}" class="text-indigo-600 hover:text-indigo-800 hover:underline font-semibold">${m.jumlah_sasaran_siswa}</a>`
            : m.jumlah_sasaran_siswa}
        </td>
        <td class="px-3 py-2 text-center">
          <span class="px-2 py-1 rounded-full text-xs font-semibold ${m.status_aktif == 1 ? 'badge-status-aktif' : 'badge-status-nonaktif'}">
            ${m.status_aktif == 1 ? 'Aktif' : 'Nonaktif'}
          </span>
        </td>
        <td class="px-3 py-2 text-center whitespace-nowrap">
          <button onclick="lihatMateri(${m.id_materi})" class="action-btn text-blue-600 hover:text-blue-800 mr-2" title="Lihat isi materi"><i class="fas fa-eye"></i></button>
          <button onclick="bukaModalEditMateri(${m.id_materi})" class="action-btn text-amber-600 hover:text-amber-800 mr-2" title="Edit materi ini"><i class="fas fa-pen"></i></button>
          <button onclick="bukaMenuAksiMateri(${m.id_materi}, ${JSON.stringify(m.judul).replace(/"/g, '&quot;')}, ${m.status_aktif})" class="action-btn text-gray-500 hover:text-gray-800" title="Aksi lainnya"><i class="fas fa-ellipsis-vertical"></i></button>
        </td>
      </tr>
    `;
    }).join('');
    renderPaginasi('paginasiMateri', filtered.length, halamanMateri, 'gantiHalamanMateri');
  }

  function bukaMenuAksiMateri(id, judul, statusAktif) {
    document.getElementById('aksiJudulMateri').textContent = judul;
    document.getElementById('aksiDaftarMenu').innerHTML = `
      <a href="bimbinganklasikal_monitoring.php?id_materi=${id}" class="w-full flex items-center gap-3 px-3 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-chart-line text-indigo-600 w-5"></i> Lihat Progress Siswa</a>
      <button onclick="tutupMenuAksiMateri(); duplikatMateri(${id})" class="w-full flex items-center gap-3 px-3 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-copy text-gray-500 w-5"></i> Duplikat Materi</button>
      ${statusAktif == 1
        ? `<button onclick="tutupMenuAksiMateri(); nonaktifkanMateri(${id})" class="w-full flex items-center gap-3 px-3 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-ban text-gray-500 w-5"></i> Nonaktifkan</button>`
        : `<button onclick="tutupMenuAksiMateri(); aktifkanMateri(${id})" class="w-full flex items-center gap-3 px-3 py-3 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"><i class="fas fa-check-circle text-green-600 w-5"></i> Aktifkan Kembali</button>`}
      <div class="border-t my-1"></div>
      <button onclick="tutupMenuAksiMateri(); hapusMateri(${id})" class="w-full flex items-center gap-3 px-3 py-3 text-sm text-red-600 hover:bg-red-50 rounded-lg"><i class="fas fa-trash w-5"></i> Hapus Permanen</button>
    `;
    document.getElementById('modalAksiMateri').classList.remove('hidden');
  }

  function tutupMenuAksiMateri() {
    document.getElementById('modalAksiMateri').classList.add('hidden');
  }

  function geserUrutanMateri(id, arah) {
    const fd = new FormData();
    fd.append('action', 'geser_urutan_materi');
    fd.append('id_materi', id);
    fd.append('arah', arah);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) muatDaftarMateri(); else alert(data.message || 'Gagal mengubah urutan.'); });
  }

  function duplikatMateri(id) {
    if (!confirm('Duplikat materi ini? Salinan akan dibuat berstatus Nonaktif supaya Anda bisa menyunting dulu sebelum ditampilkan ke siswa.')) return;
    const fd = new FormData();
    fd.append('action', 'duplikat_materi');
    fd.append('id_materi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) muatDaftarMateri(); else alert(data.message || 'Gagal menduplikat materi.'); });
  }

  function nonaktifkanMateri(id) {
    if (!confirm('Nonaktifkan materi ini? Materi tidak akan tampil lagi ke siswa yang belum mulai, tapi progress siswa yang sudah pernah mengerjakan tetap tersimpan. Materi masih bisa diaktifkan lagi kapan saja.')) return;
    const fd = new FormData();
    fd.append('action', 'nonaktifkan_materi');
    fd.append('id_materi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) muatDaftarMateri(); else alert(data.message || 'Gagal menonaktifkan materi.'); });
  }

  function aktifkanMateri(id) {
    const fd = new FormData();
    fd.append('action', 'aktifkan_materi');
    fd.append('id_materi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) muatDaftarMateri(); else alert(data.message || 'Gagal mengaktifkan materi.'); });
  }

  function hapusMateri(id, konfirmasiProgres) {
    if (!konfirmasiProgres && !confirm('Hapus materi ini secara PERMANEN? Semua slide dan LKPD untuk materi ini akan ikut terhapus dan tidak bisa dikembalikan.\n\nKalau hanya ingin menyembunyikan materi sementara, gunakan tombol Nonaktifkan saja.')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_materi');
    fd.append('id_materi', id);
    if (konfirmasiProgres) fd.append('konfirmasi_reset_progres', '1');
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          muatDaftarMateri();
        } else if (data.need_confirmation) {
          if (confirm(data.message)) hapusMateri(id, true);
        } else {
          alert(data.message || 'Gagal menghapus materi.');
        }
      });
  }

  let daftarLanjutanSaatIni = [];

  function bukaModalLanjutan(idMateri, judulMateri) {
    document.getElementById('lJudulMateri').textContent = judulMateri;
    document.getElementById('lIdMateri').value = idMateri;
    resetFormLanjutan();
    document.getElementById('isiDaftarLanjutan').innerHTML = '<p class="text-center text-gray-400 py-4 text-sm">Memuat data...</p>';
    document.getElementById('modalLanjutan').classList.remove('hidden');
    muatDaftarLanjutan(idMateri);
  }

  function tutupModalLanjutan() {
    document.getElementById('modalLanjutan').classList.add('hidden');
  }

  function muatDaftarLanjutan(idMateri) {
    const fd = new FormData();
    fd.append('action', 'list_lanjutan');
    fd.append('id_materi', idMateri);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => { if (data.success) { daftarLanjutanSaatIni = data.data; renderDaftarLanjutan(); } });
  }

  const LABEL_STATUS_LANJUTAN = { rencana: 'Rencana', proses: 'Proses', selesai: 'Selesai' };
  const WARNA_STATUS_LANJUTAN = { rencana: 'bg-gray-100 text-gray-600', proses: 'bg-amber-50 text-amber-700', selesai: 'bg-green-50 text-green-700' };

  function renderDaftarLanjutan() {
    const wrap = document.getElementById('isiDaftarLanjutan');
    if (daftarLanjutanSaatIni.length === 0) {
      wrap.innerHTML = '<p class="text-center text-gray-400 py-4 text-sm">Belum ada tindak lanjut untuk materi ini.</p>';
      return;
    }
    wrap.innerHTML = daftarLanjutanSaatIni.map(l => `
      <div class="border rounded-lg p-3 mb-2 flex items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="font-semibold text-sm">${escapeHtml(l.judul)}</p>
          <p class="text-xs text-gray-500">${l.tanggal_rencana ? escapeHtml(l.tanggal_rencana) : 'Tanggal belum ditentukan'} &bull; ${escapeHtml(l.nama_guru || '-')}</p>
          ${l.catatan ? `<p class="text-xs text-gray-600 mt-1">${escapeHtml(l.catatan)}</p>` : ''}
          <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ${WARNA_STATUS_LANJUTAN[l.status] || 'bg-gray-100 text-gray-600'}">${LABEL_STATUS_LANJUTAN[l.status] || l.status}</span>
        </div>
        <div class="flex gap-2 shrink-0">
          <button onclick="editLanjutan(${l.id_lanjutan})" class="text-amber-600 hover:text-amber-800" title="Edit"><i class="fas fa-pen"></i></button>
          <button onclick="hapusLanjutan(${l.id_lanjutan})" class="text-red-600 hover:text-red-800" title="Hapus"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `).join('');
  }

  function editLanjutan(id) {
    const l = daftarLanjutanSaatIni.find(x => x.id_lanjutan == id);
    if (!l) return;
    document.getElementById('lIdLanjutan').value = l.id_lanjutan;
    document.getElementById('lJudul').value = l.judul || '';
    document.getElementById('lTanggal').value = l.tanggal_rencana || '';
    document.getElementById('lStatus').value = l.status || 'rencana';
    document.getElementById('lGuru').value = l.nama_guru || '';
    document.getElementById('lCatatan').value = l.catatan || '';
    document.getElementById('lJudulForm').textContent = 'Edit Rencana Tindak Lanjut';
    document.getElementById('tombolBatalLanjutan').classList.remove('hidden');
  }

  function resetFormLanjutan() {
    document.getElementById('formLanjutan').reset();
    document.getElementById('lIdLanjutan').value = '';
    document.getElementById('lJudulForm').textContent = 'Tambah Rencana Tindak Lanjut';
    document.getElementById('tombolBatalLanjutan').classList.add('hidden');
  }

  function simpanLanjutan(e) {
    e.preventDefault();
    const judul = document.getElementById('lJudul').value.trim();
    if (judul === '') { alert('Judul tindak lanjut wajib diisi.'); return false; }
    const fd = new FormData();
    fd.append('action', 'simpan_lanjutan');
    fd.append('id_materi', document.getElementById('lIdMateri').value);
    fd.append('id_lanjutan', document.getElementById('lIdLanjutan').value);
    fd.append('judul', judul);
    fd.append('tanggal_rencana', document.getElementById('lTanggal').value);
    fd.append('status', document.getElementById('lStatus').value);
    fd.append('nama_guru', document.getElementById('lGuru').value);
    fd.append('catatan', document.getElementById('lCatatan').value.trim());
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) { resetFormLanjutan(); muatDaftarLanjutan(document.getElementById('lIdMateri').value); muatDaftarMateri(); }
        else alert(data.message || 'Gagal menyimpan tindak lanjut.');
      });
    return false;
  }

  function hapusLanjutan(id) {
    if (!confirm('Hapus catatan tindak lanjut ini?')) return;
    const fd = new FormData();
    fd.append('action', 'hapus_lanjutan');
    fd.append('id_lanjutan', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) { muatDaftarLanjutan(document.getElementById('lIdMateri').value); muatDaftarMateri(); }
        else alert(data.message || 'Gagal menghapus.');
      });
  }

  function lihatMateri(id) {
    const fd = new FormData();
    fd.append('action', 'lihat_materi');
    fd.append('id_materi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) { alert(data.message || 'Gagal memuat materi.'); return; }
        const m = data.data;
        let html = `<h3 class="text-lg font-bold mb-1 break-words">${escapeHtml(m.judul)}</h3>`;
        html += `<p class="text-gray-500 mb-3 break-words whitespace-pre-line">${escapeHtml(m.deskripsi || '')}</p>`;
        html += `<p class="text-xs text-gray-500 mb-4 break-words">Sasaran: ${m.sasaran.map(s => escapeHtml(s.kelas + ' ' + s.jurusan)).join(', ') || '-'} &bull; Penyusun: ${escapeHtml(m.nama_guru_pembuat || '-')}</p>`;
        m.slides.forEach((sl, i) => {
          html += `<div class="slide-block" style="overflow-wrap:anywhere;"><div class="slide-block-header"><span class="font-semibold break-words">${sl.judul_slide ? escapeHtml(sl.judul_slide) : 'Materi'}</span></div>`;
          if (sl.konten_teks) html += `<p class="text-sm text-gray-700 mb-2 whitespace-pre-line break-words" style="overflow-wrap:anywhere;">${escapeHtml(sl.konten_teks)}</p>`;
          if (sl.gambar) html += `<img src="${sl.gambar.startsWith('http') ? sl.gambar : BASE_URL + sl.gambar}" class="max-w-full rounded-lg mb-2 border">`;
          if (sl.file_ppt) html += `<p class="text-sm mb-2"><a href="${sl.file_ppt.startsWith('http') ? sl.file_ppt : BASE_URL + sl.file_ppt}" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-file-powerpoint text-orange-600 mr-1"></i> ${escapeHtml(sl.file_ppt.split('/').pop())}</a></p>`;
          if (sl.link_youtube) html += `<p class="text-sm mb-2"><i class="fab fa-youtube text-red-600 mr-1"></i> ${escapeHtml(sl.link_youtube)}</p>`;
          if (sl.butuh_lkpd == 1 && sl.pertanyaan.length > 0) {
            html += `<div class="mt-2 pt-2 border-t"><p class="text-xs font-semibold text-gray-600 mb-1">LKPD (${sl.pertanyaan.length} pertanyaan):</p>`;
            sl.pertanyaan.forEach((p, pi) => {
              html += `<p class="text-xs text-gray-600 mb-1">${pi + 1}. ${escapeHtml(p.teks_pertanyaan)} <span class="text-gray-400">(${p.tipe_jawaban.replace('_', ' ')})</span></p>`;
            });
            html += `</div>`;
          }
          html += `</div>`;
        });
        document.getElementById('isiLihatMateri').innerHTML = html;
        document.getElementById('modalLihatMateri').classList.remove('hidden');
      });
  }

  function tutupModalLihatMateri() {
    document.getElementById('modalLihatMateri').classList.add('hidden');
  }

  function muatDaftarKelasJurusan() {
    const fd = new FormData();
    fd.append('action', 'get_kelas_jurusan');
    return fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          daftarKelasJurusan = data.data;
          const wrap = document.getElementById('daftarSasaranCheckbox');
          if (daftarKelasJurusan.length === 0) {
            wrap.innerHTML = `<p class="text-gray-400">Belum ada data kelas siswa.</p>`;
            return;
          }

          const kelompokKelas = {};
          daftarKelasJurusan.forEach((kj) => {
            if (!kelompokKelas[kj.kelas]) kelompokKelas[kj.kelas] = [];
            kelompokKelas[kj.kelas].push(kj);
          });
          wrap.innerHTML = Object.keys(kelompokKelas).map((kelas, gi) => `
            <div class="mb-3 pb-2 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
              <label class="flex items-center gap-2 py-1 cursor-pointer font-semibold text-gray-700">
                <input type="checkbox" class="sasaran-checkbox-kelas" data-kelas-group="${gi}" onchange="toggleSemuaJurusanKelas(${gi}, this.checked)">
                <span>Kelas ${escapeHtml(kelas)} <span class="text-xs font-normal text-gray-400">(semua jurusan / sasaran umum)</span></span>
              </label>
              <div class="pl-6">
                ${kelompokKelas[kelas].map((kj) => `
                  <label class="flex items-center gap-2 py-1 cursor-pointer">
                    <input type="checkbox" class="sasaran-checkbox" data-kelas-group="${gi}" value='${JSON.stringify(kj)}' onchange="perbaruiCentangKelas(${gi})">
                    <span>${escapeHtml(kj.kelas)} ${escapeHtml(kj.jurusan || '')}</span>
                  </label>
                `).join('')}
              </div>
            </div>
          `).join('');
        }
      });
  }

  function toggleSemuaJurusanKelas(groupIndex, checked) {
    document.querySelectorAll(`.sasaran-checkbox[data-kelas-group="${groupIndex}"]`).forEach(cb => {
      cb.checked = checked;
    });
  }

  function perbaruiCentangKelas(groupIndex) {
    const semuaJurusan = document.querySelectorAll(`.sasaran-checkbox[data-kelas-group="${groupIndex}"]`);
    const semuaTercentang = Array.from(semuaJurusan).every(cb => cb.checked);
    const checkboxKelas = document.querySelector(`.sasaran-checkbox-kelas[data-kelas-group="${groupIndex}"]`);
    if (checkboxKelas) checkboxKelas.checked = semuaTercentang;
  }

  function bukaModalTambahMateri() {
    document.getElementById('formTambahMateri').reset();
    document.getElementById('fIdMateri').value = '';
    document.getElementById('daftarSlideBlock').innerHTML = '';
    hitungSlide = 0;
    tambahSlideBlock();
    
    const urutanBerikutnya = daftarMateri.length > 0 ? Math.max(...daftarMateri.map(m => m.urutan)) + 1 : 1;
    document.getElementById('fUrutanMateri').value = urutanBerikutnya;
    document.getElementById('judulModalMateri').textContent = 'Tambah Materi Bimbingan Klasikal';
    document.getElementById('ikonModalMateri').className = 'fas fa-plus text-blue-600 mr-1';
    document.getElementById('tulisanTombolSimpanMateri').textContent = 'Simpan Materi';
    muatDaftarKelasJurusan().then(() => {});
    tampilkanStepInfoMateri();
    document.getElementById('modalTambahMateri').classList.remove('hidden');
  }

  function bukaModalEditMateri(id) {
    const fd = new FormData();
    fd.append('action', 'lihat_materi');
    fd.append('id_materi', id);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => {
        if (!data.success) { alert(data.message || 'Gagal memuat materi.'); return; }
        const m = data.data;
        document.getElementById('formTambahMateri').reset();
        document.getElementById('fIdMateri').value = m.id_materi;
        document.getElementById('fJudulMateri').value = m.judul || '';
        document.getElementById('fDeskripsiMateri').value = m.deskripsi || '';
        document.getElementById('fFungsiLayanan').value = m.fungsi_layanan || '';
        document.getElementById('fGuruPembuat').value = m.nama_guru_pembuat || '';
        document.getElementById('fUrutanMateri').value = m.urutan || 1;
        document.getElementById('judulModalMateri').textContent = 'Edit Materi Bimbingan Klasikal';
        document.getElementById('ikonModalMateri').className = 'fas fa-pen text-amber-600 mr-1';
        document.getElementById('tulisanTombolSimpanMateri').textContent = 'Simpan Perubahan';

        document.getElementById('daftarSlideBlock').innerHTML = '';
        hitungSlide = 0;
        if (m.slides.length > 0) {
          tambahSlideBlock(m.slides[0]);
        } else {
          tambahSlideBlock();
        }

        muatDaftarKelasJurusan().then(() => {
          const sasaranAktif = new Set((m.sasaran || []).map(s => s.kelas + '|' + s.jurusan));
          document.querySelectorAll('.sasaran-checkbox').forEach(cb => {
            const v = JSON.parse(cb.value);
            if (sasaranAktif.has(v.kelas + '|' + v.jurusan)) cb.checked = true;
          });
          document.querySelectorAll('.sasaran-checkbox-kelas').forEach(cbk => {
            perbaruiCentangKelas(cbk.dataset.kelasGroup);
          });
        });

        document.getElementById('modalTambahMateri').classList.remove('hidden');
        tampilkanStepInfoMateri();
      })
      .catch(() => alert('Terjadi kesalahan saat memuat materi.'));
  }

  function tutupModalTambahMateri() {
    document.getElementById('modalTambahMateri').classList.add('hidden');
  }

  function tampilkanStepInfoMateri() {
    document.getElementById('stepInfoMateri').classList.remove('hidden');
    document.getElementById('footerStep1').classList.remove('hidden');
    document.getElementById('stepSusunMateri').classList.add('hidden');
    document.getElementById('langkahIndikator1').className = 'flex items-center gap-1.5 text-blue-600';
    document.getElementById('langkahIndikator1').querySelector('span').className = 'w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]';
    document.getElementById('langkahIndikator2').className = 'flex items-center gap-1.5 text-gray-400';
    document.getElementById('langkahIndikator2').querySelector('span').className = 'w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-[10px]';
    document.getElementById('modalTambahMateri').querySelector('.overflow-y-auto').scrollTop = 0;
  }

  function lanjutKeSusunMateri() {
    const judul = document.getElementById('fJudulMateri').value.trim();
    if (!judul) { alert('Judul materi belum diisi.'); document.getElementById('fJudulMateri').focus(); return; }
    const fungsi = document.getElementById('fFungsiLayanan').value;
    if (!fungsi) { alert('Fungsi layanan belum dipilih.'); document.getElementById('fFungsiLayanan').focus(); return; }
    const guru = document.getElementById('fGuruPembuat').value;
    if (!guru) { alert('Guru BK belum dipilih.'); document.getElementById('fGuruPembuat').focus(); return; }
    const adaSasaran = document.querySelectorAll('.sasaran-checkbox:checked').length > 0;
    if (!adaSasaran) { alert('Sasaran kelas belum dipilih.'); return; }

    document.getElementById('stepInfoMateri').classList.add('hidden');
    document.getElementById('footerStep1').classList.add('hidden');
    document.getElementById('stepSusunMateri').classList.remove('hidden');
    document.getElementById('langkahIndikator1').className = 'flex items-center gap-1.5 text-gray-400';
    document.getElementById('langkahIndikator1').querySelector('span').className = 'w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-[10px]';
    document.getElementById('langkahIndikator2').className = 'flex items-center gap-1.5 text-blue-600';
    document.getElementById('langkahIndikator2').querySelector('span').className = 'w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]';
    document.getElementById('modalTambahMateri').querySelector('.overflow-y-auto').scrollTop = 0;
  }

  function kembaliKeInfoMateri() {
    tampilkanStepInfoMateri();
  }

  function tambahSlideBlock(dataSlide) {
    const wrap = document.getElementById('daftarSlideBlock');
    if (wrap.children.length > 0) return;
    const idx = hitungSlide++;
    const div = document.createElement('div');
    div.className = 'slide-block';
    div.dataset.idx = idx;
    div.innerHTML = `
      <div class="slide-block-header">
        <span class="font-semibold text-sm">Isi Materi</span>
      </div>
      <div class="mb-3">
        <label class="block text-xs font-medium text-gray-600 mb-1">Topik</label>
        <input type="text" class="slide-input-judul w-full px-3 py-2 border rounded-lg text-sm" placeholder="Contoh: Apa itu Mengenal Diri?">
      </div>
      <div class="mb-3">
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-medium text-gray-600">Materi Pembelajaran</label>
          <button type="button" onclick="bukaPickerSimpanan('teks', this.closest('.slide-block'), 'teks')" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-archive"></i>Pilih dari Simpanan</button>
        </div>
        <textarea class="slide-input-teks w-full px-3 py-2 border rounded-lg text-sm" rows="4" placeholder="Tulis materi yang akan dipelajari siswa di bagian ini..." oninput="this.closest('.slide-block').dataset.teksArsip=''; renderChipBahan(this.closest('.slide-block'),'teks');"></textarea>
        <div class="chip-bahan-teks"></div>
      </div>

      <div class="flex items-center gap-2 flex-wrap mb-3">
        <button type="button" class="media-toggle-btn text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50" onclick="toggleMediaField(this, 'gambar')"><i class="fas fa-image mr-1"></i>Gambar</button>
        <button type="button" class="media-toggle-btn text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50" onclick="toggleMediaField(this, 'youtube')"><i class="fas fa-video mr-1"></i>Video</button>
        <button type="button" class="media-toggle-btn text-xs font-semibold px-3 py-1.5 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50" onclick="toggleMediaField(this, 'ppt')"><i class="fas fa-file mr-1"></i>File</button>
      </div>

      <div class="media-field-gambar hidden mb-3 bg-gray-50 border rounded-lg p-3">
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-medium text-gray-600">Gambar</label>
          <div class="flex items-center gap-2">
            <button type="button" onclick="bukaPickerSimpanan('gambar', this.closest('.slide-block'), 'gambar')" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-archive"></i>Pilih dari Simpanan</button>
            <button type="button" onclick="toggleMediaField(this, 'gambar', true)" class="text-gray-400 hover:text-red-600" title="Batalkan"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <input type="file" class="slide-input-gambar w-full text-sm" accept="image/*" onchange="onPilihFileBaru(this, 'gambar')">
        <div class="chip-bahan-gambar"></div>
      </div>
      <div class="media-field-youtube hidden mb-3 bg-gray-50 border rounded-lg p-3">
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-medium text-gray-600">Link YouTube</label>
          <div class="flex items-center gap-2">
            <button type="button" onclick="bukaPickerSimpanan('youtube', this.closest('.slide-block'), 'youtube')" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-archive"></i>Pilih dari Simpanan</button>
            <button type="button" onclick="toggleMediaField(this, 'youtube', true)" class="text-gray-400 hover:text-red-600" title="Batalkan"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <input type="text" class="slide-input-youtube w-full px-3 py-2 border rounded-lg text-sm" placeholder="https://youtube.com/watch?v=..." oninput="this.closest('.slide-block').dataset.youtubeArsip='';">
        <div class="chip-bahan-youtube"></div>
      </div>
      <div class="media-field-ppt hidden mb-3 bg-gray-50 border rounded-lg p-3">
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-medium text-gray-600">File PPT</label>
          <div class="flex items-center gap-2">
            <button type="button" onclick="bukaPickerSimpanan('ppt', this.closest('.slide-block'), 'ppt')" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-archive"></i>Pilih dari Simpanan</button>
            <button type="button" onclick="toggleMediaField(this, 'ppt', true)" class="text-gray-400 hover:text-red-600" title="Batalkan"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <input type="file" class="slide-input-ppt w-full text-sm" accept=".ppt,.pptx" onchange="onPilihFileBaru(this, 'ppt')">
        <div class="chip-bahan-ppt"></div>
      </div>

      <div class="lkpd-summary flex items-center justify-between bg-gray-50 border rounded-lg px-3 py-2.5 mt-2">
        <div>
          <span class="text-xs font-semibold text-gray-500 block mb-0.5">Aktivitas Siswa / LKPD</span>
          <span class="lkpd-summary-text text-sm text-gray-400">Belum ada LKPD</span>
        </div>
        <div class="lkpd-summary-actions flex items-center gap-2">
          <button type="button" onclick="bukaPopupLkpd(this)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 px-2 py-1"><i class="fas fa-plus mr-1"></i>Tambahkan LKPD</button>
        </div>
      </div>

      <div class="lkpd-builder hidden fixed inset-0 bg-black/50 flex items-center justify-center p-3 md:p-4 z-[9999]">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[88vh] overflow-y-auto p-4 md:p-5">
          <div class="flex items-center justify-between mb-3 pb-3 border-b">
            <h3 class="text-sm font-bold text-gray-800"><i class="fas fa-clipboard-list text-blue-600 mr-1"></i> Aktivitas Siswa / LKPD</h3>
            <button type="button" onclick="tutupPopupLkpd(this)" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button>
          </div>
          <label class="flex items-center gap-2 text-sm mb-3 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
            <input type="checkbox" class="slide-input-butuh-lkpd">
            <span>Siswa wajib mengisi LKPD ini sebelum lanjut ke bagian berikutnya</span>
          </label>
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-600">Pertanyaan</span>
            <button type="button" onclick="bukaPickerSimpanan('lkpd', this.closest('.slide-block'), 'lkpd')" class="inline-flex items-center gap-1 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-full"><i class="fas fa-archive"></i>Impor dari Simpanan</button>
          </div>
          <div class="daftar-pertanyaan"></div>
          <button type="button" onclick="tambahPertanyaanLkpd(this)" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg text-xs mt-1">
            <i class="fas fa-plus mr-1"></i> Tambah Pertanyaan
          </button>
          <div class="flex items-center justify-between gap-2 pt-3 mt-3 border-t">
            <button type="button" onclick="hapusSemuaLkpd(this)" class="text-red-600 hover:text-red-800 text-xs font-semibold"><i class="fas fa-trash mr-1"></i>Hapus LKPD</button>
            <button type="button" onclick="tutupPopupLkpd(this)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-xs font-semibold">Selesai</button>
          </div>
        </div>
      </div>
    `;
    wrap.appendChild(div);

    if (dataSlide) {
      div.querySelector('.slide-input-judul').value = dataSlide.judul_slide || '';
      div.querySelector('.slide-input-teks').value = dataSlide.konten_teks || '';
      div.querySelector('.slide-input-youtube').value = dataSlide.link_youtube || '';
      if (dataSlide.link_youtube) div.querySelector('.media-field-youtube').classList.remove('hidden');
      if (dataSlide.gambar) {
        div.dataset.gambarExisting = dataSlide.gambar;
        div.querySelector('.slide-input-gambar').disabled = true;
        div.querySelector('.media-field-gambar').classList.remove('hidden');
        div.querySelector('.chip-bahan-gambar').innerHTML = `<span class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full mt-1"><i class="fas fa-image"></i> Gambar sudah ada <button type="button" onclick="lepasFileExisting(this,'gambar')" class="text-green-500 hover:text-green-800 ml-1"><i class="fas fa-times"></i></button></span>`;
      }
      if (dataSlide.file_ppt) {
        div.dataset.pptExisting = dataSlide.file_ppt;
        div.querySelector('.slide-input-ppt').disabled = true;
        div.querySelector('.media-field-ppt').classList.remove('hidden');
        const namaPpt = dataSlide.file_ppt.split('/').pop().split('?')[0];
        div.querySelector('.chip-bahan-ppt').innerHTML = `<span class="inline-flex items-center gap-1 bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full mt-1"><i class="fas fa-file-powerpoint"></i> ${escapeHtml(namaPpt)} <button type="button" onclick="lepasFileExisting(this,'ppt')" class="text-green-500 hover:text-green-800 ml-1" title="Hapus file PPT"><i class="fas fa-times"></i></button></span>`;
      }
      if (dataSlide.butuh_lkpd == 1 && dataSlide.pertanyaan && dataSlide.pertanyaan.length > 0) {
        const cb = div.querySelector('.slide-input-butuh-lkpd');
        cb.checked = true;
        const builder = div.querySelector('.lkpd-builder');
        const daftarP = builder.querySelector('.daftar-pertanyaan');
        dataSlide.pertanyaan.forEach(p => tambahPertanyaanDariData(daftarP, { teks: p.teks_pertanyaan, tipe: p.tipe_jawaban, opsi: p.opsi_jawaban || [] }));
      }
    }
    perbaruiRingkasanLkpd(div);
  }

  function lepasFileExisting(btn, field) {
    
    const slideBlock = btn.closest('.slide-block');
    delete slideBlock.dataset[field + 'Existing'];
    slideBlock.querySelector('.slide-input-' + field).disabled = false;
    slideBlock.querySelector('.chip-bahan-' + field).innerHTML = '';
  }

  function hapusSlideBlock(btn) {
    const wrap = document.getElementById('daftarSlideBlock');
    if (wrap.children.length <= 1) { alert('Materi minimal harus punya 1 bagian.'); return; }
    btn.closest('.slide-block').remove();
    Array.from(wrap.children).forEach((el, i) => { el.querySelector('.slide-block-header span').textContent = 'Bagian ' + (i + 1); });
  }

  function toggleMediaField(btn, field, paksaTutup) {
    const slideBlock = btn.closest('.slide-block');
    const fieldWrap = slideBlock.querySelector('.media-field-' + field);
    const akanTutup = paksaTutup || !fieldWrap.classList.contains('hidden');
    fieldWrap.classList.toggle('hidden', akanTutup);
    if (akanTutup) {
      if (field === 'gambar' || field === 'ppt') {
        const input = slideBlock.querySelector('.slide-input-' + field);
        input.value = '';
        input.disabled = false;
      } else if (field === 'youtube') {
        slideBlock.querySelector('.slide-input-youtube').value = '';
      }
      delete slideBlock.dataset[field + 'Existing'];
      slideBlock.dataset[field + 'Arsip'] = '';
      slideBlock.querySelector('.chip-bahan-' + field).innerHTML = '';
    }
  }

  function bukaPopupLkpd(btn) {
    const slideBlock = btn.closest('.slide-block');
    const builder = slideBlock.querySelector('.lkpd-builder');
    const daftarP = builder.querySelector('.daftar-pertanyaan');
    if (daftarP.children.length === 0) {
      tambahPertanyaanLkpd(builder.querySelector('button[onclick^="tambahPertanyaanLkpd"]'));
    }
    slideBlock.querySelector('.slide-input-butuh-lkpd').checked = true;
    builder.classList.remove('hidden');
  }

  function tutupPopupLkpd(btn) {
    const slideBlock = btn.closest('.slide-block');
    const builder = slideBlock.querySelector('.lkpd-builder');
    builder.classList.add('hidden');
    perbaruiRingkasanLkpd(slideBlock);
  }

  function hapusSemuaLkpd(btn) {
    if (!confirm('Hapus LKPD di bagian ini?')) return;
    const slideBlock = btn.closest('.slide-block');
    const builder = slideBlock.querySelector('.lkpd-builder');
    builder.querySelector('.daftar-pertanyaan').innerHTML = '';
    slideBlock.querySelector('.slide-input-butuh-lkpd').checked = false;
    builder.classList.add('hidden');
    perbaruiRingkasanLkpd(slideBlock);
  }

  function perbaruiRingkasanLkpd(slideBlock) {
    const builder = slideBlock.querySelector('.lkpd-builder');
    const jumlah = builder.querySelectorAll('.pertanyaan-block').length;
    const teks = slideBlock.querySelector('.lkpd-summary-text');
    const aksi = slideBlock.querySelector('.lkpd-summary-actions');
    if (jumlah > 0) {
      teks.textContent = '✓ LKPD sudah ditambahkan • ' + jumlah + ' pertanyaan';
      teks.className = 'lkpd-summary-text text-sm text-green-600 font-medium';
      aksi.innerHTML = `
        <button type="button" onclick="bukaPopupLkpd(this)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 px-2 py-1">Edit LKPD</button>
        <button type="button" onclick="hapusSemuaLkpd(this)" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1">Hapus LKPD</button>
      `;
    } else {
      teks.textContent = 'Belum ada LKPD';
      teks.className = 'lkpd-summary-text text-sm text-gray-400';
      aksi.innerHTML = `<button type="button" onclick="bukaPopupLkpd(this)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 px-2 py-1"><i class="fas fa-plus mr-1"></i>Tambahkan LKPD</button>`;
    }
  }

  function buatElemenPertanyaan() {
    const div = document.createElement('div');
    div.className = 'pertanyaan-block';
    div.innerHTML = `
      <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
        <span class="pertanyaan-nomor text-xs font-semibold text-gray-500 w-14">No. 1</span>
        <input type="text" placeholder="Tulis pertanyaan..." class="pertanyaan-teks flex-1 min-w-[140px] px-2 py-1 border rounded text-sm mr-2">
        <select class="pertanyaan-tipe px-2 py-1 border rounded text-sm" onchange="toggleOpsiJawaban(this)">
          <option value="esai">Esai</option>
          <option value="isian_singkat">Isian Singkat</option>
          <option value="pilihan_ganda">Pilihan Ganda</option>
          <option value="checkbox">Checkbox</option>
        </select>
        <button type="button" onclick="hapusPertanyaanLkpd(this)" class="text-red-500 hover:text-red-700 ml-2"><i class="fas fa-times"></i></button>
      </div>
      <div class="opsi-jawaban-wrap hidden">
        <div class="daftar-opsi"></div>
        <button type="button" onclick="tambahOpsiJawaban(this)" class="text-xs text-blue-600 hover:text-blue-800 mt-1"><i class="fas fa-plus mr-1"></i>Tambah Opsi</button>
      </div>
    `;
    return div;
  }

  function tambahPertanyaanLkpd(btn, containerId) {
    const daftarP = containerId ? document.getElementById(containerId) : btn.closest('.lkpd-builder').querySelector('.daftar-pertanyaan');
    daftarP.appendChild(buatElemenPertanyaan());
    renumberPertanyaanLkpd(daftarP);
  }

  function tambahPertanyaanDariData(daftarP, p) {
    const div = buatElemenPertanyaan();
    daftarP.appendChild(div);
    div.querySelector('.pertanyaan-teks').value = p.teks || '';
    div.querySelector('.pertanyaan-tipe').value = p.tipe || 'esai';
    if (p.tipe === 'pilihan_ganda' || p.tipe === 'checkbox') {
      const wrap = div.querySelector('.opsi-jawaban-wrap');
      wrap.classList.remove('hidden');
      const daftarOpsi = wrap.querySelector('.daftar-opsi');
      (p.opsi || []).forEach(o => {
        const odiv = document.createElement('div');
        odiv.className = 'flex items-center gap-2 mb-1';
        odiv.innerHTML = `<input type="text" placeholder="Opsi jawaban" class="opsi-teks flex-1 px-2 py-1 border rounded text-sm" value="${escapeHtml(o)}"><button type="button" onclick="this.closest('div').remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>`;
        daftarOpsi.appendChild(odiv);
      });
    }
    renumberPertanyaanLkpd(daftarP);
  }

  function hapusPertanyaanLkpd(btn) {
    const daftarP = btn.closest('.daftar-pertanyaan');
    btn.closest('.pertanyaan-block').remove();
    renumberPertanyaanLkpd(daftarP);
  }

  function renumberPertanyaanLkpd(daftarP) {
    Array.from(daftarP.children).forEach((el, i) => {
      const nomor = el.querySelector('.pertanyaan-nomor');
      if (nomor) nomor.textContent = 'No. ' + (i + 1);
    });
  }

  function toggleOpsiJawaban(select) {
    const wrap = select.closest('.pertanyaan-block').querySelector('.opsi-jawaban-wrap');
    const perluOpsi = select.value === 'pilihan_ganda' || select.value === 'checkbox';
    wrap.classList.toggle('hidden', !perluOpsi);
    if (perluOpsi && wrap.querySelector('.daftar-opsi').children.length === 0) {
      tambahOpsiJawaban(wrap.querySelector('button'));
      tambahOpsiJawaban(wrap.querySelector('button'));
    }
  }

  function tambahOpsiJawaban(btn) {
    const daftarOpsi = btn.closest('.opsi-jawaban-wrap').querySelector('.daftar-opsi');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 mb-1';
    div.innerHTML = `
      <input type="text" placeholder="Opsi jawaban" class="opsi-teks flex-1 px-2 py-1 border rounded text-sm">
      <button type="button" onclick="this.closest('div').remove()" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
    `;
    daftarOpsi.appendChild(div);
  }

  function simpanMateri(e) {
    e.preventDefault();
    const judul = document.getElementById('fJudulMateri').value.trim();
    if (judul === '') { alert('Judul materi wajib diisi.'); return false; }

    const sasaranTerpilih = Array.from(document.querySelectorAll('.sasaran-checkbox:checked')).map(cb => JSON.parse(cb.value));
    if (sasaranTerpilih.length === 0) { alert('Pilih minimal satu sasaran kelas/jurusan.'); return false; }

    const slideBlocks = document.querySelectorAll('#daftarSlideBlock .slide-block');
    if (slideBlocks.length === 0) { alert('Materi harus memiliki minimal satu bagian.'); return false; }

    const idMateriEdit = document.getElementById('fIdMateri').value;
    const modeEdit = !!idMateriEdit;

    const fd = new FormData();
    fd.append('action', modeEdit ? 'update_materi' : 'simpan_materi');
    if (modeEdit) fd.append('id_materi', idMateriEdit);
    fd.append('judul', judul);
    fd.append('deskripsi', document.getElementById('fDeskripsiMateri').value.trim());
    fd.append('fungsi_layanan', document.getElementById('fFungsiLayanan').value);
    fd.append('nama_guru_pembuat', document.getElementById('fGuruPembuat').value);
    fd.append('urutan', document.getElementById('fUrutanMateri').value || 1);
    fd.append('sasaran', JSON.stringify(sasaranTerpilih));

    let semuaValid = true;
    slideBlocks.forEach((block, i) => {
      fd.append('slide_judul[]', block.querySelector('.slide-input-judul').value.trim());
      fd.append('slide_teks[]', block.querySelector('.slide-input-teks').value.trim());
      fd.append('slide_youtube[]', block.querySelector('.slide-input-youtube').value.trim());
      const gambarFile = block.querySelector('.slide-input-gambar').files[0];
      if (gambarFile) {
        if (gambarFile.size > 3 * 1024 * 1024) {
          alert('Gambar di bagian ' + (i + 1) + ' melebihi 3 MB. Kompres dulu.');
          semuaValid = false;
          return;
        }
        if (gambarFile.size > 10 * 1024 * 1024) {
          alert('File besar di bagian ' + (i + 1) + '. Pastikan hosting mengizinkan upload_max_filesize ≥ 20M.');
        }
        fd.append('slide_gambar[' + i + ']', gambarFile);
      }
      const pptFile = block.querySelector('.slide-input-ppt').files[0];
      if (pptFile) {
        if (pptFile.size > 20 * 1024 * 1024) {
          alert('File PPT di bagian ' + (i + 1) + ' melebihi 20 MB. Kompres dulu atau unggah lewat link.');
          semuaValid = false;
          return;
        }
        if (pptFile.size > 10 * 1024 * 1024) {
          if (!confirm('File PPT di bagian ' + (i + 1) + ' berukuran ' + Math.round(pptFile.size / 1048576 * 10) / 10 + ' MB (besar).\n\nPastikan hosting mengizinkan upload_max_filesize ≥ 20M dan post_max_size ≥ 32M.\n\nLanjutkan unggah?')) {
            semuaValid = false;
            return;
          }
        }
        fd.append('slide_ppt[' + i + ']', pptFile);
      }
      fd.append('slide_gambar_arsip[]', block.dataset.gambarArsip || '');
      fd.append('slide_ppt_arsip[]', block.dataset.pptArsip || '');
      fd.append('slide_gambar_existing[]', block.dataset.gambarExisting || '');
      fd.append('slide_ppt_existing[]', block.dataset.pptExisting || '');
      fd.append('slide_youtube_arsip[]', block.dataset.youtubeArsip || '');
      fd.append('slide_teks_arsip[]', block.dataset.teksArsip || '');
      fd.append('slide_lkpd_arsip[]', block.dataset.lkpdArsip || '');
      const butuhLkpd = block.querySelector('.slide-input-butuh-lkpd').checked;
      fd.append('slide_butuh_lkpd[]', butuhLkpd ? '1' : '');

      const daftarPertanyaan = [];
      if (butuhLkpd) {
        block.querySelectorAll('.pertanyaan-block').forEach(pb => {
          const teks = pb.querySelector('.pertanyaan-teks').value.trim();
          const tipe = pb.querySelector('.pertanyaan-tipe').value;
          if (teks === '') { semuaValid = false; return; }
          const opsi = Array.from(pb.querySelectorAll('.opsi-teks')).map(o => o.value.trim()).filter(o => o !== '');
          if ((tipe === 'pilihan_ganda' || tipe === 'checkbox') && opsi.length < 2) { semuaValid = false; return; }
          daftarPertanyaan.push({ teks, tipe, opsi });
        });
        if (daftarPertanyaan.length === 0) { semuaValid = false; }
      }
      fd.append('slide_pertanyaan_json[]', JSON.stringify(daftarPertanyaan));
    });

    if (!semuaValid) { alert('Ada pertanyaan LKPD yang belum lengkap (teks kosong atau opsi kurang dari 2).'); return false; }

    kirimSimpanMateri(fd);
    return false;
  }

  function kirimSimpanMateri(fd) {
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(async (res) => {
        const text = await res.text();
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Server mengembalikan respons non-JSON. Kemungkinan post_max_size terlewati atau error PHP. Cuplikan: ' + text.slice(0, 200));
        }
      })
      .then(data => {
        if (data.success) {
          if (data.upload_warning) {
            const baris = String(data.upload_warning).split(/\r?\n/).filter(Boolean)[0] || data.upload_warning;
            alert('Materi tersimpan, tetapi ada file yang GAGAL diunggah: ' + baris);
          }
          tutupModalTambahMateri();
          muatDaftarMateri();
        } else if (data.need_confirmation) {
          if (confirm(data.message)) {
            fd.set('konfirmasi_reset_progres', '1');
            kirimSimpanMateri(fd);
          }
        } else {
          let msg = data.message || 'Gagal menyimpan materi.';
          if (data.php_ini) {
            msg += '\n\nLimit PHP: upload_max_filesize=' + data.php_ini.upload_max_filesize
              + ', post_max_size=' + data.php_ini.post_max_size;
          }
          alert(msg);
        }
      })
      .catch((err) => alert('Terjadi kesalahan saat menyimpan materi.\n' + (err && err.message ? err.message : '')));

    return false;
  }

  let pickerSlideBlock = null;
  let pickerField = null;

  function bukaPickerSimpanan(tipe, slideBlock, field) {
    pickerSlideBlock = slideBlock;
    pickerField = field;
    document.getElementById('isiPickerSimpanan').innerHTML = '<p class="text-center text-gray-400 py-6">Memuat data...</p>';
    document.getElementById('modalPickerSimpanan').classList.remove('hidden');
    const fd = new FormData();
    fd.append('action', 'list_arsip');
    fd.append('guru', '');
    fd.append('tipe', tipe);
    fetch(window.location.pathname, { method: 'POST', body: fd })
      .then(res => res.json())
      .then(data => renderPickerSimpanan(data.success ? data.data : []));
  }

  function tutupPickerSimpanan() {
    document.getElementById('modalPickerSimpanan').classList.add('hidden');
    pickerSlideBlock = null;
    pickerField = null;
  }

  function renderPickerSimpanan(data) {
    const wrap = document.getElementById('isiPickerSimpanan');
    if (data.length === 0) {
      wrap.innerHTML = '<p class="text-center text-gray-400 py-6">Belum ada bahan jenis ini di Pustaka Bahan.</p>';
      return;
    }
    wrap.innerHTML = data.map((a, i) => `
      <div class="border rounded-lg p-3 mb-2 hover:bg-gray-50 cursor-pointer" onclick="pilihBahanDariPicker(${i})">
        <p class="font-semibold text-sm">${escapeHtml(a.judul)}</p>
        <p class="text-xs text-gray-500">${escapeHtml(a.kategori || '-')} &bull; oleh ${escapeHtml(a.nama_guru_upload || '-')}</p>
      </div>
    `).join('');
    wrap.dataset.items = JSON.stringify(data);
  }

  function pilihBahanDariPicker(index) {
    const data = JSON.parse(document.getElementById('isiPickerSimpanan').dataset.items || '[]');
    const item = data[index];
    if (!item || !pickerSlideBlock || !pickerField) return;
    terapkanPilihanArsip(pickerSlideBlock, pickerField, item);
    tutupPickerSimpanan();
  }

  function terapkanPilihanArsip(slideBlock, field, item) {
    slideBlock.dataset[field + 'Arsip'] = item.id_arsip;
    slideBlock.dataset[field + 'ArsipJudul'] = item.judul;
    if (field === 'gambar' || field === 'ppt') {
      const fileInput = slideBlock.querySelector('.slide-input-' + field);
      fileInput.value = '';
      fileInput.disabled = true;
    } else if (field === 'youtube') {
      slideBlock.querySelector('.slide-input-youtube').value = item.link || '';
    } else if (field === 'teks') {
      slideBlock.querySelector('.slide-input-teks').value = item.konten_teks || '';
    } else if (field === 'lkpd') {
      const builder = slideBlock.querySelector('.lkpd-builder');
      const daftarP = builder.querySelector('.daftar-pertanyaan');
      daftarP.innerHTML = '';
      const pertanyaan = JSON.parse(item.lkpd_json || '[]');
      pertanyaan.forEach(p => tambahPertanyaanDariData(daftarP, p));
      slideBlock.querySelector('.slide-input-butuh-lkpd').checked = true;
      builder.classList.remove('hidden');
    }
    renderChipBahan(slideBlock, field);
  }

  function onPilihFileBaru(inputEl, field) {
    const slideBlock = inputEl.closest('.slide-block');
    slideBlock.dataset[field + 'Arsip'] = '';
    slideBlock.dataset[field + 'ArsipJudul'] = '';
    delete slideBlock.dataset[field + 'Existing'];
    const file = inputEl.files && inputEl.files[0];
    const chipWrap = slideBlock.querySelector('.chip-bahan-' + field);
    if (!chipWrap) return;
    if (file) {
      const ikon = field === 'ppt' ? 'fa-file-powerpoint' : 'fa-image';
      const ukuranMb = file.size / 1048576;
      const ukuranLabel = ukuranMb >= 1
        ? (Math.round(ukuranMb * 10) / 10) + ' MB'
        : Math.round(file.size / 1024) + ' KB';
      let warn = '';
      if (field === 'ppt' && file.size > 10 * 1024 * 1024) {
        warn = ' — file besar, pastikan hosting upload_max_filesize ≥ 20M';
      } else if (field === 'gambar' && file.size > 2 * 1024 * 1024) {
        warn = ' — gambar cukup besar (disarankan ≤ 1–2 MB)';
      }
      if (field === 'ppt' && file.size > 20 * 1024 * 1024) {
        alert('File PPT melebihi 20 MB. Kompres dulu atau unggah lewat link Google Drive.');
        inputEl.value = '';
        chipWrap.innerHTML = '';
        return;
      }
      if (field === 'gambar' && file.size > 3 * 1024 * 1024) {
        alert('Gambar melebihi 3 MB. Kompres dulu.');
        inputEl.value = '';
        chipWrap.innerHTML = '';
        return;
      }
      chipWrap.innerHTML = `<span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 text-xs px-2 py-1 rounded-full mt-1">
        <i class="fas ${ikon}"></i> ${escapeHtml(file.name)} (${ukuranLabel})${warn} — siap diunggah
      </span>`;
    } else {
      chipWrap.innerHTML = '';
    }
  }

  function batalPilihanArsip(el, field) {
    const slideBlock = el.closest('.slide-block');
    slideBlock.dataset[field + 'Arsip'] = '';
    slideBlock.dataset[field + 'ArsipJudul'] = '';
    if (field === 'gambar' || field === 'ppt') {
      slideBlock.querySelector('.slide-input-' + field).disabled = false;
    }
    renderChipBahan(slideBlock, field);
  }

  function renderChipBahan(slideBlock, field) {
    const chipWrap = slideBlock.querySelector('.chip-bahan-' + field);
    if (!chipWrap) return;
    const id = slideBlock.dataset[field + 'Arsip'];
    const judul = slideBlock.dataset[field + 'ArsipJudul'];
    if (id) {
      chipWrap.innerHTML = `<span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full mt-1">
        <i class="fas fa-archive"></i> ${escapeHtml(judul)}
        <button type="button" onclick="batalPilihanArsip(this, '${field}')" class="text-blue-400 hover:text-blue-700 ml-1"><i class="fas fa-times"></i></button>
      </span>`;
    } else {
      chipWrap.innerHTML = '';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    skipSimpanUrl = true;

    const fg = document.getElementById('filterGuruArsip');
    const ft = document.getElementById('filterTipeArsip');
    if (params.get('filter_guru')) fg.value = params.get('filter_guru');
    if (params.get('filter_tipe')) ft.value = params.get('filter_tipe');

    const tabParam = params.get('tab');
    if (tabParam === 'simpanan') pindahTab('simpanan');

    muatDaftarArsip();

    muatDaftarMateri().then(() => {
      const fk = params.get('filter_kelas');
      if (fk) {
        const sel = document.getElementById('filterKelasMateri');
        if (sel) {
          if (![...sel.options].some(o => o.value === fk)) {
            const opt = document.createElement('option');
            opt.value = fk;
            opt.textContent = fk;
            sel.appendChild(opt);
          }
          sel.value = fk;
          renderTabelMateri();
        }
      }
      skipSimpanUrl = false;
      simpanFilterArsipKeUrl();
    }).catch(() => {
      skipSimpanUrl = false;
      simpanFilterArsipKeUrl();
    });
  });
</script>
</body>
</html>