<?php
session_start();

$a = rand(10, 25);
$b = rand(1, 9);

$_SESSION['captcha'] = $a + $b;

echo json_encode([
    "a" => $a,
    "b" => $b
]);
