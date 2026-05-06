<?php

require_once "db_config.php";

$success = false;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, 'teacher')
        ");

        $stmt->execute([$username, $email, $password]);

        $success = true;
        $message = "Teacher account created successfully!";

    } catch (Exception $e) {
        $success = false;
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Teacher Account</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Create Teacher Account</h2>
        <p>Register a new teacher in the system</p>
    </div>

    <div class="content-padding">

        <?php if ($message): ?>
            <div class="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter teacher username" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="teacher@email.com" required>
            </div>

            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Password" required>

                <i id="eyeIcon" class="fa fa-eye" onclick="togglePassword()" title="Show password"></i>
                <small id="eyeHint" class="eye-hint">Show password</small>
            </div>

            <button type="submit" class="auth-btn">Create Teacher</button>

        </form>

    </div>

    <div class="auth-footer">
        <a href="login.php">← Back to Login</a>
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