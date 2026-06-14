<?php
require_once __DIR__ . '/../includes/config.php';
if (!isAdmin()) redirect(SITE_URL . '/login.php');
$db = getDB();

$msg = '';
$aksi = $_GET['aksi'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// DELETE
if ($aksi === 'hapus' && $editId) {
    $db->prepare("DELETE FROM buku WHERE id=?")->execute([$editId]);
    redirect(SITE_URL . '/admin/buku.php?msg=hapus');
}

// SAVE (tambah/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)($_POST['id'] ?? 0);
    $judul      = trim($_POST['judul'] ?? '');
    $pengarang  = trim($_POST['pengarang'] ?? '');
    $penerbit   = trim($_POST['penerbit'] ?? '');
    $tahun      = (int)($_POST['tahun_terbit'] ?? 0);
    $isbn       = trim($_POST['isbn'] ?? '');
    $kat_id     = (int)($_POST['kategori_id'] ?? 0);
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $cover_url  = trim($_POST['cover_url'] ?? '');
    $file_url   = trim($_POST['file_url'] ?? '');
    $halaman    = (int)($_POST['halaman'] ?? 0);
    $bahasa     = trim($_POST['bahasa'] ?? 'Indonesia');
    $status     = $_POST['status'] ?? 'tersedia';
    $slug       = slugify($judul);

    if ($id) {
        // Check slug conflict
        $check = $db->prepare("SELECT id FROM buku WHERE slug=? AND id!=?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) $slug .= '-' . $id;
        $db->prepare("UPDATE buku SET judul=?,slug=?,pengarang=?,penerbit=?,tahun_terbit=?,isbn=?,kategori_id=?,deskripsi=?,cover_url=?,file_url=?,halaman=?,bahasa=?,status=? WHERE id=?")
           ->execute([$judul,$slug,$pengarang,$penerbit,$tahun?$tahun:null,$isbn,$kat_id?$kat_id:null,$deskripsi,$cover_url,$file_url,$halaman,$bahasa,$status,$id]);
        $msg = ['type'=>'success','text'=>'Buku berhasil diperbarui.'];
    } else {
        $check = $db->prepare("SELECT id FROM buku WHERE slug=?");
        $check->execute([$slug]);
        if ($check->fetch()) $slug .= '-' . time();
        $db->prepare("INSERT INTO buku (judul,slug,pengarang,penerbit,tahun_terbit,isbn,kategori_id,deskripsi,cover_url,file_url,halaman,bahasa,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([$judul,$slug,$pengarang,$penerbit,$tahun?$tahun:null,$isbn,$kat_id?$kat_id:null,$deskripsi,$cover_url,$file_url,$halaman,$bahasa,$status]);
        $msg = ['type'=>'success','text'=>'Buku berhasil ditambahkan.'];
    }
    $aksi = 'list';
}

$kategoriList = $db->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
$bukuEdit = null;
if ($aksi === 'edit' && $editId) {
    $stmtE = $db->prepare("SELECT * FROM buku WHERE id=?");
    $stmtE->execute([$editId]);
    $bukuEdit = $stmtE->fetch();
    if (!$bukuEdit) $aksi = 'list';
}

$bukuList = $db->query("
    SELECT b.*, k.nama as nama_kategori FROM buku b
    LEFT JOIN kategori k ON b.kategori_id=k.id
    ORDER BY b.created_at DESC
")->fetchAll();

if (isset($_GET['msg'])) {
    $msgMap = ['hapus'=>'Buku berhasil dihapus.'];
    if (isset($msgMap[$_GET['msg']])) $msg = ['type'=>'success','text'=>$msgMap[$_GET['msg']]];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku — <?= SITE_NAME ?></title>
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
            <a href="buku.php" class="active"><i class="fas fa-books"></i> Kelola Buku</a>
            <a href="kategori.php"><i class="fas fa-tags"></i> Kategori</a>
            <a href="anggota.php"><i class="fas fa-users"></i> Anggota</a>
            <a href="peminjaman.php"><i class="fas fa-book-reader"></i> Peminjaman</a>
            <a href="<?= SITE_URL ?>"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
            <a href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </nav>
    </aside>
    <main class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fas fa-books"></i> Kelola Buku</h1>
            <?php if ($aksi !== 'tambah' && $aksi !== 'edit'): ?>
                <a href="?aksi=tambah" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Buku</a>
            <?php else: ?>
                <a href="buku.php" class="btn" style="background:var(--cream-dark);color:var(--text)"><i class="fas fa-arrow-left"></i> Kembali</a>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg['type'] ?>" data-dismiss="1">
                <i class="fas fa-check-circle"></i> <?= sanitize($msg['text']) ?>
            </div>
        <?php endif; ?>

        <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>
        <!-- FORM TAMBAH/EDIT -->
        <div class="table-card">
            <div class="table-card-header">
                <h3><?= $aksi==='edit' ? 'Edit Buku' : 'Tambah Buku Baru' ?></h3>
            </div>
            <div style="padding:1.5rem">
                <form method="POST">
                    <?php if ($bukuEdit): ?><input type="hidden" name="id" value="<?= $bukuEdit['id'] ?>"><?php endif; ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="form-group">
                            <label>Judul Buku *</label>
                            <input type="text" name="judul" class="form-control"
                                   value="<?= sanitize($bukuEdit['judul'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Pengarang *</label>
                            <input type="text" name="pengarang" class="form-control"
                                   value="<?= sanitize($bukuEdit['pengarang'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Penerbit</label>
                            <input type="text" name="penerbit" class="form-control"
                                   value="<?= sanitize($bukuEdit['penerbit'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Tahun Terbit</label>
                            <input type="number" name="tahun_terbit" class="form-control" min="1900" max="2099"
                                   value="<?= $bukuEdit['tahun_terbit'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" class="form-control"
                                   value="<?= sanitize($bukuEdit['isbn'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori_id" class="form-control">
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($bukuEdit['kategori_id']??'') == $k['id'] ? 'selected':'' ?>>
                                        <?= sanitize($k['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Halaman</label>
                            <input type="number" name="halaman" class="form-control" min="0"
                                   value="<?= $bukuEdit['halaman'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="tersedia" <?= ($bukuEdit['status']??'tersedia')==='tersedia'?'selected':'' ?>>Tersedia</option>
                                <option value="tidak_tersedia" <?= ($bukuEdit['status']??'')==='tidak_tersedia'?'selected':'' ?>>Tidak Tersedia</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>URL Cover (link gambar)</label>
                        <input type="url" name="cover_url" class="form-control"
                               value="<?= htmlspecialchars($bukuEdit['cover_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>URL File PDF</label>
                        <input type="url" name="file_url" class="form-control"
                               value="<?= htmlspecialchars($bukuEdit['file_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="5"><?= sanitize($bukuEdit['deskripsi'] ?? '') ?></textarea>
                    </div>
                    <div style="display:flex;gap:1rem">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $aksi==='edit' ? 'Simpan Perubahan' : 'Tambahkan Buku' ?>
                        </button>
                        <a href="buku.php" class="btn" style="background:var(--cream-dark);color:var(--text)">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- TABLE -->
        <div class="table-card">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid var(--border)">
                <input type="text" id="searchBuku" class="form-control" style="max-width:300px" placeholder="🔍 Cari buku...">
            </div>
            <div style="overflow-x:auto">
            <table class="data-table" id="tableBuku">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Pengarang</th>
                        <th>Kategori</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bukuList as $i => $b): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td style="font-weight:600;max-width:180px"><?= sanitize($b['judul']) ?></td>
                        <td><?= sanitize($b['pengarang']) ?></td>
                        <td><span class="badge badge-blue"><?= sanitize($b['nama_kategori'] ?? '-') ?></span></td>
                        <td><?= number_format($b['views']) ?></td>
                        <td><span class="badge <?= $b['status']==='tersedia'?'badge-green':'badge-red' ?>"><?= $b['status'] ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="?aksi=edit&id=<?= $b['id'] ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                <a href="?aksi=hapus&id=<?= $b['id'] ?>" class="btn btn-sm btn-delete"
                                   data-confirm="Hapus buku '<?= addslashes($b['judul']) ?>'?">
                                   <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
// Live search
const inp = document.getElementById('searchBuku');
if (inp) {
    inp.addEventListener('input', () => {
        const q = inp.value.toLowerCase();
        document.querySelectorAll('#tableBuku tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>
