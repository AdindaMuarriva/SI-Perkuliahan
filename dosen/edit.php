<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$nidn = trim($_GET["nidn"] ?? "");
if ($nidn === "") {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare(
    "SELECT nama_dosen, email FROM dosen WHERE nidn = ?"
);
$stmt->bind_param("s", $nidn);
$stmt->execute();
$dosen = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dosen) {
    exit("Data dosen tidak ditemukan.");
}

$nama = $dosen["nama_dosen"];
$email = $dosen["email"] ?? "";
$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $pesan = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $nama = trim($_POST["nama_dosen"] ?? "");
        $email = trim($_POST["email"] ?? "");

        if ($nama === "") {
            $pesan = "Nama dosen wajib diisi.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = "Format email tidak valid.";
        } else {
            $emailDB = $email === "" ? null : $email;

            $stmt = $conn->prepare(
                "UPDATE dosen SET nama_dosen = ?, email = ? WHERE nidn = ?"
            );
            $stmt->bind_param("sss", $nama, $emailDB, $nidn);

            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?status=edit");
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                $pesan = $ex->getCode() === 1062
                    ? "Email tersebut sudah digunakan."
                    : "Gagal memperbarui data dosen.";
            }
            $stmt->close();
        }
    }
}

renderHeader("Edit Dosen", "dosen");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Edit Data Dosen</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-danger"><?= e($pesan) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?nidn=<?= urlencode($nidn) ?>">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label>NIDN</label>
            <input type="text" class="form-control" value="<?= e($nidn) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="nama_dosen">Nama Dosen *</label>
            <input type="text" name="nama_dosen" id="nama_dosen" class="form-control" maxlength="100" value="<?= e($nama) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="100" value="<?= e($email) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php renderFooter(); ?>