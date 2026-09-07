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

  $GURU_ID_BY_NAMA = [];
  $q_guru_filter = mysqli_query($koneksi, "SELECT id_guru, nama FROM guru");
  if ($q_guru_filter) {
    while ($guru_filter = mysqli_fetch_assoc($q_guru_filter)) {
      $GURU_ID_BY_NAMA[trim((string) $guru_filter['nama'])] = (int) $guru_filter['id_guru'];
    }
  }

  function getTeacherIdBK($namaGuru) {
    global $GURU_ID_BY_NAMA;
    $namaGuru = trim((string) $namaGuru);
    return $GURU_ID_BY_NAMA[$namaGuru] ?? null;
  }

function hitungSemesterTahunAjaran($bulan, $tahun) {
    if ($bulan >= 7 && $bulan <= 12) {
        return ['semester' => 'Ganjil', 'tahun_pelajaran' => $tahun . '/' . ($tahun + 1)];
    }
    return ['semester' => 'Genap', 'tahun_pelajaran' => ($tahun - 1) . '/' . $tahun];
}

const BIDANG_LAPORAN_BK = ['Pribadi', 'Belajar', 'Sosial', 'Karier'];

const TEMPLATE_BENTUK_KEGIATAN_BK = [
    'Konseling Individu'   => 'Konseling tatap muka dan pendampingan siswa',
    'Konseling Kelompok'   => 'Diskusi kelompok dan pendampingan siswa',
    'Bimbingan Kelompok'   => 'Diskusi kelompok dan pendampingan siswa',
    'Home Visit'           => 'Kunjungan dan pendampingan siswa di rumah',
    'Panggilan Ortu'       => 'Pertemuan dan koordinasi bersama orang tua/wali',
    'Konsultasi Siswa'     => 'Konsultasi tatap muka langsung bersama siswa',
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
                      WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'
                        AND ko.jenis_konsultasi = 'ortu'",
            'groupby' => '',
        ],
        [
            'label' => 'Konsultasi Siswa',
            'sql' => "SELECT ko.tanggal_pemanggilan AS tgl, 'Konsultasi Siswa' AS jenis, ko.nama_guru_bk AS guru,
                        COALESCE(NULLIF(s.kelas,''), NULLIF(ko.kelas,''), '-') AS kelas,
                        COALESCE(NULLIF(s.jurusan,''), NULLIF(ko.jurusan,''), '-') AS jurusan, 1 AS jml
                      FROM konsultasi_ortu ko
                      LEFT JOIN siswa s ON s.nis = ko.nis
                      WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'
                        AND ko.jenis_konsultasi = 'siswa'",
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
            'teacher_id'      => getTeacherIdBK($g['guru']) ?? null,
          ];
        }
    return $hasil;
}

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
                        AND ko.jenis_konsultasi = 'ortu'
                      ORDER BY ko.id_konsultasi ASC",
        ],
        [
            'label' => 'Konsultasi Siswa',
            'sql' => "SELECT ko.id_konsultasi AS id_sumber, ko.nama_guru_bk AS guru, ko.bidang_layanan AS bidang, ko.permasalahan AS teks
                      FROM konsultasi_ortu ko
                      WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'
                        AND ko.jenis_konsultasi = 'siswa'
                      ORDER BY ko.id_konsultasi ASC",
        ],
    ];

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

            $barisTeks = preg_split('/\r\n|\r|\n/', $teksRaw);
            $barisTeks = array_values(array_filter(array_map('trim', $barisTeks), fn($x) => $x !== ''));
            if (empty($barisTeks)) continue;

            $jenisSumber = !empty($r['jenis']) ? $r['jenis'] : $m['label'];
            $guru = trim((string) ($r['guru'] ?? ''));
            $idSumber = (string) ($r['id_sumber'] ?? '');

            foreach ($bidangNorm as $b) {
                foreach ($barisTeks as $iBaris => $teksBaris) {

                    $kunciMentah = $m['label'] . '|' . $idSumber . '|' . $iBaris . '|' . $b;

                    $kunciAsal = $m['label'] . '|' . $idSumber . '|' . $iBaris;
                    $per_bidang[$b][] = [
                        'sumber_key'        => 'masalah-' . md5($kunciMentah),
                        'sumber_asal'       => 'asal-' . md5($kunciAsal),
                        'bidang'            => $b,
                        'masalah'           => $teksBaris,
                        'jml_siswa_masalah' => '',
                        'tindak_awal'       => '',
                        'nama_guru'         => $guru,
                        'teacher_id'        => getTeacherIdBK($guru),
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

/**
 * Memecah isi kolom dokumentasi (text) milik home_visit / konsultasi_ortu
 * menjadi array path foto. Mendukung format JSON array maupun daftar
 * path yang dipisah koma / baris baru, supaya tidak perlu mengubah
 * struktur tabel yang sudah ada.
 */
function parseDaftarFotoBK($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') return [];

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $out = [];
        foreach ($decoded as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            } elseif (is_array($item) && !empty($item['path'])) {
                $out[] = trim($item['path']);
            }
        }
        return $out;
    }

    $parts = preg_split('/[,\r\n]+/', $raw);
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p !== '') $out[] = $p;
    }
    return $out;
}

/**
 * Mengumpulkan foto dokumentasi OTOMATIS dari layanan BK
 * (Konseling Individu, Konseling/Bimbingan Kelompok, Home Visit,
 * Konsultasi Orang Tua, Konsultasi Siswa) sesuai rentang tanggal
 * laporan bulan berjalan. Guru pada tiap foto mengikuti guru yang
 * menginput data layanan tersebut (bukan guru lain).
 */
