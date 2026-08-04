<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa']) || !isset($_GET['versi'])) {
    header("Location: hasil_tes.php");
    exit;
}

$id_siswa = (int) $_GET['id_siswa'];
$versi    = trim((string) $_GET['versi']);
$versi_valid = ['X', 'XI', 'XII'];

if ($id_siswa <= 0 || !in_array($versi, $versi_valid, true)) {
    die("Parameter tidak valid.");
}

$q_siswa = mysqli_prepare($koneksi, "SELECT nama, kelas, jurusan FROM siswa WHERE id_siswa = ? LIMIT 1");
mysqli_stmt_bind_param($q_siswa, "i", $id_siswa);
mysqli_stmt_execute($q_siswa);
$siswa = mysqli_stmt_get_result($q_siswa)->fetch_assoc();
mysqli_stmt_close($q_siswa);

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

// Pastikan siswa memang sudah mengerjakan versi ini sebelum dicetak.
$q_hasil = mysqli_prepare($koneksi, "SELECT id_hasil FROM hasil_asesmen WHERE id_siswa = ? AND versi = ? LIMIT 1");
mysqli_stmt_bind_param($q_hasil, "is", $id_siswa, $versi);
mysqli_stmt_execute($q_hasil);
$hasil_ada = mysqli_stmt_get_result($q_hasil)->fetch_assoc();
mysqli_stmt_close($q_hasil);

if (!$hasil_ada) {
    die("Siswa ini belum memiliki hasil Asesmen BK Kelas $versi.");
}

/**
 * Konten laporan statis (identik dengan format resmi di
 * Hasil_Asesmen_BK_X_XI_XII.docx). Bagian HASIL UTAMA & REKOMENDASI
 * sengaja fixed per versi (bukan digenerate dari jawaban_json
 * masing-masing siswa) sesuai permintaan klien — hanya identitas
 * siswa yang dinamis.
 */
$konten = [
    'X' => [
        'judul'    => 'LAPORAN HASIL ASESMEN SISWA KELAS X',
        'subjudul' => 'Mengenal Diri, Beradaptasi di SMK, dan Merancang Masa Depan',
        'hasil_utama' => [
            'Merasa senang/sangat senang bersekolah di sini, memilih jurusan sesuai minat dan peluang kerja. Kendala utama: manajemen waktu dan kepercayaan diri.',
            'Kelebihan: disiplin, bertanggung jawab, mudah bergaul, cepat belajar. Ingin ditingkatkan: percaya diri, komunikasi, public speaking.',
            'Belajar rata-rata 1–2 jam sehari; saat kesulitan mencari lewat internet atau bertanya guru. Lebih paham lewat praktik langsung, contoh nyata, dan kaitan materi dengan dunia kerja. Suka tugas praktik/proyek.',
        ],
        'rekomendasi' => [
            'Perbanyak praktik dan hubungkan materi dengan dunia kerja nyata.',
            'Gunakan media menarik seperti video dan alat peraga; berikan umpan balik jelas.',
            'Berikan kesempatan bertanya dan berpendapat untuk melatih kepercayaan diri.',
            'Berikan panduan cara mengatur waktu belajar dengan baik.',
        ],
    ],
    'XI' => [
        'judul'    => 'LAPORAN HASIL ASESMEN SISWA KELAS XI',
        'subjudul' => 'Pengembangan Kompetensi, Soft Skill, dan Persiapan Karier',
        'hasil_utama' => [
            'Perkembangan belajar umumnya baik hingga sangat baik; sudah menguasai kompetensi dasar, komunikasi, dan kerja tim. Masih ada materi yang perlu diperdalam.',
            'Sebagian sudah melaksanakan PKL: meningkat kedisiplinan, penggunaan teknologi, dan keterampilan kejuruan. Kendala: adaptasi lingkungan, komunikasi profesional, dan penguasaan materi.',
            'Soft skill cukup baik, namun perlu dilatih kepemimpinan, percaya diri berbicara di depan umum, dan penyelesaian masalah.',
            'Rencana masa depan mulai terarah; membutuhkan persiapan sertifikat kompetensi, bahasa Inggris, portofolio, dan simulasi seleksi.',
        ],
        'rekomendasi' => [
            'Gunakan studi kasus dan hubungkan materi dengan kebutuhan industri saat ini.',
            'Perbanyak praktik di laboratorium, gunakan peralatan/software terbaru, persiapkan siswa menghadapi sertifikasi.',
            'Latih kemampuan bahasa Inggris, keterampilan digital, kepemimpinan, dan penyusunan dokumen karier.',
        ],
    ],
    'XII' => [
        'judul'    => 'LAPORAN HASIL ASESMEN SISWA KELAS XII',
        'subjudul' => 'Perencanaan Karier dan Masa Depan Peserta Didik',
        'hasil_utama' => [
            'Umumnya merasa siap/sangat siap menjelang lulus; memiliki kelebihan karakter dan sudah menguasai kompetensi inti jurusan.',
            'Rencana setelah lulus sudah jelas: kuliah, bekerja, berwirausaha, atau kuliah sambil bekerja.',
            'Memiliki pengalaman PKL, organisasi, dan sertifikat; masih butuh pendampingan jalur masuk kampus, penyusunan CV/portofolio, simulasi wawancara, atau perencanaan usaha.',
            'Ingin lebih diperdalam: etika dunia kerja, bahasa Inggris, teknologi terbaru, dan kesiapan mental menghadapi persaingan.',
        ],
        'rekomendasi' => [
            'Hubungkan materi dengan persyaratan kuliah maupun dunia kerja; latih berpikir kritis dan presentasi.',
            'Fokus pada praktik nyata, simulasi pekerjaan, persiapan sertifikasi, wawancara kerja, dan penyusunan dokumen profesional.',
            'Hadirkan praktisi industri dan berikan pelatihan pendukung seperti bahasa Inggris, etika kerja, serta pemanfaatan teknologi/AI.',
        ],
    ],
];

