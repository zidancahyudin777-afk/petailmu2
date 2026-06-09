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
        .dashboard-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .dashboard-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .welcome-message {
            color: #666;
            font-size: 16px;
        }
        .student-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .student-info h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
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
        }
        .logout-btn {
            display: inline-block;
            background: #dc3545;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background: #c82333;
            color: #fff;
            text-decoration: none;
        }
        .nav-area {
            text-align: center;
            margin-top: 20px;
        }
        .nav-home-btn {
            background: #28a745;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-home-btn:hover {
            background: #218838;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h2>Dashboard Siswa</h2>
            <p class="welcome-message">Selamat datang di dashboard belajar Anda</p>
        </div>
        
        <?php if ($student): ?>
        <div class="student-info">
            <h3>Informasi Siswa</h3>
            <div class="info-row">
                <span class="info-label">Username</span>
                <span class="info-value"><?php echo htmlspecialchars($student['username']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nama</span>
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
        
        <div class="nav-area">
            <a href="logout.php?type=student" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            <a href="index.php" class="nav-home-btn">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
