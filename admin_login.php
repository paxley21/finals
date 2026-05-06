<?php
require 'db_config.php';
session_start();


$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $password = $_POST['password'];

    // Get user by username or email
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE username = ? OR email = ? 
        LIMIT 1
    ");

    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        // Check role first
        if ($user['role'] !== 'admin') {
            $error = "❌ You are not an admin.";
        } 
        // Verify password
        elseif (password_verify($password, $user['password'])) {

            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['username'];

            header("Location: index.php");
            exit();

        } else {
            $error = "❌ Incorrect password.";
        }

    } else {
        $error = "❌ Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Admin Panel</h2>
        <p>Secure access for system administrators</p>
    </div>

    <div class="content-padding">

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">

            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="login" placeholder="Enter username or email" required>
            </div>

            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required>

                <i id="eyeIcon" class="fa fa-eye" onclick="togglePassword()" title="Show password"></i>

                <small id="eyeHint" class="eye-hint">Show password</small>
            </div>

            <button type="submit" class="auth-btn">Login as Admin</button>

        </form>

    </div>

    <div class="auth-footer">
        <a href="login.php">← Back to User Login</a><br>
        <a href="register_admin.php">Admin Create</a>
    </div>

</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    const hint = document.getElementById("eyeHint");

    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
        hint.textContent = "Hide password";
    } else {
        pass.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
        hint.textContent = "Show password";
    }
}
</script>

</body>
</html>