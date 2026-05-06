<?php
require 'db_config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$user = null;

// Validate token
if (empty($token)) {
    $error = '❌ Invalid reset link. Please try again.';
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE reset_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = '❌ Invalid reset link. The link may not exist.';
    } elseif (strtotime($user['token_expiry']) < time()) {
        $error = '❌ Reset link has expired. Please request a new one.';
    }
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error) {
    
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($password)) {
        $error = '❌ Password is required.';
    } elseif (strlen($password) < 8) {
        $error = '❌ Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = '❌ Passwords do not match.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = '❌ Password must contain at least one uppercase letter and one number.';
    } else {
        // Hash and update password
        $newpass = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, token_expiry = NULL
            WHERE id = ?
        ");

        if ($update->execute([$newpass, $user['id']])) {
            $success = '✓ Password updated successfully! Redirecting to login...';
            echo '<script>
                setTimeout(() => {
                    window.location.href = "login.php";
                }, 2500);
            </script>';
        } else {
            $error = '❌ Failed to update password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
        }

        /* Header */
        .reset-header {
            text-align: center;
            margin-bottom: 32px;
            animation: slideDown 0.6s ease-out;
        }

        .reset-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .reset-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient-1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .reset-header p {
            font-size: 14px;
            color: var(--text-light);
        }

        /* Form Card */
        .form-card {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.5s ease-out;
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

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
        }

        .form-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px 0 0 10px;
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

        .toggle-password {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: var(--bg-light);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
        }

        .form-input:focus + .toggle-password {
            border-color: var(--primary-light);
            background: #eff6ff;
        }

        .toggle-password:hover {
            background: white;
            border-color: var(--primary-light);
        }

        /* Password Strength Meter */
        .strength-meter {
            margin-top: 12px;
            display: none;
        }

        .strength-meter.show {
            display: block;
        }

        .strength-bar {
            height: 6px;
            background: var(--border);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            background: var(--danger);
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-fill.fair {
            width: 33%;
            background: var(--warning);
        }

        .strength-fill.good {
            width: 66%;
            background: #3b82f6;
        }

        .strength-fill.strong {
            width: 100%;
            background: var(--success);
        }

        .strength-text {
            font-size: 12px;
            color: var(--text-light);
        }

        /* Requirements Checklist */
        .requirements {
            background: var(--bg-light);
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
            margin-bottom: 24px;
        }

        .req-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .req-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .req-item:last-child {
            margin-bottom: 0;
        }

        .req-icon {
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .req-item.met {
            color: var(--success);
        }

        .req-item.met .req-icon {
            color: var(--success);
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
            text-align: center;
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

            .reset-header h1 {
                font-size: 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="reset-header">
            <div class="reset-icon">🔐</div>
            <h1>Reset Password</h1>
            <p>Create a new secure password for your account</p>
        </div>

        <!-- Alert Messages -->
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
        <?php if (!$error): ?>
        <div class="form-card">
            <form method="POST" id="resetForm" novalidate>

                <!-- New Password Field -->
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input"
                            placeholder="Enter your new password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword" title="Show/Hide Password">👁️</button>
                    </div>

                    <!-- Password Strength Meter -->
                    <div class="strength-meter" id="strengthMeter">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text">
                            Strength: <span id="strengthText">Weak</span>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <div class="requirements">
                        <div class="req-title">Password Requirements</div>
                        <div class="req-item" id="req-length">
                            <span class="req-icon">○</span>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="req-item" id="req-uppercase">
                            <span class="req-icon">○</span>
                            <span>One uppercase letter (A-Z)</span>
                        </div>
                        <div class="req-item" id="req-number">
                            <span class="req-icon">○</span>
                            <span>One number (0-9)</span>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password" 
                            class="form-input"
                            placeholder="Re-enter your password"
                            required
                        >
                        <button type="button" class="toggle-password" id="toggleConfirm" title="Show/Hide Password">👁️</button>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    💡 Use a strong, unique password that you don't use elsewhere for better security.
                </div>

                <!-- Action Buttons -->
                <div class="form-actions" style="margin-top: 32px;">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        🔐 Update Password
                    </button>
                    <a href="login.php" class="btn btn-secondary">← Back to Login</a>
                </div>

            </form>
        </div>
        <?php else: ?>
            <!-- Error State with Link -->
            <div class="form-card" style="text-align: center;">
                <div style="font-size: 48px; margin-bottom: 16px;">🚫</div>
                <h2 style="margin-bottom: 8px;">Cannot Reset Password</h2>
                <p style="color: var(--text-light); margin-bottom: 24px;">Please request a new password reset link.</p>
                <a href="forgot_password.php" class="btn btn-primary" style="width: 100%;">📧 Request New Link</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirm = document.getElementById('toggleConfirm');
        const submitBtn = document.getElementById('submitBtn');
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const form = document.getElementById('resetForm');

        if (form) {
            // Toggle password visibility
            togglePassword.addEventListener('click', (e) => {
                e.preventDefault();
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                togglePassword.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
            });

            toggleConfirm.addEventListener('click', (e) => {
                e.preventDefault();
                const type = confirmInput.type === 'password' ? 'text' : 'password';
                confirmInput.type = type;
                toggleConfirm.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
            });

            // Password strength calculator
            function calculateStrength() {
                const password = passwordInput.value;
                let strength = 0;
                let feedback = [];

                // Check requirements
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*]/.test(password);

                // Update requirement checkmarks
                document.getElementById('req-length').classList.toggle('met', hasLength);
                document.getElementById('req-uppercase').classList.toggle('met', hasUppercase);
                document.getElementById('req-number').classList.toggle('met', hasNumber);

                if (hasLength) strength += 25;
                if (hasUppercase) strength += 25;
                if (hasNumber) strength += 25;
                if (hasSpecial) strength += 25;

                // Show strength meter only if typing
                if (password.length > 0) {
                    strengthMeter.classList.add('show');
                } else {
                    strengthMeter.classList.remove('show');
                }

                // Update strength display
                strengthFill.classList.remove('fair', 'good', 'strong');
                if (strength <= 50) {
                    strengthFill.classList.add('fair');
                    strengthText.textContent = 'Weak';
                } else if (strength <= 75) {
                    strengthFill.classList.add('good');
                    strengthText.textContent = 'Good';
                } else {
                    strengthFill.classList.add('strong');
                    strengthText.textContent = 'Strong';
                }

                strengthFill.style.width = strength + '%';

                updateSubmitButton();
            }

            function updateSubmitButton() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                const hasLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const passwordsMatch = password === confirm && confirm.length > 0;

                const isValid = hasLength && hasUppercase && hasNumber && passwordsMatch;
                submitBtn.disabled = !isValid;
            }

            // Event listeners
            passwordInput.addEventListener('input', calculateStrength);
            confirmInput.addEventListener('input', updateSubmitButton);

            // Initial state
            updateSubmitButton();
        }
    </script>
</body>
</html>