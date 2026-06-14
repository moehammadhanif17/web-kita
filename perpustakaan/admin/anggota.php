<?php
// admin/anggota.php — Kelola Anggota
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

$users = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as total_pinjam
    FROM users u
    LEFT JOIN peminjaman p ON u.id=p.user_id
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetchAll();

// Toggle status
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Anggota — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
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
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-users"></i> Kelola Anggota</h1>
        </div>
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Nama</th><th>Email</th><th>No. Anggota</th><th>Role</th><th>Total Pinjam</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td style="font-weight:600"><?= sanitize($u['nama']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><?= sanitize($u['no_anggota'] ?? '-') ?></td>
                        <td><span class="badge <?= $u['role']==='admin'?'badge-amber':'badge-blue' ?>"><?= $u['role'] ?></span></td>
                        <td><?= $u['total_pinjam'] ?></td>
                        <td><span class="badge <?= $u['status']==='aktif'?'badge-green':'badge-red' ?>"><?= $u['status'] ?></span></td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                            <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['status']==='aktif'?'btn-delete':'btn-edit' ?>">
                                <?= $u['status']==='aktif' ? '<i class="fas fa-ban"></i> Nonaktifkan' : '<i class="fas fa-check"></i> Aktifkan' ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
