<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "koneksi.php";
require_once "auth.php";

if (!empty($_SESSION["id_user"])) {
    header("Location: index.php");
    exit;
}

$pesan      = "";
$info       = $_GET["info"]  ?? "";
$roleInput  = "mahasiswa";
$identInput = ""; // NIM / NIDN / username tergantung role

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $roleInput  = trim($_POST["role"]      ?? "");
    $identInput = trim($_POST["identitas"] ?? "");
    $passwordInput = $_POST["password"]    ?? "";

    if ($roleInput === "" || $identInput === "" || $passwordInput === "") {
        $pesan = "Semua kolom wajib diisi.";
    } elseif (!in_array($roleInput, ["admin", "dosen", "mahasiswa"], true)) {
        $pesan = "Role tidak valid.";
    } else {
        // Tentukan kolom pencarian berdasarkan role
        if ($roleInput === "mahasiswa") {
            $searchCol = "nim";
        } elseif ($roleInput === "dosen") {
            $searchCol = "nidn";
        } else {
            $searchCol = "username"; // admin
        }

        $stmt = $conn->prepare(
            "SELECT id_user, username, password, role, nim, nidn
             FROM users
             WHERE `$searchCol` = ? AND role = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $pesan = "Terjadi kesalahan pada database.";
        } else {
            $stmt->bind_param("ss", $identInput, $roleInput);
            $stmt->execute();
            $stmt->bind_result(
                $idUser,
                $usernameDB,
                $passwordDB,
                $roleDB,
                $nimDB,
                $nidnDB
            );

            $akunDitemukan = $stmt->fetch();
            $stmt->close();

            if ($akunDitemukan && password_verify($passwordInput, $passwordDB)) {
                // Verifikasi identitas terhubung dengan benar
                $identitasValid =
                    ($roleDB === "admin") ||
                    ($roleDB === "dosen"     && !empty($nidnDB)) ||
                    ($roleDB === "mahasiswa" && !empty($nimDB));

                if (!$identitasValid) {
                    $pesan = "Akun belum terhubung dengan data resmi. Hubungi administrator.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION["id_user"]  = $idUser;
                    $_SESSION["username"] = $usernameDB;
                    $_SESSION["role"]     = $roleDB;
                    $_SESSION["nim"]      = $nimDB;
                    $_SESSION["nidn"]     = $nidnDB;
                    generateCsrfToken();

                    header("Location: index.php");
                    exit;
                }
            } else {
                // Pesan generik agar tidak bocorkan info akun mana yang ada
                if ($roleInput === "mahasiswa") {
                    $pesan = "NIM atau password salah.";
                } elseif ($roleInput === "dosen") {
                    $pesan = "NIDN atau password salah.";
                } else {
                    $pesan = "Username atau password salah.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Perkuliahan</title>
    <meta name="description" content="Masuk ke portal Sistem Informasi Perkuliahan (SIP) Akademik.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-badge">SIP</div>
                <h1>SIP Perkuliahan</h1>
                <p>Masuk ke portal sistem informasi akademik</p>
            </div>

            <?php if ($info === "register_disabled"): ?>
                <div class="alert alert-info">Pendaftaran akun publik tidak tersedia. Hubungi administrator untuk pembuatan akun.</div>
            <?php endif; ?>

            <?php if ($pesan !== ""): ?>
                <div class="alert alert-danger"><?= e($pesan) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="role">Login Sebagai (Role):</label>
                    <select name="role" id="role" class="form-control" required onchange="updateIdentitasField()">
                        <option value="mahasiswa" <?= $roleInput === "mahasiswa" ? "selected" : "" ?>>Mahasiswa</option>
                        <option value="dosen"     <?= $roleInput === "dosen"     ? "selected" : "" ?>>Dosen</option>
                        <option value="admin"     <?= $roleInput === "admin"     ? "selected" : "" ?>>Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="identitas" id="identitas-label">NIM (Nomor Induk Mahasiswa):</label>
                    <input
                        type="text"
                        id="identitas"
                        name="identitas"
                        class="form-control"
                        placeholder="Masukkan NIM Anda"
                        value="<?= e($identInput) ?>"
                        autocomplete="username"
                        required
                    >
                    <small id="identitas-hint" style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">
                        Gunakan NIM yang terdaftar di kampus.
                    </small>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Masuk ke Sistem
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--text-muted);">
                Belum memiliki akun? Hubungi <strong style="color: var(--primary);">Administrator</strong> untuk pendaftaran.
            </div>
        </div>
    </div>

    <script>
        const roleConfig = {
            mahasiswa: {
                label:       'NIM (Nomor Induk Mahasiswa):',
                placeholder: 'Masukkan NIM Anda',
                hint:        'Gunakan NIM yang terdaftar di kampus.'
            },
            dosen: {
                label:       'NIDN (Nomor Induk Dosen Nasional):',
                placeholder: 'Masukkan NIDN Anda',
                hint:        'Gunakan NIDN yang terdaftar sebagai dosen.'
            },
            admin: {
                label:       'Username:',
                placeholder: 'Masukkan username',
                hint:        'Gunakan username akun administrator.'
            }
        };

        function updateIdentitasField() {
            const role   = document.getElementById('role').value;
            const cfg    = roleConfig[role] || roleConfig.mahasiswa;
            document.getElementById('identitas-label').textContent = cfg.label;
            document.getElementById('identitas').placeholder       = cfg.placeholder;
            document.getElementById('identitas-hint').textContent  = cfg.hint;
        }

        // Jalankan saat pertama kali halaman dimuat agar label sesuai nilai terpilih
        updateIdentitasField();
    </script>
</body>
</html>