<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

// Statistik
$totalBuku     = $db->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota  = $db->query("SELECT COUNT(*) FROM users WHERE role='anggota'")->fetchColumn();
$totalKategori = $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$totalDownload = $db->query("SELECT SUM(downloads) FROM buku")->fetchColumn() ?: 0;

// Buku terbaru
$bukuTerbaru = $db->query("
    SELECT b.*, k.nama as nama_kategori 
    FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id
    ORDER BY b.created_at DESC LIMIT 8
")->fetchAll();

// Buku populer
$bukuPopuler = $db->query("
    SELECT b.*, k.nama as nama_kategori
    FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id
    ORDER BY b.views DESC LIMIT 4
")->fetchAll();

// Kategori
$kategoriList = $db->query("
    SELECT k.*, COUNT(b.id) as jumlah_buku
    FROM kategori k LEFT JOIN buku b ON k.id=b.kategori_id
    GROUP BY k.id ORDER BY jumlah_buku DESC
")->fetchAll();
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-content">
        <h1>Selamat Datang di <span><?= SITE_NAME ?></span></h1>
        <p>Akses ribuan koleksi buku digital berkualitas. Baca, pinjam, dan unduh kapan saja dan di mana saja.</p>
        <div class="hero-actions">
            <a href="katalog.php" class="btn btn-primary"><i class="fas fa-search"></i> Jelajahi Katalog</a>
            <?php if (!isLoggedIn()): ?>
                <a href="daftar.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Daftar Gratis</a>
            <?php else: ?>
                <a href="peminjaman.php" class="btn btn-outline"><i class="fas fa-book-reader"></i> Pinjaman Saya</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <div class="stat-num"><?= number_format($totalBuku) ?>+</div>
                <div class="stat-label">Koleksi Buku</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= number_format($totalAnggota) ?>+</div>
                <div class="stat-label">Anggota</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= $totalKategori ?></div>
                <div class="stat-label">Kategori</div>
            </div>
            <div class="stat">
                <div class="stat-num"><?= number_format($totalDownload) ?>+</div>
                <div class="stat-label">Unduhan</div>
            </div>
        </div>
    </div>
</section>

<!-- KATEGORI -->
<section class="section" style="background:white;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Jelajahi <span>Kategori</span></h2>
                <div class="divider"></div>
            </div>
            <a href="kategori.php" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="kategori-grid">
            <?php foreach ($kategoriList as $kat): ?>
            <a href="katalog.php?kategori=<?= urlencode($kat['slug']) ?>" class="kategori-card">
                <div class="kategori-icon">
                    <i class="fas fa-<?= htmlspecialchars($kat['ikon'] ?? 'book') ?>"></i>
                </div>
                <div class="kategori-name"><?= sanitize($kat['nama']) ?></div>
                <div class="kategori-count"><?= $kat['jumlah_buku'] ?> buku</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BUKU TERBARU -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Buku <span>Terbaru</span></h2>
                <div class="divider"></div>
            </div>
            <a href="katalog.php?sort=terbaru" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if (empty($bukuTerbaru)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>Belum ada buku</h3>
                <p>Koleksi buku sedang dalam proses penambahan.</p>
            </div>
        <?php else: ?>
        <div class="book-grid">
            <?php foreach ($bukuTerbaru as $buku): ?>
            <div class="book-card">
                <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="book-cover">
                    <?php if ($buku['cover_url']): ?>
                        <img src="<?= htmlspecialchars($buku['cover_url']) ?>" 
                             alt="<?= sanitize($buku['judul']) ?>"
                             onerror="this.parentElement.innerHTML='<div class=book-cover-placeholder><i class=\'fas fa-book\'></i><span><?= addslashes(substr($buku['judul'],0,20)) ?></span></div>'">
                    <?php else: ?>
                        <div class="book-cover-placeholder">
                            <i class="fas fa-book"></i>
                            <span><?= sanitize(substr($buku['judul'], 0, 25)) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($buku['status'] === 'tersedia'): ?>
                        <span class="book-badge">Tersedia</span>
                    <?php endif; ?>
                </a>
                <div class="book-info">
                    <?php if ($buku['nama_kategori']): ?>
                        <div class="book-category"><?= sanitize($buku['nama_kategori']) ?></div>
                    <?php endif; ?>
                    <div class="book-title">
                        <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>"><?= sanitize($buku['judul']) ?></a>
                    </div>
                    <div class="book-author"><i class="fas fa-user-pen"></i> <?= sanitize($buku['pengarang']) ?></div>
                    <div class="book-footer">
                        <div class="book-stats">
                            <span><i class="fas fa-eye"></i> <?= number_format($buku['views']) ?></span>
                            <span><i class="fas fa-download"></i> <?= number_format($buku['downloads']) ?></span>
                        </div>
                        <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="btn-book">Baca</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- BUKU POPULER -->
<section class="section" style="background:white;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Paling <span>Banyak Dibaca</span></h2>
                <div class="divider"></div>
            </div>
        </div>
        <div class="book-grid">
            <?php foreach ($bukuPopuler as $buku): ?>
            <div class="book-card">
                <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="book-cover">
                    <?php if ($buku['cover_url']): ?>
                        <img src="<?= htmlspecialchars($buku['cover_url']) ?>" alt="<?= sanitize($buku['judul']) ?>"
                             onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="book-cover-placeholder">
                            <i class="fas fa-book"></i>
                            <span><?= sanitize(substr($buku['judul'], 0, 25)) ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <div class="book-info">
                    <?php if ($buku['nama_kategori']): ?>
                        <div class="book-category"><?= sanitize($buku['nama_kategori']) ?></div>
                    <?php endif; ?>
                    <div class="book-title">
                        <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>"><?= sanitize($buku['judul']) ?></a>
                    </div>
                    <div class="book-author"><i class="fas fa-user-pen"></i> <?= sanitize($buku['pengarang']) ?></div>
                    <div class="book-footer">
                        <div class="book-stats">
                            <span><i class="fas fa-eye"></i> <?= number_format($buku['views']) ?></span>
                        </div>
                        <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="btn-book">Baca</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
