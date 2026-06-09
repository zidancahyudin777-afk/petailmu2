<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: siswa_login.php");
    exit;
}

require_once 'config/database.php';
require_once 'classes/StudentManager.php';

$database = new Database();
$pdo = $database->getConnection();
$studentManager = new StudentManager();

// Get student data
try {
    $student = $studentManager->getStudentById($_SESSION['student_id']);
    if (!$student) {
        session_destroy();
        header("Location: siswa_login.php");
        exit;
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    $student = null;
}

// Get latest learning data
$learningData = null;
try {
    $query = "SELECT * FROM learning_data WHERE student_id = :student_id ORDER BY tanggal_input DESC LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':student_id' => $_SESSION['student_id']]);
    $learningData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching learning data: ' . $e->getMessage());
}

// Generate recommendation based on Decision Tree logic
$rekomendasi = null;
$alasanRekomendasi = '';
$fokusBelajar = '';
$strategiBelajar = '';
$rencana7Hari = [];
$catatanTutor = '';
$catatanOrtu = '';

if ($learningData) {
    $nilai = floatval($learningData['nilai']);
    $tingkatKesulitan = strtolower($learningData['tingkat_kesulitan']);
    $gayaBelajar = !empty($learningData['gaya_belajar']) ? strtolower($learningData['gaya_belajar']) : '';

    // Decision Tree Logic (unchanged)
    // Rule 1: IF nilai < 60 OR tingkat_kesulitan = Sulit THEN Pendampingan Intensif
    // Rule 2: IF nilai >= 60 AND nilai < 80 THEN Program Intensif
    // Rule 3: IF nilai >= 80 AND tingkat_kesulitan != Sulit THEN Program Reguler / Pengayaan

    if ($nilai < 60 || $tingkatKesulitan === 'sulit') {
        $rekomendasi = 'Pendampingan Intensif';
        
        $alasanRekomendasi = 'Berdasarkan analisis data belajar Anda, sistem merekomendasikan Pendampingan Intensif. ' .
            'Hal ini dikarenakan nilai terakhir Anda berada di bawah 60 atau Anda mengalami tingkat kesulitan yang tinggi dalam materi. ' .
            'Kondisi ini menunjukkan bahwa Anda memerlukan bantuan lebih dekat dari tutor untuk memahami konsep dasar dengan baik.';
        
        $fokusBelajar = 'Fokus utama adalah penguatan konsep dasar dan pemahaman fundamental. ' .
            'Anda perlu mengulang materi-materi inti, memperkuat fondasi pengetahuan, dan berlatih secara terbimbing ' .
            'untuk membangun kepercayaan diri dalam menyelesaikan soal-soal.';
        
        // Learning style strategy
        if ($gayaBelajar === 'visual') {
            $strategiBelajar = 'Sebagai pelajar visual, manfaatkan diagram, peta konsep, dan catatan berwarna untuk memahami materi. ' .
                'Gunakan spidol warna-warni untuk menandai poin-poin penting, buat mind map untuk setiap bab, ' .
                'dan tonton video pembelajaran yang banyak menggunakan visualisasi. Gambar ilustrasi untuk membantu pemahaman.';
        } elseif ($gayaBelajar === 'auditori') {
            $strategiBelajar = 'Sebagai pelajar auditori, manfaatkan diskusi dengan teman atau tutor untuk memahami materi. ' .
                'Baca materi dengan suara keras, rekam penjelasan tutor dan dengarkan kembali, ' .
                'ikuti kelompok belajar untuk berdiskusi, dan gunakan aplikasi text-to-speech untuk mendengar materi pelajaran.';
        } elseif ($gayaBelajar === 'kinestetik') {
            $strategiBelajar = 'Sebagai pelajar kinestetik, belajarlah sambil bergerak dan praktik langsung. ' .
                'Gunakan benda konkret untuk memahami konsep abstrak, lakukan simulasi atau eksperimen kecil, ' .
                'berlatih soal sambil berjalan-jalan, dan gunakan gesture atau gerakan tangan untuk mengingat rumus.';
        } else {
            $strategiBelajar = 'Gunakan kombinasi berbagai metode belajar: baca materi dengan teliti, kerjakan latihan soal secara rutin, ' .
                'buat ringkasan dengan kata-kata sendiri, dan jangan ragu untuk bertanya kepada tutor ketika ada yang belum dipahami.';
        }
        
        $rencana7Hari = [
            'Hari 1' => 'Identifikasi topik yang paling sulit dan diskusikan dengan tutor',
            'Hari 2' => 'Review konsep dasar dari topik tersebut dengan bimbingan tutor',
            'Hari 3' => 'Kerjakan latihan soal dasar dengan pendampingan',
            'Hari 4' => 'Ulangi review dan perbaiki kesalahan dari latihan sebelumnya',
            'Hari 5' => 'Kerjakan soal latihan mandiri dengan tingkat kesulitan rendah',
            'Hari 6' => 'Diskusi hasil latihan dengan tutor dan minta feedback',
            'Hari 7' => 'Evaluasi mingguan dan tentukan target untuk minggu berikutnya'
        ];
        
        $catatanTutor = 'Mohon tutor memberikan perhatian khusus pada siswa ini. Periksa kesulitan utama yang dihadapi, ' .
            'berikan penjelasan konsep dasar secara bertahap, dan pastikan siswa benar-benar memahami sebelum melanjutkan ke materi berikutnya. ' .
            'Berikan latihan terbimbing dengan intensitas tinggi dan berikan motivasi secara konsisten.';
        
        $catatanOrtu = 'Diharapkan orang tua dapat memantau konsistensi belajar anak di rumah. ' .
            'Pastikan anak memiliki waktu belajar yang teratur, dampingi anak saat mengerjakan tugas, ' .
            'dan berkomunikasi dengan tutor untuk perkembangan anak. Berikan dukungan dan motivasi agar anak tidak mudah menyerah.';
            
    } elseif ($nilai >= 60 && $nilai < 80) {
        $rekomendasi = 'Program Intensif';
        
        $alasanRekomendasi = 'Berdasarkan analisis data belajar Anda, sistem merekomendasikan Program Intensif. ' .
            'Nilai Anda berada dalam kategori menengah (60-79), yang menunjukkan pemahaman yang cukup baik namun masih ada ruang untuk peningkatan. ' .
            'Dengan program intensif, Anda dapat memperkuat topik-topik yang masih lemah dan meningkatkan nilai secara signifikan.';
        
        $fokusBelajar = 'Fokus pada penguatan topik-topik yang masih menjadi kelemahan dan pemantapan pemahaman konsep. ' .
            'Anda perlu mengidentifikasi area yang perlu diperbaiki, melakukan latihan terarah, dan memastikan tidak ada kesenjangan pemahaman ' .
            'sebelum melanjutkan ke materi yang lebih advanced.';
        
        // Learning style strategy
        if ($gayaBelajar === 'visual') {
            $strategiBelajar = 'Sebagai pelajar visual, buatlah rangkuman berwarna untuk setiap topik, gunakan grafik dan tabel ' .
                'untuk membandingkan konsep, tonton video tutorial dengan animasi, dan buat flashcard bergambar untuk rumus-rumus penting.';
        } elseif ($gayaBelajar === 'auditori') {
            $strategiBelajar = 'Sebagai pelajar auditori, jelaskan materi kepada teman atau keluarga dengan suara keras, ' .
                'dengarkan podcast pendidikan terkait materi, ikuti sesi tanya jawab dengan tutor, dan buat lagu atau rhyme ' .
                'untuk mengingat rumus atau konsep penting.';
        } elseif ($gayaBelajar === 'kinestetik') {
            $strategiBelajar = 'Sebagai pelajar kinestetik, kerjakan banyak latihan soal secara aktif, gunakan alat peraga ' .
                'untuk memahami konsep, buat model atau prototype jika memungkinkan, dan ambil istirahat singkat setiap 30 menit ' .
                'untuk tetap fokus saat belajar.';
        } else {
            $strategiBelajar = 'Kombinasikan membaca teori dengan langsung mengerjakan latihan soal. Buat catatan ringkas setelah setiap sesi belajar, ' .
                'kerjakan soal dengan variasi tingkat kesulitan, dan evaluasi kesalahan untuk menghindari pengulangan.';
        }
        
        $rencana7Hari = [
            'Hari 1' => 'Identifikasi 3 topik yang paling perlu diperbaiki berdasarkan hasil evaluasi',
            'Hari 2' => 'Review dan pelajari ulang topik pertama dengan latihan soal sedang',
            'Hari 3' => 'Lanjutkan review topik kedua dengan bimbingan tutor jika diperlukan',
            'Hari 4' => 'Kerjakan latihan soal campuran untuk topik yang sudah direview',
            'Hari 5' => 'Review topik ketiga dan identifikasi sisa kelemahan',
            'Hari 6' => 'Kerjakan soal latihan dengan waktu terbatas (simulasi ujian)',
            'Hari 7' => 'Evaluasi mingguan dan catat progress yang telah dicapai'
        ];
        
        $catatanTutor = 'Siswa ini berada dalam kategori menengah dan berpotensi untuk meningkat. Berikan latihan yang terarah ' .
            'pada topik-topik yang masih lemah, berikan feedback konstruktif atas kesalahan yang dibuat, dan dorong siswa ' .
            'untuk lebih percaya diri. Fokus pada quality over quantity dalam latihan soal.';
        
        $catatanOrtu = 'Dukung anak dengan menyediakan lingkungan belajar yang kondusif di rumah. Pastikan anak memiliki jadwal belajar ' .
            'yang teratur dan seimbang dengan waktu istirahat. Pantau progress belajar anak dan berikan apresiasi untuk setiap kemajuan yang dicapai.';
            
    } else {
        // nilai >= 80 AND tingkat_kesulitan != Sulit
        $rekomendasi = 'Program Reguler / Pengayaan';
        
        $alasanRekomendasi = 'Berdasarkan analisis data belajar Anda, sistem merekomendasikan Program Reguler dengan pengayaan. ' .
            'Nilai Anda sangat baik (80 ke atas) dan tingkat kesulitan yang dihadapi tidak termasuk kategori sulit. ' .
            'Ini menunjukkan pemahaman yang solid terhadap materi. Program pengayaan akan membantu Anda mencapai potensi maksimal.';
        
        $fokusBelajar = 'Fokus pada pengayaan materi dan pengembangan kemampuan berpikir tingkat tinggi. ' .
            'Anda dapat mempelajari materi tambahan yang lebih advanced, mengerjakan soal-soal tantangan, ' .
            'dan mengembangkan keterampilan problem solving untuk persiapan kompetisi atau ujian tingkat lanjut.';
        
        // Learning style strategy
        if ($gayaBelajar === 'visual') {
            $strategiBelajar = 'Sebagai pelajar visual dengan kemampuan baik, buatlah peta konsep yang komprehensif untuk menghubungkan antar-topik, ' .
                'analisis diagram dan grafik kompleks, buat infografis untuk merangkum materi, dan eksplorasi sumber belajar visual ' .
                'seperti dokumenter edukasi atau presentasi ilmiah.';
        } elseif ($gayaBelajar === 'auditori') {
            $strategiBelajar = 'Sebagai pelajar auditori dengan kemampuan baik, ikuti diskusi tingkat lanjut dengan teman atau tutor, ' .
                'presentasikan materi kepada orang lain untuk memperdalam pemahaman, dengarkan kuliah online atau webinar, ' .
                'dan ajarkan konsep kepada teman yang membutuhkan bantuan.';
        } elseif ($gayaBelajar === 'kinestetik') {
            $strategiBelajar = 'Sebagai pelajar kinestetik dengan kemampuan baik, terlibat dalam proyek praktis yang menerapkan konsep learned, ' .
                'ikuti kompetisi atau olimpiade sains, lakukan eksperimen atau penelitian sederhana, dan gunakan pendekatan learning by teaching ' .
                'dengan membimbing teman dalam praktikum.';
        } else {
            $strategiBelajar = 'Dengan kemampuan yang sudah baik, tingkatkan belajar dengan mengerjakan soal-soal HOTS (Higher Order Thinking Skills), ' .
                'ikuti tryout atau kompetisi untuk menguji kemampuan, pelajari materi beyond kurikulum, dan kembangkan strategi belajar mandiri ' .
                'untuk terus berkembang.';
        }
        
        $rencana7Hari = [
            'Hari 1' => 'Kerjakan soal-soal tantangan dengan tingkat kesulitan tinggi',
            'Hari 2' => 'Pelajari materi pengayaan di luar kurikulum standar',
            'Hari 3' => 'Ikuti diskusi kelompok atau forum belajar untuk berbagi pengetahuan',
            'Hari 4' => 'Kerjakan soal olimpiade atau kompetisi untuk melatih problem solving',
            'Hari 5' => 'Review materi advanced dan buat koneksi antar konsep',
            'Hari 6' => 'Ajarkan materi kepada teman (peer teaching) untuk memperdalam pemahaman',
            'Hari 7' => 'Evaluasi mingguan dan tentukan target challenge untuk minggu berikutnya'
        ];
        
        $catatanTutor = 'Siswa ini memiliki kemampuan yang sangat baik. Berikan materi pengayaan dan soal-soal tantangan untuk menjaga motivasi belajar. ' .
            'Dorong siswa untuk mengikuti kompetisi atau olimpiade, fasilitasi pembelajaran mandiri, dan berikan kesempatan untuk peer teaching. ' .
            'Bantu siswa menetapkan target yang lebih tinggi.';
        
        $catatanOrtu = 'Anak Anda menunjukkan prestasi belajar yang sangat baik. Dukung dengan menyediakan akses ke sumber belajar tambahan ' .
            'seperti buku pengayaan atau kursus online. Dorong anak untuk mengikuti kompetisi atau kegiatan akademik yang menantang. ' .
            'Tetap pantau keseimbangan antara belajar dan aktivitas lainnya.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rekomendasi Belajar - Bimbingan Belajar Peta Ilmu</title>
    <link rel="stylesheet" href="stylemain.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            max-height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
            padding: 15px 10px;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 15px 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(220, 53, 69, 0.8);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .sidebar-footer .logout-btn:hover {
            background: #dc3545;
            transform: translateX(5px);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        .recommendation-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .page-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        /* Card Styles */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #667eea;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-header h3 {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
        }

        .card-header i {
            color: #667eea;
            font-size: 20px;
        }

        .card-content {
            color: #444;
            line-height: 1.8;
            font-size: 14px;
        }

        /* Recommendation Badge */
        .recommendation-badge {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .badge-intensif {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: #fff;
        }

        .badge-program-intensif {
            background: linear-gradient(135deg, #ffa502 0%, #ff8c00 100%);
            color: #fff;
        }

        .badge-reguler {
            background: linear-gradient(135deg, #2ed573 0%, #1abc9c 100%);
            color: #fff;
        }

        /* Data Grid */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .data-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .data-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .data-value {
            font-size: 16px;
            color: #333;
            font-weight: 600;
        }

        /* Study Plan List */
        .study-plan-list {
            list-style: none;
        }

        .study-plan-list li {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .study-plan-list li:before {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            color: #667eea;
            font-size: 14px;
            margin-top: 2px;
        }

        .study-plan-list li:nth-child(odd) {
            background: #f0f2f5;
        }

        /* Note Box */
        .note-box {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .note-box.tutor {
            background: #e3f2fd;
            border-left-color: #2196f3;
        }

        .note-box.parent {
            background: #fce4ec;
            border-left-color: #e91e63;
        }

        .note-box strong {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        .note-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .empty-state p {
            color: #888;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #667eea;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .data-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 20px;
            }

            .page-header h2 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-graduation-cap"></i> Peta Ilmu</h3>
                <p>Dashboard Siswa</p>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="siswa_dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="program.php">
                        <i class="fas fa-book"></i>
                        <span>Daftar Program</span>
                    </a>
                </li>
                <li>
                    <a href="input_data_belajar.php">
                        <i class="fas fa-edit"></i>
                        <span>Input Data Belajar</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-chart-line"></i>
                        <span>Perkembangan Belajar</span>
                    </a>
                </li>
                <li>
                    <a href="rekomendasi_belajar.php" class="active">
                        <i class="fas fa-lightbulb"></i>
                        <span>Rekomendasi Belajar</span>
                    </a>
                </li>
                <li>
                    <a href="program.php">
                        <i class="fas fa-eye"></i>
                        <span>Lihat Program</span>
                    </a>
                </li>
                <li>
                    <a href="kontak.php">
                        <i class="fas fa-envelope"></i>
                        <span>Kontak Kami</span>
                    </a>
                </li>
                <li>
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali ke Website</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="siswa_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="recommendation-container">
                <div class="page-header">
                    <h2><i class="fas fa-lightbulb"></i> Rekomendasi Belajar</h2>
                    <p>Rekomendasi program belajar yang disesuaikan dengan kondisi Anda</p>
                </div>

                <?php if (!$learningData): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum Ada Data Belajar</h3>
                        <p>Belum ada data belajar. Silakan isi data belajar terlebih dahulu untuk mendapatkan rekomendasi.</p>
                        <a href="input_data_belajar.php" class="btn-primary">
                            <i class="fas fa-edit"></i>
                            Isi Data Belajar
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Latest Learning Data -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-database"></i>
                            <h3>Data Belajar Terakhir</h3>
                        </div>
                        <div class="card-content">
                            <div class="data-grid">
                                <div class="data-item">
                                    <div class="data-label"><i class="fas fa-book"></i> Mata Pelajaran</div>
                                    <div class="data-value"><?php echo htmlspecialchars($learningData['mata_pelajaran']); ?></div>
                                </div>
                                <div class="data-item">
                                    <div class="data-label"><i class="fas fa-star"></i> Nilai</div>
                                    <div class="data-value"><?php echo number_format($learningData['nilai'], 0); ?></div>
                                </div>
                                <div class="data-item">
                                    <div class="data-label"><i class="fas fa-layer-group"></i> Tingkat Kesulitan</div>
                                    <div class="data-value"><?php echo ucfirst(htmlspecialchars($learningData['tingkat_kesulitan'])); ?></div>
                                </div>
                                <div class="data-item">
                                    <div class="data-label"><i class="fas fa-brain"></i> Gaya Belajar</div>
                                    <div class="data-value"><?php echo !empty($learningData['gaya_belajar']) ? ucfirst(htmlspecialchars($learningData['gaya_belajar'])) : '-'; ?></div>
                                </div>
                                <div class="data-item">
                                    <div class="data-label"><i class="fas fa-calendar"></i> Tanggal Input</div>
                                    <div class="data-value"><?php echo date('d M Y', strtotime($learningData['tanggal_input'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation Result -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i>
                            <h3>Hasil Rekomendasi</h3>
                        </div>
                        <div class="card-content">
                            <?php
                            $badgeClass = 'badge-reguler';
                            if ($rekomendasi === 'Pendampingan Intensif') {
                                $badgeClass = 'badge-intensif';
                            } elseif ($rekomendasi === 'Program Intensif') {
                                $badgeClass = 'badge-program-intensif';
                            }
                            ?>
                            <span class="recommendation-badge <?php echo $badgeClass; ?>">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($rekomendasi); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-question-circle"></i>
                            <h3>Alasan Rekomendasi</h3>
                        </div>
                        <div class="card-content">
                            <p><?php echo nl2br(htmlspecialchars($alasanRekomendasi)); ?></p>
                        </div>
                    </div>

                    <!-- Learning Focus -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bullseye"></i>
                            <h3>Fokus Belajar</h3>
                        </div>
                        <div class="card-content">
                            <p><?php echo nl2br(htmlspecialchars($fokusBelajar)); ?></p>
                        </div>
                    </div>

                    <!-- Learning Strategy -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>Strategi Belajar</h3>
                        </div>
                        <div class="card-content">
                            <p><?php echo nl2br(htmlspecialchars($strategiBelajar)); ?></p>
                        </div>
                    </div>

                    <!-- 7-Day Study Plan -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Rencana Belajar 7 Hari</h3>
                        </div>
                        <div class="card-content">
                            <ul class="study-plan-list">
                                <?php foreach ($rencana7Hari as $hari => $aktivitas): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($hari); ?>:</strong>
                                        <span><?php echo htmlspecialchars($aktivitas); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Note for Tutor -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-user-tie"></i>
                            <h3>Catatan untuk Tutor</h3>
                        </div>
                        <div class="card-content">
                            <div class="note-box tutor">
                                <strong><i class="fas fa-chalkboard-teacher"></i> Untuk Tutor:</strong>
                                <p><?php echo nl2br(htmlspecialchars($catatanTutor)); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Note for Parents -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users"></i>
                            <h3>Catatan untuk Orang Tua</h3>
                        </div>
                        <div class="card-content">
                            <div class="note-box parent">
                                <strong><i class="fas fa-home"></i> Untuk Orang Tua:</strong>
                                <p><?php echo nl2br(htmlspecialchars($catatanOrtu)); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="input_data_belajar.php" class="btn-primary" style="margin-right: 10px;">
                            <i class="fas fa-edit"></i> Update Data Belajar
                        </a>
                        <a href="siswa_dashboard.php" class="btn-primary" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>
