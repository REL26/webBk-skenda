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

function hitungSemesterTahunAjaran($bulan, $tahun) {
    if ($bulan >= 7 && $bulan <= 12) {
        return ['semester' => 'Ganjil', 'tahun_pelajaran' => $tahun . '/' . ($tahun + 1)];
    }
    return ['semester' => 'Genap', 'tahun_pelajaran' => ($tahun - 1) . '/' . $tahun];
}

const BIDANG_LAPORAN_BK = ['Pribadi', 'Belajar', 'Sosial', 'Karier'];

// Template deskripsi "Bentuk Kegiatan" Bagian III, dipetakan dari jenis layanan.
// Sengaja berupa PEMETAAN TETAP (bukan field form baru) supaya guru tidak perlu
// mengisi apa pun tambahan -- cukup buat laporan layanan seperti biasa, lalu
// Bentuk Kegiatan otomatis terisi deskripsi aktivitas yang sesuai. Kalau nanti
// ada jenis layanan baru atau template perlu direvisi, cukup tambah/ubah baris
// di peta ini saja.
const TEMPLATE_BENTUK_KEGIATAN_BK = [
    'Konseling Individu'   => 'Konseling tatap muka dan pendampingan siswa',
    'Konseling Kelompok'   => 'Diskusi kelompok dan pendampingan siswa',
    'Bimbingan Kelompok'   => 'Diskusi kelompok dan pendampingan siswa',
    'Home Visit'           => 'Kunjungan dan pendampingan siswa di rumah',
    'Panggilan Ortu'       => 'Pertemuan dan koordinasi bersama orang tua/wali',
];

function templateBentukKegiatanBK($jenisLayanan) {
    return TEMPLATE_BENTUK_KEGIATAN_BK[$jenisLayanan] ?? '';
}

function normalisasiBidangBK($rawBidangLayanan) {
    static $peta = ['Pribadi' => 'Pribadi', 'Belajar' => 'Belajar', 'Sosial' => 'Sosial', 'Karir' => 'Karier', 'Karier' => 'Karier'];
    $hasil = [];
    foreach (explode(',', (string) $rawBidangLayanan) as $b) {
        $b = trim($b);
        if ($b === '') continue;
        $norm = $peta[$b] ?? null;
        if ($norm !== null && !in_array($norm, $hasil, true)) {
            $hasil[] = $norm;
        }
    }
    return $hasil;
}

function getRekapOtomatisBK($koneksi, $awal, $akhir) {
    $modul = [
        [
            'label' => 'Konseling Individu',
            'sql' => "SELECT ki.tanggal_pelaksanaan AS tgl, 'Konseling Individu' AS jenis, ki.nama_guru AS guru,
                        COALESCE(NULLIF(s.kelas,''),'-') AS kelas, COALESCE(NULLIF(s.jurusan,''),'-') AS jurusan, 1 AS jml
                      FROM konseling_individu ki
                      LEFT JOIN siswa s ON s.id_siswa = ki.id_siswa
                      WHERE ki.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'",
            'groupby' => '',
        ],
        [
            'label' => 'Konseling & Bimbingan Kelompok',
            'sql' => "SELECT k.tanggal_pelaksanaan AS tgl, k.jenis_layanan AS jenis, k.nama_guru AS guru,
                        COALESCE(NULLIF(s.kelas,''),'-') AS kelas, COALESCE(NULLIF(s.jurusan,''),'-') AS jurusan, COUNT(*) AS jml
                      FROM kelompok k
                      JOIN detail_kelompok dk ON dk.id_kelompok = k.id_kelompok
                      JOIN siswa s ON s.id_siswa = dk.id_siswa
                      WHERE k.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'
                        AND k.jenis_layanan IN ('Konseling Kelompok','Bimbingan Kelompok')",
            'groupby' => 'GROUP BY k.id_kelompok, k.tanggal_pelaksanaan, k.jenis_layanan, k.nama_guru, s.kelas, s.jurusan',
        ],
        [
            'label' => 'Home Visit',
            'sql' => "SELECT hv.hari_tanggal AS tgl, 'Home Visit' AS jenis, hv.nama_petugas AS guru,
                        COALESCE(NULLIF(s.kelas,''), NULLIF(hv.kelas,''), '-') AS kelas,
                        COALESCE(NULLIF(s.jurusan,''), NULLIF(hv.jurusan,''), '-') AS jurusan, 1 AS jml
                      FROM home_visit hv
                      LEFT JOIN siswa s ON s.nis = hv.nis
                      WHERE hv.hari_tanggal BETWEEN '$awal' AND '$akhir'",
            'groupby' => '',
        ],
        [
            'label' => 'Panggilan Ortu',
            'sql' => "SELECT ko.tanggal_pemanggilan AS tgl, 'Panggilan Ortu' AS jenis, ko.nama_guru_bk AS guru,
                        COALESCE(NULLIF(s.kelas,''), NULLIF(ko.kelas,''), '-') AS kelas,
                        COALESCE(NULLIF(s.jurusan,''), NULLIF(ko.jurusan,''), '-') AS jurusan, 1 AS jml
                      FROM konsultasi_ortu ko
                      LEFT JOIN siswa s ON s.nis = ko.nis
                      WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'",
            'groupby' => '',
        ],
    ];

    $grup = [];
    foreach ($modul as $m) {
        $sql = $m['sql'];
        if (!empty($m['groupby'])) {
            $sql .= ' ' . $m['groupby'];
        }
        $res = mysqli_query($koneksi, $sql);
        if (!$res) {
            error_log('Rekap otomatis BK gagal untuk modul "' . $m['label'] . '": ' . mysqli_error($koneksi));
            continue;
        }
        while ($r = mysqli_fetch_assoc($res)) {
            $guru = trim((string) $r['guru']);
            $key = $r['tgl'] . '|' . $r['jenis'] . '|' . $guru;
            if (!isset($grup[$key])) {
                $grup[$key] = ['tanggal' => $r['tgl'], 'jenis_layanan' => $r['jenis'], 'guru' => $guru, 'sasaran' => [], 'jumlah' => 0];
            }
            $label = trim($r['kelas'] . ' ' . $r['jurusan']);
            if ($label === '') $label = '-';
            if (!in_array($label, $grup[$key]['sasaran'], true)) {
                $grup[$key]['sasaran'][] = $label;
            }
            $grup[$key]['jumlah'] += (int) $r['jml'];
        }
    }

    ksort($grup);
    $hasil = [];
    foreach ($grup as $key => $g) {
        $hasil[] = [
            'sumber_key'      => 'keg-' . md5($key),
            'jenis_layanan'   => $g['jenis_layanan'],
            'sasaran_kelas'   => implode(', ', $g['sasaran']),
            'jumlah_siswa'    => (string) $g['jumlah'],
            'waktu'           => $g['tanggal'],
            'bentuk_kegiatan' => templateBentukKegiatanBK($g['jenis_layanan']),
            'keterangan'      => 'Terlaksana',
            'nama_guru'       => $g['guru'],
        ];
    }
    return $hasil;
}

