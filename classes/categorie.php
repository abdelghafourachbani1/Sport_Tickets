<?php


class Categorie {

    private int $id;
    private string $nom;
    private float $prix;
    private int $places_disponibles;
    private int $match_id;

    public function __construct(string $nom, float $prix, int $places)
    {
        $this->nom = $nom;
        $this->prix = $prix;
        $this->places_disponibles = $places;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPlacesDisponibles(): int
    {
        return $this->places_disponibles;
    }

    public function reserverPlace(): bool
    {
        if ($this->places_disponibles <= 0) {
            return false;
        }

        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE categories SET places_disponibles = places_disponibles - 1 
                        WHERE id = :id AND places_disponibles > 0";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $this->places_disponibles--;
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur réservation place: " . $e->getMessage());
            return false;
        }
    }

    public function libererPlace(): void
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE categories SET places_disponibles = places_disponibles + 1 
                        WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->places_disponibles++;
            }
        } catch (PDOException $e) {
            error_log("Erreur libération place: " . $e->getMessage());
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function setPrix(float $prix): void
    {
        $this->prix = $prix;
    }

    public function setPlacesDisponibles(int $places): void
    {
        $this->places_disponibles = $places;
    }

    public function getMatchId(): int
    {
        return $this->match_id;
    }

    public function setMatchId(int $matchId): void
    {
        $this->match_id = $matchId;
    }

    public function save(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "INSERT INTO categories (match_id, nom, prix, places_disponibles) 
                        VALUES (:match_id, :nom, :prix, :places_disponibles)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':match_id', $this->match_id, PDO::PARAM_INT);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':prix', $this->prix);
            $stmt->bindParam(':places_disponibles', $this->places_disponibles, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->id = (int) $db->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde catégorie: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Categorie {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM categories WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $categorie = new Categorie($data['nom'], $data['prix'], $data['places_disponibles']);
            $categorie->setId($data['id']);
            $categorie->setMatchId($data['match_id']);

            return $categorie;
        } catch (PDOException $e) {
            error_log("Erreur chargement catégorie: " . $e->getMessage());
            return null;
        }
    }

    public static function getByMatch(int $matchId): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM categories WHERE match_id = :matchId ORDER BY prix DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération catégories: " . $e->getMessage());
            return [];
        }
    }

    public function update(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE categories SET nom = :nom, prix = :prix, 
                        places_disponibles = :places_disponibles WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':prix', $this->prix);
            $stmt->bindParam(':places_disponibles', $this->places_disponibles, PDO::PARAM_INT);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur mise à jour catégorie: " . $e->getMessage());
            return false;
        }
    }

    public function estComplete(): bool
    {
        return $this->places_disponibles <= 0;
    }

    public function getPourcentageVente(): float {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT 
                        (SELECT COUNT(*) FROM billets WHERE categorie_id = :id) as vendues,
                        (SELECT COUNT(*) FROM billets WHERE categorie_id = :id) + places_disponibles as total
                        FROM categories WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data['total'] == 0) {
                return 0;
            }

            return ($data['vendues'] / $data['total']) * 100;
        } catch (PDOException $e) {
            error_log("Erreur calcul pourcentage: " . $e->getMessage());
            return 0;
        }
    }
}
