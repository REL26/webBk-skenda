<?php
session_start();
include '../koneksi.php';

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['action']) || $_GET['action'] !== 'export_all_cv') {
    die("Invalid access");
}

$tahun_map = [
    "1" => "2024/2025",
    "2" => "2025/2026",
    "3" => "2026/2027",
    "4" => "2027/2028"
];

$where   = [];
$where[] = "s.kelas != 'LULUS'";

$filters = [
    "nama"    => "s.nama",
    "kelas"   => "s.kelas",
    "jurusan" => "s.jurusan",
    "nis"     => "s.nis",
    "gender"  => "s.jenis_kelamin",
    "tahun"   => "s.tahun_ajaran_id"
];

$filter_label = [];

foreach ($filters as $key => $field) {
    if (!empty($_GET[$key])) {
        $val = mysqli_real_escape_string($koneksi, $_GET[$key]);

        if (in_array($key, ['kelas', 'jurusan', 'gender', 'tahun'])) {
            $where[] = "$field = '$val'";
        } else {
            $where[] = "$field LIKE '%$val%'";
        }

        if ($key === "tahun" && isset($tahun_map[$val])) {
            $filter_label[] = $tahun_map[$val];
        } else {
            $filter_label[] = strtoupper($key) . "-" . $val;
        }
    }
}

$whereSQL = implode(" AND ", $where);

$folderName = "CV_SISWA";
if (!empty($filter_label)) {
    $folderName .= "_" . implode("_", $filter_label);
}

$folderName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $folderName);

