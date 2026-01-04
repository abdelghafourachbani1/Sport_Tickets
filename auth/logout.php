<?php
require_once __DIR__ . '/../config/setup.php';

// Créer un objet User temporaire pour appeler logout
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $user = User::findById($userId);

    if ($user) {
        $user->logout();
    }
}

// Détruire la session manuellement si nécessaire
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

// Rediriger vers la page d'accueil
header('Location: ../pages/home.php');
exit;
