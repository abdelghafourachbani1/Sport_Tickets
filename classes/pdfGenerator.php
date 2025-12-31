<?php

class PDFGenerator
{

    /**
     * Génère le PDF d'un billet
     */
    public static function genererBillet($ticket)
    {
        // Utiliser TCPDF ou FPDF
        // Contenu: infos match, place, catégorie, QR code
        return 'ticket_' . $ticket->getId() . '.pdf';
    }

    /**
     * Génère le PDF récapitulatif des billets (BONUS)
     */
    public static function genererRecapitulatif($acheteur, $billets)
    {
        // Génère un PDF avec tous les billets de l'acheteur
        return 'recapitulatif_' . $acheteur->getId() . '.pdf';
    }

    /**
     * Génère le rapport de statistiques
     */
    public static function genererRapportStatistiques($statistiques)
    {
        // Génère un PDF avec les stats
        return 'rapport_stats.pdf';
    }
}