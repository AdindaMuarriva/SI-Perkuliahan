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

$sql = "SELECT
            n.id_nilai,
            n.nilai_tugas,
            n.nilai_uts,
            n.nilai_uas,
            n.nilai_akhir,
            m.nim,
            m.nama_mahasiswa,
            mk.kode_mk,
            mk.nama_mk,
            kl.nama_kelas,
            kl.semester,
            kl.tahun_ajaran,
            d.nama_dosen
        FROM nilai n
        JOIN krs k ON n.id_krs = k.id_krs
        JOIN mahasiswa m ON k.nim = m.nim
        JOIN kelas_kuliah kl ON k.id_kelas = kl.id_kelas
        JOIN mata_kuliah mk ON kl.kode_mk = mk.kode_mk
        JOIN dosen d ON kl.nidn = d.nidn";

if ($role === "dosen") {
    $sql .= " WHERE kl.nidn = ?";
} elseif ($role === "mahasiswa") {
    $sql .= " WHERE k.nim = ?";
}

$sql .= " ORDER BY m.nama_mahasiswa, mk.nama_mk";

$stmt = $conn->prepare($sql);

if ($role === "dosen") {
    $stmt->bind_param("s", $nidn);
} elseif ($role === "mahasiswa") {
    $stmt->bind_param("s", $nim);
}

$stmt->execute();
$result = $stmt->get_result();
$status = $_GET["status"] ?? "";

renderHeader("Data Nilai Mahasiswa", "nilai");
?>

<div class="card">
    <div class="card-title">
        <span>Kelola Nilai Akademik</span>
        <?php if (in_array($role, ["admin", "dosen"], true)): ?>
            <a class="btn btn-primary btn-sm" href="tambah.php">+ Input / Edit Nilai</a>
        <?php endif; ?>
    </div>

    <?php if ($status === "tambah"): ?>
        <div class="alert alert-success">Nilai berhasil disimpan.</div>
    <?php elseif ($status === "edit"): ?>
        <div class="alert alert-success">Nilai berhasil diperbarui.</div>
    <?php elseif ($status === "hapus"): ?>
        <div class="alert alert-success">Nilai berhasil dihapus.</div>
    <?php elseif ($status === "gagal_hapus"): ?>
        <div class="alert alert-danger">Nilai gagal dihapus atau datanya tidak ditemukan.</div>
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
                    <th>Semester</th>
                    <th>Thn Ajaran</th>
                    <th>Tugas</th>
                    <th>UTS</th>
                    <th>UAS</th>
                    <th>Nilai Akhir</th>
                    <?php if (in_array($role, ["admin", "dosen"], true)): ?>
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
                            <td><strong><?= e($row["kode_mk"]) ?></strong> — <?= e($row["nama_mk"]) ?></td>
                            <td><span style="font-weight:700; color:var(--primary);">Kelas <?= e($row["nama_kelas"]) ?></span></td>
                            <td>Sem <?= (int)$row["semester"] ?></td>
                            <td><?= e($row["tahun_ajaran"]) ?></td>
                            <td>
                                <?= $row["nilai_tugas"] !== null
                                    ? number_format((float)$row["nilai_tugas"], 2)
                                    : "-" ?>
                            </td>
                            <td>
                                <?= $row["nilai_uts"] !== null
                                    ? number_format((float)$row["nilai_uts"], 2)
                                    : "-" ?>
                            </td>
                            <td>
                                <?= $row["nilai_uas"] !== null
                                    ? number_format((float)$row["nilai_uas"], 2)
                                    : "-" ?>
                            </td>
                            <td>
                                <strong style="font-size: 15px; color: var(--accent);">
                                    <?= $row["nilai_akhir"] !== null
                                        ? number_format((float)$row["nilai_akhir"], 2)
                                        : "-" ?>
                                </strong>
                            </td>
                            <?php if (in_array($role, ["admin", "dosen"], true)): ?>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a class="btn btn-edit btn-sm" href="edit.php?id=<?= (int)$row["id_nilai"] ?>">
                                            Edit
                                        </a>

                                        <form action="hapus.php" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus nilai ini?')">
                                            <?= getCsrfInputField() ?>
                                            <input type="hidden" name="id_nilai"
                                                   value="<?= (int)$row["id_nilai"] ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= in_array($role, ["admin", "dosen"], true) ? 12 : 11 ?>">
                            Belum ada data nilai mahasiswa.
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