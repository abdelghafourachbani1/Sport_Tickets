<?php

class Equipe {

    private int $id;
    private string $nom;
    private string $logo; 

    public function __construct(string $nom, string $logo){
        $this->nom = $nom;
        $this->logo = $logo;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
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

    public function save(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $checkQuery = "SELECT id FROM equipes WHERE nom = :nom AND logo = :logo LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':nom', $this->nom);
            $checkStmt->bindParam(':logo', $this->logo);
            $checkStmt->execute();
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $this->id = $existing['id'];
                return true;
            }

            $query = "INSERT INTO equipes (nom, logo) VALUES (:nom, :logo)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':logo', $this->logo);

            if ($stmt->execute()) {
                $this->id = (int) $db->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde équipe: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Equipe
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM equipes WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $equipe = new Equipe($data['nom'], $data['logo']);
            $equipe->setId($data['id']);

            return $equipe;
        } catch (PDOException $e) {
            error_log("Erreur chargement équipe: " . $e->getMessage());
            return null;
        }
    }

    public function update(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE equipes SET nom = :nom, logo = :logo WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':logo', $this->logo);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur mise à jour équipe: " . $e->getMessage());
            return false;
        }
    }

    public function delete(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "DELETE FROM equipes WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur suppression équipe: " . $e->getMessage());
            return false;
        }
    }

    public static function getAll(): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM equipes ORDER BY nom ASC";
            $stmt = $db->query($query);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération équipes: " . $e->getMessage());
            return [];
        }
    }
}
