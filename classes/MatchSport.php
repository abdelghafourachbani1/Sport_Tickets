<?php

require_once __DIR__ . '/Equipe.php';
require_once __DIR__ . '/categorie.php';


class MatchSport {
    
    private int $id;
    private Equipe $equipe_domicile;
    private Equipe $equipe_exterieur;
    private DateTime $date;
    private string $lieu;
    private int $duree = 90;
    private int $places_totales;
    private array $categories = [];
    private string $statut = 'en_attente'; 
    private int $organisateur_id;
    private DateTime $date_creation;

    public function __construct(Equipe $dom, Equipe $ext, DateTime $date, string $lieu) {
        $this->equipe_domicile = $dom;
        $this->equipe_exterieur = $ext;
        $this->date = $date;
        $this->lieu = $lieu;
        $this->date_creation = new DateTime();
    }

    public function ajouterCategorie(Categorie $categorie): void {
        if (count($this->categories) < 3) {
            $this->categories[] = $categorie;
        } else {
            throw new Exception("Maximum 3 catégories autorisées par match.");
        }
    }

    public function getCategories(): array {
        return $this->categories;
    }

    public function calculerPlacesDisponibles(): int {
        $total = 0;
        foreach ($this->categories as $categorie) {
            $total += $categorie->getPlacesDisponibles();
        }
        return $total;
    }

