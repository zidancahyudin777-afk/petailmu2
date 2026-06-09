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

$message = '';
$messageType = '';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mataPelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $nilai = $_POST['nilai'] ?? '';
    $tingkatKesulitan = $_POST['tingkat_kesulitan'] ?? '';
    $gayaBelajar = $_POST['gaya_belajar'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    // Validation
    if (empty($mataPelajaran)) {
        $message = 'Mata pelajaran wajib diisi!';
        $messageType = 'error';
    } elseif (empty($nilai)) {
        $message = 'Nilai terakhir wajib diisi!';
        $messageType = 'error';
    } elseif (empty($tingkatKesulitan)) {
        $message = 'Tingkat kesulitan wajib dipilih!';
        $messageType = 'error';
    } elseif (empty($gayaBelajar)) {
        $message = 'Gaya belajar wajib dipilih!';
        $messageType = 'error';
    } else {
        // Validate nilai range
        if ($nilai < 0 || $nilai > 100) {
            $message = 'Nilai harus dalam rentang 0 sampai 100!';
            $messageType = 'error';
        } else {
            try {
                // Save learning data
                $query = "INSERT INTO learning_data (student_id, mata_pelajaran, nilai, tingkat_kesulitan, gaya_belajar, catatan, tanggal_input) 
                          VALUES (:student_id, :mata_pelajaran, :nilai, :tingkat_kesulitan, :gaya_belajar, :catatan, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':student_id' => $_SESSION['student_id'],
                    ':mata_pelajaran' => $mataPelajaran,
                    ':nilai' => $nilai,
                    ':tingkat_kesulitan' => $tingkatKesulitan,
                    ':gaya_belajar' => $gayaBelajar,
                    ':catatan' => !empty($catatan) ? $catatan : null
                ]);
                
                $message = 'Data belajar berhasil disimpan!';
                $messageType = 'success';
                
                // Clear POST data
                $_POST = [];
            } catch (Exception $e) {
                $message = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                $messageType = 'error';
                error_log('Save Learning Data Error: ' . $e->getMessage());
            }
        }
    }
}

// Get available subjects from database
$mataPelajaranList = [];
try {
    $query = "SELECT kode, nama FROM mata_pelajaran ORDER BY nama";
    $stmt = $pdo->query($query);
    $mataPelajaranList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching subjects: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Input Data Belajar - Bimbingan Belajar Peta Ilmu</title>
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .form-header .description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section-title {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group .helper-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
            font-style: italic;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        select.form-control {
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

            .form-container {
                padding: 20px;
            }

            .form-header h2 {
                font-size: 20px;
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
                    <a href="input_data_belajar.php" class="active">
                        <i class="fas fa-edit"></i>
                        <span>Input Data Belajar</span>
                    </a>
                </li>
                <li>
                    <a href="perkembangan_belajar.php">
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
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-pen-to-square"></i> Input Data Belajar</h2>
                    <p class="description">
                        <i class="fas fa-info-circle"></i> Isi data belajar berdasarkan hasil belajar terakhir. 
                        Data ini akan digunakan sistem untuk memberikan rekomendasi layanan pembelajaran yang sesuai.
                    </p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="message <?php echo htmlspecialchars($messageType); ?>">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <!-- Data Belajar Section -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-book-open"></i> Informasi Belajar
                        </h3>

                        <!-- Mata Pelajaran -->
                        <div class="form-group">
                            <label for="mata_pelajaran">Mata Pelajaran <span style="color: #dc3545;">*</span></label>
                            <select id="mata_pelajaran" name="mata_pelajaran" class="form-control" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($mataPelajaranList as $mp): ?>
                                    <option value="<?php echo htmlspecialchars($mp['nama']); ?>" 
                                            <?php echo (isset($_POST['mata_pelajaran']) && $_POST['mata_pelajaran'] === $mp['nama']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mp['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="helper-text"><i class="fas fa-question-circle"></i> Pilih mata pelajaran yang ingin Anda catat.</p>
                        </div>

                        <!-- Nilai Terakhir -->
                        <div class="form-group">
                            <label for="nilai">Nilai Terakhir <span style="color: #dc3545;">*</span></label>
                            <input type="number" id="nilai" name="nilai" class="form-control" 
                                   placeholder="Contoh: 85" 
                                   min="0" max="100" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['nilai'] ?? ''); ?>" required />
                            <p class="helper-text"><i class="fas fa-question-circle"></i> Masukkan nilai dalam rentang 0 sampai 100.</p>
                        </div>

                        <!-- Tingkat Kesulitan -->
                        <div class="form-group">
                            <label for="tingkat_kesulitan">Tingkat Kesulitan Materi <span style="color: #dc3545;">*</span></label>
                            <select id="tingkat_kesulitan" name="tingkat_kesulitan" class="form-control" required>
                                <option value="">-- Pilih Tingkat Kesulitan --</option>
                                <option value="mudah" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] === 'mudah') ? 'selected' : ''; ?>>Mudah</option>
                                <option value="sedang" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] === 'sedang') ? 'selected' : ''; ?>>Sedang</option>
                                <option value="sulit" <?php echo (isset($_POST['tingkat_kesulitan']) && $_POST['tingkat_kesulitan'] === 'sulit') ? 'selected' : ''; ?>>Sulit</option>
                            </select>
                            <p class="helper-text"><i class="fas fa-question-circle"></i> Pilih tingkat kesulitan materi yang dirasakan.</p>
                        </div>

                        <!-- Gaya Belajar -->
                        <div class="form-group">
                            <label for="gaya_belajar">Gaya Belajar <span style="color: #dc3545;">*</span></label>
                            <select id="gaya_belajar" name="gaya_belajar" class="form-control" required>
                                <option value="">-- Pilih Gaya Belajar --</option>
                                <option value="visual" <?php echo (isset($_POST['gaya_belajar']) && $_POST['gaya_belajar'] === 'visual') ? 'selected' : ''; ?>>Visual (Belajar dengan melihat/gambar)</option>
                                <option value="auditori" <?php echo (isset($_POST['gaya_belajar']) && $_POST['gaya_belajar'] === 'auditori') ? 'selected' : ''; ?>>Auditori (Belajar dengan mendengar)</option>
                                <option value="kinestetik" <?php echo (isset($_POST['gaya_belajar']) && $_POST['gaya_belajar'] === 'kinestetik') ? 'selected' : ''; ?>>Kinestetik (Belajar dengan praktik/gerak)</option>
                                <option value="membaca" <?php echo (isset($_POST['gaya_belajar']) && $_POST['gaya_belajar'] === 'membaca') ? 'selected' : ''; ?>>Membaca/Menulis (Belajar dengan teks)</option>
                            </select>
                            <p class="helper-text"><i class="fas fa-question-circle"></i> Pilih gaya belajar yang paling sesuai dengan kebiasaan belajar siswa.</p>
                        </div>

                        <!-- Catatan -->
                        <div class="form-group">
                            <label for="catatan">Catatan Belajar</label>
                            <textarea id="catatan" name="catatan" class="form-control" 
                                      placeholder="Tuliskan kendala atau hal penting terkait proses belajar..."><?php echo htmlspecialchars($_POST['catatan'] ?? ''); ?></textarea>
                            <p class="helper-text"><i class="fas fa-question-circle"></i> Tuliskan kendala atau hal penting terkait proses belajar.</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Simpan Data Belajar
                    </button>
                </form>
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
