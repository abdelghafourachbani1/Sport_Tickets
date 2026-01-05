<?php
require_once __DIR__ . '/../config/setup.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $user = $userId;
    if ($user) {
        $user->logout();
    }
}
$_SESSION = array();
session_destroy();
header('Location: ../pages/home.php');
exit;
