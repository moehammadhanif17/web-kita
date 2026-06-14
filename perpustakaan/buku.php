<?php
require_once __DIR__ . '/includes/header.php';
$db = getDB();

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (!$slug) redirect(SITE_URL . '/katalog.php');

$stmt = $db->prepare("
    SELECT b.*, k.nama as nama_kategori, k.slug as kategori_slug
    FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id
    WHERE b.slug = ?
");
$stmt->execute([$slug]);
$buku = $stmt->fetch();
if (!$buku) {
    http_response_code(404);
    echo '<div class="container" style="padding:5rem 1rem;text-align:center"><h1>Buku Tidak Ditemukan</h1><a href="katalog.php" class="btn btn-teal" style="margin-top:1rem">Kembali ke Katalog</a></div>';
    require_once __DIR__ . '/includes/footer.php'; exit;
}

$pageTitle = $buku['judul'];

// Increment views
$db->prepare("UPDATE buku SET views = views + 1 WHERE id = ?")->execute([$buku['id']]);

// Handle borrow
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinjam'])) {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    $userId   = $_SESSION['user_id'];
    $tglPinjam = date('Y-m-d');
    $tglKembali = date('Y-m-d', strtotime('+14 days'));
    // Check already borrowed
    $check = $db->prepare("SELECT id FROM peminjaman WHERE user_id=? AND buku_id=? AND status='dipinjam'");
    $check->execute([$userId, $buku['id']]);
    if ($check->fetch()) {
        $msg = ['type'=>'info', 'text'=>'Anda sudah meminjam buku ini.'];
    } else {
        $ins = $db->prepare("INSERT INTO peminjaman (user_id,buku_id,tanggal_pinjam,tanggal_kembali) VALUES (?,?,?,?)");
        $ins->execute([$userId, $buku['id'], $tglPinjam, $tglKembali]);
        $msg = ['type'=>'success', 'text'=>"Berhasil dipinjam! Batas kembali: $tglKembali"];
    }
}

// Handle download
if (isset($_GET['download'])) {
    if (!isLoggedIn()) redirect(SITE_URL . '/login.php');
    $db->prepare("UPDATE buku SET downloads = downloads + 1 WHERE id = ?")->execute([$buku['id']]);
    if ($buku['file_url']) {
        header("Location: " . $buku['file_url']); exit;
    }
    $msg = ['type'=>'info', 'text'=>'File unduhan belum tersedia untuk buku ini.'];
}

// Reviews
$ulasan = $db->prepare("
    SELECT u.*, us.nama as user_nama
    FROM ulasan u JOIN users us ON u.user_id=us.id
    WHERE u.buku_id=? ORDER BY u.created_at DESC LIMIT 10
");
$ulasan->execute([$buku['id']]);
$reviews = $ulasan->fetchAll();

$avgRating = $db->prepare("SELECT AVG(rating) FROM ulasan WHERE buku_id=?");
$avgRating->execute([$buku['id']]);
$avg = round($avgRating->fetchColumn(), 1);

// Handle review submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan']) && isLoggedIn()) {
    $rating   = (int)($_POST['rating'] ?? 5);
    $komentar = trim($_POST['komentar'] ?? '');
    $check = $db->prepare("SELECT id FROM ulasan WHERE user_id=? AND buku_id=?");
    $check->execute([$_SESSION['user_id'], $buku['id']]);
    if ($check->fetch()) {
        $db->prepare("UPDATE ulasan SET rating=?, komentar=? WHERE user_id=? AND buku_id=?")
           ->execute([$rating, $komentar, $_SESSION['user_id'], $buku['id']]);
    } else {
        $db->prepare("INSERT INTO ulasan (user_id,buku_id,rating,komentar) VALUES (?,?,?,?)")
           ->execute([$_SESSION['user_id'], $buku['id'], $rating, $komentar]);
    }
    redirect($_SERVER['REQUEST_URI']);
}

