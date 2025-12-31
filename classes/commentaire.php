<?php

class Commentaire
{
    private $id;
    private $matchId;
    private $acheteurId;
    private $texte;
    private $note; // 1 à 5 étoiles (BONUS)
    private $datePublication;
    private $approuve = true;

    /**
     * Constructeur
     */
    public function __construct($matchId, $acheteurId, $texte, $note)
    {
        $this->matchId = $matchId;
        $this->acheteurId = $acheteurId;
        $this->texte = $texte;
        $this->setNote($note);
        $this->datePublication = date('Y-m-d H:i:s');
    }

    // ========== GETTERS ==========
    public function getId()
    {
        return $this->id;
    }
    public function getMatchId()
    {
        return $this->matchId;
    }
    public function getAcheteurId()
    {
        return $this->acheteurId;
    }
    public function getTexte()
    {
        return $this->texte;
    }
    public function getNote()
    {
        return $this->note;
    }
    public function getDatePublication()
    {
        return $this->datePublication;
    }
    public function isApprouve()
    {
        return $this->approuve;
    }

    // ========== SETTERS ==========
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setTexte($texte)
    {
        $this->texte = $texte;
    }
    public function setApprouve($approuve)
    {
        $this->approuve = $approuve;
    }

    /**
     * Définit la note avec validation
     */
    public function setNote($note)
    {
        if ($note < 1 || $note > 5) {
            throw new Exception("La note doit être entre 1 et 5 étoiles");
        }
        $this->note = $note;
    }

    // ========== MÉTHODES ==========

    /**
     * Retourne la note en étoiles (HTML)
     */
    public function getEtoiles()
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->note) {
                $html .= '★'; // Étoile pleine
            } else {
                $html .= '☆'; // Étoile vide
            }
        }
        return $html;
    }

    /**
     * Formate la date de publication
     */
    public function getDateFormatee()
    {
        return date('d/m/Y à H:i', strtotime($this->datePublication));
    }

    /**
     * Retourne un extrait du commentaire
     */
    public function getExtrait($longueur = 100)
    {
        if (strlen($this->texte) > $longueur) {
            return substr($this->texte, 0, $longueur) . '...';
        }
        return $this->texte;
    }

    /**
     * Modère le commentaire
     */
    public function moderer($approuve)
    {
        $this->approuve = $approuve;
    }
}