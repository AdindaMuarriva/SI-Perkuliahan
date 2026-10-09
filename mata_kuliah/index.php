<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$result = $conn->query("SELECT * FROM mata_kuliah ORDER BY kode_mk ASC");
$status = $_GET["status"] ?? "";

renderHeader("Data Mata Kuliah", "mata_kuliah");
?>

<div class="card">
    <div class="card-title">
        <span>Kelola Mata Kuliah</span>
        <a class="btn btn-primary btn-sm" href="tambah.php">+ Tambah Mata Kuliah</a>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">Mata kuliah berhasil ditambahkan.</div>
    <?php elseif ($status === "edit"): ?>
        <div class="alert alert-success">Mata kuliah berhasil diperbarui.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">Mata kuliah berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">Mata kuliah tidak dapat dihapus karena masih digunakan oleh kelas kuliah.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode MK</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= e($row["kode_mk"]) ?></strong></td>
                        <td><?= e($row["nama_mk"]) ?></td>
                        <td><span style="font-weight: 700; color: var(--primary);"><?= e($row["sks"]) ?> SKS</span></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a class="btn btn-edit btn-sm" href="edit.php?kode_mk=<?= urlencode($row["kode_mk"]) ?>">Edit</a>
                                <form method="POST" action="hapus.php"
                                      onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                                    <?= getCsrfInputField() ?>
                                    <input type="hidden" name="kode_mk" value="<?= e($row["kode_mk"]) ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">Belum ada data mata kuliah.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>