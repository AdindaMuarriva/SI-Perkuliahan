<?php
require_once "../auth.php";
wajibRole(["admin"]);

require_once "../koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    exit("Token CSRF tidak valid. <a href='index.php'>Kembali</a>");
}

$id = filter_input(INPUT_POST, "id_kelas", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM kelas_kuliah WHERE id_kelas = ?");
$stmt->bind_param("i", $id);

try {
    $stmt->execute();
    $berhasil = $stmt->affected_rows > 0;
    $stmt->close();

    header("Location: index.php?status=" . ($berhasil ? "hapus" : "gagal_hapus"));
    exit;
} catch (mysqli_sql_exception $ex) {
    $stmt->close();

    if ($ex->getCode() === 1451) {
        header("Location: index.php?status=gagal_hapus");
        exit;
    }

    http_response_code(500);
    exit("Terjadi kesalahan saat menghapus data kelas. <a href='index.php'>Kembali</a>");
}
?>