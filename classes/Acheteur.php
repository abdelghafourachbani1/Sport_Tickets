<?php

class Acheteur extends User {

    public function __construct($nom, $prenom, $email, $motDePasse, $telephone = null)
    {
        parent::__construct($nom, $prenom, $email, $motDePasse, $telephone);
        $this->role = 'acheteur';
    }

    /**
     * Retourne l'URL du dashboard (Polymorphisme)
     */
    public function getDashboardUrl()
    {
        return '/pages/home.php';
    }

    /**
     * Achète des billets pour un match
     * Maximum 4 billets par match
     */
    public function acheterBillets($matchId, $categorieId, $places, $quantite)
    {
        if ($quantite > 4) {
            throw new Exception("Maximum 4 billets par match autorisé");
        }
        if ($quantite < 1) {
            throw new Exception("Quantité invalide");
        }
        // La logique d'achat sera dans le DAO
        return true;
    }

    /**
     * Consulte l'historique des billets achetés
     */
    public function consulterHistoriqueBillets()
    {
        // Sera implémenté dans le DAO
        return [];
    }

    /**
     * Laisse un commentaire après la fin du match
     */
    public function laisserCommentaire($matchId, $texte, $note)
    {
        if ($note < 1 || $note > 5) {
            throw new Exception("La note doit être entre 1 et 5 étoiles");
        }
        // Sera implémenté dans le DAO
        return true;
    }

    /**
     * Télécharge le PDF récapitulatif des billets
     */
    public function telechargerPDFRecapitulatif()
    {
        // Génère un PDF avec tous les billets achetés
        return true;
    }

    /**
     * Vérifie si l'acheteur peut commenter un match
     */
    public function peutCommenter($matchId)
    {
        // Vérifie si le match est terminé et si l'acheteur a un billet
        return true;
    }
}
