<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
if (!in_array($_SESSION['role'], $allowed_roles)) {
    die("ACCESS DENIED");
}
?>