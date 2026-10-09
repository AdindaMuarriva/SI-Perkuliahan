<?php
require_once "../auth.php";
wajibRole(["admin", "mahasiswa"]);

require_once "../koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!verifyCsrfToken($token)) {
    http_response_code(403);
    exit("Permintaan tidak valid. Kembali ke halaman <a href='index.php'>KRS</a> dan coba lagi.");
}

$idKrs = filter_input(
    INPUT_POST,
    "id_krs",
    FILTER_VALIDATE_INT
);

if (!$idKrs || $idKrs < 1) {
    http_response_code(400);
    exit("ID KRS tidak valid. <a href='index.php'>Kembali</a>");
}

// Ambil pemilik KRS dari database
$stmt = $conn->prepare(
    "SELECT nim FROM krs WHERE id_krs = ? LIMIT 1"
);
$stmt->bind_param("i", $idKrs);
$stmt->execute();
$stmt->bind_result($nimPemilik);
$krsDitemukan = $stmt->fetch();
$stmt->close();

if (!$krsDitemukan) {
    http_response_code(404);
    exit("Data KRS tidak ditemukan. <a href='index.php'>Kembali</a>");
}

// Mahasiswa hanya boleh menghapus KRS dengan NIM miliknya
if (
    $_SESSION["role"] === "mahasiswa" &&
    $nimPemilik !== ($_SESSION["nim"] ?? "")
) {
    http_response_code(403);
    exit(
        "Akses ditolak. Kamu hanya boleh menghapus KRS milikmu sendiri. " .
        '<a href="index.php">Kembali ke KRS</a>'
    );
}

$stmt = $conn->prepare("DELETE FROM krs WHERE id_krs = ?");
$stmt->bind_param("i", $idKrs);

try {
    $stmt->execute();
    $berhasil = $stmt->affected_rows === 1;
    $stmt->close();

    if ($berhasil) {
        header("Location: index.php?status=hapus");
        exit;
    } else {
        header("Location: index.php?status=gagal_hapus");
        exit;
    }
} catch (mysqli_sql_exception $ex) {
    $stmt->close();
    if ($ex->getCode() === 1451) {
        header("Location: index.php?status=gagal_hapus");
        exit;
    }
    http_response_code(500);
    exit("KRS gagal dihapus. <a href='index.php'>Kembali</a>");
}
?>