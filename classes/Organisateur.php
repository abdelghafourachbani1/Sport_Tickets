<?php
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/MatchSport.php';
require_once __DIR__ . '/Equipe.php';
require_once __DIR__ . '/categorie.php';
require_once __DIR__ . '/../config/database.php';

class Organisateur extends User {
    
    private array $matches_crees;
    private string $statut_validation;

    public function __construct(string $nom, string $prenom, string $email, string $password) {
        parent::__construct($nom, $prenom, $email, $password);
        $this->setRole('organisateur');
        $this->matches_crees = [];
        $this->statut_validation = 'en_attente';
    }

    public function getPermissions(): array {
        return [
            'creer_match',
            'modifier_match',
            'consulter_statistiques',
            'consulter_commentaires',
            'gerer_categories',
            'gerer_profil'
        ];
    }

    public function creerMatch(array $dataMatch): ?MatchSport {
        try {

            if (!$this->validerDonneesMatch($dataMatch)) {
                throw new Exception("Données du match invalides.");
            }

            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            $equipeDomicile = new Equipe($dataMatch['equipe_domicile_nom'], $dataMatch['equipe_domicile_logo']);
            $equipeExterieur = new Equipe($dataMatch['equipe_exterieur_nom'], $dataMatch['equipe_exterieur_logo']);

            if (!$equipeDomicile->save() || !$equipeExterieur->save()) {
                throw new Exception("Erreur lors de la création des équipes.");
            }

            $date = new DateTime($dataMatch['date']);
            $match = new MatchSport($equipeDomicile, $equipeExterieur, $date, $dataMatch['lieu']);
            $match->setOrganisateurId($this->id);
            $match->setPlacesTotales($dataMatch['places_totales']);

            if (!$match->save()) {
                throw new Exception("Erreur lors de la création du match.");
            }

            if (isset($dataMatch['categories']) && is_array($dataMatch['categories'])) {
                $nbCategories = min(count($dataMatch['categories']), 3);
                
                for ($i = 0; $i < $nbCategories; $i++) {
                    $catData = $dataMatch['categories'][$i];
                    $categorie = new Categorie(
                        $catData['nom'],
                        $catData['prix'],
                        $catData['places_disponibles']
                    );
                    $categorie->setMatchId($match->getId());
                    
                    if (!$categorie->save()) {
                        throw new Exception("Erreur lors de la création de la catégorie.");
                    }

                    $match->ajouterCategorie($categorie);
                }
            }

            $db->commit();

            $this->matches_crees[] = $match;

            return $match;

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("Erreur création match: " . $e->getMessage());
            throw $e;
        }
    }

    private function validerDonneesMatch(array $data): bool {
        $champsRequis = [
            'equipe_domicile_nom', 
            'equipe_exterieur_nom',
            'date',
            'lieu',
            'places_totales',
            'categories'
        ];

        foreach ($champsRequis as $champ) {
            if (!isset($data[$champ]) || empty($data[$champ])) {
                return false;
            }
        }

        if ($data['places_totales'] > 2000 || $data['places_totales'] < 1) {
            return false;
        }

        if (!is_array($data['categories']) || count($data['categories']) > 3 || count($data['categories']) < 1) {
            return false;
        }

        $date = new DateTime($data['date']);
        $now = new DateTime();
        if ($date <= $now) {
            return false;
        }

        return true;
    }

    public function consulterStatistiques(int $matchId): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            if (!$this->verifierProprietaireMatch($matchId)) {
                throw new Exception("Vous n'êtes pas le propriétaire de ce match.");
            }

            $stats = [];

            $query = "CALL sp_calculer_revenus_organisateur(:orgId)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $revenusGlobaux = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $stats['revenus_globaux'] = $revenusGlobaux;

            $query2 = "SELECT 
                        COUNT(b.id) as billets_vendus,
                        SUM(b.prix_paye) as chiffre_affaires,
                        AVG(c.note) as note_moyenne
                            FROM matches m
                            LEFT JOIN billets b ON m.id = b.match_id
                            LEFT JOIN commentaires c ON m.id = c.match_id
                            WHERE m.id = :matchId
                            GROUP BY m.id";

            $stmt2 = $db->prepare($query2);
            $stmt2->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt2->execute();
            $statsMatch = $stmt2->fetch(PDO::FETCH_ASSOC);

            $stats['match'] = $statsMatch ?: [
                'billets_vendus' => 0,
                'chiffre_affaires' => 0,
                'note_moyenne' => null
            ];

            $query3 = "SELECT 
                        cat.nom,
                        COUNT(b.id) as billets_vendus,
                        cat.places_disponibles,
                        SUM(b.prix_paye) as revenus
                            FROM categories cat
                            LEFT JOIN billets b ON cat.id = b.categorie_id
                            WHERE cat.match_id = :matchId
                            GROUP BY cat.id";

            $stmt3 = $db->prepare($query3);
            $stmt3->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt3->execute();
            $stats['categories'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (Exception $e) {
            error_log("Erreur statistiques organisateur: " . $e->getMessage());
            throw $e;
        }
    }

    public function consulterCommentaires(int $matchId): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            if (!$this->verifierProprietaireMatch($matchId)) {
                throw new Exception("Vous n'êtes pas le propriétaire de ce match.");
            }

