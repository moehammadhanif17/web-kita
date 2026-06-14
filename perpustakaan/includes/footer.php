<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>" class="brand">
                    <i class="fas fa-book-open"></i>
                    <span><?= SITE_NAME ?></span>
                </a>
                <p>Perpustakaan digital modern untuk akses koleksi buku kapan saja dan di mana saja.</p>
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>">Beranda</a></li>
                    <li><a href="<?= SITE_URL ?>/katalog.php">Katalog Buku</a></li>
                    <li><a href="<?= SITE_URL ?>/kategori.php">Kategori</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Akun</h4>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= SITE_URL ?>/profil.php">Profil Saya</a></li>
                        <li><a href="<?= SITE_URL ?>/peminjaman.php">Riwayat Pinjam</a></li>
                        <li><a href="<?= SITE_URL ?>/logout.php">Keluar</a></li>
                    <?php else: ?>
                        <li><a href="<?= SITE_URL ?>/login.php">Masuk</a></li>
                        <li><a href="<?= SITE_URL ?>/daftar.php">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-contact">
                <h4>Kontak</h4>
                <p><i class="fas fa-map-marker-alt"></i> Jl. Perpustakaan No. 1, Indonesia</p>
                <p><i class="fas fa-envelope"></i> info@digipustaka.id</p>
                <p><i class="fas fa-phone"></i> (021) 1234-5678</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Dibuat dengan <i class="fas fa-heart" style="color:#e74c3c"></i> untuk kemajuan literasi Indonesia.</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
