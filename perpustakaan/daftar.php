<?php
$pageTitle = 'Daftar Anggota';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect(SITE_URL);

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirmasi'] ?? '';
    if (!$nama || !$email || !$password) {
        $error = 'Semua bidang wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $db = getDB();
        $cek = $db->prepare("SELECT id FROM users WHERE email=?");
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $noAnggota = 'MBR-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (nama,email,password,no_anggota) VALUES (?,?,?,?)")
               ->execute([$nama, $email, $hash, $noAnggota]);
            $success = "Pendaftaran berhasil! Nomor anggota Anda: <strong>$noAnggota</strong>. Silakan masuk.";
        }
    }
}
?>

<div class="section">
<div class="container">
    <div class="form-card" style="max-width:520px">
        <h2><i class="fas fa-user-plus" style="color:var(--teal)"></i> Daftar Anggota</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
            <a href="login.php" class="btn btn-primary btn-full" style="margin-bottom:1rem">Masuk Sekarang</a>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nama Lengkap</label>
                <input type="text" name="nama" class="form-control"
                       value="<?= isset($_POST['nama']) ? sanitize($_POST['nama']) : '' ?>"
                       placeholder="Nama lengkap Anda" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Alamat Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>"
                       placeholder="email@contoh.com" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                <input type="password" name="konfirmasi" class="form-control"
                       placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>
        <?php endif; ?>
        <div class="form-footer">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
