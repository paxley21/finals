<?php
session_start();
require 'db_config.php';

/*
========================================
  AUTH CHECK
========================================
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Student';

/*
========================================
  FETCH STUDENT TIMETABLE
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

/*
========================================
  GROUP BY DAY
========================================
*/
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$timetable = [];

foreach ($days as $day) {
    $timetable[$day] = [];
}

foreach ($schedules as $row) {
    $timetable[$row['day_of_week']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Timetable</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .timetable-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .day-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 15px;
            border-top: 5px solid var(--dark-blue);
        }

        .day-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--dark-blue);
            margin-bottom: 10px;
        }

        .class-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--dark-blue);
        }

        .class-box:hover {
            background: #eef4ff;
        }

        .course {
            font-weight: bold;
            color: #333;
        }

        .time {
            font-size: 0.85rem;
            color: #666;
        }

        .meta {
            font-size: 0.85rem;
            color: #444;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back {
    margin-bottom: 40px;
    margin-top: 20px;
}

.back a {
    display: inline-block;
    background: var(--dark-blue);
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(7, 59, 210, 0.2);
}

.back a:hover {
    transform: translateY(-2px);
    background: #062fa3;
    box-shadow: 0 6px 15px rgba(7, 59, 210, 0.3);
}

.back a:active {
    transform: translateY(0);
    box-shadow: 0 3px 8px rgba(7, 59, 210, 0.2);
}
    </style>
</head>

<body>

<div class="main-container fade-in">

    <!-- HEADER -->
    <div class="header-banner">
        <div class="top-header">
            <div>
                <h1>My Timetable</h1>
                <p><?= htmlspecialchars($username) ?>'s Weekly Schedule</p>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="content-padding">

        <?php if (empty($schedules)): ?>
            <p style="text-align:center;">No timetable available yet.</p>
        <?php else: ?>

        <div class="timetable-grid">

            <?php foreach ($timetable as $day => $classes): ?>

                <div class="day-card">
                    <div class="day-title"><?= $day ?></div>

                    <?php if (empty($classes)): ?>
                        <p style="color:#999;">No classes</p>
                    <?php else: ?>

                        <?php foreach ($classes as $c): ?>
                            <div class="class-box">
                                <div class="course">
                                    <?= htmlspecialchars($c['course_name']) ?>
                                </div>

                                <div class="time">
    <span>
        <?= date("g:i A", strtotime($row['start_time'])) ?> -
        <?= date("g:i A", strtotime($row['end_time'])) ?>
    </span>

                                </div>

                                <div class="meta">
                                    <?= htmlspecialchars($c['teacher_name'] ?? 'TBA') ?><br>
                                    <?= htmlspecialchars($c['room_name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>

            <?php endforeach; ?>

        </div>

        <?php endif; ?>
        <div class="back">
    <a href="dashboard.php">Back</a>
</div>
    </div>
</div>

</body>
</html>