<?php

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Ticket.php';
require_once __DIR__ . '/pdfGenerator.php';
require_once __DIR__ . '/emailService.php';
require_once __DIR__ . '/commentaire.php';


class Acheteur extends User {
    
    private array $historique_billets;

    public function __construct(string $nom, string $prenom, string $email, string $password) {
        parent::__construct($nom, $prenom, $email, $password, "actif");
        $this->role = 'admin';
        $this->historique_billets = [];
    }

    public function getPermissions(): array {
        return [
            'consulter_matches',
            'acheter_billets',
            'consulter_historique',
            'laisser_commentaires',
            'generer_pdf',
            'gerer_profil'
        ];
    }

    public function acheterBillet(MatchSport $match, Categorie $categorie, string $place): ?Billet {
        try {
            $db = Database::getInstance()->getConnection();

            $queryCheck = "SELECT COUNT(*) as total FROM billets 
                            WHERE match_id = :matchId AND acheteur_id = :acheteurId";
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->bindValue(':matchId', $match->getId());
            $stmtCheck->bindValue(':acheteurId', $this->id);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] >= 4) {
                throw new Exception("Vous avez atteint la limite de 4 billets pour ce match.");
            }

            $queryPlace = "SELECT COUNT(*) as total FROM billets 
                            WHERE match_id = :matchId AND categorie_id = :categorieId AND numero_place = :place";
            $stmtPlace = $db->prepare($queryPlace);
            $stmtPlace->bindValue(':matchId', $match->getId());
            $stmtPlace->bindValue(':categorieId', $categorie->getId());
            $stmtPlace->bindValue(':place', $place);
            $stmtPlace->execute();
            $placeResult = $stmtPlace->fetch(PDO::FETCH_ASSOC);

            if ($placeResult['total'] > 0) {
                throw new Exception("Cette place est déjà réservée.");
            }

            if ($categorie->getPlacesDisponibles() <= 0) {
                throw new Exception("Il n'y a plus de places disponibles dans cette catégorie.");
            }

            $db->beginTransaction();

            $billet = new Billet($match, $categorie, $place, $this->id);
            
            if ($billet->save()) {
                $categorie->reserverPlace();
                
                $db->commit();
                
                $pdfPath = PdfGenerator::genererBillet($billet);
                $emailService = new EmailService();
                $emailService->envoyerBilletParEmail($this->email, $billet, $pdfPath);

                $this->historique_billets[] = $billet;

                return $billet;
            }

