<?php

class Ticket{
    private $id;
    private $matchSport;
    private $acheteur;
    private $categorie;
    private $numeroPlace;
    private $prix;
    private $dateAchat;
    private $qrCode;
    private $identifiant;
    private $statut = 'valide'; // valide, annule

    /**
     * Constructeur
     */
    public function __construct($matchSport, $acheteur, $categorie, $numeroPlace, $prix)
    {
        $this->matchSport = $matchSport;
        $this->acheteur = $acheteur;
        $this->categorie = $categorie;
        $this->numeroPlace = $numeroPlace;
        $this->prix = $prix;
        $this->dateAchat = date('Y-m-d H:i:s');
        $this->identifiant = $this->genererIdentifiant();
        $this->qrCode = $this->genererQRCode();
    }

    // ========== GETTERS ==========
    public function getId()
    {
        return $this->id;
    }
    public function getMatchSport()
    {
        return $this->matchSport;
    }
    public function getAcheteur()
    {
        return $this->acheteur;
    }
    public function getCategorie()
    {
        return $this->categorie;
    }
    public function getNumeroPlace()
    {
        return $this->numeroPlace;
    }
    public function getPrix()
    {
        return $this->prix;
    }
    public function getDateAchat()
    {
        return $this->dateAchat;
    }
    public function getQrCode()
    {
        return $this->qrCode;
    }
    public function getIdentifiant()
    {
        return $this->identifiant;
    }
    public function getStatut()
    {
        return $this->statut;
    }

    // ========== SETTERS ==========
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setStatut($statut)
    {
        $this->statut = $statut;
    }

    // ========== MÉTHODES ==========

    /**
     * Génère un identifiant unique pour le billet
     */
    private function genererIdentifiant()
    {
        return 'TICKET-' . strtoupper(uniqid());
    }

    /**
     * Génère le QR Code (bonus)
     */
    private function genererQRCode()
    {
        return base64_encode($this->identifiant);
    }

    /**
     * Génère le PDF du billet
     * Contient : infos du match, numéro de place, catégorie, QR code
     */
    public function genererPDF()
    {
        // Utiliser TCPDF ou FPDF
        // Contenu : infos match, place, catégorie, QR code
        return true;
    }

    /**
     * Envoie le billet par email avec PHPMailer
     */
    public function envoyerParEmail()
    {
        // Utiliser PHPMailer
        return true;
    }

    /**
     * Vérifie si le billet est valide
     */
    public function estValide()
    {
        return $this->statut === 'valide';
    }

    /**
     * Annule le billet
     */
    public function annuler()
    {
        $this->statut = 'annule';
    }

    /**
     * Retourne les informations du billet en tableau
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'identifiant' => $this->identifiant,
            'match' => $this->matchSport->getTitre(),
            'date' => $this->matchSport->getDateMatch(),
            'lieu' => $this->matchSport->getLieu(),
            'categorie' => $this->categorie->getNom(),
            'place' => $this->numeroPlace,
            'prix' => $this->prix,
            'date_achat' => $this->dateAchat,
            'qr_code' => $this->qrCode
        ];
    }
}

?>