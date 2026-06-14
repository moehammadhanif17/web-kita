-- =============================================
-- DATABASE: Perpustakaan Digital
-- =============================================

CREATE DATABASE IF NOT EXISTS perpustakaan_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan_digital;

-- Tabel Kategori
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    ikon VARCHAR(50) DEFAULT 'book',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Buku
CREATE TABLE buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    pengarang VARCHAR(255) NOT NULL,
    penerbit VARCHAR(255),
    tahun_terbit YEAR,
    isbn VARCHAR(20),
    kategori_id INT,
    deskripsi TEXT,
    cover_url VARCHAR(500),
    file_url VARCHAR(500),
    halaman INT DEFAULT 0,
    bahasa VARCHAR(50) DEFAULT 'Indonesia',
    status ENUM('tersedia','tidak_tersedia') DEFAULT 'tersedia',
    views INT DEFAULT 0,
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- Tabel Users / Anggota
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','anggota') DEFAULT 'anggota',
    foto VARCHAR(255),
    no_anggota VARCHAR(20) UNIQUE,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Peminjaman
CREATE TABLE peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    tanggal_dikembalikan DATE,
    status ENUM('dipinjam','dikembalikan','terlambat') DEFAULT 'dipinjam',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

-- Tabel Ulasan/Review
CREATE TABLE ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buku_id) REFERENCES buku(id) ON DELETE CASCADE
);

-- =============================================
-- DATA CONTOH
-- =============================================

INSERT INTO kategori (nama, slug, deskripsi, ikon) VALUES
('Fiksi',        'fiksi',        'Novel, cerpen, dan karya fiksi',          'book-open'),
('Sains',        'sains',        'Ilmu pengetahuan alam dan teknologi',      'flask'),
('Sejarah',      'sejarah',      'Sejarah Indonesia dan dunia',              'landmark'),
('Pendidikan',   'pendidikan',   'Buku pelajaran dan referensi akademik',    'graduation-cap'),
('Bisnis',       'bisnis',       'Ekonomi, manajemen, dan kewirausahaan',    'briefcase'),
('Agama',        'agama',        'Buku-buku keagamaan dan spiritual',        'moon');

INSERT INTO buku (judul, slug, pengarang, penerbit, tahun_terbit, isbn, kategori_id, deskripsi, cover_url, halaman, bahasa, views, downloads) VALUES
('Laskar Pelangi', 'laskar-pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '978-979-1227-00-1', 1, 'Kisah sepuluh anak Belitung yang bermimpi besar meskipun hidup dalam kemiskinan. Novel fenomenal yang mengharukan dan penuh inspirasi.', 'https://upload.wikimedia.org/wikipedia/id/thumb/8/8e/Laskar_pelangi_sampul.jpg/220px-Laskar_pelangi_sampul.jpg', 529, 'Indonesia', 1250, 380),
('Bumi Manusia', 'bumi-manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, '978-979-8659-78-1', 3, 'Novel pertama tetralogi Buru karya Pramoedya. Kisah Minke, pemuda Jawa terpelajar di zaman kolonial Belanda.', 'https://upload.wikimedia.org/wikipedia/id/thumb/b/b9/Bumi_Manusia.jpg/220px-Bumi_Manusia.jpg', 535, 'Indonesia', 980, 270),
('Sapiens: Riwayat Singkat Umat Manusia', 'sapiens', 'Yuval Noah Harari', 'KPG', 2017, '978-602-424-430-5', 3, 'Sebuah eksplorasi berani tentang bagaimana Homo sapiens berhasil mendominasi Bumi dan membentuk peradaban modern.', 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/40/Sapiens%3A_A_Brief_History_of_Humankind.jpg/220px-Sapiens%3A_A_Brief_History_of_Humankind.jpg', 443, 'Indonesia', 870, 220),
('Rich Dad Poor Dad', 'rich-dad-poor-dad', 'Robert T. Kiyosaki', 'Gramedia', 2000, '978-602-03-1000-1', 5, 'Buku tentang kebebasan finansial yang mengajarkan cara berpikir orang kaya tentang uang, investasi, dan aset.', 'https://upload.wikimedia.org/wikipedia/en/thumb/b/b9/Rich_Dad_Poor_Dad.jpg/220px-Rich_Dad_Poor_Dad.jpg', 207, 'Indonesia', 1100, 450),
('Fisika Dasar', 'fisika-dasar', 'Halliday & Resnick', 'Erlangga', 2010, '978-979-099-001-3', 2, 'Buku teks fisika komprehensif untuk mahasiswa teknik dan sains. Mencakup mekanika, termodinamika, elektromagnetisme.', NULL, 820, 'Indonesia', 430, 150),
('Matematika Diskrit', 'matematika-diskrit', 'Kenneth Rosen', 'McGraw-Hill', 2012, '978-0-07-338309-5', 4, 'Teks lengkap matematika diskrit untuk ilmu komputer, mencakup logika, himpunan, relasi, graf, dan kombinatorika.', NULL, 756, 'Indonesia', 320, 90);

-- Admin default (password: admin123)
INSERT INTO users (nama, email, password, role, no_anggota, status) VALUES
('Administrator', 'admin@perpustakaan.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ADM-001', 'aktif'),
('Budi Santoso',  'budi@email.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'anggota', 'MBR-001', 'aktif');
