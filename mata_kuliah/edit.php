<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$kode = trim($_GET["kode_mk"] ?? "");

if ($kode === "") {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE kode_mk = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    exit("Mata kuliah tidak ditemukan.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $nama = trim($_POST["nama_mk"] ?? "");
        $sks = trim($_POST["sks"] ?? "");

        if ($nama === "" || $sks === "") {
            $error = "Nama mata kuliah dan SKS wajib diisi.";
        } elseif (!ctype_digit($sks) || (int)$sks < 1 || (int)$sks > 30) {
            $error = "SKS harus berupa bilangan bulat antara 1 dan 30.";
        } else {
            $jumlahSks = (int)$sks;
            $update = $conn->prepare(
                "UPDATE mata_kuliah SET nama_mk = ?, sks = ? WHERE kode_mk = ?"
            );
            $update->bind_param("sis", $nama, $jumlahSks, $kode);

            if ($update->execute()) {
                $update->close();
                header("Location: index.php?status=edit");
                exit;
            } else {
                $error = "Mata kuliah gagal diperbarui.";
                $update->close();
            }
        }

        $data["nama_mk"] = $nama;
        $data["sks"] = $sks;
    }
}

renderHeader("Edit Mata Kuliah", "mata_kuliah");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Edit Mata Kuliah</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?kode_mk=<?= urlencode($kode) ?>">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label>Kode Mata Kuliah</label>
            <input type="text" class="form-control" value="<?= e($data["kode_mk"]) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="nama_mk">Nama Mata Kuliah *</label>
            <input type="text" name="nama_mk" id="nama_mk" class="form-control" maxlength="100" required value="<?= e($data["nama_mk"]) ?>">
        </div>

        <div class="form-group">
            <label for="sks">Jumlah SKS *</label>
            <input type="number" name="sks" id="sks" class="form-control" min="1" max="30" required value="<?= e($data["sks"]) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php renderFooter(); ?>