<?php
session_start();
require 'db_config.php';

/*
========================================
  AUTH CHECK (ADMIN ONLY)
========================================
*/
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

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

$message = "";

/*
========================================
  ADD CLASSROOM
========================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {

    $room_name = trim($_POST['room_name']);
    $capacity  = intval($_POST['capacity']);

    if ($room_name === "" || $capacity <= 0) {
        $message = "<div class='error'>❌ Invalid input.</div>";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO rooms (room_name, capacity)
            VALUES (?, ?)
        ");

        $stmt->execute([$room_name, $capacity]);

        $message = "<div class='success'>✅ Classroom added successfully!</div>";
    }
}

/*
========================================
  DELETE CLASSROOM
========================================
*/
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    if ($id > 0) {

        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);

        $message = "<div class='success'>🗑️ Classroom deleted successfully!</div>";
    } else {
        $message = "<div class='error'>❌ Invalid room ID.</div>";
    }
}

/*
========================================
  FETCH ROOMS
========================================
*/
$rooms = $pdo->query("
    SELECT * FROM rooms ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Classroom Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="main-container">

    <!-- HEADER -->
    <div class="header-banner">
        <h1>Classroom Schedule Manager</h1>

        <p>
            Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?> 👋
        </p>

        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>

    <!-- CONTENT -->
    <div class="content-padding">

        <?= $message ?>

        <!-- ADD ROOM FORM -->
        <form method="POST" class="schedule-form">

            <div class="form-group">
                <label>Room Name</label>
                <input type="text" name="room_name" placeholder="e.g. Room 101" required>
            </div>

            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" placeholder="e.g. 30" min="1" required>
            </div>

            <button type="submit" name="add_room">
                Add Classroom
            </button>

        </form>

        <hr style="margin:30px 0;">

        <h3>Available Classrooms</h3>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room Name</th>
                        <th>Capacity</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">
                            No classrooms found
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['id']) ?></td>
                            <td><?= htmlspecialchars($room['room_name']) ?></td>
                            <td><?= htmlspecialchars($room['capacity']) ?></td>

                            <td>
                                <a href="edit_room.php?id=<?= $room['id'] ?>" class="edit-link">
                                    Edit
                                </a>

                                <a href="?delete=<?= $room['id'] ?>"
                                   onclick="return confirm('Delete this room?')"
                                   class="delete-link">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- BACK BUTTON -->
        <div class="back">
            <a href="index.php">← Back</a>
        </div>

    </div>

</div>

</body>
</html>