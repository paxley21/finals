<?php
session_start();
require_once "db_config.php";

$message = "";

// 🔐 MASTER AUTH PASSWORD
$MASTER_ADMIN_KEY = "ADMIN@12345";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $auth_key = $_POST['auth_key'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';

    // 1. VALIDATION CHECK
    if (empty($username) || empty($email) || empty($password_raw) || empty($auth_key)) {
        $message = "<div class='error'>❌ Please fill in all fields.</div>";
    }

    // 2. AUTH KEY CHECK
    elseif ($auth_key !== $MASTER_ADMIN_KEY) {
        $message = "<div class='error'>❌ Unauthorized access: Invalid admin key.</div>";
    }

    else {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, 'admin')
            ");

            $stmt->execute([$username, $email, $password]);

            $message = "<div class='success'>✅ Admin account created successfully!</div>";

        } catch (Exception $e) {
            $message = "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Create Admin Account</h2>
        <p>Authorized access required</p>
    </div>

    <div class="content-padding">

        <?= $message ?>

        <form method="POST" class="auth-form">

            <!-- USERNAME -->
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <i class="fa fa-eye" onclick="togglePassword('password','passwordIcon')"></i>
                </div>
            </div>

            <!-- AUTH KEY -->
            <div class="form-group">
                <label>Admin Authorization Key</label>
                <div class="password-wrapper">
                    <input type="password" id="auth_key" name="auth_key" required>
                    <i class="fa fa-eye" onclick="togglePassword('auth_key','authIcon')"></i>
                </div>
            </div>

            <button type="submit" class="auth-btn">
                Create Admin
            </button>

            <div class="back">
                <a href="index.php">← Back</a>
            </div>

        </form>

    </div>

</div>

<script>
function togglePassword(id, iconId) {
    const input = document.getElementById(id);
    const icon = event.target;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</body>
</html>