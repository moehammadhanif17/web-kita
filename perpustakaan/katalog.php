<?php
$pageTitle = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

// Filter params
$q         = isset($_GET['q'])        ? trim($_GET['q'])        : '';
$kategori  = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$sort      = isset($_GET['sort'])     ? trim($_GET['sort'])     : 'terbaru';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];
if ($q) {
    $where[] = "(b.judul LIKE ? OR b.pengarang LIKE ? OR b.isbn LIKE ?)";
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}
if ($kategori) {
    $where[] = "k.slug = ?";
    $params[] = $kategori;
}
$orderBy = match($sort) {
    'populer'  => 'b.views DESC',
    'download' => 'b.downloads DESC',
    'judul'    => 'b.judul ASC',
    default    => 'b.created_at DESC'
};
$whereStr = implode(' AND ', $where);

// Count
$stmtCount = $db->prepare("SELECT COUNT(*) FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE $whereStr");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

// Data
$stmt = $db->prepare("
    SELECT b.*, k.nama as nama_kategori, k.slug as kategori_slug
    FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id
    WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$bukuList = $stmt->fetchAll();

// Kategori list
$kategoriList = $db->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

// Build page URL helper
function pageUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}
?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container">
        <a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Beranda</a>
        <span class="sep">/</span>
        <span>Katalog Buku</span>
        <?php if ($q): ?><span class="sep">/</span><span>"<?= sanitize($q) ?>"</span><?php endif; ?>
    </div>
</div>

<div class="section">
<div class="container">
    <div class="section-header">
        <div>
            <h2 class="section-title">Katalog <span>Buku</span></h2>
            <div class="divider"></div>
        </div>
        <p style="color:var(--text-muted); font-size:.9rem">
            Menampilkan <strong><?= $total ?></strong> buku<?= $q ? " untuk \"<em>" . sanitize($q) . "</em>\"" : "" ?>
        </p>
    </div>

    <!-- FILTER BAR -->
    <form class="filter-bar" method="GET" action="katalog.php">
        <input type="text" name="q" placeholder="🔍 Cari judul, pengarang, ISBN..." value="<?= sanitize($q) ?>">
        <select name="kategori">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= htmlspecialchars($kat['slug']) ?>"
                    <?= $kategori === $kat['slug'] ? 'selected' : '' ?>>
                    <?= sanitize($kat['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sort">
            <option value="terbaru"  <?= $sort==='terbaru'  ? 'selected':'' ?>>Terbaru</option>
            <option value="populer"  <?= $sort==='populer'  ? 'selected':'' ?>>Paling Populer</option>
            <option value="download" <?= $sort==='download' ? 'selected':'' ?>>Paling Diunduh</option>
            <option value="judul"    <?= $sort==='judul'    ? 'selected':'' ?>>Judul A-Z</option>
        </select>
        <button type="submit" class="btn btn-teal"><i class="fas fa-filter"></i> Filter</button>
        <a href="katalog.php" class="btn" style="background:var(--cream-dark);color:var(--text-muted)">Reset</a>
    </form>

    <!-- BOOK GRID -->
    <?php if (empty($bukuList)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Tidak Ditemukan</h3>
            <p>Buku yang Anda cari tidak ada. Coba kata kunci lain.</p>
            <a href="katalog.php" class="btn btn-teal" style="margin-top:1rem">Lihat Semua Buku</a>
        </div>
    <?php else: ?>
        <div class="book-grid">
            <?php foreach ($bukuList as $buku): ?>
            <div class="book-card">
                <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="book-cover">
                    <?php if ($buku['cover_url']): ?>
                        <img src="<?= htmlspecialchars($buku['cover_url']) ?>" alt="<?= sanitize($buku['judul']) ?>"
                             onerror="this.parentElement.querySelector('.book-cover-placeholder')?.style.setProperty('display','flex'); this.remove()">
                    <?php endif; ?>
                    <div class="book-cover-placeholder" <?= $buku['cover_url'] ? 'style="display:none"' : '' ?>>
                        <i class="fas fa-book"></i>
                        <span><?= sanitize(substr($buku['judul'], 0, 30)) ?></span>
                    </div>
                    <span class="book-badge"><?= $buku['status'] === 'tersedia' ? 'Tersedia' : 'Habis' ?></span>
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
                        <a href="buku.php?slug=<?= urlencode($buku['slug']) ?>" class="btn-book">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= pageUrl($page-1) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1) { echo '<a href="'.pageUrl(1).'">1</a>'; if ($start > 2) echo '<span>…</span>'; }
            for ($i = $start; $i <= $end; $i++) {
                $cls = $i === $page ? ' class="active"' : '';
                echo "<a href=\"".pageUrl($i)."\"$cls>$i</a>";
            }
            if ($end < $totalPages) { if ($end < $totalPages-1) echo '<span>…</span>'; echo '<a href="'.pageUrl($totalPages).'">'.$totalPages.'</a>'; }
            ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= pageUrl($page+1) ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
