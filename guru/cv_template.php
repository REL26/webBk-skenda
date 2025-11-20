<?php
// session_start() dihilangkan
include '../koneksi.php';

// Cek sederhana untuk memastikan ID siswa tersedia
if (!isset($_GET['id_siswa'])) {
    die("ID Siswa tidak ditemukan.");
}

$id_siswa = mysqli_real_escape_string($koneksi, $_GET['id_siswa']);

$query_siswa = mysqli_query($koneksi, "
    SELECT 
        s.*,
        hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
    FROM siswa s
    LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
    WHERE s.id_siswa='$id_siswa'
");
$siswa = mysqli_fetch_assoc($query_siswa);
if (!$siswa) { die("Data siswa tidak ditemukan."); }


$query_kecerdasan = mysqli_query($koneksi, "
    SELECT *
    FROM hasil_kecerdasan
    WHERE id_siswa='$id_siswa'
    ORDER BY tanggal_tes DESC 
    LIMIT 1
");
$hasil_kecerdasan = mysqli_fetch_assoc($query_kecerdasan);

$hasil_tes_kemampuan_calculated = "Belum Mengisi";
if ($hasil_kecerdasan) {

    $skor_kecerdasan = [
        'A' => $hasil_kecerdasan['skor_A'] ?? 0,
        'B' => $hasil_kecerdasan['skor_B'] ?? 0,
        'C' => $hasil_kecerdasan['skor_C'] ?? 0,
        'D' => $hasil_kecerdasan['skor_D'] ?? 0,
        'E' => $hasil_kecerdasan['skor_E'] ?? 0,
        'F' => $hasil_kecerdasan['skor_F'] ?? 0,
        'G' => $hasil_kecerdasan['skor_G'] ?? 0,
        'H' => $hasil_kecerdasan['skor_H'] ?? 0,
    ];

    $skor_tertinggi_kecerdasan = max($skor_kecerdasan);

    if ($skor_tertinggi_kecerdasan > 0) {
        $kode_tertinggi = [];
        foreach ($skor_kecerdasan as $kode => $skor) {
            if ($skor == $skor_tertinggi_kecerdasan) {
                $kode_tertinggi[] = $kode;
            }
        }
        
        $kode_list = "'" . implode("','", $kode_tertinggi) . "'";
        $query_tipe = mysqli_query($koneksi, "
            SELECT nama_tipe 
            FROM keterangan_kecerdasan 
            WHERE kode_tipe IN ($kode_list)
        ");

        $tipe_dominan_kecerdasan = [];
        while ($tipe = mysqli_fetch_assoc($query_tipe)) {
            $tipe_dominan_kecerdasan[] = $tipe['nama_tipe'];
        }
        
        if (!empty($tipe_dominan_kecerdasan)) {
            $hasil_tes_kemampuan_calculated = implode(" & ", $tipe_dominan_kecerdasan);
        } else {
            $hasil_tes_kemampuan_calculated = implode(" & ", $kode_tertinggi) . " (Keterangan tipe belum terdaftar)";
        }
    } else {
        $hasil_tes_kemampuan_calculated = "Tes Kecerdasan telah dilakukan (Skor Nol)";
    }
}

$gaya_belajar = "Belum Mengisi";
if ($siswa['skor_visual'] !== null) {
    $skor_v = $siswa['skor_visual'];
    $skor_a = $siswa['skor_auditori'];
    $skor_k = $siswa['skor_kinestetik'];
    $skor_tertinggi = max($skor_v, $skor_a, $skor_k);
    $tipe_dominan = [];
    if ($skor_v == $skor_tertinggi) $tipe_dominan[] = 'Visual';
    if ($skor_a == $skor_tertinggi) $tipe_dominan[] = 'Auditorial';
    if ($skor_k == $skor_tertinggi) $tipe_dominan[] = 'Kinestetik';
    $gaya_belajar = implode(" & ", $tipe_dominan);
}

$tanggal_lahir_formatted = '';
if (!empty($siswa['tanggal_lahir'])) {
    $date_obj = date_create($siswa['tanggal_lahir']);
    if ($date_obj !== false) {
        $bulan_indonesia = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $tanggal_lahir_formatted = date_format($date_obj, 'd F Y');
        $tanggal_lahir_formatted = strtr($tanggal_lahir_formatted, $bulan_indonesia);

    } else {
        $tanggal_lahir_formatted = 'Tanggal tidak valid';
    }
} else {
    $tanggal_lahir_formatted = 'Belum terisi';
}

// LOGIKA BARU MENGGUNAKAN URL ABSOLUT
$app_base_url = "http://localhost/websitebk-skenda/"; // PASTIKAN NAMA FOLDER ANDA BENAR

$email_siswa = $siswa['email'] ?? '';
$email_hash = md5(strtolower(trim($email_siswa)));
$gravatar_url = "https://www.gravatar.com/avatar/{$email_hash}?s=200&d=mp"; 

$url_foto = $gravatar_url;
if ($siswa['url_foto']) {
    // Check file existence in the server's file system (relative path)
    if (file_exists('../' . $siswa['url_foto'])) {
        // Gunakan URL absolute untuk DomPDF
        $url_foto = $app_base_url . $siswa['url_foto']; 
    }
}

$nama_lengkap = htmlspecialchars($siswa['nama']);
$nama_panggilan = htmlspecialchars($siswa['nama_panggilan'] ?? '');
$nis = htmlspecialchars($siswa['nis']);
$kelas_jurusan = htmlspecialchars($siswa['kelas'] . " " . $siswa['jurusan']);

$alamat = htmlspecialchars($siswa['alamat_lengkap'] ?? 'Belum terisi');
$no_telp = htmlspecialchars($siswa['no_telp'] ?? 'Belum terisi');
$email = htmlspecialchars($siswa['email'] ?? 'Belum terisi');
$instagram = htmlspecialchars($siswa['instagram'] ?? 'Belum terisi');
$agama = htmlspecialchars($siswa['agama'] ?? 'Belum terisi');
$hobi = htmlspecialchars($siswa['hobi_kegemaran'] ?? 'Belum terisi');
$tentang_saya = htmlspecialchars($siswa['tentang_saya_singkat'] ?? 'Belum terisi');

$riwayat_smk = htmlspecialchars($siswa['riwayat_sma_smk_ma'] ?? 'Belum terisi');
$riwayat_smp = htmlspecialchars($siswa['riwayat_smp_mts'] ?? 'Belum terisi');
$riwayat_sd = htmlspecialchars($siswa['riwayat_sd_mi'] ?? 'Belum terisi');

$tipe_kemampuan = htmlspecialchars($hasil_tes_kemampuan_calculated);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=210mm, initial-scale=1.0">
<title>CV Siswa | Profil Lengkap <?php echo $nama_lengkap; ?></title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ... CSS Anda yang panjang dan detail ... */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    background: #f4f4f4; 
    font-family: 'Times New Roman', Times, serif;
    padding: 20px; 
    max-width: 100vw;
    overflow-x: auto; 
}
.cv-container {
    width: 270mm; 
    min-height: 297mm;
    background: #fff;
    margin: 0 auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.15);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.cv-container i {
    color: #1e40af;
}

.header-contact li:first-child i { 
    color: #25d366;
}
.header-contact .fa-instagram {
    color: #c13584;
}

.cv-header {
    background: #e5e7eb;
    color: #1f2937;
    padding: 35px 50px;
    display: flex;
    align-items: center;
    border-bottom: 5px solid #d1d5db;
}

.header-subtitle {
    display: none; 
}
.header-photo {
    flex-shrink: 0;
    margin-right: 35px;
}
.header-photo img {
    width: 140px;
    height: 140px;
    border-radius: 10px; 
    object-fit: cover;
    border: 5px solid white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    display: block;
}
.header-details {
    flex-grow: 1;
}
.header-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #1f2937;
}
.header-contact {
    list-style: none;
    padding: 0;
    font-size: 0.95rem;
    margin-top: 15px;
}
.contact-row {
    display: flex;
    gap: 30px;
    margin-bottom: 8px;
}
.header-contact li {
    display: flex;
    align-items: center;
}
.header-contact i {
    width: 20px;
    margin-right: 8px;
}

/* PERBAIKAN UTAMA: GANTI GRID KE FLEX */
.cv-body {
    padding: 40px 50px;
    display: flex; /* GANTI DARI grid KE flex */
    gap: 45px; /* DomPDF mungkin mengabaikannya, tapi biarkan saja */
    color: #333;
    flex-grow: 1;
}
.left-content {
    width: 38%; /* Atur lebar kolom kiri secara eksplisit */
    flex-shrink: 0;
}
.right-content {
    width: 62%; /* Atur lebar kolom kanan secara eksplisit */
    flex-grow: 1;
}


.section {
    margin-bottom: 32px;
}
.section-title {
    font-weight: 700;
    font-size: 1.15rem;
    text-transform: uppercase;
    margin-bottom: 12px;
    padding-bottom: 6px;
    color: #1f2937;
    border-bottom: 3px solid #d1d5db;
    display: flex;
    align-items: center;
}
.section-title i {
    margin-right: 10px;
    color: #1e40af; 
    font-size: 1rem;
}

.detail-item {
    font-size: 0.92rem;
    margin-bottom: 8px;
    line-height: 1.6;
    display: flex;
}
.detail-item strong {
    min-width: 145px; 
    font-weight: 600;
    color: #374151;
    flex-shrink: 0;
    margin-right: 5px; 
}

.section-content p {
    font-size: 0.92rem;
    color: #444;
    line-height: 1.7;
    text-align: justify;
}
.list {
    list-style: none;
    padding-left: 0;
}
.list li {
    margin-bottom: 8px;
    padding-left: 15px; 
    position: relative;
    font-size: 0.92rem;
    line-height: 1.6;
}
.list li::before {
    content: "•"; 
    font-family: initial;
    font-weight: bold;
    color: #1e40af; 
    position: absolute;
    left: 0;
    font-size: 1em;
    top: 0; 
}

.no-print {
    text-align: center;
    padding: 25px;
}

@media screen and (max-width: 767px) {
    
    body { 
        padding: 10px; 
        background: #fff; 
        width: 100vw;
        overflow-x: hidden; 
        position: static;
        height: auto;
    }
    .cv-container {
        width: 100%; 
        min-height: auto;
        box-shadow: none; 
        margin: 0;
        padding: 0;
        flex-direction: column;
    }
    .cv-header {
        padding: 20px 20px;
        flex-direction: column;
        text-align: center;
        border-bottom: 2px solid #d1d5db;
    }
    .header-photo {
        margin-right: 0;
        margin-bottom: 15px;
    }
    .header-photo img {
        width: 100px;
        height: 100px;
        border-width: 3px;
    }
    .header-name {
        font-size: 1.8rem;
        margin-bottom: 3px;
    }
    .header-contact {
        margin-top: 10px;
    }
    .contact-row {
        flex-direction: column; 
        gap: 5px;
    }
    .header-contact li {
        justify-content: center;
    }

    .cv-body {
        padding: 20px 20px;
        grid-template-columns: 1fr; 
        gap: 20px; 
        display: block; 
    }
    .left-content, .right-content {
        width: 100%; 
    }
    .section {
        margin-bottom: 25px; 
    }
    .section-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
    }
    .detail-item {
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .detail-item strong {
        min-width: 120px;
    }
    .section-content p, .list li {
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    .no-print {
        display: flex !important;
        justify-content: center;
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #ccc;
        padding: 15px 0;
        width: 100%;
        margin-left: -10px;
        padding-left: 10px;
        padding-right: 10px;
    }
    .no-print a, .no-print button {
        flex-grow: 1;
        text-align: center;
        margin-right: 5px;
        margin-left: 5px;
    }
    .no-print button:last-child {
        margin-right: 0;
    }
}

@media print {
* { 
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
}
@page {
    size: A4 portrait;
    margin: 0;
}
html, body {
    width: 210mm;
    height: 297mm;
    background: white !important;
    margin: 0;
    padding: 0;
    overflow: hidden !important;
}
body {
    transform: scale(0.88);
    transform-origin: top center;
    padding-top: 10mm;
}
.cv-container {
    width: 210mm;
    height: 287mm;
    background: white !important;
    margin: 0 auto;
    box-shadow: none !important;
    padding: 0;
    overflow: hidden;
}
.cv-header {
    padding: 20px 30px !important;
}
.cv-body {
    padding: 25px 30px !important;
    gap: 25px !important;
    display: flex !important; /* Pastikan flex untuk print */
}
.left-content {
    width: 38% !important; /* Atur lebar secara eksplisit untuk print */
    flex-shrink: 0 !important;
}
.right-content {
    width: 62% !important; /* Atur lebar secara eksplisit untuk print */
    flex-grow: 1 !important;
}

.section {
    margin-bottom: 18px !important;
    break-inside: avoid !important;
}
.section-title {
    font-size: 1rem !important;
    margin-bottom: 6px !important;
}
.detail-item {
    font-size: 0.9rem !important;
    line-height: 1.4 !important;
    margin-bottom: 5px !important;
}
.detail-item strong {
    font-size: 0.9rem !important;
    line-height: 1.4 !important;
}
.section-content p,
.list li {
    font-size: 0.9rem !important;
    line-height: 1.5 !important;
}
.left-content, .right-content {
    flex: unset !important;
    max-width: unset !important;
}
.no-print {
    display: none !important;
}
}
</style>
</head>
<body>
<div class="cv-container">
    <div class="cv-header">
        <div class="header-photo">
            <img src="<?php echo $url_foto; ?>" alt="Foto Profil">
        </div>
        <div class="header-details">
            <h1 class="header-name"><?php echo $nama_lengkap; ?></h1>
            <ul class="header-contact">
                <div class="contact-row">
                    <li><i class="fas fa-phone-alt"></i> <?php echo $no_telp; ?></li> 
                    <li><i class="fas fa-envelope"></i> <?php echo $email; ?></li>
                    <li><i class="fab fa-instagram"></i> <?php echo $instagram; ?></li>
                </div>
                <li><i class="fas fa-map-marker-alt"></i> <?php echo $alamat; ?></li>
            </ul>
        </div>
    </div>
    <div class="cv-body">
        
        <div class="left-content">
            <div class="section">
                <h3 class="section-title"><i class="fas fa-id-card"></i>Biodata</h3>
                <div class="detail-list">
                    <p class="detail-item"><strong>NIS</strong> : <?php echo $nis; ?></p>
                    <p class="detail-item"><strong>Nama Panggilan</strong> : <?php echo $nama_panggilan; ?></p>
                    <p class="detail-item"><strong>Tempat Lahir</strong> : <?php echo htmlspecialchars($siswa['tempat_lahir'] ?? 'Belum terisi'); ?></p>
                    <p class="detail-item"><strong>Tanggal Lahir</strong> : <?php echo $tanggal_lahir_formatted; ?></p>
                    <p class="detail-item"><strong>Agama</strong> : <?php echo $agama; ?></p>
                    <p class="detail-item"><strong>Tinggi Badan</strong> : <?php echo htmlspecialchars($siswa['tinggi_badan'] ?? 'Belum terisi'); ?> cm</p>
                    <p class="detail-item"><strong>Berat Badan</strong> : <?php echo htmlspecialchars($siswa['berat_badan'] ?? 'Belum terisi'); ?> kg</p>
                    <p class="detail-item"><strong>Hobi / Kegemaran</strong> : <?php echo $hobi; ?></p>
                </div>
            </div>

            <div class="section">
                <h3 class="section-title"><i class="fas fa-brain"></i>Hasil Tes</h3>
                <div class="detail-list">
                    <p class="detail-item"><strong>Gaya Belajar</strong> : <?php echo $gaya_belajar; ?></p>
                    <p class="detail-item"><strong>Tipe Kemampuan</strong> : <?php echo $tipe_kemampuan; ?></p>
                </div>
            </div>
        </div>

        <div class="right-content"> <div class="section">
                <h3 class="section-title"><i class="fas fa-user-circle"></i>Tentang Saya</h3>
                <div class="section-content">
                    <p><?php echo $tentang_saya; ?></p>
                </div>
            </div>

            <div class="section">
                <h3 class="section-title"><i class="fas fa-graduation-cap"></i>Riwayat Pendidikan</h3>
                <div class="section-content">
                    <ul class="list">
                        <li>SMK/MA : <?php echo $riwayat_smk; ?></li>
                        <li>SMP/MTs : <?php echo $riwayat_smp; ?></li>
                        <li>SD/MI : <?php echo $riwayat_sd; ?></li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($siswa['prestasi_pengalaman'])): ?>
            <div class="section">
                <h3 class="section-title"><i class="fas fa-trophy"></i>Prestasi & Pengalaman</h3>
                <div class="section-content">
                    <ul class="list">
                        <?php foreach (explode("\n", $siswa['prestasi_pengalaman']) as $item) {
                            if (trim($item)) echo "<li>".htmlspecialchars(trim($item))."</li>";
                        } ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($siswa['organisasi'])): ?>
            <div class="section">
                <h3 class="section-title"><i class="fas fa-users"></i>Organisasi</h3>
                <div class="section-content">
                    <ul class="list">
                        <?php foreach (explode("\n", $siswa['organisasi']) as $item) {
                            if (trim($item)) echo "<li>".htmlspecialchars(trim($item))."</li>";
                        } ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="no-print">
    <a href="detail_biodata.php?id_siswa=<?php echo $id_siswa; ?>" class="btn bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition duration-150 ease-in-out">
        <i class="fas fa-arrow-left mr-2"></i> KEMBALI
    </a>
    <button onclick="window.print()" class="btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-150 ease-in-out">
        <i class="fas fa-file-pdf mr-2"></i> EKSPOR PDF
    </button>
</div>
</body>
</html>