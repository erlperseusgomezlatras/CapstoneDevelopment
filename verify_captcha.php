<?php
// Include configuration
require_once 'config/config.php';

session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['captcha']) || !isset($_SESSION['captcha'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Captcha not found'
    ]);
    exit;
}

$userAnswer = (int)$data['captcha'];
$correctAnswer = $_SESSION['captcha'];

if ($userAnswer === $correctAnswer) {
    echo json_encode([
        'success' => true,
        'message' => 'Captcha verified successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Incorrect captcha answer'
    ]);
}
?>
