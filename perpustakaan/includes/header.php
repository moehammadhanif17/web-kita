<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="<?= SITE_DESC ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container nav-inner">
        <a href="<?= SITE_URL ?>" class="brand">
            <i class="fas fa-book-open"></i>
            <span><?= SITE_NAME ?></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= SITE_URL ?>"><i class="fas fa-home"></i> Beranda</a></li>
            <li><a href="<?= SITE_URL ?>/katalog.php"><i class="fas fa-books"></i> Katalog</a></li>
            <li><a href="<?= SITE_URL ?>/kategori.php"><i class="fas fa-tags"></i> Kategori</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= SITE_URL ?>/profil.php"><i class="fas fa-user"></i> <?= sanitize($_SESSION['nama']) ?></a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/" class="btn-admin"><i class="fas fa-cog"></i> Admin</a></li>
                <?php endif; ?>
                <li><a href="<?= SITE_URL ?>/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
            <?php else: ?>
                <li><a href="<?= SITE_URL ?>/login.php" class="btn-primary-nav"><i class="fas fa-sign-in-alt"></i> Masuk</a></li>
            <?php endif; ?>
        </ul>

        <form class="nav-search" action="<?= SITE_URL ?>/katalog.php" method="GET">
            <input type="text" name="q" placeholder="Cari buku, pengarang..." 
                   value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
</nav>
