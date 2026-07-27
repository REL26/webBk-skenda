<?php
session_start();
include '../koneksi.php';

$config = require __DIR__ . '/config_asesmen_xii.php';
$versi  = 'XII';

$id_siswa = $_SESSION['id_siswa'] ?? null;
if (!$id_siswa) {
    header("Location: ../login.php");
    exit;
}
$id_siswa = (int) $id_siswa;

$kode_tes_esc = mysqli_real_escape_string($koneksi, $config['kode_tes']);
$q_tes = mysqli_query($koneksi, "SELECT id_tes FROM master_tes WHERE kode_tes = '$kode_tes_esc' LIMIT 1");
$row_tes = $q_tes ? mysqli_fetch_assoc($q_tes) : null;
if (!$row_tes) {
    die("Konfigurasi tes '{$config['kode_tes']}' belum terdaftar di tabel master_tes. Jalankan migrasi_asesmen_bk.sql terlebih dahulu.");
}
$id_tes = (int) $row_tes['id_tes'];

$q_ta = mysqli_query($koneksi, "SELECT id_tahun FROM tahun_ajaran WHERE aktif = 1 ORDER BY id_tahun DESC LIMIT 1");
$row_ta = $q_ta ? mysqli_fetch_assoc($q_ta) : null;
$tahun_ajaran_id = $row_ta ? (int) $row_ta['id_tahun'] : null;
if (!$tahun_ajaran_id) {

    die("Tidak ada tahun ajaran yang berstatus aktif di tabel tahun_ajaran. Hubungi admin untuk mengaktifkan tahun ajaran berjalan sebelum Asesmen BK dapat diisi.");
}

$q_cek = mysqli_query($koneksi, "SELECT id_hasil FROM hasil_asesmen WHERE id_siswa = $id_siswa AND versi = '$versi' LIMIT 1");
if ($q_cek && mysqli_num_rows($q_cek) > 0) {
    header("Location: dashboard.php");
    exit;
}

$localStorageKey = 'testAnswers_asesmenXII_siswa' . $id_siswa;

foreach ($config['sections'] as $sec_key => $section) {
    $conditional = $section['conditional'] ?? null;

    if (isset($section['type']) && $section['type'] === 'scale') {
        $steps[] = [
            'kind'        => 'scale',
            'sec_key'     => $sec_key,
            'sec_title'   => $section['title_tampilan'] ?? ('Bagian ' . $sec_key . '. ' . $section['title']),
            'section'     => $section,
            'conditional' => $conditional,
        ];
    } else {
        foreach ($section['fields'] as $field) {
            $steps[] = [
                'kind'        => 'field',
                'sec_key'     => $sec_key,
                'sec_title'   => $section['title_tampilan'] ?? ('Bagian ' . $sec_key . '. ' . $section['title']),
                'field'       => $field,
                'conditional' => $conditional,
            ];
        }
    }
}
$total_steps = count($steps);
$total_cards = $total_steps + 1;

$step_conditions_js = array_map(function ($step) {
    if (!$step['conditional']) return null;
    return [
        'field'  => $step['conditional']['field'],
        'values' => $step['conditional']['equals'],
    ];
}, $steps);

