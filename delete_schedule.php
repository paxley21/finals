<?php
session_start();
require 'db_config.php';

// SECURITY CHECK
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// CHECK ID
if (!isset($_GET['id'])) {
    die("Invalid request");
}

$id = $_GET['id'];

// DELETE QUERY
$stmt = $pdo->prepare("DELETE FROM schedule WHERE id = ?");
$stmt->execute([$id]);

// REDIRECT BACK
header("Location: index.php");
exit();
?>