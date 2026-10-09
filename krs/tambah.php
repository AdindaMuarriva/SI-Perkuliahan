<?php
require_once "../auth.php";
wajibRole(["admin", "mahasiswa"]);

require_once "../koneksi.php";

$role     = $_SESSION["role"];
$nimLogin = $_SESSION["nim"] ?? "";
$pesan    = "";

if ($role === "mahasiswa" && $nimLogin === "") {
    http_response_code(403);
    exit("Akun mahasiswa belum memiliki NIM.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($token)) {
        $pesan = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    } else {
        // NIM mahasiswa selalu diambil dari session (bukan dari input form)
        $nim = ($role === "mahasiswa")
            ? $nimLogin
            : trim($_POST["nim"] ?? "");

        $idKelas = filter_input(INPUT_POST, "id_kelas", FILTER_VALIDATE_INT);

        if ($nim === "" || !$idKelas || $idKelas < 1) {
            $pesan = "Mahasiswa dan kelas wajib dipilih.";
        } else {
            // Pastikan mahasiswa terdaftar
            $stmt = $conn->prepare("SELECT nim FROM mahasiswa WHERE nim = ?");
            $stmt->bind_param("s", $nim);
            $stmt->execute();
            $mahasiswaAda = $stmt->get_result()->num_rows === 1;
            $stmt->close();

            // Pastikan kelas tersedia
            $stmt = $conn->prepare("SELECT id_kelas FROM kelas_kuliah WHERE id_kelas = ?");
            $stmt->bind_param("i", $idKelas);
            $stmt->execute();
            $kelasAda = $stmt->get_result()->num_rows === 1;
            $stmt->close();

            if (!$mahasiswaAda || !$kelasAda) {
                $pesan = "Data mahasiswa atau kelas tidak ditemukan.";
            } else {
                // Cegah duplikat KRS
                $stmt = $conn->prepare("SELECT id_krs FROM krs WHERE nim = ? AND id_kelas = ?");
                $stmt->bind_param("si", $nim, $idKelas);
                $stmt->execute();
                $sudahDiambil = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if ($sudahDiambil) {
                    $pesan = "Mahasiswa tersebut sudah mengambil kelas ini sebelumnya.";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO krs (nim, id_kelas, tanggal_ambil)
                         VALUES (?, ?, CURDATE())"
                    );
                    $stmt->bind_param("si", $nim, $idKelas);

                    try {
                        if ($stmt->execute()) {
                            $stmt->close();
                            header("Location: index.php?status=tambah");
                            exit;
                        }
                    } catch (mysqli_sql_exception $ex) {
                        $pesan = $ex->getCode() === 1062
                            ? "Mahasiswa sudah mengambil kelas ini."
                            : "Gagal menambahkan KRS. Periksa kembali datanya.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Daftar mahasiswa hanya untuk admin
$mahasiswa = null;
if ($role === "admin") {
    $mahasiswa = $conn->query(
        "SELECT nim, nama_mahasiswa FROM mahasiswa ORDER BY nama_mahasiswa"
    );
}

// Opsi kelas kuliah
if ($role === "mahasiswa") {
    // Mahasiswa hanya melihat kelas yang belum diambil
    $stmtKelas = $conn->prepare(
        "SELECT kk.id_kelas, mk.nama_mk, kk.nama_kelas,
                kk.semester, kk.tahun_ajaran, d.nama_dosen
         FROM kelas_kuliah kk
         JOIN mata_kuliah mk ON mk.kode_mk = kk.kode_mk
         JOIN dosen d ON d.nidn = kk.nidn
         WHERE NOT EXISTS (
             SELECT 1 FROM krs k
             WHERE k.nim = ? AND k.id_kelas = kk.id_kelas
         )
         ORDER BY mk.nama_mk"
    );
    $stmtKelas->bind_param("s", $nimLogin);
    $stmtKelas->execute();
    $kelas = $stmtKelas->get_result();
} else {
    $stmtKelas = null;
    $kelas = $conn->query(
        "SELECT kk.id_kelas, mk.nama_mk, kk.nama_kelas,
                kk.semester, kk.tahun_ajaran, d.nama_dosen
         FROM kelas_kuliah kk
         JOIN mata_kuliah mk ON mk.kode_mk = kk.kode_mk
         JOIN dosen d ON d.nidn = kk.nidn
         ORDER BY mk.nama_mk"
    );
}

renderHeader("Tambah KRS", "krs");
?>

<div class="card">
    <div class="card-title">
        <span>Tambah Kartu Rencana Studi (KRS)</span>
        <a href="index.php" class="btn btn-outline btn-sm">← Kembali</a>
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="alert alert-danger"><?= e($pesan) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <?= getCsrfInputField() ?>

        <?php if ($role === "admin"): ?>
            <div class="form-group">
                <label for="nim">Pilih Mahasiswa *</label>
                <select name="nim" id="nim" class="form-control" required>
                    <option value="">-- Pilih Mahasiswa --</option>
                    <?php while ($m = $mahasiswa->fetch_assoc()): ?>
                        <option value="<?= e($m["nim"]) ?>">
                            <?= e($m["nim"] . " — " . $m["nama_mahasiswa"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php else: ?>
            <div style="background: var(--cream-bg); border: 1px solid var(--cream-border); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 14px; color: var(--text-dark);">
                Mahasiswa: <strong style="color: var(--primary);"><?= e($nimLogin) ?></strong>
                (NIM Anda — otomatis dari akun yang sedang login)
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="id_kelas">Pilih Kelas Kuliah *</label>
            <?php if ($kelas && $kelas->num_rows > 0): ?>
                <select name="id_kelas" id="id_kelas" class="form-control" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php while ($k = $kelas->fetch_assoc()): ?>
                        <option value="<?= (int)$k["id_kelas"] ?>">
                            <?= e(
                                $k["nama_mk"] .
                                " — Kelas " . $k["nama_kelas"] .
                                " — Sem " . $k["semester"] .
                                " (" . $k["tahun_ajaran"] . ")" .
                                " — Dosen: " . $k["nama_dosen"]
                            ) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php else: ?>
                <div class="alert alert-info">
                    <?= $role === "mahasiswa"
                        ? "Semua kelas kuliah yang tersedia saat ini sudah Anda ambil."
                        : "Belum ada kelas kuliah yang terdaftar dalam sistem." ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($kelas && $kelas->num_rows > 0): ?>
            <button type="submit" class="btn btn-primary">Simpan KRS</button>
        <?php endif; ?>
    </form>
</div>

<?php
if ($stmtKelas !== null) {
    $stmtKelas->close();
}
renderFooter();
?>