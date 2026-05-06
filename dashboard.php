<?php
session_start();
require 'db_config.php';

/*
========================================
  1. AUTH CHECK
========================================
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/*
========================================
  2. USER INFO
========================================
*/
$username = $_SESSION['username'] ?? 'User';
$student_id = $_SESSION['user_id'];

/*
========================================
  3. GREETING
========================================
*/
$hour = date("H");

if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

/*
========================================
  4. FETCH STUDENT SCHEDULE (FIXED)
========================================
*/
$stmt = $pdo->prepare("
    SELECT 
        s.course_name,
        s.day_of_week,
        s.start_time,
        s.end_time,
        r.room_name,
        u.username AS teacher_name
    FROM student_schedule ss
    JOIN schedule s ON ss.schedule_id = s.id
    JOIN rooms r ON s.room_id = r.id
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE ss.student_id = ?
    ORDER BY FIELD(
        s.day_of_week,
        'Monday','Tuesday','Wednesday','Thursday','Friday'
    ), s.start_time
");

$stmt->execute([$student_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: 0.3s;
            border-left: 5px solid var(--dark-blue);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.1);
        }

        .dashboard-card h3 {
            margin: 0;
            color: var(--dark-blue);
            font-size: 1rem;
        }

        .dashboard-card p {
            font-size: 2rem;
            margin: 10px 0 0;
            font-weight: bold;
            color: #333;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .quick-links a {
            text-decoration: none;
            background: var(--dark-blue);
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
        }

        .quick-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(7,59,210,0.3);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>

<div class="main-container fade-in">

    <div class="header-banner">
        <div class="top-bar">
            <div>
                <h1>Dashboard</h1>
                <p><?= $greeting . ', ' . htmlspecialchars($username) . '!' ?></p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="content-padding">

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-cards">

            <div class="dashboard-card">
                <h3>My Total Classes</h3>
                <p><?= count($schedules) ?></p>
            </div>

            <div class="dashboard-card">
                <h3>Today's Classes</h3>
                <p>
                    <?php
                    $today = date("l");
                    $todayCount = 0;

                    foreach ($schedules as $sched) {
                        if ($sched['day_of_week'] === $today) {
                            $todayCount++;
                        }
                    }

                    echo $todayCount;
                    ?>
                </p>
            </div>

            <div class="dashboard-card">
                <h3>Current Day</h3>
                <p style="font-size:1.2rem;"><?= date("l") ?></p>
            </div>

            <?php
date_default_timezone_set('Asia/Manila');
?>

<div class="dashboard-card">
    <h3>Current Time</h3>
    <p style="font-size:1.2rem;"><?= date("h:i A") ?></p>
</div>

        </div>

        <!-- QUICK LINKS -->
        <div class="quick-links">
            <a href="timetable.php">View Timetable</a>
            <a href="profile.php">My Profile</a>
        </div>

        <!-- TABLE -->
        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Room</th>
                        <th>Day</th>
                        <th>Time</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">
                            No schedules assigned yet
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td class="course-name">
                                <?= htmlspecialchars($row['course_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['teacher_name'] ?? 'Not Assigned') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['room_name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['day_of_week']) ?>
                            </td>

                            <td>
    <span class="time-tag">
        <?= date("g:i A", strtotime($row['start_time'])) ?> -
        <?= date("g:i A", strtotime($row['end_time'])) ?>
    </span>
</td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>
</div>

</body>
</html>