function getFotoOtomatisBK($koneksi, $awal, $akhir) {
    $hasil = [];

    // 1) Konseling Individu -> tabel dokumentasi_konseling
    $q = mysqli_query($koneksi, "
        SELECT dk.id_dokumentasi, dk.file_path, ki.nama_guru AS guru, ki.tanggal_pelaksanaan AS tgl
        FROM dokumentasi_konseling dk
        JOIN konseling_individu ki ON ki.id_konseling = dk.id_konseling
        WHERE ki.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'
        ORDER BY dk.id_dokumentasi ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            if (trim((string) $r['file_path']) === '') continue;
            $hasil[] = [
                'sumber_key' => 'auto-ki-' . $r['id_dokumentasi'],
                'sumber'     => 'Konseling Individu',
                'guru'       => trim((string) $r['guru']),
                'teacher_id' => getTeacherIdBK($r['guru']),
                'tanggal'    => $r['tgl'],
                'path'       => $r['file_path'],
            ];
        }
    } else {
        error_log('Foto otomatis BK (konseling individu) gagal: ' . mysqli_error($koneksi));
    }

    // 2) Konseling / Bimbingan Kelompok -> tabel dokumentasi_kelompok
    $q = mysqli_query($koneksi, "
        SELECT dkk.id_dokumentasi, dkk.file_path, k.nama_guru AS guru, k.tanggal_pelaksanaan AS tgl
        FROM dokumentasi_kelompok dkk
        JOIN kelompok k ON k.id_kelompok = dkk.id_kelompok
        WHERE k.tanggal_pelaksanaan BETWEEN '$awal' AND '$akhir'
        ORDER BY dkk.id_dokumentasi ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            if (trim((string) $r['file_path']) === '') continue;
            $hasil[] = [
                'sumber_key' => 'auto-kel-' . $r['id_dokumentasi'],
                'sumber'     => 'Konseling/Bimbingan Kelompok',
                'guru'       => trim((string) $r['guru']),
                'teacher_id' => getTeacherIdBK($r['guru']),
                'tanggal'    => $r['tgl'],
                'path'       => $r['file_path'],
            ];
        }
    } else {
        error_log('Foto otomatis BK (kelompok) gagal: ' . mysqli_error($koneksi));
    }

    // 3) Home Visit -> kolom text `dokumentasi` pada tabel home_visit
    $q = mysqli_query($koneksi, "
        SELECT hv.id_visit, hv.dokumentasi, hv.nama_petugas AS guru, hv.hari_tanggal AS tgl
        FROM home_visit hv
        WHERE hv.hari_tanggal BETWEEN '$awal' AND '$akhir'
        ORDER BY hv.id_visit ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $daftar = parseDaftarFotoBK($r['dokumentasi']);
            foreach ($daftar as $i => $path) {
                $hasil[] = [
                    'sumber_key' => 'auto-hv-' . $r['id_visit'] . '-' . $i,
                    'sumber'     => 'Home Visit',
                    'guru'       => trim((string) $r['guru']),
                    'teacher_id' => getTeacherIdBK($r['guru']),
                    'tanggal'    => $r['tgl'],
                    'path'       => $path,
                ];
            }
        }
    } else {
        error_log('Foto otomatis BK (home visit) gagal: ' . mysqli_error($koneksi));
    }

    // 4) Konsultasi Orang Tua & Konsultasi Siswa -> kolom text `dokumentasi`
    //    pada tabel konsultasi_ortu (dipakai bersama untuk kedua jenis layanan)
    $q = mysqli_query($koneksi, "
        SELECT ko.id_konsultasi, ko.dokumentasi, ko.nama_guru_bk AS guru, ko.tanggal_pemanggilan AS tgl
        FROM konsultasi_ortu ko
        WHERE ko.tanggal_pemanggilan BETWEEN '$awal' AND '$akhir'
        ORDER BY ko.id_konsultasi ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $daftar = parseDaftarFotoBK($r['dokumentasi']);
            foreach ($daftar as $i => $path) {
                $hasil[] = [
                    'sumber_key' => 'auto-ko-' . $r['id_konsultasi'] . '-' . $i,
                    'sumber'     => 'Konsultasi Ortu/Siswa',
                    'guru'       => trim((string) $r['guru']),
                    'teacher_id' => getTeacherIdBK($r['guru']),
                    'tanggal'    => $r['tgl'],
                    'path'       => $path,
                ];
            }
        }
    } else {
        error_log('Foto otomatis BK (konsultasi) gagal: ' . mysqli_error($koneksi));
    }

    // Dedup berbasis path+sumber_key supaya foto yang sama tidak tampil dobel
    $unik = [];
    $dilihat = [];
    foreach ($hasil as $f) {
        $cek = $f['sumber_key'] . '|' . $f['path'];
        if (isset($dilihat[$cek])) continue;
        $dilihat[$cek] = true;
        $unik[] = $f;
    }

    return $unik;
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

        $rekap   = getRekapOtomatisBK($koneksi, $awal, $akhir);
        $masalah = getMasalahOtomatisBK($koneksi, $awal, $akhir);
        $foto    = getFotoOtomatisBK($koneksi, $awal, $akhir);
        echo json_encode(['success' => true, 'rekap' => $rekap, 'masalah' => $masalah, 'foto' => $foto]);
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

