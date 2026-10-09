<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$nim = trim($_GET["nim"] ?? "");

if ($nim === "") {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
$stmt->bind_param("s", $nim);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    exit("Data mahasiswa tidak ditemukan.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $nama = trim($_POST["nama_mahasiswa"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $prodi = trim($_POST["program_studi"] ?? "");

        if ($nama === "" || $prodi === "") {
            $error = "Nama dan program studi wajib diisi.";
        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } else {
            $emailDb = $email === "" ? null : $email;

            $update = $conn->prepare(
                "UPDATE mahasiswa
                 SET nama_mahasiswa = ?, email = ?, program_studi = ?
                 WHERE nim = ?"
            );
            $update->bind_param("ssss", $nama, $emailDb, $prodi, $nim);

            try {
                if ($update->execute()) {
                    $update->close();
                    header("Location: index.php?status=edit");
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                $error = $ex->getCode() === 1062
                    ? "Email sudah digunakan mahasiswa lain."
                    : "Data gagal diperbarui.";
            }
            $update->close();
        }

        $data["nama_mahasiswa"] = $nama;
        $data["email"] = $email;
        $data["program_studi"] = $prodi;
    }
}

renderHeader("Edit Mahasiswa", "mahasiswa");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Edit Data Mahasiswa</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?nim=<?= urlencode($nim) ?>">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label>NIM (Nomor Induk Mahasiswa)</label>
            <input type="text" class="form-control" value="<?= e($data["nim"]) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="nama_mahasiswa">Nama Mahasiswa *</label>
            <input type="text" name="nama_mahasiswa" id="nama_mahasiswa" class="form-control" maxlength="100" required value="<?= e($data["nama_mahasiswa"]) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="100" value="<?= e($data["email"]) ?>">
        </div>

        <div class="form-group">
            <label for="program_studi">Program Studi *</label>
            <input type="text" name="program_studi" id="program_studi" class="form-control" maxlength="100" required value="<?= e($data["program_studi"]) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php renderFooter(); ?>