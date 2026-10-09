<?php
require_once "../auth.php";
wajibRole(["admin", "dosen"]);

require_once "../koneksi.php";

$role = $_SESSION["role"];
$nidn = $_SESSION["nidn"] ?? "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$sqlFetch = "SELECT n.id_nilai, n.nilai_tugas, n.nilai_uts, n.nilai_uas,
                    m.nama_mahasiswa, mk.nama_mk, kl.nidn
             FROM nilai n
             JOIN krs k ON n.id_krs = k.id_krs
             JOIN mahasiswa m ON k.nim = m.nim
             JOIN kelas_kuliah kl ON k.id_kelas = kl.id_kelas
             JOIN mata_kuliah mk ON kl.kode_mk = mk.kode_mk
             WHERE n.id_nilai = ?";

$stmt = $conn->prepare($sqlFetch);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    exit("Data nilai tidak ditemukan. <a href='index.php'>Kembali</a>");
}

// Pembatasan IDOR untuk Dosen: hanya boleh edit nilai dari kelas sendiri
if ($role === "dosen" && $data["nidn"] !== $nidn) {
    http_response_code(403);
    exit("Akses ditolak. Anda hanya diperbolehkan mengedit nilai dari kelas yang Anda ampu. <a href='index.php'>Kembali</a>");
}

$tugas = (string) $data["nilai_tugas"];
$uts   = (string) $data["nilai_uts"];
$uas   = (string) $data["nilai_uas"];
$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($token)) {
        $pesan = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $tugas = trim($_POST["nilai_tugas"] ?? "");
        $uts   = trim($_POST["nilai_uts"] ?? "");
        $uas   = trim($_POST["nilai_uas"] ?? "");

        $valid = true;

        foreach ([$tugas, $uts, $uas] as $nilai) {
            if (
                $nilai === "" ||
                !is_numeric($nilai) ||
                (float)$nilai < 0 ||
                (float)$nilai > 100
            ) {
                $valid = false;
                break;
            }
        }

        if (!$valid) {
            $pesan = "Semua nilai harus diisi dengan angka antara 0 sampai 100.";
        } else {
            $tugasFloat = (float)$tugas;
            $utsFloat   = (float)$uts;
            $uasFloat   = (float)$uas;
            $nilaiAkhir = round($tugasFloat * 0.30 + $utsFloat * 0.30 + $uasFloat * 0.40, 2);

            $stmtUpdate = $conn->prepare(
                "UPDATE nilai
                 SET nilai_tugas = ?, nilai_uts = ?,
                     nilai_uas = ?, nilai_akhir = ?
                 WHERE id_nilai = ?"
            );

            $stmtUpdate->bind_param(
                "ddddi",
                $tugasFloat, $utsFloat, $uasFloat, $nilaiAkhir, $id
            );

            if ($stmtUpdate->execute()) {
                $stmtUpdate->close();
                header("Location: index.php?status=edit");
                exit;
            }

            $pesan = "Gagal memperbarui nilai. Silakan coba lagi.";
            $stmtUpdate->close();
        }
    }
}

renderHeader("Edit Nilai Mahasiswa", "nilai");
?>

<div class="card">
    <div class="card-title">
        <span>Edit Nilai Mahasiswa</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <div style="background: var(--cream-bg); border: 1px solid var(--cream-border); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
        <p style="margin: 3px 0; font-size: 14px; color: var(--text-dark);">
            <strong style="color: var(--primary);">Mahasiswa:</strong>
            <?= e($data["nama_mahasiswa"]) ?>
        </p>
        <p style="margin: 3px 0; font-size: 14px; color: var(--text-dark);">
            <strong style="color: var(--primary);">Mata Kuliah:</strong>
            <?= e($data["nama_mk"]) ?>
        </p>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-danger"><?= e($pesan) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int)$id ?>">
        <?= getCsrfInputField() ?>

        <div class="form-group">
            <label for="nilai_tugas">Nilai Tugas (0–100) *</label>
            <input
                type="number"
                id="nilai_tugas"
                name="nilai_tugas"
                class="form-control"
                min="0"
                max="100"
                step="0.01"
                value="<?= e($tugas) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="nilai_uts">Nilai UTS (0–100) *</label>
            <input
                type="number"
                id="nilai_uts"
                name="nilai_uts"
                class="form-control"
                min="0"
                max="100"
                step="0.01"
                value="<?= e($uts) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="nilai_uas">Nilai UAS (0–100) *</label>
            <input
                type="number"
                id="nilai_uas"
                name="nilai_uas"
                class="form-control"
                min="0"
                max="100"
                step="0.01"
                value="<?= e($uas) ?>"
                required
            >
        </div>

        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px;">
            * Bobot rumus nilai akhir: <strong>30% Tugas + 30% UTS + 40% UAS</strong>
        </p>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php renderFooter(); ?>