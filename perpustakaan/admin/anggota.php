<?php
// admin/anggota.php — Kelola Anggota
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

$msg  = '';
$aksi = $_GET['aksi'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ── HAPUS ──────────────────────────────────────────────
if ($aksi === 'hapus' && $editId) {
    $db->prepare("DELETE FROM users WHERE id=? AND role!='admin'")->execute([$editId]);
    redirect(SITE_URL . '/admin/anggota.php?msg=hapus');
}

// ── TOGGLE STATUS ──────────────────────────────────────
if (isset($_GET['toggle']) && (int)$_GET['toggle']) {
    $uid = (int)$_GET['toggle'];
    $cur = $db->prepare("SELECT status FROM users WHERE id=?");
    $cur->execute([$uid]);
    $row = $cur->fetch();
    if ($row) {
        $newStatus = $row['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        $db->prepare("UPDATE users SET status=? WHERE id=?")->execute([$newStatus, $uid]);
    }
    redirect(SITE_URL . '/admin/anggota.php');
}

// ── SIMPAN (TAMBAH / EDIT) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)($_POST['id'] ?? 0);
    $nama       = trim($_POST['nama']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = $_POST['password']        ?? '';
    $konfirmasi = $_POST['konfirmasi']      ?? '';
    $role       = $_POST['role']            ?? 'anggota';
    $status     = $_POST['status']          ?? 'aktif';
    $no_anggota = trim($_POST['no_anggota'] ?? '');

    // Validasi
    $errors = [];
    if (!$nama)  $errors[] = 'Nama wajib diisi.';
    if (!$email) $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    // Cek duplikat email
    $cekEmail = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $cekEmail->execute([$email, $id]);
    if ($cekEmail->fetch()) $errors[] = 'Email sudah digunakan anggota lain.';

    if (!$id) {
        // Tambah baru — password wajib
        if (!$password) $errors[] = 'Password wajib diisi untuk anggota baru.';
        elseif (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        elseif ($password !== $konfirmasi) $errors[] = 'Konfirmasi password tidak cocok.';
    } else {
        // Edit — password opsional, hanya diubah jika diisi
        if ($password && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if ($password && $password !== $konfirmasi) $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if ($errors) {
        $msg = ['type' => 'danger', 'text' => implode('<br>', $errors)];
    } else {
        // Generate no anggota otomatis jika kosong
        if (!$no_anggota) {
            $last = $db->query("SELECT MAX(id) FROM users")->fetchColumn();
            $no_anggota = 'MBR-' . str_pad(($last + 1), 4, '0', STR_PAD_LEFT);
        }

        if ($id) {
            // UPDATE
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET nama=?,email=?,password=?,role=?,status=?,no_anggota=? WHERE id=?")
                   ->execute([$nama, $email, $hash, $role, $status, $no_anggota, $id]);
            } else {
                $db->prepare("UPDATE users SET nama=?,email=?,role=?,status=?,no_anggota=? WHERE id=?")
                   ->execute([$nama, $email, $role, $status, $no_anggota, $id]);
            }
            $msg = ['type' => 'success', 'text' => "Data anggota <strong>$nama</strong> berhasil diperbarui."];
        } else {
            // INSERT
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (nama,email,password,role,status,no_anggota) VALUES (?,?,?,?,?,?)")
               ->execute([$nama, $email, $hash, $role, $status, $no_anggota]);
            $msg = ['type' => 'success', 'text' => "Anggota <strong>$nama</strong> berhasil ditambahkan. No: <strong>$no_anggota</strong>"];
        }
        $aksi = 'list';
    }
}

// ── LOAD DATA EDIT ─────────────────────────────────────
$editUser = null;
if ($aksi === 'edit' && $editId) {
    $s = $db->prepare("SELECT * FROM users WHERE id=?");
    $s->execute([$editId]);
    $editUser = $s->fetch();
    if (!$editUser) $aksi = 'list';
}

// ── PESAN DARI REDIRECT ────────────────────────────────
if (isset($_GET['msg'])) {
    $msgMap = ['hapus' => 'Anggota berhasil dihapus.'];
    if (isset($msgMap[$_GET['msg']])) $msg = ['type' => 'success', 'text' => $msgMap[$_GET['msg']]];
}

// ── LOAD LIST ANGGOTA ──────────────────────────────────
$search = trim($_GET['q'] ?? '');
$whereSearch = $search ? "WHERE (u.nama LIKE ? OR u.email LIKE ? OR u.no_anggota LIKE ?)" : '';
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$stmt = $db->prepare("
    SELECT u.*, COUNT(DISTINCT p.id) as total_pinjam,
           SUM(CASE WHEN p.status='dipinjam' THEN 1 ELSE 0 END) as sedang_pinjam
    FROM users u
    LEFT JOIN peminjaman p ON u.id=p.user_id
    $whereSearch
    GROUP BY u.id ORDER BY u.created_at DESC
");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anggota — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 200;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px;
            padding: 2rem; width: 100%; max-width: 520px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            animation: slideUp .2s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .modal-title {
            font-family: var(--font-display);
            font-size: 1.3rem; color: var(--teal-dark);
        }
        .modal-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.2rem; color: var(--text-muted);
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .modal-close:hover { background: var(--cream-dark); }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--teal-dark), var(--teal-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: .85rem;
            flex-shrink: 0;
        }
        .toggle-password {
            position: absolute; right: .75rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted);
        }
        .password-wrap { position: relative; }
        .hint { font-size: .78rem; color: var(--text-muted); margin-top: .3rem; }
        .stat-pills { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .pill {
            background: #fff; border-radius: 50px;
            padding: .45rem 1rem; font-size: .85rem; font-weight: 600;
            box-shadow: var(--shadow); display: flex; align-items: center; gap: .4rem;
        }
        .pill i { font-size: .9rem; }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand"><i class="fas fa-book-open"></i> <?= SITE_NAME ?></div>
        <nav class="sidebar-menu">
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="buku.php"><i class="fas fa-books"></i> Kelola Buku</a>
            <a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a>
            <a href="anggota.php" class="active"><i class="fas fa-users"></i> Anggota</a>
            <a href="peminjaman.php"><i class="fas fa-book-reader"></i> Peminjaman</a>
            <a href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-users"></i> Kelola Anggota</h1>
            <button class="btn btn-primary" onclick="bukaModal()">
                <i class="fas fa-user-plus"></i> Tambah Anggota
            </button>
        </div>

        <!-- STAT PILLS -->
        <?php
        $totalAnggota  = count(array_filter($users, fn($u) => $u['role']==='anggota'));
        $totalAktif    = count(array_filter($users, fn($u) => $u['status']==='aktif' && $u['role']==='anggota'));
        $totalNonaktif = count(array_filter($users, fn($u) => $u['status']==='nonaktif' && $u['role']==='anggota'));
        $totalPinjam   = array_sum(array_column($users, 'sedang_pinjam'));
        ?>
        <div class="stat-pills">
            <div class="pill"><i class="fas fa-users" style="color:var(--teal)"></i> <?= $totalAnggota ?> Anggota</div>
            <div class="pill"><i class="fas fa-check-circle" style="color:#1a7a4a"></i> <?= $totalAktif ?> Aktif</div>
            <div class="pill"><i class="fas fa-times-circle" style="color:#c0392b"></i> <?= $totalNonaktif ?> Nonaktif</div>
            <div class="pill"><i class="fas fa-book-reader" style="color:var(--amber)"></i> <?= $totalPinjam ?> Sedang Pinjam</div>
        </div>

        <!-- ALERT -->
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>" data-dismiss="1">
                <i class="fas fa-<?= $msg['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
                <?= $msg['text'] ?>
            </div>
        <?php endif; ?>

        <!-- SEARCH + FILTER -->
        <form method="GET" action="anggota.php" style="display:flex;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
            <input type="text" name="q" class="form-control" style="max-width:300px"
                   placeholder="🔍 Cari nama, email, no anggota..."
                   value="<?= sanitize($search) ?>">
            <button type="submit" class="btn btn-teal btn-sm"><i class="fas fa-search"></i> Cari</button>
            <?php if ($search): ?>
                <a href="anggota.php" class="btn btn-sm" style="background:var(--cream-dark);color:var(--text)">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </form>

        <!-- TABLE -->
        <div class="table-card">
            <?php if ($search): ?>
                <div style="padding:.75rem 1.5rem;background:var(--cream);border-bottom:1px solid var(--border);font-size:.88rem;color:var(--text-muted)">
                    Menampilkan <strong><?= count($users) ?></strong> hasil untuk "<em><?= sanitize($search) ?></em>"
                </div>
            <?php endif; ?>
            <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Anggota</th>
                        <th>No. Anggota</th>
                        <th>Role</th>
                        <th>Pinjam</th>
                        <th>Bergabung</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted)">
                            <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                            Tidak ada anggota ditemukan.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.75rem">
                                <div class="avatar"><?= strtoupper(substr($u['nama'], 0, 2)) ?></div>
                                <div>
                                    <div style="font-weight:600;color:var(--teal-dark)"><?= sanitize($u['nama']) ?></div>
                                    <div style="font-size:.8rem;color:var(--text-muted)"><?= sanitize($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:var(--cream-dark);padding:.2rem .5rem;border-radius:4px;font-size:.82rem">
                                <?= sanitize($u['no_anggota'] ?? '-') ?>
                            </code>
                        </td>
                        <td>
                            <span class="badge <?= $u['role']==='admin'?'badge-amber':'badge-blue' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td>
                            <span title="Total pinjam"><?= $u['total_pinjam'] ?> total</span>
                            <?php if ($u['sedang_pinjam'] > 0): ?>
                                <br><span class="badge badge-amber" style="font-size:.7rem"><?= $u['sedang_pinjam'] ?> aktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.85rem;color:var(--text-muted)">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td>
                            <span class="badge <?= $u['status']==='aktif'?'badge-green':'badge-red' ?>">
                                <?= $u['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <!-- Edit -->
                                <button class="btn btn-sm btn-edit"
                                    onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($u)) ?>)"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <!-- Toggle status -->
                                <a href="?toggle=<?= $u['id'] ?>"
                                   class="btn btn-sm <?= $u['status']==='aktif'?'btn-delete':'btn-edit' ?>"
                                   title="<?= $u['status']==='aktif'?'Nonaktifkan':'Aktifkan' ?>">
                                    <i class="fas fa-<?= $u['status']==='aktif'?'ban':'check' ?>"></i>
                                </a>
                                <!-- Hapus -->
                                <a href="?aksi=hapus&id=<?= $u['id'] ?>"
                                   class="btn btn-sm btn-delete"
                                   data-confirm="Hapus anggota '<?= addslashes($u['nama']) ?>'? Data peminjaman juga ikut terhapus."
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </main>
</div>

<!-- ═══════════════════════════════════════════
     MODAL TAMBAH / EDIT ANGGOTA
═══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalAnggota">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="modalJudul">
                <i class="fas fa-user-plus"></i> Tambah Anggota Baru
            </h3>
            <button class="modal-close" onclick="tutupModal()" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" id="formAnggota">
            <input type="hidden" name="id" id="fieldId" value="">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <!-- Nama -->
                <div class="form-group" style="grid-column:1/-1">
                    <label><i class="fas fa-user"></i> Nama Lengkap <span style="color:#e74c3c">*</span></label>
                    <input type="text" name="nama" id="fieldNama" class="form-control"
                           placeholder="Nama lengkap anggota" required>
                </div>

                <!-- Email -->
                <div class="form-group" style="grid-column:1/-1">
                    <label><i class="fas fa-envelope"></i> Email <span style="color:#e74c3c">*</span></label>
                    <input type="email" name="email" id="fieldEmail" class="form-control"
                           placeholder="email@contoh.com" required>
                </div>

                <!-- No Anggota -->
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> No. Anggota</label>
                    <input type="text" name="no_anggota" id="fieldNoAnggota" class="form-control"
                           placeholder="Otomatis jika dikosongkan">
                    <div class="hint">Kosongkan untuk generate otomatis</div>
                </div>

                <!-- Role -->
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Role</label>
                    <select name="role" id="fieldRole" class="form-control">
                        <option value="anggota">Anggota</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="form-group" style="grid-column:1/-1">
                    <label><i class="fas fa-toggle-on"></i> Status</label>
                    <select name="status" id="fieldStatus" class="form-control">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span id="labelPwRequired" style="color:#e74c3c">*</span></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="fieldPassword" class="form-control"
                               placeholder="Minimal 6 karakter">
                        <button type="button" class="toggle-password" onclick="togglePw('fieldPassword', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="hint" id="hintPassword">Wajib diisi untuk anggota baru.</div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                    <div class="password-wrap">
                        <input type="password" name="konfirmasi" id="fieldKonfirmasi" class="form-control"
                               placeholder="Ulangi password">
                        <button type="button" class="toggle-password" onclick="togglePw('fieldKonfirmasi', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tombol -->
            <div style="display:flex;gap:.75rem;margin-top:.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                    <i class="fas fa-save" id="iconSimpan"></i>
                    <span id="labelSimpan">Tambah Anggota</span>
                </button>
                <button type="button" class="btn" style="background:var(--cream-dark);color:var(--text)"
                        onclick="tutupModal()">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
const overlay = document.getElementById('modalAnggota');

function bukaModal() {
    // Reset form untuk mode Tambah
    document.getElementById('formAnggota').reset();
    document.getElementById('fieldId').value = '';
    document.getElementById('modalJudul').innerHTML = '<i class="fas fa-user-plus"></i> Tambah Anggota Baru';
    document.getElementById('labelSimpan').textContent = 'Tambah Anggota';
    document.getElementById('iconSimpan').className = 'fas fa-user-plus';
    document.getElementById('labelPwRequired').style.display = '';
    document.getElementById('hintPassword').textContent = 'Wajib diisi untuk anggota baru.';
    document.getElementById('fieldPassword').required = true;
    document.getElementById('fieldKonfirmasi').required = true;
    overlay.classList.add('open');
}

function bukaModalEdit(user) {
    document.getElementById('formAnggota').reset();
    document.getElementById('fieldId').value        = user.id;
    document.getElementById('fieldNama').value      = user.nama;
    document.getElementById('fieldEmail').value     = user.email;
    document.getElementById('fieldNoAnggota').value = user.no_anggota || '';
    document.getElementById('fieldRole').value      = user.role;
    document.getElementById('fieldStatus').value    = user.status;
    document.getElementById('fieldPassword').value  = '';
    document.getElementById('fieldKonfirmasi').value = '';

    document.getElementById('modalJudul').innerHTML = '<i class="fas fa-user-edit"></i> Edit Anggota';
    document.getElementById('labelSimpan').textContent = 'Simpan Perubahan';
    document.getElementById('iconSimpan').className = 'fas fa-save';
    document.getElementById('labelPwRequired').style.display = 'none';
    document.getElementById('hintPassword').textContent = 'Kosongkan jika tidak ingin mengubah password.';
    document.getElementById('fieldPassword').required = false;
    document.getElementById('fieldKonfirmasi').required = false;
    overlay.classList.add('open');
}

function tutupModal() {
    overlay.classList.remove('open');
}

// Tutup modal klik luar
overlay.addEventListener('click', function(e) {
    if (e.target === overlay) tutupModal();
});

// ESC untuk tutup
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupModal();
});

// Toggle show/hide password
function togglePw(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Buka modal otomatis jika ada error validasi dari server
<?php if ($msg && $msg['type'] === 'danger'): ?>
window.addEventListener('DOMContentLoaded', () => bukaModal());
<?php endif; ?>
</script>
</body>
</html>
