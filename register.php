<?php
require 'db_config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user  = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $message = "<div style='color:red;'>Please fill in all fields.</div>";
    } else {

        $passHash = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user, $email, $passHash]);

            $message = "<div style='color:green;'>
                Registration successful!
            </div>";

        } catch (PDOException $e) {
            $message = "<div style='color:red;'>
                Error: Email already exists.
            </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Create Account</h2>
        <p>Join the Classroom Management System</p>
    </div>

    <div class="content-padding">

        <?= $message ?>

        <form method="POST" class="auth-form">

            <!-- EMAIL -->
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="email@example.com" required>
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
                <label>Password</label>

                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Password" required>

                    <i id="eyeIcon" class="fa fa-eye" onclick="togglePassword()" title="Show password"></i>

                    <small id="eyeHint" class="eye-hint">Show password</small>
                </div>
            </div>

            <button type="submit" class="auth-btn">Sign Up</button>

        </form>

    </div>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Log In</a>
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