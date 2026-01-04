<?php

require_once __DIR__ . '/../vendor/autoload.php'; 


class EmailService {
    
    private PHPMailer $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com'; 
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'votre-email@gmail.com'; 
            $this->mailer->Password = 'votre-mot-de-passe-app'; 
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;

            $this->mailer->setFrom('noreply@billetterie.com', 'Billetterie Sportive');
            $this->mailer->CharSet = 'UTF-8';

        } catch (Exception $e) {
            error_log("Erreur configuration email: " . $e->getMessage());
        }
    }

    public function envoyerBilletParEmail(string $destinataire, Billet $billet, string $pdfPath): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($destinataire);

            $infos = $billet->getInfos();
            $this->mailer->Subject = 'Votre billet pour ' . $infos['match']['equipe_domicile'] . 
                                    ' vs ' . $infos['match']['equipe_exterieur'];

            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getTemplateBillet($billet);

            $this->mailer->AltBody = $this->getTemplateBilletTexte($billet);

            if (file_exists($pdfPath)) {
                $this->mailer->addAttachment($pdfPath, 'billet.pdf');
            }

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Erreur envoi email billet: " . $e->getMessage());
            return false;
        }
    }

    public function envoyerConfirmationInscription(string $destinataire, string $nom): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($destinataire);
            $this->mailer->Subject = 'Bienvenue sur la Billetterie Sportive';

            $this->mailer->isHTML(true);
            $this->mailer->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Bienvenue {$nom} !</h2>
                    <p>Votre compte a été créé avec succès.</p>
                    <p>Vous pouvez maintenant acheter des billets pour vos matchs préférés.</p>
                    <p>
                        <a href='http://localhost/projet/auth/login.php' 
                            style='background-color: #4CAF50; color: white; padding: 10px 20px; 
                                    text-decoration: none; border-radius: 5px;'>
                            Se connecter
                        </a>
                    </p>
                    <p>Cordialement,<br>L'équipe Billetterie Sportive</p>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Bienvenue {$nom} ! Votre compte a été créé avec succès.";

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Erreur envoi email inscription: " . $e->getMessage());
            return false;
        }
    }

    public function envoyerNotificationValidation(string $destinataire, string $nomOrganisateur, string $lieuMatch): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($destinataire);
            $this->mailer->Subject = 'Votre match a été validé !';

            $this->mailer->isHTML(true);
            $this->mailer->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Félicitations {$nomOrganisateur} !</h2>
                    <p>Votre match à <strong>{$lieuMatch}</strong> a été validé par l'administrateur.</p>
                    <p>Il est maintenant visible par tous les utilisateurs et les billets peuvent être achetés.</p>
                    <p>
                        <a href='http://localhost/projet/organizer/stats.php' 
                            style='background-color: #2196F3; color: white; padding: 10px 20px; 
                                    text-decoration: none; border-radius: 5px;'>
                            Voir mes statistiques
                        </a>
                    </p>
                    <p>Cordialement,<br>L'équipe Billetterie Sportive</p>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Félicitations {$nomOrganisateur} ! Votre match à {$lieuMatch} a été validé.";

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Erreur envoi notification validation: " . $e->getMessage());
            return false;
        }
    }

    public function envoyerNotificationRefus(string $destinataire, string $nomOrganisateur, string $lieuMatch, string $raison): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($destinataire);
            $this->mailer->Subject = 'Votre match a été refusé';

            $this->mailer->isHTML(true);
            $this->mailer->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Bonjour {$nomOrganisateur},</h2>
                    <p>Nous sommes désolés de vous informer que votre match à <strong>{$lieuMatch}</strong> a été refusé.</p>
                    <p><strong>Raison du refus:</strong></p>
                    <p style='background-color: #f44336; color: white; padding: 15px; border-radius: 5px;'>
                        {$raison}
                    </p>
                    <p>Vous pouvez modifier votre demande et la resoumettre.</p>
                    <p>Cordialement,<br>L'équipe Billetterie Sportive</p>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Bonjour {$nomOrganisateur}, votre match à {$lieuMatch} a été refusé. Raison: {$raison}";

            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Erreur envoi notification refus: " . $e->getMessage());
            return false;
        }
    }

    private function getTemplateBillet(Billet $billet): string {
        $infos = $billet->getInfos();

        return "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .ticket { border: 2px solid #333; padding: 20px; max-width: 600px; margin: 0 auto; }
                    .header { background-color: #4CAF50; color: white; padding: 15px; text-align: center; }
                    .info { margin: 15px 0; }
                    .label { font-weight: bold; color: #666; }
                    .qr-code { text-align: center; margin: 20px 0; font-size: 18px; background: #f0f0f0; padding: 15px; }
                </style>
            </head>
            <body>
                <div class='ticket'>
                    <div class='header'>
                        <h1>BILLET DE MATCH</h1>
                    </div>
                    <div class='info'>
                        <p><span class='label'>Match:</span> {$infos['match']['equipe_domicile']} vs {$infos['match']['equipe_exterieur']}</p>
                        <p><span class='label'>Date:</span> {$infos['match']['date']}</p>
                        <p><span class='label'>Lieu:</span> {$infos['match']['lieu']}</p>
                        <p><span class='label'>Catégorie:</span> {$infos['categorie']}</p>
                        <p><span class='label'>Place:</span> {$infos['numero_place']}</p>
                        <p><span class='label'>Prix:</span> {$infos['prix_paye']} DH</p>
                    </div>
                    <div class='qr-code'>
                        <p><strong>Code QR:</strong> {$infos['qr_code']}</p>
                    </div>
                    <p style='text-align: center; color: #666; font-size: 12px;'>
                        Veuillez présenter ce billet à l'entrée du stade
                    </p>
                </div>
            </body>
            </html>
        ";
    }

    private function getTemplateBilletTexte(Billet $billet): string {
        $infos = $billet->getInfos();

        return "
            ========== BILLET DE MATCH ==========
            
            Match: {$infos['match']['equipe_domicile']} vs {$infos['match']['equipe_exterieur']}
            Date: {$infos['match']['date']}
            Lieu: {$infos['match']['lieu']}
            Catégorie: {$infos['categorie']}
                    Place: {$infos['numero_place']}
        Prix: {$infos['prix_paye']} DH
        
        Code QR: {$infos['qr_code']}
        
        Veuillez présenter ce billet à l'entrée du stade
    ";
    }
}