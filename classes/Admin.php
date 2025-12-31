<?php

class Admin extends User {

    public function __construct($nom, $prenom, $email, $motDePasse, $telephone = null)
    {
        parent::__construct($nom, $prenom, $email, $motDePasse, $telephone);
        $this->role = 'admin';
    }

    /**
     * Retourne l'URL du dashboard (Polymorphisme)
     */
    public function getDashboardUrl()
    {
        return '/admin/dashboard.php';
    }

    /**
     * Gère un utilisateur (activer/désactiver)
     */
    public function gererUtilisateur($userId, $action)
    {
        if (!in_array($action, ['activer', 'desactiver'])) {
            throw new Exception("Action invalide");
        }
        // Logique dans le DAO
        return true;
    }

    /**
     * Active un utilisateur
     */
    public function activerUtilisateur($userId)
    {
        return $this->gererUtilisateur($userId, 'activer');
    }

    /**
     * Désactive un utilisateur
     */
    public function desactiverUtilisateur($userId)
    {
        return $this->gererUtilisateur($userId, 'desactiver');
    }

    /**
     * Valide une demande de match (accepter/refuser)
     */
    public function validerEvenement($evenementId, $statut, $motifRefus = null)
    {
        if (!in_array($statut, ['valide', 'refuse'])) {
            throw new Exception("Statut invalide");
        }
        if ($statut === 'refuse' && empty($motifRefus)) {
            throw new Exception("Un motif de refus est requis");
        }
        // Logique dans le DAO
        return true;
    }

    /**
     * Accepte une demande de match
     */
    public function accepterEvenement($evenementId)
    {
        return $this->validerEvenement($evenementId, 'valide');
    }

    /**
     * Refuse une demande de match
     */
    public function refuserEvenement($evenementId, $motif)
    {
        return $this->validerEvenement($evenementId, 'refuse', $motif);
    }

    /**
     * Consulte les statistiques globales
     */
    public function consulterStatistiquesGlobales()
    {
        // Retourne les stats de toute la plateforme
        return [
            'total_utilisateurs' => 0,
            'total_evenements' => 0,
            'total_billets_vendus' => 0,
            'chiffre_affaires_total' => 0,
            'evenements_en_attente' => 0
        ];
    }

    /**
     * Consulte tous les commentaires de la plateforme
     */
    public function consulterTousCommentaires()
    {
        return [];
    }

    /**
     * Supprime un commentaire inapproprié
     */
    public function supprimerCommentaire($commentaireId)
    {
        return true;
    }

    /**
     * Consulte tous les utilisateurs
     */
    public function consulterUtilisateurs($role = null)
    {
        return [];
    }

    /**
     * Consulte les événements en attente de validation
     */
    public function consulterEvenementsEnAttente()
    {
        return [];
    }
}

?>