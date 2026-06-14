<?php
// =============================================
// LOGIN PAGE
// =============================================
$pageTitle = 'Masuk';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect(SITE_URL);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'aktif'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : SITE_URL;
            redirect($redirect);
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>

<div class="section">
<div class="container">
    <div class="form-card">
        <h2><i class="fas fa-book-open" style="color:var(--teal)"></i> <?= SITE_NAME ?></h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Alamat Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>"
                       placeholder="email@contoh.com" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Password Anda" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>
        <div class="form-footer">
            Belum punya akun? <a href="daftar.php">Daftar sekarang</a>
        </div>
        <div class="form-footer" style="margin-top:.5rem;font-size:.82rem;color:var(--text-muted)">
            Demo: admin@perpustakaan.id / password
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
