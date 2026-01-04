<?php

require_once __DIR__ . '/MatchSport.php';
require_once __DIR__ . '/categorie.php';


class Billet {

    private int $id;
    private string $numero_place;
    private Categorie $categorie;
    private MatchSport $match;
    private int $acheteur_id;
    private DateTime $date_achat;
    private float $prix_paye;
    private string $qr_code;

    public function __construct(MatchSport $match, Categorie $cat, string $place, int $acheteurId)
    {
        $this->match = $match;
        $this->categorie = $cat;
        $this->numero_place = $place;
        $this->acheteur_id = $acheteurId;
        $this->prix_paye = $cat->getPrix();
        $this->date_achat = new DateTime();
        $this->qr_code = $this->genererQRCode();
    }

    public function genererQRCode(): string
    {
        $data = sprintf(
            "%d-%d-%s-%d",
            $this->match->getId(),
            $this->categorie->getId(),
            $this->numero_place,
            time()
        );

        return strtoupper(md5($data));
    }

    public function getPDFPath(): string
    {
        $uploadDir = __DIR__ . '/../uploads/billets/';
        $filename = 'billet_' . $this->id . '_' . $this->qr_code . '.pdf';

        return $uploadDir . $filename;
    }

    public function getInfos(): array
    {
        return [
            'id' => $this->id,
            'numero_place' => $this->numero_place,
            'categorie' => $this->categorie->getNom(),
            'prix_paye' => $this->prix_paye,
            'match' => [
                'equipe_domicile' => $this->match->getEquipeDomicile()->getNom(),
                'equipe_exterieur' => $this->match->getEquipeExterieur()->getNom(),
                'date' => $this->match->getDate()->format('d/m/Y H:i'),
                'lieu' => $this->match->getLieu()
            ],
            'date_achat' => $this->date_achat->format('d/m/Y H:i'),
            'qr_code' => $this->qr_code
        ];
    }

    // ============ GETTERS & SETTERS ============

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNumeroPlace(): string
    {
        return $this->numero_place;
    }

    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }

    public function getMatch(): MatchSport
    {
        return $this->match;
    }

    public function getAcheteurId(): int
    {
        return $this->acheteur_id;
    }

    public function getDateAchat(): DateTime
    {
        return $this->date_achat;
    }

    public function getPrixPaye(): float
    {
        return $this->prix_paye;
    }

    public function getQrCode(): string
    {
        return $this->qr_code;
    }

    public function setQrCode(string $qr_code): void
    {
        $this->qr_code = $qr_code;
    }

    public function save(): bool
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "INSERT INTO billets 
                        (match_id, categorie_id, acheteur_id, numero_place, 
                        prix_paye, date_achat, qr_code) 
                    VALUES (:match_id, :categorie_id, :acheteur_id, :numero_place, 
                            :prix_paye, :date_achat, :qr_code)";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':match_id', $this->match->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':categorie_id', $this->categorie->getId(), PDO::PARAM_INT);
            $stmt->bindParam(':acheteur_id', $this->acheteur_id, PDO::PARAM_INT);
            $stmt->bindParam(':numero_place', $this->numero_place);
            $stmt->bindParam(':prix_paye', $this->prix_paye);
            $dateString = $this->date_achat->format('Y-m-d H:i:s');
            $stmt->bindParam(':date_achat', $dateString);
            $stmt->bindParam(':qr_code', $this->qr_code);

            if ($stmt->execute()) {
                $this->id = (int) $db->lastInsertId();
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde billet: " . $e->getMessage());
            return false;
        }
    }

    public static function findById(int $id): ?Billet
    {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $query = "SELECT * FROM billets WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            // Charger le match et la catégorie
            $match = MatchSport::findById($data['match_id']);
            $categorie = Categorie::findById($data['categorie_id']);

            if (!$match || !$categorie) {
                return null;
            }

            $billet = new Billet($match, $categorie, $data['numero_place'], $data['acheteur_id']);
            $billet->setId($data['id']);
            $billet->prix_paye = $data['prix_paye'];
            $billet->date_achat = new DateTime($data['date_achat']);
            $billet->setQrCode($data['qr_code']);

            return $billet;
        } catch (Exception $e) {
            error_log("Erreur chargement billet: " . $e->getMessage());
            return null;
        }
    }

    public function annuler(): bool
    {
        try {
            // Vérifier que le match n'a pas commencé
            $now = new DateTime();
            if ($this->match->getDate() <= $now) {
                throw new Exception("Impossible d'annuler un billet pour un match déjà commencé.");
            }

            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance()->getConnection();

            $db->beginTransaction();

            // Supprimer le billet
            $query = "DELETE FROM billets WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
            $stmt->execute();

            // Libérer la place
            $this->categorie->libererPlace();

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            error_log("Erreur annulation billet: " . $e->getMessage());
            return false;
        }
    }

    public function estValide(): bool
    {
        return !empty($this->qr_code) && $this->id > 0;
    }
}
