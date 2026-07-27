<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    header("Location: ../login.php");
    exit;
}

$id_siswa = $_SESSION['id_siswa'];

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
$daftar_gaya = [];
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
    $daftar_gaya = $tipe_dominan;
}

$penjelasan_gaya_belajar = [
    'Visual' => [
        'ciri' => [
            'Belajar lebih mudah melalui gambar, warna, atau diagram.'
        ],
        'saran' => [
            'Gunakan mind map, video, dan catatan berwarna.'
        ]
    ],

    'Auditorial' => [
        'ciri' => [
            'Lebih cepat memahami penjelasan yang didengar.'
        ],
        'saran' => [
            'Belajar melalui diskusi atau mendengarkan rekaman materi.'
        ]
    ],

    'Kinestetik' => [
        'ciri' => [
            'Lebih mudah belajar melalui praktik langsung.'
        ],
        'saran' => [
            'Belajar sambil praktik atau simulasi.'
        ]
    ]
];

$penjelasan_tipe_kemampuan = [
    'Linguistik' => [
        'ciri' => [
            'Senang membaca, menulis, dan menyampaikan ide.',
            'Mudah memahami informasi melalui kata-kata.'
        ],
        'penjelasan' => 'Mudah memahami dan menyampaikan informasi melalui bahasa, baik lisan maupun tulisan.'
    ],

    'Logis-Matematis' => [
        'ciri' => [
            'Suka menganalisis, berhitung, dan memecahkan masalah.',
            'Berpikir runtut dan logis.'
        ],
        'penjelasan' => 'Cenderung berpikir sistematis dan mudah memahami pola, angka, serta hubungan sebab-akibat.'
    ],

    'Visual-Spasial' => [
        'ciri' => [
            'Mudah memahami gambar, bentuk, peta, dan desain.',
            'Memiliki imajinasi visual yang baik.'
        ],
        'penjelasan' => 'Mudah memahami informasi melalui gambar, ruang, dan bentuk visual lainnya.'
    ],

    'Kinestetik' => [
        'ciri' => [
            'Lebih suka belajar melalui praktik langsung.',
            'Aktif menggunakan keterampilan fisik.'
        ],
        'penjelasan' => 'Lebih mudah memahami sesuatu melalui gerakan tubuh dan pengalaman langsung.'
    ],

    'Musikal' => [
        'ciri' => [
            'Peka terhadap nada, irama, dan musik.',
            'Mudah mengingat melalui lagu atau suara.'
        ],
        'penjelasan' => 'Memiliki kepekaan tinggi terhadap suara, nada, dan pola irama.'
    ],

    'Interpersonal' => [
        'ciri' => [
            'Mudah bekerja sama dan berkomunikasi.',
            'Mampu memahami perasaan orang lain.'
        ],
        'penjelasan' => 'Mudah berinteraksi, bekerja sama, dan memahami perasaan serta kebutuhan orang lain.'
    ],

    'Intrapersonal' => [
        'ciri' => [
            'Mengenal diri sendiri dengan baik.',
            'Mampu mengatur emosi dan memiliki tujuan.'
        ],
        'penjelasan' => 'Memiliki pemahaman yang baik tentang diri sendiri, termasuk perasaan dan tujuan pribadi.'
    ],

    'Naturalis' => [
        'ciri' => [
            'Menyukai alam, tumbuhan, dan hewan.',
            'Peka terhadap lingkungan sekitar.'
        ],
        'penjelasan' => 'Memiliki kepekaan terhadap alam serta mudah mengenali dan mengelompokkan hal-hal di lingkungan sekitar.'
    ]
];

$daftar_tipe_kemampuan = array_map('trim', explode('&', $hasil_tes_kemampuan_calculated));

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

$email_siswa = $siswa['email'] ?? '';
$email_hash = md5(strtolower(trim($email_siswa)));
$gravatar_url = "https://www.gravatar.com/avatar/{$email_hash}?s=200&d=mp"; 

$url_foto = $siswa['url_foto'] 
    ? (file_exists('../' . $siswa['url_foto']) ? '../' . $siswa['url_foto'] : $gravatar_url) 
    : $gravatar_url;


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

