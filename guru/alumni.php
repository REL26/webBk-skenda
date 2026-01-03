<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ../login.php");
    exit;
}

$filter_nama    = isset($_GET['nama']) ? mysqli_real_escape_string($koneksi, trim($_GET['nama'])) : '';
$filter_jurusan = isset($_GET['jurusan']) ? mysqli_real_escape_string($koneksi, trim($_GET['jurusan'])) : '';

$query = "SELECT s.*, 
        (SELECT COUNT(*) FROM hasil_gayabelajar WHERE id_siswa = s.id_siswa) AS has_gb,
        (SELECT COUNT(*) FROM hasil_kecerdasan WHERE id_siswa = s.id_siswa) AS has_kc
          FROM siswa s 
          WHERE s.kelas LIKE 'Lulus%'";

if ($filter_nama)    { $query .= " AND s.nama LIKE '%$filter_nama%'"; }
if ($filter_jurusan) { $query .= " AND s.jurusan = '$filter_jurusan'"; }

$query .= " ORDER BY s.nama ASC";
$data_siswa = mysqli_query($koneksi, $query);

$data_jurusan = mysqli_query($koneksi,"SELECT DISTINCT jurusan FROM siswa WHERE kelas LIKE 'Lulus%' ORDER BY jurusan");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Arsip Alumni</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="icon" type="image/png" href="https://epkl.smkn2-bjm.sch.id/vendor/adminlte/dist/img/smkn2.png">
<style>
body{
background:linear-gradient(180deg,#0f2f2a,#0b2420);
font-family:'Plus Jakarta Sans',sans-serif;
}
.fade{animation:fade .3s ease}
@keyframes fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
</style>
</head>

<body class="min-h-screen text-gray-800">

<div class="max-w-5xl mx-auto px-4 py-10 fade">

<div class="flex items-center justify-between mb-8">
    <div class="flex items-center gap-3 text-white">
        <i class="fas fa-archive text-xl"></i>
        <h1 class="text-xl font-bold tracking-wide">Arsip Alumni</h1>
    </div>
    <a href="hasil_tes.php" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm transition flex items-center gap-2 border border-white/20">
        <i class="fas fa-arrow-left text-xs"></i> Kembali
    </a>
</div>

<div class="bg-white rounded-xl p-3 mb-8 border">
<form class="flex gap-2 flex-col md:flex-row">
<input type="text" name="nama" placeholder="Cari nama / NIS" value="<?= htmlspecialchars($filter_nama) ?>"
class="flex-1 px-4 py-2 border rounded-lg text-sm">
<select name="jurusan" onchange="this.form.submit()" class="px-4 py-2 border rounded-lg text-sm">
<option value="">Semua Jurusan</option>
<?php while($j=mysqli_fetch_assoc($data_jurusan)): ?>
<option value="<?= $j['jurusan'] ?>" <?= $filter_jurusan==$j['jurusan']?'selected':'' ?>>
<?= $j['jurusan'] ?>
</option>
<?php endwhile; ?>
</select>
<button class="px-5 py-2 bg-emerald-700 text-white rounded-lg text-sm">
<i class="fas fa-search mr-2"></i>Cari
</button>
</form>
</div>

<div class="space-y-3">
<?php if(mysqli_num_rows($data_siswa) > 0): ?>
    <?php while($row=mysqli_fetch_assoc($data_siswa)): ?>
    <div class="bg-white border rounded-xl p-5 hover:border-emerald-300 transition fade">
    <div class="flex justify-between items-center">
    <div class="flex items-center gap-4">
    <div class="w-12 h-12 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700 font-bold">
    <i class="fas fa-user-graduate"></i>
    </div>
    <div>
    <h3 class="font-semibold"><?= htmlspecialchars($row['nama']) ?></h3>
    <p class="text-xs text-gray-500">
    <i class="fas fa-graduation-cap mr-1"></i><?= $row['jurusan'] ?>
    <span class="mx-1">•</span>
    <i class="fas fa-id-card mr-1"></i><?= $row['nis'] ?>
    </p>
    </div>
    </div>
    <button onclick="showResultModal(
    <?= $row['id_siswa'] ?>,
    '<?= addslashes($row['nama']) ?>',
    '<?= $row['nis'] ?>',
    '<?= addslashes($row['jurusan']) ?>',
    <?= $row['has_kc']>0?1:0 ?>,
    <?= $row['has_gb']>0?1:0 ?>
    )"
    class="px-4 py-2 bg-gray-100 hover:bg-emerald-700 hover:text-white rounded-lg text-xs transition flex items-center gap-2">
    <i class="fas fa-folder-tree"></i>Detail Arsip
    </button>
    </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="bg-white/5 border border-white/10 rounded-2xl p-20 text-center fade">
        <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-users-slash text-white/30 text-3xl"></i>
        </div>
        <h3 class="text-white font-bold text-lg">Data Alumni Tidak Ditemukan</h3>
        <p class="text-white/40 text-sm mt-1">Belum ada data siswa dengan status Lulus yang tersedia.</p>
    </div>
<?php endif; ?>
</div>
</div>

<div id="resultModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
<div class="absolute inset-0 bg-black/50" onclick="hideResultModal()"></div>

<div class="relative bg-white w-full max-w-md rounded-xl z-50 fade">
<div class="p-5 border-b flex justify-between items-center">
<div>
<h3 id="modalTitle" class="font-bold"></h3>
<p id="modalSubtitle" class="text-xs text-emerald-700"></p>
</div>
<button onclick="hideResultModal()" class="text-gray-400 hover:text-red-500">
<i class="fas fa-xmark"></i>
</button>
</div>

<div class="p-6 space-y-3" id="resultCardsContainer"></div>
</div>
</div>

<script>
    function buildResultCards(id,kc,gb){
        const data=[
        {title:'Biodata Siswa',desc:'Identitas & Data Pokok',icon:'fa-folder-open',url:`detail_biodata.php?id_siswa=${id}`,active:true},
        {title:'Tes Kemampuan',desc:'Potensi & Kecerdasan',icon:'fa-chart-column',url:`detail_hasil_kemampuan.php?id_siswa=${id}&type=kecerdasan`,active:kc==1},
        {title:'Tes Gaya Belajar',desc:'Visual, Auditori, Kinestetik',icon:'fa-book-open',url:`detail_hasil_gayabelajar.php?id_siswa=${id}&type=gayabelajar`,active:gb==1}
    ];
    const c=document.getElementById('resultCardsContainer');
    c.innerHTML='';
    data.forEach(d=>{
    c.innerHTML+=`
<a href="${d.active?d.url:'#'}"
class="${d.active?'':'opacity-40 pointer-events-none'} flex items-center justify-between p-4 border rounded-lg hover:border-emerald-400 transition">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700">
<i class="fas ${d.icon}"></i>
</div>
<div>
<p class="text-sm font-semibold">${d.title}</p>
<p class="text-xs text-gray-500">${d.desc}</p>
</div>
</div>
<i class="fas ${d.active?'fa-chevron-right':'fa-lock'} text-gray-300"></i>
</a>`;
});
}

    function showResultModal(id,nama,nis,jurusan,kc,gb){
document.getElementById('modalTitle').textContent=nama;
document.getElementById('modalSubtitle').textContent='NIS '+nis+' • '+jurusan;
buildResultCards(id,kc,gb);
document.getElementById('resultModal').classList.remove('hidden');
document.body.style.overflow='hidden';
    }

    function hideResultModal(){
document.getElementById('resultModal').classList.add('hidden');
document.body.style.overflow='auto';
    }
</script>

</body>
</html>