$query = mysqli_query($koneksi, "
    SELECT id_siswa, nama, kelas, jurusan
    FROM siswa s
    WHERE $whereSQL
");

if (!$query) die(mysqli_error($koneksi));

$tempDir = __DIR__ . "/temp_pdf/";
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$zipName = $folderName . ".zip";
$zipPath = $tempDir . $zipName;

$zip = new ZipArchive;
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

function buildCvHtml($id_siswa_param, $koneksi) {

    $id_siswa = mysqli_real_escape_string($koneksi, $id_siswa_param);

    $query_siswa = mysqli_query($koneksi, "
        SELECT s.*, hg.skor_visual, hg.skor_auditori, hg.skor_kinestetik
        FROM siswa s
        LEFT JOIN hasil_gayabelajar hg ON s.id_siswa = hg.id_siswa
        WHERE s.id_siswa='$id_siswa'
    ");
    $siswa = mysqli_fetch_assoc($query_siswa);
    if (!$siswa) return '';

    $query_kecerdasan = mysqli_query($koneksi, "
        SELECT * FROM hasil_kecerdasan
        WHERE id_siswa='$id_siswa'
        ORDER BY tanggal_tes DESC LIMIT 1
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
                if ($skor == $skor_tertinggi_kecerdasan) $kode_tertinggi[] = $kode;
            }
            $kode_list = "'" . implode("','", $kode_tertinggi) . "'";
            $query_tipe = mysqli_query($koneksi, "
                SELECT nama_tipe FROM keterangan_kecerdasan WHERE kode_tipe IN ($kode_list)
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

    $tanggal_lahir_formatted = 'Belum terisi';
    if (!empty($siswa['tanggal_lahir'])) {
        $date_obj = date_create($siswa['tanggal_lahir']);
        if ($date_obj !== false) {
            $bulan_indonesia = [
                'January'=>'Januari','February'=>'Februari','March'=>'Maret',
                'April'=>'April','May'=>'Mei','June'=>'Juni',
                'July'=>'Juli','August'=>'Agustus','September'=>'September',
                'October'=>'Oktober','November'=>'November','December'=>'Desember'
            ];
            $tanggal_lahir_formatted = strtr(date_format($date_obj, 'd F Y'), $bulan_indonesia);
        } else {
            $tanggal_lahir_formatted = 'Tanggal tidak valid';
        }
    }

    $email_siswa  = $siswa['email'] ?? '';
    $email_hash   = md5(strtolower(trim($email_siswa)));
    $gravatar_url = "https://www.gravatar.com/avatar/{$email_hash}?s=200&d=mp";

    $foto_base64 = '';
    if ($siswa['url_foto'] && file_exists('../' . $siswa['url_foto'])) {
        $local_path = '../' . $siswa['url_foto'];
        $foto_data  = file_get_contents($local_path);
        $foto_ext   = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
        $mime_map   = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
        $foto_mime  = $mime_map[$foto_ext] ?? 'image/jpeg';
        $foto_base64 = 'data:' . $foto_mime . ';base64,' . base64_encode($foto_data);
    } else {
        $foto_data = @file_get_contents($gravatar_url);
        if ($foto_data) {
            $foto_base64 = 'data:image/jpeg;base64,' . base64_encode($foto_data);
        }
    }

    $foto_src = $foto_base64 ?: $gravatar_url;

    $nama_lengkap   = htmlspecialchars($siswa['nama']);
    $nama_panggilan = htmlspecialchars($siswa['nama_panggilan'] ?? '');
    $nis            = htmlspecialchars($siswa['nis']);
    $kelas_jurusan  = htmlspecialchars($siswa['kelas'] . " " . $siswa['jurusan']);
    $alamat         = htmlspecialchars($siswa['alamat_lengkap']       ?? 'Belum terisi');
    $no_telp        = htmlspecialchars($siswa['no_telp']              ?? 'Belum terisi');
    $email          = htmlspecialchars($siswa['email']                ?? 'Belum terisi');
    $instagram      = htmlspecialchars($siswa['instagram']            ?? 'Belum terisi');
    $agama          = htmlspecialchars($siswa['agama']                ?? 'Belum terisi');
    $hobi           = htmlspecialchars($siswa['hobi_kegemaran']       ?? 'Belum terisi');
    $tentang_saya   = htmlspecialchars($siswa['tentang_saya_singkat'] ?? 'Belum terisi');
    $riwayat_smk    = htmlspecialchars($siswa['riwayat_sma_smk_ma']  ?? 'Belum terisi');
    $riwayat_smp    = htmlspecialchars($siswa['riwayat_smp_mts']     ?? 'Belum terisi');
    $riwayat_sd     = htmlspecialchars($siswa['riwayat_sd_mi']        ?? 'Belum terisi');
    $tipe_kemampuan = htmlspecialchars($hasil_tes_kemampuan_calculated);
    $tempat_lahir   = htmlspecialchars($siswa['tempat_lahir'] ?? 'Belum terisi');
    $tinggi_badan   = htmlspecialchars($siswa['tinggi_badan'] ?? 'Belum terisi');
    $berat_badan    = htmlspecialchars($siswa['berat_badan']  ?? 'Belum terisi');

    $prestasi_rows = '';
    if (!empty($siswa['prestasi_pengalaman'])) {
        foreach (explode("\n", $siswa['prestasi_pengalaman']) as $item) {
            $item = trim($item);
            if ($item === '') continue;
            $prestasi_rows .= '<tr>'
                . '<td style="width:16px;vertical-align:top;font-size:14px;color:#4a90c4;font-weight:bold;padding:4px 4px 4px 0;">&bull;</td>'
                . '<td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;">' . htmlspecialchars($item) . '</td>'
                . '</tr>';
        }
    }

    $organisasi_rows = '';
    if (!empty($siswa['organisasi'])) {
        foreach (explode("\n", $siswa['organisasi']) as $item) {
            $item = trim($item);
            if ($item === '') continue;
            $organisasi_rows .= '<tr>'
                . '<td style="width:16px;vertical-align:top;font-size:14px;color:#4a90c4;font-weight:bold;padding:4px 4px 4px 0;">&bull;</td>'
                . '<td style="font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;padding:4px 0;">' . htmlspecialchars($item) . '</td>'
                . '</tr>';
        }
    }

    $section_prestasi = '';
    if ($prestasi_rows) {
        $section_prestasi = '
        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Prestasi &amp; Pengalaman
            </div>
            <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                ' . $prestasi_rows . '
            </table>
        </div>';
    }

    $section_organisasi = '';
    if ($organisasi_rows) {
        $section_organisasi = '
        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Organisasi
            </div>
            <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                ' . $organisasi_rows . '
            </table>
        </div>';
    }

    $html = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>CV Siswa - ' . $nama_lengkap . '</title>
<style>
@page {
    size: A4;
    margin: 0;
}
* {
    margin: 0;
    padding: 0;
}
body {
    font-family: Arial, sans-serif;
    font-size: 12px;
    color: #2d2d2d;
    background: #ffffff;
    margin: 0;
    padding: 0;
    width: 100%;
}
</style>
</head>
<body>

<table style="width:100%;border-collapse:collapse;table-layout:fixed;background-color:#1a2e4a;">
<tr>
    <td style="width:150px;background-color:#14243c;padding:24px 18px 24px 24px;text-align:center;vertical-align:middle;">
        <img src="' . $foto_src . '" alt="Foto Profil" style="width:108px;height:108px;border:3px solid #4a90c4;display:block;margin:0 auto;">
    </td>
    <td style="padding:22px 24px 22px 20px;vertical-align:middle;background-color:#1a2e4a;">
        <div style="font-size:22px;font-weight:bold;color:#ffffff;margin-bottom:3px;">' . $nama_lengkap . '</div>
        <div style="font-size:11px;color:#90b8d8;margin-bottom:14px;font-style:italic;">' . $kelas_jurusan . '</div>
        <table style="width:100%;border-collapse:collapse;border-top:1px solid #2e4d6e;padding-top:12px;margin-top:0;">
            <tr><td colspan="4" style="height:12px;"></td></tr>
            <tr>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;width:30px;">Telp</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;width:6px;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 16px 3px 0;vertical-align:top;width:45%;">' . $no_telp . '</td>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;width:36px;">Email</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;width:6px;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 0 3px 0;vertical-align:top;">' . $email . '</td>
            </tr>
            <tr>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;">Alamat</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 16px 3px 0;vertical-align:top;">' . $alamat . '</td>
                <td style="font-size:10px;font-weight:bold;color:#7db8d8;white-space:nowrap;padding:3px 4px 3px 0;vertical-align:top;">Instagram</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 6px 3px 0;vertical-align:top;">:</td>
                <td style="font-size:10px;color:#c8dff0;padding:3px 0 3px 0;vertical-align:top;">' . $instagram . '</td>
            </tr>
        </table>
    </td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse;table-layout:fixed;">
<tr>
    <td style="background-color:#4a90c4;height:4px;font-size:0;line-height:0;padding:0;">&nbsp;</td>
</tr>
</table>

<table style="width:100%;border-collapse:collapse;table-layout:fixed;">
<tr>

    <td style="width:36%;vertical-align:top;padding:22px 16px 22px 24px;border-right:1px solid #dde3ea;background-color:#f5f7fa;">

        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Biodata
            </div>
            <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;width:95px;">Nama Panggilan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;width:10px;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $nama_panggilan . '</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tempat Lahir</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $tempat_lahir . '</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tanggal Lahir</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $tanggal_lahir_formatted . '</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Agama</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $agama . '</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tinggi Badan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $tinggi_badan . ' cm</td>
                </tr>
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Berat Badan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $berat_badan . ' kg</td>
                </tr>
                <tr>
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Hobi</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;color:#3d3d3d;padding:4px 0;vertical-align:top;">' . $hobi . '</td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Hasil Tes
            </div>
            <table style="width:100%;border-collapse:collapse;table-layout:fixed;">
                <tr style="border-bottom:1px solid #eaecef;">
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;width:95px;">Gaya Belajar</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;width:10px;text-align:center;">:</td>
                    <td style="font-size:10px;padding:4px 0;vertical-align:top;">
                        <span style="background-color:#e8f2fb;color:#1a5f8a;border:1px solid #b0d0ea;padding:2px 7px;font-size:9px;font-weight:bold;">' . $gaya_belajar . '</span>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;font-weight:bold;color:#1a2e4a;white-space:nowrap;padding:4px 0;vertical-align:top;">Tipe Kemampuan</td>
                    <td style="font-size:10px;color:#9aa3af;padding:4px 0;vertical-align:top;text-align:center;">:</td>
                    <td style="font-size:10px;padding:4px 0;vertical-align:top;">
                        <span style="background-color:#e8f2fb;color:#1a5f8a;border:1px solid #b0d0ea;padding:2px 7px;font-size:9px;font-weight:bold;">' . $tipe_kemampuan . '</span>
                    </td>
                </tr>
            </table>
        </div>

    </td>

    <td style="width:64%;vertical-align:top;padding:22px 24px 22px 20px;background-color:#ffffff;">

        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Tentang Saya
            </div>
            <table style="width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed;">
                <tr>
                    <td style="background-color:#eef5fb;padding:10px 12px;font-size:11px;color:#3d3d3d;line-height:1.6;vertical-align:top;border-left:3px solid #4a90c4;">' . $tentang_saya . '</td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom:20px;">
            <div style="font-size:10px;font-weight:bold;text-transform:uppercase;color:#1a2e4a;border-bottom:2px solid #4a90c4;padding-bottom:5px;margin-bottom:10px;">
                <span style="display:inline-block;width:7px;height:7px;background-color:#4a90c4;margin-right:6px;vertical-align:middle;"></span>Riwayat Pendidikan
            </div>
            <table style="width:100%;border-collapse:collapse;">
    <tr style="border-bottom:1px solid #eaecef;">
        <td style="width:10px;color:#4a90c4;padding:2px 4px 2px 0;">&bull;</td>
        <td style="font-size:11px;color:#3d3d3d;padding:2px 0;">
            <strong>SMK / MA:</strong> ' . $riwayat_smk . '
        </td>
    </tr>

    <tr style="border-bottom:1px solid #eaecef;">
        <td style="width:10px;color:#4a90c4;padding:2px 4px 2px 0;">&bull;</td>
        <td style="font-size:11px;color:#3d3d3d;padding:2px 0;">
            <strong>SMP / MTs:</strong> ' . $riwayat_smp . '
        </td>
    </tr>

    <tr>
        <td style="width:10px;color:#4a90c4;padding:2px 4px 2px 0;">&bull;</td>
        <td style="font-size:11px;color:#3d3d3d;padding:2px 0;">
            <strong>SD / MI:</strong> ' . $riwayat_sd . '
        </td>
    </tr>
</table>
        </div>

        ' . $section_prestasi . '

        ' . $section_organisasi . '

    </td>

</tr>
</table>
</table>

</body>
</html>';

    return $html;
}

while ($s = mysqli_fetch_assoc($query)) {

    $id = $s['id_siswa'];

    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $s['nama']);
    $pdfName  = "{$safeName}_{$s['kelas']}_{$s['jurusan']}.pdf";
    $pdfPath  = $tempDir . $pdfName;

    $html = buildCvHtml($id, $koneksi);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($pdfPath, $dompdf->output());

    if (file_exists($pdfPath)) {
        $zip->addFile($pdfPath, $pdfName);
    }
}

$zip->close();

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=$zipName");
header("Content-Length: " . filesize($zipPath));

readfile($zipPath);

foreach (glob($tempDir . "*.pdf") as $f) unlink($f);
unlink($zipPath);

exit;