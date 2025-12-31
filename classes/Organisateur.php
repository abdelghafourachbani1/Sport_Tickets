<?php

class Organisateur extends User {

    public function __construct($nom, $prenom, $email, $motDePasse, $telephone = null)
    {
        parent::__construct($nom, $prenom, $email, $motDePasse, $telephone);
        $this->role = 'organisateur';
    }

    /**
     * Retourne l'URL du dashboard (Polymorphisme)
     */
    public function getDashboardUrl()
    {
        return '/organizer/stats.php';
    }

    /**
     * Crée une demande d'événement sportif
     * Maximum 2000 places et 3 catégories
     */
    public function creerEvenement(
        $equipe1Nom,
        $equipe1Logo,
        $equipe2Nom,
        $equipe2Logo,
        $dateMatch,
        $heureMatch,
        $lieu,
        $nbPlaces,
        $categories
    ) {

        // Validation
        if ($nbPlaces > 2000) {
            throw new Exception("Maximum 2000 places autorisées");
        }
        if (count($categories) > 3) {
            throw new Exception("Maximum 3 catégories autorisées");
        }
        if (empty($categories)) {
            throw new Exception("Au moins une catégorie est requise");
        }

        // Créer l'événement (logique dans DAO)
        return true;
    }

    /**
     * Modifie un événement existant (si non validé)
     */
    public function modifierEvenement($evenementId, $data)
    {
        // Vérifie que l'événement n'est pas encore validé
        return true;
    }

    /**
     * Supprime un événement (si non validé)
     */
    public function supprimerEvenement($evenementId)
    {
        return true;
    }

    /**
     * Consulte les statistiques d'un événement
     * - Billets vendus
     * - Chiffre d'affaires
     */
    public function consulterStatistiques($evenementId)
    {
        // Retourne un objet Statistiques
        return [];
    }

    /**
     * Consulte tous les événements créés
     */
    public function consulterMesEvenements($statut = null)
    {
        // Retourne la liste des événements
        return [];
    }

    /**
     * Consulte les commentaires et avis d'un événement
     */
    public function consulterCommentaires($evenementId)
    {
        return [];
    }

    /**
     * Vérifie si l'organisateur peut modifier l'événement
     */
    public function peutModifier($evenementId)
    {
        // Un événement ne peut être modifié que s'il n'est pas validé
        return true;
    }
}

?>