$tempat_lahir = htmlspecialchars($siswa['tempat_lahir'] ?? 'Belum terisi');
$tinggi_badan = htmlspecialchars($siswa['tinggi_badan'] ?? 'Belum terisi');
$berat_badan = htmlspecialchars($siswa['berat_badan'] ?? 'Belum terisi');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<title>CV Siswa | Profil Lengkap <?php echo $nama_lengkap; ?></title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<style>
@page { size: A4; margin: 0; }
* { margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; box-sizing: border-box; }
html, body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    color: #2d2d2d;
    background-color: #e9edf3;
    margin: 0;
    padding: 0;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}
table { border-collapse: collapse; }

/* ================= TOOLBAR ================= */
.toolbar-wrap {
    position: sticky;
    top: 0;
    z-index: 999;
    background: linear-gradient(180deg, #ffffff 0%, #f2f4f8 100%);
    border-bottom: 1px solid #dbe1ea;
    padding: 14px 16px;
    box-shadow: 0 2px 10px rgba(20, 36, 60, 0.08);
}
.toolbar {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.toolbar a,
.toolbar button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 22px;
    text-decoration: none;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.2px;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
    font-family: Arial, sans-serif;
}
.toolbar a.btn-kembali {
    background-color: #ffffff;
    color: #1a2e4a;
    border: 2px solid #1a2e4a;
    box-shadow: 0 2px 6px rgba(26, 46, 74, 0.12);
}
.toolbar a.btn-kembali:hover {
    background-color: #1a2e4a;
    color: #ffffff;
    transform: translateY(-1px);
}
.toolbar button.btn-export {
    background: linear-gradient(135deg, #4a90c4 0%, #2f6fa3 100%);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(74, 144, 196, 0.35);
}
.toolbar button.btn-export:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(74, 144, 196, 0.45);
}
.toolbar a.btn-kembali:active,
.toolbar button.btn-export:active {
    transform: translateY(0);
    opacity: 0.9;
}
.icon-svg { width: 15px; height: 15px; display: inline-block; flex-shrink: 0; }

/* ================= CV WRAPPER (fixed size, scrollable on mobile) ================= */
.cv-outer {
    width: 100%;
    overflow-x: auto;
    padding: 20px 0 40px 0;
    -webkit-overflow-scrolling: touch;
}
.cv-page {
    width: 794px;      /* ukuran A4 (px @96dpi) tetap, tidak mengecil di HP */
    min-width: 794px;
    margin: 0 auto;
    background-color: #ffffff;
    box-shadow: 0 4px 18px rgba(20, 36, 60, 0.15);
}

@media (max-width: 850px) {
    .cv-outer { padding: 16px 0 32px 0; }
    .toolbar-wrap { padding: 12px 10px; }
    .toolbar a, .toolbar button { padding: 10px 18px; font-size: 12.5px; }
}

@media print {
    html, body {
        background-color: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
    }
    .toolbar-wrap {
        display: none !important;
        position: static !important;
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    .cv-outer {
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        background-color: #ffffff !important;
    }
    .cv-page {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-shadow: none !important;
        margin: 0 !important;
    }
}
</style>
</head>
<body>

<div class="toolbar-wrap">
    <div class="toolbar">
        <a class="btn-kembali" href="data_profiling.php">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <button class="btn-export" onclick="window.print()">
            <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
            Ekspor PDF
        </button>
    </div>
</div>

<div class="cv-outer">
<div class="cv-page">

<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;background-color:#1a2e4a;">
<tr>
    <td width="150" style="width:300px;background-color:#14243c;padding:24px 18px 24px 24px;text-align:center;vertical-align:middle;">
        <img src="<?php echo $url_foto; ?>" alt="Foto Profil"
             width="108" height="108"
             style="width:108px;height:108px;border:3px solid #4a90c4;display:block;margin:0 auto;">
    </td>
    <td style="padding:22px 24px 22px 20px;vertical-align:middle;background-color:#1a2e4a;">
        <div style="font-size:22px;font-weight:bold;color:#ffffff;margin-bottom:3px;"><?php echo $nama_lengkap; ?></div>
        <div style="font-size:11px;color:#90b8d8;margin-bottom:14px;font-style:italic;"><?php echo $kelas_jurusan; ?></div>
        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;border-top:1px solid #2e4d6e;">
            <tr><td colspan="6" style="height:12px;font-size:1px;line-height:1px;">&nbsp;</td></tr>
            <tr>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;width:30px;">Telp</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;width:8px;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 16px 3px 0;vertical-align:top;width:45%;"><?php echo $no_telp; ?></td>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;width:36px;">Email</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;width:8px;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 0;vertical-align:top;"><?php echo $email; ?></td>
            </tr>
            <tr>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;">Alamat</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 16px 3px 0;vertical-align:top;"><?php echo $alamat; ?></td>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;">Instagram</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 0;vertical-align:top;"><?php echo $instagram; ?></td>
            </tr>
        </table>
    </td>
</tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
<tr>
    <td height="4" style="background-color:#4a90c4;height:4px;font-size:1px;line-height:1px;">&nbsp;</td>
</tr>
</table>

<table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
<tr>

    <td width="36%" style="width:36%;vertical-align:top;padding:22px 16px 22px 24px;border-right:1px solid #dde3ea;background-color:#f5f7fa;">

        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Biodata</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;width:95px;">NIS</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;width:10px;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $nis; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Nama Panggilan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $nama_panggilan; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tempat Lahir</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $tempat_lahir; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tanggal Lahir</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $tanggal_lahir_formatted; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Agama</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $agama; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tinggi Badan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $tinggi_badan; ?> cm</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Berat Badan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $berat_badan; ?> kg</td>
                </tr>
                <tr>
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Hobi</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;"><?php echo $hobi; ?></td>
                </tr>
            </table>
        </td></tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Hasil Tes</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;width:95px;">Gaya Belajar</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;width:10px;text-align:center;">:</td>
                    <td style="font-size:10px;padding:4px 0;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0"><tr>
                            <td style="background-color:#e8f2fb;color:#1a5f8a;border:1px solid #b0d0ea;padding:2px 7px;font-size:9px;font-weight:bold;"><?php echo $gaya_belajar; ?></td>
                        </tr></table>
                    </td>
                </tr>
                <?php foreach ($daftar_gaya as $gaya):
                    if (!isset($penjelasan_gaya_belajar[$gaya])) continue;
                    $info = $penjelasan_gaya_belajar[$gaya];
                ?>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td colspan="3" style="padding:4px 0 10px 0;">
                        <div style="font-size:9px;font-weight:bold;color:#1a5f8a;margin-bottom:3px;">Ciri-ciri</div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:6px;">
                            <?php foreach ($info['ciri'] as $ciri): ?>
                            <tr>
                                <td width="10" style="width:10px;font-size:9px;color:#4a90c4;font-weight:bold;vertical-align:top;padding:2px 4px 2px 0;">&bull;</td>
                                <td style="font-size:9px;color:#3d3d3d;line-height:1.5;vertical-align:top;padding:2px 0;"><?php echo htmlspecialchars($ciri); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                        <div style="font-size:9px;font-weight:bold;color:#1a5f8a;margin-bottom:3px;">Saran Belajar</div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
                            <?php foreach ($info['saran'] as $saran): ?>
                            <tr>
                                <td width="10" style="width:10px;font-size:9px;color:#4a90c4;font-weight:bold;vertical-align:top;padding:2px 4px 2px 0;">&bull;</td>
                                <td style="font-size:9px;color:#3d3d3d;line-height:1.5;vertical-align:top;padding:2px 0;"><?php echo htmlspecialchars($saran); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tipe Kemampuan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 4px;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;padding:4px 0;vertical-align:top;">
                        <table cellpadding="0" cellspacing="0"><tr>
                            <td style="background-color:#e8f2fb;color:#1a5f8a;border:1px solid #b0d0ea;padding:2px 7px;font-size:9px;font-weight:bold;"><?php echo $tipe_kemampuan; ?></td>
                        </tr></table>
                    </td>
                </tr>
                <?php foreach ($daftar_tipe_kemampuan as $tipe):
                    if (!isset($penjelasan_tipe_kemampuan[$tipe])) continue;
                    $info_tipe = $penjelasan_tipe_kemampuan[$tipe];
                ?>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td colspan="3" style="padding:4px 0 10px 0;">
                        <div style="font-size:9px;font-weight:bold;color:#1a2e4a;margin-bottom:4px;"><?php echo htmlspecialchars($tipe); ?></div>
                        <div style="font-size:9px;font-weight:bold;color:#1a5f8a;margin-bottom:3px;">Ciri-ciri</div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:6px;">
                            <?php foreach ($info_tipe['ciri'] as $ciri): ?>
                            <tr>
                                <td width="10" style="width:10px;font-size:9px;color:#4a90c4;font-weight:bold;vertical-align:top;padding:2px 4px 2px 0;">&bull;</td>
                                <td style="font-size:9px;color:#3d3d3d;line-height:1.5;vertical-align:top;padding:2px 0;"><?php echo htmlspecialchars($ciri); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                        <?php if (!empty($info_tipe['penjelasan'])): ?>
                        <div style="font-size:9px;font-weight:bold;color:#1a5f8a;margin-bottom:3px;">Penjelasan</div>
                        <div style="font-size:9px;color:#3d3d3d;line-height:1.5;"><?php echo htmlspecialchars($info_tipe['penjelasan']); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if ($gaya_belajar != "Belum Mengisi"): ?>
                <tr>
                    <td colspan="3" style="padding:8px 0 0 0;">
                        <div style="font-size:9px;font-weight:bold;color:#1a5f8a;margin-bottom:3px;">Kesimpulan</div>
                        <div style="font-size:9px;color:#3d3d3d;line-height:1.5;">
                            Kamu cenderung bergaya belajar <strong><?php echo htmlspecialchars($gaya_belajar); ?></strong>
                            dengan kekuatan pada tipe kemampuan <strong><?php echo htmlspecialchars($hasil_tes_kemampuan_calculated); ?></strong>. Ini bukan berarti kamu hanya bisa belajar dengan cara itu, tetapi cara tersebut biasanya membuatmu lebih cepat memahami materi. Tetap latih ketiga gaya belajar agar kemampuan belajarmu semakin lengkap dan mudah menyesuaikan diri dalam berbagai situasi pembelajaran.
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </td></tr>
        </table>

    </td>

    <td width="64%" style="width:64%;vertical-align:top;padding:22px 24px 22px 20px;background-color:#ffffff;">

        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Tentang Saya</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="background-color:#eef5fb;border-left:3px solid #4a90c4;padding:10px 12px;font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;"><?php echo $tentang_saya; ?></td>
            </tr>
            </table>
        </td></tr>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Riwayat Pendidikan</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
                <tr style="border-bottom:1px solid #eaecef;">
                    <td width="16" style="width:16px;color:#4a90c4;font-size:14px;font-weight:bold;padding:4px 4px 4px 0;vertical-align:top;">&bull;</td>
                    <td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;"><strong>SMK / MA:</strong> <?php echo $riwayat_smk; ?></td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="width:16px;color:#4a90c4;font-size:14px;font-weight:bold;padding:4px 4px 4px 0;vertical-align:top;">&bull;</td>
                    <td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;"><strong>SMP / MTs:</strong> <?php echo $riwayat_smp; ?></td>
                </tr>
                <tr>
                    <td style="width:16px;color:#4a90c4;font-size:14px;font-weight:bold;padding:4px 4px 4px 0;vertical-align:top;">&bull;</td>
                    <td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;"><strong>SD / MI:</strong> <?php echo $riwayat_sd; ?></td>
                </tr>
            </table>
        </td></tr>
        </table>

        <?php if (!empty($siswa['prestasi_pengalaman'])): ?>
        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Prestasi &amp; Pengalaman</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <?php foreach (explode("\n", $siswa['prestasi_pengalaman']) as $item):
                    $item = trim($item);
                    if ($item === '') continue; ?>
                <tr>
                    <td width="16" style="width:16px;vertical-align:top;font-size:14px;color:#4a90c4;font-weight:bold;padding:4px 4px 4px 0;">&bull;</td>
                    <td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;"><?php echo htmlspecialchars($item); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td></tr>
        </table>
        <?php endif; ?>

        <?php if (!empty($siswa['organisasi'])): ?>
        <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <td style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;padding-top:0;">
                <table cellpadding="0" cellspacing="0"><tr>
                    <td width="7" height="7" style="width:7px;height:7px;background-color:#4a90c4;font-size:1px;line-height:1px;">&nbsp;</td>
                    <td style="padding-left:6px;font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;">Organisasi</td>
                </tr></table>
            </td>
        </tr>
        <tr><td style="padding-top:10px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <?php foreach (explode("\n", $siswa['organisasi']) as $item):
                    $item = trim($item);
                    if ($item === '') continue; ?>
                <tr>
                    <td width="16" style="width:16px;vertical-align:top;font-size:14px;color:#4a90c4;font-weight:bold;padding:4px 4px 4px 0;">&bull;</td>
                    <td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;"><?php echo htmlspecialchars($item); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </td></tr>
        </table>
        <?php endif; ?>

    </td>

</tr>
</table>

</div>
</div>

</body>
</html>