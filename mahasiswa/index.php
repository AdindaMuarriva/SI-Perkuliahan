<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

$sql = "SELECT * FROM mahasiswa ORDER BY nim ASC";
$result = $conn->query($sql);
$status = $_GET["status"] ?? "";

renderHeader("Data Mahasiswa", "mahasiswa");
?>

<div class="card">
    <div class="card-title">
        <span>Kelola Data Mahasiswa</span>
        <a class="btn btn-primary btn-sm" href="tambah.php">+ Tambah Mahasiswa</a>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">Data mahasiswa berhasil ditambahkan.</div>
    <?php elseif ($status === "edit"): ?>
        <div class="alert alert-success">Data mahasiswa berhasil diperbarui.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">Data mahasiswa berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">Data mahasiswa tidak dapat dihapus karena masih terhubung dengan data KRS atau Akun User.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Email</th>
                    <th>Program Studi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= e($row["nim"]) ?></strong></td>
                    <td><?= e($row["nama_mahasiswa"]) ?></td>
                    <td><?= e($row["email"] ?? "-") ?></td>
                    <td><?= e($row["program_studi"]) ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a class="btn btn-edit btn-sm" href="edit.php?nim=<?= urlencode($row["nim"]) ?>">Edit</a>
                            <form method="POST" action="hapus.php"
                                  onsubmit="return confirm('Yakin ingin menghapus mahasiswa ini?')">
                                <?= getCsrfInputField() ?>
                                <input type="hidden" name="nim" value="<?= e($row["nim"]) ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Belum ada data mahasiswa.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderFooter(); ?>