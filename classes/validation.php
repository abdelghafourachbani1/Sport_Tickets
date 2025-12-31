<?php
class Validation
{

    /**
     * Nettoie une chaîne contre XSS
     */
    public static function nettoyer($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Valide un email
     */
    public static function validerEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valide un mot de passe (min 8 caractères)
     */
    public static function validerMotDePasse($password)
    {
        return strlen($password) >= 8;
    }

    /**
     * Valide un numéro de téléphone
     */
    public static function validerTelephone($telephone)
    {
        return preg_match('/^[0-9]{10}$/', $telephone);
    }

    /**
     * Valide une date
     */
    public static function validerDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Valide un nombre positif
     */
    public static function validerNombrePositif($nombre)
    {
        return is_numeric($nombre) && $nombre > 0;
    }

    /**
     * Génère un token CSRF
     */
    public static function genererTokenCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie le token CSRF
     */
    public static function verifierTokenCSRF($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}