<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$pesan = "";
$kode_mk = "";
$nidn = "";
$nama_kelas = "";
$semester = "";
$tahun_ajaran = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $pesan = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $kode_mk = trim($_POST["kode_mk"] ?? "");
        $nidn = trim($_POST["nidn"] ?? "");
        $nama_kelas = trim($_POST["nama_kelas"] ?? "");
        $semester = trim($_POST["semester"] ?? "");
        $tahun_ajaran = trim($_POST["tahun_ajaran"] ?? "");

        if (
            $kode_mk === "" || $nidn === "" || $nama_kelas === "" ||
            !ctype_digit($semester) || (int)$semester < 1 ||
            (int)$semester > 20 ||
            !preg_match('/^\d{4}\/\d{4}$/', $tahun_ajaran)
        ) {
            $pesan = "Periksa kembali semua data yang diisi.";
        } elseif (
            (int)substr($tahun_ajaran, 5, 4) !==
            (int)substr($tahun_ajaran, 0, 4) + 1
        ) {
            $pesan = "Tahun ajaran harus berurutan, misalnya 2026/2027.";
        } else {
            // Validasi keberadaan kode_mk & nidn di database
            $chkMk = $conn->prepare("SELECT kode_mk FROM mata_kuliah WHERE kode_mk = ?");
            $chkMk->bind_param("s", $kode_mk);
            $chkMk->execute();
            $mkAda = $chkMk->get_result()->num_rows > 0;
            $chkMk->close();

            $chkDsn = $conn->prepare("SELECT nidn FROM dosen WHERE nidn = ?");
            $chkDsn->bind_param("s", $nidn);
            $chkDsn->execute();
            $dsnAda = $chkDsn->get_result()->num_rows > 0;
            $chkDsn->close();

            if (!$mkAda || !$dsnAda) {
                $pesan = "Mata kuliah atau Dosen yang dipilih tidak valid.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO kelas_kuliah
                     (kode_mk, nidn, nama_kelas, semester, tahun_ajaran)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $semesterInt = (int)$semester;
                $stmt->bind_param(
                    "sssis",
                    $kode_mk, $nidn, $nama_kelas,
                    $semesterInt, $tahun_ajaran
                );

                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        header("Location: index.php?status=tambah");
                        exit;
                    }
                } catch (mysqli_sql_exception $ex) {
                    $pesan = "Gagal menambahkan kelas. Periksa kembali data input.";
                }
                $stmt->close();
            }
        }
    }
}

$mata_kuliah = $conn->query(
    "SELECT kode_mk, nama_mk FROM mata_kuliah ORDER BY nama_mk"
);
$dosen = $conn->query(
    "SELECT nidn, nama_dosen FROM dosen ORDER BY nama_dosen"
);

renderHeader("Tambah Kelas Kuliah", "kelas_kuliah");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Form Tambah Kelas Kuliah</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-danger"><?= e($pesan) ?></div>
    <?php endif; ?>

    <?php if ($mata_kuliah->num_rows === 0 || $dosen->num_rows === 0): ?>
        <div class="alert alert-info">
            Tambahkan data mata kuliah dan dosen terlebih dahulu.
        </div>
    <?php else: ?>
        <form method="POST" action="tambah.php">
            <?= getCsrfInputField() ?>

            <div class="form-group">
                <label for="kode_mk">Mata Kuliah *</label>
                <select name="kode_mk" id="kode_mk" class="form-control" required>
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while ($mk = $mata_kuliah->fetch_assoc()): ?>
                        <option value="<?= e($mk["kode_mk"]) ?>"
                            <?= $kode_mk === $mk["kode_mk"] ? "selected" : "" ?>>
                            <?= e($mk["kode_mk"] . " - " . $mk["nama_mk"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nidn">Dosen Pengajar *</label>
                <select name="nidn" id="nidn" class="form-control" required>
                    <option value="">-- Pilih Dosen --</option>
                    <?php while ($dsn = $dosen->fetch_assoc()): ?>
                        <option value="<?= e($dsn["nidn"]) ?>"
                            <?= $nidn === $dsn["nidn"] ? "selected" : "" ?>>
                            <?= e($dsn["nama_dosen"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nama_kelas">Nama Kelas *</label>
                <input type="text" name="nama_kelas" id="nama_kelas" class="form-control" maxlength="10"
                       value="<?= e($nama_kelas) ?>" required placeholder="Contoh: A">
            </div>

            <div class="form-group">
                <label for="semester">Semester *</label>
                <input type="number" name="semester" id="semester" class="form-control" min="1" max="20"
                       value="<?= e($semester) ?>" required>
            </div>

            <div class="form-group">
                <label for="tahun_ajaran">Tahun Ajaran *</label>
                <input type="text" name="tahun_ajaran" id="tahun_ajaran" class="form-control"
                       placeholder="2026/2027" pattern="\d{4}/\d{4}"
                       value="<?= e($tahun_ajaran) ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Kelas</button>
        </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>