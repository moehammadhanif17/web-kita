<?php
$pageTitle = 'Kategori Buku';
require_once __DIR__ . '/includes/header.php';
$db = getDB();
$kategoriList = $db->query("
    SELECT k.*, COUNT(b.id) as jumlah_buku
    FROM kategori k LEFT JOIN buku b ON k.id=b.kategori_id
    GROUP BY k.id ORDER BY jumlah_buku DESC
")->fetchAll();
?>
<div class="breadcrumb">
    <div class="container">
        <a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Beranda</a>
        <span class="sep">/</span><span>Kategori</span>
    </div>
</div>
<div class="section">
<div class="container">
    <div class="section-header">
        <div><h2 class="section-title">Semua <span>Kategori</span></h2><div class="divider"></div></div>
    </div>
    <div class="kategori-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
        <?php foreach ($kategoriList as $kat): ?>
        <a href="katalog.php?kategori=<?= urlencode($kat['slug']) ?>" class="kategori-card">
            <div class="kategori-icon">
                <i class="fas fa-<?= htmlspecialchars($kat['ikon'] ?? 'book') ?>"></i>
            </div>
            <div class="kategori-name"><?= sanitize($kat['nama']) ?></div>
            <div class="kategori-count"><?= $kat['jumlah_buku'] ?> buku</div>
            <?php if ($kat['deskripsi']): ?>
                <p style="font-size:.8rem;color:var(--text-muted);margin-top:.5rem;line-height:1.4">
                    <?= sanitize(substr($kat['deskripsi'], 0, 80)) ?>
                </p>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
