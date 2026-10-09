<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$error = "";
$kode = "";
$nama = "";
$sks = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!verifyCsrfToken($token)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $kode = strtoupper(trim($_POST["kode_mk"] ?? ""));
        $nama = trim($_POST["nama_mk"] ?? "");
        $sks = trim($_POST["sks"] ?? "");

        if ($kode === "" || $nama === "" || $sks === "") {
            $error = "Semua kolom wajib diisi.";
        } elseif (!ctype_digit($sks) || (int)$sks < 1 || (int)$sks > 30) {
            $error = "SKS harus berupa bilangan bulat antara 1 dan 30.";
        } else {
            $jumlahSks = (int)$sks;
            $stmt = $conn->prepare(
                "INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("ssi", $kode, $nama, $jumlahSks);

            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?status=tambah");
                    exit;
                }
            } catch (mysqli_sql_exception $ex) {
                $error = $ex->getCode() === 1062
                    ? "Kode mata kuliah tersebut sudah digunakan."
                    : "Data gagal disimpan. Periksa kembali input kamu.";
            }
            $stmt->close();
        }
    }
}

renderHeader("Tambah Mata Kuliah", "mata_kuliah");
?>

<div class="card" style="max-width: 650px; margin: auto;">
    <div class="card-title">
        <span>Form Tambah Mata Kuliah</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label for="kode_mk">Kode Mata Kuliah *</label>
            <input type="text" name="kode_mk" id="kode_mk" class="form-control" maxlength="10" required value="<?= e($kode) ?>" placeholder="Contoh: IF101">
        </div>

        <div class="form-group">
            <label for="nama_mk">Nama Mata Kuliah *</label>
            <input type="text" name="nama_mk" id="nama_mk" class="form-control" maxlength="100" required value="<?= e($nama) ?>" placeholder="Contoh: Pemrograman Web">
        </div>

        <div class="form-group">
            <label for="sks">Jumlah SKS *</label>
            <input type="number" name="sks" id="sks" class="form-control" min="1" max="30" required value="<?= e($sks) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Simpan Mata Kuliah</button>
    </form>
</div>

<?php renderFooter(); ?>