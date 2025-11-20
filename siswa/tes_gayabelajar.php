<?php
session_start();
include '../koneksi.php'; 

$id_tes_gayabelajar = 2; 
$id_siswa = $_SESSION['id_siswa'] ?? null;

if (!$id_siswa) {
    header("Location: ../login.php");
    exit;
}

$localStorageKey = 'testAnswers_gayaBelajar_siswa'.$id_siswa;

if (isset($_POST['submit'])) {
    
    $tanggal_tes = date('Y-m-d H:i:s');
    $skor_vak = ['V' => 0, 'A' => 0, 'K' => 0];

    mysqli_begin_transaction($koneksi);
    $success = true;

    $q_sesi = "INSERT INTO sesi_tes (id_tes, id_siswa, status, started_at) 
                 VALUES ('$id_tes_gayabelajar', '$id_siswa', 'finished', '$tanggal_tes')";

    if (!mysqli_query($koneksi, $q_sesi)) {
        $success = false;
    }
    $id_sesi = mysqli_insert_id($koneksi); 

    if ($id_sesi === 0) {
        mysqli_rollback($koneksi);
        die("Error: Gagal membuat sesi tes baru. " . mysqli_error($koneksi));
    }
    
    foreach ($_POST as $key => $jawaban_kode) {
        if (strpos($key, 'soal_') === 0) {
            $id_soal = (int)str_replace('soal_', '', $key);
            $jawaban_kode = mysqli_real_escape_string($koneksi, $jawaban_kode);

            $q_jawaban = "INSERT INTO jawaban_gayabelajar (id_sesi, id_soal, jawaban) 
                          VALUES ($id_sesi, $id_soal, '$jawaban_kode')";
            
            if (!mysqli_query($koneksi, $q_jawaban)) {
                $success = false;
                break;
            }

            if (isset($skor_vak[$jawaban_kode])) {
                $skor_vak[$jawaban_kode] += 1;
            }
        }
    }
    
    if ($success) {
        $skor_v = $skor_vak['V'];
        $skor_a = $skor_vak['A'];
        $skor_k = $skor_vak['K'];

        $q_hasil = "INSERT INTO hasil_gayabelajar (id_sesi, id_siswa, tanggal_tes, skor_visual, skor_auditori, skor_kinestetik) 
                    VALUES ($id_sesi, $id_siswa, '$tanggal_tes', $skor_v, $skor_a, $skor_k)";

        if (!mysqli_query($koneksi, $q_hasil)) {
            $success = false;
        }
        $id_hasil = mysqli_insert_id($koneksi); 
    }

    if ($success) {
        mysqli_commit($koneksi);
        
        header("Location: hasil_gayabelajar.php?id_hasil=$id_hasil&cleanup=true");
        exit;
    } else {
        mysqli_rollback($koneksi);
        die("Error: Terjadi kesalahan saat menyimpan jawaban atau hasil. " . mysqli_error($koneksi));
    }
}

