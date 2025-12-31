<?php
class Equipe
{
    private $id;
    private $nom;
    private $logo; // Chemin vers le fichier logo
    private $pays;

    /**
     * Constructeur
     */
    public function __construct($nom, $logo = null, $pays = null)
    {
        $this->nom = $nom;
        $this->logo = $logo;
        $this->pays = $pays;
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
    public function getLogo()
    {
        return $this->logo;
    }
    public function getPays()
    {
        return $this->pays;
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
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }
    public function setPays($pays)
    {
        $this->pays = $pays;
    }

    // ========== MÉTHODES ==========

    /**
     * Retourne le chemin complet du logo
     */
    public function getLogoUrl()
    {
        if ($this->logo) {
            return '/uploads/' . $this->logo;
        }
        return '/assets/images/default-team.png';
    }

    /**
     * Upload le logo de l'équipe
     */
    public function uploadLogo($file)
    {
        // Validation du fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Format de fichier non autorisé");
        }

        // Génération du nom unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'team_' . uniqid() . '.' . $extension;

        // Upload
        $uploadDir = __DIR__ . '/../uploads/';
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $this->logo = $filename;
            return true;
        }
        return false;
    }
}