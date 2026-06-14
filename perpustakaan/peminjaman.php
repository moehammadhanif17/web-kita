<?php
$pageTitle = 'Riwayat Peminjaman';
require_once __DIR__ . '/includes/header.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, b.judul, b.slug, b.cover_url, b.pengarang
    FROM peminjaman p JOIN buku b ON p.buku_id=b.id
    WHERE p.user_id=? ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$list = $stmt->fetchAll();
?>

<div class="breadcrumb">
    <div class="container">
        <a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Beranda</a>
        <span class="sep">/</span><span>Riwayat Peminjaman</span>
    </div>
</div>

<div class="section">
<div class="container">
    <div class="section-header">
        <div><h2 class="section-title">Riwayat <span>Peminjaman</span></h2><div class="divider"></div></div>
    </div>
    <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="fas fa-book-reader"></i>
            <h3>Belum Ada Peminjaman</h3>
            <p>Anda belum meminjam buku apapun.</p>
            <a href="katalog.php" class="btn btn-teal" style="margin-top:1rem">Jelajahi Katalog</a>
        </div>
    <?php else: ?>
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Batas Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $p): ?>
                    <tr>
                        <td>
                            <a href="buku.php?slug=<?= urlencode($p['slug']) ?>" style="font-weight:600;color:var(--teal-dark)">
                                <?= sanitize($p['judul']) ?>
                            </a>
                            <br><small style="color:var(--text-muted)"><?= sanitize($p['pengarang']) ?></small>
                        </td>
                        <td><?= $p['tanggal_pinjam'] ?></td>
                        <td><?= $p['tanggal_kembali'] ?></td>
                        <td>
                            <?php
                            $late = $p['status'] === 'dipinjam' && strtotime($p['tanggal_kembali']) < time();
                            $badgeClass = $late ? 'badge-red' : ($p['status']==='dikembalikan' ? 'badge-green' : 'badge-amber');
                            $label = $late ? 'Terlambat' : ($p['status']==='dikembalikan' ? 'Dikembalikan' : 'Dipinjam');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $label ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