// Menghasilkan SATU BARIS PER PERMASALAHAN (bukan gabungan per-bidang), supaya
// tiap permasalahan bisa punya Jumlah Siswa & Tindak Awal sendiri, dan bisa
// disaring per Guru BK di sisi tampilan tanpa perlu query ulang ke server.
// TIDAK ada filter guru di sini secara sengaja -- filter Guru BK sekarang murni
// tampilan (client-side), supaya data asli tidak pernah berubah/hilang hanya
// karena guru mengganti-ganti pilihan filter.
function getMasalahOtomatisBK($koneksi, $awal, $akhir) {
    $modul = [
        [
            'label' => 'Konseling Individu',
            'sql' => "SELECT ki.id_konseling AS id_sumber, ki.nama_guru AS guru, ki.bidang_layanan AS bidang, ki.gejala_nampak AS teks
                      FROM konseling_individu ki
                      WHERE ki.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'
                      ORDER BY ki.id_konseling ASC",
        ],
        [
            'label' => 'Konseling/Bimbingan Kelompok',
            'sql' => "SELECT k.id_kelompok AS id_sumber, k.nama_guru AS guru, k.bidang_layanan AS bidang, k.gejala AS teks, k.jenis_layanan AS jenis
                      FROM kelompok k
                      WHERE k.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'
                        AND k.jenis_layanan IN ('Konseling Kelompok','Bimbingan Kelompok')
                      ORDER BY k.id_kelompok ASC",
        ],
        [
            'label' => 'Home Visit',
            'sql' => "SELECT hv.id_visit AS id_sumber, hv.nama_petugas AS guru, hv.bidang_layanan AS bidang, hv.masalah AS teks
                      FROM home_visit hv
                      WHERE hv.hari_tanggal BETWEEN '$awal' AND '$akhir'
                      ORDER BY hv.id_visit ASC",
        ],
        [
            'label' => 'Panggilan Ortu',
            'sql' => "SELECT ko.id_konsultasi AS id_sumber, ko.nama_guru_bk AS guru, ko.bidang_layanan AS bidang, ko.permasalahan AS teks
                      FROM konsultasi_ortu ko
                      WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'
                      ORDER BY ko.id_konsultasi ASC",
        ],
    ];

    // Kelompokkan per bidang dulu (urutan bidang tetap: Pribadi, Belajar, Sosial, Karier).
    $per_bidang = [];
    foreach (BIDANG_LAPORAN_BK as $b) { $per_bidang[$b] = []; }

    foreach ($modul as $m) {
        $res = mysqli_query($koneksi, $m['sql']);
        if (!$res) {
            error_log('Rekap masalah otomatis BK gagal untuk modul "' . $m['label'] . '": ' . mysqli_error($koneksi));
            continue;
        }
        while ($r = mysqli_fetch_assoc($res)) {
            $bidangNorm = normalisasiBidangBK($r['bidang'] ?? '');
            if (empty($bidangNorm)) continue;

            $teksRaw = (string) ($r['teks'] ?? '');
            // Kalau satu laporan memuat beberapa permasalahan/gejala (dipisah baris
            // baru), pecah jadi baris Bagian IV yang terpisah -- sesuai poin 2.
            $barisTeks = preg_split('/\r\n|\r|\n/', $teksRaw);
            $barisTeks = array_values(array_filter(array_map('trim', $barisTeks), fn($x) => $x !== ''));
            if (empty($barisTeks)) continue;

            $jenisSumber = !empty($r['jenis']) ? $r['jenis'] : $m['label'];
            $guru = trim((string) ($r['guru'] ?? ''));
            $idSumber = (string) ($r['id_sumber'] ?? '');

            foreach ($bidangNorm as $b) {
                foreach ($barisTeks as $iBaris => $teksBaris) {
                    // Kunci stabil per-bidang: modul + id baris sumber + indeks baris
                    // teks + bidang. Dipakai Bagian IV (satu baris tampilan per bidang).
                    $kunciMentah = $m['label'] . '|' . $idSumber . '|' . $iBaris . '|' . $b;
                    // Kunci "asal" TANPA bidang: mengidentifikasi satu kegiatan/laporan
                    // + satu kalimat permasalahan yang sama, terlepas dari berapa
                    // bidang yang dipilih guru untuknya. Dipakai Bagian V supaya satu
                    // kegiatan yang sama tidak dobel hanya karena dipilih >1 bidang.
                    $kunciAsal = $m['label'] . '|' . $idSumber . '|' . $iBaris;
                    $per_bidang[$b][] = [
                        'sumber_key'        => 'masalah-' . md5($kunciMentah),
                        'sumber_asal'       => 'asal-' . md5($kunciAsal),
                        'bidang'            => $b,
                        'masalah'           => $teksBaris,
                        'jml_siswa_masalah' => '',
                        'tindak_awal'       => '',
                        'nama_guru'         => $guru,
                        'jenis_sumber'      => $jenisSumber,
                    ];
                }
            }
        }
    }

    $hasil = [];
    foreach (BIDANG_LAPORAN_BK as $b) {
        foreach ($per_bidang[$b] as $baris) {
            $hasil[] = $baris;
        }
    }
    return $hasil;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'cek_laporan_bulan') {
        $bulan = (int) ($_POST['bulan'] ?? 0);
        $tahun = (int) ($_POST['tahun'] ?? 0);
        if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
            echo json_encode(['success' => false, 'message' => 'Bulan/tahun tidak valid.']);
            exit;
        }
        $info = hitungSemesterTahunAjaran($bulan, $tahun);
        $tp = mysqli_real_escape_string($koneksi, $info['tahun_pelajaran']);
        $q = mysqli_query($koneksi, "SELECT id_laporan, status FROM laporan_bk WHERE bulan = $bulan AND tahun_pelajaran = '$tp' LIMIT 1");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        echo json_encode([
            'success'         => true,
            'ada'             => (bool) $row,
            'id_laporan'      => $row['id_laporan'] ?? null,
            'status'          => $row['status'] ?? null,
            'semester'        => $info['semester'],
            'tahun_pelajaran' => $info['tahun_pelajaran'],
        ]);
        exit;
    }

    if ($action === 'get_rekap_otomatis') {
        $bulan = (int) ($_POST['bulan'] ?? 0);
        $tahun = (int) ($_POST['tahun'] ?? 0);
        if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
            echo json_encode(['success' => false, 'message' => 'Bulan/tahun tidak valid.']);
            exit;
        }
        $awal  = sprintf('%04d-%02d-01', $tahun, $bulan);
        $akhir = date('Y-m-t', strtotime($awal));
        // Catatan: sengaja TIDAK memfilter guru di sini. Data yang dikirim ke
        // client SELALU lengkap (semua guru); Filter Guru BK di halaman hanya
        // menyaring TAMPILAN di browser, supaya data asli tidak pernah berubah
        // atau hilang akibat gonta-ganti filter.
        $rekap   = getRekapOtomatisBK($koneksi, $awal, $akhir);
        $masalah = getMasalahOtomatisBK($koneksi, $awal, $akhir);
        echo json_encode(['success' => true, 'rekap' => $rekap, 'masalah' => $masalah]);
        exit;
    }

    if ($action === 'simpan') {
        $id_laporan      = isset($_POST['id_laporan']) ? (int) $_POST['id_laporan'] : 0;
        $nama_dokumen    = mysqli_real_escape_string($koneksi, $_POST['nama_dokumen'] ?? '');
        $bulan           = (int) ($_POST['bulan'] ?? 0);
        $tahun           = (int) ($_POST['tahun'] ?? 0);
        $sasaran         = mysqli_real_escape_string($koneksi, $_POST['sasaran'] ?? '');
        $koordinator_nip = mysqli_real_escape_string($koneksi, $_POST['koordinator_nip'] ?? '');
        $nama_koordinator = mysqli_real_escape_string($koneksi, $_POST['nama_koordinator'] ?? '');
        $nama_guru_bk    = mysqli_real_escape_string($koneksi, $_POST['nama_guru_bk'] ?? '');
        $nip_guru_bk     = mysqli_real_escape_string($koneksi, $_POST['nip_guru_bk'] ?? '');

        $dokumentasi_raw = $_POST['dokumentasi_json'] ?? '[]';
        if (json_decode($dokumentasi_raw) === null) {
            $dokumentasi_raw = '[]';
        }
        $dokumentasi_foto = mysqli_real_escape_string($koneksi, $dokumentasi_raw);

        if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
            echo json_encode(['success' => false, 'message' => 'Bulan laporan belum dipilih dengan benar.']);
            exit;
        }
        $info            = hitungSemesterTahunAjaran($bulan, $tahun);
        $semester        = $info['semester'];
        $tahun_pelajaran = mysqli_real_escape_string($koneksi, $info['tahun_pelajaran']);
        $tanggal         = mysqli_real_escape_string($koneksi, date('Y-m-d'));

        $rekap_raw   = $_POST['rekap_json'] ?? '[]';
        $masalah_raw = $_POST['masalah_json'] ?? '[]';
        $tindak_raw  = $_POST['tindak_json'] ?? '[]';

        foreach (['rekap_raw', 'masalah_raw', 'tindak_raw'] as $var) {
            if (json_decode($$var) === null) {
                $$var = '[]';
            }
        }

        $materi_rekap  = mysqli_real_escape_string($koneksi, $rekap_raw);
        $masalah       = mysqli_real_escape_string($koneksi, $masalah_raw);
        $tindak_lanjut = mysqli_real_escape_string($koneksi, $tindak_raw);

        if ($nama_dokumen === '') {
            $namaBulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $nama_dokumen = 'Laporan Bulanan BK - ' . $namaBulanIndo[$bulan] . ' ' . $tahun;
        }
        $nama_dokumen = mysqli_real_escape_string($koneksi, $nama_dokumen);

        if ($id_laporan > 0) {
            $cek = mysqli_query($koneksi, "SELECT status, id_guru FROM laporan_bk WHERE id_laporan = $id_laporan");
            $row = $cek ? mysqli_fetch_assoc($cek) : null;

            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
                exit;
            }
            if ((int)$row['id_guru'] !== $id_guru_login) {
                echo json_encode(['success' => false, 'message' => 'Anda tidak berhak mengedit dokumen ini.']);
                exit;
            }
            if ($row['status'] === 'final') {
                echo json_encode(['success' => false, 'message' => 'Laporan ini sudah dikunci dan tidak bisa diedit langsung. Buka kuncinya dulu ya sebelum mengedit.']);
                exit;
            }

            $query = "UPDATE laporan_bk SET
                        nama_dokumen = '$nama_dokumen',
                        semester = '$semester',
                        tahun_pelajaran = '$tahun_pelajaran',
                        bulan = $bulan,
                        sasaran = '$sasaran',
                        tanggal = '$tanggal',
                        koordinator_nip = '$koordinator_nip',
                        nama_koordinator = '$nama_koordinator',
                        nama_guru_bk = '$nama_guru_bk',
                        nip_guru_bk = '$nip_guru_bk',
                        dokumentasi_foto = '$dokumentasi_foto',
                        materi_rekap = '$materi_rekap',
                        masalah = '$masalah',
                        tindak_lanjut = '$tindak_lanjut'
                      WHERE id_laporan = $id_laporan";
            $aksi_log = 'diedit';
        } else {
            $query = "INSERT INTO laporan_bk
                        (nama_dokumen, semester, tahun_pelajaran, bulan, sasaran, tanggal, koordinator_nip, nama_koordinator, nama_guru_bk, nip_guru_bk, dokumentasi_foto, id_guru, materi_rekap, masalah, tindak_lanjut, status)
                      VALUES
                        ('$nama_dokumen', '$semester', '$tahun_pelajaran', $bulan, '$sasaran', '$tanggal', '$koordinator_nip', '$nama_koordinator', '$nama_guru_bk', '$nip_guru_bk', '$dokumentasi_foto', $id_guru_login, '$materi_rekap', '$masalah', '$tindak_lanjut', 'draft')";
            $aksi_log = 'dibuat';
        }

        if (mysqli_query($koneksi, $query)) {
            if ($id_laporan == 0) {
                $id_laporan = mysqli_insert_id($koneksi);
            }
            mysqli_query($koneksi, "INSERT INTO riwayat_laporan (id_laporan, aksi, id_guru) VALUES ($id_laporan, '$aksi_log', $id_guru_login)");
            echo json_encode(['success' => true, 'id_laporan' => $id_laporan, 'message' => 'Laporan berhasil disimpan. Anda masih bisa mengeditnya kapan saja.']);
        } else {
            $pesan = (mysqli_errno($koneksi) === 1062)
                ? 'Laporan untuk bulan tersebut sudah ada. Silakan buka laporan yang sudah ada untuk mengeditnya.'
                : 'Gagal menyimpan: ' . mysqli_error($koneksi);
            echo json_encode(['success' => false, 'message' => $pesan]);
        }
        exit;
    }

    if ($action === 'finalisasi') {
        $id_laporan = (int) ($_POST['id_laporan'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM laporan_bk WHERE id_laporan = $id_laporan");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;

        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan atau bukan milik Anda.']);
            exit;
        }

        $ok = mysqli_query($koneksi, "UPDATE laporan_bk SET status = 'final', finalized_at = NOW() WHERE id_laporan = $id_laporan");
        if ($ok) {
            mysqli_query($koneksi, "INSERT INTO riwayat_laporan (id_laporan, aksi, id_guru) VALUES ($id_laporan, 'difinalisasi', $id_guru_login)");
            echo json_encode(['success' => true, 'message' => 'Dokumen berhasil difinalisasi.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal finalisasi: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    if ($action === 'buka_draft') {
        $id_laporan = (int) ($_POST['id_laporan'] ?? 0);
        $cek = mysqli_query($koneksi, "SELECT id_guru FROM laporan_bk WHERE id_laporan = $id_laporan");
        $row = $cek ? mysqli_fetch_assoc($cek) : null;

        if (!$row || (int)$row['id_guru'] !== $id_guru_login) {
            echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan atau bukan milik Anda.']);
            exit;
        }

        $ok = mysqli_query($koneksi, "UPDATE laporan_bk SET status = 'draft', finalized_at = NULL WHERE id_laporan = $id_laporan");
        if ($ok) {
            mysqli_query($koneksi, "INSERT INTO riwayat_laporan (id_laporan, aksi, id_guru) VALUES ($id_laporan, 'dibuka_ulang', $id_guru_login)");
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dibuka kembali dan siap diedit.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . mysqli_error($koneksi)]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
    exit;
}

$laporan = null;
$laporan_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($laporan_id > 0) {
    $q = mysqli_query($koneksi, "SELECT * FROM laporan_bk WHERE id_laporan = $laporan_id AND id_guru = $id_guru_login");
    $laporan = $q ? mysqli_fetch_assoc($q) : null;
    if (!$laporan) {
        $laporan_id = 0;
    }
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="Sistem Konseling Kelompok - SMKN 2 Banjarmasin"
    />
    <title class="no-print">Konseling Kelompok | Program BK | BK SMKN 2 Banjarmasin</title>
    <link
      rel="icon"
      type="image/png"
      href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
              @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

              * {
                  font-family: 'Inter', sans-serif;
                  margin: 0;
                  padding: 0;
                  box-sizing: border-box;
              }

              :root {
                  --primary: #0F3A3A;
                  --primary-dark: #0B2E2E;
                  --primary-light: #123E44;
                  --accent: #5FA8A1;
                  --accent-dark: #4C8E89;
                  --white: #FFFFFF;
                  --gray-50: #F9FAFB;
                  --gray-200: #E5E7EB;
                  --success: #4C8E89;
                  --warning: #5FA8A1;
                  --danger: #9B2C2C;
              }

              html {
                  overflow-y: scroll;
                  scroll-behavior: smooth;
              }
              #dokumentasi img {
        max-height: 180px;
        object-fit: cover;
      }

              body {
                  background: linear-gradient(135deg, #f5f7fa 0%, #e8eef2 100%);
                  min-height: 100vh;
                  max-width: 100%;
                  overflow-x: hidden;
              }

              .modal {
                  transition: opacity 0.3s ease, visibility 0.3s ease;
                  visibility: hidden;
                  opacity: 0;
              }

              .modal.open {
                  visibility: visible;
                  opacity: 1;
              }

              .data-table-report {
                  min-width: 800px;
              }

              .baris-tersembunyi-filter {
                  display: none !important;
              }

              .card-hover {
                  transition: all 0.3s ease;
              }

              .card-hover:hover {
                  transform: translateY(-4px);
                  box-shadow: 0 12px 24px rgba(0,0,0,0.15);
              }

              .btn-action {
                  transition: all 0.2s ease;
              }

              .btn-action:hover {
                  transform: scale(1.05);
              }

              .stat-card {
                  background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
                  backdrop-filter: blur(10px);
                  border: 1px solid rgba(47, 108, 110, 0.1);
              }

              .stat-card:hover {
                  transform: translateY(-2px);
                  box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
              }

              @keyframes slideIn {
                  from {
                      opacity: 0;
                      transform: translateY(20px);
                  }
                  to {
                      opacity: 1;
                      transform: translateY(0);
                  }
              }

              .animate-slide-in {
                  animation: slideIn 0.5s ease-out;
              }

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

                  .input{
                      margin-top: 20px;
                  }
              }

              @media (min-width: 768px) {
                  main {
                      margin-left: 260px;
                  }
              }

              .grid {
                  width: 100%;
                  box-sizing: border-box;
              }

              .grid > * {
                  overflow-x: hidden;
              }

              .primary-color { color: var(--primary); }
              .primary-bg { background-color: var(--primary-light); }
              .secondary-bg { background-color: #E6EEF0; }

              .print-value-proxy {
                display: none;
              }

              .sign-header {
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                min-height: 64px;
                margin-bottom: 0.5rem;
              }

              .sign-header .no-print {
                width: 100%;
              }

              .report-section {
                border: 1px solid var(--gray-200);
                border-radius: 0.75rem;
                overflow: hidden;
                background: var(--white);
              }

              .report-section > summary {
                list-style: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.25rem;
                background: var(--gray-50);
              }

              .report-section > summary::-webkit-details-marker {
                display: none;
              }

              .report-section > summary .section-title {
                display: flex;
                align-items: center;
                margin: 0;
              }

              .report-section > summary .chevron {
                transition: transform 0.2s ease;
                color: var(--primary);
              }

              .report-section[open] > summary .chevron {
                transform: rotate(180deg);
              }

              .report-section > .report-section-body {
                padding: 1.25rem;
              }
              
              .date-input-wrapper {
                position: relative;
                display: flex;
                align-items: center;
                width: 100%;
              }
              
              .date-input-wrapper input[type="date"] {
                width: 100%;
                padding: 6px 8px;
                border: none;
                background: transparent;
                font-size: 0.875rem;
                outline: none;
                cursor: pointer;
                min-height: 34px;
              }
              
              .date-input-wrapper input[type="date"]:hover {
                background-color: rgba(0,0,0,0.03);
              }
              
              .date-input-wrapper input[type="date"]:focus {
                background-color: rgba(0,0,0,0.05);
              }
              
              .date-input-wrapper input[type="date"]::-webkit-calendar-picker-indicator {
                cursor: pointer;
                padding: 4px;
                opacity: 0.6;
              }
              
              .date-input-wrapper input[type="date"]::-webkit-calendar-picker-indicator:hover {
                opacity: 1;
              }
              
              .table-scroll-wrapper {
                overflow-x: auto;
                width: 100%;
              }
              
              .col-no {
                width: 4%;
                min-width: 35px;
                max-width: 45px;
                white-space: nowrap;
              }
              
              .col-tanggal {
                width: 14%;
                min-width: 110px;
              }
              
              table {
                width: 100% !important;
                table-layout: fixed !important;
              }

              @media print {

  @page {
    size: A4 portrait;
    margin: 1.5cm 1.8cm;
  }

  html, body,
  div, p, span, a, li, ol, ul,
  table, thead, tbody, tr, th, td,
  input, select, label, h1, h2, h3, h4, h5, h6 {
    font-family: "Times New Roman", Times, serif !important;
    font-size: 10pt !important;
    color: #000000 !important;
    background-color: transparent !important;
    box-shadow: none !important;
    text-shadow: none !important;
    border-radius: 0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body {
    background: #ffffff !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  .no-print,
  button, .btn,
  aside, header, nav,
  #mobileMenu, #menuOverlay,
  input[type="file"],
  .overflow-x-auto > *:not(table) {
    display: none !important;
  }

  i[class*="fa-"] {
    display: none !important;
  }

  select {
    display: none !important;
  }

  main {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  #main-content {
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .bg-white.rounded-xl,
  .bg-white {
    padding: 0 !important;
    margin: 0 !important;
  }

  #main-content > div {
    padding: 0 !important;
    margin: 0 !important;
  }

  .mb-6, .mb-8, .mt-6, .mt-8, .p-6, .p-8, .md\:p-8 {
    margin: 0 !important;
    padding: 0 !important;
  }

  .mb-8 {
    margin-bottom: 10pt !important;
  }

  h3 {
    font-size: 11pt !important;
    font-weight: bold !important;
    margin-top: 14pt !important;
    margin-bottom: 5pt !important;
    text-transform: uppercase !important;
    letter-spacing: 0.2pt !important;
  }

  p {
    line-height: 1.6 !important;
    margin-bottom: 4pt !important;
    text-align: left !important;
  }

  .text-justify {
    text-align: justify !important;
  }

  ol, ul {
    padding-left: 16pt !important;
    margin-bottom: 6pt !important;
  }

  li {
    line-height: 1.6 !important;
    margin-bottom: 2pt !important;
  }

  .judul {
    display: block !important;
    margin-bottom: 12pt !important;
  }

  .judul h3:first-child {
    font-size: 13pt !important;
    text-align: center !important;
    margin-top: 0 !important;
    margin-bottom: 10pt !important;
    letter-spacing: 0.5pt !important;
    font-weight: bold !important;
  }

  .judul > h3:not(:first-child) {
    font-size: 11pt !important;
    text-align: left !important;
    margin-top: 10pt !important;
    margin-bottom: 4pt !important;
  }

  .judul p {
    line-height: 1.7 !important;
  }

  .overflow-x-auto {
    overflow: visible !important;
    width: 100% !important;
  }

  table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-bottom: 8pt !important;
    table-layout: fixed !important;
    page-break-inside: auto !important;
  }

  th, td {
    border: 1pt solid #000000 !important;
    padding: 4pt 5pt !important;
    vertical-align: middle !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
    line-height: 1.35 !important;
  }

  th {
    font-weight: bold !important;
    text-align: center !important;
    vertical-align: middle !important;
    background-color: #e8e8e8 !important;
    line-height: 1.25 !important;
    padding: 5pt 3pt !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  tr {
    page-break-inside: avoid !important;
  }

  /* Kolom "Aksi" (selalu kolom terakhir tiap tabel laporan) tidak dicetak. */
  th:last-child,
  td:last-child {
    display: none !important;
  }

  colgroup col:last-child {
    visibility: collapse !important;
  }

  /* ===================== BAGIAN III - REKAP KEGIATAN (7 kolom cetak) =====================
     No 4% | Jenis Layanan 13% | Sasaran 11% | Jumlah Siswa 8% | Waktu 10% | Bentuk Kegiatan 24% | Keterangan 30% */
  #rekapKegiatan {
    table-layout: fixed !important;
    width: 100% !important;
  }

  #rekapKegiatan th:nth-child(1), #rekapKegiatan td:nth-child(1) { width: 4%  !important; text-align: center !important; white-space: nowrap !important; }
  #rekapKegiatan th:nth-child(2), #rekapKegiatan td:nth-child(2) { width: 13% !important; text-align: left !important; }
  #rekapKegiatan th:nth-child(3), #rekapKegiatan td:nth-child(3) { width: 11% !important; text-align: center !important; }
  #rekapKegiatan th:nth-child(4), #rekapKegiatan td:nth-child(4) { width: 8%  !important; text-align: center !important; }
  #rekapKegiatan th:nth-child(5), #rekapKegiatan td:nth-child(5) { width: 10% !important; text-align: center !important; }
  #rekapKegiatan th:nth-child(6), #rekapKegiatan td:nth-child(6) { width: 24% !important; text-align: left !important; }
  #rekapKegiatan th:nth-child(7), #rekapKegiatan td:nth-child(7) { width: 30% !important; text-align: left !important; }

  /* ===================== BAGIAN IV - REKAP PERMASALAHAN (5 kolom cetak) =====================
     No 5% | Bidang 12% | Permasalahan 40% | Jumlah Siswa 10% | Tindak Awal 33%
     Kolom No & Bidang digabung (rowspan) per kelompok bidang -- lihat JS
     terapkanRowspanBidangIV(); ukurannya tetap konsisten di layar maupun cetak.
     PENTING: lebar sel BODY memakai selector class (.sel-no, .sel-bidang, dst),
     BUKAN td:nth-child -- karena baris ke-2/ke-3 dst dalam satu kelompok bidang
     TIDAK memiliki sel No/Bidang sama sekali di DOM (sungguhan dihapus, bukan
     cuma disembunyikan, supaya rowspan bekerja benar). Kalau pakai nth-child,
     baris pendek itu akan salah dapat gaya kolom No/Bidang -- itulah penyebab
     tabel Bagian IV terlihat tidak rata saat dicetak/PDF sebelumnya. */
  #rekapMasalah {
    table-layout: fixed !important;
    width: 100% !important;
  }

  #rekapMasalah th:nth-child(1) { width: 5%  !important; text-align: center !important; vertical-align: middle !important; }
  #rekapMasalah th:nth-child(2) { width: 12% !important; text-align: center !important; vertical-align: middle !important; }
  #rekapMasalah th:nth-child(3) { width: 40% !important; text-align: left !important; }
  #rekapMasalah th:nth-child(4) { width: 10% !important; text-align: center !important; }
  #rekapMasalah th:nth-child(5) { width: 33% !important; text-align: left !important; }

  #rekapMasalah td.sel-no          { width: 5%  !important; text-align: center !important; vertical-align: middle !important; }
  #rekapMasalah td.sel-bidang      { width: 12% !important; text-align: center !important; vertical-align: middle !important; }
  #rekapMasalah td.sel-permasalahan { width: 40% !important; text-align: left !important; vertical-align: top !important; }
  #rekapMasalah td.sel-jumlah      { width: 10% !important; text-align: center !important; vertical-align: top !important; }
  #rekapMasalah td.sel-tindak      { width: 33% !important; text-align: left !important; vertical-align: top !important; }

  /* Isi cell (lewat .print-value-proxy) ikut posisi vertikal sel induknya +
     tidak pernah keluar dari batas kolom, berapa pun panjang teksnya. */
  #rekapMasalah td.sel-permasalahan .print-value-proxy,
  #rekapMasalah td.sel-jumlah .print-value-proxy,
  #rekapMasalah td.sel-tindak .print-value-proxy {
    width: 100% !important;
    max-width: 100% !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
  }

  #rekapMasalah td.sel-no,
  #rekapMasalah td.sel-bidang {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
  }

  /* ===================== BAGIAN V - TINDAK LANJUT (6 kolom cetak) =====================
     No 4% | Permasalahan 28% | Layanan BK 16% | Tindak Lanjut 18% | Bulan 12% | Pihak Terkait 22% */
  #tindakLanjut {
    table-layout: fixed !important;
    width: 100% !important;
  }

  #tindakLanjut th:nth-child(1), #tindakLanjut td:nth-child(1) { width: 4%  !important; text-align: center !important; white-space: nowrap !important; }
  #tindakLanjut th:nth-child(2), #tindakLanjut td:nth-child(2) { width: 28% !important; text-align: left !important; }
  #tindakLanjut th:nth-child(3), #tindakLanjut td:nth-child(3) { width: 16% !important; text-align: left !important; }
  #tindakLanjut th:nth-child(4), #tindakLanjut td:nth-child(4) { width: 18% !important; text-align: left !important; }
  #tindakLanjut th:nth-child(5), #tindakLanjut td:nth-child(5) { width: 12% !important; text-align: center !important; }
  #tindakLanjut th:nth-child(6), #tindakLanjut td:nth-child(6) { width: 22% !important; text-align: left !important; }

  /* Teks isi (via .print-value-proxy, dibuat saat 'beforeprint' di JS) SELALU
     wrap ke baris berikutnya, tidak pernah terpotong -- berlaku merata di
     Bagian III, IV, dan V. */
  #rekapKegiatan th,
  #rekapKegiatan td,
  #rekapMasalah th,
  #rekapMasalah td,
  #tindakLanjut th,
  #tindakLanjut td {
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
  }

  input[type="text"],
  input[type="number"],
  input[type="date"] {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    display: block !important;
    -webkit-appearance: none !important;
    appearance: none !important;
  }

  input[type="number"]::-webkit-inner-spin-button,
  input[type="number"]::-webkit-outer-spin-button,
  input[type="date"]::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none !important;
  }

  input::placeholder {
    color: transparent !important;
  }

  /* Input/textarea/select asli disembunyikan saat cetak; teksnya sudah
     dipindah ke .print-value-proxy oleh event 'beforeprint' di JS supaya
     bisa wrap bebas tanpa batasan lebar input aslinya. */
  table input[type="text"],
  table input[type="number"],
  table input[type="date"],
  table select,
  table textarea {
    display: none !important;
  }

  .print-info-table,
  .print-info-table td {
    border: none !important;
    padding: 1.5pt 0 !important;
    font-size: 10pt !important;
  }

  .penutup-ttd-wrap {
    display: block !important;
    page-break-before: always !important;
    break-before: page !important;
  }

  .penutup-ttd-wrap .penutup-judul {
    page-break-before: avoid !important;
    break-before: avoid !important;
  }

  .penutup-heading {
    font-size: 11pt !important;
    text-align: left !important;
    text-transform: uppercase !important;
    margin-top: 14pt !important;
    margin-bottom: 3pt !important;
    letter-spacing: 0.2pt !important;
  }

  .penutup-judul p {
    margin-top: 0 !important;
  }

  [style*="page-break-after"] {
    page-break-after: avoid !important;
    break-after: avoid !important;
  }

  .signature-area {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 0 30pt !important;
    margin-top: 16pt !important;
    page-break-before: avoid !important;
    break-before: avoid !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    text-align: center !important;
  }

  .signature-area > div {
    display: block !important;
  }

  .signature-area p {
    line-height: 1.5 !important;
    margin-bottom: 2pt !important;
    text-align: center !important;
  }

  .sign-space {
    display: block !important;
    height: 48pt !important;
    margin: 0 !important;
  }

  span[id^="print"] {
    display: block !important;
    font-weight: bold !important;
    text-align: center !important;
    margin-bottom: 0 !important;
  }

  .signature-area > div > div.border-t {
    border-top: 1pt solid #000000 !important;
    width: 180pt !important;
    margin: 1pt auto 0 !important;
    display: block !important;
  }

  .sign-header {
    min-height: 34pt !important;
    justify-content: flex-end !important;
  }

  .report-section {
    border: none !important;
    border-radius: 0 !important;
  }

  .report-section > summary {
    display: block !important;
    list-style: none !important;
    padding: 0 !important;
    margin-bottom: 6pt !important;
    background: transparent !important;
    cursor: default !important;
  }

  .report-section > summary::-webkit-details-marker,
  .report-section > summary::marker {
    display: none !important;
  }

  .report-section > summary .section-title {
    display: flex !important;
    align-items: center !important;
    font-size: 12pt !important;
    font-weight: bold !important;
    text-transform: uppercase !important;
    margin: 0 !important;
    page-break-after: avoid !important;
    break-after: avoid !important;
  }

  .report-section > .report-section-body {
    display: block !important;
    padding: 0 !important;
  }

  .hidden {
    display: none !important;
  }

  .print\:block {
    display: block !important;
  }

  .hidden.print\:block {
    display: block !important;
  }

  #dokumentasi-section {
    page-break-before: always !important;
    break-before: always !important;
    padding: 0 !important;
    margin: 0 !important;
    display: block !important;
  }

  #dokumentasi-section > h3 {
    display: block !important;
    font-size: 12pt !important;
    text-align: center !important;
    text-transform: uppercase !important;
    margin-top: 0 !important;
    margin-bottom: 12pt !important;
    letter-spacing: 0.3pt !important;
  }

  #dokumentasi {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    grid-auto-rows: 178pt !important;
    gap: 6pt !important;
    width: 100% !important;
  }

  #dokumentasi > div {
    display: block !important;
    overflow: hidden !important;
  }

  #dokumentasi img {
    display: block !important;
    width: 100% !important;
    height: 178pt !important;
    object-fit: cover !important;
    border: 1pt solid #888888 !important;
  }

  #dokumentasi > div:nth-child(n+13) {
    display: none !important;
  }

  #dokumentasi button,
  #dokumentasi .no-print {
    display: none !important;
  }

  .print-value-proxy {
    display: block !important;
    font-family: "Times New Roman", Times, serif !important;
    font-size: 10pt !important;
    color: #000 !important;
    white-space: normal !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    word-break: break-word !important;
  }

  .table-scroll-wrapper {
    overflow: visible !important;
    overflow-x: visible !important;
    overflow-y: visible !important;
  }

  #rekapMasalah {
    overflow: visible !important;
  }

  
  .print-hide {
    display: none !important;
  }

  .print-hide-nip, #nipKoordinator, #nipGuruBK {
    display: none !important;
  }

  #rekapKegiatan td:nth-child(3) {
    text-align: center !important;
    vertical-align: middle !important;
  }
}
    </style>
  </head>
  <body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">
   <?php include __DIR__ . '/partials/sidebar.php'; ?>
      <main class="flex-grow p-4 md:p-8">
        <div class="no-print mb-6">
          <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-clipboard-list primary-color mr-2"></i> Laporan BK
          </h1>
          <p class="text-sm text-gray-600">
            Buat dan kelola Laporan Bimbingan dan Konseling
          </p>
        </div>
    <div id="main-content">
        <div class="bg-white rounded-xl shadow-md p-6 md:p-8">
          <div class="judul hidden print:block mb-6">
            <h3 class="text-xl font-bold mb-4">Bimbingan dan Konseling (BK)</h3>
            <div class="print-hide">
              <p class="text-sm mb-2">
                Sekolah : SMK Negeri 2 Banjarmasin<br />
                Alamat Sekolah : Jl. Brigjen Hasan Basri No. 6 Banjarmasin<br />
                Bulan / Tahun : <?php
                  $bulan_list = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus','September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'];
                  echo $bulan_list[date('F')] . ' ' . date('Y');
                ?>
              </p>
              <p class="text-sm mb-4">Disusun oleh:<br />Guru BK / Konselor</p>
            </div>

            <?php if ($laporan): ?>
            <table class="print-info-table" style="width:100%; margin-bottom:10pt; border-collapse:collapse;">
              <tr>
                <td style="padding:2pt 0; font-weight:bold; text-align:center;"><?php echo htmlspecialchars($laporan['nama_dokumen']); ?></td>
              </tr>
              <tr>
                <td style="padding:2pt 0; text-align:center;"><?php echo htmlspecialchars($laporan['semester']); ?> <?php echo htmlspecialchars($laporan['tahun_pelajaran']); ?></td>
              </tr>
              <tr>
                <td style="padding:2pt 0; text-align:center;"><?php echo htmlspecialchars($laporan['sasaran']); ?></td>
              </tr>
              <tr>
                <td style="padding:2pt 0; text-align:center;"><?php echo $laporan['tanggal'] ? date('d F Y', strtotime($laporan['tanggal'])) : '-'; ?></td>
              </tr>
            </table>
            <?php endif; ?>

            <h3 class="text-lg font-bold mt-6 mb-2">I. PENDAHULUAN</h3>
            <p class="text-sm text-justify mb-4">
              Laporan Bimbingan dan Konseling (BK) ini disusun sebagai
              bentuk pertanggungjawaban pelaksanaan layanan BK di SMK Negeri 2
              Banjarmasin selama bulan <?php echo $bulan_list[date('F')] . ' ' . date('Y'); ?>. Laporan ini memuat kegiatan
              layanan BK, permasalahan peserta didik, serta tindak lanjut yang
              telah dan akan dilakukan.
            </p>

            <h3 class="text-lg font-bold mb-2">II. TUJUAN</h3>
            <ol class="text-sm mb-4 list-decimal list-inside">
              <li>
                Mendokumentasikan seluruh kegiatan layanan BK yang telah
                dilaksanakan.
              </li>
              <li>Mengetahui perkembangan dan permasalahan peserta didik.</li>
              <li>
                Menjadi bahan evaluasi serta dasar penyusunan tindak lanjut
                layanan BK berikutnya.
              </li>
            </ol>
          </div>

          <input type="hidden" id="idLaporan" value="<?php echo (int) $laporan_id; ?>">
          <input type="hidden" id="statusLaporan" value="<?php echo $laporan ? htmlspecialchars($laporan['status']) : 'draft'; ?>">

          <div class="no-print mb-6 flex items-center justify-between flex-wrap gap-2">
            <span id="badgeStatus" class="px-3 py-1 rounded-full text-sm font-semibold <?php echo ($laporan && $laporan['status'] === 'final') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
              <?php echo ($laporan && $laporan['status'] === 'final') ? '🟢 Final' : '🟡 Belum Final'; ?>
            </span>
            <a href="riwayat_laporanbk.php" class="text-sm text-blue-600 hover:underline">
              <i class="fas fa-clock-rotate-left mr-1"></i> Lihat Riwayat Laporan
            </a>
          </div>

          <div class="no-print mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Dokumen</label>
              <input type="text" id="namaDokumen" placeholder="Terisi otomatis, bisa diedit"
                class="w-full px-3 py-2 border rounded text-sm"
                value="<?php echo $laporan ? htmlspecialchars($laporan['nama_dokumen']) : ''; ?>">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bulan Laporan</label>
              <input type="month" id="bulanLaporan" class="w-full px-3 py-2 border rounded text-sm"
                <?php
                  $bulanValueAwal = '';
                  if ($laporan) {
                      $b = (int) $laporan['bulan'];
                      $tp = explode('/', $laporan['tahun_pelajaran']);
                      $tahunAwal = ($b >= 7) ? (int) ($tp[0] ?? date('Y')) : (int) ($tp[1] ?? date('Y'));
                      $bulanValueAwal = sprintf('%04d-%02d', $tahunAwal, $b);
                  }
                ?>
                value="<?php echo $bulanValueAwal; ?>"
                <?php echo $laporan ? 'readonly' : ''; ?>>
              <p class="text-xs text-gray-500 mt-1 no-print" id="hintBulanLaporan">
                <?php echo $laporan ? 'Bulan laporan tidak bisa diubah setelah dibuat.' : 'Semester &amp; tahun pelajaran otomatis mengikuti bulan ini.'; ?>
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
              <input type="text" id="semesterLaporan" readonly
                class="w-full px-3 py-2 border rounded text-sm bg-gray-100 text-gray-700"
                value="<?php echo $laporan ? htmlspecialchars($laporan['semester']) : ''; ?>">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Pelajaran</label>
              <input type="text" id="tahunPelajaranLaporan" readonly
                class="w-full px-3 py-2 border rounded text-sm bg-gray-100 text-gray-700"
                value="<?php echo $laporan ? htmlspecialchars($laporan['tahun_pelajaran']) : ''; ?>">
            </div>
          </div>

          <div class="no-print mb-8 bg-blue-50 border border-blue-200 rounded-lg p-3 flex flex-wrap items-end gap-2">
            <div class="flex-1 min-w-[240px]">
              <label class="block text-xs font-medium text-gray-700 mb-1">
                <i class="fas fa-filter mr-1"></i> Filter Guru BK
              </label>
              <select id="filterGuruBK" class="input w-full px-3 py-2 border rounded text-sm">
                <option value="">Semua Guru BK</option>
                <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt): ?>
                <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                Berlaku untuk seluruh bagian laporan (III, IV, V) beserta hasil cetak/PDF-nya.
                Filter ini hanya menyaring tampilan data asli tidak berubah.
              </p>
            </div>
          </div>

          <details class="report-section mb-8 no-print-toggle" open>
            <summary>
              <h3 class="text-lg font-bold text-gray-800 section-title">
                <i class="no-print fas fa-list-check text-blue-600 mr-2"></i>
                III. REKAPITULASI KEGIATAN LAYANAN BK
              </h3>
              <i class="fas fa-chevron-down chevron no-print"></i>
            </summary>
            <div class="report-section-body">
              <div class="table-scroll-wrapper">
                <table
                  id="rekapKegiatan"
                  class="w-full border-collapse border border-gray-300"
                >
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="col-no border border-gray-300 px-1 py-2 text-sm text-center whitespace-nowrap">No</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:14%;">Jenis<br>Layanan</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:12%;">Sasaran</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:8%;">Jumlah<br>Siswa</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center col-tanggal">Waktu</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:16%;">Bentuk<br>Kegiatan</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:16%;">Keterangan</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center no-print" style="width:5%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
              <button
                onclick="tambahRekap()"
                class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print"
              >
                <i class="fas fa-plus mr-2"></i> Tambah Baris
              </button>
              <datalist id="listSasaranKegiatan"></datalist>
            </div>
          </details>

          <details class="report-section mb-8 no-print-toggle" open>
            <summary>
              <h3 class="text-lg font-bold text-gray-800 section-title">
                <i class="no-print fas fa-exclamation-triangle text-blue-600 mr-2"></i>
                IV. REKAP PERMASALAHAN PESERTA DIDIK
              </h3>
              <i class="fas fa-chevron-down chevron no-print"></i>
            </summary>
            <div class="report-section-body">
              <div class="no-print flex flex-wrap items-center gap-2 mb-3">
                <span class="text-xs font-medium text-gray-600 mr-1">Tambah baris permasalahan ke bidang:</span>
                <?php foreach (BIDANG_LAPORAN_BK as $b): ?>
                <button type="button" onclick="tambahBarisMasalahManual('<?php echo htmlspecialchars($b, ENT_QUOTES); ?>')"
                  class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300">
                  <i class="fas fa-plus mr-1"></i><?php echo htmlspecialchars($b); ?>
                </button>
                <?php endforeach; ?>
              </div>
              <div class="table-scroll-wrapper">
                <table
                  id="rekapMasalah"
                  class="w-full border-collapse border border-gray-300"
                >
                  <colgroup>
                    <col style="width: 4%;" />
                    <col style="width: 11%;" />
                    <col style="width: 39%;" />
                    <col style="width: 11%;" />
                    <col style="width: 28%;" />
                    <col style="width: 7%;" />
                  </colgroup>
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center whitespace-nowrap">No</th>
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center">Bidang</th>
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center">Permasalahan</th>
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center">Jumlah<br />Siswa</th>
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center">Tindak Awal</th>
                      <th class="border border-gray-300 px-1 py-2 text-sm text-center no-print">Aksi</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
              <p class="text-xs text-gray-500 mt-2 no-print">
                Permasalahan terisi otomatis dari laporan Home Visit, Konseling Individu/Kelompok,
                dan Konsultasi Orang Tua bulan ini &mdash; tetap bisa diedit manual. Jumlah Siswa selalu diisi manual.
                Nomor mengikuti 4 bidang (Pribadi, Belajar, Sosial, Karier); beberapa permasalahan pada
                bidang yang sama akan berbagi nomor yang sama.
              </p>
            </div>
          </details>

          <details class="report-section mb-8 no-print-toggle" open>
            <summary>
              <h3 class="text-lg font-bold text-gray-800 section-title">
                <i class="no-print fas fa-tasks text-blue-600 mr-2"></i>
                V. TINDAK LANJUT
              </h3>
              <i class="fas fa-chevron-down chevron no-print"></i>
            </summary>
            <div class="report-section-body">
              <div class="table-scroll-wrapper">
                <table
                  id="tindakLanjut"
                  class="w-full border-collapse border border-gray-300"
                >
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="col-no border border-gray-300 px-1 py-2 text-sm text-center whitespace-nowrap">No</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:18%;">Permasalahan</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:14%;">Layanan<br>BK</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:16%;">Tindak<br>Lanjut</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center col-tanggal">Bulan</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center" style="width:16%;">Pihak<br>Terkait</th>
                      <th class="border border-gray-300 px-3 py-2 text-sm text-center no-print" style="width:5%;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
              <button
                onclick="tambahTindak()"
                class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm no-print"
              >
                <i class="fas fa-plus mr-2"></i> Tambah Baris
              </button>
            </div>
          </details>

          <div class="penutup-ttd-wrap">
          <div class="penutup-judul hidden print:block mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center penutup-heading">
              <i class="no-print fas fa-flag-checkered text-gray-600 mr-2"></i>
              VI. PENUTUP
            </h3>
            <p class="text-sm text-gray-700 text-justify mb-4">
              Demikian Laporan Bimbingan dan Konseling ini disusun
              sebagai bahan evaluasi dan dokumentasi kegiatan BK di sekolah.
              Diharapkan laporan ini dapat menjadi dasar peningkatan layanan BK
              pada bulan berikutnya.
            </p>
          </div>

          <?php $bulan_indo = [ 'January' => 'Januari', 'February' =>
          'Februari', 'March' => 'Maret', 'April' => 'April', 'May' => 'Mei',
          'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus', 'September'
          => 'September', 'October' => 'Oktober', 'November' => 'November',
          'December' => 'Desember' ]; $tgl_sekarang = date('d') . ' ' .
          $bulan_indo[date('F')] . ' ' . date('Y'); $nama_kepsek = "Novie
          Bambang Rumadi, S.T., M.Pd"; ?>

          <div class="signature-area grid grid-cols-2 gap-16 mt-6 print:mt-8 text-center">

  <div>
    <div class="sign-header">
      <p class="text-sm font-semibold mb-1">Mengetahui,</p>
      <p class="text-sm mb-0">Kepala Sekolah atau Koordinator BK</p>
    </div>

    <select
      id="pilihKoordinator"
      class="no-print w-full px-3 py-2 border rounded mb-2 text-sm"
      onchange="syncPrintText(this, 'printKoordinator')"
    >
      <option value="">Pilih Nama Guru</option>
      <option value="<?php echo $nama_kepsek; ?>" <?php echo ($laporan && $laporan['nama_koordinator'] === $nama_kepsek) ? 'selected' : ''; ?>><?php echo $nama_kepsek; ?></option>
      <option value="Pahrurazi, S.Pd" <?php echo ($laporan && $laporan['nama_koordinator'] === 'Pahrurazi, S.Pd') ? 'selected' : ''; ?>>Pahrurazi, S.Pd</option>
    </select>

    <input
      id="nipKoordinator"
      type="text"
      class="no-print w-full px-3 py-2 border rounded text-sm"
      placeholder="Masukkan NIP"
      value="<?php echo $laporan ? htmlspecialchars($laporan['koordinator_nip']) : ''; ?>"
      oninput="
        document.getElementById('valNipKoordinator').textContent =
          this.value
      "
    />

    <p class="hidden print:block sign-space">&nbsp;</p>
    <span
      id="printKoordinator"
      class="hidden print:block font-bold"
    ></span>
    <div
      class="hidden print:block border-t border-black w-56 mx-auto mt-1"
    ></div>
    <p class="hidden print:block text-sm mt-1 text-center">
      NIP: <span id="valNipKoordinator"></span>
    </p>
  </div>

  <div>
    <div class="sign-header">
      <div class="no-print">
        <input type="date" id="inputTglTtd" class="w-40 px-2 py-1 border rounded text-sm text-center mb-1" onchange="formatTanggalTtd(this.value)">
      </div>
      <p id="teksTglTtd" class="hidden print:block text-sm font-semibold mb-1">
        <?php echo $tgl_sekarang?>
      </p>
      <p class="text-sm mb-0">Guru Bimbingan dan Konseling</p>
    </div>

    <select
      id="pilihGuruBK"
      class="input no-print w-full px-3 py-2 border rounded mb-2 text-sm"
      onchange="syncPrintText(this, 'printGuruBK')"
    >
      <option value="">Pilih Nama Guru</option>
    <?php
      foreach ($DAFTAR_GURU_BK as $nama_guru_opt):
          $selected = ($laporan && $laporan['nama_guru_bk'] === $nama_guru_opt) ? 'selected' : '';
    ?>
    <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($nama_guru_opt); ?></option>
    <?php endforeach; ?>
    </select>

    <input
      id="nipGuruBK"
      type="text"
      class="no-print w-full px-3 py-2 border rounded text-sm"
      placeholder="Masukkan NIP"
      value="<?php echo $laporan ? htmlspecialchars($laporan['nip_guru_bk']) : ''; ?>"
      oninput="
        document.getElementById('valNipGuruBK').textContent =
          this.value
      "
    />

    <p class="hidden print:block sign-space">&nbsp;</p>
    <span
      id="printGuruBK"
      class="hidden print:block font-bold"
    ></span>
    <div
      class="hidden print:block border-t border-black w-56 mx-auto mt-1"
    ></div>
    <p class="hidden print:block text-sm mt-1 text-center">
      NIP: <span id="valNipGuruBK"></span>
    </p>
  </div>
