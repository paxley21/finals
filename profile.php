<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// Get user's account age
$created_date = new DateTime($user['created_at'] ?? date('Y-m-d'));
$now = new DateTime();
$interval = $created_date->diff($now);
$account_age = $interval->days;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            max-width: 900px;
            margin: 0 auto;
        }

        /* Header Navigation */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
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

        .nav-links {
            display: flex;
            gap: 12px;
        }

        .btn-icon {
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
            cursor: pointer;
            font-size: 18px;
        }

        .btn-icon:hover {
            background: var(--primary-light);
            color: white;
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Main Profile Section */
        .profile-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .avatar-card {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            animation: fadeInUp 0.6s ease-out 0.1s both;
            position: relative;
            overflow: hidden;
        }

        .avatar-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: var(--gradient-2);
            border-radius: 50%;
            opacity: 0.1;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 700;
            margin: 0 auto 16px;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 16px rgba(30, 58, 138, 0.2);
        }

        .avatar-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: var(--success);
            border-radius: 50%;
            border: 2px solid white;
            margin-left: -16px;
            margin-top: 96px;
        }

        .user-name {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .user-role {
            font-size: 14px;
            color: var(--accent);
            text-transform: capitalize;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            background: var(--bg-light);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 4px;
        }

        /* Profile Details Card */
        .details-card {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--bg-light);
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 12px;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 700;
        }

        .details-group {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg-light);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .details-group:hover {
            background: #eff6ff;
            transform: translateX(4px);
        }

        .details-icon {
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
            flex-shrink: 0;
        }

        .details-content {
            flex: 1;
        }

        .details-label {
            font-size: 12px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .details-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            word-break: break-all;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 2px solid var(--bg-light);
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
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(30, 58, 138, 0.3);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-wrapper {
                grid-template-columns: 1fr;
            }

            .nav-header {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
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

        .details-card {
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Navigation Header -->
        <div class="nav-header">
            <h1>👤 Profile</h1>
            <div class="nav-links">
                <a href="dashboard.php" class="btn-icon" title="Back to Dashboard">←</a>
                <a href="edit_profile.php" class="btn-icon" title="Edit Profile">✎</a>
            </div>
        </div>

        <!-- Main Profile Section -->
        <div class="profile-wrapper">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="avatar-card">
                    <div class="avatar">
                        <?= strtoupper(substr(htmlspecialchars($user['username']), 0, 1)) ?>
                    </div>
                    <div class="avatar-status"></div>
                    <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= $account_age ?></div>
                            <div class="stat-label">Days Active</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">✓</div>
                            <div class="stat-label">Verified</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="details-card">
                <div class="card-header">
                    <div class="card-header-icon">ℹ</div>
                    <h2>Account Details</h2>
                </div>

                <div class="details-group">
                    <div class="details-icon">✉</div>
                    <div class="details-content">
                        <div class="details-label">Email Address</div>
                        <div class="details-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>

                <div class="details-group">
                    <div class="details-icon">👥</div>
                    <div class="details-content">
                        <div class="details-label">Account Role</div>
                        <div class="details-value"><?= htmlspecialchars($user['role']) ?></div>
                    </div>
                </div>

                <div class="details-group">
                    <div class="details-icon">📅</div>
                    <div class="details-content">
                        <div class="details-label">Member Since</div>
                        <div class="details-value"><?= date('F j, Y', strtotime($user['created_at'] ?? date('Y-m-d'))) ?></div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="edit_profile.php" class="btn btn-primary">
                        ✎ Edit Profile
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>