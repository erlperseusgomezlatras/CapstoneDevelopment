<?php
// Include configuration
require_once 'config/config.php';

session_start();

function generateCaptcha() {
    $a = rand(10, 25);
    $b = rand(1, 9);
    $_SESSION['captcha'] = $a + $b;
    return [$a, $b];
}

[$a, $b] = generateCaptcha();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'a' => $a,
    'b' => $b
]);
?>
