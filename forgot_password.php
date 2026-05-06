<?php
require 'db_config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $pdo->prepare("
            UPDATE users 
            SET reset_token = ?, token_expiry = ?
            WHERE email = ?
        ");
        $update->execute([$token, $expiry, $email]);

        // IMPORTANT: change this to your real domain later
        $link = "reset_password.php?token=$token";

        $message = "
        <div class='success'>
            ✔ Reset link generated<br><br>
            <a href='$link'>Open Password Reset Page</a>
        </div>";

    } else {
        $message = "<div class='error'>❌ Email not found</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="auth-body">

<div class="auth-card">

    <div class="auth-header">
        <h2>Forgot Password</h2>
        <p>Enter your email to reset your password</p>
    </div>

    <div class="auth-form">

        <?= $message ?>

        <form method="POST">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <button type="submit" class="auth-btn">
                Send Reset Link
            </button>

        </form>

    </div>

    <div class="auth-footer">
        <a href="login.php">← Back to Login</a>
    </div>

</div>

</body>
</html>