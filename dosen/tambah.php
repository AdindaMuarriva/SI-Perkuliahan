<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$pesan = "";
$nidn = "";
$nama = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $pesan = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $nidn = trim($_POST["nidn"] ?? "");
        $nama = trim($_POST["nama_dosen"] ?? "");
        $email = trim($_POST["email"] ?? "");

        if ($nidn === "" || $nama === "") {
            $pesan = "NIDN dan nama dosen wajib diisi.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = "Format email tidak valid.";
        } else {
            $emailDB = $email === "" ? null : $email;

            $stmt = $conn->prepare(
                "INSERT INTO dosen (nidn, nama_dosen, email)
                 VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $nidn, $nama, $emailDB);

            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?status=tambah");
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                $pesan = $ex->getCode() === 1062
                    ? "NIDN atau email tersebut sudah digunakan."
                    : "Gagal menyimpan data dosen.";
            }
            $stmt->close();
        }
    }
}

renderHeader("Tambah Dosen", "dosen");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Form Tambah Dosen</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-danger"><?= e($pesan) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label for="nidn">NIDN *</label>
            <input type="text" name="nidn" id="nidn" class="form-control" maxlength="20" value="<?= e($nidn) ?>" required>
        </div>

        <div class="form-group">
            <label for="nama_dosen">Nama Dosen *</label>
            <input type="text" name="nama_dosen" id="nama_dosen" class="form-control" maxlength="100" value="<?= e($nama) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="100" value="<?= e($email) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Data Dosen</button>
    </form>
</div>

<?php renderFooter(); ?>