<?php
require_once "../auth.php";
wajibRole(["admin", "dosen", "mahasiswa"]);

require_once "../koneksi.php";

$role = $_SESSION["role"];
$nim = $_SESSION["nim"] ?? "";
$nidn = $_SESSION["nidn"] ?? "";

if ($role === "mahasiswa" && $nim === "") {
    http_response_code(403);
    exit("Akun mahasiswa belum memiliki NIM.");
}

if ($role === "dosen" && $nidn === "") {
    http_response_code(403);
    exit("Akun dosen belum memiliki NIDN.");
}

$sql = "
    SELECT
        k.id_krs,
        k.nim,
        m.nama_mahasiswa,
        mk.nama_mk,
        kk.nama_kelas,
        kk.semester,
        kk.tahun_ajaran,
        d.nama_dosen,
        k.tanggal_ambil
    FROM krs k
    JOIN mahasiswa m ON m.nim = k.nim
    JOIN kelas_kuliah kk ON kk.id_kelas = k.id_kelas
    JOIN mata_kuliah mk ON mk.kode_mk = kk.kode_mk
    JOIN dosen d ON d.nidn = kk.nidn
";

if ($role === "mahasiswa") {
    $sql .= " WHERE k.nim = ?";
} elseif ($role === "dosen") {
    $sql .= " WHERE kk.nidn = ?";
}

$sql .= " ORDER BY k.tanggal_ambil DESC, k.id_krs DESC";

$stmt = $conn->prepare($sql);

if ($role === "mahasiswa") {
    $stmt->bind_param("s", $nim);
} elseif ($role === "dosen") {
    $stmt->bind_param("s", $nidn);
}

$stmt->execute();
$result = $stmt->get_result();
$status = $_GET["status"] ?? "";

renderHeader("Kartu Rencana Studi (KRS)", "krs");
?>

<div class="card">
    <div class="card-title">
        <span>Data Rencana Studi (KRS)</span>
        <?php if (in_array($role, ["admin", "mahasiswa"], true)): ?>
            <a class="btn btn-primary btn-sm" href="tambah.php">+ Tambah KRS</a>
        <?php endif; ?>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">KRS berhasil ditambahkan.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">KRS berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">KRS gagal dihapus karena masih terhubung dengan data Nilai.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Mata Kuliah</th>
                    <th>Kelas</th>
                    <th>Dosen</th>
                    <th>Semester</th>
                    <th>Tahun Ajaran</th>
                    <th>Tgl Ambil</th>
                    <?php if (in_array($role, ["admin", "mahasiswa"], true)): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($row["nim"]) ?></strong></td>
                        <td><?= e($row["nama_mahasiswa"]) ?></td>
                        <td><?= e($row["nama_mk"]) ?></td>
                        <td><span style="font-weight: 700; color: var(--primary);">Kelas <?= e($row["nama_kelas"]) ?></span></td>
                        <td><?= e($row["nama_dosen"]) ?></td>
                        <td>Sem <?= (int)$row["semester"] ?></td>
                        <td><?= e($row["tahun_ajaran"]) ?></td>
                        <td><?= e($row["tanggal_ambil"]) ?></td>

                        <?php if (in_array($role, ["admin", "mahasiswa"], true)): ?>
                            <td>
                                <form method="POST" action="hapus.php"
                                      onsubmit="return confirm('Yakin ingin menghapus KRS ini?');">
                                    <?= getCsrfInputField() ?>
                                    <input type="hidden" name="id_krs"
                                           value="<?= (int)$row["id_krs"] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= in_array($role, ["admin", "mahasiswa"], true) ? 10 : 9 ?>">
                        Belum ada data KRS yang dapat ditampilkan.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt->close();
renderFooter();
?>