$bulanDariUrl = '';
if (!$laporan && isset($_GET['bulan']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['bulan'])) {
    $bulanDariUrl = $_GET['bulan'];
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
    min-height: 0 !important;
    height: auto !important;
  }

  html {
    min-height: 0 !important;
    height: auto !important;
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
    box-sizing: border-box !important;
  }

  th, td {
    box-sizing: border-box !important;
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

  th:last-child,
  td:last-child {
    display: none !important;
  }

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

  #rekapMasalah th,
  #rekapMasalah td {
    font-size: 9.5pt !important;
    line-height: 1.25 !important;
    padding: 3pt 4pt !important;
  }

  #rekapMasalah td.sel-permasalahan,
  #rekapMasalah td.sel-tindak {
    padding-top: 3pt !important;
    padding-bottom: 3pt !important;
  }

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
    page-break-before: auto !important;
    break-before: auto !important;
    margin-top: 8pt !important;
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
    gap: 0 20pt !important;
    margin-top: 8pt !important;
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
    height: 28pt !important;
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
    margin-bottom: 12pt !important;
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
    break-before: page !important;
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
    page-break-after: avoid !important;
    break-after: avoid !important;
  }

  /* Layout flex-wrap (bukan CSS grid) dipakai supaya browser dapat memecah
     dokumentasi ke halaman berikutnya secara otomatis tanpa memotong foto
     atau merusak tata letak, berapa pun jumlah fotonya (tidak dibatasi). */
  #dokumentasi {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 6pt !important;
    width: 100% !important;
  }

  #dokumentasi > div {
    display: block !important;
    position: relative !important;
    overflow: hidden !important;
    width: calc(33.333% - 4pt) !important;
    height: 178pt !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  #dokumentasi img {
    display: block !important;
    width: 100% !important;
    height: 178pt !important;
    object-fit: cover !important;
    border: 1pt solid #888888 !important;
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
    width: 100% !important;
    box-sizing: border-box !important;
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

          <div class="no-print mb-3 flex items-center justify-between flex-wrap gap-2">
            <span id="badgeStatus" class="px-3 py-1 rounded-full text-sm font-semibold <?php echo ($laporan && $laporan['status'] === 'final') ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
              <?php echo ($laporan && $laporan['status'] === 'final') ? '🟢 Final - Terkunci' : '🟡 Draft - Belum Dikunci'; ?>
            </span>
            <a href="riwayat_laporanbk.php" class="sm:hidden text-sm text-blue-600 hover:underline">
              <i class="fas fa-clock-rotate-left mr-1"></i> Lihat Riwayat Laporan
            </a>
          </div>

          <div class="no-print mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
              <p class="text-sm font-bold text-blue-800 flex items-center gap-2">
                <i class="fas fa-calendar-week"></i> Laporan untuk Bulan Mana?
              </p>
              <?php if ($laporan): ?>
                <span class="text-xs text-gray-500">
                  <i class="fas fa-lock mr-1"></i>Bulan tidak bisa diubah lagi untuk laporan yang sudah dibuat. Gunakan tombol panah untuk pindah ke laporan bulan lain.
                </span>
              <?php else: ?>
                <span class="text-xs text-gray-500">
                  <i class="fas fa-circle-info mr-1"></i>Pilih bulan, atau geser pakai tombol panah di samping.
                </span>
              <?php endif; ?>
            </div>
            <div class="flex items-stretch gap-2 relative">
              <button type="button" id="btnBulanSebelumnya" title="Bulan sebelumnya"
                class="px-3 rounded-lg border border-blue-300 bg-white text-blue-700 hover:bg-blue-100 transition font-semibold">
                <i class="fas fa-chevron-left"></i>
              </button>
              <input type="month" id="bulanLaporan"
                class="flex-1 min-w-0 px-3 py-2.5 border-2 border-blue-300 rounded-lg text-sm font-semibold text-blue-900 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                <?php
                  $bulanValueAwal = '';
                  if ($laporan) {
                      $b = (int) $laporan['bulan'];
                      $tp = explode('/', $laporan['tahun_pelajaran']);
                      $tahunAwal = ($b >= 7) ? (int) ($tp[0] ?? date('Y')) : (int) ($tp[1] ?? date('Y'));
                      $bulanValueAwal = sprintf('%04d-%02d', $tahunAwal, $b);
                  } elseif ($bulanDariUrl) {
                      $bulanValueAwal = $bulanDariUrl;
                  }
                ?>
                value="<?php echo $bulanValueAwal; ?>"
                <?php echo $laporan ? 'readonly' : ''; ?>>
              <button type="button" id="btnBulanBerikutnya" title="Bulan berikutnya"
                class="px-3 rounded-lg border border-blue-300 bg-white text-blue-700 hover:bg-blue-100 transition font-semibold">
                <i class="fas fa-chevron-right"></i>
              </button>
              <button type="button" id="btnBulanCepat" title="Lompat ke bulan tertentu"
                class="px-3 rounded-lg border border-blue-300 bg-white text-blue-700 hover:bg-blue-100 transition font-semibold">
                <i class="fas fa-calendar-days"></i>
              </button>
              <div id="panelBulanCepat" class="hidden absolute top-full left-0 right-0 sm:right-auto sm:w-72 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-20 p-3">
                <div class="flex items-center justify-between mb-2">
                  <button type="button" id="btnTahunCepatMundur" class="w-7 h-7 rounded hover:bg-gray-100 text-gray-500"><i class="fas fa-chevron-left text-xs"></i></button>
                  <span id="labelTahunCepat" class="text-sm font-bold text-gray-700"></span>
                  <button type="button" id="btnTahunCepatMaju" class="w-7 h-7 rounded hover:bg-gray-100 text-gray-500"><i class="fas fa-chevron-right text-xs"></i></button>
                </div>
                <div id="gridBulanCepat" class="grid grid-cols-3 gap-1.5"></div>
              </div>
              <a href="riwayat_laporanbk.php" title="Lihat semua riwayat laporan"
                class="hidden sm:inline-flex items-center gap-2 px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">
                <i class="fas fa-clock-rotate-left"></i> Riwayat
              </a>
            </div>
            <p class="text-xs text-gray-500 mt-2 no-print" id="hintBulanLaporan">
              <?php echo $laporan ? 'Bulan tidak bisa diubah setelah laporan dibuat, tapi isinya tetap bisa diedit.' : 'Semester & tahun pelajaran mengikuti bulan yang dipilih.'; ?>
            </p>
          </div>

          <div class="no-print mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Dokumen</label>
              <input type="text" id="namaDokumen" placeholder="Terisi otomatis, bisa diedit"
                class="w-full px-3 py-2 border rounded text-sm"
                value="<?php echo $laporan ? htmlspecialchars($laporan['nama_dokumen']) : ''; ?>">
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
                <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt):
                  $teacher_id_opt = getTeacherIdBK($nama_guru_opt);
                  $filter_value_opt = $teacher_id_opt !== null ? (string) $teacher_id_opt : 'nama:' . $nama_guru_opt;
                ?>
                <option value="<?php echo htmlspecialchars($filter_value_opt); ?>" data-teacher-id="<?php echo $teacher_id_opt !== null ? $teacher_id_opt : ''; ?>" data-nama-guru="<?php echo htmlspecialchars($nama_guru_opt); ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
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
              <div class="no-print mb-4">
                <p class="text-xs font-medium text-gray-600 mb-2">Tambah baris permasalahan ke bidang:</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <?php foreach (BIDANG_LAPORAN_BK as $b): ?>
                  <button type="button" onclick="tambahBarisMasalahManual('<?php echo htmlspecialchars($b, ENT_QUOTES); ?>')"
                    class="flex items-center justify-center gap-1.5 bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 px-3 py-2 rounded-lg text-xs font-semibold border border-gray-300 hover:border-blue-300 shadow-sm transition-colors">
                    <i class="fas fa-plus text-[10px]"></i><span><?php echo htmlspecialchars($b); ?></span>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="table-scroll-wrapper">
                <table
                  id="rekapMasalah"
                  class="w-full border-collapse border border-gray-300"
                >
                  <thead>
                    <tr class="bg-gray-200">
                      <th class="col-no border border-gray-300 px-1 py-2 text-sm text-center whitespace-nowrap">No</th>
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
            <p class="no-print text-gray-500 text-xs ms-5 mb-2">
              Foto dari Konseling Individu, Konseling/Bimbingan Kelompok, Home Visit, Konsultasi Ortu, dan
              Konsultasi Siswa pada bulan ini akan otomatis muncul di sini. Untuk foto tambahan, pilih Guru
              terlebih dahulu lalu pilih foto (tidak ada batas jumlah, maksimal 2 MB per foto).
            </p>
            <div class="no-print flex flex-col sm:flex-row gap-2 mb-4">
              <select id="pilihGuruManualFoto" class="input px-3 py-2 border rounded-lg text-sm sm:w-1/3">
                <option value="">1. Pilih Guru</option>
                <?php foreach ($DAFTAR_GURU_BK as $nama_guru_opt):
                  $teacher_id_opt = getTeacherIdBK($nama_guru_opt);
                ?>
                <option value="<?php echo htmlspecialchars($nama_guru_opt); ?>" data-id-guru="<?php echo $teacher_id_opt !== null ? $teacher_id_opt : ''; ?>"><?php echo htmlspecialchars($nama_guru_opt); ?></option>
                <?php endforeach; ?>
              </select>
              <input
                type="file"
                accept="image/*"
                multiple
                onchange="previewFoto(event)"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 w-full sm:flex-1"
              />
            </div>

            <div
              id="dokumentasi"
              class="grid grid-cols-2 md:grid-cols-3 gap-4"
            ></div>
          </div>

          <div class="no-print mt-2 mb-4 text-center">
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wide">Langkah Selanjutnya</h4>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 no-print" id="actionButtons">
            <button
              id="btnSimpan"
              onclick="simpanDokumen()"
              class="flex flex-col items-center text-center gap-1 bg-emerald-600 text-white px-4 py-4 rounded-xl hover:bg-emerald-700 transition font-semibold shadow-sm"
              title="Menyimpan perubahan, dokumen masih berstatus draft dan bisa diedit lagi kapan saja"
            >
              <i class="fas fa-save text-xl mb-1"></i>
              <span>Simpan Perubahan</span>
              <span class="text-xs font-normal opacity-90">Tetap draft, bisa diedit lagi</span>
            </button>
            <button
              id="btnFinalisasi"
              onclick="finalisasiDokumen()"
              class="flex flex-col items-center text-center gap-1 bg-indigo-600 text-white px-4 py-4 rounded-xl hover:bg-indigo-700 transition font-semibold shadow-sm"
              title="Mengunci dokumen supaya siap dicetak. Masih bisa dibuka lagi untuk diedit lewat tombol Buka Kunci"
            >
              <i class="fas fa-flag-checkered text-xl mb-1"></i>
              <span>Selesaikan & Kunci</span>
              <span class="text-xs font-normal opacity-90">Siap dicetak, masih bisa dibuka lagi</span>
            </button>
            <button
              id="btnBukaDraft"
              onclick="bukaSebagaiDraft()"
              class="hidden flex flex-col items-center text-center gap-1 bg-amber-600 text-white px-4 py-4 rounded-xl hover:bg-amber-700 transition font-semibold shadow-sm"
              title="Membuka kembali laporan yang sudah dikunci agar bisa diedit"
            >
              <i class="fas fa-lock-open text-xl mb-1"></i>
              <span>Buka Kunci untuk Edit</span>
              <span class="text-xs font-normal opacity-90">Kembali ke status draft</span>
            </button>
            <button
              id="btnCetak"
              onclick="cetakLaporan()"
              disabled
              class="flex flex-col items-center text-center gap-1 bg-blue-600 text-white px-4 py-4 rounded-xl hover:bg-blue-700 transition font-semibold shadow-sm disabled:opacity-40 disabled:cursor-not-allowed"
              title="Hanya bisa dicetak setelah laporan diselesaikan dan dikunci"
            >
              <i class="fas fa-file-pdf text-xl mb-1"></i>
              <span>Cetak / Simpan PDF</span>
              <span class="text-xs font-normal opacity-90">Aktif setelah dikunci</span>
            </button>
          </div>
          <div id="draftRecovery" class="hidden no-print mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            <div class="font-semibold">Draft lokal ditemukan</div>
            <div id="draftRecoveryText" class="mt-1"></div>
            <div class="mt-2 flex flex-wrap gap-2">
              <button type="button" onclick="pulihkanDraftLokal()" class="rounded bg-amber-600 px-3 py-1.5 text-white hover:bg-amber-700">Pulihkan Draft</button>
              <button type="button" onclick="gunakanDataServer()" class="rounded border border-amber-400 px-3 py-1.5 text-amber-900 hover:bg-amber-100">Gunakan Data Server</button>
            </div>
          </div>
          <div id="draftStatus" class="no-print mt-2 text-center text-xs text-gray-500"></div>
          <div class="no-print mt-3 text-center">
            <button
              id="btnResetForm"
              onclick="resetForm()"
              class="inline-flex items-center gap-2 text-gray-500 hover:text-red-600 text-sm font-medium transition"
              title="Mengosongkan semua isian di form ini, tidak menghapus data yang sudah tersimpan sampai kamu menekan Simpan"
            >
              <i class="fas fa-redo"></i> Kosongkan Semua Isian
            </button>
            <button
              type="button"
              id="btnSinkronkanData"
              onclick="sinkronkanData()"
              class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm font-medium transition"
              title="Mengambil data otomatis terbaru tanpa memuat ulang halaman"
            >
              <i class="fas fa-sync-alt"></i> <span id="labelSinkronkanData">Sinkronkan Data</span>
            </button>
            <button
              type="button"
              onclick="hapusDraftLokal(true)"
              class="inline-flex items-center gap-2 text-gray-500 hover:text-red-600 text-sm font-medium transition"
              title="Menghapus draft lokal saja, tidak menghapus data database"
            >
              <i class="fas fa-trash-alt"></i> Hapus Draft Lokal
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

            row.className = "hover:bg-gray-50 transition-colors";
            row.dataset.sumberKey = '';
            row.dataset.manual = '1';
            row.dataset.namaGuru = '';
            row.dataset.teacherId = '';

            row.innerHTML = `
            <td class="border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700"></td>
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
                <button type="button" onclick="hapusBarisDenganNomor(this)" class="text-red-500 hover:text-red-700 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            perbaruiNomorBaris(tbody);
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

            let nomor = 0;
            Array.from(tbody.rows).forEach((tr) => {
              const selNo = tr.cells[0];
              if (!selNo) return;
              if (tr.classList.contains('baris-tersembunyi-filter')) {
                selNo.textContent = '';
                return;
              }
              nomor += 1;
              selNo.textContent = nomor;
            });
          }

          function hapusBarisDenganNomor(btn) {
            const tr = btn.closest('tr');
            const tbody = tr ? tr.closest('tbody') : null;
            if (!tr || !tbody) return;
            tr.remove();
            perbaruiNomorBaris(tbody);
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

          function buatBarisMasalah(namaBidang, opts) {
            opts = opts || {};
            const table = document.getElementById("rekapMasalah");
            const tbody = table.querySelector("tbody");
            const row = document.createElement('tr');

            const barisBidangSama = Array.from(tbody.querySelectorAll('tr'))
              .filter((tr) => tr.dataset.bidang === namaBidang && tr.dataset.filler !== '1');

            if (barisBidangSama.length > 0) {

              const last = barisBidangSama[barisBidangSama.length - 1];
              if (last.nextSibling) last.parentNode.insertBefore(row, last.nextSibling);
              else last.parentNode.appendChild(row);
            } else {

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

            row.dataset.sumberAsal = opts.sumberAsal || row.dataset.sumberKey;
            row.dataset.manual = opts.manual ? '1' : '0';
            row.dataset.namaGuru = opts.namaGuru || '';
            row.dataset.teacherId = opts.teacherId || '';
            row.dataset.jenisSumber = opts.jenisSumber || '';

            row.innerHTML = `
            <td class="sel-no border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700"></td>
            <td class="sel-bidang border border-gray-300 px-1 py-2 text-sm font-medium text-gray-700"></td>
            <td class="sel-permasalahan border border-gray-300 px-1 py-1">
                <textarea name="masalah[]" rows="1" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="-" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="sel-jumlah border border-gray-300 px-1 py-1 text-center">
                <input type="number" name="jml_siswa_masalah[]" min="0" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none text-center" placeholder="-" oninput="if(this.value<0)this.value=0; tandaiManual(this)">
            </td>
            <td class="sel-tindak border border-gray-300 px-1 py-1">
                <textarea name="tindak_awal[]" rows="1" class="w-full px-1 py-1 border-0 focus:ring-0 text-sm bg-transparent outline-none resize-none overflow-hidden align-middle" placeholder="-" oninput="autoResizeTextarea(this); tandaiManual(this)"></textarea>
            </td>
            <td class="sel-aksi border border-gray-300 px-1 py-1 text-center no-print">
                <button type="button" onclick="hapusBarisMasalah(this)" class="text-red-500 hover:text-red-700 transition" title="Hapus baris permasalahan ini">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            return row;
          }

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
        <td class="sel-permasalahan border border-gray-300 px-1 py-2 text-center text-sm text-gray-700 font-bold">-</td>
        <td class="sel-jumlah border border-gray-300 px-1 py-2 text-center text-sm text-gray-700 font-bold">-</td>
        <td class="sel-tindak border border-gray-300 px-1 py-2 text-center text-sm text-gray-700 font-bold">-</td>
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

          function pastikanFillerBidangIV() {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;
            BIDANG_LAPORAN_BK.forEach((b) => {
              const ada = Array.from(tbody.querySelectorAll('tr')).some((tr) => tr.dataset.bidang === b && tr.dataset.filler === '1');
              if (!ada) buatBarisFillerBidang(b);
            });
          }

          function tambahBarisMasalahManual(namaBidang) {
            const filter = document.getElementById('filterGuruBK');
            const option = filter?.selectedOptions[0];
            const nilaiFilter = filter?.value || '';
            const namaGuru = option?.dataset.namaGuru || (nilaiFilter.startsWith('nama:') ? nilaiFilter.slice(5) : '');
            const teacherId = option?.dataset.teacherId || (nilaiFilter.startsWith('nama:') ? '' : nilaiFilter);
            const row = buatBarisMasalah(namaBidang, {
              manual: true,
              namaGuru: nilaiFilter ? namaGuru : '',
              teacherId: nilaiFilter ? teacherId : '',
            });
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

          function terapkanRowspanBidangIV() {
            const tbody = document.querySelector('#rekapMasalah tbody');
            if (!tbody) return;
            pastikanFillerBidangIV();

            const semuaBaris = Array.from(tbody.rows);
            let nomorBidang = 0;
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

              if (grupTampil.length > 0) nomorBidang += 1;

              grup.forEach((tr) => {
                let tdNo = tr.querySelector('.sel-no');
                let tdBidang = tr.querySelector('.sel-bidang');
                const isPertamaTampil = grupTampil.length > 0 && tr === grupTampil[0];

                if (isPertamaTampil) {

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
                  tdNo.textContent = nomorBidang;
                  tdBidang.textContent = bidang;
                } else {

                  if (tdNo) tdNo.remove();
                  if (tdBidang) tdBidang.remove();
                }
              });

              i = j;
            }
          }

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

            row.className = "hover:bg-gray-50 transition-colors";
            row.dataset.sumberKey = '';
            row.dataset.manual = '1';
            row.dataset.namaGuru = '';
            row.dataset.teacherId = '';

            row.innerHTML = `
            <td class="border border-gray-300 px-1 py-2 text-center text-sm font-medium text-gray-700"></td>
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
                <button type="button" onclick="hapusBarisDenganNomor(this)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
            perbaruiNomorBaris(tbody);
            return row;
          }

          // Kumpulan sumber_key foto OTOMATIS yang sedang tampil, untuk dedup & sinkronisasi
          const fotoOtomatisAktif = new Set();
          let seqFotoManual = 0;

          /**
           * meta = {
           *   src: string (dataURL / path file),
           *   tipe: 'manual' | 'auto',
           *   guru: string,
           *   idGuru: string|number|null,
           *   sumberKey: string (unik, dipakai untuk dedup & filter),
           *   sumber: string (label asal, mis. 'Konseling Individu')
           * }
           */
          function renderFotoDokumentasi(box, meta) {
            if (typeof meta === "string") {
              // Kompatibilitas data lama: item berupa string src saja (dianggap manual)
              meta = { src: meta, tipe: "manual", guru: "", idGuru: null, sumberKey: "manual-legacy-" + (++seqFotoManual), sumber: "Manual" };
            }

            const wrapper = document.createElement("div");
            wrapper.className = "relative group";
            wrapper.dataset.sumberKey = meta.sumberKey || "";
            wrapper.dataset.tipe = meta.tipe || "manual";
            wrapper.dataset.namaGuru = meta.guru || "";
            wrapper.dataset.teacherId = meta.idGuru || "";
            wrapper.dataset.idGuru = meta.idGuru || "";
            wrapper.dataset.sumber = meta.sumber || "";
            wrapper.dataset.src = meta.src;

            const img = document.createElement("img");
            img.src = meta.src;
            img.className =
              "w-full h-48 object-cover rounded-lg shadow-md hover:shadow-xl transition border border-gray-200";

            const label = document.createElement("div");
            label.className =
              "no-print absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-2 py-1 rounded-b-lg truncate";
            const badge = meta.tipe === "auto" ? "Otomatis" : "Manual";
            label.textContent = badge + (meta.guru ? " • " + meta.guru : "");
            label.title = (meta.sumber ? meta.sumber + " — " : "") + (meta.guru || "");

            const btnHapus = document.createElement("button");
            btnHapus.type = "button";
            btnHapus.innerHTML = '<i class="fas fa-times"></i>';
            btnHapus.className =
              "absolute top-2 right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center opacity-0 group-hover:opacity-100 transition no-print shadow-lg";
            btnHapus.title = meta.tipe === "auto"
              ? "Hapus dari laporan ini saja (dokumentasi asli pada layanan BK tidak akan terhapus)"
              : "Hapus foto manual ini dari laporan";
            btnHapus.onclick = () => {
              if (meta.tipe === "auto" && meta.sumberKey) {
                fotoOtomatisAktif.delete(meta.sumberKey);
              }
              wrapper.remove();
              if (box.querySelectorAll("img").length === 0) {
                box.innerHTML =
                  '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
              }
            };

            wrapper.appendChild(img);
            wrapper.appendChild(label);
            wrapper.appendChild(btnHapus);
            box.appendChild(wrapper);

            if (meta.tipe === "auto" && meta.sumberKey) {
              fotoOtomatisAktif.add(meta.sumberKey);
            }

            return wrapper;
          }

          /**
           * Menggabungkan foto OTOMATIS hasil fetch ke dalam #dokumentasi tanpa
           * menghapus foto MANUAL yang sudah ada, dan tanpa menampilkan foto
           * yang sama dua kali (dedup berdasarkan sumber_key).
           */
          function mergeFotoOtomatis(fotoFresh) {
            const box = document.getElementById("dokumentasi");
            if (!box) return;
            if (box.querySelector("p")) box.innerHTML = "";

            const keySekarang = new Set(
              Array.from(box.querySelectorAll('[data-tipe="auto"]')).map((el) => el.dataset.sumberKey)
            );
            const keyFresh = new Set();

            (fotoFresh || []).forEach((f) => {
              if (!f || !f.path || !f.sumber_key) return;
              keyFresh.add(f.sumber_key);
              if (keySekarang.has(f.sumber_key)) return; // sudah ada, jangan duplikat

              renderFotoDokumentasi(box, {
                src: f.path,
                tipe: "auto",
                guru: f.guru || "",
                idGuru: f.teacher_id || null,
                sumberKey: f.sumber_key,
                sumber: f.sumber || "",
              });
            });

            // Hapus foto otomatis yang sudah tidak ada lagi di sumber layanan BK
            // (misalnya dokumentasi aslinya dihapus dari layanan terkait)
            box.querySelectorAll('[data-tipe="auto"]').forEach((el) => {
              if (!keyFresh.has(el.dataset.sumberKey)) {
                fotoOtomatisAktif.delete(el.dataset.sumberKey);
                el.remove();
              }
            });

            if (box.querySelectorAll("img").length === 0) {
              box.innerHTML =
                '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
            }

            terapkanFilterGuru();
          }

          function previewFoto(event) {
            const box = document.getElementById("dokumentasi");
            const selGuru = document.getElementById("pilihGuruManualFoto");
            const guruTerpilih = selGuru ? selGuru.value : "";
            const newFiles = Array.from(event.target.files);
            const maxSize = 2 * 1024 * 1024;

            if (!guruTerpilih) {
              alert("Pilih Guru terlebih dahulu sebelum menambahkan foto dokumentasi.");
              event.target.value = "";
              return;
            }

            if (box.querySelector("p")) {
              box.innerHTML = "";
            }

            newFiles.forEach((file) => {
              if (!file.type.startsWith("image/")) {
                alert("File " + file.name + " bukan gambar!");
                return;
              }

              if (file.size > maxSize) {
                alert("File " + file.name + " terlalu besar! Maksimal 2MB.");
                return;
              }

              const reader = new FileReader();
              reader.onload = () =>
                renderFotoDokumentasi(box, {
                  src: reader.result,
                  tipe: "manual",
                  guru: guruTerpilih,
                  idGuru: selGuru.selectedOptions[0] ? selGuru.selectedOptions[0].dataset.idGuru || null : null,
                  sumberKey: "manual-" + Date.now() + "-" + (++seqFotoManual),
                  sumber: "Manual",
                });
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

          function geserBulan(delta) {
            const inputBulan = document.getElementById('bulanLaporan');
            const [thnStr, blnStr] = (inputBulan.value || '').split('-');
            let thn = parseInt(thnStr, 10);
            let bln = parseInt(blnStr, 10);
            if (!thn || !bln) {
              const sekarang = new Date();
              thn = sekarang.getFullYear();
              bln = sekarang.getMonth() + 1;
            }
            bln += delta;
            if (bln < 1) { bln = 12; thn -= 1; }
            if (bln > 12) { bln = 1; thn += 1; }
            inputBulan.value = thn + '-' + String(bln).padStart(2, '0');
            prosesPerubahanBulan(false);
          }

          document.getElementById('btnBulanSebelumnya')?.addEventListener('click', () => geserBulan(-1));
          document.getElementById('btnBulanBerikutnya')?.addEventListener('click', () => geserBulan(1));

          const namaBulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
          let tahunPanelCepat = new Date().getFullYear();

          function renderPanelBulanCepat() {
            const inputBulan = document.getElementById('bulanLaporan');
            const [thnAktifStr, blnAktifStr] = (inputBulan.value || '').split('-');
            const thnAktif = parseInt(thnAktifStr, 10);
            const blnAktif = parseInt(blnAktifStr, 10);

            document.getElementById('labelTahunCepat').textContent = tahunPanelCepat;

            const grid = document.getElementById('gridBulanCepat');
            grid.innerHTML = '';
            for (let b = 1; b <= 12; b++) {
              const aktif = thnAktif === tahunPanelCepat && blnAktif === b;
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.textContent = namaBulanSingkat[b - 1];
              btn.className = 'py-2 rounded-lg text-sm font-semibold transition ' +
                (aktif ? 'bg-blue-600 text-white' : 'bg-gray-50 hover:bg-blue-100 text-gray-700');
              btn.addEventListener('click', () => {
                inputBulan.value = tahunPanelCepat + '-' + String(b).padStart(2, '0');
                document.getElementById('panelBulanCepat').classList.add('hidden');
                prosesPerubahanBulan(false);
              });
              grid.appendChild(btn);
            }
          }

          document.getElementById('btnBulanCepat')?.addEventListener('click', (e) => {
            e.stopPropagation();
            const panel = document.getElementById('panelBulanCepat');
            const buka = panel.classList.contains('hidden');
            if (buka) {
              const [thnAktifStr] = (document.getElementById('bulanLaporan').value || '').split('-');
              tahunPanelCepat = parseInt(thnAktifStr, 10) || new Date().getFullYear();
              renderPanelBulanCepat();
            }
            panel.classList.toggle('hidden');
          });

          document.getElementById('btnTahunCepatMundur')?.addEventListener('click', () => { tahunPanelCepat--; renderPanelBulanCepat(); });
          document.getElementById('btnTahunCepatMaju')?.addEventListener('click', () => { tahunPanelCepat++; renderPanelBulanCepat(); });

          document.addEventListener('click', (e) => {
            const panel = document.getElementById('panelBulanCepat');
            if (!panel || panel.classList.contains('hidden')) return;
            if (!panel.contains(e.target) && e.target.id !== 'btnBulanCepat') panel.classList.add('hidden');
          });

          const KOLOM_REKAP = ['jenis_layanan', 'sasaran_kelas', 'jumlah_siswa', 'waktu', 'bentuk_kegiatan', 'keterangan'];

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
                tr.dataset.teacherId = baris.teacher_id || '';
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
                tr.dataset.teacherId = baris.teacher_id || '';
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
              if (trIV.dataset.filler === '1') return;
              const sumberKeyIV = trIV.dataset.sumberKey;
              if (!sumberKeyIV) return;

              const elMasalahIV = trIV.querySelector('[name="masalah[]"]');
              const teksMasalah = elMasalahIV ? ambilNilaiElemen(elMasalahIV).trim() : '';
              if (!teksMasalah) return;

              const sumberAsal = trIV.dataset.sumberAsal || sumberKeyIV;
              if (sumberAsalSudahDiproses.has(sumberAsal)) return;
              sumberAsalSudahDiproses.add(sumberAsal);

              keyFresh.add(sumberAsal);
              const jenisSumber = trIV.dataset.jenisSumber || '';
              const namaGuru = trIV.dataset.namaGuru || '';

              let trV = petaKey[sumberAsal];
              if (trV) {
                trV.dataset.namaGuru = namaGuru;
                trV.dataset.teacherId = trIV.dataset.teacherId || '';
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
                trV.dataset.teacherId = trIV.dataset.teacherId || '';

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
            const optionTerpilih = sel ? sel.selectedOptions[0] : null;
            const filterTeacherId = optionTerpilih?.dataset.teacherId || (nilai.startsWith('nama:') ? '' : nilai);
            const filterNamaGuru = optionTerpilih?.dataset.namaGuru || (nilai.startsWith('nama:') ? nilai.slice(5) : '');
            const printGuruBK = document.getElementById('printGuruBK');
            const pilihGuruBK = document.getElementById('pilihGuruBK');
            const pilihGuruManualFoto = document.getElementById('pilihGuruManualFoto');
            if (pilihGuruBK) pilihGuruBK.value = nilai ? filterNamaGuru : '';
            if (pilihGuruManualFoto) pilihGuruManualFoto.value = nilai ? filterNamaGuru : '';
            if (printGuruBK) printGuruBK.textContent = nilai ? filterNamaGuru : '';
            ['#rekapKegiatan tbody', '#rekapMasalah tbody', '#tindakLanjut tbody'].forEach((sel2) => {
              document.querySelectorAll(`${sel2} > tr`).forEach((tr) => {
                if (tr.dataset.filler === '1') {
                  tr.classList.remove('baris-tersembunyi-filter');
                  return;
                }
                const teacherId = tr.dataset.teacherId || '';
                const guru = tr.dataset.namaGuru || '';
                const tampil = !nilai || (filterTeacherId && teacherId === filterTeacherId) || (!teacherId && filterNamaGuru && guru === filterNamaGuru);
                tr.classList.toggle('baris-tersembunyi-filter', !tampil);
              });
            });

            document.querySelectorAll('#dokumentasi > div[data-nama-guru], #dokumentasi > div[data-sumber-key]').forEach((div) => {
              const teacherId = div.dataset.teacherId || '';
              const guru = div.dataset.namaGuru || '';
              const tampil = !nilai || (filterTeacherId && teacherId === filterTeacherId) || (!teacherId && filterNamaGuru && guru === filterNamaGuru);
              div.classList.toggle('baris-tersembunyi-filter', !tampil);
              div.classList.toggle('hidden', !tampil);
            });

            terapkanRowspanBidangIV();

            perbaruiNomorBaris(document.querySelector('#rekapKegiatan tbody'));
            perbaruiNomorBaris(document.querySelector('#tindakLanjut tbody'));
          }

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
                trAda.dataset.teacherId = baris.teacher_id || '';
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
                  teacherId: baris.teacher_id,
                  jenisSumber: baris.jenis_sumber,
                });
                const elMasalah = row.querySelector('[name="masalah[]"]');
                if (elMasalah) { setNilaiElemen(elMasalah, baris.masalah); autoResizeTextarea(elMasalah); }
              }
            });

            Array.from(tbody.querySelectorAll('tr')).forEach((tr) => {
              if (tr.dataset.sumberKey && !keyFresh.has(tr.dataset.sumberKey) && tr.dataset.manual !== '1' && !tr.dataset.sumberKey.startsWith('manual-')) {
                tr.remove();
              }
            });

            pastikanSemuaBidangAdaBarisIV();
            terapkanRowspanBidangIV();
          }

          let seqMuatDataOtomatis = 0;
          let draftRecoveryChecked = false;

          async function muatDataOtomatis(bulan, tahun) {
            const seqSaya = ++seqMuatDataOtomatis;
            const tbodyRekap = document.querySelector('#rekapKegiatan tbody');
            let sinkronBerhasil = false;

            pastikanSemuaBidangAdaBarisIV();

            try {
              const res = await fetch('laporanbk.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_rekap_otomatis', bulan, tahun }),
              });
              const hasil = await res.json();
              if (seqSaya !== seqMuatDataOtomatis) return;
              if (hasil.success) {
                sinkronBerhasil = true;
                mergeOtomatisKeTabel('#rekapKegiatan tbody', hasil.rekap || [], tambahRekap, KOLOM_REKAP, {
                  isiJikaKosongFields: ['bentuk_kegiatan'],
                  keteranganField: 'keterangan',
                  kunciKonten: true,
                });
                mergeMasalahIV(hasil.masalah || []);
                mergeFotoOtomatis(hasil.foto || []);
              }
            } catch (e) {
              console.error(e);
            }

            if (seqSaya !== seqMuatDataOtomatis) return;

            if (!tbodyRekap || tbodyRekap.rows.length === 0) tambahRekap();

            sinkronTindakDariMasalah();

            terapkanFilterGuru();
            if (!draftRecoveryChecked) {
              draftRecoveryChecked = true;
              cekDraftLokal();
            }
            return sinkronBerhasil;
          }

          async function prosesPerubahanBulan(saatMuatAwal) {
            const info = terapkanInfoBulan(document.getElementById('bulanLaporan').value);
            if (!info) return;
            draftRecoveryChecked = false;
            sembunyikanRecoveryDraft();

            const targetBulanStr = info.tahun + '-' + String(info.bulan).padStart(2, '0');

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

            if (!saatMuatAwal && window.DATA_LAPORAN_EXISTING) {
              const urlPeriode = new URL(window.location.href);
              urlPeriode.searchParams.set('bulan', targetBulanStr);
              urlPeriode.searchParams.delete('id');
              window.location.href = urlPeriode.pathname + urlPeriode.search;
              return;
            }

            const urlPeriode = new URL(window.location.href);
            urlPeriode.searchParams.set('bulan', targetBulanStr);
            urlPeriode.searchParams.delete('id');
            window.history.replaceState({}, '', urlPeriode);

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

            elFilterGuruBK.addEventListener('change', function () {
              const url = new URL(window.location.href);
              if (this.value) url.searchParams.set('guru_id', this.value);
              else url.searchParams.delete('guru_id');
              window.history.replaceState({}, '', url);
              draftRecoveryChecked = false;
              sembunyikanRecoveryDraft();
              terapkanFilterGuru();
              cekDraftLokal();
            });
          }

          document.addEventListener("DOMContentLoaded", () => {
            const filterGuruBK = document.getElementById('filterGuruBK');
            const guruIdDariUrl = new URLSearchParams(window.location.search).get('guru_id');
            if (filterGuruBK && guruIdDariUrl !== null) filterGuruBK.value = guruIdDariUrl;

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
              tr.dataset.teacherId = baris.teacher_id || '';
            });

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
                teacherId: baris.teacher_id || '',
                jenisSumber: baris.jenis_sumber || '',
              });

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
              tr.dataset.teacherId = baris.teacher_id || '';
            });
          }

          function syncPrintText(selectEl, targetId) {
            const target = document.getElementById(targetId);
            if (targetId === 'printGuruBK') {
              const filterGuru = document.getElementById('filterGuruBK');
              if (filterGuru && filterGuru.value) {
                target.textContent = filterGuru.selectedOptions[0]?.dataset.namaGuru || '';
                return;
              }
            }
            target.textContent = selectEl.value;
          }

          function cetakLaporan() {
            terapkanFilterGuru();
            window.print();
          }

          window.addEventListener('beforeprint', function () {
            terapkanFilterGuru();
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
                span.textContent = inp.value || (inp.closest('#rekapMasalah') ? '-' : '');
                inp.parentNode.insertBefore(span, inp.nextSibling);
              });

            document.querySelectorAll('table textarea').forEach(function (ta) {
              const span = document.createElement('span');
              span.className = 'print-value-proxy';
              span.textContent = ta.value || (ta.closest('#rekapMasalah') ? '-' : '');
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

          let timerAutosaveDraft = null;
          let draftLokalAktif = null;

          function konteksDraftLokal() {
            const periode = document.getElementById('bulanLaporan')?.value || '';
            const filter = document.getElementById('filterGuruBK');
            const option = filter?.selectedOptions[0];
            const teacherId = option?.dataset.teacherId || (filter?.value?.startsWith('nama:') ? '' : filter?.value || '');
            return { periode, teacher_id: teacherId || 'all', filter_guru: filter?.value || '' };
          }

          function kunciDraftLokal(konteks) {
            return 'laporan_bk_draft_v1:' + (konteks.teacher_id || 'all') + ':' + (konteks.periode || 'tanpa-periode');
          }

          function ambilDataDraftLokal() {
            const konteks = konteksDraftLokal();
            return {
              version: 1,
              saved_at: new Date().toISOString(),
              konteks,
              data: {
                rekap: kumpulkanBarisTabel('#rekapKegiatan tbody', KOLOM_REKAP),
                masalah: kumpulkanBarisTabel('#rekapMasalah tbody', KOLOM_MASALAH),
                tindak: kumpulkanBarisTabel('#tindakLanjut tbody', KOLOM_TINDAK),
                dokumentasi: Array.from(document.querySelectorAll('#dokumentasi > div[data-sumber-key], #dokumentasi > div[data-tipe]')).map((div) => ({
                  src: div.dataset.src || (div.querySelector('img') ? div.querySelector('img').src : ''),
                  tipe: div.dataset.tipe || 'manual', guru: div.dataset.namaGuru || '',
                  id_guru: div.dataset.idGuru || div.dataset.teacherId || null,
                  sumber_key: div.dataset.sumberKey || '', sumber: div.dataset.sumber || '',
                })),
              },
              fields: {
                nama_dokumen: document.getElementById('namaDokumen')?.value || '',
                koordinator_nip: document.getElementById('nipKoordinator')?.value || '',
                nama_koordinator: document.getElementById('pilihKoordinator')?.value || '',
                nama_guru_bk: document.getElementById('pilihGuruBK')?.value || '',
                nip_guru_bk: document.getElementById('nipGuruBK')?.value || '',
              },
            };
          }

          function simpanDraftLokal() {
            const draft = ambilDataDraftLokal();
            if (!draft.konteks.periode) return;
            try {
              localStorage.setItem(kunciDraftLokal(draft.konteks), JSON.stringify(draft));
              const status = document.getElementById('draftStatus');
              if (status) status.textContent = 'Draft lokal tersimpan ' + new Date(draft.saved_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (error) { console.warn('Draft lokal tidak dapat disimpan:', error); }
          }

          function jadwalkanAutosaveDraft() {
            clearTimeout(timerAutosaveDraft);
            timerAutosaveDraft = setTimeout(simpanDraftLokal, 600);
          }

          function sembunyikanRecoveryDraft() {
            document.getElementById('draftRecovery')?.classList.add('hidden');
            draftLokalAktif = null;
          }

          function cekDraftLokal() {
            const konteks = konteksDraftLokal();
            if (!konteks.periode) return;
            try {
              const draft = JSON.parse(localStorage.getItem(kunciDraftLokal(konteks)) || 'null');
              if (!draft?.data) return;
              draftLokalAktif = draft;
              const teks = document.getElementById('draftRecoveryText');
              if (teks) teks.textContent = 'Terakhir disimpan: ' + (draft.saved_at ? new Date(draft.saved_at).toLocaleString() : 'waktu tidak diketahui') + '. Pilih tindakan yang ingin digunakan.';
              document.getElementById('draftRecovery')?.classList.remove('hidden');
            } catch (error) { console.warn('Draft lokal tidak dapat dibaca:', error); }
          }

          function terapkanDraftLokal(draft) {
            const data = draft.data || {};
            ['rekapKegiatan', 'rekapMasalah', 'tindakLanjut'].forEach((tableId) => document.querySelector('#' + tableId + ' tbody')?.replaceChildren());
            document.getElementById('namaDokumen').value = draft.fields?.nama_dokumen || '';
            document.getElementById('nipKoordinator').value = draft.fields?.koordinator_nip || '';
            document.getElementById('pilihKoordinator').value = draft.fields?.nama_koordinator || '';
            document.getElementById('pilihGuruBK').value = draft.fields?.nama_guru_bk || '';
            document.getElementById('nipGuruBK').value = draft.fields?.nip_guru_bk || '';
            restoreSemuaTabel({ rekap: data.rekap || [], masalah: data.masalah || [], tindak: data.tindak || [] });
            const box = document.getElementById('dokumentasi');
            if (box) {
              box.innerHTML = '';
              (data.dokumentasi || []).forEach((item) => renderFotoDokumentasi(box, item));
              if (!data.dokumentasi?.length) box.innerHTML = '<p class="text-sm text-gray-500 col-span-full text-center py-8">Belum ada foto yang dipilih</p>';
            }
            terapkanFilterGuru();
          }

          function pulihkanDraftLokal() {
            if (!draftLokalAktif) return;
            terapkanDraftLokal(draftLokalAktif);
            sembunyikanRecoveryDraft();
            document.getElementById('draftStatus').textContent = 'Draft lokal berhasil dipulihkan.';
          }

          function hapusDraftLokal(mintaKonfirmasi) {
            const konteks = konteksDraftLokal();
            if (mintaKonfirmasi && !confirm('Hapus draft lokal untuk periode dan guru yang sedang dipilih? Data database tidak akan terhapus.')) return;
            try { localStorage.removeItem(kunciDraftLokal(konteks)); } catch (error) { console.warn(error); }
            sembunyikanRecoveryDraft();
            document.getElementById('draftStatus').textContent = 'Draft lokal dihapus. Data server tetap aman.';
          }

          function gunakanDataServer() { hapusDraftLokal(false); document.getElementById('draftStatus').textContent = 'Data server digunakan.'; }

          async function sinkronkanData() {
            const info = ambilBulanTahunAktif();
            const status = document.getElementById('draftStatus');
            const button = document.getElementById('btnSinkronkanData');
            const label = document.getElementById('labelSinkronkanData');
            const icon = button?.querySelector('i');
            if (!info) return;
            if (button) button.disabled = true;
            if (label) label.textContent = 'Menyinkronkan...';
            if (icon) icon.className = 'fas fa-spinner fa-spin';
            if (status) status.textContent = 'Sedang mengambil data terbaru dari server...';
            try {
              const berhasil = await muatDataOtomatis(info.bulan, info.tahun);
              if (status) status.textContent = berhasil ? 'Data berhasil disinkronkan pada ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Sinkronisasi gagal. Data tetap dipertahankan.';
            } catch (error) {
              if (status) status.textContent = 'Sinkronisasi gagal. Data yang sedang dikerjakan tetap dipertahankan.';
            } finally {
              if (button) button.disabled = false;
              if (label) label.textContent = 'Sinkronkan Data';
              if (icon) icon.className = 'fas fa-sync-alt';
            }
          }

          function kumpulkanBarisTabel(tbodySelector, kolom) {
            const hasil = [];
            document.querySelectorAll(`${tbodySelector} tr`).forEach((tr) => {

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
                baris.teacher_id = tr.dataset.teacherId || null;
                if (tr.dataset.jenisSumber !== undefined) baris.jenis_sumber = tr.dataset.jenisSumber || '';
              }

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

            const fotoData = Array.from(document.querySelectorAll('#dokumentasi > div[data-sumber-key], #dokumentasi > div[data-tipe]')).map((div) => ({
              src: div.dataset.src || (div.querySelector('img') ? div.querySelector('img').src : ''),
              tipe: div.dataset.tipe || 'manual',
              guru: div.dataset.namaGuru || '',
              id_guru: div.dataset.idGuru || null,
              sumber_key: div.dataset.sumberKey || '',
              sumber: div.dataset.sumber || '',
            }));
            fd.append('dokumentasi_json', JSON.stringify(fotoData));

            fd.append('rekap_json', JSON.stringify(kumpulkanBarisTabel('#rekapKegiatan tbody', KOLOM_REKAP)));
            fd.append('masalah_json', JSON.stringify(kumpulkanBarisTabel('#rekapMasalah tbody', KOLOM_MASALAH)));
            fd.append('tindak_json', JSON.stringify(kumpulkanBarisTabel('#tindakLanjut tbody', KOLOM_TINDAK)));

            return fd;
          }

          function terapkanStatusUI(status) {
            document.getElementById('statusLaporan').value = status;
            const badge = document.getElementById('badgeStatus');
            const isFinal = status === 'final';

            badge.textContent = isFinal ? '🟢 Final - Terkunci' : '🟡 Draft - Belum Dikunci';
            badge.className = 'px-3 py-1 rounded-full text-sm font-semibold ' +
              (isFinal ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700');

            document.getElementById('btnSimpan').classList.toggle('hidden', isFinal);
            document.getElementById('btnFinalisasi').classList.toggle('hidden', isFinal);
            document.getElementById('btnBukaDraft').classList.toggle('hidden', !isFinal);
            document.getElementById('btnResetForm').classList.toggle('hidden', isFinal);
            document.getElementById('btnCetak').disabled = !isFinal;

            ['#rekapKegiatan', '#rekapMasalah', '#tindakLanjut'].forEach((tableSelector) => {
              const table = document.querySelector(tableSelector);
              if (!table) return;
              const section = table.closest('.report-section');
              table.querySelectorAll('input, select, textarea, button').forEach((el) => {
                el.disabled = isFinal;
              });
              section?.querySelectorAll('button').forEach((button) => {
                button.disabled = isFinal;
                button.classList.toggle('pointer-events-none', isFinal);
                button.classList.toggle('opacity-50', isFinal);
              });
            });
          }

          function simpanDokumen() {
            const btn = document.getElementById('btnSimpan');
            const icon = btn.querySelector('i');
            const label = btn.querySelector('span:first-of-type');
            const iconClassAsal = icon.className;
            const labelTeksAsal = label.textContent;

            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin text-xl mb-1';
            label.textContent = 'Menyimpan...';

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
                  hapusDraftLokal(false);
                  alert(data.message);
                } else {
                  const status = document.getElementById('draftStatus');
                  if (status) status.textContent = 'Gagal menyimpan ke server. Draft Anda tetap tersimpan secara lokal.';
                  alert('Gagal: ' + data.message);
                }
              })
              .catch(() => {
                const status = document.getElementById('draftStatus');
                if (status) status.textContent = 'Gagal menyimpan ke server. Draft Anda tetap tersimpan secara lokal.';
                alert('Terjadi kesalahan koneksi saat menyimpan. Draft Anda tetap tersimpan secara lokal.');
              })
              .finally(() => {
                btn.disabled = false;
                icon.className = iconClassAsal;
                label.textContent = labelTeksAsal;
              });
          }

          function finalisasiDokumen() {
            const idLaporan = document.getElementById('idLaporan').value;
            if (!idLaporan || idLaporan == 0) {
              alert('Simpan laporan ini terlebih dahulu sebelum menyelesaikannya.');
              return;
            }
            if (!confirm('Setelah diselesaikan, laporan akan berstatus final dan hanya bisa dicetak. Anda tetap bisa membukanya kembali untuk diedit lewat tombol Buka Kunci. Lanjutkan?')) return;

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
              fotoTersimpan.forEach(item => renderFotoDokumentasi(boxDokumentasi, typeof item === 'string' ? item : {
                src: item.src || item.path || '',
                tipe: item.tipe || 'manual',
                guru: item.guru || '',
                idGuru: item.id_guru || item.teacher_id || null,
                sumberKey: item.sumber_key || 'manual-legacy-' + (++seqFotoManual),
                sumber: item.sumber || 'Manual',
              }));
            }

            const pilihKoordinatorEl = document.getElementById('pilihKoordinator');
            const pilihGuruBKEl = document.getElementById('pilihGuruBK');
            if (pilihKoordinatorEl) syncPrintText(pilihKoordinatorEl, 'printKoordinator');
            if (pilihGuruBKEl) syncPrintText(pilihGuruBKEl, 'printGuruBK');
            terapkanFilterGuru();

            const nipKoordinatorEl = document.getElementById('nipKoordinator');
            const nipGuruBKEl = document.getElementById('nipGuruBK');
            if (nipKoordinatorEl) document.getElementById('valNipKoordinator').textContent = nipKoordinatorEl.value;
            if (nipGuruBKEl) document.getElementById('valNipGuruBK').textContent = nipGuruBKEl.value;

            document.addEventListener('input', (event) => {
              if (event.target.closest('#main-content')) jadwalkanAutosaveDraft();
            });
            document.addEventListener('change', (event) => {
              if (event.target.closest('#main-content')) jadwalkanAutosaveDraft();
            });
            window.addEventListener('beforeunload', simpanDraftLokal);
          });
        </script>
      </main>
    </div>
  </body>
</html>