// Related books
$related = $db->prepare("
    SELECT b.*, k.nama as nama_kategori FROM buku b
    LEFT JOIN kategori k ON b.kategori_id=k.id
    WHERE b.kategori_id=? AND b.id!=? LIMIT 4
");
$related->execute([$buku['kategori_id'], $buku['id']]);
$relatedBooks = $related->fetchAll();
?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <div class="container">
        <a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Beranda</a>
        <span class="sep">/</span>
        <a href="katalog.php">Katalog</a>
        <?php if ($buku['nama_kategori']): ?>
            <span class="sep">/</span>
            <a href="katalog.php?kategori=<?= urlencode($buku['kategori_slug']) ?>"><?= sanitize($buku['nama_kategori']) ?></a>
        <?php endif; ?>
        <span class="sep">/</span>
        <span><?= sanitize(substr($buku['judul'], 0, 40)) ?></span>
    </div>
</div>

<div class="section">
<div class="container">
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?>" data-dismiss="1">
            <i class="fas fa-<?= $msg['type']==='success'?'check-circle':($msg['type']==='info'?'info-circle':'exclamation-circle') ?>"></i>
            <?= sanitize($msg['text']) ?>
        </div>
    <?php endif; ?>

    <div class="detail-grid">
        <!-- COVER -->
        <div>
            <div class="detail-cover">
                <?php if ($buku['cover_url']): ?>
                    <img src="<?= htmlspecialchars($buku['cover_url']) ?>" alt="<?= sanitize($buku['judul']) ?>">
                <?php else: ?>
                    <div class="book-cover-placeholder" style="padding:2rem;text-align:center;color:rgba(255,255,255,.7)">
                        <i class="fas fa-book" style="font-size:5rem;display:block;margin-bottom:1rem"></i>
                        <?= sanitize($buku['judul']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Actions below cover -->
            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.6rem">
                <?php if ($buku['status'] === 'tersedia'): ?>
                    <form method="POST">
                        <button type="submit" name="pinjam" class="btn btn-primary btn-full">
                            <i class="fas fa-book-reader"></i> Pinjam Buku
                        </button>
                    </form>
                    <?php if ($buku['file_url']): ?>
                        <a href="?slug=<?= urlencode($slug) ?>&download=1" class="btn btn-teal btn-full">
                            <i class="fas fa-download"></i> Unduh PDF
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="btn btn-full" style="background:var(--cream-dark);color:var(--text-muted);cursor:not-allowed">
                        <i class="fas fa-times-circle"></i> Tidak Tersedia
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- INFO -->
        <div>
            <?php if ($buku['nama_kategori']): ?>
                <div class="book-category" style="font-size:.85rem;margin-bottom:.5rem">
                    <a href="katalog.php?kategori=<?= urlencode($buku['kategori_slug']) ?>"><?= sanitize($buku['nama_kategori']) ?></a>
                </div>
            <?php endif; ?>
            <h1 style="font-family:var(--font-display);font-size:2rem;color:var(--teal-dark);margin-bottom:.75rem">
                <?= sanitize($buku['judul']) ?>
            </h1>
            <div class="detail-meta">
                <span><i class="fas fa-user-pen"></i> <?= sanitize($buku['pengarang']) ?></span>
                <?php if ($buku['penerbit']): ?><span><i class="fas fa-building"></i> <?= sanitize($buku['penerbit']) ?></span><?php endif; ?>
                <?php if ($buku['tahun_terbit']): ?><span><i class="fas fa-calendar"></i> <?= $buku['tahun_terbit'] ?></span><?php endif; ?>
                <?php if ($avg > 0): ?>
                    <span>
                        <i class="fas fa-star" style="color:var(--amber)"></i>
                        <?= $avg ?>/5 (<?= count($reviews) ?> ulasan)
                    </span>
                <?php endif; ?>
                <span><i class="fas fa-eye"></i> <?= number_format($buku['views']) ?> dilihat</span>
                <span><i class="fas fa-download"></i> <?= number_format($buku['downloads']) ?> diunduh</span>
            </div>

            <?php if ($buku['deskripsi']): ?>
                <p class="detail-desc"><?= nl2br(sanitize($buku['deskripsi'])) ?></p>
            <?php endif; ?>

            <div class="detail-specs">
                <?php if ($buku['isbn']): ?>
                    <div class="spec-item"><div class="spec-label">ISBN</div><div class="spec-value"><?= sanitize($buku['isbn']) ?></div></div>
                <?php endif; ?>
                <?php if ($buku['halaman']): ?>
                    <div class="spec-item"><div class="spec-label">Halaman</div><div class="spec-value"><?= $buku['halaman'] ?></div></div>
                <?php endif; ?>
                <div class="spec-item"><div class="spec-label">Bahasa</div><div class="spec-value"><?= sanitize($buku['bahasa']) ?></div></div>
                <div class="spec-item">
                    <div class="spec-label">Status</div>
                    <div class="spec-value">
                        <span class="badge <?= $buku['status']==='tersedia'?'badge-green':'badge-red' ?>">
                            <?= $buku['status'] === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ULASAN -->
    <div style="margin-top:3rem">
        <h3 style="font-family:var(--font-display);font-size:1.4rem;color:var(--teal-dark);margin-bottom:1.5rem">
            Ulasan Pembaca
        </h3>

        <?php if (isLoggedIn()): ?>
        <div style="background:#fff;border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow);margin-bottom:1.5rem">
            <h4 style="margin-bottom:1rem;color:var(--teal-dark)">Tulis Ulasan</h4>
            <form method="POST">
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" class="form-control" style="max-width:200px">
                        <?php for ($i=5;$i>=1;$i--): ?>
                            <option value="<?= $i ?>"><?= str_repeat('★',$i) . str_repeat('☆',5-$i) ?> (<?= $i ?>)</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Komentar</label>
                    <textarea name="komentar" class="form-control" rows="3" placeholder="Bagikan pendapat Anda tentang buku ini..."></textarea>
                </div>
                <button type="submit" name="kirim_ulasan" class="btn btn-teal">
                    <i class="fas fa-paper-plane"></i> Kirim Ulasan
                </button>
            </form>
        </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:1.5rem">
                <i class="fas fa-info-circle"></i>
                <a href="login.php" style="font-weight:700">Masuk</a> untuk memberikan ulasan.
            </div>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <p style="color:var(--text-muted)">Belum ada ulasan. Jadilah yang pertama!</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
            <div style="background:#fff;border-radius:var(--radius-sm);padding:1.25rem;box-shadow:var(--shadow);margin-bottom:1rem">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <strong style="color:var(--teal-dark)"><?= sanitize($rev['user_nama']) ?></strong>
                    <span style="color:var(--text-muted);font-size:.82rem"><?= timeAgo($rev['created_at']) ?></span>
                </div>
                <div class="stars" style="margin-bottom:.4rem">
                    <?php for ($i=1;$i<=5;$i++) echo $i<=$rev['rating']?'★':'☆'; ?>
                </div>
                <?php if ($rev['komentar']): ?>
                    <p style="font-size:.9rem;color:var(--text)"><?= sanitize($rev['komentar']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- RELATED -->
    <?php if (!empty($relatedBooks)): ?>
    <div style="margin-top:3rem">
        <h3 style="font-family:var(--font-display);font-size:1.4rem;color:var(--teal-dark);margin-bottom:1.5rem">
            Buku Serupa
        </h3>
        <div class="book-grid">
            <?php foreach ($relatedBooks as $rb): ?>
            <div class="book-card">
                <a href="buku.php?slug=<?= urlencode($rb['slug']) ?>" class="book-cover">
                    <?php if ($rb['cover_url']): ?>
                        <img src="<?= htmlspecialchars($rb['cover_url']) ?>" alt="<?= sanitize($rb['judul']) ?>">
                    <?php else: ?>
                        <div class="book-cover-placeholder"><i class="fas fa-book"></i></div>
                    <?php endif; ?>
                </a>
                <div class="book-info">
                    <div class="book-title"><a href="buku.php?slug=<?= urlencode($rb['slug']) ?>"><?= sanitize($rb['judul']) ?></a></div>
                    <div class="book-author"><?= sanitize($rb['pengarang']) ?></div>
                    <div class="book-footer">
                        <div class="book-stats"><span><i class="fas fa-eye"></i> <?= number_format($rb['views']) ?></span></div>
                        <a href="buku.php?slug=<?= urlencode($rb['slug']) ?>" class="btn-book">Detail</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
