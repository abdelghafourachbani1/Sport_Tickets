<?php

class EmailService
{
    private $mailer;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Configuration PHPMailer sera faite ici
    }

    /**
     * Envoie un email de confirmation d'inscription
     */
    public function envoyerConfirmationInscription($email, $nom)
    {
        // Logique avec PHPMailer
        return true;
    }

    /**
     * Envoie un billet par email
     */
    public function envoyerBillet($email, $ticketPDF, $nomMatch)
    {
        // Logique avec PHPMailer + pièce jointe PDF
        return true;
    }

    /**
     * Envoie une notification de validation/refus de match
     */
    public function envoyerNotificationValidation($email, $nomMatch, $statut, $motif = null)
    {
        // Logique avec PHPMailer
        return true;
    }

    /**
     * Envoie un email de récupération de mot de passe
     */
    public function envoyerResetPassword($email, $token)
    {
        // Logique avec PHPMailer
        return true;
    }
}