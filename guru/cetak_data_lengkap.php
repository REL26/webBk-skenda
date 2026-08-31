<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa = mysqli_real_escape_string($koneksi, $_GET['id_siswa']);

$query_siswa = mysqli_query($koneksi, "
    SELECT
        s.*,
        t.tahun AS tahun_ajaran,
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
    FROM siswa s
    LEFT JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
    LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
    WHERE s.id_siswa = '$id_siswa'
");
$siswa = mysqli_fetch_assoc($query_siswa);
if (!$siswa) { die("Data siswa tidak ditemukan."); }

// =========================================================================
// ARRAY MAPPING DESKRIPSI & SARAN (2-3 Kalimat Deskripsi + 1 Kalimat Saran)
// =========================================================================

$learningStyleDescriptions = [
    'Visual' => [
        'deskripsi' => 'Siswa lebih mudah memahami informasi melalui penglihatan seperti gambar, diagram, warna, dan tulisan.',
        'saran'     => 'Gunakan media visual seperti catatan berwarna, mind map, grafik, dan ilustrasi.'
    ],
    'Auditorial' => [
        'deskripsi' => 'Siswa lebih mudah memahami informasi melalui pendengaran, diskusi, dan penjelasan lisan.',
        'saran'     => 'Gunakan metode diskusi, membaca dengan suara, dan penjelasan verbal.'
    ],
    'Kinestetik' => [
        'deskripsi' => 'Siswa lebih mudah memahami informasi melalui aktivitas, praktik, dan pengalaman langsung.',
        'saran'     => 'Gunakan pembelajaran praktik, simulasi, dan kegiatan yang melibatkan gerakan.'
    ]
];

$multipleIntelligenceDescriptions = [
    'Linguistik (Bahasa)' => [
        'deskripsi' => 'Siswa memiliki kemampuan unggul dalam mengolah kata, baik secara tertulis maupun lisan. Cenderung mudah memahami bacaan, mahir bercerita, dan mampu mengomunikasikan ide dengan sangat jelas.',
        'saran'     => 'Kemampuan ini dapat terus dikembangkan melalui kebiasaan membaca berbagai jenis bacaan, menulis ringkasan atau jurnal, serta aktif mengikuti diskusi, debat, maupun presentasi.'
    ],
    
    'Logis-Matematis' => [
        'deskripsi' => 'Siswa memiliki kecakapan tinggi dalam penalaran logis, analisis angka, dan pemecahan masalah yang sistematis. Cenderung menyukai pola ilmiah, perhitungan, serta berpikir secara kritis dan terstruktur.',
        'saran'     => 'Sebaiknya lebih sering melatih kemampuan analisis melalui soal-soal yang menantang, pemecahan masalah, serta membiasakan diri menyusun pola pikir secara logis dan sistematis.'
    ],
    
    'Spasial-Visual' => [
        'deskripsi' => 'Siswa mampu memvisualisasikan gambar, ruang, dan bentuk secara akurat di dalam pikiran. Sangat peka terhadap keindahan bentuk, warna, serta orientasi arah dan tata letak objek.',
        'saran'     => 'Proses belajar akan lebih optimal apabila memanfaatkan media visual seperti diagram, peta konsep, ilustrasi, video pembelajaran, maupun penggunaan warna pada catatan.'
    ],
    
    'Kinestetik-Jasmani' => [
        'deskripsi' => 'Siswa menggunakan gerak tubuh untuk mengekspresikan ide, menyelesaikan masalah, atau mempelajari keterampilan baru. Memiliki koordinasi motorik, keseimbangan, dan ketangkasan fisik yang sangat baik.',
        'saran'     => 'Belajar melalui praktik langsung, simulasi, eksperimen, atau aktivitas fisik akan membantu meningkatkan pemahaman sekaligus menjaga fokus selama proses belajar.'
    ],
    
    'Musikal' => [
        'deskripsi' => 'Siswa peka terhadap irama, melodi, nada, dan struktur musik. Cenderung mudah mengenali pola bunyi serta dapat belajar lebih efektif dengan bantuan ritme atau suasana yang harmonis.',
        'saran'     => 'Manfaatkan musik instrumental, ritme, maupun rekaman audio sebagai media belajar dapat membantu memahami materi sekaligus memperkuat daya ingat.'
    ],
    
    'Interpersonal' => [
        'deskripsi' => 'Siswa memiliki kepekaan sosial tinggi, mudah berempati, serta mampu memahami perasaan dan motivasi orang lain. Sangat terampil dalam berkomunikasi, berkolaborasi, dan membangun relasi dalam kelompok.',
        'saran'     => 'Potensi ini akan semakin berkembang apabila aktif terlibat dalam diskusi kelompok, kegiatan organisasi, kerja sama tim, maupun berbagi pengetahuan dengan teman.'
    ],
    
    'Intrapersonal' => [
        'deskripsi' => 'Siswa memiliki pemahaman yang mendalam tentang diri sendiri, termasuk kelebihan, kelemahan, serta tujuan pribadinya. Cenderung mandiri, reflektif, dan mampu mengelola emosi dengan bijak.',
        'saran'     => 'Kemampuan ini dapat diperkuat dengan menetapkan target belajar yang jelas, melakukan refleksi secara berkala, serta membangun kebiasaan belajar yang mandiri dan terarah.'
    ],
    
    'Naturalis' => [
        'deskripsi' => 'Siswa memiliki kepekaan tinggi terhadap alam sekitar, flora, fauna, dan fenomena lingkungan. Cenderung suka mengamati ekosistem serta mengelompokkan pola-pola yang ada di alam raya.',
        'saran'     => 'Mengaitkan materi pembelajaran dengan lingkungan sekitar, melakukan observasi, atau mengikuti kegiatan luar ruangan dapat membantu mengembangkan potensi yang dimiliki.'
    ],
];

// --- Gaya Belajar (dominan) ---
$gaya_belajar = "Belum Mengisi";
$gaya_belajar_dominan_list = [];
if ($siswa['skor_visual'] !== null) {
    $skor_v = (float) $siswa['skor_visual'];
    $skor_a = (float) $siswa['skor_auditori'];
    $skor_k = (float) $siswa['skor_kinestetik'];
    $skor_tertinggi = max($skor_v, $skor_a, $skor_k);
    if ($skor_v == $skor_tertinggi) $gaya_belajar_dominan_list[] = 'Visual';
    if ($skor_a == $skor_tertinggi) $gaya_belajar_dominan_list[] = 'Auditorial';
    if ($skor_k == $skor_tertinggi) $gaya_belajar_dominan_list[] = 'Kinestetik';
    $gaya_belajar = implode(" & ", $gaya_belajar_dominan_list);
}

// --- Tes Kemampuan / Kecerdasan (dominan) ---
$map_kecerdasan = [
    'A' => 'Linguistik (Bahasa)', 'B' => 'Logis-Matematis', 'C' => 'Spasial-Visual',
    'D' => 'Kinestetik-Jasmani', 'E' => 'Musikal', 'F' => 'Interpersonal',
    'G' => 'Intrapersonal', 'H' => 'Naturalis',
];
$query_kecerdasan = mysqli_query($koneksi, "
    SELECT * FROM hasil_kecerdasan WHERE id_siswa = '$id_siswa' ORDER BY tanggal_tes DESC LIMIT 1
");
$hasil_kecerdasan = mysqli_fetch_assoc($query_kecerdasan);
$tipe_kemampuan = "Belum Mengisi";
$skor_kecerdasan = [];
$nama_tipe_kecerdasan_list = [];
if ($hasil_kecerdasan) {
    $skor_kecerdasan = [
        'A' => $hasil_kecerdasan['skor_A'] ?? 0, 'B' => $hasil_kecerdasan['skor_B'] ?? 0,
        'C' => $hasil_kecerdasan['skor_C'] ?? 0, 'D' => $hasil_kecerdasan['skor_D'] ?? 0,
        'E' => $hasil_kecerdasan['skor_E'] ?? 0, 'F' => $hasil_kecerdasan['skor_F'] ?? 0,
        'G' => $hasil_kecerdasan['skor_G'] ?? 0, 'H' => $hasil_kecerdasan['skor_H'] ?? 0,
    ];
    $skor_tertinggi_kc = max($skor_kecerdasan);
    if ($skor_tertinggi_kc > 0) {
        $kode_tertinggi = array_keys($skor_kecerdasan, $skor_tertinggi_kc);
        foreach ($kode_tertinggi as $kd) {
            $nama_tipe_kecerdasan_list[] = $map_kecerdasan[$kd] ?? $kd;
        }
        $tipe_kemampuan = implode(" & ", $nama_tipe_kecerdasan_list);
    } else {
        $tipe_kemampuan = "Semua Skor 0";
    }
}

// --- Tes Kepribadian ---
$query_kepribadian = mysqli_query($koneksi, "
    SELECT hk.skor_total, hk.tipe, hk.tanggal_tes
    FROM hasil_kepribadian hk
    WHERE hk.id_siswa = '$id_siswa'
    ORDER BY hk.tanggal_tes DESC LIMIT 1
");
$hasil_kepribadian = $query_kepribadian ? mysqli_fetch_assoc($query_kepribadian) : null;
$nama_tipe_kepribadian = null;
$deskripsi_kepribadian = null;
$saran_kepribadian = null;
if ($hasil_kepribadian && !empty($hasil_kepribadian['tipe'])) {
    $kode_tipe_esc = mysqli_real_escape_string($koneksi, $hasil_kepribadian['tipe']);
    $q_ket = mysqli_query($koneksi, "SELECT * FROM keterangan_kepribadian WHERE kode_tipe = '$kode_tipe_esc' LIMIT 1");
    $ket = $q_ket ? mysqli_fetch_assoc($q_ket) : null;
    if ($ket) {
        $nama_tipe_kepribadian = $ket['nama_tipe'];
        $deskripsi_kepribadian = $ket['deskripsi'];
        $saran_kepribadian     = $ket['saran'];
    }
}

// --- Foto: base64-kan supaya konsisten muncul saat export PDF ---
$email_hash   = md5(strtolower(trim($siswa['email'] ?? '')));
$gravatar_url = "https://www.gravatar.com/avatar/{$email_hash}?s=200&d=mp";
$foto_base64  = '';

if (!empty($siswa['url_foto']) && file_exists('../' . $siswa['url_foto'])) {
    $local_path = '../' . $siswa['url_foto'];
    $foto_data  = file_get_contents($local_path);
    $foto_ext   = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
    $mime_map   = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $foto_mime  = $mime_map[$foto_ext] ?? 'image/jpeg';
    $foto_base64 = 'data:' . $foto_mime . ';base64,' . base64_encode($foto_data);
} else {
    $foto_data = @file_get_contents($gravatar_url);
    if ($foto_data) {
        $foto_base64 = 'data:image/jpeg;base64,' . base64_encode($foto_data);
    }
}
$foto_src = $foto_base64 ?: $gravatar_url;

function isi($v, $satuan = '') {
    if ($v === null || $v === '') return '<span style="color:#9aa3af;">Belum diisi</span>';
    return htmlspecialchars($v) . ($satuan ? ' ' . $satuan : '');
}

// Format angka penghasilan jadi format Rupiah Indonesia yang benar, mis. 3000000
// menjadi "Rp 3.000.000" (titik sebagai pemisah ribuan, tanpa desimal karena
// nilai penghasilan selalu bulat).
function formatRupiah($v) {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '<span style="color:#9aa3af;">Belum diisi</span>';
    }
    return 'Rp ' . number_format((float) $v, 0, ',', '.');
}

$url_kembali = "detail_biodata.php?id_siswa=" . urlencode($id_siswa);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cetak Data Lengkap - <?php echo htmlspecialchars($siswa['nama']); ?></title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<style>
@page { size: A4; margin: 0; margin-top: 26px; }
@page :first { margin-top: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
html, body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11.5px;
    color: #2b2f36;
    background-color: #e9edf3;
    line-height: 1.5;
}
table { border-collapse: collapse; width: 100%; }

/* ================= TOOLBAR ================= */
.toolbar-wrap {
    position: sticky; top: 0; z-index: 999;
    background: linear-gradient(180deg, #ffffff 0%, #f2f4f8 100%);
    border-bottom: 1px solid #dbe1ea;
    padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(20, 36, 60, 0.08);
}
.toolbar {
    max-width: 900px; margin: 0 auto;
    display: flex; justify-content: center; align-items: center; gap: 14px; flex-wrap: wrap;
}
.toolbar a, .toolbar button {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 22px; text-decoration: none; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 700; cursor: pointer; font-family: Arial, sans-serif;
}
.toolbar a.btn-kembali {
    background-color: #ffffff; color: #1a2e4a; border: 2px solid #1a2e4a;
    box-shadow: 0 2px 6px rgba(26, 46, 74, 0.12);
}
.toolbar a.btn-kembali:hover { background-color: #1a2e4a; color: #ffffff; }
.toolbar button.btn-export {
    background: linear-gradient(135deg, #4a90c4 0%, #2f6fa3 100%); color: #ffffff;
    box-shadow: 0 4px 10px rgba(74, 144, 196, 0.35);
}
.icon-svg { width: 15px; height: 15px; }

/* ================= WRAPPER (fixed size, scrollable di mobile) ================= */
.doc-outer { width: 100%; overflow-x: auto; padding: 20px 0 40px 0; -webkit-overflow-scrolling: touch; }
.doc-page { width: 794px; min-width: 794px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 18px rgba(20,36,60,0.15); padding: 30px 36px 38px 36px; }

/* ================= ISI DOKUMEN ================= */
.kop { text-align: center; border-bottom: 2px solid #1a2e4a; padding-bottom: 12px; margin-bottom: 20px; }
.kop h1 { font-size: 15px; font-weight: 700; letter-spacing: 0.6px; color: #1a2e4a; }
.kop p { font-size: 10.5px; color: #6b7280; margin-top: 3px; letter-spacing: 0.2px; }

.header-siswa { display: flex; gap: 18px; align-items: flex-start; margin-bottom: 20px; padding-bottom: 18px; border-bottom: 1px solid #e5e9f0; }
.header-siswa img { width: 96px; height: 116px; object-fit: cover; border: 1px solid #d7dce4; border-radius: 4px; flex-shrink: 0; }
.header-siswa .id-nama { font-size: 16.5px; font-weight: 700; color: #1a2e4a; }
.header-siswa .id-sub { font-size: 11px; color: #5b6472; margin-top: 4px; }

.section-title {
    font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    color: #1a2e4a;
    background-color: #eef1f5; border-left: 4px solid #1a2e4a; padding: 6px 10px;
    margin: 20px 0 10px 0; border-radius: 0 3px 3px 0;
}
.data-table td { padding: 5px 6px; vertical-align: top; border-bottom: 1px solid #eef0f3; font-size: 11.3px; }
.data-table td.label { width: 190px; font-weight: 600; color: #3a4150; white-space: nowrap; }
.data-table td.colon { width: 12px; color: #b0b6c0; }
.data-table.two-col { display: table; }
.two-col-wrap { display: flex; gap: 28px; }
.two-col-wrap .data-table { width: 50%; }

.psy-box { border: 1px solid #dfe3e8; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; background: #fbfcfd; }
.psy-box .psy-title { font-size: 11.3px; font-weight: 700; color: #1a2e4a; margin-bottom: 7px; }
.psy-badge { display: inline-block; background: #e8f2fb; color: #1a5f8a; border: 1px solid #b0d0ea; padding: 3px 11px; border-radius: 20px; font-weight: 700; font-size: 11px; }
.psy-badge.empty { background: #f1f2f4; color: #8a9099; border-color: #dfe1e5; font-weight: 600; }
.psy-box .psy-desc { font-size: 10.8px; color: #4a5160; margin-top: 8px; line-height: 1.55; }
.psy-box .psy-desc strong { color: #2b2f36; }

/* --- STYLING KHUSUS KARAKTERISTIK KEPRIBADIAN (COMPATIBLE DENGAN DOMPDF) --- */
.psy-char-row {
    display: table;
    width: 100%;
    margin-top: 8px;
    font-size: 10.8px;
    color: #4a5160;
    line-height: 1.55;
}
.psy-char-label {
    display: table-cell;
    width: 55px;
    vertical-align: top;
    font-weight: 700;
    color: #2b2f36;
    padding-right: 4px;
    white-space: nowrap;
}
.psy-char-content {
    display: table-cell;
    vertical-align: top;
}
.psy-char-item {
    display: table;
    width: 100%;
    margin-bottom: 1.5px;
}
.psy-char-bullet {
    display: table-cell;
    width: 14px;
    vertical-align: top;
    font-weight: 700;
}
.psy-char-text {
    display: table-cell;
    vertical-align: top;
}

.longtext { font-size: 11.2px; line-height: 1.6; color: #3a4150; white-space: pre-line; }

.ttd-wrap { margin-top: 32px; display: flex; justify-content: flex-end; }
.ttd-box { text-align: center; width: 220px; font-size: 11px; color: #3a4150; }
.ttd-box .space { height: 55px; }

/* ================= PAGE BREAK CONTROL ================= */
.section-lead {
    page-break-inside: avoid;
    break-inside: avoid;
}
.section-title {
    page-break-after: avoid;
    break-after: avoid;
}
.data-table tr {
    page-break-inside: avoid;
    break-inside: avoid;
}
.psy-box {
    page-break-inside: avoid;
    break-inside: avoid;
}

/* ================= REVISI: OPTIMASI LAYOUT DATA PRIBADI & KELUARGA ================= */
/* Lebih rapat, padding dikurangi, gap diperkecil */
.two-col-wrap {
    gap: 12px; /* sebelumnya 28px, didekatkan */
}
.two-col-wrap .data-table {
    width: 50%;
}
.data-table td {
    padding: 3px 4px; /* sebelumnya 5px 6px, dikurangi */
    font-size: 11px; /* sedikit lebih kecil */
    border-bottom: 1px solid #f0f2f5;
}
.data-table td.label {
    width: 130px; /* sebelumnya 190px, dipersempit agar lebih proporsional */
    font-weight: 600;
    color: #3a4150;
    white-space: nowrap;
}
.data-table td.colon {
    width: 10px;
    color: #b0b6c0;
    text-align: center;
}
/* Jarak antar section-title dengan tabel diperkecil */
.section-title {
    margin: 14px 0 6px 0; /* sebelumnya 20px 0 10px 0 */
    padding: 5px 10px;
}
/* Jarak antar bagian dalam section-lead */
.section-lead {
    margin-bottom: 2px;
}
/* Tabel di dalam section-lead yang terakhir (tempat tinggal) */
.section-lead .data-table {
    margin-top: 4px; /* sebelumnya 18px, dikurangi */
}
/* Spacer untuk data keluarga bagian bawah */
.section-lead .data-table + .data-table {
    margin-top: 0;
}
/* Header siswa sedikit lebih rapat */
.header-siswa {
    margin-bottom: 14px; /* sebelumnya 20px */
    padding-bottom: 12px; /* sebelumnya 18px */
}
/* Kop margin bawah dikurangi */
.kop {
    padding-bottom: 10px; /* sebelumnya 12px */
    margin-bottom: 16px; /* sebelumnya 20px */
}
/* Tabel di dalam two-col-wrap tidak ada margin tambahan */
.two-col-wrap .data-table {
    margin: 0;
}

@media (max-width: 850px) {
    .doc-outer { padding: 16px 0 32px 0; }
    .toolbar-wrap { padding: 12px 10px; }
    .toolbar a, .toolbar button { padding: 10px 18px; font-size: 12.5px; }
}

@media print {
    html, body { background-color: #ffffff !important; margin: 0 !important; padding: 0 !important; height: auto !important; }
    .toolbar-wrap { display: none !important; position: static !important; height: 0 !important; padding: 0 !important; margin: 0 !important; border: none !important; box-shadow: none !important; }
    .doc-outer { padding: 0 !important; margin: 0 !important; overflow: visible !important; background-color: #ffffff !important; }
    .doc-page { width: 100% !important; min-width: 0 !important; max-width: 100% !important; box-shadow: none !important; margin: 0 !important; padding: 12px 16px !important; }
    .two-col-wrap { display: flex !important; }
}
</style>
</head>
<body>

<div class="toolbar-wrap">
    <div class="toolbar">
        <a class="btn-kembali" href="<?php echo htmlspecialchars($url_kembali); ?>">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <button class="btn-export" onclick="window.print()">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
            Ekspor PDF
        </button>
    </div>
</div>

<div class="doc-outer">
<div class="doc-page">

    <div class="kop">
        <h1>DATA LENGKAP SISWA BIMBINGAN DAN KONSELING</h1>
        <p>SMK Negeri 2 Banjarmasin</p>
    </div>

    <div class="header-siswa">
        <img src="<?php echo $foto_src; ?>" alt="Foto Siswa">
        <div>
            <div class="id-nama"><?php echo htmlspecialchars($siswa['nama']); ?></div>
            <div class="id-sub">NIS: <?php echo htmlspecialchars($siswa['nis']); ?> &nbsp;|&nbsp; NISN: <?php echo htmlspecialchars($siswa['nisn'] ?? '-'); ?></div>
            <div class="id-sub"><?php echo htmlspecialchars($siswa['kelas'] . ' - ' . $siswa['jurusan']); ?> &nbsp;|&nbsp; T.A. <?php echo htmlspecialchars($siswa['tahun_ajaran'] ?? '-'); ?></div>
        </div>
    </div>

    <!-- ============ DATA PRIBADI ============ -->
    <div class="section-lead">
    <div class="section-title">1. Data Pribadi</div>
    <div class="two-col-wrap">
        <table class="data-table">
            <tr><td class="label">Nama Panggilan</td><td class="colon">:</td><td><?php echo isi($siswa['nama_panggilan']); ?></td></tr>
            <tr><td class="label">Jenis Kelamin</td><td class="colon">:</td><td><?php echo ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : (($siswa['jenis_kelamin'] == 'P') ? 'Perempuan' : isi(null)); ?></td></tr>
            <tr><td class="label">Tempat, Tgl Lahir</td><td class="colon">:</td><td><?php echo isi($siswa['tempat_lahir']); ?>, <?php echo $siswa['tanggal_lahir'] ? date('d-m-Y', strtotime($siswa['tanggal_lahir'])) : isi(null); ?></td></tr>
            <tr><td class="label">Anak Ke</td><td class="colon">:</td><td><?php echo isi($siswa['anak_ke']); ?></td></tr>
            <tr><td class="label">Agama</td><td class="colon">:</td><td><?php echo isi($siswa['agama']); ?></td></tr>
            <tr><td class="label">Suku</td><td class="colon">:</td><td><?php echo isi($siswa['suku']); ?></td></tr>
            <tr><td class="label">Tinggi / Berat Badan</td><td class="colon">:</td><td><?php echo isi($siswa['tinggi_badan'], 'cm'); ?> / <?php echo isi($siswa['berat_badan'], 'kg'); ?></td></tr>
            <tr><td class="label">Riwayat Penyakit</td><td class="colon">:</td><td><?php echo ($siswa['riwayat_penyakit'] === null || $siswa['riwayat_penyakit'] === '') ? 'Tidak ada' : htmlspecialchars($siswa['riwayat_penyakit']); ?></td></tr>
        </table>
        <table class="data-table">
            <tr><td class="label">Alamat Lengkap</td><td class="colon">:</td><td><?php echo isi($siswa['alamat_lengkap']); ?></td></tr>
            <tr><td class="label">No. Telepon</td><td class="colon">:</td><td><?php echo isi($siswa['no_telp']); ?></td></tr>
            <tr><td class="label">Email</td><td class="colon">:</td><td><?php echo isi($siswa['email']); ?></td></tr>
            <tr><td class="label">Instagram</td><td class="colon">:</td><td><?php echo isi($siswa['instagram']); ?></td></tr>
            <tr><td class="label">Hobi / Kegemaran</td><td class="colon">:</td><td><?php echo isi($siswa['hobi_kegemaran']); ?></td></tr>
            <tr><td class="label">Cita-cita</td><td class="colon">:</td><td><?php echo isi($siswa['cita_cita']); ?></td></tr>
            <tr><td class="label">Tempat Curhat</td><td class="colon">:</td><td><?php echo isi($siswa['tempat_curhat']); ?></td></tr>
        </table>
    </div>
    </div>

  <!-- ============ DATA KELUARGA ============ -->
<div class="section-lead">
    <div class="section-title">2. Data Keluarga</div>

    <div class="two-col-wrap">

        <!-- ================= AYAH ================= -->
        <table class="data-table">
            <tr>
                <td class="label">Nama Ayah</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['nama_ayah']); ?></td>
            </tr>
            <tr>
                <td class="label">Tempat Lahir Ayah</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['tempat_lahir_ayah']); ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Lahir Ayah</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['tanggal_lahir_ayah']); ?></td>
            </tr>
            <tr>
                <td class="label">Pekerjaan Ayah</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['pekerjaan_ayah']); ?></td>
            </tr>
            <tr>
                <td class="label">Penghasilan Ayah</td>
                <td class="colon">:</td>
                <td><?php echo formatRupiah($siswa['penghasilan_ayah']); ?></td>
            </tr>
            <tr>
                <td class="label">No. HP Ayah</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['no_hp_ayah']); ?></td>
            </tr>
        </table>

        <!-- ================= IBU ================= -->
        <table class="data-table">
            <tr>
                <td class="label">Nama Ibu</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['nama_ibu']); ?></td>
            </tr>
            <tr>
                <td class="label">Tempat Lahir Ibu</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['tempat_lahir_ibu']); ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Lahir Ibu</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['tanggal_lahir_ibu']); ?></td>
            </tr>
            <tr>
                <td class="label">Pekerjaan Ibu</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['pekerjaan_ibu']); ?></td>
            </tr>
            <tr>
                <td class="label">Penghasilan Ibu</td>
                <td class="colon">:</td>
                <td><?php echo formatRupiah($siswa['penghasilan_ibu']); ?></td>
            </tr>
            <tr>
                <td class="label">No. HP Ibu</td>
                <td class="colon">:</td>
                <td><?php echo isi($siswa['no_hp_ibu']); ?></td>
            </tr>
        </table>

    </div>

    <!-- ================= TEMPAT TINGGAL ================= -->
    <table class="data-table" style="margin-top:4px; width:100%;">
        <tr>
            <td class="label">Status Tempat Tinggal</td>
            <td class="colon">:</td>
            <td><?php echo isi($siswa['status_tempat_tinggal']); ?></td>
        </tr>
        <tr>
            <td class="label">Jarak ke Sekolah</td>
            <td class="colon">:</td>
            <td><?php echo isi($siswa['jarak_ke_sekolah']); ?></td>
        </tr>
        <tr>
            <td class="label">Transportasi ke Sekolah</td>
            <td class="colon">:</td>
            <td><?php echo isi($siswa['transportasi_ke_sekolah']); ?></td>
        </tr>
    </table>
</div>

    <!-- ============ DATA AKADEMIK & FASILITAS ============ -->
    <div class="section-lead">
    <div class="section-title">3. Riwayat Pendidikan &amp; Fasilitas Belajar</div>
    <div class="two-col-wrap">
        <table class="data-table">
            <tr><td class="label">SMP / MTs</td><td class="colon">:</td><td><?php echo isi($siswa['riwayat_smp_mts']); ?></td></tr>
            <tr><td class="label">SD / MI</td><td class="colon">:</td><td><?php echo isi($siswa['riwayat_sd_mi']); ?></td></tr>
            <tr><td class="label">Pelajaran Disenangi</td><td class="colon">:</td><td><?php echo isi($siswa['pelajaran_disenangi']); ?></td></tr>
            <tr><td class="label">Pelajaran Tdk Disenangi</td><td class="colon">:</td><td><?php echo isi($siswa['pelajaran_tdk_disenangi']); ?></td></tr>
            <tr><td class="label">Bahasa Sehari-hari</td><td class="colon">:</td><td><?php echo isi($siswa['bahasa_sehari_hari']); ?></td></tr>
            <tr><td class="label">Bahasa Asing Dikuasai</td><td class="colon">:</td><td><?php echo isi($siswa['bahasa_asing_dikuasai']); ?></td></tr>
        </table>
        <table class="data-table">
            <tr><td class="label">Kepemilikan HP/Laptop</td><td class="colon">:</td><td><?php echo isi($siswa['memiliki_hp_laptop']); ?></td></tr>
            <tr><td class="label">Fasilitas Internet</td><td class="colon">:</td><td><?php echo isi($siswa['fasilitas_internet']); ?></td></tr>
            <tr><td class="label">Fasilitas Belajar di Rumah</td><td class="colon">:</td><td><?php echo isi($siswa['fasilitas_belajar_dirumah']); ?></td></tr>
            <tr><td class="label">Buku Pelajaran Dimiliki</td><td class="colon">:</td><td><?php echo isi($siswa['buku_pelajaran_dimiliki']); ?></td></tr>
        </table>
    </div>
    </div>

    <!-- ============ TENTANG DIRI ============ -->
    <div class="section-lead">
    <div class="section-title">4. Tentang Diri, Prestasi &amp; Organisasi</div>
    <table class="data-table">
        <tr><td class="label">Tentang Saya Singkat</td><td class="colon">:</td><td class="longtext"><?php echo isi($siswa['tentang_saya_singkat']); ?></td></tr>
        <tr><td class="label">Prestasi / Pengalaman</td><td class="colon">:</td><td class="longtext"><?php echo isi($siswa['prestasi_pengalaman']); ?></td></tr>
        <tr><td class="label">Organisasi</td><td class="colon">:</td><td class="longtext"><?php echo isi($siswa['organisasi']); ?></td></tr>
        <tr><td class="label">Kelebihan Diri</td><td class="colon">:</td><td class="longtext"><?php echo isi($siswa['kelebihan_diri']); ?></td></tr>
        <tr><td class="label">Kekurangan Diri</td><td class="colon">:</td><td class="longtext"><?php echo isi($siswa['kekurangan_diri']); ?></td></tr>
    </table>
    </div>

    <!-- ============ DATA PSIKOLOGIS ============ -->
    <div class="section-lead">
    <div class="section-title">5. Hasil Tes Psikologis</div>

    <!-- 1. GAYA BELAJAR -->
    <div class="psy-box">
        <div class="psy-title">Gaya Belajar</div>
        <?php if ($siswa['skor_visual'] !== null): ?>
            <span class="psy-badge"><?php echo htmlspecialchars($gaya_belajar); ?></span>
            <?php
            foreach ($gaya_belajar_dominan_list as $gb_tipe):
                if (isset($learningStyleDescriptions[$gb_tipe])):
                    $info_gb = $learningStyleDescriptions[$gb_tipe];
            ?>
                    <div class="psy-desc"><strong>Deskripsi: </strong> <?php echo htmlspecialchars($info_gb['deskripsi']); ?></div>
                    <div class="psy-desc"><strong>Saran:</strong> <?php echo htmlspecialchars($info_gb['saran']); ?></div>
            <?php
                endif;
            endforeach;
            ?>
        <?php else: ?>
            <span class="psy-badge empty">Belum Mengerjakan</span>
        <?php endif; ?>
    </div>
    </div>

    <!-- 2. TES KEMAMPUAN / KECERDASAN MAJEMUK -->
    <div class="psy-box">
        <div class="psy-title">Tes Kemampuan / Kecerdasan Majemuk</div>
        <?php if ($hasil_kecerdasan): ?>
            <span class="psy-badge"><?php echo htmlspecialchars($tipe_kemampuan); ?></span>
            <?php
            foreach ($nama_tipe_kecerdasan_list as $mi_tipe):
                if (isset($multipleIntelligenceDescriptions[$mi_tipe])):
                    $info_mi = $multipleIntelligenceDescriptions[$mi_tipe];
            ?>
                    <div class="psy-desc"><strong>Deskripsi: </strong> <?php echo htmlspecialchars($info_mi['deskripsi']); ?></div>
                    <div class="psy-desc"><strong>Saran:</strong> <?php echo htmlspecialchars($info_mi['saran']); ?></div>
            <?php
                endif;
            endforeach;
            ?>
        <?php else: ?>
            <span class="psy-badge empty">Belum Mengerjakan</span>
        <?php endif; ?>
    </div>

    <!-- 3. TES KEPRIBADIAN -->
    <div class="psy-box">
        <div class="psy-title">Tes Kepribadian</div>
        <?php if ($hasil_kepribadian): ?>
            <span class="psy-badge"><?php echo htmlspecialchars($nama_tipe_kepribadian ?? 'Belum dapat ditentukan'); ?></span>
            <?php if ($deskripsi_kepribadian): ?>
            <div class="psy-char-row">
                <div class="psy-char-label">Deskripsi:</div>
                <div class="psy-char-content">
                    <?php
                    $karakteristik = preg_split('/\r\n|\r|\n/', trim($deskripsi_kepribadian));
                    foreach ($karakteristik as $item):
                        $item = trim($item);
                        $item = preg_replace('/^[•\-\*]\s*/u', '', $item);
                        if ($item !== ''):
                    ?>
                    <div class="psy-char-item">
                        <div class="psy-char-bullet">&bull;</div>
                        <div class="psy-char-text"><?php echo htmlspecialchars($item); ?></div>
                    </div>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($saran_kepribadian): 
                $saran_rapi = preg_replace('/\s+/', ' ', trim($saran_kepribadian));
            ?>
            <div class="psy-desc"><strong>Saran:</strong> <?php echo htmlspecialchars($saran_rapi); ?></div>
            <?php endif; ?>
        <?php else: ?>
            <span class="psy-badge empty">Belum Mengerjakan</span>
        <?php endif; ?>
    </div>

    <div class="ttd-wrap">
        <div class="ttd-box">
            Banjarmasin, <?php echo date('d F Y'); ?><br>
            <div class="space"></div>
            (____________________________)
        </div>
    </div>

</div>
</div>

</body>
</html>