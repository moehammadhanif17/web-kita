<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

$totalBuku      = $db->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota   = $db->query("SELECT COUNT(*) FROM users WHERE role='anggota'")->fetchColumn();
$totalPinjam    = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$totalTerlambat = $db->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND tanggal_kembali < CURDATE()")->fetchColumn();

$recentBuku = $db->query("
    SELECT b.*, k.nama as nama_kategori FROM buku b
    LEFT JOIN kategori k ON b.kategori_id=k.id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

$recentPinjam = $db->query("
    SELECT p.*, b.judul, u.nama as user_nama
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id JOIN users u ON p.user_id=u.id
    ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-book-open"></i> <?= SITE_NAME ?>
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="buku.php"><i class="fas fa-books"></i> Kelola Buku</a>
            <a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a>
            <a href="anggota.php"><i class="fas fa-users"></i> Anggota</a>
            <a href="peminjaman.php"><i class="fas fa-book-reader"></i> Peminjaman</a>
            <a href="<?= SITE_URL ?>" style="margin-top:auto;border-top:1px solid rgba(255,255,255,.1)">
                <i class="fas fa-external-link-alt"></i> Lihat Website
            </a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <span style="color:var(--text-muted);font-size:.9rem">
                Selamat datang, <strong><?= sanitize($_SESSION['nama']) ?></strong>
            </span>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-books"></i></div>
                <div class="stat-info">
                    <div class="num"><?= number_format($totalBuku) ?></div>
                    <div class="label">Total Buku</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="num"><?= number_format($totalAnggota) ?></div>
                    <div class="label">Anggota</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-book-reader"></i></div>
                <div class="stat-info">
                    <div class="num"><?= number_format($totalPinjam) ?></div>
                    <div class="label">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="num"><?= number_format($totalTerlambat) ?></div>
                    <div class="label">Terlambat Kembali</div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <!-- Buku Terbaru -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3><i class="fas fa-books"></i> Buku Terbaru</h3>
                    <a href="buku.php" class="btn btn-teal btn-sm">Kelola</a>
                </div>
                <table class="data-table">
                    <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentBuku as $b): ?>
                        <tr>
                            <td style="max-width:150px">
                                <a href="../buku.php?slug=<?= urlencode($b['slug']) ?>" 
                                   style="font-weight:600;color:var(--teal-dark);font-size:.85rem">
                                    <?= sanitize(substr($b['judul'],0,30)) ?>
                                </a>
                            </td>
                            <td><span class="badge badge-blue" style="font-size:.72rem"><?= sanitize($b['nama_kategori'] ?? '-') ?></span></td>
                            <td><span class="badge <?= $b['status']==='tersedia'?'badge-green':'badge-red' ?>"><?= $b['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Peminjaman Terbaru -->
            <div class="table-card">
                <div class="table-card-header">
                    <h3><i class="fas fa-book-reader"></i> Peminjaman Terbaru</h3>
                    <a href="peminjaman.php" class="btn btn-teal btn-sm">Kelola</a>
                </div>
                <table class="data-table">
                    <thead><tr><th>Anggota</th><th>Buku</th><th>Kembali</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentPinjam as $p): ?>
                        <tr>
                            <td style="font-size:.85rem"><?= sanitize($p['user_nama']) ?></td>
                            <td style="font-size:.83rem;max-width:120px"><?= sanitize(substr($p['judul'],0,25)) ?></td>
                            <td>
                                <?php $late = strtotime($p['tanggal_kembali']) < time() && $p['status']==='dipinjam'; ?>
                                <span class="badge <?= $late?'badge-red':'badge-amber' ?>" style="font-size:.72rem">
                                    <?= $p['tanggal_kembali'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="table-card">
            <div class="table-card-header"><h3>Aksi Cepat</h3></div>
            <div style="padding:1.25rem;display:flex;gap:1rem;flex-wrap:wrap">
                <a href="buku.php?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Buku</a>
                <a href="kategori.php?aksi=tambah" class="btn btn-teal"><i class="fas fa-plus"></i> Tambah Kategori</a>
                <a href="anggota.php" class="btn" style="background:var(--cream-dark);color:var(--text)"><i class="fas fa-users"></i> Kelola Anggota</a>
            </div>
        </div>
    </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
