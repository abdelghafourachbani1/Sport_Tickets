<?php

class MatchSport {
    private $id;
    private $equipe1;
    private $equipe2;
    private $dateMatch;
    private $heureMatch;
    private $lieu;
    private $duree = 90; // minutes
    private $nbPlacesTotal;
    private $organisateurId;
    private $statut = 'en_attente'; // en_attente, valide, refuse
    private $motifRefus;
    private $dateCreation;
    private $categories = []; // Composition

    /**
     * Constructeur
     */
    public function __construct($equipe1, $equipe2, $dateMatch, $heureMatch, $lieu, $nbPlacesTotal, $organisateurId)
    {
        $this->equipe1 = $equipe1;
        $this->equipe2 = $equipe2;
        $this->dateMatch = $dateMatch;
        $this->heureMatch = $heureMatch;
        $this->lieu = $lieu;
        $this->nbPlacesTotal = $nbPlacesTotal;
        $this->organisateurId = $organisateurId;
        $this->dateCreation = date('Y-m-d H:i:s');
    }

    // ========== GETTERS ==========
    public function getId()
    {
        return $this->id;
    }
    public function getEquipe1()
    {
        return $this->equipe1;
    }
    public function getEquipe2()
    {
        return $this->equipe2;
    }
    public function getDateMatch()
    {
        return $this->dateMatch;
    }
    public function getHeureMatch()
    {
        return $this->heureMatch;
    }
    public function getLieu()
    {
        return $this->lieu;
    }
    public function getDuree()
    {
        return $this->duree;
    }
    public function getNbPlacesTotal()
    {
        return $this->nbPlacesTotal;
    }
    public function getOrganisateurId()
    {
        return $this->organisateurId;
    }
    public function getStatut()
    {
        return $this->statut;
    }
    public function getMotifRefus()
    {
        return $this->motifRefus;
    }
    public function getDateCreation()
    {
        return $this->dateCreation;
    }
    public function getCategories()
    {
        return $this->categories;
    }

    // ========== SETTERS ==========
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setDateMatch($date)
    {
        $this->dateMatch = $date;
    }
    public function setHeureMatch($heure)
    {
        $this->heureMatch = $heure;
    }
    public function setLieu($lieu)
    {
        $this->lieu = $lieu;
    }
    public function setStatut($statut)
    {
        $this->statut = $statut;
    }
    public function setMotifRefus($motif)
    {
        $this->motifRefus = $motif;
    }

    // ========== MÉTHODES ==========

    /**
     * Ajoute une catégorie au match (max 3)
     */
    public function ajouterCategorie($categorie)
    {
        if (count($this->categories) >= 3) {
            throw new Exception("Maximum 3 catégories autorisées");
        }
        $this->categories[] = $categorie;
    }

    /**
     * Vérifie si le match est terminé
     */
    public function estTermine()
    {
        $dateTimeMatch = strtotime($this->dateMatch . ' ' . $this->heureMatch);
        $dateTimeFin = $dateTimeMatch + ($this->duree * 60);
        return time() > $dateTimeFin;
    }

    /**
     * Vérifie si le match est publié (validé)
     */
    public function estPublie()
    {
        return $this->statut === 'valide';
    }

    /**
     * Vérifie si des places sont disponibles
     */
    public function aDesPlacesDisponibles()
    {
        // Sera calculé avec le DAO
        return true;
    }

    /**
     * Calcule le nombre de billets vendus
     */
    public function calculerNbBilletsVendus()
    {
        // Sera implémenté avec le DAO
        return 0;
    }

    /**
     * Calcule le chiffre d'affaires
     */
    public function calculerChiffreAffaires()
    {
        // Sera implémenté avec le DAO
        return 0;
    }

    /**
     * Calcule la note moyenne du match
     */
    public function calculerNoteMoyenne()
    {
        // Sera implémenté avec le DAO
        return 0;
    }

    /**
     * Retourne le titre du match
     */
    public function getTitre()
    {
        return $this->equipe1->getNom() . ' vs ' . $this->equipe2->getNom();
    }

    /**
     * Vérifie si le match peut être modifié
     */
    public function peutEtreModifie()
    {
        return $this->statut === 'en_attente' && !$this->estTermine();
    }
}

?>