            $db->rollBack();
            return null;

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("Erreur achat billet: " . $e->getMessage());
            throw $e;
        }
    }

    public function consulterHistorique(): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT b.*, 
                                m.date as match_date, m.lieu, m.statut as match_statut,
                                e1.nom as equipe_domicile, e1.logo as logo_domicile,
                                e2.nom as equipe_exterieur, e2.logo as logo_exterieur,
                                c.nom as categorie_nom, c.prix as categorie_prix
                        FROM billets b
                        JOIN matches m ON b.match_id = m.id
                        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                        JOIN categories c ON b.categorie_id = c.id
                        WHERE b.acheteur_id = :acheteurId
                        ORDER BY b.date_achat DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $this->historique_billets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->historique_billets;

        } catch (PDOException $e) {
            error_log("Erreur consultation historique: " . $e->getMessage());
            return [];
        }
    }

    public function genererPDFRecapitulatif(): ?string {
        try {
            require_once __DIR__ . '/pdfGenerator.php';
            
            $billets = $this->consulterHistorique();
            
            if (empty($billets)) {
                return null;
            }

            return PdfGenerator::genererRecapitulatifBillets($billets, $this);

        } catch (Exception $e) {
            error_log("Erreur génération PDF récapitulatif: " . $e->getMessage());
            return null;
        }
    }

    public function laisserCommentaire(MatchSport $match, string $texte, int $note): ?Commentaire {
        try {
            $db = Database::getInstance()->getConnection();

            if (!$match->estTermine()) {
                throw new Exception("Vous ne pouvez commenter qu'après la fin du match.");
            }

            $queryCheck = "SELECT COUNT(*) as total FROM billets 
                            WHERE match_id = :matchId AND acheteur_id = :acheteurId";
            $stmtCheck = $db->prepare($queryCheck);
            $stmtCheck->bindValue(':matchId', $match->getId());
            $stmtCheck->bindValue(':acheteurId', $this->id);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] == 0) {
                throw new Exception("Vous devez avoir acheté un billet pour commenter ce match.");
            }

            $queryExist = "SELECT COUNT(*) as total FROM commentaires 
                            WHERE match_id = :matchId AND user_id = :userId";
            $stmtExist = $db->prepare($queryExist);
            $stmtExist->bindValue(':matchId', $match->getId());
            $stmtExist->bindValue(':userId', $this->id);
            $stmtExist->execute();
            $existResult = $stmtExist->fetch(PDO::FETCH_ASSOC);

            if ($existResult['total'] > 0) {
                throw new Exception("Vous avez déjà commenté ce match.");
            }

            $commentaire = new Commentaire($texte, $note, $this->id, $match->getId());
            
            if ($commentaire->save()) {
                return $commentaire;
            }

            return null;

        } catch (Exception $e) {
            error_log("Erreur création commentaire: " . $e->getMessage());
            throw $e;
        }
    }

    public function peutCommenter(int $matchId): bool {
        try {
            $db = Database::getInstance()->getConnection();

            $queryMatch = "SELECT statut, date FROM matches WHERE id = :matchId";
            $stmtMatch = $db->prepare($queryMatch);
            $stmtMatch->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmtMatch->execute();
            $match = $stmtMatch->fetch(PDO::FETCH_ASSOC);

            if (!$match || $match['statut'] !== 'terminé') {
                return false;
            }

            $queryBillet = "SELECT COUNT(*) as total FROM billets 
                            WHERE match_id = :matchId AND acheteur_id = :acheteurId";
            $stmtBillet = $db->prepare($queryBillet);
            $stmtBillet->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmtBillet->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmtBillet->execute();
            $billet = $stmtBillet->fetch(PDO::FETCH_ASSOC);

            if ($billet['total'] == 0) {
                return false;
            }

            $queryCommentaire = "SELECT COUNT(*) as total FROM commentaires 
                                WHERE match_id = :matchId AND user_id = :userId";
            $stmtCommentaire = $db->prepare($queryCommentaire);
            $stmtCommentaire->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmtCommentaire->bindParam(':userId', $this->id, PDO::PARAM_INT);
            $stmtCommentaire->execute();
            $commentaire = $stmtCommentaire->fetch(PDO::FETCH_ASSOC);

            return $commentaire['total'] == 0;

        } catch (PDOException $e) {
            error_log("Erreur vérification commentaire: " . $e->getMessage());
            return false;
        }
    }

    public function getNombreBilletsMatch(int $matchId): int {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT COUNT(*) as total FROM billets 
                        WHERE match_id = :matchId AND acheteur_id = :acheteurId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total'];

        } catch (PDOException $e) {
            error_log("Erreur comptage billets: " . $e->getMessage());
            return 0;
        }
    }

    public function telechargerBillet(int $billetId): ?string {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM billets WHERE id = :billetId AND acheteur_id = :acheteurId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':billetId', $billetId, PDO::PARAM_INT);
            $stmt->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $billetData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$billetData) {
                return null;
            }

            $billet = Billet::findById($billetId);
            
            if ($billet) {
                return $billet->getPDFPath();
            }

            return null;

        } catch (Exception $e) {
            error_log("Erreur téléchargement billet: " . $e->getMessage());
            return null;
        }
    }

    public function getStatistiques(): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $stats = [];

            $query1 = "SELECT COUNT(*) as total FROM billets WHERE acheteur_id = :acheteurId";
            $stmt1 = $db->prepare($query1);
            $stmt1->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt1->execute();
            $stats['total_billets'] = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];

            $query2 = "SELECT SUM(prix_paye) as total FROM billets WHERE acheteur_id = :acheteurId";
            $stmt2 = $db->prepare($query2);
            $stmt2->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt2->execute();
            $result = $stmt2->fetch(PDO::FETCH_ASSOC);
            $stats['montant_total'] = $result['total'] ?? 0;

            $query3 = "SELECT COUNT(*) as total FROM commentaires WHERE user_id = :userId";
            $stmt3 = $db->prepare($query3);
            $stmt3->bindParam(':userId', $this->id);
            $stmt3->execute();
            $stats['total_commentaires'] = $stmt3->fetch(PDO::FETCH_ASSOC)['total'];
            $query4 = "SELECT COUNT(DISTINCT b.match_id) as total 
                    FROM billets b 
                    JOIN matches m ON b.match_id = m.id 
                    WHERE b.acheteur_id = :acheteurId AND m.date > NOW()";
            $stmt4 = $db->prepare($query4);
            $stmt4->bindParam(':acheteurId', $this->id, PDO::PARAM_INT);
            $stmt4->execute();
            $stats['matches_a_venir'] = $stmt4->fetch(PDO::FETCH_ASSOC)['total'];

            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur statistiques acheteur: " . $e->getMessage());
            return [];
        }
    }
}