<?php
require_once "auth.php";
wajibLogin();

require_once "koneksi.php";

$role     = $_SESSION["role"];
$username = $_SESSION["username"];
$nim      = $_SESSION["nim"]  ?? "";
$nidn     = $_SESSION["nidn"] ?? "";

// Ambil nama asli dosen/mahasiswa untuk ditampilkan di dashboard
$namaDisplay = $username; // default (admin)
if ($role === "mahasiswa" && !empty($nim)) {
    $stmtN = $conn->prepare("SELECT nama_mahasiswa FROM mahasiswa WHERE nim = ? LIMIT 1");
    if ($stmtN) {
        $stmtN->bind_param("s", $nim);
        $stmtN->execute();
        $stmtN->bind_result($tmpNama);
        if ($stmtN->fetch() && $tmpNama) { $namaDisplay = $tmpNama; }
        $stmtN->close();
    }
} elseif ($role === "dosen" && !empty($nidn)) {
    $stmtN = $conn->prepare("SELECT nama_dosen FROM dosen WHERE nidn = ? LIMIT 1");
    if ($stmtN) {
        $stmtN->bind_param("s", $nidn);
        $stmtN->execute();
        $stmtN->bind_result($tmpNama);
        if ($stmtN->fetch() && $tmpNama) { $namaDisplay = $tmpNama; }
        $stmtN->close();
    }
}

