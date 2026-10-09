<?php
require_once "../auth.php";
wajibRole(["admin", "dosen"]);

require_once "../koneksi.php";

$role = $_SESSION["role"];
$nidn = $_SESSION["nidn"] ?? "";
$error = "";
$statusMsg = "";

if ($role === "dosen" && $nidn === "") {
    http_response_code(403);
    exit("Akun dosen belum terhubung ke NIDN.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($token)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        $idKrs = filter_input(INPUT_POST, "id_krs", FILTER_VALIDATE_INT);
        $tugas = trim($_POST["nilai_tugas"] ?? "");
        $uts = trim($_POST["nilai_uts"] ?? "");
        $uas = trim($_POST["nilai_uas"] ?? "");

        $valid = $idKrs && $tugas !== "" && $uts !== "" && $uas !== "";

        foreach ([$tugas, $uts, $uas] as $nilai) {
            if (!is_numeric($nilai) || (float)$nilai < 0 || (float)$nilai > 100) {
                $valid = false;
            }
        }

        if (!$valid) {
            $error = "Pilih KRS mahasiswa dan masukkan nilai tugas, UTS, dan UAS antara 0 sampai 100.";
        } else {
            // Validasi server-side bahwa id_krs ini valid dan milik kelas dosen yang login (jika role dosen)
            $checkSql = "SELECT k.id_krs
                         FROM krs k
                         JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas
                         WHERE k.id_krs = ?";

            if ($role === "dosen") {
                $checkSql .= " AND kk.nidn = ?";
            }

            $stmtChk = $conn->prepare($checkSql);
            if ($role === "dosen") {
                $stmtChk->bind_param("is", $idKrs, $nidn);
            } else {
                $stmtChk->bind_param("i", $idKrs);
            }
            $stmtChk->execute();
            $krsValid = $stmtChk->get_result()->num_rows === 1;
            $stmtChk->close();

            if (!$krsValid) {
                $error = "KRS yang dipilih tidak valid atau bukan merupakan bagian dari kelas Anda.";
            } else {
                $tugasFloat = (float)$tugas;
                $utsFloat = (float)$uts;
                $uasFloat = (float)$uas;
                $akhir = round($tugasFloat * 0.30 + $utsFloat * 0.30 + $uasFloat * 0.40, 2);

                // Upsert: Simpan nilai baru atau perbarui jika sudah ada
                $stmtUpsert = $conn->prepare(
                    "INSERT INTO nilai (id_krs, nilai_tugas, nilai_uts, nilai_uas, nilai_akhir)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                     nilai_tugas = VALUES(nilai_tugas),
                     nilai_uts = VALUES(nilai_uts),
                     nilai_uas = VALUES(nilai_uas),
                     nilai_akhir = VALUES(nilai_akhir)"
                );
                $stmtUpsert->bind_param("idddd", $idKrs, $tugasFloat, $utsFloat, $uasFloat, $akhir);

                if ($stmtUpsert->execute()) {
                    $stmtUpsert->close();
                    header("Location: index.php?status=tambah");
                    exit;
                } else {
                    $error = "Nilai gagal disimpan. Periksa kembali datanya.";
                    $stmtUpsert->close();
                }
            }
        }
    }
}

// Mengambil seluruh daftar mahasiswa yang mengambil kelas (KRS)
$sqlPilihan = "SELECT k.id_krs, m.nim, m.nama_mahasiswa, mk.nama_mk, kk.nama_kelas,
                      n.id_nilai, n.nilai_tugas, n.nilai_uts, n.nilai_uas, n.nilai_akhir
               FROM krs k
               JOIN mahasiswa m ON k.nim = m.nim
               JOIN kelas_kuliah kk ON k.id_kelas = kk.id_kelas
               JOIN mata_kuliah mk ON kk.kode_mk = mk.kode_mk
               LEFT JOIN nilai n ON n.id_krs = k.id_krs";

if ($role === "dosen") {
    $sqlPilihan .= " WHERE kk.nidn = ?";
}

$sqlPilihan .= " ORDER BY m.nama_mahasiswa, mk.nama_mk";

$stmtPilihan = $conn->prepare($sqlPilihan);
if ($role === "dosen") {
    $stmtPilihan->bind_param("s", $nidn);
}
$stmtPilihan->execute();
$pilihan = $stmtPilihan->get_result();

renderHeader("Input Nilai Mahasiswa", "nilai");
?>

<div class="card">
    <div class="card-title">
        <span>Input / Perbarui Nilai Mahasiswa</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($pilihan && $pilihan->num_rows > 0): ?>
        <form method="POST" action="tambah.php">
            <?= getCsrfInputField() ?>

            <div class="form-group">
                <label for="id_krs">Pilih Mahasiswa & Kelas Kuliah *</label>
                <select name="id_krs" id="id_krs" class="form-control" required>
                    <option value="">-- Pilih Mahasiswa & Mata Kuliah --</option>
                    <?php while ($p = $pilihan->fetch_assoc()): ?>
                        <?php
                        $sudahAda = $p["id_nilai"] !== null;
                        $labelStatus = $sudahAda
                            ? " [Sudah ada nilai: T:{$p['nilai_tugas']} | U:{$p['nilai_uts']} | U:{$p['nilai_uas']}]"
                            : " [Belum ada nilai]";
                        ?>
                        <option value="<?= (int)$p["id_krs"] ?>"
                            <?= (string)($_POST["id_krs"] ?? "") === (string)$p["id_krs"] ? "selected" : "" ?>>
                            <?= e($p["nim"] . " - " . $p["nama_mahasiswa"] . " | " . $p["nama_mk"] . " (" . $p["nama_kelas"] . ")" . $labelStatus) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px;">
                    * Anda dapat memilih mahasiswa mana saja di kelas Anda. Jika nilai sudah ada, sistem akan langsung memperbaruinya.
                </small>
            </div>

            <div class="form-group">
                <label for="nilai_tugas">Nilai Tugas (0–100) *</label>
                <input type="number" name="nilai_tugas" id="nilai_tugas" class="form-control" min="0" max="100" step="0.01" required value="<?= e($_POST["nilai_tugas"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="nilai_uts">Nilai UTS (0–100) *</label>
                <input type="number" name="nilai_uts" id="nilai_uts" class="form-control" min="0" max="100" step="0.01" required value="<?= e($_POST["nilai_uts"] ?? "") ?>">
            </div>

            <div class="form-group">
                <label for="nilai_uas">Nilai UAS (0–100) *</label>
                <input type="number" name="nilai_uas" id="nilai_uas" class="form-control" min="0" max="100" step="0.01" required value="<?= e($_POST["nilai_uas"] ?? "") ?>">
            </div>

            <button type="submit" class="btn btn-primary">Simpan Nilai</button>
        </form>

        <p style="font-size: 13px; color: var(--text-muted); margin-top: 15px;">
            * Bobot rumus nilai akhir: <strong>30% Tugas + 30% UTS + 40% UAS</strong>.
        </p>
    <?php else: ?>
        <div class="alert alert-info">
            Belum ada mahasiswa yang mengambil kelas Anda di KRS.
        </div>
    <?php endif; ?>
</div>

<?php
$stmtPilihan->close();
renderFooter();
?>