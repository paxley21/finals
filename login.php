<?php
session_start();
require 'db_config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE (username = ? OR email = ?)
    AND role = 'student'
    LIMIT 1
");
$stmt->execute([$login, $login]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData && password_verify($pass, $userData['password'])) {

    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['role'] = $userData['role'];

    session_write_close();

    header("Location: dashboard.php");
    exit();

} else {
    $error = "Invalid login credentials or unauthorized role.";
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Classroom Manager</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Please enter your details to continue</p>
    </div>

    <div class="auth-form">

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="login" placeholder="Enter username or email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <i id="eyeIcon" class="fa fa-eye" onclick="togglePassword()" title="Show password"></i>
                    <small id="eyeHint" class="eye-hint">Show password</small>
                </div>
            </div>

            <button type="submit" class="auth-btn">Login to Dashboard</button>

        </form>

    </div>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Sign up</a><br>
        <a href="teacher_login.php">Teacher's Login</a><br>
        <a href="admin_login.php" class="admin-emoji" title="Admin">
    🔐
</a>
<div class="forgot">
    <a href="forgot_password.php">Forgot Password?</a>
</div>
    </div>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");
    const hint = document.getElementById("eyeHint");

    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
        icon.title = "Hide password";
        hint.textContent = "Hide password";
    } else {
        pass.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
        icon.title = "Show password";
        hint.textContent = "Show password";
    }
}
</script>

</body>
</html>