$query_soal = mysqli_query($koneksi, "
    SELECT 
        sgb.id_soal, sgb.nomor, sgb.pernyataan,
        sogb.kode_opsi, sogb.teks_opsi 
    FROM soal_gaya_belajar sgb
    JOIN soal_opsi_gaya_belajar sogb ON sgb.id_soal = sogb.id_soal
    WHERE sgb.nomor BETWEEN 1 AND 30
    ORDER BY sgb.nomor ASC, sogb.kode_opsi ASC
");

$soal_lengkap = [];
$current_id_soal = null;

while ($row = mysqli_fetch_assoc($query_soal)) {
    if ($row['id_soal'] !== $current_id_soal) {
        $current_id_soal = $row['id_soal'];
        $soal_lengkap[$current_id_soal] = [
            'id_soal' => $row['id_soal'],
            'nomor' => $row['nomor'],
            'pernyataan' => $row['pernyataan'],
            'opsi' => []
        ];
    }
    
    $map_kode = [
        'V' => ['label' => 'A', 'value' => 'V'], 
        'A' => ['label' => 'B', 'value' => 'A'], 
        'K' => ['label' => 'C', 'value' => 'K']
    ];

    $kode_opsi_db = strtoupper($row['kode_opsi']);

    if (isset($map_kode[$kode_opsi_db])) {
        $soal_lengkap[$current_id_soal]['opsi'][$map_kode[$kode_opsi_db]['label']] = [
            'teks' => $row['teks_opsi'],
            'value' => $map_kode[$kode_opsi_db]['value'] 
        ];
    }
}

foreach ($soal_lengkap as $id => &$soal) {
    if (!empty($soal['opsi'])) {
        ksort($soal['opsi']);
    }
}
$all_soal = array_values($soal_lengkap); 
$total_soal = count($all_soal);

$total_cards = $total_soal + 1;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes Gaya Belajar | BK SMKN 2 Banjarmasin</title>
    <link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #2F6C6E; 
        }
        .question-card {
            display: none;
        }
        .question-card.active {
            display: block;
        }
        
        .scrollable-nav-desktop-wrapper {
            position: relative;
        }
        .scrollable-nav-desktop {
            max-height: 400px; 
            overflow-y: auto;
            padding-right: 15px;
            margin-right: -15px;
            padding-bottom: 5px; 
        }
        .scrollable-nav-desktop::-webkit-scrollbar {
            width: 5px; 
        }
        .scrollable-nav-desktop::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .scrollable-nav-desktop::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .desktop-scroll-shadow {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to top, rgba(255, 255, 255, 1) 50%, rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
            transition: opacity 0.3s;
        }
        
        .scrollable-nav-mobile-wrapper {
            position: relative;
            margin-bottom: 0.75rem; 
        }
        .scrollable-nav-mobile {
            overflow-x: auto;
            white-space: nowrap; 
            padding-bottom: 5px; 
        }
        .scroll-shadow-left, .scroll-shadow-right {
            position: absolute;
            top: 0;
            height: calc(100% - 5px); 
            width: 30px;
            pointer-events: none;
            transition: opacity 0.3s;
            z-index: 10;
        }
        .scroll-shadow-left {
            left: 0;
            background: linear-gradient(to right, rgba(255, 255, 255, 1) 50%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
        }
        .scroll-shadow-right {
            right: 0;
            background: linear-gradient(to left, rgba(255, 255, 255, 1) 50%, rgba(255, 255, 255, 0) 100%);
        }

        .nav-button {
            transition: background-color 0.1s, border-color 0.1s, color 0.1s;
            display: inline-flex; 
            flex-shrink: 0; 
            align-items: center; 
            justify-content: center; 
            border-radius: 0.5rem; 
            height: 2.25rem; 
            width: 2.25rem; 
            font-size: 0.875rem; 
            font-weight: 500;
        }
        
        .answer-label {
             display: flex; 
             align-items: flex-start; 
             padding: 0.75rem; 
             border-radius: 0.5rem; 
             transition: all 0.15s ease-in-out;
             border: 1px solid #e5e7eb; 
             background-color: white;
             cursor: pointer;
        }
        .answer-label:hover {
            border-color: #60a5fa; 
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .answer-label.is-checked {
            border-color: #10b981; 
            background-color: #ecfdf5; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .answer-label.is-checked .radio-text {
            font-weight: 600;
            color: #059669; 
        }
        
        .answer-label .option-code {
            flex-shrink: 0; 
            margin-right: 0.75rem; 
            
            width: 1.75rem; 
            height: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f3f4f6;
            color: #4b5563;
            font-weight: 600;
            border: 2px solid #9ca3af;
            transition: all 0.15s ease-in-out;
            margin-top: 0.1rem; 
            font-size: 0.875rem;
        }
        .answer-label.is-checked .option-code {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }

        .answer-label input[type="radio"] {
            position: absolute; 
            opacity: 0; 
            width: 0; 
            height: 0;
        }
        .answer-label .text-wrapper {
            flex-grow: 1; 
            padding-top: 0.1rem; 
            padding-bottom: 0.1rem;
            font-size: 0.875rem; 
        }
        
        @media (min-width: 640px) {
            .answer-label .text-wrapper {
                font-size: 1rem; 
            }
        }
    </style>
</head>
<body class="min-h-screen p-3 sm:p-5 lg:p-8">

    <div class="max-w-6xl mx-auto bg-white p-4 sm:p-6 lg:p-8 rounded-xl shadow-2xl">

        <div class="mb-5 sm:mb-6 border-b pb-4 sm:pb-5">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-800">Tes Gaya Belajar</h1>
            <p class="text-sm text-gray-600 font-medium mt-1">Pilih satu opsi (A, B, atau C) yang paling sesuai dengan diri Anda untuk setiap pernyataan.</p>
            
            <div class="mt-4 flex items-center space-x-2">
                <span class="text-xs sm:text-sm font-medium text-gray-600">Progres</span>
                <div class="flex-grow bg-gray-200 rounded-full h-2 relative">
                    <div id="progressBar" class="bg-green-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
                <span id="progressText" class="text-xs sm:text-sm font-medium text-gray-700">0 / <?= $total_soal; ?></span>
            </div>
        </div>

        <form method="POST" id="testForm" class="flex flex-col lg:flex-row gap-6">

            <div class="lg:w-1/4 order-1 lg:order-1">
                <div class="bg-white p-4 rounded-xl shadow-md lg:shadow-lg sticky lg:top-4 border border-gray-100">
                    <h2 class="text-lg font-bold mb-3 text-gray-700 hidden lg:block">Peta Soal</h2>
                    
                    <div class="lg:hidden scrollable-nav-mobile-wrapper">
                        <div id="navContainerMobile" class="scrollable-nav-mobile flex gap-2">
                            <?php 
                            for ($i = 1; $i <= $total_soal; $i++) { ?>
                                <button 
                                    type="button" 
                                    data-question-id="<?= $all_soal[$i-1]['id_soal']; ?>" 
                                    data-q-index="<?= $i - 1; ?>"
                                    id="nav-m-<?= $i; ?>"
                                    class="nav-button text-center 
                                         bg-gray-100 text-gray-600 border border-gray-300"
                                    onclick="showQuestion(<?= $i - 1; ?>)">
                                    <?= $i; ?>
                                </button>
                            <?php } ?>
                            
                            <button 
                                type="button" 
                                data-question-id="final" 
                                data-q-index="<?= $total_soal; ?>"
                                id="nav-m-<?= $total_cards; ?>"
                                class="nav-button text-center 
                                     bg-indigo-600 text-white border border-indigo-600 px-3 w-auto"
                                onclick="showQuestion(<?= $total_soal; ?>)"
                                style="width: 4.5rem; height: 2.25rem;">
                                Kirim
                            </button>
                        </div>
                        <div class="scroll-shadow-left" id="shadowLeftMobile"></div>
                        <div class="scroll-shadow-right" id="shadowRightMobile"></div>
                    </div>

                    <div id="navContainerDesktopWrapper" class="hidden lg:block scrollable-nav-desktop-wrapper">
                        <div id="navContainerDesktop" class="grid grid-cols-5 gap-2 scrollable-nav-desktop">
                            <?php 
                            for ($i = 1; $i <= $total_soal; $i++) { ?>
                                <button 
                                    type="button" 
                                    data-question-id="<?= $all_soal[$i-1]['id_soal']; ?>" 
                                    data-q-index="<?= $i - 1; ?>"
                                    id="nav-d-<?= $i; ?>"
                                    class="nav-button w-full 
                                         bg-gray-100 text-gray-600 border border-gray-300"
                                    onclick="showQuestion(<?= $i - 1; ?>)">
                                    <?= $i; ?>
                                </button>
                            <?php } ?>
                            
                            <button 
                                type="button" 
                                data-question-id="final" 
                                data-q-index="<?= $total_soal; ?>"
                                id="nav-d-<?= $total_cards; ?>"
                                class="nav-button w-full 
                                     bg-indigo-600 text-white border border-indigo-600 col-span-5 hover:bg-indigo-700"
                                onclick="showQuestion(<?= $total_soal; ?>)"
                                style="height: 2.5rem;">
                                Kirim Jawaban
                            </button>
                        </div>
                        <div class="desktop-scroll-shadow" id="shadowBottomDesktop"></div>
                    </div>
                    
                    <a href="dashboard.php" onclick="return confirmExit()" class="w-full mt-6 flex items-center justify-center px-4 py-2 border border-red-500 text-red-500 rounded-lg font-semibold hover:bg-red-50 transition text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H7a3 3 0 01-3-3v-1M4 8V7a3 3 0 013-3h4"></path></svg>
                        Keluar Tes
                    </a>

                    <div class="mt-4 text-xs space-y-1 hidden lg:block"> 
                        <p class="flex items-center"><span class="h-3 w-3 bg-green-600 rounded-full mr-2"></span>Sudah dijawab</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-gray-800 rounded-full mr-2"></span>Soal aktif</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-gray-300 rounded-full mr-2"></span>Belum dijawab</p>
                        <p class="flex items-center"><span class="h-3 w-3 bg-indigo-600 rounded-full mr-2"></span>Halaman Kirim</p>
                    </div>
                </div>
            </div>

            <div class="lg:w-3/4 order-2 lg:order-2">
                <div id="questionWrapper">
                    <?php $question_number = 1; 
                    foreach ($all_soal as $soal_item) { ?>
                        <div id="q-<?= $question_number - 1; ?>" class="question-card bg-white p-5 sm:p-6 rounded-xl shadow-lg border border-gray-100 <?= $question_number == 1 ? 'active' : ''; ?>">
                            <div class="mb-5 text-lg sm:text-xl font-semibold text-gray-800 flex items-start">
                                <span class="bg-gray-800 text-white h-7 w-7 sm:h-8 sm:w-8 flex items-center justify-center rounded-full mr-3 text-sm sm:text-base flex-shrink-0"><?= $question_number; ?></span>
                                <span class="pt-0.5 sm:pt-0"><?= htmlspecialchars($soal_item['pernyataan']); ?></span>
                            </div>
                            
                            <div class="space-y-3 mt-6">
                                <?php
                                $options = $soal_item['opsi'];
                                $labels = array_keys($options);
                                ?>

                                <?php foreach ($labels as $label) {
                                    $option_data = $options[$label];
                                ?>
                                    <label class="answer-label block cursor-pointer">
                                        <input 
                                            type="radio" 
                                            name="soal_<?= $soal_item['id_soal']; ?>" 
                                            value="<?= $option_data['value']; ?>" 
                                            onchange="handleRadioChange(this);"
                                            required>
                                        <span class="option-code"><?= $label; ?></span>
                                        <span class="font-medium text-gray-700 radio-text text-wrapper">
                                            <?= htmlspecialchars($option_data['teks']); ?>
                                        </span>
                                    </label>
                                <?php } ?>
                            </div>

                            <div class="flex justify-between mt-8">
                                <button 
                                    type="button" 
                                    id="prev-btn-<?= $question_number - 1; ?>" 
                                    class="prev-btn flex items-center justify-center px-4 py-2 sm:px-5 sm:py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:border-gray-200 disabled:text-gray-400"
                                    onclick="showQuestion(<?= $question_number - 2; ?>)">
                                    <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                    <span class="hidden sm:inline">Sebelumnya</span>
                                    <span class="sm:hidden">Balik</span>
                                </button>
                                
                                <button 
                                    type="button" 
                                    id="next-btn-<?= $question_number - 1; ?>" 
                                    class="next-btn flex items-center justify-center px-4 py-2 sm:px-6 sm:py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition text-sm"
                                    onclick="showQuestion(<?= $question_number; ?>)">
                                    <span class="hidden sm:inline">Selanjutnya</span>
                                    <span class="sm:hidden">Lanjut</span>
                                    <svg class="w-4 h-4 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                                
                                <button 
                                    type="submit" 
                                    name="submit" 
                                    id="submit-soal-<?= $question_number - 1; ?>"
                                    class="submit-btn flex items-center justify-center px-4 py-2 sm:px-6 sm:py-2.5 bg-gray-800 text-white rounded-lg font-bold hover:bg-gray-900 transition text-sm"
                                    style="display:none;">
                                    Kirim Angket & Hasil
                                </button>
                            </div>
                        </div>
                    <?php $question_number++; } ?>
                    
                    <div id="q-<?= $total_soal; ?>" class="question-card bg-white p-5 sm:p-6 rounded-xl shadow-lg border border-gray-100">
                        <div class="mb-6 text-xl sm:text-2xl font-bold text-gray-800 flex items-start">
                            <span class="bg-indigo-600 text-white h-7 w-7 sm:h-8 sm:w-8 flex items-center justify-center rounded-full mr-3 text-sm sm:text-base flex-shrink-0">✓</span>
                            <span class="pt-0.5 flex-grow">Langkah Terakhir: Kirim Jawaban</span>
                        </div>
                        
                        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 mb-6 rounded-lg text-sm sm:text-base" role="alert">
                            <p class="font-bold">Perhatian!</p>
                            <p class="mt-1">Pastikan Anda telah menjawab semua <?= $total_soal; ?> soal. Jika ada soal yang belum terjawab, Anda akan diberikan peringatan saat mencoba mengirim.</p>
                        </div>
                        
                        <div class="flex justify-between mt-8">
                            <button 
                                type="button" 
                                id="prev-btn-<?= $total_soal; ?>" 
                                class="prev-btn flex items-center justify-center px-4 py-2 sm:px-5 sm:py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition text-sm"
                                onclick="showQuestion(<?= $total_soal - 1; ?>)">
                                <svg class="w-4 h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                <span class="hidden sm:inline">Sebelumnya</span>
                                <span class="sm:hidden">Balik</span>
                            </button>
                            
                            <button 
                                type="submit" 
                                name="submit" 
                                class="submit-btn flex items-center justify-center px-4 py-2 sm:px-6 sm:py-2.5 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700 transition text-sm"
                                onclick="return confirmSubmit()">
                                Kirim Angket & Hasil
                            </button>
                        </div>
                    </div>
                    </div>
            </div>

        </form>
    </div>

<script>
    const totalQuestions = <?= $total_soal; ?>;
    const totalCards = <?= $total_cards; ?>;
    let currentQuestionIndex = 0; 
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
        const confirmed = confirm("Apakah Anda yakin ingin mengakhiri tes? Progres Anda akan disimpan sementara. Tekan OK untuk menyimpan dan keluar, atau Batalkan untuk melanjutkan.");
        return confirmed;
    }

    function getAnsweredCount() {
        let answeredCount = 0;
        for (let i = 0; i < totalQuestions; i++) {
            const card = questionCards[i];
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                const radioGroupName = radio.name;
                if (form.elements[radioGroupName] && 
                    Array.from(form.elements[radioGroupName]).some(r => r.checked)) {
                    answeredCount++;
                }
            }
        }
        return answeredCount;
    }

    function confirmSubmit() {
        const answeredCount = getAnsweredCount();
        if (answeredCount < totalQuestions) {
            alert(`Anda baru menjawab ${answeredCount} dari ${totalQuestions} soal. Mohon kembali ke soal yang belum dijawab sebelum mengirim!`);
            return false;
        }
        return confirm("Apakah Anda yakin ingin mengirim semua jawaban dan mengakhiri tes?");
    }

    function getNavButtonsByIndex(index) {
        const qNum = index + 1;
        const mobileBtn = document.getElementById(`nav-m-${qNum}`);
        const desktopBtn = document.getElementById(`nav-d-${qNum}`);
        return { mobileBtn, desktopBtn };
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
        const scrollTop = navContainerDesktop.scrollTop;
        shadowBottomDesktop.style.opacity = scrollTop < maxScroll - 5 ? 1 : 0;
    }

    if (navContainerMobile) navContainerMobile.addEventListener('scroll', updateNavScrollShadowMobile);
    if (navContainerDesktop) navContainerDesktop.addEventListener('scroll', updateNavScrollShadowDesktop);


    function scrollToActiveNav(index) {
        if (window.innerWidth < 1024) { 
            const activeBtn = document.getElementById(`nav-m-${index + 1}`);
            if (activeBtn) {
                const scrollContainer = navContainerMobile;
                const buttonLeft = activeBtn.offsetLeft;
                const buttonWidth = activeBtn.offsetWidth;
                const containerWidth = scrollContainer.offsetWidth;
                
                scrollContainer.scrollLeft = buttonLeft - (containerWidth / 2) + (buttonWidth / 2);
                updateNavScrollShadowMobile(); 
            }
        } else {
            const activeBtn = document.getElementById(`nav-d-${index + 1}`);
             if (activeBtn) {
                const scrollContainer = navContainerDesktop;
                scrollContainer.scrollTop = activeBtn.offsetTop - scrollContainer.clientHeight / 2 + activeBtn.clientHeight / 2;
                updateNavScrollShadowDesktop();
            }
        }
    }

    function updateRadioVisual(radio) {
        const questionCard = radio.closest('.question-card');
        const labels = questionCard.querySelectorAll('.answer-label');
        
        labels.forEach(label => label.classList.remove('is-checked'));

        if (radio.checked) {
            radio.closest('.answer-label').classList.add('is-checked');
        }
    }

    function handleRadioChange(radio) {
        const questionId = radio.name.replace('soal_', '');
        saveAnswer(questionId, radio.value);
        updateRadioVisual(radio);
        updateProgress();
    }

    function saveAnswer(questionId, value) {
        let answers = JSON.parse(localStorage.getItem(localStorageKey)) || {};
        answers[questionId] = value;
        localStorage.setItem(localStorageKey, JSON.stringify(answers));
    }

    function loadAnswers() {
        const answers = JSON.parse(localStorage.getItem(localStorageKey)) || {};
        let initialIndex = 0;
        let highestAnsweredIndex = -1;

        questionCards.forEach((card, i) => {
            const radioInput = card.querySelector('input[type="radio"]');
            if (!radioInput) return;

            const questionId = radioInput.name.replace('soal_', '');
            const answerValue = answers[questionId];
            
            if (answerValue) {
                const radio = form.querySelector(`input[name="soal_${questionId}"][value="${answerValue}"]`);
                if (radio) {
                    radio.checked = true;
                    updateRadioVisual(radio);
                    highestAnsweredIndex = i;
                }
            }
        });

        if (highestAnsweredIndex > -1) {
            initialIndex = (highestAnsweredIndex === totalQuestions - 1) ? totalQuestions : highestAnsweredIndex + 1;
        }
        
        showQuestion(initialIndex);
        updateProgress();
    }
    
    function setNavColor(btn, isActive, isAnswered) {
        btn.classList.remove('bg-gray-800', 'text-white', 'border-gray-800', 'bg-green-600', 'border-green-600', 'bg-gray-100', 'text-gray-600', 'border-gray-300', 'hover:bg-green-700', 'hover:bg-gray-200', 'bg-indigo-600', 'border-indigo-600', 'hover:bg-indigo-700', 'bg-indigo-800', 'border-indigo-800');
        
        if (isActive) {
            if (btn.dataset.questionId === 'final') {
                btn.classList.add('bg-indigo-800', 'text-white', 'border-indigo-800');
            } else {
                btn.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
            }
        } else if (btn.dataset.questionId === 'final') {
             btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'hover:bg-indigo-700');
        } else if (isAnswered) {
            btn.classList.add('bg-green-600', 'text-white', 'border-green-600', 'hover:bg-green-700');
        } else {
            btn.classList.add('bg-gray-100', 'text-gray-600', 'border-gray-300', 'hover:bg-gray-200');
        }
    }
    
    function updateProgress() {
        const answeredCount = getAnsweredCount();
        const percentage = (answeredCount / totalQuestions) * 100;
        progressBar.style.width = percentage + '%';
        progressText.textContent = `${answeredCount} / ${totalQuestions}`;
        
        for (let i = 0; i < totalQuestions; i++) {
             const card = questionCards[i];
             const radio = card.querySelector('input[type="radio"]');
             
             if (radio) {
                 const radioGroupName = radio.name;
                 const isAnswered = form.elements[radioGroupName] && 
                                     Array.from(form.elements[radioGroupName]).some(r => r.checked);
                
                const { mobileBtn, desktopBtn } = getNavButtonsByIndex(i);
                if (mobileBtn && desktopBtn) {
                     setNavColor(mobileBtn, i === currentQuestionIndex, isAnswered);
                     setNavColor(desktopBtn, i === currentQuestionIndex, isAnswered);
                }
            }
        }
        const { mobileBtn: finalMobileBtn, desktopBtn: finalDesktopBtn } = getNavButtonsByIndex(totalQuestions);
        if (finalMobileBtn && finalDesktopBtn) {
            setNavColor(finalMobileBtn, currentQuestionIndex === totalQuestions, answeredCount === totalQuestions);
            setNavColor(finalDesktopBtn, currentQuestionIndex === totalQuestions, answeredCount === totalQuestions);
        }
    }
    
    function showQuestion(index) {
        if (index >= 0 && index < totalCards) { 
            
            questionCards.forEach((card, i) => {
                card.classList.remove('active');
            });
            
            questionCards[index].classList.add('active');
            
            currentQuestionIndex = index;
            scrollToActiveNav(index);

            questionCards.forEach((card, i) => {
                 const prevButton = card.querySelector('.prev-btn');
                 const nextButton = card.querySelector('.next-btn');
                 const submitButtons = card.querySelectorAll('.submit-btn'); 

                 if (i === index) {
                     if (prevButton) prevButton.disabled = (index === 0);
                     
                     if (nextButton) nextButton.style.display = (index < totalQuestions - 1) ? 'flex' : 'none';
                     
                     if (index === totalQuestions - 1) {
                         if (nextButton) {
                             nextButton.style.display = 'flex';
                             nextButton.innerHTML = `<span class="hidden sm:inline">Selanjutnya</span><span class="sm:hidden">Lanjut</span><svg class="w-4 h-4 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>`;
                             nextButton.onclick = () => showQuestion(totalQuestions); 
                         }
                         submitButtons.forEach(btn => btn.style.display = 'none');
                     } else if (index < totalQuestions - 1) {
                         if (nextButton) {
                             nextButton.innerHTML = `<span class="hidden sm:inline">Selanjutnya</span><span class="sm:hidden">Lanjut</span><svg class="w-4 h-4 ml-1 sm:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>`;
                             nextButton.onclick = () => showQuestion(index + 1);
                         }
                         submitButtons.forEach(btn => btn.style.display = 'none');
                     }
                     
                     if (index === totalQuestions) {
                         if (nextButton) nextButton.style.display = 'none'; 
                         submitButtons.forEach(btn => btn.style.display = 'flex');
                     } else {
                         submitButtons.forEach(btn => btn.style.display = 'none');
                     }
                 }
            });

            updateProgress(); 
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadAnswers();
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