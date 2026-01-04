<?php

require_once __DIR__ . '/../vendor/autoload.php'; 
use FPDF;


class PdfGenerator {

    public static function genererBillet(Billet $billet): string {
        $infos = $billet->getInfos();

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 20);

        // En-tête
        $pdf->SetFillColor(76, 175, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 20, 'BILLET DE MATCH', 0, 1, 'C', true);
        $pdf->Ln(10);

        // Informations du match
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'INFORMATIONS DU MATCH', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(50, 8, 'Match:', 0, 0);
        $pdf->Cell(0, 8, $infos['match']['equipe_domicile'] . ' vs ' . $infos['match']['equipe_exterieur'], 0, 1);
        
        $pdf->Cell(50, 8, 'Date:', 0, 0);
        $pdf->Cell(0, 8, $infos['match']['date'], 0, 1);
        
        $pdf->Cell(50, 8, 'Lieu:', 0, 0);
        $pdf->Cell(0, 8, $infos['match']['lieu'], 0, 1);
        
        $pdf->Ln(5);

        // Informations du billet
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'INFORMATIONS DU BILLET', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(50, 8, utf8_decode('Catégorie:'), 0, 0);
        $pdf->Cell(0, 8, $infos['categorie'], 0, 1);
        
        $pdf->Cell(50, 8, utf8_decode('Numéro de place:'), 0, 0);
        $pdf->Cell(0, 8, $infos['numero_place'], 0, 1);
        
        $pdf->Cell(50, 8, utf8_decode('Prix payé:'), 0, 0);
        $pdf->Cell(0, 8, $infos['prix_paye'] . ' DH', 0, 1);
        
        $pdf->Cell(50, 8, 'Date d\'achat:', 0, 0);
        $pdf->Cell(0, 8, $infos['date_achat'], 0, 1);
        
        $pdf->Ln(10);

        // QR Code (simulé avec le code en texte)
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'CODE QR', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 16);
        $pdf->Cell(0, 15, $infos['qr_code'], 0, 1, 'C', true);
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 5, utf8_decode('Veuillez présenter ce billet à l\'entrée du stade'), 0, 1, 'C');

        // Créer le dossier s'il n'existe pas
        $uploadDir = __DIR__ . '/../uploads/billets/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Sauvegarder le PDF
        $filename = 'billet_' . $billet->getId() . '_' . $infos['qr_code'] . '.pdf';
        $filepath = $uploadDir . $filename;
        $pdf->Output('F', $filepath);

        return $filepath;
    }

    public static function genererQRCode(string $data): string {
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
        
        $uploadDir = __DIR__ . '/../uploads/qrcodes/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'qr_' . md5($data) . '.png';
        $filepath = $uploadDir . $filename;

        // Télécharger l'image QR Code
        $imageData = file_get_contents($qrApiUrl);
        file_put_contents($filepath, $imageData);

        return $filepath;
    }

    public static function genererRecapitulatifBillets(array $billets, User $user): string {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 18);

        // En-tête
        $pdf->SetFillColor(33, 150, 243);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, utf8_decode('RÉCAPITULATIF DES BILLETS'), 0, 1, 'C', true);
        $pdf->Ln(5);

        // Informations utilisateur
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Utilisateur: ' . $user->getNomComplet(), 0, 1);
        $pdf->Cell(0, 8, 'Email: ' . $user->getEmail(), 0, 1);
        $pdf->Cell(0, 8, 'Date: ' . date('d/m/Y'), 0, 1);
        $pdf->Ln(10);

        // Tableau des billets
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(70, 8, 'Match', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Lieu', 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Catégorie'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Place', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $total = 0;
        
        foreach ($billets as $billet) {
            $date = date('d/m/Y', strtotime($billet['match_date']));
            $match = substr($billet['equipe_domicile'] . ' vs ' . $billet['equipe_exterieur'], 0, 30);
            $lieu = substr($billet['lieu'], 0, 18);
            
            $pdf->Cell(30, 7, $date, 1);
            $pdf->Cell(70, 7, $match, 1);
            $pdf->Cell(40, 7, $lieu, 1);
            $pdf->Cell(25, 7, $billet['categorie_nom'], 1);
            $pdf->Cell(25, 7, $billet['numero_place'], 1);
            $pdf->Ln();
            
            $total += $billet['prix_paye'];
        }

        // Total
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Total des billets: ' . count($billets), 0, 1);
        $pdf->Cell(0, 10, utf8_decode('Montant total dépensé: ') . number_format($total, 2) . ' DH', 0, 1);

        // Sauvegarder
        $uploadDir = __DIR__ . '/../uploads/recapitulatifs/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'recapitulatif_' . $user->getId() . '_' . time() . '.pdf';
        $filepath = $uploadDir . $filename;
        $pdf->Output('F', $filepath);

        return $filepath;
    }
}