    public function calculerChiffreAffaires(): float {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT SUM(prix_paye) as total FROM billets WHERE match_id = :matchId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;

        } catch (PDOException $e) {
            error_log("Erreur calcul chiffre d'affaires: " . $e->getMessage());
            return 0;
        }
    }

    public function calculerNoteMoyenne(): float {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT AVG(note) as moyenne FROM commentaires 
                        WHERE match_id = :matchId AND statut = 'validé'";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['moyenne'] ?? 0, 2);

        } catch (PDOException $e) {
            error_log("Erreur calcul note moyenne: " . $e->getMessage());
            return 0;
        }
    }

    public function estTermine(): bool {
        $now = new DateTime();
        $matchEnd = clone $this->date;
        $matchEnd->modify('+' . $this->duree . ' minutes');
        
        return $now > $matchEnd || $this->statut === 'terminé';
    }

    public function valider(): void {
        $this->statut = 'validé';
        $this->update();
    }

    public function refuser(string $raison): void {
        $this->statut = 'refusé';
        $this->update();
    }

    // ============ GETTERS & SETTERS ============

    public function getId(): int {
        return $this->id;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function getEquipeDomicile(): Equipe {
        return $this->equipe_domicile;
    }

    public function getEquipeExterieur(): Equipe {
        return $this->equipe_exterieur;
    }

    public function getDate(): DateTime {
        return $this->date;
    }

    public function setDate(DateTime $date): void {
        $this->date = $date;
    }

    public function getLieu(): string {
        return $this->lieu;
    }

    public function setLieu(string $lieu): void {
        $this->lieu = $lieu;
    }

    public function getDuree(): int {
        return $this->duree;
    }

    public function getPlacesTotales(): int {
        return $this->places_totales;
    }

    public function setPlacesTotales(int $places): void {
        if ($places > 2000) {
            throw new Exception("Maximum 2000 places autorisées.");
        }
        $this->places_totales = $places;
    }

    public function getStatut(): string {
        return $this->statut;
    }

    public function setStatut(string $statut): void {
        $this->statut = $statut;
    }

    public function getOrganisateurId(): int {
        return $this->organisateur_id;
    }

    public function setOrganisateurId(int $id): void {
        $this->organisateur_id = $id;
    }

    public function getDateCreation(): DateTime {
        return $this->date_creation;
    }

    public function save(): bool {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "INSERT INTO matches 
                        (equipe_domicile_id, equipe_exterieur_id, date, lieu, duree, 
                        places_totales, statut, organisateur_id, date_creation) 
                        VALUES (:dom_id, :ext_id, :date, :lieu, :duree, :places, 
                                :statut, :org_id, :date_creation)";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':dom_id', $this->equipe_domicile->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':ext_id', $this->equipe_exterieur->getId(), PDO::PARAM_INT);
            $dateString = $this->date->format('Y-m-d H:i:s');
            $stmt->bindParam(':date', $dateString);
            $stmt->bindParam(':lieu', $this->lieu);
            $stmt->bindParam(':duree', $this->duree, PDO::PARAM_INT);
            $stmt->bindParam(':places', $this->places_totales, PDO::PARAM_INT);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':org_id', $this->organisateur_id, PDO::PARAM_INT);
            $dateCreationString = $this->date_creation->format('Y-m-d H:i:s');
            $stmt->bindParam(':date_creation', $dateCreationString);

            if ($stmt->execute()) {
                $this->id = (int) $db->lastInsertId();
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Erreur sauvegarde match: " . $e->getMessage());
            return false;
        }
    }

    public function update(): bool {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE matches SET 
                        date = :date, lieu = :lieu, statut = :statut 
                        WHERE id = :id";

            $stmt = $db->prepare($query);
            $dateString = $this->date->format('Y-m-d H:i:s');
            $stmt->bindParam(':date', $dateString);
            $stmt->bindParam(':lieu', $this->lieu);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Erreur mise à jour match: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?MatchSport {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM matches WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $equipeDom = Equipe::findById($data['equipe_domicile_id']);
            $equipeExt = Equipe::findById($data['equipe_exterieur_id']);

            if (!$equipeDom || !$equipeExt) {
                return null;
            }

            $date = new DateTime($data['date']);
            $match = new MatchSport($equipeDom, $equipeExt, $date, $data['lieu']);
            $match->setId($data['id']);
            $match->setPlacesTotales($data['places_totales']);
            $match->setStatut($data['statut']);
            $match->setOrganisateurId($data['organisateur_id']);

            $categories = Categorie::getByMatch($id);
            foreach ($categories as $catData) {
                $cat = new Categorie($catData['nom'], $catData['prix'], $catData['places_disponibles']);
                $cat->setId($catData['id']);
                $cat->setMatchId($id);
                $match->ajouterCategorie($cat);
            }

            return $match;

        } catch (Exception $e) {
            error_log("Erreur chargement match: " . $e->getMessage());
            return null;
        }
    }

    public static function getAll(array $filtres = []): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT m.*, 
                            e1.nom as equipe_domicile, e1.logo as logo_domicile,
                            e2.nom as equipe_exterieur, e2.logo as logo_exterieur
                    FROM matches m
                    JOIN equipes e1 ON m.equipe_domicile_id = e1.id
                    JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
                    WHERE m.statut = 'validé'";

            $params = [];

            if (!empty($filtres['lieu'])) {
                $query .= " AND m.lieu LIKE :lieu";
                $params[':lieu'] = '%' . $filtres['lieu'] . '%';
            }


            if (!empty($filtres['date_debut'])) {
                $query .= " AND m.date >= :date_debut";
                $params[':date_debut'] = $filtres['date_debut'];
            }

            if (!empty($filtres['date_fin'])) {
                $query .= " AND m.date <= :date_fin";
                $params[':date_fin'] = $filtres['date_fin'];
            }

            if (!empty($filtres['equipe'])) {
                $query .= " AND (e1.nom LIKE :equipe OR e2.nom LIKE :equipe)";
                $params[':equipe'] = '%' . $filtres['equipe'] . '%';
            }

            $query .= " ORDER BY m.date ASC";

            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Erreur récupération matches: " . $e->getMessage());
            return [];
        }
    }

    public function getCommentaires(): array {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT c.*, u.nom, u.prenom 
                        FROM commentaires c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.match_id = :matchId AND c.statut = 'validé'
                        ORDER BY c.date_creation DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération commentaires: " . $e->getMessage());
            return [];
        }
    }

    public function getNombreBilletsVendus(): int
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT COUNT(*) as total FROM billets WHERE match_id = :matchId";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Erreur comptage billets: " . $e->getMessage());
            return 0;
        }
    }
}