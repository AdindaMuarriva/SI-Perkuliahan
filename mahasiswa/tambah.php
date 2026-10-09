<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$error = "";
$nim = "";
$nama = "";
$email = "";
$prodi = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $nim = trim($_POST["nim"] ?? "");
        $nama = trim($_POST["nama_mahasiswa"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $prodi = trim($_POST["program_studi"] ?? "");

        if ($nim === "" || $nama === "" || $prodi === "") {
            $error = "NIM, nama, dan program studi wajib diisi.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } else {
            $emailDb = $email === "" ? null : $email;

            $stmt = $conn->prepare(
                "INSERT INTO mahasiswa (nim, nama_mahasiswa, email, program_studi)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $nim, $nama, $emailDb, $prodi);

            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?status=tambah");
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                $error = $ex->getCode() === 1062
                    ? "NIM atau email sudah digunakan."
                    : "Data gagal disimpan. Periksa kembali input kamu.";
            }
            $stmt->close();
        }
    }
}

renderHeader("Tambah Mahasiswa", "mahasiswa");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Form Tambah Mahasiswa</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label for="nim">NIM *</label>
            <input type="text" name="nim" id="nim" class="form-control" maxlength="15" required value="<?= e($nim) ?>">
        </div>

        <div class="form-group">
            <label for="nama_mahasiswa">Nama Mahasiswa *</label>
            <input type="text" name="nama_mahasiswa" id="nama_mahasiswa" class="form-control" maxlength="100" required value="<?= e($nama) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="100" value="<?= e($email) ?>">
        </div>

        <div class="form-group">
            <label for="program_studi">Program Studi *</label>
            <input type="text" name="program_studi" id="program_studi" class="form-control" maxlength="100" required value="<?= e($prodi) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Data Mahasiswa</button>
    </form>
</div>

<?php renderFooter(); ?>