<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru']) || !isset($_GET['id_siswa']) || !isset($_GET['versi'])) {
    header("Location: hasil_tes.php");
    exit;
}

$nama_pengguna = isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Guru Bimbingan dan Konseling';

$id_siswa = (int) $_GET['id_siswa'];
$versi    = trim((string) $_GET['versi']);

$versi_valid = ['X', 'XI', 'XII'];

$error_message = null;

if ($id_siswa <= 0) {
    $error_message = 'Parameter id_siswa tidak valid.';
} elseif (!in_array($versi, $versi_valid, true)) {
    $error_message = 'Parameter versi tidak valid. Gunakan salah satu dari: X, XI, XII.';
}

function muat_config_asesmen(string $versi): ?array
{
    $map = [
        'X'   => __DIR__ . '/../siswa/config_asesmen_x.php',
        'XI'  => __DIR__ . '/../siswa/config_asesmen_xi.php',
        'XII' => __DIR__ . '/../siswa/config_asesmen_xii.php',
    ];

    if (!isset($map[$versi]) || !is_file($map[$versi])) {
        return null;
    }

    $config = require $map[$versi];
    return is_array($config) ? $config : null;
}

function decode_jawaban(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);

    if (is_string($data)) {
        $data = json_decode($data, true);
    }

    return is_array($data) ? $data : [];
}

