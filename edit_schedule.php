<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid request");
}

// GET EXISTING DATA
$stmt = $pdo->prepare("SELECT * FROM schedule WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die("Schedule not found");
}

// GET ROOMS
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);

// GET TEACHERS (FIXED)
$teachers = $pdo->query("SELECT id, username FROM users WHERE role='teacher'")
                ->fetchAll(PDO::FETCH_ASSOC);

// UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course   = $_POST['course'];
    $teacher  = $_POST['teacher_id'];
    $room_id  = $_POST['room_id'];
    $day      = $_POST['day'];
    $start    = $_POST['start'];
    $end      = $_POST['end'];

    $update = $pdo->prepare("
        UPDATE schedule 
        SET course_name=?, teacher_id=?, room_id=?, day_of_week=?, start_time=?, end_time=?
        WHERE id=?
    ");

    $update->execute([
        $course,
        $teacher,
        $room_id,
        $day,
        $start,
        $end,
        $id
    ]);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="main-container">
    <div class="header-banner">
        <h1>Edit Schedule</h1>
    </div>

    <div class="content-padding">

        <form method="POST" class="schedule-form">

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="course"
                    value="<?= htmlspecialchars($schedule['course_name']) ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Teacher</label>
                <select name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"
                            <?= $t['id'] == $schedule['teacher_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Room</label>
                <select name="room_id" required>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"
                            <?= $room['id'] == $schedule['room_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($room['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Day</label>
                <select name="day" required>
                    <?php
                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                    foreach ($days as $d):
                    ?>
                        <option value="<?= $d ?>"
                            <?= $schedule['day_of_week'] == $d ? 'selected' : '' ?>>
                            <?= $d ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Start Time</label>
                <input type="time" name="start"
    value="<?= substr($schedule['start_time'], 0, 5) ?>"
    required>

            </div>

            <div class="form-group">
                <label>End Time</label>
                <input type="time" name="end"
    value="<?= substr($schedule['end_time'], 0, 5) ?>"
    required>
            </div>

            <button type="submit" class="auth-btn">Update Schedule</button>

        </form>

        <div class="back">
            <a href="index.php">← Back</a>
        </div>

    </div>
</div>

</body>
</html>