<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$FOLDER_GURU_BK = '../guru/';
define('BK_BAGAN_DURASI_WAJIB', 300);

$q_siswa = mysqli_query($koneksi, "SELECT id_siswa, nama, kelas, jurusan FROM siswa WHERE id_siswa = $id_siswa LIMIT 1");
$siswa = $q_siswa ? mysqli_fetch_assoc($q_siswa) : null;
if (!$siswa) { header("Location: ../login.php"); exit; }
$nama_siswa = $siswa['nama'] ?? 'Siswa';
$kelas_siswa = trim($siswa['kelas'] ?? '');
$jurusan_siswa = trim($siswa['jurusan'] ?? '');


if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bk_progress_bagan (
    id_progress INT(11) NOT NULL AUTO_INCREMENT,
    id_siswa INT(11) NOT NULL,
    id_materi INT(11) NOT NULL,
    jenis_bagan ENUM('ppt','video','tugas') NOT NULL,
    waktu_mulai DATETIME DEFAULT NULL,
    waktu_selesai DATETIME DEFAULT NULL,
    status_selesai TINYINT(1) NOT NULL DEFAULT 0,
    durasi_detik INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_progress),
    UNIQUE KEY uniq_siswa_materi_bagan (id_siswa, id_materi, jenis_bagan),
    KEY id_materi (id_materi),
    KEY id_siswa (id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");



$bkCols = [];
$qCols = mysqli_query($koneksi, "SHOW COLUMNS FROM bk_progress_bagan");
if ($qCols) {
    while ($c = mysqli_fetch_assoc($qCols)) {
        $bkCols[strtolower($c['Field'])] = true;
    }
}
if (!isset($bkCols['jenis_bagan'])) {
    mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD COLUMN jenis_bagan ENUM('ppt','video','tugas') NOT NULL DEFAULT 'ppt' AFTER id_materi");
}
if (!isset($bkCols['waktu_mulai'])) {
    mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD COLUMN waktu_mulai DATETIME DEFAULT NULL");
}
if (!isset($bkCols['waktu_selesai'])) {
    mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD COLUMN waktu_selesai DATETIME DEFAULT NULL");
}
if (!isset($bkCols['status_selesai'])) {
    mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD COLUMN status_selesai TINYINT(1) NOT NULL DEFAULT 0");
}
if (!isset($bkCols['durasi_detik'])) {
    mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD COLUMN durasi_detik INT(11) NOT NULL DEFAULT 0");
}

if (isset($bkCols['id_bagan'])) {
    @mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan MODIFY COLUMN id_bagan INT(11) NOT NULL DEFAULT 0");
}

$bkCols = [];
$qCols2 = mysqli_query($koneksi, "SHOW COLUMNS FROM bk_progress_bagan");
if ($qCols2) {
    while ($c = mysqli_fetch_assoc($qCols2)) {
        $bkCols[strtolower($c['Field'])] = true;
    }
}


$qAllIdx = mysqli_query($koneksi, "SHOW INDEX FROM bk_progress_bagan");
$idxCols = []; 
if ($qAllIdx) {
    while ($ix = mysqli_fetch_assoc($qAllIdx)) {
        if ((int) ($ix['Non_unique'] ?? 1) === 0 && ($ix['Key_name'] ?? '') !== 'PRIMARY') {
            $kn = $ix['Key_name'];
            if (!isset($idxCols[$kn])) $idxCols[$kn] = [];
            $idxCols[$kn][(int) $ix['Seq_in_index']] = strtolower($ix['Column_name']);
        }
    }
}
foreach ($idxCols as $kn => $cols) {
    ksort($cols);
    $cols = array_values($cols);
    $isCorrect = ($cols === ['id_siswa', 'id_materi', 'jenis_bagan']);
    if (!$isCorrect) {
        
        @mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan DROP INDEX `" . mysqli_real_escape_string($koneksi, $kn) . "`");
    }
}

$qIdx = mysqli_query($koneksi, "SHOW INDEX FROM bk_progress_bagan WHERE Key_name = 'uniq_siswa_materi_bagan'");
if (!$qIdx || mysqli_num_rows($qIdx) === 0) {
    mysqli_query($koneksi, "DELETE t1 FROM bk_progress_bagan t1
        INNER JOIN bk_progress_bagan t2
        WHERE t1.id_progress > t2.id_progress
          AND t1.id_siswa = t2.id_siswa
          AND t1.id_materi = t2.id_materi
          AND t1.jenis_bagan = t2.jenis_bagan");
    @mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan ADD UNIQUE KEY uniq_siswa_materi_bagan (id_siswa, id_materi, jenis_bagan)");
}

@mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan MODIFY COLUMN status_selesai TINYINT(1) NOT NULL DEFAULT 0");
@mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan MODIFY COLUMN jenis_bagan ENUM('ppt','video','tugas') NOT NULL");

$JENIS_BAGAN = ['ppt', 'video', 'tugas'];
$LABEL_BAGAN = ['ppt' => 'Materi PPT', 'video' => 'Video', 'tugas' => 'Tugas / Kuis'];
$MAP_ID_BAGAN = ['ppt' => 1, 'video' => 2, 'tugas' => 3];




function simpan_progress_bagan_row($koneksi, $id_siswa, $id_materi, $jenis, $status_selesai, $durasi_detik = 0, $set_waktu_mulai = true) {
    global $MAP_ID_BAGAN;
    $id_siswa = (int) $id_siswa;
    $id_materi = (int) $id_materi;
    $jenisEsc = mysqli_real_escape_string($koneksi, $jenis);
    $status_selesai = (int) $status_selesai;
    $durasi_detik = (int) $durasi_detik;
    $idBagan = (int) ($MAP_ID_BAGAN[$jenis] ?? 0);

    
    static $punyaIdBagan = null;
    if ($punyaIdBagan === null) {
        $punyaIdBagan = false;
        $qc = mysqli_query($koneksi, "SHOW COLUMNS FROM bk_progress_bagan LIKE 'id_bagan'");
        if ($qc && mysqli_num_rows($qc) > 0) {
            $punyaIdBagan = true;
            @mysqli_query($koneksi, "ALTER TABLE bk_progress_bagan MODIFY COLUMN id_bagan INT(11) NOT NULL DEFAULT 0");
        }
    }

    
    $q = mysqli_query($koneksi, "SELECT id_progress FROM bk_progress_bagan
        WHERE id_siswa = $id_siswa AND id_materi = $id_materi AND jenis_bagan = '$jenisEsc' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;

    if ($row) {
        $sql = "UPDATE bk_progress_bagan SET status_selesai = $status_selesai, durasi_detik = GREATEST(COALESCE(durasi_detik,0), $durasi_detik)";
        if ($status_selesai === 1) {
            $sql .= ", waktu_selesai = NOW()";
        }
        if ($set_waktu_mulai) {
            $sql .= ", waktu_mulai = IFNULL(waktu_mulai, NOW())";
        }
        if ($punyaIdBagan) {
            $sql .= ", id_bagan = $idBagan";
        }
        $sql .= " WHERE id_progress = " . (int) $row['id_progress'];
        $ok = mysqli_query($koneksi, $sql);
        return $ok ? true : mysqli_error($koneksi);
    }

    
    $cols = "id_siswa, id_materi, jenis_bagan, waktu_mulai, status_selesai, durasi_detik";
    $vals = "$id_siswa, $id_materi, '$jenisEsc', NOW(), $status_selesai, $durasi_detik";
    if ($punyaIdBagan) {
        $cols = "id_bagan, " . $cols;
        $vals = "$idBagan, " . $vals;
    }
    if ($status_selesai === 1) {
        $ok = mysqli_query($koneksi, "INSERT INTO bk_progress_bagan ($cols, waktu_selesai) VALUES ($vals, NOW())");
        if ($ok) return true;
    }
    $ok = mysqli_query($koneksi, "INSERT INTO bk_progress_bagan ($cols) VALUES ($vals)");
    if ($ok) return true;
    return mysqli_error($koneksi);
}


function ambil_daftar_materi_siswa($koneksi, $id_siswa, $kelas, $jurusan) {
    $kelasEsc = mysqli_real_escape_string($koneksi, $kelas);
    $jurusanEsc = mysqli_real_escape_string($koneksi, $jurusan);
    $sql = "SELECT DISTINCT bm.* FROM bk_materi bm
            INNER JOIN bk_materi_sasaran bms ON bms.id_materi = bm.id_materi
            WHERE bm.status_aktif = 1 AND bms.kelas = '$kelasEsc' AND bms.jurusan = '$jurusanEsc'
            ORDER BY bm.urutan ASC, bm.id_materi ASC";
    $data = [];
    $q = mysqli_query($koneksi, $sql);
    if ($q) while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
    return $data;
}

function hitung_progress_materi($koneksi, $id_siswa, $id_materi) {
    $idm = (int) $id_materi;
    $jmlSelesai = 0;
    
    $q = mysqli_query($koneksi, "SELECT COUNT(DISTINCT jenis_bagan) AS jml FROM bk_progress_bagan
        WHERE id_siswa = $id_siswa AND id_materi = $idm AND status_selesai = 1
          AND jenis_bagan IN ('ppt','video','tugas')");
    if ($q) $jmlSelesai = min(3, (int) mysqli_fetch_assoc($q)['jml']);
    return ['jumlah_slide' => 3, 'jumlah_selesai' => $jmlSelesai, 'selesai' => ($jmlSelesai >= 3)];
}

function bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas, $jurusan) {
    $daftarMateri = ambil_daftar_materi_siswa($koneksi, $id_siswa, $kelas, $jurusan);
    $hasil = []; $materiSebelumnyaSelesai = true; $judulSebelumnya = null;
    foreach ($daftarMateri as $m) {
        $progress = hitung_progress_materi($koneksi, $id_siswa, $m['id_materi']);
        $terkunci = !$materiSebelumnyaSelesai;
        if ($terkunci) $status = 'terkunci';
        elseif ($progress['selesai']) $status = 'selesai';
        elseif ($progress['jumlah_selesai'] > 0) $status = 'berlangsung';
        else $status = 'tersedia';
        $m['jumlah_slide'] = $progress['jumlah_slide'];
        $m['jumlah_selesai'] = $progress['jumlah_selesai'];
        $m['status'] = $status;
        $m['judul_materi_sebelum'] = $terkunci ? $judulSebelumnya : null;
        $hasil[] = $m;
        $materiSebelumnyaSelesai = $progress['selesai'];
        $judulSebelumnya = $m['judul'];
    }
    return $hasil;
}

function materi_boleh_diakses($koneksi, $id_siswa, $kelas, $jurusan, $id_materi) {
    foreach (bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas, $jurusan) as $m) {
        if ((int) $m['id_materi'] === (int) $id_materi) return $m['status'] !== 'terkunci' ? $m : false;
    }
    return false;
}





function hitung_elapsed_bagan(array $row): int {
    if ((int) ($row['status_selesai'] ?? 0) === 1) {
        return max((int) ($row['durasi_detik'] ?? 0), BK_BAGAN_DURASI_WAJIB);
    }
    if (empty($row['waktu_mulai'])) {
        return 0;
    }
    $mulaiTs = strtotime($row['waktu_mulai']);
    if (!$mulaiTs) {
        return 0;
    }
    return max(0, time() - $mulaiTs);
}

function ambil_progress_bagan($koneksi, $id_siswa, $id_materi) {
    global $JENIS_BAGAN;
    $idm = (int) $id_materi;
    $map = [];
    foreach ($JENIS_BAGAN as $j) {
        $map[$j] = [
            'jenis_bagan' => $j,
            'status_selesai' => 0,
            'waktu_mulai' => null,
            'waktu_selesai' => null,
            'durasi_detik' => 0,
            'elapsed_detik' => 0,
        ];
    }
    $q = mysqli_query($koneksi, "SELECT * FROM bk_progress_bagan WHERE id_siswa = $id_siswa AND id_materi = $idm");
    if ($q) while ($r = mysqli_fetch_assoc($q)) {
        $jenis = trim((string) ($r['jenis_bagan'] ?? ''));
        
        if ($jenis === '' || !isset($map[$jenis])) {
            
            if ((int) ($r['status_selesai'] ?? 0) === 1) {
                foreach ($JENIS_BAGAN as $j) {
                    if ((int) $map[$j]['status_selesai'] !== 1) {
                        $jenis = $j;
                        
                        $idProg = (int) $r['id_progress'];
                        $jEsc = mysqli_real_escape_string($koneksi, $j);
                        mysqli_query($koneksi, "UPDATE bk_progress_bagan SET jenis_bagan = '$jEsc' WHERE id_progress = $idProg");
                        break;
                    }
                }
            } else {
                continue;
            }
        }
        if (!isset($map[$jenis])) continue;
        
        if ((int) $map[$jenis]['status_selesai'] === 1 && (int) ($r['status_selesai'] ?? 0) !== 1) {
            continue;
        }
        $map[$jenis] = [
            'jenis_bagan' => $jenis,
            'status_selesai' => (int) $r['status_selesai'],
            'waktu_mulai' => $r['waktu_mulai'],
            'waktu_selesai' => $r['waktu_selesai'],
            'durasi_detik' => (int) $r['durasi_detik'],
            'elapsed_detik' => hitung_elapsed_bagan($r),
        ];
    }
    return $map;
}


function kumpulkan_konten_bagan($koneksi, $id_siswa, $id_materi) {
    $idm = (int) $id_materi;
    $pptList = []; $videoList = []; $pertanyaan = []; $teksPendukung = [];
    $qsl = mysqli_query($koneksi, "SELECT * FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1 ORDER BY urutan ASC, id_slide ASC");
    if ($qsl) while ($sl = mysqli_fetch_assoc($qsl)) {
        if (!empty($sl['konten_teks'])) $teksPendukung[] = ['judul' => $sl['judul_slide'] ?: null, 'teks' => $sl['konten_teks']];
        if (!empty($sl['gambar'])) $pptList[] = ['tipe' => 'gambar', 'path' => $sl['gambar'], 'judul' => $sl['judul_slide'] ?: null];
        if (!empty($sl['file_ppt'])) $pptList[] = ['tipe' => 'ppt', 'path' => $sl['file_ppt'], 'judul' => $sl['judul_slide'] ?: null];
        if (!empty($sl['link_youtube'])) $videoList[] = ['url' => $sl['link_youtube'], 'judul' => $sl['judul_slide'] ?: null];
        if ((int) $sl['butuh_lkpd'] === 1) {
            $ids = (int) $sl['id_slide'];
            $qp = mysqli_query($koneksi, "SELECT * FROM bk_lkpd_pertanyaan WHERE id_slide = $ids ORDER BY urutan ASC, id_pertanyaan ASC");
            if ($qp) while ($p = mysqli_fetch_assoc($qp)) {
                $p['opsi_jawaban'] = $p['opsi_jawaban'] ? json_decode($p['opsi_jawaban'], true) : [];
                $idp = (int) $p['id_pertanyaan'];
                $qj = mysqli_query($koneksi, "SELECT jawaban FROM bk_jawaban_lkpd WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp LIMIT 1");
                $rj = $qj ? mysqli_fetch_assoc($qj) : null;
                $p['jawaban_tersimpan'] = $rj ? $rj['jawaban'] : '';
                $p['rating_guru'] = 0;
                $p['catatan_guru'] = '';
                $p['nama_guru_tanggapan'] = '';
                $qt = mysqli_query($koneksi, "SELECT t.rating, t.catatan, t.nama_guru, g.nama AS nama_guru_tbl
                    FROM bk_tanggapan_lkpd t
                    LEFT JOIN guru g ON g.id_guru = t.id_guru
                    WHERE t.id_siswa = $id_siswa AND t.id_pertanyaan = $idp LIMIT 1");
                if ($qt && ($rt = mysqli_fetch_assoc($qt))) {
                    $p['rating_guru'] = (int) $rt['rating'];
                    $p['catatan_guru'] = $rt['catatan'] ?? '';
                    $nm = trim((string) ($rt['nama_guru'] ?? ''));
                    if ($nm === '') $nm = trim((string) ($rt['nama_guru_tbl'] ?? ''));
                    $p['nama_guru_tanggapan'] = $nm;
                }
                $pertanyaan[] = $p;
            }
        }
    }
    return ['ppt' => $pptList, 'teks' => $teksPendukung, 'video' => $videoList, 'tugas' => $pertanyaan];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'list_materi_siswa') {
        $daftar = bangun_daftar_materi_dengan_status($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa);
        $data = array_map(function ($m, $i) {
            return [
                'id_materi' => (int) $m['id_materi'], 'nomor' => $i + 1, 'judul' => $m['judul'],
                'deskripsi' => $m['deskripsi'], 'nama_guru_pembuat' => $m['nama_guru_pembuat'],
                'jumlah_slide' => (int) $m['jumlah_slide'], 'jumlah_selesai' => (int) $m['jumlah_selesai'],
                'status' => $m['status'], 'judul_materi_sebelum' => $m['judul_materi_sebelum'],
            ];
        }, $daftar, array_keys($daftar));
        echo json_encode(['success' => true, 'data' => $data, 'nama_siswa' => $nama_siswa, 'kelas_jurusan' => trim($kelas_siswa . ' ' . $jurusan_siswa)]);
        exit;
    }

    if ($action === 'lihat_materi_siswa') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $akses = materi_boleh_diakses($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa, $idm);
        if (!$akses) { echo json_encode(['success' => false, 'message' => 'Materi ini belum dapat diakses. Selesaikan materi sebelumnya terlebih dahulu.']); exit; }
        $qm = mysqli_query($koneksi, "SELECT * FROM bk_materi WHERE id_materi = $idm LIMIT 1");
        $materi = $qm ? mysqli_fetch_assoc($qm) : null;
        if (!$materi) { echo json_encode(['success' => false, 'message' => 'Materi tidak ditemukan.']); exit; }
        $konten = kumpulkan_konten_bagan($koneksi, $id_siswa, $idm);
        $progressBagan = ambil_progress_bagan($koneksi, $id_siswa, $idm);
        $baganStatus = [];
        $sebelumSelesai = true;
        foreach (['ppt', 'video', 'tugas'] as $j) {
            $p = $progressBagan[$j];
            $baganStatus[$j] = [
                'jenis'          => $j,
                'label'          => $LABEL_BAGAN[$j],
                'status_selesai' => (int) $p['status_selesai'],
                'terkunci'       => $sebelumSelesai ? 0 : 1,
                'waktu_mulai'    => $p['waktu_mulai'],
                'durasi_detik'   => (int) $p['durasi_detik'],
                
                'elapsed_detik'  => (int) $p['elapsed_detik'],
            ];
            $sebelumSelesai = ((int) $p['status_selesai'] === 1);
        }
        echo json_encode([
            'success' => true, 'folder_guru' => $FOLDER_GURU_BK, 'durasi_wajib' => BK_BAGAN_DURASI_WAJIB,
            'data' => [
                'id_materi' => (int) $materi['id_materi'], 'judul' => $materi['judul'], 'deskripsi' => $materi['deskripsi'],
                'nama_guru_pembuat' => $materi['nama_guru_pembuat'], 'status_materi' => $akses['status'],
                'konten' => $konten, 'bagan' => $baganStatus,
            ],
        ]);
        exit;
    }

    if ($action === 'mulai_bagan') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $jenis = trim($_POST['jenis_bagan'] ?? '');
        if (!in_array($jenis, $JENIS_BAGAN, true)) { echo json_encode(['success' => false, 'message' => 'Jenis bagan tidak valid.']); exit; }
        $akses = materi_boleh_diakses($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa, $idm);
        if (!$akses) { echo json_encode(['success' => false, 'message' => 'Materi belum dapat diakses.']); exit; }
        
        $progress = ambil_progress_bagan($koneksi, $id_siswa, $idm);
        if ((int) $progress[$jenis]['status_selesai'] === 1) {
            echo json_encode([
                'success' => true,
                'sudah_selesai' => true,
                'elapsed_detik' => (int) $progress[$jenis]['elapsed_detik'],
                'waktu_mulai' => $progress[$jenis]['waktu_mulai'],
                'durasi_wajib' => BK_BAGAN_DURASI_WAJIB,
                'server_unix' => time(),
            ]);
            exit;
        }
        $jenisEsc = mysqli_real_escape_string($koneksi, $jenis);
        $qCek = mysqli_query($koneksi, "SELECT id_progress, waktu_mulai, status_selesai, durasi_detik FROM bk_progress_bagan WHERE id_siswa = $id_siswa AND id_materi = $idm AND jenis_bagan = '$jenisEsc' LIMIT 1");
        $row = $qCek ? mysqli_fetch_assoc($qCek) : null;
        $waktuMulai = null;

        if (!$row) {
            
            $__sr = simpan_progress_bagan_row($koneksi, $id_siswa, $idm, $jenis, 0, 0, true);
            $waktuMulai = date('Y-m-d H:i:s');
            $elapsed = 0;
        } else {
            if (empty($row['waktu_mulai'])) {
                
                mysqli_query($koneksi, "UPDATE bk_progress_bagan SET waktu_mulai = NOW() WHERE id_progress = " . (int) $row['id_progress']);
                $waktuMulai = date('Y-m-d H:i:s');
                $elapsed = 0;
            } else {
                
                $waktuMulai = $row['waktu_mulai'];
                $elapsed = hitung_elapsed_bagan($row);
            }
        }
        echo json_encode([
            'success' => true,
            'sudah_selesai' => false,
            'elapsed_detik' => (int) $elapsed,
            'waktu_mulai' => $waktuMulai,
            'durasi_wajib' => BK_BAGAN_DURASI_WAJIB,
            'server_unix' => time(),
        ]);
        exit;
    }

    
    if ($action === 'update_durasi_bagan') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $jenis = trim($_POST['jenis_bagan'] ?? '');
        $durasiClient = (int) ($_POST['durasi_detik'] ?? 0);
        if (!in_array($jenis, $JENIS_BAGAN, true)) { echo json_encode(['success' => false, 'message' => 'Jenis bagan tidak valid.']); exit; }
        $jenisEsc = mysqli_real_escape_string($koneksi, $jenis);
        $qRow = mysqli_query($koneksi, "SELECT id_progress, waktu_mulai, status_selesai FROM bk_progress_bagan WHERE id_siswa = $id_siswa AND id_materi = $idm AND jenis_bagan = '$jenisEsc' LIMIT 1");
        $row = $qRow ? mysqli_fetch_assoc($qRow) : null;
        if (!$row || (int) $row['status_selesai'] === 1) {
            echo json_encode(['success' => true, 'skipped' => true]);
            exit;
        }
        
        $elapsedServer = 0;
        if (!empty($row['waktu_mulai'])) {
            $ts = strtotime($row['waktu_mulai']);
            if ($ts) $elapsedServer = max(0, time() - $ts);
        }
        $simpan = min(max($durasiClient, $elapsedServer), 86400);
        mysqli_query($koneksi, "UPDATE bk_progress_bagan SET durasi_detik = $simpan WHERE id_progress = " . (int) $row['id_progress']);
        echo json_encode(['success' => true, 'elapsed_detik' => $elapsedServer, 'durasi_detik' => $simpan]);
        exit;
    }

    if ($action === 'selesai_bagan') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        $jenis = trim($_POST['jenis_bagan'] ?? '');
        $durasiClient = (int) ($_POST['durasi_detik'] ?? 0);
        if (!in_array($jenis, $JENIS_BAGAN, true)) { echo json_encode(['success' => false, 'message' => 'Jenis bagan tidak valid.']); exit; }
        $akses = materi_boleh_diakses($koneksi, $id_siswa, $kelas_siswa, $jurusan_siswa, $idm);
        if (!$akses) { echo json_encode(['success' => false, 'message' => 'Materi belum dapat diakses.']); exit; }
        
        
        $progress = ambil_progress_bagan($koneksi, $id_siswa, $idm);
        if ((int) $progress[$jenis]['status_selesai'] === 1) {
            echo json_encode(['success' => true, 'message' => 'Bagan sudah selesai sebelumnya.', 'sudah_selesai' => true]);
            exit;
        }
        
        $durasiMinimal = BK_BAGAN_DURASI_WAJIB - 15;
        $jenisEsc = mysqli_real_escape_string($koneksi, $jenis);
        $qRow = mysqli_query($koneksi, "SELECT * FROM bk_progress_bagan WHERE id_siswa = $id_siswa AND id_materi = $idm AND jenis_bagan = '$jenisEsc' LIMIT 1");
        $row = $qRow ? mysqli_fetch_assoc($qRow) : null;
        $durasiServer = 0;
        if ($row && !empty($row['waktu_mulai'])) {
            $mulaiTs = strtotime($row['waktu_mulai']);
            if ($mulaiTs) $durasiServer = max(0, time() - $mulaiTs);
        }
        
        
        if ((!$row || empty($row['waktu_mulai'])) && $durasiClient >= $durasiMinimal) {
            $backSec = min(max($durasiClient, BK_BAGAN_DURASI_WAJIB), 86400);
            $backdate = date('Y-m-d H:i:s', time() - $backSec);
            if ($row) {
                mysqli_query($koneksi, "UPDATE bk_progress_bagan SET waktu_mulai = '$backdate' WHERE id_progress = " . (int) $row['id_progress']);
            } else {
                mysqli_query($koneksi, "INSERT INTO bk_progress_bagan (id_siswa, id_materi, jenis_bagan, waktu_mulai, status_selesai, durasi_detik) VALUES ($id_siswa, $idm, '$jenisEsc', '$backdate', 0, 0)");
                $qRow = mysqli_query($koneksi, "SELECT * FROM bk_progress_bagan WHERE id_siswa = $id_siswa AND id_materi = $idm AND jenis_bagan = '$jenisEsc' LIMIT 1");
                $row = $qRow ? mysqli_fetch_assoc($qRow) : null;
            }
            $durasiServer = $backSec;
        }
        $durasiAkhir = max($durasiClient, $durasiServer);
        
        if ($jenis !== 'tugas' && $durasiAkhir < $durasiMinimal) {
            echo json_encode([
                'success' => false,
                'message' => 'Bagan belum bisa diselesaikan. Sisa waktu minimal sekitar ' . max(1, BK_BAGAN_DURASI_WAJIB - $durasiAkhir) . ' detik lagi.',
                'durasi_tercatat' => $durasiAkhir,
                'durasi_wajib' => BK_BAGAN_DURASI_WAJIB,
            ]);
            exit;
        }
        if ($jenis === 'tugas') {
            $konten = kumpulkan_konten_bagan($koneksi, $id_siswa, $idm);
            $jawabanPost = json_decode($_POST['jawaban'] ?? '{}', true);
            if (!is_array($jawabanPost)) $jawabanPost = [];
            foreach ($konten['tugas'] as $p) {
                $idp = (int) $p['id_pertanyaan'];
                $j = $jawabanPost[$idp] ?? ($jawabanPost[(string) $idp] ?? '');
                if (is_array($j)) $j = implode(', ', array_map('trim', $j));
                if (trim((string) $j) === '') {
                    echo json_encode(['success' => false, 'message' => 'Lengkapi semua pertanyaan tugas/kuis sebelum menyelesaikan bagan ini.']);
                    exit;
                }
            }
            foreach ($konten['tugas'] as $p) {
                $idp = (int) $p['id_pertanyaan'];
                $j = $jawabanPost[$idp] ?? ($jawabanPost[(string) $idp] ?? '');
                if (is_array($j)) $j = implode(', ', array_map('trim', $j));
                $jEsc = mysqli_real_escape_string($koneksi, trim((string) $j));
                
                $qCekJ = mysqli_query($koneksi, "SELECT id_jawaban FROM bk_jawaban_lkpd WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp LIMIT 1");
                if ($qCekJ && mysqli_fetch_assoc($qCekJ)) {
                    mysqli_query($koneksi, "UPDATE bk_jawaban_lkpd SET jawaban = '$jEsc' WHERE id_siswa = $id_siswa AND id_pertanyaan = $idp");
                } else {
                    mysqli_query($koneksi, "INSERT INTO bk_jawaban_lkpd (id_siswa, id_pertanyaan, jawaban) VALUES ($id_siswa, $idp, '$jEsc')");
                }
            }
        }


        $durasiSimpan = min($durasiAkhir, 86400);
        $resSave = simpan_progress_bagan_row($koneksi, $id_siswa, $idm, $jenis, 1, $durasiSimpan, true);
        if ($resSave !== true) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan status selesai: ' . (is_string($resSave) ? $resSave : mysqli_error($koneksi)),
            ]);
            exit;
        }

        
        $progressBaru = ambil_progress_bagan($koneksi, $id_siswa, $idm);
        
        $progressBaru[$jenis]['status_selesai'] = 1;
        $semua = true;
        foreach ($JENIS_BAGAN as $j) {
            if ((int) $progressBaru[$j]['status_selesai'] !== 1) { $semua = false; break; }
        }
        echo json_encode([
            'success' => true,
            'semua_bagan_selesai' => $semua,
            'durasi_detik' => $durasiSimpan,
            'jenis_bagan' => $jenis,
            'status_selesai' => 1,
        ]);
        exit;
    }

    if ($action === 'selesai_materi') {
        $idm = (int) ($_POST['id_materi'] ?? 0);
        if ($idm <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID materi tidak valid.']);
            exit;
        }

        
        $errors = [];
        foreach ($JENIS_BAGAN as $j) {
            $res = simpan_progress_bagan_row($koneksi, $id_siswa, $idm, $j, 1, BK_BAGAN_DURASI_WAJIB, true);
            if ($res !== true) {
                $errors[] = $j . ': ' . $res;
            }
        }

        
        $qsl = @mysqli_query($koneksi, "SELECT id_slide FROM bk_slide WHERE id_materi = $idm AND status_aktif = 1");
        if ($qsl) while ($sl = mysqli_fetch_assoc($qsl)) {
            $ids = (int) $sl['id_slide'];
            @mysqli_query($koneksi, "INSERT INTO bk_progress_slide (id_siswa, id_materi, id_slide, status_selesai, waktu_selesai)
                VALUES ($id_siswa, $idm, $ids, 1, NOW())
                ON DUPLICATE KEY UPDATE status_selesai = 1, waktu_selesai = NOW()");
        }

        $pm = hitung_progress_materi($koneksi, $id_siswa, $idm);
        if ((int) $pm['jumlah_selesai'] < 3 && $errors) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . implode(' | ', $errors),
                'jumlah_selesai' => (int) $pm['jumlah_selesai'],
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'materi_selesai' => true,
            'jumlah_selesai' => max(3, (int) $pm['jumlah_selesai']),
            'jumlah_slide' => 3,
        ]);
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
    <title>Bimbingan Klasikal | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2A6163;
            --primary-color-dark: #1F4A4B;
            --secondary-color: #2F9160;
            --navbar-bg: #163B3C;
            --surface-muted: #F4F7F6;
        }
        body { background-color: var(--surface-muted); }
        .primary-color { color: var(--primary-color); }
        .primary-bg { background-color: var(--primary-color); }
        .primary-border { border-color: var(--primary-color); }
        .navbar-bg { background-color: var(--navbar-bg); }

        .materi-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid #E5E7EB;
            border-radius: 1rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(22, 59, 60, 0.06);
            background: #fff;
        }
        .materi-card.tersedia:hover, .materi-card.berlangsung:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(22, 59, 60, 0.12);
            border-color: var(--primary-color);
        }
        .materi-card.selesai {
            background-color: #F0FBF5;
            border: 2px solid var(--secondary-color);
        }
        .materi-card.terkunci {
            filter: grayscale(60%);
            opacity: 0.68;
            cursor: not-allowed;
            background-color: #F9FAFB;
        }
        .badge { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 9999px; }
        .badge-selesai { background: var(--secondary-color); color: #fff; }
        .badge-berlangsung { background: #FDE68A; color: #92400E; }
        .badge-tersedia { background: rgba(42,97,99,0.1); color: var(--primary-color); }
        .badge-terkunci { background: #E5E7EB; color: #6B7280; }

        .progress-track { background: #E5E7EB; border-radius: 9999px; height: 6px; overflow: hidden; }
        #isiBaganAktif, #viewerDeskripsiMateri { overflow-wrap: anywhere; word-break: break-word; }
        #isiBaganAktif img { max-width: 100%; height: auto; }
        .progress-fill { background: var(--secondary-color); height: 100%; border-radius: 9999px; transition: width .4s ease; }

        .step-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; cursor: pointer; transition: all .2s ease; }
        .step-item:hover { background: #F4F7F6; }
        .step-item.active { background: rgba(42,97,99,0.1); border: 1px solid var(--primary-color); }
        .step-item.locked { opacity: .5; cursor: not-allowed; }
        .step-dot { width: 26px; height: 26px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; flex-shrink: 0; }
        .step-dot.done { background: var(--secondary-color); color: #fff; }
        .step-dot.current { background: var(--primary-color); color: #fff; }
        .step-dot.locked { background: #E5E7EB; color: #9CA3AF; }

        .yt-wrap { position: relative; width: 100%; padding-top: 56.25%; border-radius: .75rem; overflow: hidden; background: #000; }
        .yt-wrap iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .doc-embed-wrap { width: 100%; height: min(75vh, 560px); border-radius: .75rem; overflow: hidden; background: #f3f4f6; border: 1px solid #e5e7eb; }
        .doc-embed-wrap iframe { width: 100%; height: 100%; border: 0; }

        #viewerMateri { display: none; }
        #viewerMateri.aktif { display: block; }
        #listMateriSection.tersembunyi { display: none; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 10px; }
    
        .bagan-item { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border-radius:12px; cursor:pointer; transition:all .2s ease; border:1px solid transparent; }
        .bagan-item:hover:not(.locked) { background:#F4F7F6; }
        .bagan-item.active { background:rgba(42,97,99,0.1); border-color:var(--primary-color); }
        .bagan-item.locked { opacity:.55; cursor:not-allowed; }
        .bagan-item.done { background:#F0FBF5; border-color:rgba(47,145,96,0.35); }
        .bagan-dot { width:32px; height:32px; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; }
        .bagan-dot.done { background:var(--secondary-color); color:#fff; }
        .bagan-dot.current { background:var(--primary-color); color:#fff; }
        .bagan-dot.locked { background:#E5E7EB; color:#9CA3AF; }
        .bagan-dot.ready { background:rgba(42,97,99,0.15); color:var(--primary-color); }
        .timer-bar-track { background:#E5E7EB; border-radius:9999px; height:8px; overflow:hidden; }
        .timer-bar-fill { background:var(--primary-color); height:100%; border-radius:9999px; transition:width .5s linear; }
        .timer-bar-fill.done { background:var(--secondary-color); }

    </style>
</head>
<body class="font-sans bg-gray-50 text-gray-800 flex flex-col min-h-screen">

    <header class="navbar-bg flex justify-between items-center px-4 md:px-8 py-3 shadow-lg relative z-30">
        <a href="dashboard.php" class="flex items-center space-x-2.5">
            <img src="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png" alt="Logo" class="h-9 w-9 bg-white rounded-md p-0.5">
            <div>
                <strong class="text-base md:text-xl text-white font-extrabold tracking-tight">BK - SMKN 2 BJM</strong>
                <small class="hidden md:block text-xs text-teal-100/70">Bimbingan dan Konseling</small>
            </div>
        </a>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="dashboard.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Beranda</a>
            <a href="data_profiling.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Data Profiling</a>
            <a href="riwayatkonselingsiswa.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Riwayat</a>
            <a href="ganti_password.php" class="text-teal-100/80 hover:text-white border-b-2 border-transparent hover:border-white pb-1 transition">Ganti Password</a>
            <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition text-sm font-semibold shadow-md">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </button>
        </nav>
        <button onclick="toggleMenu()" class="md:hidden text-white text-2xl p-2 z-40 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <div id="menuOverlay" class="hidden fixed inset-0 bg-black/50 z-20" onclick="toggleMenu()"></div>
    <div id="mobileMenu" class="hidden absolute top-[64px] left-0 w-full bg-white shadow-xl z-30 md:hidden flex-col text-left text-base border-t border-gray-100">
        <a href="dashboard.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-home mr-3"></i>Beranda</a>
        <a href="data_profiling.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-user-edit mr-3"></i>Data Profiling</a>
        <a href="riwayatkonselingsiswa.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-history mr-3"></i>Riwayat</a>
        <a href="ganti_password.php" class="py-3 px-4 text-gray-700 hover:bg-gray-50 transition flex items-center"><i class="fas fa-key mr-3"></i>Ganti Password</a>
        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white py-3 hover:bg-red-700 transition text-sm font-semibold mt-1">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
        </button>
    </div>

    <section class="py-10 md:py-14 px-4 text-white" style="background: linear-gradient(135deg, var(--navbar-bg), var(--primary-color));">
        <div class="max-w-5xl mx-auto">
            <a href="dashboard.php" class="inline-flex items-center gap-2 text-teal-100/80 hover:text-white text-sm mb-4 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-chalkboard-user text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold">Bimbingan Klasikal</h1>
                    <p class="text-teal-50/80 text-sm md:text-base mt-1">
                        Materi bimbingan untuk kelas <?php echo htmlspecialchars(trim($kelas_siswa . ' ' . $jurusan_siswa)); ?>. Selesaikan setiap materi secara berurutan.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <main class="flex-grow max-w-5xl w-full mx-auto px-4 py-8 md:py-10">

        <div id="listMateriSection">
            <div id="ringkasanProgress" class="hidden bg-white border border-gray-200 rounded-2xl p-5 mb-6 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-bold text-gray-700">Progres Keseluruhan</p>
                    <p class="text-sm font-bold primary-color"><span id="ringkasanTeks">0/0 materi</span></p>
                </div>
                <div class="progress-track"><div id="ringkasanFill" class="progress-fill" style="width:0%"></div></div>
            </div>

            <div id="daftarMateriWrap" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="col-span-full text-center py-16 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-3"></i>
                    <p class="text-sm">Memuat daftar materi...</p>
                </div>
            </div>
        </div>

        
        <div id="viewerMateri">
            <button onclick="tutupViewerMateri()" class="inline-flex items-center gap-2 text-sm font-semibold primary-color hover:opacity-75 mb-4 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Materi
            </button>
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 md:p-6 border-b border-gray-100">
                    <h2 id="viewerJudulMateri" class="text-xl md:text-2xl font-extrabold text-gray-800"></h2>
                    <p id="viewerDeskripsiMateri" class="text-sm text-gray-500 mt-1"></p>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex-1 progress-track" style="height:8px"><div id="viewerProgressFill" class="progress-fill" style="width:0%"></div></div>
                        <span id="viewerProgressTeks" class="text-xs font-bold text-gray-500 whitespace-nowrap">0/3 bagan</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3">
                    <div class="md:col-span-1 border-b md:border-b-0 md:border-r border-gray-100 p-3 md:p-4">
                        <p class="text-xs font-bold uppercase text-gray-400 px-2 mb-2">Alur Belajar</p>
                        <p class="text-[11px] text-gray-400 px-2 mb-3">Setiap bagan wajib dibuka minimal 5 menit.</p>
                        <div id="daftarBagan" class="space-y-2"></div>
                    </div>
                    <div class="md:col-span-2 p-5 md:p-7"><div id="isiBaganAktif"></div></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-5 navbar-bg text-white text-xs md:text-sm mt-auto shadow-inner">
        <p class="text-sm text-gray-200 font-light">&copy; 2025 <span class="font-semibold">Bimbingan dan Konseling SMKN 2 Banjarmasin</span></p>
        <p class="text-xs text-gray-400 mt-1">Developed by <span class="font-medium">SahDu Team</span></p>
    </footer>

<script>
function toggleMenu(){const m=document.getElementById('mobileMenu'),o=document.getElementById('menuOverlay'),t=!m.classList.contains('hidden');if(t){m.classList.add('hidden');m.classList.remove('flex');o.classList.add('hidden');}else{m.classList.remove('hidden');m.classList.add('flex');o.classList.remove('hidden');}}
function escapeHtml(t){if(t==null)return'';const d=document.createElement('div');d.textContent=String(t);return d.innerHTML;}
function nl2br(t){return escapeHtml(t).replace(/\n/g,'<br>');}
const STATUS_LABEL={selesai:'Selesai',berlangsung:'Sedang Berjalan',tersedia:'Tersedia',terkunci:'Terkunci'};
const STATUS_ICON={selesai:'fa-circle-check',berlangsung:'fa-play-circle',tersedia:'fa-lock-open',terkunci:'fa-lock'};
const URUTAN_BAGAN=['ppt','video','tugas'];
const LABEL_BAGAN={ppt:'Materi PPT',video:'Video',tugas:'Tugas / Kuis'};
const IKON_BAGAN={ppt:'fa-file-powerpoint',video:'fa-video',tugas:'fa-list-check'};
let materiAktifId=null,dataMateriAktif=null,baganAktif=null,durasiWajib=300,timerState={},heartbeatId=null;
function formatDetik(s){s=Math.max(0,Math.floor(s));const m=Math.floor(s/60),d=s%60;return String(m).padStart(2,'0')+':'+String(d).padStart(2,'0');}

/** Parse response JSON dengan aman; tampilkan isi error jika bukan JSON */
function fetchJson(url, opts){
  return fetch(url, opts).then(async function(r){
    const text=await r.text();
    let data=null;
    try{ data=JSON.parse(text); }catch(e){
      const potong=(text||'').replace(/\s+/g,' ').slice(0,200);
      throw new Error('Server tidak mengembalikan JSON (HTTP '+r.status+'). '+potong);
    }
    if(!r.ok && data && data.message) throw new Error(data.message);
    return data;
  });
}

/** localStorage backup agar timer tidak hilang saat refresh */
function lsKey(idm, jenis){ return 'bk_timer_v2_'+idm+'_'+jenis; }
function lsSelesaiKey(idm){ return 'bk_selesai_v2_'+idm; }
function simpanTimerLocal(idm, jenis, st){
  try{
    if(!idm||!jenis||!st) return;
    localStorage.setItem(lsKey(idm,jenis), JSON.stringify({
      startMs: st.startMs || null,
      waktuMulai: st.waktuMulai || null,
      savedAt: Date.now(),
      elapsed: st.elapsed||0
    }));
  }catch(e){}
}
function bacaTimerLocal(idm, jenis){
  try{
    const raw=localStorage.getItem(lsKey(idm,jenis));
    if(!raw) return null;
    return JSON.parse(raw);
  }catch(e){ return null; }
}
function hapusTimerLocal(idm, jenis){
  try{ localStorage.removeItem(lsKey(idm,jenis)); }catch(e){}
}
/** Simpan status selesai bagan di localStorage (backup jika DB gagal) */
function simpanSelesaiLocal(idm, jenis){
  try{
    const key=lsSelesaiKey(idm);
    let map={};
    try{ map=JSON.parse(localStorage.getItem(key)||'{}'); }catch(e){ map={}; }
    map[jenis]=1;
    localStorage.setItem(key, JSON.stringify(map));
  }catch(e){}
}
function bacaSelesaiLocal(idm){
  try{
    return JSON.parse(localStorage.getItem(lsSelesaiKey(idm))||'{}');
  }catch(e){ return {}; }
}
function hapusSelesaiLocal(idm){
  try{ localStorage.removeItem(lsSelesaiKey(idm)); }catch(e){}
}
/** Hitung elapsed dari localStorage startMs (wall-clock client) */
function elapsedDariLocal(loc){
  if(!loc) return 0;
  if(loc.startMs && Number(loc.startMs)>0){
    return Math.max(0, Math.floor((Date.now()-Number(loc.startMs))/1000));
  }
  return Math.max(0, Number(loc.elapsed)||0);
}

function muatDaftarMateriSiswa(){
  const fd=new FormData();fd.append('action','list_materi_siswa');
  fetch(window.location.pathname,{method:'POST',body:fd}).then(r=>r.json()).then(data=>{if(data.success)renderDaftarMateriSiswa(data.data);});
}
function renderDaftarMateriSiswa(daftar){
  const wrap=document.getElementById('daftarMateriWrap'),ring=document.getElementById('ringkasanProgress');
  if(!daftar.length){wrap.innerHTML='<div class="col-span-full text-center py-16 bg-white border border-gray-200 rounded-2xl"><i class="fas fa-chalkboard text-4xl text-gray-300 mb-3"></i><p class="text-gray-500 font-semibold">Belum ada materi Bimbingan Klasikal untuk kelas Anda.</p></div>';ring.classList.add('hidden');return;}
  const tot=daftar.filter(m=>m.status==='selesai').length;
  document.getElementById('ringkasanTeks').textContent=tot+'/'+daftar.length+' materi';
  document.getElementById('ringkasanFill').style.width=(daftar.length?(tot/daftar.length*100):0)+'%';
  ring.classList.remove('hidden');
  wrap.innerHTML=daftar.map(m=>{
    const bisa=m.status!=='terkunci',persen=m.jumlah_slide>0?Math.round(m.jumlah_selesai/m.jumlah_slide*100):0;
    const tombol=m.status==='selesai'?'Lihat Kembali':m.status==='berlangsung'?'Lanjutkan':m.status==='tersedia'?'Mulai Materi':'Terkunci';
    return '<div class="materi-card '+m.status+' p-5 md:p-6" '+(bisa?'onclick="window.location.hash=\'materi-'+m.id_materi+'\';bukaMateriSiswa('+m.id_materi+')"':'')+'>'+
      '<div class="flex items-start justify-between gap-2 mb-3"><span class="w-9 h-9 rounded-lg bg-[#2A6163]/10 text-[#2A6163] font-extrabold text-sm flex items-center justify-center">'+m.nomor+'</span>'+
      '<span class="badge badge-'+m.status+'"><i class="fas '+STATUS_ICON[m.status]+' mr-1"></i>'+STATUS_LABEL[m.status]+'</span></div>'+
      '<h3 class="font-bold text-slate-800 text-lg">'+escapeHtml(m.judul)+'</h3>'+
      '<p class="text-sm text-slate-500 mt-1.5 line-clamp-2">'+escapeHtml(m.deskripsi||'Tidak ada deskripsi.')+'</p>'+
      (m.status==='terkunci'&&m.judul_materi_sebelum?'<p class="text-xs text-amber-600 mt-2"><i class="fas fa-circle-info mr-1"></i>Selesaikan "'+escapeHtml(m.judul_materi_sebelum)+'" terlebih dahulu.</p>':'')+
      '<div class="mt-4"><div class="flex justify-between text-xs text-slate-500 mb-1"><span>'+m.jumlah_selesai+'/'+m.jumlah_slide+' bagan</span><span>'+persen+'%</span></div>'+
      '<div class="progress-track"><div class="progress-fill" style="width:'+persen+'%"></div></div></div>'+
      '<div class="mt-4 text-sm font-bold '+(bisa?'primary-color':'text-gray-400')+'">'+tombol+(bisa?' <i class="fas fa-chevron-right text-xs"></i>':'')+'</div></div>';
  }).join('');
}

function bukaMateriSiswa(id){
  hentikanSemuaTimer();
  const fd=new FormData();fd.append('action','lihat_materi_siswa');fd.append('id_materi',id);
  fetchJson(window.location.pathname,{method:'POST',body:fd}).then(data=>{
    if(!data.success){alert(data.message||'Materi belum dapat diakses.');return;}
    materiAktifId=id;window.FOLDER_GURU_BK=data.folder_guru||'../guru/';
    durasiWajib=data.durasi_wajib||300;dataMateriAktif=data.data;timerState={};

    // Gabungkan status selesai dari localStorage (jika DB belum sempat menyimpan)
    const selesaiLocal=bacaSelesaiLocal(id);
    let perluSync=[];
    URUTAN_BAGAN.forEach(j=>{
      const b=dataMateriAktif.bagan[j];
      if(b.status_selesai!=1 && selesaiLocal[j]==1){
        b.status_selesai=1;
        perluSync.push(j);
      }
    });
    // Perbaiki terkunci berdasarkan status selesai yang sudah digabung
    let prevOk=true;
    URUTAN_BAGAN.forEach(j=>{
      dataMateriAktif.bagan[j].terkunci = prevOk ? 0 : 1;
      prevOk = (dataMateriAktif.bagan[j].status_selesai==1);
    });

    URUTAN_BAGAN.forEach(j=>{
      const b=dataMateriAktif.bagan[j];
      let elapsedServer=0;
      if(b.status_selesai==1) elapsedServer=durasiWajib;
      else if(typeof b.elapsed_detik==='number') elapsedServer=Math.max(0,b.elapsed_detik);

      const loc=bacaTimerLocal(id,j);
      const elapsedLocal=elapsedDariLocal(loc);
      let elapsed=Math.max(elapsedServer, elapsedLocal);
      if(b.status_selesai==1) elapsed=durasiWajib;

      let startMs=null;
      if(loc && loc.startMs) startMs=Number(loc.startMs);
      else if(elapsed>0) startMs=Date.now()-(elapsed*1000);

      timerState[j]={
        elapsed: elapsed,
        waktuMulai: b.waktu_mulai || (loc && loc.waktuMulai) || null,
        startMs: startMs,
        anchorElapsed: elapsed,
        anchorClientMs: Date.now(),
        running: false,
        intervalId: null
      };
      if(elapsed>0 && b.status_selesai!=1) simpanTimerLocal(id,j,timerState[j]);
    });

    // Re-sync bagan yang selesai di local tapi belum di server (background)
    perluSync.forEach(j=>{
      const fd2=new FormData();
      fd2.append('action','selesai_bagan');
      fd2.append('id_materi',id);
      fd2.append('jenis_bagan',j);
      fd2.append('durasi_detik',durasiWajib);
      fetchJson(window.location.pathname,{method:'POST',body:fd2}).catch(()=>{});
    });

    let awal='ppt';
    for(const j of URUTAN_BAGAN){
      const b=dataMateriAktif.bagan[j];
      if(b.terkunci==0 && b.status_selesai!=1){ awal=j; break; }
      if(b.status_selesai==1) awal=j;
    }
    baganAktif=awal;renderViewerMateri();
  }).catch(err=>{alert(err.message||'Gagal memuat materi.');});
}
function renderViewerMateri(){
  document.getElementById('viewerJudulMateri').textContent=dataMateriAktif.judul;
  document.getElementById('viewerDeskripsiMateri').textContent=dataMateriAktif.deskripsi||'';
  updateProgressBaganHeader();renderDaftarBagan();renderIsiBagan();mulaiTimerBaganAktif();
  document.getElementById('listMateriSection').classList.add('tersembunyi');
  document.getElementById('viewerMateri').classList.add('aktif');
  window.scrollTo({top:0,behavior:'smooth'});updateUrlState(materiAktifId,baganAktif);
}
function updateProgressBaganHeader(){
  let s=0;URUTAN_BAGAN.forEach(j=>{if(dataMateriAktif.bagan[j].status_selesai==1)s++;});
  document.getElementById('viewerProgressTeks').textContent=s+'/3 bagan';
  document.getElementById('viewerProgressFill').style.width=(s/3*100)+'%';
}
function renderDaftarBagan(){
  document.getElementById('daftarBagan').innerHTML=URUTAN_BAGAN.map((j,idx)=>{
    const b=dataMateriAktif.bagan[j],ts=timerState[j]||{elapsed:0};
    // Pastikan elapsed terbaru dari wall-clock sebelum render sidebar
    if(ts.waktuMulai || ts.anchorClientMs) hitungElapsedWallClock(j);
    const selesai=b.status_selesai==1,terkunci=b.terkunci==1,aktif=baganAktif===j;
    let dc='locked',di='<i class="fas fa-lock"></i>';
    if(selesai){dc='done';di='<i class="fas fa-check"></i>';}else if(!terkunci){dc=aktif?'current':'ready';di='<i class="fas '+IKON_BAGAN[j]+'"></i>';}
    let ic=selesai?'done':(terkunci?'locked':'');if(aktif)ic+=' active';
    let st='';
    if(selesai)st='<span class="text-[11px] font-semibold text-green-600"><i class="fas fa-check-circle"></i> Selesai</span>';
    else if(terkunci)st='<span class="text-[11px] text-gray-400"><i class="fas fa-lock"></i> Terkunci</span>';
    else if(j==='tugas'){st='<span class="text-[11px] text-slate-500"><i class="fas fa-pen"></i> Isi kuis</span>';}
    else{const sisa=Math.max(0,durasiWajib-(ts.elapsed||0));st=sisa<=0?'<span class="text-[11px] font-semibold text-teal-700">Siap diselesaikan</span>':'<span class="text-[11px] text-amber-700">Sisa '+formatDetik(sisa)+'</span>';}
    return '<div class="bagan-item '+ic+'" '+(terkunci?'':'onclick="pilihBagan(\''+j+'\')"')+'><span class="bagan-dot '+dc+'">'+di+'</span><div class="min-w-0 flex-1"><p class="text-sm font-semibold '+(terkunci?'text-gray-400':'text-gray-800')+'">'+(idx+1)+'. '+LABEL_BAGAN[j]+'</p>'+st+'</div></div>';
  }).join('');
}
function pilihBagan(j){
  const b=dataMateriAktif.bagan[j];
  if(!b||b.terkunci==1||baganAktif===j)return;
  // Simpan draft jawaban LKPD sebelum pindah bagan
  if(baganAktif==='tugas') simpanDraftLkpd();
  hentikanTimerBagan(baganAktif);
  baganAktif=j;
  renderDaftarBagan();
  renderIsiBagan();
  mulaiTimerBaganAktif();
  updateUrlState(materiAktifId,baganAktif);
}

/* ========== TIMER (wall-clock + localStorage) ==========
 * elapsed dihitung dari:
 *   1) startMs client (localStorage)  ATAU
 *   2) anchorElapsed + (now - anchorClientMs) dari server
 * Ambil nilai terbesar agar refresh / pindah tab tidak mereset.
 * TIDAK ada pause saat document.hidden.
 */
function hitungElapsedWallClock(jenis){
  const st=timerState[jenis];
  if(!st) return 0;
  if(dataMateriAktif && dataMateriAktif.bagan[jenis] && dataMateriAktif.bagan[jenis].status_selesai==1){
    st.elapsed=durasiWajib;
    return st.elapsed;
  }
  let dariStart=0;
  if(st.startMs && Number(st.startMs)>0){
    dariStart=Math.max(0, Math.floor((Date.now()-Number(st.startMs))/1000));
  }
  let dariAnchor=0;
  if(typeof st.anchorElapsed==='number' && st.anchorClientMs){
    dariAnchor=Math.max(0, st.anchorElapsed + Math.floor((Date.now()-st.anchorClientMs)/1000));
  }
  st.elapsed=Math.max(dariStart, dariAnchor, st.elapsed||0);
  return st.elapsed;
}

function setAnchorDariServer(st, elapsedServer, waktuMulai){
  const e=Math.max(0, Number(elapsedServer)||0);
  // Jangan turunkan elapsed yang sudah lebih tinggi di client/localStorage
  const current=Math.max(0, st.elapsed||0);
  const finalE=Math.max(e, current);
  st.elapsed=finalE;
  st.anchorElapsed=finalE;
  st.anchorClientMs=Date.now();
  if(waktuMulai) st.waktuMulai=waktuMulai;
  // Pastikan startMs ada dan tidak lebih baru dari yang seharusnya
  const startIdeal=Date.now()-(finalE*1000);
  if(!st.startMs || Number(st.startMs)<=0){
    st.startMs=startIdeal;
  } else if(startIdeal < Number(st.startMs)){
    st.startMs=startIdeal;
  }
}

function mulaiIntervalTimer(st){
  if(st.running && st.intervalId) return;
  if(st.intervalId){clearInterval(st.intervalId);st.intervalId=null;}
  st.running=true;
  st.intervalId=setInterval(function(){
    if(!baganAktif||!timerState[baganAktif])return;
    const cur=timerState[baganAktif];
    hitungElapsedWallClock(baganAktif);
    if(materiAktifId) simpanTimerLocal(materiAktifId, baganAktif, cur);
    updateTimerUI();
    if(cur.elapsed>=durasiWajib){
      cur.elapsed=durasiWajib;
      if(cur.intervalId){clearInterval(cur.intervalId);cur.intervalId=null;}
      cur.running=false;
      if(materiAktifId) simpanTimerLocal(materiAktifId, baganAktif, cur);
      renderDaftarBagan();
      renderIsiBagan();
    }
  },1000);
}

function mulaiTimerBaganAktif(){
  if(!baganAktif||!dataMateriAktif)return;
  if(dataMateriAktif.bagan[baganAktif].status_selesai==1)return;
  // Bagan tugas/kuis tidak memakai timer 5 menit
  if(baganAktif==='tugas'){
    // tetap catat mulai di server (opsional) tanpa interval UI
    const fd=new FormData();
    fd.append('action','mulai_bagan');
    fd.append('id_materi',materiAktifId);
    fd.append('jenis_bagan','tugas');
    fetch(window.location.pathname,{method:'POST',body:fd}).catch(()=>{});
    return;
  }

  const st=timerState[baganAktif];
  if(!st)return;

  // Jika belum ada startMs, set sekarang (awal timer)
  if(!st.startMs){
    st.startMs=Date.now()-((st.elapsed||0)*1000);
  }
  simpanTimerLocal(materiAktifId, baganAktif, st);

  const fd=new FormData();
  fd.append('action','mulai_bagan');
  fd.append('id_materi',materiAktifId);
  fd.append('jenis_bagan',baganAktif);

  fetchJson(window.location.pathname,{method:'POST',body:fd})
    .then(data=>{
      if(!data.success)return;
      if(data.sudah_selesai){
        setAnchorDariServer(st, durasiWajib, data.waktu_mulai);
        dataMateriAktif.bagan[baganAktif].status_selesai=1;
        hapusTimerLocal(materiAktifId, baganAktif);
        renderDaftarBagan();renderIsiBagan();
        return;
      }
      const elapsedSrv=typeof data.elapsed_detik==='number' ? data.elapsed_detik : 0;
      // Ambil max(server, local) — jangan pernah turun ke 0 jika local sudah jalan
      const elapsedLocal=elapsedDariLocal(bacaTimerLocal(materiAktifId, baganAktif));
      const elapsedGabungan=Math.max(elapsedSrv, elapsedLocal, st.elapsed||0);
      setAnchorDariServer(st, elapsedGabungan, data.waktu_mulai||st.waktuMulai);
      if(data.durasi_wajib) durasiWajib=data.durasi_wajib;
      simpanTimerLocal(materiAktifId, baganAktif, st);

      if(st.elapsed>=durasiWajib){
        st.elapsed=durasiWajib;
        updateTimerUI();
        renderIsiBagan();
        return;
      }
      mulaiIntervalTimer(st);
      updateTimerUI();
      mulaiHeartbeat();
    })
    .catch(function(){
      // Offline / error server: lanjut dari localStorage / anchor
      hitungElapsedWallClock(baganAktif);
      if(!st.startMs) st.startMs=Date.now()-((st.elapsed||0)*1000);
      simpanTimerLocal(materiAktifId, baganAktif, st);
      if(st.elapsed>=durasiWajib){renderIsiBagan();return;}
      mulaiIntervalTimer(st);
      updateTimerUI();
    });
}

function hentikanTimerBagan(j){
  const st=timerState[j];
  if(!st)return;
  if(st.intervalId){clearInterval(st.intervalId);st.intervalId=null;}
  st.running=false;
  // Simpan progress sebelum interval dihentikan
  if(materiAktifId){ hitungElapsedWallClock(j); simpanTimerLocal(materiAktifId, j, st); }
}
function hentikanSemuaTimer(){
  URUTAN_BAGAN.forEach(hentikanTimerBagan);
  if(heartbeatId){clearInterval(heartbeatId);heartbeatId=null;}
}

function updateTimerUI(){
  if(!baganAktif)return;
  const st=timerState[baganAktif];
  if(!st)return;
  hitungElapsedWallClock(baganAktif);
  const elapsed=Math.min(st.elapsed,durasiWajib);
  const sisa=Math.max(0,durasiWajib-elapsed);
  const persen=Math.min(100,(elapsed/durasiWajib)*100);

  renderDaftarBagan();

  const elS=document.getElementById('timerSisaTeks');
  const elElapsed=document.getElementById('timerElapsedTeks');
  const elF=document.getElementById('timerProgressFill');
  if(elS){
    elS.textContent=sisa<=0
      ?'Waktu terpenuhi — Anda bisa menyelesaikan bagan ini'
      :'Sisa waktu: '+formatDetik(sisa);
  }
  if(elElapsed){
    elElapsed.textContent=formatDetik(elapsed)+' / '+formatDetik(durasiWajib);
  }
  if(elF){
    elF.style.width=persen+'%';
    elF.classList.toggle('done',sisa<=0);
  }
  const btn=document.getElementById('btnSelesaiBagan');
  if(btn){
    const siap=sisa<=0;
    btn.disabled=!siap;
    btn.classList.toggle('opacity-50',!siap);
    btn.classList.toggle('cursor-not-allowed',!siap);
  }
}

function mulaiHeartbeat(){
  if(heartbeatId){clearInterval(heartbeatId);heartbeatId=null;}
  heartbeatId=setInterval(function(){
    if(!materiAktifId||!baganAktif||!timerState[baganAktif])return;
    if(dataMateriAktif&&dataMateriAktif.bagan[baganAktif].status_selesai==1)return;
    hitungElapsedWallClock(baganAktif);
    simpanTimerLocal(materiAktifId, baganAktif, timerState[baganAktif]);
    const fd=new FormData();
    fd.append('action','update_durasi_bagan');
    fd.append('id_materi',materiAktifId);
    fd.append('jenis_bagan',baganAktif);
    fd.append('durasi_detik',timerState[baganAktif].elapsed||0);
    fetch(window.location.pathname,{method:'POST',body:fd}).catch(()=>{});
  },15000);
}

function lkpdSudahLengkap(){
  if(!dataMateriAktif) return true;
  const list=(dataMateriAktif.konten&&dataMateriAktif.konten.tugas)||[];
  if(!list.length) return true; // tidak ada pertanyaan → boleh selesai
  const j=kumpulkanJawabanLkpd();
  for(const p of list){
    const id=String(p.id_pertanyaan);
    const v=j[id];
    if(v==null || (typeof v==='string' && v.trim()==='') || (Array.isArray(v) && v.length===0)) return false;
  }
  return true;
}
function updateTombolTugas(){
  const btn=document.getElementById('btnSelesaiBagan');
  if(!btn || baganAktif!=='tugas') return;
  const ok=lkpdSudahLengkap();
  btn.disabled=!ok;
  btn.classList.toggle('opacity-50', !ok);
  btn.classList.toggle('cursor-not-allowed', !ok);
}
function renderIsiBagan(){
  const wrap=document.getElementById('isiBaganAktif');if(!dataMateriAktif||!baganAktif){wrap.innerHTML='';return;}
  const b=dataMateriAktif.bagan[baganAktif],st=timerState[baganAktif]||{elapsed:0};
  const selesai=b.status_selesai==1,sisa=Math.max(0,durasiWajib-st.elapsed),persen=Math.min(100,st.elapsed/durasiWajib*100),konten=dataMateriAktif.konten;
  const isTugas=baganAktif==='tugas';
  const subSelesai='Bagan ini sudah selesai';
  const subAktif=isTugas?'Isi semua pertanyaan di bawah, lalu tandai selesai':'Pelajari materi di bawah ini minimal 5 menit';
  let html='<div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100"><span class="w-10 h-10 rounded-xl flex items-center justify-center '+(selesai?'bg-green-100 text-green-700':'bg-[#2A6163]/10 text-[#2A6163]')+'"><i class="fas '+(selesai?'fa-check':IKON_BAGAN[baganAktif])+' text-lg"></i></span><div><h3 class="text-lg font-bold text-gray-800">'+LABEL_BAGAN[baganAktif]+'</h3><p class="text-xs text-gray-500">'+(selesai?subSelesai:subAktif)+'</p></div></div>';
  // Timer bar hanya untuk PPT & Video — bukan untuk Tugas/Kuis
  if(!selesai && !isTugas){
    html+='<div class="bg-slate-50 border border-slate-200 rounded-xl p-3 mb-5"><div class="flex justify-between text-xs mb-1.5"><span id="timerSisaTeks" class="font-semibold text-slate-600">'+(sisa<=0?'Waktu terpenuhi — Anda bisa menyelesaikan bagan ini':'Sisa waktu: '+formatDetik(sisa))+'</span><span id="timerElapsedTeks" class="text-slate-400">'+formatDetik(Math.min(st.elapsed,durasiWajib))+' / '+formatDetik(durasiWajib)+'</span></div><div class="timer-bar-track"><div id="timerProgressFill" class="timer-bar-fill '+(sisa<=0?'done':'')+'" style="width:'+persen+'%"></div></div></div>';
  }
  if(baganAktif==='ppt')html+=renderKontenPpt(konten);else if(baganAktif==='video')html+=renderKontenVideo(konten);else html+=renderKontenTugas(konten,selesai);
  html+='<div id="pesanErrorBagan" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2 mt-5"></div>';
  html+='<div class="flex flex-wrap items-center justify-between gap-3 mt-6 pt-5 border-t border-gray-100">';
  const idx=URUTAN_BAGAN.indexOf(baganAktif);
  html+=idx>0?'<button type="button" onclick="pilihBagan(\''+URUTAN_BAGAN[idx-1]+'\')" class="text-sm font-semibold text-gray-500"><i class="fas fa-chevron-left mr-1"></i> Bagan Sebelumnya</button>':'<span></span>';
  if(selesai){
    const semua=URUTAN_BAGAN.every(j=>dataMateriAktif.bagan[j].status_selesai==1);
    if(idx<2){
      html+='<button type="button" onclick="pilihBagan(\''+URUTAN_BAGAN[idx+1]+'\')" class="bg-[#2A6163] hover:bg-[#1F4A4B] text-white font-bold text-sm px-6 py-2.5 rounded-lg">Lanjut ke '+LABEL_BAGAN[URUTAN_BAGAN[idx+1]]+' <i class="fas fa-chevron-right ml-1"></i></button>';
    } else if(semua){
      html+='<button type="button" onclick="selesaikanMateri()" class="bg-[#2F9160] hover:bg-[#26744d] text-white font-bold text-sm px-6 py-2.5 rounded-lg"><i class="fas fa-flag-checkered mr-1"></i> Selesaikan Materi</button>';
    }
  }else{
    // PPT/Video: kunci sampai timer 5 menit. Tugas: kunci sampai semua jawaban terisi.
    let dis;
    if(isTugas){
      // sementara disable; akan di-update setelah form ter-render via updateTombolTugas
      dis=true;
    }else{
      dis=sisa>0;
    }
    html+='<button type="button" id="btnSelesaiBagan" onclick="tandaiBaganSelesai()" class="bg-[#2A6163] hover:bg-[#1F4A4B] text-white font-bold text-sm px-6 py-2.5 rounded-lg '+(dis?'opacity-50 cursor-not-allowed':'')+'" '+(dis?'disabled':'')+'><i class="fas fa-check mr-1"></i> Tandai Selesai</button>';
  }
  html+='</div>';
  wrap.innerHTML=html;
  if(baganAktif==='tugas'){ pasangListenerDraftLkpd(); updateTombolTugas(); }
  if(isTugas && !selesai){
    // aktifkan tombol saat semua jawaban terisi
    updateTombolTugas();
    const form=document.getElementById('formLkpd');
    if(form){
      form.addEventListener('input', updateTombolTugas);
      form.addEventListener('change', updateTombolTugas);
    }
  }
}

function renderKontenPpt(k){
  let h='';
  (k.teks||[]).forEach(t=>{h+='<div class="prose prose-sm max-w-none text-gray-700 mb-4 whitespace-pre-wrap" style="overflow-wrap:anywhere;">'+(t.judul?'<p class="font-semibold mb-1 break-words">'+escapeHtml(t.judul)+'</p>':'')+nl2br(t.teks)+'</div>';});
  const items=k.ppt||[];
  if(!items.length&&!(k.teks||[]).length)return '<div class="text-center py-12 bg-gray-50 border border-dashed rounded-xl"><i class="fas fa-file-powerpoint text-4xl text-gray-300 mb-3"></i><p class="text-gray-500 font-semibold">Tidak ada materi PPT untuk materi ini</p><p class="text-xs text-gray-400 mt-1">Tetap buka bagan ini 5 menit sebelum lanjut.</p></div>';
  // Judul slide di atas gambar/PPT dihilangkan — cukup teks pendukung (judul+deskripsi) di atas
  items.forEach(it=>{h+=it.tipe==='gambar'?renderGambarSlide(it.path):renderDokumenSlide(it.path);});
  return h;
}
function renderKontenVideo(k){
  const list=k.video||[];
  if(!list.length)return '<div class="text-center py-12 bg-gray-50 border border-dashed rounded-xl"><i class="fas fa-video text-4xl text-gray-300 mb-3"></i><p class="text-gray-500 font-semibold">Belum ada video untuk materi ini</p><p class="text-xs text-gray-400 mt-1">Tetap buka bagan ini 5 menit sebelum lanjut.</p></div>';
  let h='';
  list.forEach(v=>{
    if(v.judul)h+='<p class="text-sm font-semibold text-gray-700 mb-2">'+escapeHtml(v.judul)+'</p>';
    const yt=extractYoutubeId(v.url),dr=!yt?extractGoogleDriveId(v.url):null;
    if(yt)h+='<div class="yt-wrap mb-6"><iframe src="https://www.youtube.com/embed/'+yt+'?playsinline=1&rel=0" allowfullscreen playsinline></iframe></div>';
    else if(dr)h+=embedGoogleDrive(dr,urlLengkap(v.url),'video');
    else h+='<a href="'+escapeHtml(v.url)+'" target="_blank" class="inline-flex items-center gap-2 text-red-600 font-semibold mb-6"><i class="fab fa-youtube"></i> Buka Video</a>';
  });
  return h;
}
function renderKontenTugas(k,sudah){
  const list=k.tugas||[];
  if(!list.length)return '<div class="text-center py-12 bg-gray-50 border border-dashed rounded-xl"><i class="fas fa-list-check text-4xl text-gray-300 mb-3"></i><p class="text-gray-500 font-semibold">Tidak ada pertanyaan tugas/kuis</p><p class="text-xs text-gray-400 mt-1">Isi kuis jika tersedia, lalu tandai selesai.</p></div>';
  let h='<p class="text-sm font-bold text-gray-700 mb-4"><i class="fas fa-list-check primary-color mr-1"></i> LKPD</p><div id="formLkpd" class="space-y-4">';
  list.forEach((p,pi)=>{
    const jawab=jawabanEfektif(p);
    h+='<div class="bg-gray-50 border rounded-xl p-4"><p class="text-sm font-semibold mb-3 break-words" style="overflow-wrap:anywhere;">'+(pi+1)+'. '+escapeHtml(p.teks_pertanyaan)+'</p>';
    const dis=sudah?'disabled':'',nm='lkpd_'+p.id_pertanyaan;
    if(p.tipe_jawaban==='pilihan_ganda')(p.opsi_jawaban||[]).forEach(o=>{h+='<label class="flex items-center gap-2 text-sm py-1"><input type="radio" name="'+nm+'" value="'+escapeHtml(o)+'" '+(jawab===o?'checked':'')+' '+dis+' class="lkpd-input" data-id-pertanyaan="'+p.id_pertanyaan+'"> '+escapeHtml(o)+'</label>';});
    else if(p.tipe_jawaban==='checkbox'){const t=jawab.split(',').map(s=>s.trim()).filter(Boolean);(p.opsi_jawaban||[]).forEach(o=>{h+='<label class="flex items-center gap-2 text-sm py-1"><input type="checkbox" value="'+escapeHtml(o)+'" '+(t.includes(o)?'checked':'')+' '+dis+' class="lkpd-input-checkbox" data-id-pertanyaan="'+p.id_pertanyaan+'"> '+escapeHtml(o)+'</label>';});}
    else if(p.tipe_jawaban==='isian_singkat')h+='<input type="text" class="lkpd-input w-full border rounded-lg px-3 py-2 text-sm" data-id-pertanyaan="'+p.id_pertanyaan+'" value="'+escapeHtml(jawab)+'" '+dis+'>';
    else h+='<textarea class="lkpd-input w-full border rounded-lg px-3 py-2 text-sm" rows="3" data-id-pertanyaan="'+p.id_pertanyaan+'" '+dis+'>'+escapeHtml(jawab)+'</textarea>';
    // Tampilkan bintang tanggapan guru jika sudah selesai & ada rating
    const rating=parseInt(p.rating_guru||0,10);
    if(sudah && rating>=1 && rating<=5){
      h+='<div class="mt-3 pt-2 border-t border-gray-200 flex items-center gap-1 flex-wrap">';
      h+='<span class="text-xs text-gray-500 mr-1">Tanggapan guru:</span>';
      for(let i=1;i<=5;i++){
        h+='<i class="fas fa-star text-sm '+(i<=rating?'text-amber-400':'text-gray-300')+'"></i>';
      }
      h+='<span class="text-xs text-gray-500 ml-1">'+rating+'/5</span>';
      if(p.nama_guru_tanggapan) h+='<span class="text-xs text-gray-400 ml-1">oleh '+escapeHtml(p.nama_guru_tanggapan)+'</span>';
      if(p.catatan_guru) h+='<p class="w-full text-xs text-gray-600 mt-1 italic">'+escapeHtml(p.catatan_guru)+'</p>';
      h+='</div>';
    }
    h+='</div>';
  });
  return h+'</div>';
}
function kunciDraftLkpd(idMateri){ return 'bk_lkpd_draft_'+idMateri; }
function simpanDraftLkpd(){
  if(!materiAktifId || !document.getElementById('formLkpd')) return;
  try{
    localStorage.setItem(kunciDraftLkpd(materiAktifId), JSON.stringify(kumpulkanJawabanLkpd()));
  }catch(e){}
}
function muatDraftLkpd(){
  if(!materiAktifId) return {};
  try{ return JSON.parse(localStorage.getItem(kunciDraftLkpd(materiAktifId))||'{}')||{}; }
  catch(e){ return {}; }
}
function hapusDraftLkpd(idMateri){
  try{ localStorage.removeItem(kunciDraftLkpd(idMateri||materiAktifId)); }catch(e){}
}
/** Gabungkan jawaban DB + draft lokal (DB menang jika sudah ada isi) */
function jawabanEfektif(p){
  const dariDb = (p.jawaban_tersimpan||'').trim();
  if(dariDb) return dariDb;
  const draft = muatDraftLkpd();
  const id = String(p.id_pertanyaan);
  let v = draft[id];
  if(v==null) return '';
  if(Array.isArray(v)) return v.map(s=>String(s).trim()).filter(Boolean).join(', ');
  return String(v).trim();
}
function kumpulkanJawabanLkpd(){
  const j={};
  document.querySelectorAll('#formLkpd .lkpd-input').forEach(el=>{const id=el.dataset.idPertanyaan;if(el.type==='radio'){if(el.checked)j[id]=el.value;}else j[id]=el.value.trim();});
  document.querySelectorAll('#formLkpd .lkpd-input-checkbox').forEach(el=>{const id=el.dataset.idPertanyaan;if(!j[id])j[id]=[];if(el.checked)j[id].push(el.value);});
  return j;
}
function pasangListenerDraftLkpd(){
  const form=document.getElementById('formLkpd');
  if(!form) return;
  form.addEventListener('input', simpanDraftLkpd);
  form.addEventListener('change', function(){ simpanDraftLkpd(); updateTombolTugas(); });
}
function tandaiBaganSelesai(){
  if(!baganAktif||!dataMateriAktif||dataMateriAktif.bagan[baganAktif].status_selesai==1)return;
  const st=timerState[baganAktif];
  const elapsed=st?hitungElapsedWallClock(baganAktif):0;
  // Tugas/kuis: tidak wajib timer — wajib isi semua jawaban
  if(baganAktif==='tugas'){
    if(!lkpdSudahLengkap()){
      const e=document.getElementById('pesanErrorBagan');
      if(e){e.textContent='Lengkapi semua pertanyaan tugas/kuis sebelum menandai selesai.';e.classList.remove('hidden');}
      return;
    }
  }else if(elapsed<durasiWajib){
    const e=document.getElementById('pesanErrorBagan');
    if(e){e.textContent='Anda belum memenuhi waktu minimal 5 menit. Sisa: '+formatDetik(durasiWajib-elapsed);e.classList.remove('hidden');}
    return;
  }
  const btn=document.getElementById('btnSelesaiBagan');
  if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';}
  const fd=new FormData();
  fd.append('action','selesai_bagan');
  fd.append('id_materi',materiAktifId);
  fd.append('jenis_bagan',baganAktif);
  fd.append('durasi_detik',elapsed);
  if(baganAktif==='tugas') fd.append('jawaban',JSON.stringify(kumpulkanJawabanLkpd()));
  fetchJson(window.location.pathname,{method:'POST',body:fd}).then(data=>{
    if(!data.success){
      const e=document.getElementById('pesanErrorBagan');
      if(e){e.textContent=data.message||'Gagal menyimpan.';e.classList.remove('hidden');}
      if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check mr-1"></i> Tandai Selesai';}
      return;
    }
    dataMateriAktif.bagan[baganAktif].status_selesai=1;
    // Pertahankan jawaban di UI: tulis ke data lokal + hapus draft
    if(baganAktif==='tugas' && dataMateriAktif.konten && Array.isArray(dataMateriAktif.konten.tugas)){
      const jwb=kumpulkanJawabanLkpd();
      dataMateriAktif.konten.tugas.forEach(p=>{
        const id=String(p.id_pertanyaan);
        let v=jwb[id];
        if(Array.isArray(v)) v=v.map(s=>String(s).trim()).filter(Boolean).join(', ');
        p.jawaban_tersimpan = (v==null?'':String(v));
      });
      hapusDraftLkpd(materiAktifId);
    }
    simpanSelesaiLocal(materiAktifId, baganAktif);
    hapusTimerLocal(materiAktifId, baganAktif);
    const idx=URUTAN_BAGAN.indexOf(baganAktif);
    if(idx<2) dataMateriAktif.bagan[URUTAN_BAGAN[idx+1]].terkunci=0;
    hentikanTimerBagan(baganAktif);
    updateProgressBaganHeader();
    renderDaftarBagan();
    renderIsiBagan();
  }).catch(err=>{
    const e=document.getElementById('pesanErrorBagan');
    if(e){e.textContent=err.message||'Kesalahan jaringan. Coba lagi tanpa refresh — timer Anda tetap tersimpan.';e.classList.remove('hidden');}
    if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check mr-1"></i> Tandai Selesai';}
  });
}
function selesaikanMateri(){
  if(!materiAktifId||!URUTAN_BAGAN.every(j=>dataMateriAktif.bagan[j].status_selesai==1)){alert('Selesaikan ketiga bagan terlebih dahulu.');return;}
  if(!confirm('Tandai materi ini sebagai selesai?'))return;
  const fd=new FormData();fd.append('action','selesai_materi');fd.append('id_materi',materiAktifId);
  fetchJson(window.location.pathname,{method:'POST',body:fd}).then(data=>{
    if(!data.success){
      let msg=data.message||'Gagal menyimpan status materi.';
      if(data.jumlah_selesai!=null) msg+=' (DB: '+data.jumlah_selesai+'/3)';
      alert(msg);
      return;
    }
    URUTAN_BAGAN.forEach(j=>hapusTimerLocal(materiAktifId,j));
    hapusSelesaiLocal(materiAktifId);
    // Tutup viewer dan muat ulang daftar (status harus jadi Selesai / 3/3)
    tutupViewerMateri();
  }).catch(err=>alert(err.message||'Kesalahan jaringan.'));
}
function tutupViewerMateri(){hentikanSemuaTimer();document.getElementById('viewerMateri').classList.remove('aktif');document.getElementById('listMateriSection').classList.remove('tersembunyi');materiAktifId=null;dataMateriAktif=null;baganAktif=null;updateUrlState(null,null);muatDaftarMateriSiswa();}

function extractYoutubeId(u){if(!u)return null;const m=u.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);return m?m[1]:null;}
function extractGoogleDriveId(u){if(!u||!/drive\.google\.com/.test(u))return null;let m=u.match(/\/d\/([a-zA-Z0-9_-]{15,})/);if(m)return m[1];m=u.match(/[?&]id=([a-zA-Z0-9_-]{15,})/);return m?m[1]:null;}
function ambilEkstensi(p){if(!p)return'';const b=p.split('?')[0].split('#')[0],x=b.split('.');return x.length>1?x.pop().toLowerCase():'';}
function urlAbsolut(u){try{return new URL(u,window.location.href).href;}catch(e){return u;}}
function urlLengkap(p){
  if(!p) return '';
  if(/^https?:\/\//i.test(p)) return p;
  const base=(window.FOLDER_GURU_BK||'../guru/').replace(/\/?$/,'/');
  // Jika path sudah berisi uploads/... langsung gabung
  if(p.indexOf('uploads/')===0 || p.indexOf('/uploads/')>=0) return base+p.replace(/^\//,'');
  // Jika hanya nama file, coba folder gambar lalu ppt
  const nama=p.split('/').pop();
  if(p.indexOf('/')===-1){
    // default: coba gambar dulu (akan di-fallback onerror)
    return base+'uploads/bimbingan_klasikal/gambar/'+nama;
  }
  return base+p.replace(/^\//,'');
}
/** Daftar kandidat URL untuk gambar/file (path di DB bisa relatif/hanya nama file) */
function kandidatUrlMedia(path){
  if(!path) return [];
  if(/^https?:\/\//i.test(path)) return [path];
  const base=(window.FOLDER_GURU_BK||'../guru/').replace(/\/?$/,'/');
  const nama=path.split('/').pop().split('?')[0];
  const list=[];
  const add=u=>{ if(u && list.indexOf(u)<0) list.push(u); };
  add(base+path.replace(/^\//,''));
  add(base+'uploads/bimbingan_klasikal/gambar/'+nama);
  add(base+'uploads/bimbingan_klasikal/ppt/'+nama);
  add(base+'uploads/bimbingan_klasikal/arsip/'+nama);
  add('../guru/uploads/bimbingan_klasikal/gambar/'+nama);
  add('../guru/uploads/bimbingan_klasikal/ppt/'+nama);
  add(path);
  return list;
}
const IKON_JENIS={pdf:{icon:'fa-file-pdf',warna:'red'},ppt:{icon:'fa-file-powerpoint',warna:'orange'},pptx:{icon:'fa-file-powerpoint',warna:'orange'},doc:{icon:'fa-file-word',warna:'blue'},docx:{icon:'fa-file-word',warna:'blue'}};
function kartuBukaFile(url,nama,ket){const ext=ambilEkstensi(nama),m=IKON_JENIS[ext]||{icon:'fa-file-arrow-down',warna:'gray'};return '<div class="bg-'+m.warna+'-50 border border-'+m.warna+'-200 rounded-xl p-4 mb-5"><div class="flex items-center gap-3"><i class="fas '+m.icon+' text-'+m.warna+'-600 text-2xl"></i><div class="min-w-0 flex-1"><p class="text-sm font-bold truncate">'+escapeHtml(nama)+'</p><p class="text-xs mt-0.5">'+escapeHtml(ket||'Unduh file')+'</p></div><a href="'+url+'" target="_blank" download class="bg-'+m.warna+'-600 text-white text-xs font-semibold px-3 py-2 rounded-lg"><i class="fas fa-download"></i> Unduh</a></div></div>';}
function embedGoogleDrive(id,url,label){return '<div class="flex justify-between mb-2"><span class="text-xs font-semibold"><i class="fas fa-eye mr-1"></i>Pratinjau '+escapeHtml(label)+'</span><a href="'+url+'" target="_blank" class="text-xs bg-gray-100 px-3 py-1.5 rounded-lg">Buka / Unduh</a></div><div class="doc-embed-wrap mb-2"><iframe src="https://drive.google.com/file/d/'+id+'/preview" loading="lazy"></iframe></div>';}
function embedPdf(url){return '<div class="flex justify-between mb-2"><span class="text-xs font-semibold">Pratinjau PDF</span><a href="'+url+'" target="_blank" class="text-xs bg-gray-100 px-3 py-1.5 rounded-lg">Unduh</a></div><div class="doc-embed-wrap mb-2"><iframe src="'+url+'#toolbar=1" loading="lazy"></iframe></div>';}
function embedOffice(url,ext){
  const nama=url.split('/').pop().split('?')[0];
  const abs=urlAbsolut(url);
  const isLocal=/^(localhost|127\.|192\.168\.)/.test(location.hostname);
  // Selalu tampilkan area pratinjau. Office Online butuh URL publik;
  // di localhost iframe mungkin kosong — tetap sediakan tombol buka.
  const officeEmbed='https://view.officeapps.live.com/op/embed.aspx?src='+encodeURIComponent(abs);
  const officeView='https://view.officeapps.live.com/op/view.aspx?src='+encodeURIComponent(abs);
  let ifr='';
  if(!isLocal){
    ifr='<div class="doc-embed-wrap mb-2"><iframe src="'+officeEmbed+'" loading="lazy" allowfullscreen></iframe></div>';
  } else {
    // Localhost: coba tampilkan iframe (sering gagal karena URL tidak publik) + petunjuk
    ifr='<div class="doc-embed-wrap mb-2 bg-gray-50 flex flex-col items-center justify-center text-center p-6" style="min-height:280px">'
      +'<i class="fas fa-file-powerpoint text-orange-400 text-5xl mb-3"></i>'
      +'<p class="text-sm font-semibold text-gray-700 mb-1">Pratinjau PPT</p>'
      +'<p class="text-xs text-gray-500 mb-4 max-w-md">Di localhost, preview online biasanya tidak tersedia. Gunakan tombol di bawah untuk membuka/mengunduh. Di hosting (URL publik), pratinjau otomatis muncul.</p>'
      +'<div class="flex flex-wrap gap-2 justify-center">'
      +'<a href="'+url+'" target="_blank" class="bg-orange-600 text-white text-xs font-semibold px-4 py-2 rounded-lg"><i class="fas fa-external-link-alt mr-1"></i> Buka File</a>'
      +'<a href="'+officeView+'" target="_blank" class="border border-orange-300 text-orange-700 text-xs font-semibold px-4 py-2 rounded-lg">Coba Office Online</a>'
      +'</div></div>';
  }
  return '<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4">'
    +'<div class="flex flex-wrap justify-between gap-3 mb-2">'
    +'<div class="flex items-center gap-2"><i class="fas fa-file-powerpoint text-orange-600 text-2xl"></i>'
    +'<div><p class="text-sm font-bold">'+escapeHtml(nama)+'</p><p class="text-xs text-gray-500">PPT '+ext.toUpperCase()+'</p></div></div>'
    +'<div class="flex flex-wrap gap-2">'
    +'<a href="'+url+'" download class="bg-orange-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg">Unduh PPT</a>'
    +'<a href="'+officeView+'" target="_blank" class="border border-orange-300 text-orange-700 text-xs font-semibold px-3 py-1.5 rounded-lg">Office Online</a>'
    +'</div></div>'+ifr+'</div>';
}
function renderDokumenSlide(path){
  const kandidat=kandidatUrlMedia(path);
  const url=kandidat[0]||urlLengkap(path);
  const nama=(path||'').split('/').pop().split('?')[0];
  const did=extractGoogleDriveId(path);
  if(did) return embedGoogleDrive(did,url,'dokumen');
  const ext=ambilEkstensi(path||nama);
  if(ext==='pdf') return embedPdf(url);
  if(['ppt','pptx','doc','docx'].includes(ext)) return embedOffice(url,ext);
  return kartuBukaFile(url,nama,'Unduh file');
}
window.gambarSlideGagalDimuat=function(img,urlAwal){
  // Coba kandidat URL berikutnya dari data-kandidat
  let list=[];
  try{ list=JSON.parse(img.getAttribute('data-kandidat')||'[]'); }catch(e){ list=[]; }
  const idx=parseInt(img.getAttribute('data-idx')||'0',10)+1;
  if(idx<list.length){
    img.setAttribute('data-idx',String(idx));
    img.src=list[idx];
    return;
  }
  const n=(list[0]||urlAwal||'').split('/').pop().split('?')[0];
  const d=document.createElement('div');
  d.innerHTML=kartuBukaFile(list[0]||urlAwal,n,'Gambar gagal dimuat — file mungkin belum diunggah atau path salah');
  img.replaceWith(d.firstElementChild);
};
function renderGambarSlide(path){
  const did=extractGoogleDriveId(path);
  if(did) return embedGoogleDrive(did,urlLengkap(path),'gambar');
  const list=kandidatUrlMedia(path);
  const url=list[0]||'';
  const kandidatJson=JSON.stringify(list).replace(/'/g,"&#39;");
  return '<img src="'+url+'" data-kandidat=\''+kandidatJson+'\' data-idx="0" class="w-full rounded-xl border mb-5" alt="Gambar materi" onerror="gambarSlideGagalDimuat(this,\''+String(url).replace(/'/g,"\\'")+'\')">';
}
function updateUrlState(id,b){if(id)location.hash='materi-'+id+(b?'-bagan-'+b:'');else if(location.hash.startsWith('#materi-'))history.replaceState(null,document.title,location.pathname+location.search);}
function periksaHashUrlSaatLoad(){const m=location.hash.match(/^#materi-(\d+)/);if(m){const id=parseInt(m[1],10);if(id>0)bukaMateriSiswa(id);}}
window.addEventListener('popstate',function(){if(!location.hash.match(/^#materi-/)&&materiAktifId!==null){hentikanSemuaTimer();document.getElementById('viewerMateri').classList.remove('aktif');document.getElementById('listMateriSection').classList.remove('tersembunyi');materiAktifId=null;muatDaftarMateriSiswa();}});
// Timer TIDAK di-pause saat pindah tab. Wall-clock tetap akurat.
// Saat tab kembali terlihat, cukup hitung ulang elapsed + update UI.
document.addEventListener('visibilitychange',function(){
  if(document.hidden) return; // jangan pause
  if(!materiAktifId || !baganAktif || !dataMateriAktif) return;
  if(dataMateriAktif.bagan[baganAktif].status_selesai==1) return;
  // Re-hitung dari anchor lokal (cepat) lalu re-sync server di background
  hitungElapsedWallClock(baganAktif);
  updateTimerUI();
  if(timerState[baganAktif] && !timerState[baganAktif].running){
    mulaiTimerBaganAktif();
  }
});
document.addEventListener('DOMContentLoaded',function(){muatDaftarMateriSiswa();periksaHashUrlSaatLoad();});
</script>
</body>
</html>