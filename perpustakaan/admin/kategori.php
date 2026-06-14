<?php
// admin/kategori.php
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

$msg = '';
$aksi  = $_GET['aksi'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

if ($aksi === 'hapus' && $editId) {
    $db->prepare("DELETE FROM kategori WHERE id=?")->execute([$editId]);
    redirect(SITE_URL . '/admin/kategori.php?msg=hapus');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $nama  = trim($_POST['nama'] ?? '');
    $slug  = slugify($nama);
    $desk  = trim($_POST['deskripsi'] ?? '');
    $ikon  = trim($_POST['ikon'] ?? 'book');
    if ($id) {
        $db->prepare("UPDATE kategori SET nama=?,slug=?,deskripsi=?,ikon=? WHERE id=?")->execute([$nama,$slug,$desk,$ikon,$id]);
        $msg = ['type'=>'success','text'=>'Kategori diperbarui.'];
    } else {
        $db->prepare("INSERT INTO kategori (nama,slug,deskripsi,ikon) VALUES (?,?,?,?)")->execute([$nama,$slug,$desk,$ikon]);
        $msg = ['type'=>'success','text'=>'Kategori ditambahkan.'];
    }
    $aksi = 'list';
}

$editKat = null;
if ($aksi === 'edit' && $editId) {
    $s = $db->prepare("SELECT * FROM kategori WHERE id=?"); $s->execute([$editId]); $editKat = $s->fetch();
}

$katList = $db->query("SELECT k.*, COUNT(b.id) as jumlah_buku FROM kategori k LEFT JOIN buku b ON k.id=b.kategori_id GROUP BY k.id ORDER BY k.nama")->fetchAll();

if (isset($_GET['msg']) && $_GET['msg']==='hapus') $msg = ['type'=>'success','text'=>'Kategori dihapus.'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori — <?= SITE_NAME ?></title>
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
            <a href="kategori.php" class="active"><i class="fas fa-tags"></i> Kategori</a>
            <a href="anggota.php"><i class="fas fa-users"></i> Anggota</a>
            <a href="peminjaman.php"><i class="fas fa-book-reader"></i> Peminjaman</a>
            <a href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-tags"></i> Kelola Kategori</h1>
            <?php if ($aksi === 'list'): ?>
                <a href="?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Kategori</a>
            <?php else: ?>
                <a href="kategori.php" class="btn" style="background:var(--cream-dark);color:var(--text)"><i class="fas fa-arrow-left"></i> Kembali</a>
            <?php endif; ?>
        </div>
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>" data-dismiss="1"><i class="fas fa-check-circle"></i> <?= $msg['text'] ?></div>
        <?php endif; ?>

        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>
        <div class="table-card">
            <div class="table-card-header"><h3><?= $aksi==='edit'?'Edit Kategori':'Tambah Kategori' ?></h3></div>
            <div style="padding:1.5rem;max-width:500px">
                <form method="POST">
                    <?php if ($editKat): ?><input type="hidden" name="id" value="<?= $editKat['id'] ?>"><?php endif; ?>
                    <div class="form-group">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= sanitize($editKat['nama'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ikon (Font Awesome, contoh: book, flask, landmark)</label>
                        <input type="text" name="ikon" class="form-control"
                               value="<?= sanitize($editKat['ikon'] ?? 'book') ?>">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= sanitize($editKat['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="table-card">
            <table class="data-table">
                <thead><tr><th>#</th><th>Nama</th><th>Slug</th><th>Jumlah Buku</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($katList as $i => $k): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><i class="fas fa-<?= htmlspecialchars($k['ikon']) ?>"></i> <strong><?= sanitize($k['nama']) ?></strong></td>
                        <td><code><?= sanitize($k['slug']) ?></code></td>
                        <td><?= $k['jumlah_buku'] ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="?aksi=edit&id=<?= $k['id'] ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?aksi=hapus&id=<?= $k['id'] ?>" class="btn btn-sm btn-delete"
                                   data-confirm="Hapus kategori '<?= addslashes($k['nama']) ?>'?"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
