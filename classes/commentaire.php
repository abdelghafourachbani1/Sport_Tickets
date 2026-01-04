<?php

class Commentaire {

    private int $id;
    private string $texte;
    private int $note; 
    private DateTime $date_creation;
    private int $user_id;
    private int $match_id;
    private string $statut = 'en_attente'; 

    public function __construct(string $texte, int $note, int $userId, int $matchId)
    {
        if ($note < 1 || $note > 5) {
            throw new Exception("La note doit être entre 1 et 5.");
        }

        $this->texte = $texte;
        $this->note = $note;
        $this->user_id = $userId;
        $this->match_id = $matchId;
        $this->date_creation = new DateTime();
    }

    public function valider(): void
    {
        $this->statut = 'validé';
        $this->update();
    }

    public function refuser(): void
    {
        $this->statut = 'refusé';
        $this->update();
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function getTexte(): string
    {
        return $this->texte;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTexte(string $texte): void
    {
        $this->texte = $texte;
    }

    public function setNote(int $note): void
    {
        if ($note < 1 || $note > 5) {
            throw new Exception("La note doit être entre 1 et 5.");
        }
        $this->note = $note;
    }

    public function getDateCreation(): DateTime
    {
        return $this->date_creation;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getMatchId(): int
    {
        return $this->match_id;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): void
    {
        $this->statut = $statut;
    }

    public function save(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "INSERT INTO commentaires 
                        (match_id, user_id, texte, note, date_creation, statut) 
                        VALUES (:match_id, :user_id, :texte, :note, :date_creation, :statut)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':match_id', $this->match_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':texte', $this->texte);
            $stmt->bindParam(':note', $this->note, PDO::PARAM_INT);
            $dateString = $this->date_creation->format('Y-m-d H:i:s');
            $stmt->bindParam(':date_creation', $dateString);
            $stmt->bindParam(':statut', $this->statut);

            if ($stmt->execute()) {
                $this->id = (int) $db->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde commentaire: " . $e->getMessage());
            return false;
        }
    }

    public function update(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "UPDATE commentaires SET 
                        texte = :texte, note = :note, statut = :statut 
                        WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':texte', $this->texte);
            $stmt->bindParam(':note', $this->note, PDO::PARAM_INT);
            $stmt->bindParam(':statut', $this->statut);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur mise à jour commentaire: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Commentaire
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM commentaires WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            $commentaire = new Commentaire(
                $data['texte'],
                $data['note'],
                $data['user_id'],
                $data['match_id']
            );
            $commentaire->setId($data['id']);
            $commentaire->setStatut($data['statut']);
            $commentaire->date_creation = new DateTime($data['date_creation']);

            return $commentaire;
        } catch (Exception $e) {
            error_log("Erreur chargement commentaire: " . $e->getMessage());
            return null;
        }
    }

    public function delete(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "DELETE FROM commentaires WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur suppression commentaire: " . $e->getMessage());
            return false;
        }
    }

    public static function getByMatch(int $matchId, string $statut = 'validé'): array
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT c.*, u.nom, u.prenom, u.email 
                        FROM commentaires c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.match_id = :matchId";

            if (!empty($statut)) {
                $query .= " AND c.statut = :statut";
            }

            $query .= " ORDER BY c.date_creation DESC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':matchId', $matchId, PDO::PARAM_INT);

            if (!empty($statut)) {
                $stmt->bindParam(':statut', $statut);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération commentaires: " . $e->getMessage());
            return [];
        }
    }

    public function getEtoilesHTML(): string
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->note) {
                $html .= '<span class="star filled">★</span>';
            } else {
                $html .= '<span class="star">☆</span>';
            }
        }
        return $html;
    }
}
