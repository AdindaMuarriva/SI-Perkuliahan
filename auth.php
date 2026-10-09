<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escapes strings for safe HTML output (XSS prevention).
 */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

/**
 * Ensures the user is logged in.
 */
function wajibLogin(): void
{
    if (empty($_SESSION["id_user"])) {
        header("Location: /sistem_perkuliahan/login.php");
        exit;
    }
}

/**
 * Ensures the user is logged in and has one of the specified roles.
 */
function wajibRole($roles): void
{
    wajibLogin();

    $roles = (array) $roles;
    $roleAktif = $_SESSION["role"] ?? "";

    if (!in_array($roleAktif, $roles, true)) {
        http_response_code(403);
        exit(
            "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Akses Ditolak</title>" .
            "<link rel='stylesheet' href='/sistem_perkuliahan/assets/css/style.css'></head><body>" .
            "<div class='auth-wrapper'><div class='auth-card'>" .
            "<div class='auth-header'><div class='logo-badge'>!</div><h1>403 - Akses Ditolak</h1>" .
            "<p>Akun kamu tidak memiliki izin untuk membuka halaman ini.</p></div>" .
            '<p style="text-align:center"><a class="btn btn-primary" href="/sistem_perkuliahan/index.php">Kembali ke Dashboard</a></p>' .
            "</div></div></body></html>"
        );
    }
}

/**
 * Ensures the logged in user is a student with a valid linked NIM.
 */
function wajibIdentitasMahasiswa(): string
{
    wajibRole(["mahasiswa"]);

    $nim = $_SESSION["nim"] ?? "";

    if ($nim === "") {
        http_response_code(403);
        exit("Akun mahasiswa belum terhubung ke data mahasiswa.");
    }

    return $nim;
}

/**
 * Ensures the logged in user is a lecturer with a valid linked NIDN.
 */
function wajibIdentitasDosen(): string
{
    wajibRole(["dosen"]);

    $nidn = $_SESSION["nidn"] ?? "";

    if ($nidn === "") {
        http_response_code(403);
        exit("Akun dosen belum terhubung ke data dosen.");
    }

    return $nidn;
}

/**
 * Generates a CSRF token if one does not exist for the session.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

/**
 * Verifies a submitted CSRF token against the session token.
 */
