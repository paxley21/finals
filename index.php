<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

/*
========================================
  LOGOUT
========================================
*/
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

/*
========================================
  DELETE SCHEDULE
========================================
*/
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $pdo->prepare("DELETE FROM student_schedule WHERE schedule_id = ?")
        ->execute([$id]);

    $pdo->prepare("DELETE FROM schedule WHERE id = ?")
        ->execute([$id]);

    header("Location: index.php");
    exit();
}

$message = "";

/*
========================================
  ADD CLASS + ASSIGN STUDENT
========================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {

    $course     = trim($_POST['course']);
    $room_id    = $_POST['room_id'];
    $day        = $_POST['day'];
    $start      = $_POST['start'];
    $end        = $_POST['end'];
    $teacher_id = $_POST['teacher_id'];
    $student_id = $_POST['student_id'];

    if ($start >= $end) {
        $message = "<div class='error'>❌ End time must be later than start time.</div>";
    } else {

        $check = $pdo->prepare("
            SELECT * FROM schedule
            WHERE room_id = ?
            AND day_of_week = ?
            AND (start_time < ? AND end_time > ?)
        ");

        $check->execute([$room_id, $day, $end, $start]);

        if ($check->rowCount() > 0) {

            $conflict = $check->fetch(PDO::FETCH_ASSOC);

            $message = "<div class='error'>
                ❌ Room conflict with <strong>" .
                htmlspecialchars($conflict['course_name']) .
                "</strong>
            </div>";

        } else {

            $insert = $pdo->prepare("
                INSERT INTO schedule
                (course_name, teacher_id, room_id, day_of_week, start_time, end_time)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $course,
                $teacher_id,
                $room_id,
                $day,
                $start,
                $end
            ]);

            $schedule_id = $pdo->lastInsertId();

            $assign = $pdo->prepare("
                INSERT INTO student_schedule (student_id, schedule_id)
                VALUES (?, ?)
            ");

            $assign->execute([$student_id, $schedule_id]);

            $message = "<div class='success'>✅ Schedule created successfully!</div>";
        }
    }
}

/*
========================================
  FETCH DATA
========================================
*/
$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);

$students = $pdo->query("
    SELECT id, username, section 
    FROM users 
    WHERE role='student'
    ORDER BY section, username
")->fetchAll(PDO::FETCH_ASSOC);

$teachers = $pdo->query("
    SELECT id, username FROM users WHERE role='teacher'
")->fetchAll(PDO::FETCH_ASSOC);

$schedule = $pdo->query("
    SELECT 
        schedule.*,
        rooms.room_name,
        student.username AS student_name,
        student.section AS student_section,
        teacher.username AS teacher_name
    FROM schedule
    JOIN rooms ON schedule.room_id = rooms.id
    LEFT JOIN student_schedule ON schedule.id = student_schedule.schedule_id
    LEFT JOIN users student ON student_schedule.student_id = student.id
    LEFT JOIN users teacher ON schedule.teacher_id = teacher.id
    ORDER BY 
        student.section,
        student.username,
        FIELD(schedule.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'),
        start_time
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Classroom Schedule Manager</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="main-container">

    <div class="header-banner">
        <h1>Classroom Schedule Manager</h1>
        <p>Welcome, <?= htmlspecialchars($admin_name) ?> 👋</p>
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>

    <div class="content-padding">

        <div class="status-msg"><?= $message ?></div>

        <!-- FORM -->
        <form method="POST">
            <div class="schedule-form">

            <div class="form-group">
                   <label>Section</label>
                    <input type="text" name="section" required>
            </div>

                <div class="form-group">
                    <label>Student</label>
                    <select name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="course" required>
                </div>

                <div class="form-group">
                    <label>Room</label>
                    <select name="room_id" required>
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>">
                                <?= htmlspecialchars($r['room_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Day</label>
                    <select name="day" required>
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                        <option>Saturday</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Teacher</label>
                    <select name="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start" required>
                </div>

                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end" required>
                </div>

                <button type="submit" name="add_class">Add Schedule</button>

                <button type="button" name="add_class">
                    <a href="classrooms.php">Manage Rooms</a>
                </button>

            </div>
        </form>

        <hr>

        <h3>Schedules</h3>
        <div style="margin-bottom: 15px;">
    <input type="text"
           id="searchInput"
           placeholder="Search student..."
           style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
</div>

        <?php
$current_student = '';

foreach ($schedule as $row):

    $student = $row['student_name'] ?? 'Unknown Student';
    $section = $row['student_section'] ?? 'No Section';

    $group_key = $student . '|' . $section;

    if ($current_student !== $group_key):

        if ($current_student !== '') {
            echo "</tbody></table></div><br>";
        }

        $current_student = $group_key;
?>

<div class="table-container">
    <h4>
        Student: <?= htmlspecialchars($student) ?> 
        (<?= htmlspecialchars($section) ?>)
    </h4>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Room</th>
                <th>Day</th>
                <th>Time</th>
                <th>Teacher</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

<?php endif; ?>

<tr>
    <td><?= htmlspecialchars($row['course_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['room_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['day_of_week'] ?? '') ?></td>
    <td>
        <?= date("g:i A", strtotime($row['start_time'])) ?> -
        <?= date("g:i A", strtotime($row['end_time'])) ?>
    </td>
    <td><?= htmlspecialchars($row['teacher_name'] ?? 'N/A') ?></td>
    <td>
        <a href="edit_schedule.php?id=<?= $row['id'] ?>" class="edit-link">Edit</a>
        <a href="?delete=<?= $row['id'] ?>"
           onclick="return confirm('Delete this schedule?')" class="delete-link">
           Delete
        </a>
    </td>
</tr>

<?php endforeach; ?>

<?php if (!empty($schedule)): ?>
    </tbody>
    </table>
</div>
<?php endif; ?>
    </div>
</div>

</body>
<script>
document.getElementById("searchInput").addEventListener("keyup", function () {

    let value = this.value.toLowerCase();
    let sections = document.querySelectorAll(".table-container");

    sections.forEach(section => {

        let text = section.innerText.toLowerCase();

        if (text.includes(value)) {
            section.style.display = "";
        } else {
            section.style.display = "none";
        }
    });

});
</script>
</html>