</div>
          </div>
    </div>
    
          <div id="dokumentasi-section" class="mb-8 mt-8">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
              <i class="no-print fas fa-images text-purple-600 mr-2"></i>
              DOKUMENTASI KEGIATAN
            </h3>
            <p class="no-print text-red-700 text-sm ms-5 mb-1">Maksimal 12 foto dan maksimal berukuran 2 mb</p>
            <input
              type="file"
              accept="image/*"
              multiple
              onchange="previewFoto(event)"
              class="mb-4 text-sm border border-gray-300 rounded-lg px-3 py-2 w-full no-print"
            />
            
            <div
              id="dokumentasi"
              class="grid grid-cols-2 md:grid-cols-3 gap-4"
            ></div>
          </div>

          <div class="flex justify-center gap-4 no-print flex-wrap" id="actionButtons">
            <button
              id="btnSimpan"
              onclick="simpanDokumen()"
              class="bg-emerald-600 text-white px-6 py-3 rounded-lg hover:bg-emerald-700 transition font-semibold"
              title="Menyimpan perubahan, dokumen masih bisa diedit setelah ini"
            >
              <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
            <button
              id="btnFinalisasi"
              onclick="finalisasiDokumen()"
              class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold"
              title="Mengunci dokumen agar tidak bisa diedit lagi, lalu bisa dicetak"
            >
              <i class="fas fa-flag-checkered mr-2"></i> Selesaikan & Kunci Laporan
            </button>
            <button
              id="btnBukaDraft"
              onclick="bukaSebagaiDraft()"
              class="hidden bg-amber-600 text-white px-6 py-3 rounded-lg hover:bg-amber-700 transition font-semibold"
              title="Membuka kembali laporan yang sudah dikunci agar bisa diedit"
            >
              <i class="fas fa-lock-open mr-2"></i> Buka Kunci untuk Edit Lagi
            </button>
            <button
              id="btnCetak"
              onclick="window.print()"
              disabled
              class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
              title="Hanya bisa dicetak setelah laporan diselesaikan dan dikunci"
            >
              <i class="fas fa-file-pdf mr-2"></i> Cetak / Simpan sebagai PDF
            </button>
            <button
              id="btnResetForm"
              onclick="resetForm()"
              class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition font-semibold"
              title="Mengosongkan semua isian di form ini"
            >
              <i class="fas fa-redo mr-2"></i> Kosongkan Semua Isian
            </button>
          </div>
        </div>

        <script>
          <?php if ($laporan): ?>
          window.DATA_LAPORAN_EXISTING = {
            rekap: <?php echo $laporan['materi_rekap'] ? $laporan['materi_rekap'] : '[]'; ?>,
            masalah: <?php echo $laporan['masalah'] ? $laporan['masalah'] : '[]'; ?>,
            tindak: <?php echo $laporan['tindak_lanjut'] ? $laporan['tindak_lanjut'] : '[]'; ?>,
            dokumentasi: <?php echo $laporan['dokumentasi_foto'] ? $laporan['dokumentasi_foto'] : '[]'; ?>
          };
          <?php
            // Bulan & tahun awal (kalender) dari laporan yang sedang dibuka,
            // dipakai untuk menarik ulang rekap kegiatan (Section III) secara live
            // dari tabel sumber setiap kali laporan ini dibuka.
            $b = (int) $laporan['bulan'];
            $tpArr = explode('/', $laporan['tahun_pelajaran']);
            $tahunAwalRekap = ($b >= 7) ? (int) ($tpArr[0] ?? date('Y')) : (int) ($tpArr[1] ?? date('Y'));
          ?>
          window.LAPORAN_BULAN_AKTIF = <?php echo $b; ?>;
          window.LAPORAN_TAHUN_AKTIF = <?php echo $tahunAwalRekap; ?>;
          <?php endif; ?>

          const dataSasaran = [
          <?php
          $q = mysqli_query($koneksi,"
              SELECT DISTINCT jurusan, kelas
              FROM siswa
              WHERE jurusan!='' AND kelas!=''
          ");

          while($d=mysqli_fetch_assoc($q)){
              echo "'".$d['jurusan']." ".$d['kelas']."',";
          }
          ?>
          ];
        </script>

        <script src="partials/sidebar-script.js"></script>
        <script>
          function tambahRekap() {
            const table = document.getElementById("rekapKegiatan");
            const tbody = table.querySelector("tbody");
            const row = tbody.insertRow();
            const rowNum = tbody.rows.length;

            row.className = "hover:bg-gray-50 transition-colors";
            row.dataset.sumberKey = '';
            row.dataset.manual = '1';
            row.dataset.namaGuru = '';

            row.innerHTML = `
            <td class="border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700">${rowNum}</td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="jenis_layanan[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Jenis Layanan" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="text" name="sasaran_kelas[]" list="listSasaranKegiatan" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" placeholder="Mis: PPLG B XII, RPL X" oninput="tandaiManual(this)">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <input type="number" name="jumlah_siswa[]" min="0" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none text-center" placeholder="0" oninput="if(this.value<0)this.value=0; tandaiManual(this)">
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <div class="date-input-wrapper">
                    <input type="date" name="waktu[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none" onchange="updateTanggalDisplay(this); tandaiManual(this)">
                </div>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="bentuk_kegiatan[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Bentuk" oninput="autoResizeTextarea(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="keterangan[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Keterangan" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            return row;
          }

          function tandaiManual(el) {
            const tr = el.closest('tr');
            if (tr) tr.dataset.manual = '1';
          }

          function ambilNilaiElemen(el) {
            if (!el) return '';
            if (el.tagName === 'SELECT' && el.multiple) {
              return Array.from(el.selectedOptions).map((o) => o.value).join(', ');
            }
            return el.value;
          }

          function setNilaiElemen(el, val) {
            if (!el) return;
            if (el.tagName === 'SELECT' && el.multiple) {
              const dipilih = new Set(String(val || '').split(',').map((s) => s.trim()).filter(Boolean));
              Array.from(el.options).forEach((o) => { o.selected = dipilih.has(o.value); });
              return;
            }
            el.value = val !== undefined && val !== null ? val : '';
          }

          function perbaruiNomorBaris(tbody) {
            Array.from(tbody.rows).forEach((tr, idx) => {
              const selNo = tr.cells[0];
              if (selNo) selNo.textContent = idx + 1;
            });
          }

          function autoResizeTextarea(el) {
            el.style.height = "auto";
            el.style.height = el.scrollHeight + "px";
          }

          function parseKelasItem(item) {
            const tokens = item.trim().split(/\s+/).filter(Boolean);
            const tingkatList = ["XIII", "XII", "XI", "X"];
            let tingkat = "";
            let rombel = "";
            const sisa = [];

            tokens.forEach((token) => {
              if (tingkat === "") {
                const cocokUtuh = tingkatList.find((t) => t === token.toUpperCase());
                if (cocokUtuh) {
                  tingkat = cocokUtuh;
                  return;
                }

                const cocokGabung = tingkatList.find((t) =>
                  token.toUpperCase().startsWith(t),
                );
                if (cocokGabung) {
                  tingkat = cocokGabung;
                  const sisaToken = token.substring(cocokGabung.length);
                  if (sisaToken) rombel = sisaToken;
                  return;
                }
              }
              sisa.push(token);
            });

            if (!rombel && sisa.length > 1) {
              rombel = sisa.pop();
            }

            const jurusan = sisa.join(" ");

            return { tingkat, jurusan, rombel, asli: item };
          }

          function formatKelasTampilan(item) {
            const parsed = parseKelasItem(item);
            if (!parsed.tingkat) {
              return item;
            }
            return [parsed.tingkat, parsed.jurusan, parsed.rombel]
              .filter(Boolean)
              .join(" ");
          }

          function urutkanDataSasaran(data) {
            const urutanTingkat = { X: 1, XI: 2, XII: 3, XIII: 4 };

            return data
              .map((item) => parseKelasItem(item))
              .sort((a, b) => {
                const ta = urutanTingkat[a.tingkat] || 99;
                const tb = urutanTingkat[b.tingkat] || 99;
                if (ta !== tb) return ta - tb;

                const jurusanBanding = a.jurusan.localeCompare(b.jurusan);
                if (jurusanBanding !== 0) return jurusanBanding;

                return a.rombel.localeCompare(b.rombel);
              })
              .map((parsed) => parsed.asli);
          }

          function formatTanggalIndonesia(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            const bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                          'Juli','Agustus','September','Oktober','November','Desember'];
            return parseInt(parts[2]) + ' ' + bulan[parseInt(parts[1]) - 1] + ' ' + parts[0];
          }

          function updateTanggalDisplay(inputEl) {
          }

          const BIDANG_LAPORAN_BK = ['Pribadi', 'Belajar', 'Sosial', 'Karier'];

          function buatKunciManualUnik(prefix) {
            return prefix + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
          }

          // Satu baris = satu permasalahan. "No" dan "Bidang" ditampilkan lewat
          // rowspan (lihat terapkanRowspanBidangIV) sehingga beberapa permasalahan
          // pada bidang yang sama berbagi satu nomor, tanpa membuat nomor baru.
          function buatBarisMasalah(namaBidang, opts) {
            opts = opts || {};
            const table = document.getElementById("rekapMasalah");
            const tbody = table.querySelector("tbody");
            const row = document.createElement('tr');

            // Baris "filler" (baris-kosong-tampilan-<bidang>) tidak dihitung sebagai
            // baris data sungguhan saat menentukan posisi sisip -- dia murni penanda
            // visual, selalu ditaruh paling akhir dalam grup bidangnya.
            const barisBidangSama = Array.from(tbody.querySelectorAll('tr'))
              .filter((tr) => tr.dataset.bidang === namaBidang && tr.dataset.filler !== '1');

            if (barisBidangSama.length > 0) {
              // Sudah ada baris bidang ini -- taruh tepat setelah baris terakhirnya,
              // supaya grup bidang tetap berurutan/tidak tercerai-berai.
              const last = barisBidangSama[barisBidangSama.length - 1];
              if (last.nextSibling) last.parentNode.insertBefore(row, last.nextSibling);
              else last.parentNode.appendChild(row);
            } else {
              // Belum ada baris bidang ini -- sisipkan tepat sebelum bidang
              // berikutnya (urutan tetap: Pribadi, Belajar, Sosial, Karier).
              const idxBidang = BIDANG_LAPORAN_BK.indexOf(namaBidang);
              let sisipSebelum = null;
              for (let i = idxBidang + 1; i < BIDANG_LAPORAN_BK.length && !sisipSebelum; i++) {
                sisipSebelum = Array.from(tbody.querySelectorAll('tr')).find((tr) => tr.dataset.bidang === BIDANG_LAPORAN_BK[i]) || null;
              }
              if (sisipSebelum) sisipSebelum.parentNode.insertBefore(row, sisipSebelum);
              else tbody.appendChild(row);
            }

            return isiKontenBarisMasalah(row, namaBidang, opts);
          }

          function isiKontenBarisMasalah(row, namaBidang, opts) {
            opts = opts || {};
            row.className = "hover:bg-gray-50 transition-colors";
            row.dataset.bidang = namaBidang;
            row.dataset.sumberKey = opts.sumberKey || buatKunciManualUnik('manual');
            // sumberAsal mengidentifikasi kegiatan/laporan+permasalahan yang sama
            // TANPA memandang bidang -- dipakai untuk mencegah duplikasi di Bagian V
            // ketika satu laporan dipilih ke lebih dari satu bidang. Baris manual
            // (tanpa opts.sumberAsal) memakai sumberKey-nya sendiri sebagai fallback,
            // supaya tetap unik/terpisah dari baris manual lain.
            row.dataset.sumberAsal = opts.sumberAsal || row.dataset.sumberKey;
            row.dataset.manual = opts.manual ? '1' : '0';
            row.dataset.namaGuru = opts.namaGuru || '';
            row.dataset.jenisSumber = opts.jenisSumber || '';

            row.innerHTML = `
            <td class="sel-no border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700"></td>
            <td class="sel-bidang border border-gray-300 px-1 py-2 text-sm font-medium text-gray-700"></td>
            <td class="sel-permasalahan border border-gray-300 px-1 py-1">
                <textarea name="masalah[]" rows="1" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Deskripsi masalah" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="sel-jumlah border border-gray-300 px-1 py-1 text-center">
                <input type="number" name="jml_siswa_masalah[]" min="0" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none text-center" placeholder="0" oninput="if(this.value<0)this.value=0; tandaiManual(this)">
            </td>
            <td class="sel-tindak border border-gray-300 px-1 py-1">
                <textarea name="tindak_awal[]" rows="1" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Tindak awal" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="sel-aksi border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="hapusBarisMasalah(this)" class="text-red-500 hover:text-red-700 transition" title="Hapus baris permasalahan ini">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            return row;
          }

          // Baris statis (tidak diedit, tidak ikut disimpan) yang MUNCUL HANYA
          // ketika seluruh baris data sungguhan pada suatu bidang sedang
          // disembunyikan oleh Filter Guru BK -- supaya kelompok bidang itu
          // (No + nama Bidang) tetap terlihat, tidak lenyap dari tabel/PDF.
          // Ini murni tampilan: filter tidak pernah mengubah data/struktur asli.
          function buatBarisFillerBidang(namaBidang) {
            const table = document.getElementById("rekapMasalah");
            const tbody = table.querySelector("tbody");
            const row = document.createElement('tr');
            row.className = "bg-gray-50";
            row.dataset.bidang = namaBidang;
            row.dataset.filler = '1';
            row.style.display = 'none';
            row.innerHTML = `
            <td class="sel-no border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700"></td>
            <td class="sel-bidang border border-gray-300 px-1 py-2 text-sm font-medium text-gray-700"></td>
            <td class="sel-permasalahan border border-gray-300 px-1 py-2 text-center text-sm text-gray-400">&mdash;</td>
            <td class="sel-jumlah border border-gray-300 px-1 py-2 text-center text-sm text-gray-400">0</td>
            <td class="sel-tindak border border-gray-300 px-1 py-2 text-center text-sm text-gray-400">&mdash;</td>
            <td class="sel-aksi border border-gray-300 px-1 py-2 text-center no-print"></td>
        `;

            const barisBidangSama = Array.from(tbody.querySelectorAll('tr')).filter((tr) => tr.dataset.bidang === namaBidang);
            if (barisBidangSama.length > 0) {
              const last = barisBidangSama[barisBidangSama.length - 1];
              if (last.nextSibling) last.parentNode.insertBefore(row, last.nextSibling);
              else last.parentNode.appendChild(row);
            } else {
              tbody.appendChild(row);
            }
            return row;
          }

          // Pastikan tiap bidang punya persis 1 baris filler (dibuat sekali,
          // lalu tinggal ditampilkan/disembunyikan oleh terapkanRowspanBidangIV).
          function pastikanFillerBidangIV() {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;
            BIDANG_LAPORAN_BK.forEach((b) => {
              const ada = Array.from(tbody.querySelectorAll('tr')).some((tr) => tr.dataset.bidang === b && tr.dataset.filler === '1');
              if (!ada) buatBarisFillerBidang(b);
            });
          }

          function tambahBarisMasalahManual(namaBidang) {
            const row = buatBarisMasalah(namaBidang, { manual: true });
            terapkanRowspanBidangIV();
            terapkanFilterGuru();
            row.querySelector('[name="masalah[]"]')?.focus();
          }

          function hapusBarisMasalah(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            const tbody = tr.closest('tbody');
            const bidang = tr.dataset.bidang;
            const jumlahSebidang = Array.from(tbody.querySelectorAll('tr'))
              .filter((r) => r.dataset.bidang === bidang && r.dataset.filler !== '1').length;

            if (jumlahSebidang <= 1) {
              // Selalu sisakan minimal 1 baris per bidang supaya nomor/bidang tetap ada.
              const elMasalah = tr.querySelector('[name="masalah[]"]');
              const elJml = tr.querySelector('[name="jml_siswa_masalah[]"]');
              const elTindak = tr.querySelector('[name="tindak_awal[]"]');
              [elMasalah, elJml, elTindak].forEach((el) => { if (el) el.value = ''; });
              if (elMasalah) autoResizeTextarea(elMasalah);
              if (elTindak) autoResizeTextarea(elTindak);
              tr.dataset.manual = '1';
              return;
            }

            tr.remove();
            terapkanRowspanBidangIV();
          }

          // Menghitung ulang penggabungan (rowspan) kolom No & Bidang berdasarkan
          // baris yang SEDANG TAMPIL (mengikuti filter guru aktif). Kalau filter
          // membuat semua baris data sungguhan pada suatu bidang tersembunyi,
          // baris filler bidang itu yang ditampilkan sebagai gantinya -- supaya
          // ke-4 bidang SELALU ada di tabel/PDF, apa pun hasil filternya.
          function terapkanRowspanBidangIV() {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;
            pastikanFillerBidangIV();

            const nomorBidang = {};
            BIDANG_LAPORAN_BK.forEach((b, i) => { nomorBidang[b] = i + 1; });

            const semuaBaris = Array.from(tbody.rows);
            let i = 0;
            while (i < semuaBaris.length) {
              const bidang = semuaBaris[i].dataset.bidang;
              let j = i;
              while (j < semuaBaris.length && semuaBaris[j].dataset.bidang === bidang) j++;
              const grup = semuaBaris.slice(i, j);
              const grupNyata = grup.filter((tr) => tr.dataset.filler !== '1');
              const grupFiller = grup.find((tr) => tr.dataset.filler === '1');
              let grupTampil = grupNyata.filter((tr) => !tr.classList.contains('baris-tersembunyi-filter'));

              if (grupTampil.length === 0 && grupFiller) {
                grupFiller.style.display = '';
                grupTampil = [grupFiller];
              } else if (grupFiller) {
                grupFiller.style.display = 'none';
              }

              grup.forEach((tr) => {
                let tdNo = tr.querySelector('.sel-no');
                let tdBidang = tr.querySelector('.sel-bidang');
                const isPertamaTampil = grupTampil.length > 0 && tr === grupTampil[0];

                if (isPertamaTampil) {
                  // Baris ini yang "memegang" gabungan No + Bidang untuk
                  // kelompoknya -- pastikan sel-nya ADA sebagai elemen DOM
                  // nyata (dibuat lagi kalau sebelumnya sempat dilepas karena
                  // baris ini dulu bukan yang pertama tampil).
                  if (!tdNo) {
                    tdNo = document.createElement('td');
                    tdNo.className = 'sel-no border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700';
                    tr.insertBefore(tdNo, tr.firstChild);
                  }
                  if (!tdBidang) {
                    tdBidang = document.createElement('td');
                    tdBidang.className = 'sel-bidang border border-gray-300 px-1 py-2 text-sm font-medium text-gray-700';
                    tdNo.after(tdBidang);
                  }
                  tdNo.style.display = '';
                  tdBidang.style.display = '';
                  tdNo.rowSpan = grupTampil.length;
                  tdBidang.rowSpan = grupTampil.length;
                  tdNo.textContent = nomorBidang[bidang] || '';
                  tdBidang.textContent = bidang;
                } else {
                  // PENTING: sel No/Bidang pada baris SELAIN yang pertama harus
                  // benar-benar DIHAPUS dari DOM (bukan cuma display:none).
                  // Kalau hanya disembunyikan, browser tetap menghitungnya
                  // sebagai "kolom hilang" pada baris itu, sehingga isi kolom
                  // Permasalahan/Jumlah Siswa/Tindak Awal ikut bergeser ke kiri
                  // dan tabel jadi tidak rata saat dicetak/PDF -- ini pemicu
                  // masalah "tabel Bagian IV tidak rata" yang terlihat di PDF.
                  if (tdNo) tdNo.remove();
                  if (tdBidang) tdBidang.remove();
                }
              });

              i = j;
            }
          }

          // Pastikan setiap 4 bidang selalu punya minimal 1 baris DATA sungguhan
          // (kosong kalau memang belum ada permasalahan), supaya struktur "4
          // kelompok nomor" pada Bagian IV selalu tersedia untuk diisi/disimpan.
          function pastikanSemuaBidangAdaBarisIV() {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;
            BIDANG_LAPORAN_BK.forEach((b) => {
              const ada = Array.from(tbody.querySelectorAll('tr')).some((tr) => tr.dataset.bidang === b && tr.dataset.filler !== '1');
              if (!ada) buatBarisMasalah(b, { manual: true });
            });
            pastikanFillerBidangIV();
          }

          function tambahTindak() {
            const table = document.getElementById("tindakLanjut");
            const tbody = table.querySelector("tbody");
            const row = tbody.insertRow();
            const rowNum = tbody.rows.length;

            row.className = "hover:bg-gray-50 transition-colors";
            row.dataset.sumberKey = '';
            row.dataset.manual = '1';
            row.dataset.namaGuru = '';

            row.innerHTML = `
            <td class="border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700">${rowNum}</td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="tl_permasalahan[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Permasalahan" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="tl_layanan[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Layanan BK" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="tl_tindak_lanjut[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Tindak lanjut" oninput="autoResizeTextarea(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <select name="tl_waktu[]" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none cursor-pointer">
                    <option value="">Pilih Bulan</option>
                    <option value="Januari">Januari</option>
                    <option value="Februari">Februari</option>
                    <option value="Maret">Maret</option>
                    <option value="April">April</option>
                    <option value="Mei">Mei</option>
                    <option value="Juni">Juni</option>
                    <option value="Juli">Juli</option>
                    <option value="Agustus">Agustus</option>
                    <option value="September">September</option>
                    <option value="Oktober">Oktober</option>
                    <option value="November">November</option>
                    <option value="Desember">Desember</option>
                </select>
            </td>
            <td class="border border-gray-300 px-1 py-1">
                <textarea name="tl_pihak[]" rows="1" class="w-full px-2 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="Pihak terkait" oninput="autoResizeTextarea(this)"></textarea>
            </td>
            <td class="border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            return row;
          }

          function renderFotoDokumentasi(box, src) {
            const wrapper = document.createElement("div");
            wrapper.className = "relative group";

            const img = document.createElement("img");
            img.src = src;
            img.className =
              "w-full h-48 object-cover rounded-lg shadow-md hover:shadow-xl transition border border-gray-200";

            const btnHapus = document.createElement("button");
            btnHapus.type = "button";
            btnHapus.innerHTML = '<i class="fas fa-times"></i>';
            btnHapus.className =
              "absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center opacity-0 group-hover:opacity-100 transition no-print shadow-lg";
            btnHapus.onclick = () => {
              wrapper.remove();
              if (box.querySelectorAll("img").length === 0) {
                box.innerHTML =
                  '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
              }
            };

            wrapper.appendChild(img);
            wrapper.appendChild(btnHapus);
            box.appendChild(wrapper);
          }

          function previewFoto(event) {
            const box = document.getElementById("dokumentasi");
            const newFiles = Array.from(event.target.files);
            const maxSize = 2 * 1024 * 1024;
            const maxPhotos = 12;

            if (box.querySelector("p")) {
              box.innerHTML = "";
            }

            newFiles.forEach((file) => {
              const currentPhotos = box.querySelectorAll("img").length;

              if (currentPhotos >= maxPhotos) {
                alert("Maksimal hanya boleh 12 foto!");
                return;
              }

              if (!file.type.startsWith("image/")) {
                alert("File " + file.name + " bukan gambar!");
                return;
              }

              if (file.size > maxSize) {
                alert("File " + file.name + " terlalu besar! Maksimal 2MB.");
                return;
              }

              const reader = new FileReader();
              reader.onload = () => renderFotoDokumentasi(box, reader.result);
              reader.readAsDataURL(file);
            });

            event.target.value = "";
          }

          function resetForm() {
            if (
              confirm(
                "Semua isian di form ini akan dikosongkan dan tidak bisa dikembalikan. Lanjutkan?",
              )
            ) {
              ["rekapKegiatan", "rekapMasalah", "tindakLanjut"].forEach(
                (tableId) => {
                  const table = document.getElementById(tableId);
                  const tbody = table.querySelector("tbody");
                  if (tbody) {
                    tbody.innerHTML = "";
                  }
                },
              );

              document.querySelectorAll("select").forEach((select) => {
                select.selectedIndex = 0;
              });

              document
                .querySelectorAll('input[type="text"], input[type="number"]')
                .forEach((input) => {
                  input.value = "";
                });

              const fileInput = document.querySelector('input[type="file"]');
              if (fileInput) {
                fileInput.value = "";
              }

              const dokumentasi = document.getElementById("dokumentasi");
              if (dokumentasi) {
                dokumentasi.innerHTML =
                  '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
              }

              const idLaporanEl = document.getElementById('idLaporan');
              if (!idLaporanEl || !idLaporanEl.value || idLaporanEl.value === '0') {
                document.getElementById('bulanLaporan').value = '';
                siapkanLaporanBulanBaru();
              }

              alert("Semua isian sudah dikosongkan.");
            }
          }

          function formatTanggalTtd(dateStr) {
            if (!dateStr) {
              document.getElementById('teksTglTtd').textContent = '';
              return;
            }
            const parts = dateStr.split('-');
            const bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                           'Juli','Agustus','September','Oktober','November','Desember'];
            const tgl = parts[2];
            const bln = bulan[parseInt(parts[1], 10) - 1];
            const thn = parts[0];
            document.getElementById('teksTglTtd').textContent = tgl + ' ' + bln + ' ' + thn;
          }

          const NAMA_BULAN_INDO = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

          function isiDatalistSasaran() {
            const listEl = document.getElementById('listSasaranKegiatan');
            if (!listEl) return;
            listEl.innerHTML = '';
            urutkanDataSasaran(dataSasaran).forEach((item) => {
              const opt = document.createElement('option');
              opt.value = formatKelasTampilan(item);
              listEl.appendChild(opt);
            });
          }

          function hitungSemesterTahunAjaran(bulan, tahun) {
            if (bulan >= 7 && bulan <= 12) {
              return { semester: 'Ganjil', tahun_pelajaran: tahun + '/' + (tahun + 1) };
            }
            return { semester: 'Genap', tahun_pelajaran: (tahun - 1) + '/' + tahun };
          }

          function terapkanInfoBulan(bulanVal) {
            if (!bulanVal) return null;
            const [thnStr, blnStr] = bulanVal.split('-');
            const bulan = parseInt(blnStr, 10);
            const tahun = parseInt(thnStr, 10);
            const info = hitungSemesterTahunAjaran(bulan, tahun);
            document.getElementById('semesterLaporan').value = info.semester;
            document.getElementById('tahunPelajaranLaporan').value = info.tahun_pelajaran;
            const namaEl = document.getElementById('namaDokumen');
            if (!namaEl.value.trim()) {
              namaEl.value = 'Laporan Bulanan BK - ' + NAMA_BULAN_INDO[bulan] + ' ' + tahun;
            }
            return { bulan, tahun, ...info };
          }

          async function siapkanLaporanBulanBaru() {
            const inputBulan = document.getElementById('bulanLaporan');
            if (!inputBulan.value) {
              const sekarang = new Date();
              inputBulan.value = sekarang.getFullYear() + '-' + String(sekarang.getMonth() + 1).padStart(2, '0');
            }
            await prosesPerubahanBulan(true);
            inputBulan.addEventListener('change', () => prosesPerubahanBulan(false));
          }

          const KOLOM_REKAP = ['jenis_layanan', 'sasaran_kelas', 'jumlah_siswa', 'waktu', 'bentuk_kegiatan', 'keterangan'];
          // Catatan: 'bidang' TIDAK lagi termasuk kolom form per-baris -- Bidang
          // sekarang murni struktural (dataset.bidang + rowspan), bukan field
          // yang diedit langsung per baris permasalahan.
          const KOLOM_MASALAH = ['masalah', 'jml_siswa_masalah', 'tindak_awal'];
          const KOLOM_TINDAK = ['tl_permasalahan', 'tl_layanan', 'tl_tindak_lanjut', 'tl_waktu', 'tl_pihak'];

          const FIELD_SELALU_MANUAL = {
            masalah: ['jml_siswa_masalah', 'tindak_awal'],
          };

          function isiBarisTerakhir(tbody, kolom, dataBaris) {
            const tr = tbody.rows[tbody.rows.length - 1];
            kolom.forEach((nama) => {
              const el = tr.querySelector(`[name="${nama}[]"]`);
              if (el && dataBaris[nama] !== undefined) {
                setNilaiElemen(el, dataBaris[nama]);
                if (el.tagName === 'TEXTAREA') autoResizeTextarea(el);
              }
            });
          }

          function kunciKontenRekap(jenisLayanan, waktu) {
            return String(jenisLayanan || '').trim().toLowerCase() + '|' + String(waktu || '').trim();
          }

          function mergeOtomatisKeTabel(tbodySelector, dataFresh, fnTambahBaris, kolomList, opts) {
            opts = opts || {};
            const skipFields = opts.skipFields || [];
            const isiJikaKosongFields = opts.isiJikaKosongFields || [];
            const keteranganField = opts.keteranganField || null;
            const tbody = document.querySelector(tbodySelector);
            if (!tbody) return;

            const barisAda = Array.from(tbody.querySelectorAll('tr'));
            const petaKey = {};
            const petaKonten = {};
            barisAda.forEach((tr) => {
              if (tr.dataset.sumberKey) {
                petaKey[tr.dataset.sumberKey] = tr;
              } else if (opts.kunciKonten) {
                const elJenis = tr.querySelector('[name="jenis_layanan[]"]');
                const elWaktu = tr.querySelector('[name="waktu[]"]');
                const k = kunciKontenRekap(elJenis ? elJenis.value : '', elWaktu ? elWaktu.value : '');
                if (k !== '|' && !petaKonten[k]) petaKonten[k] = tr;
              }
            });

            const keyFresh = new Set();

            (dataFresh || []).forEach((baris) => {
              if (!baris.sumber_key) return;
              keyFresh.add(baris.sumber_key);
              let tr = petaKey[baris.sumber_key];

              if (!tr && opts.kunciKonten) {
                const k = kunciKontenRekap(baris.jenis_layanan, baris.waktu);
                if (petaKonten[k]) {
                  tr = petaKonten[k];
                  tr.dataset.sumberKey = baris.sumber_key;
                  tr.dataset.manual = '0';
                  delete petaKonten[k];
                }
              }

              if (tr) {
                tr.dataset.namaGuru = baris.nama_guru || '';
                if (baris.jenis_sumber !== undefined) tr.dataset.jenisSumber = baris.jenis_sumber || '';
                if (tr.dataset.manual === '1') return;

                kolomList.forEach((nama) => {
                  if (baris[nama] === undefined) return;
                  const el = tr.querySelector(`[name="${nama}[]"]`);
                  if (!el) return;
                  if (nama === keteranganField) {
                    const nilaiSaatIni = ambilNilaiElemen(el);
                    if (!nilaiSaatIni || nilaiSaatIni === 'Terlaksana') {
                      setNilaiElemen(el, baris[nama] || 'Terlaksana');
                    }
                    return;
                  }
                  // Field seperti Bentuk Kegiatan: BOLEH diisikan otomatis
                  // (mengejar/backfill) selama selnya MASIH KOSONG -- ini juga
                  // mencakup baris lama yang sempat tersimpan kosong sebelum
                  // template Bentuk Kegiatan ditambahkan. Begitu ada isinya
                  // (otomatis atau ditulis manual oleh guru), tidak akan
                  // ditimpa lagi oleh refresh data berikutnya.
                  if (isiJikaKosongFields.includes(nama)) {
                    if (!ambilNilaiElemen(el)) {
                      setNilaiElemen(el, baris[nama]);
                      if (el.tagName === 'TEXTAREA') autoResizeTextarea(el);
                    }
                    return;
                  }
                  if (skipFields.includes(nama)) return;
                  setNilaiElemen(el, baris[nama]);
                  if (el.tagName === 'TEXTAREA') autoResizeTextarea(el);
                });
              } else {
                fnTambahBaris(baris);
                tr = tbody.rows[tbody.rows.length - 1];
                tr.dataset.sumberKey = baris.sumber_key;
                tr.dataset.manual = '0';
                tr.dataset.namaGuru = baris.nama_guru || '';
                tr.dataset.jenisSumber = baris.jenis_sumber || '';
                isiBarisTerakhir(tbody, kolomList, baris);
                if (keteranganField) {
                  const elK = tr.querySelector(`[name="${keteranganField}[]"]`);
                  if (elK && !ambilNilaiElemen(elK)) setNilaiElemen(elK, 'Terlaksana');
                }
              }
            });

            barisAda.forEach((tr) => {
              if (tr.dataset.sumberKey && !keyFresh.has(tr.dataset.sumberKey) && tr.dataset.manual !== '1') {
                tr.remove();
              }
            });

            perbaruiNomorBaris(tbody);
          }

          // Menyalin Bagian IV -> Bagian V. PENTING: satu laporan/kegiatan yang
          // sama bisa muncul sebagai BEBERAPA baris di Bagian IV (satu per bidang
          // yang dipilih guru untuknya), tapi tetap harus jadi SATU baris saja di
          // Bagian V -- karena itu tetap satu kegiatan/permasalahan yang sama,
          // bukan beberapa kegiatan. Dedup dilakukan lewat "sumber_asal" (identitas
          // laporan+kalimat masalah TANPA bidang), bukan "sumber_key" milik IV
          // (yang sengaja unik per-bidang supaya baris IV-nya terpisah per bidang).
          // Kalau satu laporan memang punya beberapa KALIMAT masalah berbeda,
          // masing-masing tetap dapat sumber_asal sendiri -> tetap jadi baris V
          // terpisah (sesuai aturan "beberapa permasalahan = beberapa baris").
          function sinkronTindakDariMasalah() {
            const tbodyIV = document.querySelector('#rekapMasalah tbody');
            const tbodyV = document.querySelector('#tindakLanjut tbody');
            if (!tbodyIV || !tbodyV) return;

            const barisV = Array.from(tbodyV.querySelectorAll('tr'));
            const petaKey = {};
            barisV.forEach((tr) => { if (tr.dataset.sumberKey) petaKey[tr.dataset.sumberKey] = tr; });

            const keyFresh = new Set();
            const sumberAsalSudahDiproses = new Set();

            Array.from(tbodyIV.querySelectorAll('tr')).forEach((trIV) => {
              if (trIV.dataset.filler === '1') return; // baris filler tampilan, bukan data
              const sumberKeyIV = trIV.dataset.sumberKey;
              if (!sumberKeyIV) return;

              const elMasalahIV = trIV.querySelector('[name="masalah[]"]');
              const teksMasalah = elMasalahIV ? ambilNilaiElemen(elMasalahIV).trim() : '';
              if (!teksMasalah) return;

              // Sudah ada baris V untuk kegiatan/permasalahan (sumber_asal) yang
              // sama dari duplikasi bidang lain -- lewati, jangan buat baris kedua.
              const sumberAsal = trIV.dataset.sumberAsal || sumberKeyIV;
              if (sumberAsalSudahDiproses.has(sumberAsal)) return;
              sumberAsalSudahDiproses.add(sumberAsal);

              keyFresh.add(sumberAsal);
              const jenisSumber = trIV.dataset.jenisSumber || '';
              const namaGuru = trIV.dataset.namaGuru || '';

              let trV = petaKey[sumberAsal];
              if (trV) {
                trV.dataset.namaGuru = namaGuru;
                if (trV.dataset.manual === '1') return;

                const elP = trV.querySelector('[name="tl_permasalahan[]"]');
                const elL = trV.querySelector('[name="tl_layanan[]"]');
                if (elP) { setNilaiElemen(elP, teksMasalah); autoResizeTextarea(elP); }
                if (elL) { setNilaiElemen(elL, jenisSumber); autoResizeTextarea(elL); }
              } else {
                tambahTindak();
                trV = tbodyV.rows[tbodyV.rows.length - 1];
                trV.dataset.sumberKey = sumberAsal;
                trV.dataset.manual = '0';
                trV.dataset.namaGuru = namaGuru;

                const elP = trV.querySelector('[name="tl_permasalahan[]"]');
                const elL = trV.querySelector('[name="tl_layanan[]"]');
                if (elP) { setNilaiElemen(elP, teksMasalah); autoResizeTextarea(elP); }
                if (elL) { setNilaiElemen(elL, jenisSumber); autoResizeTextarea(elL); }
              }
            });

            barisV.forEach((tr) => {
              if (tr.dataset.sumberKey && !keyFresh.has(tr.dataset.sumberKey) && tr.dataset.manual !== '1') {
                tr.remove();
              }
            });

            perbaruiNomorBaris(tbodyV);
          }

          function terapkanFilterGuru() {
            const sel = document.getElementById('filterGuruBK');
            const nilai = sel ? sel.value : '';
            ['#rekapKegiatan tbody', '#rekapMasalah tbody', '#tindakLanjut tbody'].forEach((sel2) => {
              document.querySelectorAll(`${sel2} > tr`).forEach((tr) => {
                const guru = tr.dataset.namaGuru || '';
                const tampil = !nilai || !guru || guru === nilai;
                tr.classList.toggle('baris-tersembunyi-filter', !tampil);
              });
            });
            // Rowspan No/Bidang Bagian IV bergantung baris mana yang tampil,
            // jadi harus dihitung ulang setiap kali filter berubah.
            terapkanRowspanBidangIV();
          }

          // Merge khusus Bagian IV: setiap permasalahan otomatis adalah baris
          // tersendiri (bukan bucket gabungan), disisipkan pada posisi yang benar
          // di dalam grup bidangnya. Baris manual (termasuk baris auto yang sudah
          // diedit manual) tidak pernah ditimpa atau dihapus oleh data live.
          function mergeMasalahIV(dataFresh) {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;

            const petaKey = {};
            Array.from(tbody.querySelectorAll('tr')).forEach((tr) => {
              if (tr.dataset.sumberKey) petaKey[tr.dataset.sumberKey] = tr;
            });

            const keyFresh = new Set();

            (dataFresh || []).forEach((baris) => {
              if (!baris.sumber_key) return;
              keyFresh.add(baris.sumber_key);
              const trAda = petaKey[baris.sumber_key];

              if (trAda) {
                trAda.dataset.namaGuru = baris.nama_guru || '';
                trAda.dataset.jenisSumber = baris.jenis_sumber || '';
                trAda.dataset.sumberAsal = baris.sumber_asal || trAda.dataset.sumberKey;
                if (trAda.dataset.manual === '1') return;

                ['masalah', 'jml_siswa_masalah', 'tindak_awal'].forEach((nama) => {
                  if (FIELD_SELALU_MANUAL.masalah.includes(nama)) return;
                  if (baris[nama] === undefined) return;
                  const el = trAda.querySelector(`[name="${nama}[]"]`);
                  if (!el) return;
                  setNilaiElemen(el, baris[nama]);
                  if (el.tagName === 'TEXTAREA') autoResizeTextarea(el);
                });
              } else {
                const row = buatBarisMasalah(baris.bidang, {
                  sumberKey: baris.sumber_key,
                  sumberAsal: baris.sumber_asal,
                  manual: false,
                  namaGuru: baris.nama_guru,
                  jenisSumber: baris.jenis_sumber,
                });
                const elMasalah = row.querySelector('[name="masalah[]"]');
                if (elMasalah) { setNilaiElemen(elMasalah, baris.masalah); autoResizeTextarea(elMasalah); }
              }
            });

            // Hapus baris auto lama yang sudah tidak ada di data terbaru (mis. data
            // sumbernya dihapus/diedit di modul asal) -- kecuali sudah diedit manual.
            Array.from(tbody.querySelectorAll('tr')).forEach((tr) => {
              if (tr.dataset.sumberKey && !keyFresh.has(tr.dataset.sumberKey) && tr.dataset.manual !== '1' && !tr.dataset.sumberKey.startsWith('manual-')) {
                tr.remove();
              }
            });

            pastikanSemuaBidangAdaBarisIV();
            terapkanRowspanBidangIV();
          }

          let seqMuatDataOtomatis = 0;

          // Mengambil data otomatis Bagian III & IV SELALU LENGKAP (tanpa filter
          // guru) dari server, lalu di-merge ke tabel. Filter Guru BK TIDAK memicu
          // fungsi ini lagi -- lihat terapkanFilterGuru() yang murni menyaring
          // tampilan di browser tanpa fetch ulang, supaya data tidak pernah
          // ter-reset/hilang/tertukar akibat gonta-ganti filter.
          async function muatDataOtomatis(bulan, tahun) {
            const seqSaya = ++seqMuatDataOtomatis;
            const tbodyRekap = document.querySelector('#rekapKegiatan tbody');

            pastikanSemuaBidangAdaBarisIV();

            try {
              const res = await fetch('laporanbk.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_rekap_otomatis', bulan, tahun }),
              });
              const hasil = await res.json();
              if (seqSaya !== seqMuatDataOtomatis) return;
              if (hasil.success) {
                mergeOtomatisKeTabel('#rekapKegiatan tbody', hasil.rekap || [], tambahRekap, KOLOM_REKAP, {
                  isiJikaKosongFields: ['bentuk_kegiatan'],
                  keteranganField: 'keterangan',
                  kunciKonten: true,
                });
                mergeMasalahIV(hasil.masalah || []);
              }
            } catch (e) {
              console.error(e);
            }

            if (seqSaya !== seqMuatDataOtomatis) return;

            if (!tbodyRekap || tbodyRekap.rows.length === 0) tambahRekap();

            sinkronTindakDariMasalah();

            terapkanFilterGuru();
          }

          async function prosesPerubahanBulan(saatMuatAwal) {
            const info = terapkanInfoBulan(document.getElementById('bulanLaporan').value);
            if (!info) return;

            try {
              const cekRes = await fetch('laporanbk.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'cek_laporan_bulan', bulan: info.bulan, tahun: info.tahun }),
              });
              const cek = await cekRes.json();
              if (cek.success && cek.ada) {
                window.location.href = 'laporanbk.php?id=' + cek.id_laporan;
                return;
              }
            } catch (e) {
              console.error(e);
            }

            await muatDataOtomatis(info.bulan, info.tahun);
          }

          function ambilBulanTahunAktif() {
            if (window.LAPORAN_BULAN_AKTIF && window.LAPORAN_TAHUN_AKTIF) {
              return { bulan: window.LAPORAN_BULAN_AKTIF, tahun: window.LAPORAN_TAHUN_AKTIF };
            }
            return terapkanInfoBulan(document.getElementById('bulanLaporan').value);
          }

          const elFilterGuruBK = document.getElementById('filterGuruBK');
          if (elFilterGuruBK) {
            // PENTING: ganti filter TIDAK memicu fetch/merge ulang ke server sama
            // sekali -- murni menyembunyikan/menampilkan baris yang sudah ada di
            // tabel (lihat terapkanFilterGuru). Ini yang membuat filter aman
            // diganti berkali-kali tanpa risiko data ter-reset, hilang, atau
            // tertukar antar bagian (III, IV, V) maupun saat dicetak ke PDF.
            elFilterGuruBK.addEventListener('change', function () {
              terapkanFilterGuru();
            });
          }

          document.addEventListener("DOMContentLoaded", () => {
            document
              .querySelectorAll(".animate-slide-in")
              .forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
              });

            isiDatalistSasaran();

            if (window.DATA_LAPORAN_EXISTING) {
              restoreSemuaTabel(window.DATA_LAPORAN_EXISTING);
              if (window.LAPORAN_BULAN_AKTIF && window.LAPORAN_TAHUN_AKTIF) {
                muatDataOtomatis(window.LAPORAN_BULAN_AKTIF, window.LAPORAN_TAHUN_AKTIF);
              } else {
                terapkanFilterGuru();
              }
            } else {
              siapkanLaporanBulanBaru();
            }

            const inputTgl = document.getElementById('inputTglTtd');
            if(inputTgl) {
                inputTgl.value = '';
                formatTanggalTtd('');
            }
          });

          function restoreSemuaTabel(data) {
            const rekap = data.rekap || [];
            const masalah = data.masalah || [];
            const tindak = data.tindak || [];

            rekap.forEach((baris) => {
              const tr = tambahRekap();
              isiBarisTerakhir(document.querySelector('#rekapKegiatan tbody'), KOLOM_REKAP, baris);
              tr.dataset.sumberKey = baris.sumber_key || '';
              tr.dataset.manual = baris.sumber_key ? (baris.manual ? '1' : '0') : '1';
              tr.dataset.namaGuru = baris.nama_guru || '';
            });

            // Bagian IV bisa berisi dua format data lama/baru:
            // - BARU: satu baris tersimpan = satu permasalahan (sumber_key unik
            //   per permasalahan, baik otomatis "masalah-..." maupun manual
            //   "manual-...").
            // - LAMA (sebelum fitur ini dipecah per-baris): satu baris tersimpan
            //   = satu bidang ("sumber_key" berupa "bidang-xxx") berisi beberapa
            //   permasalahan digabung dalam satu teks multi-baris. Ini otomatis
            //   dipecah jadi baris-baris terpisah supaya tetap tampil benar,
            //   dan ditandai manual (aman, tidak akan tertimpa data live).
            masalah.forEach((baris) => {
              const bidangLegacy = BIDANG_LAPORAN_BK.find((b) => baris.sumber_key === 'bidang-' + b.toLowerCase());
              const namaBidang = bidangLegacy || BIDANG_LAPORAN_BK.find((b) => b === baris.bidang) || BIDANG_LAPORAN_BK[0];

              if (bidangLegacy) {
                const teksGabung = String(baris.masalah || '').split(/\r\n|\r|\n/).map((s) => s.trim()).filter(Boolean);
                if (teksGabung.length === 0) teksGabung.push('');
                teksGabung.forEach((teks) => {
                  const tr = buatBarisMasalah(namaBidang, { manual: true });
                  const elMasalah = tr.querySelector('[name="masalah[]"]');
                  setNilaiElemen(elMasalah, teks);
                  autoResizeTextarea(elMasalah);
                });
                return;
              }

              const tr = buatBarisMasalah(namaBidang, {
                sumberKey: baris.sumber_key || buatKunciManualUnik('manual'),
                sumberAsal: baris.sumber_asal || baris.sumber_key,
                manual: !!baris.manual,
                namaGuru: baris.nama_guru || '',
                jenisSumber: baris.jenis_sumber || '',
              });
              // Isi langsung lewat referensi baris `tr` (bukan isiBarisTerakhir),
              // karena buatBarisMasalah menyisipkan baris secara posisional
              // (sesuai urutan bidang), belum tentu jadi baris terakhir di tbody.
              KOLOM_MASALAH.forEach((nama) => {
                const el = tr.querySelector(`[name="${nama}[]"]`);
                if (el && baris[nama] !== undefined) {
                  setNilaiElemen(el, baris[nama]);
                  if (el.tagName === 'TEXTAREA') autoResizeTextarea(el);
                }
              });
            });

            pastikanSemuaBidangAdaBarisIV();
            terapkanRowspanBidangIV();

            tindak.forEach((baris) => {
              const tr = tambahTindak();
              isiBarisTerakhir(document.querySelector('#tindakLanjut tbody'), KOLOM_TINDAK, baris);
              tr.dataset.sumberKey = baris.sumber_key || '';
              tr.dataset.manual = baris.sumber_key ? (baris.manual ? '1' : '0') : '1';
              tr.dataset.namaGuru = baris.nama_guru || '';
            });
          }

          function syncPrintText(selectEl, targetId) {
            const target = document.getElementById(targetId);
            target.textContent = selectEl.value;
          }

          window.addEventListener('beforeprint', function () {
            document.querySelectorAll('table select').forEach(function (sel) {
              const span = document.createElement('span');
              span.className = 'print-value-proxy';
              if (sel.multiple) {
                span.textContent = Array.from(sel.selectedOptions).map(function (o) { return o.text; }).join(', ');
              } else {
                span.textContent = sel.value
                  ? sel.options[sel.selectedIndex]?.text || sel.value
                  : '';
              }
              sel.parentNode.insertBefore(span, sel.nextSibling);
            });

            document.querySelectorAll('table input[type="date"]').forEach(function (inp) {
              const span = document.createElement('span');
              span.className = 'print-value-proxy';

              if (inp.value) {
                const d = new Date(inp.value);
                const bulan = ['Januari','Februari','Maret','April','Mei','Juni',
                               'Juli','Agustus','September','Oktober','November','Desember'];
                span.textContent = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
              } else {
                span.textContent = '';
              }

              inp.parentNode.insertBefore(span, inp.nextSibling);
            });

            document
              .querySelectorAll('table input[type="text"], table input[type="number"]')
              .forEach(function (inp) {
                const span = document.createElement('span');
                span.className = 'print-value-proxy';
                span.textContent = inp.value || '';
                inp.parentNode.insertBefore(span, inp.nextSibling);
              });

            document.querySelectorAll('table textarea').forEach(function (ta) {
              const span = document.createElement('span');
              span.className = 'print-value-proxy';
              span.textContent = ta.value || '';
              ta.parentNode.insertBefore(span, ta.nextSibling);
            });
          });

          window.addEventListener('afterprint', function () {
            document.querySelectorAll('.print-value-proxy').forEach(function (el) {
              el.remove();
            });
            document.querySelectorAll('.report-section[data-was-closed]').forEach(function (el) {
              el.open = false;
              el.removeAttribute('data-was-closed');
            });
          });

          window.addEventListener('beforeprint', function () {
            document.querySelectorAll('.report-section').forEach(function (el) {
              if (!el.open) {
                el.setAttribute('data-was-closed', '1');
                el.open = true;
              }
            });
          });

          function kumpulkanBarisTabel(tbodySelector, kolom) {
            const hasil = [];
            document.querySelectorAll(`${tbodySelector} tr`).forEach((tr) => {
              // Baris filler (lihat buatBarisFillerBidang) murni tampilan saat
              // Filter Guru BK menyembunyikan seluruh data suatu bidang -- tidak
              // pernah ikut disimpan sebagai data laporan.
              if (tr.dataset.filler === '1') return;

              const baris = {};
              let adaIsi = false;
              kolom.forEach((nama) => {
                const el = tr.querySelector(`[name="${nama}[]"]`);
                const val = el ? ambilNilaiElemen(el) : '';
                baris[nama] = val;
                if (val) adaIsi = true;
              });
              if (tr.dataset.sumberKey) {
                adaIsi = true;
                baris.sumber_key = tr.dataset.sumberKey;
                baris.sumber_asal = tr.dataset.sumberAsal || tr.dataset.sumberKey;
                baris.manual = tr.dataset.manual === '1';
                baris.nama_guru = tr.dataset.namaGuru || '';
                if (tr.dataset.jenisSumber !== undefined) baris.jenis_sumber = tr.dataset.jenisSumber || '';
              }
              // Bagian IV: bidang bersifat struktural (bukan field form), tapi
              // tetap harus ikut tersimpan supaya baris bisa direstore ke grup
              // bidang yang benar saat laporan dibuka lagi.
              if (tr.dataset.bidang) {
                adaIsi = true;
                baris.bidang = tr.dataset.bidang;
              }
              if (adaIsi) hasil.push(baris);
            });
            return hasil;
          }

          function kumpulkanDataForm() {
            const fd = new FormData();
            const bulanVal = document.getElementById('bulanLaporan').value;
            const [thnPart, blnPart] = bulanVal ? bulanVal.split('-') : ['', ''];
            fd.append('id_laporan', document.getElementById('idLaporan').value || 0);
            fd.append('nama_dokumen', document.getElementById('namaDokumen').value);
            fd.append('bulan', blnPart ? parseInt(blnPart, 10) : '');
            fd.append('tahun', thnPart ? parseInt(thnPart, 10) : '');
            fd.append('koordinator_nip', document.getElementById('nipKoordinator').value);
            fd.append('nama_koordinator', document.getElementById('pilihKoordinator').value);
            fd.append('nama_guru_bk', document.getElementById('pilihGuruBK').value);
            fd.append('nip_guru_bk', document.getElementById('nipGuruBK').value);

            const fotoSrcs = Array.from(document.querySelectorAll('#dokumentasi img')).map(img => img.src);
            fd.append('dokumentasi_json', JSON.stringify(fotoSrcs));

            fd.append('rekap_json', JSON.stringify(kumpulkanBarisTabel('#rekapKegiatan tbody', KOLOM_REKAP)));
            fd.append('masalah_json', JSON.stringify(kumpulkanBarisTabel('#rekapMasalah tbody', KOLOM_MASALAH)));
            fd.append('tindak_json', JSON.stringify(kumpulkanBarisTabel('#tindakLanjut tbody', KOLOM_TINDAK)));

            return fd;
          }

          function terapkanStatusUI(status) {
            document.getElementById('statusLaporan').value = status;
            const badge = document.getElementById('badgeStatus');
            const isFinal = status === 'final';

            badge.textContent = isFinal ? '🟢 Final' : '🟡 Belum Final';
            badge.className = 'px-3 py-1 rounded-full text-sm font-semibold ' +
              (isFinal ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700');

            document.getElementById('btnSimpan').classList.toggle('hidden', isFinal);
            document.getElementById('btnFinalisasi').classList.toggle('hidden', isFinal);
            document.getElementById('btnBukaDraft').classList.toggle('hidden', !isFinal);
            document.getElementById('btnResetForm').classList.toggle('hidden', isFinal);
            document.getElementById('btnCetak').disabled = !isFinal;

            document.querySelectorAll('#main-content input, #main-content select, #main-content textarea').forEach(el => {
              el.disabled = isFinal;
            });
          }

          function simpanDokumen() {
            const btn = document.getElementById('btnSimpan');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';

            const fd = kumpulkanDataForm();
            fd.append('action', 'simpan');

            fetch(window.location.pathname, { method: 'POST', body: fd })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  document.getElementById('idLaporan').value = data.id_laporan;
                  const url = new URL(window.location);
                  url.searchParams.set('id', data.id_laporan);
                  window.history.replaceState({}, '', url);
                  alert(data.message);
                } else {
                  alert('Gagal: ' + data.message);
                }
              })
              .catch(() => alert('Terjadi kesalahan koneksi saat menyimpan.'))
              .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Perubahan';
              });
          }

          function finalisasiDokumen() {
            const idLaporan = document.getElementById('idLaporan').value;
            if (!idLaporan || idLaporan == 0) {
              alert('Simpan laporan ini terlebih dahulu sebelum menyelesaikannya.');
              return;
            }
            if (!confirm('Setelah diselesaikan, laporan akan terkunci dan tidak bisa diedit lagi (hanya bisa dicetak). Lanjutkan?')) return;

            const fd = new FormData();
            fd.append('action', 'finalisasi');
            fd.append('id_laporan', idLaporan);

            fetch(window.location.pathname, { method: 'POST', body: fd })
              .then(res => res.json())
              .then(data => {
                alert(data.message);
                if (data.success) terapkanStatusUI('final');
              })
              .catch(() => alert('Terjadi kesalahan koneksi saat finalisasi.'));
          }

          function bukaSebagaiDraft() {
            const idLaporan = document.getElementById('idLaporan').value;
            if (!confirm('Laporan akan dibuka kembali agar bisa diedit. Lanjutkan?')) return;

            const fd = new FormData();
            fd.append('action', 'buka_draft');
            fd.append('id_laporan', idLaporan);

            fetch(window.location.pathname, { method: 'POST', body: fd })
              .then(res => res.json())
              .then(data => {
                alert(data.message);
                if (data.success) terapkanStatusUI('draft');
              })
              .catch(() => alert('Terjadi kesalahan koneksi.'));
          }

          document.addEventListener('DOMContentLoaded', () => {
            const statusAwal = document.getElementById('statusLaporan').value;
            terapkanStatusUI(statusAwal);

            const boxDokumentasi = document.getElementById('dokumentasi');
            const fotoTersimpan = (window.DATA_LAPORAN_EXISTING && window.DATA_LAPORAN_EXISTING.dokumentasi) || [];
            if (boxDokumentasi && fotoTersimpan.length > 0) {
              boxDokumentasi.innerHTML = '';
              fotoTersimpan.forEach(src => renderFotoDokumentasi(boxDokumentasi, src));
            }

            const pilihKoordinatorEl = document.getElementById('pilihKoordinator');
            const pilihGuruBKEl = document.getElementById('pilihGuruBK');
            if (pilihKoordinatorEl) syncPrintText(pilihKoordinatorEl, 'printKoordinator');
            if (pilihGuruBKEl) syncPrintText(pilihGuruBKEl, 'printGuruBK');

            const nipKoordinatorEl = document.getElementById('nipKoordinator');
            const nipGuruBKEl = document.getElementById('nipGuruBK');
            if (nipKoordinatorEl) document.getElementById('valNipKoordinator').textContent = nipKoordinatorEl.value;
            if (nipGuruBKEl) document.getElementById('valNipGuruBK').textContent = nipGuruBKEl.value;
          });
        </script>
      </main>
    </div>
  </body>
</html>