function ambil_jawaban(array $jawaban, string $key)
{
    return $jawaban[$key] ?? null;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_tanggal_id(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y H:i', $ts);
}

function badge_skala_class(int $nilai): string
{
    return match (true) {
        $nilai >= 5 => 'bg-emerald-100 text-emerald-700 border border-emerald-300',
        $nilai === 4 => 'bg-teal-100 text-teal-700 border border-teal-300',
        $nilai === 3 => 'bg-amber-100 text-amber-700 border border-amber-300',
        $nilai === 2 => 'bg-orange-100 text-orange-700 border border-orange-300',
        default => 'bg-red-100 text-red-700 border border-red-300',
    };
}

function normalisasi_checkbox($nilai): array
{
    if (is_array($nilai)) {
        return array_values(array_filter($nilai, fn($v) => is_string($v) && trim($v) !== ''));
    }
    if (is_string($nilai) && trim($nilai) !== '') {
        $parts = preg_split('/\s*[,;]\s*/', trim($nilai));
        return array_values(array_filter($parts, fn($v) => trim($v) !== ''));
    }
    return [];
}

/**
 * Cek apakah sebuah section boleh ditampilkan berdasarkan aturan
 * 'conditional' di config (khusus dipakai Asesmen XII: Bagian D/E/F
 * hanya relevan sesuai pilihan 'rencana_utama' siswa di Bagian C).
 * Section tanpa 'conditional' selalu dianggap relevan.
 */
function section_relevan(array $section, array $jawaban): bool
{
    if (empty($section['conditional'])) {
        return true;
    }

    $cond_field  = $section['conditional']['field'] ?? null;
    $cond_equals = $section['conditional']['equals'] ?? [];

    if (!$cond_field) {
        return true;
    }

    $nilai_siswa = ambil_jawaban($jawaban, $cond_field);

    // Field pemicu bisa berupa radio (string tunggal) atau checkbox (array).
    $nilai_list = is_array($nilai_siswa) ? $nilai_siswa : [$nilai_siswa];

    foreach ($nilai_list as $n) {
        if (in_array($n, $cond_equals, true)) {
            return true;
        }
    }

    return false;
}

function render_jawaban_field(array $field, array $jawaban): string
{
    $key   = $field['key'];
    $type  = $field['type'];
    $nilai = ambil_jawaban($jawaban, $key);

    switch ($type) {
        case 'checkbox':
            $items = normalisasi_checkbox($nilai);
            if (empty($items)) {
                return '<span class="text-gray-400 italic text-base">Tidak ada jawaban</span>';
            }
            $html = '<ul class="list-disc pl-5 space-y-1.5 text-gray-700 text-base">';
            foreach ($items as $opsi) {
                $html .= '<li>' . e($opsi) . '</li>';
            }
            $html .= '</ul>';
            return $html;

        case 'radio':
            if ($nilai === null || trim((string) $nilai) === '') {
                return '<span class="text-gray-400 italic text-base">Tidak ada jawaban</span>';
            }
            return '<span class="inline-block px-4 py-2 rounded-full text-base font-semibold bg-teal-50 text-[#0F3A3A] border border-teal-200">' . e((string) $nilai) . '</span>';

        case 'textarea':
            if ($nilai === null || trim((string) $nilai) === '') {
                return '<span class="text-gray-400 italic text-base">Tidak ada jawaban</span>';
            }
            return '<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-gray-700 text-base whitespace-pre-line leading-relaxed">' . nl2br(e((string) $nilai)) . '</div>';

        case 'text':
        default:
            if ($nilai === null || trim((string) $nilai) === '') {
                return '<span class="text-gray-400 italic text-base">Tidak ada jawaban</span>';
            }
            return '<p class="text-gray-700 text-base">' . e((string) $nilai) . '</p>';
    }
}

function render_tabel_skala(array $items, array $jawaban_section): string
{
    $rows = '';
    foreach ($items as $item) {
        $key   = $item['key'];
        $label = $item['label'];
        $raw   = $jawaban_section[$key] ?? null;

        if ($raw === null || $raw === '') {
            $rows .= '<tr class="border-b border-gray-100">
                <td class="py-3 px-4 text-gray-700 text-base">' . e($label) . '</td>
                <td class="py-3 px-4 text-right"><span class="text-gray-400 italic text-base">-</span></td>
            </tr>';
            continue;
        }

        $nilai = (int) $raw;
        $rows .= '<tr class="border-b border-gray-100 hover:bg-gray-50">
            <td class="py-3 px-4 text-gray-700 text-base">' . e($label) . '</td>
            <td class="py-3 px-4 text-right">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-base font-bold ' . badge_skala_class($nilai) . '">' . $nilai . '</span>
            </td>
        </tr>';
    }

    return '
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-base">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 font-semibold text-gray-600 text-sm uppercase tracking-wide">Pernyataan</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-600 text-sm uppercase tracking-wide w-28">Nilai</th>
                </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
        </table>
    </div>';
}

function render_isi_section(array $section, array $jawaban, string $sec_key): string
{
    if (($section['type'] ?? '') === 'scale' && !empty($section['items'])) {
        $note = '';
        if (!empty($section['scale_note'])) {
            $note = '<p class="text-sm text-gray-500 mb-4 flex items-center gap-1.5"><i class="fas fa-circle-info"></i>' . e($section['scale_note']) . '</p>';
        }
        // Jawaban section tipe 'scale' disimpan NESTED oleh asesmen_x/xi/xii.php:
        // $jawaban[$sec_key] = ['item_key' => nilai, ...] -- bukan flat di root.
        $jawaban_section = $jawaban[$sec_key] ?? [];
        if (!is_array($jawaban_section)) {
            $jawaban_section = [];
        }
        return $note . render_tabel_skala($section['items'], $jawaban_section);
    }

    if (empty($section['fields'])) {
        return '<p class="text-gray-400 italic text-base">Tidak ada data.</p>';
    }

    $html = '<div class="divide-y divide-gray-100">';
    foreach ($section['fields'] as $field) {
        $html .= '<div class="py-4 first:pt-0 last:pb-0">
            <div class="font-semibold text-[#0F3A3A] text-base mb-2.5 flex items-start gap-2">
                <i class="fas fa-caret-right mt-1 text-[#5FA8A1]"></i>
                <span>' . e($field['label']) . '</span>
            </div>
            <div class="pl-6">' . render_jawaban_field($field, $jawaban) . '</div>
        </div>';
    }
    $html .= '</div>';

    return $html;
}

function ikon_section(string $kode): string
{
    $icons = [
        'A' => 'fa-id-card', 'B' => 'fa-graduation-cap', 'C' => 'fa-briefcase',
        'D' => 'fa-star', 'E' => 'fa-compass', 'F' => 'fa-people-group',
        'G' => 'fa-life-ring', 'H' => 'fa-book-open', 'I' => 'fa-heart',
        'J' => 'fa-flag',
    ];
    return $icons[$kode] ?? 'fa-folder-open';
}

$config = null;
if ($error_message === null) {
    $config = muat_config_asesmen($versi);
    if ($config === null) {
        $error_message = 'File konfigurasi untuk versi asesmen "' . e($versi) . '" tidak ditemukan.';
    }
}

$siswa   = null;
$hasil   = null;
$jawaban = [];

if ($error_message === null) {

    $q_siswa = "
        SELECT s.id_siswa, s.nis, s.nisn, s.nama, s.kelas, s.jurusan, s.url_foto, t.tahun AS tahun_ajaran
        FROM siswa s
        LEFT JOIN tahun_ajaran t ON s.tahun_ajaran_id = t.id_tahun
        WHERE s.id_siswa = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($koneksi, $q_siswa);
    mysqli_stmt_bind_param($stmt, "i", $id_siswa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $siswa  = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$siswa) {
        $error_message = 'Data siswa dengan ID tersebut tidak ditemukan.';
    } else {
        $q_hasil = "
            SELECT id_hasil, id_sesi, versi, jawaban_json, submitted_at, created_at
            FROM hasil_asesmen
            WHERE id_siswa = ? AND versi = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($koneksi, $q_hasil);
        mysqli_stmt_bind_param($stmt, "is", $id_siswa, $versi);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $hasil  = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$hasil) {
            $error_message = 'Siswa ini belum memiliki hasil Asesmen BK untuk versi "' . e($versi) . '".';
        } else {
            $jawaban = decode_jawaban($hasil['jawaban_json']);
            if (empty($jawaban)) {
                $error_message = 'Data jawaban asesmen ditemukan namun tidak dapat dibaca (format JSON tidak valid atau kosong). Hubungi admin untuk memeriksa data pada kolom jawaban_json.';
            }
        }
    }
}

