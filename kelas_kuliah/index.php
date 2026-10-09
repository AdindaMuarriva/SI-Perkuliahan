<?php
require_once "../auth.php";
wajibRole(["admin", "dosen", "mahasiswa"]);

require_once "../koneksi.php";

$role = $_SESSION["role"];
$nidn = $_SESSION["nidn"] ?? "";

$sql = "SELECT k.id_kelas, k.kode_mk, k.nidn,
               k.nama_kelas, k.semester, k.tahun_ajaran,
               m.nama_mk, d.nama_dosen
        FROM kelas_kuliah k
        JOIN mata_kuliah m ON k.kode_mk = m.kode_mk
        JOIN dosen d ON k.nidn = d.nidn";

if ($role === "dosen") {
    $sql .= " WHERE k.nidn = ?";
}

$sql .= " ORDER BY k.tahun_ajaran DESC, k.semester, k.nama_kelas";

$stmt = $conn->prepare($sql);

if ($role === "dosen") {
    $stmt->bind_param("s", $nidn);
}

$stmt->execute();
$result = $stmt->get_result();
$status = $_GET["status"] ?? "";

renderHeader("Data Kelas Kuliah", "kelas_kuliah");
?>

<div class="card">
    <div class="card-title">
        <span>Daftar Kelas Kuliah</span>
        <?php if ($role === "admin"): ?>
            <a class="btn btn-primary btn-sm" href="tambah.php">+ Tambah Kelas</a>
        <?php endif; ?>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">Data kelas berhasil ditambahkan.</div>
    <?php elseif ($status === "edit"): ?>
        <div class="alert alert-success">Data kelas berhasil diperbarui.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">Data kelas berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">Kelas tidak dapat dihapus karena masih digunakan dalam KRS.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Kelas</th>
                    <th>Mata Kuliah</th>
                    <th>Dosen Pengajar</th>
                    <th>Nama Kelas</th>
                    <th>Semester</th>
                    <th>Tahun Ajaran</th>
                    <?php if ($role === "admin"): ?>
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
                        <td><strong>#<?= (int) $row["id_kelas"] ?></strong></td>
                        <td>
                            <strong><?= e($row["kode_mk"]) ?></strong> — <?= e($row["nama_mk"]) ?>
                        </td>
                        <td><?= e($row["nama_dosen"]) ?></td>
                        <td><span style="font-weight: 700; color: var(--accent);">Kelas <?= e($row["nama_kelas"]) ?></span></td>
                        <td>Semester <?= (int) $row["semester"] ?></td>
                        <td><?= e($row["tahun_ajaran"]) ?></td>
                        <?php if ($role === "admin"): ?>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a class="btn btn-edit btn-sm" href="edit.php?id=<?= (int) $row["id_kelas"] ?>">Edit</a>
                                    <form action="hapus.php" method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus kelas ini?')">
                                        <?= getCsrfInputField() ?>
                                        <input type="hidden" name="id_kelas" value="<?= (int) $row["id_kelas"] ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $role === "admin" ? 8 : 7 ?>">Belum ada data kelas kuliah.</td>
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