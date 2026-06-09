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
        // Student not found, logout
        session_destroy();
        header("Location: siswa_login.php");
        exit;
    }
} catch (Exception $e) {
    error_log('Dashboard Error: ' . $e->getMessage());
    $student = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Siswa - Bimbingan Belajar Peta Ilmu</title>
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .dashboard-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .welcome-message {
            color: #666;
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-card h4 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
        }

        /* Student Info Card */
        .student-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .student-info h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-info h3 i {
            color: #667eea;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        /* Registration History */
        .history-section {
            margin-top: 30px;
        }

        .history-section h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }

        .history-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .history-card p {
            color: #666;
            margin-bottom: 10px;
        }

        .history-card p:last-child {
            margin-bottom: 0;
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

            .dashboard-container {
                padding: 15px;
            }

            .dashboard-header h2 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
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
                    <a href="siswa_dashboard.php" class="active">
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
                    <a href="#">
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
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h2>Dashboard Siswa</h2>
                    <p class="welcome-message">Selamat datang di dashboard belajar Anda</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4><i class="fas fa-user"></i> Status</h4>
                        <div class="stat-value"><?php echo $student ? 'Aktif' : '-'; ?></div>
                    </div>
                    <div class="stat-card">
                        <h4><i class="fas fa-graduation-cap"></i> Jenjang</h4>
                        <div class="stat-value"><?php echo $student ? strtoupper(htmlspecialchars($student['jenjang'])) : '-'; ?></div>
                    </div>
                    <div class="stat-card">
                        <h4><i class="fas fa-chalkboard-teacher"></i> Kelas</h4>
                        <div class="stat-value"><?php echo $student ? htmlspecialchars($student['kelas']) : '-'; ?></div>
                    </div>
                    <div class="stat-card">
                        <h4><i class="fas fa-calendar-check"></i> Pendaftaran</h4>
                        <div class="stat-value">1</div>
                    </div>
                </div>

                <?php if ($student): ?>
                <div class="student-info">
                    <h3><i class="fas fa-user-circle"></i> Informasi Siswa</h3>
                    <div class="info-row">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['username']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama Lengkap</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['nama']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['email'] ?? '-'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Jenjang</span>
                        <span class="info-value"><?php echo strtoupper(htmlspecialchars($student['jenjang'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kelas</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['kelas']); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Registration History -->
                <div class="history-section">
                    <h3><i class="fas fa-history"></i> Riwayat Pendaftaran</h3>
                    <div class="history-card">
                        <p><strong>Status:</strong> Terdaftar sebagai siswa Bimbingan Belajar Peta Ilmu</p>
                        <p><strong>Jenjang:</strong> <?php echo $student ? strtoupper(htmlspecialchars($student['jenjang'])) : '-'; ?></p>
                        <p><strong>Kelas:</strong> <?php echo $student ? htmlspecialchars($student['kelas']) : '-'; ?></p>
                        <p style="margin-top: 15px; font-size: 14px; color: #888;">
                            <i class="fas fa-info-circle"></i> Gunakan menu di samping untuk mengakses fitur pembelajaran.
                        </p>
                    </div>
                </div>
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
