-- Database SQL for Peta Ilmu Project
-- Safe to import - uses DROP TABLE IF EXISTS and CREATE TABLE

CREATE DATABASE IF NOT EXISTS peta_ilmu;
USE peta_ilmu;

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS learning_data;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS registration_subjects;
DROP TABLE IF EXISTS pendaftaran;
DROP TABLE IF EXISTS package_prices;
DROP TABLE IF EXISTS program_packages;
DROP TABLE IF EXISTS program_features;
DROP TABLE IF EXISTS program_benefits;
DROP TABLE IF EXISTS program_faqs;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS struktur_organisasi;
DROP TABLE IF EXISTS tim_pengajar;
DROP TABLE IF EXISTS kontak_info;
DROP TABLE IF EXISTS nilai_organisasi;
DROP TABLE IF EXISTS misi_organisasi;
DROP TABLE IF EXISTS sejarah_organisasi;
DROP TABLE IF EXISTS organisasi_info;
DROP TABLE IF EXISTS mata_pelajaran;
DROP TABLE IF EXISTS admins;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin account (username: admin, password: admin123)
-- Password hash generated using PHP password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Programs table
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(50) NOT NULL UNIQUE,
    category ENUM('sd', 'smp', 'sma') NOT NULL,
    icon VARCHAR(100) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration VARCHAR(100) DEFAULT NULL,
    frequency VARCHAR(100) DEFAULT NULL,
    subjects JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Program Features table
CREATE TABLE program_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    feature_text TEXT NOT NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Program Packages table
CREATE TABLE program_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    package_type VARCHAR(100) NOT NULL,
    description TEXT,
    package_icon VARCHAR(100) DEFAULT NULL,
    info TEXT DEFAULT NULL,
    extra_info TEXT DEFAULT NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Package Prices table
CREATE TABLE package_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    price_label VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (package_id) REFERENCES program_packages(id) ON DELETE CASCADE
);

-- Program Benefits table
CREATE TABLE program_benefits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(100) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT
);

-- Program FAQs table
CREATE TABLE program_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL
);

-- Pendaftaran (Registration) table
CREATE TABLE pendaftaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    tanggal_lahir DATE DEFAULT NULL,
    jenis_kelamin ENUM('L', 'P') DEFAULT NULL,
    alamat TEXT,
    telepon VARCHAR(20) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    jenjang ENUM('sd', 'smp', 'sma') NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    sekolah VARCHAR(255) DEFAULT NULL,
    package_id INT DEFAULT NULL,
    package_type VARCHAR(100) NOT NULL,
    durasi VARCHAR(50) NOT NULL,
    jumlah_hari INT DEFAULT NULL,
    nama_ortu VARCHAR(255) NOT NULL,
    pekerjaan_ortu VARCHAR(255) DEFAULT NULL,
    telepon_ortu VARCHAR(20) NOT NULL,
    motivasi TEXT DEFAULT NULL,
    referensi VARCHAR(255) DEFAULT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES program_packages(id) ON DELETE SET NULL
);

-- Registration Subjects table
CREATE TABLE registration_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (registration_id) REFERENCES pendaftaran(id) ON DELETE CASCADE
);

-- Organisasi Info table
CREATE TABLE organisasi_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visi TEXT,
    tahun_berdiri INT DEFAULT NULL,
    jumlah_siswa_awal INT DEFAULT NULL
);

-- Sejarah Organisasi table
CREATE TABLE sejarah_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paragraf TEXT NOT NULL,
    urutan INT NOT NULL
);

-- Misi Organisasi table
CREATE TABLE misi_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    misi_text TEXT NOT NULL,
    urutan INT NOT NULL
);

-- Nilai Organisasi table
CREATE TABLE nilai_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(100) DEFAULT NULL,
    nama VARCHAR(255) NOT NULL,
    deskripsi TEXT
);

-- Struktur Organisasi table
CREATE TABLE struktur_organisasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    posisi VARCHAR(255) NOT NULL,
    deskripsi TEXT DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL
);

-- Mata Pelajaran table
CREATE TABLE mata_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(255) NOT NULL
);

-- Tim Pengajar table
CREATE TABLE tim_pengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    mata_pelajaran_id INT NOT NULL,
    deskripsi TEXT DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    status ENUM('aktif', 'non-aktif') DEFAULT 'aktif',
    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE
);

-- Kontak Info table
CREATE TABLE kontak_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis ENUM('alamat', 'telepon', 'email', 'fax') NOT NULL,
    nilai TEXT NOT NULL,
    status ENUM('aktif', 'non-aktif') DEFAULT 'aktif',
    urutan INT DEFAULT 1
);

-- Students table (for student login with username + password)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    jenjang ENUM('sd', 'smp', 'sma') NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default student account (username: siswa, password: siswa123)
-- Password hash generated using PHP password_hash('siswa123', PASSWORD_BCRYPT)
-- Note: On Laragon/XAMPP, generate fresh hash using: php -r "echo password_hash('siswa123', PASSWORD_BCRYPT);"
INSERT INTO students (username, nama, email, password, jenjang, kelas) VALUES 
('siswa', 'Siswa Demo', 'siswa@petailmu.local', '$2y$10$e0Mzj7MWvR8qN9k5L6OZu.H8Y4XcD2fG1hI0jK3lM5nO7pQ9rStU.', 'sd', '5');

-- Learning Data table (for tracking student learning progress)
CREATE TABLE learning_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    mata_pelajaran VARCHAR(255) NOT NULL,
    nilai DECIMAL(5,2) DEFAULT NULL,
    tingkat_kesulitan ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang',
    gaya_belajar VARCHAR(100) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert default data for organization info
INSERT INTO organisasi_info (visi, tahun_berdiri, jumlah_siswa_awal) VALUES 
('Menjadi lembaga bimbingan belajar terbaik yang membantu siswa mencapai potensi maksimal mereka.', 2010, 20);

-- Insert default mata pelajaran
INSERT INTO mata_pelajaran (kode, nama) VALUES 
('MTK', 'Matematika'),
('BID', 'Bahasa Indonesia'),
('BIG', 'Bahasa Inggris'),
('IPA', 'Ilmu Pengetahuan Alam'),
('IPS', 'Ilmu Pengetahuan Sosial'),
('FIS', 'Fisika'),
('KIM', 'Kimia'),
('BIO', 'Biologi'),
('SEJ', 'Sejarah'),
('GEO', 'Geografi'),
('ECO', 'Ekonomi'),
('SOS', 'Sosiologi');

-- Insert default program benefits
INSERT INTO program_benefits (icon, title, description) VALUES 
('fas fa-chalkboard-teacher', 'Pengajar Berpengalaman', 'Tim pengajar profesional dan berpengalaman di bidangnya'),
('fas fa-users', 'Kelas Kecil', 'Kelas dengan jumlah siswa terbatas untuk pembelajaran lebih fokus'),
('fas fa-book-open', 'Materi Lengkap', 'Materi pembelajaran lengkap dan terupdate'),
('fas fa-clock', 'Fleksibel', 'Jadwal belajar yang fleksibel sesuai kebutuhan siswa');

-- Insert default program FAQs
INSERT INTO program_faqs (question, answer) VALUES 
('Bagaimana cara mendaftar?', 'Anda dapat mendaftar melalui halaman pendaftaran atau datang langsung ke lokasi kami.'),
('Berapa biaya pendaftaran?', 'Biaya pendaftaran bervariasi tergantung program yang dipilih. Silakan lihat detail program untuk informasi lebih lanjut.'),
('Apakah ada trial class?', 'Ya, kami menyediakan trial class untuk memastikan program cocok untuk siswa.');
