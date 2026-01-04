<?php

require_once __DIR__ . '/User.php';
require_once __DIR__ . '../config/database.php';
require_once __DIR__ . '/emailService.php';

class Admin extends User {

    private array $permissions;

    public function __construct(string $nom, string $prenom, string $email, string $password) {
        parent::__construct($nom, $prenom, $email, $password, 'actif');
        $this->role = 'admin';
        $this->permissions = $this->getPermission();
    }

    public function getPermission() {
        return [
            'gerer_utilisateurs',
            'valider_matches',
            'refuser_matches',
            'consulter_statistiques_globales',
            'consulter_tous_commentaires',
            'activer_desactiver_utilisateurs',
            'supprimer_contenu',
            'acces_total'
        ];
    }

    public function gererUtilisateur(int $userId, string $action) {
        try {
            $db = Database::getInstance()->getConnection();

            $status = ($action === 'activer') ? 'actif' : 'inactif';

            $query = "UPDATE users SET status = :status WHERE id = :userId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':userId', $userId);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur gestion utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function validerMatch(int $matchId) {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE matches SET statut = 'validé' WHERE id = :matchId AND statut = 'en_attente'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $this->notifierOrganisateur($matchId, 'validé');
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur validation match: " . $e->getMessage());
            return false;
        }
    }

    public function refuserMatch(int $matchId, string $raison) {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            $query = "UPDATE matches SET statut = 'refusé' WHERE id = :matchId AND statut = 'en_attente'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            $queryRaison = "INSERT INTO refus_matches (match_id, admin_id, raison, date_refus) 
                            VALUES (:matchId, :adminId, :raison, NOW())";
            $stmtRaison = $db->prepare($queryRaison);
            $stmtRaison->bindParam(':matchId', $matchId);
            $stmtRaison->bindParam(':adminId', $this->id);
            $stmtRaison->bindParam(':raison', $raison);
            $stmtRaison->execute();

            $db->commit();

            $this->notifierOrganisateur($matchId, 'refusé', $raison);
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Erreur refus match: " . $e->getMessage());
            return false;
        }
    }

    public function consulterStatistiquesGlobales() {
        try {
            $db = Database::getInstance()->getConnection();

            $stats = [];

            $query1 = "SELECT COUNT(*) as total FROM users WHERE statut = 'actif'";
            $stats['total_utilisateurs'] = $db->query($query1)->fetch(PDO::FETCH_ASSOC)['total'];

            $query2 = "SELECT COUNT(*) as total FROM matches WHERE statut = 'validé'";
            $stats['total_matches'] = $db->query($query2)->fetch(PDO::FETCH_ASSOC)['total'];

            $query3 = "SELECT COUNT(*) as total FROM billets";
            $stats['total_billets_vendus'] = $db->query($query3)->fetch(PDO::FETCH_ASSOC)['total'];

            $query4 = "SELECT SUM(prix_paye) as total FROM billets";
            $result = $db->query($query4)->fetch(PDO::FETCH_ASSOC);
            $stats['chiffre_affaires_total'] = $result['total'] ?? 0;

            $query5 = "SELECT COUNT(*) as total FROM matches WHERE statut = 'en_attente'";
            $stats['matches_en_attente'] = $db->query($query5)->fetch(PDO::FETCH_ASSOC)['total'];

            $query6 = "SELECT * FROM vue_statistiques_matches ORDER BY revenus DESC LIMIT 5";
            $stats['top_matches'] = $db->query($query6)->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur statistiques globales: " . $e->getMessage());
            return [];
        }
    }

    public function consulterTousCommentaires(string $filtre = 'tous') {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT c.*, u.nom, u.prenom, m.lieu, 
                            e1.nom as equipe_domicile, e2.nom as equipe_exterieur
                        FROM commentaires c
                        JOIN users u ON c.user_id = u.id
                        JOIN matches m ON c.match_id = m.id
                        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id";

            if ($filtre !== 'tous') {
                $query .= " WHERE c.statut = :statut";
            }

            $query .= " ORDER BY c.date_creation DESC";

            $stmt = $db->prepare($query);

            if ($filtre !== 'tous') {
                $stmt->bindParam(':statut', $filtre);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur consultation commentaires: " . $e->getMessage());
            return [];
        }
    }

    public function supprimerUtilisateur(int $userId) {
        try {
            $db = Database::getInstance()->getConnection();

            $checkQuery = "SELECT role FROM users WHERE id = :userId";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($user['role'] === 'admin') {
                return false;
            }

            $query = "DELETE FROM users WHERE id = :userId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function getTousUtilisateurs(string $role = '') {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT id, nom, prenom, email, role, statut, date_creation FROM users";

            if (!empty($role)) {
                $query .= " WHERE role = :role";
            }

            $query .= " ORDER BY date_creation DESC";

            $stmt = $db->prepare($query);

            if (!empty($role)) {
                $stmt->bindParam(':role', $role);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération utilisateurs: " . $e->getMessage());
            return [];
        }
    }

    public function modererCommentaire(int $commentaireId, string $action) {
        try {
            $db = Database::getInstance()->getConnection();

            $statut = ($action === 'valider') ? 'validé' : 'refusé';

            $query = "UPDATE commentaires SET statut = :statut WHERE id = :commentaireId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':commentaireId', $commentaireId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur modération commentaire: " . $e->getMessage());
            return false;
        }
    }

    private function notifierOrganisateur(int $matchId, string $statut, string $raison = '') {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT u.email, u.nom, u.prenom, m.lieu 
                        FROM matches m 
                        JOIN users u ON m.organisateur_id = u.id 
                        WHERE m.id = :matchId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $emailService = new EmailService();

                if ($statut === 'validé') {
                    $emailService->envoyerNotificationValidation(
                        $data['email'],
                        $data['nom'] . ' ' . $data['prenom'],
                        $data['lieu']
                    );
                } else {
                    $emailService->envoyerNotificationRefus(
                        $data['email'],
                        $data['nom'] . ' ' . $data['prenom'],
                        $data['lieu'],
                        $raison
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Erreur notification organisateur: " . $e->getMessage());
        }
    }

    public function getMatchesEnAttente() {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT m.*, 
                            e1.nom as equipe_domicile, e1.logo as logo_domicile,
                            e2.nom as equipe_exterieur, e2.logo as logo_exterieur,
                            u.nom as org_nom, u.prenom as org_prenom
                        FROM matches m
                        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                        JOIN users u ON m.organisateur_id = u.id
                        WHERE m.statut = 'en_attente'
                        ORDER BY m.date_creation DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération matches en attente: " . $e->getMessage());
            return [];
        }
    }
}
