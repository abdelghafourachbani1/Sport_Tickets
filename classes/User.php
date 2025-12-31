<?php

abstract class User {
    protected $id;
    protected $nom;
    protected $prenom;
    protected $email;
    protected $motDePasse;
    protected $telephone;
    protected $dateInscription;
    protected $actif;
    protected $role;

    /**
     * Constructeur
     */
    public function __construct($nom, $prenom, $email, $motDePasse, $telephone = null)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->motDePasse = password_hash($motDePasse, PASSWORD_DEFAULT);
        $this->telephone = $telephone;
        $this->dateInscription = date('Y-m-d H:i:s');
        $this->actif = true;
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
    public function getPrenom()
    {
        return $this->prenom;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getTelephone()
    {
        return $this->telephone;
    }
    public function getDateInscription()
    {
        return $this->dateInscription;
    }
    public function isActif()
    {
        return $this->actif;
    }
    public function getRole()
    {
        return $this->role;
    }
    public function getMotDePasse()
    {
        return $this->motDePasse;
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
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }
    public function setActif($actif)
    {
        $this->actif = $actif;
    }

    // ========== MÉTHODES ABSTRAITES (Polymorphisme) ==========
    abstract public function getDashboardUrl();

    // ========== MÉTHODES CONCRÈTES ==========

    /**
     * Vérifie si le mot de passe correspond
     */
    public function verifierMotDePasse($motDePasse)
    {
        return password_verify($motDePasse, $this->motDePasse);
    }

    /**
     * Change le mot de passe
     */
    public function changerMotDePasse($ancienMdp, $nouveauMdp)
    {
        if ($this->verifierMotDePasse($ancienMdp)) {
            $this->motDePasse = password_hash($nouveauMdp, PASSWORD_DEFAULT);
            return true;
        }
        return false;
    }

    /**
     * Met à jour le profil
     */
    public function updateProfile($nom, $prenom, $email, $telephone)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->telephone = $telephone;
    }

    /**
     * Retourne le nom complet
     */
    public function getNomComplet()
    {
        return $this->prenom . ' ' . $this->nom;
    }
}