$data = $konten[$versi];
$url_kembali = "detail_hasil_asesmen.php?id_siswa=" . urlencode((string) $id_siswa) . "&versi=" . urlencode($versi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Hasil Asesmen Kelas <?php echo htmlspecialchars($versi); ?> - <?php echo htmlspecialchars($siswa['nama']); ?></title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<style>
@page { size: A4; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
html, body { font-family: Arial, sans-serif; font-size: 12.5px; color: #1a1a1a; background-color: #e9edf3; }

/* ================= TOOLBAR ================= */
.toolbar-wrap {
    position: sticky; top: 0; z-index: 999;
    background: linear-gradient(180deg, #ffffff 0%, #f2f4f8 100%);
    border-bottom: 1px solid #dbe1ea; padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(20, 36, 60, 0.08);
}
.toolbar { max-width: 900px; margin: 0 auto; display: flex; justify-content: center; align-items: center; gap: 14px; flex-wrap: wrap; }
.toolbar a, .toolbar button {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 22px; text-decoration: none; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 700; cursor: pointer; font-family: Arial, sans-serif;
}
.toolbar a.btn-kembali { background-color: #ffffff; color: #1a2e4a; border: 2px solid #1a2e4a; box-shadow: 0 2px 6px rgba(26,46,74,0.12); }
.toolbar a.btn-kembali:hover { background-color: #1a2e4a; color: #ffffff; }
.toolbar button.btn-export { background: linear-gradient(135deg, #4a90c4 0%, #2f6fa3 100%); color: #ffffff; box-shadow: 0 4px 10px rgba(74,144,196,0.35); }
.icon-svg { width: 15px; height: 15px; }

/* ================= WRAPPER ================= */
.doc-outer { width: 100%; overflow-x: auto; padding: 20px 0 40px 0; -webkit-overflow-scrolling: touch; }
.doc-page { width: 794px; min-width: 794px; margin: 0 auto; background: #ffffff; box-shadow: 0 4px 18px rgba(20,36,60,0.15); padding: 40px 48px; }

/* ================= ISI (polos, sama seperti docx) ================= */
h1 { font-size: 15px; font-weight: bold; margin-bottom: 4px; }
.subjudul { font-size: 13px; margin-bottom: 16px; }
.section-label { font-weight: bold; font-size: 13px; margin-top: 16px; margin-bottom: 8px; }
.identitas-row { margin-bottom: 6px; font-size: 13px; }
.garis-isi { display: inline-block; border-bottom: 1px solid #000; min-width: 320px; padding-bottom: 1px; }
ul, ol { margin-left: 22px; }
ul li, ol li { font-size: 13px; line-height: 1.6; margin-bottom: 8px; }

@media (max-width: 850px) {
    .doc-outer { padding: 16px 0 32px 0; }
    .toolbar-wrap { padding: 12px 10px; }
    .toolbar a, .toolbar button { padding: 10px 18px; font-size: 12.5px; }
}

@media print {
    html, body { background-color: #ffffff !important; margin: 0 !important; padding: 0 !important; height: auto !important; }
    .toolbar-wrap { display: none !important; position: static !important; height: 0 !important; padding: 0 !important; margin: 0 !important; border: none !important; box-shadow: none !important; }
    .doc-outer { padding: 0 !important; margin: 0 !important; overflow: visible !important; background-color: #ffffff !important; }
    .doc-page { width: 100% !important; min-width: 0 !important; max-width: 100% !important; box-shadow: none !important; margin: 0 !important; padding: 1.2cm 1.6cm !important; }
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

    <h1><?php echo htmlspecialchars($data['judul']); ?></h1>
    <div class="subjudul"><?php echo htmlspecialchars($data['subjudul']); ?></div>

    <div class="section-label">IDENTITAS SISWA</div>
    <div class="identitas-row">Nama Lengkap : <span class="garis-isi"><?php echo htmlspecialchars($siswa['nama']); ?></span></div>
    <div class="identitas-row">Kelas / Jurusan : <span class="garis-isi"><?php echo htmlspecialchars($siswa['kelas'] . ' / ' . $siswa['jurusan']); ?></span></div>

    <div class="section-label">HASIL UTAMA</div>
    <ul>
        <?php foreach ($data['hasil_utama'] as $poin): ?>
        <li><?php echo htmlspecialchars($poin); ?></li>
        <?php endforeach; ?>
    </ul>

    <div class="section-label">REKOMENDASI</div>
    <ol>
        <?php foreach ($data['rekomendasi'] as $poin): ?>
        <li><?php echo htmlspecialchars($poin); ?></li>
        <?php endforeach; ?>
    </ol>

</div>
</div>

</body>
</html>