            $query = "SELECT c.*, u.nom, u.prenom, u.email
                        FROM commentaires c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.match_id = :matchId AND c.statut = 'validé'
                        ORDER BY c.date_creation DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Erreur consultation commentaires: " . $e->getMessage());
            throw $e;
        }
    }

    public function modifierMatch(int $matchId, array $newData): bool {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT statut FROM matches WHERE id = :matchId AND organisateur_id = :orgId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) {
                throw new Exception("Match introuvable ou vous n'en êtes pas le propriétaire.");
            }

            if ($match['statut'] !== 'en_attente') {
                throw new Exception("Vous ne pouvez modifier que les matches en attente de validation.");
            }

            $fields = [];
            $params = [':matchId' => $matchId];

            if (isset($newData['date'])) {
                $fields[] = "date = :date";
                $params[':date'] = $newData['date'];
            }

            if (isset($newData['lieu'])) {
                $fields[] = "lieu = :lieu";
                $params[':lieu'] = $newData['lieu'];
            }

            if (empty($fields)) {
                return false;
            }

            $updateQuery = "UPDATE matches SET " . implode(', ', $fields) . " WHERE id = :matchId";
            $updateStmt = $db->prepare($updateQuery);

            foreach ($params as $key => $value) {
                $updateStmt->bindValue($key, $value);
            }

            return $updateStmt->execute();

        } catch (Exception $e) {
            error_log("Erreur modification match: " . $e->getMessage());
            throw $e;
        }
    }

    private function verifierProprietaireMatch(int $matchId): bool {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT COUNT(*) as total FROM matches 
                        WHERE id = :matchId AND organisateur_id = :orgId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;

        } catch (PDOException $e) {
            error_log("Erreur vérification propriétaire: " . $e->getMessage());
            return false;
        }
    }

    public function getMesMatches(string $filtre = 'tous'): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT m.*, 
                            e1.nom as equipe_domicile, e1.logo as logo_domicile,
                            e2.nom as equipe_exterieur, e2.logo as logo_exterieur
                        FROM matches m
                        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                        WHERE m.organisateur_id = :orgId";

            if ($filtre !== 'tous') {
                $query .= " AND m.statut = :statut";
            }

            $query .= " ORDER BY m.date_creation DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':orgId', $this->id, PDO::PARAM_INT);

            if ($filtre !== 'tous') {
                $stmt->bindParam(':statut', $filtre);
            }

            $stmt->execute();
            $this->matches_crees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->matches_crees;

        } catch (PDOException $e) {
            error_log("Erreur récupération matches: " . $e->getMessage());
            return [];
        }
    }

    public function getDashboard(): array {
        try {
            $db = Database::getInstance()->getConnection();

            $dashboard = [];

            $query1 = "SELECT COUNT(*) as total FROM matches WHERE organisateur_id = :orgId";
            $stmt1 = $db->prepare($query1);
            $stmt1->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt1->execute();
            $dashboard['total_matches'] = $stmt1->fetch(PDO::FETCH_ASSOC)['total'];

            $query2 = "SELECT statut, COUNT(*) as total FROM matches 
                        WHERE organisateur_id = :orgId GROUP BY statut";
            $stmt2 = $db->prepare($query2);
            $stmt2->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt2->execute();
            $dashboard['matches_par_statut'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $query3 = "SELECT COUNT(b.id) as total 
                        FROM billets b 
                        JOIN matches m ON b.match_id = m.id 
                        WHERE m.organisateur_id = :orgId";
            $stmt3 = $db->prepare($query3);
            $stmt3->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt3->execute();
            $dashboard['total_billets_vendus'] = $stmt3->fetch(PDO::FETCH_ASSOC)['total'];

            $query4 = "SELECT SUM(b.prix_paye) as total 
                        FROM billets b 
                        JOIN matches m ON b.match_id = m.id 
                        WHERE m.organisateur_id = :orgId";
            $stmt4 = $db->prepare($query4);
            $stmt4->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt4->execute();
            $result = $stmt4->fetch(PDO::FETCH_ASSOC);
            $dashboard['chiffre_affaires_total'] = $result['total'] ?? 0;

            $query5 = "SELECT COUNT(*) as total FROM matches 
                        WHERE organisateur_id = :orgId AND date > NOW() AND statut = 'validé'";
            $stmt5 = $db->prepare($query5);
            $stmt5->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt5->execute();
            $dashboard['matches_a_venir'] = $stmt5->fetch(PDO::FETCH_ASSOC)['total'];

            return $dashboard;

        } catch (PDOException $e) {
            error_log("Erreur dashboard organisateur: " . $e->getMessage());
            return [];
        }
    }

    public function supprimerMatch(int $matchId): bool {
        try {
            $db = Database::getInstance()->getConnection();

            $query = "SELECT statut FROM matches WHERE id = :matchId AND organisateur_id = :orgId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->bindParam(':orgId', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $match = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$match) {
                return false;
            }

            if (!in_array($match['statut'], ['en_attente', 'refusé'])) {
                throw new Exception("Vous ne pouvez supprimer que les matches en attente ou refusés.");
            }

            $deleteQuery = "DELETE FROM matches WHERE id = :matchId";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);

            return $deleteStmt->execute();

        } catch (Exception $e) {
            error_log("Erreur suppression match: " . $e->getMessage());
            throw $e;
        }
    }
}