// Admin global counter helper
function jumlahDataAdmin($conn, $tabel) {
    $tabelValid = [
        "mahasiswa", "dosen", "mata_kuliah",
        "kelas_kuliah", "krs", "nilai"
    ];

    if (!in_array($tabel, $tabelValid, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$tabel`");
    return $result ? (int)$result->fetch_assoc()["total"] : 0;
}

// Dosen metrics
$dosenStats = [
    "total_kelas" => 0,
    "total_mahasiswa" => 0,
    "total_krs" => 0,
    "total_nilai" => 0
];

if ($role === "dosen" && !empty($nidn)) {
    // Total kelas
    $stmt = $conn->prepare("SELECT COUNT(*) FROM kelas_kuliah WHERE nidn = ?");
    $stmt->bind_param("s", $nidn);
    $stmt->execute();
    $stmt->bind_result($dosenStats["total_kelas"]);
    $stmt->fetch();
    $stmt->close();

    // Total mahasiswa unik di kelas dosen
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT k.nim) FROM krs k JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas WHERE kk.nidn = ?");
    $stmt->bind_param("s", $nidn);
    $stmt->execute();
    $stmt->bind_result($dosenStats["total_mahasiswa"]);
    $stmt->fetch();
    $stmt->close();

    // Total KRS di kelas dosen
    $stmt = $conn->prepare("SELECT COUNT(*) FROM krs k JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas WHERE kk.nidn = ?");
    $stmt->bind_param("s", $nidn);
    $stmt->execute();
    $stmt->bind_result($dosenStats["total_krs"]);
    $stmt->fetch();
    $stmt->close();

    // Total nilai diinput
    $stmt = $conn->prepare("SELECT COUNT(*) FROM nilai n JOIN krs k ON n.id_krs = k.id_krs JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas WHERE kk.nidn = ?");
    $stmt->bind_param("s", $nidn);
    $stmt->execute();
    $stmt->bind_result($dosenStats["total_nilai"]);
    $stmt->fetch();
    $stmt->close();
}

// Mahasiswa metrics
$mhsStats = [
    "total_krs" => 0,
    "total_sks" => 0,
    "rata_nilai" => 0.0
];

if ($role === "mahasiswa" && !empty($nim)) {
    // Total KRS diambil
    $stmt = $conn->prepare("SELECT COUNT(*) FROM krs WHERE nim = ?");
    $stmt->bind_param("s", $nim);
    $stmt->execute();
    $stmt->bind_result($mhsStats["total_krs"]);
    $stmt->fetch();
    $stmt->close();

    // Total SKS diambil
    $stmt = $conn->prepare("SELECT COALESCE(SUM(mk.sks), 0) FROM krs k JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas JOIN mata_kuliah mk ON kk.kode_mk = mk.kode_mk WHERE k.nim = ?");
    $stmt->bind_param("s", $nim);
    $stmt->execute();
    $stmt->bind_result($mhsStats["total_sks"]);
    $stmt->fetch();
    $stmt->close();

    // Rata-rata nilai akhir
    $stmt = $conn->prepare("SELECT COALESCE(AVG(n.nilai_akhir), 0) FROM nilai n JOIN krs k ON n.id_krs = k.id_krs WHERE k.nim = ?");
    $stmt->bind_param("s", $nim);
    $stmt->execute();
    $stmt->bind_result($mhsStats["rata_nilai"]);
    $stmt->fetch();
    $stmt->close();
}

renderHeader("Dashboard", "dashboard");
?>

<div class="card" style="border-left: 5px solid var(--primary);">
    <h2 style="font-size: 20px; color: var(--primary); font-weight: 700;">
        Selamat datang kembali, <?= e($namaDisplay) ?>!
    </h2>
    <p style="font-size: 14px; color: var(--text-muted); margin-top: 6px;">
        Anda terdaftar sebagai <strong style="color: var(--accent);"><?= e(ucfirst($role)) ?></strong>. 
        Gunakan menu sidebar di sebelah kiri untuk navigasi dan pengelolaan data akademik.
    </p>
</div>

<?php if ($role === "admin"): ?>
    <div class="card">
        <div class="card-title">Ringkasan Data Akademik Kampus</div>
        <div class="grid-stats">
            <div class="stat-card">
                <div class="label">Mahasiswa Terdaftar</div>
                <div class="value"><?= jumlahDataAdmin($conn, "mahasiswa") ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Dosen Pengajar</div>
                <div class="value"><?= jumlahDataAdmin($conn, "dosen") ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Mata Kuliah</div>
                <div class="value"><?= jumlahDataAdmin($conn, "mata_kuliah") ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Kelas Kuliah</div>
                <div class="value"><?= jumlahDataAdmin($conn, "kelas_kuliah") ?></div>
            </div>
            <div class="stat-card accent-border">
                <div class="label">KRS Terdaftar</div>
                <div class="value"><?= jumlahDataAdmin($conn, "krs") ?></div>
            </div>
            <div class="stat-card accent-border">
                <div class="label">Nilai Terinput</div>
                <div class="value"><?= jumlahDataAdmin($conn, "nilai") ?></div>
            </div>
        </div>
    </div>

<?php elseif ($role === "dosen"): ?>
    <div class="card">
        <div class="card-title">Ringkasan Kelas & Aktivitas Mengajar</div>
        <div class="grid-stats">
            <div class="stat-card">
                <div class="label">Kelas Diampu</div>
                <div class="value"><?= $dosenStats["total_kelas"] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Mahasiswa Dididik</div>
                <div class="value"><?= $dosenStats["total_mahasiswa"] ?></div>
            </div>
            <div class="stat-card accent-border">
                <div class="label">Total KRS Kelas</div>
                <div class="value"><?= $dosenStats["total_krs"] ?></div>
            </div>
            <div class="stat-card accent-border">
                <div class="label">Nilai Terinput</div>
                <div class="value"><?= $dosenStats["total_nilai"] ?></div>
            </div>
        </div>
    </div>

<?php elseif ($role === "mahasiswa"): ?>
    <div class="card">
        <div class="card-title">Ringkasan Akademik Saya</div>
        <div class="grid-stats">
            <div class="stat-card">
                <div class="label">KRS / Kelas Diambil</div>
                <div class="value"><?= $mhsStats["total_krs"] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total SKS Diambil</div>
                <div class="value"><?= $mhsStats["total_sks"] ?></div>
            </div>
            <div class="stat-card accent-border">
                <div class="label">Rata-Rata Nilai Akhir</div>
                <div class="value"><?= number_format($mhsStats["rata_nilai"], 2) ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
renderFooter();
?>