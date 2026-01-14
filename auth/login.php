<?php
include '../config/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("
    SELECT Susers.*, roles.role_name
    FROM users
    JOIN roles ON users.role_id = roles.role_id
    WHERE email = ?
");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role_name'];
    header("Location: ../" . strtolower($user['role_name']) . "/dashboard.php");
} else {
    echo "Invalid credentials";
}

if ($_POST['captcha'] != $_SESSION['captcha']) {
    die("Invalid CAPTCHA");
}

?>