if (isset($_POST['submit'])) {

    $jawaban = [];

    foreach ($config['sections'] as $sec_key => $section) {

        if (!empty($section['conditional'])) {
            $cond_field = $section['conditional']['field'];
            $cond_val   = $jawaban[$cond_field] ?? null;
            if (!in_array($cond_val, $section['conditional']['equals'], true)) {
                continue;
            }
        }

        if (isset($section['type']) && $section['type'] === 'scale') {
            $group = [];
            foreach ($section['items'] as $item) {
                $post_name = 's_' . $sec_key . '_' . $item['key'];
                $val = $_POST[$post_name] ?? null;
                $group[$item['key']] = ($val !== null && $val !== '') ? (int) $val : null;
            }
            $jawaban[$sec_key] = $group;
            continue;
        }

        foreach ($section['fields'] as $field) {
            $key       = $field['key'];
            $type      = $field['type'];
            $post_name = 'f_' . $key;

            if ($type === 'checkbox') {
                $selected = $_POST[$post_name] ?? [];
                if (!is_array($selected)) {
                    $selected = [];
                }
                $selected = array_map('trim', $selected);

                if (!empty($field['other']) && in_array('Lainnya', $selected, true)) {
                    $other_text = trim($_POST[$post_name . '_other'] ?? '');
                    $selected = array_map(function ($v) use ($other_text) {
                        return $v === 'Lainnya' ? ('Lainnya: ' . $other_text) : $v;
                    }, $selected);
                }

                $jawaban[$key] = $selected;
            } else {
                $jawaban[$key] = trim($_POST[$post_name] ?? '');
            }
        }
    }

    $jawaban_json = json_encode($jawaban, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    mysqli_begin_transaction($koneksi);
    $success = true;
    $tanggal = date('Y-m-d H:i:s');

    $q_sesi = "INSERT INTO sesi_tes (id_tes, id_siswa, status, started_at)
               VALUES ($id_tes, $id_siswa, 'finished', '$tanggal')";
    if (!mysqli_query($koneksi, $q_sesi)) {
        $success = false;
    }
    $id_sesi = mysqli_insert_id($koneksi);

    if ($success && $id_sesi) {
        $jawaban_json_esc = mysqli_real_escape_string($koneksi, $jawaban_json);
        $tahun_val = $tahun_ajaran_id ? $tahun_ajaran_id : 'NULL';

        $q_hasil = "INSERT INTO hasil_asesmen (id_sesi, id_siswa, versi, tahun_ajaran_id, jawaban_json, submitted_at)
                    VALUES ($id_sesi, $id_siswa, '$versi', $tahun_val, '$jawaban_json_esc', '$tanggal')";
        if (!mysqli_query($koneksi, $q_hasil)) {
            $success = false;
        }
    } else {
        $success = false;
    }

    if ($success) {
        mysqli_commit($koneksi);
        header("Location: dashboard.php?asesmen_selesai=1");
        exit;
    } else {
        mysqli_rollback($koneksi);
        die("Error: Terjadi kesalahan saat menyimpan jawaban. " . mysqli_error($koneksi));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['judul']); ?> | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #2F6C6E; }
        .question-card { display: none; }
        .question-card.active { display: block; }

        .scrollable-nav-desktop-wrapper { position: relative; }
        .scrollable-nav-desktop {
            max-height: 400px; overflow-y: auto; padding-right: 15px;
            margin-right: -15px; padding-bottom: 5px;
        }
        .scrollable-nav-desktop::-webkit-scrollbar { width: 5px; }
        .scrollable-nav-desktop::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .scrollable-nav-desktop::-webkit-scrollbar-track { background: #f1f5f9; }
        .desktop-scroll-shadow {
            position: absolute; bottom: 0; left: 0; right: 0; height: 30px;
            background: linear-gradient(to top, rgba(255,255,255,1) 50%, rgba(255,255,255,0) 100%);
            pointer-events: none; transition: opacity 0.3s;
        }

        .scrollable-nav-mobile-wrapper { position: relative; margin-bottom: 0.75rem; }
        .scrollable-nav-mobile { overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
        .scroll-shadow-left, .scroll-shadow-right {
            position: absolute; top: 0; height: calc(100% - 5px); width: 30px;
            pointer-events: none; transition: opacity 0.3s; z-index: 10;
        }
        .scroll-shadow-left { left: 0; background: linear-gradient(to right, rgba(255,255,255,1) 50%, rgba(255,255,255,0) 100%); opacity: 0; }
        .scroll-shadow-right { right: 0; background: linear-gradient(to left, rgba(255,255,255,1) 50%, rgba(255,255,255,0) 100%); }

        .nav-button {
            transition: background-color 0.1s, border-color 0.1s, color 0.1s;
            display: inline-flex; flex-shrink: 0; align-items: center; justify-content: center;
            border-radius: 0.5rem; height: 2.25rem; width: 2.25rem; font-size: 0.875rem; font-weight: 500;
        }

        .answer-label {
            display: flex; align-items: flex-start; padding: 0.75rem; border-radius: 0.5rem;
            transition: all 0.15s ease-in-out; border: 1px solid #e5e7eb; background-color: white; cursor: pointer;
        }
        .answer-label:hover { border-color: #60a5fa; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px 0 rgba(0,0,0,0.06); }
        .answer-label.is-checked { border-color: #10b981; background-color: #ecfdf5; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
        .answer-label.is-checked .radio-text { font-weight: 600; color: #059669; }
        .answer-label.is-disabled { opacity: 0.45; cursor: not-allowed; }

        .answer-label .option-code {
            flex-shrink: 0; margin-right: 0.75rem; width: 1.75rem; height: 1.75rem;
            display: flex; align-items: center; justify-content: center;
            background-color: #f3f4f6; color: #4b5563; font-weight: 600;
            border: 2px solid #9ca3af; transition: all 0.15s ease-in-out; margin-top: 0.1rem; font-size: 0.875rem;
        }
        .option-code.rounded-full { border-radius: 50%; }
        .option-code.rounded-md { border-radius: 0.375rem; }
        .answer-label.is-checked .option-code { background-color: #10b981; color: white; border-color: #10b981; }

        .answer-label input[type="radio"], .answer-label input[type="checkbox"] {
            position: absolute; opacity: 0; width: 0; height: 0;
        }
        .answer-label .text-wrapper { flex-grow: 1; padding-top: 0.1rem; padding-bottom: 0.1rem; font-size: 0.875rem; }
        @media (min-width: 640px) { .answer-label .text-wrapper { font-size: 1rem; } }

        .scale-row { display: flex; flex-direction: column; gap: 0.5rem; padding: 0.85rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
        @media (min-width: 640px) { .scale-row { flex-direction: row; align-items: center; justify-content: space-between; } }
        .scale-options { display: flex; gap: 0.4rem; }
        .scale-pill {
            width: 2.25rem; height: 2.25rem; border-radius: 9999px; border: 2px solid #9ca3af;
            display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem;
            color: #4b5563; cursor: pointer; transition: all 0.15s ease-in-out; background: white;
        }
        .scale-pill.is-checked { background-color: #10b981; border-color: #10b981; color: white; }
        .scale-pill input { position: absolute; opacity: 0; width: 0; height: 0; }
    </style>
</head>
<body class="min-h-screen p-3 sm:p-5 lg:p-8 flex flex-col justify-center lg:block">

    <div class="max-w-6xl mx-auto w-full bg-white p-4 sm:p-6 lg:p-8 rounded-xl shadow-2xl">

        <div class="mb-5 sm:mb-6 border-b pb-4 sm:pb-5">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-800"><?= htmlspecialchars($config['judul']); ?></h1>
            <p class="text-sm text-gray-600 font-medium mt-1"><?= htmlspecialchars($config['subjudul']); ?></p>
            <div class="mt-3 p-3 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-xs sm:text-sm rounded-lg">
                Ini bukan tes dengan skor atau nilai. Jawaban Anda hanya dapat dilihat oleh Guru BK, dan tidak akan
                ditampilkan kembali kepada Anda. Setelah dikirim, jawaban tidak bisa diubah.
            </div>
            <div class="mt-2 p-3 bg-indigo-50 border-l-4 border-indigo-400 text-indigo-800 text-xs sm:text-sm rounded-lg">
                Sebagian pertanyaan (Bagian D/E/F) hanya akan muncul sesuai dengan rencana utama yang Anda pilih
                setelah lulus — jumlah total pertanyaan pada peta soal akan otomatis menyesuaikan.
            </div>

            <div class="mt-4 flex items-center space-x-2">
                <span class="text-xs sm:text-sm font-medium text-gray-600">Progres</span>
                <div class="flex-grow bg-gray-200 rounded-full h-2 relative">
                    <div id="progressBar" class="bg-green-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
                <span id="progressText" class="text-xs sm:text-sm font-medium text-gray-700">0 / <?= $total_steps; ?></span>
            </div>
        </div>

        <form method="POST" id="testForm" class="flex flex-col lg:flex-row gap-6">

            <div class="lg:w-1/4 order-1 lg:order-1">
                <div class="bg-white p-4 rounded-xl shadow-md lg:shadow-lg sticky lg:top-4 border border-gray-100">
                    <h2 class="text-lg font-bold mb-3 text-gray-700 hidden lg:block">Peta Soal</h2>

                    <div class="lg:hidden scrollable-nav-mobile-wrapper">
                        <div id="navContainerMobile" class="scrollable-nav-mobile flex gap-2">
                            <?php for ($i = 1; $i <= $total_steps; $i++) { ?>
                                <button type="button" data-q-index="<?= $i - 1; ?>" id="nav-m-<?= $i; ?>"
                                    class="nav-button text-center bg-gray-100 text-gray-600 border border-gray-300"
                                    onclick="goToNav(<?= $i - 1; ?>)"><?= $i; ?></button>
                            <?php } ?>
                            <button type="button" data-q-index="<?= $total_steps; ?>" id="nav-m-<?= $total_cards; ?>"
                                class="nav-button text-center bg-indigo-600 text-white border border-indigo-600 px-3 w-auto"
                                onclick="goToNav(<?= $total_steps; ?>)" style="width: 4.5rem; height: 2.25rem;">Kirim</button>
                        </div>
                        <div class="scroll-shadow-left" id="shadowLeftMobile"></div>
                        <div class="scroll-shadow-right" id="shadowRightMobile"></div>
                    </div>

                    <div id="navContainerDesktopWrapper" class="hidden lg:block scrollable-nav-desktop-wrapper">
                        <div id="navContainerDesktop" class="grid grid-cols-5 gap-2 scrollable-nav-desktop">
                            <?php for ($i = 1; $i <= $total_steps; $i++) { ?>
                                <button type="button" data-q-index="<?= $i - 1; ?>" id="nav-d-<?= $i; ?>"
                                    class="nav-button w-full bg-gray-100 text-gray-600 border border-gray-300"
                                    onclick="goToNav(<?= $i - 1; ?>)"><?= $i; ?></button>
                            <?php } ?>
                            <button type="button" data-q-index="<?= $total_steps; ?>" id="nav-d-<?= $total_cards; ?>"
                                class="nav-button w-full bg-indigo-600 text-white border border-indigo-600 col-span-5 hover:bg-indigo-700"
                                onclick="goToNav(<?= $total_steps; ?>)" style="height: 2.5rem;">Kirim Jawaban</button>
                        </div>
                        <div class="desktop-scroll-shadow" id="shadowBottomDesktop"></div>
                    </div>

                    <a href="dashboard.php" onclick="return confirmExit()" class="w-full mt-6 flex items-center justify-center px-4 py-2 border border-red-500 text-red-500 rounded-lg font-semibold hover:bg-red-50 transition text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H7a3 3 0 01-3-3v-1M4 8V7a3 3 0 013-3h4"></path></svg>
                        Keluar
                    </a>

                    <div class="mt-4 text-xs space-y-1 hidden lg:block">
                        <p class="flex items-center"><span class="h-3 w-3 bg-green-600 rounded-full mr-2"></span>Sudah dijawab</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-gray-800 rounded-full mr-2"></span>Soal aktif</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-gray-300 rounded-full mr-2"></span>Belum dijawab</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-gray-50 border border-gray-200 rounded-full mr-2"></span>Terkunci</p>
                    </div>
                </div>
            </div>

            <div class="lg:w-3/4 order-2 lg:order-2">
                <div id="questionWrapper">
                <?php
                $step_number = 1;
                foreach ($steps as $step) {
                    $idx = $step_number - 1;
                    ?>
                    <div id="q-<?= $idx; ?>"
                         class="question-card bg-white p-5 sm:p-6 rounded-xl shadow-lg border border-gray-100 <?= $idx === 0 ? 'active' : ''; ?>"
                         data-step-type="<?= $step['kind'] === 'scale' ? 'scale' : $step['field']['type']; ?>">

                        <div class="mb-1 text-xs font-semibold text-indigo-600 uppercase tracking-wide">
                            <?= htmlspecialchars($step['sec_title']); ?>
                        </div>

                        <?php if ($step['kind'] === 'scale'):
                            $section = $step['section']; ?>
                            <div class="mb-5 text-lg sm:text-xl font-semibold text-gray-800 flex items-start">
                                <span class="bg-gray-800 text-white h-7 w-7 sm:h-8 sm:w-8 flex items-center justify-center rounded-full mr-3 text-sm sm:text-base flex-shrink-0"><?= $step_number; ?></span>
                                <span class="pt-0.5 sm:pt-0"><?= htmlspecialchars($section['scale_note'] ?? 'Beri nilai 1–5.'); ?></span>
                            </div>
                            <div class="space-y-3">
                                <?php foreach ($section['items'] as $item):
                                    $name = 's_' . $step['sec_key'] . '_' . $item['key']; ?>
                                    <div class="scale-row">
                                        <span class="text-sm sm:text-base text-gray-700 font-medium"><?= htmlspecialchars($item['label']); ?></span>
                                        <div class="scale-options" data-name="<?= $name; ?>">
                                            <?php for ($v = (int)($section['scale_min'] ?? 1); $v <= (int)($section['scale_max'] ?? 5); $v++): ?>
                                                <label class="scale-pill">
                                                    <input type="radio" name="<?= $name; ?>" value="<?= $v; ?>" onchange="handleScaleChange(this)">
                                                    <?= $v; ?>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php else:
                            $field = $step['field'];
                            $key   = $field['key'];
                            $type  = $field['type'];
                            $is_trigger = ($key === 'rencana_utama');
                            $trigger_attr = $is_trigger ? ' data-cond-trigger="1"' : '';
                            ?>
                            <div class="mb-5 text-lg sm:text-xl font-semibold text-gray-800 flex items-start">
                                <span class="bg-gray-800 text-white h-7 w-7 sm:h-8 sm:w-8 flex items-center justify-center rounded-full mr-3 text-sm sm:text-base flex-shrink-0"><?= $step_number; ?></span>
                                <span class="pt-0.5 sm:pt-0"><?= htmlspecialchars($field['label']); ?></span>
                            </div>

                            <?php if ($type === 'text'): ?>
                                <input type="text" name="f_<?= $key; ?>" class="w-full border border-gray-300 rounded-lg p-3 text-sm sm:text-base focus:ring-2 focus:ring-indigo-400 focus:outline-none" oninput="handleGenericInput()">

                            <?php elseif ($type === 'textarea'): ?>
                                <textarea name="f_<?= $key; ?>" rows="5" class="w-full border border-gray-300 rounded-lg p-3 text-sm sm:text-base focus:ring-2 focus:ring-indigo-400 focus:outline-none" oninput="handleGenericInput()"></textarea>

                            <?php elseif ($type === 'radio'): ?>
                                <div class="space-y-3 mt-2"<?= $trigger_attr; ?>>
                                    <?php $letters = range('A', 'Z'); foreach ($field['options'] as $i => $opt): ?>
                                        <label class="answer-label block cursor-pointer">
                                            <input type="radio" name="f_<?= $key; ?>" value="<?= htmlspecialchars($opt); ?>" onchange="handleRadioChange(this)" required>
                                            <span class="option-code rounded-full"><?= $letters[$i]; ?></span>
                                            <span class="font-medium text-gray-700 radio-text text-wrapper"><?= htmlspecialchars($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($type === 'checkbox'):
                                $max = $field['max_select'] ?? null; ?>
                                <?php if ($max): ?>
                                    <p class="text-xs text-gray-500 mb-2">Pilih maksimal <?= (int) $max; ?> jawaban.</p>
                                <?php endif; ?>
                                <div class="space-y-3 mt-2" data-max-select="<?= $max ? (int) $max : ''; ?>">
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <label class="answer-label block cursor-pointer">
                                            <input type="checkbox" name="f_<?= $key; ?>[]" value="<?= htmlspecialchars($opt); ?>" onchange="handleCheckboxChange(this)">
                                            <span class="option-code rounded-md">✓</span>
                                            <span class="font-medium text-gray-700 radio-text text-wrapper"><?= htmlspecialchars($opt); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    <?php if (!empty($field['other'])): ?>
                                        <label class="answer-label block cursor-pointer">
                                            <input type="checkbox" name="f_<?= $key; ?>[]" value="Lainnya" onchange="handleCheckboxChange(this); toggleOtherInput(this)">
                                            <span class="option-code rounded-md">✓</span>
                                            <span class="font-medium text-gray-700 radio-text text-wrapper">Lainnya</span>
                                        </label>
                                        <input type="text" name="f_<?= $key; ?>_other" placeholder="Sebutkan..." class="other-input hidden w-full border border-gray-300 rounded-lg p-2.5 text-sm mt-1 focus:ring-2 focus:ring-indigo-400 focus:outline-none" oninput="handleGenericInput()">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($step['conditional'])): ?>
                        <p class="mt-3 text-[11px] text-indigo-500 italic">
                            Pertanyaan ini hanya berlaku jika jawaban rencana utama Anda sesuai dengan bagian ini.
                        </p>
                        <?php endif; ?>

                        <div class="flex justify-between mt-8">
                            <button type="button" class="prev-btn flex items-center justify-center px-4 py-2 sm:px-5 sm:py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:border-gray-200 disabled:text-gray-400"
                                onclick="goPrev(<?= $idx; ?>)" <?= $idx === 0 ? 'disabled' : ''; ?>>
                                <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                <span class="hidden sm:inline">Sebelumnya</span><span class="sm:hidden">Balik</span>
                            </button>

                            <button type="button" class="next-btn flex items-center justify-center px-4 py-2 sm:px-6 sm:py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition text-sm"
                                onclick="goNext(<?= $idx; ?>)">
                                <span class="hidden sm:inline">Selanjutnya</span><span class="sm:hidden">Lanjut</span>
                                <svg class="w-4 h-4 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                <?php $step_number++; } ?>

                    <div id="q-<?= $total_steps; ?>" class="question-card bg-white p-5 sm:p-6 rounded-xl shadow-lg border border-gray-100">
                        <div class="mb-6 text-xl sm:text-2xl font-bold text-gray-800 flex items-start">
                            <span class="bg-indigo-600 text-white h-7 w-7 sm:h-8 sm:w-8 flex items-center justify-center rounded-full mr-3 text-sm sm:text-base flex-shrink-0">✓</span>
                            <span class="pt-0.5 flex-grow">Langkah Terakhir: Kirim Jawaban</span>
                        </div>
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 mb-3 rounded-lg text-sm sm:text-base" role="alert">
                            <p class="font-bold">Perhatian!</p>
                            <p class="mt-1">Pastikan Anda telah mengisi semua pertanyaan yang berlaku untuk pilihan Anda. Jawaban tidak dapat diubah setelah dikirim.</p>
                        </div>
                        <div class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-800 rounded-lg text-sm sm:text-base">
                            Setelah dikirim, Anda akan langsung kembali ke dashboard. Tidak ada skor atau hasil yang ditampilkan di sini — hanya Guru BK yang dapat melihat jawaban ini.
                        </div>

                        <div class="flex justify-between mt-8">
                            <button type="button" class="prev-btn flex items-center justify-center px-4 py-2 sm:px-5 sm:py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm"
                                onclick="goPrev(<?= $total_steps; ?>)">
                                <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                <span class="hidden sm:inline">Sebelumnya</span><span class="sm:hidden">Balik</span>
                            </button>
                            <button type="submit" name="submit" class="submit-btn flex items-center justify-center px-4 py-2 sm:px-6 sm:py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition text-sm"
                                onclick="return confirmSubmit()">Kirim Jawaban</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<script>
    const totalStepsAll = <?= $total_steps; ?>;
    const totalCards = <?= $total_cards; ?>;
    const stepConditions = <?= json_encode($step_conditions_js, JSON_UNESCAPED_UNICODE); ?>;

    let currentQuestionIndex = 0;
    let maxReachedIndex = 0; 
    const questionCards = document.querySelectorAll('.question-card');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const form = document.getElementById('testForm');
    const navContainerMobile = document.getElementById('navContainerMobile');
    const navContainerDesktop = document.getElementById('navContainerDesktop');
    const localStorageKey = '<?= $localStorageKey; ?>';
    const shadowLeftMobile = document.getElementById('shadowLeftMobile');
    const shadowRightMobile = document.getElementById('shadowRightMobile');
    const shadowBottomDesktop = document.getElementById('shadowBottomDesktop');

    function confirmExit() {
        return confirm("Apakah Anda yakin ingin keluar? Progres Anda akan disimpan sementara di perangkat ini.");
    }

    function getFieldValues(fieldName) {
        const name = 'f_' + fieldName;
        const checked = form.querySelectorAll(
            `input[name="${CSS.escape(name)}"]:checked, input[name="${CSS.escape(name)}[]"]:checked`
        );
        if (checked.length) return Array.from(checked).map(el => el.value);
        const single = form.querySelector(`[name="${CSS.escape(name)}"]`);
        if (single && (single.tagName === 'INPUT' || single.tagName === 'TEXTAREA')) {
            return single.value ? [single.value] : [];
        }
        return [];
    }

    function isStepVisible(idx) {
        const cond = stepConditions[idx];
        if (!cond) return true;
        const selected = getFieldValues(cond.field);
        return selected.some(v => cond.values.includes(v));
    }

    function getVisibleIndices() {
        const arr = [];
        for (let i = 0; i < totalStepsAll; i++) {
            if (isStepVisible(i)) arr.push(i);
        }
        return arr;
    }

    function computeInitialUnlock() {
        const visible = getVisibleIndices();
        let reached = 0;
        for (let pos = 0; pos < visible.length; pos++) {
            reached = visible[pos];
            if (!isStepAnswered(visible[pos])) break;
        }
        maxReachedIndex = reached;
    }

    function goToNav(index) {
        if (index === totalStepsAll) { showQuestion(index); return; }
        if (!isStepVisible(index)) return;      
        if (index > maxReachedIndex) return;    
        showQuestion(index);
    }

    function setNavLocked(btn) {
        btn.classList.remove('bg-gray-800','text-white','border-gray-800','bg-green-600','border-green-600','bg-gray-100','text-gray-600','border-gray-300','hover:bg-green-700','hover:bg-gray-200','bg-indigo-600','border-indigo-600','hover:bg-indigo-700','bg-indigo-800','border-indigo-800');
        btn.classList.add('bg-gray-50','text-gray-300','border-gray-200','cursor-not-allowed');
        btn.title = 'Selesaikan soal sebelumnya terlebih dahulu';
    }

    function goNext(currentIdx) {
        const visible = getVisibleIndices();
        const pos = visible.indexOf(currentIdx);
        let nextIdx;
        if (pos === -1 || pos === visible.length - 1) {
            nextIdx = totalStepsAll; 
        } else {
            nextIdx = visible[pos + 1];
        }
        showQuestion(nextIdx);
    }

    function goPrev(currentIdx) {
        const visible = getVisibleIndices();
        if (currentIdx === totalStepsAll) {
            showQuestion(visible.length ? visible[visible.length - 1] : 0);
            return;
        }
        const pos = visible.indexOf(currentIdx);
        if (pos <= 0) {
            showQuestion(visible.length ? visible[0] : 0);
        } else {
            showQuestion(visible[pos - 1]);
        }
    }

    function saveAllAnswers() {
        const data = {};
        const fd = new FormData(form);
        for (const [name, value] of fd.entries()) {
            if (data[name] === undefined) {
                data[name] = value;
            } else if (Array.isArray(data[name])) {
                data[name].push(value);
            } else {
                data[name] = [data[name], value];
            }
        }
        localStorage.setItem(localStorageKey, JSON.stringify(data));
    }

    function loadAllAnswers() {
        const raw = localStorage.getItem(localStorageKey);
        if (!raw) return;
        let data;
        try { data = JSON.parse(raw); } catch (e) { return; }

        Object.keys(data).forEach(name => {
            const values = Array.isArray(data[name]) ? data[name] : [data[name]];
            const els = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
            els.forEach(el => {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = values.includes(el.value);
                } else {
                    el.value = values[0] ?? '';
                }
            });
        });

        refreshAllVisuals();
    }

    function refreshAllVisuals() {
        form.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(el => {
            if (el.closest('.answer-label')) {
                updatePillVisual(el.closest('.answer-label'), el.checked);
            } else if (el.closest('.scale-pill')) {
                updatePillVisual(el.closest('.scale-pill'), el.checked);
            }
        });
        form.querySelectorAll('input[name$="_other"]').forEach(inp => {
            const checkbox = form.querySelector(`input[type="checkbox"][value="Lainnya"][name="${inp.name.replace('_other', '')}[]"]`);
            if (checkbox && checkbox.checked) inp.classList.remove('hidden');
        });
        applyMaxSelectState();
    }

    function updatePillVisual(el, isChecked) {
        el.classList.toggle('is-checked', isChecked);
    }

    function handleGenericInput() {
        saveAllAnswers();
        updateProgress();
    }

    function handleRadioChange(radio) {
        const card = radio.closest('.question-card');
        card.querySelectorAll('.answer-label').forEach(l => l.classList.remove('is-checked'));
        radio.closest('.answer-label').classList.add('is-checked');
        saveAllAnswers();
        updateProgress();
    }

    function handleCheckboxChange(checkbox) {
        updatePillVisual(checkbox.closest('.answer-label'), checkbox.checked);
        applyMaxSelectState();
        saveAllAnswers();
        updateProgress();
    }

    function handleScaleChange(radio) {
        const group = radio.closest('.scale-options');
        group.querySelectorAll('.scale-pill').forEach(p => p.classList.remove('is-checked'));
        radio.closest('.scale-pill').classList.add('is-checked');
        saveAllAnswers();
        updateProgress();
    }

    function toggleOtherInput(checkbox) {
        const wrapper = checkbox.closest('.space-y-3');
        const otherInput = wrapper.querySelector('.other-input');
        if (!otherInput) return;
        otherInput.classList.toggle('hidden', !checkbox.checked);
        if (!checkbox.checked) otherInput.value = '';
    }

    function applyMaxSelectState() {
        document.querySelectorAll('[data-max-select]').forEach(wrapper => {
            const max = parseInt(wrapper.dataset.maxSelect || '0', 10);
            if (!max) return;
            const boxes = wrapper.querySelectorAll('input[type="checkbox"]');
            const checkedCount = Array.from(boxes).filter(b => b.checked).length;
            boxes.forEach(b => {
                const label = b.closest('.answer-label');
                if (!b.checked && checkedCount >= max) {
                    b.disabled = true;
                    label.classList.add('is-disabled');
                } else {
                    b.disabled = false;
                    label.classList.remove('is-disabled');
                }
            });
        });
    }
    function isStepAnswered(index) {
        const card = questionCards[index];
        const type = card.dataset.stepType;

        if (type === 'text' || type === 'textarea') {
            const el = card.querySelector('input[type="text"], textarea');
            return !!(el && el.value.trim().length > 0);
        }
        if (type === 'radio') {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio) return false;
            return Array.from(form.elements[radio.name]).some(r => r.checked);
        }
        if (type === 'checkbox') {
            return Array.from(card.querySelectorAll('input[type="checkbox"]')).some(b => b.checked);
        }
        if (type === 'scale') {
            const groups = card.querySelectorAll('.scale-options');
            return Array.from(groups).every(g => {
                const name = g.dataset.name;
                return form.elements[name] && Array.from(form.elements[name]).some(r => r.checked);
            });
        }
        return false;
    }

    function getAnsweredCount() {
        return getVisibleIndices().filter(i => isStepAnswered(i)).length;
    }

    function confirmSubmit() {
        const visible = getVisibleIndices();
        const answeredCount = visible.filter(i => isStepAnswered(i)).length;
        if (answeredCount < visible.length) {
            alert(`Anda baru mengisi ${answeredCount} dari ${visible.length} pertanyaan yang berlaku untuk pilihan Anda. Mohon lengkapi terlebih dahulu.`);
            return false;
        }
        if (!confirm("Apakah Anda yakin ingin mengirim jawaban? Jawaban tidak dapat diubah setelah ini.")) {
            return false;
        }
        localStorage.removeItem(localStorageKey);
        return true;
    }

    function getNavButtonsByIndex(index) {
        const qNum = index + 1;
        return {
            mobileBtn: document.getElementById(`nav-m-${qNum}`),
            desktopBtn: document.getElementById(`nav-d-${qNum}`),
        };
    }

    function setNavColor(btn, isActive, isAnswered) {
        btn.classList.remove('bg-gray-800','text-white','border-gray-800','bg-green-600','border-green-600','bg-gray-100','text-gray-600','border-gray-300','hover:bg-green-700','hover:bg-gray-200','bg-indigo-600','border-indigo-600','hover:bg-indigo-700','bg-indigo-800','border-indigo-800');
        const isFinal = parseInt(btn.dataset.qIndex, 10) === totalStepsAll;
        if (isActive) {
            btn.classList.add(isFinal ? 'bg-indigo-800' : 'bg-gray-800', 'text-white', isFinal ? 'border-indigo-800' : 'border-gray-800');
        } else if (isFinal) {
            btn.classList.add('bg-indigo-600','text-white','border-indigo-600','hover:bg-indigo-700');
        } else if (isAnswered) {
            btn.classList.add('bg-green-600','text-white','border-green-600','hover:bg-green-700');
        } else {
            btn.classList.add('bg-gray-100','text-gray-600','border-gray-300','hover:bg-gray-200');
        }
    }

    function updateProgress() {
        const visible = getVisibleIndices();
        const answeredCount = visible.filter(i => isStepAnswered(i)).length;
        const denom = visible.length || 1;
        progressBar.style.width = (answeredCount / denom * 100) + '%';
        progressText.textContent = `${answeredCount} / ${visible.length}`;

        let displayNumber = 1;
        for (let i = 0; i < totalStepsAll; i++) {
            const { mobileBtn, desktopBtn } = getNavButtonsByIndex(i);
            if (!mobileBtn || !desktopBtn) continue;

            const isConditionVisible = visible.includes(i);
            mobileBtn.classList.toggle('hidden', !isConditionVisible);
            desktopBtn.classList.toggle('hidden', !isConditionVisible);
            if (!isConditionVisible) continue;

            mobileBtn.textContent = displayNumber;
            desktopBtn.textContent = displayNumber;
            displayNumber++;

            const locked = i > maxReachedIndex;
            if (locked) {
                setNavLocked(mobileBtn);
                setNavLocked(desktopBtn);
                continue;
            }
            mobileBtn.title = '';
            desktopBtn.title = '';

            const answered = isStepAnswered(i);
            setNavColor(mobileBtn, i === currentQuestionIndex, answered);
            setNavColor(desktopBtn, i === currentQuestionIndex, answered);
        }
        const { mobileBtn: fm, desktopBtn: fd } = getNavButtonsByIndex(totalStepsAll);
        if (fm && fd) {
            setNavColor(fm, currentQuestionIndex === totalStepsAll, visible.length > 0 && answeredCount === visible.length);
            setNavColor(fd, currentQuestionIndex === totalStepsAll, visible.length > 0 && answeredCount === visible.length);
        }
    }

    function scrollToActiveNav(index) {
        if (window.innerWidth < 1024) {
            const activeBtn = document.getElementById(`nav-m-${index + 1}`);
            if (activeBtn) {
                const containerWidth = navContainerMobile.offsetWidth;
                navContainerMobile.scrollLeft = activeBtn.offsetLeft - (containerWidth / 2) + (activeBtn.offsetWidth / 2);
                updateNavScrollShadowMobile();
            }
        } else {
            const activeBtn = document.getElementById(`nav-d-${index + 1}`);
            if (activeBtn) {
                navContainerDesktop.scrollTop = activeBtn.offsetTop - navContainerDesktop.clientHeight / 2 + activeBtn.clientHeight / 2;
                updateNavScrollShadowDesktop();
            }
        }
    }

    function showQuestion(index) {
        if (index < 0 || index >= totalCards) return;
        questionCards.forEach(card => card.classList.remove('active'));
        questionCards[index].classList.add('active');
        currentQuestionIndex = index;
        if (index < totalStepsAll && index > maxReachedIndex) {
            maxReachedIndex = index;
        }
        scrollToActiveNav(index);
        updateProgress();
    }

    function updateNavScrollShadowMobile() {
        if (!navContainerMobile || window.innerWidth >= 1024) return;
        const maxScroll = navContainerMobile.scrollWidth - navContainerMobile.clientWidth;
        const scrollLeft = navContainerMobile.scrollLeft;
        shadowLeftMobile.style.opacity = scrollLeft > 5 ? 1 : 0;
        shadowRightMobile.style.opacity = scrollLeft < maxScroll - 5 ? 1 : 0;
    }

    function updateNavScrollShadowDesktop() {
        if (!navContainerDesktop || window.innerWidth < 1024) return;
        const maxScroll = navContainerDesktop.scrollHeight - navContainerDesktop.clientHeight;
        shadowBottomDesktop.style.opacity = navContainerDesktop.scrollTop < maxScroll - 5 ? 1 : 0;
    }

    if (navContainerMobile) navContainerMobile.addEventListener('scroll', updateNavScrollShadowMobile);
    if (navContainerDesktop) navContainerDesktop.addEventListener('scroll', updateNavScrollShadowDesktop);

    document.addEventListener('DOMContentLoaded', () => {
        loadAllAnswers();
        computeInitialUnlock();
        showQuestion(0);
        updateNavScrollShadowMobile();
        updateNavScrollShadowDesktop();
    });
    window.addEventListener('resize', () => {
        updateNavScrollShadowMobile();
        updateNavScrollShadowDesktop();
    });
</script>
</body>
</html>