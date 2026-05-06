<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $error = '❌ Username must be at least 3 characters long';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Please enter a valid email address';
    } else {
        // Check if email already exists (for other users)
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        
        if ($check->fetch()) {
            $error = '❌ This email is already registered';
        } else {
            // Check if username already exists (for other users)
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $user_id]);
            
            if ($check->fetch()) {
                $error = '❌ This username is already taken';
            } else {
                // Update user
                $update = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?
                    WHERE id = ?
                ");
                
                if ($update->execute([$username, $email, $user_id])) {
                    $_SESSION['username'] = $username;
                    $success = '✓ Profile updated successfully!';
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    // Redirect after 2 seconds
                    echo '<script>
                        setTimeout(() => {
                            window.location.href = "profile.php";
                        }, 2000);
                    </script>';
                } else {
                    $error = '❌ Failed to update profile. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1e3a8a;
            --primary-light: #3b82f6;
            --secondary: #f97316;
            --accent: #06b6d4;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gradient-1: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --gradient-2: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        /* Header Navigation */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding: 16px 24px;
            background: var(--bg-white);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            animation: slideDown 0.5s ease-out;
        }

        .nav-header h1 {
            font-size: 24px;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--bg-light);
            border: 1px solid var(--border);
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .btn-back:hover {
            background: var(--primary-light);
            color: white;
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease-out;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Form Card */
        .form-card {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }

        .form-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 32px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
        }

        .form-group:last-of-type {
            margin-bottom: 32px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-label span {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 400;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
            color: var(--text-dark);
            background: var(--bg-white);
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input:hover {
            border-color: var(--primary-light);
        }

        .form-input.success {
            border-color: var(--success);
            background: #f0fdf4;
        }

        .form-input.error {
            border-color: var(--danger);
            background: #fef2f2;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
        }

        .form-hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-hint.success {
            color: var(--success);
        }

        .form-hint.error {
            color: var(--danger);
        }

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--gradient-1);
            color: white;
            border: none;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(30, 58, 138, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: white;
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 16px;
            margin-top: 24px;
            font-size: 13px;
            color: #1e40af;
            line-height: 1.6;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .form-card {
                padding: 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .nav-header {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Navigation Header -->
        <div class="nav-header">
            <h1>✏️ Edit Profile</h1>
            <a href="profile.php" class="btn-back" title="Back to Profile">←</a>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <h2>Update Your Information</h2>
            <p class="form-subtitle">Make changes to your account details below</p>

            <form method="POST" id="editForm" novalidate>

                <!-- Username Field -->
                <div class="form-group">
                    <label class="form-label">
                        <span>Username</span>
                        <span id="usernameCount">0/20</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            class="form-input"
                            value="<?= htmlspecialchars($user['username']) ?>" 
                            required
                            minlength="3"
                            maxlength="20"
                            placeholder="Enter your username"
                        >
                        <span class="input-icon" id="usernameIcon"></span>
                    </div>
                    <div class="form-hint" id="usernameHint"></div>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label class="form-label">
                        <span>Email Address</span>
                        <span id="emailCount">0/50</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            class="form-input"
                            value="<?= htmlspecialchars($user['email']) ?>" 
                            required
                            maxlength="50"
                            placeholder="Enter your email address"
                        >
                        <span class="input-icon" id="emailIcon"></span>
                    </div>
                    <div class="form-hint" id="emailHint"></div>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <strong>💡 Need Help?</strong>
                    Your username must be at least 3 characters. Email must be a valid email address. Keep this information up to date for account security.
                </div>

                <!-- Action Buttons -->
                <div class="form-actions" style="margin-top: 32px;">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        💾 Save Changes
                    </button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('editForm');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const submitBtn = document.getElementById('submitBtn');

        // Real-time validation and feedback
        function validateUsername() {
            const value = usernameInput.value.trim();
            const hint = document.getElementById('usernameHint');
            const icon = document.getElementById('usernameIcon');
            const count = document.getElementById('usernameCount');

            count.textContent = value.length + '/20';

            if (value.length === 0) {
                usernameInput.classList.remove('success', 'error');
                hint.textContent = '';
                icon.textContent = '';
            } else if (value.length < 3) {
                usernameInput.classList.remove('success');
                usernameInput.classList.add('error');
                hint.textContent = '❌ At least 3 characters required';
                hint.classList.remove('success');
                hint.classList.add('error');
                icon.textContent = '❌';
            } else {
                usernameInput.classList.remove('error');
                usernameInput.classList.add('success');
                hint.textContent = '✓ Username looks good';
                hint.classList.remove('error');
                hint.classList.add('success');
                icon.textContent = '✓';
            }

            updateSubmitButton();
        }

        function validateEmail() {
            const value = emailInput.value.trim();
            const hint = document.getElementById('emailHint');
            const icon = document.getElementById('emailIcon');
            const count = document.getElementById('emailCount');

            count.textContent = value.length + '/50';

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isValid = emailRegex.test(value);

            if (value.length === 0) {
                emailInput.classList.remove('success', 'error');
                hint.textContent = '';
                icon.textContent = '';
            } else if (!isValid) {
                emailInput.classList.remove('success');
                emailInput.classList.add('error');
                hint.textContent = '❌ Enter a valid email address';
                hint.classList.remove('success');
                hint.classList.add('error');
                icon.textContent = '❌';
            } else {
                emailInput.classList.remove('error');
                emailInput.classList.add('success');
                hint.textContent = '✓ Email is valid';
                hint.classList.remove('error');
                hint.classList.add('success');
                icon.textContent = '✓';
            }

            updateSubmitButton();
        }

        function updateSubmitButton() {
            const usernameValid = usernameInput.value.trim().length >= 3;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const emailValid = emailRegex.test(emailInput.value.trim());

            submitBtn.disabled = !(usernameValid && emailValid);
        }

        // Event listeners
        usernameInput.addEventListener('input', validateUsername);
        emailInput.addEventListener('input', validateEmail);

        // Initial validation
        validateUsername();
        validateEmail();

        // Prevent form submission if invalid
        form.addEventListener('submit', (e) => {
            if (!usernameInput.value.trim() || !emailInput.value.trim()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>