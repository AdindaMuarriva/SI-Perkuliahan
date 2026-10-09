<?php
require_once "../auth.php";
wajibRole(["admin", "dosen"]);

require_once "../koneksi.php";

$role = $_SESSION["role"];
$nidn = $_SESSION["nidn"] ?? "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    exit("Token CSRF tidak valid. <a href='index.php'>Kembali</a>");
}

$id = filter_input(INPUT_POST, "id_nilai", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

// Cek data dan hak akses dosen (IDOR prevention)
$stmtCheck = $conn->prepare(
    "SELECT kl.nidn
     FROM nilai n
     JOIN krs k ON n.id_krs = k.id_krs
     JOIN kelas_kuliah kl ON k.id_kelas = kl.id_kelas
     WHERE n.id_nilai = ? LIMIT 1"
);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$stmtCheck->bind_result($nidnKelas);
$ditemukan = $stmtCheck->fetch();
$stmtCheck->close();

if (!$ditemukan) {
    header("Location: index.php?status=gagal_hapus");
    exit;
}

if ($role === "dosen" && $nidnKelas !== $nidn) {
    http_response_code(403);
    exit("Akses ditolak. Anda hanya diperbolehkan menghapus nilai dari kelas Anda. <a href='index.php'>Kembali</a>");
}

$stmt = $conn->prepare("DELETE FROM nilai WHERE id_nilai = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$berhasil = $stmt->affected_rows > 0;
$stmt->close();

if ($berhasil) {
    header("Location: index.php?status=hapus");
} else {
    header("Location: index.php?status=gagal_hapus");
}
exit;
?>