<?php
class Statistiques
{
    private $matchId;
    private $nbBilletsVendus;
    private $chiffreAffaires;
    private $tauxRemplissage;
    private $noteMoyenne;
    private $nbCommentaires;
    private $ventesParCategorie = [];

    /**
     * Constructeur
     */
    public function __construct($matchId)
    {
        $this->matchId = $matchId;
        $this->calculer();
    }

    // ========== GETTERS ==========
    public function getMatchId()
    {
        return $this->matchId;
    }
    public function getNbBilletsVendus()
    {
        return $this->nbBilletsVendus;
    }
    public function getChiffreAffaires()
    {
        return $this->chiffreAffaires;
    }
    public function getTauxRemplissage()
    {
        return $this->tauxRemplissage;
    }
    public function getNoteMoyenne()
    {
        return $this->noteMoyenne;
    }
    public function getNbCommentaires()
    {
        return $this->nbCommentaires;
    }
    public function getVentesParCategorie()
    {
        return $this->ventesParCategorie;
    }

    // ========== MÉTHODES ==========

    /**
     * Calcule toutes les statistiques
     */
    private function calculer()
    {
        // Sera implémenté avec le DAO pour récupérer les vraies données
        $this->nbBilletsVendus = 0;
        $this->chiffreAffaires = 0;
        $this->tauxRemplissage = 0;
        $this->noteMoyenne = 0;
        $this->nbCommentaires = 0;
    }

    /**
     * Formate le chiffre d'affaires
     */
    public function getChiffreAffairesFormate()
    {
        return number_format($this->chiffreAffaires, 2, ',', ' ') . ' MAD';
    }

    /**
     * Formate le taux de remplissage
     */
    public function getTauxRemplissageFormate()
    {
        return round($this->tauxRemplissage, 2) . '%';
    }

    /**
     * Retourne les statistiques en tableau
     */
    public function toArray()
    {
        return [
            'billets_vendus' => $this->nbBilletsVendus,
            'chiffre_affaires' => $this->chiffreAffaires,
            'taux_remplissage' => $this->tauxRemplissage,
            'note_moyenne' => $this->noteMoyenne,
            'nb_commentaires' => $this->nbCommentaires,
            'ventes_par_categorie' => $this->ventesParCategorie
        ];
    }

    /**
     * Génère un rapport PDF des statistiques
     */
    public function genererRapportPDF()
    {
        // Utiliser TCPDF ou FPDF
        return true;
    }
}