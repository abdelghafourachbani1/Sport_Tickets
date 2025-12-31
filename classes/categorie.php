<?php
class Categorie
{
    private $id;
    private $nom;
    private $prix;
    private $nbPlaces;
    private $description;
    private $matchId;

    /**
     * Constructeur
     */
    public function __construct($nom, $prix, $nbPlaces, $matchId = null, $description = null)
    {
        $this->nom = $nom;
        $this->prix = $prix;
        $this->nbPlaces = $nbPlaces;
        $this->matchId = $matchId;
        $this->description = $description;
    }

    // ========== GETTERS ==========
    public function getId()
    {
        return $this->id;
    }
    public function getNom()
    {
        return $this->nom;
    }
    public function getPrix()
    {
        return $this->prix;
    }
    public function getNbPlaces()
    {
        return $this->nbPlaces;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getMatchId()
    {
        return $this->matchId;
    }

    // ========== SETTERS ==========
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setNom($nom)
    {
        $this->nom = $nom;
    }
    public function setPrix($prix)
    {
        $this->prix = $prix;
    }
    public function setNbPlaces($nbPlaces)
    {
        $this->nbPlaces = $nbPlaces;
    }
    public function setDescription($description)
    {
        $this->description = $description;
    }
    public function setMatchId($matchId)
    {
        $this->matchId = $matchId;
    }

    // ========== MÉTHODES ==========

    /**
     * Calcule le nombre de places restantes
     */
    public function getPlacesRestantes()
    {
        // Sera calculé avec le DAO (places totales - places vendues)
        return $this->nbPlaces;
    }

    /**
     * Vérifie si des places sont disponibles
     */
    public function aDesPlacesDisponibles()
    {
        return $this->getPlacesRestantes() > 0;
    }

    /**
     * Formate le prix avec devise
     */
    public function getPrixFormate()
    {
        return number_format($this->prix, 2, ',', ' ') . ' MAD';
    }

    /**
     * Vérifie si la catégorie est complète
     */
    public function estComplete()
    {
        return $this->getPlacesRestantes() <= 0;
    }
}