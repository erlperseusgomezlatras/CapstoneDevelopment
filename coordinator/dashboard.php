<?php
// Include configuration
require_once '../config/config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !(isset($_COOKIE['authToken']) && isset($_COOKIE['userData']))) {
    header('Location: ../login.php');
    exit();
}

// Get user data from session or cookie
$userData = null;
if (isset($_SESSION['user_id'])) {
    // Use session data if available
    $userData = [
        'level' => $_SESSION['user_role'] ?? 'Coordinator',
        'firstname' => $_SESSION['first_name'] ?? 'Coordinator',
        'email' => $_SESSION['email'] ?? ''
    ];
} else {
    // Use cookie data
    $userData = json_decode($_COOKIE['userData'], true);
}

// Verify user is a coordinator
if ($userData['level'] !== 'Coordinator') {
    header('Location: ../login.php');
    exit();
}

$coordinator_name = $userData['firstname'] ?? 'Coordinator';
$coordinator_email = $userData['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | PHINMA Practicum Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="min-h-screen bg-gray-100">
        <div class="container mx-auto py-8">
            <h1 class="text-3xl font-bold text-center mb-8">Coordinator Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($coordinator_name); ?>!</p>
        </div>
    </div>
</body>
</html>