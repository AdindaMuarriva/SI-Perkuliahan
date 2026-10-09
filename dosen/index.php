<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$result = $conn->query(
    "SELECT nidn, nama_dosen, email
     FROM dosen
     ORDER BY nama_dosen"
);

$status = $_GET["status"] ?? "";

renderHeader("Data Dosen", "dosen");
?>

<div class="card">
    <div class="card-title">
        <span>Kelola Data Dosen</span>
        <a class="btn btn-primary btn-sm" href="tambah.php">+ Tambah Dosen</a>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">Data dosen berhasil ditambahkan.</div>
    <?php elseif ($status === "edit"): ?>
        <div class="alert alert-success">Data dosen berhasil diperbarui.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">Data dosen berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">Data dosen tidak dapat dihapus karena masih digunakan pada kelas kuliah atau akun user.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NIDN</th>
                    <th>Nama Dosen</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = 1; ?>
                <?php while ($dosen = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= e($dosen["nidn"]) ?></strong></td>
                        <td><?= e($dosen["nama_dosen"]) ?></td>
                        <td><?= e($dosen["email"] ?? "-") ?></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a class="btn btn-edit btn-sm"
                                   href="edit.php?nidn=<?= urlencode($dosen["nidn"]) ?>">
                                   Edit
                                </a>

                                <form action="hapus.php" method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus dosen ini?')">
                                    <?= getCsrfInputField() ?>
                                    <input type="hidden" name="nidn"
                                           value="<?= e($dosen["nidn"]) ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Belum ada data dosen.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>