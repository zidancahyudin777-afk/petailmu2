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

// Get learning data for the logged-in student
$learningData = [];
$summaryStats = [
    'total_records' => 0,
    'average_score' => 0,
    'highest_score' => 0,
    'lowest_score' => 0,
    'latest_date' => null
];

try {
    $query = "SELECT * FROM learning_data 
              WHERE student_id = :student_id 
              ORDER BY tanggal_input DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':student_id' => $_SESSION['student_id']]);
    $learningData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    if (!empty($learningData)) {
        $scores = array_column($learningData, 'nilai');
        $scores = array_map('floatval', $scores);
        
        $summaryStats['total_records'] = count($learningData);
        $summaryStats['average_score'] = round(array_sum($scores) / count($scores), 2);
        $summaryStats['highest_score'] = max($scores);
        $summaryStats['lowest_score'] = min($scores);
        
        // Get latest date
        $latestRecord = $learningData[0];
        $summaryStats['latest_date'] = date('d F Y', strtotime($latestRecord['tanggal_input']));
    }
} catch (Exception $e) {
    error_log('Error fetching learning data: ' . $e->getMessage());
    $learningData = [];
}

// Prepare data for chart (score over time)
$chartLabels = [];
$chartScores = [];
if (!empty($learningData)) {
    // Reverse to show oldest to newest
    $reversedData = array_reverse($learningData);
    foreach ($reversedData as $index => $record) {
        $chartLabels[] = date('d/m', strtotime($record['tanggal_input']));
        $chartScores[] = floatval($record['nilai']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perkembangan Belajar - Bimbingan Belajar Peta Ilmu</title>
    <link rel="stylesheet" href="stylemain.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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

        .page-description {
            color: #666;
            font-size: 15px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            display: inline-block;
        }

        /* Summary Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .stat-card h4 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
        }

        .stat-card .stat-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }

        /* Chart Section */
        .chart-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .chart-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Table Section */
        .table-section {
            margin-top: 30px;
        }

        .table-section h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .data-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .data-table th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .data-table td {
            padding: 15px 12px;
            font-size: 14px;
            color: #333;
        }

        .data-table tbody tr:last-child {
            border-bottom: none;
        }

        /* Difficulty badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-mudah {
            background: #d4edda;
            color: #155724;
        }

        .badge-sedang {
            background: #fff3cd;
            color: #856404;
        }

        .badge-sulit {
            background: #f8d7da;
            color: #721c24;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 30px 0;
        }

        .empty-state i {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
            font-size: 15px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
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

            .content-container {
                padding: 15px;
            }

            .page-header h2 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 12px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }

            .chart-container {
                height: 250px;
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
                    <a href="perkembangan_belajar.php" class="active">
                        <i class="fas fa-chart-line"></i>
                        <span>Perkembangan Belajar</span>
                    </a>
                </li>
                <li>
                    <a href="rekomendasi_belajar.php">
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
            <div class="content-container">
                <div class="page-header">
                    <h2><i class="fas fa-chart-line"></i> Perkembangan Belajar</h2>
                    <p class="page-description">
                        <i class="fas fa-info-circle"></i> Halaman ini menampilkan riwayat dan perkembangan belajar siswa berdasarkan data belajar yang telah diinput.
                    </p>
                </div>

                <?php if (empty($learningData)): ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>Belum Ada Data Belajar</h3>
                        <p>Belum ada data belajar. Silakan isi data belajar terlebih dahulu untuk melihat perkembangan.</p>
                        <a href="input_data_belajar.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i>
                            Input Data Belajar
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Summary Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4><i class="fas fa-database"></i> Total Data</h4>
                            <div class="stat-value"><?php echo $summaryStats['total_records']; ?></div>
                            <div class="stat-label">Riwayat belajar</div>
                        </div>
                        <div class="stat-card">
                            <h4><i class="fas fa-star"></i> Rata-rata Nilai</h4>
                            <div class="stat-value"><?php echo number_format($summaryStats['average_score'], 2); ?></div>
                            <div class="stat-label">Dari semua mata pelajaran</div>
                        </div>
                        <div class="stat-card">
                            <h4><i class="fas fa-trophy"></i> Nilai Tertinggi</h4>
                            <div class="stat-value"><?php echo number_format($summaryStats['highest_score'], 2); ?></div>
                            <div class="stat-label">Prestasi terbaik</div>
                        </div>
                        <div class="stat-card">
                            <h4><i class="fas fa-chart-bar"></i> Nilai Terendah</h4>
                            <div class="stat-value"><?php echo number_format($summaryStats['lowest_score'], 2); ?></div>
                            <div class="stat-label">Perlu ditingkatkan</div>
                        </div>
                        <div class="stat-card">
                            <h4><i class="fas fa-calendar-check"></i> Terakhir Input</h4>
                            <div class="stat-value" style="font-size: 18px;"><?php echo $summaryStats['latest_date']; ?></div>
                            <div class="stat-label">Tanggal update terakhir</div>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div class="chart-section">
                        <h3><i class="fas fa-chart-area"></i> Grafik Perkembangan Nilai</h3>
                        <div class="chart-container">
                            <canvas id="scoreChart"></canvas>
                        </div>
                    </div>

                    <!-- Learning History Table -->
                    <div class="table-section">
                        <h3><i class="fas fa-history"></i> Riwayat Data Belajar</h3>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Nilai</th>
                                    <th>Tingkat Kesulitan</th>
                                    <th>Gaya Belajar</th>
                                    <th>Catatan</th>
                                    <th>Tanggal Input</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($learningData as $record): 
                                    $difficultyClass = '';
                                    switch(strtolower($record['tingkat_kesulitan'])) {
                                        case 'mudah': $difficultyClass = 'badge-mudah'; break;
                                        case 'sedang': $difficultyClass = 'badge-sedang'; break;
                                        case 'sulit': $difficultyClass = 'badge-sulit'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($record['mata_pelajaran']); ?></td>
                                    <td><strong><?php echo number_format($record['nilai'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $difficultyClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($record['tingkat_kesulitan'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(ucfirst($record['gaya_belajar'] ?? '-')); ?></td>
                                    <td><?php echo !empty($record['catatan']) ? htmlspecialchars($record['catatan']) : '<em>-</em>'; ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($record['tanggal_input'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

        // Initialize Chart
        <?php if (!empty($learningData)): ?>
        const ctx = document.getElementById('scoreChart').getContext('2d');
        const scoreChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Nilai',
                    data: <?php echo json_encode($chartScores); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Nilai: ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10
                        },
                        title: {
                            display: true,
                            text: 'Nilai'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
