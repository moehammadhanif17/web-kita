<?php
// admin/peminjaman.php
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

// Kembalikan buku
if (isset($_GET['kembali']) && (int)$_GET['kembali']) {
    $db->prepare("UPDATE peminjaman SET status='dikembalikan', tanggal_dikembalikan=CURDATE() WHERE id=?")
       ->execute([(int)$_GET['kembali']]);
    redirect(SITE_URL . '/admin/peminjaman.php');
}

$filter = $_GET['filter'] ?? 'semua';
$where  = $filter === 'dipinjam' ? "WHERE p.status='dipinjam'" : ($filter === 'terlambat' ? "WHERE p.status='dipinjam' AND p.tanggal_kembali < CURDATE()" : '');

$list = $db->query("
    SELECT p.*, b.judul, b.slug, u.nama as user_nama, u.no_anggota
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id JOIN users u ON p.user_id=u.id
    $where ORDER BY p.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman — <?= SITE_NAME ?></title>
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
            <a href="anggota.php"><i class="fas fa-users"></i> Anggota</a>
            <a href="peminjaman.php" class="active"><i class="fas fa-book-reader"></i> Peminjaman</a>
            <a href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-book-reader"></i> Manajemen Peminjaman</h1>
        </div>
        <!-- Filter -->
        <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
            <?php foreach (['semua'=>'Semua','dipinjam'=>'Sedang Dipinjam','terlambat'=>'Terlambat'] as $k=>$v): ?>
                <a href="?filter=<?= $k ?>" class="btn btn-sm <?= $filter===$k?'btn-teal':'' ?>"
                   style="<?= $filter!==$k?'background:var(--cream-dark);color:var(--text)':'' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Anggota</th><th>Buku</th><th>Tgl Pinjam</th><th>Batas Kembali</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $i => $p): ?>
                    <?php $late = $p['status']==='dipinjam' && strtotime($p['tanggal_kembali']) < time(); ?>
                    <tr <?= $late ? 'style="background:#fff8f8"' : '' ?>>
                        <td><?= $i+1 ?></td>
                        <td>
                            <strong><?= sanitize($p['user_nama']) ?></strong><br>
                            <small style="color:var(--text-muted)"><?= sanitize($p['no_anggota'] ?? '') ?></small>
                        </td>
                        <td style="max-width:160px"><?= sanitize($p['judul']) ?></td>
                        <td><?= $p['tanggal_pinjam'] ?></td>
                        <td><?= $p['tanggal_kembali'] ?><?= $late ? ' <span class="badge badge-red">Terlambat!</span>' : '' ?></td>
                        <td>
                            <span class="badge <?= $p['status']==='dikembalikan'?'badge-green':($late?'badge-red':'badge-amber') ?>">
                                <?= $p['status']==='dikembalikan' ? 'Dikembalikan' : ($late ? 'Terlambat' : 'Dipinjam') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['status'] === 'dipinjam'): ?>
                                <a href="?kembali=<?= $p['id'] ?>" class="btn btn-sm btn-edit"
                                   data-confirm="Tandai buku '<?= addslashes(substr($p['judul'],0,30)) ?>' sudah dikembalikan?">
                                    <i class="fas fa-check"></i> Kembalikan
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:.82rem"><?= $p['tanggal_dikembalikan'] ?></span>
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
