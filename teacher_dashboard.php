<?php
session_start();
require_once "db_config.php";

/*
========================================
  AUTH CHECK
========================================
*/
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$teacher_email = $_SESSION['teacher_email'] ?? 'teacher@school.edu';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    /*
    ========================================
      FETCH TEACHER PROFILE INFO
    ========================================
    */
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    ========================================
      FETCH SCHEDULE (WITH SECTION + STUDENTS)
    ========================================
    */
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.course_name,
            s.day_of_week,
            s.start_time,
            s.end_time,
            r.room_name,
            u.section,
            u.username AS student_name
        FROM schedule s
        JOIN rooms r ON s.room_id = r.id
        LEFT JOIN student_schedule ss ON s.id = ss.schedule_id
        LEFT JOIN users u ON ss.student_id = u.id
        WHERE s.teacher_id = ?
        ORDER BY 
            u.section,
            s.day_of_week,
            s.start_time,
            u.username
    ");

    $stmt->execute([$teacher_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    ========================================
      FETCH STATISTICS
    ========================================
    */
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT student_id) as total_students 
        FROM student_schedule 
        WHERE schedule_id IN (
            SELECT id FROM schedule WHERE teacher_id = ?
        )
    ");
    $stmt->execute([$teacher_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_students = $stats['total_students'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_classes FROM schedule WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_classes = $stats['total_classes'] ?? 0;

} catch (PDOException $e) {
    die("SQL ERROR: " . $e->getMessage());
}

/*
========================================
  GROUP BY COURSE, TIME, AND SECTION
========================================
*/
$grouped = [];

foreach ($schedules as $row) {
    $course = $row['course_name'] ?? 'Unknown Course';
    $time = date("g:i A", strtotime($row['start_time'])) . " - " .
            date("g:i A", strtotime($row['end_time']));
    $section = $row['section'] ?? 'No Section';
    
    // Create a unique key for grouping: Course | Time
    $groupKey = $course . " | " . $time;
    
    // Initialize if not exists
    if (!isset($grouped[$groupKey])) {
        $grouped[$groupKey] = [
            'course' => $course,
            'time' => $time,
            'room' => $row['room_name'],
            'day' => $row['day_of_week'],
            'students' => []
        ];
    }
    
    // Add student with their section
    if (!empty($row['student_name'])) {
        $grouped[$groupKey]['students'][] = [
            'name' => $row['student_name'],
            'section' => $section
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1e3a8a;
            --primary-light: #3b82f6;
            --secondary: #059669;
            --accent: #06b6d4;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --gradient-1: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --gradient-2: linear-gradient(135deg, #059669 0%, #10b981 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Top Navigation */
        .navbar {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            animation: slideDown 0.5s ease-out;
        }

        .navbar-brand {
            font-size: 20px;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--bg-light);
            border: 1px solid var(--border);
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            border: none;
        }

        .btn-icon:hover {
            background: var(--primary-light);
            color: white;
            transform: translateY(-2px);
        }

        .btn-logout {
            padding: 8px 16px;
            background: var(--gradient-1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(30, 58, 138, 0.3);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Header Section */
        .header-section {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            margin-bottom: 32px;
            align-items: center;
        }

        .header-content h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .header-content p {
            font-size: 14px;
            color: var(--text-light);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 0.6s ease-out both;
            transition: all 0.3s ease;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Search Bar */
        .search-section {
            margin-bottom: 24px;
        }

        .search-input {
            width: 100%;
            max-width: 400px;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Schedule Table */
        .schedule-card {
            background: var(--bg-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .schedule-header {
            padding: 24px;
            background: var(--gradient-1);
            color: white;
            font-size: 18px;
            font-weight: 700;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-light);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .section-header {
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            padding: 16px;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h2 {
            font-size: 22px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            color: var(--text-dark);
            transform: rotate(90deg);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin: 0 auto 16px;
        }

        .profile-info {
            text-align: center;
            margin-bottom: 24px;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .profile-email {
            font-size: 14px;
            color: var(--text-light);
        }

        .profile-detail {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg-light);
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .profile-detail-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .profile-detail-value {
            color: var(--text-light);
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-section {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .navbar {
                flex-wrap: wrap;
                gap: 12px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                padding: 24px;
            }

            th, td {
                padding: 12px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="navbar-brand">📚 Teacher Dashboard</div>
        <div class="navbar-actions">
            <button class="btn-icon" id="profileBtn" title="View Profile">👤</button>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="header-content">
                <h1>Welcome, <?= htmlspecialchars($teacher_name) ?></h1>
                <p>Manage your teaching schedule and student information</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-value"><?= $total_students ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?= $total_classes ?></div>
                <div class="stat-label">Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📍</div>
                <div class="stat-value"><?= count($grouped) ?></div>
                <div class="stat-label">Sections</div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-section">
            <input 
                type="text" 
                id="tableSearch" 
                class="search-input"
                placeholder="🔍 Search by student name, course, room, or day..."
            >
        </div>

        <!-- Schedule Table -->
        <div class="schedule-card">
            <div class="schedule-header">📅 Teaching Schedule</div>
            <div class="table-responsive">
                <table>
                    
                    <tbody>
                        <?php if (empty($grouped)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📭</div>
                                        <p>No students assigned yet</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($grouped as $groupKey => $group): ?>
                                <tr class="section-header">
                                    <td colspan="5">
                                        📚 <?= htmlspecialchars($group['course']) ?> 
                                        <span style="margin-left: 16px; font-weight: normal; color: #64748b;">
                                            ⏰ <?= htmlspecialchars($group['time']) ?> | 📍 <?= htmlspecialchars($group['room']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php foreach ($group['students'] as $student): ?>
                                    <tr>
                                        
                                        
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td>
                                            <span style="background: #f0fdf4; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                                <?= htmlspecialchars($student['section']) ?>
                                            </span>
                                        </td>
                                    
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>👤 Teacher Profile</h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>

            <div class="profile-info">
                <div class="profile-avatar">
                    <?= strtoupper(substr($teacher_name, 0, 1)) ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($teacher_name) ?></div>
                <div class="profile-email"><?= htmlspecialchars($teacher_email) ?></div>
            </div>

            <div>
                <div class="profile-detail">
                    <span class="profile-detail-label">Total Students</span>
                    <span class="profile-detail-value"><?= $total_students ?></span>
                </div>
                <div class="profile-detail">
                    <span class="profile-detail-label">Classes</span>
                    <span class="profile-detail-value"><?= $total_classes ?></span>
                </div>
                <div class="profile-detail">
                    <span class="profile-detail-label">Sections</span>
                    <span class="profile-detail-value"><?= count($grouped) ?></span>
                </div>
                <div class="profile-detail">
                    <span class="profile-detail-label">Member Since</span>
                    <span class="profile-detail-value"><?= date('M d, Y', strtotime($teacher['created_at'] ?? date('Y-m-d'))) ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const profileBtn = document.getElementById('profileBtn');
        const profileModal = document.getElementById('profileModal');
        const closeModal = document.getElementById('closeModal');
        const tableSearch = document.getElementById('tableSearch');

        // Profile Modal
        profileBtn.addEventListener('click', () => {
            profileModal.classList.add('active');
        });

        closeModal.addEventListener('click', () => {
            profileModal.classList.remove('active');
        });

        profileModal.addEventListener('click', (e) => {
            if (e.target === profileModal) {
                profileModal.classList.remove('active');
            }
        });

        // Search Functionality
        tableSearch.addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                if (row.classList.contains('section-header')) {
                    row.style.display = '';
                } else {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(value) ? '' : 'none';
                }
            });
        });
    </script>
</body>
</html>