$judul_asesmen    = $config['judul'] ?? 'Asesmen BK';
$subjudul_asesmen = $config['subjudul'] ?? '';
$sections_semua   = $config['sections'] ?? [];

// Hanya render section yang relevan (Bagian D/E/F Asesmen XII disaring
// sesuai pilihan 'rencana_utama' siswa; section lain selalu tampil).
$sections = [];
if ($error_message === null) {
    foreach ($sections_semua as $kode => $section) {
        if (section_relevan($section, $jawaban)) {
            $sections[$kode] = $section;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Hasil Asesmen<?= $siswa ? ' - ' . e($siswa['nama']) : '' ?></title>
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    * { font-family: 'Inter', sans-serif; }
    :root {
        --primary: #0F3A3A;
        --primary-light: #123E44;
        --accent: #5FA8A1;
    }
    body { background-color: #F9FAFB; }
    .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
    .accordion-content.open { max-height: 6000px; }
    .accordion-chevron { transition: transform 0.3s ease; }
    .accordion-chevron.open { transform: rotate(180deg); }
    @media print {
        .no-print { display: none !important; }
        .accordion-content { max-height: none !important; }
        body { background: #fff; }
    }
</style>
</head>
<body class="min-h-screen">

<div class="w-full max-w-[1600px] mx-auto px-6 lg:px-10 py-8">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-8 no-print">
        <h1 class="text-2xl font-black text-[#0F3A3A] flex items-center gap-3">
            <i class="fas fa-clipboard-check text-[#5FA8A1]"></i>
            Detail Hasil Asesmen BK
        </h1>
        <div class="flex items-center gap-3">
            <a href="hasil_tes.php"
               class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold text-base hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <?php if ($error_message === null): ?>
            <a href="cetak_hasil_asesmen.php?id_siswa=<?= $id_siswa ?>&versi=<?= e($versi) ?>"
               id="btnCetakAsesmen" target="_blank" rel="noopener"
               class="px-5 py-2.5 bg-[#0F3A3A] text-white rounded-lg font-semibold text-base hover:bg-[#123E44] transition flex items-center gap-2">
                <i class="fas fa-print"></i> Cetak
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error_message !== null): ?>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-14 text-center">
            <div class="w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-5 text-3xl">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-3">Data Tidak Dapat Ditampilkan</h3>
            <p class="text-base text-gray-500 max-w-xl mx-auto"><?= e($error_message) ?></p>
        </div>

    <?php else: ?>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#0F3A3A] to-[#123E44] p-6 flex items-center gap-5">
                <?php if (!empty($siswa['url_foto'])): ?>
                    <img src="../<?= e($siswa['url_foto']) ?>" alt="Foto Siswa"
                         class="w-24 h-24 rounded-full object-cover border-4 border-white/90 shadow-md flex-shrink-0">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full border-4 border-white/40 bg-white/10 flex items-center justify-center text-white text-4xl flex-shrink-0">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <h2 class="text-white font-black text-2xl truncate"><?= e($siswa['nama']) ?></h2>
                    <p class="text-teal-100 text-base mt-1">NIS <?= e($siswa['nis']) ?> &middot; Kelas <?= e($siswa['kelas']) ?> - <?= e($siswa['jurusan']) ?></p>
                </div>
                <span class="hidden sm:inline-flex px-4 py-2 bg-white/15 text-white rounded-full text-base font-bold border border-white/30">
                    Versi <?= e($versi) ?>
                </span>
            </div>

            <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">NIS</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e($siswa['nis']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Kelas</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e($siswa['kelas']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Jurusan</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e($siswa['jurusan']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Tahun Ajaran</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e($siswa['tahun_ajaran'] ?? '-') ?></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Versi Asesmen</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e($versi) ?></p>
                </div>
                <div class="col-span-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Tanggal Pengisian</p>
                    <p class="text-base font-semibold text-gray-800 mt-0.5"><?= e(format_tanggal_id($hasil['submitted_at'] ?? $hasil['created_at'])) ?></p>
                </div>
            </div>

            <?php if ($subjudul_asesmen): ?>
            <div class="px-6 pb-6">
                <p class="text-sm text-gray-500 italic border-t border-gray-100 pt-4"><?= e($subjudul_asesmen) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php
            $rencana_utama_val = ambil_jawaban($jawaban, 'rencana_utama');
            $ada_yang_disaring = $versi === 'XII' && $rencana_utama_val && count($sections) < count($sections_semua);
        ?>
        <?php if ($ada_yang_disaring): ?>
        <div class="mb-6 flex items-start gap-3 bg-teal-50 border border-teal-200 text-[#0F3A3A] rounded-xl px-5 py-4">
            <i class="fas fa-filter mt-1 text-[#5FA8A1]"></i>
            <p class="text-base">
                Rencana utama siswa: <span class="font-bold"><?= e((string) $rencana_utama_val) ?></span>.
                Bagian yang ditampilkan di bawah hanya bagian yang relevan dengan pilihan tersebut.
            </p>
        </div>
        <?php endif; ?>

        <?php if (empty($sections)): ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-gray-400 text-base">
                Tidak ada data kategori pada konfigurasi asesmen ini.
            </div>
        <?php else: ?>
            <div class="space-y-4" id="accordionAsesmen">
                <?php $no = 0; foreach ($sections as $kode => $section): $no++; ?>
                    <?php $panelId = 'panel-' . e((string) $kode); ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <button type="button" onclick="toggleAccordion('<?= $panelId ?>')"
                                class="w-full flex items-center gap-4 p-5 text-left hover:bg-gray-50 transition">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#0F3A3A] to-[#5FA8A1] flex items-center justify-center text-white text-lg flex-shrink-0">
                                <i class="fas <?= ikon_section((string) $kode) ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-800 text-lg truncate">
                                    <?= e((string) $kode) ?>. <?= e($section['title'] ?? '') ?>
                                </p>
                            </div>
                            <i id="<?= $panelId ?>-chevron" class="fas fa-chevron-down text-gray-400 text-lg accordion-chevron <?= $no === 1 ? 'open' : '' ?>"></i>
                        </button>
                        <div id="<?= $panelId ?>" class="accordion-content <?= $no === 1 ? 'open' : '' ?>">
                            <div class="px-5 pb-6 pt-2 border-t border-gray-100">
                                <?= render_isi_section($section, $jawaban, (string) $kode) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<script>
function toggleAccordion(id) {
    const panel = document.getElementById(id);
    const chevron = document.getElementById(id + '-chevron');
    panel.classList.toggle('open');
    chevron.classList.toggle('open');
}
</script>

</body>
</html>