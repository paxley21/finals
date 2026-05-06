<?php
require 'db_config.php';
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        // STRICT role check (case-safe)
        if (strtolower($user['role']) === 'teacher') {

            if (password_verify($password, $user['password'])) {

                // SESSION
                $_SESSION['teacher_id'] = $user['id'];
                $_SESSION['teacher_name'] = $user['username'];
                $_SESSION['teacher_role'] = $user['role'];

                // REDIRECT (IMPORTANT: no output before this)
                header("Location: teacher_dashboard.php");
                exit();

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "Access denied: Not a teacher account.";
        }

    } else {
        $error = "Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Teacher Login</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Teacher Portal</h2>
        <p>Secure access for teachers only</p>
    </div>

    <div class="auth-form">

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="login" placeholder="Enter username or email" required>
            </div>

            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required>

                <i id="eyeIcon" class="fa fa-eye" onclick="togglePassword()" title="Show password"></i>
                <small id="eyeHint" class="eye-hint">Show password</small>
            </div>

            <button type="submit" class="auth-btn">Login as Teacher</button>

        </form>

    </div>

    <div class="auth-footer">
        <a href="login.php">← Back to Main Login</a>
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
        hint.textContent = "Hide password";
    } else {
        pass.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
        hint.textContent = "Show password";
    }
}
</script>

</body>
</html>