function verifyCsrfToken(?string $token): bool
{
    $sessionToken = $_SESSION["csrf_token"] ?? "";
    if (empty($sessionToken) || empty($token)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Returns a hidden HTML input field containing the CSRF token.
 */
function getCsrfInputField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Renders the top part of the HTML page including head, sidebar navigation, and topbar.
 */
function renderHeader(string $title, string $activeMenu = "dashboard"): void
{
    global $conn;

    $role     = $_SESSION["role"]     ?? "";
    $username = $_SESSION["username"] ?? "User";
    $nim      = $_SESSION["nim"]      ?? "";
    $nidn     = $_SESSION["nidn"]     ?? "";
    $baseUrl  = "/sistem_perkuliahan";

    // Tampilkan nama asli dosen/mahasiswa; admin pakai username
    $displayName  = $username; // default (admin)
    $identityText = "";

    if ($role === "mahasiswa" && !empty($nim) && isset($conn)) {
        $stmtName = $conn->prepare("SELECT nama_mahasiswa FROM mahasiswa WHERE nim = ? LIMIT 1");
        if ($stmtName) {
            $stmtName->bind_param("s", $nim);
            $stmtName->execute();
            $stmtName->bind_result($namaMhs);
            if ($stmtName->fetch() && $namaMhs) {
                $displayName = $namaMhs;
            }
            $stmtName->close();
        }
        $identityText = "NIM: " . $nim;
    } elseif ($role === "dosen" && !empty($nidn) && isset($conn)) {
        $stmtName = $conn->prepare("SELECT nama_dosen FROM dosen WHERE nidn = ? LIMIT 1");
        if ($stmtName) {
            $stmtName->bind_param("s", $nidn);
            $stmtName->execute();
            $stmtName->bind_result($namaDosen);
            if ($stmtName->fetch() && $namaDosen) {
                $displayName = $namaDosen;
            }
            $stmtName->close();
        }
        $identityText = "NIDN: " . $nidn;
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> - SIP Akademik</title>
        <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    </head>
    <body>
    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-logo">S</div>
                <div class="sidebar-brand-text">
                    <h2>SIP AKADEMIK</h2>
                    <p>Sistem Perkuliahan</p>
                </div>
            </div>

            <div class="sidebar-profile">
                <div class="sidebar-profile-name"><?= e($displayName) ?></div>
                <span class="sidebar-profile-role"><?= e($role) ?></span>
                <?php if ($identityText !== ""): ?>
                    <div style="font-size: 11px; color: #F3E6D5; margin-top: 4px; opacity: 0.9;"><?= e($identityText) ?></div>
                <?php endif; ?>
            </div>

            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="<?= $baseUrl ?>/index.php" class="sidebar-menu-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if ($role === "admin"): ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/mahasiswa/" class="sidebar-menu-link <?= $activeMenu === 'mahasiswa' ? 'active' : '' ?>">
                            <span>Data Mahasiswa</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/dosen/" class="sidebar-menu-link <?= $activeMenu === 'dosen' ? 'active' : '' ?>">
                            <span>Data Dosen</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/mata_kuliah/" class="sidebar-menu-link <?= $activeMenu === 'mata_kuliah' ? 'active' : '' ?>">
                            <span>Mata Kuliah</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/kelas_kuliah/" class="sidebar-menu-link <?= $activeMenu === 'kelas_kuliah' ? 'active' : '' ?>">
                            <span>Kelas Kuliah</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/krs/" class="sidebar-menu-link <?= $activeMenu === 'krs' ? 'active' : '' ?>">
                            <span>Kartu Rencana Studi (KRS)</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/nilai/" class="sidebar-menu-link <?= $activeMenu === 'nilai' ? 'active' : '' ?>">
                            <span>Data Nilai</span>
                        </a>
                    </li>

                <?php elseif ($role === "dosen"): ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/kelas_kuliah/" class="sidebar-menu-link <?= $activeMenu === 'kelas_kuliah' ? 'active' : '' ?>">
                            <span>Kelas Saya</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/krs/" class="sidebar-menu-link <?= $activeMenu === 'krs' ? 'active' : '' ?>">
                            <span>KRS Mahasiswa</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/nilai/" class="sidebar-menu-link <?= $activeMenu === 'nilai' ? 'active' : '' ?>">
                            <span>Input & Kelola Nilai</span>
                        </a>
                    </li>

                <?php elseif ($role === "mahasiswa"): ?>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/krs/" class="sidebar-menu-link <?= $activeMenu === 'krs' ? 'active' : '' ?>">
                            <span>KRS Saya</span>
                        </a>
                    </li>
                    <li class="sidebar-menu-item">
                        <a href="<?= $baseUrl ?>/nilai/" class="sidebar-menu-link <?= $activeMenu === 'nilai' ? 'active' : '' ?>">
                            <span>Nilai Saya</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= $baseUrl ?>/logout.php" class="logout-link">
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Layout -->
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1 class="topbar-title"><?= e($title) ?></h1>
                    <p class="topbar-subtitle">Sistem Informasi Perkuliahan & Management Akademik</p>
                </div>
                <div>
                    <span style="font-size: 13px; color: var(--text-muted); font-weight: 500;">
                        Hari ini: <?= date('d M Y') ?>
                    </span>
                </div>
            </header>
            <div class="content-body">
    <?php
}

/**
 * Renders the bottom footer part of the HTML page.
 */
function renderFooter(): void
{
    ?>
            </div>
            <footer class="app-footer">
                <p>&copy; <?= date('Y') ?> <strong>SIP Akademik</strong> — Sistem Informasi Perkuliahan. All Rights Reserved.</p>
            </footer>
        </main>
    </div>
    </body>
    </html>
    <?php
}
?>