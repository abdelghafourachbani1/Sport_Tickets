<?php

/**
 * Configuration globale du projet
 */

// Chemins
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://localhost/billetterie_sportive');
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'billetterie_sportive');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'votre-mot-de-passe-app');
define('SMTP_PORT', 587);

// Paramètres de l'application
define('MAX_BILLETS_PAR_MATCH', 4);
define('MAX_PLACES_PAR_MATCH', 2000);
define('MAX_CATEGORIES_PAR_MATCH', 3);
define('DUREE_MATCH_MINUTES', 90);

// Timezone
date_default_timezone_set('Africa/Casablanca');

// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/classes/' . $class . '.php',
        BASE_PATH . '/config/' . $class . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
